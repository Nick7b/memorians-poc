<?php
/**
 * Media Selector Class
 * Handles random selection of images, videos, background images, and audio
 */

class Memorians_POC_Media_Selector {

    private $images_dir;
    private $videos_dir;
    private $bg_images_dir;
    private $audio_dir;

    public function __construct() {
        $this->images_dir = MEMORIANS_POC_MEDIA_DIR . 'images/';
        $this->videos_dir = MEMORIANS_POC_MEDIA_DIR . 'videos/';
        $this->bg_images_dir = MEMORIANS_POC_MEDIA_DIR . 'bg_images/';
        $this->audio_dir = MEMORIANS_POC_MEDIA_DIR . 'audio/';
    }

    /**
     * Select random media for video compilation
     *
     * @param string $template Template style (classic, modern, elegant)
     * @return array|WP_Error Array of selected media or error
     */
    public function select_media($template = 'classic') {
        // Get all available media files
        $all_images = $this->get_files_from_directory($this->images_dir, array('png', 'jpg', 'jpeg'));
        $all_videos = $this->get_files_from_directory($this->videos_dir, array('mp4', 'mov', 'avi'));
        $all_bg_images = $this->get_files_from_directory($this->bg_images_dir, array('png', 'jpg', 'jpeg'));
        $all_audio = $this->get_files_from_directory($this->audio_dir, array('mp3', 'wav', 'aac'));

        // Validate we have enough media
        if (count($all_images) < 15) {
            return new WP_Error('insufficient_images', 'Not enough images available. Need at least 15.');
        }

        if (count($all_videos) < 1) {
            return new WP_Error('insufficient_videos', 'No videos available. Need at least 1.');
        }

        // Randomly select 15 images
        shuffle($all_images);
        $selected_images = array_slice($all_images, 0, 15);

        // Randomly select 1 video
        $selected_video = $all_videos[array_rand($all_videos)];

        // Randomly select background image (if using one)
        $selected_bg_image = !empty($all_bg_images) ? $all_bg_images[array_rand($all_bg_images)] : null;

        // Randomly select audio track
        $selected_audio = !empty($all_audio) ? $all_audio[array_rand($all_audio)] : null;

        // Create compilation sequence
        $sequence = $this->create_sequence($selected_images, $selected_video, $template);

        return array(
            'images' => $selected_images,
            'video' => $selected_video,
            'bg_image' => $selected_bg_image,
            'audio' => $selected_audio,
            'sequence' => $sequence,
            'template' => $template
        );
    }

    /**
     * Create sequence of images and videos for compilation
     *
     * @param array $images Selected images
     * @param array $videos Selected videos
     * @param string $template Template style
     * @return array Sequence array
     */
    private function create_sequence($images, $videos, $template, $image_duration = 4) {
        $sequence = array();

        // Add images to sequence (using duration from settings)
        foreach ($images as $index => $image) {
            $sequence[] = array(
                'type' => 'image',
                'path' => $image,
                'index' => $index,
                'duration' => $image_duration // Use duration from settings
            );
        }

        // Calculate video positions - distribute evenly throughout sequence
        // Avoid first 3 and last 3 positions for better flow
        $total_slots = count($sequence);
        $video_count = count($videos);
        $safe_start = 3;
        $safe_end = $total_slots - 3;
        $safe_range = $safe_end - $safe_start;

        // Calculate interval between videos
        $interval = floor($safe_range / ($video_count + 1));

        // Insert videos at calculated positions
        foreach ($videos as $video_index => $video) {
            // Get actual video duration
            $video_duration = $this->get_video_duration($video);

            // Calculate position for this video
            // Start from safe_start, then add interval for each video
            $position = $safe_start + ($interval * ($video_index + 1));

            // Adjust for videos already inserted (each insertion shifts positions)
            $adjusted_position = $position + $video_index;

            // Insert video at calculated position
            array_splice($sequence, $adjusted_position, 0, array(
                array(
                    'type' => 'video',
                    'path' => $video,
                    'index' => $adjusted_position,
                    'duration' => $video_duration // Use actual video duration
                )
            ));
        }

        return $sequence;
    }

