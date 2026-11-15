<?php

namespace Tests\Feature\Smoke;

use App\Models\MusicSubmission;
use App\Models\Track;
use App\Models\TrackAudioFile;
use App\Models\User;
use App\Services\AudioStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Audio is not public: the files lie on the private "audio" disk, outside the
 * web root, and only logged-in users get them - streamed for the player or as
 * a download. What used to lie on the public disk moves over.
 */
class AudioPrivacyTest extends TestCase
{
    use RefreshDatabase;

    private Track $track;
    private TrackAudioFile $version;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('audio');

        $this->track = Track::create(['title' => 'Nachtblau', 'status' => 'released']);
        Storage::disk('audio')->put('tracks/nachtblau.wav', str_repeat('A', 1000));
        $this->version = $this->track->audioFiles()->create(['file_path' => 'tracks/nachtblau.wav', 'original_name' => 'nachtblau.wav', 'is_main' => true]);
    }

    public function test_the_audio_disk_lies_outside_the_web_root_and_has_no_url(): void
    {
        $root = str_replace('\\', '/', config('filesystems.disks.audio.root'));

        $this->assertStringStartsWith(str_replace('\\', '/', storage_path('app/private')), $root);
        $this->assertStringStartsNotWith(str_replace('\\', '/', public_path()), $root);
        $this->assertArrayNotHasKey('url', config('filesystems.disks.audio'));
    }

    public function test_the_player_url_is_an_admin_route_not_a_storage_link(): void
    {
        $url = $this->version->url;

        $this->assertSame(route('admin.tracks.audioFiles.stream', [$this->track, $this->version]), $url);
        $this->assertStringNotContainsString('/storage/', $url);
    }

    public function test_without_login_there_is_no_audio(): void
    {
        $this->get($this->version->url)->assertRedirect('/login');
        $this->get(route('admin.tracks.audioFiles.download', [$this->track, $this->version]))->assertRedirect('/login');
        $this->get(route('admin.tracks.download', $this->track))->assertRedirect('/login');
    }

    public function test_logged_in_the_player_streams_with_byte_ranges(): void
    {
        $this->actingAs(User::factory()->create());

        $full = $this->get($this->version->url)->assertOk();
        $this->assertStringContainsString('private', $full->headers->get('Cache-Control'));
        $this->assertStringNotContainsString('public', $full->headers->get('Cache-Control'));
        $this->assertStringNotContainsString('attachment', (string) $full->headers->get('Content-Disposition'));

        // Seeking asks for a part of the file
        $part = $this->get($this->version->url, ['Range' => 'bytes=100-199']);
        $part->assertStatus(206);
        $this->assertSame('bytes 100-199/1000', $part->headers->get('Content-Range'));

        $download = $this->get(route('admin.tracks.audioFiles.download', [$this->track, $this->version]))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename=nachtblau.wav');
        $this->assertStringContainsString('no-store', $download->headers->get('Cache-Control'));
        $this->assertStringNotContainsString('public', $download->headers->get('Cache-Control'));
    }

    public function test_a_version_of_another_track_is_not_handed_out(): void
    {
        $this->actingAs(User::factory()->create());
        $other = Track::create(['title' => 'Anders', 'status' => 'draft']);

        $this->get(route('admin.tracks.audioFiles.stream', [$other, $this->version]))->assertNotFound();
    }

    public function test_uploads_go_to_the_private_disk(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post('/admin/tracks', [
            'title' => 'Neu', 'status' => 'draft',
            'audio_files' => ['n0' => ['file' => UploadedFile::fake()->create('neu.wav', 64, 'audio/wav')]],
            'main_audio_file' => 'new:n0',
        ])->assertRedirect();

        $path = Track::where('title', 'Neu')->first()->mainAudioFile->file_path;
        Storage::disk('audio')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_deleting_a_track_deletes_its_files(): void
    {
        $this->actingAs(User::factory()->create());

        $this->delete("/admin/tracks/{$this->track->id}")->assertRedirect();

        Storage::disk('audio')->assertMissing('tracks/nachtblau.wav');
    }

    public function test_everything_on_the_public_disk_moves_over_and_nothing_is_lost(): void
    {
        $public = Storage::disk('public');
        $public->put('tracks/alt.wav', 'ALT');                 // a version still on the public disk
        $public->put('tracks/verwaist.mp3', 'ORPHAN');         // a file of a deleted track
        $public->put('submissions/demo.mp3', 'DEMO');          // a submission's audio
        $public->put('covers/bild.jpg', 'JPG');                // no audio - stays public
        $this->track->audioFiles()->create(['file_path' => 'tracks/alt.wav', 'original_name' => 'alt.wav', 'is_main' => false]);
        DB::table('music_submissions')->insert(['artist_name' => 'Band', 'file_path' => 'submissions/demo.mp3', 'status' => 'new', 'created_at' => now(), 'updated_at' => now()]);

        $this->assertSame(3, AudioStorage::moveOutOfPublic());
        $this->assertSame(0, AudioStorage::moveOutOfPublic(), 'Nothing left to move.');

        foreach (['tracks/alt.wav' => 'ALT', 'tracks/verwaist.mp3' => 'ORPHAN', 'submissions/demo.mp3' => 'DEMO'] as $path => $content) {
            $public->assertMissing($path);
            $this->assertSame($content, Storage::disk('audio')->get($path));
        }
        $public->assertExists('covers/bild.jpg');
        Storage::disk('audio')->assertExists('tracks/nachtblau.wav');
    }

    public function test_a_file_that_still_turns_up_on_the_public_disk_moves_when_asked_for(): void
    {
        Storage::disk('public')->put('submissions/spaet.mp3', 'LATE');
        $submission = MusicSubmission::create(['artist_name' => 'Band', 'file_path' => 'submissions/spaet.mp3', 'status' => 'new']);

        $this->actingAs(User::factory()->create());
        $this->get(route('admin.submissions.file', $submission))->assertOk();

        Storage::disk('public')->assertMissing('submissions/spaet.mp3');
        $this->assertSame('LATE', Storage::disk('audio')->get('submissions/spaet.mp3'));
        $this->assertNull(AudioStorage::path('../.env'), 'No way out of the disk.');
    }
}
