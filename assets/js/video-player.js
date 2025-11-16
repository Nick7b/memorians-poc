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
            images: 15,
            videos: 1,
            audio: 1
        },

        // Gallery data
        videoGallery: [],
        currentVideo: null,

        init: function() {
            this.bindEvents();
            this.loadVideoGallery(); // Start by loading gallery
        },

        bindEvents: function() {
            var self = this;

            // Gallery buttons
            $('#new-video-btn, #create-first-video-btn').on('click', function() {
                self.startNewVideo();
            });

            $('#back-to-gallery').on('click', function() {
                self.loadVideoGallery();
            });

            // Start generation button
            $('#start-generation').on('click', function() {
                self.generateVideoWithSelection();
            });

            // Edit selection button
            $('#edit-selection').on('click', function() {
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

            // Template change
            $('#template-style').on('change', function() {
                self.currentTemplate = $(this).val();
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

                        console.log('About to render media grid...');
                        self.renderMediaGrid();

                        console.log('About to random preselect...');
                        self.randomPreselect();

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

            // Render videos
            var videosHtml = '';
            $.each(this.mediaLibrary.videos, function(index, video) {
                videosHtml += '<div class="media-item" data-type="video" data-id="' + video.id + '">';
                videosHtml += '<video src="' + video.url + '" muted></video>';
                videosHtml += '<div class="media-item-overlay"><span class="media-item-checkmark">✓</span></div>';
                videosHtml += '<div class="media-item-label">' + video.filename + '</div>';
                videosHtml += '</div>';
            });
            $('#videos-grid').html(videosHtml);

            // Render audio
            var audioHtml = '';
            $.each(this.mediaLibrary.audio, function(index, audio) {
                audioHtml += '<div class="audio-item" data-type="audio" data-id="' + audio.id + '">';
                audioHtml += '<div class="audio-item-icon">🎵</div>';
                audioHtml += '<div class="audio-item-info"><div class="audio-item-name">' + audio.filename + '</div></div>';
                audioHtml += '<div class="audio-item-checkmark">✓</div>';
                audioHtml += '</div>';
            });
            $('#audio-list').html(audioHtml);

            // Bind click events
            $('.media-item[data-type="image"]').on('click', function() {
                self.toggleMediaSelection('image', $(this).data('id'));
            });

            $('.media-item[data-type="video"]').on('click', function() {
                self.toggleMediaSelection('video', $(this).data('id'));
            });

            $('.audio-item').on('click', function() {
                self.toggleMediaSelection('audio', $(this).data('id'));
            });
        },

        randomPreselect: function() {
            var self = this;

            // Randomly select images
            var shuffledImages = this.shuffleArray(this.mediaLibrary.images.slice());
            this.selectedImages = shuffledImages.slice(0, this.requirements.images).map(function(img) {
                return img.id;
            });

            // Randomly select video
            var randomVideo = this.mediaLibrary.videos[Math.floor(Math.random() * this.mediaLibrary.videos.length)];
            this.selectedVideos = [randomVideo.id];

            // Randomly select audio
            var randomAudio = this.mediaLibrary.audio[Math.floor(Math.random() * this.mediaLibrary.audio.length)];
            this.selectedAudio = [randomAudio.id];

            this.updateSelectionUI();
        },

        shuffleArray: function(array) {
            for (var i = array.length - 1; i > 0; i--) {
                var j = Math.floor(Math.random() * (i + 1));
                var temp = array[i];
                array[i] = array[j];
                array[j] = temp;
            }
            return array;
        },

        toggleMediaSelection: function(type, id) {
            var selectedArray, maxCount;

            if (type === 'image') {
                selectedArray = this.selectedImages;
                maxCount = this.requirements.images;
            } else if (type === 'video') {
                selectedArray = this.selectedVideos;
                maxCount = this.requirements.videos;
            } else if (type === 'audio') {
                selectedArray = this.selectedAudio;
                maxCount = this.requirements.audio;
            }

            var index = selectedArray.indexOf(id);

            if (index > -1) {
                // Deselect
                selectedArray.splice(index, 1);
            } else {
                // Select (if under limit)
                if (selectedArray.length < maxCount) {
                    selectedArray.push(id);
                } else {
                    // Replace first selected item
                    selectedArray.shift();
                    selectedArray.push(id);
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
            this.updateStatusBadge('images', this.selectedImages.length, this.requirements.images);
            this.updateStatusBadge('videos', this.selectedVideos.length, this.requirements.videos);
            this.updateStatusBadge('audio', this.selectedAudio.length, this.requirements.audio);

            // Update visual selection states
            $('.media-item[data-type="image"]').each(function() {
                var id = $(this).data('id');
                if (self.selectedImages.indexOf(id) > -1) {
                    $(this).addClass('selected');
                } else {
                    $(this).removeClass('selected');
                }

                // Disable if limit reached and not selected
                if (self.selectedImages.length >= self.requirements.images && self.selectedImages.indexOf(id) === -1) {
                    $(this).addClass('disabled');
                } else {
                    $(this).removeClass('disabled');
                }
            });

            $('.media-item[data-type="video"]').each(function() {
                var id = $(this).data('id');
                if (self.selectedVideos.indexOf(id) > -1) {
                    $(this).addClass('selected');
                } else {
                    $(this).removeClass('selected');
                }

                if (self.selectedVideos.length >= self.requirements.videos && self.selectedVideos.indexOf(id) === -1) {
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

                if (self.selectedAudio.length >= self.requirements.audio && self.selectedAudio.indexOf(id) === -1) {
                    $(this).addClass('disabled');
                } else {
                    $(this).removeClass('disabled');
                }
            });

            // Enable/disable generate button
            this.validateSelection();
        },

        updateStatusBadge: function(type, current, required) {
            var badge = $('#' + type + '-status');
            badge.removeClass('ready warning error');

            if (current === required) {
                badge.addClass('ready');
                badge.text('✓ Ready');
            } else if (current < required) {
                badge.addClass('warning');
                badge.text('⚠ Select ' + (required - current) + ' more');
            } else {
                badge.addClass('error');
                badge.text('✗ Too many');
            }
        },

        validateSelection: function() {
            var isValid = (
                this.selectedImages.length === this.requirements.images &&
                this.selectedVideos.length === this.requirements.videos &&
                this.selectedAudio.length === this.requirements.audio
            );

            $('#start-generation').prop('disabled', !isValid);
        },

        showMediaSelection: function() {
            $('#media-selection-panel').show();
            $('#video-container').hide();
            $('#controls-panel').hide();
            $('#info-panel').hide();
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
                video: this.selectedVideos[0],
                audio: this.selectedAudio[0]
            };

            // Add images as array
            $.each(this.selectedImages, function(index, imageId) {
                params['images[' + index + ']'] = imageId;
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

                            // Reload gallery in background to include new video
                            console.log('Reloading gallery to include new video...');
                            self.loadVideoGalleryData();
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

                html += '<div class="gallery-card" data-cache-key="' + video.cache_key + '">';
                html += '  <div class="gallery-card-video">';
                html += '    <video src="' + video.url + '" muted></video>';
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
                html += '      <span>' + imageCount + ' images, 1 video, 1 audio</span>';
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
            $('#video-gallery-panel').hide();
            $('#video-container').hide();
            $('#controls-panel').hide();
            $('#info-panel').hide();

            if (!this.mediaLibrary) {
                this.loadMediaLibrary();
            } else {
                this.randomPreselect();
                this.showMediaSelection();
            }
        },

        showGallery: function() {
            $('#video-gallery-panel').show();
            $('#gallery-empty').hide();
            $('#media-selection-panel').hide();
            $('#video-container').hide();
            $('#controls-panel').hide();
            $('#info-panel').hide();
        },

        showEmptyGallery: function() {
            $('#video-gallery-panel').show();
            $('#gallery-grid').hide();
            $('#gallery-empty').show();
            $('#media-selection-panel').hide();
            $('#video-container').hide();
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
