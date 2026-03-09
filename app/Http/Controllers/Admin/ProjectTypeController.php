<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectType;
use Illuminate\Http\Request;

class ProjectTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = ProjectType::query();

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $sortField = $request->input('sort', 'sort_order');
        $sortDir = $request->input('dir', 'asc');
        $allowedSorts = ['name', 'sort_order', 'created_at'];
        if (!in_array($sortField, $allowedSorts)) $sortField = 'sort_order';
        if (!in_array($sortDir, ['asc', 'desc'])) $sortDir = 'asc';

        $types = $query->orderBy($sortField, $sortDir)->paginate(30)->withQueryString();

        $types->getCollection()->transform(function ($type) {
            $type->usage_count = Project::where('type', $type->slug)->count();
            return $type;
        });

        return view('admin.project-types.index', compact('types'));
    }

    public function create()
    {
        $colorOptions = $this->getColorOptions();
        return view('admin.project-types.create', compact('colorOptions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:project_types,name',
            'color' => 'required|string|max:60',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        ProjectType::create([
            'name' => $request->input('name'),
            'color' => $request->input('color'),
            'sort_order' => $request->input('sort_order', 0),
        ]);

        return redirect()->route('admin.project-types.index')->with('success', 'Projekttyp erstellt.');
    }

    public function edit(ProjectType $projectType)
    {
        $colorOptions = $this->getColorOptions();
        return view('admin.project-types.edit', compact('projectType', 'colorOptions'));
    }

    public function update(Request $request, ProjectType $projectType)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:project_types,name,' . $projectType->id,
            'color' => 'required|string|max:60',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $oldSlug = $projectType->slug;

        $projectType->update([
            'name' => $request->input('name'),
            'color' => $request->input('color'),
            'sort_order' => $request->input('sort_order', 0),
        ]);

        if ($oldSlug !== $projectType->slug) {
            Project::where('type', $oldSlug)->update(['type' => $projectType->slug]);
        }

        return redirect()->route('admin.project-types.index')->with('success', 'Projekttyp aktualisiert.');
    }

    public function destroy(ProjectType $projectType)
    {
        $count = Project::where('type', $projectType->slug)->count();
        if ($count > 0) {
            return back()->with('error', "Kann nicht gelöscht werden: {$count} Projekt(e) verwenden diesen Typ.");
        }

        $projectType->delete();
        return redirect()->route('admin.project-types.index')->with('success', 'Projekttyp gelöscht.');
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
