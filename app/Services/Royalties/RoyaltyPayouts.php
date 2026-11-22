<?php

namespace App\Services\Royalties;

use App\Models\Account;
use App\Models\Accounting;
use App\Models\Booking;
use App\Models\RoyaltyLedgerEntry;
use App\Models\RoyaltyPayout;
use App\Models\RoyaltyPayoutItem;
use App\Models\RoyaltyStatement;
use App\Support\Iban;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Pays out balances: one booking per recipient (payables / bank) on the
 * execution date and a ledger entry lowering the balance, plus a pain.001
 * file for the e-banking (Swiss Payment Standards, pain.001.001.09).
 *
 * The file asks the bank for one debit per transfer (no batch booking), so
 * the bank import links each debit to its booking instead of booking twice.
 */
class RoyaltyPayouts
{
    private const COUNTRIES = [
        'schweiz' => 'CH', 'switzerland' => 'CH', 'suisse' => 'CH', 'svizzera' => 'CH',
        'deutschland' => 'DE', 'germany' => 'DE', 'österreich' => 'AT', 'oesterreich' => 'AT', 'austria' => 'AT',
        'liechtenstein' => 'LI', 'frankreich' => 'FR', 'france' => 'FR', 'italien' => 'IT', 'italy' => 'IT', 'italia' => 'IT',
        'niederlande' => 'NL', 'netherlands' => 'NL', 'belgien' => 'BE', 'belgium' => 'BE', 'spanien' => 'ES', 'spain' => 'ES',
        'grossbritannien' => 'GB', 'united kingdom' => 'GB', 'england' => 'GB', 'usa' => 'US', 'united states' => 'US',
    ];

    public function __construct(private RoyaltyBalances $balances)
    {
    }

    /**
     * @param array<string, float> $amounts party key => amount
     */
    public function create(Accounting $accounting, array $amounts, \DateTimeInterface $date, Account $bank, Account $payable, ?string $message): RoyaltyPayout
    {
        if ($accounting->is_closed) {
            throw new RoyaltyStatementException("Die Buchhaltung «{$accounting->name}» ist abgeschlossen.");
        }
        $date = Carbon::instance($date)->startOfDay();
        if (!$date->betweenIncluded($accounting->period_start, $accounting->period_end)) {
            throw new RoyaltyStatementException("Das Ausführungsdatum liegt nicht in der Periode von «{$accounting->name}».");
        }
        foreach ([$bank, $payable] as $account) {
            if ((int) $account->accounting_id !== (int) $accounting->id || $account->is_header) {
                throw new RoyaltyStatementException('Bitte Konten dieser Buchhaltung wählen.');
            }
        }

        $recipients = [];
        foreach ($amounts as $key => $amount) {
            $amount = round((float) $amount, 2);
            if ($amount <= 0) continue;
            $who = RoyaltyBalances::find($key) ?? throw new RoyaltyStatementException('Unbekannte Partei.');
            $party = $this->balances->of($who);
            if ($amount > $party->balance + 0.005) {
                throw new RoyaltyStatementException("{$party->name}: CHF " . number_format($amount, 2, '.', "'") . ' ist mehr als der Saldo von CHF ' . number_format($party->balance, 2, '.', "'") . '.');
            }
            $recipients[] = [$party, $amount];
        }
        if (!$recipients) {
            throw new RoyaltyStatementException('Bitte mindestens eine Auszahlung mit Betrag wählen.');
        }

        $message = trim((string) $message) ?: $this->defaultMessage();

        return DB::transaction(function () use ($accounting, $recipients, $date, $bank, $payable, $message) {
            $payout = RoyaltyPayout::create([
                'accounting_id' => $accounting->id,
                'execution_date' => $date->toDateString(),
                'bank_account_id' => $bank->id,
                'payable_account_id' => $payable->id,
                'debtor_name' => $accounting->accountable_name ?: 'The yelling light',
                'debtor_iban' => Iban::normalize($bank->iban),
                'message' => mb_substr($message, 0, 140),
                'total' => round(array_sum(array_column($recipients, 1)), 2),
            ]);

            foreach ($recipients as [$party, $amount]) {
                $booking = Booking::create([
                    'accounting_id' => $accounting->id,
                    'booking_date' => $date->toDateString(),
                    'description' => mb_substr("Auszahlung Abrechnung an {$party->name}", 0, 250),
                    'debit_account_id' => $payable->id,
                    'credit_account_id' => $bank->id,
                    'amount' => $amount,
                    'organization_id' => $party->organization?->id,
                    'contact_id' => $party->organization ? null : $party->contact?->id,
                ]);
                $payout->items()->create([
                    'organization_id' => $party->organization?->id,
                    'contact_id' => $party->organization ? null : $party->contact?->id,
                    'name' => mb_substr($party->name, 0, 255),
                    'iban' => $party->iban,
                    'street' => $party->street,
                    'zip' => $party->zip,
                    'city' => $party->city,
                    'country' => $party->country,
                    'amount' => $amount,
                    'booking_id' => $booking->id,
                ]);
                RoyaltyLedgerEntry::create([
                    'organization_id' => $party->organization?->id,
                    'contact_id' => $party->organization ? null : $party->contact?->id,
                    'entry_date' => $date->toDateString(),
                    'type' => RoyaltyLedgerEntry::PAYOUT,
                    'amount' => -$amount,
                    'description' => 'Auszahlung',
                    'royalty_payout_id' => $payout->id,
                    'booking_id' => $booking->id,
                ]);
            }

            return $payout;
        });
    }

