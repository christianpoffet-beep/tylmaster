<?php

namespace App\Services\Reports;

use App\Models\Accounting;
use App\Models\InvoiceTemplate;
use App\Models\Organization;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Jahresrechnung for the tax declaration: PDF (balance sheet, income
 * statement, figures for the declaration, optional letterhead and signature
 * lines), Excel (plus journal, ledgers, receipt list) and a ZIP with both and
 * every receipt and bank statement.
 *
 * The letterhead options are remembered per owner, so next year's export
 * looks the same without setting it up again.
 */
class AnnualReportExport
{
    public const DEFAULTS = [
        'logo' => null,
        'address' => true,
        'signatures' => true,
        'signature_labels' => "Präsident/in\nKassier/in",
        'signature_note' => 'Von der Generalversammlung genehmigt am',
    ];

    private const IMAGE_TYPES = ['png', 'jpg', 'jpeg', 'gif'];

    public function __construct(
        private AnnualReport $report,
        private AnnualReportReceipts $receipts,
        private AnnualReportSpreadsheet $spreadsheet,
    ) {}

    /** Everything the PDF, the workbook and the export page show. */
    public function data(Accounting $accounting, array $options): array
    {
        $logos = $this->logos($accounting);

        return $this->report->build($accounting) + $this->receipts->collect($accounting) + [
            'options' => $options,
            'logoFile' => $this->printLogo($logos[$options['logo'] ?? '']['file'] ?? null),
            'addressLines' => $options['address'] ? $this->addressLines($accounting) : [],
            'signatureLabels' => $options['signatures'] ? self::lines($options['signature_labels']) : [],
            // The app runs in UTC; the date on the paper is a Swiss one
            'generatedAt' => now('Europe/Zurich'),
        ];
    }

    // --- options -----------------------------------------------------------

    /** The owner's remembered options, the first logo it has by default. */
    public function options(Accounting $accounting): array
    {
        $saved = Setting::get($this->settingKey($accounting), []);
        $options = array_merge(self::DEFAULTS, is_array($saved) ? array_intersect_key($saved, self::DEFAULTS) : []);

        $logos = $this->logos($accounting);
        if ($options['logo'] !== '' && !isset($logos[$options['logo']])) {
            $options['logo'] = array_key_first($logos);
        }

        return $options;
    }

    public function remember(Accounting $accounting, array $options): void
    {
        Setting::set($this->settingKey($accounting), array_intersect_key($options, self::DEFAULTS));
    }

    /**
     * Logos the owner already has - the ones on its invoice templates and its
     * profile picture - as key => label and file.
     *
     * @return array<string, array{label: string, file: string}>
     */
    public function logos(Accounting $accounting): array
    {
        $owner = $accounting->accountable;
        if (!$owner) return [];

        $logos = [];
        $column = $owner instanceof Organization ? 'organization_id' : 'contact_id';
        foreach (InvoiceTemplate::where($column, $owner->id)->orderBy('name')->get() as $template) {
            if ($file = $this->image($template->effective_logo_path)) {
                $logos["template:{$template->id}"] = ['label' => "Logo der Rechnungsvorlage «{$template->name}»", 'file' => $file];
            }
        }
        if ($file = $this->image($owner->avatar_path)) {
            $logos['avatar'] = ['label' => 'Profilbild von ' . $accounting->accountable_name, 'file' => $file];
        }

        // The same picture twice (a template using the profile picture) is offered once
        return collect($logos)->unique('file')->all();
    }

    /** @return list<string> */
    public function addressLines(Accounting $accounting): array
    {
        $owner = $accounting->accountable;
        if (!$owner) return [];

        $country = trim((string) $owner->country);
        $lines = [
            $accounting->accountable_name,
            trim((string) $owner->street),
            trim($owner->zip . ' ' . $owner->city),
            in_array(mb_strtolower($country), ['', 'ch', 'schweiz', 'switzerland', 'suisse', 'svizzera']) ? '' : $country,
        ];

        return array_values(array_filter($lines, fn ($line) => $line !== ''));
    }

    // --- files -------------------------------------------------------------

    public function pdf(array $data): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView('admin.accountings.exports.annual-report', $data)->setPaper('A4', 'portrait');
    }

    public function xlsx(array $data, string $path): void
    {
        $this->spreadsheet->write($data, $path);
    }

    /** PDF, workbook, receipts and bank statements in one archive; returns its path. */
    public function zip(array $data): string
    {
        $base = $this->baseName($data['accounting']);
        $dir = storage_path('app/temp');
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $token = Str::random(8);
        $pdf = "{$dir}/{$token}.pdf";
        $xlsx = "{$dir}/{$token}.xlsx";
        $zipPath = "{$dir}/{$token}.zip";
        file_put_contents($pdf, $this->pdf($data)->output());
        $this->xlsx($data, $xlsx);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Das ZIP liess sich nicht anlegen.');
        }
        $zip->addFile($pdf, "{$base}.pdf");
        $zip->addFile($xlsx, "{$base}.xlsx");
        $added = [];
        foreach ($data['receipts'] as $receipts) {
            foreach ($receipts as $receipt) {
                if ($receipt['file'] && !isset($added[$receipt['path']])) {
                    $zip->addFile($receipt['file'], $receipt['path']);
                    $added[$receipt['path']] = true;
                }
            }
        }
        foreach ($data['statements'] as $statement) {
            if (!isset($added[$statement['path']])) {
                $zip->addFile($statement['file'], $statement['path']);
                $added[$statement['path']] = true;
            }
        }
        $zip->close(); // reads the files - only now may the temporary ones go

        @unlink($pdf);
        @unlink($xlsx);

        return $zipPath;
    }

    /** "Jahresrechnung_TYL_Buchhaltung-2025" */
    public function baseName(Accounting $accounting): string
    {
        return 'Jahresrechnung_' . AnnualReportReceipts::fileName($accounting->accountable_name . '_' . $accounting->name, 80);
    }

    /** @return list<string> */
    public static function lines(?string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $text)), fn ($l) => $l !== ''));
    }

    /**
     * The logo at print size (at most 900 x 300 px) as a data URI. A profile
     * picture straight from the camera would make dompdf slow and the PDF big.
     */
    private function printLogo(?string $file): ?string
    {
        if (!$file) return null;
        $size = @getimagesize($file);
        if (!$size) return null;

        [$width, $height] = $size;
        $scale = min(1, 900 / max($width, 1), 300 / max($height, 1));
        if (($scale >= 1 && filesize($file) < 300 * 1024) || !function_exists('imagecreatefromstring')) {
            return $file;
        }

        $source = @imagecreatefromstring(file_get_contents($file));
        if (!$source) return $file;

        $w = max(1, (int) round($width * $scale));
        $h = max(1, (int) round($height * $scale));
        $target = imagecreatetruecolor($w, $h);
        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagefill($target, 0, 0, imagecolorallocatealpha($target, 0, 0, 0, 127));
        imagecopyresampled($target, $source, 0, 0, 0, 0, $w, $h, $width, $height);

        ob_start();
        imagepng($target, null, 9);
        $png = ob_get_clean();
        imagedestroy($source);
        imagedestroy($target);

        return 'data:image/png;base64,' . base64_encode($png);
    }

    private function image(?string $path): ?string
    {
        if (!$path || !in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), self::IMAGE_TYPES, true)) return null;
        $disk = Storage::disk('public');

        return $disk->exists($path) ? $disk->path($path) : null;
    }

    private function settingKey(Accounting $accounting): string
    {
        return 'annual_report.' . Str::snake(class_basename($accounting->accountable_type)) . '.' . $accounting->accountable_id;
    }
}
