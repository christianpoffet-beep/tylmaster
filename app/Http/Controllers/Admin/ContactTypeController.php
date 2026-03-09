<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactType;
use Illuminate\Http\Request;

class ContactTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = ContactType::query();

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
            $type->usage_count = Contact::whereJsonContains('types', $type->slug)->count();
            return $type;
        });

        return view('admin.contact-types.index', compact('types'));
    }

    public function create()
    {
        $colorOptions = $this->getColorOptions();
        return view('admin.contact-types.create', compact('colorOptions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:contact_types,name',
            'color' => 'required|string|max:60',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        ContactType::create([
            'name' => $request->input('name'),
            'color' => $request->input('color'),
            'sort_order' => $request->input('sort_order', 0),
        ]);

        return redirect()->route('admin.contact-types.index')->with('success', 'Kontakttyp erstellt.');
    }

    public function edit(ContactType $contactType)
    {
        $colorOptions = $this->getColorOptions();
        return view('admin.contact-types.edit', compact('contactType', 'colorOptions'));
    }

    public function update(Request $request, ContactType $contactType)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:contact_types,name,' . $contactType->id,
            'color' => 'required|string|max:60',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $oldSlug = $contactType->slug;

        $contactType->update([
            'name' => $request->input('name'),
            'color' => $request->input('color'),
            'sort_order' => $request->input('sort_order', 0),
        ]);

        // Update contacts JSON if slug changed
        if ($oldSlug !== $contactType->slug) {
            $contacts = Contact::whereJsonContains('types', $oldSlug)->get();
            foreach ($contacts as $contact) {
                $types = $contact->types ?? [];
                $types = array_map(fn ($t) => $t === $oldSlug ? $contactType->slug : $t, $types);
                $contact->update(['types' => $types]);
            }
        }

        return redirect()->route('admin.contact-types.index')->with('success', 'Kontakttyp aktualisiert.');
    }

    public function destroy(ContactType $contactType)
    {
        $count = Contact::whereJsonContains('types', $contactType->slug)->count();
        if ($count > 0) {
            return back()->with('error', "Kann nicht gelöscht werden: {$count} Kontakt(e) verwenden diesen Typ.");
        }

        $contactType->delete();
        return redirect()->route('admin.contact-types.index')->with('success', 'Kontakttyp gelöscht.');
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
