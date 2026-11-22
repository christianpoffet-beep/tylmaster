@extends('admin.layouts.app')

@section('title', 'Abrechnungen')

@php
    $chf = fn ($value) => number_format((float) $value, 2, '.', "'");
    $card = 'bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700';
    $input = 'w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500';
    $labelClass = 'block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1';
@endphp

@section('content')
@include('admin.royalties._tabs')

<div class="mb-6">
    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Abrechnungen an Künstler:innen und Labels</h2>
    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Ein Entwurf rechnet live mit den aktuellen Verträgen und Zahlungen. Erst «Abschliessen» bucht und schreibt gut – und lässt sich rückgängig machen, solange nichts ausbezahlt ist.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="{{ $card }} p-5">
        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Neue Abrechnung</h3>
        <form method="POST" action="{{ route('admin.royalties.statements.store') }}" class="space-y-3">
            @csrf
            <div>
                <label for="title" class="{{ $labelClass }}">Titel</label>
                <input type="text" name="title" id="title" value="{{ old('title', $defaults['title']) }}" required maxlength="255" class="{{ $input }}">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label for="statement_date" class="{{ $labelClass }}">Datum der Abrechnung</label>
                    <input type="date" name="statement_date" id="statement_date" value="{{ old('statement_date', $defaults['statement_date']) }}" required class="{{ $input }}">
                </div>
                <div>
                    <label for="period_from" class="{{ $labelClass }}">Nutzung von</label>
                    <input type="date" name="period_from" id="period_from" value="{{ old('period_from', $defaults['period_from']) }}" required class="{{ $input }}">
                </div>
                <div>
                    <label for="period_to" class="{{ $labelClass }}">bis</label>
                    <input type="date" name="period_to" id="period_to" value="{{ old('period_to', $defaults['period_to']) }}" required class="{{ $input }}">
                    @error('period_to') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <p class="text-xs text-gray-400 -mt-1">Verteilt werden die Rebeat-Zahlungen, die in diesem Zeitraum eingingen, nach der Nutzung im gleichen Zeitraum.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="accounting_id" class="{{ $labelClass }}">Buchen in</label>
                    <select name="accounting_id" id="accounting_id" class="{{ $input }}">
                        <option value="">— später wählen —</option>
                        @foreach($accountings as $accounting)
                            <option value="{{ $accounting->id }}" @selected((string) old('accounting_id', $defaults['accounting_id']) === (string) $accounting->id)>{{ $accounting->name }} ({{ $accounting->period_start->format('Y') }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="pre_booked" class="{{ $labelClass }} inline-flex items-center gap-1.5">
                        Bereits pauschal gebucht (CHF)
                        @include('admin.royalties._pre-booked-tip')
                    </label>
                    <input type="number" name="pre_booked" id="pre_booked" value="{{ old('pre_booked', 0) }}" step="0.01" min="0" class="{{ $input }}">
                </div>
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Entwurf erstellen</button>
        </form>
    </div>

    <div class="{{ $card }} overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Bisherige Abrechnungen</h3>
        </div>
        @if($statements->isEmpty())
            <p class="px-5 py-6 text-sm text-gray-500 dark:text-gray-400">Noch keine.</p>
        @else
            <table class="w-full text-sm">
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($statements as $statement)
                        <tr>
                            <td class="px-5 py-2.5">
                                <a href="{{ route('admin.royalties.statements.show', $statement) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $statement->title }}</a>
                                <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $statement->statement_date->format('d.m.Y') }} · Nutzung {{ $statement->period_from->format('m.Y') }} – {{ $statement->period_to->format('m.Y') }}</span>
                            </td>
                            <td class="px-3 py-2.5 text-right tabular-nums text-gray-900 dark:text-gray-100">{{ $statement->payments_amount !== null ? 'CHF ' . $chf($statement->payments_amount) : '' }}</td>
                            <td class="px-5 py-2.5 text-right">
                                @if($statement->is_final)
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">abgeschlossen</span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">Entwurf</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
