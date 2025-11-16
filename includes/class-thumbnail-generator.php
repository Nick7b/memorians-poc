<?php
/**
 * Thumbnail Generator Class
 * Handles generation of image thumbnails and video poster frames
 *
 * @since 1.9.0
 */

class Memorians_POC_Thumbnail_Generator {

    /**
     * Thumbnail size definitions
     */
    private $image_sizes = array(
        'thumbnail' => array('width' => 150, 'height' => 150, 'crop' => true),
        'small'     => array('width' => 300, 'height' => 300, 'crop' => true),
        'medium'    => array('width' => 600, 'height' => 600, 'crop' => false),
        'large'     => array('width' => 1200, 'height' => 1200, 'crop' => false)
    );

    private $video_poster_sizes = array(
        'thumbnail' => array('width' => 150, 'height' => 150, 'crop' => true),
        'small'     => array('width' => 300, 'height' => 300, 'crop' => true),
        'medium'    => array('width' => 600, 'height' => 450, 'crop' => true),  // 4:3 aspect
        'large'     => array('width' => 1200, 'height' => 900, 'crop' => true)  // 4:3 aspect
    );

    private $generated_video_sizes = array(
        'thumb'  => array('width' => 320, 'height' => 240, 'crop' => true),
        'poster' => array('width' => 1920, 'height' => 1080, 'crop' => true)
    );

    private $media_dir;
    private $cache_dir;
    private $plugin_url;

    public function __construct() {
        $this->media_dir = MEMORIANS_POC_PLUGIN_DIR . 'media/';
        $this->cache_dir = MEMORIANS_POC_CACHE_DIR;
        $this->plugin_url = MEMORIANS_POC_PLUGIN_URL;

        // Ensure thumbnail directories exist
        $this->ensure_directories();
    }

