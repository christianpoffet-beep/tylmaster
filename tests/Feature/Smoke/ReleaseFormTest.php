<?php

namespace Tests\Feature\Smoke;

use App\Models\Organization;
use App\Models\Release;
use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The product form: the type field and the tracklist build their inputs in
 * the browser, so both report in with a marker, and the page must hand its
 * data to Alpine intact - "Vinyl 12"" once cut the x-data attribute short and
 * printed half the script above the form.
 */
class ReleaseFormTest extends TestCase
{
    use RefreshDatabase;

    private Release $release;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
        $this->release = Release::create(['title' => 'Now To Ruin', 'product_type' => ['Vinyl 12"', 'Digital Single']]);
    }

    private function save(array $data)
    {
        return $this->put("/admin/releases/{$this->release->id}", array_merge(['title' => 'Now To Ruin'], $data));
    }

    /** The attributes Alpine reads, as a browser parses them. */
    private function alpineAttributes(string $html, string $name): array
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);

        return collect((new \DOMXPath($dom))->query("//*[@{$name}]"))->map(fn ($node) => $node->getAttribute($name))->all();
    }

    /** The data the tracklist component starts with - @js wraps it in JSON.parse('...'). */
    private function tracklistData(string $html): array
    {
        $config = collect($this->alpineAttributes($html, 'x-data'))->first(fn ($value) => str_starts_with($value, 'releaseTracklist('));
        $this->assertSame(1, preg_match("/^releaseTracklist\(JSON\.parse\('(.*)'\)\)$/s", (string) $config, $m));

        return json_decode(json_decode('"' . $m[1] . '"'), true);
    }

    public function test_the_edit_page_hands_types_and_tracks_to_alpine_intact(): void
    {
        $track = Track::create(['title' => 'Breeze "Live"', 'status' => 'released']);
        $this->release->tracks()->attach($track->id, ['track_number' => 1, 'disc_number' => 1, 'role' => 'main']);

        $html = $this->get("/admin/releases/{$this->release->id}/edit")->assertOk()->getContent();
        $xData = $this->alpineAttributes($html, 'x-data');

        $outer = collect($xData)->first(fn ($value) => str_contains($value, 'selectedTypes'));
        $this->assertNotNull($outer);
        $this->assertStringContainsString('get isMusic()', $outer, 'The x-data attribute was cut short.');

        $this->assertNotNull(collect($xData)->first(fn ($value) => str_starts_with($value, 'productTypeSelector(') && str_ends_with($value, ')')));
        $this->assertNotNull(collect($xData)->first(fn ($value) => str_starts_with($value, 'releaseTracklist(') && str_ends_with($value, ')')));
        $this->assertStringNotContainsString('this.merchTypes.includes(t)); }', strip_tags($html), 'Script text leaked onto the page.');

        foreach (['product_type_submitted', 'track_ids_submitted'] as $marker) {
            $this->assertStringContainsString('name="' . $marker . '" x-bind:value="\'1\'"', $html);
        }
    }

    public function test_the_track_form_hands_its_product_rows_to_alpine_intact(): void
    {
        $track = Track::create(['title' => 'Breeze', 'status' => 'released']);
        $track->releases()->attach($this->release->id, ['track_number' => 3, 'disc_number' => 1, 'role' => 'main']);

        $html = $this->get("/admin/tracks/{$track->id}/edit")->assertOk()->getContent();
        $rows = collect($this->alpineAttributes($html, 'x-data'))
            ->first(fn ($value) => str_contains($value, 'rows:') && !str_starts_with($value, 'trackAudioFiles('));

        $this->assertNotNull($rows);
        $this->assertStringContainsString('removeRow(index)', $rows, 'The product rows of the track form were cut short.');
    }

    public function test_types_are_saved_and_left_alone_when_the_field_did_not_report_in(): void
    {
        $this->save(['product_type_submitted' => '1', 'product_type' => ['CD', 'Vinyl 7"', 'CD', '']]);
        $this->assertSame(['CD', 'Vinyl 7"'], $this->release->fresh()->product_type);

        $this->save([]);
        $this->assertSame(['CD', 'Vinyl 7"'], $this->release->fresh()->product_type, 'Saved without the field: the types stay.');

        $this->save(['product_type_submitted' => '1']);
        $this->assertSame([], $this->release->fresh()->product_type, 'The field reported in empty: the types are cleared.');
    }

    public function test_the_tracklist_saves_order_and_numbers_and_keeps_disc_and_role(): void
    {
        $a = Track::create(['title' => 'A', 'status' => 'draft']);
        $b = Track::create(['title' => 'B', 'status' => 'draft']);
        $c = Track::create(['title' => 'C', 'status' => 'draft']);
        $this->release->tracks()->attach($a->id, ['track_number' => 1, 'disc_number' => 2, 'role' => 'bonus']);
        $this->release->tracks()->attach($c->id, ['track_number' => 2, 'disc_number' => 1, 'role' => 'main']);

        $this->save([
            'track_ids_submitted' => '1',
            'track_ids' => [$b->id, $a->id],
            'track_numbers' => [$b->id => 1, $a->id => 2],
        ])->assertRedirect("/admin/releases/{$this->release->id}");

        $tracks = $this->release->fresh()->tracks->keyBy('id');
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $tracks->keys()->all(), 'C was removed.');
        $this->assertSame(1, (int) $tracks[$b->id]->pivot->track_number);
        $this->assertSame(2, (int) $tracks[$a->id]->pivot->track_number);
        $this->assertSame(2, (int) $tracks[$a->id]->pivot->disc_number, 'Disc number kept.');
        $this->assertSame('bonus', $tracks[$a->id]->pivot->role, 'Role kept.');
        $this->assertSame('main', $tracks[$b->id]->pivot->role);
    }

    public function test_saving_without_the_tracklist_keeps_the_tracks(): void
    {
        $track = Track::create(['title' => 'A', 'status' => 'draft']);
        $this->release->tracks()->attach($track->id, ['track_number' => 4, 'disc_number' => 1, 'role' => 'main']);

        $this->save(['product_type_submitted' => '1', 'product_type' => ['CD']]);

        $this->assertSame(4, (int) $this->release->fresh()->tracks->first()->pivot->track_number);
    }

    public function test_a_new_product_gets_its_types_and_tracks(): void
    {
        $track = Track::create(['title' => 'A', 'status' => 'draft']);

        $this->post('/admin/releases', [
            'title' => 'Neu',
            'product_type_submitted' => '1',
            'product_type' => ['Digital EP'],
            'track_ids_submitted' => '1',
            'track_ids' => [$track->id],
            'track_numbers' => [$track->id => ''],
        ])->assertRedirect();

        $release = Release::where('title', 'Neu')->first();
        $this->assertSame(['Digital EP'], $release->product_type);
        $this->assertNull($release->tracks->first()->pivot->track_number);
    }

    public function test_an_invalid_track_number_comes_back_with_the_tracklist_as_entered(): void
    {
        $track = Track::create(['title' => 'A', 'status' => 'draft']);

        $this->from("/admin/releases/{$this->release->id}/edit")
            ->save(['track_ids_submitted' => '1', 'track_ids' => [$track->id], 'track_numbers' => [$track->id => 0]])
            ->assertSessionHasErrors("track_numbers.{$track->id}");

        $this->assertCount(0, $this->release->fresh()->tracks);
        $page = $this->get("/admin/releases/{$this->release->id}/edit")->assertOk()->assertSee('Tracknummern beginnen bei 1.');
        $rows = $this->tracklistData($page->getContent())['rows'];
        $this->assertSame([['id' => $track->id, 'number' => 0]], $rows, 'The row comes back as entered, next to its error.');
    }

    public function test_the_picker_offers_bands_and_other_products_with_their_track_order(): void
    {
        $band = Organization::create(['type' => 'band', 'names' => ['Tar Pond']]);
        $other = Release::create(['title' => 'Sammlung', 'catalog_number' => 'MAGE002']);
        $track = Track::create(['title' => 'A', 'status' => 'draft']);
        $track->organizations()->attach($band->id);
        $track->releases()->attach($other->id, ['track_number' => 5, 'disc_number' => 1, 'role' => 'main']);
        $track->releases()->attach($this->release->id, ['track_number' => 1, 'disc_number' => 1, 'role' => 'main']);

        $data = $this->tracklistData($this->get("/admin/releases/{$this->release->id}/edit")->assertOk()->getContent());

        $this->assertSame([['id' => $band->id, 'name' => 'Tar Pond']], $data['bands']);
        $this->assertSame([['id' => $other->id, 'title' => 'Sammlung (MAGE002)']], $data['releases'], 'The product itself is not offered.');
        $this->assertSame([$band->id], $data['tracks'][0]['bandIds']);
        $this->assertContains(['id' => $other->id, 'number' => 5], $data['tracks'][0]['releases']);
        $this->assertStringContainsString('tar pond', $data['tracks'][0]['haystack']);
    }

    public function test_the_quick_picks_put_the_most_used_types_first(): void
    {
        Release::create(['title' => 'X', 'product_type' => ['CD']]);
        Release::create(['title' => 'Y', 'product_type' => ['CD', 'Ausrangiert']]);

        $quick = Release::frequentProductTypes();

        $this->assertSame('CD', $quick[0]);
        $this->assertNotContains('Ausrangiert', $quick, 'Types the settings no longer list are not offered.');
        $this->assertCount(8, $quick);
    }
}
