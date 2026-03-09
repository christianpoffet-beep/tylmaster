<?php
/**
 * TYL Admin – Setup Script fuer Shared Hosting (Metanet)
 *
 * Dieses Script fuehrt die noetigsten Artisan-Befehle aus,
 * wenn kein SSH/Terminal verfuegbar ist.
 *
 * Aufruf: https://admin.theyellinglight.ch/setup.php?token=DEIN_TOKEN
 *
 * WICHTIG: Nach erfolgreichem Setup dieses File SOFORT loeschen!
 */

// Sicherheitstoken – aendere diesen Wert vor dem Upload!
$SETUP_TOKEN = 'tyl-setup-2026-CHANGE-ME';

if (!isset($_GET['token']) || $_GET['token'] !== $SETUP_TOKEN) {
    http_response_code(403);
    die('Zugriff verweigert. Token fehlt oder ist falsch.');
}

// Laravel Bootstrap
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$output = [];
$step = $_GET['step'] ?? 'menu';

function runArtisan(string $command, array $params = []): string
{
    $exitCode = Artisan::call($command, $params);
    return Artisan::output();
}

header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>TYL Setup</title>';
echo '<style>body{font-family:monospace;max-width:800px;margin:40px auto;padding:0 20px;background:#1a1a2e;color:#e0e0e0}';
echo 'h1{color:#00d4aa}a{color:#4ecdc4;text-decoration:none}a:hover{text-decoration:underline}';
echo 'pre{background:#16213e;padding:15px;border-radius:8px;overflow-x:auto;border:1px solid #333}';
echo '.ok{color:#00d4aa}.err{color:#e74c3c}.warn{color:#f39c12}';
echo '.btn{display:inline-block;padding:10px 20px;background:#4ecdc4;color:#1a1a2e;border-radius:6px;font-weight:bold;margin:5px}</style></head><body>';
echo '<h1>TYL Admin – Setup</h1>';

$baseUrl = "?token=" . urlencode($SETUP_TOKEN);

if ($step === 'menu') {
    echo '<p>Waehle einen Schritt:</p>';
    echo '<p><a class="btn" href="' . $baseUrl . '&step=check">1. System-Check</a></p>';
    echo '<p><a class="btn" href="' . $baseUrl . '&step=migrate">2. Migrationen ausfuehren</a></p>';
    echo '<p><a class="btn" href="' . $baseUrl . '&step=seed">3. Seeders ausfuehren (Testdaten)</a></p>';
    echo '<p><a class="btn" href="' . $baseUrl . '&step=storage">4. Storage Link erstellen</a></p>';
    echo '<p><a class="btn" href="' . $baseUrl . '&step=cache">5. Caches erstellen (Produktion)</a></p>';
    echo '<p><a class="btn" href="' . $baseUrl . '&step=clear">6. Caches leeren</a></p>';
    echo '<p><a class="btn" href="' . $baseUrl . '&step=all">ALLES ausfuehren (1-5)</a></p>';
    echo '<hr><p class="warn">WICHTIG: Loesche diese Datei nach dem Setup!</p>';
}

