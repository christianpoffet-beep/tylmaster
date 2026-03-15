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
        @if($contentPost->image)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <img src="{{ asset('storage/' . $contentPost->image) }}" alt="" class="w-full max-h-[500px] object-contain bg-gray-100 dark:bg-gray-900">
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
