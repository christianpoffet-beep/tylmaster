@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
<!-- Stats (compact, clickable) -->
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-8">
    <a href="{{ route('admin.contacts.index') }}" class="flex items-center justify-between bg-white dark:bg-gray-800 rounded-lg shadow-sm px-4 py-2.5 border border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600 hover:shadow transition-all">
        <span class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            Kontakte
        </span>
        <span class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ $stats['contacts'] }}</span>
    </a>
    <a href="{{ route('admin.contracts.index') }}" class="flex items-center justify-between bg-white dark:bg-gray-800 rounded-lg shadow-sm px-4 py-2.5 border border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600 hover:shadow transition-all">
        <span class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Verträge
        </span>
        <span class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ $stats['contracts'] }}</span>
    </a>
    <a href="{{ route('admin.projects.index') }}" class="flex items-center justify-between bg-white dark:bg-gray-800 rounded-lg shadow-sm px-4 py-2.5 border border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600 hover:shadow transition-all">
        <span class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            Projekte
        </span>
        <span class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ $stats['projects'] }}</span>
    </a>
    <a href="{{ route('admin.tasks.index') }}" class="flex items-center justify-between bg-white dark:bg-gray-800 rounded-lg shadow-sm px-4 py-2.5 border border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600 hover:shadow transition-all">
        <span class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            Aufgaben
        </span>
        <span class="text-lg font-bold {{ $stats['open_tasks'] > 0 ? 'text-orange-600 dark:text-orange-400' : 'text-gray-900 dark:text-gray-100' }}">{{ $stats['open_tasks'] }}</span>
    </a>
    <a href="{{ route('admin.invoices.index') }}" class="flex items-center justify-between bg-white dark:bg-gray-800 rounded-lg shadow-sm px-4 py-2.5 border border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600 hover:shadow transition-all">
        <span class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Rechnungen
        </span>
        <span class="text-lg font-bold {{ $stats['open_invoices'] > 0 ? 'text-orange-600 dark:text-orange-400' : 'text-gray-900 dark:text-gray-100' }}">{{ $stats['open_invoices'] }}</span>
    </a>
    <a href="{{ route('admin.submissions.index') }}" class="flex items-center justify-between bg-white dark:bg-gray-800 rounded-lg shadow-sm px-4 py-2.5 border border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600 hover:shadow transition-all">
        <span class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
            Submissions
        </span>
        <span class="text-lg font-bold {{ $stats['submissions'] > 0 ? 'text-blue-600 dark:text-blue-400' : 'text-gray-900 dark:text-gray-100' }}">{{ $stats['submissions'] }}</span>
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Upcoming Tasks -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h2 class="font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                Anstehende Aufgaben
            </h2>
            <a href="{{ route('admin.tasks.create') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">+ Neue Aufgabe</a>
        </div>
        <div class="p-5">
            @forelse($upcomingTasks as $task)
                <div class="flex justify-between items-center py-2 {{ !$loop->last ? 'border-b border-gray-100 dark:border-gray-700' : '' }}">
                    <div class="flex items-center gap-2">
                        <form method="POST" action="{{ route('admin.tasks.toggle', $task) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="w-4 h-4 rounded border flex items-center justify-center border-gray-300 dark:border-gray-600 hover:border-blue-400">
                            </button>
                        </form>
                        <a href="{{ route('admin.tasks.show', $task) }}" class="text-sm font-medium text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400">{{ $task->title }}</a>
                        @if($task->priority === 'high')
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300">!</span>
                        @endif
                        @if($task->project)
                            <a href="{{ route('admin.projects.show', $task->project) }}" class="text-xs text-gray-400 hover:text-blue-600 dark:hover:text-blue-400">{{ $task->project->name }}</a>
                        @endif
                    </div>
                    @if($task->due_date)
                        <span class="text-sm whitespace-nowrap {{ $task->isOverdue() ? 'text-red-500 font-medium' : 'text-gray-500 dark:text-gray-400' }}">{{ $task->due_date->format('d.m.Y') }}</span>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">Keine anstehenden Aufgaben.</p>
            @endforelse
        </div>
    </div>

    <!-- Active Projects -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h2 class="font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Laufende Projekte
            </h2>
            <a href="{{ route('admin.projects.create') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">+ Neues Projekt</a>
        </div>
        <div class="p-5">
            @forelse($activeProjects as $project)
                <div class="flex justify-between items-center py-2 {{ !$loop->last ? 'border-b border-gray-100 dark:border-gray-700' : '' }}">
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.projects.show', $project) }}" class="text-sm font-medium text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400">{{ $project->name }}</a>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium {{ $project->status === 'in_progress' ? 'bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300' : 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-700 dark:text-yellow-300' }}">
                            {{ $project->status === 'in_progress' ? 'In Arbeit' : 'Geplant' }}
                        </span>
                    </div>
                    @if($project->deadline)
                        <span class="text-sm whitespace-nowrap {{ $project->deadline->lt(now()) ? 'text-red-500 font-medium' : 'text-gray-500 dark:text-gray-400' }}">{{ $project->deadline->format('d.m.Y') }}</span>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">Keine laufenden Projekte.</p>
            @endforelse
        </div>
    </div>

    <!-- Overdue Invoices -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Überfällige Rechnungen
            </h2>
        </div>
        <div class="p-5">
            @forelse($overdueInvoices as $invoice)
                <div class="flex justify-between items-center py-2 {{ !$loop->last ? 'border-b border-gray-100 dark:border-gray-700' : '' }}">
                    <a href="{{ route('admin.invoices.show', $invoice) }}" class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">{{ $invoice->invoice_number }}</a>
                    <span class="text-sm font-medium text-red-500">{{ number_format($invoice->amount, 2) }} {{ $invoice->currency }}</span>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">Keine überfälligen Rechnungen.</p>
            @endforelse
        </div>
    </div>

    <!-- Upcoming Birthdays -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 15.546c-.523 0-1.046.151-1.5.454a2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0A1.75 1.75 0 003 15.546V12a9 9 0 0118 0v3.546zM12 3v2m-4.243.757L6.343 7.17m9.9-1.413L17.657 7.17"/></svg>
                Geburtstage (3 Wochen)
            </h2>
        </div>
        <div class="p-5">
            @forelse($upcomingBirthdays as $contact)
                <div class="flex justify-between items-center py-2 {{ !$loop->last ? 'border-b border-gray-100 dark:border-gray-700' : '' }}">
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.contacts.show', $contact) }}" class="text-sm font-medium text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400">{{ $contact->full_name }}</a>
                        <span class="text-xs text-gray-400">wird {{ $contact->turns_age }} ({{ $contact->birth_date->format('d.m.Y') }})</span>
                    </div>
                    <span class="text-sm {{ $contact->next_birthday->isToday() ? 'text-green-600 dark:text-green-400 font-semibold' : 'text-gray-500 dark:text-gray-400' }}">
                        {{ $contact->next_birthday->isToday() ? 'Heute!' : $contact->next_birthday->format('d.m.') }}
                    </span>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">Keine Geburtstage in den nächsten 3 Wochen.</p>
            @endforelse
        </div>
    </div>

    <!-- Recent Contacts -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h2 class="font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                Letzte Kontakte
            </h2>
            <a href="{{ route('admin.contacts.create') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">+ Neuer Kontakt</a>
        </div>
        <div class="p-5">
            @forelse($recentContacts as $contact)
                <div class="flex justify-between items-center py-2 {{ !$loop->last ? 'border-b border-gray-100 dark:border-gray-700' : '' }}">
                    <div>
                        <a href="{{ route('admin.contacts.show', $contact) }}" class="text-sm font-medium text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400">{{ $contact->full_name }}</a>
                    </div>
                    <div class="flex gap-1">
                        @foreach($contact->types ?? [] as $type)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">{{ ucfirst($type) }}</span>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">Noch keine Kontakte vorhanden.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
