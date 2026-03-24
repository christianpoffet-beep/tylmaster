{{-- Image source picker: Upload, Photos, or Artworks --}}
<div x-data="imagePicker()" class="space-y-3">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bild</label>

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
        @if(isset($contentPost) && $contentPost->image)
            <div class="mb-3">
                <img src="{{ asset('storage/' . $contentPost->image) }}" alt="" class="w-32 h-32 rounded-lg object-cover">
            </div>
        @endif
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp"
               @change="clearSelection()"
               class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 dark:file:bg-blue-900/50 file:text-blue-700 dark:file:text-blue-300 hover:file:bg-blue-100 dark:hover:file:bg-blue-900">
        <p class="text-xs text-gray-400 mt-1">JPG, PNG oder WebP, max. 10 MB</p>
        @error('image') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Photos tab --}}
    <div x-show="activeTab === 'photos'" x-cloak>
        {{-- Breadcrumb --}}
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

        {{-- Subfolders --}}
        <div class="grid grid-cols-4 sm:grid-cols-6 gap-2 mb-3" x-show="!loading && subfolders.length">
            <template x-for="folder in subfolders" :key="folder.id">
                <button type="button" @click="loadPhotos(folder.id)"
                        class="flex flex-col items-center gap-1 p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-center">
                    <svg class="w-8 h-8 text-yellow-500" fill="currentColor" viewBox="0 0 24 24"><path d="M10 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/></svg>
                    <span class="text-xs text-gray-600 dark:text-gray-400 truncate w-full" x-text="folder.name"></span>
                </button>
            </template>
        </div>

        {{-- Photo grid --}}
        <div class="grid grid-cols-4 sm:grid-cols-6 gap-2" x-show="!loading && photoItems.length">
            <template x-for="item in photoItems" :key="item.id">
                <button type="button" @click="selectImage(item.type, item.id, item.thumb, item.title)"
                        :class="selectedType === item.type && selectedId == item.id ? 'ring-2 ring-blue-500 ring-offset-2 dark:ring-offset-gray-800' : ''"
                        class="relative rounded-lg overflow-hidden aspect-square bg-gray-100 dark:bg-gray-700 hover:opacity-80 transition-opacity">
                    <img :src="item.thumb" :alt="item.title" class="w-full h-full object-cover">
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
                        {{-- Main artwork --}}
                        <button type="button" @click="selectImage('artwork', artwork.id, artwork.thumb, artwork.title)"
                                :class="selectedType === 'artwork' && selectedId == artwork.id ? 'ring-2 ring-blue-500 ring-offset-2 dark:ring-offset-gray-800' : ''"
                                class="relative rounded-lg overflow-hidden aspect-square bg-gray-100 dark:bg-gray-700 hover:opacity-80 transition-opacity">
                            <img :src="artwork.thumb" :alt="artwork.title" class="w-full h-full object-cover">
                            <span class="absolute bottom-0 left-0 right-0 bg-black/60 text-white text-[10px] px-1 py-0.5 text-center">Cover</span>
                        </button>
                        {{-- Logos --}}
                        <template x-for="logo in artwork.logos" :key="logo.id">
                            <button type="button" @click="selectImage('artwork_logo', logo.id, logo.thumb, logo.title)"
                                    :class="selectedType === 'artwork_logo' && selectedId == logo.id ? 'ring-2 ring-blue-500 ring-offset-2 dark:ring-offset-gray-800' : ''"
                                    class="relative rounded-lg overflow-hidden aspect-square bg-gray-100 dark:bg-gray-700 hover:opacity-80 transition-opacity">
                                <img :src="logo.thumb" :alt="logo.title" class="w-full h-full object-contain p-1">
                                <span class="absolute bottom-0 left-0 right-0 bg-black/60 text-white text-[10px] px-1 py-0.5 text-center">Logo</span>
                            </button>
                        </template>
                    </div>
                </div>
            </template>
            <p class="text-sm text-gray-400 py-4 text-center" x-show="!artworkItems.length">Keine Artworks vorhanden.</p>
        </div>
    </div>

    {{-- Selected image info --}}
    <div x-show="selectedType" x-cloak class="flex items-center gap-3 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
        <img :src="selectedThumb" class="w-12 h-12 rounded object-cover">
        <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-blue-700 dark:text-blue-300 truncate" x-text="selectedTitle"></p>
            <p class="text-xs text-blue-500 dark:text-blue-400">Ausgewählt als Bild-Quelle</p>
        </div>
        <button type="button" @click="clearSelection()" class="text-blue-400 hover:text-blue-600 dark:hover:text-blue-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    {{-- Hidden fields --}}
    <input type="hidden" name="image_source_type" :value="selectedType">
    <input type="hidden" name="image_source_id" :value="selectedId">
</div>

@push('scripts')
<script>
function imagePicker() {
    return {
        activeTab: '{{ (isset($contentPost) && $contentPost->image_source_type) ? ($contentPost->image_source_type === "artwork_logo" || $contentPost->image_source_type === "artwork" ? "artworks" : "photos") : "upload" }}',
        loading: false,
        currentFolder: null,
        subfolders: [],
        photoItems: [],
        artworkItems: [],
        selectedType: '{{ isset($contentPost) ? ($contentPost->image_source_type ?? "") : "" }}',
        selectedId: '{{ isset($contentPost) ? ($contentPost->image_source_id ?? "") : "" }}',
        selectedThumb: '{{ isset($contentPost) && $contentPost->image_source_type ? $contentPost->effective_image_url : "" }}',
        selectedTitle: '{{ isset($contentPost) ? ($contentPost->image_source_label ?? "") : "" }}',

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
            } catch (e) {
                console.error(e);
            }
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
            } catch (e) {
                console.error(e);
            }
            this.loading = false;
        },

        selectImage(type, id, thumb, title) {
            this.selectedType = type;
            this.selectedId = id;
            this.selectedThumb = thumb;
            this.selectedTitle = title;
        },

        clearSelection() {
            this.selectedType = '';
            this.selectedId = '';
            this.selectedThumb = '';
            this.selectedTitle = '';
        }
    };
}
</script>
@endpush
