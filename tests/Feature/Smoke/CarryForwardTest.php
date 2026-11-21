<?php

namespace Tests\Feature\Smoke;

use App\Models\Account;
use App\Models\Accounting;
use App\Models\Booking;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use App\Services\CarryForward;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A new fiscal year opens with the closing balances of the previous one
 * (Saldovortrag), and what was left open there is settled in the new one.
 */
class CarryForwardTest extends TestCase
{
    use RefreshDatabase;

    private const IBAN = 'CH9300762011623852957';

    private Accounting $year2025;
    /** @var array<string, Account> */
    private array $accounts = [];
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('documents');
        $this->user = User::factory()->create();

        $owner = Organization::create(['type' => 'band', 'names' => ['Testband']]);
        $this->year2025 = Accounting::create([
            'accountable_type' => Organization::class, 'accountable_id' => $owner->id,
            'name' => 'Buchhaltung 2025', 'currency' => 'CHF', 'period_start' => '2025-01-01', 'period_end' => '2025-12-31',
        ]);
        foreach ([
            ['1000', 'Umlaufvermögen', 'asset', 0, true],
            ['1001', 'Kasse', 'asset', 100, false],
            ['1003', 'Bank', 'asset', 1000, false],
            ['1100', 'Debitoren', 'asset', 0, false],
            ['2001', 'Kreditoren', 'liability', 0, false],
            ['2801', 'Eigenkapital', 'liability', 1100, false],
            ['3000', 'Gagen', 'income', 0, false],
            ['4000', 'Proberaum', 'expense', 0, false],
        ] as [$number, $name, $type, $opening, $header]) {
            $this->accounts[$number] = $this->year2025->accounts()->create([
                'number' => $number, 'name' => $name, 'type' => $type, 'opening_balance' => $opening, 'is_header' => $header,
            ]);
        }
        $this->accounts['1003']->update(['iban' => self::IBAN]);

