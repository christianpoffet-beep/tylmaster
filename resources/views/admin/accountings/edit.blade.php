@extends('admin.layouts.app')

@section('title', 'Buchhaltung bearbeiten')

@section('content')
<div class="max-w-2xl">
    <form method="POST" action="{{ route('admin.accountings.update', $accounting) }}">
        @csrf @method('PUT')
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name *</label>
                <input type="text" name="name" id="name" value="{{ old('name', $accounting->name) }}" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="period_start" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Periode von *</label>
                    <input type="date" name="period_start" id="period_start" value="{{ old('period_start', $accounting->period_start->format('Y-m-d')) }}" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="period_end" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Periode bis *</label>
                    <input type="date" name="period_end" id="period_end" value="{{ old('period_end', $accounting->period_end->format('Y-m-d')) }}" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            <div>
                <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notizen</label>
                <textarea name="notes" id="notes" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes', $accounting->notes) }}</textarea>
            </div>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Speichern</button>
            <a href="{{ route('admin.accountings.show', $accounting) }}" class="px-5 py-2.5 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 dark:bg-gray-700/50">Abbrechen</a>
        </div>
    </form>

    <div class="mt-8 bg-red-50 rounded-xl border border-red-200 p-6">
        <h3 class="text-sm font-medium text-red-800 mb-2">Buchhaltung löschen</h3>
        <p class="text-sm text-red-600 mb-3">Alle Konten und Buchungen werden unwiderruflich gelöscht.</p>
        <form method="POST" action="{{ route('admin.accountings.destroy', $accounting) }}" onsubmit="return confirm('Buchhaltung wirklich löschen? Alle Konten und Buchungen gehen verloren.')">
            @csrf @method('DELETE')
            <button type="submit" class="px-4 py-2 bg-red-600 dark:bg-red-700 text-white text-sm rounded-lg hover:bg-red-700 dark:hover:bg-red-600">Löschen</button>
        </form>
    </div>
</div>
@endsection
