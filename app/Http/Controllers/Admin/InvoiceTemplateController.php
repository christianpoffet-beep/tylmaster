<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\InvoiceTemplate;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InvoiceTemplateController extends Controller
{
    public function index(Request $request)
    {
        $templates = InvoiceTemplate::with('contact', 'organization')
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.invoice-templates.index', compact('templates'));
    }

    public function create()
    {
        $contacts = Contact::orderBy('last_name')->get();
        $organizations = Organization::with('contacts')->orderBy('names')->get();

        $orgContactsMap = [];
        foreach ($organizations as $org) {
            $orgContactsMap[$org->id] = $org->contacts->map(fn ($c) => ['id' => $c->id, 'name' => $c->full_name])->values()->toArray();
        }

        return view('admin.invoice-templates.create', compact('contacts', 'organizations', 'orgContactsMap'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sender_type' => 'nullable|in:contact,organization',
            'organization_id' => 'nullable|exists:organizations,id',
            'contact_id' => 'nullable|exists:contacts,id',
            'recipient_type' => 'nullable|in:contact,organization',
            'recipient_organization_id' => 'nullable|exists:organizations,id',
            'recipient_contact_id' => 'nullable|exists:contacts,id',
            'logo_source' => 'required|in:avatar,custom',
            'logo' => 'nullable|image|max:2048',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
            'footer_text' => 'nullable|string',
            'payment_terms_days' => 'required|integer|min:0|max:365',
            'template_items' => 'nullable|array',
            'template_items.*.description' => 'required|string|max:255',
            'template_items.*.quantity' => 'required|numeric|min:0.001',
            'template_items.*.unit_price' => 'required|numeric|min:0',
        ]);

        // Sender logic
        $senderType = $validated['sender_type'] ?? '';
        if ($senderType === 'organization') {
            // org selected, contact_id = Ansprechperson (optional)
            if (empty($validated['organization_id'])) {
                $validated['organization_id'] = null;
                $validated['contact_id'] = null;
            }
        } elseif ($senderType === 'contact') {
            $validated['organization_id'] = null;
        } else {
            $validated['organization_id'] = null;
            $validated['contact_id'] = null;
        }

        // Recipient logic
        $recipientType = $validated['recipient_type'] ?? '';
        if ($recipientType === 'organization') {
            if (empty($validated['recipient_organization_id'])) {
                $validated['recipient_organization_id'] = null;
                $validated['recipient_contact_id'] = null;
            }
        } elseif ($recipientType === 'contact') {
            $validated['recipient_organization_id'] = null;
        } else {
            $validated['recipient_organization_id'] = null;
            $validated['recipient_contact_id'] = null;
        }

        $validated['use_avatar_as_logo'] = $validated['logo_source'] === 'avatar';
        if (!$validated['use_avatar_as_logo'] && $request->hasFile('logo')) {
            $validated['logo_path'] = $request->file('logo')->store('invoice-templates', 'public');
        }

        $templateItems = $validated['template_items'] ?? [];
        unset($validated['logo'], $validated['logo_source'], $validated['template_items'], $validated['sender_type'], $validated['recipient_type']);

        $template = InvoiceTemplate::create($validated);

        foreach ($templateItems as $i => $item) {
            $template->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'sort_order' => $i,
            ]);
        }

        return redirect()->route('admin.invoice-templates.index')->with('success', 'Rechnungsvorlage erstellt.');
    }

    public function edit(InvoiceTemplate $invoiceTemplate)
    {
        $invoiceTemplate->load('items');
        $contacts = Contact::orderBy('last_name')->get();
        $organizations = Organization::with('contacts')->orderBy('names')->get();
        $templateItems = $invoiceTemplate->items->map(fn ($i) => [
            'description' => $i->description,
            'quantity' => $i->quantity,
            'unit_price' => $i->unit_price,
        ])->values()->toArray();

        $orgContactsMap = [];
        foreach ($organizations as $org) {
            $orgContactsMap[$org->id] = $org->contacts->map(fn ($c) => ['id' => $c->id, 'name' => $c->full_name])->values()->toArray();
        }

        return view('admin.invoice-templates.edit', [
            'template' => $invoiceTemplate,
            'contacts' => $contacts,
            'organizations' => $organizations,
            'templateItems' => $templateItems,
            'orgContactsMap' => $orgContactsMap,
        ]);
    }

    public function update(Request $request, InvoiceTemplate $invoiceTemplate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sender_type' => 'nullable|in:contact,organization',
            'organization_id' => 'nullable|exists:organizations,id',
            'contact_id' => 'nullable|exists:contacts,id',
            'recipient_type' => 'nullable|in:contact,organization',
            'recipient_organization_id' => 'nullable|exists:organizations,id',
            'recipient_contact_id' => 'nullable|exists:contacts,id',
            'logo_source' => 'required|in:avatar,custom',
            'logo' => 'nullable|image|max:2048',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
            'footer_text' => 'nullable|string',
            'payment_terms_days' => 'required|integer|min:0|max:365',
            'template_items' => 'nullable|array',
            'template_items.*.description' => 'required|string|max:255',
            'template_items.*.quantity' => 'required|numeric|min:0.001',
            'template_items.*.unit_price' => 'required|numeric|min:0',
        ]);

        // Sender logic
        $senderType = $validated['sender_type'] ?? '';
        if ($senderType === 'organization') {
            if (empty($validated['organization_id'])) {
                $validated['organization_id'] = null;
                $validated['contact_id'] = null;
            }
        } elseif ($senderType === 'contact') {
            $validated['organization_id'] = null;
        } else {
            $validated['organization_id'] = null;
            $validated['contact_id'] = null;
        }

        // Recipient logic
        $recipientType = $validated['recipient_type'] ?? '';
        if ($recipientType === 'organization') {
            if (empty($validated['recipient_organization_id'])) {
                $validated['recipient_organization_id'] = null;
                $validated['recipient_contact_id'] = null;
            }
        } elseif ($recipientType === 'contact') {
            $validated['recipient_organization_id'] = null;
        } else {
            $validated['recipient_organization_id'] = null;
            $validated['recipient_contact_id'] = null;
        }

        $validated['use_avatar_as_logo'] = $validated['logo_source'] === 'avatar';
        if ($validated['use_avatar_as_logo']) {
            if ($invoiceTemplate->logo_path) {
                Storage::disk('public')->delete($invoiceTemplate->logo_path);
            }
            $validated['logo_path'] = null;
        } elseif ($request->hasFile('logo')) {
            if ($invoiceTemplate->logo_path) {
                Storage::disk('public')->delete($invoiceTemplate->logo_path);
            }
            $validated['logo_path'] = $request->file('logo')->store('invoice-templates', 'public');
        }

        $templateItems = $validated['template_items'] ?? [];
        unset($validated['logo'], $validated['logo_source'], $validated['template_items'], $validated['sender_type'], $validated['recipient_type']);

        $invoiceTemplate->update($validated);

        $invoiceTemplate->items()->delete();
        foreach ($templateItems as $i => $item) {
            $invoiceTemplate->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'sort_order' => $i,
            ]);
        }

        return redirect()->route('admin.invoice-templates.index')->with('success', 'Rechnungsvorlage aktualisiert.');
    }

    public function destroy(InvoiceTemplate $invoiceTemplate)
    {
        if ($invoiceTemplate->invoices()->exists()) {
            return back()->with('error', 'Vorlage wird von Rechnungen verwendet und kann nicht gelöscht werden.');
        }

        if ($invoiceTemplate->logo_path) {
            Storage::disk('public')->delete($invoiceTemplate->logo_path);
        }

        $invoiceTemplate->delete();

        return redirect()->route('admin.invoice-templates.index')->with('success', 'Rechnungsvorlage gelöscht.');
    }
}
