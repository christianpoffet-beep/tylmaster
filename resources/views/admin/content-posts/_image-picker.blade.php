{{-- Multi-image picker: Upload, Photos, or Artworks --}}
<div x-data="multiImagePicker()" class="space-y-3">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
        Bilder <span class="text-gray-400 font-normal">({{ isset($contentPost) ? $contentPost->images->count() : 0 }} vorhanden)</span>
    </label>

    {{-- Current images (sortable) --}}
    <div x-show="allImages.length" x-cloak class="space-y-2">
        <p class="text-xs text-gray-500 dark:text-gray-400">Reihenfolge per Drag & Drop ändern. Klick auf &times; zum Entfernen.</p>
        <div class="flex flex-wrap gap-2" x-ref="sortable">
            <template x-for="(img, index) in allImages" :key="img.key">
                <div :data-key="img.key" class="relative group w-20 h-20 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700 cursor-move border-2 border-transparent hover:border-blue-400 transition-colors">
                    <img :src="img.thumb" class="w-full h-full object-cover" :alt="img.title">
                    <button type="button" @click="removeImage(img.key)"
                            class="absolute top-0 right-0 bg-red-600 text-white w-5 h-5 flex items-center justify-center text-xs rounded-bl-lg opacity-0 group-hover:opacity-100 transition-opacity">&times;</button>
                    <span class="absolute bottom-0 left-0 right-0 bg-black/60 text-white text-[9px] px-1 py-0.5 text-center truncate" x-text="img.badge"></span>
                </div>
            </template>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="flex border-b border-gray-200 dark:border-gray-700 gap-1">
        <button type="button" @click="activeTab = 'upload'" :class="activeTab === 'upload' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors">
            Hochladen
        </button>
        <button type="button" @click="activeTab = 'photos'; loadPhotos()" :class="activeTab === 'photos' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors">
            Fotos / Bilder
        </button>
        <button type="button" @click="activeTab = 'artworks'; loadArtworks()" :class="activeTab === 'artworks' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors">
            Logo & Artwork
        </button>
    </div>

    {{-- Upload tab --}}
    <div x-show="activeTab === 'upload'" x-cloak>
        <input type="file" name="uploaded_images[]" multiple accept="image/jpeg,image/png,image/webp"
               class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 dark:file:bg-blue-900/50 file:text-blue-700 dark:file:text-blue-300 hover:file:bg-blue-100 dark:hover:file:bg-blue-900">
        <p class="text-xs text-gray-400 mt-1">JPG, PNG oder WebP, max. 10 MB pro Bild. Mehrfachauswahl möglich.</p>
        @error('uploaded_images.*') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Photos tab --}}
    <div x-show="activeTab === 'photos'" x-cloak>
        <div class="flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400 mb-3" x-show="currentFolder">
            <button type="button" @click="loadPhotos()" class="hover:text-blue-600 dark:hover:text-blue-400">Alle Ordner</button>
            <template x-if="currentFolder">
                <span class="flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <span x-text="currentFolder.name" class="text-gray-700 dark:text-gray-300"></span>
                </span>
            </template>
        </div>

        <div class="flex items-center justify-center py-4" x-show="loading">
            <svg class="animate-spin h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
        </div>

        <div class="grid grid-cols-4 sm:grid-cols-6 gap-2 mb-3" x-show="!loading && subfolders.length">
            <template x-for="folder in subfolders" :key="folder.id">
                <button type="button" @click="loadPhotos(folder.id)"
                        class="flex flex-col items-center gap-1 p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-center">
                    <svg class="w-8 h-8 text-yellow-500" fill="currentColor" viewBox="0 0 24 24"><path d="M10 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/></svg>
                    <span class="text-xs text-gray-600 dark:text-gray-400 truncate w-full" x-text="folder.name"></span>
                </button>
            </template>
        </div>

        <div class="grid grid-cols-4 sm:grid-cols-6 gap-2" x-show="!loading && photoItems.length">
            <template x-for="item in photoItems" :key="item.id">
                <button type="button" @click="addImage(item.type, item.id, item.thumb, item.title)"
                        :class="isSelected(item.type, item.id) ? 'ring-2 ring-green-500 ring-offset-2 dark:ring-offset-gray-800' : ''"
                        class="relative rounded-lg overflow-hidden aspect-square bg-gray-100 dark:bg-gray-700 hover:opacity-80 transition-opacity">
                    <img :src="item.thumb" :alt="item.title" class="w-full h-full object-cover">
                    <div x-show="isSelected(item.type, item.id)" class="absolute top-1 right-1 bg-green-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs">&#10003;</div>
                </button>
            </template>
        </div>

        <p class="text-sm text-gray-400 py-4 text-center" x-show="!loading && !subfolders.length && !photoItems.length">Keine Fotos vorhanden.</p>
    </div>

    {{-- Artworks tab --}}
    <div x-show="activeTab === 'artworks'" x-cloak>
        <div class="flex items-center justify-center py-4" x-show="loading">
            <svg class="animate-spin h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
        </div>

        <div class="space-y-4" x-show="!loading">
            <template x-for="artwork in artworkItems" :key="artwork.id">
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" x-text="artwork.title"></h4>
                    <div class="grid grid-cols-4 sm:grid-cols-6 gap-2">
                        <button type="button" @click="addImage('artwork', artwork.id, artwork.thumb, artwork.title)"
                                :class="isSelected('artwork', artwork.id) ? 'ring-2 ring-green-500 ring-offset-2 dark:ring-offset-gray-800' : ''"
                                class="relative rounded-lg overflow-hidden aspect-square bg-gray-100 dark:bg-gray-700 hover:opacity-80 transition-opacity">
                            <img :src="artwork.thumb" :alt="artwork.title" class="w-full h-full object-cover">
                            <span class="absolute bottom-0 left-0 right-0 bg-black/60 text-white text-[10px] px-1 py-0.5 text-center">Cover</span>
                            <div x-show="isSelected('artwork', artwork.id)" class="absolute top-1 right-1 bg-green-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs">&#10003;</div>
                        </button>
                        <template x-for="logo in artwork.logos" :key="logo.id">
                            <button type="button" @click="addImage('artwork_logo', logo.id, logo.thumb, logo.title)"
                                    :class="isSelected('artwork_logo', logo.id) ? 'ring-2 ring-green-500 ring-offset-2 dark:ring-offset-gray-800' : ''"
                                    class="relative rounded-lg overflow-hidden aspect-square bg-gray-100 dark:bg-gray-700 hover:opacity-80 transition-opacity">
                                <img :src="logo.thumb" :alt="logo.title" class="w-full h-full object-contain p-1">
                                <span class="absolute bottom-0 left-0 right-0 bg-black/60 text-white text-[10px] px-1 py-0.5 text-center">Logo</span>
                                <div x-show="isSelected('artwork_logo', logo.id)" class="absolute top-1 right-1 bg-green-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs">&#10003;</div>
                            </button>
                        </template>
                    </div>
                </div>
            </template>
            <p class="text-sm text-gray-400 py-4 text-center" x-show="!artworkItems.length">Keine Artworks vorhanden.</p>
        </div>
    </div>

    {{-- Hidden fields for form submission --}}
    <input type="hidden" name="keep_images" :value="JSON.stringify(keepImagesData())">
    <input type="hidden" name="selected_images" :value="JSON.stringify(newSelectionsData())">
