#!/usr/bin/env python3
"""Generate the /about timeline photos from the source library.

Sources live in `assets/about/` (gitignored client media). Output goes to
`media/about/` as WebP with no metadata — the same rules as
tools/build-gallery.py, whose helpers this reuses: EXIF rotation applied
first, colour converted to sRGB, and nothing (EXIF, GPS, ICC) carried over.

Each photo gets a full tier (width ≤ 1120) and a -560 tier for 1x screens.
Sources are never upscaled; if the -560 tier would be no smaller than the
full one it is not written.

The printed dimensions are what about.php's $timeline array needs for the
<img> width/height attributes.

Usage:  python3 tools/build-about.py
"""
import importlib.util
import sys
from pathlib import Path

from PIL import Image, ImageOps

ROOT = Path(__file__).resolve().parent.parent
SRC = ROOT / "assets" / "about"
OUT = ROOT / "media" / "about"

_spec = importlib.util.spec_from_file_location("build_gallery", ROOT / "tools" / "build-gallery.py")
gallery = importlib.util.module_from_spec(_spec)
_spec.loader.exec_module(gallery)

# slug -> (source file, optional crop box in pixels after rotation)
PHOTOS = {
    "1974": ("VIVILAKI_AFOI_OE_386a5425e2bb4a22abab4ab238f01b76.jpg", None),
    "2002": ("VIVILAKI_AFOI_OE_495ea6f97a8e4ec199c79ecff2ca3fa5.jpg", None),
    # 4:5 — trims empty tarmac below the truck, keeps the full boom and sky.
    "2025": ("CRANE1_11zon.jpg", (0, 0, 1920, 2400)),
}

TIERS = [("", 1120, 80), ("-560", 560, 78)]


def main() -> int:
    OUT.mkdir(parents=True, exist_ok=True)
    for slug, (name, box) in PHOTOS.items():
        source = SRC / name
        if not source.exists():
            print(f"missing {source.relative_to(ROOT)}", file=sys.stderr)
            return 1
        im = gallery.to_srgb(ImageOps.exif_transpose(Image.open(source)))
        if box:
            im = im.crop(box)
        full = None
        for suffix, width, quality in TIERS:
            scale = min(1.0, width / im.width)
            out = im.copy() if scale == 1.0 else im.resize(
                (round(im.width * scale), round(im.height * scale)), Image.LANCZOS)
            if full and out.width >= full[0]:
                (OUT / f"{slug}{suffix}.webp").unlink(missing_ok=True)
                continue
            full = full or out.size
            gallery.save_webp(out, OUT / f"{slug}{suffix}.webp", quality)
            print(f"  {slug}{suffix:5} {out.width}x{out.height}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
