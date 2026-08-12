#!/usr/bin/env python3
"""Generate the gallery derivatives and manifest from the source library.

`assets/images/` is the raw client media (gitignored, ~68 MB of phone photos).
This emits web-sized WebP derivatives into `media/gallery/` plus a
`manifest.php`, which `gallery.php` renders twice — once as the visible grid,
once as ImageObject schema — so the two cannot drift apart. Same single-source
rule as the $faq array in index.php.

Three properties of the sources make the processing steps mandatory, not
defensive:

  * 11 of 22 carry EXIF orientation 6 (rotate 90° CW). Without
    exif_transpose, half the gallery renders sideways.
  * 21 of 22 carry GPS EXIF. Derivatives are written with no metadata at
    all, so the client's location trail is never published.
  * All carry a Display P3 ICC profile. Dropping that without converting
    would leave browsers to assume sRGB and render every photo
    oversaturated, so the pixels are converted to sRGB first.

Order and alt text come from `tools/gallery-alt.json`, which is the authored
input. This script never invents, guesses or overwrites alt text — a missing
entry is reported and the image is skipped, because an image with no alt text
has no business shipping.

It also regenerates `sitemap.xml`, since the gallery's image entries are the
only part of it that changes and a hand-maintained image sitemap would drift.

Usage:  python3 tools/build-gallery.py
Exit 0 = clean, 1 = missing alt text or unreadable source.
"""
import io
import json
import re
import sys
from pathlib import Path

from PIL import Image, ImageCms, ImageOps

ROOT = Path(__file__).resolve().parent.parent
SRC = ROOT / "assets" / "images"
OUT = ROOT / "media" / "gallery"
ALT = ROOT / "tools" / "gallery-alt.json"
BASE = "https://vivilakiscranes.gr"

# Three tiers so the grid can offer a real srcset: 400 for the masonry column
# at 1x, 800 for the same column at 2x, 1600 for the lightbox.
TIERS = [("", 1600, 80), ("-800", 800, 78), ("-thumb", 400, 75)]

SRGB = ImageCms.createProfile("sRGB")


def to_srgb(im: Image.Image) -> Image.Image:
    """Convert to sRGB using the embedded profile, then forget the profile."""
    icc = im.info.get("icc_profile")
    if icc:
        try:
            src = ImageCms.ImageCmsProfile(io.BytesIO(icc))
            im = ImageCms.profileToProfile(im, src, SRGB, outputMode="RGB")
        except ImageCms.PyCMSError as e:
            print(f"    ICC conversion failed ({e}); falling back to raw RGB")
    return im.convert("RGB")


def resize(im: Image.Image, edge: int) -> Image.Image:
    """Downscale so the long edge is `edge`. Never upscales."""
    scale = min(1.0, edge / max(im.size))
    if scale == 1.0:
        return im.copy()
    size = (round(im.width * scale), round(im.height * scale))
    return im.resize(size, Image.LANCZOS)


def save_webp(im: Image.Image, path: Path, quality: int) -> None:
    im.info = {}          # icc_profile is read from .info on save; exif is not written unless passed
    im.save(path, "WEBP", quality=quality, method=6)


