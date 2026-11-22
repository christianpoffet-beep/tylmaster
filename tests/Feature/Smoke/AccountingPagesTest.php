<?php

namespace Tests\Feature\Smoke;

use App\Models\Account;
use App\Models\Accounting;
use App\Models\Booking;
use App\Models\Organization;
use App\Models\User;
use App\Services\ChartGroups;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The accounting pages load the debit/credit sums of all accounts at once
 * (they used to ask twice per account: 381 queries for a chart of 61), and
 * header accounts show the subtotal of the accounts under them.
 */
class AccountingPagesTest extends TestCase
{
    use RefreshDatabase;

    private Accounting $accounting;
    /** @var array<string, Account> */
    private array $accounts = [];

    protected function setUp(): void
    {
        parent::setUp();

        $owner = Organization::create(['type' => 'band', 'names' => ['Testband']]);
        $this->accounting = Accounting::create([
            'accountable_type' => Organization::class, 'accountable_id' => $owner->id,
            'name' => 'Buchhaltung 2025', 'currency' => 'CHF', 'period_start' => '2025-01-01', 'period_end' => '2025-12-31',
        ]);
        $chart = [
            ['1000', 'Umlaufvermögen', 'asset', true], ['1001', 'Kasse', 'asset', false], ['1003', 'Bank', 'asset', false],
            ['2800', 'Eigenkapital', 'liability', true], ['2801', 'Eigenkapital', 'liability', false],
            ['3000', 'Ertrag', 'income', true], ['3001', 'Gagen', 'income', false], ['3002', 'Merch', 'income', false],
            ['4000', 'Aufwand', 'expense', true],
        ];
        // A chart of realistic size: 30 expense accounts
        for ($i = 1; $i <= 30; $i++) {
            $chart[] = [(string) (4000 + $i), "Aufwand {$i}", 'expense', false];
        }
        foreach ($chart as [$number, $name, $type, $header]) {
            $this->accounts[$number] = $this->accounting->accounts()->create([
                'number' => $number, 'name' => $name, 'type' => $type, 'is_header' => $header,
                'opening_balance' => match ($number) { '1003' => 1000, '2801' => 1000, default => 0 },
            ]);
        }

        $book = fn ($debit, $credit, $amount) => Booking::create([
            'accounting_id' => $this->accounting->id, 'booking_date' => '2025-05-01', 'description' => 'Test',
            'debit_account_id' => $this->accounts[$debit]->id, 'credit_account_id' => $this->accounts[$credit]->id, 'amount' => $amount,
        ]);
        $book('1003', '3001', 500);
        $book('1001', '3002', 120);
        $book('3001', '1003', 50); // refund: lowers the income
        $book('4001', '1003', 200);
        $book('4002', '1001', 20);
    }

    private function queriesFor(string $url): int
    {
        $count = 0;
        DB::listen(function () use (&$count) { $count++; });
        $this->actingAs(User::factory()->create())->get($url)->assertOk();

        return $count;
    }

    public function test_the_pages_do_not_ask_per_account(): void
    {
        $base = "/admin/accountings/{$this->accounting->id}";

        foreach (['' => 25, '/trial-balance' => 15, '/balance-sheet' => 15, '/income-statement' => 15] as $page => $limit) {
            $this->assertLessThan($limit, $this->queriesFor($base . $page), "{$page} should not query per account (39 accounts)");
        }
    }

    public function test_header_accounts_show_their_subtotal(): void
    {
        $base = "/admin/accountings/{$this->accounting->id}";
        $user = User::factory()->create();

        // Umlaufvermögen: Kasse 100 + Bank 1250; Ertrag: 450 + 120 (net of the refund)
        $this->actingAs($user)->get($base)->assertOk()
            ->assertSee('Zwischensumme der Konten unter «Umlaufvermögen»', false)
            ->assertSee("1&#039;350.00", false)
            ->assertSee('570.00');
        $this->actingAs($user)->get("{$base}/balance-sheet")->assertOk()
            ->assertSee('Zwischensumme «Umlaufvermögen»', false)->assertSee("1&#039;350.00", false);
        $this->actingAs($user)->get("{$base}/income-statement")->assertOk()
            ->assertSee('Zwischensumme «Aufwand»', false)->assertSee('220.00');
    }

    public function test_preloaded_totals_match_the_database(): void
    {
        $fresh = $this->accounting->accounts()->where('is_header', false)->get();
        $preloaded = $this->accounting->loadAccountTotals()->accounts->where('is_header', false);

        foreach ($fresh as $account) {
            $loaded = $preloaded->firstWhere('id', $account->id);
            $this->assertSame(
                [$account->balance, $account->debit_total, $account->credit_total, $account->has_bookings],
                [$loaded->balance, $loaded->debit_total, $loaded->credit_total, $loaded->has_bookings],
                "Konto {$account->number}"
            );
        }
    }

    public function test_chart_groups_follow_the_header_accounts(): void
    {
        $groups = ChartGroups::of($this->accounting->accounts()->get());

        $this->assertSame(['Umlaufvermögen', 'Eigenkapital', 'Ertrag', 'Aufwand'], array_map(fn ($g) => $g['header']?->name, $groups));
        $this->assertSame(['1001', '1003'], $groups[0]['accounts']->pluck('number')->all());
        $this->assertCount(30, $groups[3]['accounts']);
    }
}
