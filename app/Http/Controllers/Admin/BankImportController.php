<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Accounting;
use App\Models\BankImport;
use App\Models\BankTransaction;
use App\Models\BookingRule;
use App\Services\Banking\BankImporter;
use App\Services\Banking\BankStatementException;
use App\Services\Banking\BankTransactionBooker;
use App\Services\Banking\BankTransactionMatcher;
use App\Services\DocumentStorage;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Bankabgleich: statements from the e-banking go into an inbox per
 * accounting, get a suggested counter account, and are booked from there.
 */
class BankImportController extends Controller
{
    public function index(Request $request, Accounting $accounting)
    {
        $accounting->load('accountable');
        $status = in_array($request->input('status'), [BankTransaction::BOOKED, BankTransaction::IGNORED], true)
            ? $request->input('status') : BankTransaction::OPEN;

        $accounts = $accounting->accounts()->where('is_header', false)->orderBy('number')->get();
        $bankAccounts = $accounts->where('type', 'asset')->values();

        $query = $accounting->bankTransactions()->where('status', $status)->with('account')
            ->orderBy('booking_date')->orderBy('id');

        $suggestions = [];
        if ($status === BankTransaction::OPEN) {
            $transactions = $query->get();
            $matcher = new BankTransactionMatcher($accounting);
            foreach ($transactions as $transaction) {
                $suggestions[$transaction->id] = $matcher->suggest($transaction);
            }
        } else {
            $transactions = $query->with('bookings.debitAccount', 'bookings.creditAccount')->paginate(50)->withQueryString();
        }

        $counts = $accounting->bankTransactions()->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');
        $imports = $accounting->bankImports()->with('account')->withCount([
            'transactions as booked_count' => fn ($q) => $q->where('status', BankTransaction::BOOKED),
        ])->latest()->get();
        $rules = $accounting->bookingRules()->orderBy('pattern')->get();

        return view('admin.accountings.bank', [
            'accounting' => $accounting,
            'status' => $status,
            'transactions' => $transactions,
            'suggestions' => $suggestions,
            'counts' => $counts,
            'accounts' => $accounts,
            'bankAccounts' => $bankAccounts,
            'defaultBankAccount' => $this->defaultBankAccount($bankAccounts),
            'imports' => $imports,
            'rules' => $rules,
            'reconciliation' => $this->reconciliation($accounting),
        ]);
    }

    public function import(Request $request, Accounting $accounting, BankImporter $importer)
    {
        $validated = $request->validate([
            'file' => 'required|file|max:10240',
            'account_id' => 'required|integer',
        ], [
            'file.required' => 'Bitte eine CSV-Datei aus dem E-Banking wählen.',
        ]);

        $account = $accounting->accounts()->findOrFail($validated['account_id']);

        try {
            $import = $importer->import($accounting, $account, $request->file('file'));
        } catch (BankStatementException $e) {
            return back()->with('error', $e->getMessage());
        }

        $message = "{$import->rows_new} neue Bewegung(en) eingelesen";
        if ($import->rows_duplicate) $message .= ", {$import->rows_duplicate} waren schon da";
        if ($import->rows_outside) $message .= ", {$import->rows_outside} liegen ausserhalb der Periode";

        return redirect()->route('admin.accountings.bank', $accounting)->with('success', $message . '.');
    }

    /**
     * One row as filled in (transaction=ID), or every suggestion as it stands (all=1).
     * The bulk path recomputes the suggestions on the server, so a long inbox
     * never runs into PHP's limit of form fields.
     */
    public function book(Request $request, Accounting $accounting, BankTransactionBooker $booker)
    {
        $jobs = [];
        if ($request->boolean('all')) {
            $matcher = new BankTransactionMatcher($accounting);
            $open = $accounting->bankTransactions()->where('status', BankTransaction::OPEN)
                ->orderBy('booking_date')->orderBy('id')->get();
            foreach ($open as $transaction) {
                $suggestion = $matcher->suggest($transaction);
                if ($input = BankTransactionMatcher::bookingInput($suggestion)) {
                    $jobs[] = [$transaction, $input];
                }
            }
        } else {
            $transaction = $accounting->bankTransactions()->findOrFail((int) $request->input('transaction'));
            $input = $request->only(['match', 'account_id', 'description', 'remember', 'pattern', 'splits']);
            if (!$request->boolean('split')) unset($input['splits']);
            $jobs[] = [$transaction, $input];
        }

        $booked = 0;
        $errors = [];
        foreach ($jobs as [$transaction, $input]) {
            try {
                $booker->book($transaction, $input);
                $booked++;
            } catch (BankStatementException $e) {
                $errors[] = $transaction->booking_date->format('d.m.Y') . ' ' . $transaction->text . ': ' . $e->getMessage();
            }
        }

        $redirect = redirect()->route('admin.accountings.bank', $accounting);
        if ($errors) {
            $redirect->with('error', implode(' · ', $errors));
        }
        if ($booked) {
            $redirect->with('success', "{$booked} Bewegung(en) gebucht.");
        } elseif (!$errors) {
            $redirect->with('error', 'Keine Bewegung mit Gegenkonto gefunden.');
        }

        return $redirect;
    }

