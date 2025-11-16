<?php
/**
 * AJAX handler for video history/gallery
 * Returns all generated videos with metadata
 */

header('Content-Type: application/json');

try {
    // Handle delete request
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['cache_key'])) {
        $cache_key = sanitize_text_field($_GET['cache_key']);

        $cache_manager = new Memorians_POC_Cache_Manager();
        $result = $cache_manager->delete_video($cache_key);

        echo json_encode($result);
        exit;
    }

    // Handle list request (default)
    $cache_manager = new Memorians_POC_Cache_Manager();
    $videos = $cache_manager->get_all_videos();

    echo json_encode(array(
        'success' => true,
        'videos' => $videos,
        'count' => count($videos)
    ));

} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
}

exit;
