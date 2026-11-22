<?php

namespace Tests\Feature\Smoke;

use App\Models\Account;
use App\Models\Accounting;
use App\Models\BankImport;
use App\Models\BankTransaction;
use App\Models\Booking;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\RoyaltyLedgerEntry;
use App\Models\RoyaltyPayout;
use App\Models\RoyaltyStatement;
use App\Models\User;
use App\Services\Royalties\RoyaltyBalances;
use App\Services\Royalties\RoyaltyPayouts;
use App\Services\Royalties\RoyaltyStatementBooker;
use App\Services\Royalties\RoyaltyStatementException;
use App\Support\Iban;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Paying out balances: bookings payables / bank, the balances go down, the
 * pain.001 file for the e-banking holds the transfers with a valid IBAN.
 * IBANs from the examples of the Swiss payment standards.
 */
class RoyaltyPayoutsTest extends TestCase
{
    use RefreshDatabase;

    private const DEBTOR_IBAN = 'CH9300762011623852957';
    private const BAND_IBAN = 'CH56 0483 5012 3456 7800 9';

    private User $user;
    private Accounting $accounting;
    private Account $bank;
    private Account $payable;
    private Organization $band;
    private Contact $person;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $own = Organization::create(['type' => 'label', 'names' => ['The yelling light']]);
        $this->accounting = Accounting::create(['accountable_type' => Organization::class, 'accountable_id' => $own->id,
            'name' => 'TYL 2026', 'currency' => 'CHF', 'period_start' => '2026-01-01', 'period_end' => '2026-12-31']);
        $this->bank = $this->accounting->accounts()->create(['number' => '1003', 'name' => 'Bank', 'type' => 'asset', 'iban' => self::DEBTOR_IBAN]);
        $this->payable = $this->accounting->accounts()->create(['number' => '2001', 'name' => 'Kreditoren', 'type' => 'liability']);

        $this->band = Organization::create(['type' => 'band', 'names' => ['Tar Pond'], 'iban' => self::BAND_IBAN,
            'street' => 'Musterweg 5', 'zip' => '8400', 'city' => 'Winterthur', 'country' => 'Schweiz']);
        $this->person = Contact::create(['first_name' => 'Mia', 'last_name' => 'Muster']);

