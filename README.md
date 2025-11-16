# Memorians POC - FFmpeg Video Compilation Plugin

A WordPress plugin that generates memorial video compilations using FFmpeg with random images, videos, transitions, and background music.

## Features

- **Random Media Selection**: Automatically selects 15 random images and 1 video
- **Three Template Styles**:
  - **Classic**: Traditional fades and dissolves
  - **Modern**: Dynamic transitions with circles and pixelization
  - **Elegant**: Sophisticated fades and gentle animations
- **Ken Burns Effects**: Zoom and pan animations on images
- **Professional Transitions**: 8+ different transition effects
- **Background Music**: Looping audio tracks with volume mixing
- **Caching System**: 24-hour video caching for performance
- **Responsive Design**: Works on all devices
- **Full HD Output**: 1920x1080 resolution at 60fps

## Requirements

- WordPress 5.0+
- PHP 7.4+
- FFmpeg 4.4+ installed on server
- PHP `exec()` function enabled
- Minimum 512MB PHP memory_limit
- Minimum 300s PHP max_execution_time

## Installation

1. Upload the `memorians-poc` folder to `/wp-content/plugins/`
2. Activate the plugin through WordPress admin
3. Visit Settings → Permalinks and click "Save Changes" to flush rewrite rules
4. Ensure FFmpeg is installed: `ffmpeg -version`

## Usage

### Accessing the Video Page

Navigate to: `https://memorians.nl/ffmpeg-poc/`

### Generating Videos

1. Select a template style (Classic, Modern, or Elegant)
2. Click "Generate New Video" to create a unique compilation
3. Wait for the video to generate (typically 1-3 minutes)
4. Watch, download, or share the memorial video

### Template Styles

**Classic**
- Transitions: fade, dissolve, smoothleft, smoothright
- Best for: Traditional memorial services
- Mood: Respectful and timeless

**Modern**
- Transitions: smoothleft, smoothright, circleopen, circleclose, pixelize
- Best for: Contemporary celebrations of life
- Mood: Dynamic and artistic

**Elegant**
- Transitions: fade, fadeblack, fadewhite, dissolve, circleopen, circleclose
- Best for: Formal memorial services
- Mood: Sophisticated and gentle

## Directory Structure

```
memorians-poc/
├── memorians-poc.php              # Main plugin file
├── includes/
│   ├── class-video-generator.php  # FFmpeg command builder
│   ├── class-media-selector.php   # Random media selection
│   └── class-cache-manager.php    # Caching system
├── templates/
│   ├── video-page.php             # Main display page
│   ├── generate-ajax.php          # Generation endpoint
│   └── progress-ajax.php          # Progress tracking
├── assets/
│   ├── css/
│   │   └── video-page.css         # Styling
│   └── js/
│       └── video-player.js        # Player controls
├── media/
│   ├── images/                    # Source images (1-34.png)
│   ├── videos/                    # Source videos (1-3.mp4)
│   ├── bg_images/                 # Background images
│   └── audio/                     # Background music
├── cache/                         # Generated videos
│   └── .htaccess                  # Access protection
└── README.md
```

## Media Files

### Images
- Location: `/media/images/`
- Format: PNG, JPG, JPEG
- Count: 34 available
- Selection: 15 random per video

### Videos
- Location: `/media/videos/`
- Format: MP4, MOV, AVI
- Count: 3 available
- Selection: 1 random per video

### Background Images
- Location: `/media/bg_images/`
- Format: PNG, JPG, JPEG
- Count: 5 available
- Usage: Optional overlays (future feature)

### Audio
- Location: `/media/audio/`
- Format: MP3, WAV, AAC
- Count: 2 tracks
- Selection: 1 random per video
- Volume: 25-30% (mixed with video audio)

## Technical Details

### Video Specifications

- **Resolution**: 1920x1080 (Full HD)
- **Frame Rate**: 60 fps
- **Video Codec**: H.264 (libx264)
- **Preset**: medium
- **CRF**: 23 (high quality)
- **Audio Codec**: AAC
- **Audio Bitrate**: 128kbps
- **File Size**: ~150-300MB per 3-5 minute video

### Ken Burns Effects

8 different zoom/pan patterns:
1. Zoom in from center
2. Zoom out from close
3. Pan left with zoom
4. Pan right with zoom
5. Zoom from top-left to center
6. Zoom from bottom-right to center
7. Vertical pan up with zoom
8. Vertical pan down with zoom

### Transitions

- Duration: 1 second overlap
- Randomly selected per template
- Professional memorial-appropriate effects

### Caching

- Videos cached for 24 hours
- Daily cleanup via WP Cron
- Template-based cache keys
- Force regeneration option available

## API Endpoints

### Generate Video
`GET /ffmpeg-poc/generate/?template=classic&force=false`

**Parameters**:
- `template`: classic|modern|elegant (default: classic)
- `force`: true|false (default: false) - Force regeneration

**Response**:
```json
{
  "success": true,
  "status": "completed",
  "video_url": "http://memorians.nl/wp-content/plugins/memorians-poc/cache/memorial_video_classic_2025-01-13.mp4",
  "message": "Video generated successfully!"
}
```

### Check Progress
`GET /ffmpeg-poc/progress/?template=classic`

**Response**:
```json
{
  "status": "generating",
  "progress": 65
}
```

## Troubleshooting

### FFmpeg Not Found
```bash
# Check FFmpeg installation
ffmpeg -version

# Install on Ubuntu/Debian
sudo apt-get update
sudo apt-get install ffmpeg

# Install on CentOS/RHEL
sudo yum install ffmpeg
```

### PHP Execution Timeout
Edit `php.ini`:
```ini
max_execution_time = 300
memory_limit = 512M
```

### Permission Issues
```bash
# Set proper permissions
chmod 755 /wp-content/plugins/memorians-poc/cache
chmod 644 /wp-content/plugins/memorians-poc/media/**/*
```

### Video Not Playing
- Check browser console for errors
- Verify video file exists in cache directory
- Ensure proper MIME types configured
- Test video file directly in browser

## Performance

### Generation Time
- Typical: 1-3 minutes for 3-5 minute video
- Depends on: Server CPU, media file sizes, transition complexity

### Optimization Tips
1. Use SSD storage for faster I/O
2. Increase PHP memory_limit
3. Use FFmpeg hardware acceleration if available
4. Enable opcode caching (OPcache)

## Security

- Cache directory protected with `.htaccess`
- Input sanitization on all parameters
- File path validation prevents directory traversal
- Generated videos served through WordPress

## Future Enhancements

- [ ] Background processing queue
- [ ] Email notification when video ready
- [ ] User upload interface
- [ ] Custom text overlays
- [ ] Multiple background music options
- [ ] Admin dashboard for cache management
- [ ] Video preview thumbnails
- [ ] Social media sharing buttons

## License

GPL v2 or later

## Credits

Developed by Memorians
https://memorians.nl

## Support

For issues and questions:
- Check the troubleshooting section above
- Review FFmpeg logs in `/cache/temp/`
- Contact: support@memorians.nl
