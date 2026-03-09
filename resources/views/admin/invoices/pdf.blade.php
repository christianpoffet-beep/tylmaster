<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 9pt; color: #1a1a1a; line-height: 1.4; }
        .page { position: relative; width: 100%; min-height: 100%; }

        /* Header */
        .header { padding: 40px 50px 20px; }
        .header-flex { width: 100%; }
        .logo { max-height: 50px; max-width: 180px; margin-bottom: 10px; }
        .sender-info { font-size: 8pt; color: #666; line-height: 1.5; }
        .sender-name { font-weight: bold; color: #1a1a1a; font-size: 9pt; }

        /* Recipient */
        .recipient { padding: 10px 50px 30px; }
        .recipient-name { font-size: 10pt; font-weight: bold; }

        /* Invoice info */
        .invoice-info { padding: 0 50px 20px; }
        .invoice-title { font-size: 14pt; font-weight: bold; margin-bottom: 8px; }
        .invoice-meta { font-size: 8.5pt; color: #555; }
        .invoice-meta td { padding: 2px 15px 2px 0; }
        .invoice-meta td:first-child { color: #888; }

        /* Items table */
        .items { padding: 15px 50px; }
        .items table { width: 100%; border-collapse: collapse; }
        .items thead th {
            font-size: 7.5pt; text-transform: uppercase; letter-spacing: 0.5px;
            color: #888; border-bottom: 1.5px solid #ddd; padding: 6px 8px; text-align: left;
        }
        .items thead th.right { text-align: right; }
        .items tbody td { padding: 7px 8px; border-bottom: 0.5px solid #eee; font-size: 9pt; }
        .items tbody td.right { text-align: right; font-variant-numeric: tabular-nums; }
        .items tbody td.pos { color: #aaa; width: 30px; }
        .items tfoot td {
            padding: 10px 8px 6px; font-size: 10pt; font-weight: bold;
            border-top: 1.5px solid #333;
        }
        .items tfoot td.right { text-align: right; }

        /* Notes */
        .notes { padding: 20px 50px 10px; font-size: 8.5pt; color: #555; }
        .notes-title { font-size: 8pt; color: #888; margin-bottom: 3px; }

        /* Footer */
        .footer-text { padding: 10px 50px; font-size: 7.5pt; color: #999; border-top: 0.5px solid #eee; }

        /* QR-Bill section (Swiss payment slip) */
        .qr-bill {
            position: fixed; bottom: 0; left: 0; right: 0;
            border-top: 1px dashed #000; height: 105mm; padding: 5mm;
        }
        .qr-bill-inner { width: 100%; height: 100%; }
        .qr-receipt {
            float: left; width: 62mm; height: 95mm;
            border-right: 1px dashed #000; padding-right: 5mm;
            font-size: 7pt;
        }
        .qr-payment {
            float: left; width: calc(100% - 67mm); height: 95mm;
            padding-left: 5mm; font-size: 8pt;
        }
        .qr-section-title { font-size: 6pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.3px; margin-bottom: 2px; color: #000; }
        .qr-heading { font-size: 11pt; font-weight: bold; margin-bottom: 5mm; }
        .qr-receipt .qr-heading { font-size: 9pt; }
        .qr-value { font-size: 7pt; line-height: 1.4; margin-bottom: 2mm; }
        .qr-payment .qr-value { font-size: 8pt; }
        .qr-code-img { width: 46mm; height: 46mm; }
        .qr-amount-section { margin-top: 3mm; }
    </style>
</head>
<body>
    <div class="page">
        {{-- Header with sender info --}}
        <div class="header">
            <table class="header-flex" style="width:100%">
                <tr>
                    <td style="width:50%; vertical-align:top;">
                        @if($template && $template->effective_logo_path)
                            <img src="{{ storage_path('app/public/' . $template->effective_logo_path) }}" class="logo" alt="Logo">
                        @endif
                    </td>
                    <td style="width:50%; vertical-align:top; text-align:right;">
                        @if($invoice->hasSender())
                            <div class="sender-info">
                                <div class="sender-name">{{ $invoice->sender_name }}</div>
                                @if($invoice->senderContact && $invoice->senderOrganization)
                                    <div>{{ $invoice->senderContact->full_name }}</div>
                                @endif
                                {!! nl2br(e($invoice->sender_address)) !!}
                                @if($invoice->sender_phone)<br>Tel. {{ $invoice->sender_phone }}@endif
                                @if($invoice->sender_email)<br>{{ $invoice->sender_email }}@endif
                            </div>
                        @elseif($template)
                            <div class="sender-info">
                                <div class="sender-name">{{ $template->sender_name }}</div>
                                {!! nl2br(e($template->sender_address)) !!}
                                @if($template->sender_phone)<br>Tel. {{ $template->sender_phone }}@endif
                                @if($template->sender_email)<br>{{ $template->sender_email }}@endif
                            </div>
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        {{-- Recipient --}}
        <div class="recipient">
            @if($invoice->organization)
                <div class="recipient-name">{{ $invoice->organization->primary_name }}</div>
                @if($invoice->contact)
                    <div>{{ $invoice->contact->full_name }}</div>
                @endif
                @if($invoice->organization->street)<div>{{ $invoice->organization->street }}</div>@endif
                @if($invoice->organization->zip || $invoice->organization->city)
                    <div>{{ $invoice->organization->zip }} {{ $invoice->organization->city }}</div>
                @endif
                @if($invoice->organization->email)<div>{{ $invoice->organization->email }}</div>@endif
            @elseif($invoice->contact)
                <div class="recipient-name">{{ $invoice->contact->full_name }}</div>
                @if($invoice->contact->street)<div>{{ $invoice->contact->street }}</div>@endif
                @if($invoice->contact->zip || $invoice->contact->city)
                    <div>{{ $invoice->contact->zip }} {{ $invoice->contact->city }}</div>
                @endif
                @if($invoice->contact->email)<div>{{ $invoice->contact->email }}</div>@endif
            @endif
        </div>

        {{-- Invoice title + meta --}}
        <div class="invoice-info">
            <div class="invoice-title">Rechnung {{ $invoice->invoice_number }}</div>
            @if($invoice->title)
                <div style="font-size: 10pt; color: #555; margin-bottom: 8px;">{{ $invoice->title }}</div>
            @endif
            <table class="invoice-meta">
                <tr>
                    <td>Rechnungsdatum:</td>
                    <td>{{ $invoice->invoice_date->format('d.m.Y') }}</td>
                </tr>
                @if($invoice->due_date)
                <tr>
                    <td>Fällig bis:</td>
                    <td>{{ $invoice->due_date->format('d.m.Y') }}</td>
                </tr>
                @endif
                @php
                    $vatNumber = $invoice->senderOrganization?->vat_number ?? ($template?->vat_number_display ?? null);
                @endphp
                @if($vatNumber)
                <tr>
                    <td>UID:</td>
                    <td>{{ $vatNumber }}</td>
                </tr>
                @endif
            </table>
        </div>

        {{-- Line items --}}
        <div class="items">
            <table>
                <thead>
                    <tr>
                        <th>Pos</th>
                        <th>Beschreibung</th>
                        <th class="right">Menge</th>
                        <th class="right">Einzelpreis</th>
                        <th class="right">Betrag</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $i => $item)
                        <tr>
                            <td class="pos">{{ $i + 1 }}</td>
                            <td>{{ $item->description }}</td>
                            <td class="right">{{ rtrim(rtrim(number_format($item->quantity, 3, '.', ''), '0'), '.') }}</td>
                            <td class="right">{{ number_format($item->unit_price, 2, '.', "'") }}</td>
                            <td class="right">{{ number_format($item->total, 2, '.', "'") }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    @if($invoice->vat_rate && $invoice->vat_rate > 0)
                        <tr style="border-top: 1px solid #ddd;">
                            <td colspan="4" class="right" style="padding: 5px 8px; font-size: 9pt; font-weight: normal; border-top: none;">Zwischensumme</td>
                            <td class="right" style="padding: 5px 8px; font-size: 9pt; font-weight: normal; border-top: none;">{{ number_format($invoice->subtotal, 2, '.', "'") }}</td>
                        </tr>
                        <tr>
                            <td colspan="4" class="right" style="padding: 3px 8px; font-size: 9pt; font-weight: normal; border-top: none;">MWST {{ rtrim(rtrim(number_format($invoice->vat_rate, 2, '.', ''), '0'), '.') }}%</td>
                            <td class="right" style="padding: 3px 8px; font-size: 9pt; font-weight: normal; border-top: none;">{{ number_format($invoice->vat_amount, 2, '.', "'") }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td colspan="4" class="right">Total {{ $invoice->currency }}</td>
                        <td class="right">{{ number_format($invoice->amount, 2, '.', "'") }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Notes --}}
        @if($invoice->notes)
            <div class="notes">
                <div class="notes-title">Bemerkungen</div>
                {!! nl2br(e($invoice->notes)) !!}
            </div>
        @endif

        @if($template && $template->payment_terms_days && !$invoice->due_date)
            <div class="notes">
                Zahlbar innert {{ $template->payment_terms_days }} Tagen.
            </div>
        @endif

        {{-- Footer --}}
        @if($template && $template->footer_text)
            <div class="footer-text">
                {!! nl2br(e($template->footer_text)) !!}
            </div>
        @endif

        {{-- QR-Bill Payment Slip --}}
        @if($qrSvg)
            @php
                $hasSender = $invoice->hasSender();
                $qrIban = $hasSender ? $invoice->sender_formatted_iban : ($template ? $template->formatted_iban : '');
                $qrName = $hasSender ? $invoice->sender_billing_name : ($template ? $template->billing_name : '');
                $qrAddress = $hasSender ? $invoice->sender_billing_address_line : ($template ? $template->billing_address_line : '');
                $qrZip = $hasSender ? $invoice->sender_billing_zip : ($template ? $template->billing_zip : '');
                $qrCity = $hasSender ? $invoice->sender_billing_city : ($template ? $template->billing_city : '');
            @endphp
            <div class="qr-bill">
                <div class="qr-bill-inner">
                    {{-- Receipt (Empfangsschein) --}}
                    <div class="qr-receipt">
                        <div class="qr-heading">Empfangsschein</div>

                        <div class="qr-section-title">Konto / Zahlbar an</div>
                        <div class="qr-value">
                            {{ $qrIban }}<br>
                            {{ $qrName }}<br>
                            {{ $qrAddress }}<br>
                            {{ $qrZip }} {{ $qrCity }}
                        </div>

                        @if($invoice->organization || $invoice->contact)
                            <div class="qr-section-title">Zahlbar durch</div>
                            <div class="qr-value">
                                @if($invoice->organization)
                                    {{ $invoice->organization->primary_name }}<br>
                                    @if($invoice->contact){{ $invoice->contact->full_name }}<br>@endif
                                    @if($invoice->organization->street){{ $invoice->organization->street }}<br>@endif
                                    @if($invoice->organization->zip || $invoice->organization->city){{ $invoice->organization->zip }} {{ $invoice->organization->city }}@endif
                                @elseif($invoice->contact)
                                    {{ $invoice->contact->full_name }}<br>
                                    @if($invoice->contact->street){{ $invoice->contact->street }}<br>@endif
                                    @if($invoice->contact->zip || $invoice->contact->city){{ $invoice->contact->zip }} {{ $invoice->contact->city }}@endif
                                @endif
                            </div>
                        @endif

                        <div class="qr-amount-section">
                            <div class="qr-section-title">Währung</div>
                            <div class="qr-value" style="display:inline-block; width:20mm;">{{ $invoice->currency }}</div>
                            <div class="qr-section-title" style="display:inline-block;">Betrag</div>
                            <div class="qr-value" style="display:inline-block;">{{ number_format($invoice->amount, 2, '.', ' ') }}</div>
                        </div>

                        <div style="margin-top:3mm;">
                            <div class="qr-section-title">Annahmestelle</div>
                        </div>
                    </div>

                    {{-- Payment part (Zahlteil) --}}
                    <div class="qr-payment">
                        <div class="qr-heading">Zahlteil</div>

                        <table style="width:100%">
                            <tr>
                                <td style="width:51mm; vertical-align:top;">
                                    <img src="{{ $qrSvg }}" class="qr-code-img" alt="QR-Code">
                                </td>
                                <td style="vertical-align:top; padding-left:5mm;">
                                    <div class="qr-section-title">Konto / Zahlbar an</div>
                                    <div class="qr-value">
                                        {{ $qrIban }}<br>
                                        {{ $qrName }}<br>
                                        {{ $qrAddress }}<br>
                                        {{ $qrZip }} {{ $qrCity }}
                                    </div>

                                    <div class="qr-section-title">Referenz</div>
                                    <div class="qr-value">{{ $invoice->invoice_number }}</div>

                                    @if($invoice->organization || $invoice->contact)
                                        <div class="qr-section-title">Zahlbar durch</div>
                                        <div class="qr-value">
                                            @if($invoice->organization)
                                                {{ $invoice->organization->primary_name }}<br>
                                                @if($invoice->contact){{ $invoice->contact->full_name }}<br>@endif
                                                @if($invoice->organization->street){{ $invoice->organization->street }}<br>@endif
                                                @if($invoice->organization->zip || $invoice->organization->city){{ $invoice->organization->zip }} {{ $invoice->organization->city }}@endif
                                            @elseif($invoice->contact)
                                                {{ $invoice->contact->full_name }}<br>
                                                @if($invoice->contact->street){{ $invoice->contact->street }}<br>@endif
                                                @if($invoice->contact->zip || $invoice->contact->city){{ $invoice->contact->zip }} {{ $invoice->contact->city }}@endif
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        </table>

                        <div class="qr-amount-section">
                            <div class="qr-section-title" style="display:inline-block; width:25mm;">Währung</div>
                            <div class="qr-section-title" style="display:inline-block;">Betrag</div>
                            <br>
                            <div class="qr-value" style="display:inline-block; width:25mm;">{{ $invoice->currency }}</div>
                            <div class="qr-value" style="display:inline-block;">{{ number_format($invoice->amount, 2, '.', ' ') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</body>
</html>