    /**
     * Get video duration in seconds using ffprobe
     *
     * @param string $video_path Path to video file
     * @return float Video duration in seconds (defaults to 4 if detection fails)
     */
    private function get_video_duration($video_path) {
        // Use ffprobe to get exact video duration
        $command = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($video_path);
        $output = shell_exec($command);

        if ($output !== null) {
            $duration = floatval(trim($output));

            // Validate duration is reasonable (between 0.1 and 300 seconds / 5 minutes)
            if ($duration > 0.1 && $duration <= 300) {
                error_log("Video duration detected: {$duration} seconds for " . basename($video_path));
                return $duration;
            }
        }

        // Fallback to 4 seconds if detection fails
        error_log("Failed to detect video duration for " . basename($video_path) . ", using default 4 seconds");
        return 4;
    }

    /**
     * Get files from directory with specific extensions
     *
     * @param string $directory Directory path
     * @param array $extensions Allowed extensions
     * @return array Array of file paths
     */
    private function get_files_from_directory($directory, $extensions) {
        $files = array();

        if (!is_dir($directory)) {
            return $files;
        }

        $scan = scandir($directory);
        if (!$scan) {
            return $files;
        }

        foreach ($scan as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $file_path = $directory . $file;
            if (!is_file($file_path)) {
                continue;
            }

            $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
            if (in_array($ext, $extensions)) {
                $files[] = $file_path;
            }
        }

        return $files;
    }

    /**
     * Get all available media files
     *
     * @return array|WP_Error Array of all media or error
     */
    public function get_all_media() {
        $all_images = $this->get_files_from_directory($this->images_dir, array('png', 'jpg', 'jpeg'));
        $all_videos = $this->get_files_from_directory($this->videos_dir, array('mp4', 'mov', 'avi'));
        $all_audio = $this->get_files_from_directory($this->audio_dir, array('mp3', 'wav', 'aac'));
        $all_backgrounds = $this->get_files_from_directory($this->bg_images_dir, array('png', 'jpg', 'jpeg'));

        // Convert file paths to URLs and create structured data
        $images = array();
        foreach ($all_images as $index => $path) {
            $filename = basename($path);
            $images[] = array(
                'id' => $filename,
                'filename' => $filename,
                'path' => $path,
                'url' => $this->get_media_url($path)
            );
        }

        $videos = array();
        foreach ($all_videos as $index => $path) {
            $filename = basename($path);
            $videos[] = array(
                'id' => $filename,
                'filename' => $filename,
                'path' => $path,
                'url' => $this->get_media_url($path)
            );
        }

        $audio = array();
        foreach ($all_audio as $index => $path) {
            $filename = basename($path);
            $audio[] = array(
                'id' => $filename,
                'filename' => $filename,
                'path' => $path,
                'url' => $this->get_media_url($path)
            );
        }

        $backgrounds = array();
        foreach ($all_backgrounds as $index => $path) {
            $filename = basename($path);
            $backgrounds[] = array(
                'id' => $filename,
                'filename' => $filename,
                'path' => $path,
                'url' => $this->get_media_url($path)
            );
        }

        return array(
            'images' => $images,
            'videos' => $videos,
            'audio' => $audio,
            'backgrounds' => $backgrounds,
            'requirements' => array(
                'images' => array('min' => 15, 'max' => 40),
                'videos' => array('min' => 1, 'max' => 5),
                'audio' => array('min' => 1, 'max' => 1),
                'background' => array('min' => 0, 'max' => 1)
            )
        );
    }

    /**
     * Convert file path to URL
     *
     * @param string $file_path Full file path
     * @return string URL
     */
    private function get_media_url($file_path) {
        $relative_path = str_replace(MEMORIANS_POC_PLUGIN_DIR, '', $file_path);
        return MEMORIANS_POC_PLUGIN_URL . $relative_path;
    }

