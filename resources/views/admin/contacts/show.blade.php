@extends('admin.layouts.app')

@section('title', $contact->full_name)

@section('content')
<div class="flex flex-col lg:flex-row gap-6">
    <!-- Main Info -->
    <div class="flex-1">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex justify-between items-start mb-4">
                <div class="flex items-center gap-4">
                    @if($contact->avatar_path)
                        <img src="{{ Storage::url($contact->avatar_path) }}" alt="{{ $contact->full_name }}" class="w-16 h-16 rounded-full object-cover border border-gray-200 dark:border-gray-700">
                    @else
                        <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-400 text-xl font-bold border border-gray-200 dark:border-gray-700">
                            {{ strtoupper(substr($contact->first_name, 0, 1)) }}{{ strtoupper(substr($contact->last_name, 0, 1)) }}
                        </div>
                    @endif
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $contact->full_name }}
                            <span class="text-sm font-normal text-gray-400 ml-2">{{ $contact->ref_nr }}</span>
                        </h2>
                        @if($contact->birth_date || $contact->death_date)
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                @if($contact->birth_date)* {{ $contact->birth_date->format('d.m.Y') }}@endif
                                @if($contact->death_date) &dagger; {{ $contact->death_date->format('d.m.Y') }}@endif
                            </p>
                        @endif
                    </div>
                </div>
                <div class="flex flex-wrap gap-1 justify-end">
                    @foreach($contact->types ?? [] as $type)
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">{{ ucfirst($type) }}</span>
                    @endforeach
                </div>
            </div>

            <div class="space-y-3 text-sm">
                {{-- E-Mails --}}
                @if($contact->email)
                <div>
                    <span class="text-gray-500 dark:text-gray-400">E-Mail:</span>
                    <a href="mailto:{{ $contact->email }}" class="text-blue-600 dark:text-blue-400 ml-1">{{ $contact->email }}</a>
                    <span class="text-xs text-gray-400 ml-1">(primär)</span>
                </div>
                @endif
                @foreach($contact->secondary_emails ?? [] as $secEmail)
                <div>
                    <span class="text-gray-500 dark:text-gray-400">E-Mail:</span>
                    <a href="mailto:{{ $secEmail }}" class="text-blue-600 dark:text-blue-400 ml-1">{{ $secEmail }}</a>
                </div>
                @endforeach

                {{-- Telefone --}}
                @if($contact->phone)
                <div>
                    <span class="text-gray-500 dark:text-gray-400">Telefon:</span>
                    <span class="text-gray-900 dark:text-gray-100 ml-1">{{ $contact->phone }}</span>
                    <span class="text-xs text-gray-400 ml-1">(primär)</span>
                </div>
                @endif
                @foreach($contact->secondary_phones ?? [] as $secPhone)
                <div>
                    <span class="text-gray-500 dark:text-gray-400">Telefon:</span>
                    <span class="text-gray-900 dark:text-gray-100 ml-1">{{ $secPhone }}</span>
                </div>
                @endforeach

                {{-- Adresse --}}
                @if($contact->street || $contact->city)
                <div>
                    <span class="text-gray-500 dark:text-gray-400">Adresse:</span>
                    <span class="text-gray-900 dark:text-gray-100 ml-1">
                        {{ $contact->street }}{{ $contact->street && $contact->city ? ', ' : '' }}
                        {{ $contact->zip }} {{ $contact->city }}
                        {{ $contact->country ? '(' . $contact->country . ')' : '' }}
                    </span>
                </div>
                @endif
            </div>

            {{-- Bankverbindung --}}
            @if($contact->iban || $contact->bank_name)
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Bankverbindung</h3>
                <div class="space-y-1 text-sm">
                    @if($contact->iban)
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">IBAN:</span>
                        <span class="text-gray-900 dark:text-gray-100 ml-1 font-mono">{{ $contact->iban }}</span>
                    </div>
                    @endif
                    @if($contact->bank_name)
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Bank:</span>
                        <span class="text-gray-900 dark:text-gray-100 ml-1">{{ $contact->bank_name }}</span>
                    </div>
                    @endif
                    @if($contact->bic)
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">BIC/SWIFT:</span>
                        <span class="text-gray-900 dark:text-gray-100 ml-1 font-mono">{{ $contact->bic }}</span>
                    </div>
                    @endif
                    @if($contact->bank_zip || $contact->bank_city || $contact->bank_country)
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Bankadresse:</span>
                        <span class="text-gray-900 dark:text-gray-100 ml-1">
                            {{ $contact->bank_zip }} {{ $contact->bank_city }}
                            {{ $contact->bank_country ? '(' . $contact->bank_country . ')' : '' }}
                        </span>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            @if($contact->tags->count())
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Tags</h3>
                <div class="flex flex-wrap gap-1.5">
                    @foreach($contact->tags as $tag)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">{{ $tag->name }}</span>
                    @endforeach
                </div>
            </div>
            @endif

            @if($contact->genres->count())
            <div class="mt-3 flex flex-wrap gap-1.5">
                @foreach($contact->genres as $genre)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300">{{ $genre->name }}</span>
                @endforeach
            </div>
            @endif

            @if($contact->notes)
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">Notizen</h3>
                <p class="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-line">{{ $contact->notes }}</p>
            </div>
            @endif

            @if($contact->ipis && count($contact->ipis))
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">IPI</h3>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-500 dark:text-gray-400 uppercase">
                            <th class="pb-1 pr-4">IPI-Nr.</th>
                            <th class="pb-1 pr-4">IPI Name</th>
                            <th class="pb-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($contact->ipis as $ipi)
                        <tr>
                            <td class="py-0.5 pr-4 text-gray-900 dark:text-gray-100 font-mono">{{ $ipi['number'] ?? '' }}</td>
                            <td class="py-0.5 pr-4 text-gray-900 dark:text-gray-100">{{ $ipi['name'] ?? '' }}</td>
                            <td class="py-0.5">
                                @if(!empty($ipi['primary']))
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300">primär</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        <div class="mt-4 flex gap-3">
            <a href="{{ route('admin.contacts.edit', $contact) }}" class="px-4 py-2 bg-gray-800 dark:bg-gray-600 text-white text-sm rounded-lg hover:bg-gray-700 dark:hover:bg-gray-50 dark:hover:bg-gray-700/500">Bearbeiten</a>
            <form method="POST" action="{{ route('admin.contacts.destroy', $contact) }}" onsubmit="return confirm('Kontakt wirklich löschen?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-red-600 dark:bg-red-700 text-white text-sm rounded-lg hover:bg-red-700 dark:hover:bg-red-600">Löschen</button>
            </form>
        </div>
    </div>

    <!-- Sidebar: Related Items -->
    <div class="w-full lg:w-80 space-y-4">
        <!-- Organizations -->
        @if($contact->organizations->count())
        <x-admin.collapsible-card title="Organisationen" :count="$contact->organizations->count()">
            @php
                $orgTypeLabels = ['band' => 'Band', 'label' => 'Label', 'publishing' => 'Publishing', 'venue' => 'Location/Venue', 'event_festival' => 'Veranstalter/Event/Festival', 'media' => 'Media', 'oma' => 'OMA-Kontakt'];
                $orgTypeColors = ['band' => 'bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300', 'label' => 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300', 'publishing' => 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300', 'venue' => 'bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300', 'event_festival' => 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-700 dark:text-yellow-300', 'media' => 'bg-pink-100 dark:bg-pink-900/50 text-pink-700 dark:text-pink-300', 'oma' => 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'];
            @endphp
            @foreach($contact->organizations as $org)
                <div class="flex items-center gap-2 py-1">
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium {{ $orgTypeColors[$org->type] ?? 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300' }}">{{ $orgTypeLabels[$org->type] ?? $org->type }}</span>
                    <a href="{{ route('admin.organizations.show', $org) }}" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">{{ $org->primary_name }}</a>
                </div>
            @endforeach
        </x-admin.collapsible-card>
        @endif

        <!-- Contracts -->
        <x-admin.collapsible-card title="Verträge" :count="$contact->contracts->count()">
            @forelse($contact->contracts as $contract)
                <a href="{{ route('admin.contracts.show', $contract) }}" class="block text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 py-1">{{ $contract->title }}</a>
            @empty
                <p class="text-xs text-gray-400">Keine Verträge</p>
            @endforelse
        </x-admin.collapsible-card>

        <!-- Projects -->
        <x-admin.collapsible-card title="Projekte" :count="$contact->projects->count()">
            @forelse($contact->projects as $project)
                <a href="{{ route('admin.projects.show', $project) }}" class="block text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 py-1">{{ $project->name }}</a>
            @empty
                <p class="text-xs text-gray-400">Keine Projekte</p>
            @endforelse
        </x-admin.collapsible-card>

        <!-- Invoices -->
        <x-admin.collapsible-card title="Rechnungen" :count="$contact->invoices->count()">
            @forelse($contact->invoices as $invoice)
                <a href="{{ route('admin.invoices.show', $invoice) }}" class="block text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 py-1">{{ $invoice->invoice_number }} — {{ number_format($invoice->amount, 2) }} {{ $invoice->currency }}</a>
            @empty
                <p class="text-xs text-gray-400">Keine Rechnungen</p>
            @endforelse
        </x-admin.collapsible-card>

        <!-- Tracks -->
        <x-admin.collapsible-card title="Tracks" :count="$contact->tracks->count()">
            @forelse($contact->tracks as $track)
                <a href="{{ route('admin.tracks.show', $track) }}" class="block text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 py-1">{{ $track->title }}</a>
            @empty
                <p class="text-xs text-gray-400">Keine Tracks</p>
            @endforelse
        </x-admin.collapsible-card>
    </div>
</div>
@endsection
