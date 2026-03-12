@extends('admin.layouts.app')

@section('title', $chartTemplate->name)

@php
    $typeLabels = ['asset' => 'Aktiven', 'liability' => 'Passiven', 'income' => 'Ertrag', 'expense' => 'Aufwand'];
    $typeColors = ['asset' => 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300', 'liability' => 'bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300', 'income' => 'bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300', 'expense' => 'bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300'];
@endphp

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $chartTemplate->name }}</h2>
        @if($chartTemplate->description)
            <p class="text-sm text-gray-500 mt-1">{{ $chartTemplate->description }}</p>
        @endif
    </div>
    <a href="{{ route('admin.chart-templates.edit', $chartTemplate) }}" class="px-4 py-2 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 dark:bg-gray-700/50">Bearbeiten</a>
</div>

{{-- Konten-Tabelle --}}
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase w-24">Konto-Nr.</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Bezeichnung</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase w-28">Typ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase w-20">Gruppe</th>
                    <th class="px-4 py-3 w-24"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($chartTemplate->accounts as $account)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ $account->is_header ? 'bg-gray-50' : '' }}">
                        <td class="px-4 py-2 text-sm {{ $account->is_header ? 'font-bold text-gray-900' : 'text-gray-700 pl-8' }}">{{ $account->number }}</td>
                        <td class="px-4 py-2 text-sm {{ $account->is_header ? 'font-bold text-gray-900' : 'text-gray-700' }}">{{ $account->name }}</td>
                        <td class="px-4 py-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $typeColors[$account->type] ?? '' }}">{{ $typeLabels[$account->type] ?? $account->type }}</span>
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-400">{{ $account->is_header ? 'Ja' : '' }}</td>
                        <td class="px-4 py-2 text-right">
                            <form method="POST" action="{{ route('admin.chart-templates.accounts.destroy', $account) }}" class="inline" onsubmit="return confirm('Konto entfernen?')">
                                @csrf @method('DELETE')
                                <button class="text-xs text-red-500 hover:text-red-700">Entfernen</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Noch keine Konten in dieser Vorlage.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Konto hinzufügen --}}
<div class="mt-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
    <h3 class="text-sm font-medium text-gray-900 mb-4">Konto hinzufügen</h3>
    <form method="POST" action="{{ route('admin.chart-templates.accounts.store', $chartTemplate) }}">
        @csrf
        <div class="grid grid-cols-1 sm:grid-cols-5 gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Konto-Nr. *</label>
                <input type="text" name="number" value="{{ old('number') }}" required placeholder="z.B. 1000" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-gray-500 mb-1">Bezeichnung *</label>
                <input type="text" name="name" value="{{ old('name') }}" required placeholder="z.B. Kasse" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Typ *</label>
                <select name="type" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @foreach($accountTypes as $val => $label)
                        <option value="{{ $val }}" {{ old('type') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-3">
                <label class="inline-flex items-center gap-1.5 pb-2">
                    <input type="checkbox" name="is_header" value="1" {{ old('is_header') ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-xs text-gray-600 dark:text-gray-300">Gruppe</span>
                </label>
                <button type="submit" class="px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white text-sm rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600">Hinzufügen</button>
            </div>
        </div>
        @if($errors->any())
            <p class="text-red-500 text-xs mt-2">{{ $errors->first() }}</p>
        @endif
    </form>
</div>
@endsection
