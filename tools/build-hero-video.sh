#!/usr/bin/env bash
#
# Build media/hero-video.mp4 from the raw client footage.
#
# Run from the repo root:  bash tools/build-hero-video.sh
#
# Source facts that drive the settings below:
#   * IMG_2065.mov stores a 3840x2160 LANDSCAPE frame with `rotation:-90` side
#     data, so it DISPLAYS as 2160x3840 portrait. ffmpeg applies the display
#     matrix before user filters, so the crop is expressed against the portrait
#     frame. Trusting the stored dimensions would give the wrong crop.
#   * The composition is top-weighted: the 16:9 crop keeps full width and only
#     32% of the height because the discarded remainder is empty gravel.
#   * The file embeds GPS (com.apple.quicktime.location.ISO6709) pointing at the
#     marina. `-map_metadata -1` is mandatory, not cosmetic.
#   * MP4 only. VP9 was measured within 4% of H.264 on this footage, which does
#     not pay for a second file; every browser in use decodes H.264.
#
set -euo pipefail

SRC="assets/videos/IMG_2065.mov"
OUT="media/hero-video.mp4"
POSTER="hero-poster.webp"
START=17          # seconds into the source
DUR=13            # clip length
CROP="crop=2160:1215:0:950"
BASE="$CROP,hqdn3d=2:1:2:3,fps=24,scale=1920:1080"

# --- No logo overlay -------------------------------------------------------
# A sliding logo was tried and removed. Its actual purpose was to MASK the
# "SOLD" sticker on the green container, which becomes readable when the rig
# turns and the load faces the camera near the end of the clip — not to be a
# decorative flourish, which is what it was built as. Covering it properly
# means tracking the sticker as it moves through the frame, so the position has
# to be keyframed against the footage rather than parked at frame centre.
# tools/svg2png.py is kept for whenever that is done.
#
# THE STICKER IS STILL VISIBLE in the shipped clip. Anyone re-cutting this
# footage should deal with it, by masking or by choosing a segment without it.

echo "==> encoding $OUT"
ffmpeg -y -v warning -stats \
	-ss "$START" -t "$DUR" -i "$SRC" \
	-vf "$BASE" \
	-an -sn -dn -map_metadata -1 \
	-c:v libx264 -profile:v high -pix_fmt yuv420p -crf 33 -preset slow \
	-movflags +faststart \
	"$OUT"

echo "==> poster (frame 1, before the logo enters)"
ffmpeg -y -v error -ss "$START" -i "$SRC" -frames:v 1 \
	-vf "$CROP,scale=1920:1080" -map_metadata -1 \
	-c:v libwebp -quality 80 -compression_level 6 "$POSTER"

echo
ls -l --block-size=K "$OUT" "$POSTER"
ffprobe -v error -show_entries stream=codec_name,width,height,r_frame_rate,duration \
	-show_entries format_tags -of default=noprint_wrappers=1 "$OUT"
