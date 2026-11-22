{{--
    A small (i) that opens an explanation on click (and closes on a click elsewhere).
    Usage: <x-admin.info-tip>Was diese Einstellung bewirkt …</x-admin.info-tip>
--}}
<span class="relative inline-flex align-middle" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
    <button type="button" @click="open = !open" :aria-expanded="open.toString()" aria-label="Erklärung"
            class="inline-flex items-center justify-center w-4 h-4 rounded-full text-[10px] font-bold leading-none border border-gray-400 text-gray-500 hover:border-blue-500 hover:text-blue-600 dark:border-gray-500 dark:text-gray-400 dark:hover:border-blue-400 dark:hover:text-blue-400">i</button>
    <span x-show="open" x-cloak x-transition.opacity
          class="absolute z-30 left-1/2 -translate-x-1/2 top-6 w-72 max-w-[80vw] rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 shadow-lg p-3 text-xs font-normal normal-case text-gray-700 dark:text-gray-200 text-left">
        {{ $slot }}
    </span>
</span>
