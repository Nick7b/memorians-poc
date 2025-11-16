<?php
/**
 * Cache Clearing Utility for Memorians POC
 * Access this directly to force clear all caches
 */

// Load WordPress
$wp_load_path = dirname(__FILE__) . '/../../../wp-load.php';
if (file_exists($wp_load_path)) {
    require_once($wp_load_path);
}

// Clear any WordPress caches
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
}

// Force aggressive headers
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0, s-maxage=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('ETag: "' . uniqid() . '"');

// Get current version info
$version = defined('MEMORIANS_POC_VERSION') ? MEMORIANS_POC_VERSION : 'unknown';
$timestamp = time();
$unique = uniqid();

// Build URLs with aggressive cache busting
$base_url = plugins_url('/', __FILE__);
$js_url = $base_url . 'assets/js/video-player.js?ver=' . $version . '&t=' . $timestamp . '&u=' . $unique;
$css_url = $base_url . 'assets/css/video-page.css?ver=' . $version . '&t=' . $timestamp . '&u=' . $unique;

?><!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Cache Clear - Memorians POC</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            padding: 20px;
            max-width: 600px;
            margin: 0 auto;
        }
        .status {
            background: #f0f0f0;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .success { background: #d4edda; color: #155724; }
        .info { background: #d1ecf1; color: #0c5460; }
        .warning { background: #fff3cd; color: #856404; }
        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 0;
        }
        pre {
            background: #f5f5f5;
            padding: 10px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>Cache Clear Utility</h1>

    <div class="status success">
        ✓ Cache headers sent to force reload
    </div>

    <div class="status info">
        <strong>Version Information:</strong><br>
        Plugin Version: <?php echo $version; ?><br>
        Timestamp: <?php echo $timestamp; ?><br>
        Unique ID: <?php echo $unique; ?>
    </div>

    <div class="status info">
        <strong>Asset URLs (with cache busting):</strong><br>
        <pre>JS: <?php echo htmlspecialchars($js_url); ?></pre>
        <pre>CSS: <?php echo htmlspecialchars($css_url); ?></pre>
    </div>

    <h2>Instructions for iPhone Safari:</h2>
    <ol>
        <li>Click the button below to go to the video page</li>
        <li>Once there, pull down to refresh the page</li>
        <li>If still cached, try opening in a Private/Incognito tab</li>
    </ol>

    <button onclick="forceClearAndRedirect()">Go to Video Page (Force Reload)</button>

    <h2>Alternative Method:</h2>
    <p>Try accessing the page with a unique parameter:</p>
    <pre><?php echo home_url('/ffmpeg-poc/?nocache=' . $unique); ?></pre>

    <script>
    function forceClearAndRedirect() {
        // Clear any local storage
        if (typeof(Storage) !== "undefined") {
            localStorage.clear();
            sessionStorage.clear();
        }

        // Force reload with cache bypass
        var url = '<?php echo home_url('/ffmpeg-poc/'); ?>?nocache=' + Date.now() + '&v=<?php echo $version; ?>';

        // Try to clear service workers if any
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistrations().then(function(registrations) {
                for(let registration of registrations) {
                    registration.unregister();
                }
            });
        }

        // Redirect with cache bypass
        window.location.replace(url);
    }

    // Auto-clear caches on load
    if (typeof(Storage) !== "undefined") {
        localStorage.clear();
        sessionStorage.clear();
        console.log('Local storage cleared');
    }
    </script>

    <div class="status warning">
        <strong>Debug Mode Active:</strong><br>
        Check browser console for detailed logs
    </div>
</body>
</html>