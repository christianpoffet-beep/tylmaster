<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $contract->title }}</title>
    <style>
        @page {
            margin: 2.5cm 2cm 3cm 2cm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10pt;
            color: #1a1a1a;
            line-height: 1.6;
        }

        /* Header */
        .header {
            border-bottom: 2px solid #1e3a5f;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .header h1 {
            font-size: 18pt;
            color: #1e3a5f;
            margin: 0 0 3px 0;
            letter-spacing: 0.5px;
        }
        .header .subtitle {
            font-size: 9pt;
            color: #6b7280;
            margin: 0;
        }
        .contract-number {
            font-size: 9pt;
            color: #6b7280;
            margin: 2px 0 0 0;
        }

        /* Meta info */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .meta-table td {
            padding: 5px 10px;
            font-size: 9pt;
            vertical-align: top;
        }
        .meta-table .label {
            color: #6b7280;
            width: 120px;
            font-weight: normal;
        }
        .meta-table .value {
            color: #1a1a1a;
            font-weight: 600;
        }

        /* Sections */
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 11pt;
            font-weight: 700;
            color: #1e3a5f;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 4px;
            margin-bottom: 10px;
        }

        /* Parties table */
        .parties-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .parties-table th {
            background-color: #f3f4f6;
            padding: 6px 10px;
            text-align: left;
            font-size: 8pt;
            font-weight: 600;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #d1d5db;
        }
        .parties-table td {
            padding: 8px 10px;
            font-size: 9pt;
            border-bottom: 1px solid #e5e7eb;
        }
        .parties-table .share {
            text-align: right;
            font-weight: 600;
            white-space: nowrap;
        }

        /* Zession box */
        .zession-box {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 4px;
            padding: 10px 14px;
            margin-bottom: 10px;
        }
        .zession-box .amount {
            font-size: 12pt;
            font-weight: 700;
            color: #92400e;
        }
        .zession-box .note {
            font-size: 8pt;
            color: #92400e;
            margin-top: 4px;
        }

        /* Territory */
        .territory-list {
            font-size: 9pt;
            color: #374151;
        }
        .territory-badge {
            display: inline-block;
            background-color: #e5e7eb;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 8pt;
            margin: 1px 2px;
        }
        .territory-worldwide {
            display: inline-block;
            background-color: #dbeafe;
            color: #1e40af;
            padding: 3px 10px;
            border-radius: 3px;
            font-size: 9pt;
            font-weight: 600;
        }

        /* Rights table */
        .rights-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .rights-table th {
            background-color: #f3f4f6;
            padding: 6px 10px;
            text-align: left;
            font-size: 8pt;
            font-weight: 600;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #d1d5db;
        }
        .rights-table td {
            padding: 6px 10px;
            font-size: 9pt;
            border-bottom: 1px solid #e5e7eb;
        }
        .rights-label {
            font-size: 9pt;
            color: #6b7280;
            margin-bottom: 8px;
        }

        /* Terms */
        .terms-content {
            font-size: 9pt;
            line-height: 1.7;
            white-space: pre-line;
            color: #374151;
        }

        /* Relations */
        .relation-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .relation-list li {
            padding: 3px 0;
            font-size: 9pt;
            color: #374151;
        }
        .relation-list li:before {
            content: "\2022";
            color: #1e3a5f;
            margin-right: 6px;
        }

        /* Signature area */
        .signatures {
            margin-top: 50px;
            page-break-inside: avoid;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }
        .signature-table td {
            width: 45%;
            padding: 0;
            vertical-align: top;
        }
        .signature-table td.spacer {
            width: 10%;
        }
        .signature-line {
            border-top: 1px solid #1a1a1a;
            padding-top: 4px;
            font-size: 8pt;
            color: #6b7280;
            margin-top: 60px;
        }
        .signature-name {
            font-size: 9pt;
            font-weight: 600;
            color: #1a1a1a;
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: -2cm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 7pt;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 5px;
        }

        /* Status badge */
        .status-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 10px;
            font-size: 8pt;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-draft { background: #f3f4f6; color: #6b7280; }
        .status-active { background: #d1fae5; color: #065f46; }
        .status-expired { background: #fee2e2; color: #991b1b; }
        .status-terminated { background: #ffedd5; color: #9a3412; }
    </style>
</head>
<body>
    <div class="footer">
        {{ $contract->contract_number ?? '' }} &middot; Generiert am {{ now()->format('d.m.Y H:i') }} &middot; The Yelling Light
    </div>

    <div class="header">
        <h1>{{ $contract->title }}</h1>
        @if($contract->contract_number)
            <p class="contract-number">{{ $contract->contract_number }}</p>
        @endif
    </div>

    {{-- Meta Information --}}
    <table class="meta-table">
        <tr>
            <td class="label">Typ</td>
            <td class="value">{{ $typeLabels[$contract->type] ?? ucfirst($contract->type) }}</td>
            <td class="label">Status</td>
            <td class="value">
                <span class="status-badge status-{{ $contract->status }}">{{ $statusLabels[$contract->status] ?? $contract->status }}</span>
            </td>
        </tr>
        <tr>
            <td class="label">Startdatum</td>
            <td class="value">{{ $contract->start_date?->format('d.m.Y') ?? '—' }}</td>
            <td class="label">Enddatum</td>
            <td class="value">{{ $contract->end_date?->format('d.m.Y') ?? '—' }}</td>
        </tr>
    </table>

    {{-- Parties --}}
    @if($contract->parties->count())
    <div class="section">
        <div class="section-title">Vertragsparteien</div>
        <table class="parties-table">
            <thead>
                <tr>
                    <th>Partei</th>
                    <th>Ansprechperson</th>
                    <th style="text-align: right;">Anteil</th>
                </tr>
            </thead>
            <tbody>
                @foreach($contract->parties as $party)
                <tr>
                    <td>
                        @if($party->organization)
                            {{ $party->organization->primary_name }}
                        @elseif($party->contact)
                            {{ $party->contact->full_name }}
                        @endif
                    </td>
                    <td>
                        @if($party->organization && $party->contact)
                            {{ $party->contact->full_name }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="share">{{ number_format($party->share, 2) }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Zession --}}
    @if($contract->has_zession)
    <div class="section">
        <div class="section-title">Zession (Vorschusszahlung)</div>
        <div class="zession-box">
            <div class="amount">{{ $contract->zession_currency }} {{ number_format($contract->zession_amount, 2, '.', "'") }}</div>
            <div class="note">Dieser Betrag wird mit künftigen Einnahmen verrechnet.</div>
            @if($contract->zession_notes)
                <div class="note" style="margin-top: 6px;">{{ $contract->zession_notes }}</div>
            @endif
        </div>
    </div>
    @endif

    {{-- Territory --}}
    @if($contract->territory && count($contract->territory) > 0)
    <div class="section">
        <div class="section-title">Geltungsbereich / Territory</div>
        <div class="territory-list">
            @if(in_array('ALL', $contract->territory))
                <span class="territory-worldwide">Weltweit</span>
            @else
                @foreach($contract->territory as $code)
                    <span class="territory-badge">{{ $code }}</span>
                @endforeach
            @endif
        </div>
    </div>
    @endif

    {{-- Rights / Vergütung --}}
    @if($contract->rights && count($contract->rights) > 0)
    <div class="section">
        <div class="section-title">Vergütung</div>
        @if($contract->rights_label_a || $contract->rights_label_b)
            <p class="rights-label">Die Einnahmen werden zwischen {{ $contract->rights_label_a ?? 'Partei 1' }} und {{ $contract->rights_label_b ?? 'Partei 2' }} wie folgt aufgeteilt:</p>
        @endif
        <table class="rights-table">
            <thead>
                <tr>
                    <th>Rechtetyp</th>
                    <th>Aufteilung</th>
                </tr>
            </thead>
            <tbody>
                @foreach($contract->rights as $right)
                <tr>
                    <td style="font-weight: 600;">{{ $right['label'] }}</td>
                    <td>
                        @if(($right['mode'] ?? 'split') === 'split')
                            {{ $right['split_a'] ?? 0 }}% {{ $contract->rights_label_a ?? 'Partei 1' }} / {{ $right['split_b'] ?? 0 }}% {{ $contract->rights_label_b ?? 'Partei 2' }}
                        @else
                            {{ $right['custom_text'] ?? '' }}
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Related Projects, Tracks, Releases --}}
    @if($contract->projects->count() || $contract->tracks->count() || $contract->releases->count())
    <div class="section">
        <div class="section-title">Verknüpfungen</div>
        @if($contract->projects->count())
            <p style="font-size: 9pt; font-weight: 600; color: #6b7280; margin-bottom: 3px;">Projekte</p>
            <ul class="relation-list">
                @foreach($contract->projects as $project)
                    <li>{{ $project->name }}</li>
                @endforeach
            </ul>
        @endif
        @if($contract->tracks->count())
            <p style="font-size: 9pt; font-weight: 600; color: #6b7280; margin: 8px 0 3px;">Tracks</p>
            <ul class="relation-list">
                @foreach($contract->tracks as $track)
                    <li>{{ $track->title }}{{ $track->isrc ? ' (ISRC: ' . $track->isrc . ')' : '' }}</li>
                @endforeach
            </ul>
        @endif
        @if($contract->releases->count())
            <p style="font-size: 9pt; font-weight: 600; color: #6b7280; margin: 8px 0 3px;">Releases</p>
            <ul class="relation-list">
                @foreach($contract->releases as $release)
                    <li>{{ $release->title }}{{ $release->upc ? ' (UPC: ' . $release->upc . ')' : '' }}</li>
                @endforeach
            </ul>
        @endif
    </div>
    @endif

    {{-- Terms --}}
    @if($contract->terms)
    <div class="section">
        <div class="section-title">Bedingungen / Notizen</div>
        <div class="terms-content">{{ $contract->terms }}</div>
    </div>
    @endif

    {{-- Signature area --}}
    <div class="signatures">
        <table class="signature-table">
            @php
                $sigParties = $contract->parties->take(2);
            @endphp
            <tr>
                @if($sigParties->count() >= 1)
                <td>
                    <div class="signature-line">
                        <div class="signature-name">
                            @if($sigParties[0]->organization)
                                {{ $sigParties[0]->organization->primary_name }}
                            @elseif($sigParties[0]->contact)
                                {{ $sigParties[0]->contact->full_name }}
                            @endif
                        </div>
                        Ort, Datum, Unterschrift
                    </div>
                </td>
                @endif
                <td class="spacer"></td>
                @if($sigParties->count() >= 2)
                <td>
                    <div class="signature-line">
                        <div class="signature-name">
                            @if($sigParties[1]->organization)
                                {{ $sigParties[1]->organization->primary_name }}
                            @elseif($sigParties[1]->contact)
                                {{ $sigParties[1]->contact->full_name }}
                            @endif
                        </div>
                        Ort, Datum, Unterschrift
                    </div>
                </td>
                @endif
            </tr>
        </table>
    </div>
</body>
</html>
