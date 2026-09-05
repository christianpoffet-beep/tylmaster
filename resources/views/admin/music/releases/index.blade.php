@extends('admin.layouts.app')

@section('title', 'Produkte')

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Produkte</h1>
    <a href="{{ route('admin.releases.create') }}" class="px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white text-sm rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 whitespace-nowrap">+ Neues Produkt</a>
</div>

<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
    <form method="GET" action="{{ route('admin.releases.index') }}" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Suche</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Titel, UPC, Label..." class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Typ</label>
            <select name="product_type" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">Alle</option>
                @foreach(\App\Models\Setting::productTypes() as $group => $types)
                    <optgroup label="{{ $group }}">
                        @foreach($types as $type)
                            <option value="{{ $type }}" {{ request('product_type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 bg-gray-800 dark:bg-gray-600 text-white text-sm rounded-lg hover:bg-gray-700 dark:hover:bg-gray-500">Filtern</button>
            @if(request()->hasAny(['search', 'product_type']))
                <a href="{{ route('admin.releases.index') }}" class="px-3 py-2 text-sm text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200" title="Zurücksetzen">&times;</a>
            @endif
        </div>
    </form>
</div>

<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <x-admin.sortable-header column="title">Titel</x-admin.sortable-header>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Typ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Label Name</th>
                    <x-admin.sortable-header column="upc">UPC</x-admin.sortable-header>
                    <x-admin.sortable-header column="release_date">Datum</x-admin.sortable-header>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Tracks</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($releases as $release)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                            <div class="flex items-center gap-3">
                                @if($release->cover_image_path)
                                    <img src="{{ Storage::disk('public')->url($release->cover_image_path) }}" alt="" class="w-10 h-10 object-cover rounded shadow-sm flex-shrink-0">
                                @endif
                                <a href="{{ route('admin.releases.show', $release) }}" class="hover:text-blue-600 dark:hover:text-blue-400">{{ $release->title }}</a>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $types = is_array($release->product_type) ? $release->product_type : ($release->product_type ? [$release->product_type] : []);
                            @endphp
                            @if(count($types))
                                <div class="flex flex-wrap gap-1">
                                    @foreach($types as $pt)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300">{{ $pt }}</span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-sm text-gray-400 dark:text-gray-500">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $release->label_name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 font-mono">{{ $release->upc ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $release->release_date ? $release->release_date->format('d.m.Y') : '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $release->tracks_count ?? $release->tracks->count() }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.releases.edit', $release) }}" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">Bearbeiten</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Keine Produkte gefunden.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4 flex items-center justify-between">
    <span class="text-sm text-gray-500 dark:text-gray-400">{{ $releases->total() }} Produkte gefunden</span>
    {{ $releases->links() }}
</div>
@endsection
