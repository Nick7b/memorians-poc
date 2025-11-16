<?php
/**
 * Direct test for media library (bypass WordPress routing)
 * Access at: https://memorians.nl/wp-content/plugins/memorians-poc/test-media-library.php
 */

// Load WordPress
require_once('../../../wp-load.php');

header('Content-Type: application/json');

try {
    if (!class_exists('Memorians_POC_Media_Selector')) {
        echo json_encode(array(
            'success' => false,
            'error' => 'Class not loaded. Plugin might not be active.'
        ));
        exit;
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
        'data' => $media_library,
        'note' => 'Direct test - bypassing WordPress rewrite rules'
    ));

} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ));
}
