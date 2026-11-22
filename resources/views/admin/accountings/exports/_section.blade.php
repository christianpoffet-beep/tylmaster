{{-- One section of the Jahresrechnung: groups under their header accounts, subtotals, section total.
     Expects $section, $title, $withCompare, and optionally $withTotal (default true). --}}
@php $fmt = fn ($v) => $v === null ? '' : number_format($v, 2, '.', "'"); @endphp
<tr class="section"><td colspan="{{ $withCompare ? 4 : 3 }}">{{ $title }}</td></tr>
@forelse($section['groups'] as $group)
    {{-- A header named like its section ("Ertrag" under Ertrag) needs no title of its own --}}
    @php $groupTitle = $group['title'] !== $title ? $group['title'] : null; @endphp
    @if($groupTitle)
        <tr class="group"><td></td><td colspan="{{ $withCompare ? 3 : 2 }}">{{ $groupTitle }}</td></tr>
    @endif
    @foreach($group['lines'] as $line)
        <tr>
            <td class="nr">{{ $line['number'] }}</td>
            <td>{{ $line['name'] }}</td>
            <td class="num">{{ $fmt($line['amount']) }}</td>
            @if($withCompare)<td class="num compare">{{ $fmt($line['compare']) }}</td>@endif
        </tr>
    @endforeach
    @if($groupTitle && count($section['groups']) > 1)
        <tr class="subtotal">
            <td></td>
            <td>Total {{ $groupTitle }}</td>
            <td class="num">{{ $fmt($group['total']) }}</td>
            @if($withCompare)<td class="num compare">{{ $fmt($group['compare']) }}</td>@endif
        </tr>
    @endif
@empty
    <tr><td></td><td class="muted" colspan="{{ $withCompare ? 3 : 2 }}">Keine Beträge</td></tr>
@endforelse
@if($withTotal ?? true)
    <tr class="total">
        <td></td>
        <td>Total {{ $title }}</td>
        <td class="num">{{ $fmt($section['total']) }}</td>
        @if($withCompare)<td class="num compare">{{ $fmt($section['compare']) }}</td>@endif
    </tr>
@endif
