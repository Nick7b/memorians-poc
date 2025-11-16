<?php
/**
 * AJAX handler for on-demand thumbnail generation
 * Generates thumbnails when requested by the frontend
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');

try {
    // Validate request
    if (!isset($_GET['media_path']) || !isset($_GET['media_type'])) {
        throw new Exception('Missing required parameters: media_path and media_type');
    }

    $media_path = sanitize_text_field($_GET['media_path']);
    $media_type = sanitize_text_field($_GET['media_type']);
    $size = isset($_GET['size']) ? sanitize_text_field($_GET['size']) : 'thumbnail';
    $force = isset($_GET['force']) && $_GET['force'] === 'true';

    // Validate media type
    if (!in_array($media_type, array('image', 'video', 'generated_video'))) {
        throw new Exception('Invalid media type. Must be: image, video, or generated_video');
    }

    // Convert relative path to absolute if needed
    if (!file_exists($media_path)) {
        // Try to resolve path relative to plugin directory
        $test_path = MEMORIANS_POC_PLUGIN_DIR . ltrim($media_path, '/');
        if (file_exists($test_path)) {
            $media_path = $test_path;
        } else {
            throw new Exception('Media file not found: ' . $media_path);
        }
    }

    // Initialize thumbnail generator
    if (!class_exists('Memorians_POC_Thumbnail_Generator')) {
        require_once MEMORIANS_POC_PLUGIN_DIR . 'includes/class-thumbnail-generator.php';
    }

    $thumbnail_generator = new Memorians_POC_Thumbnail_Generator();

    // Generate appropriate thumbnail
    if ($media_type === 'image') {
        $result = $thumbnail_generator->generate_image_thumbnail($media_path, $size, $force);
    } elseif ($media_type === 'video') {
        // For regular videos, generate poster
        $timestamp = isset($_GET['timestamp']) ? floatval($_GET['timestamp']) : 1.0;
        $result = $thumbnail_generator->generate_video_poster($media_path, $size, $timestamp, $force);
    } else {
        // For generated videos, generate both thumb and full poster
        $result = $thumbnail_generator->generate_video_posters($media_path, $force);
    }

    if (is_wp_error($result)) {
        echo json_encode(array(
            'success' => false,
            'error' => $result->get_error_message(),
            'error_code' => $result->get_error_code()
        ));
        exit;
    }

    // Return successful result
    echo json_encode(array(
        'success' => true,
        'thumbnail' => $result,
        'media_type' => $media_type,
        'size' => $size
    ));

} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => WP_DEBUG ? $e->getTraceAsString() : null
    ));
}

exit;