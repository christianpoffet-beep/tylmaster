<!DOCTYPE html>
<html lang="{{ $contract->language ?? 'de' }}">
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
        .header-logo {
            max-height: 60px;
            max-width: 180px;
        }
        .header-party {
            margin-top: 12px;
            font-size: 8pt;
            color: #6b7280;
            line-height: 1.5;
        }
        .header-party .party-name {
            font-weight: 600;
            color: #374151;
        }

        /* Watermark */
        .watermark {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            text-align: center;
            z-index: 0;
        }
        .watermark img {
            margin-top: 35%;
            width: 55%;
            opacity: 0.06;
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
        .party-address {
            font-size: 8pt;
            color: #6b7280;
            margin-top: 2px;
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
        .relations-note {
            font-size: 9pt;
            color: #374151;
            margin-bottom: 12px;
            white-space: pre-line;
        }
        .track-credit-block {
            margin-bottom: 10px;
        }
        .track-credit-title {
            font-size: 9pt;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 2px;
        }
        .track-credit-isrc {
            font-size: 8pt;
            font-weight: normal;
            color: #9ca3af;
        }
        .track-credit-alt {
            font-size: 8pt;
            font-weight: normal;
            color: #6b7280;
            margin-bottom: 2px;
        }
        .credit-line {
            font-size: 8.5pt;
            color: #374151;
            padding: 1px 0 1px 10px;
        }
        .credit-role {
            color: #6b7280;
        }
        .credit-meta {
            color: #9ca3af;
        }

        /* Signature area */
        .signatures {
            margin-top: 40px;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: avoid;
            margin-bottom: 28px;
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
    @if($contract->logo_as_watermark && $logoAbsolutePath)
        <div class="watermark">
            <img src="{{ $logoAbsolutePath }}" alt="">
        </div>
    @endif

    <div class="footer">
        {{ $contract->contract_number ?? '' }} &middot; {{ $t['generated_on'] }} {{ now()->format('d.m.Y H:i') }} &middot; The Yelling Light
    </div>

    <div class="header">
        <table style="width:100%; border-collapse:collapse;">
            <tr>
                <td style="vertical-align:top;">
                    <h1>{{ $contract->title }}</h1>
                    @if($contract->contract_number)
                        <p class="contract-number">{{ $contract->contract_number }}</p>
                    @endif
                </td>
                @if($contract->logo_in_header && $logoAbsolutePath)
                <td style="vertical-align:top; text-align:right; width:190px;">
                    <img src="{{ $logoAbsolutePath }}" class="header-logo" alt="Logo">
                </td>
                @endif
            </tr>
        </table>

        @if($headerParty)
        <div class="header-party">
            <span class="party-name">{{ $headerParty['name'] }}</span>@if($headerParty['contact_name']) &middot; {{ $headerParty['contact_name'] }}@endif
            @foreach($headerParty['address_lines'] as $line)
                &middot; {{ $line }}
            @endforeach
            @if($headerParty['website']) &middot; {{ $headerParty['website'] }}@endif
            @if($headerParty['phone']) &middot; {{ $t['phone_short'] }} {{ $headerParty['phone'] }}@endif
            @if($headerParty['email']) &middot; {{ $headerParty['email'] }}@endif
        </div>
        @endif
    </div>

    {{-- Meta Information --}}
    <table class="meta-table">
        <tr>
            <td class="label">{{ $t['meta_type'] }}</td>
            <td class="value">{{ $typeLabels[$contract->type] ?? ucfirst($contract->type) }}</td>
            <td class="label">{{ $t['meta_status'] }}</td>
            <td class="value">
                <span class="status-badge status-{{ $contract->status }}">{{ $t['status_' . $contract->status] ?? $contract->status }}</span>
            </td>
        </tr>
        <tr>
            <td class="label">{{ $t['meta_start'] }}</td>
            <td class="value">{{ $contract->start_date?->format('d.m.Y') ?? '—' }}</td>
            <td class="label">{{ $t['meta_end'] }}</td>
            <td class="value">{{ $contract->end_date?->format('d.m.Y') ?? '—' }}</td>
        </tr>
    </table>

    {{-- Parties --}}
    @if($contract->parties->count())
    <div class="section">
        <div class="section-title">{{ $t['parties_title'] }}</div>
        <table class="parties-table">
            <thead>
                <tr>
                    <th>{{ $t['parties_col_party'] }}</th>
                    <th>{{ $t['parties_col_contact'] }}</th>
                    <th style="text-align: right;">{{ $t['parties_col_share'] }}</th>
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
                        @if(count($party->address_lines))
                            <div class="party-address">{{ implode(', ', $party->address_lines) }}</div>
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

    {{-- Vertragsgegenstand --}}
    @if($contract->subject)
    <div class="section">
        <div class="section-title">{{ $t['subject_title'] }}</div>
        <div class="terms-content">{{ $contract->subject }}</div>
    </div>
    @endif

    {{-- Zession --}}
    @if($contract->has_zession)
    <div class="section">
        <div class="section-title">{{ $t['zession_title'] }}</div>
        <div class="zession-box">
            <div class="amount">{{ $contract->zession_currency }} {{ number_format($contract->zession_amount, 2, '.', "'") }}</div>
            <div class="note">{{ $t['zession_note'] }}</div>
            @if($contract->zession_notes)
                <div class="note" style="margin-top: 6px;">{{ $contract->zession_notes }}</div>
            @endif
        </div>
    </div>
    @endif

    {{-- Territory --}}
    @if($contract->territory && count($contract->territory) > 0)
    <div class="section">
        <div class="section-title">{{ $t['territory_title'] }}</div>
        <div class="territory-list">
            @if(in_array('ALL', $contract->territory))
                <span class="territory-worldwide">{{ $t['territory_worldwide'] }}</span>
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
    @php
        $rLabels = $contract->resolvedRightsLabels($t['party_default_prefix']);
        $rPartiesStr = count($rLabels) > 1
            ? implode(', ', array_slice($rLabels, 0, -1)) . ' ' . $t['list_conjunction'] . ' ' . end($rLabels)
            : ($rLabels[0] ?? '');
    @endphp
    <div class="section">
        <div class="section-title">{{ $t['rights_title'] }}</div>
        <p class="rights-label">{{ strtr($t['rights_intro'], [':parties' => $rPartiesStr]) }}</p>
        <table class="rights-table">
            <thead>
                <tr>
                    <th>{{ $t['rights_col_type'] }}</th>
                    <th>{{ $t['rights_col_split'] }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($contract->rights as $right)
                <tr>
                    <td style="font-weight: 600;">{{ $right['label'] }}</td>
                    <td>
                        @if(($right['mode'] ?? 'split') === 'split')
                            @php $sv = $contract->rightSplitValues($right); @endphp
                            @foreach($rLabels as $li => $lname)@if(!$loop->first) / @endif{{ $sv[$li] ?? 0 }}% {{ $lname }}@endforeach
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
    @if($contract->projects->count() || $contract->tracks->count() || $contract->releases->count() || $contract->relations_note)
    @php
        $roleLabels = collect(\App\Models\Setting::creditRoles())->flatMap(fn($roles) => $roles)->toArray();
    @endphp
    <div class="section">
        <div class="section-title">{{ $t['relations_title'] }}</div>

        <div class="relations-note">{{ $contract->relations_note ?: $t['relations_intro_default'] }}</div>

        @if($contract->projects->count())
            <p style="font-size: 9pt; font-weight: 600; color: #6b7280; margin-bottom: 3px;">{{ $t['relations_projects'] }}</p>
            <ul class="relation-list">
                @foreach($contract->projects as $project)
                    <li>{{ $project->name }}</li>
                @endforeach
            </ul>
        @endif

        @if($contract->tracks->count())
            <p style="font-size: 9pt; font-weight: 600; color: #6b7280; margin: 8px 0 4px;">{{ $t['relations_tracks'] }}</p>
            @foreach($contract->tracks as $track)
                <div class="track-credit-block">
                    <div class="track-credit-title">
                        {{ $track->display_title }}@if($track->isrc) <span class="track-credit-isrc">{{ $track->isrc_formatted }}</span>@endif
                    </div>
                    @if($track->alternative_titles_list)
                        <div class="track-credit-alt">{{ $t['relations_track_alt'] }} {{ $track->alternative_titles_list }}</div>
                    @endif
                    @if($track->contacts->count())
                        @foreach($track->contacts as $credit)
                            @php
                                $pivotIpi = $credit->pivot->ipi_number ?? null;
                                $matchedIpi = $pivotIpi ? collect($credit->ipis ?? [])->first(fn($i) => ($i['number'] ?? null) === $pivotIpi) : null;
                                $creditName = ($matchedIpi && !empty($matchedIpi['name'])) ? $matchedIpi['name'] : $credit->full_name;
                                $creditMetaParts = [];
                                if ($credit->pivot->instrument) $creditMetaParts[] = '(' . $credit->pivot->instrument . ')';
                                if ($pivotIpi) $creditMetaParts[] = '· IPI ' . $pivotIpi;
                                $creditMeta = implode(' ', $creditMetaParts);
                            @endphp
                            <div class="credit-line">
                                <span class="credit-role">{{ $roleLabels[$credit->pivot->role] ?? $credit->pivot->role }}:</span>
                                {{ $creditName }}@if($creditMeta) <span class="credit-meta">{{ $creditMeta }}</span>@endif
                            </div>
                        @endforeach
                    @endif
                </div>
            @endforeach
        @endif

        @if($contract->releases->count())
            <p style="font-size: 9pt; font-weight: 600; color: #6b7280; margin: 8px 0 3px;">{{ $t['relations_releases'] }}</p>
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
        <div class="section-title">{{ $t['terms_title'] }}</div>
        <div class="terms-content">{{ $contract->terms }}</div>
    </div>
    @endif

    {{-- Signature area — every party signs --}}
    @if($contract->parties->count())
    <div class="signatures">
        @foreach($contract->parties->chunk(2) as $sigRow)
        <table class="signature-table">
            <tr>
                @foreach($sigRow as $sp)
                    @if(!$loop->first)<td class="spacer"></td>@endif
                    <td>
                        <div class="signature-line">
                            <div class="signature-name">@if($sp->organization){{ $sp->organization->primary_name }}@elseif($sp->contact){{ $sp->contact->full_name }}@endif</div>
                            {{ $t['signature_line'] }}
                        </div>
                    </td>
                @endforeach
                @if($sigRow->count() === 1)
                    <td class="spacer"></td>
                    <td></td>
                @endif
            </tr>
        </table>
        @endforeach
    </div>
    @endif
</body>
</html>
