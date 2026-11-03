<!DOCTYPE html>
<html lang="{{ $contract->language ?? 'de' }}">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $contract->title }}</title>
    <style>
        @page {
            margin: 2.2cm 2cm 2.6cm 2cm;
        }
        /*
         * DejaVu Sans ships with dompdf in normal and bold only. Any other
         * weight (600!) finds no file and silently falls back to Times-Bold,
         * so emphasis uses "bold" throughout.
         */
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10pt;
            color: #1a1a1a;
            line-height: 1.55;
        }

        /* Header */
        .header {
            border-bottom: 2px solid #1e3a5f;
            padding-bottom: 12px;
            margin-bottom: 18px;
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
            font-weight: bold;
            color: #374151;
        }
        .nowrap {
            white-space: nowrap;
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
            margin-top: 45%;
            width: 38%;
            opacity: 0.045;
        }

        /* Meta info */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
            background-color: #f9fafb;
            page-break-inside: avoid;
        }
        .meta-table td {
            padding: 6px 10px;
            font-size: 9pt;
            vertical-align: middle;
        }
        .meta-table .label {
            color: #6b7280;
            width: 15%;
            font-weight: normal;
        }
        .meta-table .value {
            color: #1a1a1a;
            font-weight: bold;
            width: 35%;
        }

        /* Sections */
        .section {
            margin-bottom: 18px;
        }
        /* Short, self-contained blocks must never be torn across a page. */
        .section-compact {
            page-break-inside: avoid;
        }
        .section-title {
            font-size: 11pt;
            font-weight: 700;
            color: #1e3a5f;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 4px;
            margin-bottom: 9px;
        }
        /* Heading + first paragraph travel as one unit. */
        .keep-together {
            page-break-inside: avoid;
        }

        /* Party preamble */
        .preamble {
            font-size: 9.5pt;
            color: #374151;
            margin-bottom: 2px;
        }
        .preamble-block {
            page-break-inside: avoid;
            margin-bottom: 9px;
        }
        .preamble-block div {
            line-height: 1.45;
        }
        .preamble-name {
            font-weight: bold;
            color: #1a1a1a;
        }
        .preamble-role {
            color: #6b7280;
            margin-top: 2px;
        }
        .preamble-and {
            margin: 0 0 9px 0;
            color: #6b7280;
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
            font-weight: bold;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #d1d5db;
        }
        .parties-table td {
            padding: 7px 10px;
            font-size: 9pt;
            border-bottom: 1px solid #e5e7eb;
        }
        .parties-table tr.total-row td {
            border-bottom: none;
            border-top: 1px solid #d1d5db;
            font-size: 8pt;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .parties-table .share {
            text-align: right;
            font-weight: bold;
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
            page-break-inside: avoid;
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
            font-weight: bold;
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
            font-weight: bold;
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
            line-height: 1.65;
            color: #374151;
        }
        /* One block per paragraph keeps headings glued to their first lines. */
        .text-para {
            white-space: pre-line;
            margin-bottom: 8px;
        }

        /* Numbered clauses */
        .clause {
            margin-bottom: 13px;
        }
        .clause-title {
            font-size: 9.5pt;
            font-weight: 700;
            color: #1e3a5f;
            margin-bottom: 3px;
        }
        .clause-number {
            color: #6b7280;
        }

        /* Annex: same heading level as the clauses, one block per topic */
        .annex-block {
            margin-bottom: 16px;
        }
        .annex-block .clause-title {
            margin-bottom: 6px;
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
            page-break-inside: avoid;
        }
        .track-credit-title {
            font-size: 9pt;
            font-weight: bold;
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
            margin-top: 26px;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: avoid;
            margin-bottom: 18px;
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
            margin-top: 46px;
        }
        .signature-name {
            font-size: 9pt;
            font-weight: bold;
            color: #1a1a1a;
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: -1.7cm;
            left: 0;
            right: 0;
            font-size: 7pt;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 5px;
        }
        .footer table {
            width: 100%;
            border-collapse: collapse;
        }
        .footer td {
            font-size: 7pt;
            color: #9ca3af;
            padding: 0;
        }
        /*
         * dompdf resolves the current page only inside a fixed-position element,
         * and only through generated content - hence the pseudo element instead
         * of a Blade expression. The total comes from the controller.
         */
        .page-number:after {
            content: "{{ $t['page'] }} " counter(page)@if($pageCount ?? null) " {{ $t['page_of'] }} {{ $pageCount }}"@endif;
        }

        /* Status badge */
        .status-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 10px;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .contract-number .status-badge {
            margin-left: 6px;
            padding: 0 8px;
            font-size: 7pt;
            vertical-align: middle;
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
        <table>
            <tr>
                <td style="text-align: left;">{{ $contract->contract_number ?? '' }} &middot; {{ $t['generated_on'] }} {{ now()->format('d.m.Y H:i') }} &middot; {{ $headerParty['name'] ?? 'The Yelling Light' }}</td>
                <td style="text-align: right; white-space: nowrap;"><span class="page-number"></span></td>
            </tr>
        </table>
    </div>

    @php
        $typeName = $typeLabels[$contract->type] ?? ucfirst((string) $contract->type);
        // Each detail stays on one line; only the gaps between them may wrap.
        // The first entry of the first line is the party name.
        $partyLines = [];
        if ($headerParty) {
            $partyLines = array_values(array_filter([
                array_merge([$headerParty['name']], $headerParty['address_lines'], array_filter([$headerParty['website']])),
                array_values(array_filter([
                    $headerParty['contact_name'],
                    $headerParty['phone'] ? $t['phone_short'] . ' ' . $headerParty['phone'] : null,
                    $headerParty['email'],
                ])),
            ]));
        }
    @endphp
    <div class="header">
        <table style="width:100%; border-collapse:collapse;">
            <tr>
                <td style="vertical-align:top;">
                    <h1>{{ $contract->title }}</h1>
                    <p class="contract-number">
                        {{ $typeName !== '' ? strtr($t['type_title'], [':type' => $typeName]) : $t['subtitle'] }}@if($contract->contract_number) &middot; {{ $contract->contract_number }}@endif
                        {{-- The status only matters on paper while the contract is not in force. --}}
                        @if($contract->status && $contract->status !== 'active')
                            <span class="status-badge status-{{ $contract->status }}">{{ $t['status_' . $contract->status] ?? $contract->status }}</span>
                        @endif
                    </p>
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
            @foreach($partyLines as $items)
                <div>
                    @foreach($items as $item)
                        @if(!$loop->first) &middot; @endif<span class="nowrap{{ $loop->parent->first && $loop->first ? ' party-name' : '' }}">{{ $item }}</span>
                    @endforeach
                </div>
            @endforeach
        </div>
        @endif
    </div>

    @if($contract->start_date || $contract->end_date)
    <table class="meta-table">
        <tr>
            <td class="label">{{ $t['meta_start'] }}</td>
            <td class="value">{{ $contract->start_date?->format('d.m.Y') ?? '—' }}</td>
            <td class="label">{{ $t['meta_end'] }}</td>
            <td class="value">{{ $contract->end_date?->format('d.m.Y') ?? $t['meta_open_ended'] }}</td>
        </tr>
    </table>
    @endif

    {{-- Vertragsparteien: overview table plus the prose preamble --}}
    @php
        $preambleBlocks = $contract->preambleBlocks($t);
        $showPartiesTable = $contract->show_parties_table ?? true;
        $customPreamble = ($contract->preamble_mode ?? 'auto') === 'custom' ? trim((string) $contract->preamble_text) : '';
    @endphp
    @if($contract->parties->count() && ($showPartiesTable || $preambleBlocks || $customPreamble !== ''))
    <div class="section">
        <div class="section-title">{{ $t['parties_title'] }}</div>

        @if($showPartiesTable)
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
                        {{-- The address only repeats when the entity changes. --}}
                        @php
                            $prev = $loop->index > 0 ? $contract->parties[$loop->index - 1] : null;
                            $sameEntity = $prev
                                && $prev->organization_id === $party->organization_id
                                && ($party->organization_id !== null || $prev->contact_id === $party->contact_id);
                        @endphp
                        @if(count($party->address_lines) && !$sameEntity)
                            <div class="party-address">{{ implode(', ', $party->address_lines) }}</div>
                        @endif
                    </td>
                    <td>
                        @if($party->organization && $party->contact)
                            {{ $party->contact->full_name }}
                        @else
                            —
                        @endif
                        @if($party->role_label)
                            <div class="party-address">{{ $party->role_label }}</div>
                        @endif
                    </td>
                    <td class="share">{{ \App\Models\Contract::formatPercent($party->share) }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="2">{{ $t['parties_total'] }}</td>
                    <td class="share">{{ \App\Models\Contract::formatPercent($contract->parties->sum('share')) }}</td>
                </tr>
            </tbody>
        </table>
        @endif

        @if($customPreamble !== '')
            <div style="margin-top: 6px;">@include('admin.contracts.partials.text-block', ['text' => $customPreamble])</div>
        @elseif($preambleBlocks)
            <div class="preamble" style="margin-top: {{ $showPartiesTable ? '12px' : '0' }};">
                @foreach($preambleBlocks as $block)
                    @if(!$loop->first)
                        <div class="preamble-and">{{ $t['preamble_and'] }}</div>
                    @endif
                    <div class="preamble-block">
                        @foreach($block['lines'] as $i => $line)
                            @php $isRole = $i === count($block['lines']) - 1 && str_starts_with($line, '('); @endphp
                            <div class="{{ $block['strong'][$i] ? 'preamble-name' : ($isRole ? 'preamble-role' : '') }}">{{ $line }}</div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endif
    </div>
    @endif

    {{-- Clause numbering runs across the subject and the section editor. --}}
    @php
        $autoNumber = $contract->auto_number_sections ?? true;
        $clauseNo = 0;
        $sections = $contract->resolvedSections($t);
    @endphp

    {{-- Bestimmungen: the subject is clause 1, the section editor (or the legacy terms block) continues --}}
    @if($contract->subject || count($sections))
    <div class="section">
        @if($contract->subject)
            @php if ($autoNumber) { $clauseNo++; } @endphp
            <div class="clause">
                @include('admin.contracts.partials.clause', [
                    'title' => $contract->subject_heading ?: $t['subject_title'],
                    'number' => $autoNumber ? $clauseNo : null,
                    'titleClass' => 'clause-title',
                    'body' => $contract->subject,
                ])
            </div>
        @endif
        @foreach($sections as $section)
            @php if ($autoNumber && $section['numbered']) { $clauseNo++; } @endphp
            <div class="clause" @if($section['page_break'] && ($contract->subject || !$loop->first)) style="page-break-before: always;" @endif>
                @include('admin.contracts.partials.clause', [
                    'title' => $section['title'],
                    'number' => ($autoNumber && $section['numbered']) ? $clauseNo : null,
                    'titleClass' => 'clause-title',
                    'body' => $section['body'],
                ])
            </div>
        @endforeach
    </div>
    @endif

    {{-- Schlussbestimmungen --}}
    @if($contract->closing_note)
    <div class="section section-compact">
        @include('admin.contracts.partials.text-block', ['text' => $contract->closing_note])
    </div>
    @endif

    {{--
        Anhang: the data the clauses refer to ("die im Anhang aufgeführten
        Aufnahmen"). Its heading is printed inside whichever block comes
        first, so it can never end a page on its own.
    --}}
    @php
        $hasRelations = $contract->projects->count() || $contract->tracks->count() || $contract->releases->count() || $contract->relations_note;
        $hasRights = $contract->rights && count($contract->rights) > 0;
        $hasTerritory = $contract->territory && count($contract->territory) > 0;
        $annexHeadingPending = true;
    @endphp
    @if($hasRelations || $hasRights || $contract->has_zession || $hasTerritory)
    <div class="section">

    {{-- Related Projects, Tracks, Releases --}}
    @if($hasRelations)
    @php
        $roleLabels = collect(\App\Models\Setting::creditRoles())->flatMap(fn($roles) => $roles)->toArray();
        // The annex already says "Anhang" - "Anhang: Aufnahmen" would say it twice.
        $relationsTitle = $contract->relations_heading ?: $t['relations_title'];
        $relationsTitle = preg_replace('/^' . preg_quote($t['annex_title'], '/') . '\s*[:–-]\s*/iu', '', $relationsTitle) ?: $relationsTitle;
    @endphp
    <div class="annex-block">
        <div class="keep-together">
            @if($annexHeadingPending)
                <div class="section-title">{{ $t['annex_title'] }}</div>
                @php $annexHeadingPending = false; @endphp
            @endif
            <div class="clause-title">{{ $relationsTitle }}</div>
            <div class="relations-note">{{ $contract->relations_note ?: $t['relations_intro_default'] }}</div>
        </div>

        @if($contract->projects->count())
            <div class="section-compact">
                <p style="font-size: 9pt; font-weight: bold; color: #6b7280; margin-bottom: 3px;">{{ $t['relations_projects'] }}</p>
                <ul class="relation-list">
                    @foreach($contract->projects as $project)
                        <li>{{ $project->name }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($contract->tracks->count())
            {{-- The heading travels with the first track so it never ends a page alone. --}}
            @foreach($contract->tracks as $track)
                <div class="track-credit-block">
                    @if($loop->first)
                        <p style="font-size: 9pt; font-weight: bold; color: #6b7280; margin: 8px 0 4px;">{{ $t['relations_tracks'] }}</p>
                    @endif
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
            <div class="section-compact">
                <p style="font-size: 9pt; font-weight: bold; color: #6b7280; margin: 8px 0 3px;">{{ $t['relations_releases'] }}</p>
                <ul class="relation-list">
                    @foreach($contract->releases as $release)
                        <li>{{ $release->title }}{{ $release->upc ? ' (UPC: ' . $release->upc . ')' : '' }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
    @endif

    {{-- Rights / Vergütung --}}
    @if($hasRights)
    @php
        $rLabels = $contract->resolvedRightsLabels($t['party_default_prefix']);
        $rPartiesStr = count($rLabels) > 1
            ? implode(', ', array_slice($rLabels, 0, -1)) . ' ' . $t['list_conjunction'] . ' ' . end($rLabels)
            : ($rLabels[0] ?? '');
    @endphp
    <div class="annex-block section-compact">
        @if($annexHeadingPending)
            <div class="section-title">{{ $t['annex_title'] }}</div>
            @php $annexHeadingPending = false; @endphp
        @endif
        <div class="clause-title">{{ $t['rights_title'] }}</div>
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
                    <td style="font-weight: bold;">{{ $right['label'] }}</td>
                    <td>
                        @if(($right['mode'] ?? 'split') === 'split')
                            @php $sv = $contract->rightSplitValues($right); @endphp
                            @foreach($rLabels as $li => $lname)@if(!$loop->first) / @endif{{ \App\Models\Contract::formatPercent($sv[$li] ?? 0) }} {{ $lname }}@endforeach
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

    {{-- Zession --}}
    @if($contract->has_zession)
    <div class="annex-block section-compact">
        @if($annexHeadingPending)
            <div class="section-title">{{ $t['annex_title'] }}</div>
            @php $annexHeadingPending = false; @endphp
        @endif
        <div class="clause-title">{{ $t['zession_title'] }}</div>
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
    @if($hasTerritory)
    <div class="annex-block section-compact">
        @if($annexHeadingPending)
            <div class="section-title">{{ $t['annex_title'] }}</div>
            @php $annexHeadingPending = false; @endphp
        @endif
        <div class="clause-title">{{ $t['territory_title'] }}</div>
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
                            @if($sp->organization && $sp->contact)<div style="font-size: 8pt; color: #6b7280;">{{ $sp->contact->full_name }}</div>@endif
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
