# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A marketing site for **Βιβιλάκης Εμμανουήλ – Γερανοί και Μεταφορές**, a solo crane/heavy-haulage operator based in Heraklion, Crete. Deployed at `https://vivilakiscranes.gr/`, hosted on **pointer.gr** (cPanel, Apache/LiteSpeed, PHP).

Copy and design should stay honest about scale — one crane truck, one aerial lift, a forklift, personnel platforms, a boat trailer. Not "our fleet".

## Commands

There is no build system, package manager, test suite, or linter — and deliberately no bundler. The site is PHP-templated static content.

```bash
php -S localhost:8000          # dev server; must be PHP, the pages use includes
python3 tools/check-links.py   # every internal link/asset resolves
python3 tools/check-homoglyphs.py  # no Greek/Latin mixed-script words
python3 tools/build-gallery.py               # rebuild gallery derivatives + manifest + sitemap
python3 tools/build-gallery.py --sitemap-only  # just rewrite sitemap.xml (seconds, not minutes)
python3 tools/redact-gallery.py              # paint out third-party marks (build-gallery runs this itself)
python3 tools/redact-gallery.py --check      # verify the shipped masters are the redacted ones
bash    tools/build-hero-video.sh            # re-encode media/hero-video.mp4 + poster
python3 tools/build-map.py                   # rebuild map.webp from OSM tiles
python3 tools/svg2png.py <in.svg> <out.png> --height N [--shadow]
php -l <file>                  # syntax check a single file
```

`python3 -m http.server` will **not** work — it cannot execute the PHP includes.

`.htaccess` rewrites (extensionless URLs, HTTPS, compression) are Apache-only and do **not** apply under `php -S`. Extensionless URLs need checking on the real host.

## Architecture

**PHP includes, no build step.** A bundler was considered and rejected: there are no npm dependencies, no framework, ~220 lines of CSS and ~40 lines of vanilla JS — nothing to bundle. The site must stay a folder that can be FTP'd to a host by a non-developer.

```
index.php  gallery.php  privacy.php  404.php    pages
send.php                        contact-form handler; POST target is /send
config.php                      SMTP credentials, gitignored (see config.example.php)
partials/head.php               <head> + opening <body>; takes $page_title,
                                $page_description, $canonical, $json_ld, $robots
partials/header.php             skip-link, header, nav, mobile nav
partials/footer.php             footer, inline <script>, closing tags
styles.css                      the single stylesheet, linked by every page
.htaccess                       rewrites, HTTPS, canonical host, compression, caching
vendor/PHPMailer/               vendored, 3 files only (blocked from web access)
tools/                          build + verification scripts (blocked from web access)
media/hero-video.mp4            hero background video
media/gallery/                  generated WebP derivatives + manifest.php
assets/                         gitignored source media
```

Each page sets its metadata variables, then includes `head.php` → `header.php` → its own `<main>` → `footer.php`.

**Language:** all user-facing copy is Greek (`lang="el"`). An English version at `/en/` is planned but **not published**. Two things are commented out on purpose and must be uncommented *together*: the `hreflang="en"` alternate in `partials/head.php` and the `.lang-btn` in `partials/header.php`.

**Parked features** are commented out with a `PARKED` marker naming the phase that unparks them. All of them have now shipped; the only thing still commented out on purpose is the English `/en/` pair described above.

**JS behaviours** (all in `partials/footer.php`, except the gallery lightbox which lives in `gallery.php`): header transparent→solid past 40px scroll, mobile nav toggle, an `IntersectionObserver` scroll-reveal for `.reveal`, and the hero video injection. Reduced-motion is honoured in both CSS and JS.

**The hero video never blocks first paint.** `<video class="hero-video">` ships with **no `<source>`**. `partials/footer.php` injects one after `window.load`, and only if the visitor is not on `prefers-reduced-motion`, not on `saveData`, and not on a connection reporting `2g`/`3g` — a gated visitor downloads *zero* video bytes. `hero-poster.webp` is frame 1 of the video, so it is the LCP element and the cross-fade is imperceptible.

**There is no logo overlay on the video.** One was built and removed. Its real purpose was to **mask the "SOLD" sticker** on the green container, which becomes readable when the rig turns and the load faces the camera near the end of the clip — it was mistakenly built as a decorative flourish parked at frame centre, which covers nothing. **The sticker is still visible in the shipped clip.** Masking it properly means keyframing a position that tracks it through the frame, or re-cutting to a segment where it never faces the camera. `tools/svg2png.py` (pycairo, handles the `M/L/C/Z` subset these logos use) is kept for whenever that is done.

