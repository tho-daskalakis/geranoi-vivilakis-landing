#!/usr/bin/env python3
"""Paint out third-party marks in already-built gallery derivatives.

Some source photos carry things that must not be published — another company's
signage, a phone number that is not this business's, a watermark belonging to
whoever shot the picture. `tools/gallery-redactions.json` is the hand-authored
list of those regions; this script applies them to `media/gallery/<slug>.webp`
and regenerates that slug's `-800` and `-thumb` tiers from the redacted master.

Coordinates are in the *master derivative's* pixel space (long edge 1600), not
the source photo's, because for at least one entry the source no longer exists
in `assets/` and the derivative is the only copy left.

Two modes, and the difference between them matters:

  blur   Gaussian defocus. For a mark that is part of the scene and cannot be
         convincingly removed — the sign is bolted to the cabin, so it stays
         there as an out-of-focus orange plate rather than a hole in the wall.

  patch  Copy clean pixels from `from: [dx, dy]` away, colour-match them to the
         ring around the target, and composite. For a mark laid *over* flat
         texture: the watermark sits on asphalt, so asphalt from further down
         the same road makes it look like nothing was ever there. A blur here
         would leave an obvious soft smudge in the middle of a sharp road.

Both composite through a mask that is opaque over the box and feathers
**outward only**. That is load-bearing, not a detail: a plain blurred rectangle
mask ramps *inward*, leaving ~20% of the sharp original showing through at the
box edges — enough to keep white-on-grey text legible. The first version of
this script did exactly that and the watermark was still readable as a ghost.

`media/gallery/redactions.lock.json` records the digest of each redacted
master so the pass knows what it has already done. That is not bookkeeping for
its own sake: blurring an already-blurred region blurs it *further*, so running
this twice without the lock would slowly spread the soft patch. A rebuild
changes the master's digest, which is exactly the signal to redact it again.

Usage:  python3 tools/redact-gallery.py [--check]
        --check writes nothing and exits 1 if any master on disk is not the
        redacted one — the guard to put in front of a deploy.
Exit 0 = clean, 1 = a target is missing, unredacted, or a mark survived.
"""
import hashlib
import importlib.util
import json
import sys
from pathlib import Path

import numpy as np
from PIL import Image, ImageChops, ImageDraw, ImageFilter

ROOT = Path(__file__).resolve().parent.parent
OUT = ROOT / "media" / "gallery"
SPEC = ROOT / "tools" / "gallery-redactions.json"
# GENERATED — slug -> digest of the redacted master. Lives in tools/ rather
# than media/gallery/ because .htaccess blocks tools/ from the web and this
# is build state, not an asset the site serves.
LOCK = ROOT / "tools" / "gallery-redactions.lock.json"

# The master is re-encoded from pixels that are already once-lossy, so it is
# saved a little above the 80 the build uses to keep generation loss invisible.
# The smaller tiers are downscaled from the in-memory result, not from this
# file, so they lose nothing extra.
MASTER_QUALITY = 88

# How much of the box counts as "the mark" when measuring whether it survived:
# the most locally-contrasty tenth of the pixels, which is where text lives
# whether it is white-on-asphalt or dark-blue-on-orange.
MARK_PERCENTILE = 90


def _build_gallery():
    """Import the sibling build-gallery.py for its tier table and encoders.

    Imported lazily and by path because the filename has a hyphen, and because
    build-gallery.py imports *this* module at the end of its own build — doing
    it lazily on both sides keeps that from being a circular import.
    """
    if "build_gallery" in sys.modules:
        return sys.modules["build_gallery"]
    spec = importlib.util.spec_from_file_location(
        "build_gallery", Path(__file__).with_name("build-gallery.py"))
    mod = importlib.util.module_from_spec(spec)
    sys.modules["build_gallery"] = mod
    spec.loader.exec_module(mod)
    return mod


def outward_mask(size: tuple[int, int], box: list[int], feather: int) -> Image.Image:
    """Fully opaque over `box`, fading out over `feather` px beyond it.

    `lighter()` against the hard rectangle is what makes the feather one-sided.
    A bare GaussianBlur of the rectangle also fades *inward*, which lets the
    original bleed back through exactly where the mark is.
    """
    m = Image.new("L", size, 0)
    ImageDraw.Draw(m).rectangle(box, fill=255)
    return ImageChops.lighter(m, m.filter(ImageFilter.GaussianBlur(feather)))


def ring_mean(im: Image.Image, box: list[int], pad: int = 20) -> np.ndarray:
    """Mean RGB of the frame of pixels just outside `box`."""
    a = np.asarray(im.crop((box[0] - pad, box[1] - pad, box[2] + pad, box[3] + pad)), float)
    inner = np.zeros(a.shape[:2], bool)
    inner[pad:-pad, pad:-pad] = True
    return a[~inner].reshape(-1, 3).mean(0)


def apply_blur(im: Image.Image, r: dict) -> Image.Image:
    blurred = im.filter(ImageFilter.GaussianBlur(r.get("sigma", 16)))
    return Image.composite(blurred, im, outward_mask(im.size, r["box"], r.get("feather", 10)))


