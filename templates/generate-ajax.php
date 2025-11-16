<?php
/**
 * AJAX handler for video generation
 * This file is loaded via template_redirect, so WordPress is already loaded
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display to browser
ini_set('log_errors', 1);

header('Content-Type: application/json');

try {
    // Get template from request
    $template = isset($_GET['template']) ? sanitize_text_field($_GET['template']) : 'classic';

    // Validate template
    $valid_templates = array('classic', 'modern', 'elegant');
    if (!in_array($template, $valid_templates)) {
        $template = 'classic';
    }

    // Check for force regeneration
    $force = isset($_GET['force']) && $_GET['force'] === 'true';

    $cache_manager = new Memorians_POC_Cache_Manager();

    // Check if video already exists in cache
    if (!$force) {
        $cached_video = $cache_manager->get_cached_video($template);
        if ($cached_video) {
            $video_url = $cache_manager->get_video_url($cached_video);
            echo json_encode(array(
                'success' => true,
                'status' => 'cached',
                'video_url' => $video_url,
                'message' => 'Video already generated and cached.'
            ));
            exit;
        }
    }

    // Check if generation is in progress
    $status = $cache_manager->get_generation_status($template);
    if ($status && $status['status'] === 'generating') {
        echo json_encode(array(
            'success' => true,
            'status' => 'generating',
            'message' => 'Video generation is in progress.'
        ));
        exit;
    }

    // If no media selection provided and not forcing, just return "no cache" status
    // This prevents auto-generation when just checking for cached video
    if (!isset($_GET['images']) && !isset($_GET['video']) && !$force) {
        echo json_encode(array(
            'success' => false,
            'status' => 'no_cache',
            'message' => 'No cached video available. Please select media to generate.'
        ));
        exit;
    }

    // Generate new video
    $generator = new Memorians_POC_Video_Generator();

    // Check if user provided media selection
    if (isset($_GET['images']) && isset($_GET['videos'])) {
        // User-selected media
        $image_ids = $_GET['images'];
        $video_ids = $_GET['videos'];
        $audio_id = isset($_GET['audio']) ? sanitize_text_field($_GET['audio']) : null;
        $background_id = isset($_GET['background']) ? sanitize_text_field($_GET['background']) : null;

        // Sanitize image IDs and video IDs
        $image_ids = array_map('sanitize_text_field', $image_ids);
        $video_ids = array_map('sanitize_text_field', $video_ids);

        // Get settings array if provided
        $settings = array();
        if (isset($_GET['settings']) && is_array($_GET['settings'])) {
            $settings = array(
                'imageScale' => isset($_GET['settings']['imageScale']) ? floatval($_GET['settings']['imageScale']) : 1.0,
                'videoScale' => isset($_GET['settings']['videoScale']) ? floatval($_GET['settings']['videoScale']) : 1.0,
                'imageDuration' => isset($_GET['settings']['imageDuration']) ? floatval($_GET['settings']['imageDuration']) : 4,
                'transitionDuration' => isset($_GET['settings']['transitionDuration']) ? floatval($_GET['settings']['transitionDuration']) : 1,
                'kenBurnsIntensity' => isset($_GET['settings']['kenBurnsIntensity']) ? floatval($_GET['settings']['kenBurnsIntensity']) : 1.0,
                'backgroundBlur' => isset($_GET['settings']['backgroundBlur']) ? intval($_GET['settings']['backgroundBlur']) : 0,
                'mediaShadow' => isset($_GET['settings']['mediaShadow']) && $_GET['settings']['mediaShadow'] === '1',
                'paddingColor' => isset($_GET['settings']['paddingColor']) ? sanitize_hex_color($_GET['settings']['paddingColor']) : '#000000',
                'videoQuality' => isset($_GET['settings']['videoQuality']) ? sanitize_text_field($_GET['settings']['videoQuality']) : 'medium',
                'outputResolution' => isset($_GET['settings']['outputResolution']) ? sanitize_text_field($_GET['settings']['outputResolution']) : '1080p',
                'frameRate' => isset($_GET['settings']['frameRate']) ? intval($_GET['settings']['frameRate']) : 30,
                'musicVolume' => isset($_GET['settings']['musicVolume']) ? intval($_GET['settings']['musicVolume']) : 80,
                'audioFade' => isset($_GET['settings']['audioFade']) && $_GET['settings']['audioFade'] === '1'
            );
        }

        // DEBUG: Log settings
        error_log("generate-ajax.php: Received settings = " . json_encode($settings));
        error_log("generate-ajax.php: Received background_id = " . ($background_id ? $background_id : 'NULL'));

        $result = $generator->generate_with_selection($template, $image_ids, $video_ids, $audio_id, $background_id, $settings);
    } else {
        // This should not happen anymore, but keep as fallback
        $result = $generator->generate($template);
    }

    if ($result['success']) {
        // Check the actual status from the result
        if (isset($result['status']) && $result['status'] === 'generating') {
            // Return generating status - progress polling will handle the rest
            echo json_encode(array(
                'success' => true,
                'status' => 'generating',
                'message' => isset($result['message']) ? $result['message'] : 'Video generation started'
            ));
        } else {
            // Return completed status with video URL
            echo json_encode(array(
                'success' => true,
                'status' => 'completed',
                'video_url' => $result['video_url'],
                'message' => 'Video generated successfully!'
            ));
        }
    } else {
        echo json_encode(array(
            'success' => false,
            'status' => 'failed',
            'error' => isset($result['error']) ? $result['error'] : 'Unknown error',
            'message' => 'Failed to generate video: ' . (isset($result['error']) ? $result['error'] : 'Unknown error')
        ));
    }

} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'status' => 'error',
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'message' => 'Error: ' . $e->getMessage()
    ));
}

exit;
