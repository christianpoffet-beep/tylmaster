{{-- Sending the statements by e-mail: per party an address, subject and text with placeholders, optionally a test run to one address --}}
@php
    $mailStatus = [
        \App\Models\RoyaltyStatementMail::SENT => ['versendet', 'text-green-700 dark:text-green-400'],
        \App\Models\RoyaltyStatementMail::TEST => ['Probe', 'text-gray-500 dark:text-gray-400'],
        \App\Models\RoyaltyStatementMail::FAILED => ['Fehler', 'text-red-600 dark:text-red-400'],
    ];
    $mailInput = 'rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm focus:border-blue-500 focus:ring-blue-500';
@endphp
<details class="{{ $card }} mt-6 overflow-hidden" @if(session('error') && old('subject')) open @endif>
    <summary class="px-5 py-3 cursor-pointer text-sm font-medium text-gray-900 dark:text-gray-100">
        Per E-Mail versenden
        <span class="font-normal text-gray-500 dark:text-gray-400">· {{ $recipients->filter(fn ($r) => $r->last?->status === \App\Models\RoyaltyStatementMail::SENT)->count() }} von {{ $recipients->count() }} versendet</span>
    </summary>
    <form method="POST" action="{{ route('admin.royalties.statements.mail', $statement) }}" class="px-5 pb-5 space-y-4"
          x-data="{ test: @js((bool) old('test', !$final)) }">
        @csrf
        <p class="text-xs text-gray-500 dark:text-gray-400">
            Jede Partei erhält ihr PDF als Anhang, über denselben Absender wie die Kampagnen.
            @unless($final) Ein Entwurf geht nur als Probe an Dich hinaus. @endunless
        </p>

        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full text-sm">
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($recipients as $recipient)
                        @php [$statusLabel, $statusClass] = $recipient->last ? $mailStatus[$recipient->last->status] : [null, null]; @endphp
                        <tr>
                            <td class="px-3 py-2 w-8">
                                <input type="checkbox" name="send[{{ $recipient->key }}]" value="1"
                                       @checked(old("send.{$recipient->key}") !== null || (!old('subject') && $recipient->email && $recipient->last?->status !== \App\Models\RoyaltyStatementMail::SENT))
                                       class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                            </td>
                            <td class="px-3 py-2 text-gray-900 dark:text-gray-100">{{ $recipient->name }}</td>
                            <td class="px-3 py-2">
                                <input type="email" name="email[{{ $recipient->key }}]" value="{{ old("email.{$recipient->key}", $recipient->email) }}" placeholder="keine Adresse"
                                       class="w-64 {{ $mailInput }}">
                            </td>
                            <td class="px-3 py-2 text-xs whitespace-nowrap {{ $statusClass }}">
                                @if($recipient->last)
                                    {{ $statusLabel }} {{ $recipient->last->created_at->timezone('Europe/Zurich')->format('d.m.Y H:i') }}
                                    @if($recipient->last->error) <span class="block">{{ \Illuminate\Support\Str::limit($recipient->last->error, 60) }}</span> @endif
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div>
            <label for="mail-subject" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Betreff</label>
            <input type="text" name="subject" id="mail-subject" value="{{ old('subject', $mailTemplate['subject']) }}" required maxlength="200" class="w-full {{ $mailInput }}">
        </div>
        <div>
            <label for="mail-body" class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 inline-flex items-center gap-1.5">
                Text
                <x-admin.info-tip>
                    Platzhalter werden pro Partei ersetzt:
                    @foreach(\App\Services\Royalties\RoyaltyStatementMailer::PLACEHOLDERS as $placeholder => $meaning)
                        <br><code>{{ $placeholder }}</code> – {{ $meaning }}
                    @endforeach
                    <br>Betreff und Text werden für die nächste Abrechnung gemerkt.
                </x-admin.info-tip>
            </label>
            <textarea name="body" id="mail-body" rows="10" required maxlength="5000" class="w-full font-mono {{ $mailInput }}">{{ old('body', $mailTemplate['body']) }}</textarea>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input type="checkbox" name="test" value="1" x-model="test" @disabled(!$final) @checked(old('test', !$final))
                       class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                Probeversand – alle Mails an:
            </label>
            @unless($final) <input type="hidden" name="test" value="1"> @endunless
            <input type="email" name="test_address" value="{{ old('test_address', auth()->user()?->email) }}" :disabled="!test" class="w-64 {{ $mailInput }}">
            @error('test_address') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700"
                x-text="test ? 'Probe senden' : 'Abrechnungen versenden'"
                @click="if (!test && !confirm('Die Abrechnungen jetzt an die ausgewählten Parteien senden?')) $event.preventDefault()">Senden</button>
    </form>
</details>
