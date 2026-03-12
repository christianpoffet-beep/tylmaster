<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Contact;
use App\Models\Contract;
use App\Models\Document;
use App\Models\Genre;
use App\Models\Project;
use App\Models\Track;
use App\Models\Release;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OrganizationController extends Controller
{
    public function index(Request $request)
    {
        $query = Organization::query();

        if ($search = $request->input('search')) {
            $query->where('names', 'like', "%{$search}%");
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        $sortField = $request->input('sort', 'created_at');
        $sortDir = $request->input('dir', 'desc');
        $allowedSorts = ['names', 'type', 'created_at'];
        if (!in_array($sortField, $allowedSorts)) $sortField = 'created_at';
        if (!in_array($sortDir, ['asc', 'desc'])) $sortDir = 'desc';

        $organizations = $query->withCount('contacts')->orderBy($sortField, $sortDir)->paginate(20)->withQueryString();
        return view('admin.organizations.index', compact('organizations'));
    }

    public function create()
    {
        $tracks = Track::orderBy('title')->get();
        $releases = Release::orderBy('title')->get();
        $genres = Genre::orderBy('name')->get();
        return view('admin.organizations.create', compact('tracks', 'releases', 'genres'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|exists:organization_types,slug',
            'legal_form' => 'nullable|string|max:50',
            'names' => 'required|array|min:1',
            'names.*' => 'required|string|max:255',
            'biography' => 'nullable|string',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'street' => 'nullable|string|max:255',
            'zip' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:34',
            'bank_name' => 'nullable|string|max:255',
            'bank_zip' => 'nullable|string|max:20',
            'bank_city' => 'nullable|string|max:255',
            'bank_country' => 'nullable|string|max:255',
            'bic' => 'nullable|string|max:11',
            'vat_number' => 'nullable|string|max:50',
            'avatar' => 'nullable|image|max:2048',
            'websites' => 'nullable|array',
            'websites.*' => 'nullable|url|max:500',
            'contact_ids' => 'nullable|array',
            'contact_ids.*' => 'exists:contacts,id',
            'project_ids' => 'nullable|array',
            'project_ids.*' => 'exists:projects,id',
            'track_ids' => 'nullable|array',
            'track_ids.*' => 'exists:tracks,id',
            'release_ids' => 'nullable|array',
            'release_ids.*' => 'exists:releases,id',
            'contract_ids' => 'nullable|array',
            'contract_ids.*' => 'exists:contracts,id',
        ]);

        if ($request->hasFile('avatar')) {
            $validated['avatar_path'] = $request->file('avatar')->store('avatars/organizations', 'public');
        }
        unset($validated['avatar']);

        // Filter empty websites
        $validated['websites'] = array_values(array_filter($validated['websites'] ?? []));

        $organization = Organization::create($validated);

        $organization->contacts()->sync($request->input('contact_ids', []));
        $organization->projects()->sync($request->input('project_ids', []));
        $organization->tracks()->sync($request->input('track_ids', []));
        $organization->releases()->sync($request->input('release_ids', []));
        $organization->contracts()->sync($request->input('contract_ids', []));
        $organization->genres()->sync($request->input('genre_ids', []));

        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $path = $file->store('organizations', 'public');
            $organization->documents()->create([
                'title' => $file->getClientOriginalName(),
                'category' => 'organization',
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'notes' => $request->input('document_notes'),
            ]);
        }

        return redirect()->route('admin.organizations.show', $organization)->with('success', 'Organisation erstellt.');
    }

    public function show(Organization $organization)
    {
        $organization->load(['contacts', 'projects', 'tracks', 'releases', 'contracts', 'genres']);
        $organization->setRelation('documents', $organization->documents()->withTrashed()->get());
        return view('admin.organizations.show', compact('organization'));
    }

    public function edit(Organization $organization)
    {
        $tracks = Track::orderBy('title')->get();
        $releases = Release::orderBy('title')->get();
        $genres = Genre::orderBy('name')->get();
        $organization->load(['contacts', 'projects', 'tracks', 'releases', 'contracts', 'genres']);
        $organization->setRelation('documents', $organization->documents()->withTrashed()->get());
        return view('admin.organizations.edit', compact('organization', 'tracks', 'releases', 'genres'));
    }

    public function update(Request $request, Organization $organization)
    {
        $validated = $request->validate([
            'type' => 'required|exists:organization_types,slug',
            'legal_form' => 'nullable|string|max:50',
            'names' => 'required|array|min:1',
            'names.*' => 'required|string|max:255',
            'biography' => 'nullable|string',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'street' => 'nullable|string|max:255',
            'zip' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:34',
            'bank_name' => 'nullable|string|max:255',
            'bank_zip' => 'nullable|string|max:20',
            'bank_city' => 'nullable|string|max:255',
            'bank_country' => 'nullable|string|max:255',
            'bic' => 'nullable|string|max:11',
            'vat_number' => 'nullable|string|max:50',
            'avatar' => 'nullable|image|max:2048',
            'remove_avatar' => 'nullable|boolean',
            'websites' => 'nullable|array',
            'websites.*' => 'nullable|url|max:500',
            'contact_ids' => 'nullable|array',
            'contact_ids.*' => 'exists:contacts,id',
            'project_ids' => 'nullable|array',
            'project_ids.*' => 'exists:projects,id',
            'track_ids' => 'nullable|array',
            'track_ids.*' => 'exists:tracks,id',
            'release_ids' => 'nullable|array',
            'release_ids.*' => 'exists:releases,id',
            'contract_ids' => 'nullable|array',
            'contract_ids.*' => 'exists:contracts,id',
        ]);

        if ($request->boolean('remove_avatar') && $organization->avatar_path) {
            Storage::disk('public')->delete($organization->avatar_path);
            $validated['avatar_path'] = null;
        } elseif ($request->hasFile('avatar')) {
            if ($organization->avatar_path) {
                Storage::disk('public')->delete($organization->avatar_path);
            }
            $validated['avatar_path'] = $request->file('avatar')->store('avatars/organizations', 'public');
        }
        unset($validated['avatar'], $validated['remove_avatar']);

        $validated['websites'] = array_values(array_filter($validated['websites'] ?? []));

        $organization->update($validated);

        $organization->contacts()->sync($request->input('contact_ids', []));
        $organization->projects()->sync($request->input('project_ids', []));
        $organization->tracks()->sync($request->input('track_ids', []));
        $organization->releases()->sync($request->input('release_ids', []));
        $organization->contracts()->sync($request->input('contract_ids', []));
        $organization->genres()->sync($request->input('genre_ids', []));

        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $path = $file->store('organizations', 'public');
            $organization->documents()->create([
                'title' => $file->getClientOriginalName(),
                'category' => 'organization',
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'notes' => $request->input('document_notes'),
            ]);
        }

        return redirect()->route('admin.organizations.show', $organization)->with('success', 'Organisation aktualisiert.');
    }

    public function destroy(Organization $organization)
    {
        $organization->delete();
        return redirect()->route('admin.organizations.index')->with('success', 'Organisation gelöscht.');
    }

    public function storeQuick(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|exists:organization_types,slug',
            'name' => 'required|string|max:255',
        ]);

        $org = Organization::create([
            'type' => $validated['type'],
            'names' => [$validated['name']],
        ]);

        return response()->json([
            'id' => $org->id,
            'primary_name' => $org->primary_name,
            'all_names' => $org->all_names,
            'type' => $org->type,
        ]);
    }

    public function search(Request $request)
    {
        $query = Organization::query();

        if ($q = $request->input('q')) {
            $terms = preg_split('/\s+/', trim($q));
            $query->where(function ($qb) use ($terms) {
                foreach ($terms as $term) {
                    $qb->where('names', 'like', "%{$term}%");
                }
            });
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        $results = $query->orderBy('names')->limit(50)->get()->map(fn ($org) => [
            'id' => $org->id,
            'primary_name' => $org->primary_name,
            'all_names' => $org->all_names,
            'type' => $org->type,
        ]);

        return response()->json($results);
    }
}
