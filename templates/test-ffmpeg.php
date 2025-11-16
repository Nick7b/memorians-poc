<?php
/**
 * Test FFmpeg installation and basic functionality
 */

header('Content-Type: text/plain');

echo "=== FFmpeg Test ===\n\n";

// Test 1: Check FFmpeg version
echo "1. Checking FFmpeg installation...\n";
$ffmpeg_version = shell_exec('ffmpeg -version 2>&1');
if ($ffmpeg_version) {
    echo "✓ FFmpeg is installed\n";
    echo substr($ffmpeg_version, 0, 200) . "\n\n";
} else {
    echo "✗ FFmpeg not found\n\n";
}

// Test 2: Check directories
echo "2. Checking directories...\n";
$plugin_dir = dirname(__DIR__);
$cache_dir = $plugin_dir . '/cache/';
$media_images = $plugin_dir . '/media/images/';
$media_videos = $plugin_dir . '/media/videos/';

echo "Plugin dir: $plugin_dir\n";
echo "Cache dir: $cache_dir - " . (is_dir($cache_dir) ? "EXISTS" : "MISSING") . " - " . (is_writable($cache_dir) ? "WRITABLE" : "NOT WRITABLE") . "\n";
echo "Images dir: $media_images - " . (is_dir($media_images) ? "EXISTS" : "MISSING") . "\n";
echo "Videos dir: $media_videos - " . (is_dir($media_videos) ? "EXISTS" : "MISSING") . "\n\n";

// Test 3: Count media files
echo "3. Counting media files...\n";
if (is_dir($media_images)) {
    $images = glob($media_images . '*.{png,jpg,jpeg}', GLOB_BRACE);
    echo "Images found: " . count($images) . "\n";
    if (count($images) > 0) {
        echo "First image: " . basename($images[0]) . "\n";
    }
}
if (is_dir($media_videos)) {
    $videos = glob($media_videos . '*.{mp4,mov,avi}', GLOB_BRACE);
    echo "Videos found: " . count($videos) . "\n";
    if (count($videos) > 0) {
        echo "First video: " . basename($videos[0]) . "\n";
    }
}
echo "\n";

// Test 4: Simple FFmpeg command
echo "4. Testing simple FFmpeg command...\n";
if (is_dir($media_images) && count($images) > 0) {
    $test_image = $images[0];
    $test_output = $cache_dir . 'test_output.mp4';

    echo "Input: $test_image\n";
    echo "Output: $test_output\n";

    $cmd = "ffmpeg -y -loop 1 -i " . escapeshellarg($test_image) . " -t 2 -vf 'scale=1920:1080:force_original_aspect_ratio=decrease,pad=1920:1080:(ow-iw)/2:(oh-ih)/2' -c:v libx264 -preset ultrafast -crf 28 " . escapeshellarg($test_output) . " 2>&1";

    echo "\nExecuting: $cmd\n\n";

    $output = shell_exec($cmd);

    if (file_exists($test_output)) {
        $size = filesize($test_output);
        echo "✓ Video created successfully! Size: " . round($size/1024, 2) . " KB\n";
        echo "Path: $test_output\n";

        // Clean up
        @unlink($test_output);
    } else {
        echo "✗ Video creation failed\n";
        echo "FFmpeg output:\n";
        echo $output;
    }
}

echo "\n=== End Test ===\n";
exit;
