@extends('admin.layouts.app')

@section('title', 'Probebilanz – ' . $accounting->name)

@php
    $typeColors = ['asset' => 'bg-blue-100 text-blue-700', 'liability' => 'bg-purple-100 text-purple-700', 'income' => 'bg-green-100 text-green-700', 'expense' => 'bg-red-100 text-red-700'];
    $grandDebit = 0;
    $grandCredit = 0;
@endphp

@section('content')
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <div>
        <h2 class="text-xl font-bold text-gray-900">Probebilanz</h2>
        <p class="text-sm text-gray-500 mt-1">{{ $accounting->name }} · {{ $accounting->accountable_name }} · {{ $accounting->period_start->format('d.m.Y') }} – {{ $accounting->period_end->format('d.m.Y') }}</p>
    </div>
    <a href="{{ route('admin.accountings.show', $accounting) }}" class="px-4 py-2 bg-white text-gray-700 text-sm rounded-lg border border-gray-300 hover:bg-gray-50">Zurück</a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-24">Nr.</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bezeichnung</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-32">Soll</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-32">Haben</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-32">Saldo</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach(['asset', 'liability', 'income', 'expense'] as $type)
                    @if(isset($grouped[$type]))
                        <tr class="bg-gray-50">
                            <td colspan="5" class="px-4 py-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $typeColors[$type] }}">{{ $typeLabels[$type] }}</span>
                            </td>
                        </tr>
                        @foreach($grouped[$type] as $account)
                            @php
                                $debit = $account->debit_total;
                                $credit = $account->credit_total;
                                $balance = $account->balance;
                                $grandDebit += $debit;
                                $grandCredit += $credit;
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-sm text-gray-700 pl-8">{{ $account->number }}</td>
                                <td class="px-4 py-2 text-sm text-gray-900">
                                    <a href="{{ route('admin.accountings.ledger', [$accounting, $account]) }}" class="hover:text-blue-600">{{ $account->name }}</a>
                                </td>
                                <td class="px-4 py-2 text-sm text-right font-mono text-gray-700">{{ $debit > 0 ? number_format($debit, 2, '.', "'") : '' }}</td>
                                <td class="px-4 py-2 text-sm text-right font-mono text-gray-700">{{ $credit > 0 ? number_format($credit, 2, '.', "'") : '' }}</td>
                                <td class="px-4 py-2 text-sm text-right font-mono {{ $balance < 0 ? 'text-red-600' : 'text-gray-900' }}">{{ number_format($balance, 2, '.', "'") }}</td>
                            </tr>
                        @endforeach
                    @endif
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50">
                <tr class="font-bold">
                    <td colspan="2" class="px-4 py-3 text-sm text-gray-900">Total</td>
                    <td class="px-4 py-3 text-sm text-right font-mono text-gray-900">{{ number_format($grandDebit, 2, '.', "'") }}</td>
                    <td class="px-4 py-3 text-sm text-right font-mono text-gray-900">{{ number_format($grandCredit, 2, '.', "'") }}</td>
                    <td class="px-4 py-3 text-sm text-right font-mono {{ abs($grandDebit - $grandCredit) > 0.01 ? 'text-red-600' : 'text-green-600' }}">
                        {{ number_format($grandDebit - $grandCredit, 2, '.', "'") }}
                        @if(abs($grandDebit - $grandCredit) < 0.01)
                            <span class="text-green-500 ml-1">&#10003;</span>
                        @endif
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
