{{-- Contract logo picker (Artwork logo OR direct upload) + placement toggles --}}
{{-- Usage: @include('admin.partials.contract-logo', ['contract' => $contract ?? null]) --}}

@php
    $logoContract = $contract ?? null;
    $hasLogo = $logoContract && $logoContract->logo_path;
    $currentLogoUrl = $hasLogo ? \Illuminate\Support\Facades\Storage::disk('public')->url($logoContract->logo_path) : null;
    $defaultSource = old('logo_source', $hasLogo ? 'keep' : 'none');
    $defaultHeader = old('logo_in_header', $logoContract?->logo_in_header) ? true : false;
    $defaultWatermark = old('logo_as_watermark', $logoContract?->logo_as_watermark) ? true : false;
@endphp

<div class="border-t border-gray-200 dark:border-gray-700 pt-6"
     x-data="contractLogo({
        source: '{{ $defaultSource }}',
        hasLogo: {{ $hasLogo ? 'true' : 'false' }},
        inHeader: {{ $defaultHeader ? 'true' : 'false' }},
        asWatermark: {{ $defaultWatermark ? 'true' : 'false' }},
     })">
    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Logo</p>
    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Wird im PDF oben rechts und/oder als Wasserzeichen eingefügt.</p>

    <input type="hidden" name="logo_source" :value="source">
    <input type="hidden" name="artwork_logo_id" :value="source === 'artwork' ? (selectedLogo?.id ?? '') : ''">

    {{-- Source selection --}}
    <div class="space-y-2 mb-4">
        @if($hasLogo)
        <label class="flex items-center gap-3 p-2.5 rounded-lg border border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50">
            <input type="radio" value="keep" x-model="source" class="text-blue-600 focus:ring-blue-500">
            <span class="flex items-center gap-3">
                <img src="{{ $currentLogoUrl }}" alt="Logo" class="h-8 w-auto max-w-[80px] object-contain bg-white rounded border border-gray-200">
                <span class="text-sm text-gray-700 dark:text-gray-300">Aktuelles Logo behalten</span>
            </span>
        </label>
        @endif

        <label class="flex items-center gap-3 p-2.5 rounded-lg border border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50">
            <input type="radio" value="none" x-model="source" class="text-blue-600 focus:ring-blue-500">
            <span class="text-sm text-gray-700 dark:text-gray-300">Kein Logo</span>
        </label>

        <label class="flex items-center gap-3 p-2.5 rounded-lg border border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50">
            <input type="radio" value="artwork" x-model="source" class="text-blue-600 focus:ring-blue-500">
            <span class="text-sm text-gray-700 dark:text-gray-300">Aus Logo / Artwork wählen</span>
        </label>

        <label class="flex items-center gap-3 p-2.5 rounded-lg border border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50">
            <input type="radio" value="upload" x-model="source" class="text-blue-600 focus:ring-blue-500">
            <span class="text-sm text-gray-700 dark:text-gray-300">Direkt hochladen</span>
        </label>
    </div>

    {{-- Artwork logo picker --}}
    <div x-show="source === 'artwork'" x-transition class="mb-4 ml-1">
        <div class="relative">
            <input type="text" x-model="query" @input.debounce.300ms="search()" @focus="search()"
                placeholder="Artwork suchen..."
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">

            <div x-show="open && results.length > 0" @click.away="open = false" x-cloak
                class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-72 overflow-y-auto">
                <template x-for="result in results" :key="result.id">
                    <button type="button" @click="selectLogo(result)"
                        class="w-full flex items-center gap-3 text-left px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700 last:border-0">
                        <img :src="result.url" alt="" class="h-8 w-auto max-w-[60px] object-contain bg-white rounded border border-gray-200">
                        <span class="text-sm text-gray-900 dark:text-gray-100" x-text="result.label"></span>
                    </button>
                </template>
            </div>
            <div x-show="open && results.length === 0 && !loading" x-cloak
                class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg p-3">
                <p class="text-sm text-gray-500 dark:text-gray-400">Keine Logos gefunden.</p>
            </div>
        </div>

        <div x-show="selectedLogo" x-cloak class="flex items-center gap-3 mt-3">
            <img :src="selectedLogo?.url" alt="" class="h-10 w-auto max-w-[100px] object-contain bg-white rounded border border-gray-200">
            <span class="text-sm text-gray-700 dark:text-gray-300" x-text="selectedLogo?.label"></span>
            <button type="button" @click="selectedLogo = null" class="text-red-400 hover:text-red-600 text-sm">Entfernen</button>
        </div>
    </div>

    {{-- Direct upload --}}
    <div x-show="source === 'upload'" x-transition class="mb-4 ml-1">
        <input type="file" name="logo_file" accept="image/*"
            class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 dark:file:bg-blue-900/50 file:text-blue-700 dark:file:text-blue-300 hover:file:bg-blue-100 dark:hover:file:bg-blue-900">
        <p class="text-xs text-gray-400 mt-1">PNG, JPG oder SVG. Empfohlen: transparenter Hintergrund.</p>
    </div>
    @error('logo_file') <p class="text-red-500 text-xs mb-2 ml-1">{{ $message }}</p> @enderror

    {{-- Placement toggles --}}
    <div x-show="source !== 'none'" x-transition class="space-y-2 ml-1">
        <label class="flex items-center gap-3 cursor-pointer">
            <input type="hidden" name="logo_in_header" value="0">
            <input type="checkbox" name="logo_in_header" value="1" x-model="inHeader" class="rounded text-blue-600 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600">
            <span class="text-sm text-gray-700 dark:text-gray-300">Oben rechts im PDF anzeigen</span>
        </label>
        <label class="flex items-center gap-3 cursor-pointer">
            <input type="hidden" name="logo_as_watermark" value="0">
            <input type="checkbox" name="logo_as_watermark" value="1" x-model="asWatermark" class="rounded text-blue-600 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600">
            <span class="text-sm text-gray-700 dark:text-gray-300">Als Wasserzeichen einfügen</span>
        </label>
    </div>
</div>

<script>
function contractLogo(init) {
    return {
        source: init.source,
        hasLogo: init.hasLogo,
        inHeader: init.inHeader,
        asWatermark: init.asWatermark,
        query: '',
        results: [],
        open: false,
        loading: false,
        selectedLogo: null,

        async search() {
            this.loading = true;
            this.open = true;
            try {
                const params = new URLSearchParams({ q: this.query });
                const res = await fetch(`{{ route('admin.contracts.logo-search') }}?${params}`);
                this.results = await res.json();
            } catch (e) {
                this.results = [];
            }
            this.loading = false;
        },

        selectLogo(logo) {
            this.selectedLogo = logo;
            this.query = '';
            this.results = [];
            this.open = false;
        },
    };
}
</script>