        // Profit 2025: 500 + 300 - 200 = 600; the 300 are still owed (Debitoren)
        $this->book('2025-03-01', '1003', '3000', 500);
        $this->book('2025-05-01', '4000', '1003', 200);
        $this->book('2025-12-15', '1100', '3000', 300);
    }

    private function book(string $date, string $debit, string $credit, float $amount, ?Accounting $accounting = null): Booking
    {
        $accounting ??= $this->year2025;
        $account = fn ($number) => $accounting->accounts()->where('number', $number)->firstOrFail()->id;

        return Booking::create([
            'accounting_id' => $accounting->id, 'booking_date' => $date, 'description' => "Buchung {$debit}/{$credit}",
            'debit_account_id' => $account($debit), 'credit_account_id' => $account($credit), 'amount' => $amount,
        ]);
    }

    private function open2026(): Accounting
    {
        return app(CarryForward::class)->open($this->year2025, [
            'name' => 'Buchhaltung 2026', 'period_start' => '2026-01-01', 'period_end' => '2026-12-31',
        ], $this->accounts['2801']);
    }

    private function openings(Accounting $accounting): array
    {
        return $accounting->accounts()->get()->mapWithKeys(fn (Account $a) => [$a->number => (float) $a->opening_balance])->all();
    }

    private function upload(Accounting $accounting, array $movements): void
    {
        $lines = ['IBAN;Booked At;Text;Credit/Debit Amount;Balance;Valuta Date'];
        $balance = 1300.0;
        foreach ($movements as [$date, $text, $amount]) {
            $balance = round($balance + $amount, 2);
            $lines[] = self::IBAN . ";{$date} 00:00:00.0;{$text};{$amount};{$balance};{$date} 00:00:00.0";
        }

        $this->actingAs($this->user)->post("/admin/accountings/{$accounting->id}/bank/import", [
            'file' => UploadedFile::fake()->createWithContent('Konto.csv', implode("\r\n", $lines)),
            'account_id' => $accounting->accounts()->where('number', '1003')->value('id'),
        ])->assertSessionHas('success');
    }

    public function test_the_next_year_opens_with_last_years_closing_balances(): void
    {
        $this->actingAs($this->user)->get("/admin/accountings/create?from={$this->year2025->id}")
            ->assertOk()->assertSee('Folgejahr eröffnen')->assertSee('Gewinn / Verlust des Vorjahres auf');

        $response = $this->actingAs($this->user)->post('/admin/accountings', [
            'source_accounting_id' => $this->year2025->id, 'name' => 'Buchhaltung 2026',
            'period_start' => '2026-01-01', 'period_end' => '2026-12-31',
            'profit_account_id' => $this->accounts['2801']->id, 'close_source' => '1',
        ]);

        $next = Accounting::where('name', 'Buchhaltung 2026')->sole();
        $response->assertRedirect("/admin/accountings/{$next->id}");

        $this->assertSame([$this->year2025->accountable_type, $this->year2025->accountable_id, 'CHF', $this->year2025->id],
            [$next->accountable_type, $next->accountable_id, $next->currency, (int) $next->previous_accounting_id]);
        $this->assertSame([
            '1000' => 0.0, '1001' => 100.0, '1003' => 1300.0, '1100' => 300.0,
            '2001' => 0.0, '2801' => 1700.0, '3000' => 0.0, '4000' => 0.0,
        ], $this->openings($next), 'Closing balances carried over, the profit of 600 onto equity.');
        $this->assertTrue($next->accounts()->where('number', '1000')->value('is_header'));
        $this->assertSame(self::IBAN, $next->accounts()->where('number', '1003')->value('iban'));
        $this->assertSame('2801', $next->profitAccount->number);
        $this->assertTrue($this->year2025->fresh()->is_closed);

        $this->actingAs($this->user)->get("/admin/accountings/{$next->id}")
            ->assertOk()->assertSee('Eröffnet aus')->assertDontSee('passen nicht mehr');
        $this->actingAs($this->user)->get("/admin/accountings/{$this->year2025->id}")
            ->assertOk()->assertSee('Folgejahr:')->assertDontSee('Folgejahr eröffnen');
    }

    public function test_a_new_accounting_without_a_previous_year_still_works(): void
    {
        $this->actingAs($this->user)->get('/admin/accountings/create')
            ->assertOk()->assertSee('Neue Buchhaltung')->assertSee('Kontoplan-Vorlage');

        $this->actingAs($this->user)->post('/admin/accountings', [
            'accountable_type' => 'organization', 'accountable_id' => $this->year2025->accountable_id,
            'name' => 'Projekt X', 'currency' => 'EUR', 'period_start' => '2026-01-01', 'period_end' => '2026-12-31',
            'chart_template_id' => '', 'notes' => '',
            'custom_accounts' => [['number' => '1003', 'name' => 'Bank', 'type' => 'asset', 'is_header' => '0', 'opening_balance' => '50']],
        ])->assertSessionHasNoErrors();

        $accounting = Accounting::where('name', 'Projekt X')->sole();
        $this->assertNull($accounting->previous_accounting_id);
        $this->assertSame(['1003' => 50.0], $this->openings($accounting));
    }

    public function test_a_loss_lowers_the_equity(): void
    {
        $this->book('2025-11-01', '4000', '1003', 800);

        $plan = app(CarryForward::class)->plan($this->year2025->load('accounts'), $this->accounts['2801']);

        $this->assertSame(-200.0, $plan['profit']);
        $this->assertSame(900.0, $plan['openings'][$this->accounts['2801']->id]);
        $this->assertSame([900.0, 900.0, 0.0], [$plan['assets'], $plan['liabilities'], $plan['difference']]);
    }

    public function test_the_new_year_must_follow_the_old_one_and_take_its_profit_on_a_liability(): void
    {
        $data = ['source_accounting_id' => $this->year2025->id, 'name' => 'Buchhaltung 2026', 'period_end' => '2026-12-31'];

        $this->actingAs($this->user)->post('/admin/accountings', $data + [
            'period_start' => '2025-06-01', 'profit_account_id' => $this->accounts['2801']->id,
        ])->assertSessionHasErrors('period_start');

        $this->actingAs($this->user)->post('/admin/accountings', $data + [
            'period_start' => '2026-01-01', 'profit_account_id' => $this->accounts['3000']->id,
        ])->assertSessionHasErrors('profit_account_id');

        $this->assertSame(1, Accounting::count());
    }

    public function test_late_bookings_in_the_old_year_can_be_taken_over_again(): void
    {
        $next = $this->open2026();

        // Booked in 2025 after 2026 was opened - on an account 2026 does not know yet
        $this->year2025->accounts()->create(['number' => '1004', 'name' => 'Sparkonto', 'type' => 'asset']);
        $this->book('2025-12-30', '1004', '1003', 100);

        $this->actingAs($this->user)->get("/admin/accountings/{$next->id}")
            ->assertOk()->assertSee('passen nicht mehr')->assertSee('Sparkonto');

        $this->actingAs($this->user)->post("/admin/accountings/{$next->id}/refresh-openings")
            ->assertSessionHas('success');

        $openings = $this->openings($next);
        $this->assertSame([1200.0, 100.0, 1700.0], [$openings['1003'], $openings['1004'], $openings['2801']]);
        $this->assertSame('Sparkonto', $next->accounts()->where('number', '1004')->value('name'));
        $this->assertTrue(app(CarryForward::class)->differences($next->fresh())->isEmpty());
    }

    public function test_last_years_open_invoice_is_paid_in_the_new_year(): void
    {
        $invoice = Invoice::create([
            'invoice_number' => 'RE-2025-0099', 'type' => 'outgoing', 'amount' => 300, 'currency' => 'CHF',
            'invoice_date' => '2025-12-15', 'status' => 'open', 'accounting_id' => $this->year2025->id,
            'debit_account_id' => $this->accounts['1100']->id, 'credit_account_id' => $this->accounts['3000']->id,
        ]);
        $next = $this->open2026();
        $this->upload($next, [['2026-01-20', 'Gutschrift Veranstalter RE-2025-0099', 300]]);

        $this->actingAs($this->user)->get("/admin/accountings/{$next->id}/bank")
            ->assertOk()->assertSee('Rechnung RE-2025-0099', false);
        $this->actingAs($this->user)->post("/admin/accountings/{$next->id}/bank/book", ['all' => 1]);

        $payment = Booking::where('accounting_id', $next->id)->sole();
        $this->assertSame(
            [$next->accounts()->where('number', '1003')->value('id'), $next->accounts()->where('number', '1100')->value('id'), Booking::ROLE_PAYMENT],
            [(int) $payment->debit_account_id, (int) $payment->credit_account_id, $payment->invoice_role],
            'Bank an Debitoren - the receivable carried into 2026.'
        );
        $this->assertSame('paid', $invoice->fresh()->status);
    }

    public function test_marking_last_years_invoice_paid_in_the_new_year_books_it_there(): void
    {
        $invoice = Invoice::create([
            'invoice_number' => 'RE-2025-0099', 'type' => 'outgoing', 'amount' => 300, 'currency' => 'CHF',
            'invoice_date' => '2025-12-15', 'status' => 'open', 'accounting_id' => $this->year2025->id,
            'debit_account_id' => $this->accounts['1100']->id, 'credit_account_id' => $this->accounts['3000']->id,
        ]);
        $next = $this->open2026();

        $this->actingAs($this->user)->patch("/admin/invoices/{$invoice->id}/paid", ['paid_at' => '2026-02-01']);

        $payment = Booking::where('invoice_id', $invoice->id)->sole();
        $this->assertSame((int) $next->id, (int) $payment->accounting_id);
        $this->assertSame('1100', Account::find($payment->credit_account_id)->number);
        $this->assertSame((int) $next->id, (int) Account::find($payment->credit_account_id)->accounting_id);
    }

    public function test_last_years_open_expense_is_paid_and_booked_in_the_new_year(): void
    {
        $expense = Expense::create([
            'description' => 'Proberaum Dezember', 'amount' => 50, 'currency' => 'CHF', 'expense_date' => '2025-12-20',
            'accounting_id' => $this->year2025->id, 'account_id' => $this->accounts['4000']->id,
        ]);
        $next = $this->open2026();
        $this->upload($next, [['2026-01-10', 'Zahlung Proberaum GmbH', -50]]);

        $this->actingAs($this->user)->get("/admin/accountings/{$next->id}/bank")
            ->assertOk()->assertSee('Ausgabe «Proberaum Dezember»', false);
        $this->actingAs($this->user)->post("/admin/accountings/{$next->id}/bank/book", ['all' => 1]);

        $expense->refresh();
        $rent2026 = $next->accounts()->where('number', '4000')->value('id');
        $this->assertSame([(int) $next->id, (int) $rent2026, 'bank'], [(int) $expense->accounting_id, (int) $expense->account_id, $expense->status]);
        $this->assertSame((int) $rent2026, (int) $expense->booking->debit_account_id);
        $this->assertSame(1, Expense::count());
    }
}
