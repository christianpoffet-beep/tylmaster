@extends('admin.layouts.app')

@section('title', $contentPost->title ?: 'Beitrag')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">{{ $contentPost->title ?: 'Beitrag' }}</h1>
    <div class="flex items-center gap-3">
        <form method="POST" action="{{ route('admin.content-posts.duplicate', $contentPost) }}">
            @csrf
            <button type="submit" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">Duplizieren</button>
        </form>
        <a href="{{ route('admin.content-posts.edit', $contentPost) }}" class="px-4 py-2 bg-blue-600 dark:bg-blue-700 text-white text-sm rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600">Bearbeiten</a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Left: Details --}}
    <div class="lg:col-span-1 space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Details</h2>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Plattform</dt>
                    <dd class="text-gray-900 dark:text-gray-100">{{ $contentPost->platform_label }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Status</dt>
                    <dd><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $contentPost->status_color }}">{{ $contentPost->status_label }}</span></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Geplant</dt>
                    <dd class="text-gray-900 dark:text-gray-100">{{ $contentPost->scheduled_at?->format('d.m.Y H:i') ?? '—' }}</dd>
                </div>
                @if($contentPost->published_at)
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Veröffentlicht</dt>
                    <dd class="text-gray-900 dark:text-gray-100">{{ $contentPost->published_at->format('d.m.Y H:i') }}</dd>
                </div>
                @endif
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Erstellt</dt>
                    <dd class="text-gray-900 dark:text-gray-100">{{ $contentPost->created_at->format('d.m.Y H:i') }}</dd>
                </div>
                @if($contentPost->image_source_label)
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">Bild-Quelle</dt>
                    <dd class="text-gray-900 dark:text-gray-100 text-right text-xs">{{ $contentPost->image_source_label }}</dd>
                </div>
                @endif
            </dl>

            @if($contentPost->status !== 'published')
                <div class="mt-5 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <form method="POST" action="{{ route('admin.content-posts.markPublished', $contentPost) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" onclick="return confirm('Als veröffentlicht markieren?')"
                                class="w-full px-4 py-2 bg-green-600 dark:bg-green-700 text-white text-sm rounded-lg hover:bg-green-700 dark:hover:bg-green-600 text-center">
                            Als veröffentlicht markieren
                        </button>
                    </form>
                </div>
            @endif
        </div>

        {{-- Share section --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Vorschau teilen</h2>

            @if($contentPost->share_token)
                <div x-data="{ copied: false }" class="space-y-3">
                    <div class="flex items-center gap-2">
                        <input type="text" readonly value="{{ $contentPost->share_url }}"
                               class="flex-1 text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 bg-gray-50">
                        <button type="button" @click="navigator.clipboard.writeText('{{ $contentPost->share_url }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                class="px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <span x-show="!copied">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            </span>
                            <span x-show="copied" x-cloak class="text-green-600 dark:text-green-400 text-xs">Kopiert!</span>
                        </button>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ $contentPost->share_url }}" target="_blank"
                           class="flex-1 px-3 py-2 text-sm text-center rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                            Vorschau öffnen
                        </a>
                        <form method="POST" action="{{ route('admin.content-posts.unshare', $contentPost) }}" class="flex-1">
                            @csrf
                            @method('DELETE')
                            <button type="submit" onclick="return confirm('Link deaktivieren? Externe Personen können die Vorschau danach nicht mehr sehen.')"
                                    class="w-full px-3 py-2 text-sm rounded-lg border border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30">
                                Link deaktivieren
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <form method="POST" action="{{ route('admin.content-posts.share', $contentPost) }}">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center justify-center gap-2 px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                        Teilen-Link erstellen
                    </button>
                </form>
            @endif
        </div>

        @if($contentPost->notes)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Notizen</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 whitespace-pre-line">{{ $contentPost->notes }}</p>
        </div>
        @endif
    </div>

    {{-- Right: Preview --}}
    <div class="lg:col-span-2 space-y-6">
        {{-- Image --}}
        @if($contentPost->effective_image_url)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <img src="{{ $contentPost->effective_image_url }}" alt="" class="w-full max-h-[500px] object-contain bg-gray-100 dark:bg-gray-900">
        </div>
        @endif

        {{-- Caption --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Caption</h2>
            <div class="text-sm text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ $contentPost->caption }}</div>

            @if($contentPost->hashtags)
                <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-blue-600 dark:text-blue-400">{{ $contentPost->hashtags }}</p>
                </div>
            @endif

            {{-- Copy button --}}
            <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700" x-data="{ copied: false }">
                <button @click="
                    const text = {{ Js::from($contentPost->caption . ($contentPost->hashtags ? '\n\n' . $contentPost->hashtags : '')) }};
                    navigator.clipboard.writeText(text);
                    copied = true;
                    setTimeout(() => copied = false, 2000);
                " class="px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                    <span x-show="!copied">Caption + Hashtags kopieren</span>
                    <span x-show="copied" x-cloak class="text-green-600 dark:text-green-400">Kopiert!</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
