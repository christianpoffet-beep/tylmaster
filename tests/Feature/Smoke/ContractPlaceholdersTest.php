<?php

namespace Tests\Feature\Smoke;

use App\Models\Contact;
use App\Models\Contract;
use App\Models\ContractParty;
use App\Models\ContractTemplate;
use App\Models\ContractType;
use App\Models\Organization;
use App\Models\User;
use App\Services\ContractPlaceholders;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A contract template is written once with {label_name}, {artist_members} …
 * and every contract prints its own names, shares and dates. What the
 * contract cannot know (notice period, place of jurisdiction) comes from its
 * «Vertragswerte», which a template hands on to every new contract.
 */
class ContractPlaceholdersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        ContractType::firstOrCreate(['slug' => 'label'], ['name' => 'Label', 'sort_order' => 1]);
    }

    /** The label plus a band of two, as in a real label deal. */
    protected function bandContract(array $attributes = []): Contract
    {
        $contract = Contract::create($attributes + [
            'contract_number' => 'VT-2026-0007',
            'title' => 'Labeldeal Red Tape Redemption',
            'type' => 'label',
            'status' => 'draft',
            'language' => 'de',
            'start_date' => '2026-09-01',
            'rights' => [
                ['label' => 'Streaming & Downloads', 'mode' => 'split', 'splits' => [20, 40, 40]],
                ['label' => 'Synchronisation', 'mode' => 'split', 'splits' => [50, 25, 25]],
            ],
            'placeholder_values' => [
                ['key' => 'notice_period', 'value' => 'drei Wochen'],
                ['key' => 'jurisdiction', 'value' => 'Winterthur'],
            ],
        ]);

        $label = Organization::create(['type' => 'label', 'names' => ['The yelling light'], 'websites' => ['https://theyellinglight.ch/']]);
        $band = Organization::create(['type' => 'band', 'names' => ['Red Tape Redemption']]);

        ContractParty::create(['contract_id' => $contract->id, 'organization_id' => $label->id, 'share' => 20, 'role_label' => 'Label', 'sort_order' => 0]);
        // Only the first member carries the role, the second joins through the band.
        foreach ([['Tobias', 'Kalt', 'Künstler'], ['Lukas', 'Oberholzer', null]] as $i => [$first, $last, $role]) {
            $member = Contact::create(['first_name' => $first, 'last_name' => $last]);
            ContractParty::create([
                'contract_id' => $contract->id,
                'organization_id' => $band->id,
                'contact_id' => $member->id,
                'share' => 40,
                'role_label' => $role,
                'sort_order' => $i + 1,
            ]);
        }

        return $contract->fresh()->load(['parties.organization', 'parties.contact']);
    }

    /** The label plus one artist without a band. */
    protected function soloContract(?string $gender = 'male', array $attributes = []): Contract
    {
        $contract = Contract::create($attributes + [
            'title' => 'Labeldeal Max Muster',
            'type' => 'label',
            'status' => 'draft',
            'language' => 'de',
        ]);

        $label = Organization::create(['type' => 'label', 'names' => ['The yelling light']]);
        $artist = Contact::create(['first_name' => 'Max', 'last_name' => 'Muster', 'gender' => $gender]);

        ContractParty::create(['contract_id' => $contract->id, 'organization_id' => $label->id, 'share' => 30, 'role_label' => 'Label', 'sort_order' => 0]);
        ContractParty::create(['contract_id' => $contract->id, 'contact_id' => $artist->id, 'share' => 70, 'role_label' => 'Künstler', 'sort_order' => 1]);

        return $contract->fresh()->load(['parties.organization', 'parties.contact']);
    }

    public function test_a_band_contract_fills_names_shares_and_the_plural(): void
    {
        $contract = $this->bandContract();

        $this->assertSame(
            'Red Tape Redemption überträgt The yelling light die Masterrechte.',
            $contract->fillPlaceholders('{artist_name} überträgt {label_name} die Masterrechte.')
        );
        $this->assertSame(
            'Tobias Kalt und Lukas Oberholzer teilen The yelling light je ihre aktuellen Kontaktdaten mit und melden Änderungen.',
            $contract->fillPlaceholders('{artist_members} {teilt_teilen} {label_name} {je_ihre} aktuellen Kontaktdaten mit und {meldet_melden} Änderungen.')
        );
        $this->assertSame(
            'Die Vergütungsansprüche der Mitglieder von Red Tape Redemption bleiben unberührt.',
            $contract->fillPlaceholders('Die Vergütungsansprüche {artist_interpreten} bleiben unberührt.')
        );
        // Both members' columns add up to the band's share.
        $this->assertSame('80 / 20, Sync 50 / 50', $contract->fillPlaceholders('{artist_share} / {label_share}, Sync {sync_artist_share} / {sync_label_share}'));
        $this->assertSame('theyellinglight.ch ab 01.09.2026 (VT-2026-0007)', $contract->fillPlaceholders('{webshop_url} ab {start_date} ({contract_number})'));
        $this->assertSame('drei Wochen, Winterthur', $contract->fillPlaceholders('{notice_period}, {jurisdiction}'));
        $this->assertSame(
            'Festgehalten. Der Anteil geht an Tobias Kalt und Lukas Oberholzer. Sie haften solidarisch.',
            $contract->fillPlaceholders('Festgehalten.[[MEHRERE: Der Anteil geht an {artist_members}.]][[EINE: Nur eine Person.]] Sie {haftet|haften} solidarisch.')
        );
    }

    public function test_a_solo_artist_reads_in_the_singular(): void
    {
        $contract = $this->soloContract('male');

        $this->assertSame(
            'Max Muster teilt The yelling light seine Kontaktdaten mit.',
            $contract->fillPlaceholders('{artist_members} {teilt_teilen} {label_name} {je_ihre} Kontaktdaten mit.')
        );
        $this->assertSame('Max Muster', $contract->fillPlaceholders('{artist_name}'));
        $this->assertSame('von Max Muster', $contract->fillPlaceholders('{artist_interpreten}'));
        $this->assertSame('Frei. Allein.', $contract->fillPlaceholders('Frei.[[MEHRERE: Gemeinsam.]][[eine: Allein.]]'));
        // No remuneration row: the general party shares stand in.
        $this->assertSame('70 / 30', $contract->fillPlaceholders('{artist_share} / {label_share}'));

        $this->assertSame('ihre', $this->soloContract('female')->fillPlaceholders('{je_ihre}'));
        $this->assertSame('ihre bzw. seine', $this->soloContract(null)->fillPlaceholders('{je_ihre}'));
    }

    public function test_a_gap_stays_visible_and_is_reported(): void
    {
        $contract = $this->soloContract('male', [
            'subject' => 'Gerichtsstand ist {jurisdiction}. Sync: {sync_artist_share} %.',
            'placeholder_values' => [['key' => 'webshop_url', 'value' => 'shop.example.ch']],
        ]);

        $this->assertSame('Gerichtsstand ist {jurisdiction}. Sync: {sync_artist_share} %.', $contract->fillPlaceholders($contract->subject));
        $this->assertSame([
            'jurisdiction' => 'unbekannt, als Vertragswert erfassen',
            'sync_artist_share' => 'hat in diesem Vertrag keinen Wert',
        ], $contract->placeholders()->problems());

        // A Vertragswert replaces the automatic value of the same name.
        $this->assertSame('shop.example.ch', $contract->fillPlaceholders('{webshop_url}'));
    }

    /** A publisher and one or two authors, the publisher not necessarily first. */
    protected function publishingContract(array $authors): Contract
    {
        $contract = Contract::create([
            'title' => 'Publishing Deal',
            'type' => 'publishing',
            'status' => 'draft',
            'language' => 'de',
        ]);

        foreach ($authors as $i => [$first, $last, $gender]) {
            $author = Contact::create(['first_name' => $first, 'last_name' => $last, 'gender' => $gender]);
            ContractParty::create(['contract_id' => $contract->id, 'contact_id' => $author->id, 'share' => 60 / count($authors), 'role_label' => 'Urheberin oder Urheber', 'sort_order' => $i]);
        }
        $publisher = Organization::create(['type' => 'publishing', 'names' => ['The yelling light']]);
        ContractParty::create(['contract_id' => $contract->id, 'organization_id' => $publisher->id, 'share' => 40, 'role_label' => 'Verlag', 'sort_order' => 9]);

        return $contract->fresh()->load(['parties.organization', 'parties.contact']);
    }

    public function test_a_publishing_deal_fills_publisher_authors_and_verbs(): void
    {
        $contract = $this->publishingContract([['Anna', 'Muster', 'female'], ['Zoë', 'Müller', null]]);

        $this->assertSame(
            'Anna Muster und Zoë Müller übertragen The yelling light die Rechte und erhalten 60 %, der Verlag 40 %.',
            $contract->fillPlaceholders('{author_names} {überträgt_übertragen} {publisher_name} die Rechte und {erhält_erhalten} {author_share} %, der Verlag {publisher_share} %.')
        );
        $this->assertSame(
            'Sie stellen, garantieren, sind und haben.',
            $contract->fillPlaceholders('Sie {stellt_stellen}, {garantiert_garantieren}, {ist_sind} und {hat_haben}.')
        );

        $solo = $this->publishingContract([['Anna', 'Muster', 'female']]);
        $this->assertSame(
            'Anna Muster überträgt The yelling light die Rechte, erhält 60 % und garantiert.',
            $solo->fillPlaceholders('{author_names} {überträgt_übertragen} {publisher_name} die Rechte, {erhält_erhalten} {author_share} % und {garantiert_garantieren}.')
        );
        $this->assertSame('Anna Muster', $solo->fillPlaceholders('{author_name}'));
    }

    /**
     * Only something that looks like one verb in two forms is read as a
     * pair. A missing value must stay visible, never become half its name.
     */
    public function test_a_missing_value_is_never_taken_for_a_verb_pair(): void
    {
        foreach (['notice_period', 'billing_date', 'billing_period_end', 'payout_threshold', 'jurisdiction', 'label_names'] as $name) {
            $this->assertNull(ContractPlaceholders::verbPair($name), $name);
        }
        $this->assertSame(['überträgt', 'übertragen'], ContractPlaceholders::verbPair('überträgt_übertragen'));
        $this->assertSame(['kann', 'können'], ContractPlaceholders::verbPair('kann_können'));

        $contract = $this->publishingContract([['Anna', 'Muster', 'female']]);
        $contract->update(['subject' => 'Kündigungsfrist {notice_period}, {erhält_erhalten}.']);

        $this->assertSame('Kündigungsfrist {notice_period}, erhält.', $contract->fillPlaceholders($contract->subject));
        $this->assertSame(['notice_period' => 'unbekannt, als Vertragswert erfassen'], $contract->fresh()->placeholders()->problems());
    }

    public function test_the_help_lists_every_automatic_placeholder(): void
    {
        $this->assertSame(
            array_keys(ContractPlaceholders::AUTOMATIC),
            array_keys((new Contract(['language' => 'de']))->placeholders()->values())
        );
    }

    public function test_the_pdf_and_the_view_print_the_filled_text(): void
    {
        $contract = $this->bandContract([
            'subject' => '{artist_name} überträgt {label_name} die Rechte. Gerichtsstand: {court}.',
            'sections' => [
                ['title' => 'Vergütung für {artist_name}', 'body' => '{artist_name} erhält {artist_share} %.', 'numbered' => true, 'page_break' => false],
            ],
            'closing_note' => 'Gilt ab {start_date}.',
        ]);

        $html = view('admin.contracts.pdf', [
            'contract' => $contract,
            'typeLabels' => [],
            't' => Contract::pdfStrings('de'),
            'headerParty' => $contract->header_party,
            'logoAbsolutePath' => null,
            'pageCount' => null,
        ])->render();

        $this->assertStringContainsString('Red Tape Redemption überträgt The yelling light die Rechte.', $html);
        $this->assertStringContainsString('Vergütung für Red Tape Redemption', $html);
        $this->assertStringContainsString('Red Tape Redemption erhält 80 %.', $html);
        $this->assertStringContainsString('Gilt ab 01.09.2026.', $html);
        $this->assertStringNotContainsString('{artist_name}', $html);

        $this->actingAs(User::factory()->create())
            ->get("/admin/contracts/{$contract->id}")
            ->assertOk()
            ->assertSee('Red Tape Redemption erhält 80 %.')
            ->assertSee('Platzhalter ohne Wert')
            ->assertSee('<mark class="', false)
            ->assertSee('{court}');
    }

    public function test_a_template_hands_its_values_to_a_new_contract(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/admin/contract-templates', [
            'name' => 'Labeldeal digital',
            'contract_type_slug' => 'label',
            'language' => 'de',
            'default_placeholder_values_submitted' => '1',
            'default_placeholder_values' => [
                ['key' => '{Notice Period}', 'value' => ' drei Wochen '],
                ['key' => 'jurisdiction', 'value' => 'Winterthur'],
                ['key' => '', 'value' => ''],
            ],
        ])->assertRedirect();

        $template = ContractTemplate::where('name', 'Labeldeal digital')->firstOrFail();
        $this->assertSame([
            ['key' => 'notice_period', 'value' => 'drei Wochen'],
            ['key' => 'jurisdiction', 'value' => 'Winterthur'],
        ], $template->default_placeholder_values);

        $this->actingAs($user)
            ->get("/admin/contract-templates/{$template->id}/data")
            ->assertOk()
            ->assertJsonPath('default_placeholder_values.0.key', 'notice_period');

        // The contract form copies the template's rows in the browser and posts them.
        $label = Organization::create(['type' => 'label', 'names' => ['The yelling light']]);
        $artist = Contact::create(['first_name' => 'Max', 'last_name' => 'Muster']);
        $parties = [
            ['type' => 'organization', 'organization_id' => $label->id, 'share' => 20, 'role_label' => 'Label'],
            ['type' => 'contact', 'contact_id' => $artist->id, 'share' => 80, 'role_label' => 'Künstler'],
        ];

        $this->actingAs($user)->post('/admin/contracts', [
            'title' => 'Aus Vorlage',
            'type' => 'label',
            'status' => 'draft',
            'template_id' => $template->id,
            'parties' => $parties,
            'placeholder_values_submitted' => '1',
            'placeholder_values' => [
                ['key' => 'notice_period', 'value' => 'sechs Wochen'],
                ['key' => 'jurisdiction', 'value' => 'Winterthur'],
            ],
        ])->assertRedirect();

        $contract = Contract::where('title', 'Aus Vorlage')->firstOrFail();
        $this->assertSame('sechs Wochen', $contract->fillPlaceholders('{notice_period}'));

        // A submit without the editor leaves the values alone.
        $this->actingAs($user)->put("/admin/contracts/{$contract->id}", [
            'title' => 'Aus Vorlage',
            'type' => 'label',
            'status' => 'draft',
            'parties' => $parties,
        ])->assertRedirect();

        $this->assertCount(2, $contract->fresh()->placeholder_values);
    }

    public function test_an_unusable_name_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->from('/admin/contract-templates/create')
            ->post('/admin/contract-templates', [
                'name' => 'Vorlage',
                'contract_type_slug' => 'label',
                'language' => 'de',
                'default_placeholder_values_submitted' => '1',
                'default_placeholder_values' => [
                    ['key' => '1. Frist', 'value' => 'drei Wochen'],
                    ['key' => '', 'value' => 'ohne Namen'],
                ],
            ])
            ->assertSessionHasErrors(['default_placeholder_values.0.key', 'default_placeholder_values.1.key']);

        $this->assertFalse(ContractTemplate::where('name', 'Vorlage')->exists());
    }
}
