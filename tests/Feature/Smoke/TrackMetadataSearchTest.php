<?php

namespace Tests\Feature\Smoke;

use App\Models\Contact;
use App\Models\Organization;
use App\Models\Release;
use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The tracklist filter on a product used to match the title only. It now also
 * matches the alternative titles, the linked band, label and publisher plus the
 * credited people.
 */
class TrackMetadataSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function makeTrack(): Track
    {
        $track = Track::create([
            'title' => 'Nachtblau',
            'version' => 'Radio Edit',
            'isrc' => 'CHTYL2600001',
            'alternative_titles' => ['Night Blue'],
        ]);

        $band = Organization::create(['type' => 'band', 'names' => ['Tar Pond']]);
        $label = Organization::create(['type' => 'label', 'names' => ['The Yelling Light']]);
        $publisher = Organization::create(['type' => 'publishing', 'names' => ['Zentrum Musikverlag']]);
        $track->organizations()->attach([$band->id, $label->id, $publisher->id]);

        $credit = Contact::create(['first_name' => 'Anna', 'last_name' => 'Muster']);
        $track->contacts()->attach($credit->id, ['role' => 'guitar']);

        return $track->fresh(['organizations', 'contacts']);
    }

    public function test_haystack_covers_titles_isrc_organizations_and_credits(): void
    {
        $haystack = $this->makeTrack()->search_haystack;

        foreach (['nachtblau', 'radio edit', 'night blue', 'chtyl2600001', 'tar pond', 'the yelling light', 'zentrum musikverlag', 'anna muster'] as $needle) {
            $this->assertStringContainsString($needle, $haystack, "Missing from haystack: {$needle}");
        }
    }

    public function test_haystack_is_lowercased(): void
    {
        $this->assertSame(mb_strtolower($this->makeTrack()->search_haystack), $this->makeTrack()->search_haystack);
    }

    public function test_band_names_are_exposed_for_display(): void
    {
        $this->assertSame('Tar Pond', $this->makeTrack()->band_names);
    }

    /**
     * The filter runs in the browser over data rendered into the page, so the
     * metadata has to reach the HTML for the release form to be searchable.
     */
    public function test_release_form_carries_the_metadata_into_the_page(): void
    {
        $this->makeTrack();
        $release = Release::create(['title' => 'Sammlung 2026']);

        foreach (["/admin/releases/create", "/admin/releases/{$release->id}/edit"] as $url) {
            $response = $this->actingAs(User::factory()->create())->get($url);

            $response->assertOk();
            foreach (['tar pond', 'the yelling light', 'zentrum musikverlag', 'anna muster'] as $needle) {
                $this->assertStringContainsString($needle, $response->getContent(), "Missing on {$url}: {$needle}");
            }
        }
    }

    /**
     * Titles with an apostrophe used to be escaped by hand into a JS string
     * literal. @js() encodes them properly.
     */
    public function test_a_title_with_quotes_does_not_break_the_page(): void
    {
        Track::create(['title' => "Rock 'n' Roll \"live\""]);
        $release = Release::create(['title' => 'Sammlung 2026']);

        $html = $this->actingAs(User::factory()->create())
            ->get("/admin/releases/{$release->id}/edit")
            ->assertOk()
            ->getContent();

        // The tracklist hands its tracks to Alpine; the attribute has to arrive in one piece
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
        $config = collect((new \DOMXPath($dom))->query('//*[@x-data]'))
            ->map(fn ($node) => $node->getAttribute('x-data'))
            ->first(fn ($value) => str_starts_with($value, 'releaseTracklist('));

        $this->assertSame(1, preg_match("/^releaseTracklist\\(JSON\\.parse\\('(.*)'\\)\\)$/s", (string) $config, $m), 'The tracklist data was cut short.');
        $this->assertSame("Rock 'n' Roll \"live\"", json_decode(json_decode('"' . $m[1] . '"'), true)['tracks'][0]['title']);
    }
}
