@extends('admin.layouts.app')

@section('title', 'Aufgaben')

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <form method="GET" action="{{ route('admin.tasks.index') }}" class="flex flex-wrap gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Suche..." class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
        <select name="status" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Alle</option>
            <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Offen</option>
            <option value="done" {{ request('status') === 'done' ? 'selected' : '' }}>Erledigt</option>
        </select>
        <select name="priority" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Alle Prioritäten</option>
            <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>Hoch</option>
            <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>Mittel</option>
            <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>Tief</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-gray-800 dark:bg-gray-600 text-white text-sm rounded-lg hover:bg-gray-700 dark:hover:bg-gray-50 dark:hover:bg-gray-700/500">Filtern</button>
        @if(request('search') || request('status') || request('priority'))
            <a href="{{ route('admin.tasks.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 dark:hover:text-gray-200 dark:text-gray-100">Zurücksetzen</a>
        @endif
    </form>
    <a href="{{ route('admin.tasks.create') }}" class="px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white text-sm rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 whitespace-nowrap">+ Neue Aufgabe</a>
</div>

{{-- Desktop: Tabelle --}}
<div class="hidden md:block bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-4 py-3 w-10"></th>
                    <x-admin.sortable-header column="title">Aufgabe</x-admin.sortable-header>
                    <x-admin.sortable-header column="priority">Priorität</x-admin.sortable-header>
                    <x-admin.sortable-header column="due_date">Fällig am</x-admin.sortable-header>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Projekt</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($tasks as $task)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ $task->is_completed ? 'opacity-50' : '' }}">
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('admin.tasks.toggle', $task) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="w-5 h-5 rounded border-2 flex items-center justify-center {{ $task->is_completed ? 'bg-blue-500 border-blue-500 text-white' : 'border-gray-300 hover:border-blue-400' }}">
                                    @if($task->is_completed)
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    @endif
                                </button>
                            </form>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <a href="{{ route('admin.tasks.show', $task) }}" class="font-medium {{ $task->is_completed ? 'line-through text-gray-400' : 'text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400' }}">{{ $task->title }}</a>
                        </td>
                        <td class="px-4 py-3">
                            @if($task->priority === 'high')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300">Hoch</span>
                            @elseif($task->priority === 'medium')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 dark:bg-yellow-900/50 text-yellow-700 dark:text-yellow-300">Mittel</span>
                            @elseif($task->priority === 'low')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">Tief</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm {{ $task->isOverdue() ? 'text-red-600 font-medium' : 'text-gray-500' }}">
                            {{ $task->due_date?->format('d.m.Y') ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                            @if($task->project)
                                <a href="{{ route('admin.projects.show', $task->project) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">{{ $task->project->name }}</a>
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.tasks.edit', $task) }}" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">Bearbeiten</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Keine Aufgaben gefunden.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Mobile: Karten --}}
<div class="md:hidden space-y-2">
    @forelse($tasks as $task)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 {{ $task->is_completed ? 'opacity-50' : '' }} {{ $task->isOverdue() ? 'border-l-4 border-l-red-400' : '' }}">
            <div class="flex items-start gap-3">
                <form method="POST" action="{{ route('admin.tasks.toggle', $task) }}" class="pt-0.5">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="w-5 h-5 rounded border-2 flex items-center justify-center flex-shrink-0 {{ $task->is_completed ? 'bg-blue-500 border-blue-500 text-white' : 'border-gray-300 hover:border-blue-400' }}">
                        @if($task->is_completed)
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        @endif
                    </button>
                </form>
                <div class="flex-1 min-w-0">
                    <a href="{{ route('admin.tasks.show', $task) }}" class="text-sm font-medium {{ $task->is_completed ? 'line-through text-gray-400' : 'text-gray-900' }}">{{ $task->title }}</a>
                    <div class="flex flex-wrap items-center gap-2 mt-1.5 text-xs">
                        @if($task->priority)
                            @if($task->priority === 'high')
                                <span class="inline-flex items-center px-2 py-0.5 rounded font-medium bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300">Hoch</span>
                            @elseif($task->priority === 'medium')
                                <span class="inline-flex items-center px-2 py-0.5 rounded font-medium bg-yellow-100 dark:bg-yellow-900/50 text-yellow-700 dark:text-yellow-300">Mittel</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">Tief</span>
                            @endif
                        @endif
                        @if($task->due_date)
                            <span class="{{ $task->isOverdue() ? 'text-red-600 font-medium' : 'text-gray-500' }}">
                                {{ $task->due_date->format('d.m.Y') }}
                            </span>
                        @endif
                        @if($task->project)
                            <a href="{{ route('admin.projects.show', $task->project) }}" class="text-blue-600">{{ $task->project->name }}</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-8 text-center text-sm text-gray-500 dark:text-gray-400">
            Keine Aufgaben gefunden.
        </div>
    @endforelse
</div>

<div class="mt-4">{{ $tasks->links() }}</div>
@endsection
