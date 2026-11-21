<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Accounting;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Expense;
use App\Services\DocumentStorage;
use App\Services\ExpenseBooker;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with(['contact', 'account', 'accounting', 'paidFromAccount', 'booking'])->withCount('documents');

        if ($search = $request->input('search')) {
            $query->where('description', 'like', "%{$search}%");
        }
        if ($accountingId = $request->input('accounting_id')) {
            $query->where('accounting_id', $accountingId);
        }
        match ($request->input('status')) {
            'open' => $query->awaitingBank(),
            'bank' => $query->whereNotNull('booking_id')->whereNull('paid_from_account_id'),
            'direct' => $query->whereNotNull('booking_id')->whereNotNull('paid_from_account_id'),
            'unbooked' => $query->whereNull('booking_id')->where(fn ($q) => $q->whereNull('accounting_id')->orWhereNull('account_id')),
            default => null,
        };

        $sortField = $request->input('sort', 'expense_date');
        $sortDir = $request->input('dir', 'desc');
        $allowedSorts = ['description', 'amount', 'expense_date', 'created_at'];
        if (!in_array($sortField, $allowedSorts)) $sortField = 'expense_date';
        if (!in_array($sortDir, ['asc', 'desc'])) $sortDir = 'desc';

        $expenses = $query->orderBy($sortField, $sortDir)->paginate(20)->withQueryString();
        $accountings = Accounting::orderByDesc('period_start')->orderBy('name')->get();

        return view('admin.finances.expenses.index', compact('expenses', 'accountings'));
    }

    public function create()
    {
        return view('admin.finances.expenses.create', $this->formData(new Expense(['currency' => 'CHF'])));
    }

    public function store(Request $request, ExpenseBooker $booker)
    {
        $validated = $this->validated($request, new Expense());

        $expense = Expense::create($validated);
        $this->storeDocuments($request, $expense);
        $booker->sync($expense);

        return redirect()->route('admin.expenses.index')->with('success', 'Ausgabe erfasst.');
    }

    public function edit(Expense $expense)
    {
        $expense->load('documents', 'booking.bankTransaction', 'accounting');

        return view('admin.finances.expenses.edit', $this->formData($expense));
    }

    public function update(Request $request, Expense $expense, ExpenseBooker $booker)
    {
        $validated = $this->validated($request, $expense);

        $expense->update($validated);
        $this->storeDocuments($request, $expense);
        $booker->sync($expense->fresh());

        return redirect()->route('admin.expenses.index')->with('success', 'Ausgabe aktualisiert.');
    }

    /**
     * The expense goes with its receipts. A booking it made itself (cash) goes
     * too; a bank booking stays - the money did leave the account.
     */
    public function destroy(Expense $expense)
    {
        $booking = $expense->booking;
        if ($booking && !$booking->bank_transaction_id) {
            if ($booking->accounting?->is_closed) {
                return back()->with('error', 'Die Buchung dieser Ausgabe liegt in einer abgeschlossenen Buchhaltung.');
            }
            $booking->delete();
        }

        foreach ($expense->documents as $document) {
            $document->deleteFile();
            $document->forceDelete();
        }
        $expense->delete();

        return redirect()->route('admin.expenses.index')->with('success', 'Ausgabe gelöscht.');
    }

    public function destroyDocument(Expense $expense, Document $document)
    {
        if ($document->documentable_id !== $expense->id || $document->documentable_type !== Expense::class) {
            abort(403);
        }

        $document->deleteFile();
        $document->forceDelete();

        return back()->with('success', 'Beleg gelöscht.');
    }

    private function formData(Expense $expense): array
    {
        return [
            'expense' => $expense,
            'contacts' => Contact::orderBy('last_name')->get(),
            'accountings' => Accounting::with('accounts')->orderByDesc('period_start')->orderBy('name')->get(),
        ];
    }

    private function validated(Request $request, Expense $expense): array
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|in:CHF,EUR,USD',
            'expense_date' => 'required|date',
            'contact_id' => 'nullable|exists:contacts,id',
            'notes' => 'nullable|string',
            'accounting_id' => 'nullable|exists:accountings,id',
            'account_id' => 'nullable|integer',
            'paid_from_account_id' => 'nullable|integer',
            'documents' => 'nullable|array',
            'documents.*' => 'file|mimes:pdf,jpg,jpeg,png,heic,webp,doc,docx,xls,xlsx,csv,txt|max:51200',
        ]);
        unset($validated['documents']);

        $accounting = !empty($validated['accounting_id']) ? Accounting::find($validated['accounting_id']) : null;
        $accountIn = fn ($id) => $id && $accounting
            ? Account::where('accounting_id', $accounting->id)->where('is_header', false)->find($id) : null;

        $validated['account_id'] = $accountIn($validated['account_id'] ?? null)?->id;
        $validated['paid_from_account_id'] = $accountIn($validated['paid_from_account_id'] ?? null)?->id;

        $errors = [];
        if (!empty($request->input('account_id')) && !$validated['account_id']) {
            $errors['account_id'] = 'Dieses Konto gehört nicht zur gewählten Buchhaltung.';
        }
        if ($validated['paid_from_account_id']) {
            if (!$validated['account_id']) {
                $errors['account_id'] = 'Für eine bar/privat bezahlte Ausgabe braucht es ein Aufwandkonto.';
            } elseif ($validated['paid_from_account_id'] === $validated['account_id']) {
                $errors['paid_from_account_id'] = '«Bezahlt mit» und Aufwandkonto müssen verschieden sein.';
            }
            if ($accounting && $validated['currency'] !== $accounting->currency) {
                $errors['currency'] = "Bar/privat bezahlte Ausgaben bitte in {$accounting->currency} erfassen, der Währung der Buchhaltung.";
            }
        }

        // Settled by a bank movement: that booking decides where and when
        $bankBooking = $expense->booking && $expense->booking->bank_transaction_id ? $expense->booking : null;
        if ($bankBooking) {
            if ((int) $validated['accounting_id'] !== (int) $bankBooking->accounting_id) {
                $errors['accounting_id'] = 'Diese Ausgabe ist mit einer Bankbuchung verknüpft und bleibt in deren Buchhaltung.';
            }
            if ($validated['paid_from_account_id']) {
                $errors['paid_from_account_id'] = 'Diese Ausgabe wurde schon über die Bank bezahlt.';
            }
            if (!$validated['account_id']) {
                $errors['account_id'] = 'Die Bankbuchung braucht ein Aufwandkonto.';
            }
        }

        // A closed accounting keeps its bookings as they are
        $closed = collect([$expense->accounting, $accounting])->filter()->first(fn ($a) => $a->is_closed);
        if ($closed && $expense->booking_id) {
            $bookingFields = ['accounting_id', 'account_id', 'paid_from_account_id', 'amount', 'expense_date', 'description'];
            foreach ($bookingFields as $field) {
                $old = $expense->getAttribute($field);
                $old = $old instanceof \DateTimeInterface ? $old->format('Y-m-d') : $old;
                if ((string) $old !== (string) ($validated[$field] ?? '') && !($field === 'amount' && (float) $old === (float) $validated[$field])) {
                    $errors[$field] = "Die Buchhaltung «{$closed->name}» ist abgeschlossen – die Buchung dieser Ausgabe lässt sich nicht mehr ändern.";
                    break;
                }
            }
        } elseif ($closed && $validated['paid_from_account_id']) {
            $errors['accounting_id'] = "Die Buchhaltung «{$closed->name}» ist abgeschlossen.";
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return $validated;
    }

    private function storeDocuments(Request $request, Expense $expense): void
    {
        foreach ($request->file('documents', []) as $file) {
            $expense->documents()->create([
                'title' => $file->getClientOriginalName(),
                'category' => 'invoice',
                'file_path' => $file->store('expenses', DocumentStorage::DISK),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ]);
        }
    }
}
