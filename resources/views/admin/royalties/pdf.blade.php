@php
    $chf = fn ($v) => number_format((float) $v, 2, '.', "'");
    $int = fn ($v) => number_format((int) $v, 0, '.', "'");
    $pct = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.') . ' %';
    $period = $statement->period_from->format('d.m.Y') . ' – ' . $statement->period_to->format('d.m.Y');
    $sender = $senderLines[0] ?? 'The yelling light';
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<title>Abrechnung {{ $period }} – {{ $party->name }}</title>
<style>
    @page { margin: 1.8cm 2cm 2.2cm 2cm; }
    body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 9pt; color: #1f2937; }
    .footer { position: fixed; bottom: -1.4cm; left: 0; right: 0; font-size: 7pt; color: #9ca3af; }
    .footer td { padding: 0; }
    .page-number:after { content: "Seite " counter(page); }
    .draft { position: fixed; top: 9cm; left: 0; right: 0; text-align: center; font-size: 72pt; color: #f3f4f6; transform: rotate(-30deg); z-index: -1; }
    .letterhead { width: 100%; margin-bottom: 1.2cm; }
    .letterhead td { vertical-align: top; padding: 0; }
    .address { line-height: 1.4; }
    .address .name { font-weight: bold; }
    .logo { max-height: 2.2cm; max-width: 6cm; }
    .recipient { margin: 0 0 1cm; line-height: 1.4; }
    h1 { font-size: 16pt; margin: 0; }
    .subtitle { color: #6b7280; margin: 3pt 0 12pt; }
    p { margin: 0 0 6pt; line-height: 1.45; }
    h2 { font-size: 10.5pt; margin: 16pt 0 4pt; }
    .contract-note { color: #6b7280; font-size: 8pt; margin-bottom: 4pt; }
    table.lines { width: 100%; border-collapse: collapse; }
    table.lines th { font-size: 7.5pt; font-weight: normal; color: #6b7280; text-align: right; padding: 0 4pt 3pt; border-bottom: 0.5pt solid #9ca3af; }
    table.lines th:first-child { text-align: left; }
    table.lines td { padding: 1.6pt 4pt; vertical-align: top; }
    table.lines tr { page-break-inside: avoid; }
    .num { text-align: right; white-space: nowrap; width: 2.3cm; }
    tr.sum td { border-top: 0.5pt solid #9ca3af; font-weight: bold; padding-top: 3pt; }
    tr.minus td { color: #6b7280; }
    table.summary { width: 9cm; margin-left: auto; margin-top: 14pt; border-collapse: collapse; }
    table.summary td { padding: 2pt 4pt; }
    table.summary tr.total td { border-top: 1pt solid #1f2937; font-weight: bold; font-size: 10pt; }
    .muted { color: #6b7280; }
</style>
</head>
<body>
@unless($statement->is_final)
    <div class="draft">ENTWURF</div>
@endunless

<table class="footer">
    <tr>
        <td>{{ $sender }} · Abrechnung {{ $period }} · {{ $party->name }}</td>
        <td style="text-align: right"><span class="page-number"></span></td>
    </tr>
</table>

<table class="letterhead">
    <tr>
        <td class="address">
            @foreach($senderLines as $i => $line)
                <div class="{{ $i === 0 ? 'name' : '' }}">{{ $line }}</div>
            @endforeach
        </td>
        <td style="text-align: right">
            @if($logoFile)
                <img src="{{ $logoFile }}" class="logo" alt="">
            @endif
        </td>
    </tr>
</table>

<div class="recipient">
    @foreach($recipientLines as $line)
        <div>{{ $line }}</div>
    @endforeach
</div>

<p class="muted" style="text-align: right">{{ $statement->statement_date->format('d.m.Y') }}</p>

<h1>Abrechnung Streaming &amp; Downloads</h1>
<p class="subtitle">Nutzung {{ $period }} · {{ $statement->title }}</p>

<p>
    Für die Nutzung in diesem Zeitraum hat unser Vertrieb (Rebeat) insgesamt CHF {{ $chf($result['pot']) }} ausbezahlt.
    Verteilt wird nach der gemeldeten Nutzung: 1 EUR Nutzung entspricht CHF {{ number_format($result['factor'], 4, '.', "'") }} –
    Wechselkurs, Gebühren und Abzüge tragen damit alle gleichermassen. Dein Anteil richtet sich nach dem Vertrag des jeweiligen Produkts.
</p>

@foreach($contracts as $group)
    <h2>
        @if($group->contract)
            {{ $group->contract->title }}
        @else
            Ohne Vertrag
        @endif
    </h2>
    <div class="contract-note">
        @if($group->contract)
            {{ $group->contract->contract_number ? 'Vertrag ' . $group->contract->contract_number . ' · ' : '' }}Dein Anteil an Streaming &amp; Downloads: {{ $pct($group->percent) }}
        @else
            Vertrieb für das Label: 100 % der Erlöse
        @endif
    </div>
    <table class="lines">
        <thead>
            <tr>
                <th>Track / Produkt</th>
                <th>Nutzungen</th>
                <th>Nutzung EUR</th>
                <th>Erlös CHF</th>
                <th>Anteil CHF</th>
            </tr>
        </thead>
        <tbody>
            @foreach($group->items as $item)
                <tr>
                    <td>{{ $item->track?->display_title ?? ($item->release ? $item->release->title . ' (Album-Download)' : '—') }}</td>
                    <td class="num">{{ $int($item->quantity) }}</td>
                    <td class="num">{{ $chf($item->usage_amount) }}</td>
                    <td class="num">{{ $chf($item->base_amount) }}</td>
                    <td class="num">{{ $chf($item->amount) }}</td>
                </tr>
            @endforeach
            <tr class="sum">
                <td colspan="4">Anteil</td>
                <td class="num">{{ $chf($group->share) }}</td>
            </tr>
            @if($group->recouped > 0.004)
                <tr class="minus">
                    <td colspan="4">Verrechnung Vorschüsse und Kosten gemäss Vertrag (danach noch offen: CHF {{ $chf($group->open_after) }})</td>
                    <td class="num">− {{ $chf($group->recouped) }}</td>
                </tr>
            @endif
        </tbody>
    </table>
@endforeach

<table class="summary">
    <tr><td>Saldo vor dieser Abrechnung</td><td class="num">CHF {{ $chf($balanceBefore) }}</td></tr>
    <tr><td>Gutschrift aus dieser Abrechnung</td><td class="num">CHF {{ $chf($party->credit) }}</td></tr>
    <tr class="total"><td>Neuer Saldo zu Deinen Gunsten</td><td class="num">CHF {{ $chf($balanceAfter) }}</td></tr>
</table>

<p style="margin-top: 14pt">
    @if($balanceAfter >= $minimum)
        Wir überweisen Dir den Saldo in den nächsten Tagen auf das bei uns hinterlegte Konto. Bitte melde Dich, falls sich Deine Bankverbindung geändert hat.
    @else
        Beträge unter CHF {{ $chf($minimum) }} tragen wir auf die nächste Abrechnung vor. Möchtest Du den Saldo trotzdem ausbezahlt haben, melde Dich einfach.
    @endif
</p>
<p style="margin-top: 14pt">Fragen zur Abrechnung beantworten wir gerne{{ $contactLine ? ': ' . $contactLine : '.' }}</p>
<p class="muted">Erstellt am {{ $generatedAt->format('d.m.Y') }}.</p>
</body>
</html>
