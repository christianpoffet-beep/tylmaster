<?php

namespace Tests\Feature\Smoke;

use App\Models\Contact;
use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\ContractType;
use App\Models\Organization;
use App\Models\Release;
use App\Models\Track;
use App\Models\User;
use App\Services\ContractTrackPicker;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Tracks are added to a contract like to a product's tracklist: search or
 * filter by band, label and person, then add with the plus. A track has to
 * turn up through its product's label and its band's members too, which are
 * often the only link entered.
 */
class ContractTrackPickerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_track_is_found_through_band_label_and_person(): void
    {
        $band = Organization::create(['type' => 'band', 'names' => ['Red Tape Redemption']]);
        $label = Organization::create(['type' => 'label', 'names' => ['The yelling light']]);
        $member = Contact::create(['first_name' => 'Tobias', 'last_name' => 'Kalt']);
        $band->contacts()->attach($member->id);
        $composer = Contact::create(['first_name' => 'Zoë', 'last_name' => 'Müller']);

        // Band on the track, label only on the product, a credit for the composer.
        $track = Track::create(['title' => 'Nachtblau', 'status' => 'released', 'isrc' => 'CH4811280001']);
        $track->organizations()->attach($band->id);
        $track->contacts()->attach($composer->id, ['role' => 'composer']);
        $release = Release::create(['title' => 'Blue Hour', 'status' => 'released']);
        $release->organizations()->attach($label->id);
        $track->releases()->attach($release->id, ['track_number' => 1]);

        $other = Track::create(['title' => 'Anderswo', 'status' => 'draft']);

        $config = ContractTrackPicker::config([$track->id, 9999]);
        $row = collect($config['tracks'])->firstWhere('id', $track->id);

        $this->assertSame([$track->id], $config['selected'], 'Unknown ids are dropped.');
        $this->assertSame('Red Tape Redemption', $row['bands']);
        $this->assertSame('The yelling light', $row['labels']);
        $this->assertSame([$band->id], $row['bandIds']);
        $this->assertSame([$label->id], $row['labelIds']);
        $this->assertEqualsCanonicalizing([$composer->id, $member->id], $row['personIds']);
        foreach (['the yelling light', 'tobias kalt', 'müller', 'blue hour', 'ch4811280001'] as $term) {
            $this->assertStringContainsString($term, $row['haystack'], "«{$term}» must find the track.");
        }

        $this->assertSame(['Red Tape Redemption'], array_column($config['bands'], 'name'));
        $this->assertSame(['The yelling light'], array_column($config['labels'], 'name'));
        $this->assertSame(['Tobias Kalt', 'Zoë Müller'], array_column($config['people'], 'name'));
        $this->assertSame([], collect($config['tracks'])->firstWhere('id', $other->id)['personIds']);
    }

    public function test_the_contract_and_template_forms_use_the_picker(): void
    {
        $user = User::factory()->create();
        Track::create(['title' => 'Nachtblau', 'status' => 'released']);
        $contract = Contract::create(['title' => 'V', 'type' => 'label', 'status' => 'draft']);
        $template = ContractTemplate::create(['name' => 'Vorlage', 'contract_type_slug' => 'label', 'language' => 'de']);

        $pages = [
            '/admin/contracts/create' => 'track_ids',
            "/admin/contracts/{$contract->id}/edit" => 'track_ids',
            '/admin/contract-templates/create' => 'default_track_ids',
            "/admin/contract-templates/{$template->id}/edit" => 'default_track_ids',
        ];
        foreach ($pages as $url => $field) {
            $html = $this->actingAs($user)->get($url)->assertOk()->getContent();
            $this->assertStringContainsString('x-data="contractTrackPicker(', $html, $url);
            $this->assertStringContainsString('name="' . $field . '_submitted"', $html, $url);
            $this->assertStringContainsString('name="' . $field . '[]"', $html, $url);
            $this->assertStringNotContainsString('x-data="trackSearch()"', $html, $url);
        }
    }

    public function test_the_picked_tracks_are_saved(): void
    {
        ContractType::firstOrCreate(['slug' => 'label'], ['name' => 'Label', 'sort_order' => 1]);
        $track = Track::create(['title' => 'Nachtblau', 'status' => 'released']);
        $contract = Contract::create(['title' => 'V', 'type' => 'label', 'status' => 'draft']);
        $label = Organization::create(['type' => 'label', 'names' => ['The yelling light']]);
        $artist = Contact::create(['first_name' => 'Max', 'last_name' => 'Muster']);

        $this->actingAs(User::factory()->create())->put("/admin/contracts/{$contract->id}", [
            'title' => 'V',
            'type' => 'label',
            'status' => 'draft',
            'parties' => [
                ['type' => 'organization', 'organization_id' => $label->id, 'share' => 50],
                ['type' => 'contact', 'contact_id' => $artist->id, 'share' => 50],
            ],
            'track_ids_submitted' => '1',
            'track_ids' => [$track->id],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame([$track->id], $contract->fresh()->tracks->pluck('id')->all());
    }
}
