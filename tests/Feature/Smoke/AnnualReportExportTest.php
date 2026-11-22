<?php

namespace Tests\Feature\Smoke;

use App\Models\Account;
use App\Models\Accounting;
use App\Models\Booking;
use App\Models\Expense;
use App\Models\InvoiceTemplate;
use App\Models\Organization;
use App\Models\Setting;
use App\Models\User;
use App\Services\CarryForward;
use App\Services\Reports\AnnualReport;
use App\Services\Reports\AnnualReportReceipts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;
use ZipArchive;

/**
 * The Jahresrechnung for the tax declaration: figures grouped under the
 * header accounts, PDF, Excel and a ZIP with every receipt.
 */
class AnnualReportExportTest extends TestCase
{
    use RefreshDatabase;

    private Accounting $accounting;
    private Organization $owner;
    /** @var array<string, Account> */
    private array $accounts = [];
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('documents');
        Storage::fake('public');
        $this->user = User::factory()->create();

        $this->owner = Organization::create(['type' => 'band', 'names' => ['Testverein'], 'street' => 'Holderweg 20', 'zip' => '3000', 'city' => 'Bern', 'country' => 'Schweiz']);
        $this->accounting = Accounting::create([
            'accountable_type' => Organization::class, 'accountable_id' => $this->owner->id,
            'name' => 'Buchhaltung 2025', 'currency' => 'CHF', 'period_start' => '2025-01-01', 'period_end' => '2025-12-31',
        ]);
        foreach ([
            ['1000', 'Umlaufvermögen', 'asset', 0, true],
            ['1003', 'Bank', 'asset', 1000, false],
            ['2000', 'Fremdkapital', 'liability', 0, true],
            ['2001', 'Kreditoren', 'liability', 200, false],
            ['2800', 'Eigenkapital', 'liability', 0, true],
            ['2801', 'Vereinsvermögen', 'liability', 800, false],
            ['3000', 'Ertrag', 'income', 0, true],
            ['3001', 'Konzerte', 'income', 0, false],
            ['3002', 'Merch', 'income', 0, false],
            ['4000', 'Aufwand', 'expense', 0, true],
            ['4001', 'Proberaum', 'expense', 0, false],
            ['4002', 'Unbenutzt', 'expense', 0, false],
        ] as [$number, $name, $type, $opening, $header]) {
            $this->accounts[$number] = $this->accounting->accounts()->create([
                'number' => $number, 'name' => $name, 'type' => $type, 'opening_balance' => $opening, 'is_header' => $header,
            ]);
        }

        $this->book('2025-02-01', '1003', '3001', 600, 'Konzert Bern');
        $this->book('2025-03-01', '1003', '3002', 150, 'Merch');
        $rent = $this->book('2025-04-01', '4001', '1003', 300, 'Proberaum Q2');

