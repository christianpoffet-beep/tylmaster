@extends('admin.layouts.app')

@section('title', 'Kampagne: ' . $campaign->name)

@section('content')
<div class="mb-6 flex items-center justify-between">
    <a href="{{ route('admin.campaigns.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">&larr; Zurück zu Kampagnen</a>
    <div class="flex gap-2">
        <a href="{{ route('admin.campaigns.edit', $campaign) }}" class="px-3 py-1.5 text-xs bg-blue-600 text-white rounded-lg hover:bg-blue-700">Bearbeiten</a>
        <form method="POST" action="{{ route('admin.campaigns.duplicate', $campaign) }}">
            @csrf
            <button type="submit" class="px-3 py-1.5 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">Duplizieren</button>
        </form>
    </div>
</div>

<div class="max-w-4xl grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Linke Spalte: Meta --}}
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $campaign->name }}</h2>

            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400">Status</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $campaign->status_color }}">{{ $campaign->status_label }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400">Typ</span>
                    <span class="text-gray-900 dark:text-gray-100">{{ $campaign->type === 'email' ? 'E-Mail' : 'Brief' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400">Sprache</span>
                    <span class="text-gray-900 dark:text-gray-100">{{ strtoupper($campaign->language) }}</span>
                </div>
                @if($campaign->template)
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Vorlage</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ $campaign->template->name }}</span>
                    </div>
                @endif
                @if($campaign->addressCircle)
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Adresskreis</span>
                        <a href="{{ route('admin.address-circles.edit', $campaign->addressCircle) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $campaign->addressCircle->name }}</a>
                    </div>
                @endif
                @if($campaign->sender_name || $campaign->sender_email)
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
                        <span class="text-gray-500 dark:text-gray-400 text-xs uppercase">Absender</span>
                        @if($campaign->sender_name)
                            <p class="text-gray-900 dark:text-gray-100">{{ $campaign->sender_name }}</p>
                        @endif
                        @if($campaign->sender_email)
                            <p class="text-gray-500 dark:text-gray-400">{{ $campaign->sender_email }}</p>
                        @endif
                        @if($campaign->reply_to)
                            <p class="text-gray-400 text-xs">Reply-To: {{ $campaign->reply_to }}</p>
                        @endif
                    </div>
                @endif
                <div class="border-t border-gray-200 dark:border-gray-700 pt-3 flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400">Erstellt</span>
                    <span class="text-gray-900 dark:text-gray-100">{{ $campaign->created_at->format('d.m.Y H:i') }}</span>
                </div>
                @if($campaign->sent_at)
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400">Gesendet</span>
                        <span class="text-gray-900 dark:text-gray-100">{{ $campaign->sent_at->format('d.m.Y H:i') }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Versand-Statistiken --}}
        @if($sendStats['total'] > 0)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Versand</h3>
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div>
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $sendStats['sent'] }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Zugestellt</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $sendStats['failed'] }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Fehler</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-600 dark:text-gray-400">{{ $sendStats['total'] }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Gesamt</div>
                    </div>
                </div>
                <a href="{{ route('admin.campaigns.sendLog', $campaign) }}" class="block mt-3 text-center text-xs text-blue-600 dark:text-blue-400 hover:underline">Versandprotokoll anzeigen</a>
            </div>
        @endif

        {{-- Kampagne senden --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 space-y-4">
            @if($campaign->status === 'draft')
                @if($campaign->address_circle_id && $campaign->subject && $campaign->body)
                    <form method="POST" action="{{ route('admin.campaigns.send', $campaign) }}"
                          onsubmit="return confirm('Kampagne jetzt an alle Empfänger im Adresskreis «{{ $campaign->addressCircle?->name }}» senden?')">
                        @csrf
                        <button type="submit" class="w-full px-4 py-2.5 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">
                            Kampagne senden
                        </button>
                        @if($campaign->addressCircle)
                            <p class="text-xs text-gray-400 mt-1 text-center">{{ $campaign->addressCircle->member_count }} Empfänger im Adresskreis</p>
                        @endif
                    </form>
                @else
                    <div class="text-xs text-gray-400 space-y-1">
                        <p class="font-medium text-gray-500 dark:text-gray-400">Vor dem Senden benötigt:</p>
                        @if(!$campaign->address_circle_id)
                            <p>— Adresskreis zuweisen</p>
                        @endif
                        @if(!$campaign->subject)
                            <p>— Betreff ausfüllen</p>
                        @endif
                        @if(!$campaign->body)
                            <p>— Inhalt erstellen</p>
                        @endif
                    </div>
                @endif
            @elseif($campaign->status === 'sending')
                <p class="text-sm text-yellow-600 dark:text-yellow-400 mb-3">Versand hängt — erneut versuchen:</p>
                <form method="POST" action="{{ route('admin.campaigns.send', $campaign) }}"
                      onsubmit="return confirm('Versand erneut starten?')">
                    @csrf
                    <button type="submit" class="w-full px-4 py-2.5 text-sm bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 font-medium">
                        Erneut senden
                    </button>
                </form>
            @elseif($campaign->status === 'sent')
                <p class="text-sm text-green-600 dark:text-green-400">Kampagne wurde am {{ $campaign->sent_at?->format('d.m.Y H:i') }} gesendet.</p>
            @endif
        </div>

        @if($campaign->notes)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Notizen</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 whitespace-pre-wrap">{{ $campaign->notes }}</p>
            </div>
        @endif
    </div>

    {{-- Rechte Spalte: Inhalt Vorschau --}}
    <div class="lg:col-span-2 space-y-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Betreff</h3>
            <p class="text-gray-900 dark:text-gray-100 mb-4">{{ $campaign->subject ?? '—' }}</p>

            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Inhalt</h3>
            @if($campaign->body)
                <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4 bg-white dark:bg-gray-900">
                    <iframe id="preview-frame" class="w-full border-0" style="min-height: 400px;"></iframe>
                </div>
            @else
                <p class="text-sm text-gray-400 italic">Kein Inhalt definiert.</p>
            @endif
        </div>

        {{-- Test-Versand --}}
        @if($campaign->subject && $campaign->body)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <form method="POST" action="{{ route('admin.campaigns.testSend', $campaign) }}" class="flex items-center gap-3">
                    @csrf
                    <label class="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">Test-E-Mail:</label>
                    <input type="email" name="test_email" placeholder="test@example.com" required
                           class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <button type="submit" class="px-4 py-2 text-sm bg-gray-800 dark:bg-gray-600 text-white rounded-lg hover:bg-gray-700 dark:hover:bg-gray-500 whitespace-nowrap">Test senden</button>
                </form>
            </div>
        @endif
    </div>
</div>

@if($campaign->body)
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const iframe = document.getElementById('preview-frame');
    const doc = iframe.contentDocument || iframe.contentWindow.document;
    doc.open();
    doc.write(@json($campaign->body));
    doc.close();
    // Auto-resize
    setTimeout(() => {
        iframe.style.height = doc.body.scrollHeight + 20 + 'px';
    }, 100);
});
</script>
@endpush
@endif
@endsection
