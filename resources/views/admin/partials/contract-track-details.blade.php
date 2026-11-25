{{-- How the linked songs appear in the PDF. Contracts and templates store the same column. --}}
{{-- Usage: @include('admin.partials.contract-track-details', ['model' => $contract ?? null]) --}}
@php
    $trackDetails = old('track_details', $model?->track_details ?: \App\Models\Contract::TRACK_DETAILS_DEFAULT);
@endphp
<div class="border-t border-gray-200 dark:border-gray-700 pt-6"
     x-data="{ value: @js($trackDetails) }"
     @track-details-set.window="value = $event.detail || @js(\App\Models\Contract::TRACK_DETAILS_DEFAULT)">
    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Songs im PDF</p>
    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Wie die verknüpften Tracks im Anhang erscheinen.</p>
    <div class="space-y-2">
        @foreach(\App\Models\Contract::TRACK_DETAILS as $key => [$label, $hint])
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="radio" name="track_details" value="{{ $key }}" x-model="value" class="mt-0.5 text-blue-600 focus:ring-blue-500">
                <span>
                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                    <span class="block text-xs text-gray-400">{{ $hint }}</span>
                </span>
            </label>
        @endforeach
    </div>
</div>
