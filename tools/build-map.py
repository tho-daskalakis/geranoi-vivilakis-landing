#!/usr/bin/env python3
"""Build the static map image for the contact section.

Why static: OpenStreetMap's iframe embed (openstreetmap.org/export/embed.html)
migrated to MapLibre GL. It now REQUIRES WebGL — so it renders nothing on
machines without it — and pulls ~1.3 MB of JavaScript to show one pin, roughly
three times the weight of the entire rest of the page. Google's embed would
mean cookies and therefore a consent banner on a site that currently sets none.

A committed image sidesteps all of it: no JavaScript, no WebGL, no cookies, no
banner, and no third-party request at runtime, which also means OpenStreetMap
is no longer a recipient of visitor data and drops out of the privacy policy.

Tile usage: this is a one-off build step, not runtime tile fetching, which is
what OSM's tile usage policy is concerned with. It sends an identifying
User-Agent, requests a couple of dozen tiles at most, and caches them locally
so re-runs do not re-fetch. Attribution is baked into the image AND rendered as
real linked text in the page, as ODbL requires.

Style: SATELLITE by default. Four styles were rendered and compared for this
location — OSM standard, CARTO Voyager, CARTO Positron and Esri World Imagery.
The vector styles all render this semi-rural industrial area as near-empty
washes (Positron is almost blank; Voyager loses the ΒΙΟΠΑ Φοινικιάς label),
which reads as a broken map to anyone used to Google. The satellite imagery
shows the actual sheds, yards and olive groves and is what the client's own
Google embed used.

LICENCE NOTE, needs a decision before launch: Esri's World Imagery tiles are
reachable without an API key and are used here with the attribution Esri
requires, but Esri's terms contemplate use through an ArcGIS account, and
permanently caching the imagery is a grey area for a commercial site. Options
if that matters: switch back with `--style osm` (cleanly ODbL-licensed), or buy
imagery rights. Google/Mapbox static APIs are not a way out — both forbid
caching tiles beyond 30 days, so neither can be committed to a repo.

Usage:  python3 tools/build-map.py [--zoom 16] [--style satellite|osm]
"""
import argparse
import math
import sys
import time
import urllib.request
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

ROOT = Path(__file__).resolve().parent.parent
CACHE = ROOT / ".tilecache"

LAT, LON = 35.292582, 25.111259          # the yard
OUT_W, OUT_H = 1200, 680                 # @2x; the @1x is this halved
TILE = 256
UA = "vivilakiscranes.gr static map builder (one-off; contact info@vivilakiscranes.gr)"
FONT_PATHS = [
    "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf",
    "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf",
]


def deg2num(lat: float, lon: float, zoom: int) -> tuple[float, float]:
    """Lat/lon to fractional slippy-map tile coordinates."""
    n = 2.0 ** zoom
    x = (lon + 180.0) / 360.0 * n
    y = (1.0 - math.asinh(math.tan(math.radians(lat))) / math.pi) / 2.0 * n
    return x, y


STYLES = {
    # Esri serves World Imagery as /{z}/{y}/{x} — row before column, unlike OSM.
    "satellite": ("https://server.arcgisonline.com/ArcGIS/rest/services/"
                  "World_Imagery/MapServer/tile/{z}/{y}/{x}",
                  "© Esri, Maxar, Earthstar Geographics"),
    "osm": ("https://tile.openstreetmap.org/{z}/{x}/{y}.png",
            "© OpenStreetMap contributors"),
}


def fetch_tile(style: str, zoom: int, x: int, y: int) -> Image.Image:
    cached = CACHE / f"{style}_{zoom}_{x}_{y}.png"
    if cached.exists():
        return Image.open(cached).convert("RGB")
    CACHE.mkdir(parents=True, exist_ok=True)
    url = STYLES[style][0].format(z=zoom, x=x, y=y)
    req = urllib.request.Request(url, headers={"User-Agent": UA})
    with urllib.request.urlopen(req, timeout=30) as r:
        cached.write_bytes(r.read())
    time.sleep(0.25)          # be a good citizen
    return Image.open(cached).convert("RGB")


