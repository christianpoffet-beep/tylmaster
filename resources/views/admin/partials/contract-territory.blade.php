{{--
    Geltungsbereich / Territory. Shared by the contract form and the template
    form, which store the same thing under a "default_" name.

    Parameters: $territory (array of country codes, 'ALL' for worldwide),
                $territoryPresets, $field (default 'territory')
--}}
@php
    $field ??= 'territory';
    $territory = array_values($territory ?? []);

    // Worldwide clears the country boxes, so a failed validation would come back
    // with an empty list. The toggle reports separately - and wins.
    if (old($field . '_worldwide')) {
        $territory = ['ALL'];
    }
@endphp
<div class="border-t border-gray-200 dark:border-gray-700 pt-6"
     x-data="territorySelector(@js($territory), @js($territoryPresets))"
     @territory-set.window="applySelection($event.detail)">
    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Geltungsbereich / Territory</p>

    <div class="flex items-center gap-3 mb-3">
        <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" x-model="worldwide" @change="onWorldwideToggle()" class="sr-only peer">
            <div class="w-9 h-5 bg-gray-200 dark:bg-gray-600 peer-focus:ring-2 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
        </label>
        <span class="text-sm text-gray-700 dark:text-gray-300">Weltweit</span>
    </div>
    <input type="hidden" name="{{ $field }}_worldwide" :value="worldwide ? '1' : '0'">

    <div x-show="!worldwide" x-transition>
        <div class="flex flex-wrap gap-2 mb-3">
            @foreach($territoryPresets as $key => $preset)
                @if($key !== 'world')
                <button type="button"
                    @click="togglePreset('{{ $key }}')"
                    :class="isPresetActive('{{ $key }}') ? 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 border-blue-300 dark:border-blue-600' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600'"
                    class="px-3 py-1.5 text-xs font-medium rounded-lg border hover:shadow-sm transition-colors">
                    {{ $preset['label'] }}
                </button>
                @endif
            @endforeach
        </div>

        <div class="max-h-48 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-700 p-3">
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-1">
                <template x-for="country in allCountries" :key="country.code">
                    <label class="inline-flex items-center gap-1.5 text-xs cursor-pointer py-0.5">
                        <input type="checkbox" :value="country.code" name="{{ $field }}[]" :checked="selected.includes(country.code)" @change="toggleCountry(country.code)" class="rounded text-blue-600 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600">
                        <span class="text-gray-700 dark:text-gray-300 truncate" x-text="country.name"></span>
                    </label>
                </template>
            </div>
        </div>
        <p class="text-xs text-gray-400 mt-1"><span x-text="selected.length"></span> Länder ausgewählt</p>
    </div>
</div>

