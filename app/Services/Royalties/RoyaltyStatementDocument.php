<?php

namespace App\Services\Royalties;

use App\Models\Contract;
use App\Models\RoyaltyLedgerEntry;
use App\Models\RoyaltyStatement;
use App\Models\RoyaltyStatementItem as Item;
use App\Services\Reports\AnnualReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * The statement a party receives as PDF: its share per contract and track,
 * the advances recouped, the credit and its balance before and after.
 * A draft can be printed too (marked as such), to check it before it counts.
 */
class RoyaltyStatementDocument
{
    public function __construct(
        private RoyaltyStatementCalculator $calculator,
        private AnnualReportExport $letterhead,
    ) {
    }

    /**
     * Items and parties of the statement: kept ones of a final statement,
     * computed ones of a draft. Only the credited parties get a document.
     *
     * @return array{result: array, parties: Collection}
     */
    public function prepare(RoyaltyStatement $statement): array
    {
        if ($statement->is_final) {
            $result = ['items' => $statement->items()->get(), 'pot' => (float) $statement->payments_amount,
                'usage' => (float) $statement->usage_amount, 'factor' => (float) $statement->factor];
        } else {
            $result = $this->calculator->calculate($statement);
        }
        $result['items']->loadMissing(['track', 'release', 'contract.parties']);

        $parties = RoyaltyStatementBooker::parties($result['items'])
            ->whereIn('role', [Item::ARTIST, Item::LABEL])
            ->each(fn ($p) => $p->key = $p->organization ? 'org-' . $p->organization->id : 'contact-' . $p->contact?->id)
            ->values();

        return ['result' => $result, 'parties' => $parties];
    }

    public function pdf(RoyaltyStatement $statement, object $party, array $result, ?array $data = null): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView('admin.royalties.pdf', $data ?? $this->data($statement, $party, $result))->setPaper('A4', 'portrait');
    }

    /** All documents of a statement in one ZIP; returns the path of a temporary file */
    public function zip(RoyaltyStatement $statement): string
    {
        ['result' => $result, 'parties' => $parties] = $this->prepare($statement);
        $path = tempnam(sys_get_temp_dir(), 'abrechnung') . '.zip';
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        foreach ($parties as $party) {
            $zip->addFromString($this->fileName($statement, $party), $this->pdf($statement, $party, $result)->output());
        }
        $zip->close();

        return $path;
    }

    public function fileName(RoyaltyStatement $statement, object $party): string
    {
        $name = Str::slug(Str::ascii($party->name, 'de')) ?: 'partei';

        return 'Abrechnung_' . $statement->period_from->format('Y') . '-' . $statement->period_to->format('Y') . '_' . $name
            . ($statement->is_final ? '' : '_ENTWURF') . '.pdf';
    }

    /** What the PDF shows (public for the tests that read it as HTML) */
    public function data(RoyaltyStatement $statement, object $party, array $result): array
    {
        $accounting = $statement->accounting;
        $logos = $accounting ? $this->letterhead->logos($accounting) : [];
        $options = $accounting ? $this->letterhead->options($accounting) : [];
        $logo = $logos[$options['logo'] ?? ''] ?? reset($logos) ?: null;

        // Per contract (or "no contract" for a label without one), tracks by share
        $contracts = $party->items->groupBy(fn (Item $i) => (string) $i->contract_id)->map(function (Collection $items) use ($statement, $result) {
            $contract = $items->first()->contract;

            return (object) [
                'contract' => $contract,
                'percent' => $items->first()->percent,
                'items' => $items->sortByDesc('amount')->values(),
                'share' => $items->sum('amount'),
                'recouped' => $items->sum('recouped'),
                'open_after' => $contract ? $this->openAfter($statement, $contract, $result['items']) : 0.0,
            ];
        })->values();

        $balanceBefore = (float) $this->ledger($party)
            ->where(fn ($q) => $q->whereNull('royalty_statement_id')->orWhere('royalty_statement_id', '!=', $statement->id))
            ->whereDate('entry_date', '<=', $statement->statement_date)->sum('amount');

        return [
            'statement' => $statement,
            'party' => $party,
            'contracts' => $contracts,
            'result' => $result,
            'balanceBefore' => $balanceBefore,
            'balanceAfter' => round($balanceBefore + $party->credit, 2),
            'senderLines' => $accounting ? $this->letterhead->addressLines($accounting) : [],
            'recipientLines' => $this->recipientLines($party),
            'logoFile' => $this->letterhead->printLogo($logo['file'] ?? null),
            'minimum' => (float) config('royalties.payout_minimum', 100),
            'generatedAt' => now('Europe/Zurich'),
        ];
    }

    /** Advances of the contract still open once this statement counts */
    private function openAfter(RoyaltyStatement $statement, Contract $contract, Collection $items): float
    {
        $open = RoyaltyStatementCalculator::openAdvances($contract, $statement->statement_date);
        if ($statement->is_final) {
            return $open;
        }

        return max(0, round($open - $items->where('contract_id', $contract->id)->sum('recouped'), 2));
    }

    private function ledger(object $party)
    {
        return $party->organization
            ? RoyaltyLedgerEntry::where('organization_id', $party->organization->id)
            : RoyaltyLedgerEntry::whereNull('organization_id')->where('contact_id', $party->contact?->id);
    }

    /** @return list<string> */
    private function recipientLines(object $party): array
    {
        $who = $party->organization ?? $party->contact;
        $country = trim((string) ($who?->country ?? ''));

        return array_values(array_filter([
            $party->name,
            trim((string) ($who?->street ?? '')),
            trim(($who?->zip ?? '') . ' ' . ($who?->city ?? '')),
            in_array(mb_strtolower($country), ['', 'ch', 'schweiz', 'switzerland', 'suisse', 'svizzera'], true) ? '' : $country,
        ], fn ($line) => $line !== ''));
    }
}
