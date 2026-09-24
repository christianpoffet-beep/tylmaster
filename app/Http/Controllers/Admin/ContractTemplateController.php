<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesContractLogo;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\ContractType;
use App\Models\Document;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ContractTemplateController extends Controller
{
    use HandlesContractLogo;

    public function index(Request $request)
    {
        $query = ContractTemplate::query();

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($language = $request->input('language')) {
            $query->where('language', $language);
        }

        $sortField = $request->input('sort', 'sort_order');
        $sortDir = $request->input('dir', 'asc');
        $allowedSorts = ['name', 'sort_order', 'contract_type_slug', 'language', 'created_at'];
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

        $territoryPresets = Contract::TERRITORY_PRESETS;

        return view('admin.contract-templates.create', compact('contractTypes', 'contacts', 'organizations', 'orgContactsMap', 'territoryPresets'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:contract_templates,name',
            'contract_type_slug' => 'required|exists:contract_types,slug',
            'language' => 'required|in:de,en,es',
            'default_status' => 'nullable|in:draft,active,expired,terminated',
            'default_terms' => 'nullable|string',
            'default_subject' => 'nullable|string',
            'default_subject_heading' => 'nullable|string|max:120',
            'default_relations_note' => 'nullable|string',
            'default_relations_heading' => 'nullable|string|max:120',
            'default_closing_note' => 'nullable|string',
            'default_sections' => 'nullable|array',
            'default_sections.*.title' => 'nullable|string|max:255',
            'default_sections.*.body' => 'nullable|string',
            'default_sections.*.page_break' => 'nullable|boolean',
            'default_sections.*.numbered' => 'nullable|boolean',
            'default_auto_number_sections' => 'nullable|boolean',
            'default_has_zession' => 'nullable|boolean',
            'default_zession_amount' => 'nullable|numeric|min:0',
            'default_zession_currency' => 'nullable|in:CHF,EUR,USD',
            'default_zession_notes' => 'nullable|string',
            'default_territory' => 'nullable|array',
            'default_territory.*' => 'string|size:2',
            'default_preamble_mode' => 'nullable|in:auto,custom,none',
            'default_preamble_text' => 'nullable|string',
            'default_show_parties_table' => 'nullable|boolean',
            'default_project_ids' => 'nullable|array',
            'default_project_ids.*' => 'exists:projects,id',
            'default_track_ids' => 'nullable|array',
            'default_track_ids.*' => 'exists:tracks,id',
            'default_release_ids' => 'nullable|array',
            'default_release_ids.*' => 'exists:releases,id',
            'document' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,txt,jpg,jpeg,png|max:51200',
            'logo_source' => 'nullable|in:keep,none,artwork,upload',
            'artwork_logo_id' => 'nullable|exists:artwork_logos,id',
            'logo_file' => 'nullable|file|image|max:51200',
            'logo_in_header' => 'nullable|boolean',
            'logo_as_watermark' => 'nullable|boolean',
            'parties' => 'nullable|array',
            'parties.*.type' => 'required|in:organization,contact',
            'parties.*.organization_id' => 'nullable',
            'parties.*.contact_id' => 'nullable',
            'parties.*.share' => 'required|numeric|min:0|max:100',
            'parties.*.role_label' => 'nullable|string|max:120',
            'rights' => 'nullable|array',
            'rights.*.label' => 'required|string|max:255',
            'rights.*.mode' => 'required|in:split,custom',
            'rights.*.splits' => 'nullable|array',
            'rights.*.splits.*' => 'nullable|numeric',
            'rights_label_a' => 'nullable|string|max:50',
            'rights_label_b' => 'nullable|string|max:50',
            'rights_labels' => 'nullable|array',
            'rights_labels.*' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $parties = $request->input('parties', []);
        $parties = array_values(array_filter($parties, fn ($p) => !empty($p['organization_id']) || !empty($p['contact_id'])));

        $sections = $this->normalizeSections($request);

        $rights = $request->input('rights', []);
        $rights = array_values(array_filter($rights, fn ($r) => !empty($r['label'])));

        $rightsLabels = $request->input('rights_labels', []);
        $rightsLabels = is_array($rightsLabels) ? array_values($rightsLabels) : [];
        $hasMeaningfulLabels = count($rightsLabels) > 2
            || count(array_filter($rightsLabels, fn ($l) => $l !== null && $l !== '')) > 0;
        $rightsLabels = $hasMeaningfulLabels ? $rightsLabels : null;

        $attributes = [
            'name' => $request->input('name'),
            'contract_type_slug' => $request->input('contract_type_slug'),
            'language' => $request->input('language', 'de'),
            'default_status' => $request->input('default_status'),
            'default_terms' => $request->input('default_terms'),
            'default_subject' => $request->input('default_subject'),
            'default_subject_heading' => $request->input('default_subject_heading'),
            'default_relations_note' => $request->input('default_relations_note'),
            'default_relations_heading' => $request->input('default_relations_heading'),
            'default_closing_note' => $request->input('default_closing_note'),
            'default_sections' => $sections,
            'default_parties' => !empty($parties) ? $parties : null,
            'rights' => !empty($rights) ? $rights : null,
            'rights_label_a' => $rightsLabels[0] ?? $request->input('rights_label_a'),
            'rights_label_b' => $rightsLabels[1] ?? $request->input('rights_label_b'),
            'rights_labels' => $rightsLabels,
            'sort_order' => $request->input('sort_order', 0),
        ] + $this->defaultsFromRequest($request);

        $this->applyLogoSelection($request, $attributes);

        $template = ContractTemplate::create($attributes);

        $this->storeDocument($request, $template);

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
        $territoryPresets = Contract::TERRITORY_PRESETS;
        $contractTemplate->setRelation('documents', $contractTemplate->documents()->withTrashed()->get());

        return view('admin.contract-templates.edit', compact('contractTemplate', 'contractTypes', 'contacts', 'organizations', 'orgContactsMap', 'partiesData', 'territoryPresets'));
    }

    public function update(Request $request, ContractTemplate $contractTemplate)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:contract_templates,name,' . $contractTemplate->id,
            'contract_type_slug' => 'required|exists:contract_types,slug',
            'language' => 'required|in:de,en,es',
            'default_status' => 'nullable|in:draft,active,expired,terminated',
            'default_terms' => 'nullable|string',
            'default_subject' => 'nullable|string',
            'default_subject_heading' => 'nullable|string|max:120',
            'default_relations_note' => 'nullable|string',
            'default_relations_heading' => 'nullable|string|max:120',
            'default_closing_note' => 'nullable|string',
            'default_sections' => 'nullable|array',
            'default_sections.*.title' => 'nullable|string|max:255',
            'default_sections.*.body' => 'nullable|string',
            'default_sections.*.page_break' => 'nullable|boolean',
            'default_sections.*.numbered' => 'nullable|boolean',
            'default_auto_number_sections' => 'nullable|boolean',
            'default_has_zession' => 'nullable|boolean',
            'default_zession_amount' => 'nullable|numeric|min:0',
            'default_zession_currency' => 'nullable|in:CHF,EUR,USD',
            'default_zession_notes' => 'nullable|string',
            'default_territory' => 'nullable|array',
            'default_territory.*' => 'string|size:2',
            'default_preamble_mode' => 'nullable|in:auto,custom,none',
            'default_preamble_text' => 'nullable|string',
            'default_show_parties_table' => 'nullable|boolean',
            'default_project_ids' => 'nullable|array',
            'default_project_ids.*' => 'exists:projects,id',
            'default_track_ids' => 'nullable|array',
            'default_track_ids.*' => 'exists:tracks,id',
            'default_release_ids' => 'nullable|array',
            'default_release_ids.*' => 'exists:releases,id',
            'document' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,txt,jpg,jpeg,png|max:51200',
            'logo_source' => 'nullable|in:keep,none,artwork,upload',
            'artwork_logo_id' => 'nullable|exists:artwork_logos,id',
            'logo_file' => 'nullable|file|image|max:51200',
            'logo_in_header' => 'nullable|boolean',
            'logo_as_watermark' => 'nullable|boolean',
            'parties' => 'nullable|array',
            'parties.*.type' => 'required|in:organization,contact',
            'parties.*.organization_id' => 'nullable',
            'parties.*.contact_id' => 'nullable',
            'parties.*.share' => 'required|numeric|min:0|max:100',
            'parties.*.role_label' => 'nullable|string|max:120',
            'rights' => 'nullable|array',
            'rights.*.label' => 'required|string|max:255',
            'rights.*.mode' => 'required|in:split,custom',
            'rights.*.splits' => 'nullable|array',
            'rights.*.splits.*' => 'nullable|numeric',
            'rights_label_a' => 'nullable|string|max:50',
            'rights_label_b' => 'nullable|string|max:50',
            'rights_labels' => 'nullable|array',
            'rights_labels.*' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $parties = $request->input('parties', []);
        $parties = array_values(array_filter($parties, fn ($p) => !empty($p['organization_id']) || !empty($p['contact_id'])));

        $sections = $this->normalizeSections($request);

        $rights = $request->input('rights', []);
        $rights = array_values(array_filter($rights, fn ($r) => !empty($r['label'])));

        $rightsLabels = $request->input('rights_labels', []);
        $rightsLabels = is_array($rightsLabels) ? array_values($rightsLabels) : [];
        $hasMeaningfulLabels = count($rightsLabels) > 2
            || count(array_filter($rightsLabels, fn ($l) => $l !== null && $l !== '')) > 0;
        $rightsLabels = $hasMeaningfulLabels ? $rightsLabels : null;

        $attributes = [
            'name' => $request->input('name'),
            'contract_type_slug' => $request->input('contract_type_slug'),
            'language' => $request->input('language', 'de'),
            'default_status' => $request->input('default_status'),
            'default_terms' => $request->input('default_terms'),
            'default_subject' => $request->input('default_subject'),
            'default_subject_heading' => $request->input('default_subject_heading'),
            'default_relations_note' => $request->input('default_relations_note'),
            'default_relations_heading' => $request->input('default_relations_heading'),
            'default_closing_note' => $request->input('default_closing_note'),
            'default_sections' => $sections,
            'default_parties' => !empty($parties) ? $parties : null,
            'rights' => !empty($rights) ? $rights : null,
            'rights_label_a' => $rightsLabels[0] ?? $request->input('rights_label_a'),
            'rights_label_b' => $rightsLabels[1] ?? $request->input('rights_label_b'),
            'rights_labels' => $rightsLabels,
            'sort_order' => $request->input('sort_order', 0),
        ] + $this->defaultsFromRequest($request);

        $this->applyLogoSelection($request, $attributes);

        $contractTemplate->update($attributes);

        $this->storeDocument($request, $contractTemplate);

        return redirect()->route('admin.contract-templates.index')->with('success', 'Vertragsvorlage aktualisiert.');
    }

    public function destroy(ContractTemplate $contractTemplate)
    {
        $contractTemplate->delete();
        return redirect()->route('admin.contract-templates.index')->with('success', 'Vertragsvorlage gelöscht.');
    }

    /**
     * The contract-shaped defaults a template hands to every new contract.
     */
    private function defaultsFromRequest(Request $request): array
    {
        $defaults = [
            'default_auto_number_sections' => $request->boolean('default_auto_number_sections'),
            'default_has_zession' => $request->boolean('default_has_zession'),
            'default_zession_amount' => $request->input('default_zession_amount'),
            'default_zession_currency' => $request->input('default_zession_currency', 'CHF'),
            'default_zession_notes' => $request->input('default_zession_notes'),
            'default_preamble_mode' => $request->input('default_preamble_mode', 'auto'),
            'default_preamble_text' => $request->input('default_preamble_text'),
            'default_show_parties_table' => $request->boolean('default_show_parties_table'),
        ];

        if (!$defaults['default_has_zession']) {
            $defaults['default_zession_amount'] = null;
            $defaults['default_zession_notes'] = null;
        }
        if ($defaults['default_preamble_mode'] !== 'custom') {
            $defaults['default_preamble_text'] = null;
        }

        // Worldwide is stored as the single 'ALL' entry, exactly like a contract.
        $defaults['default_territory'] = $request->boolean('default_territory_worldwide')
            ? ['ALL']
            : ($request->input('default_territory') ?: null);

        // The search fields build their inputs in the browser; a section that
        // never reported in must not wipe what is stored.
        foreach (['project', 'track', 'release'] as $kind) {
            $field = 'default_' . $kind . '_ids';
            if ($request->filled($field . '_submitted')) {
                $defaults[$field] = $request->input($field) ?: null;
            }
        }

        return $defaults;
    }

    /**
     * Store the uploaded default document on the template.
     */
    private function storeDocument(Request $request, ContractTemplate $template): void
    {
        if (!$request->hasFile('document')) {
            return;
        }

        $file = $request->file('document');
        $path = $file->store('contract-templates', 'public');

        $template->documents()->create([
            'title' => $file->getClientOriginalName(),
            'category' => 'contract',
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'notes' => $request->input('document_notes'),
        ]);
    }

    /**
     * Archive one of the template's documents.
     */
    public function archiveDocument(ContractTemplate $contractTemplate, Document $document)
    {
        if ($document->documentable_id !== $contractTemplate->id || $document->documentable_type !== ContractTemplate::class) {
            abort(403);
        }

        $document->delete();

        return redirect()->route('admin.contract-templates.edit', $contractTemplate)->with('success', 'Dokument archiviert.');
    }

    /**
     * Drop empty section rows from the editor payload.
     */
    private function normalizeSections(Request $request): ?array
    {
        $sections = $request->input('default_sections', []);
        $sections = is_array($sections) ? array_values($sections) : [];

        $sections = array_values(array_filter(
            array_map(fn ($s) => [
                'title' => trim((string) ($s['title'] ?? '')),
                'body' => rtrim((string) ($s['body'] ?? '')),
                'page_break' => (bool) ($s['page_break'] ?? false),
                'numbered' => (bool) ($s['numbered'] ?? true),
            ], $sections),
            fn ($s) => $s['title'] !== '' || $s['body'] !== ''
        ));

        return $sections !== [] ? $sections : null;
    }

    public function data(ContractTemplate $contractTemplate)
    {
        return response()->json([
            'contract_type_slug' => $contractTemplate->contract_type_slug,
            'default_status' => $contractTemplate->default_status,
            'language' => $contractTemplate->language ?? 'de',
            'default_terms' => $contractTemplate->default_terms,
            'default_subject' => $contractTemplate->default_subject,
            'default_subject_heading' => $contractTemplate->default_subject_heading,
            'default_relations_note' => $contractTemplate->default_relations_note,
            'default_relations_heading' => $contractTemplate->default_relations_heading,
            'default_closing_note' => $contractTemplate->default_closing_note,
            'default_sections' => $contractTemplate->default_sections,
            'default_auto_number_sections' => (bool) $contractTemplate->default_auto_number_sections,
            'default_has_zession' => (bool) $contractTemplate->default_has_zession,
            'default_zession_amount' => $contractTemplate->default_zession_amount,
            'default_zession_currency' => $contractTemplate->default_zession_currency,
            'default_zession_notes' => $contractTemplate->default_zession_notes,
            'default_territory' => $contractTemplate->default_territory ?? [],
            'default_preamble_mode' => $contractTemplate->default_preamble_mode,
            'default_preamble_text' => $contractTemplate->default_preamble_text,
            'default_show_parties_table' => (bool) $contractTemplate->default_show_parties_table,
            'default_projects' => $contractTemplate->defaultProjects()
                ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'status' => $p->status ?? null])->values(),
            'default_tracks' => $contractTemplate->defaultTracks()
                ->map(fn ($t) => ['id' => $t->id, 'title' => $t->display_title ?? $t->title, 'status' => $t->status])->values(),
            'default_releases' => $contractTemplate->defaultReleases()
                ->map(fn ($r) => ['id' => $r->id, 'title' => $r->title, 'status' => $r->status ?? null])->values(),
            'logo' => $contractTemplate->logo_path ? [
                'url' => Storage::disk('public')->url($contractTemplate->logo_path),
                'label' => $contractTemplate->name,
                'in_header' => (bool) $contractTemplate->logo_in_header,
                'as_watermark' => (bool) $contractTemplate->logo_as_watermark,
            ] : null,
            'document_count' => $contractTemplate->documents()->count(),
            'default_parties' => $contractTemplate->default_parties,
            'rights' => $contractTemplate->rights,
            'rights_label_a' => $contractTemplate->rights_label_a,
            'rights_label_b' => $contractTemplate->rights_label_b,
            'rights_labels' => $contractTemplate->rights_labels,
        ]);
    }
}
