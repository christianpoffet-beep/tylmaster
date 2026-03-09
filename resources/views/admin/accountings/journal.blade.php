@extends('admin.layouts.app')

@section('title', 'Buchungsjournal – ' . $accounting->name)

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <div>
        <h2 class="text-xl font-bold text-gray-900">Buchungsjournal</h2>
        <p class="text-sm text-gray-500 mt-1">{{ $accounting->name }} · {{ $accounting->accountable_name }}</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('admin.accountings.show', $accounting) }}" class="px-4 py-2 bg-white text-gray-700 text-sm rounded-lg border border-gray-300 hover:bg-gray-50">Zurück</a>
        @if(!$accounting->is_closed)
            <a href="{{ route('admin.bookings.create', $accounting) }}" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">+ Neue Buchung</a>
        @endif
    </div>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('admin.accountings.journal', $accounting) }}" class="flex flex-wrap gap-2 mb-4 items-end">
    <input type="date" name="from" value="{{ request('from') }}" class="rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Von">
    <input type="date" name="to" value="{{ request('to') }}" class="rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Bis">
    @if($projects->count())
        <select name="project_id" class="rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Alle Projekte</option>
            @foreach($projects as $project)
                <option value="{{ $project->id }}" {{ request('project_id') == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
            @endforeach
        </select>
    @endif
    @if($organizations->count())
        <select name="organization_id" class="rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Alle Organisationen</option>
            @foreach($organizations as $org)
                <option value="{{ $org->id }}" {{ request('organization_id') == $org->id ? 'selected' : '' }}>{{ $org->primary_name }}</option>
            @endforeach
        </select>
    @endif
    @if($contacts->count())
        <select name="contact_id" class="rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Alle Kontakte</option>
            @foreach($contacts as $contact)
                <option value="{{ $contact->id }}" {{ request('contact_id') == $contact->id ? 'selected' : '' }}>{{ $contact->full_name }}</option>
            @endforeach
        </select>
    @endif
    <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-700">Filtern</button>
    @if(request('from') || request('to') || request('project_id') || request('contact_id') || request('organization_id'))
        <a href="{{ route('admin.accountings.journal', $accounting) }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Zurücksetzen</a>
    @endif
</form>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-28">Datum</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-24">Beleg-Nr.</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Beschreibung</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Soll</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Haben</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-32">Betrag</th>
                    <th class="px-4 py-3 w-20"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($bookings as $booking)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 text-sm text-gray-500">{{ $booking->booking_date->format('d.m.Y') }}</td>
                        <td class="px-4 py-2 text-sm text-gray-400">
                            {{ $booking->reference ?? '—' }}
                            @if($booking->documents->count())
                                <svg class="w-3.5 h-3.5 inline ml-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="{{ $booking->documents->count() }} Beleg(e)"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-900">
                            {{ $booking->description }}
                            @if($booking->project || $booking->organization || $booking->contact)
                                <div class="flex flex-wrap gap-1 mt-0.5">
                                    @if($booking->project)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-indigo-100 text-indigo-700">{{ $booking->project->name }}</span>
                                    @endif
                                    @if($booking->organization)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-teal-100 text-teal-700">{{ $booking->organization->primary_name }}</span>
                                    @endif
                                    @if($booking->contact)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-purple-100 text-purple-700">{{ $booking->contact->full_name }}</span>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ $booking->debitAccount->number }} {{ $booking->debitAccount->name }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">{{ $booking->creditAccount->number }} {{ $booking->creditAccount->name }}</td>
                        <td class="px-4 py-2 text-sm text-right font-mono text-gray-900">{{ number_format($booking->amount, 2, '.', "'") }}</td>
                        <td class="px-4 py-2 text-right">
                            @if(!$accounting->is_closed)
                                <a href="{{ route('admin.bookings.edit', $booking) }}" class="text-xs text-blue-600 hover:text-blue-800">Bearb.</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">Keine Buchungen vorhanden.</td>
                    </tr>
                @endforelse
            </tbody>
            @if($bookings->count())
            <tfoot class="bg-gray-50">
                <tr>
                    <td colspan="5" class="px-4 py-2 text-sm font-medium text-gray-700 text-right">Total:</td>
                    <td class="px-4 py-2 text-sm text-right font-mono font-bold text-gray-900">{{ number_format($bookings->sum('amount'), 2, '.', "'") }}</td>
                    <td></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
<div class="mt-4">{{ $bookings->links() }}</div>
@endsection
