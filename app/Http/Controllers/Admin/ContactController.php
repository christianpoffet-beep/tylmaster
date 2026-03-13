<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactType;
use App\Models\Genre;
use App\Models\Project;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ContactController extends Controller
{
    private function applyFilters(Request $request)
    {
        $query = Contact::with('genres');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($type = $request->input('type')) {
            $query->whereJsonContains('types', $type);
        }

        if ($city = $request->input('city')) {
            $query->where('city', 'like', "%{$city}%");
        }

        if ($country = $request->input('country')) {
            $query->where('country', $country);
        }

        if ($gender = $request->input('gender')) {
            $query->where('gender', $gender);
        }

        if ($genreId = $request->input('genre')) {
            $query->whereHas('genres', fn ($q) => $q->where('genres.id', $genreId));
        }

        return $query;
    }

    public function index(Request $request)
    {
        $query = $this->applyFilters($request);

        $sortField = $request->input('sort', 'last_name');
        $sortDir = $request->input('dir', 'asc');
        $allowedSorts = ['last_name', 'first_name', 'email', 'created_at'];
        if (!in_array($sortField, $allowedSorts)) $sortField = 'last_name';
        if (!in_array($sortDir, ['asc', 'desc'])) $sortDir = 'asc';

        $contacts = $query->orderBy($sortField, $sortDir)->paginate(20)->withQueryString();
        $contactTypes = ContactType::orderBy('sort_order')->get();
        $genres = Genre::orderBy('name')->get();
        $countries = Contact::whereNotNull('country')->where('country', '!=', '')->distinct()->orderBy('country')->pluck('country');

        return view('admin.contacts.index', compact('contacts', 'contactTypes', 'genres', 'countries'));
    }

    public function export(Request $request)
    {
        $contacts = $this->applyFilters($request)->orderBy('last_name')->get();

        $filename = 'kontakte_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($contacts) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF"); // BOM for Excel

            fputcsv($file, ['Ref', 'Nachname', 'Vorname', 'E-Mail', 'Telefon', 'Typ', 'Genre', 'Geschlecht', 'Strasse', 'PLZ', 'Ort', 'Land'], ';');

            foreach ($contacts as $c) {
                fputcsv($file, [
                    $c->ref_nr,
                    $c->last_name,
                    $c->first_name,
                    $c->email,
                    $c->phone,
                    implode(', ', $c->types ?? []),
                    $c->genres->pluck('name')->implode(', '),
                    $c->gender ? ucfirst($c->gender) : '',
                    $c->street,
                    $c->zip,
                    $c->city,
                    $c->country,
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function create()
    {
        $tags = Tag::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $genres = Genre::orderBy('name')->get();
        $contactTypes = ContactType::orderBy('sort_order')->get();
        return view('admin.contacts.create', compact('tags', 'projects', 'genres', 'contactTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'nullable|string|in:male,female,other',
            'birth_date' => 'nullable|date',
            'death_date' => 'nullable|date|after_or_equal:birth_date',
            'email' => 'nullable|email|max:255',
            'secondary_emails' => 'nullable|array',
            'secondary_emails.*' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'secondary_phones' => 'nullable|array',
            'secondary_phones.*' => 'nullable|string|max:255',
            'street' => 'nullable|string|max:255',
            'zip' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'nationality' => 'nullable|string|max:2',
            'ahv_number' => 'nullable|string|max:16|regex:/^756\.\d{4}\.\d{4}\.\d{2}$/',
            'iban' => 'nullable|string|max:34',
            'bank_name' => 'nullable|string|max:255',
            'bank_zip' => 'nullable|string|max:20',
            'bank_city' => 'nullable|string|max:255',
            'bank_country' => 'nullable|string|max:255',
            'bic' => 'nullable|string|max:11',
            'avatar' => 'nullable|image|max:2048',
            'types' => 'required|array|min:1',
            'types.*' => 'exists:contact_types,slug',
            'notes' => 'nullable|string',
            'ipis' => 'nullable|array',
            'ipis.*.number' => 'nullable|string|max:50',
            'ipis.*.name' => 'nullable|string|max:255',
            'ipis.*.primary' => 'nullable',
        ]);

        if ($request->hasFile('avatar')) {
            $validated['avatar_path'] = $request->file('avatar')->store('avatars/contacts', 'public');
        }
        unset($validated['avatar']);

        // Filter out empty secondary entries
        $validated['secondary_emails'] = array_values(array_filter($validated['secondary_emails'] ?? []));
        $validated['secondary_phones'] = array_values(array_filter($validated['secondary_phones'] ?? []));

        // Filter out empty IPI entries and normalize primary flag
        $validated['ipis'] = array_values(array_filter($validated['ipis'] ?? [], fn ($ipi) => !empty($ipi['number']) || !empty($ipi['name'])));
        $validated['ipis'] = array_map(fn ($ipi) => ['number' => $ipi['number'] ?? '', 'name' => $ipi['name'] ?? '', 'primary' => !empty($ipi['primary'])], $validated['ipis']);

        $contact = Contact::create($validated);

        if ($request->has('tags')) {
            $contact->tags()->sync($request->input('tags'));
        }

        if ($request->has('project_ids')) {
            $contact->projects()->sync($request->input('project_ids'));
        }

        $contact->organizations()->sync($request->input('organization_ids', []));
        $contact->genres()->sync($request->input('genre_ids', []));

        return redirect()->route('admin.contacts.show', $contact)->with('success', 'Kontakt erstellt.');
    }

    public function show(Contact $contact)
    {
        $contact->load(['tags', 'contracts', 'projects', 'invoices', 'tracks', 'organizations', 'genres']);
        return view('admin.contacts.show', compact('contact'));
    }

    public function edit(Contact $contact)
    {
        $tags = Tag::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $genres = Genre::orderBy('name')->get();
        $contactTypes = ContactType::orderBy('sort_order')->get();
        $contact->load(['projects', 'organizations', 'genres']);
        return view('admin.contacts.edit', compact('contact', 'tags', 'projects', 'genres', 'contactTypes'));
    }

    public function update(Request $request, Contact $contact)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'nullable|string|in:male,female,other',
            'birth_date' => 'nullable|date',
            'death_date' => 'nullable|date|after_or_equal:birth_date',
            'email' => 'nullable|email|max:255',
            'secondary_emails' => 'nullable|array',
            'secondary_emails.*' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'secondary_phones' => 'nullable|array',
            'secondary_phones.*' => 'nullable|string|max:255',
            'street' => 'nullable|string|max:255',
            'zip' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'nationality' => 'nullable|string|max:2',
            'ahv_number' => 'nullable|string|max:16|regex:/^756\.\d{4}\.\d{4}\.\d{2}$/',
            'iban' => 'nullable|string|max:34',
            'bank_name' => 'nullable|string|max:255',
            'bank_zip' => 'nullable|string|max:20',
            'bank_city' => 'nullable|string|max:255',
            'bank_country' => 'nullable|string|max:255',
            'bic' => 'nullable|string|max:11',
            'avatar' => 'nullable|image|max:2048',
            'remove_avatar' => 'nullable|boolean',
            'types' => 'required|array|min:1',
            'types.*' => 'exists:contact_types,slug',
            'notes' => 'nullable|string',
            'ipis' => 'nullable|array',
            'ipis.*.number' => 'nullable|string|max:50',
            'ipis.*.name' => 'nullable|string|max:255',
            'ipis.*.primary' => 'nullable',
        ]);

        if ($request->boolean('remove_avatar') && $contact->avatar_path) {
            Storage::disk('public')->delete($contact->avatar_path);
            $validated['avatar_path'] = null;
        } elseif ($request->hasFile('avatar')) {
            if ($contact->avatar_path) {
                Storage::disk('public')->delete($contact->avatar_path);
            }
            $validated['avatar_path'] = $request->file('avatar')->store('avatars/contacts', 'public');
        }
        unset($validated['avatar'], $validated['remove_avatar']);

        // Filter out empty secondary entries
        $validated['secondary_emails'] = array_values(array_filter($validated['secondary_emails'] ?? []));
        $validated['secondary_phones'] = array_values(array_filter($validated['secondary_phones'] ?? []));

        // Filter out empty IPI entries and normalize primary flag
        $validated['ipis'] = array_values(array_filter($validated['ipis'] ?? [], fn ($ipi) => !empty($ipi['number']) || !empty($ipi['name'])));
        $validated['ipis'] = array_map(fn ($ipi) => ['number' => $ipi['number'] ?? '', 'name' => $ipi['name'] ?? '', 'primary' => !empty($ipi['primary'])], $validated['ipis']);

        $contact->update($validated);

        $contact->tags()->sync($request->input('tags', []));
        $contact->projects()->sync($request->input('project_ids', []));
        $contact->organizations()->sync($request->input('organization_ids', []));
        $contact->genres()->sync($request->input('genre_ids', []));

        return redirect()->route('admin.contacts.show', $contact)->with('success', 'Kontakt aktualisiert.');
    }

    public function destroy(Contact $contact)
    {
        $contact->delete();
        return redirect()->route('admin.contacts.index')->with('success', 'Kontakt gelöscht.');
    }

    public function storeQuick(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
        ]);

        $contact = Contact::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'types' => ['other'],
        ]);

        return response()->json([
            'id' => $contact->id,
            'type' => 'contact',
            'name' => $contact->full_name,
            'detail' => null,
        ]);
    }

    public function search(Request $request)
    {
        $query = Contact::query();

        if ($q = $request->input('q')) {
            $query->where(function ($qb) use ($q) {
                $qb->where('first_name', 'like', "%{$q}%")
                   ->orWhere('last_name', 'like', "%{$q}%")
                   ->orWhere('email', 'like', "%{$q}%");
            });
        }

        $results = $query->orderBy('last_name')->limit(50)->get()->map(fn ($c) => [
            'id' => $c->id,
            'name' => $c->full_name,
            'email' => $c->email,
        ]);

        return response()->json($results);
    }
}
