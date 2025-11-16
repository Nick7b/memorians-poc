<?php
/**
 * Video Generator Class
 * Builds and executes FFmpeg commands for video compilation
 */

class Memorians_POC_Video_Generator {

    private $media_selector;
    private $cache_manager;
    private $temp_dir;

    public function __construct() {
        $this->media_selector = new Memorians_POC_Media_Selector();
        $this->cache_manager = new Memorians_POC_Cache_Manager();
        $this->temp_dir = MEMORIANS_POC_CACHE_DIR . 'temp/';

        // Create temp directory if doesn't exist
        if (!file_exists($this->temp_dir)) {
            wp_mkdir_p($this->temp_dir);
        }
    }

    /**
     * Generate video compilation with user-selected media
     *
     * @param string $template Template style (classic, modern, elegant)
     * @param array $image_ids Array of selected image IDs
     * @param array $video_ids Array of selected video IDs
     * @param string $audio_id Selected audio ID
     * @param string $background_id Selected background image ID (optional)
     * @return array Result with video path or error
     */
    public function generate_with_selection($template, $image_ids, $video_ids, $audio_id = null, $background_id = null) {
        // Check if FFmpeg is available
        if (!$this->check_ffmpeg()) {
            return array(
                'success' => false,
                'error' => 'FFmpeg is not installed or not accessible.'
            );
        }

        // Create selection array for cache key
        $selection = array(
            'images' => $image_ids,
            'videos' => $video_ids,
            'audio' => $audio_id,
            'background' => $background_id
        );

        // Get cache key based on selection
        $cache_key = $this->cache_manager->get_cache_key($template, $selection);

        // Check if already generating
        $status = $this->cache_manager->get_generation_status($template);
        if ($status && $status['status'] === 'generating') {
            return array(
                'success' => false,
                'error' => 'Video generation already in progress.',
                'status' => 'generating'
            );
        }

        // Select media by IDs
        $media = $this->media_selector->select_media_by_ids($image_ids, $video_ids, $audio_id, $background_id, $template);
        if (is_wp_error($media)) {
            return array(
                'success' => false,
                'error' => $media->get_error_message()
            );
        }

        // Set status to generating with cache_key and selection
        $this->cache_manager->set_generation_status($template, 'generating', array(
            'start_time' => time(),
            'media_count' => count($media['sequence']),
            'cache_key' => $cache_key,
            'selection' => $selection
        ));

        // Build FFmpeg command
        $output_path = MEMORIANS_POC_CACHE_DIR . $cache_key . '.mp4';
        $command = $this->build_ffmpeg_command($media, $output_path, $template);

        // Check if command was built successfully
        if ($command === false) {
            return array(
                'success' => false,
                'error' => 'Not enough images to create video. Need at least 3 images.'
            );
        }

        // Execute FFmpeg command asynchronously
        $result = $this->execute_ffmpeg($command, $output_path, $template, $cache_key);

        if (!$result['success']) {
            // Failed to start process
            $this->cache_manager->set_generation_status($template, 'failed', array(
                'error' => $result['error']
            ));
            return $result;
        }

        // Save metadata for this video
        $metadata = array(
            'template' => $template,
            'selection' => $selection,
            'media_count' => count($media['sequence'])
        );
        $this->cache_manager->save_metadata($cache_key, $metadata);

        // Process started successfully - status remains 'generating'
        // Progress endpoint will update status to 'completed' when process finishes
        return array(
            'success' => true,
            'status' => 'generating',
            'message' => 'Video generation started successfully',
            'cache_key' => $cache_key
        );
    }