    /** Takes a run back - as long as none of its debits is matched with the bank statement */
    public function cancel(RoyaltyPayout $payout): void
    {
        $bookings = Booking::whereIn('id', $payout->items()->pluck('booking_id')->filter())->get();
        if ($bookings->contains(fn (Booking $b) => $b->bank_transaction_id)) {
            throw new RoyaltyStatementException('Diese Auszahlung ist schon mit dem Bankauszug abgeglichen. Zuerst dort die Bewegungen wieder öffnen.');
        }
        if ($payout->accounting?->is_closed) {
            throw new RoyaltyStatementException("Die Buchhaltung «{$payout->accounting->name}» ist abgeschlossen.");
        }

        DB::transaction(function () use ($payout, $bookings) {
            $payout->ledgerEntries()->delete();
            $bookings->each->delete();
            $payout->delete();
        });
    }

    /** The order for the e-banking: transfers with an IBAN that checks out */
    public function pain001(RoyaltyPayout $payout): string
    {
        $items = $payout->items()->get()->filter->is_transferable->values();
        $sum = number_format((float) $items->sum('amount'), 2, '.', '');
        $id = 'TYL-' . $payout->id . '-' . $payout->created_at->format('YmdHis');

        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('Document');
        $xml->writeAttribute('xmlns', 'urn:iso:std:iso:20022:tech:xsd:pain.001.001.09');
        $xml->startElement('CstmrCdtTrfInitn');

        $xml->startElement('GrpHdr');
        $xml->writeElement('MsgId', $id);
        $xml->writeElement('CreDtTm', $payout->created_at->format('Y-m-d\TH:i:s'));
        $xml->writeElement('NbOfTxs', (string) $items->count());
        $xml->writeElement('CtrlSum', $sum);
        $xml->startElement('InitgPty');
        $xml->writeElement('Nm', self::text($payout->debtor_name, 70));
        $xml->endElement();
        $xml->endElement();

        $xml->startElement('PmtInf');
        $xml->writeElement('PmtInfId', $id . '-1');
        $xml->writeElement('PmtMtd', 'TRF');
        $xml->writeElement('BtchBookg', 'false');
        $xml->writeElement('NbOfTxs', (string) $items->count());
        $xml->writeElement('CtrlSum', $sum);
        $xml->startElement('ReqdExctnDt');
        $xml->writeElement('Dt', $payout->execution_date->format('Y-m-d'));
        $xml->endElement();
        $xml->startElement('Dbtr');
        $xml->writeElement('Nm', self::text($payout->debtor_name, 70));
        $xml->endElement();
        $xml->startElement('DbtrAcct');
        $xml->startElement('Id');
        $xml->writeElement('IBAN', (string) Iban::normalize($payout->debtor_iban));
        $xml->endElement();
        $xml->endElement();
        $xml->startElement('DbtrAgt');
        $xml->startElement('FinInstnId');
        if ($iid = Iban::iid($payout->debtor_iban)) {
            $xml->startElement('ClrSysMmbId');
            $xml->startElement('ClrSysId');
            $xml->writeElement('Cd', 'CHBCC');
            $xml->endElement();
            $xml->writeElement('MmbId', $iid);
            $xml->endElement();
        }
        $xml->endElement();
        $xml->endElement();

        foreach ($items as $i => $item) {
            $xml->startElement('CdtTrfTxInf');
            $xml->startElement('PmtId');
            $xml->writeElement('InstrId', $id . '-' . ($i + 1));
            $xml->writeElement('EndToEndId', $id . '-' . ($i + 1));
            $xml->endElement();
            $xml->startElement('Amt');
            $xml->startElement('InstdAmt');
            $xml->writeAttribute('Ccy', 'CHF');
            $xml->text(number_format((float) $item->amount, 2, '.', ''));
            $xml->endElement();
            $xml->endElement();
            $xml->startElement('Cdtr');
            $xml->writeElement('Nm', self::text($item->name, 70));
            $xml->startElement('PstlAdr');
            if ($item->street) $xml->writeElement('StrtNm', self::text($item->street, 70));
            if ($item->zip) $xml->writeElement('PstCd', self::text($item->zip, 16));
            $xml->writeElement('TwnNm', self::text($item->city ?: '-', 35));
            $xml->writeElement('Ctry', $item->country ?: (substr((string) Iban::normalize($item->iban), 0, 2) ?: 'CH'));
            $xml->endElement();
            $xml->endElement();
            $xml->startElement('CdtrAcct');
            $xml->startElement('Id');
            $xml->writeElement('IBAN', (string) Iban::normalize($item->iban));
            $xml->endElement();
            $xml->endElement();
            $xml->startElement('RmtInf');
            $xml->writeElement('Ustrd', self::text($payout->message ?: 'Abrechnung', 140));
            $xml->endElement();
            $xml->endElement();
        }

        $xml->endElement(); // PmtInf
        $xml->endElement(); // CstmrCdtTrfInitn
        $xml->endElement(); // Document
        $xml->endDocument();

        return $xml->outputMemory();
    }

