{{-- Balance of an organization or a contact from the statements to artists; shown once there is a movement --}}
@php
    $royaltyEntries = app(\App\Services\Royalties\RoyaltyBalances::class)->entries($who)->orderByDesc('entry_date')->orderByDesc('id')->get();
@endphp
@if($royaltyEntries->isNotEmpty())
    @php $royaltyBalance = round((float) $royaltyEntries->sum('amount'), 2); @endphp
    <x-admin.collapsible-card title="Abrechnungen">
        <div class="flex items-baseline justify-between mb-2">
            <span class="text-xs text-gray-500 dark:text-gray-400">Saldo zu ihren Gunsten</span>
            <span class="text-lg font-semibold tabular-nums text-gray-900 dark:text-gray-100">CHF {{ number_format($royaltyBalance, 2, '.', "'") }}</span>
        </div>
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
            @foreach($royaltyEntries->take(8) as $entry)
                <div class="flex justify-between gap-2 py-1 text-xs">
                    <span class="text-gray-600 dark:text-gray-300">
                        {{ $entry->entry_date->format('d.m.Y') }} ·
                        @if($entry->royalty_statement_id)
                            <a href="{{ route('admin.royalties.statements.show', $entry->royalty_statement_id) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $entry->description }}</a>
                        @elseif($entry->royalty_payout_id)
                            <a href="{{ route('admin.royalties.payouts.show', $entry->royalty_payout_id) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $entry->description }}</a>
                        @else
                            {{ $entry->description }}
                        @endif
                    </span>
                    <span class="tabular-nums {{ $entry->amount < 0 ? 'text-gray-500 dark:text-gray-400' : 'text-gray-900 dark:text-gray-100' }}">{{ number_format((float) $entry->amount, 2, '.', "'") }}</span>
                </div>
            @endforeach
        </div>
        <a href="{{ route('admin.royalties.payouts.index') }}" class="inline-block mt-2 text-xs text-blue-600 dark:text-blue-400 hover:underline">Saldi & Auszahlungen</a>
    </x-admin.collapsible-card>
@endif
