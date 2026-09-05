<?php

namespace Tests\Feature\Smoke;

use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * An alternative title that another track already carries as its own title is
 * the usual shape of a doublette in the catalogue. The save still goes through -
 * this is a hint, not a rule.
 */
class TrackDuplicateTitleWarningTest extends TestCase
{
    use RefreshDatabase;

    protected function saveWithAlternativeTitles(array $titles)
    {
        $track = Track::create(['title' => 'Nachtblau', 'status' => 'draft']);

        return [$this->actingAs(User::factory()->create())
            ->put("/admin/tracks/{$track->id}", [
                'title' => 'Nachtblau',
                'status' => 'draft',
                'alternative_titles_submitted' => '1',
                'alternative_titles' => $titles,
            ]), $track];
    }

    public function test_it_warns_about_a_track_that_already_carries_the_title(): void
    {
        Track::create(['title' => 'Night Blue', 'status' => 'released']);

        [$response, $track] = $this->saveWithAlternativeTitles(['Night Blue']);

        $response->assertRedirect()->assertSessionHas('warning');
        $this->assertStringContainsString('Night Blue', session('warning'));

        // The hint does not block the save.
        $this->assertSame(['Night Blue'], $track->fresh()->alternative_titles);
    }

    public function test_the_comparison_ignores_case_and_spacing(): void
    {
        Track::create(['title' => 'Night Blue', 'status' => 'released']);

        $this->saveWithAlternativeTitles(['  night blue  '])[0]
            ->assertSessionHas('warning');
    }

    public function test_it_stays_quiet_when_the_title_is_free(): void
    {
        Track::create(['title' => 'Morgenrot', 'status' => 'released']);

        $this->saveWithAlternativeTitles(['Night Blue'])[0]
            ->assertSessionMissing('warning');
    }

    /** A track may repeat its own title as an alternative one without a fuss. */
    public function test_the_track_itself_never_triggers_the_warning(): void
    {
        $this->saveWithAlternativeTitles(['Nachtblau'])[0]
            ->assertSessionMissing('warning');
    }

    public function test_several_clashes_are_named_together(): void
    {
        Track::create(['title' => 'Night Blue', 'status' => 'released']);
        Track::create(['title' => 'Morgenrot', 'status' => 'released']);

        $this->saveWithAlternativeTitles(['Night Blue', 'Morgenrot'])[0]
            ->assertSessionHas('warning');

        $this->assertStringContainsString('Night Blue', session('warning'));
        $this->assertStringContainsString('Morgenrot', session('warning'));
    }

    public function test_a_new_track_is_checked_as_well(): void
    {
        Track::create(['title' => 'Night Blue', 'status' => 'released']);

        $this->actingAs(User::factory()->create())
            ->post('/admin/tracks', [
                'title' => 'Nachtblau',
                'status' => 'draft',
                'alternative_titles_submitted' => '1',
                'alternative_titles' => ['Night Blue'],
            ])
            ->assertRedirect()
            ->assertSessionHas('warning');
    }
}
