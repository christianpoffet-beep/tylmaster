<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Accounting;
use App\Models\BankTransaction;
use App\Services\CarryForward;
use App\Services\DocumentStorage;
use App\Services\Reports\AnnualReportExport;
use Illuminate\Http\Request;

/** Jahresrechnung for the tax declaration: a page to check and set it up, and the downloads. */
class AnnualReportController extends Controller
{
    public function show(Accounting $accounting, AnnualReportExport $export, CarryForward $carryForward)
    {
        $options = $export->options($accounting);
        $data = $export->data($accounting, $options);

        return view('admin.accountings.export', $data + [
            'logos' => $export->logos($accounting),
            'address' => $export->addressLines($accounting),
            'openBankCount' => $accounting->bankTransactions()->where('status', BankTransaction::OPEN)->count(),
            'openingDifferences' => $carryForward->differences($accounting),
        ]);
    }

    public function download(Request $request, Accounting $accounting, AnnualReportExport $export)
    {
        $input = $request->validate([
            'format' => 'required|in:pdf,xlsx,zip',
            'logo' => 'nullable|string|max:50',
            'address' => 'nullable|boolean',
            'signatures' => 'nullable|boolean',
            'signature_labels' => 'nullable|string|max:500',
            'signature_note' => 'nullable|string|max:200',
        ]);

        $logos = $export->logos($accounting);
        $options = [
            'logo' => isset($logos[$input['logo'] ?? '']) ? $input['logo'] : '',
            'address' => $request->boolean('address'),
            'signatures' => $request->boolean('signatures'),
            'signature_labels' => $input['signature_labels'] ?? '',
            'signature_note' => $input['signature_note'] ?? '',
        ];
        $export->remember($accounting, $options);

        // Ledgers of a busy year in dompdf and PhpSpreadsheet take some memory
        ini_set('memory_limit', '512M');
        $data = $export->data($accounting, $options);
        $base = $export->baseName($accounting);

        return match ($input['format']) {
            'pdf' => $export->pdf($data)->download("{$base}.pdf"),
            'xlsx' => $this->sendTemporary($this->xlsx($export, $data), "{$base}.xlsx"),
            'zip' => $this->sendTemporary($export->zip($data), "{$base}.zip"),
        };
    }

    private function xlsx(AnnualReportExport $export, array $data): string
    {
        $dir = storage_path('app/temp');
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $path = $dir . '/' . \Illuminate\Support\Str::random(8) . '.xlsx';
        $export->xlsx($data, $path);

        return $path;
    }

    private function sendTemporary(string $path, string $name)
    {
        return DocumentStorage::privately(response()->download($path, $name)->deleteFileAfterSend());
    }
}
