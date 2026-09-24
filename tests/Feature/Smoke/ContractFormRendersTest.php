<?php

namespace Tests\Feature\Smoke;

use App\Models\Contact;
use App\Models\Contract;
use App\Models\ContractParty;
use App\Models\ContractTemplate;
use App\Models\ContractType;
use App\Models\Organization;
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

        $this->actingAs($user)->get('/admin/contracts/create')->assertOk();
        $this->actingAs($user)->get("/admin/contracts/{$contract->id}/edit")->assertOk();
        $this->actingAs($user)->get("/admin/contracts/{$contract->id}")->assertOk();
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

        $template = ContractTemplate::create([
            'name' => 'Labeldeal Standard',
            'contract_type_slug' => 'label',
            'language' => 'de',
            'default_sections' => [['title' => 'Vergütung', 'body' => '80/20.', 'numbered' => true, 'page_break' => false]],
        ]);

        $this->actingAs($user)->get('/admin/contract-templates/create')->assertOk();
        $this->actingAs($user)->get("/admin/contract-templates/{$template->id}/edit")->assertOk();

        $this->actingAs($user)
            ->get("/admin/contract-templates/{$template->id}/data")
            ->assertOk()
            ->assertJsonPath('default_sections.0.title', 'Vergütung');
    }
}
