{{--
    Parteien-Präambel ("... vertreten durch ..., nachfolgend «Label»").

    Needs the surrounding Alpine scope to expose `parties`, `orgMeta` and
    `contactMeta` — both contractForm() and templateForm() do.

    Parameters: $prefix (field name prefix, default ''), $mode,
                $showPartiesTable, $text
--}}
@php
    $prefix ??= '';
    $field = fn ($name) => $prefix . $name;
@endphp
<div class="border-t border-gray-200 dark:border-gray-700 pt-6"
     x-data="{ preambleMode: @js($mode) }"
     @preamble-set.window="preambleMode = $event.detail.mode || 'auto'">
    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Präambel der Vertragsparteien</p>
    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
        Der Einleitungstext mit Namen, Adressen und Rollen der Parteien. Im Modus «Automatisch» wird er aus
        den oben erfassten Parteien generiert — er gehört dann <strong>nicht</strong> mehr in den Vertragsgegenstand.
    </p>

    <div class="flex flex-wrap gap-4 mb-3">
        @foreach(['auto' => 'Automatisch', 'custom' => 'Eigener Text', 'none' => 'Keine Präambel'] as $value => $label)
            <label class="inline-flex items-center">
                <input type="radio" name="{{ $field('preamble_mode') }}" value="{{ $value }}" x-model="preambleMode" class="text-blue-600 focus:ring-blue-500">
                <span class="ml-1.5 text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
            </label>
        @endforeach
    </div>

    <label class="inline-flex items-center mb-3">
        <input type="hidden" name="{{ $field('show_parties_table') }}" value="0">
        <input type="checkbox" name="{{ $field('show_parties_table') }}" value="1" {{ $showPartiesTable ? 'checked' : '' }} class="rounded text-blue-600 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600">
        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Parteien-Tabelle mit Anteilen im PDF anzeigen</span>
    </label>

    <div x-show="preambleMode === 'auto'" x-cloak>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Vorschau (aus den Parteien generiert):</p>
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-3 text-xs text-gray-700 dark:text-gray-300 whitespace-pre-line leading-relaxed" x-text="preamblePreview || 'Noch keine Parteien mit Rolle erfasst.'"></div>
        <p class="text-[11px] text-gray-400 mt-1">Die Rolle («Label», «Künstlerin oder Künstler» …) wird pro Partei oben erfasst. Parteien mit gleicher Rolle werden zusammengefasst.</p>
    </div>

    <div x-show="preambleMode === 'custom'" x-cloak>
        <textarea name="{{ $field('preamble_text') }}" rows="8" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Eigener Präambel-Text …">{{ $text }}</textarea>
    </div>
</div>

@once
<script>
/**
 * Mirrors Contract::preambleBlocks() so the admin sees what the PDF will print
 * before saving.
 */
function contractPreamblePreview(parties, orgMeta, contactMeta) {
    const groups = [];
    (parties || []).forEach(p => {
        const role = (p.role_label || '').trim();
        const key = role ? role.toLowerCase() : null;
        const last = groups[groups.length - 1];
        if (key && last && last.key === key) {
            last.parties.push(p);
        } else {
            groups.push({ key, role, parties: [p] });
        }
    });

    const entityOf = (p) => p.type === 'organization'
        ? orgMeta[p.organization_id]
        : contactMeta[p.contact_id];

    const blocks = [];
    groups.forEach(g => {
        const lines = [];
        const joint = g.parties.length > 1;
        const orgIds = [...new Set(g.parties.filter(p => p.type === 'organization' && p.organization_id).map(p => String(p.organization_id)))];
        const sharedOrg = (joint && orgIds.length === 1 && g.parties.every(p => String(p.organization_id) === orgIds[0]))
            ? orgMeta[orgIds[0]]
            : null;

        if (sharedOrg) {
            lines.push(sharedOrg.name);
            lines.push('bestehend aus');
            g.parties.forEach(p => {
                const c = contactMeta[p.contact_id];
                if (!c) return;
                lines.push(c.address ? c.name + ', ' + c.address : c.name);
            });
        } else {
            g.parties.forEach(p => {
                const e = entityOf(p);
                if (!e) return;
                lines.push(e.name);
                if (p.type === 'organization' && p.contact_id && contactMeta[p.contact_id]) {
                    lines.push('vertreten durch ' + contactMeta[p.contact_id].name);
                }
                if (e.address) lines.push(e.address);
            });
        }

        if (lines.length === 0) return;
        if (g.role) {
            lines.push('(nachfolgend ' + (joint ? 'gemeinsam ' : '') + '«' + g.role + '»)');
        }
        blocks.push(lines.join('\n'));
    });

    return blocks.join('\n\nund\n\n');
}
</script>
@endonce
