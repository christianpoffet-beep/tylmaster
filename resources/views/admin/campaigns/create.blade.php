@extends('admin.layouts.app')

@section('title', 'Neue Kampagne')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.campaigns.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">&larr; Zurück zu Kampagnen</a>
</div>

<form method="POST" action="{{ route('admin.campaigns.store') }}" class="max-w-4xl"
      x-data="campaignForm()" x-init="init()">
    @csrf

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Neue Kampagne</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name *</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Typ *</label>
                    <select name="type" id="type" required
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="email">E-Mail</option>
                        <option value="letter">Brief</option>
                    </select>
                </div>
                <div>
                    <label for="language" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sprache *</label>
                    <select name="language" id="language" required
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="de">Deutsch</option>
                        <option value="en">English</option>
                        <option value="fr">Français</option>
                        <option value="it">Italiano</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="campaign_template_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Vorlage</label>
                <select name="campaign_template_id" id="campaign_template_id" x-model="templateId" @change="loadTemplate()"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Ohne Vorlage</option>
                    @foreach($templates as $t)
                        <option value="{{ $t->id }}">{{ $t->name }} ({{ strtoupper($t->language) }})</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">Vorlage laden überschreibt Betreff und Inhalt.</p>
            </div>
            <div>
                <label for="address_circle_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Adresskreis</label>
                <select name="address_circle_id" id="address_circle_id"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Kein Adresskreis</option>
                    @foreach($addressCircles as $ac)
                        <option value="{{ $ac->id }}" {{ old('address_circle_id') == $ac->id ? 'selected' : '' }}>{{ $ac->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="sender_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Absender Name</label>
                <input type="text" name="sender_name" id="sender_name" value="{{ old('sender_name') }}"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label for="sender_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Absender E-Mail</label>
                <input type="email" name="sender_email" id="sender_email" value="{{ old('sender_email') }}"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label for="reply_to" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Antwort an (Reply-To)</label>
                <input type="email" name="reply_to" id="reply_to" value="{{ old('reply_to') }}"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
        </div>

        <div>
            <label for="subject" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Betreff</label>
            <input type="text" name="subject" id="subject" x-ref="subject" value="{{ old('subject') }}"
                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
        </div>

        <div>
            <label for="body" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Inhalt (HTML)</label>
            <textarea name="body" id="body" x-ref="body" rows="20"
                      class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm font-mono focus:border-blue-500 focus:ring-blue-500">{{ old('body') }}</textarea>
            <p class="text-xs text-gray-400 mt-1">Platzhalter: {name}, {email}, {organization}</p>
        </div>

        <div>
            <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notizen (intern)</label>
            <textarea name="notes" id="notes" rows="3"
                      class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes') }}</textarea>
        </div>
    </div>

    <div class="mt-6 flex items-center gap-4">
        <button type="submit" class="px-6 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">Kampagne erstellen</button>
        <a href="{{ route('admin.campaigns.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">Abbrechen</a>
    </div>
</form>

@push('scripts')
<script>
function campaignForm() {
    return {
        templateId: '',
        init() {},
        async loadTemplate() {
            if (!this.templateId) return;
            try {
                const res = await fetch(`/admin/campaign-templates/${this.templateId}/data`);
                const data = await res.json();
                this.$refs.subject.value = data.subject || '';
                this.$refs.body.value = data.body || '';
                document.getElementById('language').value = data.language || 'de';
            } catch (e) {
                console.error('Fehler beim Laden der Vorlage', e);
            }
        }
    };
}
</script>
@endpush
@endsection