        foreach ([[$this->band, 150], [$this->person, 40]] as [$who, $amount]) {
            RoyaltyLedgerEntry::create([
                'organization_id' => $who instanceof Organization ? $who->id : null,
                'contact_id' => $who instanceof Contact ? $who->id : null,
                'entry_date' => '2026-12-15', 'type' => 'credit', 'amount' => $amount, 'description' => 'Abrechnung 2023–2025',
            ]);
        }
    }

    private function pay(array $amounts): RoyaltyPayout
    {
        return app(RoyaltyPayouts::class)->create($this->accounting, $amounts, Carbon::parse('2026-12-17'), $this->bank, $this->payable, null);
    }

    public function test_a_payout_books_lowers_the_balances_and_writes_the_payment_file(): void
    {
        $payout = $this->pay(["org-{$this->band->id}" => 150, "contact-{$this->person->id}" => 40]);

        $this->assertSame(190.0, (float) $payout->total);
        $this->assertSame([[150.0, '2001', '1003'], [40.0, '2001', '1003']],
            Booking::with(['debitAccount', 'creditAccount'])->orderBy('id')->get()->map(fn ($b) => [(float) $b->amount, $b->debitAccount->number, $b->creditAccount->number])->all());
        $this->assertSame([0.0, 0.0], [app(RoyaltyBalances::class)->of($this->band)->balance, app(RoyaltyBalances::class)->of($this->person)->balance]);

        $xml = app(RoyaltyPayouts::class)->pain001($payout);
        $doc = simplexml_load_string($xml);
        $this->assertNotFalse($doc, 'Well-formed XML');
        $doc->registerXPathNamespace('p', 'urn:iso:std:iso:20022:tech:xsd:pain.001.001.09');
        $value = fn (string $path) => (string) ($doc->xpath($path)[0] ?? '');

        $this->assertSame('1', $value('//p:GrpHdr/p:NbOfTxs'), 'The person without IBAN is not in the file');
        $this->assertSame('150.00', $value('//p:GrpHdr/p:CtrlSum'));
        $this->assertSame('false', $value('//p:PmtInf/p:BtchBookg'), 'One debit per transfer, so the bank import can link each');
        $this->assertSame('2026-12-17', $value('//p:ReqdExctnDt/p:Dt'));
        $this->assertSame(self::DEBTOR_IBAN, $value('//p:DbtrAcct/p:Id/p:IBAN'));
        $this->assertSame('00762', $value('//p:DbtrAgt/p:FinInstnId/p:ClrSysMmbId/p:MmbId'));
        $this->assertSame('CH5604835012345678009', $value('//p:CdtTrfTxInf/p:CdtrAcct/p:Id/p:IBAN'));
        $this->assertSame('Tar Pond', $value('//p:CdtTrfTxInf/p:Cdtr/p:Nm'));
        $this->assertSame(['Musterweg 5', '8400', 'Winterthur', 'CH'], [$value('//p:Cdtr/p:PstlAdr/p:StrtNm'), $value('//p:Cdtr/p:PstlAdr/p:PstCd'),
            $value('//p:Cdtr/p:PstlAdr/p:TwnNm'), $value('//p:Cdtr/p:PstlAdr/p:Ctry')]);
        $this->assertSame('Abrechnung The yelling light', $value('//p:RmtInf/p:Ustrd'));
    }

    public function test_more_than_the_balance_is_refused(): void
    {
        try {
            $this->pay(["org-{$this->band->id}" => 150.5]);
            $this->fail('More than the balance');
        } catch (RoyaltyStatementException $e) {
            $this->assertStringContainsString('mehr als der Saldo', $e->getMessage());
        }
        $this->assertSame(0, Booking::count());
    }

    public function test_a_payout_is_cancelled_until_the_bank_statement_matched_it(): void
    {
        $payout = $this->pay(["org-{$this->band->id}" => 100]);
        app(RoyaltyPayouts::class)->cancel($payout);
        $this->assertSame([0, 150.0], [Booking::count(), app(RoyaltyBalances::class)->of($this->band)->balance]);

        // Matched with the bank statement: stays
        $payout = $this->pay(["org-{$this->band->id}" => 100]);
        $import = BankImport::create(['accounting_id' => $this->accounting->id, 'account_id' => $this->bank->id, 'format' => 'raiffeisen', 'file_name' => 'k.csv']);
        $transaction = BankTransaction::create(['accounting_id' => $this->accounting->id, 'account_id' => $this->bank->id, 'bank_import_id' => $import->id,
            'booking_date' => '2026-12-17', 'amount' => -100, 'text' => 'Zahlung Tar Pond', 'fingerprint' => 'x', 'status' => 'booked']);
        Booking::query()->update(['bank_transaction_id' => $transaction->id]);

        $this->expectException(RoyaltyStatementException::class);
        app(RoyaltyPayouts::class)->cancel($payout);
    }

    public function test_a_statement_paid_out_from_cannot_be_undone(): void
    {
        $statement = RoyaltyStatement::create(['title' => 'A', 'statement_date' => '2026-12-15', 'period_from' => '2023-01-01', 'period_to' => '2025-12-31',
            'accounting_id' => $this->accounting->id, 'status' => 'final', 'finalized_at' => now()->subMinute()]);
        RoyaltyLedgerEntry::query()->update(['royalty_statement_id' => $statement->id]);
        $this->pay(["org-{$this->band->id}" => 100]);

        $this->expectExceptionMessage('Zuerst die Auszahlungen stornieren');
        app(RoyaltyStatementBooker::class)->undo($statement);
    }

    public function test_the_pages_suggest_book_and_download(): void
    {
        $this->actingAs($this->user)->get('/admin/royalties/payouts')->assertOk()
            ->assertSee('Saldi (2)')->assertSee('Tar Pond')->assertSee(Iban::format(self::BAND_IBAN))->assertSee('fehlt');

        $html = $this->actingAs($this->user)->get("/admin/royalties/payouts/create?accounting={$this->accounting->id}")->assertOk()
            ->assertSee('Mia Muster')->assertSee('keine IBAN')->getContent();
        $json = str_replace(chr(92) . 'u0022', '"', $html); // @js() writes the quotes escaped
        $this->assertStringContainsString('"org-' . $this->band->id . '":{"pay":true', $json, 'From CHF 100 with an IBAN: suggested');
        $this->assertStringContainsString('"contact-' . $this->person->id . '":{"pay":false', $json, 'Below CHF 100: not suggested');

        $this->actingAs($this->user)->post('/admin/royalties/payouts', [
            'accounting_id' => $this->accounting->id, 'execution_date' => '2026-12-17', 'bank_account_id' => $this->bank->id,
            'payable_account_id' => $this->payable->id, 'pay' => ["org-{$this->band->id}" => '1'], 'amount' => ["org-{$this->band->id}" => '150', "contact-{$this->person->id}" => '40'],
        ])->assertRedirect()->assertSessionHas('success');
        $payout = RoyaltyPayout::sole();
        $this->assertSame(150.0, (float) $payout->total, 'Only the ticked one');

        $this->actingAs($this->user)->get("/admin/royalties/payouts/{$payout->id}")->assertOk()->assertSee('Zahlungsdatei (pain.001)')->assertSee('Stornieren');
        $this->actingAs($this->user)->get("/admin/royalties/payouts/{$payout->id}/pain001")->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->assertSee('pain.001.001.09', false);

        $this->actingAs($this->user)->get("/admin/organizations/{$this->band->id}")->assertOk()->assertSee('Abrechnungen')->assertSee('CHF 0.00');
        $this->actingAs($this->user)->get("/admin/accountings/{$this->accounting->id}")->assertOk()->assertSee('Auszahlungen Künstler:innen');

        $this->actingAs($this->user)->delete("/admin/royalties/payouts/{$payout->id}")->assertRedirect('/admin/royalties/payouts');
        $this->assertSame(0, RoyaltyPayout::count());
    }

    public function test_ibans_are_checked(): void
    {
        $this->assertTrue(Iban::isValid(self::BAND_IBAN));
        $this->assertFalse(Iban::isValid('CH5604835012345678008'));
        $this->assertTrue(Iban::isQrIban('CH4431999123000889012'));
        $this->assertSame('CH', RoyaltyPayouts::countryCode('Schweiz'));
        $this->assertSame('DE', RoyaltyPayouts::countryCode(null, 'DE89370400440532013000'));
    }
}
