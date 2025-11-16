<?php
/**
 * Plugin Name: Memorians POC - FFmpeg Video Compilation
 * Plugin URI: https://memorians.nl
 * Description: Generates memorial video compilations using FFmpeg with random images and videos
 * Version: 1.0.0
 * Author: Memorians
 * License: GPL v2 or later
 * Text Domain: memorians-poc
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MEMORIANS_POC_VERSION', '1.9.0');
define('MEMORIANS_POC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MEMORIANS_POC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MEMORIANS_POC_MEDIA_DIR', MEMORIANS_POC_PLUGIN_DIR . 'media/');
define('MEMORIANS_POC_CACHE_DIR', MEMORIANS_POC_PLUGIN_DIR . 'cache/');

// Autoload classes
spl_autoload_register(function ($class) {
    $prefix = 'Memorians_POC_';
    $base_dir = MEMORIANS_POC_PLUGIN_DIR . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . 'class-' . strtolower(str_replace('_', '-', $relative_class)) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

/**
 * Plugin activation
 */
function memorians_poc_activate() {
    memorians_poc_rewrite_rules();
    flush_rewrite_rules();

    // Create cache directory if it doesn't exist
    if (!file_exists(MEMORIANS_POC_CACHE_DIR)) {
        wp_mkdir_p(MEMORIANS_POC_CACHE_DIR);
    }

    // Store version for auto-flush on updates
    update_option('memorians_poc_version', MEMORIANS_POC_VERSION);

    // Create .htaccess to allow MP4 access but deny other files
    $htaccess_path = MEMORIANS_POC_CACHE_DIR . '.htaccess';
    $htaccess_content = "# Deny access by default\nOrder deny,allow\nDeny from all\n\n# Allow access to MP4 videos only\n<FilesMatch \"\\.mp4$\">\n    Order allow,deny\n    Allow from all\n</FilesMatch>";
    file_put_contents($htaccess_path, $htaccess_content);

    // Create temp directory
    if (!file_exists(MEMORIANS_POC_CACHE_DIR . 'temp/')) {
        wp_mkdir_p(MEMORIANS_POC_CACHE_DIR . 'temp/');
    }

    // Check FFmpeg availability
    $ffmpeg_check = shell_exec('ffmpeg -version 2>&1');
    if (strpos($ffmpeg_check, 'ffmpeg version') === false) {
        add_option('memorians_poc_ffmpeg_warning', true);
    }
}
register_activation_hook(__FILE__, 'memorians_poc_activate');

/**
 * Plugin deactivation
 */
function memorians_poc_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'memorians_poc_deactivate');

/**
 * Add custom rewrite rules
 */
function memorians_poc_rewrite_rules() {
    add_rewrite_rule(
        '^ffmpeg-poc/?$',
        'index.php?memorians_poc_page=video',
        'top'
    );

    add_rewrite_rule(
        '^ffmpeg-poc/generate/?$',
        'index.php?memorians_poc_page=generate',
        'top'
    );

    add_rewrite_rule(
        '^ffmpeg-poc/progress/?$',
        'index.php?memorians_poc_page=progress',
        'top'
    );

    add_rewrite_rule(
        '^ffmpeg-poc/test/?$',
        'index.php?memorians_poc_page=test',
        'top'
    );

    add_rewrite_rule(
        '^ffmpeg-poc/debug/?$',
        'index.php?memorians_poc_page=debug',
        'top'
    );

    add_rewrite_rule(
        '^ffmpeg-poc/media-library/?$',
        'index.php?memorians_poc_page=media_library',
        'top'
    );

    add_rewrite_rule(
        '^ffmpeg-poc/diagnostics/?$',
        'index.php?memorians_poc_page=diagnostics',
        'top'
    );

    add_rewrite_rule(
        '^ffmpeg-poc/video-history/?$',
        'index.php?memorians_poc_page=video_history',
        'top'
    );

    add_rewrite_rule(
        '^ffmpeg-poc/thumbnail/?$',
        'index.php?memorians_poc_page=thumbnail',
        'top'
    );

    add_rewrite_tag('%memorians_poc_page%', '([^&]+)');
}
add_action('init', 'memorians_poc_rewrite_rules');

/**
 * Auto-flush rewrite rules if plugin version changed
 */
