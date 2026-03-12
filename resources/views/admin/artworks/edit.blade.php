@extends('admin.layouts.app')

@section('title', 'Artwork bearbeiten')

@section('content')
<div class="max-w-3xl">
    <form method="POST" action="{{ route('admin.artworks.update', $artwork) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-6">
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Titel *</label>
                <input type="text" name="title" id="title" value="{{ old('title', $artwork->title) }}" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <hr class="border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Artwork-Bild</h3>

            @if($artwork->artwork_path)
            <div class="flex items-start gap-4">
                <div class="w-32 h-32 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700 flex-shrink-0">
                    @if(str_starts_with($artwork->artwork_mime_type ?? '', 'image/jpeg') || str_starts_with($artwork->artwork_mime_type ?? '', 'image/jpg'))
                        <img src="{{ $artwork->artwork_url }}" alt="{{ $artwork->title }}" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center text-gray-400 text-xs">{{ strtoupper(pathinfo($artwork->artwork_original_name, PATHINFO_EXTENSION)) }}</div>
                    @endif
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    <p>{{ $artwork->artwork_original_name }}</p>
                    <p class="text-xs text-gray-400">{{ number_format(($artwork->artwork_file_size ?? 0) / 1024 / 1024, 1) }} MB</p>
                </div>
            </div>
            @endif

            <div>
                <label for="artwork_file" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $artwork->artwork_path ? 'Artwork ersetzen' : 'Artwork hochladen' }}</label>
                <input type="file" name="artwork_file" id="artwork_file" accept=".jpg,.jpeg,.tiff,.tif"
                    class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 dark:file:bg-blue-900/50 file:text-blue-700 dark:file:text-blue-300 hover:file:bg-blue-100 dark:hover:file:bg-blue-900">
                <p class="text-xs text-gray-400 mt-1">Exakt 3000 × 3000 Pixel, JPG oder TIFF, mind. 300 DPI empfohlen</p>
                @error('artwork_file') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <hr class="border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Credits</h3>

            <div class="space-y-4">
                @include('admin.partials.credit-search', ['role' => 'photographer', 'label' => 'Fotograf:in', 'selected' => $artwork->creditsForRole('photographer')])
                @include('admin.partials.credit-search', ['role' => 'artwork_by', 'label' => 'Artwork by', 'selected' => $artwork->creditsForRole('artwork_by')])
                @include('admin.partials.credit-search', ['role' => 'logo_by', 'label' => 'Logo by', 'selected' => $artwork->creditsForRole('logo_by')])
                @include('admin.partials.credit-search', ['role' => 'design_by', 'label' => 'Design by', 'selected' => $artwork->creditsForRole('design_by')])
            </div>

            <div>
                <label for="yoc" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">YOC (Year of Creation)</label>
                <input type="number" name="yoc" id="yoc" value="{{ old('yoc', $artwork->yoc) }}" min="1900" max="{{ date('Y') + 1 }}" class="w-32 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                @error('yoc') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <hr class="border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Projekte</h3>

            @if($projects->count())
            <div class="max-h-48 overflow-y-auto border border-gray-200 rounded-lg p-3 space-y-2">
                @php $selectedProjects = old('project_ids', $artwork->projects->pluck('id')->toArray()); @endphp
                @foreach($projects as $project)
                    <label class="flex items-center">
                        <input type="checkbox" name="project_ids[]" value="{{ $project->id }}" {{ in_array($project->id, $selectedProjects) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $project->name }}</span>
                    </label>
                @endforeach
            </div>
            @else
                <p class="text-sm text-gray-400">Keine Projekte vorhanden.</p>
            @endif

            <hr class="border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Bestehende Logos</h3>

            @if($artwork->logos->count())
            <div class="space-y-2">
                @foreach($artwork->logos as $logo)
                <div class="flex items-center justify-between py-2 px-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded bg-gray-200 overflow-hidden flex-shrink-0">
                            <img src="{{ $logo->url }}" alt="{{ $logo->original_name }}" class="w-full h-full object-cover">
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm text-gray-900 truncate">{{ $logo->original_name }}</p>
                            @if($logo->comment)
                                <p class="text-xs text-gray-400 dark:text-gray-500 truncate">{{ $logo->comment }}</p>
                            @endif
                        </div>
                    </div>
                    <button type="button" class="text-red-500 hover:text-red-700 text-sm ml-2"
                        onclick="if(confirm('Logo wirklich löschen?')) { const f = document.createElement('form'); f.method='POST'; f.action='{{ route('admin.artworks.logos.destroy', [$artwork, $logo]) }}'; f.innerHTML='<input type=hidden name=_token value={{ csrf_token() }}><input type=hidden name=_method value=DELETE>'; document.body.appendChild(f); f.submit(); }">Löschen</button>
                </div>
                @endforeach
            </div>
            @else
                <p class="text-sm text-gray-400">Keine Logos vorhanden.</p>
            @endif

            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Neue Logos hinzufügen</h3>

            <div x-data="{ logos: [{ comment: '' }] }">
                <p class="text-xs text-gray-400 mb-2">Alle Bildformate erlaubt. Zu jedem Logo kann ein Kommentar hinzugefügt werden.</p>

                <template x-for="(logo, index) in logos" :key="index">
                    <div class="flex flex-col sm:flex-row gap-2 mb-3 p-3 bg-gray-50 rounded-lg">
                        <div class="flex-1">
                            <input type="file" :name="'logos[' + index + '][file]'" accept="image/*"
                                class="w-full text-sm text-gray-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 dark:file:bg-blue-900/50 file:text-blue-700 dark:file:text-blue-300 hover:file:bg-blue-100 dark:hover:file:bg-blue-900">
                        </div>
                        <div class="flex-1">
                            <input type="text" :name="'logos[' + index + '][comment]'" x-model="logo.comment" placeholder="Kommentar (optional)" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <button type="button" x-show="logos.length > 1" @click="logos.splice(index, 1)" class="px-2 py-1 text-red-500 hover:text-red-700 text-lg self-start" title="Entfernen">&times;</button>
                    </div>
                </template>

                <button type="button" @click="logos.push({ comment: '' })" class="inline-flex items-center text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Logo hinzufügen
                </button>
            </div>
        </div>

        <div class="mt-4 flex gap-3">
            <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Artwork aktualisieren</button>
            <a href="{{ route('admin.artworks.show', $artwork) }}" class="px-5 py-2.5 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 dark:bg-gray-700/50">Abbrechen</a>
        </div>
    </form>

    <!-- Löschen -->
    <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
        <form method="POST" action="{{ route('admin.artworks.destroy', $artwork) }}" onsubmit="return confirm('Artwork wirklich löschen? Alle zugehörigen Logos werden ebenfalls gelöscht.')">
            @csrf
            @method('DELETE')
            <h3 class="text-sm font-semibold text-red-600 mb-2">Gefahrenzone</h3>
            <p class="text-sm text-gray-500 mb-3">Das Artwork und alle zugehörigen Logos werden unwiderruflich gelöscht.</p>
            <button type="submit" class="px-5 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700">Artwork löschen</button>
        </form>
    </div>
</div>
@endsection
