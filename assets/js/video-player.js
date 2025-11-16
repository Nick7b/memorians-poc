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
        requirements: {
            images: { min: 15, max: 40 },
            videos: { min: 1, max: 5 },
            audio: { min: 1, max: 1 }
        },

        // Gallery data
        videoGallery: [],
        currentVideo: null,

        // Preview tracking
        currentPreviewVideo: null,
        currentPreviewAudio: null,

        init: function() {
            this.bindEvents();
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

            console.log('=== LOADING MEDIA LIBRARY ===');
            console.log('URL:', memoriansPoC.mediaLibraryUrl);

            $.ajax({
                url: memoriansPoC.mediaLibraryUrl,
                dataType: 'json',
                success: function(response) {
                    console.log('=== MEDIA LIBRARY RESPONSE ===');
                    console.log('Response:', response);
                    console.log('Success:', response && response.success);
                    console.log('Has data:', response && response.data);

                    if (response && response.success) {
                        console.log('Images count:', response.data.images.length);
                        console.log('Videos count:', response.data.videos.length);
                        console.log('Audio count:', response.data.audio.length);

                        self.mediaLibrary = response.data;
                        self.requirements = response.data.requirements;

                        // Reset selection arrays (no pre-selection)
                        self.selectedImages = [];
                        self.selectedVideos = [];
                        self.selectedAudio = [];

                        console.log('About to render media grid...');
                        self.renderMediaGrid();

                        console.log('About to show media selection...');
                        self.showMediaSelection();

                        console.log('=== MEDIA SELECTION COMPLETE ===');
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

            // Render images
            var imagesHtml = '';
            $.each(this.mediaLibrary.images, function(index, image) {
                imagesHtml += '<div class="media-item" data-type="image" data-id="' + image.id + '">';
                imagesHtml += '<img src="' + image.url + '" alt="' + image.filename + '">';
                imagesHtml += '<div class="media-item-overlay"><span class="media-item-checkmark">✓</span></div>';
                imagesHtml += '<div class="media-item-label">' + image.filename + '</div>';
                imagesHtml += '</div>';
            });
            $('#images-grid').html(imagesHtml);

            // Render videos (using similar layout to audio items)
            var videosHtml = '';
            $.each(this.mediaLibrary.videos, function(index, video) {
                videosHtml += '<div class="video-item" data-type="video" data-id="' + video.id + '" data-url="' + video.url + '">';
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

            // Bind click events for selection
            $('.media-item[data-type="image"]').on('click', function() {
                self.toggleMediaSelection('image', $(this).data('id'));
            });

            $('.video-item').on('click', function(e) {
                // Don't select if clicking the preview button
                if (!$(e.target).closest('.video-preview-btn').length) {
                    self.toggleMediaSelection('video', $(this).data('id'));
                }
            });

            $('.audio-item').on('click', function(e) {
                // Don't select if clicking the preview button
                if (!$(e.target).closest('.audio-preview-btn').length) {
                    self.toggleMediaSelection('audio', $(this).data('id'));
                }
            });

            // Bind preview button events
            $('.video-preview-btn').on('click', function(e) {
                e.stopPropagation();
                var videoId = $(this).data('video-id');
                self.toggleVideoPreview(videoId, $(this));
            });

            $('.audio-preview-btn').on('click', function(e) {
                e.stopPropagation();
                var audioId = $(this).data('audio-id');
                self.toggleAudioPreview(audioId, $(this));
            });
        },

        toggleMediaSelection: function(type, id) {
            var selectedArray, maxCount;

            if (type === 'image') {
                selectedArray = this.selectedImages;
                maxCount = this.requirements.images.max;
            } else if (type === 'video') {
                selectedArray = this.selectedVideos;
                maxCount = this.requirements.videos.max;
            } else if (type === 'audio') {
                selectedArray = this.selectedAudio;
                maxCount = this.requirements.audio.max;
            }

            var index = selectedArray.indexOf(id);

            if (index > -1) {
                // Deselect - always allowed
                selectedArray.splice(index, 1);
            } else {
                // Select - behavior depends on media type
                if (selectedArray.length < maxCount) {
                    // Under limit - just add it
                    selectedArray.push(id);
                } else {
                    // Limit reached - behavior differs by type
                    if (type === 'image' || type === 'video') {
                        // Images and Videos: prevent selection when max reached
                        console.log('Selection limit reached for ' + type + '. Maximum is ' + maxCount + '. Please deselect an item first.');
                        return;
                    } else {
                        // Audio: replace current selection with new one (auto-switch)
                        selectedArray[0] = id;
                        console.log('Auto-switching ' + type + ' selection to new item.');
                    }
                }
            }

            // Update the arrays
            if (type === 'image') {
                this.selectedImages = selectedArray;
            } else if (type === 'video') {
                this.selectedVideos = selectedArray;
            } else if (type === 'audio') {
                this.selectedAudio = selectedArray;
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
            $('.media-item[data-type="image"]').each(function() {
                var id = $(this).data('id');
                if (self.selectedImages.indexOf(id) > -1) {
                    $(this).addClass('selected');
                } else {
                    $(this).removeClass('selected');
                }

                // Disable if max limit reached and not selected
                if (self.selectedImages.length >= self.requirements.images.max && self.selectedImages.indexOf(id) === -1) {
                    $(this).addClass('disabled');
                } else {
                    $(this).removeClass('disabled');
                }
            });

            $('.video-item').each(function() {
                var id = $(this).data('id');
                if (self.selectedVideos.indexOf(id) > -1) {
                    $(this).addClass('selected');
                } else {
                    $(this).removeClass('selected');
                }

                // Disable if max limit reached and not selected
                if (self.selectedVideos.length >= self.requirements.videos.max && self.selectedVideos.indexOf(id) === -1) {
                    $(this).addClass('disabled');
                } else {
                    $(this).removeClass('disabled');
                }
            });

            $('.audio-item').each(function() {
                var id = $(this).data('id');
                if (self.selectedAudio.indexOf(id) > -1) {
                    $(this).addClass('selected');
                } else {
                    $(this).removeClass('selected');
                }

                // Disable if max limit reached and not selected
                if (self.selectedAudio.length >= self.requirements.audio.max && self.selectedAudio.indexOf(id) === -1) {
                    $(this).addClass('disabled');
                } else {
                    $(this).removeClass('disabled');
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

            // Add images as array
            $.each(this.selectedImages, function(index, imageId) {
                params['images[' + index + ']'] = imageId;
            });

            // Add videos as array
            $.each(this.selectedVideos, function(index, videoId) {
                params['videos[' + index + ']'] = videoId;
            });

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

                html += '<div class="gallery-card" data-cache-key="' + video.cache_key + '">';
                html += '  <div class="gallery-card-video">';
                html += '    <video src="' + video.url + '#t=0.001" muted preload="metadata"></video>';
                html += '    <div class="gallery-card-overlay">';
                html += '      <button class="gallery-play-btn" data-cache-key="' + video.cache_key + '">▶ Play</button>';
                html += '    </div>';
                html += '  </div>';
                html += '  <div class="gallery-card-info">';
                html += '    <div class="gallery-card-meta">';
                html += '      <span class="gallery-date">' + dateStr + '</span>';
                html += '      <span class="gallery-size">' + sizeStr + '</span>';
                html += '    </div>';
                html += '    <div class="gallery-card-details">';
                html += '      <span>Template: ' + (video.template || 'classic') + '</span>';
                html += '      <span>' + imageCount + ' images, ' + videoCount + ' video' + (videoCount > 1 ? 's' : '') + '</span>';
                html += '    </div>';
                html += '    <div class="gallery-card-actions">';
                html += '      <button class="gallery-delete-btn" data-cache-key="' + video.cache_key + '">Delete</button>';
                html += '    </div>';
                html += '  </div>';
                html += '</div>';
            });

            $('#gallery-grid').html(html);

            // Bind click events
            $('.gallery-play-btn').on('click', function() {
                var cacheKey = $(this).data('cache-key');
                self.playGalleryVideo(cacheKey);
            });

            $('.gallery-delete-btn').on('click', function() {
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
            var $videoItem = $('.video-item[data-id="' + videoId + '"]');
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
            var $audioItem = $('.audio-item[data-id="' + audioId + '"]');
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