function memorians_poc_check_version() {
    $stored_version = get_option('memorians_poc_version');

    if ($stored_version !== MEMORIANS_POC_VERSION) {
        // Version changed, flush rules
        memorians_poc_rewrite_rules();
        flush_rewrite_rules();
        update_option('memorians_poc_version', MEMORIANS_POC_VERSION);

        error_log('Memorians POC: Flushed rewrite rules for version ' . MEMORIANS_POC_VERSION);
    }
}
add_action('init', 'memorians_poc_check_version', 20);

/**
 * Handle template redirect
 */
function memorians_poc_template_redirect() {
    $page = get_query_var('memorians_poc_page');

    if (!$page) {
        return;
    }

    switch ($page) {
        case 'video':
            include MEMORIANS_POC_PLUGIN_DIR . 'templates/video-page.php';
            exit;

        case 'generate':
            include MEMORIANS_POC_PLUGIN_DIR . 'templates/generate-ajax.php';
            exit;

        case 'progress':
            include MEMORIANS_POC_PLUGIN_DIR . 'templates/progress-ajax.php';
            exit;

        case 'test':
            include MEMORIANS_POC_PLUGIN_DIR . 'templates/test-ffmpeg.php';
            exit;

        case 'debug':
            include MEMORIANS_POC_PLUGIN_DIR . 'templates/debug-generation.php';
            exit;

        case 'media_library':
            include MEMORIANS_POC_PLUGIN_DIR . 'templates/media-library-ajax.php';
            exit;

        case 'diagnostics':
            include MEMORIANS_POC_PLUGIN_DIR . 'templates/diagnostics.php';
            exit;

        case 'video_history':
            include MEMORIANS_POC_PLUGIN_DIR . 'templates/video-history-ajax.php';
            exit;

        case 'thumbnail':
            include MEMORIANS_POC_PLUGIN_DIR . 'templates/thumbnail-ajax.php';
            exit;
    }
}
add_action('template_redirect', 'memorians_poc_template_redirect');

/**
 * Enqueue scripts and styles
 */
function memorians_poc_enqueue_assets() {
    if (get_query_var('memorians_poc_page') === 'video') {
        wp_enqueue_style(
            'memorians-poc-video-page',
            MEMORIANS_POC_PLUGIN_URL . 'assets/css/video-page.css',
            array(),
            MEMORIANS_POC_VERSION
        );

        // Add timestamp for aggressive cache busting on mobile
        $cache_buster = MEMORIANS_POC_VERSION . '.' . time();

        wp_enqueue_script(
            'memorians-poc-video-player',
            MEMORIANS_POC_PLUGIN_URL . 'assets/js/video-player.js',
            array('jquery'),
            $cache_buster,
            true
        );

        wp_localize_script('memorians-poc-video-player', 'memoriansPoC', array(
            'ajaxUrl' => home_url('/ffmpeg-poc/'),
            'generateUrl' => home_url('/ffmpeg-poc/generate/'),
            'progressUrl' => home_url('/ffmpeg-poc/progress/'),
            'mediaLibraryUrl' => home_url('/ffmpeg-poc/media-library/'),
            'videoHistoryUrl' => home_url('/ffmpeg-poc/video-history/')
        ));
    }
}
add_action('wp_enqueue_scripts', 'memorians_poc_enqueue_assets');

/**
 * Admin notice for FFmpeg warning
 */
function memorians_poc_admin_notice() {
    if (get_option('memorians_poc_ffmpeg_warning')) {
        ?>
        <div class="notice notice-error">
            <p><strong>Memorians POC:</strong> FFmpeg is not installed or not accessible. Please install FFmpeg to use this plugin.</p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'memorians_poc_admin_notice');

/**
 * Schedule cache cleanup
 */
function memorians_poc_schedule_cleanup() {
    if (!wp_next_scheduled('memorians_poc_cleanup_cache')) {
        wp_schedule_event(time(), 'daily', 'memorians_poc_cleanup_cache');
    }
}
add_action('wp', 'memorians_poc_schedule_cleanup');

/**
 * Cleanup old cached videos
 */
function memorians_poc_cleanup_cache_callback() {
    $cache_manager = new Memorians_POC_Cache_Manager();
    $cache_manager->cleanup_old_videos();
}
add_action('memorians_poc_cleanup_cache', 'memorians_poc_cleanup_cache_callback');
