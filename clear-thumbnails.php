<?php
/**
 * Clear Thumbnails Utility
 * Removes all cached thumbnails to force regeneration
 */

// Define the cache directories
$cache_dir = __DIR__ . '/cache/';
$directories = [
    $cache_dir . 'thumbnails/',
    $cache_dir . 'posters/'
];

echo "=== Memorians POC Thumbnail Cleanup ===\n\n";

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        echo "Directory not found: $dir\n";
        continue;
    }

    echo "Cleaning: $dir\n";
    $count = 0;

    // Recursively find and delete image files
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $path) {
        if ($path->isFile()) {
            $ext = strtolower($path->getExtension());
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                if (unlink($path->getPathname())) {
                    $count++;
                    echo "  Deleted: " . basename($path->getPathname()) . "\n";
                }
            }
        }
    }

    echo "  Total files deleted: $count\n\n";
}

// Clear any temporary files
$temp_dir = $cache_dir . 'temp/';
if (is_dir($temp_dir)) {
    echo "Cleaning temp directory: $temp_dir\n";
    $temp_count = 0;

    $temp_files = glob($temp_dir . '*');
    foreach ($temp_files as $file) {
        if (is_file($file)) {
            // Only delete files older than 1 hour
            if (time() - filemtime($file) > 3600) {
                if (unlink($file)) {
                    $temp_count++;
                }
            }
        }
    }
    echo "  Deleted $temp_count old temp files\n\n";
}

echo "=== Cleanup Complete ===\n";
echo "\nNote: Thumbnails will be regenerated on next page load.\n";
echo "Clear your browser cache for best results.\n";