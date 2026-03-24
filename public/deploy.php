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

// Check which files exist on server
echo "--- File Check ---\n";
$files = [
    'app/Http/Controllers/ContentPreviewController.php',
    'app/Http/Controllers/Admin/ContentPostController.php',
    'app/Models/ContentPost.php',
    'database/migrations/2026_03_24_013035_add_share_token_and_image_source_to_content_posts.php',
    'resources/views/admin/content-posts/_image-picker.blade.php',
    'resources/views/admin/content-posts/create.blade.php',
    'resources/views/admin/content-posts/show.blade.php',
    'resources/views/public/content-preview.blade.php',
    'routes/web.php',
];
foreach ($files as $f) {
    $path = base_path($f);
    $exists = file_exists($path);
    $size = $exists ? filesize($path) : 0;
    $mtime = $exists ? date('Y-m-d H:i:s', filemtime($path)) : '-';
    $status = $exists ? "OK ($size bytes, $mtime)" : "MISSING!";
    echo ($exists ? '  ' : '  ') . "$f => $status\n";
}

echo "\n--- Git Info ---\n";
echo "HEAD: " . trim(shell_exec('git rev-parse --short HEAD 2>&1')) . "\n";
echo "Branch: " . trim(shell_exec('git branch --show-current 2>&1')) . "\n";

echo "\n--- Storage Link Check ---\n";
$linkPath = __DIR__ . '/storage';
$targetPath = realpath(__DIR__ . '/../storage/app/public');
echo "Link path: $linkPath\n";
echo "Target path: $targetPath\n";
echo "Link exists: " . (file_exists($linkPath) ? 'YES' : 'NO') . "\n";
echo "Is symlink: " . (is_link($linkPath) ? 'YES' : 'NO') . "\n";
if (is_link($linkPath)) {
    echo "Points to: " . readlink($linkPath) . "\n";
} elseif (is_dir($linkPath)) {
    echo "Is regular dir (not symlink)!\n";
}
echo "Target exists: " . (file_exists($targetPath) ? 'YES' : 'NO') . "\n";
if ($targetPath && is_dir($targetPath)) {
    $files_in_storage = scandir($targetPath);
    echo "Files in target: " . implode(', ', array_diff($files_in_storage, ['.', '..'])) . "\n";
}
echo "APP_URL: " . env('APP_URL') . "\n";
echo "Test URL: " . Illuminate\Support\Facades\Storage::disk('public')->url('test.jpg') . "\n";

// Fix: if public/storage is a regular dir (not symlink), replace with symlink
if (file_exists($linkPath) && !is_link($linkPath) && is_dir($linkPath)) {
    echo "\n> public/storage is a regular dir, replacing with symlink...\n";
    // Check if dir is empty (only . and ..)
    $contents = @scandir($linkPath);
    $isEmpty = !$contents || count(array_diff($contents, ['.', '..'])) === 0;
    if ($isEmpty) {
        if (@rmdir($linkPath)) {
            echo "  Removed empty directory.\n";
            if (@symlink($targetPath, $linkPath)) {
                echo "  Symlink created: $linkPath -> $targetPath\n";
            } else {
                echo "  ERROR: symlink() failed. Trying artisan...\n";
                try {
                    Illuminate\Support\Facades\Artisan::call('storage:link');
                    echo Illuminate\Support\Facades\Artisan::output();
                } catch (\Throwable $e) {
                    echo "  ERROR: " . $e->getMessage() . "\n";
                }
            }
        } else {
            echo "  ERROR: Could not remove directory.\n";
        }
    } else {
        echo "  Dir is NOT empty, not replacing automatically.\n";
        echo "  Contents: " . implode(', ', array_diff($contents, ['.', '..'])) . "\n";
    }
} elseif (!file_exists($linkPath) && $targetPath) {
    echo "\n> Creating symlink...\n";
    if (@symlink($targetPath, $linkPath)) {
        echo "  Symlink created: $linkPath -> $targetPath\n";
    } else {
        echo "  ERROR: symlink() failed.\n";
    }
}

echo "\n--- Cache Clear ---\n";
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
