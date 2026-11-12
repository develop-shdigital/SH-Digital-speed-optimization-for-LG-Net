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
| `.claude/skills/ponytail*/`, `CLAUDE.md` | Ponytail coding skills (v5.3.3), switched on for every session; see [Ponytail](#ponytail-coding-skills). |
| `.claude/skills/graphify/`, `graphify-out/` | Graphify skill (0.9.107) and the knowledge graph of this repository; see [Graphify](#graphify-knowledge-graph-of-the-code). |
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
| Archivo and Public Sans swap in with a different width. | Size-matched Arial fallbacks, also for Work Sans and in plugin stylesheets (hero slider, availability check, order wizard). Their widths are tuned on the site's own texts so that headings break into the same lines before and after the font arrives (bold faces for Public Sans). Font preloads are off: they delayed the main image on phones. | Live |
| The header logo competes with the main image (twice per page). | The main image per device gets the priority (in post grids such as /ratgeber/ the first card's image); first-section backgrounds are preloaded. | Live |
| Post-card images and backgrounds and everything below the first screen load with the page (on /ratgeber/ six card images, among them a 436 KB PNG, share the connection with the main image). | They load when they come near the screen (images natively, not in the menu, popups, tabs or accordions). | Live |
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

Lab measurements, November 2026: Lighthouse 13.6 (the engine of PageSpeed Insights, with its
simulated mid-range phone on slow 4G and its desktop) on a local replay of the live pages, three
phone runs and one desktop run per page and version. Scores as ranges, LCP as averages.

- **before:** the pages as the site sends them now;
- **Live:** SHD Performance 1.6.0 with its defaults;
- **all:** also with the three Test features switched to Live (JavaScript deferred or started on
  the first interaction, jQuery Migrate removed), as the checklist proposes once the Test view has
  been checked.

| Page | Phone before | Phone, Live | Phone, all | Phone LCP: before / Live / all | Desktop before | Desktop, Live | Desktop, all |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Homepage | 91–93 | 93–97 | 99–100 | 2.5 s / 2.4 s / 1.8 s | 99 | 99 | 100 |
| /mobile/ | 76–84 | 91–93 | 99–100 | 4.1 s / 1.5 s / 1.1 s | 100 | 100 | 100 |
| /tv/ | 77–86 | 86–89 | 99–100 | 4.0 s / 3.6 s / 1.8 s | 99 | 100 | 100 |
| /internet/ | 81–83 | 83–89 | 98–99 | 3.8 s / 3.3 s / 1.9 s | 99 | 100 | 100 |
| /kontakt/ | 97 | 97–98 | 100 | 2.3 s / 2.2 s / 1.5 s | 100 | 100 | 100 |
| /shop/ | 90–94 | 91–94 | 98–99 | 3.1 s / 3.1 s / 2.3 s | 99 | 99 | 100 |
| /ratgeber/ | 95–97 | 94–96 | 99 | 2.6 s / 2.7 s / 2.0 s | 100 | 100 | 100 |
| /telefonanbieter-schweiz/ | 74–83 | 72–75 | 86 | 4.9 s / 4.9 s / 4.1 s | 89 | 90 | 91 |
| /en/ | 92–94 | 96–97 | 99–100 | 2.5 s / 2.2 s / 1.6 s | 98 | 99 | 100 |

Largest layout shift in any run: before 0.094, Live 0.018, all 0.018. Total Blocking Time on
phones: before 82–379 ms, Live 92–399 ms, all 1–126 ms.

- **95+ on phones needs the Test features.** With the Live defaults the homepage, /mobile/ and
  /en/ gain most. On /tv/ and /internet/ the cookie banner's text stays the largest element, and
  Cookiez can only draw it after jQuery and Elementor's scripts. With the Test features switched
  to Live, every measured page but one scores 98–100 on phones and 100 on computers.
- **/telefonanbieter-schweiz/ needs its image replaced** (86 on phones with all features): the
  article's main image is the 1.9 MB PNG, 436 KB at phone size. Replacing it with WebP
  (SHD Performance → Images, a checklist task) removes most of that download. With the Live
  defaults alone this page stays where it was; its run-to-run differences come from jQuery Migrate
  and Elementor Pro's scripts, which only the Test features change.
- **Not in these numbers:** the replay answers at once, so the 0.8–1.8 s the server needs to build
  a page today are left out of every version. The page cache removes most of that time on the
  live site, so the real gain is larger than the table shows.
- **Nothing visible changes.** After loading and scrolling, the optimized pages are identical to
  the originals on 15 pages, phone and desktop, with the Live defaults (every heading, paragraph,
  image, list item and button within 1 px, every image loaded, no new script errors), and on the
  8 pages also checked with all features. The pages with Work Sans were compared with originals
  that load the same font files (the replay blocks Google Fonts). The fixture test passes on all
  29 saved pages. While the web fonts load, the text uses fallbacks tuned on the site's own texts:
  elements in the first two screens that change size when the fonts arrive went from 41 to 0 on
  /internet/, 30 to 2 on /ratgeber/, 9 to 0 on /kontakt/ and 57 to 0 on /en/ (phones).

**On the live site** (after installing, and again after switching the Test features to Live):
PageSpeed Insights for the
[homepage](https://pagespeed.web.dev/analysis?url=https%3A%2F%2Flgnet-wordpress.shdigital.ch%2F),
[/mobile/](https://pagespeed.web.dev/analysis?url=https%3A%2F%2Flgnet-wordpress.shdigital.ch%2Fmobile%2F),
[/tv/](https://pagespeed.web.dev/analysis?url=https%3A%2F%2Flgnet-wordpress.shdigital.ch%2Ftv%2F),
[/internet/](https://pagespeed.web.dev/analysis?url=https%3A%2F%2Flgnet-wordpress.shdigital.ch%2Finternet%2F),
[/ratgeber/](https://pagespeed.web.dev/analysis?url=https%3A%2F%2Flgnet-wordpress.shdigital.ch%2Fratgeber%2F)
and [/telefonanbieter-schweiz/](https://pagespeed.web.dev/analysis?url=https%3A%2F%2Flgnet-wordpress.shdigital.ch%2Ftelefonanbieter-schweiz%2F),
a few minutes after activating, when SHD Performance → Page cache shows the pages as stored. Real visitors: SHD Performance → Web Vitals
after a few days (75th percentile, phones and computers separately).

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
delayed or deferred at the wrong time; an image placeholder does not match its image; a post card
other than the first of its grid gets high priority; Google Fonts hints are removed while Google
Fonts are still linked; or the document no longer parses.

Rules carried over from the original project: tests never send analytics hits, consent logs or
form posts to live services (the replay blocks every other host and answers REST and non-GET
requests locally); live measurements only with PageSpeed Insights and curl; no tracking IDs, API
keys, font files or saved site HTML in the repository.

### Ponytail (coding skills)

`.claude/skills/ponytail*` are the six skills of [Ponytail](https://github.com/DietrichGebert/ponytail)
(MIT), copied unchanged from release v5.3.3 (commit `7801d06`) after reading them: coding rules
(the smallest complete change, reuse before writing, never cut validation, security or tests) and
review, audit, debt and gain reports. `CLAUDE.md` switches them on for every session in this
repository. Only the skills are installed, not Ponytail's Claude Code plugin: its hooks add a file
list of the repository to every session and write a cost and token ledger after every turn. To
update, review a newer release and copy its `skills/` folders; to switch off, remove the section
in `CLAUDE.md`.

### Graphify (knowledge graph of the code)

[Graphify](https://github.com/Graphify-Labs/graphify) (Apache-2.0) turns the repository into a
queryable knowledge graph in `graphify-out/` (`graph.json`, `GRAPH_REPORT.md`, `graph.html`). Its
skill (`.claude/skills/graphify/`), the rules in `CLAUDE.md` (query the graph before searching code,
`graphify update .` after code changes) and its hooks in `.claude/settings.json` (a reminder before
searches and file reads) come from `graphify install --project` of version 0.9.107, read before
installing. `.graphifyignore` keeps the Claude setup, the bundled web-vitals library and the minified
script copies out of the graph. Code is read structurally without any AI; the docs are read by
Claude itself (subagents), so no API key is needed.

Every machine that runs Claude Code on this repository needs the command-line tool, otherwise its
hooks report "graphify: command not found" before each search or file read:

```bash
uv tool install graphifyy==0.9.107
```

In Claude Code on the web, put that line in the environment's setup script.

### Claude Code memory (claude-mem)

`.claude/settings.json` turns on the [claude-mem](https://github.com/thedotmack/claude-mem)
plugin, pinned to `v15.9.0` (the reviewed release) with its usage statistics off. It works in
Claude Code on your own computer; cloud sessions do not load plugins from a repository. Setup and
how to switch it off: see the original project's README.
