<?php
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');

echo "=== VS08 DIAGNOSTIC ===\n\n";

$file = __DIR__ . '/single-vs08_voyage.php';
echo "Template: $file\n";
echo "Exists: " . (file_exists($file) ? 'YES' : 'NO') . "\n";
echo "Size: " . (file_exists($file) ? filesize($file) . ' bytes' : 'N/A') . "\n";
echo "Modified: " . (file_exists($file) ? date('Y-m-d H:i:s', filemtime($file)) : 'N/A') . "\n";
echo "Contains svVolReady: " . (file_exists($file) && strpos(file_get_contents($file), 'svVolReady') !== false ? 'YES' : 'NO') . "\n";
echo "Contains v5: " . (file_exists($file) && strpos(file_get_contents($file), 'v5') !== false ? 'YES' : 'NO') . "\n\n";

echo "PHP Dir: " . __DIR__ . "\n";
echo "Theme Dir: " . (function_exists('get_template_directory') ? 'N/A (not loaded WP)' : 'N/A') . "\n";
echo "OPcache: " . (function_exists('opcache_get_status') ? 'Available' : 'Not available') . "\n";

if (function_exists('opcache_get_status')) {
    $s = @opcache_get_status(false);
    if ($s) {
        echo "OPcache enabled: " . ($s['opcache_enabled'] ? 'YES' : 'NO') . "\n";
        echo "Cached scripts: " . ($s['opcache_statistics']['num_cached_scripts'] ?? '?') . "\n";
    }
}

echo "\nTimestamp: " . date('Y-m-d H:i:s') . "\n";
echo "Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'unknown') . "\n";
