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

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-gray-100 font-sans antialiased">
    <div x-data="{
        sidebarOpen: false,
        collapsed: localStorage.getItem('sidebar-collapsed') === 'true',
        toggleCollapse() { this.collapsed = !this.collapsed; localStorage.setItem('sidebar-collapsed', this.collapsed); }
    }" class="flex h-screen overflow-hidden">

        <!-- Mobile overlay -->
        <div
            x-show="sidebarOpen"
            x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-30 bg-gray-600 bg-opacity-75 lg:hidden"
            @click="sidebarOpen = false"
        ></div>

        <!-- Sidebar -->
        <aside
            :class="[
                sidebarOpen ? 'translate-x-0' : '-translate-x-full',
                collapsed && !sidebarOpen ? 'lg:w-11' : 'lg:w-64'
            ]"
            class="fixed inset-y-0 left-0 z-40 w-64 bg-gray-900 text-white transform transition-all duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0 flex flex-col"
            :style="collapsed && !sidebarOpen ? 'overflow: visible' : ''"
        >
            <!-- Logo -->
            <div class="flex items-center justify-center h-12 border-b border-gray-800" :class="collapsed && !sidebarOpen ? 'h-10' : 'h-12'">
                <span x-show="!collapsed || sidebarOpen" x-cloak class="text-xl font-bold tracking-wider">TYL ADMIN</span>
                <span x-show="collapsed && !sidebarOpen" x-cloak class="text-sm font-bold hidden lg:block">T</span>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 py-2 space-y-0.5" :class="collapsed && !sidebarOpen ? 'px-1 overflow-visible' : 'px-3 overflow-y-auto'">
                @php
                    $navItems = [
                        ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'match' => 'admin.dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z'],
                        ['route' => 'admin.contacts.index', 'label' => 'Kontakte', 'match' => 'admin.contacts.*', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                        ['route' => 'admin.organizations.index', 'label' => 'Organisationen', 'match' => 'admin.organizations.*', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                        ['route' => 'admin.documents.index', 'label' => 'Dokumente', 'match' => 'admin.documents.*', 'icon' => 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z'],
                    ];
                @endphp

                @foreach($navItems as $item)
                    @php
                        $matches = collect(explode('||', $item['match']))->contains(fn($p) => request()->routeIs($p));
                    @endphp
                    <a href="{{ route($item['route']) }}"
                       class="group relative flex items-center text-sm font-medium {{ $matches ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}"
                       :class="collapsed && !sidebarOpen ? 'justify-center p-1.5 rounded' : 'px-3 py-2.5 rounded-lg'">
                        <svg class="w-5 h-5 flex-shrink-0" :class="collapsed && !sidebarOpen ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"></path>
                        </svg>
                        <span x-show="!collapsed || sidebarOpen" x-cloak>{{ $item['label'] }}</span>
                        {{-- Tooltip: hidden via class toggle, not x-show, so CSS hover works --}}
                        <span :class="collapsed && !sidebarOpen ? 'absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-gray-900 text-white text-xs font-medium whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity duration-150 z-50 shadow-lg ring-1 ring-gray-700' : 'hidden'">
                            {{ $item['label'] }}
                        </span>
                    </a>
                @endforeach

                {{-- Projekte (aufklappbar) --}}
                @php
                    $projekteActive = request()->routeIs('admin.projects.*') || request()->routeIs('admin.tasks.*') || request()->routeIs('admin.contracts.*') || request()->routeIs('admin.tracks.*') || request()->routeIs('admin.releases.*') || request()->routeIs('admin.submissions.*') || request()->routeIs('admin.artworks.*') || request()->routeIs('admin.photos.*');

                    $projekteItems = [
                        ['route' => 'admin.tasks.index', 'label' => 'Aufgaben', 'match' => 'admin.tasks.*', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
                        ['route' => 'admin.contracts.index', 'label' => 'Verträge', 'match' => 'admin.contracts.*', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                        ['route' => 'admin.tracks.index', 'label' => 'Musik', 'match' => 'admin.tracks.*||admin.releases.*', 'icon' => 'M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3'],
                        ['route' => 'admin.submissions.index', 'label' => 'Submissions', 'match' => 'admin.submissions.*', 'icon' => 'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4'],
                        ['route' => 'admin.artworks.index', 'label' => 'Logo & Artwork', 'match' => 'admin.artworks.*', 'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'],
                        ['route' => 'admin.photos.index', 'label' => 'Fotos / Bilder', 'match' => 'admin.photos.*', 'icon' => 'M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z M15 13a3 3 0 11-6 0 3 3 0 016 0z'],
                    ];
                @endphp
                <div x-data="{ open: {{ $projekteActive ? 'true' : 'false' }} }">
                    <div class="group relative flex items-center {{ $projekteActive ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}"
                         :class="collapsed && !sidebarOpen ? 'justify-center p-1.5 rounded' : 'rounded-lg'">
                        <a href="{{ route('admin.projects.index') }}"
                           class="flex-1 flex items-center text-sm font-medium"
                           :class="collapsed && !sidebarOpen ? 'justify-center' : 'px-3 py-2.5'">
                            <svg class="w-5 h-5 flex-shrink-0" :class="collapsed && !sidebarOpen ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            <span x-show="!collapsed || sidebarOpen" x-cloak>Projekte</span>
                        </a>
                        <button @click.prevent="open = !open" x-show="!collapsed || sidebarOpen" x-cloak
                            class="pr-3 py-2.5 text-gray-400 hover:text-white">
                            <svg class="w-3.5 h-3.5 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <span :class="collapsed && !sidebarOpen ? 'absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-gray-900 text-white text-xs font-medium whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity duration-150 z-50 shadow-lg ring-1 ring-gray-700' : 'hidden'">
                            Projekte
                        </span>
                    </div>
                    <div x-show="open || (collapsed && !sidebarOpen)" x-cloak class="space-y-0.5" :class="collapsed && !sidebarOpen ? '' : 'pl-2'">
                        @foreach($projekteItems as $item)
                            @php
                                $matches = collect(explode('||', $item['match']))->contains(fn($p) => request()->routeIs($p));
                            @endphp
                            <a href="{{ route($item['route']) }}"
                               class="group relative flex items-center text-sm font-medium {{ $matches ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}"
                               :class="collapsed && !sidebarOpen ? 'justify-center p-1.5 rounded' : 'px-3 py-2 rounded-lg'">
                                <svg class="w-5 h-5 flex-shrink-0" :class="collapsed && !sidebarOpen ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"></path>
                                </svg>
                                <span x-show="!collapsed || sidebarOpen" x-cloak>{{ $item['label'] }}</span>
                                <span :class="collapsed && !sidebarOpen ? 'absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-gray-900 text-white text-xs font-medium whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity duration-150 z-50 shadow-lg ring-1 ring-gray-700' : 'hidden'">
                                    {{ $item['label'] }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Finanzen (aufklappbar) --}}
                @php
                    $finanzenActive = request()->routeIs('admin.invoices.*') || request()->routeIs('admin.expenses.*') || request()->routeIs('admin.accountings.*') || request()->routeIs('admin.bookings.*');

                    $finanzenItems = [
                        ['route' => 'admin.accountings.index', 'label' => 'Buchhaltung', 'match' => 'admin.accountings.*||admin.bookings.*', 'icon' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z'],
                    ];
                @endphp
                <div x-data="{ open: {{ $finanzenActive ? 'true' : 'false' }} }">
                    <div class="group relative flex items-center {{ $finanzenActive ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}"
                         :class="collapsed && !sidebarOpen ? 'justify-center p-1.5 rounded' : 'rounded-lg'">
                        <a href="{{ route('admin.invoices.index') }}"
                           class="flex-1 flex items-center text-sm font-medium"
                           :class="collapsed && !sidebarOpen ? 'justify-center' : 'px-3 py-2.5'">
                            <svg class="w-5 h-5 flex-shrink-0" :class="collapsed && !sidebarOpen ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span x-show="!collapsed || sidebarOpen" x-cloak>Finanzen</span>
                        </a>
                        <button @click.prevent="open = !open" x-show="!collapsed || sidebarOpen" x-cloak
                            class="pr-3 py-2.5 text-gray-400 hover:text-white">
                            <svg class="w-3.5 h-3.5 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <span :class="collapsed && !sidebarOpen ? 'absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-gray-900 text-white text-xs font-medium whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity duration-150 z-50 shadow-lg ring-1 ring-gray-700' : 'hidden'">
                            Finanzen
                        </span>
                    </div>
                    <div x-show="open || (collapsed && !sidebarOpen)" x-cloak class="space-y-0.5" :class="collapsed && !sidebarOpen ? '' : 'pl-2'">
                        @foreach($finanzenItems as $item)
                            @php
                                $matches = collect(explode('||', $item['match']))->contains(fn($p) => request()->routeIs($p));
                            @endphp
                            <a href="{{ route($item['route']) }}"
                               class="group relative flex items-center text-sm font-medium {{ $matches ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}"
                               :class="collapsed && !sidebarOpen ? 'justify-center p-1.5 rounded' : 'px-3 py-2 rounded-lg'">
                                <svg class="w-5 h-5 flex-shrink-0" :class="collapsed && !sidebarOpen ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"></path>
                                </svg>
                                <span x-show="!collapsed || sidebarOpen" x-cloak>{{ $item['label'] }}</span>
                                <span :class="collapsed && !sidebarOpen ? 'absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-gray-900 text-white text-xs font-medium whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity duration-150 z-50 shadow-lg ring-1 ring-gray-700' : 'hidden'">
                                    {{ $item['label'] }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>

                <!-- Settings -->
                <div class="pt-3 mt-3 border-t border-gray-700">
                    <p x-show="!collapsed || sidebarOpen" x-cloak class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-wider text-gray-500">Settings</p>

                    @php
                        $labelsActive = request()->routeIs('admin.genres.*') || request()->routeIs('admin.contact-types.*') || request()->routeIs('admin.organization-types.*') || request()->routeIs('admin.project-types.*') || request()->routeIs('admin.contract-types.*');
                        $vorlagenActive = request()->routeIs('admin.chart-templates.*') || request()->routeIs('admin.invoice-templates.*') || request()->routeIs('admin.contract-templates.*');
                    @endphp

                    {{-- Labels --}}
                    <div x-data="{ open: {{ $labelsActive ? 'true' : 'false' }} }">
                        <button @click="open = !open" x-show="!collapsed || sidebarOpen" x-cloak
                            class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold uppercase tracking-wider {{ $labelsActive ? 'text-white' : 'text-gray-400 hover:text-gray-200' }}">
                            <span>Labels</span>
                            <svg class="w-3.5 h-3.5 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open || (collapsed && !sidebarOpen)" x-cloak class="space-y-0.5">
                            <a href="{{ route('admin.genres.index') }}"
                               class="group relative flex items-center text-sm font-medium {{ request()->routeIs('admin.genres.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}"
                               :class="collapsed && !sidebarOpen ? 'justify-center p-1.5 rounded' : 'px-3 py-2 rounded-lg'">
                                <svg class="w-5 h-5 flex-shrink-0" :class="collapsed && !sidebarOpen ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                </svg>
                                <span x-show="!collapsed || sidebarOpen" x-cloak>Genres</span>
                                <span :class="collapsed && !sidebarOpen ? 'absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-gray-900 text-white text-xs font-medium whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity duration-150 z-50 shadow-lg ring-1 ring-gray-700' : 'hidden'">Genres</span>
                            </a>

                            <a href="{{ route('admin.contact-types.index') }}"
                               class="group relative flex items-center text-sm font-medium {{ request()->routeIs('admin.contact-types.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}"
                               :class="collapsed && !sidebarOpen ? 'justify-center p-1.5 rounded' : 'px-3 py-2 rounded-lg'">
                                <svg class="w-5 h-5 flex-shrink-0" :class="collapsed && !sidebarOpen ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <span x-show="!collapsed || sidebarOpen" x-cloak>Kont. Typ</span>
                                <span :class="collapsed && !sidebarOpen ? 'absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-gray-900 text-white text-xs font-medium whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity duration-150 z-50 shadow-lg ring-1 ring-gray-700' : 'hidden'">Kont. Typ</span>
                            </a>

                            <a href="{{ route('admin.organization-types.index') }}"
                               class="group relative flex items-center text-sm font-medium {{ request()->routeIs('admin.organization-types.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}"
                               :class="collapsed && !sidebarOpen ? 'justify-center p-1.5 rounded' : 'px-3 py-2 rounded-lg'">
                                <svg class="w-5 h-5 flex-shrink-0" :class="collapsed && !sidebarOpen ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                <span x-show="!collapsed || sidebarOpen" x-cloak>Org. Typen</span>
                                <span :class="collapsed && !sidebarOpen ? 'absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-gray-900 text-white text-xs font-medium whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity duration-150 z-50 shadow-lg ring-1 ring-gray-700' : 'hidden'">Org. Typen</span>
                            </a>

                            <a href="{{ route('admin.project-types.index') }}"
                               class="group relative flex items-center text-sm font-medium {{ request()->routeIs('admin.project-types.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}"
                               :class="collapsed && !sidebarOpen ? 'justify-center p-1.5 rounded' : 'px-3 py-2 rounded-lg'">
                                <svg class="w-5 h-5 flex-shrink-0" :class="collapsed && !sidebarOpen ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                                </svg>
                                <span x-show="!collapsed || sidebarOpen" x-cloak>Projekt Typ</span>
                                <span :class="collapsed && !sidebarOpen ? 'absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-gray-900 text-white text-xs font-medium whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity duration-150 z-50 shadow-lg ring-1 ring-gray-700' : 'hidden'">Projekt Typ</span>
                            </a>

                            <a href="{{ route('admin.contract-types.index') }}"
                               class="group relative flex items-center text-sm font-medium {{ request()->routeIs('admin.contract-types.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}"
                               :class="collapsed && !sidebarOpen ? 'justify-center p-1.5 rounded' : 'px-3 py-2 rounded-lg'">
                                <svg class="w-5 h-5 flex-shrink-0" :class="collapsed && !sidebarOpen ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <span x-show="!collapsed || sidebarOpen" x-cloak>Vertragstypen</span>
                                <span :class="collapsed && !sidebarOpen ? 'absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-gray-900 text-white text-xs font-medium whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity duration-150 z-50 shadow-lg ring-1 ring-gray-700' : 'hidden'">Vertragstypen</span>
                            </a>
                        </div>
                    </div>

                    {{-- Vorlagen --}}
                    <div x-data="{ open: {{ $vorlagenActive ? 'true' : 'false' }} }" class="mt-1">
                        <button @click="open = !open" x-show="!collapsed || sidebarOpen" x-cloak
                            class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold uppercase tracking-wider {{ $vorlagenActive ? 'text-white' : 'text-gray-400 hover:text-gray-200' }}">
                            <span>Vorlagen</span>
                            <svg class="w-3.5 h-3.5 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open || (collapsed && !sidebarOpen)" x-cloak class="space-y-0.5">
                            <a href="{{ route('admin.chart-templates.index') }}"
                               class="group relative flex items-center text-sm font-medium {{ request()->routeIs('admin.chart-templates.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}"
                               :class="collapsed && !sidebarOpen ? 'justify-center p-1.5 rounded' : 'px-3 py-2 rounded-lg'">
                                <svg class="w-5 h-5 flex-shrink-0" :class="collapsed && !sidebarOpen ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <span x-show="!collapsed || sidebarOpen" x-cloak>Kontopläne</span>
                                <span :class="collapsed && !sidebarOpen ? 'absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-gray-900 text-white text-xs font-medium whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity duration-150 z-50 shadow-lg ring-1 ring-gray-700' : 'hidden'">Kontopläne</span>
                            </a>

                            <a href="{{ route('admin.invoice-templates.index') }}"
                               class="group relative flex items-center text-sm font-medium {{ request()->routeIs('admin.invoice-templates.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}"
                               :class="collapsed && !sidebarOpen ? 'justify-center p-1.5 rounded' : 'px-3 py-2 rounded-lg'">
                                <svg class="w-5 h-5 flex-shrink-0" :class="collapsed && !sidebarOpen ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <span x-show="!collapsed || sidebarOpen" x-cloak>Rechnungsvorlagen</span>
                                <span :class="collapsed && !sidebarOpen ? 'absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-gray-900 text-white text-xs font-medium whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity duration-150 z-50 shadow-lg ring-1 ring-gray-700' : 'hidden'">Rechnungsvorlagen</span>
                            </a>

                            <a href="{{ route('admin.contract-templates.index') }}"
                               class="group relative flex items-center text-sm font-medium {{ request()->routeIs('admin.contract-templates.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}"
                               :class="collapsed && !sidebarOpen ? 'justify-center p-1.5 rounded' : 'px-3 py-2 rounded-lg'">
                                <svg class="w-5 h-5 flex-shrink-0" :class="collapsed && !sidebarOpen ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path>
                                </svg>
                                <span x-show="!collapsed || sidebarOpen" x-cloak>Vertragsvorlagen</span>
                                <span :class="collapsed && !sidebarOpen ? 'absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-gray-900 text-white text-xs font-medium whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity duration-150 z-50 shadow-lg ring-1 ring-gray-700' : 'hidden'">Vertragsvorlagen</span>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Benutzerverwaltung --}}
                @php
                    $userAdminActive = request()->routeIs('admin.help') || request()->routeIs('admin.changelog') || request()->routeIs('admin.activity-logs.*');

                    $userAdminItems = [
                        ['route' => 'admin.help', 'label' => 'Benutzeranleitung', 'match' => 'admin.help', 'icon' => 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ['route' => 'admin.changelog', 'label' => 'Change Log', 'match' => 'admin.changelog', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
                        ['route' => 'admin.activity-logs.index', 'label' => 'Logfile', 'match' => 'admin.activity-logs.*', 'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                    ];
                @endphp
                <div class="pt-3 mt-3 border-t border-gray-700">
                    <p x-show="!collapsed || sidebarOpen" x-cloak class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-wider text-gray-500">Benutzerverwaltung</p>

                    <div class="space-y-0.5">
                        @foreach($userAdminItems as $item)
                            @php
                                $matches = collect(explode('||', $item['match']))->contains(fn($p) => request()->routeIs($p));
                            @endphp
                            <a href="{{ route($item['route']) }}"
                               class="group relative flex items-center text-sm font-medium {{ $matches ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}"
                               :class="collapsed && !sidebarOpen ? 'justify-center p-1.5 rounded' : 'px-3 py-2 rounded-lg'">
                                <svg class="w-5 h-5 flex-shrink-0" :class="collapsed && !sidebarOpen ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"></path>
                                </svg>
                                <span x-show="!collapsed || sidebarOpen" x-cloak>{{ $item['label'] }}</span>
                                <span :class="collapsed && !sidebarOpen ? 'absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-gray-900 text-white text-xs font-medium whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity duration-150 z-50 shadow-lg ring-1 ring-gray-700' : 'hidden'">
                                    {{ $item['label'] }}
                                </span>
                            </a>
                        @endforeach

                        {{-- Passwort ändern (external link) --}}
                        <a href="{{ url('/profile') }}"
                           class="group relative flex items-center text-sm font-medium text-gray-300 hover:bg-gray-800 hover:text-white"
                           :class="collapsed && !sidebarOpen ? 'justify-center p-1.5 rounded' : 'px-3 py-2 rounded-lg'">
                            <svg class="w-5 h-5 flex-shrink-0" :class="collapsed && !sidebarOpen ? '' : 'mr-3'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                            </svg>
                            <span x-show="!collapsed || sidebarOpen" x-cloak>Passwort ändern</span>
                            <span :class="collapsed && !sidebarOpen ? 'absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-gray-900 text-white text-xs font-medium whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity duration-150 z-50 shadow-lg ring-1 ring-gray-700' : 'hidden'">
                                Passwort ändern
                            </span>
                        </a>
                    </div>
                </div>
            </nav>

            <!-- Collapse toggle (desktop only) -->
            <div class="hidden lg:block border-t border-gray-800" :class="collapsed && !sidebarOpen ? 'p-1' : 'p-2'">
                <button @click="toggleCollapse()" class="w-full flex items-center justify-center rounded-lg text-gray-400 hover:text-white hover:bg-gray-800" :class="collapsed && !sidebarOpen ? 'p-1.5' : 'p-2'">
                    <svg x-show="!collapsed" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
                    <svg x-show="collapsed" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                </button>
            </div>

            <!-- User section at bottom -->
            <div class="border-t border-gray-800" :class="collapsed && !sidebarOpen ? 'p-1' : 'p-4'">
                <div class="flex items-center" :class="collapsed && !sidebarOpen ? 'justify-center' : ''">
                    <div x-show="!collapsed || sidebarOpen" x-cloak class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate">{{ Auth::user()->name }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ Auth::user()->email }}</p>
                    </div>
                    <div class="flex items-center" :class="collapsed && !sidebarOpen ? 'gap-0' : 'gap-1'">
                        <form method="POST" action="{{ route('logout') }}" class="group relative">
                            @csrf
                            <button type="submit" class="text-gray-400 hover:text-white" :class="collapsed && !sidebarOpen ? 'p-1.5' : ''">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                            </button>
                            <span :class="collapsed && !sidebarOpen ? 'absolute left-full ml-3 px-2.5 py-1.5 rounded-md bg-gray-900 text-white text-xs font-medium whitespace-nowrap opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity duration-150 z-50 shadow-lg ring-1 ring-gray-700 bottom-0' : 'hidden'">
                                Abmelden
                            </span>
                        </form>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top bar -->
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="flex items-center justify-between px-4 py-3 sm:px-6">
                    <button @click="sidebarOpen = true" class="lg:hidden text-gray-500 hover:text-gray-700 focus:outline-none">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <h1 class="text-lg font-semibold text-gray-800">@yield('title', 'Dashboard')</h1>
                    <div></div>
                </div>
            </header>

            <!-- Flash messages -->
            @if(session('success'))
                <div class="mx-4 sm:mx-6 mt-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mx-4 sm:mx-6 mt-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Page content -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6">
                @yield('content')
            </main>
        </div>
    </div>

    @include('admin.partials.document-viewer-modal')
    @stack('scripts')
</body>
</html>
