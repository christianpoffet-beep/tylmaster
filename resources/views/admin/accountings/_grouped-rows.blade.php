{{--
    Table rows of one side of the balance sheet or the income statement,
    grouped under their header accounts: the header row carries the subtotal.
    Parameters: $groups (ChartGroups::of), $accounting, $withOpening (bool)
--}}
@php $fmt = fn ($v) => number_format($v, 2, '.', "'"); @endphp
@foreach($groups as $group)
    @continue($group['accounts']->isEmpty())
    @if($group['header'])
        @php $subtotal = $group['accounts']->sum(fn ($a) => $a->balance); @endphp
        <tr class="bg-gray-50 dark:bg-gray-700/50">
            <td class="px-4 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $group['header']->number }}</td>
            <td class="px-4 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $group['header']->name }}</td>
            @if($withOpening)
                <td class="px-4 py-2 text-sm text-right font-mono font-semibold text-gray-500 dark:text-gray-400">{{ $fmt($group['accounts']->sum('opening_balance')) }}</td>
            @endif
            <td class="px-4 py-2 text-sm text-right font-mono font-semibold {{ $subtotal < 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-gray-100' }}" title="Zwischensumme «{{ $group['header']->name }}»">{{ $fmt($subtotal) }}</td>
        </tr>
    @endif
    @foreach($group['accounts'] as $account)
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
            <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 {{ $group['header'] ? 'pl-8' : '' }}">{{ $account->number }}</td>
            <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-100">
                <a href="{{ route('admin.accountings.ledger', [$accounting, $account]) }}" class="hover:text-blue-600 dark:hover:text-blue-400">{{ $account->name }}</a>
            </td>
            @if($withOpening)
                <td class="px-4 py-2 text-sm text-right font-mono text-gray-500 dark:text-gray-400">{{ $fmt($account->opening_balance) }}</td>
            @endif
            <td class="px-4 py-2 text-sm text-right font-mono {{ $account->balance < 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-gray-100' }}">{{ $fmt($account->balance) }}</td>
        </tr>
    @endforeach
@endforeach