    public function ignore(Request $request, Accounting $accounting, BankTransactionBooker $booker)
    {
        $transaction = $accounting->bankTransactions()->findOrFail((int) $request->input('transaction'));

        try {
            $booker->ignore($transaction);
        } catch (BankStatementException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Bewegung ignoriert.');
    }

    public function reopen(BankTransaction $bankTransaction, BankTransactionBooker $booker)
    {
        try {
            $booker->reopen($bankTransaction);
        } catch (BankStatementException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Bewegung ist wieder offen.');
    }

    public function file(BankImport $bankImport)
    {
        $path = DocumentStorage::path($bankImport->file_path) ?? abort(404);

        return DocumentStorage::privately(response()->download($path, $bankImport->file_name));
    }

    /** A wrong file can go again - as long as nothing of it is booked. */
    public function destroyImport(BankImport $bankImport)
    {
        if ($bankImport->accounting->is_closed) {
            return back()->with('error', 'Die Buchhaltung ist abgeschlossen.');
        }
        if ($bankImport->transactions()->where('status', BankTransaction::BOOKED)->exists()) {
            return back()->with('error', 'Aus diesem Import ist schon etwas gebucht. Zuerst diese Bewegungen wieder öffnen.');
        }

        $bankImport->transactions()->delete();
        DocumentStorage::delete($bankImport->file_path);
        $bankImport->delete();

        return back()->with('success', 'Import entfernt.');
    }

    public function destroyRule(BookingRule $bookingRule)
    {
        $bookingRule->delete();

        return back()->with('success', 'Regel gelöscht.');
    }

    private function defaultBankAccount($bankAccounts): ?Account
    {
        return $bankAccounts->first(fn ($a) => $a->iban)
            ?? $bankAccounts->firstWhere('number', '1003')
            ?? $bankAccounts->first(fn ($a) => str_contains(mb_strtolower($a->name), 'bank'))
            ?? $bankAccounts->first();
    }

    /**
     * Bank balance against book balance, per bank account: before the first
     * imported movement and after the last one.
     */
    private function reconciliation(Accounting $accounting): array
    {
        $result = [];
        foreach ($accounting->bankImports()->with('account')->get()->groupBy('account_id') as $imports) {
            $account = $imports->first()->account;
            $first = $imports->sortBy('statement_from')->first();
            $last = $imports->sortByDesc('statement_to')->first();
            if (!$account || !$first->statement_from || !$last->statement_to) continue;

            $before = $first->statement_from->copy()->subDay();
            $result[] = [
                'account' => $account,
                'opening' => ['date' => $before, 'bank' => $first->opening_balance, 'books' => $this->balanceAt($account, $before)],
                'closing' => ['date' => $last->statement_to, 'bank' => $last->closing_balance, 'books' => $this->balanceAt($account, $last->statement_to)],
                'open' => $accounting->bankTransactions()->where('account_id', $account->id)
                    ->where('status', BankTransaction::OPEN)->count(),
            ];
        }

        return $result;
    }

    private function balanceAt(Account $account, Carbon $date): float
    {
        $day = $date->toDateString();
        // whereDate: SQLite keeps "2025-12-31 00:00:00", which a plain string compare puts after "2025-12-31"
        $debits = (float) $account->debitBookings()->whereDate('booking_date', '<=', $day)->sum('amount');
        $credits = (float) $account->creditBookings()->whereDate('booking_date', '<=', $day)->sum('amount');

        return round((float) $account->opening_balance + $debits - $credits, 2);
    }
}