    /**
     * Generate video compilation
     *
     * @param string $template Template style (classic, modern, elegant)
     * @return array Result with video path or error
     */
    public function generate($template = 'classic') {
        // Check if FFmpeg is available
        if (!$this->check_ffmpeg()) {
            return array(
                'success' => false,
                'error' => 'FFmpeg is not installed or not accessible.'
            );
        }

        // Check if already generating
        $status = $this->cache_manager->get_generation_status($template);
        if ($status && $status['status'] === 'generating') {
            return array(
                'success' => false,
                'error' => 'Video generation already in progress.',
                'status' => 'generating'
            );
        }

        // Select media
        $media = $this->media_selector->select_media($template);
        if (is_wp_error($media)) {
            return array(
                'success' => false,
                'error' => $media->get_error_message()
            );
        }

        // Set status to generating
        $this->cache_manager->set_generation_status($template, 'generating', array(
            'start_time' => time(),
            'media_count' => count($media['sequence'])
        ));

        // Build FFmpeg command
        $output_path = MEMORIANS_POC_CACHE_DIR . $this->cache_manager->get_cache_key($template) . '.mp4';
        $command = $this->build_ffmpeg_command($media, $output_path, $template); // Use full version with effects

        // Check if command was built successfully
        if ($command === false) {
            return array(
                'success' => false,
                'error' => 'Not enough images to create video. Need at least 3 images.'
            );
        }

        // Execute FFmpeg command asynchronously
        $result = $this->execute_ffmpeg($command, $output_path, $template);

        if (!$result['success']) {
            // Failed to start process
            $this->cache_manager->set_generation_status($template, 'failed', array(
                'error' => $result['error']
            ));
            return $result;
        }

        // Process started successfully - status remains 'generating'
        // Progress endpoint will update status to 'completed' when process finishes
        return array(
            'success' => true,
            'status' => 'generating',
            'message' => 'Video generation started successfully'
        );
    }

    /**
     * Build FFmpeg command (ultra-simplified version for POC)
     *
     * @param array $media Selected media
     * @param string $output_path Output file path
     * @param string $template Template style
     * @return string FFmpeg command
     */
    private function build_ffmpeg_command_simple($media, $output_path, $template) {
        // Ultra-simplified: only 5 images with simple fade, no video clips
        $inputs = array();
        $filter_parts = array();

        // Use only 5 images for fast generation
        $image_count = 0;
        foreach ($media['sequence'] as $item) {
            if ($item['type'] === 'image' && $image_count < 5) {
                $inputs[] = "-loop 1 -t 4 -i " . escapeshellarg($item['path']);
                $image_count++;
            }
        }

        // If we don't have enough images, bail out
        if ($image_count < 3) {
            return false;
        }

        // Scale all inputs to same size (720p for faster encoding)
        // Force yuv420p pixel format for browser compatibility
        for ($i = 0; $i < $image_count; $i++) {
            $filter_parts[] = "[{$i}:v]scale=1280:720:force_original_aspect_ratio=decrease,pad=1280:720:(ow-iw)/2:(oh-ih)/2,setsar=1,fps=25,format=yuv420p[v{$i}]";
        }

        // Chain simple fade transitions
        $current = "v0";
        $offset = 3; // Start first transition at 3 seconds

        for ($i = 1; $i < $image_count; $i++) {
            $next = ($i < $image_count - 1) ? "vt{$i}" : "vout";
            // Use simple fade transition for speed
            $filter_parts[] = "[{$current}][v{$i}]xfade=transition=fade:duration=1:offset={$offset}[{$next}]";
            $current = $next;
            $offset += 3; // 4 seconds per image - 1 second overlap
        }

        // Add background audio if available (limit to video duration)
        $video_duration = $offset + 1; // Total video duration in seconds
        if ($media['audio']) {
            $inputs[] = "-t " . $video_duration . " -i " . escapeshellarg($media['audio']);
            $filter_parts[] = "[" . $image_count . ":a]volume=0.3,afade=t=in:st=0:d=2,afade=t=out:st=" . ($video_duration - 2) . ":d=2[aout]";
        } else {
            // Create silent audio matching video duration
            $filter_parts[] = "anullsrc=channel_layout=stereo:sample_rate=44100:duration=" . $video_duration . "[aout]";
        }

        $inputs_str = implode(' ', $inputs);
        $filter_str = implode('; ', $filter_parts);

        // Use faster preset and lower resolution for POC
        // Audio duration is already limited to match video, so no need for -shortest
        // Explicitly set yuv420p pixel format for maximum browser compatibility
        $command = "ffmpeg -y {$inputs_str} -filter_complex \"{$filter_str}\" -map \"[vout]\" -map \"[aout]\" -c:v libx264 -preset ultrafast -crf 28 -pix_fmt yuv420p -c:a aac -b:a 128k " . escapeshellarg($output_path);

        return $command;
    }

