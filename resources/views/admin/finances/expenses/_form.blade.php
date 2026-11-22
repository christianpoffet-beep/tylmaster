{{--
    Shared by create and edit. Needs $expense, $contacts, $accountings (with their accounts).
--}}
@php
    $input = 'w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500';
    $label = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1';
    $bankBooking = $expense->booking && $expense->booking->bank_transaction_id ? $expense->booking : null;
    $formConfig = [
        'accountingId' => old('accounting_id', $expense->accounting_id),
        'accountId' => old('account_id', $expense->account_id),
        'paidFromId' => old('paid_from_account_id', $expense->paid_from_account_id),
        'locked' => (bool) $bankBooking,
        'accountings' => $accountings->map(fn ($a) => [
            'id' => $a->id,
            'currency' => $a->currency,
            'closed' => $a->is_closed,
            'accounts' => $a->accounts->where('is_header', false)->map(fn ($acc) => [
                'id' => $acc->id, 'number' => $acc->number, 'name' => $acc->name, 'type' => $acc->type,
            ])->values(),
        ])->values(),
    ];
@endphp

<div x-data="expenseForm(@js($formConfig))" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-6">
    <div>
        <label for="description" class="{{ $label }}">Beschreibung *</label>
        <input type="text" name="description" id="description" value="{{ old('description', $expense->description) }}" required class="{{ $input }}" placeholder="z.B. Webhosting 2025">
        @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <label for="amount" class="{{ $label }}">Betrag *</label>
            <input type="number" name="amount" id="amount" value="{{ old('amount', $expense->amount) }}" step="0.01" min="0.01" required class="{{ $input }}">
            @error('amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="currency" class="{{ $label }}">Währung</label>
            <select name="currency" id="currency" class="{{ $input }}">
                @foreach(['CHF', 'EUR', 'USD'] as $currency)
                    <option value="{{ $currency }}" @selected(old('currency', $expense->currency ?? 'CHF') === $currency)>{{ $currency }}</option>
                @endforeach
            </select>
            @error('currency') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="expense_date" class="{{ $label }}">Datum (Beleg) *</label>
            <input type="date" name="expense_date" id="expense_date" value="{{ old('expense_date', $expense->expense_date?->format('Y-m-d')) }}" required class="{{ $input }}">
            @error('expense_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Buchhaltung --}}
    <div class="border-t border-gray-200 dark:border-gray-700 pt-6 space-y-4">
        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Buchhaltung</p>

        @if($bankBooking)
            <div class="rounded-lg bg-green-50 dark:bg-green-900/20 p-3 text-sm text-green-800 dark:text-green-300">
                Über die Bank bezahlt und gebucht am {{ $bankBooking->booking_date->format('d.m.Y') }}
                ({{ number_format((float) $bankBooking->amount, 2, '.', "'") }} {{ $expense->accounting?->currency }}).
                Buchhaltung, Betrag und Datum der Buchung kommen von der Bank &ndash; das Aufwandkonto lässt sich hier noch ändern.
            </div>
            <input type="hidden" name="accounting_id" value="{{ $expense->accounting_id }}">
        @endif

        @if($expense->accounting?->is_closed)
            <p class="text-sm text-amber-600 dark:text-amber-400">Die Buchhaltung «{{ $expense->accounting->name }}» ist abgeschlossen &ndash; die Buchung bleibt, wie sie ist.</p>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div @if($bankBooking) class="hidden" @endif>
                <label for="accounting_id" class="{{ $label }}">Buchhaltung</label>
                <select @unless($bankBooking) name="accounting_id" @endunless id="accounting_id" x-model="accountingId" @change="onAccountingChange()" class="{{ $input }}">
                    <option value="">— keine —</option>
                    @foreach($accountings as $accounting)
                        <option value="{{ $accounting->id }}">{{ $accounting->name }}{{ $accounting->is_closed ? ' (abgeschlossen)' : '' }}</option>
                    @endforeach
                </select>
                @error('accounting_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div x-show="accountingId">
                <label for="account_id" class="{{ $label }}">Aufwandkonto</label>
                <select name="account_id" id="account_id" x-model="accountId" class="{{ $input }}">
                    <option value="">— wählen —</option>
                    <optgroup label="Aufwand">
                        <template x-for="acc in accountsOf('expense')" :key="acc.id">
                            <option :value="acc.id" :selected="acc.id == accountId" x-text="acc.number + ' ' + acc.name"></option>
                        </template>
                    </optgroup>
                    <optgroup label="Andere Konten">
                        <template x-for="acc in accountsOf('other')" :key="acc.id">
                            <option :value="acc.id" :selected="acc.id == accountId" x-text="acc.number + ' ' + acc.name"></option>
                        </template>
                    </optgroup>
                </select>
                @error('account_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        @unless($bankBooking)
            <div x-show="accountingId">
                <label for="paid_from_account_id" class="{{ $label }}">Bezahlt mit</label>
                <select name="paid_from_account_id" id="paid_from_account_id" x-model="paidFromId" class="{{ $input }} sm:w-1/2">
                    <option value="">Bank &ndash; wird im Bankabgleich zugeordnet</option>
                    <optgroup label="Sofort buchen (bar / privat bezahlt)">
                        <template x-for="acc in accountsOf('payment')" :key="acc.id">
                            <option :value="acc.id" :selected="acc.id == paidFromId" x-text="acc.number + ' ' + acc.name"></option>
                        </template>
                    </optgroup>
                </select>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" x-show="!paidFromId">
                    Die Ausgabe bleibt offen, bis die Zahlung im Bankauszug auftaucht &ndash; der Bankabgleich schlägt sie dann vor.
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" x-show="paidFromId">
                    Wird sofort gebucht: Aufwandkonto an das gewählte Konto, am Datum des Belegs.
                </p>
                @error('paid_from_account_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        @endunless
    </div>

    <div class="border-t border-gray-200 dark:border-gray-700 pt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="contact_id" class="{{ $label }}">Kontakt</label>
            <select name="contact_id" id="contact_id" class="{{ $input }}">
                <option value="">-- Kein Kontakt --</option>
                @foreach($contacts as $contact)
                    <option value="{{ $contact->id }}" @selected(old('contact_id', $expense->contact_id) == $contact->id)>{{ $contact->full_name }}</option>
                @endforeach
            </select>
            @error('contact_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="documents" class="{{ $label }}">Belege hochladen</label>
            <input type="file" name="documents[]" id="documents" multiple class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 dark:file:bg-blue-900/50 file:text-blue-700 dark:file:text-blue-300 hover:file:bg-blue-100 dark:hover:file:bg-blue-900">
            <p class="text-xs text-gray-400 mt-1">Quittung, Rechnung, Foto (PDF/JPG/PNG, max. 50 MB)</p>
            @error('documents.*') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="notes" class="{{ $label }}">Notizen</label>
        <textarea name="notes" id="notes" rows="2" class="{{ $input }}">{{ old('notes', $expense->notes) }}</textarea>
    </div>
</div>

<script>
function expenseForm(cfg) {
    return {
        accountings: cfg.accountings,
        accountingId: cfg.accountingId ? String(cfg.accountingId) : '',
        accountId: cfg.accountId ? String(cfg.accountId) : '',
        paidFromId: cfg.paidFromId ? String(cfg.paidFromId) : '',

        accountsOf(kind) {
            const accounting = this.accountings.find(a => a.id == this.accountingId);
            const accounts = accounting ? accounting.accounts : [];
            if (kind === 'expense') return accounts.filter(a => a.type === 'expense');
            if (kind === 'payment') return accounts.filter(a => a.type === 'asset' || a.type === 'liability');
            return accounts.filter(a => a.type !== 'expense');
        },
        onAccountingChange() {
            this.accountId = '';
            this.paidFromId = '';
        },
    };
}
</script>
