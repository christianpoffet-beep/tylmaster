<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Contact;
use App\Models\Contract;
use App\Models\Document;
use App\Models\Project;
use App\Models\Track;
use App\Models\MusicSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $query = Task::with('project');

        if ($search = $request->input('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        if ($request->input('status') === 'open') {
            $query->where('is_completed', false);
        } elseif ($request->input('status') === 'done') {
            $query->where('is_completed', true);
        }

        if ($priority = $request->input('priority')) {
            $query->where('priority', $priority);
        }

        $allowedSorts = ['title', 'priority', 'due_date', 'is_completed', 'created_at'];
        if ($request->input('sort') && in_array($request->input('sort'), $allowedSorts)) {
            $sortDir = in_array($request->input('dir'), ['asc', 'desc']) ? $request->input('dir') : 'asc';
            $tasks = $query->orderBy($request->input('sort'), $sortDir)->paginate(30)->withQueryString();
        } else {
            $tasks = $query->orderByRaw('is_completed ASC, CASE WHEN due_date IS NULL THEN 1 ELSE 0 END, due_date ASC')->paginate(30)->withQueryString();
        }

        return view('admin.tasks.index', compact('tasks'));
    }

    public function create()
    {
        $contacts = Contact::orderBy('last_name')->get();
        $projects = Project::orderBy('name')->get();
        $contracts = Contract::orderBy('title')->get();
        $tracks = Track::orderBy('title')->get();
        $submissions = MusicSubmission::orderBy('created_at', 'desc')->get();

        return view('admin.tasks.create', compact('contacts', 'projects', 'contracts', 'tracks', 'submissions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'priority' => 'nullable|in:low,medium,high',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        $task = Task::create($validated);

        $this->syncRelationships($task, $request);
        $this->handleDocumentUploads($task, $request);

        return redirect()->route('admin.tasks.show', $task)->with('success', 'Aufgabe erstellt.');
    }

    public function show(Task $task)
    {
        $task->load(['project', 'contacts', 'contracts', 'documents', 'tracks', 'releases', 'projects', 'submissions', 'uploadedDocuments']);
        return view('admin.tasks.show', compact('task'));
    }

    public function edit(Task $task)
    {
        $task->load(['contacts', 'contracts', 'tracks', 'projects', 'submissions', 'uploadedDocuments']);
        $contacts = Contact::orderBy('last_name')->get();
        $projects = Project::orderBy('name')->get();
        $contracts = Contract::orderBy('title')->get();
        $tracks = Track::orderBy('title')->get();
        $submissions = MusicSubmission::orderBy('created_at', 'desc')->get();

        return view('admin.tasks.edit', compact('task', 'contacts', 'projects', 'contracts', 'tracks', 'submissions'));
    }

    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'priority' => 'nullable|in:low,medium,high',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        $task->update($validated);

        $this->syncRelationships($task, $request);
        $this->handleDocumentUploads($task, $request);

        return redirect()->route('admin.tasks.show', $task)->with('success', 'Aufgabe aktualisiert.');
    }

    public function destroy(Task $task)
    {
        foreach ($task->uploadedDocuments as $doc) {
            Storage::disk('public')->delete($doc->file_path);
        }
        $task->delete();
        return redirect()->route('admin.tasks.index')->with('success', 'Aufgabe gelöscht.');
    }

    public function toggle(Task $task)
    {
        $task->update(['is_completed' => !$task->is_completed]);
        return back()->with('success', $task->is_completed ? 'Aufgabe erledigt.' : 'Aufgabe wieder geöffnet.');
    }

    public function destroyDocument(Task $task, Document $document)
    {
        if ((int) $document->documentable_id === (int) $task->id && $document->documentable_type === Task::class) {
            Storage::disk('public')->delete($document->file_path);
            $document->delete();
            return back()->with('success', 'Dokument gelöscht.');
        }

        return back()->with('error', 'Dokument gehört nicht zu dieser Aufgabe.');
    }

    private function syncRelationships(Task $task, Request $request): void
    {
        $task->contacts()->sync($request->input('contact_ids', []));
        $task->contracts()->sync($request->input('contract_ids', []));
        $task->tracks()->sync($request->input('track_ids', []));
        $task->projects()->sync($request->input('linked_project_ids', []));
        $task->submissions()->sync($request->input('submission_ids', []));
    }

    private function handleDocumentUploads(Task $task, Request $request): void
    {
        $files = $request->file('doc_files') ?? [];
        $notes = $request->input('doc_notes', []);

        foreach ($files as $index => $file) {
            if (!$file || !$file->isValid()) {
                continue;
            }

            $path = $file->store('documents/tasks', 'public');

            $task->uploadedDocuments()->create([
                'title' => $file->getClientOriginalName(),
                'category' => 'other',
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'notes' => $notes[$index] ?? null,
            ]);
        }
    }
}
