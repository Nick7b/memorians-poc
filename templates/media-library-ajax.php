<?php
/**
 * AJAX handler for media library
 * Returns all available media files
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');

// Debug: Log that this endpoint was hit
error_log('Media Library endpoint accessed');

try {
    // Check if classes are loaded
    if (!class_exists('Memorians_POC_Media_Selector')) {
        throw new Exception('Memorians_POC_Media_Selector class not found');
    }

    $media_selector = new Memorians_POC_Media_Selector();
    $media_library = $media_selector->get_all_media();

    if (is_wp_error($media_library)) {
        echo json_encode(array(
            'success' => false,
            'error' => $media_library->get_error_message()
        ));
        exit;
    }

    echo json_encode(array(
        'success' => true,
        'data' => $media_library
    ));

} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ));
}

exit;
