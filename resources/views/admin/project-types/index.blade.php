@extends('admin.layouts.app')

@section('title', 'Projekttypen')

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <form method="GET" action="{{ route('admin.project-types.index') }}" class="flex gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Typ suchen..." class="rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 w-48">
        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-700">Suchen</button>
        @if(request('search'))
            <a href="{{ route('admin.project-types.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Zurücksetzen</a>
        @endif
    </form>
    <a href="{{ route('admin.project-types.create') }}" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 whitespace-nowrap">+ Typ erstellen</a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <x-admin.sortable-header column="sort_order" :default="true">Reihenfolge</x-admin.sortable-header>
                    <x-admin.sortable-header column="name">Name</x-admin.sortable-header>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Farbe</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Projekte</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($types as $type)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $type->sort_order }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $type->name }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $type->color }}">{{ $type->name }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $type->usage_count }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('admin.project-types.edit', $type) }}" class="text-sm text-blue-600 hover:text-blue-800">Bearbeiten</a>
                            @if($type->usage_count === 0)
                            <form method="POST" action="{{ route('admin.project-types.destroy', $type) }}" class="inline ml-2" onsubmit="return confirm('Typ wirklich löschen?')">
                                @csrf @method('DELETE')
                                <button class="text-sm text-red-500 hover:text-red-700">Löschen</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">Keine Projekttypen gefunden.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $types->links() }}</div>
@endsection