    /**
     * Build FFmpeg command (full version with Ken Burns and transitions)
     *
     * @param array $media Selected media
     * @param string $output_path Output file path
     * @param string $template Template style
     * @return string FFmpeg command
     */
    private function build_ffmpeg_command($media, $output_path, $template) {
        $inputs = array();
        $filter_complex = array();
        $input_index = 0;

        // Calculate total video duration first
        $total_duration = 0;
        $sequence_count = count($media['sequence']);
        foreach ($media['sequence'] as $item) {
            $total_duration += ($item['duration'] ?? 4);
        }
        // Subtract overlaps (1 second per transition)
        $total_duration -= ($sequence_count - 1);

        // Debug log
        error_log("Video generation: {$sequence_count} items, calculated duration: {$total_duration} seconds");

        // Process each item in sequence
        foreach ($media['sequence'] as $seq_index => $item) {
            // Get the duration for this item (images have preset duration, videos we'll determine)
            $clip_duration = $item['duration'] ?? 4;

            // Calculate exact frame count needed: duration × framerate
            // For xfade to work properly, each input must produce exact frame count
            $frame_count = $clip_duration * 30; // 30fps standard

            if ($item['type'] === 'image') {
                // Add image input with loop, framerate set to 30fps, and exact duration
                // Setting -framerate 30 ensures consistent timebase (1/30) for all inputs
                $inputs[] = "-loop 1 -framerate 30 -t {$clip_duration} -i " . escapeshellarg($item['path']);

                // Apply Ken Burns effect with exact frame count matching the clip duration
                // zoompan d parameter = number of frames this effect should generate
                // settb=AVTB uses automatic video timebase for proper synchronization
                // Mobile-optimized: 1080x1920 portrait orientation for full-screen mobile viewing
                $ken_burns = $this->media_selector->get_ken_burns_effect($seq_index, $template, $frame_count);
                $filter_complex[] = "[{$input_index}:v]fps=30,scale=1080:1920:force_original_aspect_ratio=decrease,pad=1080:1920:(ow-iw)/2:(oh-ih)/2,{$ken_burns},setpts=PTS-STARTPTS,settb=AVTB,setsar=1,format=yuv420p[v{$seq_index}]";

                $input_index++;
            } else {
                // Add video input - no duration limiting at input stage
                $inputs[] = "-i " . escapeshellarg($item['path']);

                // For video clips: normalize to 30fps and extract exact frame count
                // 1. fps=30 converts any source framerate to 30fps (duplicate/drop frames as needed)
                // 2. setpts=PTS-STARTPTS resets timestamps to start at 0
                // 3. select='lt(n,{$frame_count})' selects EXACTLY the required frames
                // 4. setpts=N/(30*TB) generates sequential timestamps (prevents frame timing issues)
                // 5. All other filters normalize resolution and format for xfade compatibility
                // This approach works for videos of ANY duration - we just take the first N frames
                // Mobile-optimized: 1080x1920 portrait orientation for full-screen mobile viewing
                $filter_complex[] = "[{$input_index}:v]fps=30,setpts=PTS-STARTPTS,select='lt(n,{$frame_count})',setpts=N/(30*TB),scale=1080:1920:force_original_aspect_ratio=decrease,pad=1080:1920:(ow-iw)/2:(oh-ih)/2,settb=AVTB,setsar=1,format=yuv420p[v{$seq_index}]";

                // Video audio is ignored - we only use background music
                $input_index++;
            }
        }

        // Add background audio input - limit to video duration
        if ($media['audio']) {
            $audio_input_index = $input_index;
            $inputs[] = "-t {$total_duration} -i " . escapeshellarg($media['audio']);
            $input_index++;
        }

        // Build xfade transitions between all clips
        // Important: xfade OVERLAPS videos - the transition duration is consumed from both inputs
        // Offset calculation must account for this overlap to maintain proper timing
        $current_video = "v0";
        $transition_duration = 1; // 1 second crossfade
        $offset = $media['sequence'][0]['duration'] ?? 4;

        for ($i = 1; $i < count($media['sequence']); $i++) {
            $transition = $this->media_selector->get_transition($template);
            $next_label = ($i < count($media['sequence']) - 1) ? "vt{$i}" : "vout";

            // Offset is when the transition starts in the accumulated timeline
            // We want it to start {transition_duration} seconds before the current offset
            $filter_complex[] = "[{$current_video}][v{$i}]xfade=transition={$transition}:duration={$transition_duration}:offset=" . ($offset - $transition_duration) . "[{$next_label}]";

            $current_video = $next_label;

            // Add next clip duration but subtract transition overlap
            // This is critical: each transition consumes time from both clips
            $offset += ($media['sequence'][$i]['duration'] ?? 4) - $transition_duration;
        }

        // Handle audio (background music only, video audio is muted)
        if ($media['audio']) {
            // Use background music with fades
            $filter_complex[] = "[{$audio_input_index}:a]volume=0.3,afade=t=in:st=0:d=2,afade=t=out:st=" . ($total_duration - 2) . ":d=2[aout]";
        } else {
            // No audio at all, create silence
            $filter_complex[] = "anullsrc=channel_layout=stereo:sample_rate=44100:duration={$total_duration}[aout]";
        }

        // Build final command
        $inputs_str = implode(' ', $inputs);
        $filter_str = implode('; ', $filter_complex);

        // Mobile-optimized encoding settings for portrait video (1080x1920)
        // -f mp4 explicitly sets MP4 muxer format
        // -profile:v baseline -level 4.0 uses most compatible H.264 settings for all mobile devices
        // -preset medium balances encoding speed and compression efficiency
        // -crf 23 provides excellent quality with variable bitrate (adaptive quality)
        // -g 60 sets keyframe interval to 2 seconds (30fps × 2) for better seeking/streaming
        // -bf 2 enables B-frames for better compression (10-20% smaller files without quality loss)
        // -pix_fmt yuv420p ensures universal mobile compatibility
        // -c:a aac AAC audio codec (universal mobile standard)
        // -b:a 192k audio bitrate for better quality on mobile speakers/headphones
        // -ar 48000 sets 48kHz sample rate (video standard, better quality than 44.1kHz)
        // -movflags +faststart relocates metadata to beginning for instant mobile streaming
        // -stats_period 0.5 outputs progress stats every 0.5 seconds
        $command = "ffmpeg -y -stats_period 0.5 {$inputs_str} -filter_complex \"{$filter_str}\" -map \"[vout]\" -map \"[aout]\" -t {$total_duration} -shortest -c:v libx264 -profile:v baseline -level 4.0 -preset medium -crf 23 -g 60 -bf 2 -pix_fmt yuv420p -c:a aac -b:a 192k -ar 48000 -f mp4 -movflags +faststart " . escapeshellarg($output_path);

        return $command;
    }

