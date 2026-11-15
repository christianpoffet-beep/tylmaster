<?php

namespace Tests\Feature\Smoke;

use App\Models\Organization;
use App\Models\Release;
use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Hauptkünstler" and "Label" on a product are linked band and label
 * organizations, no longer free text. Text typed before stays visible until
 * an organization of its kind is linked.
 */
class ReleaseArtistLabelTest extends TestCase
{
    use RefreshDatabase;

    private Organization $band;
    private Organization $label;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
        $this->band = Organization::create(['type' => 'band', 'names' => ['S.G.A.T.V']]);
        $this->label = Organization::create(['type' => 'label', 'names' => ['Lawless Daydreamer']]);
    }

    private function save(Release $release, array $data)
    {
        return $this->put("/admin/releases/{$release->id}", array_merge(['title' => $release->title], $data));
    }

    public function test_the_form_links_organizations_instead_of_free_text(): void
    {
        $release = Release::create(['title' => 'Now To Ruin']);

        foreach (["/admin/releases/{$release->id}/edit", '/admin/releases/create'] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertDontSee('name="main_artist"', false)
                ->assertDontSee('name="label_name"', false)
                ->assertSee('Hauptkünstler suchen...')
                ->assertSee('Label suchen...')
                ->assertSee('name="band_ids_submitted"', false)
                ->assertSee('name="label_ids_submitted"', false);
        }
    }

    public function test_old_text_shows_as_a_hint_until_an_organization_is_linked(): void
    {
        $release = Release::create(['title' => 'Alt', 'main_artist' => 'Unbekannte Band', 'label_name' => 'Altes Label']);

        $this->get("/admin/releases/{$release->id}/edit")
            ->assertSee('Bisher als Text erfasst: „Unbekannte Band“', false)
            ->assertSee('Bisher als Text erfasst: „Altes Label“', false);
        $this->get("/admin/releases/{$release->id}")->assertSee('Unbekannte Band')->assertSee('Altes Label');

        // Saved without linking anything: the text stays
        $this->save($release, ['band_ids_submitted' => '1', 'label_ids_submitted' => '1', 'publisher_ids_submitted' => '1']);
        $this->assertSame('Unbekannte Band', $release->fresh()->main_artist);

        // Linked: the organizations take over, the text gives way
        $this->save($release, [
            'band_ids_submitted' => '1', 'band_ids' => [$this->band->id],
            'label_ids_submitted' => '1', 'label_ids' => [$this->label->id],
            'main_artist' => 'Freitext wird ignoriert',
        ]);

        $fresh = $release->fresh('organizations');
        $this->assertEqualsCanonicalizing([$this->band->id, $this->label->id], $fresh->organizations->pluck('id')->all());
        $this->assertNull($fresh->main_artist);
        $this->assertNull($fresh->label_name);
        $this->assertSame('S.G.A.T.V', $fresh->main_artist_display);
        $this->assertSame('Lawless Daydreamer', $fresh->label_display);
    }

    public function test_a_product_without_label_takes_the_labels_of_its_tracks(): void
    {
        $release = Release::create(['title' => 'Neu']);
        $track = Track::create(['title' => 'A', 'status' => 'draft']);
        $track->organizations()->attach($this->label->id);
        $otherLabel = Organization::create(['type' => 'label', 'names' => ['Magic And Gain']]);

        $this->save($release, ['track_ids_submitted' => '1', 'track_ids' => [$track->id], 'label_ids_submitted' => '1']);
        $this->assertSame([$this->label->id], $release->fresh()->organizations->pluck('id')->all());

        // A label chosen on the product wins over the tracks'
        $this->save($release, ['track_ids_submitted' => '1', 'track_ids' => [$track->id], 'label_ids_submitted' => '1', 'label_ids' => [$otherLabel->id]]);
        $this->assertSame([$otherLabel->id], $release->fresh()->organizations->pluck('id')->all());
    }

    public function test_the_list_shows_and_finds_the_linked_label_and_artist(): void
    {
        $linked = Release::create(['title' => 'Verknüpft']);
        $linked->organizations()->attach([$this->band->id, $this->label->id]);
        Release::create(['title' => 'Anderes']);

        $this->get('/admin/releases')->assertSee('Lawless Daydreamer');
        $this->get('/admin/releases?search=daydream')->assertSee('Verknüpft')->assertDontSee('Anderes');
        $this->get('/admin/releases?search=s.g.a')->assertSee('Verknüpft')->assertDontSee('Anderes');
    }

    public function test_the_migration_links_the_organizations_the_old_text_names(): void
    {
        $exact = Release::create(['title' => 'Exakt', 'main_artist' => 's.g.a.t.v', 'label_name' => 'Lawless Daydreamer']);
        $list = Release::create(['title' => 'Liste', 'label_name' => 'Lawless Daydreamer, Unbekannt']);
        $alreadyLinked = Release::create(['title' => 'Schon', 'label_name' => 'Lawless Daydreamer']);
        $alreadyLinked->organizations()->attach($this->label->id);

        $migration = require database_path('migrations/2026_11_15_200000_link_release_artist_and_label_organizations.php');
        $migration->up();
        $migration->up(); // safe to run again

        $exact = $exact->fresh('organizations');
        $this->assertEqualsCanonicalizing([$this->band->id, $this->label->id], $exact->organizations->pluck('id')->all());
        $this->assertNull($exact->main_artist, 'Fully matched text gives way.');
        $this->assertNull($exact->label_name);

        $list = $list->fresh('organizations');
        $this->assertSame([$this->label->id], $list->organizations->pluck('id')->all());
        $this->assertSame('Lawless Daydreamer, Unbekannt', $list->label_name, 'A name without organization keeps the text.');

        $this->assertCount(1, $alreadyLinked->fresh()->organizations);
    }
}
