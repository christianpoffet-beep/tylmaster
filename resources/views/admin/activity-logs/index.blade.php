@extends('admin.layouts.app')

@section('title', 'Logfile')

@section('content')
<div class="mb-6">
    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">Aktivitätslog</h2>
    <p class="text-sm text-gray-500 mt-1">Alle Änderungen im System</p>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('admin.activity-logs.index') }}" class="mb-6">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Suche</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, Feld, Wert..."
                    class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Aktion</label>
                <select name="action" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Alle</option>
                    <option value="created" {{ request('action') === 'created' ? 'selected' : '' }}>Erstellt</option>
                    <option value="updated" {{ request('action') === 'updated' ? 'selected' : '' }}>Geändert</option>
                    <option value="deleted" {{ request('action') === 'deleted' ? 'selected' : '' }}>Gelöscht</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Bereich</label>
                <select name="model_type" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Alle</option>
                    @foreach($modelTypes as $type)
                        @php
                            $tempLog = new \App\Models\ActivityLog(['model_type' => $type]);
                        @endphp
                        <option value="{{ $type }}" {{ request('model_type') === $type ? 'selected' : '' }}>
                            {{ $tempLog->model_type_label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Von</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                    class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Bis</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                    class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-gray-800 dark:bg-gray-600 text-white text-sm rounded-lg hover:bg-gray-700 dark:hover:bg-gray-50 dark:hover:bg-gray-700/500">Filtern</button>
                <a href="{{ route('admin.activity-logs.export', request()->query()) }}" class="px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700">Excel Export</a>
                @if(request()->hasAny(['search', 'action', 'model_type', 'date_from', 'date_to']))
                    <a href="{{ route('admin.activity-logs.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 dark:hover:text-gray-200 dark:text-gray-100">Zurücksetzen</a>
                @endif
            </div>
        </div>
    </div>
</form>

{{-- Log Table --}}
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Datum</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Benutzer</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Aktion</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Bereich</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Feld</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Alter Wert</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Neuer Wert</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($logs as $log)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 dark:bg-gray-700/50">
                        <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">
                            {{ $log->created_at->format('d.m.Y H:i') }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap">
                            {{ $log->user_name }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $log->action_color }}">
                                {{ $log->action_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap">
                            {{ $log->model_type_label }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                            {{ $log->field ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 max-w-xs truncate" title="{{ $log->old_value }}">
                            {{ $log->old_value !== null ? Str::limit($log->old_value, 50) : 'null' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 max-w-xs truncate" title="{{ $log->new_value }}">
                            {{ $log->new_value !== null ? Str::limit($log->new_value, 50) : 'null' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Keine Einträge gefunden.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">{{ $logs->links() }}</div>
@endsection
