<?php

namespace Tests\Feature\Smoke;

use App\Models\Organization;
use App\Models\Release;
use App\Models\RoyaltyImport;
use App\Models\RoyaltyLine;
use App\Models\Track;
use App\Models\User;
use App\Services\Royalties\RoyaltyUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The usage of a track or a product, as Rebeat reported it: per month,
 * country, service and subscription - all narrowed by the same filters.
 */
class RoyaltyUsageTest extends TestCase
{
    use RefreshDatabase;

    private Track $track;
    private Release $release;

    protected function setUp(): void
    {
        parent::setUp();
        $band = Organization::create(['type' => 'band', 'names' => ['Tar Pond']]);
        $this->track = Track::create(['title' => 'Fog', 'status' => 'released']);
        $this->track->organizations()->attach($band);
        $this->release = Release::create(['title' => 'Coroner']);
        $this->release->tracks()->attach($this->track, ['track_number' => 1, 'disc_number' => 1]);

        $import = RoyaltyImport::create(['source' => 'rebeat', 'file_name' => 'x.csv', 'completed_at' => now(), 'confirmed_at' => now()]);
        $line = fn (array $values) => RoyaltyLine::create($values + ['royalty_import_id' => $import->id, 'source' => 'rebeat',
            'external_id' => uniqid('', true), 'track_id' => $this->track->id, 'sales_type' => 'Stream', 'sales_category' => 'Track',
            'country' => 'SWITZERLAND', 'portal' => 'Spotify', 'stream_type' => 'P']);

        $line(['period' => '2024-01-01', 'quantity' => 100, 'amount' => 0.40]);
        $line(['period' => '2024-01-01', 'quantity' => 50, 'amount' => 0.30, 'country' => 'GERMANY', 'portal' => 'iTunes', 'stream_type' => 'Family Monthly PAID']);
        $line(['period' => '2024-03-01', 'quantity' => 20, 'amount' => 0.10, 'sales_category' => 'Video', 'portal' => 'YouTube', 'stream_type' => null]);
        $line(['period' => '2025-02-01', 'quantity' => 2, 'amount' => 2.40, 'sales_type' => 'Download', 'portal' => 'iTunes', 'stream_type' => null]);
        // An album download of the product, without track
        $line(['period' => '2025-02-01', 'quantity' => 1, 'amount' => 5.00, 'track_id' => null, 'release_id' => $this->release->id,
            'sales_type' => 'Download', 'sales_category' => 'Album', 'portal' => 'iTunes', 'stream_type' => null]);
    }

    private function usageOf(array $filters, string $kind): int
    {
        return (new RoyaltyUsage($this->track, $filters))->totals()[$kind];
    }

    public function test_the_figures_of_a_track_and_its_product(): void
    {
        $track = new RoyaltyUsage($this->track);
        $totals = $track->totals();
        $this->assertSame([150, 2, 20, 4], [$totals['stream'], $totals['download'], $totals['video'], $totals['lines']]);
        $this->assertEqualsWithDelta(3.2, $totals['amount'], 0.0001);

        $this->assertSame(3, (new RoyaltyUsage($this->release))->totals()['download'], 'The album download counts for the product');

        // Months without usage in between are there, as zero
        $months = $track->months();
        $this->assertSame(['2024-01', '2025-02'], [$months->first()->month->format('Y-m'), $months->last()->month->format('Y-m')]);
        $this->assertCount(14, $months);
        $this->assertSame(0, $months[1]->quantity);

        $this->assertSame(['Switzerland', 'Germany'], $track->ranking('country')->pluck('name')->all());
        $this->assertSame(['Spotify', 'iTunes', 'YouTube'], $track->ranking('portal')->pluck('name')->all());
        $this->assertSame(['P', 'Family Monthly PAID', 'ohne Angabe'], $track->ranking('stream_type')->pluck('name')->all());
        $this->assertSame(['P', 'Übrige (2)'], $track->ranking('stream_type', 1)->pluck('name')->all(), 'A long tail folds into one row');
        $this->assertCount(2, $track->ranking('country', 1), 'A single one left is shown, not folded');
    }

    public function test_the_filters_narrow_every_figure(): void
    {
        $this->assertSame([150, 0, 20], [$this->usageOf(['year' => '2024'], 'stream'), $this->usageOf(['year' => '2024'], 'download'), $this->usageOf(['year' => '2024'], 'video')]);
        $this->assertSame(50, $this->usageOf(['country' => 'GERMANY'], 'stream'));
        $this->assertSame([2, 0], [$this->usageOf(['kind' => 'download'], 'download'), $this->usageOf(['kind' => 'download'], 'stream')]);
        $this->assertSame(20, $this->usageOf(['stream_type' => '—', 'kind' => 'video'], 'video'));
        $this->assertSame(['2025', '2024'], (new RoyaltyUsage($this->track, ['year' => '2024']))->options()['year'], 'The options keep the whole range');
    }

    public function test_the_pages_show_the_usage(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get("/admin/tracks/{$this->track->id}")->assertOk()
            ->assertSee('Nutzung bei Rebeat')->assertSee('Nach Monat, Land, Dienst und Abotyp ansehen');
        $this->actingAs($user)->get("/admin/releases/{$this->release->id}")->assertOk()->assertSee('Nutzung bei Rebeat');

        $this->actingAs($user)->get("/admin/royalties/usage?track={$this->track->id}")->assertOk()
            ->assertSee('Nutzung: Fog')->assertSee('Nutzungen pro Monat')->assertSee('Switzerland')->assertSee('Family Monthly PAID')
            ->assertSee('Einzelne Meldungen (4)')->assertSee('Jan 2024: 150 Nutzungen, EUR 0.70', false);
        $this->actingAs($user)->get("/admin/royalties/usage?track={$this->track->id}&country=GERMANY")->assertOk()
            ->assertSee('Einzelne Meldungen (1)')->assertSee('Filter zurücksetzen');
        $this->actingAs($user)->get("/admin/royalties/usage?release={$this->release->id}")->assertOk()->assertSee('Einzelne Meldungen (5)');

        $other = Track::create(['title' => 'Still', 'status' => 'released']);
        $this->actingAs($user)->get("/admin/tracks/{$other->id}")->assertOk()->assertDontSee('Nutzung bei Rebeat');
    }
}
