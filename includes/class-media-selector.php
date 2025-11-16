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
     * Create sequence of media items with video randomly placed
     *
     * @param array $images Selected images
     * @param string $video Selected video
     * @param string $template Template style
     * @return array Sequence array
     */
    private function create_sequence($images, $video, $template) {
        $sequence = array();

        // Add images to sequence
        foreach ($images as $index => $image) {
            $sequence[] = array(
                'type' => 'image',
                'path' => $image,
                'index' => $index,
                'duration' => 4 // 4 seconds per image
            );
        }

        // Insert video at random position (not first or last)
        $video_position = rand(3, count($sequence) - 3);
        array_splice($sequence, $video_position, 0, array(
            array(
                'type' => 'video',
                'path' => $video,
                'index' => $video_position,
                'duration' => 4 // 4 seconds per video clip to match images
            )
        ));

        return $sequence;
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

        return array(
            'images' => $images,
            'videos' => $videos,
            'audio' => $audio,
            'requirements' => array(
                'images' => 15,
                'videos' => 1,
                'audio' => 1
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
     * @param string $video_id Video filename
     * @param string $audio_id Audio filename
     * @param string $template Template style
     * @return array|WP_Error Array of selected media or error
     */
    public function select_media_by_ids($image_ids, $video_id, $audio_id, $template = 'classic') {
        // Validate we have the right quantities
        if (count($image_ids) !== 15) {
            return new WP_Error('invalid_selection', 'Exactly 15 images are required. Received: ' . count($image_ids));
        }

        if (empty($video_id)) {
            return new WP_Error('invalid_selection', 'Exactly 1 video is required.');
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

        // Resolve image IDs to paths
        $selected_images = array();
        foreach ($image_ids as $img_id) {
            if (!isset($valid_image_ids[$img_id])) {
                return new WP_Error('invalid_media', 'Invalid image ID: ' . $img_id);
            }
            $selected_images[] = $valid_image_ids[$img_id];
        }

        // Resolve video ID to path
        if (!isset($valid_video_ids[$video_id])) {
            return new WP_Error('invalid_media', 'Invalid video ID: ' . $video_id);
        }
        $selected_video = $valid_video_ids[$video_id];

        // Resolve audio ID to path (optional)
        $selected_audio = null;
        if (!empty($audio_id) && isset($valid_audio_ids[$audio_id])) {
            $selected_audio = $valid_audio_ids[$audio_id];
        }

        // Create compilation sequence
        $sequence = $this->create_sequence($selected_images, $selected_video, $template);

        return array(
            'images' => $selected_images,
            'video' => $selected_video,
            'bg_image' => null,
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
     * @return string FFmpeg zoompan filter string
     */
    public function get_ken_burns_effect($index, $template = 'classic', $frame_count = 120) {
        // Frame count is passed from video generator based on clip duration
        // Default is 120 frames (4 seconds at 30fps) for backward compatibility
        // IMPORTANT: fps=30 parameter ensures zoompan outputs at exactly 30fps
        $patterns = array(
            // Zoom in slowly from center
            "zoompan=z='min(zoom+0.0015,1.3)':d={$frame_count}:s=1920x1080:fps=30",

            // Zoom out from close
            "zoompan=z='if(lte(zoom,1.0),1.3,max(1.0,zoom-0.0015))':d={$frame_count}:s=1920x1080:fps=30",

            // Pan left with slight zoom
            "zoompan=z='min(zoom+0.001,1.2)':x='iw/2-(iw/zoom/2)-((iw/zoom/2)*0.5*in/{$frame_count})':d={$frame_count}:s=1920x1080:fps=30",

            // Pan right with slight zoom
            "zoompan=z='min(zoom+0.001,1.2)':x='iw/2-(iw/zoom/2)+((iw/zoom/2)*0.5*in/{$frame_count})':d={$frame_count}:s=1920x1080:fps=30",

            // Zoom to center from top-left
            "zoompan=z='min(zoom+0.002,1.4)':x='iw/2-(iw/zoom/2)-(iw/10)+(in*(iw/10)/{$frame_count})':y='ih/2-(ih/zoom/2)-(ih/10)+(in*(ih/10)/{$frame_count})':d={$frame_count}:s=1920x1080:fps=30",

            // Zoom to center from bottom-right
            "zoompan=z='min(zoom+0.002,1.4)':x='iw/2-(iw/zoom/2)+(iw/10)-(in*(iw/10)/{$frame_count})':y='ih/2-(ih/zoom/2)+(ih/10)-(in*(ih/10)/{$frame_count})':d={$frame_count}:s=1920x1080:fps=30",

            // Slow zoom with vertical pan up
            "zoompan=z='min(zoom+0.0012,1.25)':y='ih/2-(ih/zoom/2)-((ih/zoom/2)*0.3*in/{$frame_count})':d={$frame_count}:s=1920x1080:fps=30",

            // Slow zoom with vertical pan down
            "zoompan=z='min(zoom+0.0012,1.25)':y='ih/2-(ih/zoom/2)+((ih/zoom/2)*0.3*in/{$frame_count})':d={$frame_count}:s=1920x1080:fps=30"
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
