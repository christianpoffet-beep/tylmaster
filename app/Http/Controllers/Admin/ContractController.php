<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\SyncsFormRelations;
use App\Http\Controllers\Controller;
use App\Models\Artwork;
use App\Models\ArtworkLogo;
use App\Models\Contract;
use App\Models\Contact;
use App\Models\ContractParty;
use App\Models\ContractTemplate;
use App\Models\ContractType;
use App\Models\Document;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Track;
use App\Models\Release;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ContractController extends Controller
{
    use SyncsFormRelations;

    public function index(Request $request)
    {
        $query = Contract::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('contract_number', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        $sortField = $request->input('sort', 'created_at');
        $sortDir = $request->input('dir', 'desc');
        $allowedSorts = ['title', 'contract_number', 'type', 'status', 'start_date', 'end_date', 'created_at'];
        if (!in_array($sortField, $allowedSorts)) $sortField = 'created_at';
        if (!in_array($sortDir, ['asc', 'desc'])) $sortDir = 'desc';

        $contracts = $query->with(['parties.organization', 'parties.contact'])
            ->orderBy($sortField, $sortDir)
            ->paginate(20)
            ->withQueryString();

        $contractTypes = ContractType::orderBy('sort_order')->get();

        return view('admin.contracts.index', compact('contracts', 'contractTypes'));
    }

    public function create()
    {
        $contacts = Contact::orderBy('last_name')->get();
        $organizations = Organization::with('contacts')->orderBy('names')->get();
        $projects = Project::orderBy('name')->get();
        $tracks = Track::orderBy('title')->get();
        $releases = Release::orderBy('title')->get();

        $orgContactsMap = [];
        foreach ($organizations as $org) {
            $orgContactsMap[$org->id] = $org->contacts->map(fn ($c) => ['id' => $c->id, 'name' => $c->full_name])->values()->toArray();
        }

        $contractTypes = ContractType::orderBy('sort_order')->get();
        $templates = ContractTemplate::orderBy('sort_order')->get();

        $territoryPresets = Contract::TERRITORY_PRESETS;

        return view('admin.contracts.create', compact('contacts', 'organizations', 'projects', 'tracks', 'releases', 'orgContactsMap', 'contractTypes', 'templates', 'territoryPresets'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|exists:contract_types,slug',
            'status' => 'required|in:draft,active,expired,terminated',
            'language' => 'nullable|in:de,en,es',
            'start_date' => 'nullable|date',
            'document' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,txt,jpg,jpeg,png|max:51200',
            'logo_source' => 'nullable|in:keep,none,artwork,upload',
            'artwork_logo_id' => 'nullable|exists:artwork_logos,id',
            'logo_file' => 'nullable|file|image|max:51200',
            'logo_in_header' => 'nullable|boolean',
            'logo_as_watermark' => 'nullable|boolean',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'terms' => 'nullable|string',
            'subject' => 'nullable|string',
            'relations_note' => 'nullable|string',
            'has_zession' => 'nullable|boolean',
            'zession_amount' => 'nullable|numeric|min:0',
            'zession_currency' => 'nullable|in:CHF,EUR,USD',
            'zession_notes' => 'nullable|string',
            'territory' => 'nullable|array',
            'territory.*' => 'string|size:2',
            'parties' => 'required|array|min:2',
            'parties.*.type' => 'required|in:organization,contact',
            'parties.*.organization_id' => 'nullable|exists:organizations,id',
            'parties.*.contact_id' => 'nullable|exists:contacts,id',
            'parties.*.share' => 'required|numeric|min:0|max:100',
            'rights' => 'nullable|array',
            'rights.*.label' => 'required|string|max:255',
            'rights.*.mode' => 'required|in:split,custom',
            'rights.*.splits' => 'nullable|array',
            'rights.*.splits.*' => 'nullable|numeric',
            'rights_label_a' => 'nullable|string|max:50',
            'rights_label_b' => 'nullable|string|max:50',
            'rights_labels' => 'nullable|array',
            'rights_labels.*' => 'nullable|string|max:50',
            'project_ids' => 'nullable|array',
            'project_ids.*' => 'exists:projects,id',
            'track_ids' => 'nullable|array',
            'track_ids.*' => 'exists:tracks,id',
            'release_ids' => 'nullable|array',
            'release_ids.*' => 'exists:releases,id',
        ]);

        // Validate shares sum to 100
        $totalShare = collect($validated['parties'])->sum('share');
        if (abs($totalShare - 100) > 0.01) {
            return back()->withInput()->withErrors(['parties' => 'Die Summe der Anteile muss 100% ergeben (aktuell: ' . number_format($totalShare, 2) . '%).']);
        }

        // Process rights
        $rights = $request->input('rights', []);
        $rights = array_values(array_filter($rights, fn ($r) => !empty($r['label'])));
        $validated['rights'] = !empty($rights) ? $rights : null;

        // Party labels for the split (dynamic N-party list)
        $rightsLabels = $request->input('rights_labels', []);
        $rightsLabels = is_array($rightsLabels) ? array_values($rightsLabels) : [];
        $hasMeaningfulLabels = count($rightsLabels) > 2
            || count(array_filter($rightsLabels, fn ($l) => $l !== null && $l !== '')) > 0;
        $validated['rights_labels'] = $hasMeaningfulLabels ? $rightsLabels : null;
        $validated['rights_label_a'] = $rightsLabels[0] ?? $request->input('rights_label_a');
        $validated['rights_label_b'] = $rightsLabels[1] ?? $request->input('rights_label_b');

        $this->handleLogo($request, $validated);

        $validated['contract_number'] = Contract::generateNumber();
        $validated['has_zession'] = $request->boolean('has_zession');
        if (!$validated['has_zession']) {
            $validated['zession_amount'] = null;
            $validated['zession_notes'] = null;
        }
        // Handle territory: 'ALL' for worldwide or array of country codes
        if ($request->boolean('territory_worldwide')) {
            $validated['territory'] = ['ALL'];
        }
        $parties = $validated['parties'];
        unset(
            $validated['parties'], $validated['project_ids'], $validated['track_ids'], $validated['release_ids'],
            $validated['logo_source'], $validated['artwork_logo_id'], $validated['logo_file']
        );

        $contract = Contract::create($validated);

        foreach ($parties as $i => $party) {
            $contract->parties()->create([
                'organization_id' => $party['type'] === 'organization' ? ($party['organization_id'] ?? null) : null,
                'contact_id' => $party['contact_id'] ?? null,
                'share' => $party['share'],
                'sort_order' => $i,
            ]);
        }

        $this->syncSubmitted($request, $contract->projects(), 'project_ids');
        $this->syncSubmitted($request, $contract->tracks(), 'track_ids');
        $this->syncSubmitted($request, $contract->releases(), 'release_ids');

        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $path = $file->store('contracts', 'public');
            $contract->documents()->create([
                'title' => $file->getClientOriginalName(),
                'category' => 'contract',
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'notes' => $request->input('document_notes'),
            ]);
        }

        return redirect()->route('admin.contracts.show', $contract)->with('success', 'Vertrag erstellt.');
    }

    public function show(Contract $contract)
    {
        $contract->load(['parties.organization', 'parties.contact', 'projects', 'tracks.contacts', 'releases']);
        $contract->setRelation('documents', $contract->documents()->withTrashed()->get());
        $contractTypes = ContractType::orderBy('sort_order')->get();
        return view('admin.contracts.show', compact('contract', 'contractTypes'));
    }

    public function edit(Contract $contract)
    {
        $contract->load(['parties.organization', 'parties.contact', 'projects', 'tracks', 'releases']);
        $contract->setRelation('documents', $contract->documents()->withTrashed()->get());

        $contacts = Contact::orderBy('last_name')->get();
        $organizations = Organization::with('contacts')->orderBy('names')->get();
        $projects = Project::orderBy('name')->get();
        $tracks = Track::orderBy('title')->get();
        $releases = Release::orderBy('title')->get();

        $orgContactsMap = [];
        foreach ($organizations as $org) {
            $orgContactsMap[$org->id] = $org->contacts->map(fn ($c) => ['id' => $c->id, 'name' => $c->full_name])->values()->toArray();
        }

        $partiesData = $contract->parties->map(fn ($p) => [
            'type' => $p->organization_id ? 'organization' : 'contact',
            'organization_id' => $p->organization_id ? (string) $p->organization_id : '',
            'contact_id' => $p->contact_id ? (string) $p->contact_id : '',
            'share' => (float) $p->share,
        ])->values()->toArray();

        $contractTypes = ContractType::orderBy('sort_order')->get();

        $territoryPresets = Contract::TERRITORY_PRESETS;

        return view('admin.contracts.edit', compact('contract', 'contacts', 'organizations', 'projects', 'tracks', 'releases', 'orgContactsMap', 'partiesData', 'contractTypes', 'territoryPresets'));
    }

    public function update(Request $request, Contract $contract)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|exists:contract_types,slug',
            'status' => 'required|in:draft,active,expired,terminated',
            'language' => 'nullable|in:de,en,es',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'terms' => 'nullable|string',
            'subject' => 'nullable|string',
            'relations_note' => 'nullable|string',
            'has_zession' => 'nullable|boolean',
            'zession_amount' => 'nullable|numeric|min:0',
            'zession_currency' => 'nullable|in:CHF,EUR,USD',
            'zession_notes' => 'nullable|string',
            'document' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,txt,jpg,jpeg,png|max:51200',
            'logo_source' => 'nullable|in:keep,none,artwork,upload',
            'artwork_logo_id' => 'nullable|exists:artwork_logos,id',
            'logo_file' => 'nullable|file|image|max:51200',
            'logo_in_header' => 'nullable|boolean',
            'logo_as_watermark' => 'nullable|boolean',
            'territory' => 'nullable|array',
            'territory.*' => 'string|size:2',
            'parties' => 'required|array|min:2',
            'parties.*.type' => 'required|in:organization,contact',
            'parties.*.organization_id' => 'nullable|exists:organizations,id',
            'parties.*.contact_id' => 'nullable|exists:contacts,id',
            'parties.*.share' => 'required|numeric|min:0|max:100',
            'rights' => 'nullable|array',
            'rights.*.label' => 'required|string|max:255',
            'rights.*.mode' => 'required|in:split,custom',
            'rights.*.splits' => 'nullable|array',
            'rights.*.splits.*' => 'nullable|numeric',
            'rights_label_a' => 'nullable|string|max:50',
            'rights_label_b' => 'nullable|string|max:50',
            'rights_labels' => 'nullable|array',
            'rights_labels.*' => 'nullable|string|max:50',
            'project_ids' => 'nullable|array',
            'project_ids.*' => 'exists:projects,id',
            'track_ids' => 'nullable|array',
            'track_ids.*' => 'exists:tracks,id',
            'release_ids' => 'nullable|array',
            'release_ids.*' => 'exists:releases,id',
        ]);

        // Validate shares sum to 100
        $totalShare = collect($validated['parties'])->sum('share');
        if (abs($totalShare - 100) > 0.01) {
            return back()->withInput()->withErrors(['parties' => 'Die Summe der Anteile muss 100% ergeben (aktuell: ' . number_format($totalShare, 2) . '%).']);
        }

        // Process rights
        $rights = $request->input('rights', []);
        $rights = array_values(array_filter($rights, fn ($r) => !empty($r['label'])));
        $validated['rights'] = !empty($rights) ? $rights : null;

        // Party labels for the split (dynamic N-party list)
        $rightsLabels = $request->input('rights_labels', []);
        $rightsLabels = is_array($rightsLabels) ? array_values($rightsLabels) : [];
        $hasMeaningfulLabels = count($rightsLabels) > 2
            || count(array_filter($rightsLabels, fn ($l) => $l !== null && $l !== '')) > 0;
        $validated['rights_labels'] = $hasMeaningfulLabels ? $rightsLabels : null;
        $validated['rights_label_a'] = $rightsLabels[0] ?? $request->input('rights_label_a');
        $validated['rights_label_b'] = $rightsLabels[1] ?? $request->input('rights_label_b');

        $this->handleLogo($request, $validated, $contract);

        $validated['has_zession'] = $request->boolean('has_zession');
        if (!$validated['has_zession']) {
            $validated['zession_amount'] = null;
            $validated['zession_notes'] = null;
        }
        if ($request->boolean('territory_worldwide')) {
            $validated['territory'] = ['ALL'];
        }

        $parties = $validated['parties'];
        unset(
            $validated['parties'], $validated['project_ids'], $validated['track_ids'], $validated['release_ids'],
            $validated['logo_source'], $validated['artwork_logo_id'], $validated['logo_file']
        );

        $contract->update($validated);

        $contract->parties()->delete();
        foreach ($parties as $i => $party) {
            $contract->parties()->create([
                'organization_id' => $party['type'] === 'organization' ? ($party['organization_id'] ?? null) : null,
                'contact_id' => $party['contact_id'] ?? null,
                'share' => $party['share'],
                'sort_order' => $i,
            ]);
        }

        $this->syncSubmitted($request, $contract->projects(), 'project_ids');
        $this->syncSubmitted($request, $contract->tracks(), 'track_ids');
        $this->syncSubmitted($request, $contract->releases(), 'release_ids');

        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $path = $file->store('contracts', 'public');
            $contract->documents()->create([
                'title' => $file->getClientOriginalName(),
                'category' => 'contract',
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'notes' => $request->input('document_notes'),
            ]);
        }

        return redirect()->route('admin.contracts.show', $contract)->with('success', 'Vertrag aktualisiert.');
    }

    public function archiveDocument(Contract $contract, Document $document)
    {
        if ($document->documentable_id !== $contract->id || $document->documentable_type !== Contract::class) {
            abort(403);
        }

        if ($document->is_archived) {
            return redirect()->route('admin.contracts.edit', $contract)->with('error', 'Archivierte Dokumente können nicht gelöscht werden.');
        }

        $document->delete();

        return redirect()->route('admin.contracts.edit', $contract)->with('success', 'Dokument archiviert.');
    }

    public function pdf(Request $request, Contract $contract)
    {
        $contract->load(['parties.organization', 'parties.contact', 'projects', 'tracks.contacts', 'releases']);

        $contractTypes = ContractType::orderBy('sort_order')->get();
        $typeLabels = $contractTypes->pluck('name', 'slug')->toArray();

        // Language-specific strings for the PDF
        $t = Contract::pdfStrings($contract->language);
        $headerParty = $contract->header_party;

        // Absolute filesystem path for the logo (dompdf needs a local path)
        $logoAbsolutePath = null;
        if ($contract->logo_path && Storage::disk('public')->exists($contract->logo_path)) {
            $logoAbsolutePath = Storage::disk('public')->path($contract->logo_path);
        }

        $pdf = Pdf::loadView('admin.contracts.pdf', compact('contract', 'typeLabels', 't', 'headerParty', 'logoAbsolutePath'));
        $pdf->setPaper('A4', 'portrait');

        $filename = ($contract->contract_number ?? 'Vertrag') . '_' . now()->format('Ymd_His') . '.pdf';

        // Archive modes: 0 = download only, 1 = archive + download, 2 = archive only
        $archiveMode = (int) $request->input('archive', 0);

        if ($archiveMode >= 1) {
            $path = 'contracts/archived/' . $filename;
            Storage::disk('public')->put($path, $pdf->output());

            $contract->documents()->create([
                'title' => $filename,
                'category' => 'contract',
                'file_path' => $path,
                'file_size' => Storage::disk('public')->size($path),
                'mime_type' => 'application/pdf',
                'notes' => 'Archiviertes Vertrags-PDF (generiert am ' . now()->format('d.m.Y H:i') . ')',
                'is_archived' => true,
            ]);

            if ($archiveMode === 2) {
                return redirect()->route('admin.contracts.show', $contract)->with('success', 'PDF wurde archiviert.');
            }
        }

        return $pdf->download($filename);
    }

    public function search(Request $request)
    {
        $query = Contract::query();

        if ($q = $request->input('q')) {
            $query->where(function ($qb) use ($q) {
                $qb->where('title', 'like', "%{$q}%")
                   ->orWhere('contract_number', 'like', "%{$q}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $results = $query->orderBy('title')->limit(50)->get()->map(fn ($c) => [
            'id' => $c->id,
            'title' => $c->title,
            'contract_number' => $c->contract_number,
            'status' => $c->status,
        ]);

        return response()->json($results);
    }

    /**
     * Search artwork logos for the contract logo picker.
     */
    public function logoSearch(Request $request)
    {
        $q = $request->input('q', '');

        $artworks = Artwork::query()
            ->when($q, fn ($query) => $query->where('title', 'like', "%{$q}%"))
            ->with('logos')
            ->orderBy('title')
            ->limit(30)
            ->get();

        $results = [];
        foreach ($artworks as $artwork) {
            foreach ($artwork->logos as $logo) {
                $results[] = [
                    'id' => $logo->id,
                    'path' => $logo->file_path,
                    'url' => $logo->url,
                    'label' => $artwork->title . ($logo->comment ? ' — ' . $logo->comment : ''),
                ];
            }
        }

        return response()->json($results);
    }

    /**
     * Resolve the contract logo from the request and write logo_* keys into $validated.
     *
     * logo_source: keep | none | artwork | upload
     *  - keep    → leave the existing logo_path untouched (default)
     *  - none    → remove the logo
     *  - artwork → reference an existing ArtworkLogo's file
     *  - upload  → store the freshly uploaded file
     */
    private function handleLogo(Request $request, array &$validated, ?Contract $contract = null): void
    {
        $validated['logo_in_header'] = $request->boolean('logo_in_header');
        $validated['logo_as_watermark'] = $request->boolean('logo_as_watermark');

        $source = $request->input('logo_source', 'keep');

        switch ($source) {
            case 'none':
                $validated['logo_path'] = null;
                break;

            case 'upload':
                if ($request->hasFile('logo_file')) {
                    $validated['logo_path'] = $request->file('logo_file')->store('contracts/logos', 'public');
                } else {
                    unset($validated['logo_path']); // nothing uploaded → keep current
                }
                break;

            case 'artwork':
                $logo = $request->filled('artwork_logo_id')
                    ? ArtworkLogo::find($request->input('artwork_logo_id'))
                    : null;
                if ($logo) {
                    $validated['logo_path'] = $logo->file_path;
                } else {
                    unset($validated['logo_path']);
                }
                break;

            case 'keep':
            default:
                unset($validated['logo_path']); // do not modify
                break;
        }
    }
}
