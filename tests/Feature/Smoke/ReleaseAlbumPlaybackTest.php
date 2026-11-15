<?php

namespace Tests\Feature\Smoke;

use App\Models\Organization;
use App\Models\Release;
use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A product's tracklist plays as an album in the global player: the page
 * hands the player every track with audio, in tracklist order, and each play
 * button starts the album at its own place.
 */
class ReleaseAlbumPlaybackTest extends TestCase
{
    use RefreshDatabase;

    private Release $release;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
        $this->release = Release::create(['title' => 'Now To Ruin']);
    }

    private function track(string $title, ?int $number, bool $withAudio = true): Track
    {
        $track = Track::create(['title' => $title, 'status' => 'released']);
        $this->release->tracks()->attach($track->id, ['track_number' => $number, 'disc_number' => 1, 'role' => 'main']);
        if ($withAudio) {
            $track->audioFiles()->create(['file_path' => "tracks/{$title}.wav", 'original_name' => "{$title}.wav", 'is_main' => true]);
        }

        return $track;
    }

    public function test_the_queue_holds_the_tracks_with_audio_in_tracklist_order(): void
    {
        $band = Organization::create(['type' => 'band', 'names' => ['S.G.A.T.V']]);
        $second = $this->track('Breaking Free', 2);
        $this->track('Ohne Audio', 3, withAudio: false);
        $first = $this->track('Now To Ruin', 1);
        $first->organizations()->attach($band->id);

        $queue = $this->release->fresh()->playerQueue();

        $this->assertSame('release-' . $this->release->id, $queue['id']);
        $this->assertSame('Now To Ruin', $queue['title']);
        $this->assertSame([$first->id, $second->id], array_column($queue['items'], 'trackId'), 'In order, without the track that has no audio.');
        $this->assertSame([1, 2], array_column($queue['items'], 'number'));
        $this->assertSame('S.G.A.T.V', $queue['items'][0]['artist']);
        $this->assertSame(route('admin.tracks.audioFiles.stream', [$first, $first->mainAudioFile]), $queue['items'][0]['url'], 'Played through the protected route.');
    }

    public function test_the_product_page_offers_the_album_and_starts_each_track_at_its_place(): void
    {
        $this->track('Now To Ruin', 1);
        $this->track('Ohne Audio', 2, withAudio: false);
        $this->track('Breaking Free', 3);

        $this->get("/admin/releases/{$this->release->id}")
            ->assertOk()
            ->assertSee('Album abspielen')
            ->assertSee('2 von 3 Tracks haben eine Audiodatei')
            ->assertSee('$store.player.playQueue(album, 0)', false)
            ->assertSee('$store.player.playQueue(album, 1)', false)
            ->assertDontSee('$store.player.playQueue(album, 2)', false);
    }

    public function test_without_audio_there_is_nothing_to_play(): void
    {
        $this->track('Ohne Audio', 1, withAudio: false);

        $this->get("/admin/releases/{$this->release->id}")
            ->assertOk()
            ->assertDontSee('Album abspielen');
    }
}
