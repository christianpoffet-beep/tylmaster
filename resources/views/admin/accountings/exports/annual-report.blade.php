@php
    $fmt = fn ($v) => $v === null ? '' : number_format($v, 2, '.', "'");
    $start = $accounting->period_start;
    $end = $accounting->period_end;
    // A calendar year is "2025", anything else its dates
    $periodLabel = $start->format('m-d') === '01-01' && $end->format('m-d') === '12-31' && $start->year === $end->year
        ? (string) $start->year
        : $start->format('d.m.Y') . ' – ' . $end->format('d.m.Y');
    $openingLabel = $start->copy()->subDay()->format('d.m.Y');
    $hasPrevious = (bool) $previous;
    $resultLabel = \App\Services\Reports\AnnualReport::resultLabel($result);
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<title>Jahresrechnung {{ $periodLabel }} – {{ $accounting->accountable_name }}</title>
<style>
    @page { margin: 1.8cm 2cm 2.2cm 2cm; }
    body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 9pt; color: #1f2937; }
    .footer { position: fixed; bottom: -1.4cm; left: 0; right: 0; font-size: 7pt; color: #9ca3af; }
    .footer td { padding: 0; }
    .page-number:after { content: "Seite " counter(page); }
    .letterhead { width: 100%; margin-bottom: 1cm; }
    .letterhead td { vertical-align: top; padding: 0; }
    .address { font-size: 9pt; line-height: 1.4; }
    .address .name { font-weight: bold; }
    .logo { max-height: 2.2cm; max-width: 6cm; }
    h1 { font-size: 20pt; margin: 0; }
    .subtitle { color: #6b7280; margin: 3pt 0 0; }
    h2 { font-size: 12pt; margin: 22pt 0 6pt; padding-bottom: 3pt; border-bottom: 1pt solid #1f2937; }
    table.report { width: 100%; border-collapse: collapse; }
    table.report th { font-size: 7.5pt; font-weight: normal; color: #6b7280; text-align: right; padding: 0 4pt 3pt; border-bottom: 0.5pt solid #9ca3af; }
    table.report td { padding: 1.6pt 4pt; vertical-align: top; }
    table.report tr { page-break-inside: avoid; }
    .nr { width: 1.2cm; color: #6b7280; }
    .num { width: 2.7cm; text-align: right; white-space: nowrap; }
    .compare { color: #6b7280; }
    tr.section td { font-weight: bold; font-size: 10pt; padding-top: 10pt; }
    tr.group td { font-weight: bold; padding-top: 6pt; }
    tr.subtotal td { font-style: italic; border-top: 0.5pt solid #d1d5db; }
    tr.total td { font-weight: bold; border-top: 0.8pt solid #1f2937; }
    tr.result td { font-weight: bold; font-size: 10pt; border-top: 0.8pt solid #1f2937; border-bottom: 2pt double #1f2937; padding-top: 4pt; padding-bottom: 4pt; }
    .muted { color: #9ca3af; }
    .warning { color: #b91c1c; margin-top: 6pt; }
    .facts { width: 100%; border-collapse: collapse; margin-top: 4pt; }
    .facts td { padding: 3pt 4pt; border-bottom: 0.5pt solid #e5e7eb; }
    .facts .num { width: 3.2cm; }
    .note { font-size: 7.5pt; color: #6b7280; margin-top: 6pt; }
    .page-break { page-break-before: always; }
    .signatures { width: 100%; margin-top: 0.4cm; border-collapse: collapse; }
    .signatures td { width: 50%; padding: 1.4cm 0.6cm 0 0; vertical-align: bottom; }
    .signature-line { border-top: 0.5pt solid #1f2937; padding-top: 3pt; font-size: 8pt; color: #4b5563; }
    .keep { page-break-inside: avoid; }
</style>
</head>
<body>

<div class="footer">
    <table width="100%"><tr>
        <td>{{ $accounting->accountable_name }} · Jahresrechnung {{ $periodLabel }} · erstellt am {{ $generatedAt->format('d.m.Y') }}</td>
        <td align="right" class="page-number"></td>
    </tr></table>
</div>

@if($addressLines || $logoFile)
    <table class="letterhead"><tr>
        <td class="address">
            @foreach($addressLines as $line)
                <div class="{{ $loop->first ? 'name' : '' }}">{{ $line }}</div>
            @endforeach
        </td>
        <td align="right">
            @if($logoFile)<img src="{{ $logoFile }}" class="logo" alt="">@endif
        </td>
    </tr></table>
@endif

<h1>Jahresrechnung {{ $periodLabel }}</h1>
<p class="subtitle">{{ $accounting->accountable_name }} · {{ $accounting->name }} · {{ $start->format('d.m.Y') }} – {{ $end->format('d.m.Y') }} · Beträge in {{ $accounting->currency }}</p>

{{-- Bilanz --}}
<h2>Bilanz per {{ $end->format('d.m.Y') }}</h2>
<table class="report">
    <thead><tr><th></th><th></th><th>{{ $end->format('d.m.Y') }}</th><th>{{ $openingLabel }}</th></tr></thead>
    <tbody>
        @include('admin.accountings.exports._section', ['section' => $sections['asset'], 'title' => 'Aktiven', 'withCompare' => true])
        @include('admin.accountings.exports._section', ['section' => $sections['liability'], 'title' => 'Passiven', 'withCompare' => true, 'withTotal' => false])
        <tr>
            <td></td>
            <td>Jahresergebnis ({{ $resultLabel }})</td>
            <td class="num">{{ $fmt($result) }}</td>
            <td class="num compare"></td>
        </tr>
        <tr class="total">
            <td></td>
            <td>Total Passiven</td>
            <td class="num">{{ $fmt($total_liabilities) }}</td>
            <td class="num compare">{{ $fmt($sections['liability']['compare']) }}</td>
        </tr>
    </tbody>
</table>
@if(abs($difference) >= 0.005)
    <p class="warning">Die Bilanz geht um {{ $fmt($difference) }} {{ $accounting->currency }} nicht auf – die Eröffnungssaldi sind nicht ausgeglichen.</p>
@endif

{{-- Erfolgsrechnung --}}
<div class="page-break"></div>
<h2>Erfolgsrechnung {{ $start->format('d.m.Y') }} – {{ $end->format('d.m.Y') }}</h2>
<table class="report">
    <thead><tr><th></th><th></th><th>{{ $periodLabel }}</th>@if($hasPrevious)<th>{{ $previous->name }}</th>@endif</tr></thead>
    <tbody>
        @include('admin.accountings.exports._section', ['section' => $sections['income'], 'title' => 'Ertrag', 'withCompare' => $hasPrevious])
        @include('admin.accountings.exports._section', ['section' => $sections['expense'], 'title' => 'Aufwand', 'withCompare' => $hasPrevious])
        <tr class="result">
            <td></td>
            <td>Jahresergebnis ({{ $resultLabel }})</td>
            <td class="num">{{ $fmt($result) }}</td>
            @if($hasPrevious)<td class="num compare">{{ $fmt($previous_result) }}</td>@endif
        </tr>
    </tbody>
</table>

{{-- Angaben für die Steuererklärung --}}
<div class="keep">
    <h2>Angaben für die Steuererklärung</h2>
    <table class="facts">
        <tr><td>Jahresergebnis {{ $periodLabel }} ({{ $resultLabel }})</td><td class="num">{{ $fmt($result) }}</td></tr>
        <tr><td>Eigenkapital per {{ $end->format('d.m.Y') }} (Konten 28xx–29xx, inkl. Jahresergebnis)</td><td class="num">{{ $fmt($equity) }}</td></tr>
        <tr><td>Fremdkapital per {{ $end->format('d.m.Y') }}</td><td class="num">{{ $fmt($debt) }}</td></tr>
        <tr><td>Bilanzsumme per {{ $end->format('d.m.Y') }}</td><td class="num">{{ $fmt($total_assets) }}</td></tr>
    </table>
    <p class="note">Beträge in {{ $accounting->currency }}. Grundlage sind {{ $journal->count() }} Buchungen; Journal, Kontoblätter und Belegverzeichnis liegen als Excel-Datei bei.</p>
</div>

{{-- Unterschriften --}}
@if($signatureLabels)
    <div class="keep">
        <h2>Visum</h2>
        @if(trim((string) ($options['signature_note'] ?? '')) !== '')
            <table width="100%" style="margin-top: 0.6cm;"><tr>
                <td style="white-space: nowrap; padding-right: 6pt;">{{ $options['signature_note'] }}</td>
                <td style="border-bottom: 0.5pt solid #1f2937; width: 100%;">&nbsp;</td>
            </tr></table>
        @endif
        <table class="signatures">
            @foreach(array_chunk($signatureLabels, 2) as $pair)
                <tr>
                    @foreach($pair as $label)
                        <td><div class="signature-line">Ort, Datum, Unterschrift · {{ $label }}</div></td>
                    @endforeach
                    @if(count($pair) === 1)<td></td>@endif
                </tr>
            @endforeach
        </table>
    </div>
@endif

</body>
</html>
