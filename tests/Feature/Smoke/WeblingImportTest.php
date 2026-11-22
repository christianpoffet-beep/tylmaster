<?php

namespace Tests\Feature\Smoke;

use App\Models\Accounting;
use App\Models\Booking;
use App\Models\ChartTemplate;
use App\Models\ChartTemplateAccount;
use App\Models\Organization;
use App\Models\User;
use App\Services\CarryForward;
use App\Services\Webling\WeblingImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * A year from Webling's "Bilanz und Erfolgsrechnung" export: chart matched to
 * a template, opening balances, one collective booking per account - and
 * the next year opens from it. Made-up figures in Webling's layout.
 */
class WeblingImportTest extends TestCase
{
    use RefreshDatabase;

    /** fromArray() skips cells loosely equal to its "null value" - with null that would drop every 0, Webling writes them */
    private const KEEP_ZEROS = '__empty__';

    private Organization $owner;
    private ChartTemplate $template;
    private string $file;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = Organization::create(['type' => 'label', 'names' => ['Testlabel']]);

        $this->template = ChartTemplate::create(['name' => 'Musiklabel / Verein', 'slug' => 'musiklabel-verein']);
        foreach ([
            ['1000', 'Umlaufvermögen', 'asset', true], ['1001', 'Kasse', 'asset', false], ['1003', 'Bank', 'asset', false], ['1004', 'PayPal', 'asset', false],
            ['2000', 'Fremdkapital', 'liability', true], ['2001', 'Kreditoren', 'liability', false],
            ['2800', 'Eigenkapital', 'liability', true], ['2801', 'Eigenkapital', 'liability', false],
            ['3300', 'Musik Ertrag', 'income', true], ['3301', 'Rebeat - Streaming & Download', 'income', false], ['3307', 'SWISSPERFORM', 'income', false],
            ['4000', 'Administrativer Aufwand', 'expense', true], ['4010', 'Gebühren Kontoführung Bank', 'expense', false],
            ['6000', 'Musik Aufwand', 'expense', true], ['6004', 'Herstellungskosten Tonträger', 'expense', false],
        ] as $i => [$number, $name, $type, $header]) {
            ChartTemplateAccount::create(['chart_template_id' => $this->template->id, 'number' => $number, 'name' => $name,
                'type' => $type, 'is_header' => $header, 'sort_order' => $i]);
        }

