<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sitzung abgelaufen</title>
    <style>
        :root { --bg: #f9fafb; --card: #ffffff; --text: #111827; --muted: #6b7280; --line: #e5e7eb; --link: #2563eb; }
        @media (prefers-color-scheme: dark) {
            :root { --bg: #111827; --card: #1f2937; --text: #f3f4f6; --muted: #9ca3af; --line: #374151; --link: #60a5fa; }
        }
        body { margin: 0; background: var(--bg); color: var(--text); font: 15px/1.5 system-ui, -apple-system, "Segoe UI", sans-serif; }
        main { max-width: 36rem; margin: 3rem auto; padding: 0 1rem; }
        .card { background: var(--card); border: 1px solid var(--line); border-radius: 12px; padding: 1.5rem; }
        h1 { font-size: 1.25rem; margin: 0 0 .5rem; }
        p { margin: 0 0 1rem; color: var(--muted); }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        td { padding: .35rem 0; border-top: 1px solid var(--line); }
        td:last-child { text-align: right; font-family: ui-monospace, monospace; }
        a { color: var(--link); }
    </style>
</head>
<body>
<main>
    <div class="card">
        <h1>Sitzung abgelaufen (419)</h1>
        <p>Das Formular gehört zu einer Sitzung, die der Server nicht mehr kennt. Bitte zurückgehen, die Seite neu laden (F5) und nochmals senden – nicht diese Seite neu laden.</p>
        <p><a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/admin') }}">← Zurück</a></p>

        <p style="margin-top:1.5rem">Kommt das immer wieder, helfen diese Angaben bei der Suche (sie enthalten nichts Geheimes):</p>
        <table>
            @foreach(\App\Support\SessionDiagnosis::of(request()) as $label => $value)
                <tr><td>{{ $label }}</td><td>{{ $value }}</td></tr>
            @endforeach
        </table>
    </div>
</main>
</body>
</html>
