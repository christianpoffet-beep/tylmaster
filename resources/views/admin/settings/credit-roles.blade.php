@extends('admin.layouts.app')

@section('title', 'Track-Credits')

@section('content')
<div class="max-w-4xl">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Track-Credits verwalten</h1>
    </div>

    <form method="POST" action="{{ route('admin.settings.credit-roles.update') }}" x-data="creditRolesEditor()">
        @csrf

        <div class="space-y-4">
            <template x-for="(group, gi) in groups" :key="gi">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                    {{-- Group header --}}
                    <div class="flex items-center gap-3 p-4 border-b border-gray-200 dark:border-gray-700">
                        <input type="text" x-model="group.name" :name="'groups[' + gi + '][name]'" placeholder="Gruppenname (z.B. Produktion)"
                            class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm font-semibold focus:border-blue-500 focus:ring-blue-500">
                        <button type="button" @click="removeGroup(gi)" class="text-red-400 hover:text-red-600 text-xs">Gruppe entfernen</button>
                    </div>

                    {{-- Items --}}
                    <div class="p-4 space-y-2">
                        <template x-for="(item, ii) in group.items" :key="ii">
                            <div class="flex items-center gap-2">
                                <input type="text" x-model="item.key" :name="'groups[' + gi + '][items][' + ii + '][key]'" placeholder="Schlüssel (z.B. producer)"
                                    class="w-1/3 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm font-mono focus:border-blue-500 focus:ring-blue-500">
                                <input type="text" x-model="item.label" :name="'groups[' + gi + '][items][' + ii + '][label]'" placeholder="Anzeigename (z.B. Produzent)"
                                    class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                                <button type="button" @click="group.items.splice(ii, 1)" class="text-red-500 hover:text-red-700 flex-shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </template>
                        <button type="button" @click="group.items.push({ key: '', label: '' })" class="inline-flex items-center text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800 mt-1">
                            <svg class="w-3.5 h-3.5 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            Rolle hinzufügen
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <button type="button" @click="addGroup()" class="inline-flex items-center text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Neue Gruppe
            </button>
        </div>

        <div class="mt-6 flex gap-3">
            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Speichern</button>
            <a href="{{ route('admin.settings.system') }}" class="px-5 py-2.5 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">Abbrechen</a>
        </div>
    </form>
</div>

<script>
function creditRolesEditor() {
    const data = @json($roles);
    const groups = [];
    for (const [name, items] of Object.entries(data)) {
        const itemList = [];
        for (const [key, label] of Object.entries(items)) {
            itemList.push({ key, label });
        }
        groups.push({ name, items: itemList });
    }
    return {
        groups,
        addGroup() {
            this.groups.push({ name: '', items: [{ key: '', label: '' }] });
        },
        removeGroup(gi) {
            if (confirm('Gruppe wirklich entfernen?')) {
                this.groups.splice(gi, 1);
            }
        }
    };
}
</script>
@endsection
