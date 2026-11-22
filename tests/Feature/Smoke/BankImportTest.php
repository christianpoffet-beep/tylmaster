<?php

namespace Tests\Feature\Smoke;

use App\Models\Account;
use App\Models\Accounting;
use App\Models\BankTransaction;
use App\Models\Booking;
use App\Models\BookingRule;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use App\Services\Banking\BankStatementException;
use App\Services\Banking\BankStatementReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Bankabgleich: a Raiffeisen CSV goes into the inbox of an accounting and is
 * booked from there - by hand, by rule, as an invoice payment, or by linking
 * a booking that is already in the journal.
 */
class BankImportTest extends TestCase
{
    use RefreshDatabase;

    private const IBAN = 'CH9300762011623852957';

    private Accounting $accounting;
    private Account $bank;
    private Account $debtors;
    private Account $streaming;
    private Account $photo;
    private Account $hosting;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('documents');

        $owner = Organization::create(['type' => 'band', 'names' => ['Testband']]);
        $this->accounting = Accounting::create([
            'accountable_type' => Organization::class,
            'accountable_id' => $owner->id,
            'name' => 'Buchhaltung 2025',
            'currency' => 'CHF',
            'period_start' => '2025-01-01',
            'period_end' => '2025-12-31',
        ]);

        $make = fn ($number, $name, $type, $opening = 0) => $this->accounting->accounts()->create([
            'number' => $number, 'name' => $name, 'type' => $type, 'opening_balance' => $opening,
        ]);
        $this->accounting->accounts()->create(['number' => '1000', 'name' => 'Umlaufvermögen', 'type' => 'asset', 'is_header' => true]);
        $this->bank = $make('1003', 'Bank', 'asset', 1000);
        $this->debtors = $make('1100', 'Debitoren', 'asset');
        $this->photo = $make('3201', 'Bandfotografie', 'income');
        $this->streaming = $make('3302', 'Streaming', 'income');
        $this->hosting = $make('4008', 'Webhosting', 'expense');
        $make('2801', 'Eigenkapital', 'liability', 1000);
    }

    /** A Raiffeisen export as the e-banking delivers it: Windows-1252, details on a line of their own. */
    private function csv(array $movements, float $opening = 1000.0): string
    {
        $lines = ['IBAN;Booked At;Text;Credit/Debit Amount;Balance;Valuta Date'];
        $balance = $opening;
        foreach ($movements as [$date, $text, $amount, $details]) {
            $balance = round($balance + $amount, 2);
            $lines[] = self::IBAN . ";{$date} 00:00:00.0;{$text};{$amount};{$balance};{$date} 00:00:00.0";
            if ($details !== null) $lines[] = ";;{$details};;;";
        }

        return mb_convert_encoding(implode("\r\n", $lines) . "\r\n", 'Windows-1252', 'UTF-8');
    }

    private function defaultCsv(): string
    {
        return $this->csv([
            ['2025-01-31', 'Gutschrift Musikvertrieb GmbH EUR 54.46, Umrechnungskurs 0.9292', 50.6, 'Hauptstrasse 1 3430 Tulln AT Fakturen'],
            ['2025-05-28', 'Gutschrift Musikvertrieb GmbH EUR 56.25, Umrechnungskurs 0.9199', 51.74, 'Hauptstrasse 1 3430 Tulln AT Fakturen'],
            ['2025-09-16', 'Zahlung Hosting AG', -93.45, 'Josefstrasse 1 8005 Zürich CHF 93.45'],
            ['2025-12-31', 'Abschlussbetreffnis von 31.12.2024 bis 31.12.2025', 0, null],
        ]);
    }

    private function upload(string $content, ?Account $account = null)
    {
        return $this->actingAs(User::factory()->create())
            ->post("/admin/accountings/{$this->accounting->id}/bank/import", [
                'file' => UploadedFile::fake()->createWithContent('Konto.csv', $content),
                'account_id' => ($account ?? $this->bank)->id,
            ]);
    }

    private function bookRow(BankTransaction $tx, array $input)
    {
        return $this->actingAs(User::factory()->create())
            ->post("/admin/accountings/{$this->accounting->id}/bank/book", ['transaction' => $tx->id] + $input);
    }

    public function test_the_reader_folds_details_converts_umlauts_and_skips_zero_lines(): void
    {
        $statement = BankStatementReader::parse($this->defaultCsv());

        $this->assertSame(self::IBAN, $statement['iban']);
        $this->assertCount(3, $statement['rows'], 'The zero line is no movement.');
        $this->assertSame('2025-09-16', $statement['rows'][2]['booking_date']);
        $this->assertSame(-93.45, $statement['rows'][2]['amount']);
        $this->assertStringContainsString('Zürich', $statement['rows'][2]['details']);
        $this->assertSame(1000.0, $statement['opening_balance']);
        $this->assertSame(1008.89, $statement['closing_balance']);
        $this->assertSame('2025-12-31', $statement['to']);
    }

    public function test_an_unknown_file_is_refused_with_a_clear_message(): void
    {
        $this->expectException(BankStatementException::class);
        BankStatementReader::parse("Datum,Betrag\n2025-01-01,5\n");
    }

    public function test_import_fills_the_inbox_remembers_the_iban_and_skips_what_is_already_there(): void
    {
        $this->upload($this->defaultCsv())->assertRedirect()->assertSessionHas('success');

        $this->assertSame(3, $this->accounting->bankTransactions()->where('status', 'open')->count());
        $this->assertSame(self::IBAN, $this->bank->fresh()->iban);
        $this->assertSame(0, Booking::count(), 'Importing must not book anything yet.');

        // The same export again (or an overlapping one) adds nothing
        $this->upload($this->defaultCsv());
        $this->assertSame(3, $this->accounting->bankTransactions()->count());
        $this->assertSame(3, $this->accounting->bankImports()->latest('id')->first()->rows_duplicate);
    }

    public function test_a_statement_of_another_account_is_refused(): void
    {
        $this->bank->update(['iban' => 'CH5604835012345678009']);

        $this->upload($this->defaultCsv())->assertSessionHas('error');
        $this->assertSame(0, BankTransaction::count());
    }

    public function test_movements_outside_the_period_are_skipped(): void
    {
        $this->upload($this->csv([
            ['2024-12-30', 'Zahlung Hosting AG', -10, null],
            ['2025-01-05', 'Zahlung Hosting AG', -20, null],
        ]));

        $this->assertSame(1, BankTransaction::count());
        $this->assertSame(1, $this->accounting->bankImports()->first()->rows_outside);
    }

    public function test_booking_by_hand_remembers_a_rule_that_books_the_next_one(): void
    {
        $this->upload($this->defaultCsv());
        [$first, $second, $hosting] = $this->accounting->bankTransactions()->orderBy('booking_date')->get();

        $this->bookRow($first, [
            'account_id' => $this->streaming->id,
            'description' => 'Streaming-Abrechnung',
            'remember' => '1',
            'pattern' => 'Musikvertrieb',
        ])->assertSessionHas('success');

        $booking = Booking::sole();
        $this->assertSame($this->bank->id, (int) $booking->debit_account_id, 'Money in: bank is debited.');
        $this->assertSame($this->streaming->id, (int) $booking->credit_account_id);
        $this->assertSame('50.60', $booking->amount);
        $this->assertSame('2025-01-31', $booking->booking_date->toDateString());
        $this->assertSame($first->id, (int) $booking->bank_transaction_id);
        $this->assertSame('booked', $first->fresh()->status);

        $rule = BookingRule::sole();
        $this->assertSame(['Musikvertrieb', 'in', '3302'], [$rule->pattern, $rule->direction, $rule->account_number]);

        // The page now suggests the rule for the second payment; "all" books it
        $this->actingAs(User::factory()->create())
            ->get("/admin/accountings/{$this->accounting->id}/bank")
            ->assertOk()
            ->assertSee('Regel «Musikvertrieb»', false);

        $this->actingAs(User::factory()->create())
            ->post("/admin/accountings/{$this->accounting->id}/bank/book", ['all' => 1]);

        $this->assertSame('booked', $second->fresh()->status);
        $this->assertSame('Streaming-Abrechnung', $second->fresh()->bookings()->sole()->description);
        $this->assertSame('open', $hosting->fresh()->status, 'No rule for the hosting payment - it stays open.');
    }

    public function test_an_outgoing_payment_debits_the_expense(): void
    {
        $this->upload($this->defaultCsv());
        $hosting = $this->accounting->bankTransactions()->where('amount', '<', 0)->sole();

        $this->bookRow($hosting, ['account_id' => $this->hosting->id]);

        $booking = Booking::sole();
        $this->assertSame($this->hosting->id, (int) $booking->debit_account_id);
        $this->assertSame($this->bank->id, (int) $booking->credit_account_id);
        $this->assertSame('93.45', $booking->amount);
    }

    public function test_a_split_must_add_up_and_creates_one_booking_per_line(): void
    {
        $this->upload($this->defaultCsv());
        $hosting = $this->accounting->bankTransactions()->where('amount', '<', 0)->sole();

        $this->bookRow($hosting, ['split' => 1, 'splits' => [
            ['account_id' => $this->hosting->id, 'amount' => '80.00'],
            ['account_id' => $this->photo->id, 'amount' => '10.00'],
        ]])->assertSessionHas('error');
        $this->assertSame(0, Booking::count());

        $this->bookRow($hosting, ['split' => 1, 'splits' => [
            ['account_id' => $this->hosting->id, 'amount' => '80.00'],
            ['account_id' => $this->photo->id, 'amount' => '13.45'],
        ]])->assertSessionHas('success');
        $this->assertSame(2, Booking::where('bank_transaction_id', $hosting->id)->count());
    }

    public function test_an_open_invoice_is_recognised_paid_and_reopened_again(): void
    {
        $invoice = Invoice::create([
            'invoice_number' => 'RE-2025-0016', 'type' => 'outgoing', 'amount' => 38, 'currency' => 'CHF',
            'invoice_date' => '2025-01-10', 'status' => 'open', 'accounting_id' => $this->accounting->id,
            'debit_account_id' => $this->debtors->id, 'credit_account_id' => $this->photo->id,
        ]);
        $this->upload($this->csv([['2025-01-29', 'Gutschrift Muster Hans', 38, 'Holderweg 20 RF5716 RE-2025-0016 Fotos']]));
        $tx = BankTransaction::sole();

        $this->actingAs(User::factory()->create())
            ->get("/admin/accountings/{$this->accounting->id}/bank")
            ->assertSee('Rechnung RE-2025-0016', false);

        $this->actingAs(User::factory()->create())
            ->post("/admin/accountings/{$this->accounting->id}/bank/book", ['all' => 1]);

        $payment = Booking::sole();
        $this->assertSame([$this->bank->id, $this->debtors->id], [(int) $payment->debit_account_id, (int) $payment->credit_account_id]);
        $this->assertSame(Booking::ROLE_PAYMENT, $payment->invoice_role);
        $this->assertSame('2025-01-29', $payment->booking_date->toDateString(), 'Booked on the day of the bank, not today.');
        $this->assertSame('paid', $invoice->fresh()->status);

        // Back into the inbox: the payment goes, the invoice is open again
        $this->actingAs(User::factory()->create())->patch("/admin/bank-transactions/{$tx->id}/reopen");
        $this->assertSame(0, Booking::count());
        $this->assertSame('open', $tx->fresh()->status);
        $this->assertSame('open', $invoice->fresh()->status);
    }

    public function test_a_booking_already_in_the_journal_is_linked_not_booked_twice(): void
    {
        $manual = Booking::create([
            'accounting_id' => $this->accounting->id, 'booking_date' => '2025-09-15', 'description' => 'Hosting 2025',
            'debit_account_id' => $this->hosting->id, 'credit_account_id' => $this->bank->id, 'amount' => 93.45,
        ]);
        $this->upload($this->defaultCsv());
        $hosting = $this->accounting->bankTransactions()->where('amount', '<', 0)->sole();

        $this->actingAs(User::factory()->create())
            ->post("/admin/accountings/{$this->accounting->id}/bank/book", ['all' => 1]);

        $this->assertSame(1, Booking::count(), 'Linked, not booked a second time.');
        $this->assertSame($hosting->id, (int) $manual->fresh()->bank_transaction_id);
        $this->assertSame('booked', $hosting->fresh()->status);

        // Reopening only unlinks a booking the import did not make
        $this->actingAs(User::factory()->create())->patch("/admin/bank-transactions/{$hosting->id}/reopen");
        $this->assertNotNull($manual->fresh());
        $this->assertNull($manual->fresh()->bank_transaction_id);
    }

    public function test_deleting_the_booking_in_the_journal_puts_the_movement_back_into_the_inbox(): void
    {
        $this->upload($this->defaultCsv());
        $hosting = $this->accounting->bankTransactions()->where('amount', '<', 0)->sole();
        $this->bookRow($hosting, ['account_id' => $this->hosting->id]);

        $this->actingAs(User::factory()->create())->delete('/admin/bookings/' . Booking::sole()->id);

        $this->assertSame('open', $hosting->fresh()->status);
    }

    public function test_reconciliation_compares_bank_and_book_balance(): void
    {
        $this->upload($this->defaultCsv());
        foreach ($this->accounting->bankTransactions()->get() as $tx) {
            $this->bookRow($tx, ['account_id' => $tx->amount > 0 ? $this->streaming->id : $this->hosting->id]);
        }

        $this->actingAs(User::factory()->create())
            ->get("/admin/accountings/{$this->accounting->id}/bank?status=booked")
            ->assertOk()
            ->assertSee('1&#039;008.89', false)
            ->assertSee('✓ stimmt', false)
            ->assertDontSee('Differenz', false);
    }

    public function test_a_closed_accounting_takes_no_statement(): void
    {
        $this->accounting->update(['status' => 'closed']);

        $this->upload($this->defaultCsv())->assertSessionHas('error');
        $this->assertSame(0, BankTransaction::count());
    }

    public function test_marking_an_invoice_paid_books_on_the_given_day(): void
    {
        $invoice = Invoice::create([
            'invoice_number' => 'RE-2025-0020', 'type' => 'outgoing', 'amount' => 120, 'currency' => 'CHF',
            'invoice_date' => '2025-03-01', 'status' => 'open', 'accounting_id' => $this->accounting->id,
            'debit_account_id' => $this->debtors->id, 'credit_account_id' => $this->photo->id,
        ]);

        $this->actingAs(User::factory()->create())
            ->patch("/admin/invoices/{$invoice->id}/paid", ['paid_at' => '2025-03-20']);

        $payment = Booking::where('invoice_id', $invoice->id)->sole();
        $this->assertSame('2025-03-20', $payment->booking_date->toDateString());
        $this->assertSame(Booking::ROLE_PAYMENT, $payment->invoice_role);
    }
}
