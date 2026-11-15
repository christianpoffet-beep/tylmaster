{{--
    ISRC field: stays free to type or overwrite, with a "Generieren" button
    for the next free code and a warning when the code is in use elsewhere.

    $inputAttributes: name/id/value of the input, as escaped HTML attributes -
                      or Alpine bindings when the field sits in a template
    $trackId:         the track being edited (its own records are no
                      duplicates), null on create
    $scope:           'track' for the track's own ISRC, 'audio' for a version's
    $inputClass:      classes of the input
--}}
@php $scope = $scope ?? 'track'; @endphp
<div x-data="isrcField({ trackId: @js($trackId ?? null), scope: @js($scope) })" x-init="check()" @isrc-changed.window="checkForm()">
    <div class="flex gap-2">
        <input type="text" x-ref="input" data-isrc-input data-isrc-scope="{{ $scope }}" {!! $inputAttributes !!}
               placeholder="CC-XXX-YY-NNNNN" @input.debounce.400ms="check()" autocomplete="off"
               class="{{ $inputClass }} min-w-0 flex-1 font-mono">
        <button type="button" @click="generate()" :disabled="busy"
                class="flex-shrink-0 px-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-xs font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50"
                title="Nächste freie ISRC vergeben">
            <span x-text="busy ? '…' : 'Generieren'">Generieren</span>
        </button>
    </div>
    <div x-show="duplicates.length" x-cloak class="mt-1 text-xs text-amber-600 dark:text-amber-400">
        Bereits vergeben:
        <template x-for="(duplicate, index) in duplicates" :key="duplicate.url + index">
            <span><a :href="duplicate.url" target="_blank" class="underline hover:text-amber-800 dark:hover:text-amber-300" x-text="duplicate.label"></a><span x-show="index < duplicates.length - 1">, </span></span>
        </template>
    </div>
    <p x-show="doubledInForm" x-cloak class="mt-1 text-xs text-amber-600 dark:text-amber-400">Diese ISRC steht hier schon bei einer anderen Audioversion.</p>
    <p x-show="error" x-cloak x-text="error" class="mt-1 text-xs text-red-600 dark:text-red-400"></p>
</div>

@once
@push('scripts')
<script>
function isrcField(config) {
    const nextUrl = @js(route('admin.tracks.nextIsrc'));
    const checkUrl = @js(route('admin.tracks.checkIsrc'));
    const normalize = value => String(value || '').toUpperCase().replace(/[^A-Z0-9]/g, '');

    return {
        duplicates: [],
        doubledInForm: false,
        busy: false,
        error: '',
        ticket: 0,

        // The other ISRC fields of the form that are in use (not on a version marked for removal)
        others() {
            const form = this.$el.closest('form');
            return form ? [...form.querySelectorAll('[data-isrc-input]')].filter(input => input !== this.$refs.input && input.offsetParent !== null) : [];
        },

        // Two versions of one track should not share a code; the track's own ISRC may equal its main version's
        checkForm() {
            const code = normalize(this.$refs.input.value);
            this.doubledInForm = config.scope === 'audio' && code !== '' &&
                this.others().some(input => input.dataset.isrcScope === 'audio' && normalize(input.value) === code);
        },

        async check() {
            this.error = '';
            this.checkForm();
            this.$dispatch('isrc-changed');

            const code = normalize(this.$refs.input.value);
            const ticket = ++this.ticket;
            if (!/^[A-Z]{2}[A-Z0-9]{3}\d{7}$/.test(code)) { this.duplicates = []; return; }

            try {
                const params = new URLSearchParams({ isrc: code });
                if (config.trackId) params.set('track', config.trackId);
                const response = await fetch(checkUrl + '?' + params, { headers: { Accept: 'application/json' } });
                const data = await response.json();
                if (ticket === this.ticket) this.duplicates = data.duplicates || [];
            } catch (e) {
                // A failed check blocks nothing; the save shows the warning too.
            }
        },

        async generate() {
            const input = this.$refs.input;
            const current = input.value.trim();
            if (current && !confirm('Die erfasste ISRC ' + current + ' durch die nächste freie Nummer ersetzen?')) return;

            this.busy = true;
            this.error = '';
            try {
                // Codes typed into this form but not saved yet are taken too
                const params = new URLSearchParams();
                this.others().forEach(other => { const code = normalize(other.value); if (code) params.append('taken[]', code); });
                const response = await fetch(nextUrl + '?' + params, { headers: { Accept: 'application/json' } });
                const data = await response.json();
                if (!response.ok || !data.formatted) throw new Error(data.message || 'Es konnte keine ISRC vergeben werden.');

                input.value = data.formatted;
                // Lets x-model (new versions) follow
                input.dispatchEvent(new Event('input', { bubbles: true }));
                this.check();
            } catch (e) {
                this.error = e.message || 'Es konnte keine ISRC vergeben werden.';
            }
            this.busy = false;
        },
    };
}
</script>
@endpush
@endonce
