@extends('admin.layouts.app')

@section('title', 'Versandprotokoll: ' . $campaign->name)

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.campaigns.show', $campaign) }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">&larr; Zurück zur Kampagne</a>
</div>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Versandprotokoll: {{ $campaign->name }}</h1>
    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $campaign->status_color }}">{{ $campaign->status_label }}</span>
</div>

<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Empfänger</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">E-Mail</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Gesendet</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Fehler</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($sends as $send)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                            {{ $send->name }}
                            <span class="text-xs text-gray-400">({{ $send->recipient_type === 'contact' ? 'Kontakt' : 'Organisation' }})</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $send->email }}</td>
                        <td class="px-4 py-3">
                            @if($send->status === 'sent')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300">Gesendet</span>
                            @elseif($send->status === 'failed')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300">Fehler</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">Ausstehend</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $send->sent_at?->format('d.m.Y H:i') ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-red-500">{{ $send->error ?? '' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Noch keine Einträge.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@if($sends->hasPages())
    <div class="mt-4">{{ $sends->links() }}</div>
@endif
@endsection
