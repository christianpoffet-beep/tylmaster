<?php

namespace Tests\Feature\Smoke;

use App\Models\AddressCircle;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke tests for the CSV exports (contacts, organizations, address circles).
 *
 * These are streamed responses built with fputcsv, so the assertions check that
 * the stream actually produces rows — an exception inside the stream callback
 * would otherwise still return HTTP 200 with a truncated body.
 */
class CsvExportTest extends TestCase
{
    use RefreshDatabase;

    protected function seedRecords(): array
    {
        $contact = Contact::create([
            'first_name' => 'Anna',
            'last_name' => 'Muster',
            'email' => 'anna@example.test',
            'city' => 'Winterthur',
        ]);

        $organization = Organization::create([
            'type' => 'band',
            'names' => ['Testband', 'Zweitname'],
            'email' => 'band@example.test',
            'city' => 'Zürich',
        ]);

        return [$contact, $organization];
    }

    public function test_contacts_export_returns_a_csv_with_rows(): void
    {
        $this->seedRecords();

        $response = $this->actingAs(User::factory()->create())
            ->get('/admin/contacts-export');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv, 'BOM is missing — Excel would mangle the umlauts.');
        $this->assertStringContainsString('Nachname', $csv);
        $this->assertStringContainsString('Muster', $csv);
        $this->assertStringContainsString('anna@example.test', $csv);
    }

    /**
     * Regression guard: organizations carry their names in a JSON array, so the
     * export has to go through `primary_name` rather than a `name` column.
     */
    public function test_organizations_export_writes_the_primary_name(): void
    {
        $this->seedRecords();

        $response = $this->actingAs(User::factory()->create())
            ->get('/admin/organizations-export');

        $response->assertOk();

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Testband', $csv);
        $this->assertStringContainsString('Zweitname', $csv, 'Secondary names should land in the "Weitere Namen" column.');
        $this->assertStringContainsString('band@example.test', $csv);
    }

    public function test_address_circle_export_contains_both_member_types(): void
    {
        [$contact, $organization] = $this->seedRecords();

        $circle = AddressCircle::create(['name' => 'Testkreis', 'info' => '']);
        $circle->contactMembers()->attach($contact->id);
        $circle->organizationMembers()->attach($organization->id);

        $response = $this->actingAs(User::factory()->create())
            ->get("/admin/address-circles/{$circle->id}/export");

        $response->assertOk();

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Muster', $csv);
        $this->assertStringContainsString('Testband', $csv);
    }
}