</div>

@push('scripts')
<script>
function multiImagePicker() {
    return {
        activeTab: 'upload',
        loading: false,
        currentFolder: null,
        subfolders: [],
        photoItems: [],
        artworkItems: [],

        // All images currently attached (existing + newly selected from library)
        allImages: {!! json_encode(
            isset($contentPost)
                ? $contentPost->images->map(fn ($img) => [
                    'key' => 'existing-' . $img->id,
                    'existingId' => $img->id,
                    'type' => $img->source_type,
                    'sourceId' => $img->source_id,
                    'thumb' => $img->url,
                    'title' => $img->label,
                    'badge' => ucfirst($img->source_type),
                    'isNew' => false,
                ])->values()
                : []
        ) !!},

        _keyCounter: 0,

        addImage(type, id, thumb, title) {
            // Don't add duplicates
            if (this.isSelected(type, id)) return;

            this.allImages.push({
                key: 'new-' + (++this._keyCounter),
                existingId: null,
                type: type,
                sourceId: id,
                thumb: thumb,
                title: title,
                badge: type === 'photo' ? 'Foto' : type === 'artwork' ? 'Artwork' : 'Logo',
                isNew: true,
            });
        },

        removeImage(key) {
            this.allImages = this.allImages.filter(img => img.key !== key);
        },

        isSelected(type, id) {
            return this.allImages.some(img => img.type === type && img.sourceId == id);
        },

        keepImagesData() {
            return this.allImages
                .filter(img => !img.isNew)
                .map((img, i) => ({ id: img.existingId, sort_order: i }));
        },

        newSelectionsData() {
            return this.allImages
                .filter(img => img.isNew)
                .map(img => ({ type: img.type, id: img.sourceId, thumb: img.thumb, title: img.title }));
        },

        async loadPhotos(folderId = null) {
            this.loading = true;
            try {
                const url = new URL('{{ route("admin.content-posts.image-browser") }}', window.location.origin);
                url.searchParams.set('tab', 'photos');
                if (folderId) url.searchParams.set('folder_id', folderId);
                const resp = await fetch(url);
                const data = await resp.json();
                this.currentFolder = data.folder;
                this.subfolders = data.subfolders || [];
                this.photoItems = data.items || [];
            } catch (e) { console.error(e); }
            this.loading = false;
        },

        async loadArtworks() {
            if (this.artworkItems.length) return;
            this.loading = true;
            try {
                const url = new URL('{{ route("admin.content-posts.image-browser") }}', window.location.origin);
                url.searchParams.set('tab', 'artworks');
                const resp = await fetch(url);
                const data = await resp.json();
                this.artworkItems = data.items || [];
            } catch (e) { console.error(e); }
            this.loading = false;
        },

        initSortable() {
            if (!this.$refs.sortable) return;
            const el = this.$refs.sortable;
            let dragEl = null;

            el.addEventListener('dragstart', (e) => {
                dragEl = e.target.closest('[data-key]');
                if (dragEl) {
                    dragEl.style.opacity = '0.5';
                    e.dataTransfer.effectAllowed = 'move';
                }
            });

            el.addEventListener('dragend', (e) => {
                if (dragEl) dragEl.style.opacity = '1';
                dragEl = null;
            });

            el.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
            });

            el.addEventListener('drop', (e) => {
                e.preventDefault();
                const target = e.target.closest('[data-key]');
                if (!target || !dragEl || target === dragEl) return;

                const fromKey = dragEl.dataset.key;
                const toKey = target.dataset.key;
                const fromIdx = this.allImages.findIndex(i => i.key === fromKey);
                const toIdx = this.allImages.findIndex(i => i.key === toKey);

                if (fromIdx === -1 || toIdx === -1) return;
                const [moved] = this.allImages.splice(fromIdx, 1);
                this.allImages.splice(toIdx, 0, moved);
            });

            // Make items draggable
            new MutationObserver(() => {
                el.querySelectorAll('[data-key]').forEach(item => {
                    item.draggable = true;
                });
            }).observe(el, { childList: true, subtree: true });
            el.querySelectorAll('[data-key]').forEach(item => item.draggable = true);
        },

        init() {
            this.$nextTick(() => this.initSortable());
        }
    };
}
</script>
@endpush
