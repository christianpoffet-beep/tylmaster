{{-- Instagram-style feed card --}}
@php $imageCount = $post->images->count(); @endphp

<article class="bg-black text-white border border-gray-800 rounded-xl overflow-hidden">
    {{-- Header --}}
    <header class="flex items-center justify-between px-3 py-2.5">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-yellow-400 via-pink-500 to-purple-600 p-0.5">
                <div class="w-full h-full rounded-full bg-black flex items-center justify-center text-[10px] font-bold tracking-wider">TYL</div>
            </div>
            <div class="leading-tight">
                <p class="text-sm font-semibold">{{ $username }}</p>
                @if($post->title)
                    <p class="text-[11px] text-gray-400">{{ $post->title }}</p>
                @endif
            </div>
        </div>
        <button type="button" class="text-gray-400 hover:text-white" aria-label="Mehr">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><circle cx="5" cy="12" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/></svg>
        </button>
    </header>

    {{-- Image area --}}
    @if($imageCount)
        <div class="relative bg-black select-none"
             @touchstart="onTouchStart($event)"
             @touchend="onTouchEnd($event)">
            <div class="relative w-full" :style="ratio === 'auto' ? '' : ('aspect-ratio: ' + ratio + ';')">
                @foreach($post->images as $i => $img)
                    <div x-show="current === {{ $i }}"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         class="absolute inset-0 flex items-center justify-center">
                        <img src="{{ $img->url }}" alt="" class="w-full h-full"
                             :class="ratio === 'auto' ? 'object-contain' : 'object-cover'">
                    </div>
                @endforeach
            </div>

            {{-- Multi-image stack icon (top-right) --}}
            @if($imageCount > 1)
                <div class="absolute top-3 right-3 pointer-events-none">
                    <svg class="w-6 h-6 text-white drop-shadow" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17 3H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V5a2 2 0 00-2-2zM7 1h10a4 4 0 014 4v10a4 4 0 01-4 4H7a4 4 0 01-4-4V5a4 4 0 014-4z" fill-rule="evenodd"/>
                        <path d="M20 7a1 1 0 011 1v12a3 3 0 01-3 3H8a1 1 0 110-2h10a1 1 0 001-1V8a1 1 0 011-1z"/>
                    </svg>
                </div>

                {{-- Counter --}}
                <div class="absolute top-3 left-1/2 -translate-x-1/2 bg-black/70 text-white text-xs font-medium px-2 py-0.5 rounded-full">
                    <span x-text="current + 1"></span> / {{ $imageCount }}
                </div>

                {{-- Prev / Next --}}
                <button type="button" @click="prev()"
                    x-show="current > 0"
                    class="absolute left-2 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white text-gray-900 rounded-full w-8 h-8 flex items-center justify-center shadow-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button type="button" @click="next()"
                    x-show="current < {{ $imageCount - 1 }}"
                    class="absolute right-2 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white text-gray-900 rounded-full w-8 h-8 flex items-center justify-center shadow-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                </button>
            @endif
        </div>

        {{-- Action bar --}}
        <div class="flex items-center justify-between px-3 pt-3">
            <div class="flex items-center gap-4">
                <button type="button" class="hover:opacity-70" aria-label="Gefällt mir">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                </button>
                <button type="button" class="hover:opacity-70" aria-label="Kommentieren">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                </button>
                <button type="button" class="hover:opacity-70" aria-label="Teilen">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10l18-7-7 18-2.5-7.5L3 10z"/></svg>
                </button>
            </div>
            <button type="button" class="hover:opacity-70" aria-label="Speichern">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
            </button>
        </div>

        {{-- Dots for multi-image --}}
        @if($imageCount > 1)
            <div class="flex justify-center gap-1 pt-2">
                @foreach($post->images as $i => $img)
                    <button type="button" @click="current = {{ $i }}"
                        :class="current === {{ $i }} ? 'bg-sky-500' : 'bg-gray-600'"
                        class="w-1.5 h-1.5 rounded-full transition-colors"></button>
                @endforeach
            </div>
        @endif
    @endif

    {{-- Caption --}}
    @if($captionHtml)
        <div class="px-3 py-2.5 text-sm leading-relaxed"
             x-data="{ expanded: false, long: {{ strlen(strip_tags($captionHtml)) > 140 ? 'true' : 'false' }} }">
            <div :style="long && !expanded ? 'display:-webkit-box; -webkit-box-orient:vertical; -webkit-line-clamp:3; overflow:hidden' : ''">
                <span class="font-semibold mr-1.5">{{ $username }}</span>{!! $captionHtml !!}
            </div>
            <template x-if="long && !expanded">
                <button type="button" @click="expanded = true" class="text-gray-500 text-sm mt-0.5">… mehr</button>
            </template>
        </div>
    @endif

    {{-- Timestamp --}}
    <p class="px-3 pb-3 text-[11px] text-gray-500 uppercase tracking-wide">
        {{ $post->scheduled_at?->diffForHumans() ?? $post->created_at->diffForHumans() }}
    </p>
</article>
