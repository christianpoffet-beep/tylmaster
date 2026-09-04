<?php

namespace Tests\Feature\Smoke;

use App\Models\Contact;
use App\Models\Contract;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Release;
use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every relation section of the track form renders its inputs through Alpine.
 * When that does not run, the fields are absent from the request - which an
 * unguarded sync() reads as "remove everything". Editing a track then silently
 * detached it from its products, bands, credits, projects and contracts.
 */
class TrackRelationSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected Track $track;
    protected Release $release;

    protected function setUp(): void
    {
        parent::setUp();

        $this->track = Track::create(['title' => 'Nachtblau', 'status' => 'draft']);
        $this->release = Release::create(['title' => 'Sammlung 2026']);

        $this->track->releases()->attach($this->release->id, ['track_number' => 3, 'disc_number' => 1, 'role' => 'main']);
        $this->track->organizations()->attach(Organization::create(['type' => 'band', 'names' => ['Tar Pond']])->id);
        $this->track->contacts()->attach(Contact::create(['first_name' => 'Anna', 'last_name' => 'Muster'])->id, ['role' => 'composer']);
        $this->track->projects()->attach(Project::create(['name' => 'Alpha', 'status' => 'in_progress'])->id);
        $this->track->contracts()->attach(Contract::create(['title' => 'Vertrag', 'type' => 'other', 'status' => 'draft'])->id);
    }

    /** The minimum a save needs; everything else comes from the JS sections. */
    protected function minimalPayload(array $extra = []): array
    {
        return array_merge(['title' => 'Nachtblau', 'status' => 'draft'], $extra);
    }

    /**
     * The exact report: adding an ISRC detached the track from its product.
     */
    public function test_adding_an_isrc_keeps_the_product_link(): void
    {
        $this->actingAs(User::factory()->create())
            ->put("/admin/tracks/{$this->track->id}", $this->minimalPayload(['isrc' => 'CHTYL2600001']))
            ->assertRedirect();

        $this->assertSame('CHTYL2600001', $this->track->fresh()->isrc);
        $this->assertCount(1, $this->track->fresh()->releases, 'The product link was lost.');
    }

    public function test_a_save_without_the_js_sections_keeps_every_relation(): void
    {
        $this->actingAs(User::factory()->create())
            ->put("/admin/tracks/{$this->track->id}", $this->minimalPayload());

        $track = $this->track->fresh(['releases', 'organizations', 'contacts', 'projects', 'contracts']);

        $this->assertCount(1, $track->releases, 'Products lost');
        $this->assertCount(1, $track->organizations, 'Band lost');
        $this->assertCount(1, $track->contacts, 'Credits lost');
        $this->assertCount(1, $track->projects, 'Projects lost');
        $this->assertCount(1, $track->contracts, 'Contracts lost');
    }

    /** The pivot data has to survive too, not just the link. */
    public function test_the_track_number_on_the_product_survives(): void
    {
        $this->actingAs(User::factory()->create())
            ->put("/admin/tracks/{$this->track->id}", $this->minimalPayload(['isrc' => 'CHTYL2600001']));

        $this->assertSame(3, (int) $this->track->fresh()->releases->first()->pivot->track_number);
    }

    /**
     * The guard must not block a real edit: when the section reports in, the
     * submitted list still wins - including clearing it.
     */
    public function test_a_section_that_reports_in_still_syncs(): void
    {
        $other = Release::create(['title' => 'Andere Sammlung']);

        $this->actingAs(User::factory()->create())
            ->put("/admin/tracks/{$this->track->id}", $this->minimalPayload([
                'release_ids_submitted' => '1',
                'release_ids' => [$other->id],
                'release_track_numbers' => [7],
            ]));

        $releases = $this->track->fresh()->releases;

        $this->assertCount(1, $releases);
        $this->assertSame($other->id, $releases->first()->id);
        $this->assertSame(7, (int) $releases->first()->pivot->track_number);
    }

    public function test_a_section_can_still_be_emptied_deliberately(): void
    {
        $this->actingAs(User::factory()->create())
            ->put("/admin/tracks/{$this->track->id}", $this->minimalPayload([
                'release_ids_submitted' => '1',
                'release_ids' => [],
            ]));

        $this->assertCount(0, $this->track->fresh()->releases);
    }

    public function test_credits_are_not_wiped_when_the_section_is_absent(): void
    {
        $this->actingAs(User::factory()->create())
            ->put("/admin/tracks/{$this->track->id}", $this->minimalPayload());

        $this->assertSame('composer', $this->track->fresh()->contacts->first()->pivot->role);
    }

    /**
     * The heart of the fix. The marker used to be plain HTML with value="1",
     * so it submitted even when Alpine had not rendered a single release row -
     * marker present, list empty, everything detached. Alpine now fills the
     * value, so a section that never rendered stays silent.
     */
    public function test_the_release_marker_is_filled_in_by_alpine_not_by_the_template(): void
    {
        $html = $this->actingAs(User::factory()->create())
            ->get("/admin/tracks/{$this->track->id}/edit")
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString(
            'name="release_ids_submitted" value="1"',
            $html,
            'A statically set marker submits even when the section never rendered.'
        );
        $this->assertMatchesRegularExpression(
            '/name="release_ids_submitted"\s+value=""\s+x-bind:value/',
            $html
        );
    }

    /** The existing links must reach the Alpine component, or a save drops them. */
    public function test_the_form_hands_the_existing_products_to_the_component(): void
    {
        $html = $this->actingAs(User::factory()->create())
            ->get("/admin/tracks/{$this->track->id}/edit")
            ->getContent();

        $this->assertStringContainsString('"release_id":"' . $this->release->id . '"', $html);
    }
}
