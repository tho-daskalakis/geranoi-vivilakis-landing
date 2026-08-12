#!/usr/bin/env python3
"""Fail on words that mix Greek and Latin letters.

Greek text on this site is riddled with characters that have identical-looking
Latin twins (ο/o, α/a, ε/e, ρ/p, υ/y, χ/x, ν/v, κ/k, Α/A, Ε/E, Ο/O ...). A
single wrong codepoint is invisible to a human reviewer but makes the word
unmatchable to a search engine.

This is not hypothetical: the <title> shipped for months reading "Γερανoί"
with a Latin U+006F, so the site's primary keyword matched no Greek query.

Usage:  python3 tools/check-homoglyphs.py [paths...]
Exit 0 = clean, 1 = mixed-script words found.
"""
import re
import sys
import unicodedata
from pathlib import Path

# Documentation is excluded: CLAUDE.md deliberately quotes the original
# "Γερανoί" bug as an example, and docs are never served to users.
EXTS = {".php", ".html", ".css", ".js", ".xml", ".txt"}
SKIP_DIRS = {".git", "assets", "media", "vendor", "node_modules", "tools"}

WORD = re.compile(r"[A-Za-zͰ-Ͽἀ-῿]{2,}")

# Backslash escapes glue a Latin letter onto the next word: "\nΜήνυμα" in a
# double-quoted PHP string is a newline followed by Greek, not a mixed word.
# Blanked out (not deleted) so reported line numbers stay accurate.
ESCAPE = re.compile(r"\\.", re.S)


def script_of(ch: str) -> str | None:
    name = unicodedata.name(ch, "")
    if "GREEK" in name:
        return "Greek"
    if "LATIN" in name:
        return "Latin"
    return None


def scan(path: Path) -> list[tuple[int, str, str]]:
    """Return [(line_no, word, detail), ...] for mixed-script words."""
    try:
        text = path.read_text(encoding="utf-8")
    except (UnicodeDecodeError, OSError):
        return []

    text = ESCAPE.sub(lambda m: " " * len(m.group(0)), text)

    hits = []
    for m in WORD.finditer(text):
        word = m.group(0)
        scripts = {s for s in (script_of(c) for c in word) if s}
        if len(scripts) < 2:
            continue
        line = text.count("\n", 0, m.start()) + 1
        odd = [
            f"{c!r} U+{ord(c):04X} {unicodedata.name(c, '?')}"
            for c in word
            # report the minority script's characters
            if script_of(c) == min(scripts, key=lambda s: sum(script_of(c2) == s for c2 in word))
        ]
        hits.append((line, word, "; ".join(odd)))
    return hits


def iter_files(roots: list[str]):
    for root in roots:
        p = Path(root)
        if p.is_file():
            yield p
            continue
        for f in p.rglob("*"):
            if f.is_file() and f.suffix in EXTS and not (SKIP_DIRS & set(f.parts)):
                yield f


def main() -> int:
    roots = sys.argv[1:] or ["."]
    total = 0
    for f in sorted(iter_files(roots)):
        for line, word, detail in scan(f):
            total += 1
            print(f"{f}:{line}: mixed-script word {word!r} -> {detail}")

    if total:
        print(f"\n{total} mixed Greek/Latin word(s) found.", file=sys.stderr)
        return 1
    print("No mixed Greek/Latin words found.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