    /** Two letters for a country as typed into a contact ("Schweiz", "CH"), else the IBAN's */
    public static function countryCode(?string $country, ?string $iban = null): ?string
    {
        $country = trim((string) $country);
        if (preg_match('/^[A-Za-z]{2}$/', $country)) return strtoupper($country);
        if ($country !== '' && isset(self::COUNTRIES[mb_strtolower($country)])) return self::COUNTRIES[mb_strtolower($country)];
        $fromIban = substr((string) Iban::normalize($iban), 0, 2);

        return preg_match('/^[A-Z]{2}$/', $fromIban) ? $fromIban : ($country === '' ? 'CH' : null);
    }

    private function defaultMessage(): string
    {
        $latest = RoyaltyStatement::where('status', RoyaltyStatement::FINAL)->latest('statement_date')->first();

        return $latest ? "Abrechnung {$latest->title}" : 'Abrechnung The yelling light';
    }

    /** Characters the Swiss payment standard takes, cut to length */
    private static function text(string $value, int $max): string
    {
        $value = preg_replace('/[^\p{L}\p{N} .,:\'+\-\/()?&@*!"#%;<=>_{}\[\]\\\\]/u', ' ', $value);

        return mb_substr(trim(preg_replace('/\s+/', ' ', $value)), 0, $max);
    }
}
