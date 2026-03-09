<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Accounting;
use App\Models\Booking;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\InvoiceTemplate;
use App\Models\Organization;
use App\Models\Project;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with('contact', 'organization', 'senderContact', 'senderOrganization', 'project');

        if ($search = $request->input('search')) {
            $query->where('invoice_number', 'like', "%{$search}%");
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        $sortField = $request->input('sort', 'invoice_date');
        $sortDir = $request->input('dir', 'desc');
        $allowedSorts = ['invoice_number', 'amount', 'status', 'type', 'invoice_date', 'due_date', 'created_at'];
        if (!in_array($sortField, $allowedSorts)) $sortField = 'invoice_date';
        if (!in_array($sortDir, ['asc', 'desc'])) $sortDir = 'desc';

        $invoices = $query->orderBy($sortField, $sortDir)->paginate(20)->withQueryString();
        $accountings = Accounting::with('accountable')
            ->withCount('bookings')
            ->withSum('bookings', 'amount')
            ->where('status', 'open')
            ->orderBy('name')
            ->get();
        return view('admin.finances.invoices.index', compact('invoices', 'accountings'));
    }

    public function create()
    {
        $contacts = Contact::orderBy('last_name')->get();
        $organizations = Organization::with('contacts')->orderBy('names')->get();
        $templates = InvoiceTemplate::orderBy('name')->get();
        $accountings = Accounting::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $defaultItems = [['description' => '', 'quantity' => 1, 'unit_price' => 0]];
        $nextInvoiceNumber = Invoice::generateNumber();

        $orgContactsMap = [];
        foreach ($organizations as $org) {
            $orgContactsMap[$org->id] = $org->contacts->map(function ($c) {
                return ['id' => $c->id, 'name' => $c->full_name];
            })->values()->toArray();
        }

        return view('admin.finances.invoices.create', compact(
            'contacts', 'organizations', 'templates', 'accountings', 'projects',
            'defaultItems', 'nextInvoiceNumber', 'orgContactsMap'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'type' => 'required|in:incoming,outgoing',
            'contact_id' => 'nullable|exists:contacts,id',
            'organization_id' => 'nullable|exists:organizations,id',
            'sender_contact_id' => 'nullable|exists:contacts,id',
            'sender_organization_id' => 'nullable|exists:organizations,id',
            'invoice_template_id' => 'nullable|exists:invoice_templates,id',
            'accounting_id' => 'nullable|exists:accountings,id',
            'debit_account_id' => 'nullable|exists:accounts,id',
            'credit_account_id' => 'nullable|exists:accounts,id',
            'project_id' => 'nullable|exists:projects,id',
            'currency' => 'required|in:CHF,EUR,USD',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'status' => 'required|in:open,paid,overdue',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        // Clear account fields if no accounting selected
        if (empty($validated['accounting_id'])) {
            $validated['debit_account_id'] = null;
            $validated['credit_account_id'] = null;
        }

        // Clear recipient fields based on recipient_type
        $recipientType = $request->input('recipient_type', 'contact');
        if ($recipientType === 'contact') {
            $validated['organization_id'] = null;
            if (empty($validated['contact_id'])) {
                return back()->withErrors(['contact_id' => 'Bitte einen Empfänger wählen.'])->withInput();
            }
        } elseif ($recipientType === 'organization') {
            if (empty($validated['organization_id'])) {
                return back()->withErrors(['contact_id' => 'Bitte einen Empfänger wählen.'])->withInput();
            }
            // contact_id can remain (Ansprechperson within org)
        } else {
            return back()->withErrors(['contact_id' => 'Bitte einen Empfänger wählen.'])->withInput();
        }

        // Clear sender fields based on sender_type
        $senderType = $request->input('sender_type', '');
        if ($senderType === 'contact') {
            $validated['sender_organization_id'] = null;
        } elseif ($senderType === 'organization') {
            // sender_contact_id can remain (person within org)
        } else {
            $validated['sender_contact_id'] = null;
            $validated['sender_organization_id'] = null;
        }

        $validated['invoice_number'] = Invoice::generateNumber();
        $validated['amount'] = 0;
        $items = $validated['items'];
        unset($validated['items']);

        $invoice = Invoice::create($validated);

        foreach ($items as $i => $item) {
            $invoice->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'sort_order' => $i,
            ]);
        }

        $invoice->recalculateAmount();
        $this->syncInvoiceBooking($invoice);
        $this->generateAndStorePdf($invoice);

        return redirect()->route('admin.invoices.show', $invoice)->with('success', 'Rechnung erstellt.');
    }

    public function show(Invoice $invoice)
    {
        $invoice->load('contact', 'organization', 'items', 'template', 'accounting', 'senderContact', 'senderOrganization', 'project', 'debitAccount', 'creditAccount');
        return view('admin.finances.invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice)
    {
        $invoice->load('items');
        $contacts = Contact::orderBy('last_name')->get();
        $organizations = Organization::with('contacts')->orderBy('names')->get();
        $templates = InvoiceTemplate::orderBy('name')->get();
        $accountings = Accounting::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $invoiceItems = $invoice->items->map(function ($i) {
            return [
                'description' => $i->description,
                'quantity' => $i->quantity,
                'unit_price' => $i->unit_price,
            ];
        })->values()->toArray() ?: [['description' => '', 'quantity' => 1, 'unit_price' => 0]];

        $orgContactsMap = [];
        foreach ($organizations as $org) {
            $orgContactsMap[$org->id] = $org->contacts->map(function ($c) {
                return ['id' => $c->id, 'name' => $c->full_name];
            })->values()->toArray();
        }

        return view('admin.finances.invoices.edit', compact(
            'invoice', 'contacts', 'organizations', 'templates', 'accountings',
            'projects', 'invoiceItems', 'orgContactsMap'
        ));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $oldAccountingId = $invoice->accounting_id;

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'type' => 'required|in:incoming,outgoing',
            'contact_id' => 'nullable|exists:contacts,id',
            'organization_id' => 'nullable|exists:organizations,id',
            'sender_contact_id' => 'nullable|exists:contacts,id',
            'sender_organization_id' => 'nullable|exists:organizations,id',
            'invoice_template_id' => 'nullable|exists:invoice_templates,id',
            'accounting_id' => 'nullable|exists:accountings,id',
            'debit_account_id' => 'nullable|exists:accounts,id',
            'credit_account_id' => 'nullable|exists:accounts,id',
            'project_id' => 'nullable|exists:projects,id',
            'currency' => 'required|in:CHF,EUR,USD',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'status' => 'required|in:open,paid,overdue',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        // Clear account fields if no accounting selected
        if (empty($validated['accounting_id'])) {
            $validated['debit_account_id'] = null;
            $validated['credit_account_id'] = null;
        }

        // Clear recipient fields based on recipient_type
        $recipientType = $request->input('recipient_type', 'contact');
        if ($recipientType === 'contact') {
            $validated['organization_id'] = null;
            if (empty($validated['contact_id'])) {
                return back()->withErrors(['contact_id' => 'Bitte einen Empfänger wählen.'])->withInput();
            }
        } elseif ($recipientType === 'organization') {
            if (empty($validated['organization_id'])) {
                return back()->withErrors(['contact_id' => 'Bitte einen Empfänger wählen.'])->withInput();
            }
            // contact_id can remain (Ansprechperson within org)
        } else {
            return back()->withErrors(['contact_id' => 'Bitte einen Empfänger wählen.'])->withInput();
        }

        // Clear sender fields based on sender_type
        $senderType = $request->input('sender_type', '');
        if ($senderType === 'contact') {
            $validated['sender_organization_id'] = null;
        } elseif ($senderType === 'organization') {
            // sender_contact_id can remain (person within org)
        } else {
            $validated['sender_contact_id'] = null;
            $validated['sender_organization_id'] = null;
        }

        $items = $validated['items'];
        unset($validated['items']);

        $invoice->update($validated);

        $invoice->items()->delete();
        foreach ($items as $i => $item) {
            $invoice->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'sort_order' => $i,
            ]);
        }

        $invoice->recalculateAmount();

        // If accounting changed, delete old bookings
        if ($oldAccountingId && $oldAccountingId != $invoice->accounting_id) {
            Booking::where('invoice_id', $invoice->id)
                ->where('accounting_id', $oldAccountingId)
                ->delete();
        }

        $this->syncInvoiceBooking($invoice);
        $this->generateAndStorePdf($invoice);

        return redirect()->route('admin.invoices.show', $invoice)->with('success', 'Rechnung aktualisiert.');
    }

    public function destroy(Invoice $invoice)
    {
        // Delete linked PDF document(s) from storage and DB
        foreach ($invoice->documents as $doc) {
            Storage::disk('public')->delete($doc->file_path);
            $doc->forceDelete();
        }

        $invoice->items()->delete();
        $invoice->delete();
        return redirect()->route('admin.invoices.index')->with('success', 'Rechnung gelöscht.');
    }

    public function downloadPdf(Invoice $invoice)
    {
        $document = $invoice->documents()->where('category', 'invoice')->first();

        if (!$document || !Storage::disk('public')->exists($document->file_path)) {
            $this->generateAndStorePdf($invoice);
            $document = $invoice->documents()->where('category', 'invoice')->first();
        }

        if (!$document) {
            abort(404, 'PDF konnte nicht generiert werden.');
        }

        $filename = "Rechnung-{$invoice->invoice_number}.pdf";
        return Storage::disk('public')->download($document->file_path, $filename);
    }

    public function markPaid(Request $request, Invoice $invoice)
    {
        $invoice->update(['status' => 'paid']);

        if ($invoice->accounting_id && $invoice->accounting) {
            $accounting = $invoice->accounting;
            if (!$accounting->is_closed) {
                // Check if payment booking already exists
                $existingPayment = Booking::where('invoice_id', $invoice->id)
                    ->where('description', 'like', '%(Eingang)%')
                    ->orWhere(function ($q) use ($invoice) {
                        $q->where('invoice_id', $invoice->id)
                            ->where('description', 'like', '%(Zahlung)%');
                    })
                    ->first();

                if (!$existingPayment) {
                    $accounts = $accounting->accounts()->where('is_header', false)->get();
                    $bankAccount = $this->findAccount($accounts, ['1003', '1002', '1001'], 'asset');

                    if ($invoice->type === 'outgoing') {
                        // Bank an Debitoren (use invoice's debit account as counter)
                        $debitAccount = $bankAccount;
                        $creditAccount = $invoice->debit_account_id
                            ? Account::find($invoice->debit_account_id)
                            : $this->findAccount($accounts, ['1100']);
                    } else {
                        // Kreditoren an Bank (use invoice's credit account as counter)
                        $debitAccount = $invoice->credit_account_id
                            ? Account::find($invoice->credit_account_id)
                            : $this->findAccount($accounts, ['2001']);
                        $creditAccount = $bankAccount;
                    }

                    if ($debitAccount && $creditAccount && $debitAccount->id !== $creditAccount->id) {
                        Booking::create([
                            'accounting_id' => $accounting->id,
                            'booking_date' => now()->toDateString(),
                            'description' => "Rechnung {$invoice->invoice_number}" . ($invoice->type === 'outgoing' ? ' (Eingang)' : ' (Zahlung)'),
                            'debit_account_id' => $debitAccount->id,
                            'credit_account_id' => $creditAccount->id,
                            'amount' => $invoice->amount,
                            'reference' => $invoice->invoice_number,
                            'invoice_id' => $invoice->id,
                            'project_id' => $invoice->project_id,
                            'contact_id' => $invoice->contact_id,
                            'organization_id' => $invoice->organization_id,
                        ]);
                    }
                }
            }
        }

        return back()->with('success', 'Rechnung als bezahlt markiert.');
    }

    public function templateData(InvoiceTemplate $invoiceTemplate)
    {
        $invoiceTemplate->load('items');
        return response()->json([
            'payment_terms_days' => $invoiceTemplate->payment_terms_days,
            'vat_rate' => $invoiceTemplate->vat_rate,
            'items' => $invoiceTemplate->items->map(function ($i) {
                return [
                    'description' => $i->description,
                    'quantity' => (float) $i->quantity,
                    'unit_price' => (float) $i->unit_price,
                ];
            })->values(),
            'sender_contact_id' => $invoiceTemplate->contact_id,
            'sender_organization_id' => $invoiceTemplate->organization_id,
            'recipient_contact_id' => $invoiceTemplate->recipient_contact_id,
            'recipient_organization_id' => $invoiceTemplate->recipient_organization_id,
        ]);
    }

    public function accountingAccounts(Accounting $accounting)
    {
        $accounts = $accounting->accounts()
            ->where('is_header', false)
            ->orderBy('number')
            ->get();

        return response()->json(
            $accounts->map(function ($a) {
                return ['id' => $a->id, 'number' => $a->number, 'name' => $a->name, 'type' => $a->type];
            })->values()
        );
    }

    public function organizationContacts(Organization $organization)
    {
        $contacts = $organization->contacts()->orderBy('last_name')->get();
        return response()->json(
            $contacts->map(function ($c) {
                return ['id' => $c->id, 'name' => $c->full_name];
            })->values()
        );
    }

    private function generateAndStorePdf(Invoice $invoice): void
    {
        if ($invoice->type !== 'outgoing') {
            return;
        }

        ini_set('memory_limit', '512M');
        $invoice->load('contact', 'organization', 'items', 'template', 'senderContact', 'senderOrganization');

        $qrSvg = null;
        if ($invoice->currency === 'CHF') {
            $hasIban = $invoice->hasSender()
                ? !empty($invoice->sender_iban)
                : ($invoice->template && !empty($invoice->template->iban));

            if ($hasIban) {
                $qrSvg = $this->generateQrBill($invoice);
            }
        }

        $pdf = Pdf::loadView('admin.invoices.pdf', [
            'invoice' => $invoice,
            'template' => $invoice->template,
            'qrSvg' => $qrSvg,
        ]);

        $pdf->setPaper('A4');

        // Store PDF in public documents storage
        $storagePath = "documents/invoices/Rechnung-{$invoice->invoice_number}.pdf";
        $fullPath = storage_path("app/public/{$storagePath}");
        $directory = dirname($fullPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        $pdf->save($fullPath);

        $fileSize = filesize($fullPath);

        // Create or update the Document record
        $document = $invoice->documents()->where('category', 'invoice')->first();
        if ($document) {
            // Delete old file if path changed
            if ($document->file_path !== $storagePath) {
                Storage::disk('public')->delete($document->file_path);
            }
            $document->update([
                'title' => "Rechnung-{$invoice->invoice_number}.pdf",
                'file_path' => $storagePath,
                'file_size' => $fileSize,
                'mime_type' => 'application/pdf',
            ]);
        } else {
            $invoice->documents()->create([
                'title' => "Rechnung-{$invoice->invoice_number}.pdf",
                'category' => 'invoice',
                'file_path' => $storagePath,
                'file_size' => $fileSize,
                'mime_type' => 'application/pdf',
            ]);
        }
    }

    private function findAccount($accounts, array $numbers, ?string $type = null)
    {
        foreach ($numbers as $num) {
            $acc = $accounts->where('number', $num)->where('is_header', false)->first();
            if ($acc) return $acc;
        }
        if ($type) {
            return $accounts->where('type', $type)->where('is_header', false)->first();
        }
        return null;
    }

    private function syncInvoiceBooking(Invoice $invoice): void
    {
        // Delete existing invoice booking (not payment bookings)
        Booking::where('invoice_id', $invoice->id)
            ->where('description', 'not like', '%(Eingang)%')
            ->where('description', 'not like', '%(Zahlung)%')
            ->delete();

        if (!$invoice->accounting_id || !$invoice->accounting || $invoice->accounting->is_closed) {
            return;
        }

        if (!$invoice->debit_account_id || !$invoice->credit_account_id) {
            return;
        }

        Booking::create([
            'accounting_id' => $invoice->accounting_id,
            'booking_date' => $invoice->invoice_date->toDateString(),
            'description' => "Rechnung {$invoice->invoice_number}" . ($invoice->type === 'outgoing' ? ' (Forderung)' : ' (Verbindlichkeit)'),
            'debit_account_id' => $invoice->debit_account_id,
            'credit_account_id' => $invoice->credit_account_id,
            'amount' => $invoice->amount,
            'reference' => $invoice->invoice_number,
            'invoice_id' => $invoice->id,
            'project_id' => $invoice->project_id,
            'contact_id' => $invoice->contact_id,
            'organization_id' => $invoice->organization_id,
        ]);
    }

    private function generateQrBill(Invoice $invoice): string
    {
        $tpl = $invoice->template;
        $hasSender = $invoice->hasSender();

        // Creditor (sender) info: use invoice's own sender if set, otherwise template
        $iban = $hasSender ? $invoice->sender_iban : $tpl->iban;
        $billingName = $hasSender ? $invoice->sender_billing_name : $tpl->billing_name;
        $billingAddress = $hasSender ? $invoice->sender_billing_address_line : $tpl->billing_address_line;
        $billingZip = $hasSender ? $invoice->sender_billing_zip : $tpl->billing_zip;
        $billingCity = $hasSender ? $invoice->sender_billing_city : $tpl->billing_city;
        $billingCountry = $hasSender ? $invoice->sender_billing_country : $tpl->billing_country;

        // Recipient (debtor) info
        $entity = $invoice->contact ?? $invoice->organization;
        $recipientName = '';
        $recipientAddress = '';
        $recipientZip = '';
        $recipientCity = '';
        $recipientCountry = 'CH';

        if ($entity) {
            $recipientName = ($entity instanceof Contact) ? $entity->full_name : $entity->primary_name;
            $recipientAddress = $entity->street ?? '';
            $recipientZip = $entity->zip ?? '';
            $recipientCity = $entity->city ?? '';
            $recipientCountry = $entity->country ?? 'CH';
        }

        $amount = number_format($invoice->amount, 2, '.', '');

        $qrData = implode("\n", [
            'SPC',
            '0200',
            '1',
            $iban,
            'S',
            $billingName,
            $billingAddress,
            '',
            $billingZip,
            $billingCity,
            $billingCountry,
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            $amount,
            $invoice->currency,
            'S',
            $recipientName,
            $recipientAddress,
            '',
            $recipientZip,
            $recipientCity,
            $recipientCountry,
            'NON',
            '',
            $invoice->invoice_number,
            'EPD',
        ]);

        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'svgUseCssProperties' => false,
            'eccLevel' => QRCode::ECC_M,
            'addQuietzone' => false,
            'scale' => 3,
        ]);

        return (new QRCode($options))->render($qrData);
    }
}
