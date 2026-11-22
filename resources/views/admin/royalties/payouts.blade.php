@extends('admin.layouts.app')

@section('title', 'Saldi & Auszahlungen')

@php
    $chf = fn ($value) => number_format((float) $value, 2, '.', "'");
    $card = 'bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700';
    $input = 'rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500';
    $partyUrl = fn ($p) => $p->organization ? route('admin.organizations.show', $p->organization) : route('admin.contacts.show', $p->contact);
@endphp

@section('content')
@include('admin.royalties._tabs')

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-end mb-6 gap-4">
    <div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Saldi & Auszahlungen</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Was Künstler:innen und Labels aus den Abrechnungen zugute haben. Vorgeschlagen wird die Auszahlung ab CHF {{ $chf($minimum) }} – kleinere Beträge lassen sich von Hand dazunehmen.</p>
    </div>
    <form method="GET" action="{{ route('admin.royalties.payouts.create') }}" class="flex items-end gap-2">
        <div>
            <label for="accounting" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Auszahlen aus</label>
            <select name="accounting" id="accounting" class="{{ $input }}">
                @foreach($accountings as $accounting)
                    <option value="{{ $accounting->id }}" @selected($currentAccounting?->id === $accounting->id)>{{ $accounting->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 whitespace-nowrap" @disabled($accountings->isEmpty())>Auszahlung vorbereiten</button>
    </form>
</div>

<div class="{{ $card }} overflow-hidden mb-6">
    <div class="px-5 py-3 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Saldi ({{ $parties->count() }}) · total CHF {{ $chf($parties->sum('balance')) }}</h3>
    </div>
    @if($parties->isEmpty())
        <p class="px-5 py-6 text-sm text-gray-500 dark:text-gray-400">Noch keine – die Saldi entstehen mit der ersten abgeschlossenen Abrechnung.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr class="text-xs text-gray-500 dark:text-gray-400 text-left">
                        <th class="px-5 py-2 font-medium">Partei</th>
                        <th class="px-3 py-2 font-medium">IBAN</th>
                        <th class="px-3 py-2 font-medium">Letzte Bewegung</th>
                        <th class="px-5 py-2 font-medium text-right">Saldo CHF</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($parties as $party)
                        <tr>
                            <td class="px-5 py-2"><a href="{{ $partyUrl($party) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $party->name }}</a></td>
                            <td class="px-3 py-2 text-xs">
                                @if($party->iban_ok)
                                    <span class="font-mono text-gray-600 dark:text-gray-300">{{ \App\Support\Iban::format($party->iban) }}</span>
                                @elseif($party->iban)
                                    <span class="text-red-600 dark:text-red-400">ungültig: {{ $party->iban }}</span>
                                @else
                                    <span class="text-amber-600 dark:text-amber-400">fehlt</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-gray-500 dark:text-gray-400">{{ $party->last_entry ? \Illuminate\Support\Carbon::parse($party->last_entry)->format('d.m.Y') : '' }}</td>
                            <td class="px-5 py-2 text-right tabular-nums {{ $party->balance >= $minimum ? 'font-medium text-gray-900 dark:text-gray-100' : 'text-gray-600 dark:text-gray-300' }}">{{ $chf($party->balance) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="{{ $card }} overflow-hidden">
    <div class="px-5 py-3 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Auszahlungen</h3>
    </div>
    @if($payouts->isEmpty())
        <p class="px-5 py-6 text-sm text-gray-500 dark:text-gray-400">Noch keine.</p>
    @else
        <table class="w-full text-sm">
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($payouts as $payout)
                    <tr>
                        <td class="px-5 py-2"><a href="{{ route('admin.royalties.payouts.show', $payout) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $payout->execution_date->format('d.m.Y') }}</a></td>
                        <td class="px-3 py-2 text-gray-500 dark:text-gray-400">{{ $payout->items_count }} Empfänger · {{ $payout->accounting?->name }}</td>
                        <td class="px-5 py-2 text-right tabular-nums text-gray-900 dark:text-gray-100">CHF {{ $chf($payout->total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
