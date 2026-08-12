#!/usr/bin/env python3
"""Verify every internal link and asset reference resolves.

Renders each PHP page, then for each href/src checks that the target exists —
resolving extensionless URLs the way .htaccess does (/foo -> foo.php) and
verifying that #fragments point at an id that is actually on the page.

Ignores anything inside HTML comments, since this repo deliberately parks
not-yet-ready features there.

Usage:  python3 tools/check-links.py
Exit 0 = clean, 1 = broken references found.
"""
import re
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
PAGES = sorted(p.name for p in ROOT.glob("*.php"))

COMMENT = re.compile(r"<!--.*?-->", re.S)
REF = re.compile(r'(?:href|src)="([^"]*)"')
ID = re.compile(r'id="([^"]+)"')
EXTERNAL = re.compile(r"^(https?:|mailto:|tel:|data:|javascript:)", re.I)


def render(page: str) -> str | None:
    r = subprocess.run(["php", page], cwd=ROOT, capture_output=True, text=True)
    if r.returncode != 0:
        print(f"{page}: PHP failed: {r.stderr.strip()}")
        return None
    return r.stdout


def resolve(target: str) -> Path | None:
    """Map a URL path to a file on disk, mirroring .htaccess rewrites."""
    rel = target.lstrip("/")
    if rel in ("", "/"):
        return ROOT / "index.php"
    for cand in (ROOT / rel, ROOT / f"{rel}.php", ROOT / rel / "index.php"):
        if cand.exists():
            return cand
    return None


def main() -> int:
    rendered = {p: render(p) for p in PAGES}
    if any(v is None for v in rendered.values()):
        return 1

    ids = {p: set(ID.findall(html)) for p, html in rendered.items()}
    problems = []

    for page, html in rendered.items():
        live = COMMENT.sub("", html)
        for raw in sorted(set(REF.findall(live))):
            if not raw or EXTERNAL.match(raw):
                continue

            path, _, frag = raw.partition("#")

            if not path:                      # same-page anchor
                if frag and frag not in ids[page]:
                    problems.append(f"{page}: #{frag} -> no element with that id")
                continue

            target = resolve(path)
            if target is None:
                problems.append(f"{page}: {raw} -> no such file")
                continue

            if frag:
                target_html = rendered.get(target.name)
                if target_html is None and target.suffix == ".php":
                    target_html = render(target.name)
                if target_html is not None and frag not in set(ID.findall(target_html)):
                    problems.append(f"{page}: {raw} -> {target.name} has no id={frag}")

    for p in problems:
        print(f"  BROKEN  {p}")

    checked = sum(len(set(REF.findall(COMMENT.sub('', h)))) for h in rendered.values())
    print(f"\n{len(PAGES)} page(s), {checked} reference(s) checked, {len(problems)} broken.")
    return 1 if problems else 0


if __name__ == "__main__":
    sys.exit(main())
