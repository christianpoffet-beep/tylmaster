<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'terms' => 'nullable|string',
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
            'rights_label_a' => 'nullable|string|max:50',
            'rights_label_b' => 'nullable|string|max:50',
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
        $validated['rights_label_a'] = $request->input('rights_label_a');
        $validated['rights_label_b'] = $request->input('rights_label_b');

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
        unset($validated['parties'], $validated['project_ids'], $validated['track_ids'], $validated['release_ids']);

        $contract = Contract::create($validated);

        foreach ($parties as $i => $party) {
            $contract->parties()->create([
                'organization_id' => $party['type'] === 'organization' ? ($party['organization_id'] ?? null) : null,
                'contact_id' => $party['contact_id'] ?? null,
                'share' => $party['share'],
                'sort_order' => $i,
            ]);
        }

        $contract->projects()->sync($request->input('project_ids', []));
        $contract->tracks()->sync($request->input('track_ids', []));
        $contract->releases()->sync($request->input('release_ids', []));

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
        $contract->load(['parties.organization', 'parties.contact', 'projects', 'tracks', 'releases']);
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
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'terms' => 'nullable|string',
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
            'rights_label_a' => 'nullable|string|max:50',
            'rights_label_b' => 'nullable|string|max:50',
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
        $validated['rights_label_a'] = $request->input('rights_label_a');
        $validated['rights_label_b'] = $request->input('rights_label_b');

        $validated['has_zession'] = $request->boolean('has_zession');
        if (!$validated['has_zession']) {
            $validated['zession_amount'] = null;
            $validated['zession_notes'] = null;
        }
        if ($request->boolean('territory_worldwide')) {
            $validated['territory'] = ['ALL'];
        }

        $parties = $validated['parties'];
        unset($validated['parties'], $validated['project_ids'], $validated['track_ids'], $validated['release_ids']);

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

        $contract->projects()->sync($request->input('project_ids', []));
        $contract->tracks()->sync($request->input('track_ids', []));
        $contract->releases()->sync($request->input('release_ids', []));

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
        $contract->load(['parties.organization', 'parties.contact', 'projects', 'tracks', 'releases']);

        $contractTypes = ContractType::orderBy('sort_order')->get();
        $typeLabels = $contractTypes->pluck('name', 'slug')->toArray();
        $statusLabels = ['draft' => 'Entwurf', 'active' => 'Aktiv', 'expired' => 'Ausgelaufen', 'terminated' => 'Gekündigt'];

        $pdf = Pdf::loadView('admin.contracts.pdf', compact('contract', 'typeLabels', 'statusLabels'));
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

        $results = $query->orderBy('title')->limit(50)->get()->map(fn ($c) => [
            'id' => $c->id,
            'title' => $c->title,
            'contract_number' => $c->contract_number,
        ]);

        return response()->json($results);
    }
}
