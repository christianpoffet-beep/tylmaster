<?php

namespace Tests\Feature\Smoke;

use App\Models\Contact;
use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\ContractType;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Release;
use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A contract template used to carry only the texts, the parties and the
 * rights. Everything a contract also knows - logo, attached document, linked
 * works, advance payment, territory and the PDF layout switches - now belongs
 * to the template too.
 */
class ContractTemplateDefaultsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        ContractType::firstOrCreate(['slug' => 'label'], ['name' => 'Label', 'sort_order' => 1]);
    }

    public function test_a_template_stores_every_contract_default(): void
    {
        Storage::fake('public');

        $project = Project::create(['name' => 'Album 2027']);
        $track = Track::create(['title' => 'Air of the Night', 'status' => 'released']);
        $release = Release::create(['title' => 'Air of the Night', 'status' => 'released']);

        $this->actingAs(User::factory()->create())->post('/admin/contract-templates', [
            'name' => 'Labeldeal Standard',
            'contract_type_slug' => 'label',
            'language' => 'de',
            'default_has_zession' => '1',
            'default_zession_amount' => '15',
            'default_zession_currency' => 'CHF',
            'default_zession_notes' => 'Kosten Upload Streaming',
            'default_territory_worldwide' => '1',
            'default_preamble_mode' => 'auto',
            'default_show_parties_table' => '1',
            'default_auto_number_sections' => '1',
            'default_project_ids_submitted' => '1',
            'default_project_ids' => [$project->id],
            'default_track_ids_submitted' => '1',
            'default_track_ids' => [$track->id],
            'default_release_ids_submitted' => '1',
            'default_release_ids' => [$release->id],
            'logo_source' => 'upload',
            'logo_file' => UploadedFile::fake()->image('logo.png'),
            'logo_in_header' => '1',
            'logo_as_watermark' => '1',
            'document' => UploadedFile::fake()->create('anhang.pdf', 12, 'application/pdf'),
            'document_notes' => 'Standardanhang',
        ])->assertRedirect();

        $template = ContractTemplate::where('name', 'Labeldeal Standard')->firstOrFail();

        $this->assertTrue($template->default_has_zession);
        $this->assertSame('15.00', (string) $template->default_zession_amount);
        $this->assertSame(['ALL'], $template->default_territory);
        $this->assertTrue($template->default_show_parties_table);
        $this->assertTrue($template->default_auto_number_sections);
        $this->assertSame([$project->id], $template->default_project_ids);
        $this->assertSame([$track->id], $template->default_track_ids);
        $this->assertSame([$release->id], $template->default_release_ids);
        $this->assertNotNull($template->logo_path);
        $this->assertTrue($template->logo_in_header);
        $this->assertSame(1, $template->documents()->count());
        $this->assertSame('Standardanhang', $template->documents()->first()->notes);
    }

    /**
     * The search fields build their inputs in the browser. A submit that never
     * carried them must leave the stored links alone rather than wipe them.
     */
    public function test_links_survive_a_submit_without_the_search_sections(): void
    {
        $track = Track::create(['title' => 'Nachtblau', 'status' => 'released']);
        $template = ContractTemplate::create([
            'name' => 'Vorlage',
            'contract_type_slug' => 'label',
            'language' => 'de',
            'default_track_ids' => [$track->id],
        ]);

        $this->actingAs(User::factory()->create())
            ->put("/admin/contract-templates/{$template->id}", [
                'name' => 'Vorlage',
                'contract_type_slug' => 'label',
                'language' => 'de',
            ])
            ->assertRedirect();

        $this->assertSame([$track->id], $template->fresh()->default_track_ids);
    }

    /**
     * Creating a contract from a template copies the logo and duplicates the
     * attached documents, so the contract owns its own files.
     */
    public function test_a_new_contract_inherits_the_logo_and_the_documents(): void
    {
        Storage::fake('public');

        $template = ContractTemplate::create([
            'name' => 'Labeldeal',
            'contract_type_slug' => 'label',
            'language' => 'de',
            'logo_path' => 'contracts/logos/tyl.png',
            'logo_in_header' => true,
            'logo_as_watermark' => true,
        ]);
        Storage::disk('public')->put('contracts/logos/tyl.png', 'png-bytes');
        Storage::disk('public')->put('contract-templates/anhang.pdf', 'pdf-bytes');
        $template->documents()->create([
            'title' => 'anhang.pdf',
            'category' => 'contract',
            'file_path' => 'contract-templates/anhang.pdf',
            'file_size' => 9,
            'mime_type' => 'application/pdf',
            'notes' => 'Standardanhang',
        ]);

        $org = Organization::create(['type' => 'label', 'names' => ['Test Label']]);
        $contact = Contact::create(['first_name' => 'Anna', 'last_name' => 'Muster']);

        $this->actingAs(User::factory()->create())->post('/admin/contracts', [
            'title' => 'Aus Vorlage',
            'type' => 'label',
            'status' => 'draft',
            'language' => 'de',
            'template_id' => $template->id,
            'logo_source' => 'template',
            'logo_in_header' => '1',
            'logo_as_watermark' => '1',
            'parties' => [
                ['type' => 'organization', 'organization_id' => $org->id, 'share' => 50],
                ['type' => 'contact', 'contact_id' => $contact->id, 'share' => 50],
            ],
        ])->assertRedirect();

        $contract = Contract::where('title', 'Aus Vorlage')->firstOrFail();

        $this->assertSame('contracts/logos/tyl.png', $contract->logo_path);
        $this->assertTrue($contract->logo_in_header);
        $this->assertSame(1, $contract->documents()->count());

        $copy = $contract->documents()->first();
        $this->assertNotSame('contract-templates/anhang.pdf', $copy->file_path, 'The contract must own its own file.');
        $this->assertTrue(Storage::disk('public')->exists($copy->file_path));
        $this->assertTrue(Storage::disk('public')->exists('contract-templates/anhang.pdf'), 'The template keeps its original.');
    }

    /**
     * The contract form fills itself from the JSON the template hands out, so
     * every new default has to be in there.
     */
    public function test_the_data_endpoint_exposes_the_new_defaults(): void
    {
        $track = Track::create(['title' => 'Nachtblau', 'status' => 'released']);
        $template = ContractTemplate::create([
            'name' => 'Vorlage',
            'contract_type_slug' => 'label',
            'language' => 'de',
            'default_has_zession' => true,
            'default_zession_amount' => 15,
            'default_territory' => ['CH', 'DE'],
            'default_preamble_mode' => 'none',
            'default_show_parties_table' => false,
            'default_auto_number_sections' => false,
            'default_track_ids' => [$track->id],
        ]);

        $this->actingAs(User::factory()->create())
            ->get("/admin/contract-templates/{$template->id}/data")
            ->assertOk()
            ->assertJsonPath('default_has_zession', true)
            ->assertJsonPath('default_territory', ['CH', 'DE'])
            ->assertJsonPath('default_preamble_mode', 'none')
            ->assertJsonPath('default_show_parties_table', false)
            ->assertJsonPath('default_auto_number_sections', false)
            ->assertJsonPath('default_tracks.0.title', 'Nachtblau')
            ->assertJsonPath('logo', null)
            ->assertJsonPath('document_count', 0);
    }
}
