<?php

// Temporary deploy script - DELETE AFTER USE
if (($_GET['token'] ?? '') !== 'tyl-deploy-2026') {
    http_response_code(404);
    exit('Not found');
}

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "<pre style='font-family:monospace;background:#111;color:#0f0;padding:20px;'>";
echo "=== TYL Deploy Refresh ===\n\n";

$commands = [
    'route:clear',
    'config:clear',
    'view:clear',
    'cache:clear',
    'migrate --force',
];

foreach ($commands as $cmd) {
    echo "> php artisan $cmd\n";
    try {
        Illuminate\Support\Facades\Artisan::call($cmd);
        echo Illuminate\Support\Facades\Artisan::output();
    } catch (\Throwable $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

echo "=== DONE ===\n";
echo "</pre>";
