<?php
/**
 * Clear stuck generation status
 * Run once then delete this file
 */

require_once('../../../wp-load.php');

$template = 'classic';
$cache_manager = new Memorians_POC_Cache_Manager();
$cache_key = $cache_manager->get_cache_key($template);
$output_path = MEMORIANS_POC_CACHE_DIR . $cache_key . '.mp4';

// Check if video exists
if (file_exists($output_path) && filesize($output_path) > 0) {
    // Update status to completed
    $cache_manager->set_generation_status($template, 'completed', array(
        'video_path' => $output_path,
        'video_url' => $cache_manager->get_video_url($output_path),
        'completion_time' => time(),
        'file_size' => filesize($output_path)
    ));

    echo "Status updated to COMPLETED\n";
    echo "Video URL: " . $cache_manager->get_video_url($output_path) . "\n";
    echo "File size: " . round(filesize($output_path) / 1024 / 1024, 2) . " MB\n";
} else {
    echo "Video file not found or empty\n";
}
