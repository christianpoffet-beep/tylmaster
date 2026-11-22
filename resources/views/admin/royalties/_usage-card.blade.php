{{-- Usage reported by Rebeat for a track or a product ($subject); shown once there is some --}}
@php
    $usageTotals = (new \App\Services\Royalties\RoyaltyUsage($subject))->totals();
    $usageKey = $subject instanceof \App\Models\Track ? 'track' : 'release';
    $usageInt = fn ($v) => number_format((int) $v, 0, '.', "'");
@endphp
@if($usageTotals['lines'] > 0)
    <x-admin.collapsible-card title="Nutzung bei Rebeat" class="mt-6">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            @foreach(['Streams' => $usageInt($usageTotals['stream']), 'Downloads' => $usageInt($usageTotals['download']), 'Videos' => $usageInt($usageTotals['video']), 'Gutschrift' => 'EUR ' . number_format($usageTotals['amount'], 2, '.', "'")] as $label => $value)
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $value }}</p>
                </div>
            @endforeach
        </div>
        <a href="{{ route('admin.royalties.usage', [$usageKey => $subject->id]) }}" class="inline-block mt-3 text-sm text-blue-600 dark:text-blue-400 hover:underline">Nach Monat, Land, Dienst und Abotyp ansehen</a>
    </x-admin.collapsible-card>
@endif
