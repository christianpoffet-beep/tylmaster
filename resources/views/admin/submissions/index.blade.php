@extends('admin.layouts.app')

@section('title', 'Music Submissions')

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <form method="GET" action="{{ route('admin.submissions.index') }}" class="flex flex-wrap gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Suche..." class="rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
        <select name="status" class="rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Alle Status</option>
            @foreach(['new' => 'Neu', 'reviewed' => 'Geprüft', 'accepted' => 'Akzeptiert', 'rejected' => 'Abgelehnt'] as $value => $label)
                <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-lg hover:bg-gray-700">Filtern</button>
        @if(request('search') || request('status'))
            <a href="{{ route('admin.submissions.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Zurücksetzen</a>
        @endif
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <x-admin.sortable-header column="artist_name">Artist</x-admin.sortable-header>
                    <x-admin.sortable-header column="project_name">Projekt / Track</x-admin.sortable-header>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Genre</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Songs</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Zahlung</th>
                    <x-admin.sortable-header column="status">Status</x-admin.sortable-header>
                    <x-admin.sortable-header column="created_at">Eingegangen</x-admin.sortable-header>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($submissions as $submission)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">
                            {{ $submission->artist_name ?? '-' }}
                            @if($submission->contact_id)
                                <span class="ml-1 text-green-500" title="Kontakt verknüpft">&#10003;</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900">{{ $submission->project_name ?? $submission->track_title ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $submission->genre ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $submission->songs_count }}</td>
                        <td class="px-4 py-3">
                            @if($submission->payment_status === 'paid')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">Bezahlt</span>
                            @elseif($submission->payment_status === 'pending')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-700">Ausstehend</span>
                            @else
                                <span class="text-xs text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @switch($submission->status)
                                @case('new')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700">Neu</span>
                                    @break
                                @case('reviewed')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-700">Geprüft</span>
                                    @break
                                @case('accepted')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">Akzeptiert</span>
                                    @break
                                @case('rejected')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">Abgelehnt</span>
                                    @break
                            @endswitch
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $submission->created_at->format('d.m.Y') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.submissions.show', $submission) }}" class="text-sm text-blue-600 hover:text-blue-800">Ansehen</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">Keine Submissions gefunden.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">{{ $submissions->links() }}</div>
@endsection
