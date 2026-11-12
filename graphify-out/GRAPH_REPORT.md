# Graph Report - SH-Digital-speed-optimization-for-LG-Net  (2026-11-12)

## Corpus Check
- 61 files · ~102,714 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 3 file(s) not represented in the graph (top: (none) 2, .css 1)

## Summary
- 841 nodes · 1913 edges · 32 communities (16 shown, 16 thin omitted)
- Extraction: 95% EXTRACTED · 5% INFERRED · 0% AMBIGUOUS · INFERRED: 105 edges (avg confidence: 0.89)
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- HTML optimizer and lazy loading
- Admin dashboard
- Page cache
- Fonts and CSS rewriting
- Plugin core and profiler
- Project overview and tests
- Images tab (media replace)
- Checklist and server rules
- Background images and preloads
- Cookie banner and fonts
- PSI and mirror Python tools
- CSS combiner and backgrounds
- Delayed scripts (delay.js)
- Health checks
- Module registration
- Table of contents
- Real-visitor Web Vitals
- DevTools and Lighthouse runners
- Browser layout checks
- Lab dependencies
- Page cache delivery
- Local replay server
- Lab docs and shell tools
- Site scanner
- Font fit search
- Lazy iframes
- YouTube facade

## God Nodes (most connected - your core abstractions)
1. `Html_Optimizer` - 89 edges
2. `Page_Cache` - 78 edges
3. `Admin` - 77 edges
4. `Media_Replace` - 33 edges
5. `Profiler` - 27 edges
6. `Checklist` - 23 edges
7. `Fonts` - 23 edges
8. `Health_Checks` - 22 edges
9. `SHD Performance plugin` - 19 edges
10. `Domain_Rules` - 18 edges

## Surprising Connections (you probably didn't know these)
- `Dashboard checklist (steps that need a person)` --references--> `Checklist`  [INFERRED]
  README.md → shd-performance/includes/admin/class-checklist.php
- `Stylesheets joined into one cached file` --references--> `Css_Combiner`  [INFERRED]
  README.md → shd-performance/includes/class-css-combiner.php
- `Browser cache lifetimes (.htaccess mod_expires)` --references--> `Domain_Rules`  [INFERRED]
  README.md → shd-performance/includes/class-domain-rules.php
- `Link names and logo alt text in the page language` --references--> `Html_Optimizer`  [INFERRED]
  README.md → shd-performance/includes/class-html-optimizer.php
- `Image placeholders despite aspect-ratio: unset` --references--> `Html_Optimizer`  [INFERRED]
  README.md → shd-performance/includes/class-html-optimizer.php

## Import Cycles
- None detected.

## Hyperedges (group relationships)
- **Cookie banner starts before the other scripts** — readme_cookie_banner_lcp, shd_performance_includes_class_consent_consent, shd_performance_assets_js_delay, readme_test_features [INFERRED 0.85]
- **Font fallback pipeline** — shd_performance_includes_class_fonts_fonts, shd_performance_includes_class_font_metrics_font_metrics, shd_performance_includes_class_css_rewriter_css_rewriter, readme_font_fallbacks, tools_perf_lab_browser_font_fit [INFERRED 0.85]
- **Lab measurement chain** — tools_perf_lab_replay_server, tools_perf_lab_lighthouse_run, tools_perf_lab_lighthouse_batch, readme_lab_results [INFERRED 0.85]

## Communities (32 total, 16 thin omitted)

### Community 0 - "HTML optimizer and lazy loading"
Cohesion: 0.06
Nodes (4): later(), show(), Assets, Html_Optimizer

