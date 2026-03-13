@extends('admin.layouts.app')

@section('title', 'Adresskreise')

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <form method="GET" action="{{ route('admin.address-circles.index') }}" class="flex flex-wrap gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Suche..."
               class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
        <button type="submit" class="px-4 py-2 bg-gray-800 dark:bg-gray-600 text-white text-sm rounded-lg hover:bg-gray-700">Filtern</button>
        @if(request('search'))
            <a href="{{ route('admin.address-circles.index') }}" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:hover:text-gray-100">Zurücksetzen</a>
        @endif
    </form>
    <a href="{{ route('admin.address-circles.create') }}" class="px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white text-sm rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 whitespace-nowrap">+ Neuer Adresskreis</a>
</div>

{{-- Desktop: Tabelle --}}
<div class="hidden md:block bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <x-admin.sortable-header column="name">Name</x-admin.sortable-header>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Info</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Kontakte</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Organisationen</th>
                    <x-admin.sortable-header column="created_at">Erstellt</x-admin.sortable-header>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($circles as $circle)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.address-circles.edit', $circle) }}" class="text-sm font-medium text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400">{{ $circle->name }}</a>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 max-w-xs truncate">{{ Str::limit($circle->info, 60) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $circle->contact_members_count ?? 0 }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $circle->organization_members_count ?? 0 }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $circle->created_at->format('d.m.Y') }}</td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <a href="{{ route('admin.address-circles.export', $circle) }}" class="text-sm text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300">Export</a>
                            <a href="{{ route('admin.address-circles.edit', $circle) }}" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">Bearbeiten</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Keine Adresskreise gefunden.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Mobile: Karten --}}
<div class="md:hidden space-y-3">
    @forelse($circles as $circle)
        <a href="{{ route('admin.address-circles.edit', $circle) }}" class="block bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 hover:border-blue-300 dark:hover:border-blue-600 transition-colors">
            <div class="flex items-start justify-between mb-2">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $circle->name }}</h3>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">{{ Str::limit($circle->info, 80) }}</p>
            <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                <span>{{ $circle->contact_members_count ?? 0 }} Kontakte</span>
                <span>{{ $circle->organization_members_count ?? 0 }} Organisationen</span>
            </div>
        </a>
    @empty
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-8 text-center text-sm text-gray-500 dark:text-gray-400">
            Keine Adresskreise gefunden.
        </div>
    @endforelse
</div>

<div class="mt-4">{{ $circles->links() }}</div>
@endsection
