<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Artwork;
use App\Models\Project;
use App\Models\Contact;
use App\Models\Contract;
use App\Models\Genre;
use App\Models\ProjectType;
use App\Models\Track;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::with('tasks')->withCount(['contacts', 'tasks']);

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $sortField = $request->input('sort', 'created_at');
        $sortDir = $request->input('dir', 'desc');
        $allowedSorts = ['name', 'type', 'status', 'deadline', 'created_at'];
        if (!in_array($sortField, $allowedSorts)) $sortField = 'created_at';
        if (!in_array($sortDir, ['asc', 'desc'])) $sortDir = 'desc';

        $projects = $query->orderBy($sortField, $sortDir)->paginate(20)->withQueryString();
        $projectTypes = ProjectType::orderBy('sort_order')->get();
        return view('admin.projects.index', compact('projects', 'projectTypes'));
    }

    public function create()
    {
        $contacts = Contact::orderBy('last_name')->get();
        $artworks = Artwork::orderBy('title')->get();
        $genres = Genre::orderBy('name')->get();
        $tracks = Track::orderBy('title')->get();
        $contracts = Contract::orderBy('title')->get();
        $projectTypes = ProjectType::orderBy('sort_order')->get();
        return view('admin.projects.create', compact('contacts', 'artworks', 'genres', 'tracks', 'contracts', 'projectTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|exists:project_types,slug',
            'description' => 'nullable|string',
            'status' => 'required|in:planned,in_progress,completed,paused',
            'deadline' => 'nullable|date',
        ]);

        $project = Project::create($validated);

        if ($request->has('contacts')) {
            $project->contacts()->sync($request->input('contacts'));
        }

        $project->organizations()->sync($request->input('organization_ids', []));
        $project->artworks()->sync($request->input('artwork_ids', []));
        $project->genres()->sync($request->input('genre_ids', []));
        $project->tracks()->sync($request->input('track_ids', []));
        $project->contracts()->sync($request->input('contract_ids', []));

        return redirect()->route('admin.projects.show', $project)->with('success', 'Projekt erstellt.');
    }

    public function show(Project $project)
    {
        $project->load(['contacts', 'contracts', 'tracks', 'tasks', 'artworks.logos', 'organizations', 'genres']);
        $projectTypes = ProjectType::orderBy('sort_order')->get();
        return view('admin.projects.show', compact('project', 'projectTypes'));
    }

    public function edit(Project $project)
    {
        $contacts = Contact::orderBy('last_name')->get();
        $artworks = Artwork::orderBy('title')->get();
        $genres = Genre::orderBy('name')->get();
        $tracks = Track::orderBy('title')->get();
        $contracts = Contract::orderBy('title')->get();
        $projectTypes = ProjectType::orderBy('sort_order')->get();
        $project->load(['contacts', 'organizations', 'artworks', 'genres', 'tracks', 'contracts']);
        return view('admin.projects.edit', compact('project', 'contacts', 'artworks', 'genres', 'tracks', 'contracts', 'projectTypes'));
    }

    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|exists:project_types,slug',
            'description' => 'nullable|string',
            'status' => 'required|in:planned,in_progress,completed,paused',
            'deadline' => 'nullable|date',
        ]);

        $project->update($validated);

        if ($request->has('contacts')) {
            $project->contacts()->sync($request->input('contacts'));
        }

        $project->organizations()->sync($request->input('organization_ids', []));
        $project->artworks()->sync($request->input('artwork_ids', []));
        $project->genres()->sync($request->input('genre_ids', []));
        $project->tracks()->sync($request->input('track_ids', []));
        $project->contracts()->sync($request->input('contract_ids', []));

        return redirect()->route('admin.projects.show', $project)->with('success', 'Projekt aktualisiert.');
    }

    public function destroy(Project $project)
    {
        $project->delete();
        return redirect()->route('admin.projects.index')->with('success', 'Projekt gelöscht.');
    }

    public function storeTask(Request $request, Project $project)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'due_date' => 'nullable|date',
        ]);

        $project->tasks()->create($validated);
        return back()->with('success', 'Aufgabe hinzugefügt.');
    }

    public function toggleTask(Project $project, \App\Models\Task $task)
    {
        $task->update(['is_completed' => !$task->is_completed]);
        return back();
    }
}
