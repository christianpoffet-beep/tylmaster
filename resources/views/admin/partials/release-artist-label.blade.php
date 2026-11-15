{{--
    Main artist and label of a product, for the create and the edit form:
    linked band and label organizations, no free text.
    $release: the product being edited, or null on create.

    Text typed before these were organizations stays in main_artist and
    label_name until an organization of that kind is linked, and shows here
    as a hint.
--}}
@php $linked = $release?->organizations ?? collect(); @endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        @include('admin.partials.organization-search', ['selected' => $linked->where('type', 'band'), 'inputName' => 'band_ids[]', 'orgSearchLabel' => 'Hauptkünstler', 'orgTypeFilter' => 'band'])
        @if(filled($release?->main_artist))
            <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">Bisher als Text erfasst: „{{ $release->main_artist }}“ – bitte die passende Band verknüpfen.</p>
        @endif
    </div>
    <div>
        @include('admin.partials.organization-search', ['selected' => $linked->where('type', 'label'), 'inputName' => 'label_ids[]', 'orgSearchLabel' => 'Label', 'orgTypeFilter' => 'label'])
        @if(filled($release?->label_name))
            <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">Bisher als Text erfasst: „{{ $release->label_name }}“ – bitte das passende Label verknüpfen.</p>
        @endif
        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Leer gelassen, übernimmt das Produkt beim Speichern die Labels seiner Tracks.</p>
    </div>
</div>
