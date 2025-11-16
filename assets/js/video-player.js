/**
 * Memorians POC Video Player JavaScript
 */

(function($) {
    'use strict';

    var MemoriansVideoPlayer = {
        currentTemplate: 'classic',
        progressInterval: null,
        isGenerating: false,

        // Media library data
        mediaLibrary: null,
        selectedImages: [],
        selectedVideos: [],
        selectedAudio: [],
        selectedBackground: null, // Single background ID or null for no background
        requirements: {
            images: { min: 15, max: 40 },
            videos: { min: 1, max: 5 },
            audio: { min: 1, max: 1 },
            background: { min: 0, max: 1 } // Optional
        },

        // Settings configuration
        settings: {
            imageScale: 1.0,
            videoScale: 1.0,
            imageDuration: 4,
            transitionDuration: 1,
            kenBurnsIntensity: 1.0,
            backgroundBlur: 0,
            mediaShadow: false,
            paddingColor: '#000000',
            videoQuality: 'medium',
            outputResolution: '1080p',
            frameRate: 30,
            musicVolume: 80,
            audioFade: true
        },

        // Preset configurations
        presets: {
            classic: {
                imageScale: 1.0,
                videoScale: 1.0,
                imageDuration: 4,
                transitionDuration: 1,
                kenBurnsIntensity: 1.0,
                backgroundBlur: 0,
                mediaShadow: false,
                videoQuality: 'medium',
                frameRate: 30,
                musicVolume: 80,
                audioFade: true
            },
            cinematic: {
                imageScale: 0.8,
                videoScale: 0.9,
                imageDuration: 6,
                transitionDuration: 2,
                kenBurnsIntensity: 1.5,
                backgroundBlur: 5,
                mediaShadow: true,
                videoQuality: 'high',
                frameRate: 24,
                musicVolume: 70,
                audioFade: true
            },
            minimal: {
                imageScale: 0.6,
                videoScale: 0.6,
                imageDuration: 3,
                transitionDuration: 0.5,
                kenBurnsIntensity: 0.5,
                backgroundBlur: 0,
                mediaShadow: false,
                videoQuality: 'medium',
                frameRate: 30,
                musicVolume: 60,
                audioFade: true
            },
            dynamic: {
                imageScale: 1.2,
                videoScale: 1.2,
                imageDuration: 3,
                transitionDuration: 1.5,
                kenBurnsIntensity: 2.0,
                backgroundBlur: 0,
                mediaShadow: true,
                videoQuality: 'high',
                frameRate: 60,
                musicVolume: 90,
                audioFade: false
            }
        },

        // Gallery data
        videoGallery: [],

        // Template transition defaults
        templateTransitions: {
            classic: ['fade', 'dissolve', 'smoothleft', 'smoothright'],
            modern: ['smoothleft', 'smoothright', 'circleopen', 'circleclose', 'pixelize'],
            elegant: ['fade', 'fadeblack', 'fadewhite', 'dissolve', 'circleopen', 'circleclose']
        },

        // All Ken Burns patterns available
        allKenBurnsPatterns: ['zoom_in', 'zoom_out', 'pan_left', 'pan_right', 'pan_tl', 'pan_br', 'pan_up', 'pan_down'],

        // Advanced options state
        advancedOptions: {
            overrideDefaults: false,
            selectedTransitions: [],
            selectedKenBurns: []
        },
        currentVideo: null,

        // Preview tracking
        currentPreviewVideo: null,
        currentPreviewAudio: null,

        init: function() {
            // Initialize arrays as empty to prevent any type corruption
            this.selectedImages = [];
            this.selectedVideos = [];
            this.selectedAudio = [];
            this.selectedBackground = null;

            // CRITICAL: Ensure requirements are properly set with correct values
            // This prevents any corruption from previous state
            this.requirements = {
                images: { min: 15, max: 40 },
                videos: { min: 1, max: 5 },
                audio: { min: 1, max: 1 },
                background: { min: 0, max: 1 }
            };

            console.log('Initialized with requirements:', JSON.stringify(this.requirements));
            console.log('Images max:', this.requirements.images.max);
            console.log('Videos max:', this.requirements.videos.max);

            this.bindEvents();
            this.initializeSettings();
            // Apply Classic Memorial preset by default
            this.applyPreset('classic');
            // Initialize Advanced Options
            this.initializeAdvancedOptions();
            this.loadVideoGallery(); // Start by loading gallery
        },

        bindEvents: function() {
            var self = this;

            // Back to gallery button
            $('#back-to-gallery').on('click', function() {
                self.stopVideo();
                self.loadVideoGallery();
            });

            // Start generation button
            $('#start-generation').on('click', function() {
                self.generateVideoWithSelection();
            });

            // Edit selection button
            $('#edit-selection').on('click', function() {
                self.stopVideo();

                // Always load media library when editing
                if (!self.mediaLibrary) {
                    self.loadMediaLibrary();
                } else {
                    self.showMediaSelection();
                }
            });

            // Retry button
            $('#retry-button').on('click', function() {
                if (!self.mediaLibrary) {
                    self.loadMediaLibrary();
                } else {
                    self.showMediaSelection();
                }
            });

            // Template change - controls panel (after video plays)
            $('#template-style').on('change', function() {
                self.currentTemplate = $(this).val();
                // Sync with media selection panel template selector
                $('#template-style-selection').val($(this).val());
                // Update description
                self.updateTemplateDescription($(this).val());
            });

            // Template change - media selection panel (before generation)
            $('#template-style-selection').on('change', function() {
                self.currentTemplate = $(this).val();
                // Sync with controls panel template selector
                $('#template-style').val($(this).val());
                // Update description
                self.updateTemplateDescription($(this).val());
            });

            // Download button
            $('#download-video').on('click', function() {
                self.downloadVideo();
            });

            // Fullscreen button
            $('#fullscreen-btn').on('click', function() {
                self.toggleFullscreen();
            });

            // Video events
            $('#memorial-video').on('loadeddata', function() {
                $('#download-video').show();
                $('#fullscreen-btn').show();
            });
        },

        initializeSettings: function() {
            var self = this;

            // Settings section expand/collapse
            $('#settings-header').on('click', function() {
                var $header = $(this);
                var $content = $('#settings-content');

                if ($content.is(':visible')) {
                    $content.slideUp(300);
                    $header.removeClass('expanded');
                } else {
                    $content.slideDown(300);
                    $header.addClass('expanded');
                }
            });

            // Initialize slider values
            $('.settings-slider').each(function() {
                var $slider = $(this);
                var $value = $('#' + $slider.attr('id') + '-value');
                var value = $slider.val();

                // Update display value
                if ($slider.attr('id').indexOf('scale') > -1 || $slider.attr('id').indexOf('ken-burns') > -1) {
                    $value.text(value + 'x');
                } else if ($slider.attr('id').indexOf('duration') > -1) {
                    $value.text(value + 's');
                } else if ($slider.attr('id').indexOf('blur') > -1) {
                    $value.text(value + 'px');
                } else if ($slider.attr('id').indexOf('volume') > -1) {
                    $value.text(value + '%');
                }
            });

            // Slider change events
            $('.settings-slider').on('input', function() {
                var $slider = $(this);
                var $value = $('#' + $slider.attr('id') + '-value');
                var value = $slider.val();
                var settingKey = $slider.attr('id').replace(/-/g, '');

                // Update display value
                if ($slider.attr('id').indexOf('scale') > -1 || $slider.attr('id').indexOf('ken-burns') > -1) {
                    $value.text(value + 'x');
                } else if ($slider.attr('id').indexOf('duration') > -1) {
                    $value.text(value + 's');
                } else if ($slider.attr('id').indexOf('blur') > -1) {
                    $value.text(value + 'px');
                } else if ($slider.attr('id').indexOf('volume') > -1) {
                    $value.text(value + '%');
                }

                // Map HTML IDs to settings keys
                var keyMap = {
                    'imagescale': 'imageScale',
                    'videoscale': 'videoScale',
                    'imageduration': 'imageDuration',
                    'transitionduration': 'transitionDuration',
                    'kenburnsintensity': 'kenBurnsIntensity',
                    'backgroundblur': 'backgroundBlur',
                    'musicvolume': 'musicVolume'
                };

                // Update settings
                if (keyMap[settingKey]) {
                    self.settings[keyMap[settingKey]] = parseFloat(value);
                }

                // Mark preset as custom when any setting changes
                $('#preset-config').val('custom');
                $('#preset-description').text('Customize all settings manually');
                $('#current-preset').text('Custom');
            });

            // Checkbox change events
            $('.settings-checkbox').on('change', function() {
                var $checkbox = $(this);
                var settingKey = $checkbox.attr('id').replace(/-/g, '');

                // Map HTML IDs to settings keys
                var keyMap = {
                    'mediashadow': 'mediaShadow',
                    'audiofade': 'audioFade'
                };

                if (keyMap[settingKey]) {
                    self.settings[keyMap[settingKey]] = $checkbox.prop('checked');
                }

                // Mark preset as custom
                $('#preset-config').val('custom');
                $('#preset-description').text('Customize all settings manually');
                $('#current-preset').text('Custom');
            });

            // Select change events
            $('.settings-select').not('#preset-config').on('change', function() {
                var $select = $(this);
                var settingKey = $select.attr('id').replace(/-/g, '');

                // Map HTML IDs to settings keys
                var keyMap = {
                    'videoquality': 'videoQuality',
                    'outputresolution': 'outputResolution',
                    'framerate': 'frameRate'
                };

                if (keyMap[settingKey]) {
                    var value = $select.val();
                    // Frame rate needs to be numeric
                    if (settingKey === 'framerate') {
                        value = parseInt(value);
                    }
                    self.settings[keyMap[settingKey]] = value;
                }

                // Mark preset as custom
                $('#preset-config').val('custom');
                $('#preset-description').text('Customize all settings manually');
                $('#current-preset').text('Custom');
            });

            // Color picker change
            $('#padding-color').on('change', function() {
                var color = $(this).val();
                $(this).next('.color-value').text(color);
                self.settings.paddingColor = color;

                // Mark preset as custom
                $('#preset-config').val('custom');
                $('#preset-description').text('Customize all settings manually');
                $('#current-preset').text('Custom');
            });

            // Advanced settings toggle
            $('#toggle-advanced').on('click', function(e) {
                e.preventDefault();
                var $button = $(this);
                var $advanced = $('#advanced-settings');

                if ($advanced.is(':visible')) {
                    $advanced.slideUp(300);
                    $button.removeClass('active');
                    $button.find('.toggle-text').text('Show Advanced Settings');
                } else {
                    $advanced.slideDown(300);
                    $button.addClass('active');
                    $button.find('.toggle-text').text('Hide Advanced Settings');
                }
            });

            // Preset configuration change
            $('#preset-config').on('change', function() {
                var preset = $(this).val();

                if (preset !== 'custom' && self.presets[preset]) {
                    self.applyPreset(preset);
                }

                // Update description
                var descriptions = {
                    custom: 'Customize all settings manually',
                    classic: 'Traditional memorial with balanced settings',
                    cinematic: 'Film-like quality with slower pacing and blur effects',
                    minimal: 'Clean and simple with smaller media and quick transitions',
                    dynamic: 'High energy with larger media and dramatic effects'
                };

                $('#preset-description').text(descriptions[preset] || 'Customize all settings manually');

                // Update current preset display in header
                var presetNames = {
                    custom: 'Custom',
                    classic: 'Classic Memorial',
                    cinematic: 'Cinematic',
                    minimal: 'Minimal',
                    dynamic: 'Dynamic'
                };
                $('#current-preset').text(presetNames[preset] || 'Custom');
            });

            console.log('Settings initialized');
        },

        initializeAdvancedOptions: function() {
            var self = this;

            // Advanced Options section expand/collapse
            $('#advanced-header').on('click', function() {
                var $header = $(this);
                var $content = $('#advanced-content');

                if ($content.is(':visible')) {
                    $content.slideUp(300);
                    $header.removeClass('expanded');
                } else {
                    $content.slideDown(300);
                    $header.addClass('expanded');
                }
            });

            // Override defaults toggle
            $('#override-defaults').on('change', function() {
                var isOverride = $(this).prop('checked');
                self.advancedOptions.overrideDefaults = isOverride;

                if (isOverride) {
                    $('#advanced-status-text').text('Custom Selection');
                    $('.transitions-group, .kenburns-group').removeClass('disabled');
                    $('.transition-checkbox, .kenburns-checkbox').prop('disabled', false);
                } else {
                    $('#advanced-status-text').text('Template Defaults');
                    $('.transitions-group, .kenburns-group').addClass('disabled');
                    $('.transition-checkbox, .kenburns-checkbox').prop('disabled', true);
                    // Reapply template defaults
                    self.updateTransitionCheckboxes();
                }
            });

            // Transition checkbox changes
            $('.transition-checkbox').on('change', function() {
                if (self.advancedOptions.overrideDefaults) {
                    self.updateSelectedTransitions();
                }
            });

            // Ken Burns checkbox changes
            $('.kenburns-checkbox').on('change', function() {
                if (self.advancedOptions.overrideDefaults) {
                    self.updateSelectedKenBurns();
                }
            });

            // Quick action buttons
            $('#select-all-transitions').on('click', function(e) {
                e.preventDefault();
                if (self.advancedOptions.overrideDefaults) {
                    $('.transition-checkbox').prop('checked', true);
                    self.updateSelectedTransitions();
                }
            });

            $('#clear-all-transitions').on('click', function(e) {
                e.preventDefault();
                if (self.advancedOptions.overrideDefaults) {
                    $('.transition-checkbox').prop('checked', false);
                    self.updateSelectedTransitions();
                }
            });

            $('#select-all-kenburns').on('click', function(e) {
                e.preventDefault();
                if (self.advancedOptions.overrideDefaults) {
                    $('.kenburns-checkbox').prop('checked', true);
                    self.updateSelectedKenBurns();
                }
            });

            $('#clear-all-kenburns').on('click', function(e) {
                e.preventDefault();
                if (self.advancedOptions.overrideDefaults) {
                    $('.kenburns-checkbox').prop('checked', false);
                    self.updateSelectedKenBurns();
                }
            });

            // Template style change - update Advanced Options
            $('#template-style-selection').on('change.advanced', function() {
                if (!self.advancedOptions.overrideDefaults) {
                    self.updateTransitionCheckboxes();
                }
            });

            // Initialize with template defaults
            this.updateTransitionCheckboxes();

            // By default, select all Ken Burns patterns
            $('.kenburns-checkbox').prop('checked', true);
            this.updateSelectedKenBurns();

            // Start with checkboxes disabled (not overriding)
            $('.transitions-group, .kenburns-group').addClass('disabled');
            $('.transition-checkbox, .kenburns-checkbox').prop('disabled', true);

            console.log('Advanced Options initialized');
        },

        updateTransitionCheckboxes: function() {
            var template = $('#template-style-selection').val() || 'classic';
            var transitions = this.templateTransitions[template] || this.templateTransitions['classic'];

            // Uncheck all first
            $('.transition-checkbox').prop('checked', false);

            // Check template defaults
            transitions.forEach(function(transition) {
                $('#trans-' + transition).prop('checked', true);
            });

            this.updateSelectedTransitions();
        },

        updateSelectedTransitions: function() {
            var selected = [];
            $('.transition-checkbox:checked').each(function() {
                selected.push($(this).val());
            });

            this.advancedOptions.selectedTransitions = selected;
            $('#transition-count').text('(' + selected.length + ' selected)');
        },

        updateSelectedKenBurns: function() {
            var selected = [];
            $('.kenburns-checkbox:checked').each(function() {
                selected.push($(this).val());
            });

            this.advancedOptions.selectedKenBurns = selected;
            $('#kenburns-count').text('(' + selected.length + ' selected)');
        },

        applyPreset: function(presetName) {
            var self = this;
            var preset = this.presets[presetName];

            if (!preset) return;

            // Apply all preset values
            for (var key in preset) {
                if (preset.hasOwnProperty(key)) {
                    this.settings[key] = preset[key];

                    // Update UI elements
                    switch(key) {
                        case 'imageScale':
                            $('#image-scale').val(preset[key]);
                            $('#image-scale-value').text(preset[key] + 'x');
                            break;
                        case 'videoScale':
                            $('#video-scale').val(preset[key]);
                            $('#video-scale-value').text(preset[key] + 'x');
                            break;
                        case 'imageDuration':
                            $('#image-duration').val(preset[key]);
                            $('#image-duration-value').text(preset[key] + 's');
                            break;
                        case 'transitionDuration':
                            $('#transition-duration').val(preset[key]);
                            $('#transition-duration-value').text(preset[key] + 's');
                            break;
                        case 'kenBurnsIntensity':
                            $('#ken-burns-intensity').val(preset[key]);
                            $('#ken-burns-value').text(preset[key] + 'x');
                            break;
                        case 'backgroundBlur':
                            $('#background-blur').val(preset[key]);
                            $('#background-blur-value').text(preset[key] + 'px');
                            break;
                        case 'mediaShadow':
                            $('#media-shadow').prop('checked', preset[key]);
                            break;
                        case 'videoQuality':
                            $('#video-quality').val(preset[key]);
                            break;
                        case 'outputResolution':
                            $('#output-resolution').val(preset[key]);
                            break;
                        case 'frameRate':
                            $('#frame-rate').val(preset[key]);
                            break;
                        case 'musicVolume':
                            $('#music-volume').val(preset[key]);
                            $('#music-volume-value').text(preset[key] + '%');
                            break;
                        case 'audioFade':
                            $('#audio-fade').prop('checked', preset[key]);
                            break;
                    }
                }
            }

            console.log('Applied preset:', presetName, preset);
        },

        checkForCachedVideo: function() {
            var self = this;

            console.log('=== CHECK FOR CACHED VIDEO ===');

            // Check if there's a cached video or generation in progress
            $.ajax({
                url: memoriansPoC.generateUrl,
                data: {
                    template: self.currentTemplate
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Cache check response:', response);

                    if (response.success && (response.status === 'cached' || response.status === 'completed')) {
                        // Show the cached video
                        console.log('Found cached video, showing it');
                        self.showVideo(response.video_url);
                        $('#controls-panel').show();
                        $('#info-panel').show();
                    } else if (response.success && response.status === 'generating') {
                        // Continue where we left off
                        console.log('Generation in progress, resuming...');
                        self.showLoading();
                        self.isGenerating = true;
                        self.startProgressPolling();
                    } else {
                        // No cache or error, show media selection
                        console.log('No cache found (status: ' + (response.status || 'unknown') + '), loading media library');
                        self.loadMediaLibrary();
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Cache check error:', status, error);
                    // Error, show media selection
                    self.loadMediaLibrary();
                }
            });
        },

        loadMediaLibrary: function() {
            var self = this;

            $.ajax({
                url: memoriansPoC.mediaLibraryUrl,
                dataType: 'json',
                success: function(response) {
                    if (response && response.success) {
                        console.log('Media library loaded: ' + response.data.images.length + ' images, ' +
                                   response.data.videos.length + ' videos, ' +
                                   response.data.audio.length + ' audio tracks');

                        self.mediaLibrary = response.data;

                        // Debug requirements from backend
                        console.log('Requirements from backend:', JSON.stringify(response.data.requirements));
                        console.log('Backend images.max:', response.data.requirements?.images?.max);
                        console.log('Backend videos.max:', response.data.requirements?.videos?.max);

                        // Validate requirements structure before using it
                        if (response.data.requirements &&
                            response.data.requirements.images &&
                            response.data.requirements.videos &&
                            typeof response.data.requirements.images.max === 'number' &&
                            typeof response.data.requirements.videos.max === 'number') {
                            // Backend requirements are valid, use them
                            self.requirements = {
                                images: {
                                    min: response.data.requirements.images.min || 15,
                                    max: response.data.requirements.images.max || 40
                                },
                                videos: {
                                    min: response.data.requirements.videos.min || 1,
                                    max: response.data.requirements.videos.max || 5
                                },
                                audio: {
                                    min: response.data.requirements.audio ? response.data.requirements.audio.min : 1,
                                    max: response.data.requirements.audio ? response.data.requirements.audio.max : 1
                                },
                                background: {
                                    min: response.data.requirements.background ? response.data.requirements.background.min : 0,
                                    max: response.data.requirements.background ? response.data.requirements.background.max : 1
                                }
                            };
                            console.log('Requirements set to:', JSON.stringify(self.requirements));
                            console.log('Final images.max:', self.requirements.images.max);
                            console.log('Final videos.max:', self.requirements.videos.max);
                        } else {
                            console.warn('Backend requirements invalid, using defaults');
                            console.warn('Keeping default requirements:', JSON.stringify(self.requirements));
                        }

                        // Reset selection arrays (no pre-selection)
                        self.selectedImages = [];
                        self.selectedVideos = [];
                        self.selectedAudio = [];

                        self.renderMediaGrid();
                        self.showMediaSelection();
                    } else {
                        var errorMsg = 'Unknown error';
                        if (response && response.error) {
                            errorMsg = response.error;
                        } else if (typeof response === 'string') {
                            errorMsg = 'Server returned: ' + response.substring(0, 200);
                        }
                        console.error('Media library error:', errorMsg);
                        self.showError('Failed to load media library: ' + errorMsg);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', status, error);
                    console.error('Response text:', xhr.responseText);
                    self.showError('Network error while loading media library. Check console for details.');
                }
            });
        },

        renderMediaGrid: function() {
            var self = this;
            console.log('renderMediaGrid called');

            // Render images with onclick for Safari compatibility
            var imagesHtml = '';
            $.each(this.mediaLibrary.images, function(index, image) {
                // Add onclick="void(0)" for better Safari event delegation support
                imagesHtml += '<div class="media-item" data-type="image" data-id="' + image.id + '" onclick="void(0)">';
                imagesHtml += '<img src="' + image.url + '" alt="' + image.filename + '">';
                imagesHtml += '<div class="media-item-overlay"><span class="media-item-checkmark">✓</span></div>';
                imagesHtml += '<div class="media-item-label">' + image.filename + '</div>';
                imagesHtml += '</div>';
            });
            $('#images-grid').html(imagesHtml);

            // Render videos with onclick for Safari compatibility
            var videosHtml = '';
            $.each(this.mediaLibrary.videos, function(index, video) {
                // Add onclick="void(0)" for better Safari event delegation support
                videosHtml += '<div class="video-item" data-type="video" data-id="' + video.id + '" data-url="' + video.url + '" onclick="void(0)">';
                videosHtml += '<div class="video-item-preview">';
                videosHtml += '<video class="media-video-element" src="' + video.url + '#t=0.001" muted preload="metadata"></video>';
                videosHtml += '<div class="video-item-overlay"><span class="video-item-checkmark">✓</span></div>';
                videosHtml += '</div>';
                videosHtml += '<div class="video-item-info">';
                videosHtml += '<div class="video-item-name">' + video.filename + '</div>';
                videosHtml += '</div>';
                videosHtml += '<button class="video-preview-btn" data-video-id="' + video.id + '" title="Play/Pause"><span class="video-icon">▶</span></button>';
                videosHtml += '</div>';
            });
            $('#videos-grid').html(videosHtml);

            // Render audio
            var audioHtml = '';
            $.each(this.mediaLibrary.audio, function(index, audio) {
                audioHtml += '<div class="audio-item" data-type="audio" data-id="' + audio.id + '" data-url="' + audio.url + '">';
                audioHtml += '<div class="audio-item-icon">🎵</div>';
                audioHtml += '<div class="audio-item-info">';
                audioHtml += '<div class="audio-item-name">' + audio.filename + '</div>';
                audioHtml += '<audio class="audio-element" src="' + audio.url + '" preload="metadata"></audio>';
                audioHtml += '</div>';
                audioHtml += '<button class="audio-preview-btn" data-audio-id="' + audio.id + '" title="Play/Pause"><span class="audio-icon">▶</span></button>';
                audioHtml += '<div class="audio-item-checkmark">✓</div>';
                audioHtml += '</div>';
            });
            $('#audio-list').html(audioHtml);

            // Render background images
            var backgroundHtml = '';

            // Add "No background" option first
            backgroundHtml += '<div class="background-item selected" data-type="background" data-id="none">';
            backgroundHtml += '<div class="background-item-preview">';
            backgroundHtml += '<div class="no-background-placeholder">No Background</div>';
            backgroundHtml += '</div>';
            backgroundHtml += '<div class="background-item-info">';
            backgroundHtml += '<div class="background-item-name">No Background</div>';
            backgroundHtml += '</div>';
            backgroundHtml += '<div class="background-item-checkmark">✓</div>';
            backgroundHtml += '</div>';

            // Add actual background images
            $.each(this.mediaLibrary.backgrounds || [], function(index, bg) {
                backgroundHtml += '<div class="background-item" data-type="background" data-id="' + bg.id + '">';
                backgroundHtml += '<div class="background-item-preview">';
                backgroundHtml += '<img src="' + bg.url + '" alt="' + bg.filename + '">';
                backgroundHtml += '</div>';
                backgroundHtml += '<div class="background-item-info">';
                backgroundHtml += '<div class="background-item-name">' + bg.filename + '</div>';
                backgroundHtml += '</div>';
                backgroundHtml += '<div class="background-item-checkmark">✓</div>';
                backgroundHtml += '</div>';
            });
            $('#background-list').html(backgroundHtml);

            // Unbind ALL events with our namespace first to prevent any duplicates
            $('#images-grid').off('click.memorians');
            $('#videos-grid').off('click.memorians');
            $('#audio-list').off('click.memorians');
            $('#background-list').off('click.memorians');

            // Use event delegation with namespaced events for clean binding/unbinding
            // CRITICAL: Force all IDs to strings before passing to selection functions
            $('#images-grid').on('click.memorians', '.media-item[data-type="image"]', function(e) {
                // Prevent event from bubbling up
                e.stopPropagation();
                e.stopImmediatePropagation();

                // Force to string to prevent Safari type issues
                var id = String($(this).attr('data-id'));
                console.log('Image clicked - ID:', id, 'Type:', typeof id);
                self.toggleMediaSelection('image', id);
                return false; // Prevent any further event processing
            });

            $('#videos-grid').on('click.memorians', '.video-item', function(e) {
                // Don't select if clicking the preview button
                if ($(e.target).closest('.video-preview-btn').length) {
                    return;
                }
                e.stopPropagation();
                e.stopImmediatePropagation();

                // Force to string to prevent Safari type issues
                var id = String($(this).attr('data-id'));
                console.log('Video clicked - ID:', id, 'Type:', typeof id);
                self.toggleMediaSelection('video', id);
                return false;
            });

            $('#videos-grid').on('click.memorians', '.video-preview-btn', function(e) {
                e.stopPropagation();
                e.stopImmediatePropagation();
                // Force to string for consistency
                var videoId = String($(this).attr('data-video-id'));
                self.toggleVideoPreview(videoId, $(this));
                return false;
            });

            $('#audio-list').on('click.memorians', '.audio-item', function(e) {
                // Don't select if clicking the preview button
                if ($(e.target).closest('.audio-preview-btn').length) {
                    return;
                }
                e.stopPropagation();
                e.stopImmediatePropagation();

                // Force to string to prevent Safari type issues
                var id = String($(this).attr('data-id'));
                console.log('Audio clicked - ID:', id, 'Type:', typeof id);
                self.toggleMediaSelection('audio', id);
                return false;
            });

            $('#audio-list').on('click.memorians', '.audio-preview-btn', function(e) {
                e.stopPropagation();
                e.stopImmediatePropagation();
                // Force to string for consistency
                var audioId = String($(this).attr('data-audio-id'));
                self.toggleAudioPreview(audioId, $(this));
                return false;
            });

            $('#background-list').on('click.memorians', '.background-item', function(e) {
                e.stopPropagation();
                e.stopImmediatePropagation();
                // Force to string to prevent Safari type issues
                var id = String($(this).attr('data-id'));
                console.log('Background clicked - ID:', id, 'Type:', typeof id);
                self.toggleMediaSelection('background', id);
                return false;
            });
        },

        toggleMediaSelection: function(type, id) {
            // CRITICAL FIX: Force ID to string to prevent type mismatch on Safari
            // Safari's aggressive type conversion can cause numbers in arrays
            id = String(id);
            console.log('Toggle selection - type:', type, 'id:', id, '(typeof:', typeof id + ')');
            console.log('Current requirements:', JSON.stringify(this.requirements));
            console.log('Images max:', this.requirements.images.max, 'Videos max:', this.requirements.videos.max);

            // Handle background separately (single selection, optional)
            if (type === 'background') {
                // Always allow selection - just replace current
                this.selectedBackground = id === 'none' ? null : String(id);
                this.updateSelectionUI();
                return;
            }

            var index;

            // Work directly with the actual arrays, not references
            if (type === 'image') {
                // Ensure array contains only strings for consistent comparison
                this.selectedImages = this.selectedImages.map(function(item) { return String(item); });

                // DEFENSIVE: Get max value with fallback
                var maxImages = (this.requirements && this.requirements.images && this.requirements.images.max) || 40;
                console.log('Image selection - Current count:', this.selectedImages.length, 'Max allowed:', maxImages);

                index = this.selectedImages.indexOf(id);
                console.log('Image array:', JSON.stringify(this.selectedImages), 'Looking for:', id, 'Found at index:', index);

                if (index > -1) {
                    // Deselect
                    this.selectedImages.splice(index, 1);
                    console.log('Image deselected:', id, 'Remaining:', this.selectedImages.length);
                } else {
                    // Select
                    if (this.selectedImages.length < maxImages) {
                        this.selectedImages.push(id);
                        console.log('Image selected:', id, '(' + this.selectedImages.length + '/' + maxImages + ')');
                    } else {
                        console.log('Selection limit reached for images (' + maxImages + ')');
                        console.warn('DEBUGGING: requirements object:', JSON.stringify(this.requirements));
                        return;
                    }
                }
            } else if (type === 'video') {
                // Ensure array contains only strings for consistent comparison
                this.selectedVideos = this.selectedVideos.map(function(item) { return String(item); });

                // DEFENSIVE: Get max value with fallback
                var maxVideos = (this.requirements && this.requirements.videos && this.requirements.videos.max) || 5;
                console.log('Video selection - Current count:', this.selectedVideos.length, 'Max allowed:', maxVideos);

                index = this.selectedVideos.indexOf(id);
                console.log('Video array:', JSON.stringify(this.selectedVideos), 'Looking for:', id, 'Found at index:', index);

                if (index > -1) {
                    // Deselect
                    this.selectedVideos.splice(index, 1);
                    console.log('Video deselected:', id, 'Remaining:', this.selectedVideos.length);
                } else {
                    // Select
                    if (this.selectedVideos.length < maxVideos) {
                        this.selectedVideos.push(id);
                        console.log('Video selected:', id, '(' + this.selectedVideos.length + '/' + maxVideos + ')');
                    } else {
                        console.log('Selection limit reached for videos (' + maxVideos + ')');
                        console.warn('DEBUGGING: requirements object:', JSON.stringify(this.requirements));
                        return;
                    }
                }
            } else if (type === 'audio') {
                // Ensure array contains only strings for consistent comparison
                this.selectedAudio = this.selectedAudio.map(function(item) { return String(item); });

                index = this.selectedAudio.indexOf(id);

                if (index > -1) {
                    // Deselect
                    this.selectedAudio.splice(index, 1);
                    console.log('Audio deselected:', id);
                } else {
                    // Select - auto-switch for audio (only 1 allowed)
                    if (this.selectedAudio.length < this.requirements.audio.max) {
                        this.selectedAudio.push(id);
                        console.log('Audio selected:', id);
                    } else {
                        this.selectedAudio[0] = id;
                        console.log('Auto-switching audio selection to:', id);
                    }
                }
            }

            this.updateSelectionUI();
        },

        updateSelectionUI: function() {
            var self = this;

            // Update counts
            $('#images-selected-count').text(this.selectedImages.length);
            $('#videos-selected-count').text(this.selectedVideos.length);
            $('#audio-selected-count').text(this.selectedAudio.length);

            // Update status badges
            this.updateStatusBadge('images', this.selectedImages.length, this.requirements.images.min, this.requirements.images.max);
            this.updateStatusBadge('videos', this.selectedVideos.length, this.requirements.videos.min, this.requirements.videos.max);
            this.updateStatusBadge('audio', this.selectedAudio.length, this.requirements.audio.min, this.requirements.audio.max);

            // Update visual selection states
            // CRITICAL: Ensure all arrays contain only strings before comparison
            var stringImages = self.selectedImages.map(function(item) { return String(item); });
            var stringVideos = self.selectedVideos.map(function(item) { return String(item); });
            var stringAudio = self.selectedAudio.map(function(item) { return String(item); });

            $('.media-item[data-type="image"]').each(function() {
                // Force ID to string for consistent comparison
                var id = String($(this).attr('data-id'));

                if (stringImages.indexOf(id) > -1) {
                    $(this).addClass('selected');
                } else {
                    $(this).removeClass('selected');
                }

                // Disable if max limit reached and not selected
                if (stringImages.length >= self.requirements.images.max && stringImages.indexOf(id) === -1) {
                    $(this).addClass('disabled');
                } else {
                    $(this).removeClass('disabled');
                }
            });

            $('.video-item').each(function() {
                // Force ID to string for consistent comparison
                var id = String($(this).attr('data-id'));

                if (stringVideos.indexOf(id) > -1) {
                    $(this).addClass('selected');
                } else {
                    $(this).removeClass('selected');
                }

                // Disable if max limit reached and not selected
                if (stringVideos.length >= self.requirements.videos.max && stringVideos.indexOf(id) === -1) {
                    $(this).addClass('disabled');
                } else {
                    $(this).removeClass('disabled');
                }
            });

            $('.audio-item').each(function() {
                // Force ID to string for consistent comparison
                var id = String($(this).attr('data-id'));

                if (stringAudio.indexOf(id) > -1) {
                    $(this).addClass('selected');
                } else {
                    $(this).removeClass('selected');
                }

                // Disable if max limit reached and not selected
                if (stringAudio.length >= self.requirements.audio.max && stringAudio.indexOf(id) === -1) {
                    $(this).addClass('disabled');
                } else {
                    $(this).removeClass('disabled');
                }
            });

            $('.background-item').each(function() {
                // Force ID to string for consistent comparison
                var id = String($(this).attr('data-id'));
                var isNone = id === 'none';
                var backgroundStr = self.selectedBackground ? String(self.selectedBackground) : null;
                var isSelected = (isNone && backgroundStr === null) ||
                                (backgroundStr === id);

                if (isSelected) {
                    $(this).addClass('selected');
                } else {
                    $(this).removeClass('selected');
                }
            });

            // Enable/disable generate button
            this.validateSelection();
        },

        updateStatusBadge: function(type, current, min, max) {
            var badge = $('#' + type + '-status');
            badge.removeClass('ready warning error');

            if (current >= min && current <= max) {
                badge.addClass('ready');
                badge.text('✓ Ready (' + current + ')');
            } else if (current < min) {
                badge.addClass('warning');
                badge.text('⚠ Need ' + (min - current) + ' more (min ' + min + ')');
            } else {
                badge.addClass('error');
                badge.text('✗ Too many (max ' + max + ')');
            }
        },

        validateSelection: function() {
            var isValid = (
                this.selectedImages.length >= this.requirements.images.min &&
                this.selectedImages.length <= this.requirements.images.max &&
                this.selectedVideos.length >= this.requirements.videos.min &&
                this.selectedVideos.length <= this.requirements.videos.max &&
                this.selectedAudio.length >= this.requirements.audio.min &&
                this.selectedAudio.length <= this.requirements.audio.max
            );

            $('#start-generation').prop('disabled', !isValid);
        },

        showMediaSelection: function() {
            $('#media-selection-panel').show();
            $('#video-container').hide();
            $('#controls-panel').hide();
            $('#info-panel').hide();

            // Update template description to match current selection
            this.updateTemplateDescription(this.currentTemplate);
        },

        generateVideoWithSelection: function() {
            var self = this;

            if (this.isGenerating) {
                return;
            }

            this.isGenerating = true;

            // Hide media selection, show loading
            $('#media-selection-panel').hide();
            $('#video-container').show();
            this.showLoading();
            this.updateProgress(0, 'Starting video generation...');

            // Build query parameters
            var params = {
                template: this.currentTemplate,
                force: 'true',
                audio: this.selectedAudio[0]
            };

            // Add background if selected
            if (this.selectedBackground) {
                params.background = this.selectedBackground;
            }

            // Add images as array
            $.each(this.selectedImages, function(index, imageId) {
                params['images[' + index + ']'] = imageId;
            });

            // Add videos as array
            $.each(this.selectedVideos, function(index, videoId) {
                params['videos[' + index + ']'] = videoId;
            });

            // Add all settings to params
            params['settings[imageScale]'] = this.settings.imageScale;
            params['settings[videoScale]'] = this.settings.videoScale;
            params['settings[imageDuration]'] = this.settings.imageDuration;
            params['settings[transitionDuration]'] = this.settings.transitionDuration;
            params['settings[kenBurnsIntensity]'] = this.settings.kenBurnsIntensity;
            params['settings[backgroundBlur]'] = this.settings.backgroundBlur;
            params['settings[mediaShadow]'] = this.settings.mediaShadow ? '1' : '0';
            params['settings[paddingColor]'] = this.settings.paddingColor;
            params['settings[videoQuality]'] = this.settings.videoQuality;
            params['settings[outputResolution]'] = this.settings.outputResolution;
            params['settings[frameRate]'] = this.settings.frameRate;
            params['settings[musicVolume]'] = this.settings.musicVolume;
            params['settings[audioFade]'] = this.settings.audioFade ? '1' : '0';

            // Add Advanced Options if override is enabled
            if (this.advancedOptions.overrideDefaults) {
                params['advancedOptions[overrideDefaults]'] = '1';

                // Add custom transitions
                $.each(this.advancedOptions.selectedTransitions, function(index, transition) {
                    params['advancedOptions[transitions][' + index + ']'] = transition;
                });

                // Add custom Ken Burns patterns
                $.each(this.advancedOptions.selectedKenBurns, function(index, pattern) {
                    params['advancedOptions[kenBurns][' + index + ']'] = pattern;
                });
            } else {
                params['advancedOptions[overrideDefaults]'] = '0';
            }

            console.log('Sending settings:', this.settings);
            console.log('Sending advanced options:', this.advancedOptions);

            $.ajax({
                url: memoriansPoC.generateUrl,
                data: params,
                success: function(response) {
                    if (response.success) {
                        if (response.status === 'generating') {
                            self.startProgressPolling();
                        } else if (response.status === 'completed') {
                            self.isGenerating = false;
                            self.showVideo(response.video_url);
                        }
                    } else {
                        self.isGenerating = false;
                        self.showError(response.message || 'Failed to generate video');
                    }
                },
                error: function(xhr, status, error) {
                    self.isGenerating = false;
                    self.showError('Network error: ' + error);
                }
            });
        },

        startProgressPolling: function() {
            var self = this;

            console.log('=== STARTING PROGRESS POLLING ===');

            // Clear any existing interval
            if (this.progressInterval) {
                clearInterval(this.progressInterval);
            }

            // Poll every 2 seconds
            this.progressInterval = setInterval(function() {
                console.log('Polling progress...');

                $.ajax({
                    url: memoriansPoC.progressUrl,
                    data: {
                        template: self.currentTemplate
                    },
                    success: function(response) {
                        console.log('Progress response:', response);

                        if (response.status === 'completed') {
                            console.log('=== GENERATION COMPLETED ===');
                            clearInterval(self.progressInterval);
                            self.isGenerating = false;
                            $('#controls-panel').show();
                            $('#info-panel').show();
                            self.showVideo(response.video_url);

                            // Reload gallery data and re-render to include new video
                            console.log('Reloading gallery to include new video...');
                            $.ajax({
                                url: memoriansPoC.videoHistoryUrl,
                                dataType: 'json',
                                success: function(galleryResponse) {
                                    if (galleryResponse && galleryResponse.success) {
                                        self.videoGallery = galleryResponse.videos;
                                        self.renderGallery(); // Re-render gallery with new video
                                        console.log('Gallery updated with new video: ' + galleryResponse.videos.length + ' videos total');
                                    }
                                }
                            });
                        } else if (response.status === 'failed') {
                            console.log('=== GENERATION FAILED ===');
                            clearInterval(self.progressInterval);
                            self.isGenerating = false;
                            self.showError(response.error || 'Video generation failed');
                        } else if (response.status === 'generating') {
                            var progress = response.progress || 0;
                            console.log('Progress: ' + progress + '%');
                            var message = self.getProgressMessage(progress);
                            self.updateProgress(progress, message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Progress poll error:', status, error);
                        // Continue polling even on error
                    }
                });
            }, 2000);
        },

        getProgressMessage: function(progress) {
            if (progress < 10) {
                return 'Preparing media files...';
            } else if (progress < 30) {
                return 'Processing images...';
            } else if (progress < 50) {
                return 'Applying Ken Burns effects...';
            } else if (progress < 70) {
                return 'Adding transitions...';
            } else if (progress < 90) {
                return 'Mixing audio tracks...';
            } else {
                return 'Finalizing video...';
            }
        },

        updateProgress: function(percentage, message) {
            $('#progress-fill').css('width', percentage + '%');
            $('#progress-percentage').text(Math.round(percentage) + '%');
            $('#progress-text').text(message);
        },

        showLoading: function() {
            $('#loading-screen').show();
            $('#video-player').hide();
            $('#error-screen').hide();
        },

        showVideo: function(videoUrl) {
            $('#video-source').attr('src', videoUrl);
            $('#memorial-video')[0].load();

            // Hide all other panels
            $('#loading-screen').hide();
            $('#error-screen').hide();
            $('#media-selection-panel').hide();
            $('#video-gallery-panel').hide();

            // Show video and controls
            $('#video-container').show();
            $('#video-player').show();
            $('#controls-panel').show();
            $('#info-panel').show();

            // Unmute after a short delay (user interaction)
            setTimeout(function() {
                $('#memorial-video')[0].muted = false;
            }, 1000);
        },

        showError: function(message) {
            $('#error-message').text(message);
            $('#loading-screen').hide();
            $('#video-player').hide();
            $('#error-screen').show();
        },

        downloadVideo: function() {
            var videoSrc = $('#video-source').attr('src');
            if (videoSrc) {
                var link = document.createElement('a');
                link.href = videoSrc;
                link.download = 'memorial-video-' + this.currentTemplate + '.mp4';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        },

        toggleFullscreen: function() {
            var video = $('#memorial-video')[0];

            if (!document.fullscreenElement) {
                if (video.requestFullscreen) {
                    video.requestFullscreen();
                } else if (video.webkitRequestFullscreen) {
                    video.webkitRequestFullscreen();
                } else if (video.msRequestFullscreen) {
                    video.msRequestFullscreen();
                }
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        },

        stopVideo: function() {
            var video = $('#memorial-video')[0];

            if (video) {
                // Pause the video
                video.pause();

                // Reset to beginning (optional, but good practice)
                video.currentTime = 0;

                // Mute the video
                video.muted = true;

                console.log('Video stopped and reset');
            }
        },

        // ===== GALLERY METHODS =====

        loadVideoGallery: function() {
            var self = this;

            console.log('=== LOADING VIDEO GALLERY ===');

            $.ajax({
                url: memoriansPoC.videoHistoryUrl,
                dataType: 'json',
                success: function(response) {
                    console.log('Gallery response:', response);

                    if (response && response.success) {
                        self.videoGallery = response.videos;

                        if (response.videos.length === 0) {
                            // No videos - show empty state
                            self.showEmptyGallery();
                        } else {
                            // Show gallery
                            self.renderGallery();
                            self.showGallery();
                        }
                    } else {
                        console.error('Failed to load gallery:', response);
                        self.showEmptyGallery();
                    }

                    // Always load media library in background for quick access
                    self.loadMediaLibrary();
                },
                error: function(xhr, status, error) {
                    console.error('Gallery load error:', status, error);
                    self.showEmptyGallery();
                    self.loadMediaLibrary();
                }
            });
        },

        loadVideoGalleryData: function() {
            var self = this;

            console.log('Refreshing gallery data in background...');

            $.ajax({
                url: memoriansPoC.videoHistoryUrl,
                dataType: 'json',
                success: function(response) {
                    if (response && response.success) {
                        self.videoGallery = response.videos;
                        console.log('Gallery data refreshed: ' + response.videos.length + ' videos');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Gallery data refresh error:', status, error);
                }
            });
        },

        renderGallery: function() {
            var self = this;
            var html = '';

            $.each(this.videoGallery, function(index, video) {
                var date = new Date(video.created * 1000);
                var dateStr = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
                var sizeStr = self.formatBytes(video.size);
                var imageCount = video.selection && video.selection.images ? video.selection.images.length : 0;
                var videoCount = video.selection && video.selection.videos ? video.selection.videos.length : 1;

                // Determine preset used
                var preset = 'Classic'; // Default
                if (video.selection && video.selection.settings) {
                    var settings = video.selection.settings;
                    // Check for preset patterns
                    if (settings.frameRate === 24 && settings.imageDuration === 6) {
                        preset = 'Cinematic';
                    } else if (settings.imageScale === 0.6 && settings.videoScale === 0.6) {
                        preset = 'Minimal';
                    } else if (settings.imageScale === 1.2 && settings.videoScale === 1.2) {
                        preset = 'Dynamic';
                    } else if (settings.imageScale !== 1.0 || settings.videoScale !== 1.0 ||
                              settings.imageDuration !== 4 || settings.frameRate !== 30) {
                        preset = 'Custom';
                    }
                }

                // Preset badge color
                var presetClass = 'preset-' + preset.toLowerCase();

                html += '<div class="gallery-card" data-cache-key="' + video.cache_key + '">';
                html += '  <div class="gallery-card-video">';
                html += '    <video src="' + video.url + '#t=0.001" muted preload="metadata"></video>';
                html += '    <div class="gallery-card-overlay">';
                html += '      <button class="gallery-play-btn" data-cache-key="' + video.cache_key + '">▶</button>';
                html += '    </div>';
                html += '    <span class="gallery-preset-badge ' + presetClass + '">' + preset + '</span>';
                html += '  </div>';
                html += '  <div class="gallery-card-info">';
                html += '    <div class="gallery-card-meta">';
                html += '      <span class="gallery-date">' + dateStr + '</span>';
                html += '      <span class="gallery-size">' + sizeStr + '</span>';
                html += '    </div>';
                html += '    <div class="gallery-card-details">';
                html += '      <span class="gallery-media-count">' + imageCount + ' images, ' + videoCount + ' video' + (videoCount > 1 ? 's' : '') + '</span>';
                html += '    </div>';
                html += '    <div class="gallery-card-actions">';
                html += '      <button class="gallery-action-btn gallery-play-action" data-cache-key="' + video.cache_key + '">Play</button>';
                html += '      <button class="gallery-action-btn gallery-delete-action" data-cache-key="' + video.cache_key + '">Delete</button>';
                html += '    </div>';
                html += '  </div>';
                html += '</div>';
            });

            $('#gallery-grid').html(html);

            // Bind click events
            $('.gallery-play-btn, .gallery-play-action').on('click', function() {
                var cacheKey = $(this).data('cache-key');
                self.playGalleryVideo(cacheKey);
            });

            $('.gallery-delete-action').on('click', function() {
                var cacheKey = $(this).data('cache-key');
                self.deleteGalleryVideo(cacheKey);
            });
        },

        playGalleryVideo: function(cacheKey) {
            var self = this;
            var video = this.videoGallery.find(function(v) {
                return v.cache_key === cacheKey;
            });

            if (video) {
                this.currentVideo = video;
                this.showVideo(video.url);
                $('#video-gallery-panel').hide();
                $('#controls-panel').show();
                $('#info-panel').show();
            }
        },

        deleteGalleryVideo: function(cacheKey) {
            var self = this;

            if (!confirm('Are you sure you want to delete this video? This action cannot be undone.')) {
                return;
            }

            $.ajax({
                url: memoriansPoC.videoHistoryUrl,
                data: {
                    action: 'delete',
                    cache_key: cacheKey
                },
                success: function(response) {
                    if (response && response.success) {
                        console.log('Video deleted successfully');
                        // Reload gallery
                        self.loadVideoGallery();
                    } else {
                        alert('Failed to delete video: ' + (response.message || 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    alert('Network error while deleting video');
                    console.error('Delete error:', status, error);
                }
            });
        },

        startNewVideo: function() {
            this.stopVideo();

            $('#video-gallery-panel').hide();
            $('#video-container').hide();
            $('#controls-panel').hide();
            $('#info-panel').hide();

            if (!this.mediaLibrary) {
                this.loadMediaLibrary();
            } else {
                // Reset selection arrays (no pre-selection)
                this.selectedImages = [];
                this.selectedVideos = [];
                this.selectedAudio = [];
                this.updateSelectionUI();
                this.showMediaSelection();
            }
        },

        showGallery: function() {
            $('#video-gallery-panel').show();
            $('#media-selection-panel').hide();
            $('#video-container').hide();
            $('#controls-panel').hide();
            $('#info-panel').hide();
        },

        showEmptyGallery: function() {
            // No videos - hide gallery entirely and show media selection
            $('#video-gallery-panel').hide();
            $('#video-container').hide();
            $('#controls-panel').hide();
            $('#info-panel').hide();

            // Load media library and show selection
            if (!this.mediaLibrary) {
                this.loadMediaLibrary();
            } else {
                this.showMediaSelection();
            }
        },

        updateTemplateDescription: function(template) {
            var descriptions = {
                'classic': 'Traditional fade and slide transitions',
                'modern': 'Dynamic circles and pixelize effects',
                'elegant': 'Sophisticated fades through black/white'
            };

            var description = descriptions[template] || descriptions['classic'];
            $('#template-description').text(description);
        },

        toggleVideoPreview: function(videoId, button) {
            var $videoItem = $('#videos-grid').find('.video-item[data-id="' + videoId + '"]');
            var videoElement = $videoItem.find('.media-video-element')[0];

            if (!videoElement) return;

            if (this.currentPreviewVideo === videoId && !videoElement.paused) {
                // Currently playing - pause it
                videoElement.pause();
                button.find('.video-icon').text('▶');
            } else if (this.currentPreviewVideo === videoId && videoElement.paused) {
                // Currently paused - reset and play from start
                videoElement.currentTime = 0;
                videoElement.play();
                button.find('.video-icon').text('⏸');
            } else {
                // Different video - stop others and play this one
                this.stopAllPreviews();
                videoElement.currentTime = 0;
                videoElement.play();
                button.find('.video-icon').text('⏸');
                $videoItem.addClass('previewing');
                this.currentPreviewVideo = videoId;

                // Reset when video ends
                $(videoElement).off('ended').on('ended', function() {
                    button.find('.video-icon').text('▶');
                    $videoItem.removeClass('previewing');
                    this.currentPreviewVideo = null;
                }.bind(this));
            }
        },

        toggleAudioPreview: function(audioId, button) {
            var $audioItem = $('#audio-list').find('.audio-item[data-id="' + audioId + '"]');
            var audioElement = $audioItem.find('.audio-element')[0];

            if (!audioElement) return;

            if (this.currentPreviewAudio === audioId && !audioElement.paused) {
                // Currently playing - pause it
                audioElement.pause();
                button.find('.audio-icon').text('▶');
            } else if (this.currentPreviewAudio === audioId && audioElement.paused) {
                // Currently paused - reset and play from start
                audioElement.currentTime = 0;
                audioElement.play();
                button.find('.audio-icon').text('⏸');
            } else {
                // Different audio - stop others and play this one
                this.stopAllPreviews();
                audioElement.currentTime = 0;
                audioElement.play();
                button.find('.audio-icon').text('⏸');
                $audioItem.addClass('playing');
                this.currentPreviewAudio = audioId;

                // Reset when audio ends
                $(audioElement).off('ended').on('ended', function() {
                    button.find('.audio-icon').text('▶');
                    $audioItem.removeClass('playing');
                    this.currentPreviewAudio = null;
                }.bind(this));
            }
        },

        stopAllPreviews: function() {
            // Stop all video previews
            $('.media-video-element').each(function() {
                this.pause();
                this.currentTime = 0;
            });
            $('.video-preview-btn .video-icon').text('▶');
            $('.video-item').removeClass('previewing');

            // Stop all audio previews
            $('.audio-element').each(function() {
                this.pause();
                this.currentTime = 0;
            });
            $('.audio-preview-btn .audio-icon').text('▶');
            $('.audio-item').removeClass('playing');

            this.currentPreviewVideo = null;
            this.currentPreviewAudio = null;
        },

        formatBytes: function(bytes) {
            if (bytes === 0) return '0 Bytes';
            var k = 1024;
            var sizes = ['Bytes', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        MemoriansVideoPlayer.init();
    });

})(jQuery);
