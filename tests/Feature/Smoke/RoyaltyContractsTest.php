<?php

namespace Tests\Feature\Smoke;

use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\ContractType;
use App\Models\Organization;
use App\Models\Release;
use App\Models\RoyaltyImport;
use App\Models\RoyaltyLine;
use App\Models\Track;
use App\Models\User;
use App\Services\Royalties\RoyaltySettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * What the statements need from the contracts: a category per right (which
 * split applies to streaming), amendments that replace a contract from a
 * date, advances per contract - and the overview of which products with
 * usage are covered.
 */
class RoyaltyContractsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Organization $label;
    private Organization $band;

    protected function setUp(): void
    {
        parent::setUp();
        ContractType::firstOrCreate(['slug' => 'label'], ['name' => 'Label', 'sort_order' => 1]);
        $this->user = User::factory()->create();
        $this->label = Organization::create(['type' => 'label', 'names' => ['The yelling light']]);
        $this->band = Organization::create(['type' => 'band', 'names' => ['Tar Pond']]);
    }

    private function labelDeal(array $attributes = []): Contract
    {
        $contract = Contract::create($attributes + [
            'title' => 'Labeldeal', 'type' => 'label', 'status' => 'active', 'start_date' => '2020-01-01',
            'rights' => [
                ['label' => 'Streaming & Downloads', 'mode' => 'split', 'splits' => [20, 80]],
                ['label' => 'Aufführungsrechte', 'mode' => 'custom', 'custom_text' => 'gemäss SUISA'],
            ],
        ]);
        $contract->parties()->create(['organization_id' => $this->label->id, 'share' => 20, 'sort_order' => 0]);
        $contract->parties()->create(['organization_id' => $this->band->id, 'share' => 80, 'sort_order' => 1]);

        return $contract;
    }

    private function usage(Track $track, float $amount): void
    {
        $import = RoyaltyImport::firstOrCreate(['file_name' => 'test.csv'], ['source' => 'rebeat', 'completed_at' => now()]);
        RoyaltyLine::create(['royalty_import_id' => $import->id, 'source' => 'rebeat', 'external_id' => uniqid('', true),
            'track_id' => $track->id, 'period' => '2024-05-01', 'quantity' => 100, 'amount_gross' => $amount / 0.85, 'amount' => $amount]);
    }

    public function test_the_streaming_split_is_found_by_its_category(): void
    {
        $contract = $this->labelDeal();

        $split = $contract->load('parties.organization')->splitFor('streaming');
        $this->assertSame(['The yelling light' => 20.0, 'Tar Pond' => 80.0],
            collect($split)->mapWithKeys(fn ($s) => [$s['party']->organization->primary_name => $s['percent']])->all());
        $this->assertNull($contract->splitFor('performance'), 'Free text is no split');
        $this->assertNull($contract->splitFor('physical'));

        // Chosen in the form beats the label
        $contract->update(['rights' => [['label' => 'Digitale Auswertung', 'mode' => 'split', 'splits' => [30, 70], 'category' => 'streaming']]]);
        $this->assertSame(70.0, $contract->fresh()->load('parties')->splitFor('streaming')[1]['percent']);
        $this->assertSame('physical', Contract::guessRightCategory('Physische Verkäufe'));
        $this->assertSame('other', Contract::guessRightCategory('Mechanische Rechte'));
    }

    public function test_only_signed_contracts_count_and_only_while_valid(): void
    {
        $contract = $this->labelDeal(['start_date' => '2023-06-01', 'end_date' => '2024-12-31']);
        $from = now()->setDate(2023, 1, 1);

        $this->assertTrue($contract->settlesBetween($from, $from->copy()->setDate(2023, 12, 31)));
        $this->assertFalse($contract->settlesBetween($from->copy()->setDate(2025, 1, 1), $from->copy()->setDate(2025, 12, 31)));
        $contract->update(['status' => 'draft']);
        $this->assertFalse($contract->settlesBetween($from, $from->copy()->setDate(2023, 12, 31)));
    }

    public function test_an_amendment_ends_the_contract_it_replaces(): void
    {
        $old = $this->labelDeal();

        $this->actingAs($this->user)->post('/admin/contracts', [
            'title' => 'Nachtrag', 'type' => 'label', 'status' => 'active', 'start_date' => '2026-01-01',
            'supersedes_id' => $old->id,
            'parties' => [
                ['type' => 'organization', 'organization_id' => $this->label->id, 'share' => 30],
                ['type' => 'organization', 'organization_id' => $this->band->id, 'share' => 70],
            ],
            'rights' => [['label' => 'Streaming & Downloads', 'mode' => 'split', 'splits' => [30, 70], 'category' => 'streaming']],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $new = Contract::where('title', 'Nachtrag')->sole();
        $this->assertSame('streaming', $new->rights[0]['category']);
        $this->assertSame('2025-12-31', $old->fresh()->end_date->toDateString());

        $this->actingAs($this->user)->get("/admin/contracts/{$new->id}")->assertOk()->assertSee('Ersetzt:')->assertSee('Streaming &amp; Downloads', false);
        $this->actingAs($this->user)->get("/admin/contracts/{$old->id}")->assertOk()->assertSee('Ersetzt durch:');

        // A contract cannot replace itself
        $this->actingAs($this->user)->put("/admin/contracts/{$new->id}", [
            'title' => 'Nachtrag', 'type' => 'label', 'status' => 'active', 'supersedes_id' => $new->id,
            'parties' => [
                ['type' => 'organization', 'organization_id' => $this->label->id, 'share' => 30],
                ['type' => 'organization', 'organization_id' => $this->band->id, 'share' => 70],
            ],
        ])->assertSessionHasErrors('supersedes_id');
    }

    public function test_advances_are_kept_per_contract(): void
    {
        $contract = $this->labelDeal();

        $this->actingAs($this->user)->post("/admin/contracts/{$contract->id}/advances", [
            'date' => '2023-03-01', 'description' => 'Mastering', 'amount' => '450',
        ])->assertRedirect()->assertSessionHas('success');
        $this->actingAs($this->user)->post("/admin/contracts/{$contract->id}/advances", [
            'date' => '2023-04-01', 'description' => 'CD-Pressung', 'amount' => '1200.50',
        ]);

        $this->assertSame(1650.5, (float) $contract->advances()->sum('amount'));
        $this->actingAs($this->user)->get("/admin/contracts/{$contract->id}")->assertOk()
            ->assertSee('Vorschüsse & verrechenbare Kosten', false)->assertSee('Mastering')->assertSee("1&#039;650.50", false);

        $this->actingAs($this->user)->delete('/admin/contract-advances/' . $contract->advances()->first()->id)->assertRedirect();
        $this->assertSame(1, $contract->advances()->count());
    }

    public function test_a_contract_for_a_product_opens_prefilled(): void
    {
        $release = Release::create(['title' => 'Coroner', 'release_date' => '2021-05-07']);
        $release->organizations()->attach($this->band);
        $track = Track::create(['title' => 'Fog', 'status' => 'released']);
        $release->tracks()->attach($track, ['track_number' => 1, 'disc_number' => 1]);
        $template = ContractTemplate::firstOrCreate(['slug' => 'label-standard'], ['name' => 'Label Standard', 'contract_type_slug' => 'label']);

        $html = $this->actingAs($this->user)->get("/admin/contracts/create?release={$release->id}&template={$template->id}")
            ->assertOk()->getContent();

        $this->assertStringContainsString('value="Tar Pond – Coroner"', $html);
        $this->assertStringContainsString('value="2021-05-07"', $html);
        $this->assertMatchesRegularExpression('/<option value="' . $template->id . '"\s+selected/', $html);
        $this->assertStringContainsString('"organization_id":"' . $this->label->id . '"', $html, 'The own label is a party');
        $this->assertStringContainsString('"organization_id":"' . $this->band->id . '"', $html, 'The band is a party');
        $this->assertStringContainsString('Fog', $html, 'The tracks of the product are linked');
        $this->assertStringContainsString('Veröffentlichung des Produkts', $html);
        $this->assertStringContainsString('"label":"Streaming & Downloads","mode":"split","splits":[20,80],"category":"streaming"', $html,
            'A template without rights brings the streaming split along');
    }

    public function test_without_a_release_date_the_first_usage_is_the_start(): void
    {
        $release = Release::create(['title' => 'Ohne Datum']);
        $track = Track::create(['title' => 'Nebel', 'status' => 'released']);
        $release->tracks()->attach($track, ['track_number' => 1, 'disc_number' => 1]);
        $this->usage($track, 3);

        $this->actingAs($this->user)->get("/admin/contracts/create?release={$release->id}")->assertOk()
            ->assertSee('value="2024-05-01"', false)->assertSee('erste Nutzung bei Rebeat');

        // Opened normally, nothing is suggested
        $this->actingAs($this->user)->get('/admin/contracts/create')->assertOk()
            ->assertDontSee('erste Nutzung bei Rebeat')->assertDontSee('"category":"streaming"', false);
    }

    public function test_the_overview_shows_what_is_covered_and_what_is_missing(): void
    {
        $covered = Release::create(['title' => 'Mit Vertrag']);
        $coveredTrack = Track::create(['title' => 'Song A', 'status' => 'released']);
        $covered->tracks()->attach($coveredTrack, ['track_number' => 1, 'disc_number' => 1]);
        $this->labelDeal()->releases()->attach($covered);
        $this->usage($coveredTrack, 12.5);

        $missing = Release::create(['title' => 'Ohne Vertrag']);
        $missingTrack = Track::create(['title' => 'Song B', 'status' => 'released']);
        $missing->tracks()->attach($missingTrack, ['track_number' => 1, 'disc_number' => 1]);
        $this->usage($missingTrack, 30);

        $zentrum = Organization::create(['type' => 'label', 'names' => ['Zentrum']]);
        $other = Release::create(['title' => 'Domi I']);
        $other->organizations()->attach($zentrum);
        $otherTrack = Track::create(['title' => 'Song C', 'status' => 'released']);
        $other->tracks()->attach($otherTrack, ['track_number' => 1, 'disc_number' => 1]);
        $this->usage($otherTrack, 7);

        $loose = Track::create(['title' => 'Einzelstück', 'status' => 'released']);
        $this->usage($loose, 1);

        $html = $this->actingAs($this->user)->get('/admin/royalties/contracts')->assertOk()
            ->assertSeeInOrder(['Ohne Vertrag', 'Mit Vertrag', 'Domi I'])
            ->assertSee('Vertrag fehlt')->assertSee('abgedeckt')->assertSee('20 % The yelling light · 80 % Tar Pond')
            ->assertSee('ohne Vertrag: 100 % an Zentrum')
            ->assertSee('Tracks ohne Produkt (1)')->assertSee('Einzelstück')
            ->getContent();
        $this->assertStringContainsString("release={$missing->id}", $html, 'A link to the prefilled contract');

        // A label The yelling light owns counts as its own: its products need an artist contract
        $this->actingAs($this->user)->post('/admin/royalties/contracts/own-label', ['organization_ids' => [$zentrum->id, $this->label->id]])
            ->assertSessionHas('success', '2 eigene Labels gespeichert.');
        $this->assertSame([$this->label->id, $zentrum->id], RoyaltySettings::ownOrganizations()->pluck('id')->all(), 'The yelling light first');
        $this->assertSame($this->label->id, RoyaltySettings::ownOrganization()->id);

        $this->actingAs($this->user)->get('/admin/royalties/contracts')->assertOk()
            ->assertDontSee('ohne Vertrag: 100 % an Zentrum')->assertSee('Eigene Labels (2)');

        // ... and a new contract for one of its products is made with that label
        $html = $this->actingAs($this->user)->get("/admin/contracts/create?release={$other->id}")->assertOk()->getContent();
        $this->assertStringContainsString('"organization_id":"' . $zentrum->id . '"', $html);

        $this->actingAs($this->user)->post('/admin/royalties/contracts/own-label', ['organization_ids' => []])->assertSessionHasErrors('organization_ids');
    }
}