**The map is click-to-load.** It renders as a static image — `map.webp` + `map@2x.webp`, built by `tools/build-map.py` from OSM raster tiles — and swaps in Google's interactive embed only when the visitor presses the button. This shape is deliberate and load-bearing:

- OSM's own iframe migrated to MapLibre GL, so it **requires WebGL** (it renders nothing without it) and ships ~1.3 MB of JS for one pin. Mapbox GL has the same WebGL requirement. Neither is usable as a default.
- The static preview is **satellite imagery** (Esri World Imagery, `--style satellite`, zoom 17). Four styles were compared: OSM standard, CARTO Voyager and CARTO Positron all render this semi-rural industrial area as near-empty washes, which reads as a broken map to anyone used to Google. Satellite shows the actual compound. ⚠️ **Licence needs a decision before launch** — Esri's tiles are reachable without a key and are attributed as required, but their terms contemplate an ArcGIS account and permanent caching is a grey area for commercial use. `--style osm` is the cleanly-licensed fallback. Google/Mapbox static APIs are not an escape: both forbid caching tiles past 30 days, so neither can be committed.
- Google's embed sets cookies, so loading it automatically would force a consent banner onto a site that otherwise sets **zero** cookies. Making the visitor press a button turns the load into their own affirmative act, which is the consent — so no banner is needed and anyone who never clicks is never exposed to Google.
- The choice is **not** persisted, so no cookie or storage is written either way. Do not "improve" this by remembering it.

ODbL attribution for the static tiles is required and appears twice — baked into the image and as linked text. Keep both. `privacy.php` §5 and §6 describe this exact behaviour; if the map ever loads Google automatically, both sections become wrong and a banner becomes mandatory.

**Theming** is CSS custom properties on `:root` in `styles.css`. `--primary` (teal `#2b635e`) is the header/badge/submit colour, `--secondary` (amber `#fcac00`) the CTA buttons. The header exposes `--hfg`, flipping its own foreground between transparent and `.scrolled` states.

**Logo:** `logo.svg` (black body + amber inlays) and `logo-white.svg` (white body + amber) are a real vector pair, swapped by CSS — `.logo-light` (white, for the transparent header over the hero and the dark footer) and `.logo-dark` (black, for the scrolled teal header and the light intro section). `logo.png` is kept **only** as the raster for schema.org `logo` and social crawlers; do not reference it from markup.

The vector was rebuilt from an auto-trace whose background was baked in as two full-canvas plates. The original trace is preserved at `assets/logo-source-traced.svg` (gitignored). Its amber is `#f1b000` — the logo's own colour, deliberately *not* the `--secondary` UI token.

**Breakpoints:** desktop nav appears at `min-width:1120px`; below that the hamburger `.mobile-nav` is used. Services grid goes 1→2 cols at 680px, →3 at 1024px.

## Content that must be kept in sync

- **Phone `+306949776292` / `6949 776 292`** — header call chip, mobile nav, contact info card, footer, 404 page, and JSON-LD `telephone`.
- **Services** — `.services-grid`, the header dropdown, the footer column, and `makesOffer` in the JSON-LD.
- **Address, hours, service areas** — contact card / footer text and the JSON-LD `address`, `openingHoursSpecification`, `areaServed`.

**The FAQ is no longer a sync hazard.** It is defined once as the `$faq` array at the top of `index.php` and rendered twice from that array — as the visible `<details>` list and as `FAQPage` JSON-LD. Edit the array; never edit the rendered output.

**Three FAQ entries is correct — do not "fix" it to four.** A fourth question ("Πόσο σύντομα μπορείτε να ανταποκριθείτε σε επείγουσα ανάγκη;") once existed in the JSON-LD only, and was removed from the visible page **deliberately**. Reading that 4-vs-3 mismatch as drift and re-adding the question to the page is a mistake that has already been made once. If it is ever wanted back, it goes in the `$faq` array so both renderings get it together.

**Neither is the gallery.** `tools/gallery-alt.json` is the only hand-authored input — it sets both the order and the Greek alt text. `tools/build-gallery.py` reads it and generates `media/gallery/manifest.php`, which `gallery.php` renders twice (grid + `ImageObject` schema), and `sitemap.xml`. Never edit the manifest or the sitemap by hand. A source with no alt-text entry is refused, not shipped with an empty `alt`.

**`tools/gallery-redactions.json` is the second hand-authored gallery input.** It lists regions that must be painted out of the shipped derivatives — another company's signage, someone else's watermark. `tools/build-gallery.py` runs `tools/redact-gallery.py` itself at the end of a build, because a rebuild otherwise republishes marks that were removed on purpose.

Two things about it are easy to get wrong:

