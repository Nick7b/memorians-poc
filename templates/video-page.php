<?php
// Force no caching for this page
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');

// Force reload timestamp
$force_reload = time();
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Memorial Video - Memorians POC</title>

    <!-- Load only necessary scripts with aggressive cache busting -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="<?php echo MEMORIANS_POC_PLUGIN_URL; ?>assets/css/video-page.css?ver=<?php echo MEMORIANS_POC_VERSION; ?>&t=<?php echo $force_reload; ?>">
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
    <div id="media-selection-panel" class="media-selection-panel">
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

        <!-- Background Image Section -->
        <div class="media-section">
            <h3>Background Image (Optional)</h3>
            <div id="background-list" class="background-list">
                <div class="loading-placeholder">Loading backgrounds...</div>
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

        <!-- Advanced Options Section -->
        <div class="media-section advanced-options-section">
            <div class="advanced-header" id="advanced-header">
                <h3>
                    <span class="header-text">Advanced Options</span>
                    <span class="header-icon">▼</span>
                </h3>
                <p class="advanced-status">Using: <strong id="advanced-status-text">Template Defaults</strong></p>
            </div>

            <div class="advanced-content" id="advanced-content" style="display: none;">
                <!-- Override Mode Toggle -->
                <div class="advanced-group override-toggle">
                    <label class="switch">
                        <input type="checkbox" id="override-defaults" class="override-checkbox">
                        <span class="slider round"></span>
                    </label>
                    <label for="override-defaults" class="override-label">Override Template Defaults</label>
                    <p class="settings-hint">Enable custom selection of transitions and effects</p>
                </div>

                <!-- Transitions Selection -->
                <div class="advanced-group transitions-group" id="transitions-group">
                    <h4>Transitions <span class="selection-count" id="transition-count">(0 selected)</span></h4>
                    <p class="settings-hint">Select which transitions to use between media</p>
                    <div class="checkbox-grid transitions-grid">
                        <!-- Basic Transitions -->
                        <div class="checkbox-item">
                            <input type="checkbox" id="trans-fade" class="transition-checkbox" value="fade" data-group="basic">
                            <label for="trans-fade">Fade</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="trans-dissolve" class="transition-checkbox" value="dissolve" data-group="basic">
                            <label for="trans-dissolve">Dissolve</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="trans-fadeblack" class="transition-checkbox" value="fadeblack" data-group="basic">
                            <label for="trans-fadeblack">Fade Black</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="trans-fadewhite" class="transition-checkbox" value="fadewhite" data-group="basic">
                            <label for="trans-fadewhite">Fade White</label>
                        </div>

                        <!-- Smooth Transitions -->
                        <div class="checkbox-item">
                            <input type="checkbox" id="trans-smoothleft" class="transition-checkbox" value="smoothleft" data-group="smooth">
                            <label for="trans-smoothleft">Smooth Left</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="trans-smoothright" class="transition-checkbox" value="smoothright" data-group="smooth">
                            <label for="trans-smoothright">Smooth Right</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="trans-smoothup" class="transition-checkbox" value="smoothup" data-group="smooth">
                            <label for="trans-smoothup">Smooth Up</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="trans-smoothdown" class="transition-checkbox" value="smoothdown" data-group="smooth">
                            <label for="trans-smoothdown">Smooth Down</label>
                        </div>

                        <!-- Geometric Transitions -->
                        <div class="checkbox-item">
                            <input type="checkbox" id="trans-circleopen" class="transition-checkbox" value="circleopen" data-group="geometric">
                            <label for="trans-circleopen">Circle Open</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="trans-circleclose" class="transition-checkbox" value="circleclose" data-group="geometric">
                            <label for="trans-circleclose">Circle Close</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="trans-pixelize" class="transition-checkbox" value="pixelize" data-group="geometric">
                            <label for="trans-pixelize">Pixelize</label>
                        </div>

                        <!-- Wipe Transitions -->
                        <div class="checkbox-item">
                            <input type="checkbox" id="trans-wipeleft" class="transition-checkbox" value="wipeleft" data-group="wipe">
                            <label for="trans-wipeleft">Wipe Left</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="trans-wiperight" class="transition-checkbox" value="wiperight" data-group="wipe">
                            <label for="trans-wiperight">Wipe Right</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="trans-wipeup" class="transition-checkbox" value="wipeup" data-group="wipe">
                            <label for="trans-wipeup">Wipe Up</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="trans-wipedown" class="transition-checkbox" value="wipedown" data-group="wipe">
                            <label for="trans-wipedown">Wipe Down</label>
                        </div>

                        <!-- Slide Transitions -->
                        <div class="checkbox-item">
                            <input type="checkbox" id="trans-slideleft" class="transition-checkbox" value="slideleft" data-group="slide">
                            <label for="trans-slideleft">Slide Left</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="trans-slideright" class="transition-checkbox" value="slideright" data-group="slide">
                            <label for="trans-slideright">Slide Right</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="trans-slideup" class="transition-checkbox" value="slideup" data-group="slide">
                            <label for="trans-slideup">Slide Up</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="trans-slidedown" class="transition-checkbox" value="slidedown" data-group="slide">
                            <label for="trans-slidedown">Slide Down</label>
                        </div>
                    </div>
                </div>

                <!-- Ken Burns Patterns Selection -->
                <div class="advanced-group kenburns-group" id="kenburns-group">
                    <h4>Ken Burns Patterns <span class="selection-count" id="kenburns-count">(0 selected)</span></h4>
                    <p class="settings-hint">Select zoom and pan patterns for images</p>
                    <div class="checkbox-grid kenburns-grid">
                        <div class="checkbox-item">
                            <input type="checkbox" id="kb-zoomin" class="kenburns-checkbox" value="zoom_in">
                            <label for="kb-zoomin">Zoom In</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="kb-zoomout" class="kenburns-checkbox" value="zoom_out">
                            <label for="kb-zoomout">Zoom Out</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="kb-panleft" class="kenburns-checkbox" value="pan_left">
                            <label for="kb-panleft">Pan Left</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="kb-panright" class="kenburns-checkbox" value="pan_right">
                            <label for="kb-panright">Pan Right</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="kb-pantl" class="kenburns-checkbox" value="pan_tl">
                            <label for="kb-pantl">Diagonal Top-Left</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="kb-panbr" class="kenburns-checkbox" value="pan_br">
                            <label for="kb-panbr">Diagonal Bottom-Right</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="kb-panup" class="kenburns-checkbox" value="pan_up">
                            <label for="kb-panup">Pan Up</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="kb-pandown" class="kenburns-checkbox" value="pan_down">
                            <label for="kb-pandown">Pan Down</label>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="advanced-group quick-actions">
                    <button id="select-all-transitions" class="button button-small">Select All Transitions</button>
                    <button id="clear-all-transitions" class="button button-small">Clear All Transitions</button>
                    <button id="select-all-kenburns" class="button button-small">Select All Ken Burns</button>
                    <button id="clear-all-kenburns" class="button button-small">Clear All Ken Burns</button>
                </div>
            </div>
        </div>

        <!-- Settings Section -->
        <div class="media-section settings-section">
            <div class="settings-header" id="settings-header">
                <h3>
                    <span class="header-text">Settings</span>
                    <span class="header-icon">▼</span>
                </h3>
                <p class="settings-status">Using: <strong id="current-preset">Classic Memorial</strong></p>
            </div>

            <div class="settings-content" id="settings-content" style="display: none;">
            <!-- Preset Configurations -->
            <div class="settings-group">
                <label class="settings-label">Preset Configuration</label>
                <select id="preset-config" class="settings-select">
                    <option value="custom">Custom</option>
                    <option value="classic" selected>Classic Memorial</option>
                    <option value="cinematic">Cinematic</option>
                    <option value="minimal">Minimal</option>
                    <option value="dynamic">Dynamic</option>
                </select>
                <p class="settings-description" id="preset-description">Traditional memorial with balanced settings</p>
            </div>

            <!-- Basic Settings -->
            <div class="settings-panel" id="basic-settings">
                <!-- Media Scaling -->
                <div class="settings-group">
                    <label class="settings-label">Image Scale</label>
                    <div class="slider-container">
                        <input type="range" id="image-scale" class="settings-slider" min="0.1" max="2.0" step="0.1" value="1.0">
                        <span class="slider-value" id="image-scale-value">1.0x</span>
                    </div>
                    <p class="settings-hint">Adjust image size (0.5 = half, 2.0 = double)</p>
                </div>

                <div class="settings-group">
                    <label class="settings-label">Video Scale</label>
                    <div class="slider-container">
                        <input type="range" id="video-scale" class="settings-slider" min="0.1" max="2.0" step="0.1" value="1.0">
                        <span class="slider-value" id="video-scale-value">1.0x</span>
                    </div>
                    <p class="settings-hint">Adjust video size (0.5 = half, 2.0 = double)</p>
                </div>

                <!-- Timing Controls -->
                <div class="settings-group">
                    <label class="settings-label">Image Duration</label>
                    <div class="slider-container">
                        <input type="range" id="image-duration" class="settings-slider" min="2" max="10" step="0.5" value="4">
                        <span class="slider-value" id="image-duration-value">4s</span>
                    </div>
                    <p class="settings-hint">How long each image displays</p>
                </div>

                <div class="settings-group">
                    <label class="settings-label">Transition Duration</label>
                    <div class="slider-container">
                        <input type="range" id="transition-duration" class="settings-slider" min="0.5" max="3" step="0.25" value="1">
                        <span class="slider-value" id="transition-duration-value">1s</span>
                    </div>
                    <p class="settings-hint">Duration of transitions between media</p>
                </div>
            </div>

            <!-- Advanced Settings Toggle -->
            <div class="settings-toggle">
                <button id="toggle-advanced" class="button button-secondary">
                    <span class="toggle-text">Show Advanced Settings</span>
                    <span class="toggle-icon">▼</span>
                </button>
            </div>

            <!-- Advanced Settings (Hidden by default) -->
            <div class="settings-panel" id="advanced-settings" style="display: none;">
                <!-- Visual Effects -->
                <h4>Visual Effects</h4>

                <div class="settings-group">
                    <label class="settings-label">Ken Burns Effect Intensity</label>
                    <div class="slider-container">
                        <input type="range" id="ken-burns-intensity" class="settings-slider" min="0" max="2" step="0.1" value="1">
                        <span class="slider-value" id="ken-burns-value">1.0x</span>
                    </div>
                    <p class="settings-hint">0 = disabled, 1 = normal, 2 = dramatic</p>
                </div>

                <div class="settings-group">
                    <label class="settings-label">Background Blur</label>
                    <div class="slider-container">
                        <input type="range" id="background-blur" class="settings-slider" min="0" max="20" step="1" value="0">
                        <span class="slider-value" id="background-blur-value">0px</span>
                    </div>
                    <p class="settings-hint">Blur effect for background image</p>
                </div>

                <div class="settings-group">
                    <label class="settings-label">Media Shadow</label>
                    <div class="checkbox-container">
                        <input type="checkbox" id="media-shadow" class="settings-checkbox">
                        <label for="media-shadow">Add drop shadow to media</label>
                    </div>
                </div>

                <div class="settings-group">
                    <label class="settings-label">Padding Color (when no background)</label>
                    <div class="color-picker-container">
                        <input type="color" id="padding-color" class="settings-color" value="#000000">
                        <span class="color-value">#000000</span>
                    </div>
                </div>

                <!-- Output Quality -->
                <h4>Output Quality</h4>

                <div class="settings-group">
                    <label class="settings-label">Video Quality</label>
                    <select id="video-quality" class="settings-select">
                        <option value="low">Low (2 Mbps) - Smaller file</option>
                        <option value="medium" selected>Medium (4 Mbps) - Balanced</option>
                        <option value="high">High (8 Mbps) - Better quality</option>
                        <option value="ultra">Ultra (12 Mbps) - Best quality</option>
                    </select>
                </div>

                <div class="settings-group">
                    <label class="settings-label">Resolution</label>
                    <select id="output-resolution" class="settings-select">
                        <option value="720p">720p (720×1280)</option>
                        <option value="1080p" selected>1080p (1080×1920)</option>
                        <option value="original">Original (as source)</option>
                    </select>
                </div>

                <div class="settings-group">
                    <label class="settings-label">Frame Rate</label>
                    <select id="frame-rate" class="settings-select">
                        <option value="24">24 fps - Cinematic</option>
                        <option value="30" selected>30 fps - Standard</option>
                        <option value="60">60 fps - Smooth</option>
                    </select>
                </div>

                <!-- Audio Settings -->
                <h4>Audio</h4>

                <div class="settings-group">
                    <label class="settings-label">Music Volume</label>
                    <div class="slider-container">
                        <input type="range" id="music-volume" class="settings-slider" min="0" max="100" step="5" value="80">
                        <span class="slider-value" id="music-volume-value">80%</span>
                    </div>
                </div>

                <div class="settings-group">
                    <label class="settings-label">Audio Fade</label>
                    <div class="checkbox-container">
                        <input type="checkbox" id="audio-fade" class="settings-checkbox" checked>
                        <label for="audio-fade">Fade in/out at start/end</label>
                    </div>
                </div>
            </div>
            </div> <!-- End settings-content -->
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
            <li>15-40 custom-selected images with Ken Burns effects</li>
            <li>1-5 video clips playing to full duration</li>
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

// Debug information
console.log('=== MEMORIANS POC DEBUG INFO ===');
console.log('Plugin Version: <?php echo MEMORIANS_POC_VERSION; ?>');
console.log('Page Load Time: <?php echo date('Y-m-d H:i:s'); ?>');
console.log('Unique Load ID: <?php echo uniqid(); ?>');
console.log('Cache Buster: <?php echo $force_reload; ?>');
console.log('=================================');
</script>

<script src="<?php echo MEMORIANS_POC_PLUGIN_URL; ?>assets/js/video-player.js?ver=<?php echo MEMORIANS_POC_VERSION; ?>&t=<?php echo $force_reload; ?>&nocache=<?php echo uniqid(); ?>"></script>
</body>
</html>
