<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class ContractParty extends Model
{
    use LogsActivity;

    protected $fillable = [
        'contract_id', 'organization_id', 'contact_id', 'share', 'role_label', 'sort_order',
    ];

    protected $casts = [
        'share' => 'decimal:2',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function getNameAttribute(): string
    {
        if ($this->organization) {
            $name = $this->organization->primary_name;
            if ($this->contact) {
                $name .= ' (AP: ' . $this->contact->full_name . ')';
            }
            return $name;
        }
        if ($this->contact) {
            return $this->contact->full_name;
        }
        return '—';
    }

    /**
     * Everything the party search offers: every organization, every person,
     * and every person within an organization ("Red Tape Redemption · Tobias
     * Kalt"), so a band member or the label's signatory is one pick.
     *
     * Expects the organizations with their contacts loaded.
     *
     * @return array{entries: array, organizations: object, contacts: object}
     */
    public static function pickerOptions(iterable $organizations, iterable $contacts): array
    {
        $typeLabels = OrganizationType::pluck('name', 'slug')->all();
        $entries = [];
        $orgs = [];
        $people = [];
        $orgsOfContact = [];

        foreach ($organizations as $org) {
            $name = trim((string) $org->primary_name);
            if ($name === '') {
                continue;
            }
            $type = $typeLabels[$org->type] ?? null;
            $detail = implode(' · ', array_filter([$type, $org->city]));
            $orgs[$org->id] = ['name' => $name, 'detail' => $detail];

            $entries[] = [
                'key' => 'o' . $org->id,
                'type' => 'organization',
                'organization_id' => $org->id,
                'contact_id' => null,
                'name' => $name,
                'detail' => $detail,
                'badge' => 'Organisation',
                'member' => false,
                'search' => implode(' ', array_filter([$org->all_names, $type, $org->city])),
            ];

            foreach ($org->contacts as $contact) {
                $person = trim((string) $contact->full_name);
                if ($person === '') {
                    continue;
                }
                $orgsOfContact[$contact->id][] = $name;
                $entries[] = [
                    'key' => 'o' . $org->id . 'c' . $contact->id,
                    'type' => 'organization',
                    'organization_id' => $org->id,
                    'contact_id' => $contact->id,
                    'name' => $name . ' · ' . $person,
                    'detail' => implode(' · ', array_filter([$type, 'Ansprechperson ' . $person])),
                    'badge' => 'Org. + Person',
                    'member' => true,
                    'search' => implode(' ', array_filter([$org->all_names, $person, $type])),
                ];
            }
        }

        foreach ($contacts as $contact) {
            $name = trim((string) $contact->full_name);
            if ($name === '') {
                continue;
            }
            $memberOf = $orgsOfContact[$contact->id] ?? [];
            $detail = implode(' · ', array_filter([$contact->city, $memberOf ? implode(', ', $memberOf) : null]));
            $people[$contact->id] = ['name' => $name, 'detail' => $detail];

            $entries[] = [
                'key' => 'c' . $contact->id,
                'type' => 'contact',
                'organization_id' => null,
                'contact_id' => $contact->id,
                'name' => $name,
                'detail' => $detail,
                'badge' => 'Person',
                'member' => false,
                'search' => implode(' ', array_filter([$name, $contact->city, $contact->email, implode(' ', $memberOf)])),
            ];
        }

        usort($entries, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        // Objects, so ids never collapse into a JSON list.
        return ['entries' => $entries, 'organizations' => (object) $orgs, 'contacts' => (object) $people];
    }

    /**
     * Address lines (Strasse, PLZ Ort) of this party's primary entity,
     * skipping any that are empty.
     */
    public function getAddressLinesAttribute(): array
    {
        $entity = $this->organization ?? $this->contact;
        if (!$entity) {
            return [];
        }

        $lines = [];
        if ($entity->street) {
            $lines[] = $entity->street;
        }
        if ($entity->zip || $entity->city) {
            $lines[] = trim(($entity->zip ?? '') . ' ' . ($entity->city ?? ''));
        }

        return $lines;
    }
}