- **Coordinates are in the master derivative's pixel space** (long edge 1600), *not* the source photo's. The g22 source is no longer in `assets/`, so the derivative is the only copy left to measure against.
- **`tools/gallery-redactions.lock.json` is generated** — slug → digest of the redacted master. It exists because blurring an already-blurred region blurs it *further*; without the lock, repeated runs would slowly spread the soft patch. Do not hand-edit it, and do not "simplify" it away.

Masks feather **outward only** (`ImageChops.lighter(hard_rect, gaussian(hard_rect))`). A plain blurred-rectangle mask also ramps *inward*, leaving ~20% of the sharp original showing through at the box edges — enough that the first version of this left the watermark legible as a ghost. `--check` reports a `ghost` figure in units of the region's own noise; anything at or above 0.5σ means the mark is still traceable by eye.

## Assets

Files referenced by the page live in the repo root and are committed. `assets/` is **gitignored** — it is the raw client media library (22 source images ~68 MB, 4 `.mov` videos ~213 MB, the traced logo source). Workflow: pick a source, crop/optimize, save to the repo root or `media/`, reference it.

Gallery images go through `tools/build-gallery.py`; everything else must be **compressed before committing**. `worksite.jpeg` was 2.9 MB at 3024×4032 for a slot ~544 px wide; it is now 268 KB. Every `<img>` needs explicit `width`/`height` to prevent layout shift.

**⚠️ `assets/images/` has drifted out of sync with `tools/gallery-alt.json` — do not run `build-gallery.py`.** The local `image00001`–`image00013` are a *different batch* from the ones the shipped gallery was built from; `image00083`–`image00092` still match. Verified by thumbnail comparison against the shipped derivatives: the 830s–920s match their slugs within a distance of ~4k–20k, the 1–13 range is all ~100k+ off, and their subjects match the wrong alt text (local `image00012.jpeg` is the scaffolded-building shot that the JSON assigns to `g11` via `image00005.jpeg`). A rebuild today would scramble 12 of the 22 entries and pair every one of them with a wrong Greek alt. The g22 source is gone from the library entirely. **Re-map the JSON against the real files, or restore the original library, before the build script is ever run again.**

**The source media leaks location.** 21 of the 22 photos and the `.mov` files carry GPS EXIF, and 11 photos carry EXIF orientation 6. Both pipelines strip metadata deliberately — `-map_metadata -1` for ffmpeg, a cleared `info` dict for PIL — and both apply the rotation first. If you ever hand-process a source, do the same, or you will publish the client's location and half the images sideways.

**Hero video encode** (`assets/videos/IMG_2065.mov` → `media/hero-video.mp4`): the stored frame is 3840×2160 landscape with `rotation:-90` side data, so it *displays* portrait and ffmpeg applies the rotation before your filters. The chain is `crop=2160:1215:0:950,hqdn3d=2:1:2:3,fps=24,scale=1920:1080` at CRF 33, segment `-ss 17 -t 13`. The crop keeps full width and 32% of height because the composition is top-weighted and the rest is empty gravel. **MP4 only** — VP9 was measured within 4% of H.264 on this footage, which does not pay for a second file.

## Known placeholders / unfinished items

Do not treat these as bugs to silently "fix"; they are pending real content or confirmation:

- **Three unverified claims** in the copy: "50 years" family tradition, 32 m platform height, 7 t forklift capacity. None confirmed. An inflated lifting capacity carries liability beyond marketing — confirm before launch.
- **`config.php` does not exist yet.** Without it the form validates correctly but always returns `?sent=error` and logs `config.php missing`. Copy `config.example.php` and fill in the pointer.gr SMTP credentials.
- **`privacy.php` states a 24-month retention period** and omits ΑΦΜ / ΓΕΜΗ. Both are defaults, not client-supplied facts — see the file header.
- **`media/gallery/g22.webp` is still a third-party photo.** Its TikTok watermark (`@katerinakamnaki`) and the orange sign advertising `ΑΦΟΙ ΒΙΒΙΛΑΚΗ Ο.Ε.` — the father's company, carrying a phone number that is *not* this business's — are now painted out via `tools/gallery-redactions.json`. Removing the credit mark does not grant the rights: **confirm permission to use the photo, or drop the entry from `tools/gallery-alt.json`.**
- ΑΦΜ / ΓΕΜΗ are absent from the footer; not supplied.
- No real browser QA has ever been done on this project — no Lighthouse run, no responsive pass.

## Conventions

Indentation is tabs at the HTML/PHP structural level, spaces inside `styles.css`. Match whatever surrounds the line you are editing.

**Greek homoglyphs are a real hazard here.** The `<title>` shipped for months reading `Γερανoί` with a Latin `o` (U+006F), making the site's primary keyword unmatchable. Run `tools/check-homoglyphs.py` after touching Greek text.
