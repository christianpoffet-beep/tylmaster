@extends('admin.layouts.app')

@section('title', 'Kontoplan-Vorlagen')

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <form method="GET" action="{{ route('admin.chart-templates.index') }}" class="flex gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Vorlage suchen..." class="rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 w-48">
        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-700">Suchen</button>
        @if(request('search'))
            <a href="{{ route('admin.chart-templates.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Zurücksetzen</a>
        @endif
    </form>
    <a href="{{ route('admin.chart-templates.create') }}" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 whitespace-nowrap">+ Neue Vorlage</a>
</div>

@php
    $orgTypes = \App\Models\OrganizationType::orderBy('sort_order')->pluck('name', 'slug')->toArray();
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Org.-Typ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Konten</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nutzung</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($templates as $tpl)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium">
                            <a href="{{ route('admin.chart-templates.show', $tpl) }}" class="text-gray-900 hover:text-blue-600">{{ $tpl->name }}</a>
                            @if($tpl->description)
                                <p class="text-xs text-gray-400 truncate max-w-xs">{{ $tpl->description }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $orgTypes[$tpl->organization_type_slug] ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $tpl->accounts_count }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $tpl->usage_count }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.chart-templates.edit', $tpl) }}" class="text-sm text-blue-600 hover:text-blue-800">Bearbeiten</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">Keine Vorlagen vorhanden.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $templates->links() }}</div>
@endsection
