<?php

namespace Tests\Feature\Smoke;

use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Aufnahmeort and Aufnahmejahr(e) on a track. The years are free text on
 * purpose - a single year and a span both have to fit - but they still have to
 * look like years, so a typo does not end up in the catalogue.
 */
class TrackRecordingFieldsTest extends TestCase
{
    use RefreshDatabase;

    protected function save(array $payload)
    {
        $track = Track::create(['title' => 'Nachtblau', 'status' => 'draft']);

        $response = $this->actingAs(User::factory()->create())
            ->put("/admin/tracks/{$track->id}", array_merge(
                ['title' => 'Nachtblau', 'status' => 'draft'],
                $payload
            ));

        return [$response, $track->fresh()];
    }

    public function test_location_and_years_are_stored(): void
    {
        [$response, $track] = $this->save([
            'recording_location' => 'Studio Alpha, Zürich',
            'recording_years' => '2024 - 2026',
        ]);

        $response->assertRedirect();
        $this->assertSame('Studio Alpha, Zürich', $track->recording_location);
        $this->assertSame('2024 - 2026', $track->recording_years);
    }

    public static function yearProvider(): array
    {
        return [
            'single year' => ['2026', true],
            'span' => ['2024 - 2026', true],
            'span without spaces' => ['2024-2026', true],
            'span with en dash' => ['2024 – 2026', true],
            'several years' => ['2019, 2024 - 2026', true],
            'prose' => ['letzten Sommer', false],
            'half a year' => ['20', false],
            'span in words' => ['2024 bis 2026', false],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('yearProvider')]
    public function test_the_year_field_accepts_years_and_spans_only(string $value, bool $valid): void
    {
        [$response, $track] = $this->save(['recording_years' => $value]);

        if ($valid) {
            $response->assertRedirect();
            $this->assertNotNull($track->recording_years, "Rejected a valid entry: {$value}");
        } else {
            $response->assertSessionHasErrors('recording_years');
            $this->assertNull($track->recording_years, "Stored an invalid entry: {$value}");
        }
    }

    public function test_stray_whitespace_is_collapsed(): void
    {
        [, $track] = $this->save(['recording_years' => '  2024   -   2026 ']);

        $this->assertSame('2024 - 2026', $track->recording_years);
    }

    public function test_the_detail_page_shows_both_fields(): void
    {
        [, $track] = $this->save([
            'recording_location' => 'Studio Alpha, Zürich',
            'recording_years' => '2026',
        ]);

        $this->actingAs(User::factory()->create())
            ->get("/admin/tracks/{$track->id}")
            ->assertOk()
            ->assertSee('Aufnahmeort')
            ->assertSee('Aufnahmejahr(e)')
            ->assertSee('Studio Alpha, Zürich');
    }

    public function test_the_create_form_offers_both_fields(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/tracks/create')
            ->assertOk()
            ->assertSee('name="recording_location"', false)
            ->assertSee('name="recording_years"', false);
    }
}
