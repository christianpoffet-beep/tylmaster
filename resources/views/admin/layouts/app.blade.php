<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - TYL Admin</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Dark mode (before render to prevent flash) -->
    <script>
        (function() {
            const theme = localStorage.getItem('theme') || 'system';
            if (theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-gray-100 dark:bg-gray-950 font-sans antialiased">
    <div class="min-h-screen flex flex-col">

        {{-- Top Navigation --}}
        <nav class="bg-gray-900 text-white shadow-lg relative z-50" x-data="{ mobileOpen: false }">
            <div class="max-w-full mx-auto px-3 sm:px-4">
                <div class="flex items-center h-12">
                    {{-- Logo --}}
                    <a href="{{ route('admin.dashboard') }}" class="text-lg font-bold tracking-wider mr-6 flex-shrink-0">TYL</a>

                    {{-- Desktop Navigation --}}
                    <div class="hidden md:flex items-center gap-0.5 flex-1 min-w-0">
                        @php
                            $navItems = [
                                ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'match' => 'admin.dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z'],
                                ['route' => 'admin.contacts.index', 'label' => 'Kontakte', 'match' => 'admin.contacts.*', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                                ['route' => 'admin.organizations.index', 'label' => 'Organisationen', 'match' => 'admin.organizations.*', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                                ['route' => 'admin.documents.index', 'label' => 'Dokumente', 'match' => 'admin.documents.*', 'icon' => 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z'],
                            ];
                        @endphp

                        @foreach($navItems as $item)
                            @php $matches = collect(explode('||', $item['match']))->contains(fn($p) => request()->routeIs($p)); @endphp
                            <a href="{{ route($item['route']) }}"
                               class="flex items-center gap-1.5 px-2.5 py-1.5 text-sm font-medium rounded-md transition-colors {{ $matches ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"></path></svg>
                                <span>{{ $item['label'] }}</span>
                            </a>
                        @endforeach

                        {{-- Projekte Dropdown --}}
                        @php
                            $projekteActive = request()->routeIs('admin.projects.*') || request()->routeIs('admin.tasks.*') || request()->routeIs('admin.contracts.*') || request()->routeIs('admin.tracks.*') || request()->routeIs('admin.releases.*') || request()->routeIs('admin.submissions.*') || request()->routeIs('admin.artworks.*') || request()->routeIs('admin.photos.*');
                            $projekteItems = [
                                ['route' => 'admin.projects.index', 'label' => 'Projekte', 'match' => 'admin.projects.*', 'icon' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
                                ['route' => 'admin.tasks.index', 'label' => 'Aufgaben', 'match' => 'admin.tasks.*', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
                                ['route' => 'admin.contracts.index', 'label' => 'Verträge', 'match' => 'admin.contracts.*', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                                ['route' => 'admin.tracks.index', 'label' => 'Musik', 'match' => 'admin.tracks.*||admin.releases.*', 'icon' => 'M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3'],
                                ['route' => 'admin.submissions.index', 'label' => 'Submissions', 'match' => 'admin.submissions.*', 'icon' => 'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4'],
                                ['route' => 'admin.artworks.index', 'label' => 'Logo & Artwork', 'match' => 'admin.artworks.*', 'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
                                ['route' => 'admin.photos.index', 'label' => 'Fotos / Bilder', 'match' => 'admin.photos.*', 'icon' => 'M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z M15 13a3 3 0 11-6 0 3 3 0 016 0z'],
                            ];
                        @endphp
                        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                            <button @click="open = !open"
                                    class="flex items-center gap-1.5 px-2.5 py-1.5 text-sm font-medium rounded-md transition-colors {{ $projekteActive ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                <span>Projekte</span>
                                <svg class="w-3 h-3 ml-0.5 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                            <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute left-0 mt-1 w-48 bg-gray-800 rounded-lg shadow-xl ring-1 ring-gray-700 py-1 z-50">
                                @foreach($projekteItems as $item)
                                    @php $matches = collect(explode('||', $item['match']))->contains(fn($p) => request()->routeIs($p)); @endphp
                                    <a href="{{ route($item['route']) }}"
                                       class="flex items-center gap-2 px-3 py-2 text-sm {{ $matches ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"></path></svg>
                                        {{ $item['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>

                        {{-- Finanzen Dropdown --}}
                        @php
                            $finanzenActive = request()->routeIs('admin.invoices.*') || request()->routeIs('admin.expenses.*') || request()->routeIs('admin.accountings.*') || request()->routeIs('admin.bookings.*');
                            $finanzenItems = [
                                ['route' => 'admin.invoices.index', 'label' => 'Rechnungen', 'match' => 'admin.invoices.*', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                                ['route' => 'admin.expenses.index', 'label' => 'Ausgaben', 'match' => 'admin.expenses.*', 'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
                                ['route' => 'admin.accountings.index', 'label' => 'Buchhaltung', 'match' => 'admin.accountings.*||admin.bookings.*', 'icon' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z'],
                            ];
                        @endphp
                        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                            <button @click="open = !open"
                                    class="flex items-center gap-1.5 px-2.5 py-1.5 text-sm font-medium rounded-md transition-colors {{ $finanzenActive ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span>Finanzen</span>
                                <svg class="w-3 h-3 ml-0.5 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                            <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute left-0 mt-1 w-48 bg-gray-800 rounded-lg shadow-xl ring-1 ring-gray-700 py-1 z-50">
                                @foreach($finanzenItems as $item)
                                    @php $matches = collect(explode('||', $item['match']))->contains(fn($p) => request()->routeIs($p)); @endphp
                                    <a href="{{ route($item['route']) }}"
                                       class="flex items-center gap-2 px-3 py-2 text-sm {{ $matches ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"></path></svg>
                                        {{ $item['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>

                        {{-- Kampagnen Dropdown --}}
                        @php
                            $kampagnenActive = request()->routeIs('admin.address-circles.*');
                            $kampagnenItems = [
                                ['route' => 'admin.address-circles.index', 'label' => 'Adresskreise', 'match' => 'admin.address-circles.*', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
                            ];
                        @endphp
                        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                            <button @click="open = !open"
                                    class="flex items-center gap-1.5 px-2.5 py-1.5 text-sm font-medium rounded-md transition-colors {{ $kampagnenActive ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path></svg>
                                <span>Kampagnen</span>
                                <svg class="w-3 h-3 ml-0.5 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                            <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute left-0 mt-1 w-48 bg-gray-800 rounded-lg shadow-xl ring-1 ring-gray-700 py-1 z-50">
                                @foreach($kampagnenItems as $item)
                                    @php $matches = collect(explode('||', $item['match']))->contains(fn($p) => request()->routeIs($p)); @endphp
                                    <a href="{{ route($item['route']) }}"
                                       class="flex items-center gap-2 px-3 py-2 text-sm {{ $matches ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"></path></svg>
                                        {{ $item['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>

                        {{-- Settings Dropdown --}}
                        @php
                            $settingsActive = request()->routeIs('admin.settings.*') || request()->routeIs('admin.help') || request()->routeIs('admin.changelog') || request()->routeIs('admin.activity-logs.*') || request()->routeIs('admin.genres.*') || request()->routeIs('admin.contact-types.*') || request()->routeIs('admin.organization-types.*') || request()->routeIs('admin.project-types.*') || request()->routeIs('admin.contract-types.*') || request()->routeIs('admin.chart-templates.*') || request()->routeIs('admin.invoice-templates.*') || request()->routeIs('admin.contract-templates.*');
                            $settingsItems = [
                                ['route' => 'admin.genres.index', 'label' => 'Genres', 'match' => 'admin.genres.*', 'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'],
                                ['route' => 'admin.contact-types.index', 'label' => 'Kontakt-Typen', 'match' => 'admin.contact-types.*', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                                ['route' => 'admin.organization-types.index', 'label' => 'Org. Typen', 'match' => 'admin.organization-types.*', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                                ['route' => 'admin.project-types.index', 'label' => 'Projekt-Typen', 'match' => 'admin.project-types.*', 'icon' => 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z'],
                                ['route' => 'admin.contract-types.index', 'label' => 'Vertragstypen', 'match' => 'admin.contract-types.*', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                                'divider',
                                ['route' => 'admin.chart-templates.index', 'label' => 'Kontopläne', 'match' => 'admin.chart-templates.*', 'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                                ['route' => 'admin.invoice-templates.index', 'label' => 'Rechnungsvorlagen', 'match' => 'admin.invoice-templates.*', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                                ['route' => 'admin.contract-templates.index', 'label' => 'Vertragsvorlagen', 'match' => 'admin.contract-templates.*', 'icon' => 'M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2'],
                                'divider',
                                ['route' => 'admin.settings.profile', 'label' => 'Profil', 'match' => 'admin.settings.profile', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                                ['route' => 'admin.settings.appearance', 'label' => 'Darstellung', 'match' => 'admin.settings.appearance', 'icon' => 'M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z'],
                                ['route' => 'admin.settings.system', 'label' => 'System', 'match' => 'admin.settings.system||admin.help||admin.changelog||admin.activity-logs.*', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
                            ];
                        @endphp
                        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                            <button @click="open = !open"
                                    class="flex items-center gap-1.5 px-2.5 py-1.5 text-sm font-medium rounded-md transition-colors {{ $settingsActive ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                <span>Settings</span>
                                <svg class="w-3 h-3 ml-0.5 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                            <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute left-0 mt-1 w-52 bg-gray-800 rounded-lg shadow-xl ring-1 ring-gray-700 py-1 z-50 max-h-[70vh] overflow-y-auto">
                                @foreach($settingsItems as $item)
                                    @if($item === 'divider')
                                        <div class="border-t border-gray-700 my-1"></div>
                                    @else
                                        @php $matches = collect(explode('||', $item['match']))->contains(fn($p) => request()->routeIs($p)); @endphp
                                        <a href="{{ route($item['route']) }}"
                                           class="flex items-center gap-2 px-3 py-2 text-sm {{ $matches ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"></path></svg>
                                            {{ $item['label'] }}
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Right side: User --}}
                    <div class="hidden md:flex items-center gap-3 ml-auto flex-shrink-0">
                        <span class="text-xs text-gray-400">{{ Auth::user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-gray-400 hover:text-white transition-colors" title="Abmelden">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                            </button>
                        </form>
                    </div>

                    {{-- Mobile hamburger --}}
                    <button @click="mobileOpen = !mobileOpen" class="md:hidden ml-auto text-gray-400 hover:text-white">
                        <svg x-show="!mobileOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                        <svg x-show="mobileOpen" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
            </div>

            {{-- Mobile menu --}}
            <div x-show="mobileOpen" x-cloak x-transition class="md:hidden bg-gray-800 border-t border-gray-700">
                <div class="px-3 py-3 space-y-1 max-h-[80vh] overflow-y-auto">
                    @php
                        $mobileAll = [
                            ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'match' => 'admin.dashboard'],
                            ['route' => 'admin.contacts.index', 'label' => 'Kontakte', 'match' => 'admin.contacts.*'],
                            ['route' => 'admin.organizations.index', 'label' => 'Organisationen', 'match' => 'admin.organizations.*'],
                            ['route' => 'admin.documents.index', 'label' => 'Dokumente', 'match' => 'admin.documents.*'],
                            'divider',
                            ['route' => 'admin.projects.index', 'label' => 'Projekte', 'match' => 'admin.projects.*'],
                            ['route' => 'admin.tasks.index', 'label' => 'Aufgaben', 'match' => 'admin.tasks.*'],
                            ['route' => 'admin.contracts.index', 'label' => 'Verträge', 'match' => 'admin.contracts.*'],
                            ['route' => 'admin.tracks.index', 'label' => 'Musik', 'match' => 'admin.tracks.*||admin.releases.*'],
                            ['route' => 'admin.submissions.index', 'label' => 'Submissions', 'match' => 'admin.submissions.*'],
                            ['route' => 'admin.artworks.index', 'label' => 'Logo & Artwork', 'match' => 'admin.artworks.*'],
                            ['route' => 'admin.photos.index', 'label' => 'Fotos / Bilder', 'match' => 'admin.photos.*'],
                            'divider',
                            ['route' => 'admin.invoices.index', 'label' => 'Rechnungen', 'match' => 'admin.invoices.*'],
                            ['route' => 'admin.expenses.index', 'label' => 'Ausgaben', 'match' => 'admin.expenses.*'],
                            ['route' => 'admin.accountings.index', 'label' => 'Buchhaltung', 'match' => 'admin.accountings.*||admin.bookings.*'],
                            'divider',
                            ['route' => 'admin.address-circles.index', 'label' => 'Adresskreise', 'match' => 'admin.address-circles.*'],
                            'divider',
                            ['route' => 'admin.settings.profile', 'label' => 'Profil', 'match' => 'admin.settings.profile'],
                            ['route' => 'admin.settings.appearance', 'label' => 'Darstellung', 'match' => 'admin.settings.appearance'],
                            ['route' => 'admin.settings.system', 'label' => 'System', 'match' => 'admin.settings.system||admin.help||admin.changelog||admin.activity-logs.*'],
                        ];
                    @endphp
                    @foreach($mobileAll as $item)
                        @if($item === 'divider')
                            <div class="border-t border-gray-700 my-1"></div>
                        @else
                            @php $matches = collect(explode('||', $item['match']))->contains(fn($p) => request()->routeIs($p)); @endphp
                            <a href="{{ route($item['route']) }}"
                               class="block px-3 py-2 rounded-md text-sm font-medium {{ $matches ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
                                {{ $item['label'] }}
                            </a>
                        @endif
                    @endforeach

                    <div class="border-t border-gray-700 pt-2 mt-2">
                        <div class="px-3 py-1 text-xs text-gray-400">{{ Auth::user()->name }} &mdash; {{ Auth::user()->email }}</div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-3 py-2 rounded-md text-sm text-red-400 hover:bg-gray-700 hover:text-red-300">Abmelden</button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>

        {{-- Page Header --}}
        <header class="bg-white dark:bg-gray-900 shadow-sm border-b border-gray-200 dark:border-gray-700">
            <div class="px-4 sm:px-6 py-3">
                <h1 class="text-lg font-semibold text-gray-800 dark:text-gray-100">@yield('title', 'Dashboard')</h1>
            </div>
        </header>

        {{-- Flash messages --}}
        @if(session('success'))
            <div class="mx-4 sm:mx-6 mt-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 px-4 py-3 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mx-4 sm:mx-6 mt-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        {{-- Page content --}}
        <main class="flex-1 p-4 sm:p-6">
            @yield('content')
        </main>
    </div>

    @include('admin.partials.document-viewer-modal')
    @stack('scripts')
</body>
</html>