    /**
     * Select specific media by IDs for video compilation
     *
     * @param array $image_ids Array of image filenames
     * @param array $video_ids Array of video filenames
     * @param string $audio_id Audio filename
     * @param string $background_id Background image filename (optional)
     * @param string $template Template style
     * @return array|WP_Error Array of selected media or error
     */
    public function select_media_by_ids($image_ids, $video_ids, $audio_id, $background_id = null, $template = 'classic', $image_duration = 4) {
        // Validate image count (min 15, max 40)
        $image_count = count($image_ids);
        if ($image_count < 15 || $image_count > 40) {
            return new WP_Error('invalid_selection', 'Images must be between 15 and 40. Received: ' . $image_count);
        }

        // Validate video count (min 1, max 5)
        $video_count = count($video_ids);
        if ($video_count < 1 || $video_count > 5) {
            return new WP_Error('invalid_selection', 'Videos must be between 1 and 5. Received: ' . $video_count);
        }

        // Get all available media to validate IDs
        $all_media = $this->get_all_media();

        // Build arrays of valid IDs
        $valid_image_ids = array();
        foreach ($all_media['images'] as $img) {
            $valid_image_ids[$img['id']] = $img['path'];
        }

        $valid_video_ids = array();
        foreach ($all_media['videos'] as $vid) {
            $valid_video_ids[$vid['id']] = $vid['path'];
        }

        $valid_audio_ids = array();
        foreach ($all_media['audio'] as $aud) {
            $valid_audio_ids[$aud['id']] = $aud['path'];
        }

        $valid_background_ids = array();
        foreach ($all_media['backgrounds'] as $bg) {
            $valid_background_ids[$bg['id']] = $bg['path'];
        }

        // Resolve image IDs to paths
        $selected_images = array();
        foreach ($image_ids as $img_id) {
            if (!isset($valid_image_ids[$img_id])) {
                return new WP_Error('invalid_media', 'Invalid image ID: ' . $img_id);
            }
            $selected_images[] = $valid_image_ids[$img_id];
        }

        // Resolve video IDs to paths
        $selected_videos = array();
        foreach ($video_ids as $vid_id) {
            if (!isset($valid_video_ids[$vid_id])) {
                return new WP_Error('invalid_media', 'Invalid video ID: ' . $vid_id);
            }
            $selected_videos[] = $valid_video_ids[$vid_id];
        }

        // Resolve audio ID to path (optional)
        $selected_audio = null;
        if (!empty($audio_id) && isset($valid_audio_ids[$audio_id])) {
            $selected_audio = $valid_audio_ids[$audio_id];
        }

        // Resolve background ID to path (optional)
        $selected_background = null;
        if (!empty($background_id) && isset($valid_background_ids[$background_id])) {
            $selected_background = $valid_background_ids[$background_id];
        }

        // Create compilation sequence (pass image duration)
        $sequence = $this->create_sequence($selected_images, $selected_videos, $template, $image_duration);

        return array(
            'images' => $selected_images,
            'videos' => $selected_videos,
            'bg_image' => $selected_background,
            'audio' => $selected_audio,
            'sequence' => $sequence,
            'template' => $template
        );
    }