        $this->file = $this->weblingExport();
    }

    protected function tearDown(): void
    {
        @unlink($this->file);
        parent::tearDown();
    }

    /**
     * Webling's layout: two sides per sheet, groups as rows without numbers,
     * subtotals without a name. Opening: Bank 1000, Kasse 50 / Kreditoren 100,
     * Eigenkapital 950. The year: income 700, expenses 900 (loss 200), of
     * which 120 still owed (Kreditoren 220): Bank 1000 + 700 - 900 + 120 = 920.
     */
    private function weblingExport(): string
    {
        $book = new Spreadsheet();

        $er = $book->getActiveSheet()->setTitle('Erfolgsrechnung');
        $er->fromArray([
            [null, 'Erfolgsrechnung Buchungsperiode 2024 (01.01.2024 - 31.12.2024)'],
            [],
            [null, 'Aufwand', 'Aktuell', '%', 'Vorjahr', '%', null, 'Ertrag', 'Aktuell', '%', 'Vorjahr', '%'],
            [null, 'Administrativer Aufwand', null, null, null, null, null, 'Musik'],
            [null, 'Gebührenbelastung Kontoführung - Bank', null, 0, 0, 0, null, 'Rebeat GmbH Streaming & Download income', 400, 57, 0, 0],
            [null, 'Proberaum', 150, 17, 0, 0, null, 'SWISSPERFORM', 300, 43, 0, 0],
            [null, null, 150, 17, 0, 0, null, null, 700, 100, 0, 0],
            [null, 'Musik', null, null, null, null, null, 'Total Ertrag', 700, null, 0],
            [null, 'Herstellungskosten für Tonträger', 750, 83, 0, 0],
            [null, null, 750, 83, 0, 0],
            [null, 'Total Aufwand', 900, null, 0],
            [null, 'Gewinn', null, null, 0, null, null, 'Verlust', 200],
        ], self::KEEP_ZEROS, 'A2');

        $bilanz = $book->createSheet()->setTitle('Bilanz');
        $bilanz->fromArray([
            [null, 'Bilanz Buchungsperiode 2024 vom 31.12.2024'],
            [],
            [null, 'Aktiv', 'Aktuell', '%', 'Vorjahr', '%', null, 'Passiv', 'Aktuell', '%', 'Vorjahr', '%'],
            [null, 'Umlaufvermögen', null, null, null, null, null, 'Fremdkapital'],
            [null, 'Kasse', 50, 5, 50, 5, null, 'Kreditoren', 220, 19, 100, 10],
            [null, 'Bank', 920, 95, 1000, 95, null, null, 220, 19, 100, 10],
            [null, 'Pay Pal', 0, 0, 0, 0, null, 'Eigenkapital'],
            [null, null, 970, 100, 1050, 100, null, 'Eigenkapital', 950, 81, 900, 90],
            [null, 'Total Aktive', 970, null, 1050, null, null, null, 950, 81, 900, 90],
            [null, 'Verlust', 200, null, null, null, null, 'Total Passive', 1170, null, 1000],
        ], self::KEEP_ZEROS, 'A2');

        $opening = $book->createSheet()->setTitle('Eröffnungsbilanz');
        $opening->fromArray([
            [null, 'Eröffnungsbilanz Buchungsperiode 2024 vom 01.01.2024'],
            [],
            [null, 'Aktiv', null, null, 'Passiv'],
            [null, 'Umlaufvermögen', null, null, 'Fremdkapital'],
            [null, 'Kasse', 50, null, 'Kreditoren', 100],
            [null, 'Bank', 1000, null, null, 100],
            [null, 'Pay Pal', 0, null, 'Eigenkapital'],
            [null, null, 1050, null, 'Eigenkapital', 950],
            [null, 'Total Aktive', 1050, null, 'Total Passive', 1050],
        ], self::KEEP_ZEROS, 'A2');

        $path = tempnam(sys_get_temp_dir(), 'webling') . '.xlsx';
        (new Xlsx($book))->save($path);

        return $path;
    }

    private function openings(Accounting $accounting): array
    {
        return $accounting->accounts()->where('opening_balance', '!=', 0)->pluck('opening_balance', 'number')
            ->map(fn ($v) => (float) $v)->all();
    }

    public function test_a_webling_year_becomes_an_accounting_that_matches_its_figures(): void
    {
        $result = app(WeblingImporter::class)->import($this->file, $this->owner, 'Label 2024', $this->template);
        $accounting = $result['accounting'];

        $this->assertSame(['2024-01-01', '2024-12-31'], [$accounting->period_start->toDateString(), $accounting->period_end->toDateString()]);
        $this->assertSame([], $result['warnings'], 'Closing balances and result equal Webling.');
        $this->assertSame(['Proberaum'], $result['added'], 'Only the account the template does not know is new.');

        // Matched by similar names, into the template's numbers
        $numbers = $accounting->accounts()->pluck('number', 'name')->all();
        $this->assertSame('4010', $numbers['Gebühren Kontoführung Bank']);
        $this->assertSame('6004', $numbers['Herstellungskosten Tonträger']);
        $this->assertSame('3301', $numbers['Rebeat - Streaming & Download']);
        $this->assertSame('4001', $numbers['Proberaum'], 'Added to its group "Administrativer Aufwand".');

        $this->assertSame(['1001' => 50.0, '1003' => 1000.0, '2001' => 100.0, '2801' => 950.0], $this->openings($accounting));

        $report = app(\App\Services\Reports\AnnualReport::class)->build($accounting);
        $this->assertSame([-200.0, 970.0, 0.0], [$report['result'], $report['total_assets'], $report['difference']]);

        // One collective booking per account, all against the bank, on the last day
        $this->assertSame(5, Booking::count(), 'Proberaum, Tonträger, Rebeat, SWISSPERFORM, Kreditoren');
        $this->assertSame(['2024-12-31'], Booking::pluck('booking_date')->map(fn ($d) => $d->toDateString())->unique()->values()->all());
    }

    public function test_the_next_year_opens_from_the_imported_one(): void
    {
        $previous = app(WeblingImporter::class)->import($this->file, $this->owner, 'Label 2024', $this->template)['accounting'];

        $next = app(CarryForward::class)->open($previous->load('accounts'), [
            'name' => 'Label 2025', 'period_start' => '2025-01-01', 'period_end' => '2025-12-31',
        ], $previous->accounts->firstWhere('number', '2801'));

        $this->assertSame(['1001' => 50.0, '1003' => 920.0, '2001' => 220.0, '2801' => 750.0], $this->openings($next),
            'The loss of 200 lowers the equity.');
    }

    public function test_the_import_page_uploads_and_reports_errors(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/admin/accountings')->assertSee('Aus Webling importieren');
        $this->actingAs($user)->get('/admin/accountings/import-webling')->assertOk()
            ->assertSee('Organisation oder Person suchen')->assertSee('Musiklabel / Verein');

        $this->actingAs($user)->post('/admin/accountings/import-webling', [
            'file' => new UploadedFile($this->file, 'Bilanz und Erfolgsrechnung 2024.xlsx', null, null, true),
            'accountable_type' => 'organization', 'accountable_id' => $this->owner->id,
            'name' => '', 'chart_template_id' => $this->template->id,
        ])->assertSessionHas('success');

        $accounting = Accounting::sole();
        $this->assertSame('Buchhaltung 2024 (Webling)', $accounting->name);
        $this->assertStringContainsString('Bilanz und Erfolgsrechnung 2024.xlsx', $accounting->notes);

        // A workbook that is not a Webling export
        $other = new Spreadsheet();
        $path = tempnam(sys_get_temp_dir(), 'other') . '.xlsx';
        (new Xlsx($other))->save($path);
        $this->actingAs($user)->post('/admin/accountings/import-webling', [
            'file' => new UploadedFile($path, 'Liste.xlsx', null, null, true),
            'accountable_type' => 'organization', 'accountable_id' => $this->owner->id,
        ])->assertSessionHasErrors('file');
        @unlink($path);
        $this->assertSame(1, Accounting::count());
    }

    public function test_without_a_template_the_chart_comes_from_webling(): void
    {
        $result = app(WeblingImporter::class)->import($this->file, $this->owner, null, null);
        $accounting = $result['accounting'];

        $this->assertSame([], $result['warnings']);
        $headers = $accounting->accounts()->where('is_header', true)->pluck('name', 'number')->all();
        $this->assertSame('Eigenkapital', $headers['2800'], 'Equity lands in class 28.');
        $this->assertSame(970.0, app(\App\Services\Reports\AnnualReport::class)->build($accounting)['total_assets']);
    }

    public function test_names_are_compared_by_their_words(): void
    {
        $this->assertNotNull(WeblingImporter::similarity('Pay Pal', 'PayPal'));
        $this->assertNotNull(WeblingImporter::similarity('SUISA Aufwand (Urheberrechtsentschädigung, etc.)', 'SUISA Aufwand'));
        $this->assertNull(WeblingImporter::similarity('Physische Verkäufe CD', 'Physische Verkäufe Vinyl'));
        $this->assertGreaterThan(WeblingImporter::similarity('Fotografie', 'Fotografie Ertrag'), WeblingImporter::similarity('Ertrag', 'Ertrag'));
    }
}
