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
    'database/migrations/2026_04_03_231352_add_qr_code_path_to_invoice_templates.php',
    'database/migrations/2026_04_03_232544_add_qr_code_path_to_invoices.php',
    'database/migrations/2026_04_03_235839_add_catalogs_to_organizations.php',
    'resources/views/admin/partials/track-search.blade.php',
    'resources/views/admin/partials/artwork-search.blade.php',
    'resources/views/admin/partials/release-search.blade.php',
    'resources/views/admin/partials/submission-search.blade.php',
    'resources/views/admin/partials/product-type-selector.blade.php',
    'app/Http/Controllers/Admin/ReleaseController.php',
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

    // Step 1: Move any files from public/storage/* into storage/app/public/*
    // so nothing is lost when we replace the dir with a symlink
    $contents = @scandir($linkPath);
    $items = array_diff($contents ?: [], ['.', '..']);
    echo "  Contents to merge: " . implode(', ', $items) . "\n";

    foreach ($items as $item) {
        $src = $linkPath . '/' . $item;
        $dst = $targetPath . '/' . $item;
        if (file_exists($dst)) {
            // Target already has this item — if source is dir, merge recursively
            if (is_dir($src) && is_dir($dst)) {
                echo "  Merging dir: $item (exists in both locations)\n";
                // Copy files from src that don't exist in dst
                $srcFiles = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::SELF_FIRST
                );
                foreach ($srcFiles as $fileInfo) {
                    $relative = substr($fileInfo->getPathname(), strlen($src) + 1);
                    $dstFile = $dst . '/' . $relative;
                    if ($fileInfo->isDir()) {
                        if (!is_dir($dstFile)) @mkdir($dstFile, 0755, true);
                    } elseif (!file_exists($dstFile)) {
                        @copy($fileInfo->getPathname(), $dstFile);
                        echo "    Copied: $item/$relative\n";
                    }
                }
            } else {
                echo "  Skipped: $item (already exists in target)\n";
            }
        } else {
            // Move item to target
            if (@rename($src, $dst)) {
                echo "  Moved: $item\n";
            } else {
                echo "  ERROR moving: $item\n";
            }
        }
    }

    // Step 2: Remove all remaining files from public/storage
    $removeDir = function($dir) use (&$removeDir) {
        foreach (array_diff(scandir($dir), ['.', '..']) as $item) {
            $path = $dir . '/' . $item;
            is_dir($path) ? $removeDir($path) : @unlink($path);
        }
        @rmdir($dir);
    };
    // Re-scan after moves
    $remaining = array_diff(scandir($linkPath) ?: [], ['.', '..']);
    foreach ($remaining as $item) {
        $path = $linkPath . '/' . $item;
        if (is_dir($path)) {
            $removeDir($path);
            echo "  Cleaned up dir: $item\n";
        } else {
            @unlink($path);
            echo "  Cleaned up file: $item\n";
        }
    }

    // Step 3: Remove the now-empty directory and create symlink
    if (@rmdir($linkPath)) {
        echo "  Removed empty directory.\n";
        if (@symlink($targetPath, $linkPath)) {
            echo "  ✓ Symlink created: $linkPath -> $targetPath\n";
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
        echo "  ERROR: Could not remove directory (not empty?).\n";
        $leftover = array_diff(scandir($linkPath) ?: [], ['.', '..']);
        echo "  Remaining: " . implode(', ', $leftover) . "\n";
    }
} elseif (!file_exists($linkPath) && $targetPath) {
    echo "\n> Creating symlink...\n";
    if (@symlink($targetPath, $linkPath)) {
        echo "  ✓ Symlink created: $linkPath -> $targetPath\n";
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
