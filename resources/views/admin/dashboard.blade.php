@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
<!-- Stats Grid -->
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200">
        <div class="text-sm font-medium text-gray-500">Kontakte</div>
        <div class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['contacts'] }}</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200">
        <div class="text-sm font-medium text-gray-500">Aktive Verträge</div>
        <div class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['contracts'] }}</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200">
        <div class="text-sm font-medium text-gray-500">Laufende Projekte</div>
        <div class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['projects'] }}</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200">
        <div class="text-sm font-medium text-gray-500">Offene Aufgaben</div>
        <div class="text-2xl font-bold {{ $stats['open_tasks'] > 0 ? 'text-orange-600' : 'text-gray-900' }} mt-1">{{ $stats['open_tasks'] }}</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200">
        <div class="text-sm font-medium text-gray-500">Offene Rechnungen</div>
        <div class="text-2xl font-bold text-orange-600 mt-1">{{ $stats['open_invoices'] }}</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200">
        <div class="text-sm font-medium text-gray-500">Neue Submissions</div>
        <div class="text-2xl font-bold text-blue-600 mt-1">{{ $stats['submissions'] }}</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Upcoming Tasks -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="font-semibold text-gray-800">Anstehende Aufgaben</h2>
            <a href="{{ route('admin.tasks.create') }}" class="text-sm text-blue-600 hover:text-blue-800">+ Neue Aufgabe</a>
        </div>
        <div class="p-5">
            @forelse($upcomingTasks as $task)
                <div class="flex justify-between items-center py-2 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                    <div class="flex items-center gap-2">
                        <form method="POST" action="{{ route('admin.tasks.toggle', $task) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="w-4 h-4 rounded border flex items-center justify-center border-gray-300 hover:border-blue-400">
                            </button>
                        </form>
                        <a href="{{ route('admin.tasks.show', $task) }}" class="text-sm font-medium text-gray-900 hover:text-blue-600">{{ $task->title }}</a>
                        @if($task->priority === 'high')
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">!</span>
                        @endif
                        @if($task->project)
                            <a href="{{ route('admin.projects.show', $task->project) }}" class="text-xs text-gray-400 hover:text-blue-600">{{ $task->project->name }}</a>
                        @endif
                    </div>
                    @if($task->due_date)
                        <span class="text-sm whitespace-nowrap {{ $task->isOverdue() ? 'text-red-500 font-medium' : 'text-gray-500' }}">{{ $task->due_date->format('d.m.Y') }}</span>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-500">Keine anstehenden Aufgaben.</p>
            @endforelse
        </div>
    </div>

    <!-- Expiring Contracts -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-200">
            <h2 class="font-semibold text-gray-800">Ablaufende Verträge (30 Tage)</h2>
        </div>
        <div class="p-5">
            @forelse($expiringContracts as $contract)
                <div class="flex justify-between items-center py-2 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                    <a href="{{ route('admin.contracts.show', $contract) }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">{{ $contract->title }}</a>
                    <span class="text-sm text-red-500">{{ $contract->end_date->format('d.m.Y') }}</span>
                </div>
            @empty
                <p class="text-sm text-gray-500">Keine ablaufenden Verträge.</p>
            @endforelse
        </div>
    </div>

    <!-- Overdue Invoices -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-200">
            <h2 class="font-semibold text-gray-800">Überfällige Rechnungen</h2>
        </div>
        <div class="p-5">
            @forelse($overdueInvoices as $invoice)
                <div class="flex justify-between items-center py-2 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                    <a href="{{ route('admin.invoices.show', $invoice) }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">{{ $invoice->invoice_number }}</a>
                    <span class="text-sm font-medium text-red-500">{{ number_format($invoice->amount, 2) }} {{ $invoice->currency }}</span>
                </div>
            @empty
                <p class="text-sm text-gray-500">Keine überfälligen Rechnungen.</p>
            @endforelse
        </div>
    </div>

    <!-- Upcoming Birthdays -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-200">
            <h2 class="font-semibold text-gray-800">Geburtstage (3 Wochen)</h2>
        </div>
        <div class="p-5">
            @forelse($upcomingBirthdays as $contact)
                <div class="flex justify-between items-center py-2 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.contacts.show', $contact) }}" class="text-sm font-medium text-gray-900 hover:text-blue-600">{{ $contact->full_name }}</a>
                        <span class="text-xs text-gray-400">wird {{ $contact->turns_age }}</span>
                    </div>
                    <span class="text-sm {{ $contact->next_birthday->isToday() ? 'text-green-600 font-semibold' : 'text-gray-500' }}">
                        {{ $contact->next_birthday->isToday() ? 'Heute!' : $contact->next_birthday->format('d.m.') }}
                    </span>
                </div>
            @empty
                <p class="text-sm text-gray-500">Keine Geburtstage in den nächsten 3 Wochen.</p>
            @endforelse
        </div>
    </div>

    <!-- Recent Contacts -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="font-semibold text-gray-800">Letzte Kontakte</h2>
            <a href="{{ route('admin.contacts.create') }}" class="text-sm text-blue-600 hover:text-blue-800">+ Neuer Kontakt</a>
        </div>
        <div class="p-5">
            @forelse($recentContacts as $contact)
                <div class="flex justify-between items-center py-2 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                    <div>
                        <a href="{{ route('admin.contacts.show', $contact) }}" class="text-sm font-medium text-gray-900 hover:text-blue-600">{{ $contact->full_name }}</a>
                    </div>
                    <div class="flex gap-1">
                        @foreach($contact->types ?? [] as $type)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">{{ ucfirst($type) }}</span>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500">Noch keine Kontakte vorhanden.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
