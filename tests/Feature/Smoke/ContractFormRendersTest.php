<?php

namespace Tests\Feature\Smoke;

use App\Models\Contact;
use App\Models\Contract;
use App\Models\ContractParty;
use App\Models\ContractTemplate;
use App\Models\ContractType;
use App\Models\Organization;
use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The contract and template forms carry a fair amount of Blade and Alpine.
 * These render them for real so a broken include or a stray variable surfaces
 * here instead of in the browser.
 */
class ContractFormRendersTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_contract_forms_render(): void
    {
        $user = User::factory()->create();

        $contract = Contract::create([
            'title' => 'Formular-Test',
            'type' => 'label',
            'status' => 'draft',
            'language' => 'de',
            'sections' => [['title' => 'Vertragsdauer', 'body' => 'Unbefristet.', 'numbered' => true, 'page_break' => false]],
        ]);

        $org = Organization::create(['type' => 'label', 'names' => ['Test Label']]);
        $contact = Contact::create(['first_name' => 'Anna', 'last_name' => 'Muster']);
        ContractParty::create(['contract_id' => $contract->id, 'organization_id' => $org->id, 'share' => 50, 'role_label' => 'Label', 'sort_order' => 0]);
        ContractParty::create(['contract_id' => $contract->id, 'contact_id' => $contact->id, 'share' => 50, 'role_label' => 'Künstler', 'sort_order' => 1]);

        foreach (['/admin/contracts/create', "/admin/contracts/{$contract->id}/edit"] as $url) {
            $response = $this->actingAs($user)->get($url);
            $response->assertOk();
            $this->assertBalancedAlpineTemplates($response->getContent(), $url);
        }

        $this->actingAs($user)->get("/admin/contracts/{$contract->id}")->assertOk();
    }

    /**
     * An unclosed <template x-for> swallows the whole rest of the form: Alpine
     * never renders it and the page simply stops. A 200 does not catch that, so
     * the tags are counted.
     */
    protected function assertBalancedAlpineTemplates(string $html, string $where): void
    {
        $this->assertSame(
            preg_match_all('/<template[\s>]/', $html),
            preg_match_all('/<\/template>/', $html),
            "Unbalanced <template> tags in {$where} — everything after the stray tag stays invisible."
        );
    }

    /**
     * What the section editor and the role label post has to survive the round
     * trip — empty rows dropped, checkboxes cast, roles stored per party.
     */
    public function test_saving_stores_the_sections_and_the_party_roles(): void
    {
        $user = User::factory()->create();
        ContractType::firstOrCreate(['slug' => 'label'], ['name' => 'Label', 'sort_order' => 1]);
        $org = Organization::create(['type' => 'label', 'names' => ['Test Label']]);
        $contact = Contact::create(['first_name' => 'Anna', 'last_name' => 'Muster']);

        $this->actingAs($user)->post('/admin/contracts', [
            'title' => 'Neuer Deal',
            'type' => 'label',
            'status' => 'draft',
            'language' => 'de',
            'subject' => 'Gegenstand',
            'subject_heading' => 'Vertragsgegenstand',
            'relations_heading' => 'Anhang: Aufnahmen',
            'closing_note' => 'In zweifacher Ausfertigung.',
            'preamble_mode' => 'auto',
            'show_parties_table' => '1',
            'auto_number_sections' => '1',
            'sections' => [
                ['title' => 'Vertragsdauer', 'body' => 'Unbefristet.', 'numbered' => '1', 'page_break' => '0'],
                ['title' => '', 'body' => '   '],
                ['title' => 'Gerichtsstand', 'body' => 'Winterthur.', 'numbered' => '0', 'page_break' => '1'],
            ],
            'parties' => [
                ['type' => 'organization', 'organization_id' => $org->id, 'share' => 60, 'role_label' => 'Label'],
                ['type' => 'contact', 'contact_id' => $contact->id, 'share' => 40, 'role_label' => 'Künstler'],
            ],
        ])->assertRedirect();

        $contract = Contract::where('title', 'Neuer Deal')->firstOrFail();

        $this->assertCount(2, $contract->sections, 'The blank section row must be dropped.');
        $this->assertSame('Vertragsdauer', $contract->sections[0]['title']);
        $this->assertTrue($contract->sections[0]['numbered']);
        $this->assertFalse($contract->sections[1]['numbered']);
        $this->assertTrue($contract->sections[1]['page_break']);
        $this->assertTrue($contract->auto_number_sections);
        $this->assertTrue($contract->show_parties_table);
        $this->assertSame('Anhang: Aufnahmen', $contract->relations_heading);
        $this->assertSame('In zweifacher Ausfertigung.', $contract->closing_note);
        $this->assertSame(['Label', 'Künstler'], $contract->parties->pluck('role_label')->all());
    }

    public function test_the_template_forms_render(): void
    {
        $user = User::factory()->create();

        $track = Track::create(['title' => 'Nachtblau', 'status' => 'released']);
        $template = ContractTemplate::create([
            'name' => 'Labeldeal Standard',
            'contract_type_slug' => 'label',
            'language' => 'de',
            'default_sections' => [['title' => 'Vergütung', 'body' => '80/20.', 'numbered' => true, 'page_break' => false]],
            'default_has_zession' => true,
            'default_zession_amount' => 15,
            'default_territory' => ['CH'],
            'default_track_ids' => [$track->id],
            'logo_path' => 'contracts/logos/tyl.png',
            'logo_in_header' => true,
        ]);
        $template->documents()->create([
            'title' => 'anhang.pdf',
            'category' => 'contract',
            'file_path' => 'contract-templates/anhang.pdf',
            'file_size' => 9,
            'mime_type' => 'application/pdf',
        ]);

        foreach (['/admin/contract-templates/create', "/admin/contract-templates/{$template->id}/edit"] as $url) {
            $response = $this->actingAs($user)->get($url);
            $response->assertOk();
            $this->assertBalancedAlpineTemplates($response->getContent(), $url);

            // Everything a contract knows has to be offered here too.
            $response->assertSee('Zession (Vorschusszahlung)')
                ->assertSee('Geltungsbereich / Territory')
                ->assertSee('Präambel der Vertragsparteien')
                ->assertSee('Standard-Verknüpfungen')
                ->assertSee('Standard-Vertragsdokument')
                ->assertSee('name="default_project_ids[]"', false)
                ->assertSee('name="default_track_ids[]"', false)
                ->assertSee('name="default_release_ids[]"', false)
                ->assertSee('name="logo_source"', false)
                ->assertSee('enctype="multipart/form-data"', false);
        }

        // The upload needs a place to list what is already attached.
        $this->actingAs($user)
            ->get("/admin/contract-templates/{$template->id}/edit")
            ->assertSee('anhang.pdf');

        $this->actingAs($user)
            ->get("/admin/contract-templates/{$template->id}/data")
            ->assertOk()
            ->assertJsonPath('default_sections.0.title', 'Vergütung');
    }
}
