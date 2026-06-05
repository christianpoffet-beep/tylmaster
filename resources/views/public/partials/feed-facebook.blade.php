{{-- Facebook-style feed card --}}
@php $imageCount = $post->images->count(); @endphp

<article class="bg-white text-gray-900 border border-gray-200 rounded-xl overflow-hidden shadow-sm">
    {{-- Header --}}
    <header class="flex items-center justify-between px-4 py-3">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center text-white text-xs font-bold">TYL</div>
            <div class="leading-tight">
                <p class="text-[15px] font-semibold text-gray-900">{{ $displayName }}</p>
                <p class="text-xs text-gray-500 flex items-center gap-1">
                    <span>{{ $post->scheduled_at?->diffForHumans() ?? $post->created_at->diffForHumans() }}</span>
                    <span>·</span>
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm0 2a10 10 0 100 20 10 10 0 000-20zM6 12a6 6 0 0112 0 6 6 0 01-12 0z"/></svg>
                </p>
            </div>
        </div>
        <button type="button" class="text-gray-500 hover:bg-gray-100 p-1.5 rounded-full">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/></svg>
        </button>
    </header>

    {{-- Caption above image (Facebook convention) --}}
    @if($captionHtml)
        <div class="px-4 pb-3 text-[15px] leading-relaxed text-gray-900"
             x-data="{ expanded: false, long: {{ strlen(strip_tags($captionHtml)) > 200 ? 'true' : 'false' }} }">
            @if($post->title)
                <p class="font-semibold text-base mb-1">{{ $post->title }}</p>
            @endif
            <div :style="long && !expanded ? 'display:-webkit-box; -webkit-box-orient:vertical; -webkit-line-clamp:4; overflow:hidden' : ''">{!! $captionHtml !!}</div>
            <template x-if="long && !expanded">
                <button type="button" @click="expanded = true" class="text-gray-500 text-sm mt-1 hover:underline">Mehr anzeigen</button>
            </template>
        </div>
    @endif

    {{-- Image area --}}
    @if($imageCount)
        <div class="relative bg-gray-100 select-none"
             @touchstart="onTouchStart($event)"
             @touchend="onTouchEnd($event)">
            <div class="relative w-full" style="aspect-ratio: 1 / 1;">
                @foreach($post->images as $i => $img)
                    <div x-show="current === {{ $i }}"
                         class="absolute inset-0 flex items-center justify-center">
                        <img src="{{ $img->url }}" alt="" class="w-full h-full object-cover">
                    </div>
                @endforeach
            </div>

            @if($imageCount > 1)
                <div class="absolute top-3 right-3 bg-gray-900/75 text-white text-xs font-medium px-2 py-1 rounded-full">
                    <span x-text="current + 1"></span> / {{ $imageCount }}
                </div>

                <button type="button" @click="prev()"
                    class="absolute left-3 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white rounded-full w-9 h-9 flex items-center justify-center shadow-lg ring-1 ring-white/40 backdrop-blur-sm transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button type="button" @click="next()"
                    class="absolute right-3 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white rounded-full w-9 h-9 flex items-center justify-center shadow-lg ring-1 ring-white/40 backdrop-blur-sm transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                </button>
            @endif
        </div>
    @endif

    {{-- Reactions bar --}}
    <div class="flex items-center justify-between px-4 py-2 text-xs text-gray-500 border-t border-gray-100">
        <div class="flex items-center gap-1">
            <span class="inline-flex -space-x-1">
                <span class="w-4 h-4 rounded-full bg-blue-500 flex items-center justify-center text-[8px] text-white">👍</span>
                <span class="w-4 h-4 rounded-full bg-red-500 flex items-center justify-center text-[8px] text-white">❤</span>
            </span>
            <span>128</span>
        </div>
        <div class="flex items-center gap-3">
            <span>12 Kommentare</span>
            <span>3 mal geteilt</span>
        </div>
    </div>

    {{-- Action bar --}}
    <div class="grid grid-cols-3 border-t border-gray-100 text-sm font-medium text-gray-600">
        <button type="button" class="flex items-center justify-center gap-2 py-2.5 hover:bg-gray-50">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/></svg>
            Gefällt mir
        </button>
        <button type="button" class="flex items-center justify-center gap-2 py-2.5 hover:bg-gray-50">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
            Kommentieren
        </button>
        <button type="button" class="flex items-center justify-center gap-2 py-2.5 hover:bg-gray-50">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
            Teilen
        </button>
    </div>
</article>
