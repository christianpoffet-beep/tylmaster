<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Artwork;
use App\Models\ArtworkLogo;
use App\Models\Contact;
use App\Models\Contract;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Task;
use App\Models\Photo;
use App\Models\Track;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $query = Document::with('documentable');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        if ($source = $request->input('source')) {
            $sourceMap = [
                'contact' => Contact::class,
                'contract' => Contract::class,
                'task' => Task::class,
                'track' => Track::class,
                'project' => Project::class,
                'artwork' => Artwork::class,
                'photo' => Photo::class,
                'invoice' => Invoice::class,
            ];
            if ($source === 'general') {
                $query->whereNull('documentable_type');
            } elseif ($source === 'artwork') {
                $query->whereIn('documentable_type', [Artwork::class, ArtworkLogo::class]);
            } elseif (isset($sourceMap[$source])) {
                $query->where('documentable_type', $sourceMap[$source]);
            }
        }

        $sortField = $request->input('sort', 'created_at');
        $sortDir = $request->input('dir', 'desc');
        $allowedSorts = ['title', 'category', 'file_size', 'created_at', 'mime_type'];
        if (!in_array($sortField, $allowedSorts)) {
            $sortField = 'created_at';
        }
        if (!in_array($sortDir, ['asc', 'desc'])) {
            $sortDir = 'desc';
        }

        $documents = $query->orderBy($sortField, $sortDir)
            ->paginate(30)
            ->withQueryString();

        return view('admin.documents.index', compact('documents'));
    }

    public function create()
    {
        return view('admin.documents.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|in:contract,invoice,legal,music,other',
            'file' => 'required|file|max:51200',
            'notes' => 'nullable|string|max:1000',
        ]);

        $file = $request->file('file');
        $path = $file->store('documents', 'public');

        Document::create([
            'title' => $request->input('title'),
            'category' => $request->input('category'),
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'notes' => $request->input('notes'),
        ]);

        return redirect()->route('admin.documents.index')->with('success', 'Dokument hochgeladen.');
    }

    public function show(Document $document)
    {
        $document->load('documentable');
        return view('admin.documents.show', compact('document'));
    }

    public function preview(Document $document)
    {
        $path = $document->file_path;
        $mime = $document->mime_type ?? Storage::disk('public')->mimeType($path);

        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'Datei nicht gefunden.');
        }

        return Storage::disk('public')->response($path, $document->title, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . $document->title . '"',
        ]);
    }

    public function download(Document $document)
    {
        return Storage::disk('public')->download($document->file_path, $document->title);
    }

    public function destroy(Document $document)
    {
        if ($document->documentable_type === \App\Models\Contract::class) {
            return redirect()->route('admin.documents.index')
                ->with('error', 'Vertragsdokumente können nicht gelöscht werden.');
        }

        Storage::disk('public')->delete($document->file_path);
        $document->delete();
        return redirect()->route('admin.documents.index')->with('success', 'Dokument gelöscht.');
    }
}