### Community 3 - "Fonts and CSS rewriting"
Cohesion: 0.06
Nodes (8): {closure#1}(), {closure#3}(), {closure#5}(), {closure#6}(), Css_Rewriter, Font_Metrics, Fonts, Settings

### Community 4 - "Plugin core and profiler"
Cohesion: 0.06
Nodes (3): Plugin, Profiler, Request

### Community 5 - "Project overview and tests"
Cohesion: 0.05
Nodes (17): README.md: SH Digital speed optimization for LGnet, Dashboard checklist (steps that need a person), claude-mem plugin (v15.9.0), Fixture test of the optimizer, Graphify knowledge graph (0.9.107), Lab results (Lighthouse 13.6, November 2026), LGnet site (WordPress + Elementor), Ponytail coding skills (v5.3.3) (+9 more)

### Community 8 - "Background images and preloads"
Cohesion: 0.09
Nodes (22): background_candidate(), {closure#5}(), frame_name(), frame_title(), hidden(), hide_classes(), in_first_screen(), in_first_screen_on() (+14 more)

### Community 9 - "Cookie banner and fonts"
Cohesion: 0.09
Nodes (8): Link names and logo alt text in the page language, Ally accessibility widget loaded async, Browser cache lifetimes (.htaccess mod_expires), Stylesheets joined into one cached file, Work Sans served from the site instead of Google, SHD Performance plugin, Consent, Google_Fonts

### Community 10 - "PSI and mirror Python tools"
Cohesion: 0.16
Nodes (9): fetch(), main(), curl(), main(), read_js_string(), reports_in(), save(), slug() (+1 more)

### Community 12 - "Delayed scripts (delay.js)"
Cohesion: 0.19
Nodes (22): call(), each(), fetchAhead(), listen(), makeEvent(), onClick(), onSubmit(), ownFile() (+14 more)

### Community 17 - "DevTools and Lighthouse runners"
Cohesion: 0.17
Nodes (10): DIR, insights, pageId, [url, mode = 'mobile'], connect(), LAB, OUT, LAB (+2 more)

### Community 18 - "Browser layout checks"
Cohesion: 0.14
Nodes (9): { chromium }, fs, pairs, path, SITE, { chromium }, fs, [pagePath, device = 'mobile'] (+1 more)

### Community 19 - "Lab dependencies"
Cohesion: 0.19
Nodes (13): chrome-devtools-mcp, lighthouse, @modelcontextprotocol/sdk, puppeteer-core, dependencies, chrome-devtools-mcp@1.12.0, lighthouse@13.6.0, @modelcontextprotocol/sdk@1.32.2 (+5 more)

### Community 21 - "Local replay server"
Cohesion: 0.23
Nodes (8): LAB, local, send(), server, sleep(), textual(), types, upstream()

### Community 22 - "Lab docs and shell tools"
Cohesion: 0.20
Nodes (5): batch.sh script, perf-lab README: measuring without touching the live site, Chrome DevTools audit (web-perf skill fallback), Local replay of the live site, make-cert.sh script

### Community 24 - "Font fit search"
Cohesion: 0.25
Nodes (7): { chromium }, fs, METRICS, [outFile, ...pairs], path, RANGE, SITE

### Community 25 - "Lazy iframes"
Cohesion: 0.70
Nodes (4): all(), later(), load(), start()

### Community 26 - "YouTube facade"
Cohesion: 0.70
Nodes (4): loadApi(), needsApi(), parse(), play()

## Knowledge Gaps
- **5 isolated node(s):** `LGnet site (WordPress + Elementor)`, `Ally accessibility widget loaded async`, `Ponytail coding skills (v5.3.3)`, `Graphify knowledge graph (0.9.107)`, `claude-mem plugin (v15.9.0)`
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 145 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **16 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `Html_Optimizer` connect `HTML optimizer and lazy loading` to `Fonts and CSS rewriting`, `Plugin core and profiler`, `Project overview and tests`, `Background images and preloads`, `Cookie banner and fonts`, `CSS combiner and backgrounds`?**
  _High betweenness centrality (0.231) - this node is a cross-community bridge._
- **Are the 8 inferred relationships involving `Html_Optimizer` (e.g. with `Link names and logo alt text in the page language` and `Image placeholders despite aspect-ratio: unset`) actually correct?**
  _`Html_Optimizer` has 8 INFERRED edges - model-reasoned connections that need verification._
- **What connects `LGnet site (WordPress + Elementor)`, `Ally accessibility widget loaded async`, `Ponytail coding skills (v5.3.3)` to the rest of the system?**
  _5 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `HTML optimizer and lazy loading` be split into smaller, more focused modules?**
  _Cohesion score 0.056334735857877516 - nodes in this community are weakly interconnected._
- **Why does `SHD Performance plugin` connect `Cookie banner and fonts` to `HTML optimizer and lazy loading`, `Fonts and CSS rewriting`, `Project overview and tests`, `Delayed scripts (delay.js)`, `Table of contents`, `Real-visitor Web Vitals`, `Page cache delivery`, `Lab docs and shell tools`?**
  _High betweenness centrality (0.143) - this node is a cross-community bridge._
- **Are the 2 inferred relationships involving `Page_Cache` (e.g. with `Page cache (Apache .htaccess delivery)` and `Works with Elementor Element Cache`) actually correct?**
  _`Page_Cache` has 2 INFERRED edges - model-reasoned connections that need verification._
- **Should `Admin dashboard` be split into smaller, more focused modules?**
  _Cohesion score 0.06386554621848739 - nodes in this community are weakly interconnected._