        // The receipt hangs on the expense the payment settled
        $expense = Expense::create(['description' => 'Proberaum Q2', 'amount' => 300, 'currency' => 'CHF', 'expense_date' => '2025-04-01',
            'accounting_id' => $this->accounting->id, 'account_id' => $this->accounts['4001']->id, 'booking_id' => $rent->id]);
        Storage::disk('documents')->put('expenses/quittung.pdf', '%PDF-1.4 test');
        $expense->documents()->create(['title' => 'Quittung Proberäume.pdf', 'category' => 'invoice', 'file_path' => 'expenses/quittung.pdf']);
    }

    private function book(string $date, string $debit, string $credit, float $amount, string $text): Booking
    {
        return Booking::create([
            'accounting_id' => $this->accounting->id, 'booking_date' => $date, 'description' => $text,
            'debit_account_id' => $this->accounts[$debit]->id, 'credit_account_id' => $this->accounts[$credit]->id, 'amount' => $amount,
        ]);
    }

    public function test_the_figures_are_grouped_under_their_headers(): void
    {
        $report = app(AnnualReport::class)->build($this->accounting);

        $this->assertSame(450.0, $report['result'], '750 Ertrag - 300 Aufwand');
        $income = $report['sections']['income'];
        $this->assertSame('Ertrag', $income['groups'][0]['title']);
        $this->assertSame([['3001', 600.0], ['3002', 150.0]], array_map(fn ($l) => [$l['number'], $l['amount']], $income['groups'][0]['lines']));
        $this->assertSame(['4001'], array_column($report['sections']['expense']['groups'][0]['lines'], 'number'), 'Empty accounts are left out.');

        // Bank 1450 = Kreditoren 200 + Vereinsvermögen 800 + Mehrertrag 450
        $this->assertSame([1450.0, 1450.0, 0.0], [$report['total_assets'], $report['total_liabilities'], $report['difference']]);
        $this->assertSame([1250.0, 200.0], [$report['equity'], $report['debt']]);
        $this->assertSame(1000.0, $report['sections']['asset']['compare'], 'Compared with the opening.');
        $this->assertNull($report['sections']['income']['compare'], 'No previous year, no comparison.');
    }

    public function test_the_income_statement_compares_with_the_previous_year(): void
    {
        $next = app(CarryForward::class)->open($this->accounting->load('accounts'), [
            'name' => 'Buchhaltung 2026', 'period_start' => '2026-01-01', 'period_end' => '2026-12-31',
        ], $this->accounts['2801']);
        Booking::create(['accounting_id' => $next->id, 'booking_date' => '2026-02-01', 'description' => 'Konzert',
            'debit_account_id' => $next->accounts()->where('number', '1003')->value('id'),
            'credit_account_id' => $next->accounts()->where('number', '3001')->value('id'), 'amount' => 100]);

        $report = app(AnnualReport::class)->build($next);

        $lines = collect($report['sections']['income']['groups'][0]['lines'])->keyBy('number');
        $this->assertSame([100.0, 600.0], [$lines['3001']['amount'], $lines['3001']['compare']]);
        $this->assertSame([0.0, 150.0], [$lines['3002']['amount'], $lines['3002']['compare']], 'Kept for its previous-year figure.');
        $this->assertSame([100.0, 450.0], [$report['result'], $report['previous_result']]);
    }

    public function test_the_export_page_checks_the_year_and_offers_the_owners_logo(): void
    {
        Storage::disk('public')->put('invoice-templates/logo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='));
        InvoiceTemplate::create(['name' => 'Standard', 'slug' => 'standard', 'organization_id' => $this->owner->id, 'logo_path' => 'invoice-templates/logo.png']);

        $this->actingAs($this->user)->get("/admin/accountings/{$this->accounting->id}")->assertSee('Export Steuererklärung');
        $this->actingAs($this->user)->get("/admin/accountings/{$this->accounting->id}/export")
            ->assertOk()
            ->assertSee('Die Bilanz geht auf')
            ->assertSee('Logo der Rechnungsvorlage «Standard»', false)
            ->assertSee('Holderweg 20 · 3000 Bern')
            ->assertSee('1 Beleg');
    }

    public function test_the_pdf_downloads_and_the_options_are_remembered(): void
    {
        $response = $this->actingAs($this->user)->get("/admin/accountings/{$this->accounting->id}/export/download?" . http_build_query([
            'format' => 'pdf', 'logo' => '', 'address' => 1, 'signatures' => 1,
            'signature_labels' => "Präsidentin\nKassier\nRevisor", 'signature_note' => 'Genehmigt am',
        ]));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Jahresrechnung_Testverein_Buchhaltung-2025.pdf', $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF', $response->getContent());

        $saved = Setting::get('annual_report.organization.' . $this->owner->id);
        $this->assertSame("Präsidentin\nKassier\nRevisor", $saved['signature_labels']);
        $this->actingAs($this->user)->get("/admin/accountings/{$this->accounting->id}/export")->assertSee('Revisor');
    }

    public function test_the_pdf_view_shows_the_report(): void
    {
        $export = app(\App\Services\Reports\AnnualReportExport::class);
        $data = $export->data($this->accounting, ['logo' => '', 'address' => true, 'signatures' => true,
            'signature_labels' => "Präsident/in\nKassier/in", 'signature_note' => 'Von der Generalversammlung genehmigt am']);

        $html = view('admin.accountings.exports.annual-report', $data)->render();

        // Passiven has two groups, so they get subtotals; Aktiven has one, its total says it all
        foreach (['Jahresrechnung 2025', 'Bilanz per 31.12.2025', '31.12.2024', 'Total Fremdkapital', 'Total Eigenkapital', 'Jahresergebnis (Mehrertrag)',
                  '1&#039;450.00', 'Angaben für die Steuererklärung', 'Holderweg 20', 'Kassier/in', 'Von der Generalversammlung genehmigt am'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
        $this->assertStringNotContainsString('Unbenutzt', $html);
    }

    public function test_the_workbook_has_all_sheets(): void
    {
        $response = $this->actingAs($this->user)->get("/admin/accountings/{$this->accounting->id}/export/download?format=xlsx&address=0&signatures=0");
        $response->assertOk();

        $book = IOFactory::load($response->getFile()->getPathname());
        $this->assertSame(['Bilanz', 'Erfolgsrechnung', 'Journal', 'Kontoblätter', 'Belege'], $book->getSheetNames());

        $journal = $book->getSheetByName('Journal')->toArray(null, true, false);
        $this->assertEquals([1, '01.02.2025', 'Konzert Bern'], array_slice($journal[4], 0, 3));
        $this->assertStringContainsString('Belege/0003_2025-04-01_Quittung-Proberaeume.pdf', $journal[6][7]);

        $ledger = collect($book->getSheetByName('Kontoblätter')->toArray(null, true, false))->first(fn ($row) => $row[2] === 'Schlusssaldo');
        $this->assertEquals(1450, $ledger[6], 'Bank: 1000 + 600 + 150 - 300');

        // Deleted after sending in real life; a test never sends
        unlink($response->getFile()->getPathname());
    }

    public function test_the_zip_holds_report_workbook_and_receipts(): void
    {
        $response = $this->actingAs($this->user)->get("/admin/accountings/{$this->accounting->id}/export/download?format=zip&address=1&signatures=0");
        $response->assertOk();

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($response->getFile()->getPathname()));
        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) $names[] = $zip->getNameIndex($i);
        sort($names);

        $this->assertSame([
            'Belege/0003_2025-04-01_Quittung-Proberaeume.pdf',
            'Jahresrechnung_Testverein_Buchhaltung-2025.pdf',
            'Jahresrechnung_Testverein_Buchhaltung-2025.xlsx',
        ], $names);
        $this->assertSame('%PDF-1.4 test', $zip->getFromName('Belege/0003_2025-04-01_Quittung-Proberaeume.pdf'));
        $zip->close();

        // Deleted after sending in real life; a test never sends
        unlink($response->getFile()->getPathname());
        $this->assertSame([], glob(storage_path('app/temp/*.{pdf,xlsx}'), GLOB_BRACE), 'The PDF and workbook packed into the ZIP are gone.');
    }

    public function test_a_big_logo_is_scaled_down_for_print(): void
    {
        $image = imagecreatetruecolor(2000, 3000);
        ob_start();
        imagepng($image);
        Storage::disk('public')->put('avatars/organizations/big.png', ob_get_clean());
        $this->owner->update(['avatar_path' => 'avatars/organizations/big.png']);

        $export = app(\App\Services\Reports\AnnualReportExport::class);
        $data = $export->data($this->accounting->fresh(), [
            'logo' => 'avatar', 'address' => false, 'signatures' => false, 'signature_labels' => '', 'signature_note' => '',
        ]);
        $this->assertNull($data['logoFile'], 'The export page does not scale the logo - only the PDF does.');

        $logo = $export->printLogo($data['logoSource']);
        $this->assertStringStartsWith('data:image/png;base64,', $logo);
        $size = getimagesizefromstring(base64_decode(substr($logo, strlen('data:image/png;base64,'))));
        $this->assertSame([200, 300], [$size[0], $size[1]], 'Fits 900 x 300, keeps its proportions.');
    }

    public function test_file_names_stay_plain(): void
    {
        $this->assertSame('Quittung-Raeume-Bern', AnnualReportReceipts::fileName('Quittung «Räume» / Bern'));
    }
}
