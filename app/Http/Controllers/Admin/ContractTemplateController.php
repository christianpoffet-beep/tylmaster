<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContractTemplate;
use App\Models\ContractType;
use App\Models\Organization;
use Illuminate\Http\Request;

class ContractTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = ContractTemplate::query();

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $sortField = $request->input('sort', 'sort_order');
        $sortDir = $request->input('dir', 'asc');
        $allowedSorts = ['name', 'sort_order', 'contract_type_slug', 'created_at'];
        if (!in_array($sortField, $allowedSorts)) $sortField = 'sort_order';
        if (!in_array($sortDir, ['asc', 'desc'])) $sortDir = 'asc';

        $templates = $query->orderBy($sortField, $sortDir)->paginate(30)->withQueryString();
        $contractTypes = ContractType::orderBy('sort_order')->get();

        return view('admin.contract-templates.index', compact('templates', 'contractTypes'));
    }

    public function create()
    {
        $contractTypes = ContractType::orderBy('sort_order')->get();
        $contacts = Contact::orderBy('last_name')->get();
        $organizations = Organization::with('contacts')->orderBy('names')->get();

        $orgContactsMap = [];
        foreach ($organizations as $org) {
            $orgContactsMap[$org->id] = $org->contacts->map(fn ($c) => ['id' => $c->id, 'name' => $c->full_name])->values()->toArray();
        }

        return view('admin.contract-templates.create', compact('contractTypes', 'contacts', 'organizations', 'orgContactsMap'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:contract_templates,name',
            'contract_type_slug' => 'required|exists:contract_types,slug',
            'default_status' => 'nullable|in:draft,active,expired,terminated',
            'default_terms' => 'nullable|string',
            'parties' => 'nullable|array',
            'parties.*.type' => 'required|in:organization,contact',
            'parties.*.organization_id' => 'nullable',
            'parties.*.contact_id' => 'nullable',
            'parties.*.share' => 'required|numeric|min:0|max:100',
            'rights' => 'nullable|array',
            'rights.*.label' => 'required|string|max:255',
            'rights.*.mode' => 'required|in:split,custom',
            'rights_label_a' => 'nullable|string|max:50',
            'rights_label_b' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $parties = $request->input('parties', []);
        $parties = array_values(array_filter($parties, fn ($p) => !empty($p['organization_id']) || !empty($p['contact_id'])));

        $rights = $request->input('rights', []);
        $rights = array_values(array_filter($rights, fn ($r) => !empty($r['label'])));

        ContractTemplate::create([
            'name' => $request->input('name'),
            'contract_type_slug' => $request->input('contract_type_slug'),
            'default_status' => $request->input('default_status'),
            'default_terms' => $request->input('default_terms'),
            'default_parties' => !empty($parties) ? $parties : null,
            'rights' => !empty($rights) ? $rights : null,
            'rights_label_a' => $request->input('rights_label_a'),
            'rights_label_b' => $request->input('rights_label_b'),
            'sort_order' => $request->input('sort_order', 0),
        ]);

        return redirect()->route('admin.contract-templates.index')->with('success', 'Vertragsvorlage erstellt.');
    }

    public function edit(ContractTemplate $contractTemplate)
    {
        $contractTypes = ContractType::orderBy('sort_order')->get();
        $contacts = Contact::orderBy('last_name')->get();
        $organizations = Organization::with('contacts')->orderBy('names')->get();

        $orgContactsMap = [];
        foreach ($organizations as $org) {
            $orgContactsMap[$org->id] = $org->contacts->map(fn ($c) => ['id' => $c->id, 'name' => $c->full_name])->values()->toArray();
        }

        $partiesData = $contractTemplate->default_parties ?? [];

        return view('admin.contract-templates.edit', compact('contractTemplate', 'contractTypes', 'contacts', 'organizations', 'orgContactsMap', 'partiesData'));
    }

    public function update(Request $request, ContractTemplate $contractTemplate)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:contract_templates,name,' . $contractTemplate->id,
            'contract_type_slug' => 'required|exists:contract_types,slug',
            'default_status' => 'nullable|in:draft,active,expired,terminated',
            'default_terms' => 'nullable|string',
            'parties' => 'nullable|array',
            'parties.*.type' => 'required|in:organization,contact',
            'parties.*.organization_id' => 'nullable',
            'parties.*.contact_id' => 'nullable',
            'parties.*.share' => 'required|numeric|min:0|max:100',
            'rights' => 'nullable|array',
            'rights.*.label' => 'required|string|max:255',
            'rights.*.mode' => 'required|in:split,custom',
            'rights_label_a' => 'nullable|string|max:50',
            'rights_label_b' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $parties = $request->input('parties', []);
        $parties = array_values(array_filter($parties, fn ($p) => !empty($p['organization_id']) || !empty($p['contact_id'])));

        $rights = $request->input('rights', []);
        $rights = array_values(array_filter($rights, fn ($r) => !empty($r['label'])));

        $contractTemplate->update([
            'name' => $request->input('name'),
            'contract_type_slug' => $request->input('contract_type_slug'),
            'default_status' => $request->input('default_status'),
            'default_terms' => $request->input('default_terms'),
            'default_parties' => !empty($parties) ? $parties : null,
            'rights' => !empty($rights) ? $rights : null,
            'rights_label_a' => $request->input('rights_label_a'),
            'rights_label_b' => $request->input('rights_label_b'),
            'sort_order' => $request->input('sort_order', 0),
        ]);

        return redirect()->route('admin.contract-templates.index')->with('success', 'Vertragsvorlage aktualisiert.');
    }

    public function destroy(ContractTemplate $contractTemplate)
    {
        $contractTemplate->delete();
        return redirect()->route('admin.contract-templates.index')->with('success', 'Vertragsvorlage gelöscht.');
    }

    public function data(ContractTemplate $contractTemplate)
    {
        return response()->json([
            'contract_type_slug' => $contractTemplate->contract_type_slug,
            'default_status' => $contractTemplate->default_status,
            'default_terms' => $contractTemplate->default_terms,
            'default_parties' => $contractTemplate->default_parties,
            'rights' => $contractTemplate->rights,
            'rights_label_a' => $contractTemplate->rights_label_a,
            'rights_label_b' => $contractTemplate->rights_label_b,
        ]);
    }
}
