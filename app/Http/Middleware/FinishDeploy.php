<?php

namespace App\Http\Middleware;

use App\Services\DeployFinisher;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Before the first request after a deploy is handled, its migrations run -
 * so that request already sees the new tables. Nearly always a no-op: one
 * directory listing and a few file times.
 */
class FinishDeploy
{
    public function __construct(private DeployFinisher $finisher)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        try {
            $this->finisher->finishIfNewDeploy();
        } catch (\Throwable $e) {
            // Finishing a deploy must never be the reason a page does not load.
            Log::error('Deploy auto-finish crashed', ['error' => $e->getMessage()]);
        }

        return $next($request);
    }
}
