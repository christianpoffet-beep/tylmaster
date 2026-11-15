<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DeployFinisher;
use Illuminate\Support\Facades\Log;

/**
 * Runs pending migrations and clears the caches after a deploy.
 *
 * The hosting has no SSH access, so this replaces the artisan commands that
 * would otherwise be run on the server. It sits behind auth and an admin check
 * instead of the shared token the earlier deploy scripts used.
 *
 * Normally the FinishDeploy middleware does this on its own after a deploy;
 * the button is for a second try or a run on demand.
 */
class DeployController extends Controller
{
    public function refresh(DeployFinisher $finisher)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $result = $finisher->run('button');

        Log::info('Deploy refresh ausgefuehrt', ['user_id' => auth()->id(), 'ok' => $result['ok']]);

        return back()->with('deployOutput', $result['output']);
    }
}
