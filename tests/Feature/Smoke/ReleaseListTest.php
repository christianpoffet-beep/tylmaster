<?php

namespace Tests\Feature\Smoke;

use App\Models\Artwork;
use App\Models\Organization;
use App\Models\Release;
use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The product list: band, label and type filters with several values each,
 * the main artwork per product, and a play button for the whole product.
 */
class ReleaseListTest extends TestCase
{
    use RefreshDatabase;

    private Organization $sgatv;
    private Organization $tarPond;
    private Organization $other;
    private Organization $lawless;
    private Organization $magic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
        $this->sgatv = Organization::create(['type' => 'band', 'names' => ['S.G.A.T.V']]);
        $this->tarPond = Organization::create(['type' => 'band', 'names' => ['Tar Pond']]);
        $this->other = Organization::create(['type' => 'band', 'names' => ['Ohne Produkt']]);
        $this->lawless = Organization::create(['type' => 'label', 'names' => ['Lawless Daydreamer']]);
        $this->magic = Organization::create(['type' => 'label', 'names' => ['Magic And Gain']]);
    }

    private function release(string $title, array $orgs = [], array $types = []): Release
    {
        $release = Release::create(['title' => $title, 'product_type' => $types]);
        $release->organizations()->attach(collect($orgs)->pluck('id')->all());

        return $release;
    }

    /** Titles of the products the list shows for the query. */
    private function titles(string $query = ''): array
    {
        $releases = $this->get('/admin/releases' . ($query ? '?' . $query : ''))->assertOk()->viewData('releases');

        return collect($releases->items())->pluck('title')->sort()->values()->all();
    }

    public function test_two_bands_and_a_label_any_band_on_that_label(): void
    {
        $this->release('Now To Ruin', [$this->sgatv, $this->lawless]);
        $this->release('Ozean', [$this->tarPond, $this->lawless]);
        $this->release('Andernorts', [$this->tarPond, $this->magic]);
        $this->release('Ohne Band', [$this->lawless]);

        $both = "band[]={$this->sgatv->id}&band[]={$this->tarPond->id}";
        $this->assertSame(['Andernorts', 'Now To Ruin', 'Ozean'], $this->titles($both));
        $this->assertSame(['Now To Ruin', 'Ozean'], $this->titles("{$both}&label[]={$this->lawless->id}"));
        $this->assertSame(['Andernorts', 'Now To Ruin', 'Ohne Band', 'Ozean'], $this->titles("label[]={$this->lawless->id}&label[]={$this->magic->id}"));
    }

    public function test_a_band_on_the_tracks_counts_too(): void
    {
        $release = $this->release('Sammlung');
        $track = Track::create(['title' => 'Breeze', 'status' => 'draft']);
        $track->organizations()->attach($this->sgatv->id);
        $release->tracks()->attach($track->id, ['track_number' => 1, 'disc_number' => 1, 'role' => 'main']);
        $this->release('Anderes');

        $this->assertSame(['Sammlung'], $this->titles("band[]={$this->sgatv->id}"));
        $this->get('/admin/releases')->assertSee('S.G.A.T.V');
    }

    public function test_several_types_and_the_old_single_type_link(): void
    {
        $this->release('Digital', [], ['Digital Single']);
        $this->release('Vinyl', [], ['Vinyl 12"', 'CD']);
        $this->release('Shirt', [], ['T-Shirt']);

        $this->assertSame(['Digital', 'Vinyl'], $this->titles('type[]=Digital+Single&type[]=CD'));
        $this->assertSame(['Shirt'], $this->titles('product_type=T-Shirt'));
    }

    public function test_the_filters_offer_only_what_occurs_and_show_chips_to_remove(): void
    {
        $this->release('Now To Ruin', [$this->sgatv, $this->lawless], ['CD']);
        $this->release('Ozean', [$this->tarPond]);

        $response = $this->get("/admin/releases?band[]={$this->sgatv->id}&band[]={$this->tarPond->id}&sort=title");
        $options = $response->viewData('filterOptions');

        $this->assertSame(['S.G.A.T.V', 'Tar Pond'], array_column($options['band'], 'label'), 'A band on no product is not offered.');
        $this->assertSame(['Lawless Daydreamer'], array_column($options['label'], 'label'));
        $this->assertSame(['CD'], array_column($options['type'], 'label'));

        $chips = collect($response->viewData('activeFilters'));
        $this->assertSame(['Band: S.G.A.T.V', 'Band: Tar Pond'], $chips->pluck('label')->all());
        // Removing S.G.A.T.V keeps Tar Pond and the sorting
        parse_str(parse_url($chips[0]['url'], PHP_URL_QUERY), $query);
        $this->assertEquals(['band' => [(string) $this->tarPond->id], 'sort' => 'title'], $query);

        $response->assertSee('name="band[]" value="' . $this->sgatv->id . '"', false);
    }

    public function test_the_main_artwork_is_shown_else_the_first(): void
    {
        $release = $this->release('Now To Ruin');
        $first = Artwork::create(['title' => 'Rückseite']);
        $main = Artwork::create(['title' => 'Cover']);
        foreach ([$first, $main] as $artwork) {
            $artwork->images()->create(['file_path' => "artworks/{$artwork->id}.jpg", 'original_name' => 'x.jpg']);
        }
        $release->artworks()->attach($first->id, ['is_primary' => false]);
        $release->artworks()->attach($main->id, ['is_primary' => true]);

        $this->assertSame($main->id, $release->fresh()->main_artwork->id);
        $this->get('/admin/releases')->assertSee(route('admin.artworks.thumbnail', $main), false);

        $release->artworks()->updateExistingPivot($main->id, ['is_primary' => false]);
        $this->assertSame($first->id, $release->fresh()->main_artwork->id);
    }

    public function test_a_product_with_audio_plays_as_a_whole_from_the_list(): void
    {
        $withAudio = $this->release('Now To Ruin');
        foreach (['A', 'B'] as $i => $title) {
            $track = Track::create(['title' => $title, 'status' => 'released']);
            $track->audioFiles()->create(['file_path' => "tracks/{$title}.wav", 'original_name' => "{$title}.wav", 'is_main' => true]);
            $withAudio->tracks()->attach($track->id, ['track_number' => $i + 1, 'disc_number' => 1, 'role' => 'main']);
        }
        $this->release('Stumm');

        $html = $this->get('/admin/releases')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, '$store.player.playQueue(album, 0)'), 'Only the product with audio gets a play button.');
        $this->assertStringContainsString('Ganzes Produkt abspielen (2 Tracks)', $html);
    }
}
