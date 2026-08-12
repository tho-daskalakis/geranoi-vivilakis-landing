#!/usr/bin/env python3
"""Rasterise logo.svg / logo-white.svg to a transparent PNG.

Needed because the hero logo animation is composited into the video by ffmpeg
(see tools/build-hero-video.sh), and ffmpeg cannot read SVG. No SVG rasteriser
is installed on this machine — no rsvg-convert, inkscape, ImageMagick or
cairosvg — and the project deliberately avoids adding dependencies, so this
renders the paths directly with pycairo, which is already present.

DELIBERATELY NOT A GENERAL SVG RENDERER. It handles exactly the subset the two
logo files use:
  * a single <svg> with a viewBox
  * <path> elements with a solid `fill` and absolute M / L / C / Z commands
  * nonzero fill rule (cairo's default, and the SVG default)
Anything else — transforms, gradients, strokes, relative commands, arcs — is
ignored or will raise. If the logo is ever redrawn in a tool that emits richer
SVG, replace this rather than extending it.

Usage:
  python3 tools/svg2png.py logo-white.svg out.png --height 200 [--shadow]
"""
import argparse
import math
import re
import sys
import xml.etree.ElementTree as ET
from pathlib import Path

import cairo
import numpy as np
from PIL import Image, ImageFilter

SVG_NS = "{http://www.w3.org/2000/svg}"
TOKEN = re.compile(r"([MLCZmlczHhVv])|(-?\d*\.?\d+(?:[eE][-+]?\d+)?)")
ARITY = {"M": 2, "L": 2, "C": 6, "Z": 0}


def parse_path(d: str):
    """Yield (command, [numbers]) honouring SVG implicit command repetition."""
    tokens = [(c or n) for c, n in TOKEN.findall(d)]
    i, cmd, out = 0, None, []
    while i < len(tokens):
        t = tokens[i]
        if t.isalpha():
            if t.upper() not in ARITY:
                raise ValueError(f"unsupported path command {t!r}")
            if t.islower() and t.upper() != "Z":
                raise ValueError(f"relative command {t!r} is not supported")
            cmd = t.upper()
            i += 1
            if cmd == "Z":
                out.append(("Z", []))
                cmd = "M"      # per spec, a command after Z restarts a subpath
                continue
        if cmd is None:
            raise ValueError("path data does not start with a command")
        n = ARITY[cmd]
        args = [float(x) for x in tokens[i:i + n]]
        if len(args) < n:
            break
        out.append((cmd, args))
        i += n
        # Repeated coordinates after M are implicit L, per the SVG spec.
        if cmd == "M":
            cmd = "L"
    return out


def hex_rgb(value: str):
    v = value.strip().lstrip("#")
    if len(v) == 3:
        v = "".join(ch * 2 for ch in v)
    return tuple(int(v[i:i + 2], 16) / 255 for i in (0, 2, 4))


def render(svg_file: Path, height: int, pad: int):
    root = ET.parse(svg_file).getroot()
    vb = [float(x) for x in root.get("viewBox").split()]
    vw, vh = vb[2], vb[3]
    scale = height / vh
    w = math.ceil(vw * scale)
    h = math.ceil(vh * scale)

    surface = cairo.ImageSurface(cairo.FORMAT_ARGB32, w + pad * 2, h + pad * 2)
    ctx = cairo.Context(surface)
    ctx.translate(pad, pad)
    ctx.scale(scale, scale)
    ctx.translate(-vb[0], -vb[1])

    for path in root.iter(f"{SVG_NS}path"):
        fill = path.get("fill", "#000000")
        if fill.lower() == "none":
            continue
        ctx.set_source_rgb(*hex_rgb(fill))
        ctx.new_path()
        for cmd, args in parse_path(path.get("d", "")):
            if cmd == "M":
                ctx.move_to(*args)
            elif cmd == "L":
                ctx.line_to(*args)
            elif cmd == "C":
                ctx.curve_to(*args)
            elif cmd == "Z":
                ctx.close_path()
        ctx.fill()

    surface.flush()
    # cairo ARGB32 is premultiplied BGRA in native byte order.
    buf = np.ndarray((surface.get_height(), surface.get_stride() // 4, 4),
                     dtype=np.uint8, buffer=surface.get_data())
    buf = buf[:, :surface.get_width(), :]
    b, g, r, a = (buf[:, :, i].astype(np.float32) for i in range(4))
    alpha = np.maximum(a, 1e-6)
    rgba = np.dstack([
        np.clip(r / alpha * 255, 0, 255),
        np.clip(g / alpha * 255, 0, 255),
        np.clip(b / alpha * 255, 0, 255),
        a,
    ]).astype(np.uint8)
    return Image.fromarray(rgba, "RGBA")


def add_shadow(img: Image.Image, blur: float, opacity: float, dy: int) -> Image.Image:
    """Soft dark shadow beneath the mark.

    The hero footage pans a white boat hull straight through where the logo
    sits, so a plain white mark can vanish into it. This is the ffmpeg-side
    equivalent of the drop-shadow the CSS version carried.
    """
    shadow = Image.new("RGBA", img.size, (0, 0, 0, 0))
    mask = img.getchannel("A").filter(ImageFilter.GaussianBlur(blur))
    mask = mask.point(lambda v: int(v * opacity))
    shadow.putalpha(mask)
    offset = Image.new("RGBA", img.size, (0, 0, 0, 0))
    offset.paste(shadow, (0, dy))
    return Image.alpha_composite(offset, img)


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument("svg", type=Path)
    ap.add_argument("out", type=Path)
    ap.add_argument("--height", type=int, required=True)
    ap.add_argument("--shadow", action="store_true")
    ap.add_argument("--blur", type=float, default=9.0)
    ap.add_argument("--opacity", type=float, default=0.55)
    args = ap.parse_args()

    pad = int(args.blur * 4) if args.shadow else 0
    img = render(args.svg, args.height, pad)
    if args.shadow:
        img = add_shadow(img, args.blur, args.opacity, dy=max(2, int(args.blur / 3)))

    img.save(args.out)
    print(f"{args.svg} -> {args.out}  {img.width}x{img.height}"
          f"{' (with shadow)' if args.shadow else ''}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