@once
<script>
function territorySelector(existing, presets) {
    return {
        worldwide: (existing || []).includes('ALL'),
        selected: (existing || []).filter(c => c !== 'ALL'),
        presets: presets,
        allCountries: [
            {code:'AF',name:'Afghanistan'},{code:'EG',name:'Ägypten'},{code:'AL',name:'Albanien'},{code:'DZ',name:'Algerien'},
            {code:'AD',name:'Andorra'},{code:'AR',name:'Argentinien'},{code:'AM',name:'Armenien'},{code:'AZ',name:'Aserbaidschan'},
            {code:'AU',name:'Australien'},{code:'BE',name:'Belgien'},{code:'BA',name:'Bosnien und Herzegowina'},{code:'BR',name:'Brasilien'},
            {code:'BG',name:'Bulgarien'},{code:'CL',name:'Chile'},{code:'CN',name:'China'},{code:'CR',name:'Costa Rica'},
            {code:'DK',name:'Dänemark'},{code:'DE',name:'Deutschland'},{code:'EC',name:'Ecuador'},{code:'EE',name:'Estland'},
            {code:'FI',name:'Finnland'},{code:'FR',name:'Frankreich'},{code:'GE',name:'Georgien'},{code:'GH',name:'Ghana'},
            {code:'GR',name:'Griechenland'},{code:'GB',name:'Grossbritannien'},{code:'GT',name:'Guatemala'},
            {code:'HN',name:'Honduras'},{code:'IN',name:'Indien'},{code:'ID',name:'Indonesien'},{code:'IQ',name:'Irak'},
            {code:'IR',name:'Iran'},{code:'IE',name:'Irland'},{code:'IS',name:'Island'},{code:'IL',name:'Israel'},
            {code:'IT',name:'Italien'},{code:'JP',name:'Japan'},{code:'JO',name:'Jordanien'},{code:'CA',name:'Kanada'},
            {code:'KZ',name:'Kasachstan'},{code:'KE',name:'Kenia'},{code:'CO',name:'Kolumbien'},{code:'XK',name:'Kosovo'},
            {code:'HR',name:'Kroatien'},{code:'CU',name:'Kuba'},{code:'LV',name:'Lettland'},{code:'LB',name:'Libanon'},
            {code:'LI',name:'Liechtenstein'},{code:'LT',name:'Litauen'},{code:'LU',name:'Luxemburg'},{code:'MY',name:'Malaysia'},
            {code:'MT',name:'Malta'},{code:'MA',name:'Marokko'},{code:'MX',name:'Mexiko'},{code:'MD',name:'Moldau'},
            {code:'MC',name:'Monaco'},{code:'ME',name:'Montenegro'},{code:'MZ',name:'Mosambik'},{code:'NZ',name:'Neuseeland'},
            {code:'NL',name:'Niederlande'},{code:'NG',name:'Nigeria'},{code:'MK',name:'Nordmazedonien'},{code:'NO',name:'Norwegen'},
            {code:'AT',name:'Österreich'},{code:'PK',name:'Pakistan'},{code:'PA',name:'Panama'},{code:'PY',name:'Paraguay'},
            {code:'PE',name:'Peru'},{code:'PH',name:'Philippinen'},{code:'PL',name:'Polen'},{code:'PT',name:'Portugal'},
            {code:'RO',name:'Rumänien'},{code:'RU',name:'Russland'},{code:'SA',name:'Saudi-Arabien'},{code:'SE',name:'Schweden'},
            {code:'CH',name:'Schweiz'},{code:'RS',name:'Serbien'},{code:'SG',name:'Singapur'},{code:'SK',name:'Slowakei'},
            {code:'SI',name:'Slowenien'},{code:'ES',name:'Spanien'},{code:'ZA',name:'Südafrika'},{code:'KR',name:'Südkorea'},
            {code:'TW',name:'Taiwan'},{code:'TH',name:'Thailand'},{code:'CZ',name:'Tschechien'},{code:'TN',name:'Tunesien'},
            {code:'TR',name:'Türkei'},{code:'UA',name:'Ukraine'},{code:'HU',name:'Ungarn'},{code:'UY',name:'Uruguay'},
            {code:'US',name:'USA'},{code:'AE',name:'VAE'},{code:'VE',name:'Venezuela'},{code:'VN',name:'Vietnam'},
            {code:'BY',name:'Weissrussland'},{code:'CY',name:'Zypern'}
        ],
        /** Replaces the selection when a contract template is applied. */
        applySelection(codes) {
            const list = codes || [];
            this.worldwide = list.includes('ALL');
            this.selected = list.filter(c => c !== 'ALL');
        },
        onWorldwideToggle() {
            if (this.worldwide) this.selected = [];
        },
        toggleCountry(code) {
            const idx = this.selected.indexOf(code);
            if (idx >= 0) { this.selected.splice(idx, 1); } else { this.selected.push(code); }
        },
        togglePreset(key) {
            const countries = this.presets[key]?.countries || [];
            const allPresent = countries.every(c => this.selected.includes(c));
            if (allPresent) {
                this.selected = this.selected.filter(c => !countries.includes(c));
            } else {
                countries.forEach(c => { if (!this.selected.includes(c)) this.selected.push(c); });
            }
        },
        isPresetActive(key) {
            const countries = this.presets[key]?.countries || [];
            return countries.length > 0 && countries.every(c => this.selected.includes(c));
        }
    };
}
</script>
@endonce
