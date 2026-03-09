@props(['title', 'count' => null, 'open' => true])

<div x-data="{ open: {{ $open ? 'true' : 'false' }} }" {{ $attributes->merge(['class' => 'bg-white rounded-xl shadow-sm border border-gray-200']) }}>
    <div @click="open = !open" class="flex items-center justify-between cursor-pointer px-6 py-4">
        <h3 class="text-sm font-semibold text-gray-700">
            {{ $title }}@if($count !== null) ({{ $count }})@endif
        </h3>
        <div class="flex items-center gap-2">
            @if(isset($actions))
                <div @click.stop>{{ $actions }}</div>
            @endif
            <svg :class="open && 'rotate-180'" class="w-4 h-4 text-gray-400 transition-transform duration-200 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
    </div>
    <div x-show="open" class="px-6 pb-6">
        {{ $slot }}
    </div>
</div>
