<?php

namespace Tests\Feature\Smoke;

use App\Models\Contact;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Release;
use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * The tracklist search used to look at title, version, ISRC and the free-text
 * credit fields only. It now finds a track by everything linked to it, and
 * every word of the search has to turn up somewhere.
 */
class TrackListFilterTest extends TestCase
{
    use RefreshDatabase;

    private Track $nachtblau;
    private Track $morgenrot;
    private Track $fremd;
    private Organization $tarPond;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());

        $this->tarPond = Organization::create(['type' => 'band', 'names' => ['Tar Pond', 'TP']]);
        $zueriWest = Organization::create(['type' => 'band', 'names' => ['Züri West']]);

        $this->nachtblau = Track::create(['title' => 'Nachtblau', 'status' => 'draft', 'genre' => 'Rock', 'recording_location' => 'Studio Alpha']);
        $this->morgenrot = Track::create(['title' => 'Morgenrot', 'status' => 'released', 'genre' => 'Pop']);
        $this->fremd = Track::create(['title' => 'Nachtblau', 'version' => 'Cover', 'status' => 'draft']);

        $this->nachtblau->organizations()->attach($this->tarPond->id);
        $this->morgenrot->organizations()->attach($this->tarPond->id);
        $this->fremd->organizations()->attach($zueriWest->id);
    }

    /** Asserts which tracks the list shows, by their links - titles may repeat. */
    private function assertListed(TestResponse $response, array $shown, array $hidden): void
    {
        $response->assertOk();

        foreach ($shown as $track) {
            $response->assertSee('href="' . route('admin.tracks.show', $track) . '"', false);
        }
        foreach ($hidden as $track) {
            $response->assertDontSee('href="' . route('admin.tracks.show', $track) . '"', false);
        }
    }

    private function search(string $search): TestResponse
    {
        return $this->get('/admin/tracks?search=' . urlencode($search));
    }

    public function test_a_band_finds_its_tracks_also_under_another_name_and_with_umlauts(): void
    {
        $this->assertListed($this->search('tar pond'), [$this->nachtblau, $this->morgenrot], [$this->fremd]);
        $this->assertListed($this->search('TP'), [$this->nachtblau, $this->morgenrot], [$this->fremd]);
        $this->assertListed($this->search('züri'), [$this->fremd], [$this->nachtblau, $this->morgenrot]);
        $this->assertListed($this->search('ZÜRI WEST'), [$this->fremd], [$this->nachtblau, $this->morgenrot]);
    }

    public function test_every_word_has_to_match_somewhere(): void
    {
        // "Nachtblau" alone finds both tracks of that title, the band narrows it down.
        $this->assertListed($this->search('nachtblau'), [$this->nachtblau, $this->fremd], [$this->morgenrot]);
        $this->assertListed($this->search('nachtblau tar'), [$this->nachtblau], [$this->fremd, $this->morgenrot]);
        $this->assertListed($this->search('nachtblau cover'), [$this->fremd], [$this->nachtblau, $this->morgenrot]);
    }

    public function test_quotes_keep_a_phrase_together(): void
    {
        $this->assertListed($this->search('"tar pond"'), [$this->nachtblau, $this->morgenrot], [$this->fremd]);
        $this->assertListed($this->search('"pond tar"'), [], [$this->nachtblau, $this->morgenrot, $this->fremd]);
    }

    public function test_people_products_projects_and_versions_are_searchable(): void
    {
        $this->nachtblau->contacts()->attach(Contact::create(['first_name' => 'Anna', 'last_name' => 'Muster'])->id, ['role' => 'composer']);
        $this->morgenrot->releases()->attach(Release::create(['title' => 'Sammlung 2026'])->id, ['track_number' => 1, 'disc_number' => 1, 'role' => 'main']);
        $this->fremd->projects()->attach(Project::create(['name' => 'Herbsttour', 'status' => 'in_progress'])->id);
        $this->morgenrot->audioFiles()->create([
            'file_path' => 'tracks/x.wav', 'title' => 'Instrumental', 'isrc' => 'CHTYL2600042', 'notes' => 'ohne Gesang', 'is_main' => true,
        ]);

        $this->assertListed($this->search('muster'), [$this->nachtblau], [$this->morgenrot, $this->fremd]);
        $this->assertListed($this->search('anna muster'), [$this->nachtblau], [$this->morgenrot, $this->fremd]);
        $this->assertListed($this->search('sammlung'), [$this->morgenrot], [$this->nachtblau, $this->fremd]);
        $this->assertListed($this->search('herbsttour'), [$this->fremd], [$this->nachtblau, $this->morgenrot]);
        $this->assertListed($this->search('instrumental'), [$this->morgenrot], [$this->nachtblau, $this->fremd]);
        $this->assertListed($this->search('gesang'), [$this->morgenrot], [$this->nachtblau, $this->fremd]);
        $this->assertListed($this->search('CH-TYL-26-00042'), [$this->morgenrot], [$this->nachtblau, $this->fremd]);
        $this->assertListed($this->search('chtyl2600042'), [$this->morgenrot], [$this->nachtblau, $this->fremd]);
    }

    public function test_descriptive_fields_are_searchable(): void
    {
        $this->assertListed($this->search('alpha'), [$this->nachtblau], [$this->morgenrot, $this->fremd]);
        $this->assertListed($this->search('rock'), [$this->nachtblau], [$this->morgenrot, $this->fremd]);
    }

    public function test_the_band_filter_shows_only_that_band_and_lists_bands_with_tracks(): void
    {
        Organization::create(['type' => 'band', 'names' => ['Ohne Tracks']]);

        $response = $this->get('/admin/tracks?band=' . $this->tarPond->id);

        $this->assertListed($response, [$this->nachtblau, $this->morgenrot], [$this->fremd]);
        $response->assertSee('Alle Bands')->assertSee('Züri West')->assertDontSee('Ohne Tracks');
    }

    /** The dropdown used to send the genre's ID, the track stores its name - nothing ever matched. */
    public function test_the_genre_filter_works_on_the_stored_name(): void
    {
        $response = $this->get('/admin/tracks?genre=Rock');

        $this->assertListed($response, [$this->nachtblau], [$this->morgenrot, $this->fremd]);
        $response->assertSee('<option value="Rock" selected', false);
    }

    public function test_filters_combine_and_the_hits_are_counted(): void
    {
        $this->get('/admin/tracks?band=' . $this->tarPond->id . '&status=released')
            ->assertSee('1 Track gefunden');

        $this->assertListed(
            $this->get('/admin/tracks?search=nachtblau&band=' . $this->tarPond->id),
            [$this->nachtblau],
            [$this->morgenrot, $this->fremd]
        );
    }

    public function test_the_track_picker_uses_the_same_search(): void
    {
        $ids = collect($this->getJson('/admin/tracks-search?q=' . urlencode('tar pond'))->assertOk()->json())->pluck('id')->sort()->values()->all();

        $this->assertSame(collect([$this->nachtblau->id, $this->morgenrot->id])->sort()->values()->all(), $ids);
    }

    public function test_search_terms_split_words_and_keep_quoted_phrases(): void
    {
        $this->assertSame(['tar', 'pond life', 'ch-tyl'], Track::searchTerms('  Tar  "Pond Life" CH-TYL '));
        $this->assertSame(['offen'], Track::searchTerms('"offen'));
        $this->assertSame([], Track::searchTerms('   '));
    }
}
