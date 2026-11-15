{{--
    Global audio player. The state lives in the Alpine store "player"
    (resources/js/app.js) and carries over page loads via sessionStorage.

    Two faces: the full bar at the bottom, which folds away a few seconds after
    the last interaction, and a small button in the bottom right corner that
    keeps play/pause and the progress at hand and opens the bar again.
--}}
<audio id="global-audio" preload="metadata" style="display:none"></audio>

{{-- Full bar --}}
<div id="audio-player" data-player-ui
     class="fixed bottom-0 left-0 right-0 z-50 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 shadow-lg transition-transform duration-300"
     :class="$store.player.active && !$store.player.minimized ? 'translate-y-0' : 'translate-y-full'"
     x-data
     @play-track.window="$store.player.load($event.detail)"
     @mouseenter="$store.player.holdOpen()"
     @mouseleave="$store.player.scheduleMinimize()"
     @focusin="$store.player.holdOpen()"
     @focusout="$store.player.scheduleMinimize()"
     @pointerdown="$event.pointerType !== 'mouse' && $store.player.scheduleMinimize()">

    {{-- Progress bar (clickable) --}}
    <div class="h-1 bg-gray-200 dark:bg-gray-700 cursor-pointer group" @click="$store.player.seek($event)">
        <div class="h-full bg-blue-600 transition-all duration-150 group-hover:bg-blue-500"
             :style="'width: ' + $store.player.progress + '%'"></div>
    </div>

    <div class="flex items-center gap-4 px-4 py-2 max-w-screen-xl mx-auto">
        {{-- Play/Pause --}}
        <button type="button" @click="$store.player.toggle()"
                class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-full bg-blue-600 hover:bg-blue-700 text-white transition"
                :class="$store.player.waitingForClick && 'animate-pulse'"
                :title="$store.player.waitingForClick ? 'Läuft weiter, sobald du irgendwo klickst' : ($store.player.playing ? 'Pause' : 'Abspielen')">
            <svg x-show="!$store.player.playing" class="w-5 h-5 ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
            <svg x-show="$store.player.playing" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
        </button>

        {{-- Track info --}}
        <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate" x-text="$store.player.title"></p>
            <p class="text-xs text-gray-500 dark:text-gray-400 truncate" x-show="$store.player.artist && !$store.player.waitingForClick" x-text="$store.player.artist"></p>
            <p class="text-xs text-blue-600 dark:text-blue-400 truncate" x-show="$store.player.waitingForClick" x-cloak>Läuft weiter, sobald du irgendwo auf die Seite klickst</p>
        </div>

        {{-- Time --}}
        <div class="flex-shrink-0 text-xs text-gray-500 dark:text-gray-400 tabular-nums">
            <span x-text="$store.player.formatTime($store.player.currentTime)"></span>
            <span>/</span>
            <span x-text="$store.player.formatTime($store.player.duration)"></span>
        </div>

        {{-- Volume --}}
        <div class="hidden sm:flex items-center gap-2">
            <button type="button" @click="$store.player.toggleMute()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg x-show="$store.player.volume > 0" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072M17.95 6.05a8 8 0 010 11.9M6.5 8.788h-2a1 1 0 00-1 1v4.424a1 1 0 001 1h2l4.5 3.5V5.288l-4.5 3.5z"/></svg>
                <svg x-show="$store.player.volume === 0" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707A1 1 0 0112 5.586v12.828a1 1 0 01-1.707.707L5.586 15zM17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/></svg>
            </button>
            <input type="range" min="0" max="1" step="0.05"
                   :value="$store.player.volume"
                   @input="$store.player.setVolume($event.target.value)"
                   class="w-20 h-1 accent-blue-600 cursor-pointer">
        </div>

        {{-- Minimize --}}
        <button type="button" @click="$store.player.minimize()" title="Player einklappen" class="flex-shrink-0 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>

        {{-- Close --}}
        <button type="button" @click="$store.player.close()" title="Player schliessen" class="flex-shrink-0 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
</div>

{{-- Folded away: a small button in the corner, with the progress as a ring --}}
<div id="audio-player-mini" data-player-ui x-data x-cloak
     x-show="$store.player.active && $store.player.minimized"
     x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     class="fixed bottom-4 right-4 z-50 flex items-center gap-1 p-1 pr-1.5 rounded-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-lg"
     :title="$store.player.title + ($store.player.artist ? ' – ' + $store.player.artist : '')">
    <button type="button" @click="$store.player.toggle()"
            class="relative w-10 h-10 flex items-center justify-center rounded-full bg-blue-600 hover:bg-blue-700 text-white transition"
            :class="$store.player.waitingForClick && 'animate-pulse'"
            :aria-label="$store.player.playing ? 'Pause' : 'Abspielen'">
        {{-- Progress ring: circumference of r=18 is 113.1 --}}
        <svg class="absolute inset-0 w-10 h-10 -rotate-90 pointer-events-none" viewBox="0 0 40 40" aria-hidden="true">
            <circle cx="20" cy="20" r="18" fill="none" stroke="currentColor" stroke-opacity="0.25" stroke-width="2.5"/>
            <circle cx="20" cy="20" r="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                    stroke-dasharray="113.1" :stroke-dashoffset="113.1 * (1 - $store.player.progress / 100)"/>
        </svg>
        <svg x-show="!$store.player.playing" class="w-4 h-4 ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
        <svg x-show="$store.player.playing" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
    </button>
    <button type="button" @click="$store.player.expand()" title="Player ausklappen"
            class="w-8 h-8 flex items-center justify-center rounded-full text-gray-500 hover:text-gray-800 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-100 dark:hover:bg-gray-700">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
    </button>
</div>