def main() -> int:
    if not ALT.exists():
        print(f"missing {ALT.relative_to(ROOT)} — nothing to build", file=sys.stderr)
        return 1

    entries = json.loads(ALT.read_text(encoding="utf-8"))
    OUT.mkdir(parents=True, exist_ok=True)

    # Re-encoding 22 sources takes minutes; the sitemap changes whenever a page
    # is added. --sitemap-only rewrites it from the existing manifest.
    if "--sitemap-only" in sys.argv:
        manifest_file = OUT / "manifest.php"
        if not manifest_file.exists():
            print("no manifest yet — run without --sitemap-only first", file=sys.stderr)
            return 1
        existing = [
            {"file": f, "alt": a}
            for f, a in re.findall(r"'file' => '(.*?)'.*?'alt' => '(.*?)'\],",
                                   manifest_file.read_text(encoding="utf-8"))
        ]
        write_sitemap(existing)
        print(f"sitemap.xml rewritten from {len(existing)} manifest entries.")
        return 0

    manifest, problems = [], []

    for i, entry in enumerate(entries, start=1):
        name, alt = entry.get("src", ""), (entry.get("alt") or "").strip()
        source = SRC / name
        slug = f"g{i:02d}"

        if not source.exists():
            problems.append(f"{name}: no such source")
            continue
        if not alt:
            problems.append(f"{name}: no alt text")
            continue

        try:
            im = ImageOps.exif_transpose(Image.open(source))
        except OSError as e:
            problems.append(f"{name}: unreadable ({e})")
            continue

        im = to_srgb(im)

        full = None
        for suffix, edge, quality in TIERS:
            out = resize(im, edge)
            if full is None:
                full = (out.width, out.height)
            save_webp(out, OUT / f"{slug}{suffix}.webp", quality)

        manifest.append({"file": slug, "w": full[0], "h": full[1], "alt": alt})
        print(f"  {slug}  {name:20} {im.width}x{im.height} -> {full[0]}x{full[1]}")

    if problems:
        for p in problems:
            print(f"  PROBLEM  {p}")
        print(f"\n{len(problems)} problem(s); manifest not written.", file=sys.stderr)
        return 1

    php = ["<?php", "// GENERATED by tools/build-gallery.py — do not edit.",
           "// Alt text and ordering live in tools/gallery-alt.json.", "return ["]
    for m in manifest:
        alt = m["alt"].replace("\\", "\\\\").replace("'", "\\'")
        php.append(f"\t['file' => '{m['file']}', 'w' => {m['w']}, 'h' => {m['h']}, 'alt' => '{alt}'],")
    php.append("];")
    (OUT / "manifest.php").write_text("\n".join(php) + "\n", encoding="utf-8")

    write_sitemap(manifest)

    total = sum(f.stat().st_size for f in OUT.glob("*.webp"))
    print(f"\n{len(manifest)} image(s), {total // 1024} KB of derivatives, manifest + sitemap written.")
    return 0


def write_sitemap(manifest: list[dict]) -> None:
    """Regenerate sitemap.xml.

    Written here rather than maintained by hand because the gallery's image
    entries are the only part that changes, and a sitemap listing images that
    no longer exist is worse than no image sitemap at all. Pages are listed
    only if their .php actually exists, so a parked page is never advertised.
    """
    lines = [
        '<?xml version="1.0" encoding="UTF-8"?>',
        "<!-- GENERATED by tools/build-gallery.py — do not edit. -->",
        '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"',
        '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">',
        "\t<url>",
        f"\t\t<loc>{BASE}/</loc>",
        "\t\t<changefreq>monthly</changefreq>",
        "\t\t<priority>1.0</priority>",
        "\t</url>",
    ]

    if (ROOT / "gallery.php").exists():
        lines += ["\t<url>", f"\t\t<loc>{BASE}/gallery</loc>",
                  "\t\t<changefreq>monthly</changefreq>", "\t\t<priority>0.8</priority>"]
        for m in manifest:
            alt = (m["alt"].replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;"))
            lines += ["\t\t<image:image>",
                      f"\t\t\t<image:loc>{BASE}/media/gallery/{m['file']}.webp</image:loc>",
                      f"\t\t\t<image:title>{alt}</image:title>",
                      "\t\t</image:image>"]
        lines.append("\t</url>")

    if (ROOT / "privacy.php").exists():
        lines += ["\t<url>", f"\t\t<loc>{BASE}/privacy</loc>",
                  "\t\t<changefreq>yearly</changefreq>", "\t\t<priority>0.2</priority>", "\t</url>"]

    lines.append("</urlset>")
    (ROOT / "sitemap.xml").write_text("\n".join(lines) + "\n", encoding="utf-8")


if __name__ == "__main__":
    sys.exit(main())
