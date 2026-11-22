{{-- The four pages of the statements to artists, as tabs --}}
@php
    $royaltyTabs = [
        ['admin.royalties.index', 'Rebeat-Matcher', 'admin.royalties.index||admin.royalties.show'],
        ['admin.royalties.contracts', 'Verträge', 'admin.royalties.contracts'],
        ['admin.royalties.payments.index', 'Rebeat-Zahlungen', 'admin.royalties.payments.*'],
        ['admin.royalties.statements.index', 'Abrechnungen', 'admin.royalties.statements.*'],
        ['admin.royalties.payouts.index', 'Saldi & Auszahlungen', 'admin.royalties.payouts.*'],
    ];
@endphp
<nav class="flex flex-wrap gap-1 mb-6 border-b border-gray-200 dark:border-gray-700">
    @foreach($royaltyTabs as [$tabRoute, $tabLabel, $tabMatch])
        @php $tabActive = collect(explode('||', $tabMatch))->contains(fn ($p) => request()->routeIs($p)); @endphp
        <a href="{{ route($tabRoute) }}"
           class="px-3 py-2 -mb-px text-sm border-b-2 {{ $tabActive ? 'border-blue-600 text-blue-700 dark:text-blue-300 font-medium' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200' }}">{{ $tabLabel }}</a>
    @endforeach
</nav>
