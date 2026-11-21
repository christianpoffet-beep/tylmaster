<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Accounting;
use App\Models\BankTransaction;
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
use App\Services\DocumentStorage;
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
            'qr_code' => 'nullable|image|max:2048',
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

        // QR code upload
        if ($request->hasFile('qr_code')) {
            $validated['qr_code_path'] = $request->file('qr_code')->store('invoices/qr', DocumentStorage::DISK);
        }
        unset($validated['qr_code']);

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
            'qr_code' => 'nullable|image|max:2048',
            'remove_qr_code' => 'nullable|boolean',
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

        // QR code handling
        if ($request->input('remove_qr_code')) {
            DocumentStorage::delete($invoice->qr_code_path);
            $validated['qr_code_path'] = null;
        } elseif ($request->hasFile('qr_code')) {
            DocumentStorage::delete($invoice->qr_code_path);
            $validated['qr_code_path'] = $request->file('qr_code')->store('invoices/qr', DocumentStorage::DISK);
        }
        unset($validated['qr_code'], $validated['remove_qr_code']);

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
            $oldBookings = Booking::where('invoice_id', $invoice->id)->where('accounting_id', $oldAccountingId);
            $bankTransactionIds = (clone $oldBookings)->whereNotNull('bank_transaction_id')->pluck('bank_transaction_id');
            $oldBookings->delete();
            BankTransaction::reopenUnbooked($bankTransactionIds);
        }

        $this->syncInvoiceBooking($invoice);
        $this->generateAndStorePdf($invoice);

        return redirect()->route('admin.invoices.show', $invoice)->with('success', 'Rechnung aktualisiert.');
    }

    public function destroy(Invoice $invoice)
    {
        // Delete linked PDF document(s) from storage and DB
        foreach ($invoice->documents as $doc) {
            $doc->deleteFile();
            $doc->forceDelete();
        }
        DocumentStorage::delete($invoice->qr_code_path);

        // Its bookings go with it (foreign key cascade); bank movements they settled return to the inbox
        $bankTransactionIds = $invoice->bookings()->whereNotNull('bank_transaction_id')->pluck('bank_transaction_id');

        $invoice->items()->delete();
        $invoice->delete();
        BankTransaction::reopenUnbooked($bankTransactionIds);

        return redirect()->route('admin.invoices.index')->with('success', 'Rechnung gelöscht.');
    }

    public function downloadPdf(Invoice $invoice)
    {
        $document = $invoice->documents()->where('category', 'invoice')->first();

        if (!$document || !$document->absolutePath()) {
            $this->generateAndStorePdf($invoice);
            $document = $invoice->documents()->where('category', 'invoice')->first();
        }

        $path = $document?->absolutePath() ?? abort(404, 'PDF konnte nicht generiert werden.');

        return DocumentStorage::privately(response()->download($path, "Rechnung-{$invoice->invoice_number}.pdf"));
    }

    /** The uploaded QR code (IBAN and amount) - only for logged-in users. */
    public function qrCode(Invoice $invoice)
    {
        $path = DocumentStorage::path($invoice->qr_code_path) ?? abort(404);

        return DocumentStorage::privately(response()->file($path));
    }

    public function markPaid(Request $request, Invoice $invoice)
    {
        // The day the money came in (or went out) - not the day of the click
        $paidAt = $request->validate(['paid_at' => 'nullable|date'])['paid_at'] ?? now()->toDateString();

        $invoice->update(['status' => 'paid']);

        if ($invoice->accounting_id && $invoice->accounting) {
            // Paid after the end of the invoice's year: the payment goes into the next one
            $accounting = $invoice->accounting->forDate($paidAt);
            if (!$accounting->is_closed) {
                $existingPayment = Booking::where('invoice_id', $invoice->id)
                    ->where('invoice_role', Booking::ROLE_PAYMENT)
                    ->first();

                if (!$existingPayment) {
                    $accounts = $accounting->accounts()->where('is_header', false)->get();
                    $bankAccount = $this->findAccount($accounts, ['1003', '1002', '1001'], 'asset');

                    if ($invoice->type === 'outgoing') {
                        // Bank an Debitoren (use invoice's debit account as counter)
                        $debitAccount = $bankAccount;
                        $creditAccount = $accounting->sameAccount($invoice->debit_account_id ? Account::find($invoice->debit_account_id) : null)
                            ?? $this->findAccount($accounts, ['1100']);
                    } else {
                        // Kreditoren an Bank (use invoice's credit account as counter)
                        $debitAccount = $accounting->sameAccount($invoice->credit_account_id ? Account::find($invoice->credit_account_id) : null)
                            ?? $this->findAccount($accounts, ['2001']);
                        $creditAccount = $bankAccount;
                    }

                    if ($debitAccount && $creditAccount && $debitAccount->id !== $creditAccount->id) {
                        Booking::create([
                            'accounting_id' => $accounting->id,
                            'booking_date' => $paidAt,
                            'description' => "Rechnung {$invoice->invoice_number}" . ($invoice->type === 'outgoing' ? ' (Eingang)' : ' (Zahlung)'),
                            'debit_account_id' => $debitAccount->id,
                            'credit_account_id' => $creditAccount->id,
                            'amount' => $invoice->amount,
                            'reference' => $invoice->invoice_number,
                            'invoice_id' => $invoice->id,
                            'invoice_role' => Booking::ROLE_PAYMENT,
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
            'has_qr_code' => !empty($invoiceTemplate->qr_code_path),
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
        $qrImagePath = null;
        $qrReference = null;

        // Check for uploaded QR code image: invoice-level first, then template
        $qrImagePath = DocumentStorage::path($invoice->qr_code_path)
            ?? ($invoice->template ? DocumentStorage::path($invoice->template->qr_code_path) : null);

        // Fallback: generate QR code if no image uploaded
        if (!$qrImagePath && $invoice->currency === 'CHF') {
            $hasIban = $invoice->hasSender()
                ? !empty($invoice->sender_iban)
                : ($invoice->template && !empty($invoice->template->iban));

            if ($hasIban) {
                $qrSvg = $this->generateQrBill($invoice);

                // Determine reference for display
                $rawIban = $invoice->hasSender() ? $invoice->sender_iban : $invoice->template->iban;
                $iban = strtoupper(preg_replace('/\s+/', '', $rawIban));
                if ((str_starts_with($iban, 'CH') || str_starts_with($iban, 'LI'))) {
                    $iid = (int) substr($iban, 4, 5);
                    if ($iid >= 30000 && $iid <= 31999) {
                        $ref = $this->generateQrReference($invoice->invoice_number);
                        $qrReference = implode(' ', str_split($ref, 5));
                    }
                }
            }
        }

        $pdf = Pdf::loadView('admin.invoices.pdf', [
            'invoice' => $invoice,
            'template' => $invoice->template,
            'qrSvg' => $qrSvg,
            'qrImagePath' => $qrImagePath,
            'qrReference' => $qrReference,
        ]);

        $pdf->setPaper('A4');

        // Stored with the confidential documents - the file name is easy to guess
        $storagePath = "documents/invoices/Rechnung-{$invoice->invoice_number}.pdf";
        $fullPath = DocumentStorage::disk()->path($storagePath);
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
                DocumentStorage::delete($document->file_path);
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
        // A closed accounting is not touched - not even to remove the old claim
        if ($invoice->accounting && $invoice->accounting->is_closed) {
            return;
        }

        // The claim is rebuilt from scratch (payments stay as they are)
        $oldClaims = Booking::where('invoice_id', $invoice->id)->where('invoice_role', Booking::ROLE_CLAIM)->get();
        $bankTransactionIds = $oldClaims->pluck('bank_transaction_id')->filter()->unique()->values();
        Booking::whereIn('id', $oldClaims->pluck('id'))->delete();

        if ($invoice->accounting_id && $invoice->accounting && $invoice->debit_account_id && $invoice->credit_account_id) {
            $claim = Booking::create([
                'accounting_id' => $invoice->accounting_id,
                'booking_date' => $invoice->invoice_date->toDateString(),
                'description' => "Rechnung {$invoice->invoice_number}" . ($invoice->type === 'outgoing' ? ' (Forderung)' : ' (Verbindlichkeit)'),
                'debit_account_id' => $invoice->debit_account_id,
                'credit_account_id' => $invoice->credit_account_id,
                'amount' => $invoice->amount,
                'reference' => $invoice->invoice_number,
                'invoice_id' => $invoice->id,
                'invoice_role' => Booking::ROLE_CLAIM,
                'project_id' => $invoice->project_id,
                'contact_id' => $invoice->contact_id,
                'organization_id' => $invoice->organization_id,
            ]);

            // Booked straight onto the bank and matched with the statement: the match stays while it still fits
            $transaction = BankTransaction::whereIn('id', $bankTransactionIds)->where('accounting_id', $claim->accounting_id)->first();
            $bankSide = $transaction && $transaction->is_incoming ? $claim->debit_account_id : $claim->credit_account_id;
            if ($transaction && (int) $bankSide === (int) $transaction->account_id
                && abs($transaction->abs_amount - (float) $claim->amount) < 0.005) {
                $claim->update(['bank_transaction_id' => $transaction->id]);
            }
        }

        BankTransaction::reopenUnbooked($bankTransactionIds);
    }

    private function generateQrBill(Invoice $invoice): string
    {
        $tpl = $invoice->template;
        $hasSender = $invoice->hasSender();

        // Creditor (sender) info: use invoice's own sender if set, otherwise template
        $rawIban = $hasSender ? $invoice->sender_iban : $tpl->iban;
        $billingName = $hasSender ? $invoice->sender_billing_name : $tpl->billing_name;
        $billingAddress = $hasSender ? $invoice->sender_billing_address_line : $tpl->billing_address_line;
        $billingZip = $hasSender ? $invoice->sender_billing_zip : $tpl->billing_zip;
        $billingCity = $hasSender ? $invoice->sender_billing_city : $tpl->billing_city;
        $billingCountry = $hasSender ? $invoice->sender_billing_country : $tpl->billing_country;

        // Normalize IBAN: remove spaces, uppercase
        $iban = strtoupper(preg_replace('/\s+/', '', $rawIban));

        // Detect QR-IBAN (Swiss IID 30000-31999)
        $isQrIban = false;
        if (str_starts_with($iban, 'CH') || str_starts_with($iban, 'LI')) {
            $iid = (int) substr($iban, 4, 5);
            $isQrIban = ($iid >= 30000 && $iid <= 31999);
        }

        // Reference type: QR-IBANs require QRR, regular IBANs use NON
        $refType = 'NON';
        $reference = '';
        $unstructuredMsg = mb_substr($invoice->invoice_number, 0, 140);

        if ($isQrIban) {
            $refType = 'QRR';
            $reference = $this->generateQrReference($invoice->invoice_number);
            $unstructuredMsg = '';
        }

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

        // Truncate fields to SIX spec max lengths
        $billingName = mb_substr($billingName, 0, 70);
        $billingAddress = mb_substr($billingAddress, 0, 70);
        $billingZip = mb_substr($billingZip, 0, 16);
        $billingCity = mb_substr($billingCity, 0, 35);
        $recipientName = mb_substr($recipientName, 0, 70);
        $recipientAddress = mb_substr($recipientAddress, 0, 70);
        $recipientZip = mb_substr($recipientZip, 0, 16);
        $recipientCity = mb_substr($recipientCity, 0, 35);

        // Swiss QR-Bill payload (SIX standard, 31 elements)
        $qrData = implode("\r\n", [
            'SPC',                  // 1: QR Type
            '0200',                 // 2: Version
            '1',                    // 3: Coding (UTF-8)
            $iban,                  // 4: Creditor IBAN
            'S',                    // 5: Creditor address type (structured)
            $billingName,           // 6: Creditor name
            $billingAddress,        // 7: Creditor street
            '',                     // 8: Creditor building number
            $billingZip,            // 9: Creditor postal code
            $billingCity,           // 10: Creditor city
            $billingCountry,        // 11: Creditor country
            '',                     // 12: Ultimate creditor address type (empty in v2)
            '',                     // 13: Ultimate creditor name
            '',                     // 14: Ultimate creditor street
            '',                     // 15: Ultimate creditor building
            '',                     // 16: Ultimate creditor postal code
            '',                     // 17: Ultimate creditor city
            '',                     // 18: Ultimate creditor country
            $amount,                // 19: Amount
            $invoice->currency,     // 20: Currency (CHF or EUR)
            'S',                    // 21: Debtor address type (structured)
            $recipientName,         // 22: Debtor name
            $recipientAddress,      // 23: Debtor street
            '',                     // 24: Debtor building number
            $recipientZip,          // 25: Debtor postal code
            $recipientCity,         // 26: Debtor city
            $recipientCountry,      // 27: Debtor country
            $refType,               // 28: Reference type (QRR, SCOR, NON)
            $reference,             // 29: Reference number
            $unstructuredMsg,       // 30: Unstructured message
            'EPD',                  // 31: Trailer
        ]);

        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'svgUseCssProperties' => false,
            'eccLevel' => QRCode::ECC_M,
            'addQuietzone' => true,
            'quietzoneSize' => 1,
            'scale' => 3,
        ]);

        return (new QRCode($options))->render($qrData);
    }

    /**
     * Generate a 27-digit QR reference number from the invoice number.
     * Format: 26 digits + 1 check digit (mod 10 recursive).
     */
    private function generateQrReference(string $invoiceNumber): string
    {
        // Extract digits from invoice number (e.g., "RE-2026-0001" → "202600001")
        $digits = preg_replace('/\D/', '', $invoiceNumber);

        // Pad to 26 digits
        $digits = str_pad($digits, 26, '0', STR_PAD_LEFT);
        $digits = substr($digits, 0, 26);

        // Calculate mod 10 recursive check digit
        $carry = 0;
        $table = [0, 9, 4, 6, 8, 2, 7, 1, 3, 5];
        for ($i = 0; $i < 26; $i++) {
            $carry = $table[($carry + (int) $digits[$i]) % 10];
        }
        $checkDigit = (10 - $carry) % 10;

        return $digits . $checkDigit;
    }
}
