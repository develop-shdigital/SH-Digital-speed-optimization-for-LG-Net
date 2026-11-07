=== SHD Performance ===
Contributors: shdigital
Tags: performance, core web vitals, elementor, pagespeed
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.6.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Speed and Core Web Vitals optimizations and a page cache for the LGnet Elementor site, with a dashboard checklist for the steps that need a person.

== Description ==

SHD Performance rewrites the finished HTML of each front-end page (after Elementor and all plugins have run) and stores it in its own page cache. Nothing in Elementor, the content or other plugins is changed, so turning a feature off or deactivating the plugin restores the original behaviour immediately.

Automatic optimizations for the LGnet site (each one can be Off, Test or Live):

* The cookie banner (Cookiez) loads its language file with the page and, together with the deferred or delayed scripts, starts as soon as the page has been read instead of after every image, font and script.
* Ally's accessibility widget loads without stopping the page (async).
* Work Sans, which HTML widgets load from fonts.googleapis.com, is served from the site; visitors no longer contact Google.
* Images keep their space while they load, although the site's custom CSS switches the width/height placeholder off (img { aspect-ratio: unset !important; }); loaded images are sized exactly as before.
* The homepage hero slider no longer jumps by 72 px on phones while it measures the header.
* SH Motion leaves what is on screen at load still (header, first screen); everything further down still fades in.
* The main image of each page is prioritized; the header logo no longer competes with it.
* All stylesheets of a page, with Elementor's inline CSS, are joined in the same order into one cached file; WordPress's block library stylesheet (141 KB) is left out on pages without blocks.
* Size-matched fallbacks for Archivo, Public Sans and Work Sans, so text barely moves when the font arrives.
* Lazy-loaded images download at the size they are shown (sizes="auto"); post-card backgrounds and sections further down load when they come into view.
* The articles' table of contents is filled in from the start.
* Icon-only links get a name in the language of the page (German, English, French, Italian); the logo's file-name alt text becomes “LGnet”.
* Browser cache lifetimes for CSS, JavaScript, fonts (a year) and images (30 days), written to .htaccess and checked.
* Test (check in the Test view first): JavaScript deferred or started on the first interaction, jQuery Migrate removed.
* Ready for later additions: tracking scripts start after the first interaction, YouTube click-to-play, reCAPTCHA on demand, lazy iframes and videos.
* Real-visitor Core Web Vitals (LCP, INP, CLS, FCP, TTFB) are measured without cookies. Automated browsers, such as PageSpeed Insights' test runs, are not counted.

Page cache:

* Finished pages are stored as files and sent by Apache through .htaccess rules (or by wp-content/advanced-cache.php) without starting WordPress: a few milliseconds instead of 0.8–1.8 s.
* One copy for phones and computers; every public page and every language version (/en/, /fr/, /it/) is cached again in the background after a change.
* Cleared when content changes, including Elementor saves (pages, templates, site settings) and Elementor's "Clear Files & Data". Works together with Elementor's Element Cache.
* Logged-in users, form submissions, password-protected posts, searches and addresses with parameters always get a fresh page. Ad and campaign links can optionally get the cached page.
* Waits while another page cache plugin is active.
* Brotli: if Apache has its Brotli module, it is switched on for text files.

Images tab: replace the file of an image once and every page, template, header, footer and setting that uses it shows the new file. "Make lighter" turns the current file into a WebP file with the same dimensions (compare before using it); or upload your own file. The image keeps its ID, title and alt text, Elementor's CSS and the page cache are cleared, and each replacement can be undone until the old files are deleted.

The dashboard (SHD Performance in the admin menu) also contains health checks, a page scan, a measurement of where the server spends the time to build a page (per plugin, Elementor widget, shortcode, database query and request to other servers), and a step-by-step checklist for everything that has to be changed by hand.

== Installation ==

1. Plugins → Add New → Upload Plugin, choose the zip, activate. (An update: “Replace current with uploaded”.)
2. Open SHD Performance → Page cache: the status should be “Active”. Every page is cached in the background within a few minutes.
3. Live features are already active for visitors; Test features apply only in the Test view.
4. Check the Test view (admin bar or the secret preview link), then switch the Test features to Live on the Optimizations tab.
5. Work through the Checklist tab.

== Frequently Asked Questions ==

= How do I undo a change? =

Set the feature to Off on the Optimizations tab, or deactivate the plugin. The page cache is cleared automatically when you save in the dashboard, and deactivating removes it completely. If the admin is not reachable, add `define( 'SHD_PERFORMANCE_DISABLE', true );` to wp-config.php; to stop stored pages at once, also delete the folder wp-content/cache/shd-performance/pages.

