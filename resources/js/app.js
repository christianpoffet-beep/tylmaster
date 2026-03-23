import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Global audio player store (persists across page loads via sessionStorage)
Alpine.store('player', {
    active: false,
    playing: false,
    title: '',
    artist: '',
    url: '',
    currentTime: 0,
    duration: 0,
    volume: 0.8,
    _previousVolume: 0.8,
    _audio: null,
    _raf: null,

    get progress() {
        return this.duration > 0 ? (this.currentTime / this.duration) * 100 : 0;
    },

    _getAudio() {
        if (!this._audio) {
            this._audio = document.getElementById('global-audio');
            if (this._audio) {
                this._audio.addEventListener('ended', () => { this.playing = false; this._save(); });
                this._audio.addEventListener('loadedmetadata', () => { this.duration = this._audio.duration; });
                this._audio.addEventListener('play', () => { this.playing = true; this._tick(); });
                this._audio.addEventListener('pause', () => { this.playing = false; this._save(); });
            }
        }
        return this._audio;
    },

    _tick() {
        const audio = this._getAudio();
        if (audio && !audio.paused) {
            this.currentTime = audio.currentTime;
            this.duration = audio.duration || 0;
            this._raf = requestAnimationFrame(() => this._tick());
        }
    },

    _save() {
        sessionStorage.setItem('tyl_player', JSON.stringify({
            title: this.title,
            artist: this.artist,
            url: this.url,
            currentTime: this.currentTime,
            playing: this.playing,
            volume: this.volume,
        }));
    },

    restore() {
        const raw = sessionStorage.getItem('tyl_player');
        if (!raw) return;
        const data = JSON.parse(raw);
        if (!data.url) return;

        const audio = this._getAudio();
        if (!audio) return;

        this.title = data.title || '';
        this.artist = data.artist || '';
        this.url = data.url;
        this.volume = data.volume ?? 0.8;
        this.active = true;

        audio.src = data.url;
        audio.volume = this.volume;
        audio.currentTime = data.currentTime || 0;

        if (data.playing) {
            audio.play().catch(() => {});
        }
    },

    load(detail) {
        const audio = this._getAudio();
        if (!audio) return;
        this.title = detail.title || '';
        this.artist = detail.artist || '';
        this.url = detail.url || '';
        this.active = true;
        audio.src = detail.url;
        audio.volume = this.volume;
        audio.play();
        this._save();
    },

    toggle() {
        const audio = this._getAudio();
        if (!audio) return;
        if (this.playing) { audio.pause(); } else { audio.play(); }
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
        const audio = this._getAudio();
        if (audio) { audio.pause(); audio.src = ''; }
        this.active = false;
        this.playing = false;
        this.title = '';
        this.artist = '';
        this.url = '';
        this.currentTime = 0;
        this.duration = 0;
        sessionStorage.removeItem('tyl_player');
    },

    formatTime(seconds) {
        if (!seconds || isNaN(seconds)) return '0:00';
        const m = Math.floor(seconds / 60);
        const s = Math.floor(seconds % 60);
        return m + ':' + (s < 10 ? '0' : '') + s;
    }
});

Alpine.start();

// Restore player state after page load
document.addEventListener('DOMContentLoaded', () => {
    Alpine.store('player').restore();
});
