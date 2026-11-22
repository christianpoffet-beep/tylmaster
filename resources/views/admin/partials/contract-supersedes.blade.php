{{-- An amendment replaces an earlier contract from its start date on (the statements take the one valid in each month) --}}
<div>
    <label for="supersedes_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ersetzt Vertrag</label>
    <select name="supersedes_id" id="supersedes_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
        <option value="">— keinen (neuer Vertrag) —</option>
        @foreach($otherContracts as $other)
            <option value="{{ $other->id }}" @selected((string) $selected === (string) $other->id)>{{ $other->contract_number }} · {{ $other->title }}</option>
        @endforeach
    </select>
    <p class="text-xs text-gray-400 mt-1">Für einen Nachtrag: Der alte Vertrag gilt bis zum Tag vor dem Startdatum dieses Vertrags (hat er noch kein Enddatum, wird es so gesetzt).</p>
    @error('supersedes_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
</div>
