<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationType;
use Illuminate\Http\Request;

class OrganizationTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = OrganizationType::query();

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $sortField = $request->input('sort', 'sort_order');
        $sortDir = $request->input('dir', 'asc');
        $allowedSorts = ['name', 'sort_order', 'created_at'];
        if (!in_array($sortField, $allowedSorts)) $sortField = 'sort_order';
        if (!in_array($sortDir, ['asc', 'desc'])) $sortDir = 'asc';

        $types = $query->orderBy($sortField, $sortDir)->paginate(30)->withQueryString();

        // Add organization count for each type
        $types->getCollection()->transform(function ($type) {
            $type->usage_count = Organization::where('type', $type->slug)->count();
            return $type;
        });

        return view('admin.organization-types.index', compact('types'));
    }

    public function create()
    {
        $colorOptions = $this->getColorOptions();
        return view('admin.organization-types.create', compact('colorOptions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:organization_types,name',
            'color' => 'required|string|max:60',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        OrganizationType::create([
            'name' => $request->input('name'),
            'color' => $request->input('color'),
            'sort_order' => $request->input('sort_order', 0),
        ]);

        return redirect()->route('admin.organization-types.index')->with('success', 'Organisationstyp erstellt.');
    }

    public function edit(OrganizationType $organizationType)
    {
        $colorOptions = $this->getColorOptions();
        return view('admin.organization-types.edit', compact('organizationType', 'colorOptions'));
    }

    public function update(Request $request, OrganizationType $organizationType)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:organization_types,name,' . $organizationType->id,
            'color' => 'required|string|max:60',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $oldSlug = $organizationType->slug;

        $organizationType->update([
            'name' => $request->input('name'),
            'color' => $request->input('color'),
            'sort_order' => $request->input('sort_order', 0),
        ]);

        // Update organizations if slug changed
        if ($oldSlug !== $organizationType->slug) {
            Organization::where('type', $oldSlug)->update(['type' => $organizationType->slug]);
        }

        return redirect()->route('admin.organization-types.index')->with('success', 'Organisationstyp aktualisiert.');
    }

    public function destroy(OrganizationType $organizationType)
    {
        $count = Organization::where('type', $organizationType->slug)->count();
        if ($count > 0) {
            return back()->with('error', "Kann nicht gelöscht werden: {$count} Organisation(en) verwenden diesen Typ.");
        }

        $organizationType->delete();
        return redirect()->route('admin.organization-types.index')->with('success', 'Organisationstyp gelöscht.');
    }

    private function getColorOptions(): array
    {
        return [
            'bg-purple-100 text-purple-700' => 'Lila',
            'bg-blue-100 text-blue-700' => 'Blau',
            'bg-indigo-100 text-indigo-700' => 'Indigo',
            'bg-green-100 text-green-700' => 'Grün',
            'bg-yellow-100 text-yellow-700' => 'Gelb',
            'bg-pink-100 text-pink-700' => 'Pink',
            'bg-red-100 text-red-700' => 'Rot',
            'bg-orange-100 text-orange-700' => 'Orange',
            'bg-teal-100 text-teal-700' => 'Teal',
            'bg-cyan-100 text-cyan-700' => 'Cyan',
            'bg-gray-100 text-gray-600' => 'Grau',
        ];
    }
}
