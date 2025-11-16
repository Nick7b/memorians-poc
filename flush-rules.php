<?php
/**
 * Quick script to flush WordPress rewrite rules
 * Run this once to activate the new thumbnail endpoint
 *
 * Usage: Visit https://memorians.nl/wp-content/plugins/memorians-poc/flush-rules.php
 */

// Load WordPress
require_once('../../../../wp-load.php');

// Check if user is admin (for security)
if (!current_user_can('manage_options')) {
    die('Access denied. Please log in as an administrator.');
}

// Flush rewrite rules
flush_rewrite_rules();

// Also ensure our rules are registered
memorians_poc_rewrite_rules();
flush_rewrite_rules();

// Success message
echo '<h2>✅ Success!</h2>';
echo '<p>Rewrite rules have been flushed successfully.</p>';
echo '<p>The thumbnail endpoint should now be accessible.</p>';
echo '<p><a href="/ffmpeg-poc/">Go back to video page</a></p>';
echo '<hr>';
echo '<p><small>You can delete this file after running it once.</small></p>';
?>