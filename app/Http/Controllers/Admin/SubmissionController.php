<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MusicSubmission;
use App\Models\Contact;
use App\Models\Release;
use App\Models\Track;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubmissionController extends Controller
{
    public function index(Request $request)
    {
        $query = MusicSubmission::query();

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('artist_name', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('project_name', 'like', "%{$search}%");
            });
        }

        $sortField = $request->input('sort', 'created_at');
        $sortDir = $request->input('dir', 'desc');
        $allowedSorts = ['artist_name', 'project_name', 'status', 'created_at'];
        if (!in_array($sortField, $allowedSorts)) $sortField = 'created_at';
        if (!in_array($sortDir, ['asc', 'desc'])) $sortDir = 'desc';

        $submissions = $query->orderBy($sortField, $sortDir)->paginate(20)->withQueryString();
        return view('admin.submissions.index', compact('submissions'));
    }

    public function show(MusicSubmission $submission)
    {
        $submission->load(['contact', 'release', 'contract']);
        return view('admin.submissions.show', compact('submission'));
    }

    public function updateStatus(Request $request, MusicSubmission $submission)
    {
        $request->validate([
            'status' => 'required|in:new,reviewed,accepted,rejected',
        ]);

        $submission->update(['status' => $request->input('status')]);
        return back()->with('success', 'Status aktualisiert.');
    }

    /**
     * Import submission data into Contact, Release, Tracks, and Contract records.
     */
    public function import(MusicSubmission $submission)
    {
        if ($submission->contact_id && $submission->release_id && $submission->contract_id) {
            return back()->with('error', 'Diese Submission wurde bereits importiert.');
        }

        DB::transaction(function () use ($submission) {
            // 1. Create or update Contact
            $contact = $this->createContact($submission);
            $submission->contact_id = $contact->id;

            // 2. Create Release
            $release = $this->createRelease($submission, $contact);
            $submission->release_id = $release->id;

            // 3. Create Tracks from songs_data
            $this->createTracks($submission, $release, $contact);

            // 4. Create Contract
            $contract = $this->createContract($submission, $contact);
            $submission->contract_id = $contract->id;

            // 5. Update submission status
            $submission->status = 'reviewed';
            $submission->save();
        });

        return back()->with('success', 'Submission erfolgreich importiert. Kontakt, Release, Tracks und Vertrag wurden erstellt.');
    }

    /**
     * Delete a submission.
     */
    public function destroy(MusicSubmission $submission)
    {
        $submission->delete();
        return redirect()->route('admin.submissions.index')->with('success', 'Submission gelöscht.');
    }

    private function createContact(MusicSubmission $submission): Contact
    {
        // Check if contact with same email already exists
        $contact = Contact::where('email', $submission->email)->first();

        if ($contact) {
            // Update existing contact with any new info
            $contact->update(array_filter([
                'phone' => $submission->phone,
                'street' => $submission->street,
                'zip' => $submission->zip,
                'city' => $submission->city,
                'country' => $submission->country,
            ]));
            return $contact;
        }

        return Contact::create([
            'first_name' => $submission->first_name ?? $submission->artist_name,
            'last_name' => $submission->last_name ?? '',
            'company' => null,
            'email' => $submission->email,
            'phone' => $submission->phone,
            'street' => $submission->street,
            'zip' => $submission->zip,
            'city' => $submission->city,
            'country' => $submission->country,
            'type' => 'artist',
            'notes' => "Importiert aus Music Submission #{$submission->id}",
        ]);
    }

    private function createRelease(MusicSubmission $submission, Contact $contact): Release
    {
        $release = Release::create([
            'title' => $submission->project_name ?? $submission->track_title ?? 'Unbenannt',
            'upc' => $submission->upc,
            'release_date' => $submission->release_date,
            'label' => 'The Yelling Light',
            'cover_image_path' => $submission->cover_image_path,
        ]);

        // Link artist to release
        $release->contacts()->attach($contact->id, ['role' => 'artist']);

        return $release;
    }

    private function createTracks(MusicSubmission $submission, Release $release, Contact $contact): void
    {
        $songsData = $submission->songs_data ?? [];

        if (empty($songsData)) {
            // Fallback: create single track from basic submission data
            if ($submission->track_title) {
                $track = Track::create([
                    'title' => $submission->track_title,
                    'genre' => $submission->genre,
                    'release_id' => $release->id,
                    'audio_file_path' => $submission->file_path,
                    'status' => 'pending',
                ]);
                $track->contacts()->attach($contact->id, ['role' => 'artist']);
            }
            return;
        }

        foreach ($songsData as $songData) {
            $track = Track::create([
                'title' => $songData['title'] ?? 'Unbenannt',
                'isrc' => $songData['isrc'] ?? null,
                'genre' => $submission->genre,
                'release_id' => $release->id,
                'status' => 'pending',
            ]);

            // Attach main artist
            $track->contacts()->attach($contact->id, ['role' => 'artist']);

            // Attach featured artists if present
            if (!empty($songData['featuring'])) {
                // Store featuring info in notes for now (contacts may not exist yet)
            }
        }
    }

    private function createContract(MusicSubmission $submission, Contact $contact): Contract
    {
        $contract = Contract::create([
            'title' => "Distribution – {$submission->artist_name} – " . ($submission->project_name ?? $submission->track_title ?? 'Unbenannt'),
            'type' => 'distribution',
            'status' => 'draft',
            'start_date' => $submission->contract_sign_date ?? now(),
            'end_date' => $submission->contract_end_date,
            'terms' => $this->buildContractTerms($submission),
        ]);

        // Link contact as party
        $contract->contacts()->attach($contact->id, ['role' => 'party_a']);

        return $contract;
    }

    private function buildContractTerms(MusicSubmission $submission): string
    {
        $terms = [];

        if ($submission->contract_excluded_countries) {
            $terms[] = "Ausgeschlossene Länder: {$submission->contract_excluded_countries}";
        }
        if ($submission->contract_advance_interest) {
            $terms[] = "Vorschuss-Interesse: {$submission->contract_advance_interest}";
        }
        if ($submission->digital_signature) {
            $terms[] = "Digital signiert: {$submission->digital_signature}";
        }
        if ($submission->calculated_price) {
            $terms[] = "Berechneter Preis: CHF {$submission->calculated_price}";
        }
        if ($submission->song_count) {
            $terms[] = "Anzahl Songs: {$submission->song_count}";
        }
        if ($submission->payment_status) {
            $terms[] = "Zahlungsstatus: {$submission->payment_status}";
        }

        return implode("\n", $terms) ?: 'Keine zusätzlichen Bedingungen.';
    }
}
