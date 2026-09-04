<?php

namespace Tests\Feature\Smoke;

use App\Models\Contact;
use App\Models\Contract;
use App\Models\ContractParty;
use App\Models\Organization;
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
}
