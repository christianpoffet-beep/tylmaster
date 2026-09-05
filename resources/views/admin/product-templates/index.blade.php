@extends('admin.layouts.app')

@section('title', 'Produktvorlagen')

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <form method="GET" action="{{ route('admin.product-templates.index') }}" class="flex gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Vorlage suchen..." class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500 w-48">
        <select name="language" onchange="this.form.submit()" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Alle Sprachen</option>
            @foreach(['de' => 'Deutsch', 'en' => 'English', 'fr' => 'Français', 'it' => 'Italiano', 'es' => 'Español'] as $code => $label)
                <option value="{{ $code }}" {{ request('language') === $code ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 bg-gray-800 dark:bg-gray-600 text-white text-sm rounded-lg hover:bg-gray-700 dark:hover:bg-gray-500">Suchen</button>
        @if(request('search') || request('language'))
            <a href="{{ route('admin.product-templates.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 dark:hover:text-gray-200 dark:text-gray-100">Zurücksetzen</a>
        @endif
    </form>
    <a href="{{ route('admin.product-templates.create') }}" class="px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white text-sm rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 whitespace-nowrap">+ Neue Vorlage</a>
</div>

<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <x-admin.sortable-header column="name">Name</x-admin.sortable-header>
                    <x-admin.sortable-header column="language">Sprache</x-admin.sortable-header>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Typen</th>
                    <x-admin.sortable-header column="sort_order" :default="true">Sortierung</x-admin.sortable-header>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($productTemplates as $template)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $template->name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 uppercase">{{ $template->language }}</td>
                        <td class="px-4 py-3 text-gray-900 dark:text-gray-100">
                            <div class="flex flex-wrap gap-1">
                                @foreach($template->default_product_types ?? [] as $type)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">{{ $type }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $template->sort_order }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap text-gray-900 dark:text-gray-100">
                            <a href="{{ route('admin.product-templates.edit', $template) }}" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">Bearbeiten</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Keine Produktvorlagen gefunden.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
