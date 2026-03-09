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

        return view('admin.contracts.create', compact('contacts', 'organizations', 'projects', 'tracks', 'releases', 'orgContactsMap', 'contractTypes', 'templates'));
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
            'parties' => 'required|array|min:2',
            'parties.*.type' => 'required|in:organization,contact',
            'parties.*.organization_id' => 'nullable|exists:organizations,id',
            'parties.*.contact_id' => 'nullable|exists:contacts,id',
            'parties.*.share' => 'required|numeric|min:0|max:100',
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

        $validated['contract_number'] = Contract::generateNumber();
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

        return view('admin.contracts.edit', compact('contract', 'contacts', 'organizations', 'projects', 'tracks', 'releases', 'orgContactsMap', 'partiesData', 'contractTypes'));
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
            'parties' => 'required|array|min:2',
            'parties.*.type' => 'required|in:organization,contact',
            'parties.*.organization_id' => 'nullable|exists:organizations,id',
            'parties.*.contact_id' => 'nullable|exists:contacts,id',
            'parties.*.share' => 'required|numeric|min:0|max:100',
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

        $document->delete();

        return redirect()->route('admin.contracts.edit', $contract)->with('success', 'Dokument archiviert.');
    }

    public function destroy(Contract $contract)
    {
        $contract->delete();
        return redirect()->route('admin.contracts.index')->with('success', 'Vertrag gelöscht.');
    }
}
