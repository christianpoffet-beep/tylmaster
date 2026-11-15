<?php

namespace Tests\Feature\Smoke;

use App\Models\Track;
use App\Models\TrackAudioFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A track holds several audio versions, each with its own title, ISRC and
 * remark. Exactly one of them is the main version, which the tracklist and
 * the products play and download.
 */
class TrackAudioFilesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('audio');
        $this->actingAs(User::factory()->create());
    }

    private function upload(string $name): UploadedFile
    {
        return UploadedFile::fake()->create($name, 64, str_ends_with($name, '.mp3') ? 'audio/mpeg' : 'audio/wav');
    }

    private function addVersion(Track $track, array $attributes = []): TrackAudioFile
    {
        $path = 'tracks/' . uniqid('', true) . '.wav';
        Storage::disk('audio')->put($path, 'RIFF');

        return $track->audioFiles()->create(array_merge([
            'file_path' => $path,
            'original_name' => 'file.wav',
            'is_main' => false,
        ], $attributes));
    }

    private function versionsOf(Track $track)
    {
        return $track->audioFiles()->reorder()->orderBy('sort_order')->orderBy('id')->get();
    }

    public function test_a_new_track_takes_several_versions_and_the_chosen_main(): void
    {
        $this->post('/admin/tracks', [
            'title' => 'Nachtblau',
            'status' => 'draft',
            'audio_files' => [
                'n0' => ['file' => $this->upload('master.wav'), 'title' => 'Master', 'isrc' => 'CH-TYL-26-00001', 'notes' => 'Finaler Mix'],
                'n1' => ['file' => $this->upload('radio.mp3'), 'title' => 'Radio Edit', 'isrc' => 'chtyl2600002'],
            ],
            'main_audio_file' => 'new:n1',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $versions = $this->versionsOf(Track::where('title', 'Nachtblau')->firstOrFail());

        $this->assertSame(['Master', 'Radio Edit'], $versions->pluck('title')->all());
        $this->assertSame(['CHTYL2600001', 'CHTYL2600002'], $versions->pluck('isrc')->all(), 'ISRCs are stored without dashes, uppercase.');
        $this->assertSame('Finaler Mix', $versions[0]->notes);
        $this->assertSame('radio.mp3', $versions[1]->original_name);
        $this->assertSame([false, true], $versions->pluck('is_main')->all());
        Storage::disk('audio')->assertExists($versions->pluck('file_path')->all());
    }

    public function test_without_a_choice_the_first_upload_becomes_main(): void
    {
        $this->post('/admin/tracks', [
            'title' => 'Nachtblau',
            'status' => 'draft',
            'audio_files' => [
                'n0' => ['file' => $this->upload('master.wav')],
                'n1' => ['file' => $this->upload('instrumental.wav')],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertSame([true, false], $this->versionsOf(Track::firstOrFail())->pluck('is_main')->all());
    }

    /** A row whose radio is set but which has no file must not leave the track without a main version. */
    public function test_choosing_an_empty_row_as_main_keeps_a_real_main(): void
    {
        $this->post('/admin/tracks', [
            'title' => 'Nachtblau',
            'status' => 'draft',
            'audio_files' => [
                'n0' => ['title' => 'Leer'],
                'n1' => ['file' => $this->upload('master.wav'), 'title' => 'Master'],
            ],
            'main_audio_file' => 'new:n0',
        ])->assertSessionHasNoErrors();

        $versions = $this->versionsOf(Track::firstOrFail());

        $this->assertCount(1, $versions, 'A row without a file creates nothing.');
        $this->assertTrue($versions[0]->is_main);
    }

    public function test_existing_versions_can_be_edited_switched_and_removed(): void
    {
        $track = Track::create(['title' => 'Nachtblau', 'status' => 'draft']);
        $master = $this->addVersion($track, ['title' => 'Master', 'is_main' => true]);
        $radio = $this->addVersion($track, ['title' => 'Radio', 'isrc' => 'CHTYL2600002']);
        $demo = $this->addVersion($track, ['title' => 'Demo']);

        $this->put("/admin/tracks/{$track->id}", [
            'title' => 'Nachtblau',
            'status' => 'draft',
            'existing_audio_files' => [
                $master->id => ['title' => 'Master 2026', 'isrc' => 'CH-TYL-26-00009', 'notes' => 'Neu gemastert', 'delete' => ''],
                $radio->id => ['title' => 'Radio Edit', 'isrc' => '', 'notes' => '', 'delete' => ''],
                $demo->id => ['title' => 'Demo', 'delete' => '1'],
            ],
            'main_audio_file' => "existing:{$radio->id}",
        ])->assertRedirect()->assertSessionHasNoErrors();

        $master->refresh();
        $radio->refresh();

        $this->assertSame('Master 2026', $master->title);
        $this->assertSame('CHTYL2600009', $master->isrc);
        $this->assertSame('Neu gemastert', $master->notes);
        $this->assertNull($radio->isrc, 'An emptied ISRC is cleared.');
        $this->assertFalse($master->is_main);
        $this->assertTrue($radio->is_main);

        $this->assertNull($demo->fresh());
        Storage::disk('audio')->assertMissing($demo->file_path);
    }

    public function test_removing_the_main_version_hands_the_role_on(): void
    {
        $track = Track::create(['title' => 'Nachtblau', 'status' => 'draft']);
        $master = $this->addVersion($track, ['title' => 'Master', 'is_main' => true]);
        $radio = $this->addVersion($track, ['title' => 'Radio Edit']);

        $this->put("/admin/tracks/{$track->id}", [
            'title' => 'Nachtblau',
            'status' => 'draft',
            'existing_audio_files' => [$master->id => ['delete' => '1']],
            // The radio still points at the removed one.
            'main_audio_file' => "existing:{$master->id}",
        ])->assertSessionHasNoErrors();

        $this->assertNull($master->fresh());
        $this->assertTrue($radio->fresh()->is_main);
    }

    /** Same guard as for the relation sections: missing fields are not a request to blank them. */
    public function test_a_save_without_the_audio_section_leaves_the_versions_alone(): void
    {
        $track = Track::create(['title' => 'Nachtblau', 'status' => 'draft']);
        $master = $this->addVersion($track, ['title' => 'Master', 'isrc' => 'CHTYL2600001', 'notes' => 'Final', 'is_main' => true]);

        $this->put("/admin/tracks/{$track->id}", ['title' => 'Nachtblau', 'status' => 'draft'])->assertRedirect();

        $master->refresh();
        $this->assertSame('Master', $master->title);
        $this->assertSame('CHTYL2600001', $master->isrc);
        $this->assertSame('Final', $master->notes);
        $this->assertTrue($master->is_main);
        Storage::disk('audio')->assertExists($master->file_path);
    }

    public function test_an_invalid_version_isrc_is_rejected(): void
    {
        $track = Track::create(['title' => 'Nachtblau', 'status' => 'draft']);
        $master = $this->addVersion($track, ['is_main' => true]);

        $this->from("/admin/tracks/{$track->id}/edit")
            ->put("/admin/tracks/{$track->id}", [
                'title' => 'Nachtblau',
                'status' => 'draft',
                'existing_audio_files' => [$master->id => ['isrc' => 'kein ISRC']],
            ])
            ->assertSessionHasErrors("existing_audio_files.{$master->id}.isrc");

        $this->assertNull($master->fresh()->isrc);
    }

    public function test_every_version_downloads_under_a_readable_name_and_only_from_its_own_track(): void
    {
        $track = Track::create(['title' => 'Nachtblau', 'status' => 'draft']);
        $this->addVersion($track, ['title' => 'Master', 'original_name' => 'mix_v7_final.wav', 'is_main' => true]);
        $radio = $this->addVersion($track, ['title' => 'Radio Edit', 'original_name' => 'radio.wav']);
        $other = Track::create(['title' => 'Anderer Track', 'status' => 'draft']);

        $this->get(route('admin.tracks.audioFiles.download', [$track, $radio]))
            ->assertOk()
            ->assertDownload('nachtblau-radio-edit.wav');

        $this->get(route('admin.tracks.audioFiles.download', [$other, $radio]))->assertNotFound();

        // The download icon in the tracklist hands out the main version.
        $this->get(route('admin.tracks.download', $track))->assertDownload('nachtblau-master.wav');
    }

    public function test_the_track_page_lists_every_version_with_its_download(): void
    {
        $track = Track::create(['title' => 'Nachtblau', 'status' => 'draft']);
        $master = $this->addVersion($track, ['title' => 'Master', 'isrc' => 'CHTYL2600001', 'is_main' => true]);
        $radio = $this->addVersion($track, ['title' => 'Radio Edit', 'isrc' => 'CHTYL2600002', 'notes' => 'Für Airplay gekürzt']);

        $this->get("/admin/tracks/{$track->id}")
            ->assertOk()
            ->assertSee('Main Version')
            ->assertSee('CH-TYL-26-00001')
            ->assertSee('CH-TYL-26-00002')
            ->assertSee('Für Airplay gekürzt')
            ->assertSee(route('admin.tracks.audioFiles.download', [$track, $master]), false)
            ->assertSee(route('admin.tracks.audioFiles.download', [$track, $radio]), false);
    }

    public function test_the_tracklist_and_the_product_play_the_main_version(): void
    {
        $track = Track::create(['title' => 'Nachtblau', 'status' => 'draft']);
        $demo = $this->addVersion($track, ['title' => 'Demo']);
        $main = $this->addVersion($track, ['title' => 'Master', 'is_main' => true]);
        $release = \App\Models\Release::create(['title' => 'Sammlung 2026']);
        $track->releases()->attach($release->id, ['track_number' => 1, 'disc_number' => 1, 'role' => 'main']);

        // The player URLs sit in JSON with escaped slashes
        $streamOf = fn ($version) => "audio-files/{$version->id}/stream";
        foreach (['/admin/tracks', "/admin/releases/{$release->id}"] as $page) {
            $html = str_replace(['\\\\\\/', '\\/'], '/', $this->get($page)->assertOk()->getContent());

            $this->assertStringContainsString($streamOf($main), $html, "{$page} does not play the main version.");
            $this->assertStringNotContainsString($streamOf($demo), $html, "{$page} plays a version that is not the main one.");
        }
    }

    public function test_the_forms_render_with_and_without_versions(): void
    {
        $this->get('/admin/tracks/create')->assertOk()->assertSee('trackAudioFiles(', false);

        $track = Track::create(['title' => 'Nachtblau', 'status' => 'draft']);
        $master = $this->addVersion($track, ['title' => 'Master', 'isrc' => 'CHTYL2600001', 'is_main' => true]);

        $html = $this->get("/admin/tracks/{$track->id}/edit")->assertOk()->getContent();

        $this->assertStringContainsString('name="existing_audio_files[' . $master->id . '][title]" value="Master"', $html);
        $this->assertStringContainsString('value="CH-TYL-26-00001"', $html);
        // The delete flag must come from Alpine, never from the template.
        $this->assertMatchesRegularExpression('/name="existing_audio_files\[' . $master->id . '\]\[delete\]" value=""\s+x-bind:value/', $html);
    }

    public function test_the_isrc_of_any_version_finds_its_track(): void
    {
        $track = Track::create(['title' => 'Nachtblau', 'status' => 'draft']);
        $this->addVersion($track, ['title' => 'Instrumental', 'isrc' => 'CHTYL2600042', 'is_main' => true]);
        Track::create(['title' => 'Morgenrot', 'status' => 'draft']);

        $this->get('/admin/tracks?search=CH-TYL-26-00042')
            ->assertOk()
            ->assertSee('Nachtblau')
            ->assertDontSee('Morgenrot');
    }

    /** What a track looked like before: one file in its own columns. */
    public function test_the_former_single_file_moves_over_as_main_version(): void
    {
        $track = Track::create(['title' => 'Alt', 'status' => 'draft', 'duration_seconds' => 215]);
        Storage::disk('public')->put('tracks/legacy.mp3', 'ID3');
        DB::table('tracks')->where('id', $track->id)->update([
            'audio_file_path' => 'tracks/legacy.mp3', 'audio_format' => 'mp3', 'bitrate' => 320,
        ]);

        $migration = require database_path('migrations/2026_11_15_120001_move_track_audio_into_audio_files.php');
        $migration->up();
        $migration->up(); // re-run safe

        $versions = $this->versionsOf($track);

        $this->assertCount(1, $versions);
        $this->assertTrue($versions[0]->is_main);
        $this->assertSame('tracks/legacy.mp3', $versions[0]->file_path);
        $this->assertSame('mp3', $versions[0]->audio_format);
        $this->assertSame(320, $versions[0]->bitrate);
        $this->assertSame(215, $versions[0]->duration_seconds);
        $this->assertSame(3, $versions[0]->file_size);
    }
}
