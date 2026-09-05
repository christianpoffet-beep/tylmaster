<?php

namespace Tests\Feature\Smoke;

use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A track can carry alternative titles (working title, original title, ...).
 * They are stored as a JSON list and every search that looks at the title has
 * to match them too - otherwise the field would be write-only.
 */
class TrackAlternativeTitleTest extends TestCase
{
    use RefreshDatabase;

    protected function makeTrack(array $titles = ['Arbeitstitel Grün', 'Night Blue']): Track
    {
        return Track::create([
            'title' => 'Nachtblau',
            'status' => 'draft',
            'alternative_titles' => $titles,
        ]);
    }

    /** Umlauts must survive as themselves, or a LIKE over the column misses them. */
    public function test_titles_are_stored_unescaped(): void
    {
        $this->makeTrack();

        $this->assertStringContainsString(
            'Grün',
            DB::table('tracks')->value('alternative_titles'),
            'The JSON column escaped the Umlaut - searching for it would no longer work.'
        );
    }

    public function test_the_track_list_finds_a_track_by_its_alternative_title(): void
    {
        $this->makeTrack();

        $this->actingAs(User::factory()->create())
            ->get('/admin/tracks?search=Night+Blue')
            ->assertOk()
            ->assertSee('Nachtblau');
    }

    public function test_the_track_list_still_only_finds_matching_tracks(): void
    {
        $this->makeTrack();
        Track::create(['title' => 'Morgenrot', 'status' => 'draft']);

        $this->actingAs(User::factory()->create())
            ->get('/admin/tracks?search=Night+Blue')
            ->assertOk()
            ->assertDontSee('Morgenrot');
    }

    public function test_the_track_picker_finds_a_track_by_its_alternative_title(): void
    {
        $track = $this->makeTrack();

        $results = $this->actingAs(User::factory()->create())
            ->getJson('/admin/tracks-search?q=' . urlencode('Arbeitstitel Grün'))
            ->assertOk()
            ->json();

        $this->assertCount(1, $results);
        $this->assertSame($track->id, $results[0]['id']);
        $this->assertSame('Arbeitstitel Grün, Night Blue', $results[0]['alt']);
    }

    public function test_the_tracklist_filter_matches_them_too(): void
    {
        $this->assertStringContainsString('night blue', $this->makeTrack()->fresh()->search_haystack);
    }

    public function test_saving_stores_the_titles_without_blank_rows_or_duplicates(): void
    {
        $track = Track::create(['title' => 'Nachtblau', 'status' => 'draft']);

        $this->actingAs(User::factory()->create())
            ->put("/admin/tracks/{$track->id}", [
                'title' => 'Nachtblau',
                'status' => 'draft',
                'alternative_titles_submitted' => '1',
                'alternative_titles' => ['Night Blue', '  ', 'Night Blue', ' Arbeitstitel '],
            ])
            ->assertRedirect();

        $this->assertSame(['Night Blue', 'Arbeitstitel'], $track->fresh()->alternative_titles);
    }

    public function test_clearing_every_row_removes_the_titles(): void
    {
        $track = $this->makeTrack();

        $this->actingAs(User::factory()->create())
            ->put("/admin/tracks/{$track->id}", [
                'title' => 'Nachtblau',
                'status' => 'draft',
                'alternative_titles_submitted' => '1',
            ])
            ->assertRedirect();

        $this->assertSame([], $track->fresh()->alternative_titles);
    }

    /**
     * Same guard as the relation sections: a save whose JS never rendered must
     * not read the missing inputs as "the user deleted them".
     */
    public function test_a_save_without_the_marker_keeps_the_titles(): void
    {
        $track = $this->makeTrack();

        $this->actingAs(User::factory()->create())
            ->put("/admin/tracks/{$track->id}", ['title' => 'Nachtblau', 'status' => 'draft'])
            ->assertRedirect();

        $this->assertSame(['Arbeitstitel Grün', 'Night Blue'], $track->fresh()->alternative_titles);
    }

    public function test_the_form_offers_the_field(): void
    {
        $track = $this->makeTrack();

        $this->actingAs(User::factory()->create())
            ->get("/admin/tracks/{$track->id}/edit")
            ->assertOk()
            ->assertSee('Alternativtitel')
            ->assertSee('name="alternative_titles_submitted"', false);
    }

    public function test_the_detail_page_shows_them(): void
    {
        $track = $this->makeTrack();

        $this->actingAs(User::factory()->create())
            ->get("/admin/tracks/{$track->id}")
            ->assertOk()
            ->assertSee('Arbeitstitel Grün, Night Blue');
    }
}
