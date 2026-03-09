@extends('admin.layouts.app')

@section('title', 'Projekte')

@php
    $typeLabels = $projectTypes->pluck('name', 'slug')->toArray();
    $typeColors = $projectTypes->pluck('color', 'slug')->toArray();
    $statusLabels = ['planned' => 'Geplant', 'in_progress' => 'In Arbeit', 'completed' => 'Abgeschlossen', 'paused' => 'Pausiert'];
    $statusColors = ['planned' => 'bg-blue-100 text-blue-700', 'in_progress' => 'bg-yellow-100 text-yellow-700', 'completed' => 'bg-green-100 text-green-700', 'paused' => 'bg-gray-100 text-gray-600'];
@endphp

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <form method="GET" action="{{ route('admin.projects.index') }}" class="flex flex-wrap gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Suche..." class="rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
        <select name="type" class="rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Alle Typen</option>
            @foreach($projectTypes as $pt)
                <option value="{{ $pt->slug }}" {{ request('type') === $pt->slug ? 'selected' : '' }}>{{ $pt->name }}</option>
            @endforeach
        </select>
        <select name="status" class="rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Alle Status</option>
            @foreach($statusLabels as $value => $label)
                <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-700">Filtern</button>
        @if(request('search') || request('type') || request('status'))
            <a href="{{ route('admin.projects.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Zurücksetzen</a>
        @endif
    </form>
    <a href="{{ route('admin.projects.create') }}" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 whitespace-nowrap">+ Neues Projekt</a>
</div>

{{-- Desktop: Tabelle --}}
<div class="hidden md:block bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <x-admin.sortable-header column="name">Name</x-admin.sortable-header>
                    <x-admin.sortable-header column="type">Typ</x-admin.sortable-header>
                    <x-admin.sortable-header column="status">Status</x-admin.sortable-header>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kontakte</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aufgaben</th>
                    <x-admin.sortable-header column="deadline">Deadline</x-admin.sortable-header>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($projects as $project)
                    @php
                        $totalTasks = $project->tasks_count ?? 0;
                        $completedTasks = $project->tasks->where('is_completed', true)->count();
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.projects.show', $project) }}" class="text-sm font-medium text-gray-900 hover:text-blue-600">{{ $project->name }}</a>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $typeColors[$project->type] ?? 'bg-gray-100 text-gray-600' }}">{{ $typeLabels[$project->type] ?? $project->type }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $statusColors[$project->status] ?? 'bg-gray-100 text-gray-600' }}">{{ $statusLabels[$project->status] ?? $project->status }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $project->contacts_count ?? 0 }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $completedTasks }}/{{ $totalTasks }}</td>
                        <td class="px-4 py-3 text-sm {{ $project->deadline && $project->deadline->isPast() && $project->status !== 'completed' ? 'text-red-600 font-medium' : 'text-gray-500' }}">
                            {{ $project->deadline ? $project->deadline->format('d.m.Y') : '-' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.projects.edit', $project) }}" class="text-sm text-blue-600 hover:text-blue-800">Bearbeiten</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">Keine Projekte gefunden.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Mobile: Karten --}}
<div class="md:hidden space-y-3">
    @forelse($projects as $project)
        @php
            $totalTasks = $project->tasks_count ?? 0;
            $completedTasks = $project->tasks->where('is_completed', true)->count();
        @endphp
        <a href="{{ route('admin.projects.show', $project) }}" class="block bg-white rounded-xl shadow-sm border border-gray-200 p-4 hover:border-blue-300 transition-colors">
            <div class="flex items-start justify-between mb-2">
                <h3 class="text-sm font-semibold text-gray-900">{{ $project->name }}</h3>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $typeColors[$project->type] ?? 'bg-gray-100 text-gray-600' }} ml-2 flex-shrink-0">{{ $typeLabels[$project->type] ?? $project->type }}</span>
            </div>
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <span class="inline-flex items-center px-2 py-0.5 rounded font-medium {{ $statusColors[$project->status] ?? 'bg-gray-100 text-gray-600' }}">{{ $statusLabels[$project->status] ?? $project->status }}</span>
                @if($totalTasks > 0)
                    <span class="text-gray-500">{{ $completedTasks }}/{{ $totalTasks }} Aufgaben</span>
                @endif
                @if($project->contacts_count > 0)
                    <span class="text-gray-500">{{ $project->contacts_count }} Kontakte</span>
                @endif
                @if($project->deadline)
                    <span class="{{ $project->deadline->isPast() && $project->status !== 'completed' ? 'text-red-600 font-medium' : 'text-gray-500' }}">
                        {{ $project->deadline->format('d.m.Y') }}
                    </span>
                @endif
            </div>
        </a>
    @empty
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center text-sm text-gray-500">
            Keine Projekte gefunden.
        </div>
    @endforelse
</div>

<div class="mt-4">{{ $projects->links() }}</div>
@endsection
