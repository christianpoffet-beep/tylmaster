{{--
    Data and behaviour of the party search (contract-party-picker). Include
    once per page, outside the parties loop: a <script> inside an Alpine
    <template> never runs.

    Parameters: $organizations (with contacts), $contacts
--}}
@once
<script>
const CONTRACT_PARTY_OPTIONS = (() => {
    const options = @json(\App\Models\ContractParty::pickerOptions($organizations, $contacts));
    options.entries.forEach(e => {
        e.nameNorm = contractPartyNormalize(e.name);
        e.haystack = contractPartyNormalize(e.name + ' ' + e.search);
    });
    return options;
})();

/** "Zürich" and "zurich" find each other. */
function contractPartyNormalize(text) {
    return String(text || '').normalize('NFD').replace(/\p{M}/gu, '').toLowerCase();
}

/** config.members = false: organizations and persons only, no "Band · Person" entries. */
function contractPartyPicker(config = {}) {
    return {
        query: '',
        open: false,
        editing: false,
        active: 0,
        members: config.members !== false,

        hasSelection(p) {
            return p.type === 'organization' ? !!p.organization_id : !!p.contact_id;
        },
        /** Name, detail line and badge of the chosen party. */
        selection(p) {
            if (p.type === 'organization' && p.organization_id) {
                const org = CONTRACT_PARTY_OPTIONS.organizations[p.organization_id];
                return { name: org ? org.name : 'Organisation #' + p.organization_id, detail: org ? org.detail : '', badge: 'Organisation' };
            }
            if (p.type === 'contact' && p.contact_id) {
                const person = CONTRACT_PARTY_OPTIONS.contacts[p.contact_id];
                return { name: person ? person.name : 'Kontakt #' + p.contact_id, detail: person ? person.detail : '', badge: 'Person' };
            }
            return { name: '', detail: '', badge: '' };
        },
        /**
         * Every word typed has to appear. Names that start with the query come
         * first; a person within an organization only shows once something is
         * typed, so the plain list stays short.
         */
        get results() {
            const words = contractPartyNormalize(this.query).split(/\s+/).filter(Boolean);
            const entries = this.members ? CONTRACT_PARTY_OPTIONS.entries : CONTRACT_PARTY_OPTIONS.entries.filter(e => !e.member);
            if (words.length === 0) {
                return entries.filter(e => !e.member);
            }
            const phrase = words.join(' ');
            const rank = (e) => {
                if (e.nameNorm.startsWith(phrase)) return 0;
                if (e.nameNorm.split(/[\s·,()-]+/).some(part => part.startsWith(words[0]))) return 1;
                return 2;
            };
            return entries
                .filter(e => words.every(w => e.haystack.includes(w)))
                .map(e => ({ e, r: rank(e) }))
                .sort((a, b) => a.r - b.r || (a.e.member - b.e.member))
                .slice(0, 30)
                .map(x => x.e);
        },
        badgeClass(entry) {
            if (!entry) return '';
            if (entry.badge === 'Person') return 'bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300';
            if (entry.badge === 'Org. + Person') return 'bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300';
            return 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300';
        },
        start() {
            this.editing = true;
            this.query = '';
            this.active = 0;
            this.open = true;
            this.$nextTick(() => this.$refs.search.focus());
        },
        close() {
            this.open = false;
            this.editing = false;
            this.query = '';
        },
        move(delta) {
            const count = this.results.length;
            if (!count) return;
            this.open = true;
            this.active = (this.active + delta + count) % count;
            this.$nextTick(() => {
                this.$refs.list?.querySelector(`[data-index="${this.active}"]`)?.scrollIntoView({ block: 'nearest' });
            });
        },
        pickActive(p) {
            const entry = this.results[this.active];
            if (entry) this.choose(p, entry);
        },
        choose(p, entry) {
            p.type = entry.type;
            p.organization_id = entry.organization_id ? String(entry.organization_id) : '';
            // The contact person list re-renders for the new organization first,
            // otherwise its <select> cannot show the chosen person.
            p.contact_id = '';
            const contactId = entry.contact_id ? String(entry.contact_id) : '';
            this.$nextTick(() => { p.contact_id = contactId; });
            this.close();
        },
    };
}
</script>
@endonce
