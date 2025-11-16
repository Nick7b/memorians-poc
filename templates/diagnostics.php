<?php
/**
 * Diagnostics page for Memorians POC
 * Shows all endpoints, checks, and media availability
 * WordPress is already loaded via template_redirect
 */

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Memorians POC - Diagnostics</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin: 20px 0; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        pre { background: #f9f9f9; padding: 10px; border-radius: 3px; overflow-x: auto; }
        h2 { color: #667eea; }
    </style>
</head>
<body>
    <h1>Memorians POC - Diagnostics</h1>

    <?php
    // Check 1: Plugin Active
    echo '<div class="section">';
    echo '<h2>1. Plugin Status</h2>';
    if (function_exists('is_plugin_active') && is_plugin_active('memorians-poc/memorians-poc.php')) {
        echo '<p class="success">✓ Plugin is active</p>';
    } else {
        echo '<p class="error">✗ Plugin is NOT active</p>';
    }
    echo '</div>';

    // Check 2: Classes Loaded
    echo '<div class="section">';
    echo '<h2>2. Class Availability</h2>';
    $classes = array(
        'Memorians_POC_Media_Selector',
        'Memorians_POC_Video_Generator',
        'Memorians_POC_Cache_Manager'
    );
    foreach ($classes as $class) {
        if (class_exists($class)) {
            echo '<p class="success">✓ ' . $class . ' loaded</p>';
        } else {
            echo '<p class="error">✗ ' . $class . ' NOT loaded</p>';
        }
    }
    echo '</div>';

    // Check 3: Rewrite Rules
    echo '<div class="section">';
    echo '<h2>3. Rewrite Rules</h2>';
    global $wp_rewrite;
    $rules = get_option('rewrite_rules');
    $found_rules = array();
    foreach ($rules as $pattern => $rewrite) {
        if (strpos($pattern, 'ffmpeg-poc') !== false) {
            $found_rules[$pattern] = $rewrite;
        }
    }
    if (!empty($found_rules)) {
        echo '<p class="success">✓ FFmpeg POC rewrite rules found:</p>';
        echo '<pre>' . print_r($found_rules, true) . '</pre>';
    } else {
        echo '<p class="error">✗ No FFmpeg POC rewrite rules found</p>';
        echo '<p class="warning">⚠ Go to Plugins → Deactivate → Activate to flush rewrite rules</p>';
    }
    echo '</div>';

    // Check 4: Media Library Endpoint
    echo '<div class="section">';
    echo '<h2>4. Media Library Test</h2>';
    try {
        if (!class_exists('Memorians_POC_Media_Selector')) {
            throw new Exception('Memorians_POC_Media_Selector class not loaded');
        }

        $media_selector = new Memorians_POC_Media_Selector();
        $media_library = $media_selector->get_all_media();

        if (is_wp_error($media_library)) {
            echo '<p class="error">✗ Error: ' . $media_library->get_error_message() . '</p>';
        } else {
            echo '<p class="success">✓ Media library loaded successfully</p>';
            echo '<p>Images: ' . count($media_library['images']) . '</p>';
            echo '<p>Videos: ' . count($media_library['videos']) . '</p>';
            echo '<p>Audio: ' . count($media_library['audio']) . '</p>';
            echo '<details><summary>View full response</summary><pre>' . print_r($media_library, true) . '</pre></details>';
        }
    } catch (Exception $e) {
        echo '<p class="error">✗ Exception: ' . $e->getMessage() . '</p>';
    }
    echo '</div>';

    // Check 5: Endpoint URLs
    echo '<div class="section">';
    echo '<h2>5. Endpoint URLs</h2>';
    $endpoints = array(
        'Video Page' => home_url('/ffmpeg-poc/'),
        'Generate' => home_url('/ffmpeg-poc/generate/'),
        'Progress' => home_url('/ffmpeg-poc/progress/'),
        'Media Library' => home_url('/ffmpeg-poc/media-library/'),
    );
    foreach ($endpoints as $name => $url) {
        echo '<p><strong>' . $name . ':</strong><br>';
        echo '<a href="' . $url . '" target="_blank">' . $url . '</a></p>';
    }
    echo '</div>';

    // Check 6: FFmpeg
    echo '<div class="section">';
    echo '<h2>6. FFmpeg Availability</h2>';
    $ffmpeg_check = shell_exec('ffmpeg -version 2>&1');
    if (strpos($ffmpeg_check, 'ffmpeg version') !== false) {
        echo '<p class="success">✓ FFmpeg is installed</p>';
        $lines = explode("\n", $ffmpeg_check);
        echo '<p>' . htmlspecialchars($lines[0]) . '</p>';
    } else {
        echo '<p class="error">✗ FFmpeg not found</p>';
    }
    echo '</div>';

    // Check 7: Directory Permissions
    echo '<div class="section">';
    echo '<h2>7. Directory Permissions</h2>';
    $dirs = array(
        'Cache' => MEMORIANS_POC_CACHE_DIR,
        'Temp' => MEMORIANS_POC_CACHE_DIR . 'temp/',
        'Media' => MEMORIANS_POC_MEDIA_DIR,
    );
    foreach ($dirs as $name => $dir) {
        if (is_dir($dir)) {
            if (is_writable($dir)) {
                echo '<p class="success">✓ ' . $name . ' directory writable: ' . $dir . '</p>';
            } else {
                echo '<p class="error">✗ ' . $name . ' directory NOT writable: ' . $dir . '</p>';
            }
        } else {
            echo '<p class="error">✗ ' . $name . ' directory does NOT exist: ' . $dir . '</p>';
        }
    }
    echo '</div>';
    ?>

    <div class="section">
        <h2>Next Steps</h2>
        <ol>
            <li>If rewrite rules are missing: Go to <strong>Plugins → Deactivate → Activate</strong></li>
            <li>If classes are not loaded: Ensure plugin is activated</li>
            <li>If media library works here but not on main page: Check browser console for JavaScript errors</li>
            <li>After fixing issues, visit: <a href="<?php echo home_url('/ffmpeg-poc/'); ?>">Main Page</a></li>
        </ol>
    </div>
</body>
</html>
