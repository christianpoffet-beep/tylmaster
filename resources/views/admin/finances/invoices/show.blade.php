@extends('admin.layouts.app')

@section('title', 'Rechnung ' . $invoice->invoice_number)

@section('content')
<div class="max-w-3xl">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $invoice->invoice_number }}</h2>
                @if($invoice->title)
                    <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">{{ $invoice->title }}</p>
                @endif
                <p class="text-sm text-gray-500 mt-1">{{ $invoice->type === 'incoming' ? 'Eingehende Rechnung' : 'Ausgehende Rechnung' }}</p>
                <div class="mt-2 flex items-center gap-2">
                    @switch($invoice->status)
                        @case('open')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300">Offen</span>
                            @break
                        @case('paid')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300">Bezahlt</span>
                            @break
                        @case('overdue')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300">Überfällig</span>
                            @break
                    @endswitch
                    @if($invoice->template)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">{{ $invoice->template->name }}</span>
                    @endif
                    @if($invoice->accounting)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300">{{ $invoice->accounting->name }}</span>
                    @endif
                    @if($invoice->project)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300">{{ $invoice->project->name }}</span>
                    @endif
                </div>
            </div>
            <div class="flex gap-2">
                @if(($invoice->template || $invoice->hasSender()) && $invoice->type === 'outgoing')
                    <a href="{{ route('admin.invoices.pdf', $invoice) }}" class="px-4 py-2 bg-gray-800 dark:bg-gray-600 text-white text-sm rounded-lg hover:bg-gray-700 dark:hover:bg-gray-50 dark:hover:bg-gray-700/500">PDF</a>
                @endif
                @if($invoice->status !== 'paid')
                    <div class="flex items-center gap-2">
                        <form method="POST" action="{{ route('admin.invoices.markPaid', $invoice) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700" onclick="return confirm('Als bezahlt markieren?')">Bezahlt</button>
                        </form>
                        @if($invoice->accounting)
                            <span x-data="{ show: false }" class="relative inline-block">
                                <button type="button" @click="show = !show" @click.outside="show = false" class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-green-100 text-green-600 text-xs hover:bg-green-200 focus:outline-none">?</button>
                                <div x-show="show" x-transition class="absolute z-20 bottom-full right-0 mb-2 w-72 p-2 bg-gray-800 text-white text-xs rounded-lg shadow-lg">
                                    Beim Bezahlt-Markieren wird automatisch eine Zahlungsbuchung in der verknüpften Buchhaltung ({{ $invoice->accounting->name }}) erstellt.
                                    <div class="absolute top-full right-4 border-4 border-transparent border-t-gray-800"></div>
                                </div>
                            </span>
                        @endif
                    </div>
                @endif
                <a href="{{ route('admin.invoices.edit', $invoice) }}" class="px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white text-sm rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600">Bearbeiten</a>
            </div>
        </div>

        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Absender</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                    @if($invoice->senderOrganization)
                        <a href="{{ route('admin.organizations.show', $invoice->senderOrganization) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">{{ $invoice->senderOrganization->primary_name }}</a>
                        @if($invoice->senderContact)
                            <span class="text-gray-500 dark:text-gray-400">—</span>
                            <a href="{{ route('admin.contacts.show', $invoice->senderContact) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">{{ $invoice->senderContact->full_name }}</a>
                        @endif
                    @elseif($invoice->senderContact)
                        <a href="{{ route('admin.contacts.show', $invoice->senderContact) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">{{ $invoice->senderContact->full_name }}</a>
                    @elseif($invoice->template)
                        <span class="text-gray-400">via Vorlage: {{ $invoice->template->sender_name }}</span>
                    @else
                        —
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Empfänger</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                    @if($invoice->organization)
                        <a href="{{ route('admin.organizations.show', $invoice->organization) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">{{ $invoice->organization->primary_name }}</a>
                        @if($invoice->contact)
                            <span class="text-gray-500 dark:text-gray-400">—</span>
                            <a href="{{ route('admin.contacts.show', $invoice->contact) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">{{ $invoice->contact->full_name }}</a>
                        @endif
                    @elseif($invoice->contact)
                        <a href="{{ route('admin.contacts.show', $invoice->contact) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">{{ $invoice->contact->full_name }}</a>
                    @else
                        —
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Betrag</dt>
                <dd class="mt-1 text-lg font-bold text-gray-900 dark:text-gray-100">{{ number_format($invoice->amount, 2, '.', "'") }} {{ $invoice->currency }}</dd>
                @if($invoice->vat_rate && $invoice->vat_rate > 0)
                    <dd class="text-xs text-gray-500 mt-0.5">inkl. {{ rtrim(rtrim(number_format($invoice->vat_rate, 2, '.', ''), '0'), '.') }}% MWST ({{ number_format($invoice->vat_amount, 2, '.', "'") }})</dd>
                @endif
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Rechnungsdatum</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $invoice->invoice_date ? $invoice->invoice_date->format('d.m.Y') : '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Fälligkeitsdatum</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $invoice->due_date ? $invoice->due_date->format('d.m.Y') : '—' }}</dd>
            </div>
            @if($invoice->project)
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Projekt</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                    <a href="{{ route('admin.projects.show', $invoice->project) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">{{ $invoice->project->name }}</a>
                </dd>
            </div>
            @endif
            @if($invoice->debitAccount || $invoice->creditAccount)
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Soll-Konto</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $invoice->debitAccount ? $invoice->debitAccount->number . ' ' . $invoice->debitAccount->name : '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Haben-Konto</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $invoice->creditAccount ? $invoice->creditAccount->number . ' ' . $invoice->creditAccount->name : '—' }}</dd>
            </div>
            @endif
        </dl>

        {{-- Positionen --}}
        @if($invoice->items->count())
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-medium text-gray-500 mb-3">Positionen</h3>
                <table class="min-w-full">
                    <thead>
                        <tr class="text-xs text-gray-500 dark:text-gray-400 uppercase">
                            <th class="text-left py-2 font-medium">Pos</th>
                            <th class="text-left py-2 font-medium">Beschreibung</th>
                            <th class="text-right py-2 font-medium">Menge</th>
                            <th class="text-right py-2 font-medium">Einzelpreis</th>
                            <th class="text-right py-2 font-medium">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($invoice->items as $i => $item)
                            <tr>
                                <td class="py-2 text-sm text-gray-400">{{ $i + 1 }}</td>
                                <td class="py-2 text-sm text-gray-900 dark:text-gray-100">{{ $item->description }}</td>
                                <td class="py-2 text-sm text-gray-700 dark:text-gray-300 text-right font-mono">{{ rtrim(rtrim(number_format($item->quantity, 3, '.', ''), '0'), '.') }}</td>
                                <td class="py-2 text-sm text-gray-700 dark:text-gray-300 text-right font-mono">{{ number_format($item->unit_price, 2, '.', "'") }}</td>
                                <td class="py-2 text-sm text-gray-900 dark:text-gray-100 text-right font-mono font-medium">{{ number_format($item->total, 2, '.', "'") }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        @if($invoice->vat_rate && $invoice->vat_rate > 0)
                            <tr class="border-t border-gray-200 dark:border-gray-700">
                                <td colspan="4" class="py-1.5 text-sm text-gray-500 text-right">Zwischensumme</td>
                                <td class="py-1.5 text-sm text-gray-700 dark:text-gray-300 text-right font-mono">{{ number_format($invoice->subtotal, 2, '.', "'") }}</td>
                            </tr>
                            <tr>
                                <td colspan="4" class="py-1.5 text-sm text-gray-500 text-right">MWST {{ rtrim(rtrim(number_format($invoice->vat_rate, 2, '.', ''), '0'), '.') }}%</td>
                                <td class="py-1.5 text-sm text-gray-700 dark:text-gray-300 text-right font-mono">{{ number_format($invoice->vat_amount, 2, '.', "'") }}</td>
                            </tr>
                            <tr class="border-t border-gray-300 dark:border-gray-600">
                                <td colspan="4" class="py-2 text-sm font-medium text-gray-700 dark:text-gray-300 text-right">Total {{ $invoice->currency }}</td>
                                <td class="py-2 text-sm font-bold text-gray-900 dark:text-gray-100 text-right font-mono">{{ number_format($invoice->amount, 2, '.', "'") }}</td>
                            </tr>
                        @else
                            <tr class="border-t border-gray-200 dark:border-gray-700">
                                <td colspan="4" class="py-2 text-sm font-medium text-gray-700 dark:text-gray-300 text-right">Total {{ $invoice->currency }}</td>
                                <td class="py-2 text-sm font-bold text-gray-900 dark:text-gray-100 text-right font-mono">{{ number_format($invoice->amount, 2, '.', "'") }}</td>
                            </tr>
                        @endif
                    </tfoot>
                </table>
            </div>
        @endif

        @if($invoice->notes)
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-medium text-gray-500 mb-1">Notizen</h3>
                <p class="text-sm text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ $invoice->notes }}</p>
            </div>
        @endif
    </div>

    <div class="mt-4">
        <a href="{{ route('admin.invoices.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-300">&larr; Zurück zur Übersicht</a>
    </div>
</div>
@endsection
