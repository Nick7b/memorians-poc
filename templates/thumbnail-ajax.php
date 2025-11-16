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

// Log that endpoint was reached
error_log('Thumbnail AJAX endpoint accessed');
error_log('Request params: ' . json_encode($_GET));

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
        // Remove leading slash if present
        $media_path = ltrim($media_path, '/');

        // Try multiple path resolutions
        $possible_paths = array(
            MEMORIANS_POC_PLUGIN_DIR . $media_path,
            ABSPATH . $media_path,
            dirname(MEMORIANS_POC_PLUGIN_DIR, 3) . '/' . $media_path // Go up to WordPress root
        );

        $found = false;
        foreach ($possible_paths as $test_path) {
            if (file_exists($test_path)) {
                $media_path = $test_path;
                $found = true;
                error_log('Found media file at: ' . $media_path);
                break;
            }
        }

        if (!$found) {
            // For cache files, try direct cache directory
            if (strpos($media_path, 'cache/') !== false) {
                $filename = basename($media_path);
                $cache_path = MEMORIANS_POC_CACHE_DIR . $filename;
                if (file_exists($cache_path)) {
                    $media_path = $cache_path;
                    $found = true;
                    error_log('Found cache file at: ' . $media_path);
                }
            }
        }

        if (!$found) {
            throw new Exception('Media file not found. Tried paths: ' . implode(', ', $possible_paths));
        }
    }

    // Initialize thumbnail generator
    if (!class_exists('Memorians_POC_Thumbnail_Generator')) {
        require_once MEMORIANS_POC_PLUGIN_DIR . 'includes/class-thumbnail-generator.php';
    }

    $thumbnail_generator = new Memorians_POC_Thumbnail_Generator();

    // Generate appropriate thumbnail
    if ($media_type === 'image') {
        error_log('Generating image thumbnail for: ' . $media_path . ' size: ' . $size);
        $result = $thumbnail_generator->generate_image_thumbnail($media_path, $size, $force);
        error_log('Thumbnail generation result: ' . (is_wp_error($result) ? 'ERROR: ' . $result->get_error_message() : 'SUCCESS'));
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
    $response = array(
        'success' => true,
        'thumbnail' => $result,
        'media_type' => $media_type,
        'size' => $size
    );
    error_log('Sending thumbnail response: ' . json_encode($response));
    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => WP_DEBUG ? $e->getTraceAsString() : null
    ));
}

exit;