    /**
     * Get Ken Burns effect for image based on template
     *
     * @param int $index Image index
     * @param string $template Template style
     * @param int $frame_count Number of frames
     * @param float $intensity Zoom intensity multiplier (1.0 = normal)
     * @param int $frame_rate Frame rate (default 30)
     * @return string FFmpeg zoompan filter string
     */
    public function get_ken_burns_effect($index, $template = 'classic', $frame_count = 120, $intensity = 1.0, $frame_rate = 30) {
        // Frame count is passed from video generator based on clip duration
        // Frame rate is now dynamic based on settings
        // Mobile-optimized: 1080x1920 portrait resolution for full-screen mobile viewing

        // Apply intensity scaling to zoom speeds and max zoom values
        $zoom_speed_1 = 0.0015 * $intensity;
        $zoom_speed_2 = 0.001 * $intensity;
        $zoom_speed_3 = 0.002 * $intensity;
        $zoom_speed_4 = 0.0012 * $intensity;

        $max_zoom_1 = 1.0 + (0.3 * $intensity);  // 1.3 at intensity 1.0
        $max_zoom_2 = 1.0 + (0.2 * $intensity);  // 1.2 at intensity 1.0
        $max_zoom_3 = 1.0 + (0.4 * $intensity);  // 1.4 at intensity 1.0
        $max_zoom_4 = 1.0 + (0.25 * $intensity); // 1.25 at intensity 1.0

        $patterns = array(
            // Zoom in slowly from center
            "zoompan=z='min(zoom+{$zoom_speed_1},{$max_zoom_1})':d={$frame_count}:s=1080x1920:fps={$frame_rate}",

            // Zoom out from close
            "zoompan=z='if(lte(zoom,1.0),{$max_zoom_1},max(1.0,zoom-{$zoom_speed_1}))':d={$frame_count}:s=1080x1920:fps={$frame_rate}",

            // Pan left with slight zoom
            "zoompan=z='min(zoom+{$zoom_speed_2},{$max_zoom_2})':x='iw/2-(iw/zoom/2)-((iw/zoom/2)*0.5*in/{$frame_count})':d={$frame_count}:s=1080x1920:fps={$frame_rate}",

            // Pan right with slight zoom
            "zoompan=z='min(zoom+{$zoom_speed_2},{$max_zoom_2})':x='iw/2-(iw/zoom/2)+((iw/zoom/2)*0.5*in/{$frame_count})':d={$frame_count}:s=1080x1920:fps={$frame_rate}",

            // Zoom to center from top-left
            "zoompan=z='min(zoom+{$zoom_speed_3},{$max_zoom_3})':x='iw/2-(iw/zoom/2)-(iw/10)+(in*(iw/10)/{$frame_count})':y='ih/2-(ih/zoom/2)-(ih/10)+(in*(ih/10)/{$frame_count})':d={$frame_count}:s=1080x1920:fps={$frame_rate}",

            // Zoom to center from bottom-right
            "zoompan=z='min(zoom+{$zoom_speed_3},{$max_zoom_3})':x='iw/2-(iw/zoom/2)+(iw/10)-(in*(iw/10)/{$frame_count})':y='ih/2-(ih/zoom/2)+(ih/10)-(in*(ih/10)/{$frame_count})':d={$frame_count}:s=1080x1920:fps={$frame_rate}",

            // Slow zoom with vertical pan up
            "zoompan=z='min(zoom+{$zoom_speed_4},{$max_zoom_4})':y='ih/2-(ih/zoom/2)-((ih/zoom/2)*0.3*in/{$frame_count})':d={$frame_count}:s=1080x1920:fps={$frame_rate}",

            // Slow zoom with vertical pan down
            "zoompan=z='min(zoom+{$zoom_speed_4},{$max_zoom_4})':y='ih/2-(ih/zoom/2)+((ih/zoom/2)*0.3*in/{$frame_count})':d={$frame_count}:s=1080x1920:fps={$frame_rate}"
        );

        // Select pattern based on index (cycling through patterns)
        return $patterns[$index % count($patterns)];
    }

    /**
     * Get transition effect based on template
     *
     * @param string $template Template style
     * @return string Transition name
     */
    public function get_transition($template = 'classic') {
        $transitions = array(
            'classic' => array('fade', 'dissolve', 'smoothleft', 'smoothright'),
            'modern' => array('smoothleft', 'smoothright', 'circleopen', 'circleclose', 'pixelize'),
            'elegant' => array('fade', 'fadeblack', 'fadewhite', 'dissolve', 'circleopen', 'circleclose')
        );

        $template_transitions = isset($transitions[$template]) ? $transitions[$template] : $transitions['classic'];
        return $template_transitions[array_rand($template_transitions)];
    }
}
