<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractParty;

/**
 * Fills the placeholders in a contract's texts.
 *
 * A template is written once with {label_name}, {artist_members} …, and every
 * contract made from it prints its own names, shares and dates. The values
 * come from the contract itself (parties, remuneration, dates) and from its
 * list of «Vertragswerte» (notice period, place of jurisdiction …). A
 * Vertragswert also replaces an automatic value of the same name.
 *
 *   {name}            a value
 *   {teilt|teilen}    singular | plural, by the persons on the artist's side
 *   [[MEHRERE: …]]    printed only when several persons sign for the artist
 *   [[EINE: …]]       printed only when one person signs for the artist
 *
 * A placeholder without a value stays in the text exactly as typed, so a gap
 * shows instead of silently vanishing. The texts are stored with their
 * placeholders and filled when shown, so correcting a party or a share later
 * corrects the text too.
 */
class ContractPlaceholders
{
    /** A placeholder name: a letter, then letters, digits or "_". */
    public const NAME = '[\p{L}][\p{L}\p{N}_]*';

    /** The placeholders every contract fills by itself, for the help panel. */
    public const AUTOMATIC = [
        'label_name' => 'Name des Labels: die Partei mit der Rolle «Label», sonst die erste Partei',
        'artist_name' => 'Name der Künstlerseite, z. B. die Band',
        'artist_members' => 'Personen der Künstlerseite, z. B. «Tobias Kalt und Lukas Oberholzer»',
        'artist_interpreten' => '«der Mitglieder von <Band>» bzw. «von <Person>»',
        'artist_share' => 'Anteil der Künstlerseite in %, aus der Vergütungszeile mit «Streaming», «Download» oder «Digital» im Namen (sonst der generelle Anteil)',
        'label_share' => 'Anteil des Labels in %, aus derselben Zeile',
        'sync_artist_share' => 'Anteil der Künstlerseite in %, aus der Vergütungszeile mit «Sync» im Namen',
        'sync_label_share' => 'Anteil des Labels in %, aus der Vergütungszeile mit «Sync» im Namen',
        'webshop_url' => 'Erste Website der Label-Organisation',
        'start_date' => 'Vertragsbeginn',
        'end_date' => 'Vertragsende',
        'contract_number' => 'Vertragsnummer',
        'teilt_teilen' => '«teilt» bzw. «teilen»',
        'meldet_melden' => '«meldet» bzw. «melden»',
        'je_ihre' => '«je ihre» bzw. «ihre» oder «seine» (nach dem Geschlecht im Kontakt)',
    ];

    protected ?array $values = null;

    protected ?array $sides = null;

    public function __construct(protected Contract $contract)
    {
    }

    /**
     * The text with every placeholder replaced that has a value.
     */
    public function fill(?string $text): string
    {
        $text = (string) $text;
        if (!str_contains($text, '{') && !str_contains($text, '[[')) {
            return $text;
        }

        $plural = $this->artistIsPlural();

        $text = preg_replace_callback(
            '/\[\[\s*(mehrere|eine)\s*:(.*?)\]\]/isu',
            fn ($m) => (mb_strtolower($m[1]) === 'mehrere') === $plural ? $m[2] : '',
            $text
        ) ?? $text;

        $text = preg_replace_callback(
            '/\{([^{}|\n]+)\|([^{}|\n]+)\}/u',
            fn ($m) => trim($plural ? $m[2] : $m[1]),
            $text
        ) ?? $text;

        $values = $this->values();

        return preg_replace_callback(
            '/\{(' . self::NAME . ')\}/u',
            function ($m) use ($values) {
                $value = $values[mb_strtolower($m[1])] ?? null;

                return $value !== null && $value !== '' ? $value : $m[0];
            },
            $text
        ) ?? $text;
    }

    /**
     * Placeholders the contract's texts use but cannot fill, with the reason.
     *
     * @return array<string, string>
     */
    public function problems(): array
    {
        $values = $this->values();
        $problems = [];

        foreach ($this->contract->placeholderTexts() as $text) {
            preg_match_all('/\{(' . self::NAME . ')\}/u', $this->fill($text), $matches);
            foreach ($matches[1] as $name) {
                $key = mb_strtolower($name);
                $problems[$key] = array_key_exists($key, $values)
                    ? 'hat in diesem Vertrag keinen Wert'
                    : 'unbekannt, als Vertragswert erfassen';
            }
        }

        return $problems;
    }

    /**
     * A filled text as HTML for the admin view, with every placeholder that
     * is still left wrapped in a <mark> so the gap stands out.
     */
    public static function markMissing(?string $text, string $class): string
    {
        $html = e((string) $text);

        return preg_replace('/\{' . self::NAME . '\}/u', '<mark class="' . e($class) . '">$0</mark>', $html) ?? $html;
    }

