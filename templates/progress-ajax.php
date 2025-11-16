<?php
/**
 * AJAX handler for video generation progress
 * This file is loaded via template_redirect, so WordPress is already loaded
 */

header('Content-Type: application/json');

try {
    // Get template from request
    $template = isset($_GET['template']) ? sanitize_text_field($_GET['template']) : 'classic';

    // Validate template
    $valid_templates = array('classic', 'modern', 'elegant');
    if (!in_array($template, $valid_templates)) {
        $template = 'classic';
    }

    $generator = new Memorians_POC_Video_Generator();
    $progress = $generator->get_progress($template);

    echo json_encode($progress);

} catch (Exception $e) {
    echo json_encode(array(
        'status' => 'error',
        'progress' => 0,
        'error' => $e->getMessage()
    ));
}

exit;