    /**
     * Execute FFmpeg command asynchronously
     *
     * @param string $command FFmpeg command
     * @param string $output_path Output file path
     * @param string $template Template style
     * @param string $cache_key Cache key for this generation
     * @return array Result
     */
    private function execute_ffmpeg($command, $output_path, $template, $cache_key = null) {
        // Use provided cache_key or generate one
        if ($cache_key === null) {
            $cache_key = $this->cache_manager->get_cache_key($template);
        }
        $log_file = $this->temp_dir . 'ffmpeg_' . $cache_key . '.log';
        $cmd_file = $this->temp_dir . 'command_' . $cache_key . '.txt';
        $pid_file = $this->temp_dir . 'pid_' . $cache_key . '.txt';
        $script_file = $this->temp_dir . 'run_' . $cache_key . '.sh';

        // Save command for debugging
        file_put_contents($cmd_file, $command);

        // Clear/create empty log file
        file_put_contents($log_file, '');

        // Create a shell script that will run FFmpeg in background
        $script_content = "#!/bin/bash\n";
        $script_content .= "# Auto-generated FFmpeg execution script\n";
        $script_content .= "stdbuf -oL -eL " . $command . " > " . escapeshellarg($log_file) . " 2>&1 &\n";
        $script_content .= "echo $! > " . escapeshellarg($pid_file) . "\n";

        file_put_contents($script_file, $script_content);
        chmod($script_file, 0755);

        // Execute the script - this returns immediately
        exec($script_file . ' 2>&1', $output, $return_var);

        if ($return_var !== 0) {
            $error_output = implode("\n", $output);
            return array(
                'success' => false,
                'error' => 'Failed to start FFmpeg process. Return code: ' . $return_var . '. Output: ' . $error_output
            );
        }

        // Give process a moment to start and write PID
        usleep(200000); // 200ms

        // Verify PID file was created
        if (!file_exists($pid_file)) {
            return array(
                'success' => false,
                'error' => 'Failed to start FFmpeg process. PID file not created.'
            );
        }

        $pid = trim(file_get_contents($pid_file));

        // Verify PID is valid
        if (empty($pid) || !is_numeric($pid)) {
            return array(
                'success' => false,
                'error' => 'Invalid PID: ' . $pid
            );
        }

        // Note: We don't check if process is still running here because:
        // 1. The process might complete very quickly for small videos
        // 2. The progress endpoint will verify completion anyway
        // 3. If FFmpeg failed, the progress endpoint will detect the missing output file

        // Return immediately - process runs in background
        return array(
            'success' => true,
            'status' => 'generating',
            'message' => 'Video generation started in background',
            'pid' => $pid
        );
    }

