{{-- One product (or loose track) of the contract overview; $row from RoyaltyCoverage, helpers from the page --}}
@php [$badgeLabel, $badgeClass] = $badges[$row->status]; @endphp
<tr class="align-top">
    <td class="px-5 py-2.5">
        <a href="{{ $url }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $title }}</a>
        <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $subtitle ?: '—' }}</span>
    </td>
    <td class="px-3 py-2.5 text-right tabular-nums text-gray-900 dark:text-gray-100 whitespace-nowrap">{{ $eur($row->amount) }}</td>
    <td class="px-3 py-2.5">
        @forelse($row->contracts as $contract)
            <div class="mb-1 last:mb-0">
                <a href="{{ route('admin.contracts.show', $contract) }}" class="text-gray-900 dark:text-gray-100 hover:underline">{{ $contract->contract_number ?: 'ohne Nr.' }}</a>
                <span class="text-xs text-gray-500 dark:text-gray-400">· {{ $statusLabels[$contract->status] ?? $contract->status }}{{ $contract->start_date ? ' · ab ' . $contract->start_date->format('d.m.Y') : '' }}</span>
                @if($split = $splitText($contract))
                    <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $split }}</span>
                @else
                    <span class="block text-xs text-amber-600 dark:text-amber-400">kein Recht «Streaming & Downloads» mit Prozenten</span>
                @endif
            </div>
        @empty
            @if($row->label)
                <span class="text-xs text-gray-500 dark:text-gray-400">ohne Vertrag: 100 % an {{ $row->label->primary_name }}</span>
            @else
                <span class="text-xs text-gray-400">—</span>
            @endif
        @endforelse
    </td>
    <td class="px-5 py-2.5 text-right whitespace-nowrap">
        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $badgeClass }}">{{ $badgeLabel }}</span>
        @if($row->contracts->isEmpty())
            <a href="{{ $createUrl }}" class="block mt-1 text-xs text-blue-600 dark:text-blue-400 hover:underline">Vertrag erfassen</a>
        @endif
    </td>
</tr>
