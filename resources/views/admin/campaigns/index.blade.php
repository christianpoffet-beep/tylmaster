@extends('admin.layouts.app')

@section('title', 'Kampagnen')

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Kampagnen</h1>
    <a href="{{ route('admin.campaigns.create') }}" class="px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white text-sm rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 whitespace-nowrap">+ Neue Kampagne</a>
</div>

<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
    <form method="GET" action="{{ route('admin.campaigns.index') }}" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Suche</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, Betreff..."
                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Status</label>
            <select name="status" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">Alle</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Entwurf</option>
                <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Geplant</option>
                <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Gesendet</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Abgebrochen</option>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 bg-gray-800 dark:bg-gray-600 text-white text-sm rounded-lg hover:bg-gray-700 dark:hover:bg-gray-500">Filtern</button>
            @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('admin.campaigns.index') }}" class="px-3 py-2 text-sm text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200" title="Zurücksetzen">&times;</a>
            @endif
        </div>
    </form>
</div>

<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <x-admin.sortable-header column="name">Name</x-admin.sortable-header>
                    <x-admin.sortable-header column="status">Status</x-admin.sortable-header>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Vorlage</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Adresskreis</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Sprache</th>
                    <x-admin.sortable-header column="created_at" :default="true" dir="desc">Erstellt</x-admin.sortable-header>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($campaigns as $campaign)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 dark:bg-gray-700/50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                            <a href="{{ route('admin.campaigns.show', $campaign) }}" class="hover:text-blue-600 dark:hover:text-blue-400">{{ $campaign->name }}</a>
                            @if($campaign->subject)
                                <p class="text-xs text-gray-400 dark:text-gray-500 truncate max-w-xs">{{ $campaign->subject }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $campaign->status_color }}">{{ $campaign->status_label }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $campaign->template?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                            @if($campaign->addressCircle)
                                <a href="{{ route('admin.address-circles.edit', $campaign->addressCircle) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $campaign->addressCircle->name }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ strtoupper($campaign->language) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $campaign->created_at->format('d.m.Y') }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('admin.campaigns.edit', $campaign) }}" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">Bearbeiten</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Keine Kampagnen gefunden.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4 flex items-center justify-between">
    <span class="text-sm text-gray-500 dark:text-gray-400">{{ $campaigns->total() }} Kampagnen</span>
    {{ $campaigns->links() }}
</div>
@endsection
