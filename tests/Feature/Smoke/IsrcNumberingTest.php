<?php

namespace Tests\Feature\Smoke;

use App\Models\Setting;
use App\Models\Track;
use App\Models\User;
use App\Services\IsrcRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * "Generieren" next to an ISRC field hands out the next code in sequence -
 * over all tracks and versions, whatever catalogue - starting again every
 * year. A code that is in use elsewhere in the admin earns a warning, never
 * a refusal.
 */
class IsrcNumberingTest extends TestCase
{
    use RefreshDatabase;

    private IsrcRegistry $isrcs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
        $this->isrcs = app(IsrcRegistry::class);
        Carbon::setTestNow('2026-11-15 12:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function track(string $title, ?string $isrc, array $versionIsrcs = []): Track
    {
        $track = Track::create(['title' => $title, 'status' => 'draft', 'isrc' => $isrc]);
        foreach ($versionIsrcs as $i => $code) {
            $track->audioFiles()->create(['file_path' => "tracks/{$title}-{$i}.wav", 'original_name' => "{$title} {$i}.wav", 'title' => "Version {$i}", 'isrc' => $code, 'is_main' => $i === 0]);
        }

        return $track;
    }

    public function test_numbering_starts_at_the_given_start_number_this_year(): void
    {
        $this->track('Now To Ruin', 'CH4812600019');

        $this->assertSame('CH4812600022', $this->isrcs->next());
    }

    public function test_it_continues_after_the_highest_code_on_tracks_and_versions_and_never_fills_gaps(): void
    {
        $this->track('A', 'CH4812600030');
        $this->track('B', null, ['CH-481-26-00041']); // stored with dashes from before normalizing
        $this->track('C', 'CH4812600025');
        $this->track('Fremd', 'DEABC2600999'); // another registrant does not count

        $this->assertSame('CH4812600042', $this->isrcs->next());
    }

    public function test_codes_typed_into_the_form_count_as_taken(): void
    {
        $this->assertSame('CH4812600024', $this->isrcs->next(['CH-481-26-00022', 'CH4812600023', 'murks']));
    }

    public function test_a_new_year_starts_again_at_one(): void
    {
        $this->track('A', 'CH4812600512');
        Carbon::setTestNow('2027-01-02 09:00');

        $this->assertSame('CH4812700001', $this->isrcs->next());

        $this->track('B', 'CH4812700001');
        $this->assertSame('CH4812700002', $this->isrcs->next());
    }

    public function test_prefix_and_start_can_be_set(): void
    {
        Setting::set('isrc_numbering', ['prefix' => 'CHXYZ', 'start_year' => '26', 'start_number' => 500]);

        $this->assertSame('CHXYZ2600500', $this->isrcs->next());
    }

    public function test_the_generate_endpoint_answers_formatted(): void
    {
        $this->getJson('/admin/tracks-isrc/next?taken[]=CH4812600022')
            ->assertOk()
            ->assertJson(['isrc' => 'CH4812600023', 'formatted' => 'CH-481-26-00023']);
    }

    public function test_duplicates_are_found_on_other_tracks_and_versions_but_not_on_the_track_itself(): void
    {
        $own = $this->track('Eigen', 'CH4812600050', ['CH4812600050']);
        $other = $this->track('Breeze', null, ['CH4812600050']);
        $this->track('Dritter', 'CH4812600050');

        $labels = $this->isrcs->duplicates('CH-481-26-00050', $own->id)->pluck('label')->all();
        $this->assertSame(['«Dritter»', '«Breeze» – Audioversion «Version 0»'], $labels);

        $this->getJson("/admin/tracks-isrc/check?isrc=CH4812600050&track={$other->id}")
            ->assertOk()
            ->assertJsonCount(2, 'duplicates')
            ->assertJsonPath('duplicates.0.url', route('admin.tracks.show', $own));

        $this->getJson('/admin/tracks-isrc/check?isrc=CH4812699999')->assertJsonCount(0, 'duplicates');
    }

    public function test_saving_a_used_code_warns_but_saves(): void
    {
        $this->track('Now To Ruin', 'CH4812600010');
        $track = $this->track('Neu', null);

        $this->put("/admin/tracks/{$track->id}", ['title' => 'Neu', 'status' => 'draft', 'isrc' => 'CH-481-26-00010'])
            ->assertRedirect()
            ->assertSessionHas('warning', fn ($warning) => str_contains($warning, 'CH-481-26-00010 ist bereits vergeben: «Now To Ruin»'));

        $this->assertSame('CH4812600010', $track->fresh()->isrc, 'The code is saved all the same.');
    }

    public function test_two_versions_of_one_track_with_the_same_code_are_pointed_out(): void
    {
        $track = $this->track('Neu', 'CH4812600060', ['CH4812600061', 'CH4812600061']);

        $this->put("/admin/tracks/{$track->id}", ['title' => 'Neu', 'status' => 'draft', 'isrc' => 'CH4812600060'])
            ->assertSessionHas('warning', fn ($warning) => str_contains($warning, 'CH-481-26-00061 steht bei mehreren Audioversionen'));
    }

    public function test_a_free_code_and_the_main_version_sharing_the_track_code_stay_quiet(): void
    {
        $track = $this->track('Neu', 'CH4812600070', ['CH4812600070']);

        $this->put("/admin/tracks/{$track->id}", ['title' => 'Neu', 'status' => 'draft', 'isrc' => 'CH4812600070'])
            ->assertSessionMissing('warning');
    }

    public function test_the_forms_offer_the_generator_on_every_isrc_field(): void
    {
        $track = $this->track('Neu', 'CH4812600070', ['CH4812600070']);

        $html = $this->get("/admin/tracks/{$track->id}/edit")->assertOk()->getContent();
        $this->assertSame(3, substr_count($html, 'data-isrc-scope="'), 'Track, existing version, template for new versions.');
        $this->assertStringContainsString('value="CH-481-26-00070"', $html);

        $this->get('/admin/tracks/create')->assertOk()->assertSee('data-isrc-scope="track"', false)->assertSee('Generieren');
    }
}
