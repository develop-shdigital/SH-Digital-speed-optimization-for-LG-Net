# SH Digital speed optimization for LGnet

Tools for making the LGnet site (WordPress + Elementor, staging address
https://lgnet-wordpress.shdigital.ch/) fast: a 95+ PageSpeed score on mobile and desktop and
passing Core Web Vitals, without changing how the site looks or works. Built from the
[SH Digital speed optimization](https://github.com/develop-shdigital/SH-Digital-speed-optimization)
project (SHD Performance 1.5.2, its lab and its handoff), adapted to this site.

| Folder | What it is |
| --- | --- |
| `shd-performance/` | The WordPress plugin. Install it on the site. |
| `tools/build.sh` | Minifies the plugin's front-end scripts (terser 5.51.2) and builds `dist/shd-performance-<version>.zip`. |
| `tests/optimize-fixtures.php` | Runs the optimizer over saved pages and checks that nothing visible or functional was lost. |
| `tools/perf-lab/` | Measurement and test lab: PageSpeed results, Chrome DevTools audits, a local replay of the live site, Lighthouse runs, a local WordPress. See its README. |
| `.claude/skills/web-perf/` | The Chrome DevTools performance skill (falls back to `tools/perf-lab/devtools/audit.mjs` when the MCP server is not connected). |
| `.mcp.json` | The Chrome DevTools MCP server for the skill, pinned to 1.12.0, headless. |

## What slowed the site down (November 2026)

Measured with Lighthouse 13.6 (the engine of PageSpeed Insights) on a local replay of the
live pages, with curl for the server, and in a real browser for layout shifts.

- **The cookie banner was the largest element of most pages.** Cookiez waits for the window
  `load` event (every image, font and script), then downloads its texts and draws a banner
  over half of a phone screen. Its text was the Largest Contentful Paint on /mobile/, /tv/,
  /internet/, /telefonie/, /wechseln/ and /formulare/ on phones (3.2–4.1 s) and on most pages on
  computers. Lighthouse counts an element drawn by JavaScript as loaded only after every script
  that ran before it, so the banner can only be fast when the other scripts wait.
- **No page cache:** 0.8–1.8 s until the first byte of a page (3.1 s once for /en/mobile/).
- **The homepage hero jumped on phones.** The LGnet Hero Slider measures the floating header
  only after the page has been read, and Elementor starts it a second time (which first removes
  the measured space): three jumps of 72 px, layout shift up to 0.26 when the first paint comes
  early. This was hidden in the original lab runs only because the page painted late.
- **Images had no reserved space.** `img { aspect-ratio: unset !important; }` in Elementor's
  Site Settings → Custom CSS switches off the placeholder browsers take from width and height
  (layout shift 0.21 on /mobile-abo-vergleich/ on computers). It probably compensates for widget
  CSS written as `{selector} img { … }` on /mobile/ and /mobile-abo-vergleich/, which applies to
  every image and paragraph of those pages.
- **SH Motion faded in what visitors had already seen:** the header and the first screen were
  painted, hidden when the page had been read and faded in over 0.7 s.
- **17–19 stylesheets per page** plus 100–235 KB of CSS that Elementor writes into each page;
  /tv/ also loads WordPress's block library (141 KB) without showing a block.
- **Third parties in the critical path:** Ally's accessibility widget is a blocking script from
  cdn.elementor.com at the end of every page; HTML widgets on /mobile/, /tv/ and
  /mobile-abo-vergleich/ load Work Sans from fonts.googleapis.com in the middle of the page.
- **Images:** the PNG photo of “Telefonanbieter Schweiz” is 1.9 MB (436 KB at 768 px) and is
  also loaded by the post cards on /ratgeber/ and under the articles.
- **No browser cache lifetimes** for any static file (no Cache-Control, no Expires).
- jQuery and jQuery Migrate block rendering in `<head>`; about 120 ms of jQuery Migrate per page
  on phones.

## The plugin

SHD Performance rewrites each finished page after Elementor and the other plugins have built
it, then stores it in its own page cache. It never edits Elementor data, content or other
plugins' settings. Deactivating it restores the original site.

Every feature is **Off**, **Test** (only for administrators in the Test view, or with the secret
preview link) or **Live** (every visitor).

### What it fixes automatically

| Problem found on LGnet | What the plugin does | Default |
| --- | --- | --- |
| Cookiez draws the banner only after every image, font and script. | Its language file is downloaded with the page (for visitors without a stored choice). Together with the deferred or delayed scripts, the banner script runs as soon as the page has been read, before the other scripts. | Live (starts earlier with the Test features) |
| Ally's widget blocks the page from cdn.elementor.com. | Loaded async; preconnect to its server. | Live |
| Work Sans from fonts.googleapis.com in HTML widgets. | Stylesheet and WOFF2 files copied to the site once (in the background) and served from there; Google's preconnects are left out. No visitor contacts Google. | Live |
| The hero slider jumps by 72 px on phones (up to three times). | The header space is measured the slider's way where the hero starts, before it is painted, and kept while Elementor starts the slider again. | Live |
| `img { aspect-ratio: unset !important }` removes every image's placeholder. | Images with width and height get `aspect-ratio: auto W / H` back while loading; loaded images are sized exactly as before. Only on pages where the only `!important` aspect-ratio rules are such resets. | Live |
| SH Motion hides and fades in the first screen. | A small script right before SH Motion gives what is on screen at that moment SH Motion's own opt-out (`data-fx="none"`); everything further down fades in as designed, on each device. The off-canvas menu keeps its animations. | Live |
| 17–19 stylesheets plus Elementor's inline CSS. | Joined in the same order into one cached file per page. | Live |
| WordPress's block library on pages without blocks (/tv/). | Left out when the page has no block markup at all. | Live |
| Archivo and Public Sans swap in with a different width. | Size-matched Arial fallbacks (measured from the fonts), also for Work Sans; fonts are detected by themselves. Font preloads are off: they delayed the main image on phones. | Live |
| The header logo competes with the main image (twice per page). | The main image per device gets the priority; first-section backgrounds are preloaded. | Live |
| Post-card backgrounds and lower sections load with the page. | They load when they come near the screen. | Live |
| Links with only an icon have no name; the logo's alt text is “logo-mark-dark”. | Names in the page's language (DE, EN, FR, IT); the logo becomes “LGnet”; list roles and heading levels. | Live |
| The table of contents of the articles grows after loading. | Filled in from the start. | Live |
| No browser cache lifetimes. | `.htaccess` (mod_expires): CSS, JS and fonts a year, images and videos 30 days; checked after writing, removed again if the site does not answer. | Live |
| No real-visitor data. | Cookieless Core Web Vitals measurement with a 75th-percentile dashboard. | Live |
| JavaScript blocks rendering; jQuery Migrate. | Defer all scripts, or start them on the first interaction (taps and form submits are replayed), and remove jQuery Migrate. | **Test** |

Ready for later additions, without effect today: tracking scripts start after the first
interaction, YouTube click-to-play, reCAPTCHA on demand, lazy iframes and autoplay videos.

### Page cache

Each finished page is stored in `wp-content/cache/shd-performance/pages/` (plain and gzipped)
and sent by Apache through rules at the top of `.htaccess`, without PHP (8 ms in the local test
on Apache 2.4.58), or by `wp-content/advanced-cache.php` elsewhere. Language versions (/en/,
/fr/, /it/ from SHD Translator) are found in the stored pages (hreflang) and refilled in the
background like the German pages. Logged-in users, form submissions, searches, 404 pages and
addresses with parameters always get a fresh page. Everything else works as in the
[original project](https://github.com/develop-shdigital/SH-Digital-speed-optimization#page-cache).

### Dashboard

*SHD Performance* in the admin menu: Overview (status, Test view, preview link), **Checklist**
(the steps that need a person, with automatic status), Optimizations, Page cache, Images (WebP
replacement everywhere at once), Fonts, Web Vitals and Tools (health checks, page scan, build-time
measurement, settings export).

The checklist for LGnet covers: checking the Test view and switching the Test features to Live
(the largest remaining gain), the page cache, server build time, browser cache lifetimes, the
custom CSS written as `{selector} …` and the Site Settings rule, the hero slider (with the
permanent fix for its developers), the 1.9 MB PNG and other heavy images, smaller image sizes in
post cards, the Work Sans font (optional), Elementor's inline SVG icons, the cookie banner,
tracking when it is added, accessibility, the launch on the final domain (noindex, other
addresses, Hostpoint redirects) and verification in PageSpeed Insights and with real visitors.

### Install

1. Build the zip with `tools/build.sh` (or use the one sent with the release).
2. WordPress → Plugins → Add New → Upload Plugin → choose the zip → Install → Activate. For an
   update: “Replace current with uploaded”. The page cache clears itself.
3. SHD Performance → Page cache: “Active”, sent by the web server. Every page is cached in the
   background within a few minutes.
4. Open the Checklist and follow it. First: check the Test view (admin bar → “Switch to Test
   view”, on a phone and a computer), then “Switch all Test features to Live”.

### Undo

- To undo one change, set its feature to Off. The page cache is cleared and refilled on save.
- To compare with the original, open any page with `?shd-off=1` as an administrator.
- To undo everything, deactivate the plugin: it removes its `.htaccess` blocks,
  `advanced-cache.php`, the stored pages and its `WP_CACHE` line (checked on Apache).
- If the admin is not reachable, add `define( 'SHD_PERFORMANCE_DISABLE', true );` to
  `wp-config.php`; to stop stored pages at once, delete `wp-content/cache/shd-performance/pages`.

## Results

RESULTS_PLACEHOLDER

## Development

```bash
tools/build.sh
```

The fixture test runs the optimizer over saved pages inside a WordPress install with the plugin
active (see `tools/perf-lab/README.md` for the local WordPress and the asset mirror):

```bash
wp eval-file tests/optimize-fixtures.php <folder-with-saved-html> <output-folder> live
wp eval-file tests/optimize-fixtures.php <folder-with-saved-html> <output-folder> all
```

It fails if the visible text changes; links, images, forms, iframes, videos, SVGs or headings
disappear; script tags are unbalanced or an inline script loses its code; the cookie banner is
delayed or deferred at the wrong time; an image placeholder does not match its image; Google
Fonts hints are removed while Google Fonts are still linked; or the document no longer parses.

Rules carried over from the original project: tests never send analytics hits, consent logs or
form posts to live services (the replay blocks every other host and answers REST and non-GET
requests locally); live measurements only with PageSpeed Insights and curl; no tracking IDs, API
keys, font files or saved site HTML in the repository.

### Claude Code memory (claude-mem)

`.claude/settings.json` turns on the [claude-mem](https://github.com/thedotmack/claude-mem)
plugin, pinned to `v15.9.0` (the reviewed release) with its usage statistics off. It works in
Claude Code on your own computer; cloud sessions do not load plugins from a repository. Setup and
how to switch it off: see the original project's README.
