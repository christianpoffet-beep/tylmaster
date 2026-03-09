@extends('admin.layouts.app')

@section('title', 'Kontakte')

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <form method="GET" action="{{ route('admin.contacts.index') }}" class="flex flex-wrap gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Suche..." class="rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
        <select name="type" class="rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Alle Typen</option>
            @foreach($contactTypes as $ct)
                <option value="{{ $ct->slug }}" {{ request('type') === $ct->slug ? 'selected' : '' }}>{{ $ct->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-700">Filtern</button>
        @if(request('search') || request('type'))
            <a href="{{ route('admin.contacts.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Zurücksetzen</a>
        @endif
    </form>
    <a href="{{ route('admin.contacts.create') }}" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 whitespace-nowrap">+ Neuer Kontakt</a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <x-admin.sortable-header column="last_name" :default="true">Name</x-admin.sortable-header>
                    <x-admin.sortable-header column="email">E-Mail</x-admin.sortable-header>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Typ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ort</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($contacts as $contact)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">
                            <a href="{{ route('admin.contacts.show', $contact) }}" class="hover:text-blue-600">{{ $contact->full_name }}</a>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $contact->email ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                @foreach($contact->types ?? [] as $type)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">{{ ucfirst($type) }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $contact->city ?? '-' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.contacts.edit', $contact) }}" class="text-sm text-blue-600 hover:text-blue-800">Bearbeiten</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">Keine Kontakte gefunden.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">{{ $contacts->links() }}</div>
@endsection