    /**
     * Check if FFmpeg is available
     *
     * @return bool
     */
    private function check_ffmpeg() {
        $output = shell_exec('ffmpeg -version 2>&1');
        return (strpos($output, 'ffmpeg version') !== false);
    }

    /**
     * Get generation progress
     *
     * @param string $template Template style
     * @return array Progress data
     */
    public function get_progress($template) {
        $status = $this->cache_manager->get_generation_status($template);

        if (!$status) {
            error_log('get_progress(' . $template . '): No status found');
            return array(
                'status' => 'idle',
                'progress' => 0
            );
        }

        error_log('get_progress(' . $template . '): Current status = ' . $status['status']);

        if ($status['status'] === 'completed') {
            return array(
                'status' => 'completed',
                'progress' => 100,
                'video_url' => $status['data']['video_url']
            );
        }

        if ($status['status'] === 'failed') {
            return array(
                'status' => 'failed',
                'progress' => 0,
                'error' => $status['data']['error']
            );
        }

        // Get cache_key from the status data (stored during generation)
        if (!isset($status['data']['cache_key'])) {
            error_log('ERROR: cache_key not found in generation status');
            return array(
                'status' => 'failed',
                'progress' => 0,
                'error' => 'Invalid generation status: missing cache_key'
            );
        }

        $cache_key = $status['data']['cache_key'];
        error_log('get_progress(' . $template . '): Using cache_key = ' . $cache_key);

        // Check if process is still running
        $pid_file = $this->temp_dir . 'pid_' . $cache_key . '.txt';
        $output_path = MEMORIANS_POC_CACHE_DIR . $cache_key . '.mp4';
        $log_file = $this->temp_dir . 'ffmpeg_' . $cache_key . '.log';

        error_log('get_progress(' . $template . '): Checking PID file: ' . $pid_file);
        error_log('get_progress(' . $template . '): Output path: ' . $output_path);

        // Check if process has finished using multiple methods
        $process_finished = false;

        // Method 1: Check PID file and /proc
        if (file_exists($pid_file)) {
            $pid = trim(file_get_contents($pid_file));
            // Check if process is still running (Linux/Unix)
            $running = file_exists("/proc/$pid");
            error_log('get_progress(' . $template . '): PID ' . $pid . ' running = ' . ($running ? 'yes' : 'no'));

            if (!$running) {
                $process_finished = true;
            }
        } else {
            // PID file doesn't exist, assume process finished
            error_log('get_progress(' . $template . '): PID file not found, assuming finished');
            $process_finished = true;
        }

        // Method 2: Double-check by looking for completion marker in FFmpeg log
        // FFmpeg writes "muxing overhead:" only at the very end after encoding completes
        if ($process_finished && file_exists($log_file)) {
            $log_content = file_get_contents($log_file);
            $has_completion_marker = (strpos($log_content, 'muxing overhead:') !== false);

            if (!$has_completion_marker) {
                error_log('get_progress(' . $template . '): Process appears finished but no completion marker in log - still encoding');
                $process_finished = false;
            } else {
                error_log('get_progress(' . $template . '): Completion marker found in log - encoding actually finished');
            }
        }

        error_log('get_progress(' . $template . '): Process finished = ' . ($process_finished ? 'yes' : 'no'));

        // If process finished, check result and update status
        if ($process_finished) {
            // Check if video was created successfully
            if (file_exists($output_path) && filesize($output_path) > 0) {
                // Success - update status to completed
                $this->cache_manager->set_generation_status($template, 'completed', array(
                    'cache_key' => $cache_key,
                    'video_path' => $output_path,
                    'video_url' => $this->cache_manager->get_video_url($output_path),
                    'completion_time' => time(),
                    'file_size' => filesize($output_path)
                ));

                // Clean up PID file and shell script
                @unlink($pid_file);
                $script_file = $this->temp_dir . 'run_' . $cache_key . '.sh';
                @unlink($script_file);

                error_log('Video generation completed successfully. File: ' . $output_path . ' (' . filesize($output_path) . ' bytes)');

                return array(
                    'status' => 'completed',
                    'progress' => 100,
                    'video_url' => $this->cache_manager->get_video_url($output_path)
                );
            } else {
                // Failed - video not created
                $log_content = file_exists($log_file) ? file_get_contents($log_file) : '';
                $error_msg = 'Video generation failed. File not created or empty.';

                $this->cache_manager->set_generation_status($template, 'failed', array(
                    'error' => $error_msg,
                    'log_content' => substr($log_content, -2000)
                ));

                // Clean up PID file and shell script
                @unlink($pid_file);
                $script_file = $this->temp_dir . 'run_' . $cache_key . '.sh';
                @unlink($script_file);

                error_log('Video generation failed. Output file missing or empty.');

                return array(
                    'status' => 'failed',
                    'progress' => 0,
                    'error' => $error_msg
                );
            }
        }

        // Process still running - parse FFmpeg log for progress
        $media_count = isset($status['data']['media_count']) ? $status['data']['media_count'] : 16;
        $expected_duration = ($media_count * 4) - ($media_count - 1); // 4 sec per item minus transitions

        $progress = $this->parse_ffmpeg_progress($log_file, $expected_duration);

        return array(
            'status' => 'generating',
            'progress' => $progress
        );
    }

