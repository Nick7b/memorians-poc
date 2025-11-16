<?php
/**
 * Cache Manager Class
 * Handles video caching, cleanup, and retrieval
 */

class Memorians_POC_Cache_Manager {

    private $cache_dir;
    private $cache_ttl = 86400; // 24 hours in seconds

    public function __construct() {
        $this->cache_dir = MEMORIANS_POC_CACHE_DIR;
    }

    /**
     * Get cached video if exists and not expired
     *
     * @param string $template Template style
     * @return string|false Video path or false if not cached
     */
    public function get_cached_video($template = 'classic') {
        $cache_key = $this->get_cache_key($template);
        $video_path = $this->cache_dir . $cache_key . '.mp4';

        if (file_exists($video_path)) {
            // Check if file is not expired
            $file_time = filemtime($video_path);
            if ((time() - $file_time) < $this->cache_ttl) {
                return $video_path;
            } else {
                // Expired, delete it
                @unlink($video_path);
                return false;
            }
        }

        return false;
    }

    /**
     * Get cache key for template and media selection
     *
     * @param string $template Template style
     * @param array $selection Optional media selection (images, video, audio IDs)
     * @return string Cache key
     */
    public function get_cache_key($template = 'classic', $selection = null) {
        // If selection provided, create hash-based key
        if ($selection && is_array($selection)) {
            $hash = md5(json_encode([
                'template' => $template,
                'images' => isset($selection['images']) ? $selection['images'] : [],
                'video' => isset($selection['video']) ? $selection['video'] : '',
                'audio' => isset($selection['audio']) ? $selection['audio'] : ''
            ]));
            return 'memorial_video_' . $template . '_' . substr($hash, 0, 12);
        }

        // Fallback for compatibility (when no selection provided)
        return 'memorial_video_' . $template . '_' . date('Y-m-d');
    }

    /**
     * Get video URL from path
     *
     * @param string $video_path Full path to video file
     * @return string Video URL
     */
    public function get_video_url($video_path) {
        $relative_path = str_replace(MEMORIANS_POC_PLUGIN_DIR, '', $video_path);
        return MEMORIANS_POC_PLUGIN_URL . $relative_path;
    }

    /**
     * Save generation status
     *
     * @param string $template Template style
     * @param string $status Status (generating, completed, failed)
     * @param array $data Additional data
     */
    public function set_generation_status($template, $status, $data = array()) {
        $cache_key = $this->get_cache_key($template);
        $transient_key = 'memorians_poc_gen_' . $cache_key;

        $status_data = array(
            'status' => $status,
            'timestamp' => time(),
            'data' => $data
        );

        set_transient($transient_key, $status_data, 3600); // 1 hour expiry
    }

    /**
     * Get generation status
     *
     * @param string $template Template style
     * @return array|false Status data or false
     */
    public function get_generation_status($template) {
        $cache_key = $this->get_cache_key($template);
        $transient_key = 'memorians_poc_gen_' . $cache_key;

        return get_transient($transient_key);
    }

    /**
     * Clear generation status
     *
     * @param string $template Template style
     */
    public function clear_generation_status($template) {
        $cache_key = $this->get_cache_key($template);
        $transient_key = 'memorians_poc_gen_' . $cache_key;

        delete_transient($transient_key);
    }

    /**
     * Cleanup old cached videos
     */
    public function cleanup_old_videos() {
        if (!is_dir($this->cache_dir)) {
            return;
        }

        $files = scandir($this->cache_dir);
        $deleted = 0;

        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || $file === '.htaccess') {
                continue;
            }

            $file_path = $this->cache_dir . $file;
            if (!is_file($file_path)) {
                continue;
            }

            // Check if file is older than TTL
            $file_time = filemtime($file_path);
            if ((time() - $file_time) > $this->cache_ttl) {
                if (@unlink($file_path)) {
                    $deleted++;
                }
            }
        }

        return $deleted;
    }

    /**
     * Get cache directory size
     *
     * @return int Size in bytes
     */
    public function get_cache_size() {
        if (!is_dir($this->cache_dir)) {
            return 0;
        }

        $size = 0;
        $files = scandir($this->cache_dir);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || $file === '.htaccess') {
                continue;
            }

            $file_path = $this->cache_dir . $file;
            if (is_file($file_path)) {
                $size += filesize($file_path);
            }
        }

        return $size;
    }

    /**
     * Format bytes to human readable
     *
     * @param int $bytes Bytes
     * @return string Formatted size
     */
    public function format_bytes($bytes) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Delete specific cached video
     *
     * @param string $template Template style
     * @return bool Success
     */
    public function delete_cached_video($template) {
        $cache_key = $this->get_cache_key($template);
        $video_path = $this->cache_dir . $cache_key . '.mp4';

        if (file_exists($video_path)) {
            return @unlink($video_path);
        }

        return false;
    }

    /**
     * Save video metadata to JSON file
     *
     * @param string $cache_key Cache key
     * @param array $metadata Metadata array
     * @return bool Success
     */
    public function save_metadata($cache_key, $metadata) {
        $metadata_path = $this->cache_dir . $cache_key . '.json';
        $metadata['generated_at'] = time();
        $json = json_encode($metadata, JSON_PRETTY_PRINT);
        return file_put_contents($metadata_path, $json) !== false;
    }

    /**
     * Load video metadata from JSON file
     *
     * @param string $cache_key Cache key
     * @return array|false Metadata array or false
     */
    public function load_metadata($cache_key) {
        $metadata_path = $this->cache_dir . $cache_key . '.json';
        if (!file_exists($metadata_path)) {
            return false;
        }
        $json = file_get_contents($metadata_path);
        return json_decode($json, true);
    }

    /**
     * Get all generated videos with metadata
     *
     * @return array Array of video data
     */
    public function get_all_videos() {
        if (!is_dir($this->cache_dir)) {
            return array();
        }

        $videos = array();
        $files = scandir($this->cache_dir);

        foreach ($files as $file) {
            if (substr($file, -4) === '.mp4') {
                $cache_key = substr($file, 0, -4);
                $video_path = $this->cache_dir . $file;
                $metadata_path = $this->cache_dir . $cache_key . '.json';

                $video_data = array(
                    'cache_key' => $cache_key,
                    'filename' => $file,
                    'url' => $this->get_video_url($video_path),
                    'size' => filesize($video_path),
                    'created' => filemtime($video_path)
                );

                // Load metadata if exists
                if (file_exists($metadata_path)) {
                    $metadata = json_decode(file_get_contents($metadata_path), true);
                    if ($metadata) {
                        $video_data = array_merge($video_data, $metadata);
                    }
                }

                $videos[] = $video_data;
            }
        }

        // Sort by creation time, newest first
        usort($videos, function($a, $b) {
            return $b['created'] - $a['created'];
        });

        return $videos;
    }

    /**
     * Delete video and its metadata
     *
     * @param string $cache_key Cache key
     * @return array Result with success status and message
     */
    public function delete_video($cache_key) {
        $video_path = $this->cache_dir . $cache_key . '.mp4';
        $metadata_path = $this->cache_dir . $cache_key . '.json';

        $deleted_video = false;
        $deleted_metadata = false;

        if (file_exists($video_path)) {
            $deleted_video = @unlink($video_path);
        }

        if (file_exists($metadata_path)) {
            $deleted_metadata = @unlink($metadata_path);
        }

        if ($deleted_video || $deleted_metadata) {
            return array(
                'success' => true,
                'message' => 'Video deleted successfully'
            );
        }

        return array(
            'success' => false,
            'message' => 'Video not found'
        );
    }
}
