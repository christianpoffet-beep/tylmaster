<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Why a form came back with 419 ("Page Expired"): whether the session cookie
 * arrived (and only once), whether the session behind it was found, how the
 * cookie is set up. Shown on the 419 page and to the Rebeat upload - names
 * and yes/no only, no cookie values.
 */
class SessionDiagnosis
{
    /** @return array<string, string> */
    public static function of(Request $request): array
    {
        $yes = fn (bool $value) => $value ? 'ja' : 'nein';
        $cookie = (string) config('session.cookie');
        $guard = auth()->guard('web');

        // The raw header shows a cookie of the same name sent twice (e.g. one of another site on the parent domain)
        $sent = preg_match_all('/(?:^|;\s*)' . preg_quote($cookie, '/') . '=/', (string) $request->header('Cookie'));
        $session = $request->hasSession() ? $request->session() : null;

        return [
            'Adresse' => $request->getSchemeAndHttpHost(),
            'Session-Cookie' => match (true) { $sent === 0 => 'nein', $sent === 1 => 'ja', default => "{$sent}× (doppelt)" },
            'Sitzung gefunden' => $yes($session && ($session->has('_previous') || $session->has($guard->getName()))),
            'Angemeldet bleiben' => $yes($request->cookies->has($guard->getRecallerName())),
            'Token gesendet' => $yes(filled($request->input('_token')) || filled($request->header('X-CSRF-TOKEN'))),
            'Cookie-Name' => $cookie,
            'Cookie-Domain' => (string) (config('session.domain') ?: 'nur dieser Host'),
            'Secure-Cookie' => $yes((bool) config('session.secure')),
            'Grösse' => round((int) $request->server('CONTENT_LENGTH') / 1024) . ' KB',
        ];
    }
}
