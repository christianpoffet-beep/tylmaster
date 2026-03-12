{{-- Document Viewer Modal --}}
<div x-data="documentViewer()" x-cloak
     @open-doc-viewer.window="open($event.detail)"
     @keydown.escape.window="close()"
     x-show="isOpen"
     class="fixed inset-0 z-50">

    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-black/70" @click="close()"></div>

    {{-- Modal: fixed mit Abstand zum Rand --}}
    <div class="fixed bg-white dark:bg-gray-800 rounded-xl shadow-2xl overflow-hidden"
         style="top: 24px; right: 24px; bottom: 24px; left: 24px;"
         @click.stop>

        {{-- Header --}}
        <div style="position: absolute; top: 0; left: 0; right: 0; height: 48px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between; padding: 0 20px; background: white; border-radius: 12px 12px 0 0; z-index: 10;">
            <h3 style="font-size: 14px; font-weight: 600; color: #1f2937; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; padding-right: 16px;" x-text="title"></h3>
            <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                <a :href="downloadUrl" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; font-size: 12px; font-weight: 500; color: #374151; background: #f3f4f6; border-radius: 8px; text-decoration: none;"
                   onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'">
                    <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Download
                </a>
                <button @click="close()" style="padding: 6px; color: #9ca3af; border-radius: 8px; border: none; background: none; cursor: pointer;"
                        onmouseover="this.style.color='#4b5563';this.style.background='#f3f4f6'" onmouseout="this.style.color='#9ca3af';this.style.background='none'">
                    <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        {{-- Content --}}
        <div style="position: absolute; top: 48px; right: 0; bottom: 0; left: 0; background: #f3f4f6; border-radius: 0 0 12px 12px; overflow: hidden;">

            {{-- PDF --}}
            <iframe x-show="type === 'pdf'" :src="type === 'pdf' ? url : ''"
                    style="width: 100%; height: 100%; border: 0; display: none;"></iframe>

            {{-- Image --}}
            <div x-show="type === 'image'"
                 style="width: 100%; height: 100%; display: none; align-items: center; justify-content: center; padding: 16px; overflow: auto;"
                 x-bind:style="type === 'image' ? 'width:100%;height:100%;display:flex;align-items:center;justify-content:center;padding:16px;overflow:auto' : 'display:none'">
                <img :src="type === 'image' ? url : ''" :alt="title"
                     style="max-width: 100%; max-height: 100%; object-fit: contain; border-radius: 8px;">
            </div>

            {{-- Audio --}}
            <div x-show="type === 'audio'"
                 style="width: 100%; height: 100%; display: none; flex-direction: column; align-items: center; justify-content: center; padding: 32px; gap: 24px;"
                 x-bind:style="type === 'audio' ? 'width:100%;height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:32px;gap:24px' : 'display:none'">
                <div style="width: 112px; height: 112px; border-radius: 50%; background: #e5e7eb; display: flex; align-items: center; justify-content: center;">
                    <svg style="width: 56px; height: 56px; color: #6b7280;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
                </div>
                <p style="font-size: 14px; font-weight: 500; color: #374151;" x-text="title"></p>
                <audio x-ref="audioPlayer" controls style="width: 100%; max-width: 512px;"></audio>
            </div>

            {{-- Video --}}
            <div x-show="type === 'video'"
                 style="width: 100%; height: 100%; display: none; align-items: center; justify-content: center; padding: 16px;"
                 x-bind:style="type === 'video' ? 'width:100%;height:100%;display:flex;align-items:center;justify-content:center;padding:16px' : 'display:none'">
                <video x-ref="videoPlayer" controls style="max-width: 100%; max-height: 100%; border-radius: 8px;"></video>
            </div>

            {{-- Unsupported --}}
            <div x-show="type === 'other'"
                 style="width: 100%; height: 100%; display: none; flex-direction: column; align-items: center; justify-content: center; padding: 32px; gap: 16px;"
                 x-bind:style="type === 'other' ? 'width:100%;height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:32px;gap:16px' : 'display:none'">
                <div style="width: 80px; height: 80px; border-radius: 12px; background: #e5e7eb; display: flex; align-items: center; justify-content: center;">
                    <svg style="width: 40px; height: 40px; color: #9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                </div>
                <p style="font-size: 14px; color: #4b5563;">Vorschau für diesen Dateityp nicht verfügbar.</p>
                <a :href="downloadUrl" style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; font-size: 14px; font-weight: 500; color: white; background: #2563eb; border-radius: 8px; text-decoration: none;">
                    <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Datei herunterladen
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function documentViewer() {
    return {
        isOpen: false,
        url: '',
        title: '',
        type: 'other',
        downloadUrl: '',

        open(detail) {
            this.url = detail.url;
            this.title = detail.title || 'Dokument';
            this.downloadUrl = detail.downloadUrl || detail.url;
            this.type = this.detectType(detail.mime || '');
            this.isOpen = true;
            document.body.classList.add('overflow-hidden');

            this.$nextTick(() => {
                if (this.type === 'audio' && this.$refs.audioPlayer) {
                    this.$refs.audioPlayer.src = this.url;
                }
                if (this.type === 'video' && this.$refs.videoPlayer) {
                    this.$refs.videoPlayer.src = this.url;
                }
            });
        },

        close() {
            this.isOpen = false;
            document.body.classList.remove('overflow-hidden');
            if (this.$refs.audioPlayer) { this.$refs.audioPlayer.pause(); this.$refs.audioPlayer.src = ''; }
            if (this.$refs.videoPlayer) { this.$refs.videoPlayer.pause(); this.$refs.videoPlayer.src = ''; }
            this.url = '';
        },

        detectType(mime) {
            if (mime === 'application/pdf') return 'pdf';
            if (mime.startsWith('image/')) return 'image';
            if (mime.startsWith('audio/')) return 'audio';
            if (mime.startsWith('video/')) return 'video';
            return 'other';
        }
    };
}
</script>
