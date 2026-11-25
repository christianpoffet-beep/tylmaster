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
     * Two band members, but only the first carries a role (or the rights table
     * holds fewer labels than there are parties): the second still belongs to
     * the band's side instead of standing alone without "nachfolgend".
     */
    public function test_a_band_member_without_a_role_joins_the_one_before(): void
    {
        $contract = $this->makeContract();
        $contract->parties()->delete();
        $contract->update(['rights_labels' => ['Label', 'Band']]);

        $band = Organization::create(['type' => 'band', 'names' => ['Red Tape Redemption']]);
        $label = Organization::create(['type' => 'label', 'names' => ['Test Label']]);

        ContractParty::create(['contract_id' => $contract->id, 'organization_id' => $label->id, 'share' => 20, 'sort_order' => 0]);
        foreach (['Tobias' => 'Kalt', 'Lukas' => 'Oberholzer'] as $first => $last) {
            ContractParty::create([
                'contract_id' => $contract->id,
                'organization_id' => $band->id,
                'contact_id' => Contact::create(['first_name' => $first, 'last_name' => $last])->id,
                'share' => 40,
                'sort_order' => $last === 'Kalt' ? 1 : 2,
            ]);
        }
        $contract->load(['parties.organization', 'parties.contact']);

        $blocks = $contract->preambleBlocks(Contract::pdfStrings('de'));

        $this->assertCount(2, $blocks);
        $this->assertSame([
            'Red Tape Redemption',
            'bestehend aus',
            'Tobias Kalt',
            'Lukas Oberholzer',
            '(nachfolgend gemeinsam «Band»)',
        ], $blocks[1]['lines']);
    }

    /**
     * Two authors, one entered as an artist (organization), the other as a
     * person: both are named, not "<Artist> bestehend aus <Person>".
     */
    public function test_an_artist_next_to_a_person_is_not_a_band_and_its_member(): void
    {
        $contract = $this->makeContract();
        $contract->parties()->delete();

        $publisher = Organization::create(['type' => 'label', 'names' => ['Test Verlag']]);
        $artist = Organization::create(['type' => 'band', 'names' => ['Domi Test']]);
        $person = Contact::create(['first_name' => 'Béa', 'last_name' => 'Muster', 'city' => 'Genf']);

        ContractParty::create(['contract_id' => $contract->id, 'organization_id' => $publisher->id, 'share' => 15, 'role_label' => 'Verlag', 'sort_order' => 0]);
        ContractParty::create(['contract_id' => $contract->id, 'organization_id' => $artist->id, 'share' => 42.5, 'role_label' => 'Urheberschaft', 'sort_order' => 1]);
        ContractParty::create(['contract_id' => $contract->id, 'contact_id' => $person->id, 'share' => 42.5, 'role_label' => 'Urheberschaft', 'sort_order' => 2]);
        $contract->load(['parties.organization', 'parties.contact']);

        $blocks = $contract->preambleBlocks(Contract::pdfStrings('de'));

        $this->assertSame(['Domi Test', 'Béa Muster', 'Genf', '(nachfolgend gemeinsam «Urheberschaft»)'], $blocks[1]['lines']);
        $this->assertSame('Domi Test und Béa Muster', $contract->fillPlaceholders('{author_names}'));
        $this->assertSame('15 / 85, übertragen', $contract->fillPlaceholders('{publisher_share} / {author_share}, {überträgt|übertragen}'));
    }

    /**
     * The letterhead reads like one: no "Typ" row, the kind of contract next
     * to its number, Swiss phone numbers grouped, no "https://".
     */
    public function test_the_header_names_the_kind_of_contract(): void
    {
        $contract = $this->makeContract();
        $contract->update(['type' => 'label', 'end_date' => null, 'start_date' => '2026-09-01']);
        Organization::first()->update(['phone' => '+41795676789', 'websites' => ['https://www.example.test/']]);

        $html = view('admin.contracts.pdf', [
            'contract' => $contract->fresh()->load(['parties.organization', 'parties.contact', 'projects', 'tracks.contacts', 'releases']),
            'typeLabels' => ['label' => 'Label'],
            't' => Contract::pdfStrings('de'),
            'headerParty' => $contract->fresh()->header_party,
            'logoAbsolutePath' => null,
        ])->render();

        $this->assertStringContainsString('Labelvertrag', $html);
        $this->assertStringContainsString('+41 79 567 67 89', $html);
        $this->assertStringContainsString('>www.example.test<', $html);
        $this->assertStringContainsString('unbefristet', $html);
        $this->assertStringNotContainsString('>Typ<', $html);
        $this->assertStringContainsString('>50%<', $html);
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
     * The clauses come first and all share one heading level; the data they
     * refer to (recordings, remuneration, territory) follows as the annex,
     * still above the signatures.
     */
    public function test_the_data_blocks_follow_the_clauses_as_annex(): void
    {
        $contract = $this->makeContract();
        $contract->update([
            'relations_heading' => 'Anhang: Aufnahmen',
            'relations_note' => 'Folgende Aufnahmen sind Gegenstand dieses Vertrags.',
            'sections' => [
                ['title' => 'Gerichtsstand', 'body' => 'Winterthur.', 'numbered' => true, 'page_break' => false],
            ],
        ]);

        $html = $this->renderPdfView($contract);

        $this->assertStringContainsString('<div class="clause-title"><span class="clause-number">1.</span> Vertragsgegenstand', $html);
        $this->assertSame(1, substr_count($html, '>Anhang</div>'));
        $this->assertStringNotContainsString('Anhang: Aufnahmen', $html);

        $positions = array_map(fn ($needle) => strpos($html, $needle), [
            '2.</span> Gerichtsstand', '>Anhang</div>', '>Aufnahmen</div>', '>Vergütung</div>', '>Vertragsgebiet</div>', 'Ort, Datum, Unterschrift',
        ]);
        $sorted = $positions;
        sort($sorted);
        $this->assertNotContains(false, $positions);
        $this->assertSame($sorted, $positions, 'Clauses, annex and signatures are out of order.');
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

    /**
     * A free text pasted with its own headings ("2. Rechteübertragung") prints
     * as clauses with the numbers as typed, not as one block under
     * «Bedingungen». A numbered list inside a clause is no heading.
     */
    public function test_headings_in_the_free_text_become_clauses(): void
    {
        $contract = $this->makeContract();
        $contract->update(['terms' => "2. Rechteübertragung\nDie Rechte gehen über.\n\n3. SUISA\nGemäss Ziff. 2.\n\n4. Pflichten von {publisher_name}\nBetreuen."]);

        $html = $this->renderPdfView($contract);
        $this->assertMatchesRegularExpression('/clause-number">2\.<\/span>\s*Rechteübertragung/u', $html);
        $this->assertMatchesRegularExpression('/clause-number">3\.<\/span>\s*SUISA/u', $html);
        $this->assertStringContainsString('Pflichten von Test Label', $html);
        $this->assertStringNotContainsString('>Bedingungen<', $html);

        $this->actingAs(User::factory()->create())->get("/admin/contracts/{$contract->id}")->assertOk()
            ->assertSee('Rechteübertragung')->assertSee('Gemäss Ziff. 2.');

        // A list that starts again at 1: no headings, one block as before
        $this->assertNull(Contract::splitHeadedText("1. Rechte\nText\n2. Pflichten\nDer Künstler:\n1. liefert\n2. meldet"));
        $this->assertNull(Contract::splitHeadedText("Nur Text.\n1. Ein einziger Punkt"));
        $this->assertSame([['title' => '', 'body' => 'Vorab.', 'number' => null], ['title' => 'A', 'body' => 'x', 'number' => 5], ['title' => 'B', 'body' => 'y', 'number' => 6]],
            Contract::splitHeadedText("Vorab.\n5. A\nx\n6) B\ny"));
    }

    /**
     * How the songs appear is chosen with the logo: only the title, the title
     * with the names, or the full credits (as before, the default).
     */
    public function test_the_songs_print_as_title_names_or_credits(): void
    {
        $contract = $this->makeContract();
        $contract->update(['type' => 'label']);
        $track = Track::create(['title' => 'Faust', 'status' => 'released', 'isrc' => 'CH4812600021']);
        $domi = Contact::create(['first_name' => 'Domi', 'last_name' => 'Test']);
        $bea = Contact::create(['first_name' => 'Béa', 'last_name' => 'Muster']);
        $track->contacts()->attach($domi->id, ['role' => 'composer', 'ipi_number' => '00660960337']);
        $track->contacts()->attach($domi->id, ['role' => 'instrumentalist', 'instrument' => 'Drums']);
        $track->contacts()->attach($bea->id, ['role' => 'lyricist']);
        $contract->tracks()->attach($track);

        $html = $this->renderPdfView($contract);
        $this->assertStringContainsString('Instrumentalist:', $html, 'No choice yet: the full credits');
        $this->assertStringContainsString('(Drums)', $html);

        $contract->update(['track_details' => 'names']);
        $html = $this->renderPdfView($contract);
        $this->assertStringContainsString('Domi Test, Béa Muster', $html, 'Each person once');
        $this->assertStringNotContainsString('Komponist:', $html);
        $this->assertStringNotContainsString('(Drums)', $html);

        $contract->update(['track_details' => 'title']);
        $html = $this->renderPdfView($contract);
        $this->assertStringContainsString('Faust', $html);
        $this->assertStringNotContainsString('Domi Test', $html);

        // The form keeps the choice; a form without the field leaves it alone
        $user = User::factory()->create();
        \App\Models\ContractType::firstOrCreate(['slug' => 'label'], ['name' => 'Label', 'sort_order' => 1]);
        $form = ['title' => 'Testvertrag', 'type' => 'label', 'status' => 'draft', 'language' => 'de', 'parties' => [
            ['type' => 'organization', 'organization_id' => Organization::first()->id, 'share' => 50],
            ['type' => 'contact', 'contact_id' => $domi->id, 'share' => 50],
        ]];
        $this->actingAs($user)->get("/admin/contracts/{$contract->id}/edit")->assertOk()->assertSee('Titel und beteiligte Namen');
        $this->actingAs($user)->put("/admin/contracts/{$contract->id}", $form + ['track_details' => 'names'])->assertSessionHasNoErrors();
        $this->assertSame('names', $contract->fresh()->track_details);
        $this->actingAs($user)->put("/admin/contracts/{$contract->id}", $form)->assertSessionHasNoErrors();
        $this->assertSame('names', $contract->fresh()->track_details);
        $this->actingAs($user)->put("/admin/contracts/{$contract->id}", $form + ['track_details' => 'everything'])->assertSessionHasNoErrors();
        $this->assertNull($contract->fresh()->track_details, 'Unknown: back to the default');
        $this->assertSame('credits', $contract->fresh()->trackDetails());

        // A template hands its choice to a new contract
        $this->actingAs($user)->post('/admin/contract-templates', ['name' => 'Nur Titel', 'contract_type_slug' => 'label', 'language' => 'de', 'track_details' => 'title'])
            ->assertSessionHasNoErrors();
        $template = \App\Models\ContractTemplate::where('name', 'Nur Titel')->sole();
        $this->assertSame('title', $template->track_details);
        $this->actingAs($user)->getJson("/admin/contract-templates/{$template->id}/data")->assertJsonPath('track_details', 'title');
    }

    /**
     * A publishing deal is about the works: the annex speaks of works and
     * lists their authors, not who played on the recording.
     */
    public function test_a_publishing_annex_lists_the_authors_of_the_works(): void
    {
        $contract = $this->makeContract();
        $contract->update(['type' => 'publishing', 'relations_note' => null]);
        $track = Track::create(['title' => 'Faust', 'status' => 'released']);
        $author = Contact::create(['first_name' => 'Domi', 'last_name' => 'Test']);
        $track->contacts()->attach($author->id, ['role' => 'composer', 'ipi_number' => '00660960337']);
        $track->contacts()->attach($author->id, ['role' => 'instrumentalist', 'instrument' => 'Drums']);
        $contract->tracks()->attach($track);

        $html = $this->renderPdfView($contract);
        $this->assertStringContainsString('Folgende Werke sind Bestandteil dieses Vertrages.', $html);
        $this->assertStringContainsString('>Werke<', $html);
        $this->assertStringContainsString('Komponist:', $html);
        $this->assertStringNotContainsString('Instrumentalist:', $html);

        $contract->update(['type' => 'label']);
        $html = $this->renderPdfView($contract);
        $this->assertStringContainsString('Folgende Songs sind Bestandteil dieses Vertrages.', $html);
        $this->assertStringContainsString('Instrumentalist:', $html);
    }

    /**
     * dompdf has no "pages" counter, so the total is counted in a first pass
     * and handed to the view. Without it only the page number is printed —
     * never "von 0".
     */
    public function test_the_footer_numbers_the_pages(): void
    {
        $contract = $this->makeContract();

        $withTotal = $this->renderPdfView($contract, 3);
        $this->assertStringContainsString('content: "Seite " counter(page) " von 3";', $withTotal);

        $withoutTotal = $this->renderPdfView($contract);
        $this->assertStringContainsString('content: "Seite " counter(page);', $withoutTotal);
        $this->assertStringNotContainsString('von 0', $withoutTotal);
    }

    /**
     * The download path has to report the number of pages it actually produced.
     */
    public function test_the_pdf_route_counts_its_own_pages(): void
    {
        $contract = $this->makeContract();

        $response = $this->actingAs(User::factory()->create())
            ->post("/admin/contracts/{$contract->id}/pdf");

        $response->assertOk();
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    protected function renderPdfView(Contract $contract, ?int $pageCount = null): string
    {
        return view('admin.contracts.pdf', [
            'contract' => $contract->fresh()->load(['parties.organization', 'parties.contact', 'projects', 'tracks.contacts', 'releases']),
            'typeLabels' => [],
            't' => Contract::pdfStrings($contract->language),
            'headerParty' => $contract->header_party,
            'logoAbsolutePath' => null,
            'pageCount' => $pageCount,
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

        foreach (array_keys(Contract::LANGUAGES) as $language) {
            $contract = $this->makeContract($language);

            $response = $this->actingAs($user)
                ->post("/admin/contracts/{$contract->id}/pdf");

            $response->assertOk();
            $this->assertStringStartsWith('%PDF-', $response->getContent(), "PDF failed for language: {$language}");
            $this->assertSame([], array_diff(array_keys(Contract::PDF_STRINGS['de']), array_keys(Contract::pdfStrings($language))),
                "Every text of the PDF in {$language}");
        }
        $this->assertEqualsCanonicalizing(array_keys(Contract::LANGUAGES), array_keys(Contract::PDF_STRINGS), 'A language to choose is a language to print');
    }

    /**
     * A French contract: the kind of contract, the preamble and the annex
     * in French, the contract and the template accept the language.
     */
    public function test_a_french_contract_prints_in_french(): void
    {
        $contract = $this->makeContract('fr');
        $contract->update(['type' => 'publishing']);
        $contract->parties()->get()->each(fn ($p, $i) => $p->update(['role_label' => $i === 0 ? 'Éditeur' : 'Auteur']));
        $track = Track::create(['title' => 'Faust', 'status' => 'released']);
        $track->contacts()->attach(Contact::first()->id, ['role' => 'lyricist']);
        $contract->tracks()->attach($track);

        $html = $this->renderPdfView($contract);
        $this->assertStringContainsString("Contrat d&#039;édition", $html);
        $this->assertStringContainsString("ci-après «\u{00A0}Éditeur\u{00A0}»", $html);
        $this->assertStringContainsString('Les œuvres suivantes font partie du présent contrat.', $html);
        $this->assertStringContainsString('Paroles:', $html);
        $this->assertStringContainsString('Lieu, date, signature', $html);

        $user = User::factory()->create();
        \App\Models\ContractType::firstOrCreate(['slug' => 'publishing'], ['name' => 'Publishing', 'sort_order' => 2]);
        $this->actingAs($user)->get('/admin/contracts/create')->assertOk()->assertSee('<option value="fr"', false)->assertSee('Français');
        $this->actingAs($user)->get("/admin/contracts/{$contract->id}")->assertOk()->assertSee('Français');
        $this->actingAs($user)->post('/admin/contract-templates', ['name' => 'Contrat d\'édition', 'contract_type_slug' => 'publishing', 'language' => 'fr'])
            ->assertSessionHasNoErrors();
        $this->assertSame('fr', \App\Models\ContractTemplate::where('name', "Contrat d'édition")->value('language'));
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
