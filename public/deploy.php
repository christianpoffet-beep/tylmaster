<?php
/**
 * TYL Admin - Deployment Script
 *
 * Aufruf: https://dev.admin.theyellinglight.ch/deploy.php?token=tyl-deploy-2026
 * Nach erfolgreichem Setup diese Datei vom Server löschen!
 */

$secret = 'tyl-deploy-2026';
if (($_GET['token'] ?? '') !== $secret) {
    http_response_code(403);
    die('Forbidden. Use ?token=tyl-deploy-2026');
}

// Base path = project root (one level up from public/)
$basePath = dirname(__DIR__);

set_time_limit(300);
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>\n";
echo "=== TYL Admin Deploy ===\n";
echo "Base path: $basePath\n";
echo "PHP Version: " . PHP_VERSION . "\n";

// Find the correct PHP CLI binary
// PHP_BINARY returns php-fpm on Plesk, we need the CLI version
$phpVersion = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
$phpCliCandidates = [
    "/opt/plesk/php/{$phpVersion}/bin/php",
    "/usr/local/php{$phpVersion}/bin/php",
    "/usr/bin/php{$phpVersion}",
    "/usr/bin/php",
    "php",
];

$phpBin = null;
foreach ($phpCliCandidates as $candidate) {
    if (file_exists($candidate) && is_executable($candidate)) {
        $phpBin = $candidate;
        break;
    }
}
if (!$phpBin) {
    // Last resort: try to find via exec
    exec("which php 2>/dev/null", $phpWhich);
    $phpBin = !empty($phpWhich) ? trim($phpWhich[0]) : 'php';
}
echo "PHP CLI Binary: $phpBin\n";

// Verify it's actually CLI
exec(escapeshellarg($phpBin) . ' -v 2>&1', $phpVerOut);
echo "PHP CLI Version: " . ($phpVerOut[0] ?? 'unknown') . "\n\n";

// Step 0: Setup composer command
if (!file_exists($basePath . '/composer.phar')) {
    echo "Lade composer.phar herunter...\n";
    flush();
    copy('https://getcomposer.org/composer-stable.phar', $basePath . '/composer.phar');
}
$composerCmd = escapeshellarg($phpBin) . ' ' . escapeshellarg($basePath . '/composer.phar');
echo "Composer command: $composerCmd\n\n";

// Step 1: Check if .env exists
echo "--- Step 1: .env Check ---\n";
if (!file_exists($basePath . '/.env')) {
    if (file_exists($basePath . '/.env.production')) {
        copy($basePath . '/.env.production', $basePath . '/.env');
        echo "✓ .env.production kopiert nach .env\n";
    } else {
        echo "✗ Keine .env und keine .env.production gefunden!\n";
        echo "Lade .env.production per FTP nach: $basePath/\n";
        die("Abbruch.");
    }
} else {
    echo "✓ .env existiert\n";
}

// Step 2: Composer
echo "\n--- Step 2: Composer ---\n";
if (!file_exists($basePath . '/vendor/autoload.php')) {
    echo "Composer install läuft... (kann 1-2 Minuten dauern)\n";
    flush();
    $output = [];
    $exitCode = 0;
    exec('cd ' . escapeshellarg($basePath) . ' && ' . $composerCmd . ' install --no-dev --optimize-autoloader 2>&1', $output, $exitCode);
    echo implode("\n", $output) . "\n";
    if ($exitCode !== 0) {
        die("✗ Composer install fehlgeschlagen (Exit: $exitCode)");
    }
    echo "✓ Composer install erfolgreich\n";
} else {
    echo "✓ vendor/ existiert bereits\n";
    echo "Update läuft...\n";
    flush();
    $output = [];
    exec('cd ' . escapeshellarg($basePath) . ' && ' . $composerCmd . ' install --no-dev --optimize-autoloader 2>&1', $output, $exitCode);
    echo implode("\n", array_slice($output, -5)) . "\n";
    echo "✓ Composer update erfolgreich\n";
}

// Step 3: Laravel Bootstrap
echo "\n--- Step 3: Laravel Bootstrap ---\n";
require $basePath . '/vendor/autoload.php';
$app = require_once $basePath . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
echo "✓ Laravel gebootet\n";

// Step 4: Storage link
echo "\n--- Step 4: Storage Link ---\n";
if (!is_link($basePath . '/public/storage')) {
    Illuminate\Support\Facades\Artisan::call('storage:link');
    echo Illuminate\Support\Facades\Artisan::output();
}
echo "✓ Storage link OK\n";

// Step 5: Migrations
echo "\n--- Step 5: Migrations ---\n";
try {
    Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    echo Illuminate\Support\Facades\Artisan::output();
    echo "✓ Migrations erfolgreich\n";
} catch (Exception $e) {
    echo "✗ Migration Fehler: " . $e->getMessage() . "\n";
    echo "Prüfe deine DB-Zugangsdaten in .env!\n";
}

// Step 6: Cache
echo "\n--- Step 6: Cache ---\n";
Illuminate\Support\Facades\Artisan::call('config:cache');
echo "✓ Config cached\n";
Illuminate\Support\Facades\Artisan::call('route:cache');
echo "✓ Routes cached\n";
Illuminate\Support\Facades\Artisan::call('view:cache');
echo "✓ Views cached\n";

// Step 7: Create admin user if none exists
echo "\n--- Step 7: Admin User ---\n";
try {
    $userCount = \App\Models\User::count();
    if ($userCount === 0) {
        \App\Models\User::create([
            'name' => 'Admin',
            'email' => 'admin@theyellinglight.ch',
            'password' => bcrypt('password'),
        ]);
        echo "✓ Admin-User erstellt (admin@theyellinglight.ch / password)\n";
        echo "⚠ Passwort nach dem ersten Login ändern!\n";
    } else {
        echo "✓ $userCount User vorhanden\n";
    }
} catch (Exception $e) {
    echo "✗ User-Check Fehler: " . $e->getMessage() . "\n";
}

echo "\n=== Deploy abgeschlossen ===\n";
echo "\n⚠ DIESE DATEI (deploy.php) NACH ERFOLGREICHEM SETUP VOM SERVER LÖSCHEN!\n";
echo "</pre>";
