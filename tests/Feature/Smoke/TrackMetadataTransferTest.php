<?php

namespace Tests\Feature\Smoke;

use App\Models\Contact;
use App\Models\Contract;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Release;
use App\Models\Track;
use App\Models\User;
use App\Services\TrackMetadataTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Metadaten kopieren" can paste the chosen metadata straight into other
 * tracks. The values always come from the source track; the request only
 * picks which ones.
 */
class TrackMetadataTransferTest extends TestCase
{
    use RefreshDatabase;

    private Track $source;
    private Organization $band;
    private Organization $label;
    private Contact $anna;
    private Release $release;
    private Project $project;
    private Contract $contract;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());

        $this->source = Track::create([
            'title' => 'Breeze', 'version' => 'Radio Edit', 'status' => 'released', 'genre' => 'Electro',
            'recording_location' => 'Studio Alpha', 'alternative_titles' => ['Brise'], 'isrc' => 'CHTYL2600001',
        ]);

        $this->band = Organization::create(['type' => 'band', 'names' => ['S.G.A.T.V']]);
        $this->label = Organization::create(['type' => 'label', 'names' => ['Magic And Gain']]);
        $this->source->organizations()->attach([$this->band->id, $this->label->id]);

        $this->anna = Contact::create(['first_name' => 'Anna', 'last_name' => 'Muster']);
        $this->source->contacts()->attach($this->anna->id, ['role' => 'composer']);
        $this->source->contacts()->attach($this->anna->id, ['role' => 'instrumentalist', 'instrument' => 'E-Gitarre']);

        $this->release = Release::create(['title' => 'Sammlung 2026']);
        $this->source->releases()->attach($this->release->id, ['track_number' => 4, 'disc_number' => 1, 'role' => 'main']);

        $this->project = Project::create(['name' => 'Herbsttour', 'status' => 'in_progress']);
        $this->source->projects()->attach($this->project->id);
        $this->contract = Contract::create(['title' => 'Vertrag', 'type' => 'other', 'status' => 'draft']);
        $this->source->contracts()->attach($this->contract->id);
    }

    private function credit(string $role, ?string $instrument = null): string
    {
        return implode('|', [$this->anna->id, $role, $instrument ?? '', '']);
    }

    private function paste(array $targets, array $selection, string $mode = 'add')
    {
        return $this->post("/admin/tracks/{$this->source->id}/apply-metadata", array_merge([
            'target_ids' => collect($targets)->pluck('id')->all(),
            'mode' => $mode,
        ], $selection));
    }

    public function test_chosen_fields_go_into_every_selected_track_and_nothing_else(): void
    {
        $a = Track::create(['title' => 'Eins', 'status' => 'draft', 'genre' => 'Pop', 'isrc' => 'CHTYL2600002']);
        $b = Track::create(['title' => 'Zwei', 'status' => 'draft']);

        $this->paste([$a, $b], ['fields' => ['status', 'genre', 'recording_location']])
            ->assertRedirect("/admin/tracks/{$this->source->id}")
            ->assertSessionHas('success', 'Metadaten in 2 Tracks eingefügt.');

        foreach ([$a->fresh(), $b->fresh()] as $track) {
            $this->assertSame('released', $track->status);
            $this->assertSame('Electro', $track->genre);
            $this->assertSame('Studio Alpha', $track->recording_location);
            $this->assertNull($track->version, 'Version was not selected.');
        }
        $this->assertSame('Eins', $a->fresh()->title);
        $this->assertSame('CHTYL2600002', $a->fresh()->isrc, 'The ISRC is never copied.');
    }

    /** As with the paste button in the form: an empty value does not blank the target. */
    public function test_an_empty_source_value_does_not_blank_the_target(): void
    {
        $target = Track::create(['title' => 'Eins', 'status' => 'draft', 'bpm' => 120]);

        $this->paste([$target], ['fields' => ['bpm', 'genre']]);

        $this->assertSame(120, (int) $target->fresh()->bpm);
        $this->assertSame('Electro', $target->fresh()->genre);
    }

    public function test_adding_keeps_what_the_target_has(): void
    {
        $otherBand = Organization::create(['type' => 'band', 'names' => ['Tar Pond']]);
        $bob = Contact::create(['first_name' => 'Bob', 'last_name' => 'Beispiel']);
        $target = Track::create(['title' => 'Eins', 'status' => 'draft', 'alternative_titles' => ['Uno']]);
        $target->organizations()->attach($otherBand->id);
        $target->contacts()->attach($bob->id, ['role' => 'producer']);
        // Already credited the same way - must not be doubled.
        $target->contacts()->attach($this->anna->id, ['role' => 'composer']);

        $this->paste([$target], [
            'alternative_titles' => '1',
            'bands' => [$this->band->id],
            'credits' => [$this->credit('composer'), $this->credit('instrumentalist', 'E-Gitarre')],
            'projects' => [$this->project->id],
        ]);

        $target = $target->fresh(['organizations', 'contacts', 'projects']);
        $this->assertEqualsCanonicalizing([$otherBand->id, $this->band->id], $target->organizations->pluck('id')->all());
        $this->assertSame(['Uno', 'Brise'], $target->alternative_titles);
        $this->assertCount(3, $target->contacts, 'Bob, Anna as composer (once), Anna on guitar.');
        $this->assertSame('E-Gitarre', $target->contacts->firstWhere('pivot.role', 'instrumentalist')->pivot->instrument);
        $this->assertSame([$this->project->id], $target->projects->pluck('id')->all());
    }

    public function test_replacing_swaps_only_the_chosen_lists(): void
    {
        $otherBand = Organization::create(['type' => 'band', 'names' => ['Tar Pond']]);
        $otherLabel = Organization::create(['type' => 'label', 'names' => ['Anderes Label']]);
        $bob = Contact::create(['first_name' => 'Bob', 'last_name' => 'Beispiel']);
        $target = Track::create(['title' => 'Eins', 'status' => 'draft']);
        $target->organizations()->attach([$otherBand->id, $otherLabel->id]);
        $target->contacts()->attach($bob->id, ['role' => 'producer']);

        $this->paste([$target], [
            'bands' => [$this->band->id],
            'credits' => [$this->credit('composer')],
        ], 'replace');

        $target = $target->fresh(['organizations', 'contacts']);
        $this->assertEqualsCanonicalizing([$this->band->id, $otherLabel->id], $target->organizations->pluck('id')->all(), 'The band is swapped, the label - not chosen - stays.');
        $this->assertSame([$this->anna->id], $target->contacts->pluck('id')->all());
        $this->assertSame('composer', $target->contacts->first()->pivot->role);
    }

    /** The track number on a product belongs to the source track. */
    public function test_a_new_product_link_comes_without_track_number_and_an_existing_one_keeps_its_own(): void
    {
        $fresh = Track::create(['title' => 'Eins', 'status' => 'draft']);
        $linked = Track::create(['title' => 'Zwei', 'status' => 'draft']);
        $linked->releases()->attach($this->release->id, ['track_number' => 7, 'disc_number' => 1, 'role' => 'main']);

        $this->paste([$fresh, $linked], ['releases' => [$this->release->id]], 'replace');

        $this->assertNull($fresh->fresh()->releases->first()->pivot->track_number);
        $this->assertSame(7, (int) $linked->fresh()->releases->first()->pivot->track_number);
    }

    public function test_nothing_the_source_does_not_carry_can_be_attached(): void
    {
        $foreignBand = Organization::create(['type' => 'band', 'names' => ['Fremd']]);
        $target = Track::create(['title' => 'Eins', 'status' => 'draft']);

        $this->paste([$target], [
            'bands' => [$foreignBand->id, $this->band->id],
            'labels' => [$this->band->id], // a band smuggled in as label
            'credits' => [$this->anna->id . '|producer||'],
        ]);

        $target = $target->fresh(['organizations', 'contacts']);
        $this->assertSame([$this->band->id], $target->organizations->pluck('id')->all());
        $this->assertCount(0, $target->contacts);
    }

    public function test_the_source_itself_is_left_out_and_a_selection_is_required(): void
    {
        $target = Track::create(['title' => 'Eins', 'status' => 'draft']);

        $this->paste([$this->source, $target], ['fields' => ['genre']])
            ->assertSessionHas('success', 'Metadaten in 1 Track eingefügt.');

        $this->paste([], ['fields' => ['genre']])->assertSessionHasErrors('target_ids');
        $this->paste([$target], [])->assertSessionHas('error');
        $this->paste([$target], ['fields' => ['isrc']])->assertSessionHasErrors('fields.0');
    }

    public function test_the_copy_data_identifies_each_credit(): void
    {
        $keys = collect($this->getJson("/admin/tracks/{$this->source->id}/copy-metadata")->json('credits'))->pluck('key')->sort()->values()->all();

        $this->assertSame([$this->credit('composer'), $this->credit('instrumentalist', 'E-Gitarre')], $keys);
        $this->assertSame($keys[0], TrackMetadataTransfer::creditKey($this->source->contacts()->get()->firstWhere('pivot.role', 'composer')));
    }

    public function test_the_track_picker_filters_by_band_and_product_and_leaves_out_the_source(): void
    {
        $sameBand = Track::create(['title' => 'Gleiche Band', 'status' => 'draft']);
        $sameBand->organizations()->attach($this->band->id);
        $onRelease = Track::create(['title' => 'Auf dem Produkt', 'status' => 'draft']);
        $onRelease->releases()->attach($this->release->id, ['track_number' => 1, 'disc_number' => 1, 'role' => 'main']);
        Track::create(['title' => 'Anderes', 'status' => 'draft']);

        $ids = fn (string $query) => collect($this->getJson('/admin/tracks-search?' . $query)->json())->pluck('id')->all();

        $this->assertSame([$sameBand->id], $ids("band={$this->band->id}&exclude={$this->source->id}"));
        $this->assertSame([$onRelease->id], $ids("release={$this->release->id}&exclude={$this->source->id}"));
        $this->assertNotContains($this->source->id, $ids("exclude={$this->source->id}"));
    }

    public function test_the_track_page_offers_both_steps(): void
    {
        $this->get("/admin/tracks/{$this->source->id}")
            ->assertOk()
            ->assertSee('Kopieren &amp; Tracks wählen', false)
            ->assertSee(route('admin.tracks.applyMetadata', $this->source), false)
            ->assertSee('In andere Tracks einfügen');
    }
}
