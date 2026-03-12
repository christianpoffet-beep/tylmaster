@extends('admin.layouts.app')

@section('title', 'Einstellungen - Darstellung')

@section('content')
@include('admin.settings._tabs', ['active' => 'appearance'])

<div class="max-w-2xl">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Design</h2>

        <div x-data="{ theme: localStorage.getItem('theme') || 'system' }" class="space-y-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">Wähle das Farbschema für das Admin Panel.</p>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                {{-- Light --}}
                <button @click="theme = 'light'; localStorage.setItem('theme', 'light'); document.documentElement.classList.remove('dark');"
                    :class="theme === 'light' ? 'ring-2 ring-blue-500 border-blue-500' : 'border-gray-200 dark:border-gray-600'"
                    class="flex flex-col items-center gap-3 p-4 rounded-xl border bg-white dark:bg-gray-700 hover:shadow transition-all">
                    <div class="w-full h-20 rounded-lg bg-gray-100 border border-gray-200 flex items-end p-2">
                        <div class="w-full h-3 bg-white rounded shadow-sm"></div>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">Hell</span>
                    </div>
                </button>

                {{-- Dark --}}
                <button @click="theme = 'dark'; localStorage.setItem('theme', 'dark'); document.documentElement.classList.add('dark');"
                    :class="theme === 'dark' ? 'ring-2 ring-blue-500 border-blue-500' : 'border-gray-200 dark:border-gray-600'"
                    class="flex flex-col items-center gap-3 p-4 rounded-xl border bg-white dark:bg-gray-700 hover:shadow transition-all">
                    <div class="w-full h-20 rounded-lg bg-gray-800 border border-gray-700 flex items-end p-2">
                        <div class="w-full h-3 bg-gray-700 rounded shadow-sm"></div>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">Dunkel</span>
                    </div>
                </button>

                {{-- System --}}
                <button @click="theme = 'system'; localStorage.setItem('theme', 'system'); if (window.matchMedia('(prefers-color-scheme: dark)').matches) { document.documentElement.classList.add('dark'); } else { document.documentElement.classList.remove('dark'); }"
                    :class="theme === 'system' ? 'ring-2 ring-blue-500 border-blue-500' : 'border-gray-200 dark:border-gray-600'"
                    class="flex flex-col items-center gap-3 p-4 rounded-xl border bg-white dark:bg-gray-700 hover:shadow transition-all">
                    <div class="w-full h-20 rounded-lg overflow-hidden flex border border-gray-200 dark:border-gray-700">
                        <div class="w-1/2 bg-gray-100 flex items-end p-2"><div class="w-full h-3 bg-white rounded shadow-sm"></div></div>
                        <div class="w-1/2 bg-gray-800 flex items-end p-2"><div class="w-full h-3 bg-gray-700 rounded shadow-sm"></div></div>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">System</span>
                    </div>
                </button>
            </div>

            <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">Die Einstellung wird lokal im Browser gespeichert.</p>
        </div>
    </div>
</div>
@endsection