    /**
     * Every placeholder this contract knows, by name. null means known, but
     * without a value here (no start date, no sync row …).
     *
     * @return array<string, ?string>
     */
    public function values(): array
    {
        if ($this->values !== null) {
            return $this->values;
        }

        $values = $this->automaticValues();

        foreach (self::rows($this->contract->placeholder_values) as $row) {
            if ($row['value'] !== '') {
                $values[$row['key']] = $row['value'];
            } elseif (!array_key_exists($row['key'], $values)) {
                $values[$row['key']] = null;
            }
        }

        return $this->values = $values;
    }

    /**
     * True when several persons sign for the artist's side.
     */
    public function artistIsPlural(): bool
    {
        return count($this->memberNames($this->sides()['artist'])) > 1;
    }

    /**
     * The label's side and the artist's side of the deal.
     *
     * Built on the preamble's grouping by role. The label is the group whose
     * role says «Label», otherwise the first one; the artist is the next group.
     *
     * @return array{label: ContractParty[], artist: ContractParty[]}
     */
    public function sides(): array
    {
        if ($this->sides !== null) {
            return $this->sides;
        }

        $groups = [];
        foreach ($this->contract->partyRoleGroups() as $group) {
            $lastIndex = array_key_last($groups);
            $last = $lastIndex === null ? null : $groups[$lastIndex];

            // Band members without any role still form one side.
            $sharedOrg = $last ? $this->sharedOrganizationId($last['parties']) : null;
            if ($last && $last['role'] === null && $group['role'] === null
                && $sharedOrg !== null && $sharedOrg === $this->sharedOrganizationId($group['parties'])) {
                $groups[$lastIndex]['parties'] = array_merge($last['parties'], $group['parties']);
                continue;
            }

            $groups[] = $group;
        }

        $labelIndex = 0;
        foreach ($groups as $i => $group) {
            if ($group['role'] !== null && preg_match('/label/iu', $group['role'])) {
                $labelIndex = $i;
                break;
            }
        }

        $artistIndex = null;
        foreach (array_keys($groups) as $i) {
            if ($i !== $labelIndex) {
                $artistIndex = $i;
                break;
            }
        }

        return $this->sides = [
            'label' => $groups[$labelIndex]['parties'] ?? [],
            'artist' => $artistIndex !== null ? $groups[$artistIndex]['parties'] : [],
        ];
    }

    /**
     * Clean a submitted or stored list of Vertragswerte: names lower-cased and
     * without braces, rows without a valid name dropped, a later duplicate wins.
     *
     * @return array<int, array{key: string, value: string}>
     */
    public static function rows($rows): array
    {
        $out = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            $key = self::normalizeKey((string) ($row['key'] ?? ''));
            if (!self::isValidKey($key)) {
                continue;
            }
            $out[$key] = ['key' => $key, 'value' => trim((string) ($row['value'] ?? ''))];
        }

