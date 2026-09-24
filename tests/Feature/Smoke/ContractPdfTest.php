<?php

namespace Tests\Feature\Smoke;

use App\Models\Contact;
use App\Models\Contract;
use App\Models\ContractParty;
use App\Models\Organization;
use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke tests for contract PDF generation (ContractController::pdf).
 *
 * These render through dompdf for real. They will not judge whether the layout
 * looks right, but they catch the failure mode that matters on an upgrade: the
 * renderer or the Blade view blowing up instead of producing a PDF.
 */
class ContractPdfTest extends TestCase
{
    use RefreshDatabase;

    protected function makeContract(string $language = 'de'): Contract
    {
        $contract = Contract::create([
            'contract_number' => 'V-2026-' . strtoupper($language),
            'title' => 'Testvertrag',
            'type' => 'other',
            'status' => 'draft',
            'language' => $language,
            'terms' => 'Beispielbedingungen für den Test.',
            'subject' => 'Vertragsgegenstand',
            'territory' => ['CH'],
            'rights' => [
                ['label' => 'Master', 'mode' => 'split', 'splits' => [50, 50]],
                ['label' => 'Publishing', 'mode' => 'custom', 'custom_text' => 'Nach Absprache'],
            ],
            'rights_labels' => ['Label', 'Band'],
        ]);

        $organization = Organization::create([
            'type' => 'label',
            'names' => ['Test Label'],
            'email' => 'label@example.test',
        ]);

        $contact = Contact::create([
            'first_name' => 'Anna',
            'last_name' => 'Muster',
            'email' => 'anna@example.test',
        ]);

        ContractParty::create([
            'contract_id' => $contract->id,
            'organization_id' => $organization->id,
            'share' => 50,
            'sort_order' => 0,
        ]);

        ContractParty::create([
            'contract_id' => $contract->id,
            'contact_id' => $contact->id,
            'share' => 50,
            'sort_order' => 1,
        ]);

        return $contract;
    }

    /**
     * The party preamble is generated from the parties, so a contract no longer
     * needs the "vertreten durch ... nachfolgend «Label»" block typed by hand.
     */
    public function test_the_preamble_is_built_from_the_parties(): void
    {
        $contract = $this->makeContract();
        $contract->parties()->get()->each(fn ($p, $i) => $p->update([
            'role_label' => $i === 0 ? 'Label' : 'Künstlerin oder Künstler',
        ]));
        $contract->load(['parties.organization', 'parties.contact']);

        $blocks = $contract->preambleBlocks(Contract::pdfStrings('de'));

        $this->assertCount(2, $blocks);
        $this->assertSame('Test Label', $blocks[0]['lines'][0]);
        $this->assertSame('(nachfolgend «Label»)', end($blocks[0]['lines']));
        $this->assertSame('(nachfolgend «Künstlerin oder Künstler»)', end($blocks[1]['lines']));
    }

    /**
     * Several people on the same side of a deal are named as one party:
     * "<Band> bestehend aus <Person>, <Person> ... nachfolgend gemeinsam".
     */
    public function test_parties_sharing_a_role_are_grouped_into_one_block(): void
    {
        $contract = $this->makeContract();
        $contract->parties()->delete();

        $band = Organization::create(['type' => 'band', 'names' => ['Red Tape Redemption']]);
        $label = Organization::create(['type' => 'label', 'names' => ['Test Label']]);

        ContractParty::create(['contract_id' => $contract->id, 'organization_id' => $label->id, 'share' => 20, 'role_label' => 'Label', 'sort_order' => 0]);
        foreach (['Tobias' => 'Kalt', 'Lukas' => 'Oberholzer'] as $first => $last) {
            $contact = Contact::create(['first_name' => $first, 'last_name' => $last, 'city' => 'Zürich', 'zip' => '8000']);
            ContractParty::create([
                'contract_id' => $contract->id,
                'organization_id' => $band->id,
                'contact_id' => $contact->id,
                'share' => 40,
                'role_label' => 'Künstlerin oder Künstler',
                'sort_order' => $last === 'Kalt' ? 1 : 2,
            ]);
        }
        $contract->load(['parties.organization', 'parties.contact']);

        $blocks = $contract->preambleBlocks(Contract::pdfStrings('de'));

        $this->assertCount(2, $blocks);
        $this->assertSame([
            'Red Tape Redemption',
            'bestehend aus',
            'Tobias Kalt, 8000 Zürich',
            'Lukas Oberholzer, 8000 Zürich',
            '(nachfolgend gemeinsam «Künstlerin oder Künstler»)',
        ], $blocks[1]['lines']);
    }