def apply_patch(im: Image.Image, r: dict) -> Image.Image:
    box, (dx, dy), feather = r["box"], r["from"], r.get("feather", 10)
    # The donor has to cover the feathered edge too, not just the box.
    pad = 4 * feather
    outer = (max(0, box[0] - pad), max(0, box[1] - pad),
             min(im.width, box[2] + pad), min(im.height, box[3] + pad))
    donor = im.crop((outer[0] + dx, outer[1] + dy, outer[2] + dx, outer[3] + dy))
    # Lift the donor onto the target's own brightness — asphalt is not uniform
    # across a photo, and an unmatched patch reads as a pasted rectangle even
    # when its texture is perfect.
    shifted = np.asarray(donor, float) + (
        ring_mean(im, box) - ring_mean(im, [box[0] + dx, box[1] + dy, box[2] + dx, box[3] + dy]))
    donor = Image.fromarray(np.clip(shifted, 0, 255).astype("uint8"))
    layer = im.copy()
    layer.paste(donor, (outer[0], outer[1]))
    return Image.composite(layer, im, outward_mask(im.size, box, feather))


MODES = {"blur": apply_blur, "patch": apply_patch}


def measure(before: Image.Image, after: Image.Image, box: list[int]) -> tuple[float, float]:
    """How much of the mark is left, as (detail_kept, ghost_sigmas).

    detail_kept  after/before ratio of local contrast inside the box. Text is
                 high-frequency; if this is still near 1.0 nothing happened.
    ghost_sigmas how far the pixels that *were* the mark now sit from their
                 neighbours, in units of the region's own noise. Above ~0.5 the
                 mark is still faintly traceable by eye.
    """
    b = np.asarray(before.convert("L"), float)[box[1]:box[3], box[0]:box[2]]
    a = np.asarray(after.convert("L"), float)[box[1]:box[3], box[0]:box[2]]
    detail = lambda x: np.abs(np.diff(x, axis=0)).mean() + np.abs(np.diff(x, axis=1)).mean()
    d_before = detail(b)
    kept = detail(a) / d_before if d_before else 0.0

    contrast = np.abs(b - np.asarray(before.convert("L").filter(
        ImageFilter.GaussianBlur(6)), float)[box[1]:box[3], box[0]:box[2]])
    mark = contrast >= np.percentile(contrast, MARK_PERCENTILE)
    ghost = abs(a[mark].mean() - a[~mark].mean()) / (a.std() or 1.0)
    return kept, ghost


def digest(path: Path) -> str:
    return hashlib.sha256(path.read_bytes()).hexdigest()


def apply_all(check_only: bool = False) -> int:
    if not SPEC.exists():
        print(f"missing {SPEC.relative_to(ROOT)} — nothing to redact", file=sys.stderr)
        return 1

    bg = _build_gallery()
    entries = json.loads(SPEC.read_text(encoding="utf-8"))
    lock = json.loads(LOCK.read_text(encoding="utf-8")) if LOCK.exists() else {}
    problems, touched = [], False

    for entry in entries:
        slug = entry["slug"]
        master = OUT / f"{slug}.webp"
        if not master.exists():
            problems.append(f"{slug}: no {master.relative_to(ROOT)} to redact")
            continue

        if lock.get(slug) == digest(master):
            print(f"  {slug}  already redacted, unchanged since the last pass")
            continue
        if check_only:
            problems.append(f"{slug}: master on disk is not the redacted one")
            continue

        before = Image.open(master).convert("RGB")
        im = before
        print(f"  {slug}  {entry.get('note', '')[:60]}")

        for r in entry["regions"]:
            mode = MODES.get(r["mode"])
            if mode is None:
                problems.append(f"{slug}: unknown mode {r['mode']!r}")
                continue
            im = mode(im, r)
            kept, ghost = measure(before, im, r["box"])
            flag = "" if (kept < 0.5 and ghost < 0.5) else "   <-- MARK MAY SURVIVE"
            print(f"      {r['mode']:6} {str(r['box']):26} "
                  f"detail kept {kept:5.1%}  ghost {ghost:4.2f}σ  {r.get('what', '')}{flag}")
            if flag:
                problems.append(f"{slug}: {r.get('what', r['mode'])} still detectable")

        bg.save_webp(im.copy(), master, MASTER_QUALITY)
        for suffix, edge, quality in bg.TIERS:
            if not suffix:
                continue          # the master is written above, at its own quality
            bg.save_webp(bg.resize(im, edge), OUT / f"{slug}{suffix}.webp", quality)
        lock[slug] = digest(master)
        touched = True
        print(f"      written: {slug}.webp + "
              + " ".join(f"{slug}{s}.webp" for s, _, _ in bg.TIERS if s))

    if touched:
        LOCK.write_text(json.dumps(dict(sorted(lock.items())), indent="\t") + "\n",
                        encoding="utf-8")

    for p in problems:
        print(f"  PROBLEM  {p}")
    return 1 if problems else 0


if __name__ == "__main__":
    sys.exit(apply_all(check_only="--check" in sys.argv))