        return array_values($out);
    }

    /**
     * The list as it is stored: null when empty.
     */
    public static function storedRows($rows): ?array
    {
        $rows = self::rows($rows);

        return $rows !== [] ? $rows : null;
    }

    /**
     * "{Notice Period}" → "notice_period".
     */
    public static function normalizeKey(string $key): string
    {
        $key = trim($key, " \t\n\r\0\x0B{}");
        $key = preg_replace('/[\s-]+/u', '_', $key) ?? $key;

        return mb_strtolower($key);
    }

    public static function isValidKey(string $key): bool
    {
        return (bool) preg_match('/^' . self::NAME . '$/u', self::normalizeKey($key));
    }

    /**
     * Validation for the Vertragswerte editor, posted as $field[i][key|value].
     */
    public static function validationRules(string $field): array
    {
        return [
            $field => 'nullable|array',
            $field . '.*.key' => [
                'nullable', 'string', 'max:60', 'required_with:' . $field . '.*.value',
                function ($attribute, $value, $fail) {
                    if (trim((string) $value) !== '' && !self::isValidKey((string) $value)) {
                        $fail('Der Name «' . $value . '» geht nicht als Platzhalter: erlaubt sind Buchstaben, Ziffern und «_», am Anfang ein Buchstabe.');
                    }
                },
            ],
            $field . '.*.value' => 'nullable|string|max:2000',
        ];
    }

    public static function validationMessages(string $field): array
    {
        return [
            $field . '.*.key.required_with' => 'Jeder Vertragswert braucht einen Namen.',
        ];
    }

    protected function automaticValues(): array
    {
        $contract = $this->contract;
        ['label' => $label, 'artist' => $artist] = $this->sides();

        $members = $this->memberNames($artist);
        $plural = count($members) > 1;
        $band = $this->sharedOrganizationId($artist) !== null ? $artist[0]->organization?->primary_name : null;

        $main = $this->findRight('/stream|download|digital/iu');
        $sync = $this->findRight('/sync/iu');

        return [
            'label_name' => $this->sideName($label),
            'artist_name' => $this->sideName($artist),
            'artist_members' => $members !== [] ? $this->joinList($members) : null,
            'artist_interpreten' => $members === [] ? null
                : ($plural && $band ? 'der Mitglieder von ' . $band : 'von ' . $this->joinList($members)),
            'artist_share' => $this->share($main, $artist, true),
            'label_share' => $this->share($main, $label, true),
            'sync_artist_share' => $sync ? $this->share($sync, $artist) : null,
            'sync_label_share' => $sync ? $this->share($sync, $label) : null,
            'webshop_url' => $this->website($label),
            'start_date' => $contract->start_date?->format('d.m.Y'),
            'end_date' => $contract->end_date?->format('d.m.Y'),
            'contract_number' => $contract->contract_number ?: null,
            'teilt_teilen' => $plural ? 'teilen' : 'teilt',
            'meldet_melden' => $plural ? 'melden' : 'meldet',
            'je_ihre' => $plural ? 'je ihre' : $this->possessive($artist),
        ];
    }

    /**
     * The name one side goes by: the band when all of it belongs to one
     * organization, otherwise its parties listed.
     */
    protected function sideName(array $parties): ?string
    {
        if ($parties === []) {
            return null;
        }
        if ($this->sharedOrganizationId($parties) !== null) {
            return $parties[0]->organization?->primary_name ?: null;
        }

        $names = array_values(array_unique(array_filter(array_map(
            fn (ContractParty $p) => $p->organization?->primary_name ?: $p->contact?->full_name,
            $parties
        ))));

        return $names !== [] ? $this->joinList($names) : null;
    }

    /**
     * The persons who sign for one side: the contact of each party, or the
     * organization where there is none.
     *
     * @return string[]
     */
    protected function memberNames(array $parties): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn (ContractParty $p) => $p->contact?->full_name ?: $p->organization?->primary_name,
            $parties
        ))));
    }

    /**
     * "ihre" or "seine" for a single person; an organization is "ihre".
     */
    protected function possessive(array $parties): ?string
    {
        $party = $parties[0] ?? null;
        if (!$party) {
            return null;
        }
        if (!$party->contact) {
            return 'ihre';
        }

        return match ($party->contact->gender) {
            'female' => 'ihre',
            'male' => 'seine',
            default => 'ihre bzw. seine',
        };
    }

    /**
     * One side's share of a remuneration row. Column i of the split belongs
     * to party i, so a band of two adds up both members' columns.
     */
    protected function share(?array $right, array $side, bool $fallbackToPartyShare = false): ?string
    {
        if ($side === []) {
            return null;
        }

        if ($right) {
            $all = $this->contract->parties->values()->all();
            $splits = $this->contract->rightSplitValues($right);
            $sum = null;
            foreach ($side as $party) {
                $index = array_search($party, $all, true);
                $value = $index === false ? null : ($splits[$index] ?? null);
                if ($value !== null && $value !== '') {
                    $sum = ($sum ?? 0) + (float) $value;
                }
            }

            return $sum === null ? null : rtrim(Contract::formatPercent($sum), '%');
        }

        if (!$fallbackToPartyShare) {
            return null;
        }

        return rtrim(Contract::formatPercent(array_sum(array_map(fn (ContractParty $p) => (float) $p->share, $side))), '%');
    }

    /**
     * The first split row of the remuneration whose name matches.
     */
    protected function findRight(string $pattern): ?array
    {
        foreach ((array) ($this->contract->rights ?? []) as $right) {
            if (($right['mode'] ?? 'split') === 'split' && preg_match($pattern, (string) ($right['label'] ?? ''))) {
                return $right;
            }
        }

        return null;
    }

    /**
     * "theyellinglight.ch" from the first website of the side's organization.
     */
    protected function website(array $side): ?string
    {
        foreach ($side as $party) {
            foreach ((array) ($party->organization?->websites ?? []) as $site) {
                $site = trim((string) $site);
                if ($site !== '') {
                    return rtrim(preg_replace('#^https?://#i', '', $site), '/');
                }
            }
        }

        return null;
    }

    protected function sharedOrganizationId(array $parties): ?int
    {
        $ids = array_unique(array_map(fn (ContractParty $p) => $p->organization_id, $parties));

        return count($ids) === 1 && $ids[0] !== null ? (int) $ids[0] : null;
    }

    /**
     * "A", "A und B", "A, B und C" — in the contract's language.
     */
    protected function joinList(array $items): string
    {
        $items = array_values($items);
        if (count($items) <= 1) {
            return $items[0] ?? '';
        }

        $and = Contract::pdfStrings($this->contract->language)['list_conjunction'];

        return implode(', ', array_slice($items, 0, -1)) . ' ' . $and . ' ' . end($items);
    }
}
