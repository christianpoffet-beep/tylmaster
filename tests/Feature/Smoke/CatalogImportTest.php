<?php

namespace Tests\Feature\Smoke;

use App\Models\Organization;
use App\Models\Release;
use App\Models\Track;
use App\Models\TrackAudioFile;
use App\Models\User;
use App\Services\CatalogImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The one-time catalogue import writes a few hundred records into the live
 * database in one go, so what it recognises as already there matters as much
 * as what it creates: nothing may turn up twice, and a file with errors must
 * leave everything as it was.
 */
class CatalogImportTest extends TestCase
{
    use RefreshDatabase;

    private function file(array $override = []): array
    {
        return array_replace([
            'format' => 'tyl-catalog-import',
            'version' => 1,
            'source' => 'test.xlsx',
            'created' => '2026-11-16',
            'products' => [
                [
                    'title' => 'Hybrid Beast',
                    'catalog_number' => 'FRS001',
                    'ean_upc' => '9008798291786',
                    'release_date' => '2019-04-01',
                    'product_type' => ['EP (Extended Play)', 'Vinyl 7"', 'Digital EP'],
                    'label' => 'Flexi Recording Session',
                    'bands' => ['Nekrofog', 'Betty'],
                    'tracks' => [
                        ['number' => 1, 'title' => 'Penetrator of time', 'version' => null, 'isrc' => 'CH4811280080', 'band' => 'Nekrofog', 'genre' => 'Rock', 'duration_seconds' => 177, 'recording_years' => null, 'status' => 'released'],
                        ['number' => 2, 'title' => 'My cup of tea', 'version' => null, 'isrc' => 'CH4811280082', 'band' => 'Betty', 'genre' => 'Rock', 'duration_seconds' => 175, 'recording_years' => null, 'status' => 'released'],
                    ],
                ],
                [
                    'title' => 'Jarls',
                    'catalog_number' => 'TYL047',
                    'ean_upc' => '9008798571024',
                    'release_date' => '2023-01-12',
                    'product_type' => ['Album (LP)', 'Vinyl 12"', 'CD', 'Digital Album'],
                    'label' => 'The yelling light',
                    'bands' => ['Jarls'],
                    'tracks' => [
                        ['number' => 1, 'title' => 'Waterlove', 'version' => null, 'isrc' => 'CH4811280285', 'band' => 'Jarls', 'genre' => 'Jazz', 'duration_seconds' => 516, 'recording_years' => '2022', 'status' => 'released'],
                        ['number' => 2, 'title' => 'Carnival', 'version' => 'Live', 'isrc' => 'CH4811280221', 'band' => 'Jarls', 'genre' => 'Jazz', 'duration_seconds' => null, 'recording_years' => null, 'status' => 'draft'],
                    ],
                ],
            ],
        ], $override);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_guests_and_non_admins_cannot_reach_it(): void
    {
        $this->get('/admin/catalog-import')->assertRedirect('/login');
        $this->post('/admin/catalog-import/run')->assertRedirect('/login');

        $this->actingAs(User::factory()->create(['role' => 'user']));
        $this->get('/admin/catalog-import')->assertForbidden();
        $this->post('/admin/catalog-import/run')->assertForbidden();
    }

    public function test_it_creates_products_tracks_bands_and_labels_with_their_links(): void
    {
        $this->actingAs($this->admin());

        $result = CatalogImport::fromJson(json_encode($this->file()))->run();

        $this->assertSame(['organizations' => 5, 'products' => 2, 'tracks' => 4, 'links' => 4], $result);

        $ep = Release::where('catalog_number', 'FRS001')->firstOrFail();
        $this->assertSame('Hybrid Beast', $ep->title);
        $this->assertSame(['EP (Extended Play)', 'Vinyl 7"', 'Digital EP'], $ep->product_type);
        $this->assertSame('2019-04-01', $ep->release_date->format('Y-m-d'));
        $this->assertSame('9008798291786', $ep->ean_upc);
        $this->assertEqualsCanonicalizing(['Nekrofog', 'Betty', 'Flexi Recording Session'], $ep->organizations->map->primary_name->all());
        $this->assertSame(['Penetrator of time' => 1, 'My cup of tea' => 2], $ep->tracks->mapWithKeys(fn ($t) => [$t->title => $t->pivot->track_number])->all());

        $label = Organization::where('type', 'label')->get()->firstWhere('primary_name', 'Flexi Recording Session');
        $this->assertSame(['FRS'], $label->catalogs);

        $track = Track::where('isrc', 'CH4811280082')->firstOrFail();
        $this->assertSame(['Betty', 'Flexi Recording Session'], $track->organizations->sortBy('type')->map->primary_name->values()->all());
        $this->assertSame(175, $track->duration_seconds);
        $this->assertSame('released', $track->status);

        $live = Track::where('isrc', 'CH4811280221')->firstOrFail();
        $this->assertSame('Carnival (Live)', $live->display_title);
        $this->assertSame('draft', $live->status);
        $this->assertSame('2022', Track::where('isrc', 'CH4811280285')->value('recording_years'));
    }

    public function test_what_is_there_already_is_linked_not_created_again(): void
    {
        $this->actingAs($this->admin());

        $band = Organization::create(['type' => 'band', 'names' => ['JARLS']]);
        $otherLabel = Organization::create(['type' => 'label', 'names' => ['The Yelling Light']]);
        $label = Organization::create(['type' => 'label', 'names' => ['The yelling light'], 'catalogs' => ['TYL']]);
        $release = Release::create(['title' => 'Jarls (alt)', 'ean_upc' => '9008798571024']);
        $track = Track::create(['title' => 'Waterlove', 'isrc' => 'CH-481-12-80285', 'status' => 'released']);

        $plan = CatalogImport::fromJson(json_encode($this->file()))->plan();

        $this->assertSame([], $plan['errors']);
        $this->assertSame(1, $plan['counts']['products_existing']);
        $this->assertSame(1, $plan['counts']['tracks_existing']);
        $this->assertSame(1, $plan['counts']['bands_existing']);
        $this->assertTrue(collect($plan['warnings'])->contains(fn ($w) => str_contains($w, "verknüpft wird #{$label->id}")));

        CatalogImport::fromJson(json_encode($this->file()))->run();

        $this->assertSame(2, Release::count());
        $this->assertSame('Jarls (alt)', $release->fresh()->title, 'an existing product keeps its data');
        $this->assertSame(4, Track::count());
        $this->assertSame(1, $release->tracks()->whereKey($track->id)->count());
        $this->assertSame(1, $release->tracks()->where('tracks.id', $track->id)->first()->pivot->track_number);
        $this->assertTrue($track->organizations()->whereKey($band->id)->exists());
        $this->assertTrue($release->organizations()->whereKey($label->id)->exists());
        $this->assertFalse($release->organizations()->whereKey($otherLabel->id)->exists());
        $this->assertSame(1, Organization::where('type', 'band')->get()->filter(fn ($o) => strcasecmp($o->primary_name, 'Jarls') === 0)->count());
    }

    public function test_a_second_run_creates_nothing(): void
    {
        $this->actingAs($this->admin());
        CatalogImport::fromJson(json_encode($this->file()))->run();
        $counts = [Release::count(), Track::count(), Organization::count(), \DB::table('product_track')->count()];

        $result = CatalogImport::fromJson(json_encode($this->file()))->run();

        $this->assertSame(['organizations' => 0, 'products' => 0, 'tracks' => 0, 'links' => 0], $result);
        $this->assertSame($counts, [Release::count(), Track::count(), Organization::count(), \DB::table('product_track')->count()]);
    }

    public function test_a_file_with_errors_writes_nothing(): void
    {
        $this->actingAs($this->admin());
        $file = $this->file();
        $file['products'][1]['product_type'][] = 'Schallfolie';
        $file['products'][1]['tracks'][1]['isrc'] = 'CH4811280080'; // twice in the file

        $plan = CatalogImport::fromJson(json_encode($file))->plan();
        $this->assertCount(2, $plan['errors']);

        try {
            CatalogImport::fromJson(json_encode($file))->run();
            $this->fail('run() should refuse a file with errors');
        } catch (\RuntimeException) {
        }

        $this->assertSame(0, Release::count());
        $this->assertSame(0, Track::count());
        $this->assertSame(0, Organization::count());
    }

    public function test_an_isrc_already_on_an_audio_version_blocks_the_import(): void
    {
        $this->actingAs($this->admin());
        $other = Track::create(['title' => 'Anderer Track', 'status' => 'draft']);
        TrackAudioFile::create(['track_id' => $other->id, 'file_path' => 'x.wav', 'isrc' => 'CH4811280285']);

        $plan = CatalogImport::fromJson(json_encode($this->file()))->plan();

        $this->assertCount(1, $plan['errors']);
        $this->assertStringContainsString("Audioversion von Track #{$other->id}", $plan['errors'][0]);
    }

    public function test_upload_preview_and_run_through_the_page(): void
    {
        Storage::fake('local');
        $this->actingAs($this->admin());
        $json = json_encode($this->file());

        $this->post('/admin/catalog-import', ['file' => UploadedFile::fake()->createWithContent('import.json', $json)])
            ->assertRedirect('/admin/catalog-import');

        $this->get('/admin/catalog-import')
            ->assertOk()
            ->assertSee('Hybrid Beast')
            ->assertSee('CH-481-12-80080')
            ->assertSee('Import ausführen');

        // Only the file that was looked at
        $this->post('/admin/catalog-import/run', ['token' => 'falsch'])->assertSessionHas('error');
        $this->assertSame(0, Release::count());

        $this->post('/admin/catalog-import/run', ['token' => sha1($json)])
            ->assertRedirect('/admin/catalog-import')
            ->assertSessionHas('importResult');

        $this->assertSame(2, Release::count());
        Storage::disk('local')->assertMissing('catalog-import/pending.json');
    }

    public function test_something_else_than_an_import_file_is_refused(): void
    {
        Storage::fake('local');
        $this->actingAs($this->admin());

        $this->post('/admin/catalog-import', ['file' => UploadedFile::fake()->createWithContent('x.json', '{"foo": 1}')])
            ->assertSessionHas('error');

        Storage::disk('local')->assertMissing('catalog-import/pending.json');
    }
}
