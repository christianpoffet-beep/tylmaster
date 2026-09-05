<?php

namespace Tests\Feature\Smoke;

use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Was haben wir 2024 aufgenommen?" - the track list filters by recording year.
 * The years are stored as free text ("2024 - 2026"), so a span has to answer for
 * every year it covers, not just the two that are written down.
 */
class TrackRecordingYearFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Track::create(['title' => 'Langzeitprojekt', 'status' => 'draft', 'recording_years' => '2024 - 2026']);
        Track::create(['title' => 'Schnellschuss', 'status' => 'draft', 'recording_years' => '2019']);
        Track::create(['title' => 'Ohne Angabe', 'status' => 'draft']);
    }

    public static function spanProvider(): array
    {
        return [
            'single year' => ['2026', [2026]],
            'span' => ['2024 - 2026', [2024, 2025, 2026]],
            'span without spaces' => ['2024-2025', [2024, 2025]],
            'reversed span' => ['2026 - 2024', [2024, 2025, 2026]],
            'several entries' => ['2019, 2024 - 2025', [2019, 2024, 2025]],
            'nothing' => [null, []],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('spanProvider')]
    public function test_a_span_covers_every_year_in_between(?string $value, array $expected): void
    {
        $this->assertSame($expected, Track::expandRecordingYears($value));
    }

    public function test_the_filter_finds_a_year_inside_a_span(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/tracks?recording_year=2025')
            ->assertOk()
            ->assertSee('Langzeitprojekt')
            ->assertDontSee('Schnellschuss')
            ->assertDontSee('Ohne Angabe');
    }

    public function test_the_filter_leaves_out_years_nobody_recorded_in(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/tracks?recording_year=2021')
            ->assertOk()
            ->assertDontSee('Langzeitprojekt')
            ->assertDontSee('Schnellschuss');
    }

    /** Every year covered by a span is offered, not just the ones written down. */
    public function test_the_dropdown_offers_the_years_in_use(): void
    {
        $html = $this->actingAs(User::factory()->create())
            ->get('/admin/tracks')
            ->assertOk()
            ->getContent();

        foreach (['2019', '2024', '2025', '2026'] as $year) {
            $this->assertStringContainsString('value="' . $year . '"', $html, "Year missing from the filter: {$year}");
        }
    }

    public function test_the_list_can_be_sorted_by_recording_year(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get('/admin/tracks?sort=recording_years&dir=desc')
            ->assertOk();

        $html = $response->getContent();
        $this->assertLessThan(
            strpos($html, 'Schnellschuss'),
            strpos($html, 'Langzeitprojekt'),
            'Sorting by recording year did not reorder the list.'
        );
    }
}
