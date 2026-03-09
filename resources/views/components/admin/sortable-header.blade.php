@props(['column', 'default' => false])

@php
    $currentSort = request('sort', '');
    $currentDir = request('dir', 'asc');
    $isActive = $currentSort === $column || ($default && !$currentSort);
    $nextDir = $isActive && $currentDir === 'asc' ? 'desc' : 'asc';
@endphp

<th {{ $attributes->merge(['class' => 'px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase']) }}>
    <a href="{{ request()->fullUrlWithQuery(['sort' => $column, 'dir' => $nextDir]) }}" class="group inline-flex items-center gap-1 hover:text-gray-700">
        {{ $slot }}
        @if($isActive)
            <svg class="w-3 h-3 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                @if($currentDir === 'asc')
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                @else
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                @endif
            </svg>
        @else
            <svg class="w-3 h-3 text-gray-300 group-hover:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
            </svg>
        @endif
    </a>
</th>