    /**
     * A hand-written preamble sitting in the subject must not be duplicated by
     * the generated one — switching the mode off is what does that.
     */
    public function test_the_preamble_can_be_switched_off(): void
    {
        $contract = $this->makeContract();
        $contract->update(['preamble_mode' => 'none']);
        $contract->load(['parties.organization', 'parties.contact']);

        $this->assertSame([], $contract->preambleBlocks(Contract::pdfStrings('de')));
    }

    /**
     * Clauses are numbered across the whole document: the subject is 1, the
     * sections continue from 2 — with un-numbered sections skipped.
     */
    public function test_sections_are_numbered_after_the_subject(): void
    {
        $contract = $this->makeContract();
        $contract->update([
            'sections' => [
                ['title' => 'Vertragsdauer', 'body' => 'Läuft unbefristet.', 'numbered' => true, 'page_break' => false],
                ['title' => 'Hinweis', 'body' => 'Kein Paragraf.', 'numbered' => false, 'page_break' => false],
                ['title' => 'Gerichtsstand', 'body' => 'Winterthur.', 'numbered' => true, 'page_break' => false],
            ],
        ]);

        $html = $this->renderPdfView($contract);

        $this->assertStringContainsString('1.</span> Vertragsgegenstand', $html);
        $this->assertStringContainsString('2.</span> Vertragsdauer', $html);
        $this->assertStringContainsString('3.</span> Gerichtsstand', $html);
        $this->assertStringNotContainsString('3.</span> Hinweis', $html);
    }

    /**
     * Contracts written before the section editor only carry the free text;
     * it still has to print.
     */
    public function test_the_legacy_terms_field_still_prints_without_sections(): void
    {
        $contract = $this->makeContract();

        $html = $this->renderPdfView($contract);

        $this->assertStringContainsString('Beispielbedingungen für den Test.', $html);
    }

    protected function renderPdfView(Contract $contract): string
    {
        return view('admin.contracts.pdf', [
            'contract' => $contract->fresh()->load(['parties.organization', 'parties.contact', 'projects', 'tracks.contacts', 'releases']),
            'typeLabels' => [],
            't' => Contract::pdfStrings($contract->language),
            'headerParty' => $contract->header_party,
            'logoAbsolutePath' => null,
        ])->render();
    }

    public function test_contract_pdf_renders(): void
    {
        $contract = $this->makeContract();

        $response = $this->actingAs(User::factory()->create())
            ->post("/admin/contracts/{$contract->id}/pdf");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');

        $pdf = $response->getContent();

        $this->assertStringStartsWith('%PDF-', $pdf, 'dompdf did not return a PDF document.');
        $this->assertGreaterThan(1000, strlen($pdf), 'PDF is suspiciously small — the view probably rendered empty.');
    }

    /**
     * The PDF ships in three languages via Contract::pdfStrings(). Each one has
     * to render — a missing translation key would only surface here.
     */
    public function test_contract_pdf_renders_in_every_language(): void
    {
        $user = User::factory()->create();

        foreach (['de', 'en', 'es'] as $language) {
            $contract = $this->makeContract($language);

            $response = $this->actingAs($user)
                ->post("/admin/contracts/{$contract->id}/pdf");

            $response->assertOk();
            $this->assertStringStartsWith('%PDF-', $response->getContent(), "PDF failed for language: {$language}");
        }
    }

    /**
     * Werkverzeichnis: the alternative titles of a track belong under its own
     * title, in the smaller line - so the PDF is checked on the rendered view,
     * where the order is still readable.
     */
    public function test_the_pdf_lists_the_alternative_titles_below_the_track_title(): void
    {
        $contract = $this->makeContract();
        $contract->tracks()->attach(Track::create([
            'title' => 'Nachtblau',
            'status' => 'released',
            'alternative_titles' => ['Night Blue', 'Arbeitstitel Grün'],
        ])->id);

        $html = view('admin.contracts.pdf', [
            'contract' => $contract->load(['parties.organization', 'parties.contact', 'projects', 'tracks.contacts', 'releases']),
            'typeLabels' => [],
            't' => Contract::pdfStrings($contract->language),
            'headerParty' => $contract->header_party,
            'logoAbsolutePath' => null,
        ])->render();

        $this->assertStringContainsString('auch: Night Blue, Arbeitstitel Grün', $html);
        $this->assertStringContainsString('track-credit-alt', $html);
        $this->assertLessThan(
            strpos($html, 'auch: Night Blue'),
            strpos($html, 'Nachtblau'),
            'The alternative titles have to sit below the original title.'
        );

        $this->actingAs(User::factory()->create())
            ->post("/admin/contracts/{$contract->id}/pdf")
            ->assertOk();
    }
}
