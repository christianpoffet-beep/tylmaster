@extends('admin.layouts.app')

@section('title', 'Ausgabe bearbeiten')

@section('content')
<div class="max-w-3xl">
    <form method="POST" action="{{ route('admin.expenses.update', $expense) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.finances.expenses._form')

        <div class="mt-4 flex gap-3">
            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Ausgabe aktualisieren</button>
            <a href="{{ route('admin.expenses.index') }}" class="px-5 py-2.5 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">Abbrechen</a>
            @if($expense->accounting)
                <a href="{{ route('admin.accountings.journal', $expense->accounting) }}" class="px-5 py-2.5 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">Zum Buchungsjournal</a>
            @endif
        </div>
    </form>

    {{-- Bestehende Belege --}}
    @if($expense->documents->count())
        <div class="mt-8 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Belege ({{ $expense->documents->count() }})</h3>
            <div class="space-y-2">
                @foreach($expense->documents as $doc)
                    <div class="flex items-center justify-between py-2 px-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200">{{ $doc->file_extension }}</span>
                            <span class="text-sm text-gray-900 dark:text-gray-100 truncate">{{ $doc->title }}</span>
                            <span class="text-xs text-gray-400">{{ number_format($doc->file_size / 1024, 0) }} KB</span>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <a href="{{ route('admin.documents.download', $doc) }}" class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">Download</a>
                            <form method="POST" action="{{ route('admin.expenses.documents.destroy', [$expense, $doc]) }}" onsubmit="return confirm('Beleg löschen?')" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300">Löschen</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Delete -->
    <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
        <form method="POST" action="{{ route('admin.expenses.destroy', $expense) }}"
              onsubmit="return confirm('{{ $expense->booking && !$expense->booking->bank_transaction_id ? 'Ausgabe und ihre Buchung löschen?' : 'Ausgabe wirklich löschen? Eine Bankbuchung bleibt im Journal.' }}')">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-5 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700">Ausgabe löschen</button>
        </form>
    </div>
</div>
@endsection
