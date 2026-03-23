{{-- Persistent Audio Player --}}
<div x-data="audioPlayer()" x-show="track.title" x-transition x-cloak
     class="fixed bottom-0 left-0 right-0 z-50 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 shadow-lg"
     @play-track.window="loadTrack($event.detail)">

    <audio x-ref="audio"
           @timeupdate="currentTime = $refs.audio.currentTime; duration = $refs.audio.duration || 0"
           @ended="playing = false"
           @loadedmetadata="duration = $refs.audio.duration"
           @play="playing = true"
           @pause="playing = false">
    </audio>

    {{-- Progress bar (clickable) --}}
    <div class="h-1 bg-gray-200 dark:bg-gray-700 cursor-pointer group" @click="seek($event)">
        <div class="h-full bg-blue-600 transition-all duration-150 group-hover:bg-blue-500"
             :style="'width: ' + progress + '%'"></div>
    </div>

    <div class="flex items-center gap-4 px-4 py-2 max-w-screen-xl mx-auto">
        {{-- Play/Pause --}}
        <button @click="toggle()" class="flex-shrink-0 w-10 h-10 flex items-center justify-center rounded-full bg-blue-600 hover:bg-blue-700 text-white transition">
            <svg x-show="!playing" class="w-5 h-5 ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
            <svg x-show="playing" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>
        </button>

        {{-- Track info --}}
        <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate" x-text="track.title"></p>
            <p class="text-xs text-gray-500 dark:text-gray-400 truncate" x-text="track.artist || ''"></p>
        </div>

        {{-- Time --}}
        <div class="flex-shrink-0 text-xs text-gray-500 dark:text-gray-400 tabular-nums">
            <span x-text="formatTime(currentTime)"></span>
            <span>/</span>
            <span x-text="formatTime(duration)"></span>
        </div>

        {{-- Volume --}}
        <div class="hidden sm:flex items-center gap-2">
            <button @click="toggleMute()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg x-show="volume > 0" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072M17.95 6.05a8 8 0 010 11.9M6.5 8.788h-2a1 1 0 00-1 1v4.424a1 1 0 001 1h2l4.5 3.5V5.288l-4.5 3.5z"/></svg>
                <svg x-show="volume === 0" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707A1 1 0 0112 5.586v12.828a1 1 0 01-1.707.707L5.586 15zM17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/></svg>
            </button>
            <input type="range" min="0" max="1" step="0.05" x-model="volume" @input="$refs.audio.volume = volume"
                   class="w-20 h-1 accent-blue-600 cursor-pointer">
        </div>

        {{-- Close --}}
        <button @click="close()" class="flex-shrink-0 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
</div>

<script>
function audioPlayer() {
    return {
        track: { title: '', artist: '', url: '' },
        playing: false,
        currentTime: 0,
        duration: 0,
        volume: 0.8,
        previousVolume: 0.8,

        get progress() {
            return this.duration > 0 ? (this.currentTime / this.duration) * 100 : 0;
        },

        loadTrack(detail) {
            this.track = { title: detail.title || '', artist: detail.artist || '', url: detail.url || '' };
            this.$refs.audio.src = detail.url;
            this.$refs.audio.volume = this.volume;
            this.$refs.audio.play();
        },

        toggle() {
            if (this.playing) {
                this.$refs.audio.pause();
            } else {
                this.$refs.audio.play();
            }
        },

        seek(event) {
            const rect = event.currentTarget.getBoundingClientRect();
            const pct = (event.clientX - rect.left) / rect.width;
            this.$refs.audio.currentTime = pct * this.duration;
        },

        toggleMute() {
            if (this.volume > 0) {
                this.previousVolume = this.volume;
                this.volume = 0;
            } else {
                this.volume = this.previousVolume;
            }
            this.$refs.audio.volume = this.volume;
        },

        close() {
            this.$refs.audio.pause();
            this.track = { title: '', artist: '', url: '' };
            this.playing = false;
            this.currentTime = 0;
            this.duration = 0;
        },

        formatTime(seconds) {
            if (!seconds || isNaN(seconds)) return '0:00';
            const m = Math.floor(seconds / 60);
            const s = Math.floor(seconds % 60);
            return m + ':' + (s < 10 ? '0' : '') + s;
        }
    };
}
</script>
