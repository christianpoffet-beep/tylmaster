<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * What has to happen on the server once new code has arrived: pending
 * migrations, then the caches. The hosting has no SSH, so this runs inside the
 * app - from the "Migrationen & Cache" button, and on its own on the first
 * request after a deploy that changed migrations, config or routes.
 */
class DeployFinisher
{
    public const COMMANDS = [
        'migrate' => ['--force' => true],
        'view:clear' => [],
        'cache:clear' => [],
        'route:clear' => [],
        'config:clear' => [],
    ];

    /**
     * Runs the commands and records the result for the current state of the code.
     *
     * @return array{output: array<string, string>, ok: bool}
     */
    public function run(string $trigger): array
    {
        $output = [];
        $ok = true;

        foreach (self::COMMANDS as $command => $parameters) {
            try {
                $exitCode = Artisan::call($command, $parameters);
                $output[$command] = trim(Artisan::output());
                $ok = $ok && $exitCode === 0;
            } catch (\Throwable $e) {
                $ok = false;
                $output[$command] = 'FEHLER: ' . $e->getMessage();
                Log::error("Deploy refresh failed on '{$command}'", ['error' => $e->getMessage()]);
            }
        }

        $this->writeMarker([
            'fingerprint' => $this->fingerprint(),
            'ok' => $ok,
            'trigger' => $trigger,
            'at' => now()->toIso8601String(),
            'output' => $output,
        ]);

        return ['output' => $output, 'ok' => $ok];
    }

    /**
     * Finishes a fresh deploy exactly once. Stays out of the way when switched
     * off, when this state of the code was already handled - also when that
     * failed: retrying on every request would only repeat the error, the
     * button is there for a second try - while files are still arriving, and
     * while another request is already at it.
     */
    public function finishIfNewDeploy(): void
    {
        if (!config('deploy.auto_finish') || !$this->isNewDeploy()) {
            return;
        }

        if ($this->newestWatchedFile() > time() - (int) config('deploy.settle_seconds', 30)) {
            return;
        }

        $marker = (string) config('deploy.marker');
        if (!is_dir(dirname($marker)) || !is_writable(dirname($marker))) {
            // Without a record every request would run it again.
            Log::warning('Deploy auto-finish skipped: ' . dirname($marker) . ' is not writable.');
            return;
        }

        // A file lock, not a cache lock: cache:clear would wipe the latter mid-run.
        $lock = @fopen($marker . '.lock', 'c');
        if (!$lock) {
            return;
        }

        try {
            if (!flock($lock, LOCK_EX | LOCK_NB) || !$this->isNewDeploy()) {
                return;
            }

            $result = $this->run('auto');

            Log::log($result['ok'] ? 'info' : 'error', 'Deploy automatisch abgeschlossen', $result);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /** The last recorded run, for the system page. */
    public function lastRun(): ?array
    {
        $marker = (string) config('deploy.marker');
        if (!is_file($marker)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($marker), true);

        return is_array($data) ? $data : null;
    }

    private function isNewDeploy(): bool
    {
        return ($this->lastRun()['fingerprint'] ?? null) !== $this->fingerprint();
    }

    /**
     * Migrations count by name - a new one is what matters. Config and routes
     * by modification time, since their caches go stale when they change.
     */
    private function fingerprint(): string
    {
        $parts = array_map('basename', glob(database_path('migrations/*.php')) ?: []);

        foreach ($this->cachedSources() as $file) {
            $parts[] = basename(dirname($file)) . '/' . basename($file) . ':' . @filemtime($file);
        }

        sort($parts);

        return sha1(implode("\n", $parts));
    }

    private function cachedSources(): array
    {
        return array_merge(glob(config_path('*.php')) ?: [], glob(base_path('routes/*.php')) ?: []);
    }

    private function newestWatchedFile(): int
    {
        $files = array_merge(glob(database_path('migrations/*.php')) ?: [], $this->cachedSources());

        return $files ? max(array_map(fn ($file) => (int) @filemtime($file), $files)) : 0;
    }

    private function writeMarker(array $data): void
    {
        $marker = (string) config('deploy.marker');

        if (@file_put_contents($marker, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX) === false) {
            Log::warning("Deploy marker could not be written to {$marker}.");
        }
    }
}