= Does it replace Elementor's Element Cache? =

No, they work together. Element Cache keeps rendered widgets in the database, but WordPress still starts for every visit. The page cache sends the finished page without starting WordPress, and it is cleared whenever Elementor saves a document or clears its files.

= How do I compare with the original page? =

As an administrator, open any page with `?shd-off=1`, or use "Show this page without optimizations" in the admin bar.

= How do I replace an image on every page at once? =

SHD Performance → Images (or "Replace file" in the Media Library). Choose "Use the WebP version everywhere" or upload a new file. The attachment keeps its ID; all links to the old file in pages, Elementor data, templates, settings and term meta are rewritten to the new file (serialized settings are unpacked and packed again). Values that hold PHP objects are left alone and listed. The old files stay on the server until you delete them on the image's page, so links from other websites keep working and the replacement can be undone.

= Does the Web Vitals measurement use cookies? =

No. Each page view may send one anonymous beacon with the metric values, the page path and whether the screen is phone-sized. No IP address, user agent or identifier is stored. Data older than 90 days is deleted. Automated browsers (tests, and PageSpeed Insights' lab runs) send nothing, so the numbers come from real visits only.

== Changelog ==

= 1.6.0 =
* Adapted to the LGnet site (lgnet-wordpress.shdigital.ch), based on SHD Performance 1.5.2 of sh-digital.ch.
* New: the cookie banner (Cookiez) gets its language file with the page (for visitors without a stored choice) and, together with deferred or delayed scripts, starts when the page has been read.
* New: WordPress's block library stylesheet is left out on pages without block markup (/tv/: 141 KB).
* Fixed: inline scripts deferred by “Stop JavaScript from blocking the first paint” lost their code on WordPress 7.1 (the tag processor refuses data: URLs); they are now written in the final pass, and the fixture test checks for it.
* Fixed: delayed scripts started as soon as the cookie banner moved the focus into its dialog; only a visitor's own focus counts now.
* Fixed: in a loop grid the main image was picked by the size of the files, so the third post card on /ratgeber/ got high priority instead of the first one; only the first card of a grid counts now (all cards show their images at one size), and the fixture test checks it.
* New: images in the places whose backgrounds already wait (post cards after the first row, sections below the first screen, the footer) load when they come near the screen; not in the mobile menu, popups, tabs or accordions. On /ratgeber/ the six card images no longer share the connection with the main image (phone LCP 3.2 → 2.0 s with all features in Lighthouse).
* Changed: the font fallbacks are tuned on the site's own texts (15 pages, phone and desktop), so that headings break into the same lines before and after the web font arrives: Archivo 105.4 % (its headings are all 600/700), Public Sans 104 % with Arial Bold faces for 600/700, Work Sans 107.5 %. Elements that change size when the fonts arrive (first two screens): /internet/ 41 → 0, /ratgeber/ 30 → 2, /kontakt/ 9 → 0, /en/ 57 → 0 on phones.
* New: font stacks in the stylesheets of plugins and themes get the fallbacks as well (the hero slider's --lgnet-hero-font-head, the availability check, the order wizard); their @font-face rules stay as they are.
* Fixed: “Home” link names for language folders now come only from the page's hreflang links (/tv/ was taken for a language home); fallbacks are added to fonts in style attributes and in the font: shorthand (Work Sans in HTML widgets).
* New: Ally's accessibility widget loads async; Google Fonts in HTML widgets are served from the site.
* New: image placeholders while loading despite img { aspect-ratio: unset !important; } in the site CSS (layout shift 0.21 → 0 on /mobile-abo-vergleich/ on computers in Lighthouse).
* New: the LGnet Hero Slider no longer jumps on phones (three layout shifts of 0.09 each on the homepage).
* New: SH Motion keeps the elements that are on screen at load still.
* New: language versions found in stored pages (hreflang) are preloaded into the page cache; link and frame names follow the language of the page.
* New: browser cache lifetimes for static files (.htaccess, verified, removed again if the site does not answer), with a health check.
* New: size-matched fallbacks for Archivo, Public Sans and Work Sans; fonts are detected by themselves; font preloads are off by default.
* Elementor's animation stylesheets are kept (the off-canvas menu uses fadeInDown).
* Server rules: no www twin is added on subdomains; the block is now called “SHD Server Rules”.
* Removed the parts written for sh-digital.ch: IcoFont drawings, cursor script, Qi Addons / LA-Studio Kit unloading, CookieFirst and SHD Consent handling.
* Checklist rewritten for LGnet; the page scan finds custom CSS written as “{selector} img { … }”.

Earlier versions (1.0.0–1.5.2, sh-digital.ch): see the changelog in the SH-Digital-speed-optimization repository.
