<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use Illuminate\Http\Request;

class GenreController extends Controller
{
    public function index(Request $request)
    {
        $query = Genre::withCount(['organizations', 'contacts', 'projects']);

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $sortField = $request->input('sort', 'name');
        $sortDir = $request->input('dir', 'asc');
        $allowedSorts = ['name', 'created_at'];
        if (!in_array($sortField, $allowedSorts)) $sortField = 'name';
        if (!in_array($sortDir, ['asc', 'desc'])) $sortDir = 'asc';

        $genres = $query->orderBy($sortField, $sortDir)->paginate(30)->withQueryString();

        return view('admin.genres.index', compact('genres'));
    }

    public function create()
    {
        return view('admin.genres.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:genres,name',
        ]);

        Genre::create(['name' => $request->input('name')]);

        return redirect()->route('admin.genres.index')->with('success', 'Genre erstellt.');
    }

    public function edit(Genre $genre)
    {
        return view('admin.genres.edit', compact('genre'));
    }

    public function update(Request $request, Genre $genre)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:genres,name,' . $genre->id,
        ]);

        $genre->update(['name' => $request->input('name')]);

        return redirect()->route('admin.genres.index')->with('success', 'Genre aktualisiert.');
    }

    public function destroy(Genre $genre)
    {
        $genre->delete();

        return redirect()->route('admin.genres.index')->with('success', 'Genre gelöscht.');
    }
}
