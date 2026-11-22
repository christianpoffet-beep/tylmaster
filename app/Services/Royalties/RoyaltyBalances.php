<?php

namespace App\Services\Royalties;

use App\Models\Contact;
use App\Models\Organization;
use App\Models\RoyaltyLedgerEntry;
use App\Support\Iban;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The balances of artists and labels: the sum of their credits, payouts and
 * corrections. A party is an organization (a band, a label) or a person.
 */
class RoyaltyBalances
{
    /** Everyone with a balance other than zero, the highest first */
    public function all(): Collection
    {
        $rows = RoyaltyLedgerEntry::selectRaw('organization_id, contact_id, sum(amount) as balance, max(entry_date) as last_entry')
            ->groupBy('organization_id', 'contact_id')->toBase()->get()
            ->filter(fn ($row) => abs((float) $row->balance) >= 0.005);

        $organizations = Organization::findMany($rows->pluck('organization_id')->filter())->keyBy('id');
        $contacts = Contact::findMany($rows->pluck('contact_id')->filter())->keyBy('id');

        return $rows->map(fn ($row) => $this->party(
            $row->organization_id ? $organizations->get($row->organization_id) : null,
            $row->organization_id ? null : $contacts->get($row->contact_id),
            round((float) $row->balance, 2),
            $row->last_entry,
        ))->filter(fn ($p) => $p->organization || $p->contact)->sortByDesc('balance')->values();
    }

    public function of(Organization|Contact $who): object
    {
        $balance = (float) $this->entries($who)->sum('amount');

        return $this->party($who instanceof Organization ? $who : null, $who instanceof Contact ? $who : null, round($balance, 2), null);
    }

    public function entries(Organization|Contact $who)
    {
        return $who instanceof Organization
            ? RoyaltyLedgerEntry::where('organization_id', $who->id)
            : RoyaltyLedgerEntry::whereNull('organization_id')->where('contact_id', $who->id);
    }

    /** "org-12" or "contact-5", as forms and URLs name a party */
    public static function find(string $key): Organization|Contact|null
    {
        [$type, $id] = array_pad(explode('-', $key, 2), 2, null);

        return match ($type) {
            'org' => Organization::find((int) $id),
            'contact' => Contact::find((int) $id),
            default => null,
        };
    }

    private function party(?Organization $organization, ?Contact $contact, float $balance, ?string $lastEntry): object
    {
        $who = $organization ?? $contact;
        $iban = Iban::normalize($who?->iban);

        return (object) [
            'key' => $organization ? 'org-' . $organization->id : 'contact-' . $contact?->id,
            'organization' => $organization,
            'contact' => $contact,
            'name' => $organization?->primary_name ?? $contact?->full_name ?? '—',
            'balance' => $balance,
            'last_entry' => $lastEntry,
            'iban' => $iban,
            'iban_ok' => Iban::isValid($iban) && !Iban::isQrIban($iban),
            'email' => $who?->email,
            'street' => trim((string) $who?->street) ?: null,
            'zip' => trim((string) $who?->zip) ?: null,
            'city' => trim((string) $who?->city) ?: null,
            'country' => RoyaltyPayouts::countryCode($who?->country, $iban),
        ];
    }
}