elseif ($step === 'check') {
    echo '<h2>System-Check</h2><pre>';
    echo "PHP Version: " . phpversion() . "\n";
    echo "Laravel Version: " . app()->version() . "\n";
    echo "APP_ENV: " . config('app.env') . "\n";
    echo "APP_DEBUG: " . (config('app.debug') ? 'true' : 'false') . "\n";
    echo "DB_CONNECTION: " . config('database.default') . "\n";

    // DB-Verbindung testen
    try {
        \DB::connection()->getPdo();
        echo '<span class="ok">DB-Verbindung: OK</span>' . "\n";
    } catch (\Exception $e) {
        echo '<span class="err">DB-Verbindung: FEHLER – ' . $e->getMessage() . '</span>' . "\n";
    }

    // Verzeichnis-Berechtigungen
    $dirs = ['storage/app', 'storage/framework/cache', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs', 'bootstrap/cache'];
    foreach ($dirs as $dir) {
        $path = base_path($dir);
        $writable = is_writable($path);
        echo ($writable ? '<span class="ok">OK</span>' : '<span class="err">NICHT BESCHREIBBAR</span>') . "  $dir\n";
    }

    echo '</pre>';
    echo '<p><a class="btn" href="' . $baseUrl . '&step=menu">Zurueck</a></p>';
}

elseif ($step === 'migrate') {
    echo '<h2>Migrationen</h2><pre>';
    echo htmlspecialchars(runArtisan('migrate', ['--force' => true]));
    echo '</pre>';
    echo '<p class="ok">Migrationen abgeschlossen.</p>';
    echo '<p><a class="btn" href="' . $baseUrl . '&step=menu">Zurueck</a></p>';
}

elseif ($step === 'seed') {
    echo '<h2>Seeders (Testdaten)</h2><pre>';

    $seeders = [
        'DatabaseSeeder'            => 'Admin-User',
        'ContactTypeSeeder'         => 'Kontakttypen',
        'ProjectTypeSeeder'         => 'Projekttypen',
        'ContractTypeSeeder'        => 'Vertragstypen',
        'ChartTemplateSeeder'       => 'Kontenplan Musiklabel/Verein',
        'ChartTemplateBandSeeder'   => 'Kontenplan Band',
        'ChartTemplateMusicVideoSeeder' => 'Kontenplan Musikvideo',
        'ContractTemplateSeeder'    => 'Vertragsvorlagen',
    ];

    foreach ($seeders as $class => $label) {
        echo "Seeder: $label ($class)...\n";
        try {
            echo htmlspecialchars(runArtisan('db:seed', ['--class' => $class, '--force' => true]));
            echo '<span class="ok">OK</span>' . "\n\n";
        } catch (\Exception $e) {
            echo '<span class="err">FEHLER: ' . htmlspecialchars($e->getMessage()) . '</span>' . "\n\n";
        }
    }

    echo '</pre>';
    echo '<p class="ok">Seeders abgeschlossen.</p>';
    echo '<p><a class="btn" href="' . $baseUrl . '&step=menu">Zurueck</a></p>';
}

elseif ($step === 'storage') {
    echo '<h2>Storage Link</h2><pre>';
    try {
        echo htmlspecialchars(runArtisan('storage:link'));
        echo '<span class="ok">OK</span>';
    } catch (\Exception $e) {
        echo '<span class="warn">Hinweis: ' . htmlspecialchars($e->getMessage()) . '</span>';
    }
    echo '</pre>';
    echo '<p><a class="btn" href="' . $baseUrl . '&step=menu">Zurueck</a></p>';
}

elseif ($step === 'cache') {
    echo '<h2>Caches erstellen</h2><pre>';
    echo "Config Cache...\n" . htmlspecialchars(runArtisan('config:cache'));
    echo "Route Cache...\n" . htmlspecialchars(runArtisan('route:cache'));
    echo "View Cache...\n" . htmlspecialchars(runArtisan('view:cache'));
    echo '</pre>';
    echo '<p class="ok">Caches erstellt.</p>';
    echo '<p><a class="btn" href="' . $baseUrl . '&step=menu">Zurueck</a></p>';
}

elseif ($step === 'clear') {
    echo '<h2>Caches leeren</h2><pre>';
    echo htmlspecialchars(runArtisan('config:clear'));
    echo htmlspecialchars(runArtisan('route:clear'));
    echo htmlspecialchars(runArtisan('view:clear'));
    echo htmlspecialchars(runArtisan('cache:clear'));
    echo '</pre>';
    echo '<p class="ok">Alle Caches geleert.</p>';
    echo '<p><a class="btn" href="' . $baseUrl . '&step=menu">Zurueck</a></p>';
}

elseif ($step === 'all') {
    echo '<h2>Komplettes Setup</h2><pre>';

    // 1. Migrate
    echo "<strong>== Migrationen ==</strong>\n";
    echo htmlspecialchars(runArtisan('migrate', ['--force' => true]));
    echo "\n";

    // 2. Seed all
    echo "<strong>== Seeders ==</strong>\n";
    $seeders = [
        'DatabaseSeeder', 'ContactTypeSeeder', 'ProjectTypeSeeder',
        'ContractTypeSeeder', 'ChartTemplateSeeder', 'ChartTemplateBandSeeder',
        'ChartTemplateMusicVideoSeeder', 'ContractTemplateSeeder',
    ];
    foreach ($seeders as $class) {
        echo "  $class... ";
        try {
            runArtisan('db:seed', ['--class' => $class, '--force' => true]);
            echo '<span class="ok">OK</span>' . "\n";
        } catch (\Exception $e) {
            echo '<span class="err">' . htmlspecialchars($e->getMessage()) . '</span>' . "\n";
        }
    }
    echo "\n";

    // 3. Storage link
    echo "<strong>== Storage Link ==</strong>\n";
    try {
        echo htmlspecialchars(runArtisan('storage:link'));
    } catch (\Exception $e) {
        echo '<span class="warn">' . htmlspecialchars($e->getMessage()) . '</span>' . "\n";
    }
    echo "\n";

    // 4. Cache
    echo "<strong>== Caches ==</strong>\n";
    echo htmlspecialchars(runArtisan('config:cache'));
    echo htmlspecialchars(runArtisan('route:cache'));
    echo htmlspecialchars(runArtisan('view:cache'));

    echo '</pre>';
    echo '<p class="ok">Setup abgeschlossen!</p>';
    echo '<p class="warn">WICHTIG: Loesche jetzt diese Datei (setup.php) vom Server!</p>';
    echo '<p><a class="btn" href="' . $baseUrl . '&step=menu">Zurueck</a></p>';
}

echo '</body></html>';
