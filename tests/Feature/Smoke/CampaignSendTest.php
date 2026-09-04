<?php

namespace Tests\Feature\Smoke;

use App\Models\AddressCircle;
use App\Models\Campaign;
use App\Models\CampaignSend;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Smoke tests for the campaign send path (CampaignController::send).
 *
 * This is the one path that cannot be undone once it runs, so it gets the
 * most coverage. The Brevo API is faked; nothing leaves the machine.
 */
class CampaignSendTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.brevo.api_key' => 'test-key']);
    }

    /**
     * Stub the Brevo API. Call once per test — Http::fake() appends stubs and
     * the first match wins, so a second call would not override the first.
     */
    protected function fakeBrevo(array $body = ['messageId' => '<test@brevo>'], int $status = 201): void
    {
        Http::fake(['api.brevo.com/*' => Http::response($body, $status)]);
    }

    /**
     * A campaign with one contact and one organization, ready to send.
     */
    protected function makeCampaign(string $status = 'draft'): Campaign
    {
        $circle = AddressCircle::create(['name' => 'Testkreis', 'info' => '']);

        $contact = Contact::create([
            'first_name' => 'Anna',
            'last_name' => 'Muster',
            'email' => 'anna@example.test',
        ]);

        $organization = Organization::create([
            'type' => 'band',
            'names' => ['Testband', 'Alias'],
            'email' => 'band@example.test',
        ]);

        $circle->contactMembers()->attach($contact->id);
        $circle->organizationMembers()->attach($organization->id);

        return Campaign::create([
            'name' => 'Testkampagne',
            'type' => 'email',
            'status' => $status,
            'language' => 'de',
            'address_circle_id' => $circle->id,
            'subject' => 'Betreff für {name}',
            'body' => '<p>Hallo {name}</p>',
        ]);
    }

    public function test_campaign_reaches_both_contacts_and_organizations(): void
    {
        $this->fakeBrevo();

        $campaign = $this->makeCampaign();

        $this->actingAs(User::factory()->create())
            ->post("/admin/campaigns/{$campaign->id}/send")
            ->assertRedirect(route('admin.campaigns.show', $campaign));

        Http::assertSentCount(2);

        $this->assertSame('sent', $campaign->fresh()->status);
        $this->assertSame(2, $campaign->fresh()->recipients_count);
        $this->assertSame(2, CampaignSend::where('status', 'sent')->count());
    }

    /**
     * Regression: organizations have no `name` column — the display name comes
     * from the `primary_name` accessor. Using `name` wrote NULL into a NOT NULL
     * column and aborted the send mid-run.
     */
    public function test_organization_recipient_gets_a_name(): void
    {
        $this->fakeBrevo();

        $campaign = $this->makeCampaign();

        $this->actingAs(User::factory()->create())
            ->post("/admin/campaigns/{$campaign->id}/send");

        $send = CampaignSend::where('recipient_type', 'organization')->sole();

        $this->assertSame('Testband', $send->name);
        $this->assertSame('band@example.test', $send->email);
    }

    /**
     * Regression: a resume must not mail anyone a second time. Only the
     * recipients without a successful send record should be contacted.
     */
    public function test_resending_skips_already_delivered_recipients(): void
    {
        $this->fakeBrevo();

        $campaign = $this->makeCampaign('sending');
        $contact = Contact::where('email', 'anna@example.test')->sole();

        $delivered = CampaignSend::create([
            'campaign_id' => $campaign->id,
            'recipient_type' => 'contact',
            'recipient_id' => $contact->id,
            'email' => 'anna@example.test',
            'name' => 'Anna Muster',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $this->actingAs(User::factory()->create())
            ->post("/admin/campaigns/{$campaign->id}/send");

        // Only the organization is left to receive the campaign.
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['to'][0]['email'] === 'band@example.test');

        // The earlier record survives instead of being wiped and resent.
        $this->assertDatabaseHas('campaign_sends', [
            'id' => $delivered->id,
            'status' => 'sent',
        ]);
        $this->assertSame(2, $campaign->fresh()->recipients_count);
    }

    /**
     * A failed attempt must not leave the campaign stuck, and the address has
     * to stay eligible for a later retry.
     */
    public function test_failed_send_is_recorded_and_stays_retryable(): void
    {
        $this->fakeBrevo(['message' => 'Sender not verified'], 400);

        $campaign = $this->makeCampaign();

        $this->actingAs(User::factory()->create())
            ->post("/admin/campaigns/{$campaign->id}/send");

        $this->assertSame(2, CampaignSend::where('status', 'failed')->count());
        $this->assertSame(0, $campaign->fresh()->recipients_count);

        $failed = CampaignSend::where('status', 'failed')->first();
        $this->assertSame('Sender not verified', $failed->error);
    }

    public function test_send_is_refused_without_a_brevo_key(): void
    {
        config(['services.brevo.api_key' => '']);

        $this->fakeBrevo();

        $campaign = $this->makeCampaign();

        $this->actingAs(User::factory()->create())
            ->post("/admin/campaigns/{$campaign->id}/send")
            ->assertSessionHas('error');

        Http::assertNothingSent();
        $this->assertSame('draft', $campaign->fresh()->status);
    }

    public function test_placeholders_are_replaced_per_recipient(): void
    {
        $this->fakeBrevo();

        $campaign = $this->makeCampaign();

        $this->actingAs(User::factory()->create())
            ->post("/admin/campaigns/{$campaign->id}/send");

        Http::assertSent(function ($request) {
            return $request['to'][0]['email'] === 'anna@example.test'
                && $request['subject'] === 'Betreff für Anna Muster'
                && str_contains($request['htmlContent'], 'Hallo Anna Muster');
        });
    }
}
