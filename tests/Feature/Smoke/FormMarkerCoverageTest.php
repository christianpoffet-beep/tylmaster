<?php

namespace Tests\Feature\Smoke;

use App\Models\AddressCircle;
use App\Models\Artwork;
use App\Models\Contact;
use App\Models\Contract;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Release;
use App\Models\Task;
use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The controllers now skip a relation sync unless its form section reported in.
 * That protects against silent data loss - but only works if every form really
 * renders the marker. A missing one would make that relation uneditable, which
 * is just as silent. This walks the edit forms and checks each expected marker
 * is present.
 */
class FormMarkerCoverageTest extends TestCase
{
    use RefreshDatabase;

    public static function formProvider(): array
    {
        return [
            'Track'         => ['track', ['alternative_titles', 'band_ids', 'label_ids', 'publisher_ids', 'credits', 'release_ids', 'project_ids', 'contract_ids']],
            'Projekt'       => ['project', ['contacts', 'organization_ids', 'artwork_ids', 'track_ids', 'contract_ids']],
            'Organisation'  => ['organization', ['contact_ids', 'project_ids', 'track_ids', 'release_ids', 'contract_ids']],
            'Kontakt'       => ['contact', ['project_ids', 'organization_ids']],
            'Aufgabe'       => ['task', ['contact_ids', 'contract_ids', 'track_ids', 'linked_project_ids', 'submission_ids']],
            'Vertrag'       => ['contract', ['project_ids', 'track_ids', 'release_ids']],
            'Produkt'       => ['release', ['band_ids', 'label_ids', 'publisher_ids', 'credits', 'artwork_ids', 'project_ids', 'contract_ids']],
            'Adresskreis'   => ['addressCircle', ['organization_ids', 'project_ids']],
            'Artwork'       => ['artwork', ['project_ids']],
        ];
    }

    protected function urlFor(string $kind): string
    {
        return match ($kind) {
            'track' => '/admin/tracks/' . Track::create(['title' => 'T', 'status' => 'draft'])->id . '/edit',
            'project' => '/admin/projects/' . Project::create(['name' => 'P', 'status' => 'in_progress'])->id . '/edit',
            'organization' => '/admin/organizations/' . Organization::create(['type' => 'band', 'names' => ['O']])->id . '/edit',
            'contact' => '/admin/contacts/' . Contact::create(['first_name' => 'A', 'last_name' => 'B'])->id . '/edit',
            'task' => '/admin/tasks/' . Task::create(['title' => 'A'])->id . '/edit',
            'contract' => '/admin/contracts/' . Contract::create(['title' => 'V', 'type' => 'other', 'status' => 'draft'])->id . '/edit',
            'release' => '/admin/releases/' . Release::create(['title' => 'R'])->id . '/edit',
            'addressCircle' => '/admin/address-circles/' . AddressCircle::create(['name' => 'K', 'info' => ''])->id . '/edit',
            'artwork' => '/admin/artworks/' . Artwork::create(['title' => 'A'])->id . '/edit',
        };
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('formProvider')]
    public function test_edit_form_renders_a_marker_for_every_guarded_relation(string $kind, array $fields): void
    {
        $html = $this->actingAs(User::factory()->create())
            ->get($this->urlFor($kind))
            ->assertOk()
            ->getContent();

        foreach ($fields as $field) {
            $this->assertStringContainsString(
                'name="' . $field . '_submitted"',
                $html,
                "Missing marker for {$field} - that relation can no longer be edited on this form."
            );
        }
    }

    /**
     * The same protection has to hold beyond tracks: saving a form whose JS
     * sections never rendered must not clear the existing links.
     */
    public function test_saving_a_project_without_the_js_sections_keeps_its_links(): void
    {
        $project = Project::create(['name' => 'Alpha', 'status' => 'in_progress']);
        $project->contacts()->attach(Contact::create(['first_name' => 'A', 'last_name' => 'B'])->id);
        $project->organizations()->attach(Organization::create(['type' => 'band', 'names' => ['Tar Pond']])->id);
        $project->tracks()->attach(Track::create(['title' => 'T', 'status' => 'draft'])->id);

        $this->actingAs(User::factory()->create())
            ->put("/admin/projects/{$project->id}", ['name' => 'Alpha', 'status' => 'in_progress', 'type' => 'release']);

        $fresh = $project->fresh(['contacts', 'organizations', 'tracks']);

        $this->assertCount(1, $fresh->contacts, 'Contacts lost');
        $this->assertCount(1, $fresh->organizations, 'Organizations lost');
        $this->assertCount(1, $fresh->tracks, 'Tracks lost');
    }

    public function test_saving_a_contract_without_the_js_sections_keeps_its_links(): void
    {
        $contract = Contract::create(['title' => 'V', 'type' => 'other', 'status' => 'draft']);
        $contract->tracks()->attach(Track::create(['title' => 'T', 'status' => 'draft'])->id);
        $contract->releases()->attach(Release::create(['title' => 'R'])->id);

        $this->actingAs(User::factory()->create())
            ->put("/admin/contracts/{$contract->id}", ['title' => 'V', 'type' => 'other', 'status' => 'draft', 'language' => 'de']);

        $fresh = $contract->fresh(['tracks', 'releases']);

        $this->assertCount(1, $fresh->tracks, 'Tracks lost');
        $this->assertCount(1, $fresh->releases, 'Products lost');
    }
}
