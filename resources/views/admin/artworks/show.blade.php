@extends('admin.layouts.app')

@section('title', $artwork->title)

@section('content')
<div class="max-w-4xl">
    <div class="mb-4">
        <a href="{{ route('admin.artworks.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-300">&larr; Zurück zur Übersicht</a>
    </div>

    @if(session('warning'))
    <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-sm text-yellow-800">
        {{ session('warning') }}
    </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $artwork->title }}</h2>
                @if($artwork->projects->count())
                    <p class="text-sm text-gray-500 mt-1">
                        Projekte:
                        @foreach($artwork->projects as $project)
                            <a href="{{ route('admin.projects.show', $project) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">{{ $project->name }}</a>{{ !$loop->last ? ', ' : '' }}
                        @endforeach
                    </p>
                @endif
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.artworks.edit', $artwork) }}" class="px-4 py-2 bg-gray-800 dark:bg-gray-600 text-white text-sm rounded-lg hover:bg-gray-700 dark:hover:bg-gray-50 dark:hover:bg-gray-700/500">Bearbeiten</a>
                <form method="POST" action="{{ route('admin.artworks.destroy', $artwork) }}" onsubmit="return confirm('Artwork wirklich löschen?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-red-600 dark:bg-red-700 text-white text-sm rounded-lg hover:bg-red-700 dark:hover:bg-red-600">Löschen</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Artwork-Bildvarianten --}}
    @if($artwork->images->count())
    <x-admin.collapsible-card title="Bildvarianten" :count="$artwork->images->count()" class="mt-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($artwork->images as $image)
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden {{ $image->is_primary ? 'ring-2 ring-blue-500' : '' }}">
                    <div class="aspect-square bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                        @php
                            $mime = $image->mime_type ?? '';
                            $isRenderable = str_contains($mime, 'jpeg') || str_contains($mime, 'jpg') || str_contains($mime, 'png') || str_contains($mime, 'webp');
                        @endphp
                        @if($isRenderable)
                            <img src="{{ $image->url }}" alt="{{ $image->original_name }}" class="w-full h-full object-contain">
                        @else
                            <div class="text-gray-400 text-center">
                                <p class="text-lg font-semibold">{{ $image->format_label }}</p>
                                <p class="text-xs mt-1 truncate px-2">{{ $image->original_name }}</p>
                            </div>
                        @endif
                    </div>
                    <div class="p-3 text-xs space-y-1 bg-white dark:bg-gray-800">
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            @if($image->purpose)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200">{{ $image->purpose }}</span>
                            @else
                                <span class="text-xs text-gray-400">Ohne Zweck</span>
                            @endif
                            @if($image->is_primary)
                                <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[10px] font-medium bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    Primär
                                </span>
                            @endif
                        </div>
                        <p class="text-gray-600 dark:text-gray-300 truncate" title="{{ $image->original_name }}">{{ $image->original_name }}</p>
                        <div class="text-gray-400 dark:text-gray-500 flex flex-wrap gap-x-2">
                            @if($image->dimensions_label)<span>{{ $image->dimensions_label }}</span>@endif
                            <span>{{ $image->format_label }}</span>
                            @if($image->file_size_mb)<span>{{ $image->file_size_mb }}</span>@endif
                            @if($image->dpi)<span>{{ $image->dpi }} DPI</span>@endif
                        </div>
                        <div class="pt-1">
                            <a href="{{ $image->url }}" download="{{ $image->original_name }}" class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-800">Herunterladen</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </x-admin.collapsible-card>
    @endif

    {{-- Credits --}}
    @php
        $creditRoles = [
            'photographer' => 'Fotograf:in',
            'artwork_by' => 'Artwork by',
            'logo_by' => 'Logo by',
            'design_by' => 'Design by',
        ];
        $groupedCredits = $artwork->credits->groupBy('role');
        $hasCredits = $groupedCredits->isNotEmpty() || $artwork->yoc;
    @endphp
    <x-admin.collapsible-card title="Credits" class="mt-6">
        @if($hasCredits)
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
            @foreach($creditRoles as $role => $roleLabel)
                @if($groupedCredits->has($role))
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ $roleLabel }}</dt>
                    <dd class="text-gray-900 mt-0.5 space-y-1">
                        @foreach($groupedCredits[$role] as $credit)
                            <div class="flex items-center gap-2 flex-wrap">
                                @if($credit->creditable_type === \App\Models\Contact::class)
                                    <a href="{{ route('admin.contacts.show', $credit->creditable_id) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">{{ $credit->display_name }}</a>
                                    @if($credit->ipi_number)
                                        <span class="inline-flex items-center gap-1 text-xs" title="{{ $credit->matched_ipi['name'] ?? '' }}">
                                            <span class="px-1.5 py-0.5 rounded bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 font-mono">IPI</span>
                                            <span class="font-mono text-gray-600 dark:text-gray-300">{{ $credit->ipi_number }}</span>
                                        </span>
                                        @if($credit->creditable && $credit->display_name !== $credit->creditable->full_name)
                                            <span class="text-xs text-gray-500 dark:text-gray-400">(Kontakt: {{ $credit->creditable->full_name }})</span>
                                        @endif
                                    @endif
                                @elseif($credit->creditable_type === \App\Models\Organization::class)
                                    <a href="{{ route('admin.organizations.show', $credit->creditable_id) }}" class="text-purple-600 hover:text-purple-800">{{ $credit->display_name }}</a>
                                @endif
                            </div>
                        @endforeach
                    </dd>
                </div>
                @endif
            @endforeach
            @if($artwork->yoc)
            <div>
                <dt class="text-gray-500 dark:text-gray-400">YOC (Year of Creation)</dt>
                <dd class="text-gray-900 mt-0.5">{{ $artwork->yoc }}</dd>
            </div>
            @endif
        </dl>
        @else
            <p class="text-sm text-gray-400">Keine Credits erfasst.</p>
        @endif
    </x-admin.collapsible-card>

    {{-- Logos --}}
    <x-admin.collapsible-card title="Logos" :count="$artwork->logos->count()" class="mt-6">
        @if($artwork->logos->count())
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
            @foreach($artwork->logos as $logo)
            <div class="group relative bg-gray-50 dark:bg-gray-700/50 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 {{ $logo->is_primary ? 'ring-2 ring-blue-500' : '' }}">
                <div class="aspect-square flex items-center justify-center p-2">
                    <img src="{{ $logo->url }}" alt="{{ $logo->original_name }}" class="max-w-full max-h-full object-contain">
                </div>
                <div class="p-2 border-t border-gray-200 dark:border-gray-700 space-y-0.5">
                    <div class="flex items-center gap-1 flex-wrap">
                        @if($logo->purpose)
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-200">{{ $logo->purpose }}</span>
                        @endif
                        @if($logo->is_primary)
                            <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[10px] font-medium bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                Primär
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-gray-700 dark:text-gray-200 truncate" title="{{ $logo->original_name }}">{{ $logo->original_name }}</p>
                    @if($logo->comment)
                        <p class="text-xs text-gray-400 dark:text-gray-500 truncate" title="{{ $logo->comment }}">{{ $logo->comment }}</p>
                    @endif
                </div>
                <div class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <a href="{{ Storage::disk('public')->url($logo->file_path) }}" download="{{ $logo->original_name }}" class="inline-flex items-center justify-center w-7 h-7 bg-white rounded-full shadow text-gray-500 hover:text-blue-600" title="Download">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    </a>
                </div>
            </div>
            @endforeach
        </div>
        @else
            <p class="text-sm text-gray-400">Keine Logos vorhanden.</p>
        @endif
    </x-admin.collapsible-card>
</div>
@endsection
