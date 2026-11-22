{{-- Search for a track (or a product) inside a royaltyPicker() component --}}
<div x-show="open" x-cloak class="mt-3 max-w-xl">
    <input type="text" x-model="query" @input="search()" x-ref="search" @keydown.escape="open = false"
           :placeholder="@js($picker['kind'] === 'track' ? 'Titel, Band oder ISRC' : 'Titel des Produkts')"
           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
    <div class="mt-1 max-h-64 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700">
        <p x-show="loading" class="px-3 py-2 text-xs text-gray-400">Suche …</p>
        <p x-show="!loading && results.length === 0" class="px-3 py-2 text-xs text-gray-400">Nichts gefunden.</p>
        <template x-for="item in results" :key="item.id">
            <button type="button" @click="choose(item.id)" class="w-full text-left px-3 py-2 hover:bg-blue-50 dark:hover:bg-gray-700">
                <span class="block text-sm text-gray-900 dark:text-gray-100" x-text="item.title"></span>
                <span class="block text-xs text-gray-500 dark:text-gray-400"
                      x-text="[item.artist, item.isrc, item.products].filter(Boolean).join(' · ')"></span>
            </button>
        </template>
    </div>
</div>
