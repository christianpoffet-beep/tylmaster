<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Runs pending migrations and clears the caches after a deploy.
 *
 * The hosting has no SSH access, so this replaces the artisan commands that
 * would otherwise be run on the server. It sits behind auth and an admin check
 * instead of the shared token the earlier deploy scripts used.
 */
class DeployController extends Controller
{
    protected const COMMANDS = [
        'migrate' => ['--force' => true],
        'view:clear' => [],
        'cache:clear' => [],
        'route:clear' => [],
        'config:clear' => [],
    ];

    public function refresh()
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $output = [];

        foreach (self::COMMANDS as $command => $parameters) {
            try {
                Artisan::call($command, $parameters);
                $output[$command] = trim(Artisan::output());
            } catch (\Throwable $e) {
                $output[$command] = 'FEHLER: ' . $e->getMessage();
                Log::error("Deploy refresh failed on '{$command}'", ['error' => $e->getMessage()]);
            }
        }

        Log::info('Deploy refresh ausgefuehrt', ['user_id' => auth()->id()]);

        return back()->with('deployOutput', $output);
    }
}
