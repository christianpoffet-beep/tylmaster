@extends('admin.layouts.app')

@section('title', 'Vertragsvorlagen')

@php
    $typeLabels = $contractTypes->pluck('name', 'slug')->toArray();
    $typeColors = $contractTypes->pluck('color', 'slug')->toArray();
@endphp

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <form method="GET" action="{{ route('admin.contract-templates.index') }}" class="flex gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Vorlage suchen..." class="rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 w-48">
        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-700">Suchen</button>
        @if(request('search'))
            <a href="{{ route('admin.contract-templates.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Zurücksetzen</a>
        @endif
    </form>
    <a href="{{ route('admin.contract-templates.create') }}" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 whitespace-nowrap">+ Vorlage erstellen</a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <x-admin.sortable-header column="sort_order" :default="true">Reihenfolge</x-admin.sortable-header>
                    <x-admin.sortable-header column="name">Name</x-admin.sortable-header>
                    <x-admin.sortable-header column="contract_type_slug">Vertragstyp</x-admin.sortable-header>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vorschau</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($templates as $template)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $template->sort_order }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $template->name }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $typeColors[$template->contract_type_slug] ?? 'bg-gray-100 text-gray-600' }}">{{ $typeLabels[$template->contract_type_slug] ?? ucfirst($template->contract_type_slug) }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 max-w-xs truncate">{{ Str::limit($template->default_terms, 80) }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('admin.contract-templates.edit', $template) }}" class="text-sm text-blue-600 hover:text-blue-800">Bearbeiten</a>
                            <form method="POST" action="{{ route('admin.contract-templates.destroy', $template) }}" class="inline ml-2" onsubmit="return confirm('Vorlage wirklich löschen?')">
                                @csrf @method('DELETE')
                                <button class="text-sm text-red-500 hover:text-red-700">Löschen</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">Keine Vertragsvorlagen gefunden.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $templates->links() }}</div>
@endsection
