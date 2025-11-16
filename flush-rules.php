<?php
/**
 * Quick script to flush WordPress rewrite rules
 * Run this once to activate the new thumbnail endpoint
 *
 * Usage: Visit https://memorians.nl/wp-content/plugins/memorians-poc/flush-rules.php
 */

// Try to find wp-load.php
$wp_load_path = dirname(__FILE__) . '/../../../../wp-load.php';

// Check if path exists
if (!file_exists($wp_load_path)) {
    // Try alternative path
    $wp_load_path = dirname(__FILE__) . '/../../../wp-load.php';

    if (!file_exists($wp_load_path)) {
        die('Error: Could not find wp-load.php. Please use WordPress Admin method instead: Settings > Permalinks > Save Changes');
    }
}

// Load WordPress
require_once($wp_load_path);

// Check if the function exists (plugin must be active)
if (!function_exists('memorians_poc_rewrite_rules')) {
    die('Error: Memorians POC plugin functions not found. Make sure the plugin is activated.');
}

// Check if user is logged in and is admin
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    // Provide login link
    $login_url = wp_login_url(plugin_dir_url(__FILE__) . 'flush-rules.php');
    die('Access denied. <a href="' . $login_url . '">Please log in as an administrator</a>.');
}

// Flush rewrite rules
flush_rewrite_rules();

// Also ensure our rules are registered
memorians_poc_rewrite_rules();
flush_rewrite_rules();

// Success message
?>
<!DOCTYPE html>
<html>
<head>
    <title>Flush Rewrite Rules - Success</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .success-box {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: #22c55e;
            margin-top: 0;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
        }
        .button:hover {
            background: #5a67d8;
        }
        code {
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="success-box">
        <h2>✅ Success!</h2>
        <p>Rewrite rules have been flushed successfully.</p>
        <p>The thumbnail endpoint <code>/ffmpeg-poc/thumbnail/</code> should now be accessible.</p>
        <a href="/ffmpeg-poc/" class="button">Go back to video page</a>
        <hr style="margin-top: 30px; border: none; border-top: 1px solid #e5e5e5;">
        <p style="color: #666; font-size: 14px;">
            <strong>Note:</strong> You can delete this file (<code>flush-rules.php</code>) after using it.
        </p>
    </div>
</body>
</html>