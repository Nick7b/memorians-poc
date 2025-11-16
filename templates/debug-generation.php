<?php
/**
 * Debug generation status and logs
 */

header('Content-Type: text/plain');

$template = isset($_GET['template']) ? sanitize_text_field($_GET['template']) : 'classic';

echo "=== Video Generation Debug ===\n\n";

// Check cache manager
$cache_manager = new Memorians_POC_Cache_Manager();
$cache_key = $cache_manager->get_cache_key($template);

echo "Template: $template\n";
echo "Cache Key: $cache_key\n\n";

// Check video file
$video_path = MEMORIANS_POC_CACHE_DIR . $cache_key . '.mp4';
echo "Expected video path: $video_path\n";
echo "File exists: " . (file_exists($video_path) ? "YES" : "NO") . "\n";

if (file_exists($video_path)) {
    $size = filesize($video_path);
    echo "File size: " . round($size / 1024, 2) . " KB\n";

    // Check if it's a valid MP4
    $fh = fopen($video_path, 'rb');
    $header = fread($fh, 12);
    fclose($fh);
    echo "File header (hex): " . bin2hex($header) . "\n";
    echo "Looks like MP4: " . (strpos($header, 'ftyp') !== false ? "YES" : "NO") . "\n\n";
}

// Check FFmpeg log
$temp_dir = MEMORIANS_POC_CACHE_DIR . 'temp/';
$log_file = $temp_dir . 'ffmpeg_' . $cache_key . '.log';
$cmd_file = $temp_dir . 'command_' . $cache_key . '.txt';

echo "--- FFmpeg Command ---\n";
if (file_exists($cmd_file)) {
    echo file_get_contents($cmd_file) . "\n\n";
} else {
    echo "Command file not found: $cmd_file\n\n";
}

echo "--- FFmpeg Log (last 100 lines) ---\n";
if (file_exists($log_file)) {
    $lines = file($log_file);
    $last_lines = array_slice($lines, -100);
    echo implode('', $last_lines);
} else {
    echo "Log file not found: $log_file\n";
}

echo "\n\n--- Generation Status ---\n";
$status = $cache_manager->get_generation_status($template);
if ($status) {
    print_r($status);
} else {
    echo "No generation status found\n";
}

echo "\n=== End Debug ===\n";
exit;
