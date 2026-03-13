@extends('admin.layouts.app')

@section('title', 'Kontakte')

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Kontakte</h1>
    <div class="flex gap-2">
        <a href="{{ route('admin.contacts.export', request()->query()) }}" class="px-4 py-2 bg-green-600 dark:bg-green-700 text-white text-sm rounded-lg hover:bg-green-700 dark:hover:bg-green-600 whitespace-nowrap">
            <svg class="inline w-4 h-4 mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            Excel Export
        </a>
        <a href="{{ route('admin.contacts.create') }}" class="px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white text-sm rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 whitespace-nowrap">+ Neuer Kontakt</a>
    </div>
</div>

<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
    <form method="GET" action="{{ route('admin.contacts.index') }}" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-7 gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Suche</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, E-Mail..." class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Typ</label>
            <select name="type" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">Alle Typen</option>
                @foreach($contactTypes as $ct)
                    <option value="{{ $ct->slug }}" {{ request('type') === $ct->slug ? 'selected' : '' }}>{{ $ct->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Genre</label>
            <select name="genre" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">Alle Genres</option>
                @foreach($genres as $g)
                    <option value="{{ $g->id }}" {{ request('genre') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Geschlecht</label>
            <select name="gender" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">Alle</option>
                <option value="male" {{ request('gender') === 'male' ? 'selected' : '' }}>Männlich</option>
                <option value="female" {{ request('gender') === 'female' ? 'selected' : '' }}>Weiblich</option>
                <option value="other" {{ request('gender') === 'other' ? 'selected' : '' }}>Andere</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Ort</label>
            <input type="text" name="city" value="{{ request('city') }}" placeholder="Ort..." class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Land</label>
            <select name="country" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">Alle Länder</option>
                @foreach($countries as $c)
                    <option value="{{ $c }}" {{ request('country') === $c ? 'selected' : '' }}>{{ $c }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 bg-gray-800 dark:bg-gray-600 text-white text-sm rounded-lg hover:bg-gray-700 dark:hover:bg-gray-500">Filtern</button>
            @if(request()->hasAny(['search', 'type', 'genre', 'gender', 'city', 'country']))
                <a href="{{ route('admin.contacts.index') }}" class="px-3 py-2 text-sm text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200" title="Zurücksetzen">&times;</a>
            @endif
        </div>
    </form>
</div>

<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Ref</th>
                    <x-admin.sortable-header column="last_name" :default="true">Name</x-admin.sortable-header>
                    <x-admin.sortable-header column="email">E-Mail</x-admin.sortable-header>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Typ</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Genre</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Ort</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($contacts as $contact)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 dark:bg-gray-700/50">
                        <td class="px-4 py-3 text-xs text-gray-400 dark:text-gray-500 font-mono">{{ $contact->ref_nr }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                            <a href="{{ route('admin.contacts.show', $contact) }}" class="hover:text-blue-600">{{ $contact->full_name }}</a>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $contact->email ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                @foreach($contact->types ?? [] as $type)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">{{ ucfirst($type) }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $contact->genres->pluck('name')->implode(', ') ?: '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $contact->city ?? '-' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.contacts.edit', $contact) }}" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">Bearbeiten</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Keine Kontakte gefunden.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4 flex items-center justify-between">
    <span class="text-sm text-gray-500 dark:text-gray-400">{{ $contacts->total() }} Kontakte gefunden</span>
    {{ $contacts->links() }}
</div>
@endsection
