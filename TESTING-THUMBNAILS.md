# Thumbnail System Testing Guide

## Summary of Fixes Applied

### 1. Black Border Issue (FIXED)
**Problem:** Video thumbnails had thick black borders around them
**Cause:** FFmpeg was using the `pad` filter which adds black bars
**Solution:** Changed to `crop` filter with `force_original_aspect_ratio=increase`
**File:** `/includes/class-thumbnail-generator.php` (line ~389)

### 2. Image Thumbnails Not Loading (FIXED)
**Problem:** Thumbnail URLs were correct but images didn't load
**Cause:** Missing `src` attribute prevented lazy loading initialization
**Solution:** Added base64 transparent placeholder as initial `src`
**File:** `/assets/js/video-player.js` (line ~275)

### 3. Video Thumbnails Too Large (FIXED)
**Problem:** Video section thumbnails pushed content off-screen
**Cause:** Duplicate CSS definitions with conflicting width values
**Solution:** Removed duplicate definition, set proper max-width
**File:** `/assets/css/video-page.css` (line ~578)

## Testing Steps

### Step 1: Clear Cached Thumbnails
Run this command to remove any existing thumbnails with black borders:
```bash
cd /home/ck1/apps/wp_plugins/memorians-poc/
php clear-thumbnails.php
```

### Step 2: Clear Browser Cache
**Important:** Clear your browser cache completely
- Chrome: Ctrl+Shift+Del → Select "Cached images and files" → Clear data
- Or open Developer Tools → Network tab → Check "Disable cache"

### Step 3: Test Video Page
1. Navigate to: `/ffmpeg-poc/`
2. Wait for the page to load completely

### Step 4: Verify Each Fix

#### A. Check Generated Video Thumbnails (Top Section)
- [ ] Thumbnails should be 320x180 pixels
- [ ] NO black borders should be visible
- [ ] Each thumbnail should fill its container
- [ ] Hover effect should show play button overlay

#### B. Check Media Image Thumbnails (Middle Section)
- [ ] Images should load progressively as you scroll
- [ ] Initial blur effect should transition to sharp image
- [ ] Console should show "Lazy loading image:" messages
- [ ] No 404 or 403 errors in console

#### C. Check Media Video Thumbnails (Bottom Section)
- [ ] Video posters should be 180x101 pixels
- [ ] Should fit within their containers without overflow
- [ ] Grid layout should be maintained
- [ ] No horizontal scrollbar should appear

### Step 5: Performance Check
Open Developer Tools → Network Tab:
- [ ] Initial page load should be < 2MB (was ~180MB)
- [ ] Thumbnails should load on-demand as you scroll
- [ ] WebP format should be used where supported

## Troubleshooting

### If black borders still appear:
1. The old thumbnails might be cached
2. Run the clear-thumbnails.php script again
3. Check browser Developer Tools → Network tab
4. Look for "from disk cache" - if present, do a hard refresh (Ctrl+Shift+R)

### If images still don't load:
1. Open Console (F12) and look for errors
2. Check if lazy loading messages appear
3. Verify the thumbnail URL returns an image when opened directly
4. Check for any JavaScript errors

### If video thumbnails still overflow:
1. Inspect element with Developer Tools
2. Check computed styles for `.video-item-preview`
3. Width should be max 180px
4. Height should be 101px

## Expected Console Output
When working correctly, you should see:
```
Lazy loading image: /cache/thumbnails/media/images/...
Loaded lazy image: /cache/thumbnails/media/images/...
Generated video thumbnail loaded: memorial_video_...
```

## Quick Verification Commands

Check if thumbnails are being generated:
```bash
ls -la /home/ck1/apps/wp_plugins/memorians-poc/cache/thumbnails/
ls -la /home/ck1/apps/wp_plugins/memorians-poc/cache/posters/
```

Check FFmpeg command (should show "crop" not "pad"):
```bash
grep -n "pad\|crop" /home/ck1/apps/wp_plugins/memorians-poc/includes/class-thumbnail-generator.php
```

## Report Results
After testing, please report:
1. Which fixes are working correctly
2. Any remaining issues with specific details
3. Console errors if any
4. Network tab screenshot if helpful