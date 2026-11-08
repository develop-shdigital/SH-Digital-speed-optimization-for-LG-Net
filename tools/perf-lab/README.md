# perf-lab: measuring and testing without touching the live site

Tools from the sh-digital.ch speed project, generic for any WordPress site. Set `SITE` to the
live origin (for example `SITE=https://www.example.ch`). Working files go to `work/` (not
committed). See [HANDOFF.md](../../HANDOFF.md) for when to use which.

```bash
cd tools/perf-lab && npm ci        # lighthouse 13.6.0, puppeteer-core 25.16.0, chrome-devtools-mcp 1.12.0, MCP SDK 1.32.2
```

Chrome: `CHROME_PATH`, otherwise `/opt/pw-browsers/chromium` (cloud sessions), otherwise the
installed Chrome. Playwright (for `browser/`) is installed globally in cloud sessions.

## PageSpeed Insights results — `psi/`

| Command | Does |
| --- | --- |
| `python3 -I psi/fetch.py reports work/lhr <report-link>...` | Saves the mobile and desktop result of stored report links (`https://pagespeed.web.dev/analysis/...`). No key, no quota. |
| `PSI_API_KEY=... python3 -I psi/fetch.py api work/lhr <page-url>...` | Runs PageSpeed Insights for pages (mobile and desktop). Without a key Google allows only a few calls a day. |
| `python3 -I psi/summary.py work/lhr mobile` | Score, the five metrics, points lost per metric, failing audits — per page. |
| `python3 -I psi/detail.py work/lhr mobile <audit-id>...` | The items behind findings (LCP element, render-blocking files, long tasks, third parties…). |

## Chrome DevTools (the web-perf skill's tools) — `devtools/`

`node devtools/audit.mjs <url> mobile|desktop`: performance trace with every insight analysed,
the LCP element and first screen, and on desktop the network list, accessibility snapshot and
console errors. Output in `work/devtools/`. Use it when the chrome-devtools MCP server is not
connected to the session. It opens the real page: analytics on that page count a visit.

## Local replay of the live site — `replay/`, `lighthouse/`, `browser/`

```bash
SITE=https://www.example.ch bash replay/make-cert.sh
SITE=https://www.example.ch LOCAL_MAP='[["/wp-content/plugins/shd-performance/","/abs/repo/shd-performance/"]]' node replay/server.mjs
PAGE=/abs/saved/page.html SITE=https://www.example.ch node lighthouse/run.mjs /page-path/ work/lh/page-mobile.json mobile performance,accessibility
SITE=https://www.example.ch bash lighthouse/batch.sh work/lh/base 2 /=/abs/home.html /kontakt/=/abs/kontakt.html
SITE=https://www.example.ch node browser/scroll-check.cjs /page-path/ mobile
```

The replay serves the page under test from a local HTML file (before or after the plugin) and
the site's static files from a cache filled once from the live site. Browsers reach only the
replay: every other host is blocked, the REST API and all non-GET requests are answered
locally. Lighthouse uses PageSpeed Insights' settings; compare before/after on the same machine
(it cannot reproduce the live server's own slowness — confirm on PageSpeed Insights).

- A 5xx or 429 from the live site is passed on but never cached (a busy server once answered a
  stylesheet with 503 after 15 s, which then blocked rendering in every later run).
- Lighthouse simulates the load (Lantern): the document's own server time hardly changes the
  simulated FCP/LCP, and an element drawn by JavaScript (a cookie banner) counts as loaded only
  after every script that ran before it. Compare observed and simulated values (`metrics`
  audit) before concluding that a change did nothing.
- Third-party hosts are unreachable in the replay: their cost (an accessibility widget, Google
  Fonts) only shows on PageSpeed Insights. For layout comparisons this matters too: an original
  page that links Google Fonts renders in its fallback font in the replay, while the optimized
  page (fonts copied to the site) renders in the real font. Compare against a copy of the
  original that links the same local font files.
- Lantern shares the bandwidth evenly between parallel downloads, whatever their priority:
  once scripts no longer hold the parser up, every image discovered with the page competes
  with the main image (on LGnet's /ratgeber/ six post-card images, phone LCP 2.6 → 3.2 s).
  Images below the first screen need `loading="lazy"`.
- Images that come from a srcset file the original page never requested are fetched from the
  live site; a 503 there leaves the image empty in the lab (the replay logs `UPSTREAM 503`).
- Font fallbacks: check line breaks, not only average widths. `browser/font-swap.cjs` lists the
  texts that change size when the web fonts arrive; `browser/font-fit.cjs` finds, per family
  (and weight), the `size-adjust` at which the fewest texts break into a different number of
  lines. Headings with a `max-width` in `ch` follow the digit width, which Arial Bold gets wrong.

## Local WordPress — `local-wp/`

```bash
bash tools/perf-lab/local-wp/setup.sh "$SCRATCH/wp"            # SQLite, Elementor, Hello, SHD Performance (symlink)
PHP_CLI_SERVER_WORKERS=4 php -S 127.0.0.1:8088 -t "$SCRATCH/wp" tools/perf-lab/local-wp/router.php
python3 -I tools/perf-lab/local-wp/mirror-assets.py "$SITE" <saved-pages> "$SCRATCH/wp" "$SCRATCH/mirror/uploads" --fonts --scripts
MIRROR_SITE="$SITE" MIRROR_UPLOADS="$SCRATCH/mirror/uploads" \
  php tools/perf-lab/work/bin/wp-cli.phar --allow-root --path="$SCRATCH/wp" \
  eval-file tests/optimize-fixtures.php <saved-pages> <output-folder> live
```

With `MIRROR_SITE` and `MIRROR_UPLOADS` set, the local WordPress presents itself under the live
address (mu-plugin `site-mirror.php`), so saved live pages are optimized exactly as on the site
and the result can be measured through the replay (`LOCAL_MAP` the uploads mirror too).
