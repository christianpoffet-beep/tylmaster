{{--
    Audio versions of a track, for the create and the edit form.
    $track: the track being edited, or null on create.

    Existing versions are rendered by Blade, so their fields always submit and a
    save without Alpine leaves them as they are. Only the delete flag is filled
    in by Alpine - without it nothing gets removed.
--}}
@php
    $existingAudioFiles = $track?->audioFiles ?? collect();
    $currentMain = $existingAudioFiles->firstWhere('is_main', true);

    // New rows. After a failed validation the typed values come back; the files themselves cannot.
    $newAudioRows = collect(old('audio_files', $existingAudioFiles->isEmpty() ? ['n0' => []] : []))
        ->map(fn ($row, $key) => [
            'key' => (string) $key,
            'title' => $row['title'] ?? '',
            'isrc' => $row['isrc'] ?? '',
            'notes' => $row['notes'] ?? '',
        ])
        ->values();

    $defaultMain = $currentMain
        ? 'existing:' . $currentMain->id
        : ($newAudioRows->isNotEmpty() ? 'new:' . $newAudioRows->first()['key'] : '');

    $removedAudioIds = collect(old('existing_audio_files', []))
        ->filter(fn ($row) => !empty($row['delete']))
        ->keys()->map(fn ($id) => (int) $id)->values();

    $hasAudioErrors = collect($errors->keys())->contains(fn ($key) => str_starts_with($key, 'audio_files') || str_starts_with($key, 'existing_audio_files') || $key === 'main_audio_file');

    // Everything picked goes up in one request. Past post_max_size PHP drops the
    // whole request (it ends in a 419), so the browser checks the sum first.
    $maxPostBytes = (function (string $value): int {
        $number = (float) $value;
        return (int) match (strtolower(substr(trim($value), -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    })((string) ini_get('post_max_size'));

    $audioInputClass ='w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500';
    $audioLabelClass = 'block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1';
@endphp

<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mt-6"
     x-data="trackAudioFiles({
        open: @js(!$track || $existingAudioFiles->isNotEmpty() || $hasAudioErrors),
        main: @js(old('main_audio_file', $defaultMain)),
        rows: @js($newAudioRows),
        removed: @js($removedAudioIds),
        existingIds: @js($existingAudioFiles->pluck('id')),
        errors: @js($errors->getMessages()),
        maxPostBytes: @js($maxPostBytes),
     })">
    <button type="button" @click="open = !open" class="w-full flex items-center justify-between p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            Audio
            @if($existingAudioFiles->isNotEmpty())
                <span class="text-sm font-normal text-gray-400">({{ $existingAudioFiles->count() }} {{ $existingAudioFiles->count() === 1 ? 'Version' : 'Versionen' }})</span>
            @endif
        </h3>
        <svg class="w-5 h-5 text-gray-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div x-show="open" x-collapse>
        <div class="px-6 pb-6 space-y-3">
            <p class="text-xs text-gray-500 dark:text-gray-400">Eine Datei pro Version, jede mit eigenem ISRC. Die Main Version wird in der Trackliste und bei den Produkten abgespielt und heruntergeladen.</p>

            {{-- Vorhandene Versionen --}}
            @foreach($existingAudioFiles as $audioFile)
                @php $audioId = $audioFile->id; @endphp
                <div class="p-4 rounded-lg border space-y-3"
                     :class="isRemoved({{ $audioId }}) ? 'border-red-200 dark:border-red-900/60 bg-red-50/60 dark:bg-red-900/10' : (main === 'existing:{{ $audioId }}' ? 'border-blue-300 dark:border-blue-700 bg-blue-50/40 dark:bg-blue-900/10' : 'border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/40')">
                    {{-- Delete flag: Alpine fills it in, so a form without Alpine never deletes anything. --}}
                    <input type="hidden" name="existing_audio_files[{{ $audioId }}][delete]" value="" x-bind:value="isRemoved({{ $audioId }}) ? '1' : ''">

                    <div class="flex flex-wrap sm:flex-nowrap items-start gap-3">
                        <button type="button" title="Abspielen"
                                @click="$dispatch('play-track', @js($audioFile->playerPayload()))"
                                class="w-8 h-8 flex items-center justify-center rounded-full bg-blue-600 hover:bg-blue-700 text-white transition flex-shrink-0">
                            <svg class="w-4 h-4 ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                        </button>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate" :class="isRemoved({{ $audioId }}) && 'line-through'" title="{{ $audioFile->original_name }}">{{ $audioFile->original_name ?: basename($audioFile->file_path) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $audioFile->technical_summary }}</p>
                        </div>
                        <div class="flex items-center gap-3 flex-shrink-0">
                            <label class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap" :class="isRemoved({{ $audioId }}) && 'opacity-40'">
                                <input type="radio" name="main_audio_file" value="existing:{{ $audioId }}" x-model="main" @checked($currentMain?->id === $audioId) :disabled="isRemoved({{ $audioId }})" class="text-blue-600 focus:ring-blue-500">
                                Main Version
                            </label>
                            <a href="{{ route('admin.tracks.audioFiles.download', [$track, $audioFile]) }}" title="Herunterladen" class="text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            </a>
                            <button type="button" @click="toggleRemoved({{ $audioId }})"
                                    class="text-xs font-medium"
                                    :class="isRemoved({{ $audioId }}) ? 'text-blue-600 dark:text-blue-400 hover:text-blue-800' : 'text-red-600 dark:text-red-400 hover:text-red-800'"
                                    x-text="isRemoved({{ $audioId }}) ? 'Rückgängig' : 'Entfernen'">Entfernen</button>
                        </div>
                    </div>

                    <div x-show="!isRemoved({{ $audioId }})" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="audio-title-{{ $audioId }}" class="{{ $audioLabelClass }}">Titel</label>
                            <input type="text" id="audio-title-{{ $audioId }}" name="existing_audio_files[{{ $audioId }}][title]" value="{{ old("existing_audio_files.$audioId.title", $audioFile->title) }}" placeholder="z.B. Master, Radio Edit, Instrumental" class="{{ $audioInputClass }}">
                            @error("existing_audio_files.$audioId.title") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="audio-isrc-{{ $audioId }}" class="{{ $audioLabelClass }}">ISRC</label>
                            @include('admin.partials.isrc-input', ['inputAttributes' => 'id="audio-isrc-' . $audioId . '" name="existing_audio_files[' . $audioId . '][isrc]" value="' . e(old("existing_audio_files.$audioId.isrc", $audioFile->isrc_formatted)) . '"', 'trackId' => $track?->id, 'scope' => 'audio', 'inputClass' => $audioInputClass])
                            @error("existing_audio_files.$audioId.isrc") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="audio-notes-{{ $audioId }}" class="{{ $audioLabelClass }}">Bemerkung</label>
                            <textarea id="audio-notes-{{ $audioId }}" name="existing_audio_files[{{ $audioId }}][notes]" rows="2" class="{{ $audioInputClass }}">{{ old("existing_audio_files.$audioId.notes", $audioFile->notes) }}</textarea>
                            @error("existing_audio_files.$audioId.notes") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <p x-show="isRemoved({{ $audioId }})" x-cloak class="text-xs text-red-600 dark:text-red-400">Wird beim Speichern entfernt, die Datei wird gelöscht.</p>
                </div>
            @endforeach

            {{-- Neue Versionen --}}
            <template x-for="(row, index) in rows" :key="row.key">
                <div class="p-4 rounded-lg border border-dashed space-y-3"
                     :class="main === 'new:' + row.key ? 'border-blue-300 dark:border-blue-700 bg-blue-50/40 dark:bg-blue-900/10' : 'border-gray-300 dark:border-gray-600'">
                    <div class="flex flex-wrap sm:flex-nowrap items-start gap-3">
                        <div class="flex-1 min-w-0">
                            <input type="file" :name="'audio_files[' + row.key + '][file]'" accept=".mp3,.wav,.flac,.aac,.m4a,.ogg,audio/*"
                                   class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 dark:file:bg-blue-900/50 file:text-blue-700 dark:file:text-blue-300 hover:file:bg-blue-100 dark:hover:file:bg-blue-900">
                            <p x-show="error(row, 'file')" x-text="error(row, 'file')" class="text-red-500 text-xs mt-1"></p>
                        </div>
                        <label class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap pt-2">
                            <input type="radio" name="main_audio_file" :value="'new:' + row.key" x-model="main" class="text-blue-600 focus:ring-blue-500">
                            Main Version
                        </label>
                        <button type="button" @click="removeRow(index)" title="Zeile entfernen" class="px-1 pt-1 text-lg leading-none text-red-400 hover:text-red-600">&times;</button>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label :for="'audio-new-title-' + row.key" class="{{ $audioLabelClass }}">Titel</label>
                            <input type="text" :id="'audio-new-title-' + row.key" :name="'audio_files[' + row.key + '][title]'" x-model="row.title" placeholder="z.B. Master, Radio Edit, Instrumental" class="{{ $audioInputClass }}">
                            <p x-show="error(row, 'title')" x-text="error(row, 'title')" class="text-red-500 text-xs mt-1"></p>
                        </div>
                        <div>
                            <label :for="'audio-new-isrc-' + row.key" class="{{ $audioLabelClass }}">ISRC</label>
                            @include('admin.partials.isrc-input', ['inputAttributes' => ':id="\'audio-new-isrc-\' + row.key" :name="\'audio_files[\' + row.key + \'][isrc]\'" x-model="row.isrc"', 'trackId' => $track?->id, 'scope' => 'audio', 'inputClass' => $audioInputClass])
                            <p x-show="error(row, 'isrc')" x-text="error(row, 'isrc')" class="text-red-500 text-xs mt-1"></p>
                        </div>
                        <div class="sm:col-span-2">
                            <label :for="'audio-new-notes-' + row.key" class="{{ $audioLabelClass }}">Bemerkung</label>
                            <textarea :id="'audio-new-notes-' + row.key" :name="'audio_files[' + row.key + '][notes]'" x-model="row.notes" rows="2" class="{{ $audioInputClass }}"></textarea>
                            <p x-show="error(row, 'notes')" x-text="error(row, 'notes')" class="text-red-500 text-xs mt-1"></p>
                        </div>
                    </div>
                </div>
            </template>

            <div class="flex flex-wrap items-center justify-between gap-2 pt-1">
                <button type="button" @click="addRow()" class="inline-flex items-center text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Audiodatei hinzufügen
                </button>
                <span class="text-xs text-gray-400">
                    MP3, WAV, FLAC, AAC, M4A oder OGG, max. 300 MB pro Datei
                    @if($maxPostBytes > 0)· zusammen max. {{ floor($maxPostBytes / 1024 / 1024) }} MB pro Speichern @endif
                </span>
            </div>
            <p x-show="sizeError" x-ref="sizeError" x-text="sizeError" x-cloak class="text-sm text-red-600 dark:text-red-400"></p>
            @error('main_audio_file') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
function trackAudioFiles(config) {
    return {
        open: config.open,
        main: config.main,
        rows: config.rows,
        removed: config.removed,
        existingIds: config.existingIds,
        errors: config.errors || {},
        maxPostBytes: config.maxPostBytes || 0,
        sizeError: '',

        init() {
            const form = this.$el.closest('form');
            if (!form || this.maxPostBytes <= 0) return;

            form.addEventListener('submit', (event) => {
                const total = [...form.querySelectorAll('input[type=file]')]
                    .reduce((sum, input) => sum + [...input.files].reduce((s, file) => s + file.size, 0), 0);
                // A little room for the rest of the form.
                if (total <= this.maxPostBytes - 1024 * 1024) {
                    this.sizeError = '';
                    return;
                }

                event.preventDefault();
                const mb = bytes => Math.ceil(bytes / 1024 / 1024);
                this.open = true;
                this.sizeError = 'Die gewählten Dateien sind zusammen ' + mb(total) + ' MB gross, pro Speichern gehen höchstens '
                    + Math.floor(this.maxPostBytes / 1024 / 1024) + ' MB. Bitte einen Teil jetzt speichern und die übrigen danach hinzufügen.';
                this.$nextTick(() => this.$refs.sizeError?.scrollIntoView({ behavior: 'smooth', block: 'center' }));
            });
        },

        isRemoved(id) {
            return this.removed.includes(id);
        },

        toggleRemoved(id) {
            if (this.isRemoved(id)) {
                this.removed = this.removed.filter(r => r !== id);
                if (!this.main) this.main = 'existing:' + id;
                return;
            }
            this.removed.push(id);
            if (this.main === 'existing:' + id) this.main = this.fallbackMain();
        },

        addRow() {
            const key = 'n' + Date.now().toString(36) + Math.random().toString(36).slice(2, 6);
            this.rows.push({ key, title: '', isrc: '', notes: '' });
            if (!this.main) this.main = 'new:' + key;
        },

        removeRow(index) {
            const [row] = this.rows.splice(index, 1);
            if (this.main === 'new:' + row.key) this.main = this.fallbackMain();
        },

        // When the main version goes away, the next remaining one takes its place.
        fallbackMain() {
            const id = this.existingIds.find(id => !this.isRemoved(id));
            if (id !== undefined) return 'existing:' + id;
            return this.rows.length ? 'new:' + this.rows[0].key : '';
        },

        error(row, field) {
            return (this.errors['audio_files.' + row.key + '.' + field] || [])[0] || '';
        },
    };
}
</script>
@endpush
@endonce
