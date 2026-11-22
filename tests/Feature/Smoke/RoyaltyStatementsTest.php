<?php

namespace Tests\Feature\Smoke;

use App\Models\Account;
use App\Models\Accounting;
use App\Models\Booking;
use App\Models\Contract;
use App\Models\Organization;
use App\Models\Release;
use App\Models\RoyaltyImport;
use App\Models\RoyaltyLedgerEntry;
use App\Models\RoyaltyLine;
use App\Models\RoyaltyPayment;
use App\Models\RoyaltyStatement;
use App\Models\Track;
use App\Models\User;
use App\Services\Banking\BankImporter;
use App\Services\Royalties\RoyaltyPayments;
use App\Services\Royalties\RoyaltySettings;
use App\Services\Royalties\RoyaltyStatementBooker;
use App\Services\Royalties\RoyaltyStatementException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A statement over 2023-2025, made-up figures: Rebeat paid CHF 92 for usage
 * of EUR 100 (factor 0.92).
 *   Track A, own label, contract 20/80 with an advance of CHF 5: 60 EUR
 *   Track B, label Zentrum, no contract (100% to the label):    20 EUR
 *   Track C, own label, no contract (stays, reported):          10 EUR
 *   a line without track (stays, reported):                     10 EUR
 */
class RoyaltyStatementsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Organization $own;
    private Organization $band;
    private Organization $zentrum;
    private Accounting $accounting;
    /** @var array<string, Account> */
    private array $accounts = [];
    private Contract $contract;
    private array $files = [];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('documents');
        $this->user = User::factory()->create();
        $this->own = Organization::create(['type' => 'label', 'names' => ['The yelling light']]);
        $this->band = Organization::create(['type' => 'band', 'names' => ['Tar Pond']]);
        $this->zentrum = Organization::create(['type' => 'label', 'names' => ['Zentrum']]);
        RoyaltySettings::setOwnOrganizations([$this->own->id]);

        $this->accounting = Accounting::create(['accountable_type' => Organization::class, 'accountable_id' => $this->own->id,
            'name' => 'TYL 2026', 'currency' => 'CHF', 'period_start' => '2026-01-01', 'period_end' => '2026-12-31']);
        foreach ([['1003', 'Bank', 'asset'], ['2001', 'Kreditoren', 'liability'], ['3004', 'Kostenbeteiligungen Künstler:innen', 'income'],
                  ['3302', 'Rebeat - Streaming & Download', 'income'], ['6501', 'Streaming & Download', 'expense'],
                  ['6510', 'Anteil Label Zentrum', 'expense']] as [$number, $name, $type]) {
            $this->accounts[$number] = $this->accounting->accounts()->create(['number' => $number, 'name' => $name, 'type' => $type]);
        }

        $import = RoyaltyImport::create(['source' => 'rebeat', 'file_name' => 'export.csv', 'completed_at' => now(), 'confirmed_at' => now()]);
        $line = fn (?Track $track, float $eur, string $month = '2024-03-01') => RoyaltyLine::create([
            'royalty_import_id' => $import->id, 'source' => 'rebeat', 'external_id' => uniqid('', true), 'track_id' => $track?->id,
            'period' => $month, 'quantity' => 100, 'amount_gross' => $eur / 0.85, 'amount' => $eur,
        ]);

        $releaseA = Release::create(['title' => 'Coroner']);
        $releaseA->organizations()->attach([$this->band->id, $this->own->id]);
        $trackA = Track::create(['title' => 'Fog', 'status' => 'released']);
        $releaseA->tracks()->attach($trackA, ['track_number' => 1, 'disc_number' => 1]);
        $this->contract = Contract::create(['title' => 'Tar Pond – Coroner', 'type' => 'label', 'status' => 'active', 'start_date' => '2021-01-01',
            'rights' => [['label' => 'Streaming & Downloads', 'mode' => 'split', 'splits' => [20, 80], 'category' => 'streaming']]]);
        $this->contract->parties()->create(['organization_id' => $this->own->id, 'share' => 20, 'sort_order' => 0]);
        $this->contract->parties()->create(['organization_id' => $this->band->id, 'share' => 80, 'sort_order' => 1]);
        $this->contract->releases()->attach($releaseA);
        $this->contract->advances()->create(['date' => '2023-02-01', 'description' => 'Mastering', 'amount' => 5]);

        $releaseB = Release::create(['title' => 'Domi I']);
        $releaseB->organizations()->attach($this->zentrum);
        $trackB = Track::create(['title' => 'Domi', 'status' => 'released']);
        $releaseB->tracks()->attach($trackB, ['track_number' => 1, 'disc_number' => 1]);

        $trackC = Track::create(['title' => 'Ohne Vertrag', 'status' => 'released']);
        $trackC->organizations()->attach($this->own);

        $line($trackA, 40);
        $line($trackA, 20, '2025-11-01');
        $line($trackB, 20);
        $line($trackC, 10);
        $line(null, 10);
        $line($trackA, 99, '2026-01-01'); // after the period: next statement

        RoyaltyPayment::create(['paid_at' => '2024-05-02', 'amount' => 46, 'amount_foreign' => 50, 'origin' => 'manual', 'fingerprint' => 'a']);
        RoyaltyPayment::create(['paid_at' => '2025-09-30', 'amount' => 46, 'amount_foreign' => 50, 'origin' => 'manual', 'fingerprint' => 'b']);
        RoyaltyPayment::create(['paid_at' => '2026-01-31', 'amount' => 70, 'origin' => 'manual', 'fingerprint' => 'c']); // 2026: not now
    }

    protected function tearDown(): void
    {
        foreach ($this->files as $file) @unlink($file);
        parent::tearDown();
    }

    private function statement(array $attributes = []): RoyaltyStatement
    {
        return RoyaltyStatement::create($attributes + [
            'title' => 'Abrechnung 2023–2025', 'statement_date' => '2026-12-15', 'period_from' => '2023-01-01', 'period_to' => '2025-12-31',
            'accounting_id' => $this->accounting->id, 'pre_booked' => 10,
            'accounts' => RoyaltyStatementBooker::defaultAccounts($this->accounting),
        ]);
    }

    private function bookings(): array
    {
        return Booking::with(['debitAccount', 'creditAccount'])->orderBy('id')->get()
            ->map(fn ($b) => [$b->debitAccount->number, $b->creditAccount->number, (float) $b->amount])->all();
    }

    public function test_the_draft_distributes_the_payments_by_usage_and_contract(): void
    {
        $result = app(\App\Services\Royalties\RoyaltyStatementCalculator::class)->calculate($this->statement());

        $this->assertSame([92.0, 100.0], [$result['pot'], round($result['usage'], 2)]);
        $this->assertEqualsWithDelta(0.92, $result['factor'], 0.000001);

        $parties = RoyaltyStatementBooker::parties($result['items'])
            ->mapWithKeys(fn ($p) => [$p->role . ':' . $p->name => [round($p->share, 2), round($p->recouped, 2), $p->credit]])->all();
        $this->assertSame([
            'artist:Tar Pond' => [44.16, 5.0, 39.16],           // 80% of 55.20, less the advance
            'label:Zentrum' => [18.4, 0.0, 18.4],               // no contract: 100% to the label
            'own:The yelling light' => [11.04, 0.0, 11.04],     // 20% stays
            'none:ohne Vertrag / ohne Track' => [18.4, 0.0, 18.4], // track C and the line without track
        ], $parties);

        $this->assertStringContainsString('Ohne Vertrag', implode(' ', $result['warnings']));
        $this->assertStringContainsString('ohne Track', implode(' ', $result['warnings']));
    }

    public function test_usage_missing_for_the_payouts_is_pointed_out(): void
    {
        $calculator = app(\App\Services\Royalties\RoyaltyStatementCalculator::class);
        $this->assertStringNotContainsString('Nutzungsdaten des ganzen Zeitraums', implode(' ', $calculator->calculate($this->statement())['warnings']));

        // Paid for far more than the usage read in: 1 EUR would be worth CHF 5.52
        RoyaltyPayment::create(['paid_at' => '2025-03-01', 'amount' => 460, 'amount_foreign' => 500, 'origin' => 'manual', 'fingerprint' => 'd']);
        $warnings = implode(' ', $calculator->calculate($this->statement(['title' => 'B']))['warnings']);
        $this->assertStringContainsString('1 EUR Nutzung ergibt hier CHF 5.5200, die Bank hat die Rebeat-Zahlungen aber zu CHF 0.9200 umgerechnet', $warnings);
        $this->assertStringContainsString('Rebeat hat im Zeitraum EUR 600.00 ausbezahlt, eingelesen ist Nutzung von EUR 100.00', $warnings);
    }

    public function test_finalizing_books_credits_and_marks_and_undo_takes_it_back(): void
    {
        $statement = app(RoyaltyStatementBooker::class)->finalize($this->statement());

        $this->assertTrue($statement->is_final);
        $this->assertSame([
            ['6501', '2001', 29.16], // 39.16 for the artists, less 10 booked flat before
            ['6501', '3004', 5.0],   // the recouped advance
            ['6510', '2001', 18.4],  // Zentrum, on its own account
        ], $this->bookings());

        $balances = RoyaltyLedgerEntry::with('organization')->get()->mapWithKeys(fn ($e) => [$e->party_name => (float) $e->amount])->all();
        $this->assertSame(['Tar Pond' => 39.16, 'Zentrum' => 18.4], $balances);
        $this->assertSame(5, RoyaltyLine::where('royalty_statement_id', $statement->id)->count(), 'All lines of the period, also those staying');
        $this->assertSame(2, RoyaltyPayment::where('royalty_statement_id', $statement->id)->count());
        $this->assertSame(0.0, \App\Services\Royalties\RoyaltyStatementCalculator::openAdvances($this->contract, now()), 'The advance is recouped');

        // A second statement over the same period finds nothing left
        try {
            app(RoyaltyStatementBooker::class)->finalize($this->statement(['title' => 'Nochmals']));
            $this->fail('Nothing is left to distribute');
        } catch (RoyaltyStatementException $e) {
            $this->assertStringContainsString('nichts zu verteilen', $e->getMessage());
        }

        app(RoyaltyStatementBooker::class)->undo($statement->fresh());
        $this->assertSame([[], 0, 0, 0], [$this->bookings(), RoyaltyLedgerEntry::count(),
            RoyaltyLine::whereNotNull('royalty_statement_id')->count(), RoyaltyPayment::whereNotNull('royalty_statement_id')->count()]);
        $this->assertSame(5.0, \App\Services\Royalties\RoyaltyStatementCalculator::openAdvances($this->contract, now()));
    }

    public function test_a_statement_books_only_into_an_open_accounting_of_its_date(): void
    {
        $this->accounting->update(['status' => 'closed']);
        try {
            app(RoyaltyStatementBooker::class)->finalize($this->statement());
            $this->fail('A closed accounting takes no bookings');
        } catch (RoyaltyStatementException $e) {
            $this->assertStringContainsString('abgeschlossen', $e->getMessage());
        }

        $this->accounting->update(['status' => 'open']);
        $this->expectException(RoyaltyStatementException::class);
        app(RoyaltyStatementBooker::class)->finalize($this->statement(['statement_date' => '2027-01-15']));
    }

    public function test_the_pages_create_finalize_and_undo_a_statement(): void
    {
        $this->actingAs($this->user)->get('/admin/royalties/statements')->assertOk()
            ->assertSee('Neue Abrechnung')->assertSee('value="2026-12-15"', false);

        $this->actingAs($this->user)->post('/admin/royalties/statements', [
            'title' => 'Abrechnung 2023–2025', 'statement_date' => '2026-12-15', 'period_from' => '2023-01-01', 'period_to' => '2025-12-31',
            'accounting_id' => $this->accounting->id, 'pre_booked' => '10',
        ])->assertRedirect();
        $statement = RoyaltyStatement::sole();

        $this->actingAs($this->user)->get("/admin/royalties/statements/{$statement->id}")->assertOk()
            ->assertSee('Entwurf')->assertSee('CHF 92.00')->assertSee('Tar Pond')->assertSee('Gutschrift CHF 39.16')
            ->assertSee('ohne gültigen Vertrag')->assertSee('6510 Anteil Label Zentrum')->assertSee('Diese Buchungen entstehen beim Abschliessen');

        $this->actingAs($this->user)->post("/admin/royalties/statements/{$statement->id}/finalize")->assertSessionHas('success');
        $this->actingAs($this->user)->get("/admin/royalties/statements/{$statement->id}")->assertOk()
            ->assertSee('abgeschlossen')->assertSee('Rückgängig machen')->assertSee('Gutschrift CHF 39.16');

        $this->actingAs($this->user)->post("/admin/royalties/statements/{$statement->id}/undo")->assertSessionHas('success');
        $this->assertSame(RoyaltyStatement::DRAFT, $statement->fresh()->status);

        $this->actingAs($this->user)->delete("/admin/royalties/statements/{$statement->id}")->assertRedirect('/admin/royalties/statements');
        $this->assertSame(0, RoyaltyStatement::count());
    }

    public function test_each_party_gets_its_statement_as_pdf(): void
    {
        $statement = $this->statement();
        $document = app(\App\Services\Royalties\RoyaltyStatementDocument::class);

        ['result' => $result, 'parties' => $parties] = $document->prepare($statement);
        $this->assertSame(['org-' . $this->band->id, 'org-' . $this->zentrum->id], $parties->pluck('key')->all(), 'Only the credited ones');

        // An earlier credit counts into the balance
        RoyaltyLedgerEntry::create(['organization_id' => $this->band->id, 'entry_date' => '2025-12-15', 'type' => 'credit', 'amount' => 70, 'description' => 'Früher']);
        $html = view('admin.royalties.pdf', $document->data($statement, $parties->first(), $result))->render();
        foreach (['ENTWURF', 'Tar Pond', 'Tar Pond – Coroner', 'Dein Anteil an Streaming &amp; Downloads: 80 %', 'Fog', '44.16',
                  'Verrechnung Vorschüsse und Kosten gemäss Vertrag (danach noch offen: CHF 0.00)', '− 5.00',
                  'CHF 70.00', 'CHF 39.16', 'CHF 109.16', 'Wir überweisen Dir den Saldo'] as $expected) {
            $this->assertStringContainsString($expected, $html);
        }

        $this->actingAs($this->user)->get("/admin/royalties/statements/{$statement->id}/pdf/org-{$this->band->id}")
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->actingAs($this->user)->get("/admin/royalties/statements/{$statement->id}/pdf/org-99999")->assertNotFound();

        $zip = $this->actingAs($this->user)->get("/admin/royalties/statements/{$statement->id}/pdfs")->assertOk();
        $archive = new \ZipArchive();
        $archive->open($zip->baseResponse->getFile()->getPathname());
        $names = [];
        for ($i = 0; $i < $archive->numFiles; $i++) $names[] = $archive->getNameIndex($i);
        $archive->close();
        @unlink($zip->baseResponse->getFile()->getPathname());
        $this->assertSame(['Abrechnung_2023-2025_tar-pond_ENTWURF.pdf', 'Abrechnung_2023-2025_zentrum_ENTWURF.pdf'], $names);
    }

    public function test_rebeat_payouts_come_from_bank_statements_once(): void
    {
        $csv = implode("\r\n", [
            'IBAN;Booked At;Text;Credit/Debit Amount;Balance;Valuta Date',
            'CH00;2024-03-08 00:00:00.0;Gutschrift (SEPA) von REBEAT Digital GmbH EUR 80.75, Umrechnungskurs 0.9424;76.1;1076.1;2024-03-08 00:00:00.0',
            'CH00;2024-04-01 00:00:00.0;Gutschrift Jemand;20;1096.1;2024-04-01 00:00:00.0',
        ]) . "\r\n";
        $path = $this->files[] = tempnam(sys_get_temp_dir(), 'konto') . '.csv';
        file_put_contents($path, $csv);

        $this->assertSame(['new' => 1, 'found' => 1], app(RoyaltyPayments::class)->collectFromFile($path));
        $this->assertSame(['new' => 0, 'found' => 1], app(RoyaltyPayments::class)->collectFromFile($path), 'The same file again adds nothing');

        $payment = RoyaltyPayment::whereDate('paid_at', '2024-03-08')->sole();
        $this->assertSame([76.1, 80.75, 0.9424, 'file'], [(float) $payment->amount, (float) $payment->amount_foreign, (float) $payment->rate, $payment->origin]);

        // The page: upload, list, a payout from a bank import of an accounting
        $this->actingAs($this->user)->post('/admin/royalties/payments/upload', [
            'files' => [UploadedFile::fake()->createWithContent('Konto_2024.csv', $csv)],
        ])->assertSessionHas('success', '1 Rebeat-Zahlungen gefunden, davon 0 neu.');

        app(BankImporter::class)->import($this->accounting, $this->accounts['1003'], UploadedFile::fake()->createWithContent('Konto_2026.csv', implode("\r\n", [
            'IBAN;Booked At;Text;Credit/Debit Amount;Balance;Valuta Date',
            'CH00;2026-01-30 00:00:00.0;Gutschrift REBEAT Digital GmbH EUR 60.00, Umrechnungskurs 0.93;55.8;1055.8;2026-01-30 00:00:00.0',
        ]) . "\r\n"));
        $this->actingAs($this->user)->get('/admin/royalties/payments')->assertOk()
            ->assertSee('1 neue Zahlung(en) aus dem Bankimport')->assertSee('80.75')->assertSee('55.80');
        $this->assertNotNull(RoyaltyPayment::whereDate('paid_at', '2026-01-30')->value('bank_transaction_id'));
    }
}
