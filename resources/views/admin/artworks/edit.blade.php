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
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Artwork-Bildvarianten</h3>

            <datalist id="image-purpose-suggestions">
                <option value="Master"><option value="Cover"><option value="Web">
                <option value="Spotify"><option value="Apple Music"><option value="Print">
                <option value="Social Media"><option value="Thumbnail">
            </datalist>

            @if($artwork->images->count())
            <div class="space-y-2">
                @foreach($artwork->images as $image)
                <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <div class="w-16 h-16 rounded overflow-hidden bg-gray-200 dark:bg-gray-600 flex-shrink-0">
                        @if($image->isImage() && str_contains($image->mime_type, 'jpeg') || str_contains($image->mime_type ?? '', 'png') || str_contains($image->mime_type ?? '', 'webp'))
                            <img src="{{ $image->url }}" alt="{{ $image->original_name }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-xs text-gray-500 font-medium">{{ $image->format_label }}</div>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0 space-y-1.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $image->original_name }}</span>
                            @if($image->is_primary)
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300">PRIMÄR</span>
                            @endif
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 space-x-2">
                            @if($image->dimensions_label)<span>{{ $image->dimensions_label }}</span>@endif
                            <span>{{ $image->format_label }}</span>
                            @if($image->file_size_mb)<span>{{ $image->file_size_mb }}</span>@endif
                            @if($image->dpi)<span>{{ $image->dpi }} DPI</span>@endif
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="text" name="existing_images[{{ $image->id }}][purpose]" value="{{ old('existing_images.'.$image->id.'.purpose', $image->purpose) }}" placeholder="Zweck (z.B. Master)" list="image-purpose-suggestions" class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <label class="inline-flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                <input type="radio" name="primary_image_selector" value="existing:{{ $image->id }}" {{ $image->is_primary ? 'checked' : '' }} class="text-blue-600 focus:ring-blue-500">
                                Primär
                            </label>
                            <button type="button" class="text-red-500 hover:text-red-700 text-sm"
                                onclick="if(confirm('Bildvariante wirklich löschen?')) { const f = document.createElement('form'); f.method='POST'; f.action='{{ route('admin.artworks.images.destroy', [$artwork, $image]) }}'; f.innerHTML='<input type=hidden name=_token value={{ csrf_token() }}><input type=hidden name=_method value=DELETE>'; document.body.appendChild(f); f.submit(); }">Löschen</button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
                <p class="text-sm text-gray-400">Noch keine Bildvarianten vorhanden.</p>
            @endif

            <h4 class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide pt-2">Neue Variante hochladen</h4>

            <div x-data="{ images: [{ purpose: '', primary: false }] }">
                <template x-for="(image, index) in images" :key="index">
                    <div class="mb-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg space-y-2">
                        <input type="file" :name="'images[' + index + '][file]'" accept=".jpg,.jpeg,.png,.tiff,.tif,.webp"
                            class="block w-full text-sm text-gray-500 dark:text-gray-300 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 dark:file:bg-blue-900/50 file:text-blue-700 dark:file:text-blue-300 hover:file:bg-blue-100 dark:hover:file:bg-blue-900">
                        <div class="flex items-center gap-2">
                            <input type="text" :name="'images[' + index + '][purpose]'" x-model="image.purpose" placeholder="Zweck (z.B. Master, Web)" list="image-purpose-suggestions" class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <label class="inline-flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                <input type="radio" name="primary_image_selector" :value="'new:' + index" :checked="image.primary" @change="images.forEach((img, i) => img.primary = (i === index))" class="text-blue-600 focus:ring-blue-500">
                                Als primär setzen
                            </label>
                            <button type="button" x-show="images.length > 1" @click="images.splice(index, 1)" class="px-2 py-1 text-red-500 hover:text-red-700 text-lg" title="Entfernen">&times;</button>
                        </div>
                    </div>
                </template>

                <button type="button" @click="images.push({ purpose: '', primary: false })" class="inline-flex items-center text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    Weitere Variante hinzufügen
                </button>
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

            <div>
                @include('admin.partials.project-search', ['selected' => $artwork->projects])
            </div>

            <hr class="border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Logos</h3>

            <datalist id="logo-purpose-suggestions">
                <option value="Dark"><option value="Light"><option value="Monochrome">
                <option value="Outline"><option value="Icon"><option value="Wordmark">
            </datalist>

            @if($artwork->logos->count())
            <div class="space-y-2">
                @foreach($artwork->logos as $logo)
                <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <div class="w-12 h-12 rounded bg-gray-200 dark:bg-gray-600 overflow-hidden flex-shrink-0">
                        <img src="{{ $logo->url }}" alt="{{ $logo->original_name }}" class="w-full h-full object-cover">
                    </div>
                    <div class="flex-1 min-w-0 space-y-1.5">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $logo->original_name }}</span>
                            @if($logo->is_primary)
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300">PRIMÄR</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="text" name="existing_logos[{{ $logo->id }}][comment]" value="{{ old('existing_logos.'.$logo->id.'.comment', $logo->comment) }}" placeholder="Kommentar" class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <input type="text" name="existing_logos[{{ $logo->id }}][purpose]" value="{{ old('existing_logos.'.$logo->id.'.purpose', $logo->purpose) }}" placeholder="Zweck" list="logo-purpose-suggestions" class="w-32 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <label class="inline-flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                <input type="radio" name="primary_logo_selector" value="existing:{{ $logo->id }}" {{ $logo->is_primary ? 'checked' : '' }} class="text-blue-600 focus:ring-blue-500">
                                Primär
                            </label>
                            <button type="button" class="text-red-500 hover:text-red-700 text-sm"
                                onclick="if(confirm('Logo wirklich löschen?')) { const f = document.createElement('form'); f.method='POST'; f.action='{{ route('admin.artworks.logos.destroy', [$artwork, $logo]) }}'; f.innerHTML='<input type=hidden name=_token value={{ csrf_token() }}><input type=hidden name=_method value=DELETE>'; document.body.appendChild(f); f.submit(); }">Löschen</button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
                <p class="text-sm text-gray-400">Keine Logos vorhanden.</p>
            @endif

            <h4 class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide pt-2">Neue Logos hinzufügen</h4>

            <div x-data="{ logos: [{ comment: '', purpose: '', primary: false }] }">
                <template x-for="(logo, index) in logos" :key="index">
                    <div class="mb-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg space-y-2">
                        <div class="flex flex-col sm:flex-row gap-2">
                            <div class="flex-1">
                                <input type="file" :name="'logos[' + index + '][file]'" accept="image/*"
                                    class="w-full text-sm text-gray-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 dark:file:bg-blue-900/50 file:text-blue-700 dark:file:text-blue-300 hover:file:bg-blue-100 dark:hover:file:bg-blue-900">
                            </div>
                            <div class="flex-1">
                                <input type="text" :name="'logos[' + index + '][comment]'" x-model="logo.comment" placeholder="Kommentar" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <input type="text" :name="'logos[' + index + '][purpose]'" x-model="logo.purpose" placeholder="Zweck" list="logo-purpose-suggestions" class="w-40 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <label class="inline-flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-300">
                                <input type="radio" name="primary_logo_selector" :value="'new:' + index" :checked="logo.primary" @change="logos.forEach((l, i) => l.primary = (i === index))" class="text-blue-600 focus:ring-blue-500">
                                Als primär setzen
                            </label>
                            <button type="button" x-show="logos.length > 1" @click="logos.splice(index, 1)" class="ml-auto px-2 py-1 text-red-500 hover:text-red-700 text-lg" title="Entfernen">&times;</button>
                        </div>
                    </div>
                </template>

                <button type="button" @click="logos.push({ comment: '', purpose: '', primary: false })" class="inline-flex items-center text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
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
        <form method="POST" action="{{ route('admin.artworks.destroy', $artwork) }}" onsubmit="return confirm('Artwork wirklich löschen? Alle zugehörigen Bildvarianten und Logos werden ebenfalls gelöscht.')">
            @csrf
            @method('DELETE')
            <h3 class="text-sm font-semibold text-red-600 mb-2">Gefahrenzone</h3>
            <p class="text-sm text-gray-500 mb-3">Das Artwork und alle zugehörigen Bildvarianten und Logos werden unwiderruflich gelöscht.</p>
            <button type="submit" class="px-5 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700">Artwork löschen</button>
        </form>
    </div>
</div>
@endsection