    /**
     * Parse FFmpeg log for progress
     *
     * @param string $log_file Log file path
     * @param int $expected_duration Expected total duration in seconds
     * @return int Progress percentage
     */
    private function parse_ffmpeg_progress($log_file, $expected_duration) {
        if (!file_exists($log_file)) {
            return 5; // File doesn't exist yet, just starting
        }

        // Get file size to check if log has content
        $file_size = filesize($log_file);
        if ($file_size === 0) {
            return 5; // Log file is empty, just starting
        }

        // Read last 50 lines of log (reduced from 100 for better performance)
        $file_content = file($log_file);
        if (empty($file_content)) {
            return 5;
        }

        $lines = array_slice($file_content, -50);
        $current_time = 0;

        // Search from last line backwards for most recent time
        foreach (array_reverse($lines) as $line) {
            // Look for time= HH:MM:SS.ms
            if (preg_match('/time=(\d+):(\d+):(\d+)\.(\d+)/', $line, $matches)) {
                $hours = intval($matches[1]);
                $minutes = intval($matches[2]);
                $seconds = intval($matches[3]);
                $current_time = ($hours * 3600) + ($minutes * 60) + $seconds;
                break; // Found most recent time, stop searching
            }
        }

        if ($expected_duration > 0 && $current_time > 0) {
            $progress = intval(($current_time / $expected_duration) * 100);
            return min($progress, 99); // Never show 100% until actually complete
        }

        // If we have log content but no time yet, FFmpeg is initializing
        return 8;
    }
}
