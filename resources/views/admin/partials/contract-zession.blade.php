{{--
    Zession (Vorschusszahlung). Shared by the contract form and the template
    form, which store the same thing under "default_" names.

    Parameters: $prefix (field name prefix, default ''), $has, $amount,
                $currency, $notes
--}}
@php
    $prefix ??= '';
    $id = fn ($field) => $prefix . $field;
@endphp
<div class="border-t border-gray-200 dark:border-gray-700 pt-6"
     x-data="{ hasZession: {{ $has ? 'true' : 'false' }} }"
     @zession-set.window="
        hasZession = !!$event.detail.has;
        if ($event.detail.amount != null) document.getElementById('{{ $id('zession_amount') }}').value = $event.detail.amount;
        if ($event.detail.currency) document.getElementById('{{ $id('zession_currency') }}').value = $event.detail.currency;
        document.getElementById('{{ $id('zession_notes') }}').value = $event.detail.notes ?? '';
     ">
    <div class="flex items-center gap-3 mb-3">
        <label class="relative inline-flex items-center cursor-pointer">
            <input type="hidden" name="{{ $id('has_zession') }}" value="0">
            <input type="checkbox" name="{{ $id('has_zession') }}" value="1" x-model="hasZession" class="sr-only peer">
            <div class="w-9 h-5 bg-gray-200 dark:bg-gray-600 peer-focus:ring-2 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
        </label>
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Zession (Vorschusszahlung)</span>
    </div>
    <div x-show="hasZession" x-transition class="space-y-3 ml-12">
        <p class="text-xs text-gray-500 dark:text-gray-400">Vorschuss, der mit künftigen Einnahmen verrechnet wird.</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="{{ $id('zession_amount') }}" class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Betrag</label>
                <input type="number" name="{{ $id('zession_amount') }}" id="{{ $id('zession_amount') }}" value="{{ $amount }}" step="0.01" min="0" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="0.00">
            </div>
            <div>
                <label for="{{ $id('zession_currency') }}" class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Währung</label>
                <select name="{{ $id('zession_currency') }}" id="{{ $id('zession_currency') }}" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @foreach(['CHF', 'EUR', 'USD'] as $code)
                        <option value="{{ $code }}" {{ $currency === $code ? 'selected' : '' }}>{{ $code }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div>
            <label for="{{ $id('zession_notes') }}" class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Notizen zur Zession</label>
            <textarea name="{{ $id('zession_notes') }}" id="{{ $id('zession_notes') }}" rows="2" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="z.B. Rückzahlungsbedingungen...">{{ $notes }}</textarea>
        </div>
    </div>
</div>