    /**
     * Ensure all required thumbnail directories exist
     */
    private function ensure_directories() {
        $dirs = array(
            $this->media_dir . 'images/thumbs/150/',
            $this->media_dir . 'images/thumbs/300/',
            $this->media_dir . 'images/thumbs/600/',
            $this->media_dir . 'images/thumbs/1200/',
            $this->media_dir . 'videos/posters/150/',
            $this->media_dir . 'videos/posters/300/',
            $this->media_dir . 'videos/posters/600/',
            $this->media_dir . 'videos/posters/1200/',
            $this->cache_dir . 'posters/thumb/',
            $this->cache_dir . 'posters/full/'
        );

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                wp_mkdir_p($dir);
            }
        }
    }

    /**
     * Generate thumbnail for an image
     *
     * @param string $source_path Path to source image
     * @param string $size Size key (thumbnail, small, medium, large)
     * @param bool $force Force regeneration even if exists
     * @return array|WP_Error Thumbnail data or error
     */
    public function generate_image_thumbnail($source_path, $size = 'thumbnail', $force = false) {
        if (!file_exists($source_path)) {
            return new WP_Error('file_not_found', 'Source image not found: ' . $source_path);
        }

        if (!isset($this->image_sizes[$size])) {
            return new WP_Error('invalid_size', 'Invalid thumbnail size: ' . $size);
        }

        $size_config = $this->image_sizes[$size];
        $filename = basename($source_path);
        $name_parts = pathinfo($filename);

        // Generate thumbnail filename
        $thumb_filename = $name_parts['filename'] . '-' . $size . '.' . $name_parts['extension'];
        $thumb_dir = $this->media_dir . 'images/thumbs/' . $size_config['width'] . '/';
        $thumb_path = $thumb_dir . $thumb_filename;

        // Check if thumbnail already exists
        if (!$force && file_exists($thumb_path)) {
            return $this->get_thumbnail_data($thumb_path, $size);
        }

        // Use WordPress image editor
        $image = wp_get_image_editor($source_path);

        if (is_wp_error($image)) {
            // Fallback to GD if ImageMagick not available
            return $this->generate_thumbnail_gd($source_path, $thumb_path, $size_config);
        }

        // Resize the image
        $image->resize($size_config['width'], $size_config['height'], $size_config['crop']);

        // Set quality based on size
        $quality = ($size === 'thumbnail') ? 80 : 85;
        $image->set_quality($quality);

        // Save the thumbnail
        $result = $image->save($thumb_path);

        if (is_wp_error($result)) {
            return $result;
        }

        // Generate WebP version if supported
        $this->generate_webp_version($thumb_path);

        return $this->get_thumbnail_data($thumb_path, $size);
    }

    /**
     * Fallback GD thumbnail generation
     */
    private function generate_thumbnail_gd($source_path, $thumb_path, $size_config) {
        $image_info = getimagesize($source_path);
        if (!$image_info) {
            return new WP_Error('invalid_image', 'Could not get image info');
        }

        list($orig_width, $orig_height, $type) = $image_info;

        // Load source image
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($source_path);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($source_path);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($source_path);
                break;
            default:
                return new WP_Error('unsupported_type', 'Unsupported image type');
        }

        if (!$source) {
            return new WP_Error('load_failed', 'Could not load source image');
        }

        // Calculate dimensions
        $target_width = $size_config['width'];
        $target_height = $size_config['height'];

        if ($size_config['crop']) {
            // Calculate crop dimensions
            $ratio_orig = $orig_width / $orig_height;
            $ratio_target = $target_width / $target_height;

            if ($ratio_orig > $ratio_target) {
                $new_height = $target_height;
                $new_width = $target_height * $ratio_orig;
            } else {
                $new_width = $target_width;
                $new_height = $target_width / $ratio_orig;
            }

            $x_mid = $new_width / 2;
            $y_mid = $new_height / 2;

            // Create thumbnail
            $thumb = imagecreatetruecolor($target_width, $target_height);

            // Handle transparency for PNG
            if ($type == IMAGETYPE_PNG) {
                imagealphablending($thumb, false);
                imagesavealpha($thumb, true);
                $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
                imagefilledrectangle($thumb, 0, 0, $target_width, $target_height, $transparent);
            }

            // Copy and resize
            imagecopyresampled($thumb, $source, 0, 0,
                ($x_mid - ($target_width / 2)), ($y_mid - ($target_height / 2)),
                $target_width, $target_height,
                $target_width, $target_height);
        } else {
            // Maintain aspect ratio
            $ratio = min($target_width / $orig_width, $target_height / $orig_height);
            $new_width = $orig_width * $ratio;
            $new_height = $orig_height * $ratio;

            $thumb = imagecreatetruecolor($new_width, $new_height);

            if ($type == IMAGETYPE_PNG) {
                imagealphablending($thumb, false);
                imagesavealpha($thumb, true);
                $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
                imagefilledrectangle($thumb, 0, 0, $new_width, $new_height, $transparent);
            }

            imagecopyresampled($thumb, $source, 0, 0, 0, 0,
                $new_width, $new_height, $orig_width, $orig_height);
        }

        // Save thumbnail
        $success = false;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $success = imagejpeg($thumb, $thumb_path, 85);
                break;
            case IMAGETYPE_PNG:
                $success = imagepng($thumb, $thumb_path, 8);
                break;
            case IMAGETYPE_GIF:
                $success = imagegif($thumb, $thumb_path);
                break;
        }

        imagedestroy($source);
        imagedestroy($thumb);

        if (!$success) {
            return new WP_Error('save_failed', 'Could not save thumbnail');
        }

        return $this->get_thumbnail_data($thumb_path, $size_config);
    }

    /**
     * Generate WebP version of an image
     */
    private function generate_webp_version($image_path) {
        // Check if WebP is supported
        if (!function_exists('imagewebp')) {
            return false;
        }

        $image_info = getimagesize($image_path);
        if (!$image_info) {
            return false;
        }

        list($width, $height, $type) = $image_info;

        // Load image
        $image = null;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($image_path);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($image_path);
                break;
        }

        if (!$image) {
            return false;
        }

        // Generate WebP path
        $webp_path = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $image_path);

        // Save as WebP
        $success = imagewebp($image, $webp_path, 80);
        imagedestroy($image);

        return $success;
    }

    /**
     * Generate poster frame from video
     *
     * @param string $video_path Path to video file
     * @param string $size Size key
     * @param float $timestamp Timestamp in seconds to extract frame
     * @param bool $force Force regeneration
     * @return array|WP_Error Poster data or error
     */
    public function generate_video_poster($video_path, $size = 'medium', $timestamp = 1.0, $force = false) {
        if (!file_exists($video_path)) {
            return new WP_Error('file_not_found', 'Video file not found: ' . $video_path);
        }

        if (!isset($this->video_poster_sizes[$size])) {
            return new WP_Error('invalid_size', 'Invalid poster size: ' . $size);
        }

        $size_config = $this->video_poster_sizes[$size];
        $filename = basename($video_path, '.mp4');

        // Generate poster filename
        $poster_filename = $filename . '-poster-' . $size . '.jpg';
        $poster_dir = $this->media_dir . 'videos/posters/' . $size_config['width'] . '/';
        $poster_path = $poster_dir . $poster_filename;

        // Check if poster already exists
        if (!$force && file_exists($poster_path)) {
            return $this->get_thumbnail_data($poster_path, $size);
        }

        // Use FFmpeg to extract frame
        $temp_poster = sys_get_temp_dir() . '/' . uniqid('poster_') . '.jpg';

        $cmd = sprintf(
            'ffmpeg -ss %s -i %s -vframes 1 -vf "scale=%d:%d:force_original_aspect_ratio=decrease,pad=%d:%d:(ow-iw)/2:(oh-ih)/2" -q:v 2 %s 2>&1',
            escapeshellarg($timestamp),
            escapeshellarg($video_path),
            $size_config['width'],
            $size_config['height'],
            $size_config['width'],
            $size_config['height'],
            escapeshellarg($temp_poster)
        );

        exec($cmd, $output, $return_var);

        if ($return_var !== 0 || !file_exists($temp_poster)) {
            return new WP_Error('ffmpeg_failed', 'Failed to extract video frame: ' . implode("\n", $output));
        }

        // Move to final location
        if (!rename($temp_poster, $poster_path)) {
            unlink($temp_poster);
            return new WP_Error('move_failed', 'Could not move poster to final location');
        }

        // Generate WebP version
        $this->generate_webp_version($poster_path);

        return $this->get_thumbnail_data($poster_path, $size);
    }

    /**
     * Generate poster for generated video
     *
     * @param string $video_path Path to generated video
     * @param bool $force Force regeneration
     * @return array Poster URLs for different sizes
     */
    public function generate_video_posters($video_path, $force = false) {
        $posters = array();
        $cache_key = basename($video_path, '.mp4');

        foreach ($this->generated_video_sizes as $size => $config) {
            $poster_filename = $cache_key . '-' . $size . '.jpg';
            $poster_dir = $this->cache_dir . 'posters/' . $size . '/';
            $poster_path = $poster_dir . $poster_filename;

            if (!$force && file_exists($poster_path)) {
                $posters[$size] = $this->get_thumbnail_url($poster_path);
                continue;
            }

            // Extract frame at 2 seconds (usually past any fade-in)
            $cmd = sprintf(
                'ffmpeg -ss 2 -i %s -vframes 1 -vf "scale=%d:%d:force_original_aspect_ratio=decrease,pad=%d:%d:(ow-iw)/2:(oh-ih)/2" -q:v 2 %s 2>&1',
                escapeshellarg($video_path),
                $config['width'],
                $config['height'],
                $config['width'],
                $config['height'],
                escapeshellarg($poster_path)
            );

            exec($cmd, $output, $return_var);

            if ($return_var === 0 && file_exists($poster_path)) {
                // Generate WebP version
                $this->generate_webp_version($poster_path);
                $posters[$size] = $this->get_thumbnail_url($poster_path);
            }
        }

        return $posters;
    }

    /**
     * Get thumbnail data array
     */
    private function get_thumbnail_data($thumb_path, $size) {
        $url = $this->get_thumbnail_url($thumb_path);
        $webp_path = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $thumb_path);

        $data = array(
            'path' => $thumb_path,
            'url' => $url,
            'size' => $size,
            'width' => is_array($size) ? $size['width'] : $this->image_sizes[$size]['width'],
            'height' => is_array($size) ? $size['height'] : $this->image_sizes[$size]['height'],
            'filesize' => filesize($thumb_path)
        );

        if (file_exists($webp_path)) {
            $data['webp_url'] = $this->get_thumbnail_url($webp_path);
        }

        return $data;
    }

    /**
     * Get URL from file path
     */
    private function get_thumbnail_url($file_path) {
        $relative_path = str_replace(MEMORIANS_POC_PLUGIN_DIR, '', $file_path);
        return $this->plugin_url . $relative_path;
    }

    /**
     * Generate srcset string for responsive images
     *
     * @param string $base_path Base image path
     * @param string $type 'image' or 'video'
     * @return string Srcset string
     */
    public function generate_srcset($base_path, $type = 'image') {
        $srcset = array();
        $sizes = ($type === 'image') ? $this->image_sizes : $this->video_poster_sizes;

        foreach ($sizes as $size_key => $config) {
            $thumb = $this->generate_image_thumbnail($base_path, $size_key);
            if (!is_wp_error($thumb)) {
                $srcset[] = $thumb['url'] . ' ' . $config['width'] . 'w';
            }
        }

        return implode(', ', $srcset);
    }

    /**
     * Get all thumbnail URLs for a media item
     *
     * @param string $media_path Path to media file
     * @param string $media_type 'image' or 'video'
     * @return array Array of thumbnail URLs by size
     */
    public function get_all_thumbnails($media_path, $media_type = 'image') {
        $thumbnails = array();

        if ($media_type === 'image') {
            foreach (array_keys($this->image_sizes) as $size) {
                $thumb = $this->generate_image_thumbnail($media_path, $size);
                if (!is_wp_error($thumb)) {
                    $thumbnails[$size] = $thumb;
                }
            }
        } else {
            foreach (array_keys($this->video_poster_sizes) as $size) {
                $poster = $this->generate_video_poster($media_path, $size);
                if (!is_wp_error($poster)) {
                    $thumbnails[$size] = $poster;
                }
            }
        }

        return $thumbnails;
    }

    /**
     * Batch generate thumbnails for multiple media items
     *
     * @param array $media_items Array of media items with path and type
     * @param callable $progress_callback Optional callback for progress updates
     * @return array Results array
     */
    public function batch_generate_thumbnails($media_items, $progress_callback = null) {
        $results = array(
            'success' => array(),
            'failed' => array(),
            'total' => count($media_items)
        );

        foreach ($media_items as $index => $item) {
            $media_path = $item['path'];
            $media_type = isset($item['type']) ? $item['type'] : 'image';

            if ($media_type === 'image') {
                foreach (array_keys($this->image_sizes) as $size) {
                    $result = $this->generate_image_thumbnail($media_path, $size);
                    if (is_wp_error($result)) {
                        $results['failed'][] = array(
                            'path' => $media_path,
                            'size' => $size,
                            'error' => $result->get_error_message()
                        );
                    } else {
                        $results['success'][] = array(
                            'path' => $media_path,
                            'size' => $size,
                            'url' => $result['url']
                        );
                    }
                }
            } else {
                foreach (array_keys($this->video_poster_sizes) as $size) {
                    $result = $this->generate_video_poster($media_path, $size);
                    if (is_wp_error($result)) {
                        $results['failed'][] = array(
                            'path' => $media_path,
                            'size' => $size,
                            'error' => $result->get_error_message()
                        );
                    } else {
                        $results['success'][] = array(
                            'path' => $media_path,
                            'size' => $size,
                            'url' => $result['url']
                        );
                    }
                }
            }

            // Call progress callback if provided
            if (is_callable($progress_callback)) {
                $progress = ($index + 1) / $results['total'] * 100;
                call_user_func($progress_callback, $progress, $media_path);
            }
        }

        return $results;
    }

    /**
     * Clean up old or orphaned thumbnails
     *
     * @return array Cleanup results
     */
    public function cleanup_thumbnails() {
        $results = array(
            'deleted' => 0,
            'freed_space' => 0
        );

        // Get all original media files
        $originals = array();

        // Images
        $image_files = glob($this->media_dir . 'images/*.{jpg,jpeg,png,gif}', GLOB_BRACE);
        foreach ($image_files as $file) {
            $originals[basename($file)] = true;
        }

        // Videos
        $video_files = glob($this->media_dir . 'videos/*.{mp4,mov,avi}', GLOB_BRACE);
        foreach ($video_files as $file) {
            $originals[basename($file)] = true;
        }

        // Check all thumbnail directories
        $thumb_dirs = array(
            $this->media_dir . 'images/thumbs/150/',
            $this->media_dir . 'images/thumbs/300/',
            $this->media_dir . 'images/thumbs/600/',
            $this->media_dir . 'images/thumbs/1200/',
            $this->media_dir . 'videos/posters/150/',
            $this->media_dir . 'videos/posters/300/',
            $this->media_dir . 'videos/posters/600/',
            $this->media_dir . 'videos/posters/1200/'
        );

        foreach ($thumb_dirs as $dir) {
            if (!is_dir($dir)) continue;

            $files = scandir($dir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;

                // Extract original filename from thumbnail name
                $original_name = preg_replace('/-(?:thumbnail|small|medium|large|poster-\w+)\.(jpg|jpeg|png|gif|webp)$/i', '.$1', $file);

                // Check if original exists
                if (!isset($originals[$original_name])) {
                    $file_path = $dir . $file;
                    $file_size = filesize($file_path);

                    if (unlink($file_path)) {
                        $results['deleted']++;
                        $results['freed_space'] += $file_size;
                    }
                }
            }
        }

        return $results;
    }
}