<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Memorial Video - Memorians POC</title>

    <!-- Load only necessary scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="<?php echo MEMORIANS_POC_PLUGIN_URL; ?>assets/css/video-page.css?ver=<?php echo MEMORIANS_POC_VERSION; ?>">
</head>
<body class="memorians-poc-page">

<div class="memorians-poc-container">
    <header class="memorians-header">
        <h1>Memorial Video Compilation</h1>
        <p>A tribute created with love and remembrance</p>
    </header>

    <!-- Video Gallery Panel -->
    <div id="video-gallery-panel" class="video-gallery-panel" style="display: none;">
        <div class="gallery-header">
            <h2>Your Generated Videos</h2>
        </div>

        <div id="gallery-grid" class="gallery-grid">
            <div class="loading-placeholder">Loading video gallery...</div>
        </div>
    </div>

    <!-- Media Selection Panel -->
    <div id="media-selection-panel" class="media-selection-panel" style="display: none;">
        <h2>Generate Video</h2>

        <!-- Selection Summary -->
        <div class="selection-summary">
            <div class="summary-item">
                <span>Images: <strong id="images-selected-count">0</strong> selected (min 15, max 40)</span>
                <span class="status-badge" id="images-status">⚠ Need 15 more (min 15)</span>
            </div>
            <div class="summary-item">
                <span>Videos: <strong id="videos-selected-count">0</strong> selected (min 1, max 5)</span>
                <span class="status-badge" id="videos-status">⚠ Need 1 more (min 1)</span>
            </div>
            <div class="summary-item">
                <span>Audio: <strong id="audio-selected-count">0</strong> selected (required 1)</span>
                <span class="status-badge" id="audio-status">⚠ Need 1 more (min 1)</span>
            </div>
        </div>

        <!-- Images Section -->
        <div class="media-section">
            <h3>Images (Select 15-40)</h3>
            <div id="images-grid" class="media-grid">
                <div class="loading-placeholder">Loading images...</div>
            </div>
        </div>

        <!-- Videos Section -->
        <div class="media-section">
            <h3>Videos (Select 1-5)</h3>
            <div id="videos-grid" class="media-grid">
                <div class="loading-placeholder">Loading videos...</div>
            </div>
        </div>

        <!-- Audio Section -->
        <div class="media-section">
            <h3>Audio Tracks (Select 1)</h3>
            <div id="audio-list" class="audio-list">
                <div class="loading-placeholder">Loading audio...</div>
            </div>
        </div>

        <!-- Template Selection -->
        <div class="media-section">
            <h3>Video Style</h3>
            <div class="template-selector-panel">
                <select id="template-style-selection" class="template-select">
                    <option value="classic">Classic</option>
                    <option value="modern">Modern</option>
                    <option value="elegant">Elegant</option>
                </select>
                <p id="template-description" class="template-description">Traditional fade and slide transitions</p>
            </div>
        </div>

        <!-- Generate Button -->
        <div class="generate-button-container">
            <button id="start-generation" class="button button-primary button-large" disabled>
                Start Generation
            </button>
        </div>
    </div>

    <div id="video-container" class="video-wrapper">
        <!-- Video player will be inserted here -->
        <div id="loading-screen" class="loading-screen" style="display: none;">
            <div class="loading-content">
                <div class="spinner"></div>
                <h2>Creating Your Memorial Video...</h2>
                <div class="progress-bar">
                    <div id="progress-fill" class="progress-fill"></div>
                </div>
                <p id="progress-text">Preparing media files...</p>
                <p id="progress-percentage">0%</p>
            </div>
        </div>

        <div id="video-player" class="video-player" style="display: none;">
            <video id="memorial-video" controls autoplay muted loop>
                <source id="video-source" src="" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        </div>

        <div id="error-screen" class="error-screen" style="display: none;">
            <div class="error-content">
                <h2>Oops! Something went wrong</h2>
                <p id="error-message"></p>
                <div style="margin-top: 20px; padding: 15px; background: #f9f9f9; border-radius: 10px; text-align: left; font-size: 0.9em; color: #666;">
                    <strong>Debug Info:</strong><br>
                    Open browser console (F12) for detailed error information.
                </div>
                <button id="retry-button" class="button">Try Again</button>
            </div>
        </div>
    </div>

    <div class="controls-panel" id="controls-panel" style="display: none;">
        <div class="action-buttons">
            <button id="back-to-gallery" class="button">← Back to Gallery</button>
            <button id="download-video" class="button" style="display: none;">Download Video</button>
        </div>
    </div>

    <div class="info-panel" id="info-panel" style="display: none;">
        <h3>About This Video</h3>
        <p>This memorial video compilation features:</p>
        <ul>
            <li>15 custom-selected images with Ken Burns effects</li>
            <li>1 video clip with original audio</li>
            <li>Beautiful transitions between media</li>
            <li>Background music to set the mood</li>
        </ul>
        <p class="note">Customize your selection and regenerate to create different variations.</p>
    </div>
</div>

<script>
// Pass PHP data to JavaScript
var memoriansPoC = memoriansPoC || {};
memoriansPoC.homeUrl = '<?php echo home_url(); ?>';
memoriansPoC.generateUrl = '<?php echo home_url('/ffmpeg-poc/generate/'); ?>';
memoriansPoC.progressUrl = '<?php echo home_url('/ffmpeg-poc/progress/'); ?>';
memoriansPoC.mediaLibraryUrl = '<?php echo home_url('/ffmpeg-poc/media-library/'); ?>';
memoriansPoC.videoHistoryUrl = '<?php echo home_url('/ffmpeg-poc/video-history/'); ?>';
</script>

<script src="<?php echo MEMORIANS_POC_PLUGIN_URL; ?>assets/js/video-player.js?ver=<?php echo MEMORIANS_POC_VERSION; ?>"></script>
</body>
</html>