def build(style: str, zoom: int) -> Image.Image:
    cx, cy = deg2num(LAT, LON, zoom)
    # Pixel coordinates of the centre in the global tile plane.
    px, py = cx * TILE, cy * TILE
    left, top = px - OUT_W / 2, py - OUT_H / 2

    x0, x1 = math.floor(left / TILE), math.floor((left + OUT_W) / TILE)
    y0, y1 = math.floor(top / TILE), math.floor((top + OUT_H) / TILE)

    n = 2 ** zoom
    canvas = Image.new("RGB", ((x1 - x0 + 1) * TILE, (y1 - y0 + 1) * TILE), "#e8e0d8")
    count = 0
    for tx in range(x0, x1 + 1):
        for ty in range(y0, y1 + 1):
            if not (0 <= ty < n):
                continue
            tile = fetch_tile(style, zoom, tx % n, ty)
            canvas.paste(tile, ((tx - x0) * TILE, (ty - y0) * TILE))
            count += 1
    print(f"  {count} tile(s) at z{zoom}")

    ox, oy = left - x0 * TILE, top - y0 * TILE
    img = canvas.crop((round(ox), round(oy), round(ox) + OUT_W, round(oy) + OUT_H))
    return img


def draw_marker(img: Image.Image) -> None:
    """Amber teardrop pin with a dark outline, centred on the yard."""
    d = ImageDraw.Draw(img, "RGBA")
    cx, cy = OUT_W // 2, OUT_H // 2
    r, tip = 26, 34
    # soft shadow on the ground
    d.ellipse([cx - 16, cy + tip - 8, cx + 16, cy + tip + 6], fill=(0, 0, 0, 70))
    # stem
    d.polygon([(cx - 13, cy + 8), (cx + 13, cy + 8), (cx, cy + tip)],
              fill="#fcac00", outline="#7a5200")
    d.ellipse([cx - r, cy - r, cx + r, cy + r], fill="#fcac00", outline="#7a5200", width=3)
    d.ellipse([cx - 9, cy - 9, cx + 9, cy + 9], fill="#2b635e")


def draw_attribution(img: Image.Image, text: str) -> None:
    """Attribution baked in, so the image is compliant on its own too."""
    font = None
    for p in FONT_PATHS:
        if Path(p).exists():
            font = ImageFont.truetype(p, 17)
            break
    if font is None:
        font = ImageFont.load_default()
    d = ImageDraw.Draw(img, "RGBA")
    box = d.textbbox((0, 0), text, font=font)
    w, h = box[2] - box[0], box[3] - box[1]
    x, y = OUT_W - w - 16, OUT_H - h - 14
    d.rectangle([x - 8, y - 6, x + w + 8, y + h + 8], fill=(255, 255, 255, 205))
    d.text((x, y), text, fill="#333333", font=font)


def main() -> int:
    ap = argparse.ArgumentParser()
    # z17 shows the compound itself; z16 frames an anonymous field.
    ap.add_argument("--zoom", type=int, default=17)
    ap.add_argument("--style", choices=sorted(STYLES), default="satellite")
    args = ap.parse_args()

    print(f"building static map for {LAT}, {LON} ({args.style}, z{args.zoom})")
    img = build(args.style, args.zoom)
    draw_marker(img)
    draw_attribution(img, STYLES[args.style][1])

    img.save(ROOT / "map@2x.webp", "WEBP", quality=82, method=6)
    img.resize((OUT_W // 2, OUT_H // 2), Image.LANCZOS).save(
        ROOT / "map.webp", "WEBP", quality=82, method=6)

    for name in ("map.webp", "map@2x.webp"):
        p = ROOT / name
        print(f"  {name:14} {Image.open(p).size}  {p.stat().st_size // 1024} KB")
    return 0


if __name__ == "__main__":
    sys.exit(main())
