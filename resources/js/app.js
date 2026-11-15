import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

/*
 * Global audio player.
 *
 * Every click in the admin is a full page load (Turbo is not in use), which
 * tears the <audio> element down. So the player keeps its state in
 * sessionStorage - per tab - and picks up where it was on the next page: same
 * song, same position, still playing if it was playing. If the browser does
 * not allow sound to start on its own after a reload, it continues on the
 * first click or key press on the page.
 *
 * After a few seconds the bar folds into a small button in the corner so it
 * does not cover the page; a click there opens it again.
 *
 * A product's tracklist plays as a queue: one song after the other, with
 * previous/next, a list to jump around in, and the media keys of keyboard and
 * system. A single play button anywhere else replaces the queue with that song.
 */
const PLAYER_KEY = 'tyl_player';
const MINIMIZE_AFTER_MS = 5000;

const readPlayerState = () => {
    try {
        return JSON.parse(sessionStorage.getItem(PLAYER_KEY) || 'null');
    } catch (e) {
        return null;
    }
};

Alpine.store('player', {
    active: false,
    playing: false,
    minimized: false,
    // Sound was meant to keep going but the browser wants a click first.
    waitingForClick: false,
    title: '',
    artist: '',
    url: '',
    // The album playing as a whole: { id, title, items: [{ title, artist, url, number }] }; null for a single song
    queue: null,
    queueIndex: 0,
    queueOpen: false,
    currentTime: 0,
    duration: 0,
    volume: 0.8,
    _previousVolume: 0.8,
    _audio: null,
    _raf: null,
    _lastSave: 0,
    _leaving: false,
    _minimizeTimer: null,

    get progress() {
        return this.duration > 0 ? (this.currentTime / this.duration) * 100 : 0;
    },

    get queueLength() { return this.queue ? this.queue.items.length : 0; },
    get hasNext() { return this.queueIndex < this.queueLength - 1; },
    get hasPrevious() { return this.queueLength > 0 && this.queueIndex > 0; },

    _getAudio() {
        if (!this._audio) {
            this._audio = document.getElementById('global-audio');
            if (this._audio) {
                this._audio.addEventListener('ended', () => {
                    if (this.hasNext) { this.next(); return; }
                    this.playing = false;
                    this._save();
                });
                // A file that does not load must not stop the album
                this._audio.addEventListener('error', () => { if (this.url && this.hasNext) this.next(); });
                this._audio.addEventListener('loadedmetadata', () => { this.duration = this._audio.duration; });
                this._audio.addEventListener('play', () => { this.playing = true; this.waitingForClick = false; this._tick(); });
                this._audio.addEventListener('pause', () => {
                    // A page that is being left pauses its audio - that is not the user pressing pause.
                    if (this._leaving) return;
                    this.playing = false;
                    this._save();
                });
            }
        }
        return this._audio;
    },

    _tick() {
        const audio = this._getAudio();
        if (audio && !audio.paused) {
            this.currentTime = audio.currentTime;
            this.duration = audio.duration || 0;
            // The position has to be on record by the time the page goes away.
            if (performance.now() - this._lastSave > 1000) this._save();
            this._raf = requestAnimationFrame(() => this._tick());
        }
    },

    _save() {
        this._lastSave = performance.now();
        const audio = this._getAudio();
        try {
            sessionStorage.setItem(PLAYER_KEY, JSON.stringify({
                title: this.title,
                artist: this.artist,
                url: this.url,
                currentTime: audio ? audio.currentTime : this.currentTime,
                duration: this.duration,
                // Read from the element itself: the store's flag lags behind right after play().
                playing: audio ? !audio.paused && !audio.ended : this.playing,
                volume: this.volume,
                minimized: this.minimized,
                queue: this.queue,
                queueIndex: this.queueIndex,
            }));
        } catch (e) {
            // Private mode or full storage: the player still works, it just does not carry over.
        }
    },

    // Called when the page is left (reload, link, form): records the state as it is right now.
    leave() {
        if (!this.url) return;
        this._save();
        this._leaving = true;
    },

    restore() {
        const data = readPlayerState();
        if (!data || !data.url) return;

        const audio = this._getAudio();
        if (!audio) return;

        this._leaving = false;
        this.title = data.title || '';
        this.artist = data.artist || '';
        this.url = data.url;
        this.volume = data.volume ?? 0.8;
        this.currentTime = data.currentTime || 0;
        this.duration = data.duration || 0;
        this.minimized = !!data.minimized;
        this.queue = data.queue && Array.isArray(data.queue.items) ? data.queue : null;
        this.queueIndex = data.queueIndex || 0;
        this.active = true;
        this._updateMediaSession();

        if (audio.getAttribute('src') !== data.url) {
            audio.src = data.url;
        }
        audio.volume = this.volume;

        const seek = () => { if (data.currentTime) audio.currentTime = data.currentTime; };
        if (audio.readyState >= 1) seek(); else audio.addEventListener('loadedmetadata', seek, { once: true });

        if (data.playing) this._resume();
        if (!this.minimized) this.scheduleMinimize();
    },

    _resume() {
        const audio = this._getAudio();
        audio.play().catch(() => {
            // Autoplay blocked after the reload: continue on the first interaction with the page.
            this.waitingForClick = true;
            const resume = (event) => {
                // The player's own buttons decide for themselves (else play would start and stop again).
                if (event.target.closest && event.target.closest('[data-player-ui]')) return;
                document.removeEventListener('pointerdown', resume, true);
                document.removeEventListener('keydown', resume, true);
                if (this.waitingForClick && this.url) audio.play().catch(() => {});
            };
            document.addEventListener('pointerdown', resume, true);
            document.addEventListener('keydown', resume, true);
        });
    },

    // A single song: replaces whatever queue was playing.
    load(detail) {
        if (!this._getAudio()) return;
        this.queue = null;
        this.queueIndex = 0;
        this.queueOpen = false;
        this._start(detail);
        this.expand();
    },

    // An album from the given song on. The song that is already playing just pauses or continues.
    playQueue(queue, index = 0) {
        if (!this._getAudio() || !queue || !Array.isArray(queue.items) || !queue.items[index]) return;
        if (this.isCurrent(queue.id, index)) { this.toggle(); return; }
        this.queue = { id: queue.id, title: queue.title || '', items: queue.items };
        this.playAt(index);
        this.expand();
    },

    playAt(index) {
        if (!this.queue || !this.queue.items[index]) return;
        this.queueIndex = index;
        this._start(this.queue.items[index]);
    },

    next() {
        if (this.hasNext) this.playAt(this.queueIndex + 1);
    },

    // Back to the start of the song; at its very beginning, to the song before.
    previous() {
        const audio = this._getAudio();
        if (!audio) return;
        if (audio.currentTime > 3 || !this.hasPrevious) {
            audio.currentTime = 0;
            this.currentTime = 0;
            return;
        }
        this.playAt(this.queueIndex - 1);
    },

    // Is this album (and, given an index, this song of it) the one in the player?
    isCurrent(queueId, index) {
        return this.active && !!this.queue && this.queue.id === queueId && (index === undefined || this.queueIndex === index);
    },

    _start(item) {
        const audio = this._getAudio();
        this.title = item.title || '';
        this.artist = item.artist || '';
        this.url = item.url || '';
        this.currentTime = 0;
        this.duration = 0;
        this.active = true;
        this.waitingForClick = false;
        audio.src = item.url;
        audio.volume = this.volume;
        audio.play().catch(() => {});
        this._save();
        this._updateMediaSession();
    },

    // Title in the system's media controls; the media keys skip through the album.
    _updateMediaSession() {
        if (typeof navigator === 'undefined' || !('mediaSession' in navigator)) return;
        try {
            navigator.mediaSession.metadata = this.url && typeof MediaMetadata !== 'undefined'
                ? new MediaMetadata({ title: this.title, artist: this.artist, album: this.queue ? this.queue.title : '' })
                : null;
            navigator.mediaSession.setActionHandler('previoustrack', this.queueLength > 1 ? () => this.previous() : null);
            navigator.mediaSession.setActionHandler('nexttrack', this.queueLength > 1 ? () => this.next() : null);
        } catch (e) {
            // Older browsers without some of the actions: the player works without them.
        }
    },

    toggle() {
        const audio = this._getAudio();
        if (!audio) return;
        this.waitingForClick = false;
        if (this.playing) { audio.pause(); } else { audio.play().catch(() => {}); }
    },

    // --- Folding the bar away ------------------------------------------------

    expand() {
        this.minimized = false;
        this._save();
        this.scheduleMinimize();
    },

    minimize() {
        clearTimeout(this._minimizeTimer);
        this.minimized = true;
        this.queueOpen = false;
        this._save();
    },

    // (Re)starts the countdown after which the bar folds away.
    scheduleMinimize() {
        clearTimeout(this._minimizeTimer);
        if (!this.active || this.minimized) return;
        this._minimizeTimer = setTimeout(() => this.minimize(), MINIMIZE_AFTER_MS);
    },

    // While the mouse or keyboard focus is on the bar, it stays open.
    holdOpen() {
        clearTimeout(this._minimizeTimer);
    },

    seek(event) {
        const audio = this._getAudio();
        if (!audio || !this.duration) return;
        const rect = event.currentTarget.getBoundingClientRect();
        const pct = (event.clientX - rect.left) / rect.width;
        audio.currentTime = pct * this.duration;
    },

    setVolume(val) {
        this.volume = parseFloat(val);
        const audio = this._getAudio();
        if (audio) audio.volume = this.volume;
        this._save();
    },

    toggleMute() {
        if (this.volume > 0) {
            this._previousVolume = this.volume;
            this.volume = 0;
        } else {
            this.volume = this._previousVolume;
        }
        const audio = this._getAudio();
        if (audio) audio.volume = this.volume;
    },

    close() {
        clearTimeout(this._minimizeTimer);
        const audio = this._getAudio();
        if (audio) { audio.pause(); audio.removeAttribute('src'); audio.load(); }
        this.active = false;
        this.playing = false;
        this.minimized = false;
        this.waitingForClick = false;
        this.title = '';
        this.artist = '';
        this.url = '';
        this.queue = null;
        this.queueIndex = 0;
        this.queueOpen = false;
        this.currentTime = 0;
        this.duration = 0;
        this._updateMediaSession();
        try { sessionStorage.removeItem(PLAYER_KEY); } catch (e) {}
    },

    formatTime(seconds) {
        if (!seconds || isNaN(seconds)) return '0:00';
        const m = Math.floor(seconds / 60);
        const s = Math.floor(seconds % 60);
        return m + ':' + (s < 10 ? '0' : '') + s;
    }
});

Alpine.start();

// Pick the song up again after a page load ...
document.addEventListener('DOMContentLoaded', () => {
    Alpine.store('player').restore();
});

// ... and record exactly where it was when the page is left.
window.addEventListener('pagehide', () => Alpine.store('player').leave());

// Back/forward can bring a page back from the browser's cache with a stale
// player; the state the last page left behind is the current one.
window.addEventListener('pageshow', (event) => {
    if (event.persisted) Alpine.store('player').restore();
});
