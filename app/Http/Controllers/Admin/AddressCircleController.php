<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AddressCircle;
use App\Models\Contact;
use App\Models\ContactType;
use App\Models\Genre;
use App\Models\Organization;
use App\Models\OrganizationType;
use Illuminate\Http\Request;

class AddressCircleController extends Controller
{
    public function index(Request $request)
    {
        $query = AddressCircle::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('info', 'like', "%{$search}%");
            });
        }

        $sortField = $request->input('sort', 'name');
        $sortDir = $request->input('dir', 'asc');
        $allowedSorts = ['name', 'created_at'];
        if (!in_array($sortField, $allowedSorts)) $sortField = 'name';
        if (!in_array($sortDir, ['asc', 'desc'])) $sortDir = 'asc';

        $circles = $query->withCount(['contactMembers', 'organizationMembers'])
            ->orderBy($sortField, $sortDir)
            ->paginate(20)
            ->withQueryString();

        return view('admin.address-circles.index', compact('circles'));
    }

    public function create()
    {
        return view('admin.address-circles.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'info' => 'required|string',
            'organization_ids' => 'nullable|array',
            'organization_ids.*' => 'exists:organizations,id',
            'project_ids' => 'nullable|array',
            'project_ids.*' => 'exists:projects,id',
        ]);

        $circle = AddressCircle::create($request->only('name', 'info'));
        $circle->organizations()->sync($request->input('organization_ids', []));
        $circle->projects()->sync($request->input('project_ids', []));

        return redirect()->route('admin.address-circles.edit', $circle)->with('success', 'Adresskreis erstellt. Du kannst jetzt Mitglieder hinzufügen.');
    }

    public function edit(AddressCircle $addressCircle)
    {
        $addressCircle->load(['organizations', 'projects', 'contactMembers.genres', 'organizationMembers.genres']);

        $contactTypes = ContactType::orderBy('sort_order')->get();
        $organizationTypes = OrganizationType::orderBy('sort_order')->get();
        $genres = Genre::orderBy('name')->get();

        return view('admin.address-circles.edit', compact('addressCircle', 'contactTypes', 'organizationTypes', 'genres'));
    }

    public function update(Request $request, AddressCircle $addressCircle)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'info' => 'required|string',
            'organization_ids' => 'nullable|array',
            'organization_ids.*' => 'exists:organizations,id',
            'project_ids' => 'nullable|array',
            'project_ids.*' => 'exists:projects,id',
        ]);

        $addressCircle->update($request->only('name', 'info'));
        $addressCircle->organizations()->sync($request->input('organization_ids', []));
        $addressCircle->projects()->sync($request->input('project_ids', []));

        return redirect()->route('admin.address-circles.edit', $addressCircle)->with('success', 'Adresskreis aktualisiert.');
    }

    public function destroy(AddressCircle $addressCircle)
    {
        $addressCircle->delete();
        return redirect()->route('admin.address-circles.index')->with('success', 'Adresskreis gelöscht.');
    }

    /**
     * Filter contacts and organizations based on criteria.
     */
    public function filter(Request $request, AddressCircle $addressCircle)
    {
        $type = $request->input('filter_type', 'contact'); // 'contact' or 'organization'
        $results = [];

        if ($type === 'contact') {
            $query = Contact::query();

            if ($v = $request->input('f_first_name')) $query->where('first_name', 'like', "%{$v}%");
            if ($v = $request->input('f_last_name')) $query->where('last_name', 'like', "%{$v}%");
            if ($v = $request->input('f_email')) $query->where('email', 'like', "%{$v}%");
            if ($v = $request->input('f_zip')) $query->where('zip', 'like', "%{$v}%");
            if ($v = $request->input('f_city')) $query->where('city', 'like', "%{$v}%");
            if ($v = $request->input('f_country')) $query->where('country', $v);
            if ($v = $request->input('f_gender')) $query->where('gender', $v);
            if ($v = $request->input('f_notes')) $query->where('notes', 'like', "%{$v}%");
            if ($v = $request->input('f_contact_type')) $query->whereJsonContains('types', $v);
            if ($v = $request->input('f_birth_from')) $query->whereDate('birth_date', '>=', $v);
            if ($v = $request->input('f_birth_to')) $query->whereDate('birth_date', '<=', $v);
            if ($v = $request->input('f_death_from')) $query->whereDate('death_date', '>=', $v);
            if ($v = $request->input('f_death_to')) $query->whereDate('death_date', '<=', $v);
            if ($v = $request->input('f_genre')) $query->whereHas('genres', fn ($q) => $q->where('genres.id', $v));
            if ($v = $request->input('f_project')) $query->whereHas('projects', fn ($q) => $q->where('projects.id', $v));

            $results = $query->orderBy('last_name')->limit(200)->get()->map(fn ($c) => [
                'id' => $c->id,
                'type' => 'contact',
                'name' => $c->full_name,
                'email' => $c->email,
                'gender' => $c->gender,
                'city' => $c->city,
                'country' => $c->country,
            ]);
        } else {
            $query = Organization::query();

            if ($v = $request->input('f_org_name')) {
                $query->where(function ($q) use ($v) {
                    $q->where('names', 'like', "%{$v}%");
                });
            }
            if ($v = $request->input('f_org_type')) $query->where('type', $v);
            if ($v = $request->input('f_org_bio')) $query->where('biography', 'like', "%{$v}%");
            if ($v = $request->input('f_email')) $query->where('email', 'like', "%{$v}%");
            if ($v = $request->input('f_zip')) $query->where('zip', 'like', "%{$v}%");
            if ($v = $request->input('f_city')) $query->where('city', 'like', "%{$v}%");
            if ($v = $request->input('f_country')) $query->where('country', $v);
            if ($v = $request->input('f_genre')) $query->whereHas('genres', fn ($q) => $q->where('genres.id', $v));
            if ($v = $request->input('f_project')) $query->whereHas('projects', fn ($q) => $q->where('projects.id', $v));
            if ($v = $request->input('f_contact')) $query->whereHas('contacts', fn ($q) => $q->where('contacts.id', $v));
            if ($v = $request->input('f_contract')) $query->whereHas('contracts', fn ($q) => $q->where('contracts.id', $v));

            $results = $query->orderBy('names')->limit(200)->get()->map(fn ($o) => [
                'id' => $o->id,
                'type' => 'organization',
                'name' => $o->primary_name,
                'email' => $o->email,
                'gender' => null,
                'city' => $o->city,
                'country' => $o->country,
            ]);
        }

        // Mark already-added members
        $existingContactIds = $addressCircle->contactMembers()->pluck('contacts.id')->toArray();
        $existingOrgIds = $addressCircle->organizationMembers()->pluck('organizations.id')->toArray();

        $results = $results->map(function ($r) use ($existingContactIds, $existingOrgIds) {
            $r['is_member'] = $r['type'] === 'contact'
                ? in_array($r['id'], $existingContactIds)
                : in_array($r['id'], $existingOrgIds);
            return $r;
        });

        return response()->json($results);
    }

    /**
     * Add members to address circle.
     */
    public function addMembers(Request $request, AddressCircle $addressCircle)
    {
        $request->validate([
            'members' => 'required|array',
            'members.*.id' => 'required|integer',
            'members.*.type' => 'required|in:contact,organization',
        ]);

        foreach ($request->input('members') as $member) {
            if ($member['type'] === 'contact') {
                $addressCircle->contactMembers()->syncWithoutDetaching([$member['id']]);
            } else {
                $addressCircle->organizationMembers()->syncWithoutDetaching([$member['id']]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Mitglieder hinzugefügt.']);
    }

    /**
     * Remove members from address circle.
     */
    public function removeMembers(Request $request, AddressCircle $addressCircle)
    {
        $request->validate([
            'members' => 'required|array',
            'members.*.id' => 'required|integer',
            'members.*.type' => 'required|in:contact,organization',
        ]);

        foreach ($request->input('members') as $member) {
            if ($member['type'] === 'contact') {
                $addressCircle->contactMembers()->detach($member['id']);
            } else {
                $addressCircle->organizationMembers()->detach($member['id']);
            }
        }

        return response()->json(['success' => true, 'message' => 'Mitglieder entfernt.']);
    }

    /**
     * Update email override for a member.
     */
    public function updateMemberEmail(Request $request, AddressCircle $addressCircle)
    {
        $request->validate([
            'id' => 'required|integer',
            'type' => 'required|in:contact,organization',
            'email_override' => 'nullable|email|max:255',
        ]);

        $relation = $request->input('type') === 'contact' ? 'contactMembers' : 'organizationMembers';
        $addressCircle->$relation()->updateExistingPivot($request->input('id'), [
            'email_override' => $request->input('email_override') ?: null,
        ]);

        return response()->json(['success' => true, 'message' => 'E-Mail aktualisiert.']);
    }

    /**
     * Export members as Excel/CSV.
     */
    public function export(AddressCircle $addressCircle)
    {
        $addressCircle->load(['contactMembers.genres', 'organizationMembers.genres']);

        $rows = [];

        foreach ($addressCircle->contactMembers as $c) {
            $rows[] = [
                'Typ' => 'Kontakt',
                'Name' => $c->last_name,
                'Vorname' => $c->first_name,
                'Firma' => '',
                'E-Mail' => $c->pivot->email_override ?: $c->email,
                'Geschlecht' => $c->gender,
                'Genre' => $c->genres->pluck('name')->implode(', '),
            ];
        }

        foreach ($addressCircle->organizationMembers as $o) {
            $rows[] = [
                'Typ' => 'Organisation',
                'Name' => $o->primary_name,
                'Vorname' => '',
                'Firma' => $o->primary_name,
                'E-Mail' => $o->pivot->email_override ?: $o->email,
                'Geschlecht' => '',
                'Genre' => $o->genres->pluck('name')->implode(', '),
            ];
        }

        // Generate CSV with BOM for Excel compatibility
        $filename = 'Adresskreis_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $addressCircle->name) . '_' . now()->format('Ymd') . '.csv';

        $headers = ['Typ', 'Name', 'Vorname', 'Firma', 'E-Mail', 'Geschlecht', 'Genre'];

        $callback = function () use ($rows, $headers) {
            $file = fopen('php://output', 'w');
            // BOM for Excel UTF-8
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, $headers, ';');
            foreach ($rows as $row) {
                fputcsv($file, $row, ';');
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
