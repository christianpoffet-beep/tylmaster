<?php

namespace Tests\Feature\Smoke;

use App\Models\Account;
use App\Models\Accounting;
use App\Models\BankTransaction;
use App\Models\Booking;
use App\Models\Expense;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Expenses in the accounting: paid by bank they wait for the bank inbox (and
 * appear there by themselves when a payment is booked onto an expense
 * account); paid in cash or privately they book themselves.
 */
class ExpenseAccountingTest extends TestCase
{
    use RefreshDatabase;

    private Accounting $accounting;
    private Account $cash;
    private Account $bank;
    private Account $hosting;
    private Account $fees;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('documents');
        $this->user = User::factory()->create();

        $owner = Organization::create(['type' => 'band', 'names' => ['Testband']]);
        $this->accounting = Accounting::create([
            'accountable_type' => Organization::class, 'accountable_id' => $owner->id,
            'name' => 'Buchhaltung 2025', 'currency' => 'CHF', 'period_start' => '2025-01-01', 'period_end' => '2025-12-31',
        ]);
        $make = fn ($number, $name, $type, $opening = 0) => $this->accounting->accounts()->create([
            'number' => $number, 'name' => $name, 'type' => $type, 'opening_balance' => $opening,
        ]);
        $this->cash = $make('1001', 'Kasse', 'asset', 100);
        $this->bank = $make('1003', 'Bank', 'asset', 1000);
        $this->hosting = $make('4008', 'Webhosting', 'expense');
        $this->fees = $make('4010', 'Bankgebühren', 'expense');
        $make('2801', 'Eigenkapital', 'liability', 1100);
    }

    private function upload(array $movements): void
    {
        $lines = ['IBAN;Booked At;Text;Credit/Debit Amount;Balance;Valuta Date'];
        $balance = 1000.0;
        foreach ($movements as [$date, $text, $amount]) {
            $balance = round($balance + $amount, 2);
            $lines[] = "CH9300762011623852957;{$date} 00:00:00.0;{$text};{$amount};{$balance};{$date} 00:00:00.0";
        }

        $this->actingAs($this->user)->post("/admin/accountings/{$this->accounting->id}/bank/import", [
            'file' => UploadedFile::fake()->createWithContent('Konto.csv', implode("\r\n", $lines)),
            'account_id' => $this->bank->id,
        ])->assertSessionHas('success');
    }

    private function expense(array $attributes = []): Expense
    {
        return Expense::create($attributes + [
            'description' => 'Hosting 2025', 'amount' => 93.45, 'currency' => 'CHF', 'expense_date' => '2025-09-01',
            'accounting_id' => $this->accounting->id, 'account_id' => $this->hosting->id,
        ]);
    }

    public function test_a_recorded_expense_is_found_and_paid_by_the_bank_inbox(): void
    {
        $expense = $this->expense();
        $this->assertSame('open', $expense->status);
        $this->upload([['2025-09-16', 'Zahlung Hosting AG', -93.45]]);

        $this->actingAs($this->user)->get("/admin/accountings/{$this->accounting->id}/bank")
            ->assertOk()->assertSee('Ausgabe «Hosting 2025»', false);

        $this->actingAs($this->user)->post("/admin/accountings/{$this->accounting->id}/bank/book", ['all' => 1]);

        $booking = Booking::sole();
        $this->assertSame([$this->hosting->id, $this->bank->id], [(int) $booking->debit_account_id, (int) $booking->credit_account_id]);
        $this->assertSame('2025-09-16', $booking->booking_date->toDateString(), 'Booked on the bank date.');
        $this->assertSame($booking->id, (int) $expense->fresh()->booking_id);
        $this->assertSame('bank', $expense->fresh()->status);
        $this->assertSame(1, Expense::count(), 'No second expense for a payment that settled one.');
    }

    public function test_an_expense_in_a_foreign_currency_can_be_picked_by_hand(): void
    {
        $expense = $this->expense(['amount' => 100, 'currency' => 'EUR', 'description' => 'Mastering']);
        $this->upload([['2025-09-16', 'Zahlung Studio GmbH EUR 100.00', -93.12]]);
        $tx = BankTransaction::sole();

        $this->actingAs($this->user)->post("/admin/accountings/{$this->accounting->id}/bank/book", [
            'transaction' => $tx->id, 'match' => "expense:{$expense->id}",
        ])->assertSessionHas('success');

        $this->assertSame('93.12', Booking::sole()->amount, 'The bank amount in CHF is booked.');
        $this->assertSame('bank', $expense->fresh()->status);
    }

    public function test_a_payment_booked_onto_an_expense_account_becomes_an_expense(): void
    {
        $this->upload([['2025-12-30', 'Gebührenbelastung Kontoführung', -90]]);
        $tx = BankTransaction::sole();

        $this->actingAs($this->user)->post("/admin/accountings/{$this->accounting->id}/bank/book", [
            'transaction' => $tx->id, 'account_id' => $this->fees->id, 'description' => 'Kontoführung 2025',
        ]);

        $expense = Expense::sole();
        $this->assertTrue($expense->created_from_bank);
        $this->assertSame(['Kontoführung 2025', '90.00', $this->fees->id, 'bank'], [$expense->description, $expense->amount, (int) $expense->account_id, $expense->status]);

        $this->actingAs($this->user)->get('/admin/expenses')->assertOk()->assertSee('Kontoführung 2025');

        // Back into the inbox: the expense the bank made goes too
        $this->actingAs($this->user)->patch("/admin/bank-transactions/{$tx->id}/reopen");
        $this->assertSame(0, Expense::count());
    }

    public function test_an_expense_with_a_receipt_survives_reopening_and_is_offered_again(): void
    {
        $this->upload([['2025-12-30', 'Gebührenbelastung Kontoführung', -90]]);
        $tx = BankTransaction::sole();
        $this->actingAs($this->user)->post("/admin/accountings/{$this->accounting->id}/bank/book", [
            'transaction' => $tx->id, 'account_id' => $this->fees->id,
        ]);
        $expense = Expense::sole();
        $expense->documents()->create(['title' => 'Beleg.pdf', 'category' => 'invoice', 'file_path' => 'expenses/x.pdf']);

        $this->actingAs($this->user)->patch("/admin/bank-transactions/{$tx->id}/reopen");

        $this->assertSame('open', $expense->fresh()->status, 'Kept for its receipt, waiting again.');
        $this->actingAs($this->user)->post("/admin/accountings/{$this->accounting->id}/bank/book", ['all' => 1]);
        $this->assertSame(1, Expense::count());
        $this->assertSame('bank', $expense->fresh()->status);
    }

    public function test_income_on_a_non_expense_account_makes_no_expense(): void
    {
        $this->upload([['2025-03-01', 'Bareinzahlung', -50]]);
        $this->actingAs($this->user)->post("/admin/accountings/{$this->accounting->id}/bank/book", [
            'transaction' => BankTransaction::sole()->id, 'account_id' => $this->cash->id,
        ]);

        $this->assertSame(0, Expense::count());
    }

    public function test_a_cash_expense_books_itself_and_follows_its_changes(): void
    {
        $this->actingAs($this->user)->post('/admin/expenses', [
            'description' => 'Saitenset', 'amount' => '24.90', 'currency' => 'CHF', 'expense_date' => '2025-04-02',
            'accounting_id' => $this->accounting->id, 'account_id' => $this->hosting->id, 'paid_from_account_id' => $this->cash->id,
            'documents' => [UploadedFile::fake()->create('quittung.pdf', 20, 'application/pdf')],
        ])->assertRedirect('/admin/expenses');

        $expense = Expense::sole();
        $booking = $expense->booking;
        $this->assertSame([$this->hosting->id, $this->cash->id, '24.90'], [(int) $booking->debit_account_id, (int) $booking->credit_account_id, $booking->amount]);
        $this->assertSame('direct', $expense->status);
        $this->assertSame(1, $expense->documents()->count());

        $this->actingAs($this->user)->put("/admin/expenses/{$expense->id}", [
            'description' => 'Saitenset', 'amount' => '29.90', 'currency' => 'CHF', 'expense_date' => '2025-04-03',
            'accounting_id' => $this->accounting->id, 'account_id' => $this->hosting->id, 'paid_from_account_id' => $this->cash->id,
        ]);
        $this->assertSame(['29.90', '2025-04-03'], [$booking->fresh()->amount, $booking->fresh()->booking_date->toDateString()]);

        // Paid by bank after all: its own booking goes, it waits for the bank
        $this->actingAs($this->user)->put("/admin/expenses/{$expense->id}", [
            'description' => 'Saitenset', 'amount' => '29.90', 'currency' => 'CHF', 'expense_date' => '2025-04-03',
            'accounting_id' => $this->accounting->id, 'account_id' => $this->hosting->id, 'paid_from_account_id' => '',
        ]);
        $this->assertSame(0, Booking::count());
        $this->assertSame('open', $expense->fresh()->status);
    }

    public function test_deleting_a_cash_expense_removes_its_booking_but_a_bank_booking_stays(): void
    {
        $cash = $this->expense(['paid_from_account_id' => $this->cash->id, 'description' => 'Bar']);
        app(\App\Services\ExpenseBooker::class)->sync($cash);
        $this->actingAs($this->user)->delete("/admin/expenses/{$cash->id}");
        $this->assertSame(0, Booking::count());

        $this->upload([['2025-12-30', 'Gebührenbelastung Kontoführung', -90]]);
        $this->actingAs($this->user)->post("/admin/accountings/{$this->accounting->id}/bank/book", [
            'transaction' => BankTransaction::sole()->id, 'account_id' => $this->fees->id,
        ]);
        $this->actingAs($this->user)->delete('/admin/expenses/' . Expense::sole()->id);
        $this->assertSame(1, Booking::count(), 'The money did leave the bank - that booking stays.');
        $this->assertSame('booked', BankTransaction::sole()->status);
    }

    public function test_a_bank_paid_expense_can_change_its_account_but_not_its_accounting(): void
    {
        $this->upload([['2025-12-30', 'Gebührenbelastung Kontoführung', -90]]);
        $this->actingAs($this->user)->post("/admin/accountings/{$this->accounting->id}/bank/book", [
            'transaction' => BankTransaction::sole()->id, 'account_id' => $this->fees->id,
        ]);
        $expense = Expense::sole();
        $data = ['description' => 'Gebühren', 'amount' => '90', 'currency' => 'CHF', 'expense_date' => '2025-12-30'];

        $this->actingAs($this->user)->put("/admin/expenses/{$expense->id}", $data + [
            'accounting_id' => $this->accounting->id, 'account_id' => $this->hosting->id,
        ])->assertSessionHasNoErrors();
        $this->assertSame($this->hosting->id, (int) Booking::sole()->debit_account_id);

        $other = Accounting::create(['accountable_type' => Organization::class, 'accountable_id' => 1, 'name' => 'Andere',
            'currency' => 'CHF', 'period_start' => '2025-01-01', 'period_end' => '2025-12-31']);
        $this->actingAs($this->user)->put("/admin/expenses/{$expense->id}", $data + ['accounting_id' => $other->id])
            ->assertSessionHasErrors('accounting_id');
    }

    public function test_the_forms_render(): void
    {
        $expense = $this->expense();

        $this->actingAs($this->user)->get('/admin/expenses/create')->assertOk()->assertSee('Bezahlt mit');
        $this->actingAs($this->user)->get("/admin/expenses/{$expense->id}/edit")->assertOk()->assertSee('Hosting 2025');
        $this->actingAs($this->user)->get('/admin/expenses?status=open')->assertOk()->assertSee('Hosting 2025');
    }
}
