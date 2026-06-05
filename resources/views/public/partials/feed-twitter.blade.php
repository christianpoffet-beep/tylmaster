{{-- X / Twitter-style feed card --}}
@php $imageCount = $post->images->count(); @endphp

<article class="bg-black text-white border border-gray-800 rounded-xl px-4 py-3">
    <div class="flex gap-3">
        {{-- Avatar --}}
        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-gray-700 to-gray-900 flex-shrink-0 flex items-center justify-center text-xs font-bold tracking-wider">TYL</div>

        <div class="flex-1 min-w-0">
            {{-- Header --}}
            <div class="flex items-center gap-1.5 text-sm">
                <span class="font-bold text-white">{{ $displayName }}</span>
                <svg class="w-4 h-4 text-sky-500" fill="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <span class="text-gray-500 truncate">@{{ $username }}</span>
                <span class="text-gray-500">·</span>
                <span class="text-gray-500 whitespace-nowrap">{{ $post->scheduled_at?->diffForHumans(null, true) ?? $post->created_at->diffForHumans(null, true) }}</span>
                <button type="button" class="ml-auto text-gray-500 hover:bg-gray-900 rounded-full p-1" aria-label="Mehr">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="5" cy="12" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/></svg>
                </button>
            </div>

            {{-- Caption --}}
            @if($captionHtml)
                <div class="mt-1 text-[15px] leading-snug text-white whitespace-pre-line">
                    @if($post->title)
                        <p class="font-semibold mb-1">{{ $post->title }}</p>
                    @endif
                    {!! $captionHtml !!}
                </div>
            @endif

            {{-- Image area --}}
            @if($imageCount)
                <div class="mt-3 relative bg-gray-900 rounded-2xl overflow-hidden border border-gray-800 select-none"
                     @touchstart="onTouchStart($event)"
                     @touchend="onTouchEnd($event)">
                    <div class="relative w-full overflow-hidden" style="aspect-ratio: 1 / 1;">
                        @foreach($post->images as $i => $img)
                            <div x-show="current === {{ $i }}"
                                 class="absolute inset-0 flex items-center justify-center">
                                <img src="{{ $img->url }}" alt="" class="w-full h-full object-cover">
                            </div>
                        @endforeach

                        @if($imageCount > 1)
                            <div class="absolute top-3 right-3 bg-black/80 text-white text-xs font-semibold px-2 py-1 rounded-full">
                                <span x-text="current + 1"></span> / {{ $imageCount }}
                            </div>

                            <button type="button" @click="prev()"
                                class="absolute left-2 top-1/2 -translate-y-1/2 bg-black/60 hover:bg-black/80 text-white rounded-full w-9 h-9 flex items-center justify-center ring-1 ring-white/40 backdrop-blur-sm transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                            </button>
                            <button type="button" @click="next()"
                                class="absolute right-2 top-1/2 -translate-y-1/2 bg-black/60 hover:bg-black/80 text-white rounded-full w-9 h-9 flex items-center justify-center ring-1 ring-white/40 backdrop-blur-sm transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        @endif
                    </div>
                </div>

                @if($imageCount > 1)
                    <div class="flex justify-center gap-1 mt-2">
                        @foreach($post->images as $i => $img)
                            <button type="button" @click="current = {{ $i }}"
                                :class="current === {{ $i }} ? 'bg-sky-500' : 'bg-gray-700'"
                                class="w-1.5 h-1.5 rounded-full transition-colors"></button>
                        @endforeach
                    </div>
                @endif
            @endif

            {{-- Action bar --}}
            <div class="mt-3 flex items-center justify-between text-gray-500 max-w-md">
                <button type="button" class="group inline-flex items-center gap-2 hover:text-sky-400">
                    <span class="p-2 rounded-full group-hover:bg-sky-500/10"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg></span>
                    <span class="text-xs">12</span>
                </button>
                <button type="button" class="group inline-flex items-center gap-2 hover:text-green-400">
                    <span class="p-2 rounded-full group-hover:bg-green-500/10"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg></span>
                    <span class="text-xs">34</span>
                </button>
                <button type="button" class="group inline-flex items-center gap-2 hover:text-pink-400">
                    <span class="p-2 rounded-full group-hover:bg-pink-500/10"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg></span>
                    <span class="text-xs">218</span>
                </button>
                <button type="button" class="group inline-flex items-center gap-2 hover:text-sky-400">
                    <span class="p-2 rounded-full group-hover:bg-sky-500/10"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19v-8m0 0L8 15m4-4l4 4M5 4h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5a1 1 0 011-1z"/></svg></span>
                </button>
            </div>
        </div>
    </div>
</article>
