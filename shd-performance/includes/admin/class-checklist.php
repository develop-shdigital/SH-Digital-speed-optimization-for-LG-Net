<?php
/**
 * Manual tasks: what the plugin cannot change on its own, with instructions,
 * links and an automatic status where it can be detected.
 *
 * @package SHD\Performance
 */

namespace SHD\Performance;

defined( 'ABSPATH' ) || exit;

final class Checklist {

	const OPTION = 'shd_perf_checklist';

	/** @var array<string,array> Statuses computed during this request. */
	private static $statuses = array();

	/**
	 * Task definitions in recommended order.
	 *
	 * @return array<int,array>
	 */
	public static function tasks() {
		$home = home_url( '/' );
		$psi  = 'https://pagespeed.web.dev/analysis?url=';
		$kit  = (int) get_option( 'elementor_active_kit' );

		return array(
			array(
				'id'    => 'backup-staging',
				'group' => __( 'Before you start', 'shd-performance' ),
				'title' => __( 'Make a backup', 'shd-performance' ),
				'why'   => __( 'Some tasks change Elementor settings and images. A backup makes every step reversible. The site runs on the staging address lgnet-wordpress.shdigital.ch, so it is also the right place to try everything before the launch.', 'shd-performance' ),
				'steps' => array(
					__( 'Create a full backup (files and database), for example in Hostpoint’s Control Panel or with a backup plugin.', 'shd-performance' ),
					__( 'SHD Performance itself is safe to try: features in “Test” are only visible to you, and deactivating the plugin removes every automatic change.', 'shd-performance' ),
				),
			),
			array(
				'id'    => 'preview-test',
				'group' => __( 'Before you start', 'shd-performance' ),
				'title' => __( 'Check the Test features in the Test view, then switch them to Live', 'shd-performance' ),
				'why'   => __( 'On phones, the cookie banner is the largest element of most pages, and Google counts it as loaded only after every script that runs before it: jQuery, Elementor and the plugins. Deferring or delaying those scripts (in Test until checked on this site) lets the banner and the page appear first. This is the largest remaining gain for the mobile score.', 'shd-performance' ),
				'steps' => array(
					__( 'Open the preview link from the Overview tab in a private window, on a phone and on a computer (or use “Switch to Test view” in the admin bar).', 'shd-performance' ),
					__( 'Check: header menu and the mobile menu (it slides in), the language switcher (DE, EN, FR, IT), the hero slider on the homepage (arrows, dots, swiping, autoplay), the availability check on /internet/, every form (send a test message on /kontakt/), the order wizard on /wechseln/, the cookie banner (accept, reject, customise; the round button reopens it), the accessibility button, the table of contents in the articles and the back-to-top button.', 'shd-performance' ),
					__( 'Open the browser console (F12) and look for red errors.', 'shd-performance' ),
					__( 'If everything works, press “Switch all Test features to Live” on the Optimizations tab. If one thing breaks, set only that feature to Off and tell us which.', 'shd-performance' ),
					__( 'Whenever you save here, SHD Performance clears its page cache and caches every page again in the background.', 'shd-performance' ),
				),
				'auto'  => array( __CLASS__, 'auto_preview' ),
			),
			array(
				'id'    => 'page-cache',
				'group' => __( 'Caching and server', 'shd-performance' ),
				'title' => __( 'Check that the SHD page cache is active', 'shd-performance' ),
				'why'   => __( 'Without a page cache WordPress builds every page for every visitor: 0.8 to 1.8 s before the first byte arrives (measured in November 2026), up to 3 s for a translated product page. The SHD page cache stores each finished page, sends it in a few milliseconds and refills it in the background after every change, including the /en/, /fr/ and /it/ versions.', 'shd-performance' ),
				'steps' => array(
					__( 'Open SHD Performance → Page cache: the status should be “Active”, with pages sent directly by the web server (.htaccess). If a note asks you to change wp-config.php or .htaccess, do that (or ask Hostpoint).', 'shd-performance' ),
					__( 'Run the health checks on the Tools tab: “SHD page cache” and “Page cache, phone visitors” should be green, with HIT on the first phone request.', 'shd-performance' ),
					__( 'Check a page in a private window: open the browser’s developer tools (F12) → Network → reload → click the page: the response header x-shd-cache shows “HIT (htaccess)”.', 'shd-performance' ),
					__( 'Keep Elementor’s own “Element Cache” (Elementor → Settings → Performance) on: it makes rebuilding a page faster and works together with the page cache.', 'shd-performance' ),
				),
				'links' => array( __( 'Page cache', 'shd-performance' ) => admin_url( 'admin.php?page=shd-performance&tab=cache' ) ),
				'auto'  => array( __CLASS__, 'auto_page_cache' ),
			),
			array(
				'id'       => 'cache-query-params',
				'group'    => __( 'Caching and server', 'shd-performance' ),
				'title'    => __( 'Optional: let ad and campaign links use the cache', 'shd-performance' ),
				'optional' => true,
				'why'      => __( 'Addresses with a query string are not served from the cache by default, so every visitor from Google Ads (?gclid=…) or a campaign (?utm_…) waits for WordPress to build the page. PageSpeed tests the plain address, so this does not change the score, but it affects the real-visitor Core Web Vitals of paid traffic.', 'shd-performance' ),
				'steps'    => array(
					__( 'Switch on SHD Performance → Page cache → “Ad and campaign links”. Addresses that only add marketing parameters (utm_…, gclid, gbraid, wbraid, gad_source, gad_campaignid, fbclid, msclkid, li_fat_id and a few more) then get the cached page; tracking scripts in the browser still read the parameters.', 'shd-performance' ),
					__( 'When tracking is added later, check in Google Ads and Analytics that conversions and paid sessions are still attributed correctly.', 'shd-performance' ),
				),
				'links'    => array( __( 'Page cache', 'shd-performance' ) => admin_url( 'admin.php?page=shd-performance&tab=cache' ) ),
				'auto'     => array( __CLASS__, 'auto_campaign' ),
			),
			array(
				'id'       => 'server-ttfb',
				'group'    => __( 'Caching and server', 'shd-performance' ),
				'title'    => __( 'Optional: find out what makes uncached pages slow', 'shd-performance' ),
				'optional' => true,
				'why'      => __( 'Visitors get stored pages, but WordPress still builds the page for logged-in editors, form submissions, ad links with parameters and the first request after a change. Measured in November 2026: 0.8 s for /ratgeber/ to 1.8 s for /mobile/ (3.1 s once for /en/mobile/). A healthy Elementor site builds a page in 0.3–0.8 s.', 'shd-performance' ),
				'steps'    => array(
					__( 'Run the health checks on the Tools tab: they check the PHP version (8.2+), OPcache, a persistent object cache, autoloaded options and debug settings, and measure the homepage.', 'shd-performance' ),
					__( 'Tools → “Where the build time goes”: measure the homepage, /mobile/ and /en/mobile/. It shows the time per plugin (SHD Translator, the hero slider, Ally, Cookiez…), Elementor widget and shortcode, the database queries and requests to other servers, with what to do about the biggest items.', 'shd-performance' ),
					__( 'If the measurement shows no persistent object cache, OPcache off or PHP older than 8.2: ask Hostpoint for Redis or Memcached, OPcache and PHP 8.3.', 'shd-performance' ),
					__( 'If one plugin takes much of the time, check its settings. A plugin that asks another server for something while the page is built should store the answer; ask its developer.', 'shd-performance' ),
				),
				'links'    => array( __( 'Where the build time goes', 'shd-performance' ) => admin_url( 'admin.php?page=shd-performance&tab=tools#profile' ) ),
				'auto'     => array( __CLASS__, 'auto_server' ),
				'check'    => 'ttfb_uncached',
			),
			array(
				'id'    => 'browser-cache',
				'group' => __( 'Caching and server', 'shd-performance' ),
				'title' => __( 'Browser cache lifetimes for CSS, scripts, fonts and images', 'shd-performance' ),
				'why'   => __( 'The server sent no cache lifetime for the site’s files, so browsers asked again for them on every later page view. Done automatically while “Browser cache lifetimes” is live: CSS, JavaScript and fonts a year (they get a new address when they change), images 30 days.', 'shd-performance' ),
				'steps' => array(
					__( 'Run the health checks on the Tools tab: “Browser cache lifetime of static files” should show 365 days.', 'shd-performance' ),
					__( 'If it shows “none sent”, the server lacks mod_expires or .htaccess cannot be written: ask Hostpoint to send Cache-Control lifetimes for CSS, JS, fonts and images.', 'shd-performance' ),
				),
				'links' => array( __( 'Health checks', 'shd-performance' ) => admin_url( 'admin.php?page=shd-performance&tab=tools#health' ) ),
				'auto'  => array( __CLASS__, 'auto_health' ),
				'check' => 'browser_cache',
			),
			array(
				'id'       => 'compression',
				'group'    => __( 'Caching and server', 'shd-performance' ),
				'title'    => __( 'Optional: Brotli compression and HTTP/3 at the host', 'shd-performance' ),
				'optional' => true,
				'why'      => __( 'Text files are compressed with gzip today. Brotli makes HTML, CSS and JavaScript about 15–20% smaller; HTTP/3 makes connections on mobile networks faster. Both are settings of the web server, and the PageSpeed score hardly changes, so this is a nice-to-have.', 'shd-performance' ),
				'steps'    => array(
					__( 'SHD Performance tries first: if Apache has its Brotli module, the page cache switches it on for text files (Page cache tab → “Brotli”, on by default). Run the health checks on the Tools tab; if files arrive with “br”, this task is done.', 'shd-performance' ),
					__( 'Otherwise ask Hostpoint to enable mod_brotli for text files (HTML, CSS, JavaScript, SVG and JSON). HTTP/3 needs a CDN or a web server with QUIC in front of Apache.', 'shd-performance' ),
				),
				'links'    => array( __( 'Health checks', 'shd-performance' ) => admin_url( 'admin.php?page=shd-performance&tab=tools#health' ) ),
				'auto'     => array( __CLASS__, 'auto_health' ),
				'check'    => 'compression',
			),
			array(
				'id'    => 'custom-css',
				'group' => __( 'Elementor content', 'shd-performance' ),
				'title' => __( 'Fix the custom CSS that applies to the whole page', 'shd-performance' ),
				'why'   => __( 'Several widgets on /mobile/ and /mobile-abo-vergleich/ (and their translations) have Custom CSS written as “{selector} img { … }” and “{selector} p { … }”. Elementor replaces “selector” with the widget’s own selector, but the braces around it make the browser drop that part, so the rule applies to every image or paragraph of the page: for example width: 100%, aspect-ratio: 21/9, object-fit: cover and rounded corners for every image, and centred text with a maximum width for every paragraph. The site-wide rule img { aspect-ratio: unset !important; } in Elementor → Site Settings → Custom CSS probably undoes part of it, but it also switches off the space browsers reserve for every image on every page, so content jumps when images load (layout shift 0.21 on /mobile-abo-vergleich/ on computers). SHD Performance gives images their space back while they load (“Reserve the space of images while they load”), so this task is about clean CSS, not speed.', 'shd-performance' ),
				'steps' => array(
					__( 'Tools → “Scan pages”: the section “Custom CSS” lists each widget with the broken rule and links to it.', 'shd-performance' ),
					__( 'In each widget: Advanced → Custom CSS: replace “{selector} img” with “selector img” (and “{selector} p” with “selector p”). The rule then applies to that widget only, as intended. Update, then compare the page with the live version: images and texts outside these widgets may change their look slightly, because the rule no longer reaches them; that is the intended design.', 'shd-performance' ),
					__( 'Then remove img { aspect-ratio: unset !important; } from Elementor → Site Settings → Custom CSS and check the pages again (images keep their proportions; nothing jumps while loading).', 'shd-performance' ),
				),
				'links' => array_filter(
					array(
						__( 'Page scan', 'shd-performance' ) => admin_url( 'admin.php?page=shd-performance&tab=tools#scan' ),
						__( 'Site Settings (Elementor)', 'shd-performance' ) => $kit > 0 ? admin_url( 'post.php?post=' . $kit . '&action=elementor' ) : '',
					)
				),
				'auto'  => array( __CLASS__, 'auto_custom_css' ),
			),
			array(
				'id'    => 'hero-slider',
				'group' => __( 'Elementor content', 'shd-performance' ),
				'title' => __( 'LGnet Hero Slider: no jumps on phones (fixed automatically; permanent fix in the slider)', 'shd-performance' ),
				'why'   => __( 'On phones the homepage hero jumped by 72 px up to three times while loading: the slider measures the header only when the page has been read, and Elementor starts it a second time, which first removes the measured space (layout shift up to 0.26, more than Google’s limit of 0.1). “Homepage hero slider: no jumps on phones” fixes it from the outside. The clean fix belongs in the slider plugin itself.', 'shd-performance' ),
				'steps' => array(
					__( 'Nothing to do while “Homepage hero slider: no jumps on phones” is live.', 'shd-performance' ),
					__( 'For the plugin’s developers (lgnet-hero-slider, assets/hero-slider.js): in initAll(), keep an existing instance on the live site and only rebuild it in the Elementor editor; in destroy(), keep --lgnet-hero-header-h when the slider is started again; and measure the header once right where the hero begins (an inline call after the hero markup), so the first paint already has the space.', 'shd-performance' ),
				),
				'auto'  => array( __CLASS__, 'auto_hero' ),
			),
			array(
				'id'    => 'images',
				'group' => __( 'Images', 'shd-performance' ),
				'title' => __( 'Replace oversized image files (no visible quality loss)', 'shd-performance' ),
				'why'   => __( 'The picture of the article “Telefonanbieter Schweiz” is a PNG photo: 1.9 MB at full size and 436 KB in the 768 px size phones load. It is the largest element of that article and is loaded again by the post cards on /ratgeber/ and under the other articles. Photos saved as PNG usually become 80–90% smaller as WebP.', 'shd-performance' ),
				'steps' => array(
					__( 'Open the Images tab. It lists the largest files; open telefonanbieter-schweiz.png with “Make lighter or replace”.', 'shd-performance' ),
					__( '“Make lighter” shows a WebP version with the same width and height next to the original. Compare both at full size, then press “Use the WebP version everywhere”. Every page, template and setting that uses the image switches to the new file; Elementor’s CSS and the page cache are cleared automatically.', 'shd-performance' ),
					__( 'Do the same for the other files the list shows over 300 KB.', 'shd-performance' ),
					__( 'Keep the old files for a few days while you check the pages (each replacement can be undone), then delete them on the image’s page.', 'shd-performance' ),
				),
				'links' => array( __( 'Images tab', 'shd-performance' ) => admin_url( 'admin.php?page=shd-performance&tab=images' ) ),
				'auto'  => array( __CLASS__, 'auto_images' ),
			),
			array(
				'id'       => 'image-sizes',
				'group'    => __( 'Images', 'shd-performance' ),
				'title'    => __( 'Optional: smaller image sizes in the post cards and product images', 'shd-performance' ),
				'optional' => true,
				'why'      => __( 'The post cards on /ratgeber/ and under the articles load the full-size featured images, and several product pages use “Full” images in the first screen (for example a 1254 × 1254 px picture on /shop/, 99 KB on phones). PageSpeed estimates 30–530 KB of savings per page.', 'shd-performance' ),
				'steps'    => array(
					__( 'Post cards (the loop item template of /ratgeber/ and of the related posts): set the featured image’s size to “Medium Large” (768 px) instead of “Full”. Where the card image is a container background (Background → Image, dynamic “Featured Image”), choose the image size there too.', 'shd-performance' ),
					__( 'Image widgets in the first screen of the product pages: “Image Resolution” → “Large” (1024 px) instead of “Full” where the picture is shown at most about 600 px wide.', 'shd-performance' ),
				),
				'links'    => array( __( 'Media library', 'shd-performance' ) => admin_url( 'upload.php' ) ),
			),
			array(
				'id'       => 'site-fonts',
				'group'    => __( 'Fonts', 'shd-performance' ),
				'title'    => __( 'Optional: use the site’s fonts in the comparison tables', 'shd-performance' ),
				'optional' => true,
				'why'      => __( 'The HTML widgets with the comparison tables on /mobile/, /tv/ and /mobile-abo-vergleich/ load a third font, Work Sans, from Google. SHD Performance now serves it from this site (“Serve Google Fonts from this site”), so visitors no longer contact Google. Using Archivo or Public Sans there instead would save three more font files (about 100 KB) on these pages. This is a design decision.', 'shd-performance' ),
				'steps'    => array(
					__( 'In each of these HTML widgets: remove the three <link> lines for fonts.googleapis.com and fonts.gstatic.com at the top, and replace “Work Sans” with “Public Sans” (or Archivo for headings) in the widget’s CSS. Compare the table before and after.', 'shd-performance' ),
				),
				'auto'     => array( __CLASS__, 'auto_google_fonts' ),
			),
			array(
				'id'    => 'font-icons',
				'group' => __( 'Fonts', 'shd-performance' ),
				'title' => __( 'Keep Elementor’s inline SVG icons switched on', 'shd-performance' ),
				'why'   => __( 'Elementor draws the icons as inline SVG (Elementor → Settings → Features → “Inline Font Icons”), so no icon font is downloaded. Keep it that way when adding icons.', 'shd-performance' ),
				'steps' => array(
					__( 'Nothing to do while the health check “Elementor “Inline Font Icons”” is green.', 'shd-performance' ),
				),
				'auto'  => array( __CLASS__, 'auto_health' ),
				'check' => 'font_icons',
			),
			array(
				'id'    => 'cookie-banner',
				'group' => __( 'Third parties', 'shd-performance' ),
				'title' => __( 'Cookie banner: shown as soon as the page has been read', 'shd-performance' ),
				'why'   => __( 'Cookiez waits for every image, font and script before it draws the banner, and the banner is the largest element of most pages on phones, so Google measured the pages as loaded only then. “Show the cookie banner as soon as the page has been read” loads its texts with the page and, together with the deferred or delayed scripts of the first task, draws it before those scripts (Largest Contentful Paint about 2 s instead of 4 s on phones in Lighthouse).', 'shd-performance' ),
				'steps' => array(
					__( 'Do the first task (Test features to Live). Nothing else to do while the feature is live.', 'shd-performance' ),
					__( 'Optional design decision: a smaller banner box on phones (Cookiez’s layout setting, instead of the bar over half the screen) would make the page’s own content the largest element again.', 'shd-performance' ),
				),
				'auto'  => array( __CLASS__, 'auto_cookie_banner' ),
			),
			array(
				'id'       => 'tracking-ga',
				'group'    => __( 'Third parties', 'shd-performance' ),
				'title'    => __( 'When tracking is added: one Google tag, started after the first interaction', 'shd-performance' ),
				'optional' => true,
				'why'      => __( 'The site has no analytics or ad tags today. When they are added, load Google Analytics or Google Tag Manager from one place only (a theme option, a plugin or the tag manager, not two of them); “Start tracking scripts after the first interaction” then keeps them out of the page load automatically. Connect them to Cookiez’s consent (Google Consent Mode) so they respect the visitor’s choice.', 'shd-performance' ),
				'steps'    => array(
					__( 'After adding a tag, run the health checks: “Google Analytics 4 loaded once” must stay green.', 'shd-performance' ),
				),
				'auto'     => array( __CLASS__, 'auto_health' ),
				'check'    => 'ga_duplicate',
			),
			array(
				'id'    => 'accessibility',
				'group' => __( 'Accessibility', 'shd-performance' ),
				'title' => __( 'Accessibility findings', 'shd-performance' ),
				'why'   => __( 'Found by Lighthouse and the browser’s accessibility tree; they also count in the PageSpeed accessibility score.', 'shd-performance' ),
				'steps' => array(
					__( 'Done automatically while “Accessibility: link names, list roles, skip link, heading levels, logo text” is live (Optimizations tab): icon-only links (phone, menu, back to top) get a name in the language of the page; the post cards get list roles; headings that skip levels get the right level for screen readers (aria-level); the logo’s alternative text “logo-mark-dark” becomes “LGnet”.', 'shd-performance' ),
					__( 'Optional, in Elementor: your own wording for an icon link (Link options (gear icon) → Custom Attributes, for example “aria-label|Menü öffnen”) and the logo’s alternative text in the media library. The plugin keeps what you set.', 'shd-performance' ),
				),
				'auto'  => array( __CLASS__, 'auto_accessibility' ),
			),
			array(
				'id'    => 'launch',
				'group' => __( 'Launch', 'shd-performance' ),
				'title' => __( 'At the launch on the final domain', 'shd-performance' ),
				'why'   => __( 'The site runs on the staging address and asks search engines not to index it (noindex). When it moves to its own domain, a few settings follow.', 'shd-performance' ),
				'steps' => array(
					__( 'Settings → Reading: untick “Discourage search engines from indexing this site”.', 'shd-performance' ),
					__( 'SHD Performance → Optimizations → Advanced settings → “Other addresses”: list the domain’s other addresses (for example lgnet.ch when the site is on www.lgnet.ch, old domains). The web server then sends them to the same page in one step, and robots.txt lists only the site’s own sitemap.', 'shd-performance' ),
					__( 'A redirect set up in Hostpoint’s Control Panel happens before the site and must point directly to the final address (301, keep the path).', 'shd-performance' ),
					__( 'Purge the page cache once (Page cache tab) and run the health checks.', 'shd-performance' ),
				),
				'links' => array( __( 'Reading settings', 'shd-performance' ) => admin_url( 'options-reading.php' ) ),
				'auto'  => array( __CLASS__, 'auto_launch' ),
			),
			array(
				'id'       => 'redirects',
				'group'    => __( 'Launch', 'shd-performance' ),
				'title'    => __( 'Other addresses go to the site in one step', 'shd-performance' ),
				'optional' => true,
				'why'      => __( 'Each extra redirect costs a round trip, and one that WordPress answers costs about a second.', 'shd-performance' ),
				'steps'    => array(
					__( 'Done automatically for the addresses listed in the advanced settings while “robots.txt and redirects of the other addresses” is live.', 'shd-performance' ),
				),
				'auto'     => array( __CLASS__, 'auto_redirects' ),
				'check'    => 'redirects',
			),
			array(
				'id'       => 'robots',
				'group'    => __( 'Launch', 'shd-performance' ),
				'title'    => __( 'robots.txt lists only the site’s own sitemap', 'shd-performance' ),
				'optional' => true,
				'why'      => __( 'Sitemap lines of other addresses make search engines read the sitemap of an address that only redirects.', 'shd-performance' ),
				'steps'    => array(
					__( 'Done automatically while “robots.txt and redirects of the other addresses” is live: sitemap lines that point to another address are removed (a copy of the old file is kept in uploads/shd-performance/backup); nothing else in the file changes.', 'shd-performance' ),
				),
				'links'    => array( __( 'Yoast file editor', 'shd-performance' ) => admin_url( 'admin.php?page=wpseo_tools&tool=file-editor' ) ),
				'auto'     => array( __CLASS__, 'auto_robots' ),
				'check'    => 'robots',
			),
			array(
				'id'    => 'verify-lab',
				'group' => __( 'Verify', 'shd-performance' ),
				'title' => __( 'Verify with PageSpeed Insights (mobile and desktop)', 'shd-performance' ),
				'why'   => __( 'Target: 95+ on both. PageSpeed varies by a few points between runs, so test each page two or three times.', 'shd-performance' ),
				'steps' => array(
					__( 'Wait a few minutes after a change: the page cache is refilled in the background. Then run PageSpeed Insights for the pages below.', 'shd-performance' ),
					__( 'If a page stays below target, its report shows the remaining items; most are covered by the tasks above.', 'shd-performance' ),
				),
				'links' => array(
					__( 'Homepage', 'shd-performance' ) => $psi . rawurlencode( $home ),
					'/mobile/'                        => $psi . rawurlencode( home_url( '/mobile/' ) ),
					'/internet/'                      => $psi . rawurlencode( home_url( '/internet/' ) ),
					'/tv/'                            => $psi . rawurlencode( home_url( '/tv/' ) ),
					'/kontakt/'                       => $psi . rawurlencode( home_url( '/kontakt/' ) ),
					'/ratgeber/'                      => $psi . rawurlencode( home_url( '/ratgeber/' ) ),
					'/en/'                            => $psi . rawurlencode( home_url( '/en/' ) ),
				),
			),
			array(
				'id'    => 'verify-field',
				'group' => __( 'Verify', 'shd-performance' ),
				'title' => __( 'Verify the real-visitor Core Web Vitals after 2–4 weeks', 'shd-performance' ),
				'why'   => __( 'Core Web Vitals “pass” is decided on real visits (75th percentile: LCP ≤ 2.5 s, INP ≤ 200 ms, CLS ≤ 0.1). Google has no such data for this site yet; the Web Vitals tab shows it from your own visitors.', 'shd-performance' ),
				'steps' => array(
					__( 'Check the Web Vitals tab: mobile and desktop should show “good” for LCP, INP and CLS.', 'shd-performance' ),
					__( 'Once the site is public and Google has enough data, Search Console → Core Web Vitals shows the same assessment.', 'shd-performance' ),
				),
				'links' => array(
					__( 'Web Vitals tab', 'shd-performance' ) => admin_url( 'admin.php?page=shd-performance&tab=vitals' ),
					'Search Console'                         => 'https://search.google.com/search-console/core-web-vitals',
				),
				'auto'  => array( __CLASS__, 'auto_field' ),
			),
		);
	}

	/**
	 * Stored manual marks: task id => timestamp.
	 *
	 * @return array<string,int>
	 */
	public static function marks() {
		$marks = get_option( self::OPTION, array() );
		return is_array( $marks ) ? $marks : array();
	}

	/**
	 * @param string $id   Task id.
	 * @param bool   $done Done or open.
	 */
	public static function mark( $id, $done ) {
		self::$statuses = array();
		$marks          = self::marks();
		if ( $done ) {
			$marks[ $id ] = time();
		} else {
			unset( $marks[ $id ] );
		}
		update_option( self::OPTION, $marks, false );
	}

	/**
	 * Status of a task: automatic detection first, then the manual mark.
	 *
	 * @param array $task Task.
	 * @return array{status:string,note:string,manual:bool}
	 */
	public static function status( array $task ) {
		if ( isset( self::$statuses[ $task['id'] ] ) ) {
			return self::$statuses[ $task['id'] ];
		}
		$auto = array(
			'status' => 'unknown',
			'note'   => '',
		);
		if ( isset( $task['auto'] ) && is_callable( $task['auto'] ) ) {
			$auto = call_user_func( $task['auto'], $task );
		}
		$marks  = self::marks();
		$manual = isset( $marks[ $task['id'] ] );
		if ( 'done' !== $auto['status'] && $manual ) {
			$auto['status'] = 'done';
			$auto['note']   = trim( $auto['note'] . ' ' . __( '(marked as done)', 'shd-performance' ) );
		}
		$auto['manual'] = $manual;

		self::$statuses[ $task['id'] ] = $auto;
		return $auto;
	}

	/**
	 * First required task that is not done.
	 *
	 * @return array{task:array,step:int}|null Task and its 1-based step in the guide.
	 */
	public static function next() {
		foreach ( self::tasks() as $index => $task ) {
			if ( empty( $task['optional'] ) && 'done' !== self::status( $task )['status'] ) {
				return array(
					'task' => $task,
					'step' => $index + 1,
				);
			}
		}
		return null;
	}

	/**
	 * @return array{done:int,total:int}
	 */
	public static function progress() {
		$done  = 0;
		$total = 0;
		foreach ( self::tasks() as $task ) {
			if ( ! empty( $task['optional'] ) ) {
				continue;
			}
			++$total;
			if ( 'done' === self::status( $task )['status'] ) {
				++$done;
			}
		}
		return array(
			'done'  => $done,
			'total' => $total,
		);
	}

	/* Automatic status detectors. */

	public static function auto_health( array $task ) {
		$health = Health_Checks::last();
		$result = null;
		// The first check (in the task's order) that has a result decides.
		foreach ( (array) $task['check'] as $check ) {
			if ( null !== $health && isset( $health['results'][ $check ] ) ) {
				$result = $health['results'][ $check ];
				break;
			}
		}
		if ( null === $result ) {
			return array(
				'status' => 'unknown',
				'note'   => __( 'Run the health checks to detect this.', 'shd-performance' ),
			);
		}
		return array(
			'status' => 'good' === $result['status'] ? 'done' : ( 'info' === $result['status'] ? 'unknown' : 'open' ),
			'note'   => $result['value'],
		);
	}

	public static function auto_accessibility() {
		if ( 'live' === Settings::state( 'a11y_names' ) ) {
			return array(
				'status' => 'done',
				'note'   => __( 'Done automatically: link names, list roles, skip link target, heading levels and the logo text.', 'shd-performance' ),
			);
		}
		return array(
			'status' => 'open',
			'note'   => __( '“Accessibility: link names, list roles, skip link, heading levels, logo text” is not live (Optimizations tab).', 'shd-performance' ),
		);
	}

	public static function auto_robots( array $task ) {
		$status = Domain_Rules::status();
		if ( 'live' === Settings::state( Domain_Rules::FEATURE ) && isset( $status['robots'] ) ) {
			if ( in_array( $status['robots'], array( 'cleaned', 'clean' ), true ) ) {
				return array(
					'status' => 'done',
					'note'   => isset( $status['robots_cleaned'] )
						/* translators: %s: date */
						? sprintf( __( 'Done automatically on %s; the old file is kept in uploads/shd-performance/backup.', 'shd-performance' ), wp_date( (string) get_option( 'date_format' ), (int) $status['robots_cleaned'] ) )
						: __( 'robots.txt lists only this address’s sitemaps.', 'shd-performance' ),
				);
			}
			if ( 'virtual' === $status['robots'] ) {
				return array(
					'status' => 'done',
					'note'   => __( 'WordPress builds robots.txt; sitemap lines of other addresses are left out automatically.', 'shd-performance' ),
				);
			}
			if ( 'unwritable' === $status['robots'] ) {
				return array(
					'status' => 'open',
					'note'   => __( 'robots.txt cannot be changed by WordPress (file permissions): remove the line by hand.', 'shd-performance' ),
				);
			}
		}
		return self::auto_health( $task );
	}

	public static function auto_redirects( array $task ) {
		$result = self::auto_health( $task );
		$status = Domain_Rules::status();
		$rules  = isset( $status['redirects'] ) ? $status['redirects'] : '';
		$notes  = array(
			'active'     => __( 'Other addresses that reach the site are redirected by the web server in one step.', 'shd-performance' ),
			'reverted'   => __( 'The redirect rules were removed again because the site did not answer after they were written.', 'shd-performance' ),
			'unwritable' => __( '.htaccess cannot be changed by WordPress (file permissions).', 'shd-performance' ),
			'not_apache' => __( 'The server does not read .htaccess; set the redirects at the host.', 'shd-performance' ),
		);
		if ( isset( $notes[ $rules ] ) ) {
			$result['note'] = trim( $notes[ $rules ] . ' ' . $result['note'] );
		}
		if ( 'done' !== $result['status'] && 'active' === $rules ) {
			$result['note'] .= ' ' . __( 'The remaining extra step is the redirect at Hostpoint (see the steps).', 'shd-performance' );
		}
		return $result;
	}

	public static function auto_cookie_banner() {
		$cookiez = false;
		foreach ( (array) get_option( 'active_plugins', array() ) as $plugin ) {
			if ( 0 === strpos( (string) $plugin, 'cookiez/' ) ) {
				$cookiez = true;
			}
		}
		if ( ! $cookiez ) {
			return array(
				'status' => 'done',
				'note'   => __( 'Cookiez is not active.', 'shd-performance' ),
			);
		}
		$live    = 'live' === Settings::state( 'cookie_banner' );
		$scripts = 'live' === Settings::state( 'defer_scripts' ) || 'live' === Settings::state( 'delay_all_scripts' );
		if ( ! $live ) {
			return array(
				'status' => 'open',
				'note'   => __( '“Show the cookie banner as soon as the page has been read” is not live (Optimizations tab).', 'shd-performance' ),
			);
		}
		return array(
			'status' => $scripts ? 'done' : 'open',
			'note'   => $scripts ? __( 'Done automatically: the banner starts as soon as the page has been read, before the other scripts.', 'shd-performance' ) : __( 'Its texts load with the page; it starts earlier once the deferred or delayed scripts are live (first task).', 'shd-performance' ),
		);
	}

	public static function auto_custom_css() {
		$scan = Site_Scanner::last();
		if ( null === $scan || ! isset( $scan['css'] ) ) {
			return array(
				'status' => 'unknown',
				'note'   => __( 'Run “Scan pages” on the Tools tab.', 'shd-performance' ),
			);
		}
		$count = count( (array) $scan['css'] );
		return array(
			'status' => 0 === $count ? 'done' : 'open',
			/* translators: %d: rules */
			'note'   => 0 === $count ? __( 'No custom CSS applies to the whole page.', 'shd-performance' ) : sprintf( _n( '%d rule to fix (see Tools → Page scan).', '%d rules to fix (see Tools → Page scan).', $count, 'shd-performance' ), $count ),
		);
	}

	public static function auto_hero() {
		$live = 'live' === Settings::state( 'hero_steady' );
		return array(
			'status' => $live ? 'done' : 'open',
			'note'   => $live ? __( 'Done automatically on the site; the permanent fix in the slider plugin is optional.', 'shd-performance' ) : __( '“Homepage hero slider: no jumps on phones” is not live (Optimizations tab).', 'shd-performance' ),
		);
	}

	public static function auto_google_fonts() {
		$copies = Google_Fonts::copies();
		$live   = 'live' === Settings::state( 'google_fonts' );
		return array(
			'status' => 'unknown',
			/* translators: %d: stylesheets */
			'note'   => $live ? sprintf( _n( 'Google Fonts are served from this site (%d stylesheet copied).', 'Google Fonts are served from this site (%d stylesheets copied).', count( $copies ), 'shd-performance' ), count( $copies ) ) : __( '“Serve Google Fonts from this site” is not live.', 'shd-performance' ),
		);
	}

	public static function auto_launch() {
		$public = '1' === (string) get_option( 'blog_public', '1' );
		$host   = Domain_Rules::canonical_host();
		return array(
			'status' => $public && false === strpos( $host, 'lgnet-wordpress.' ) ? 'done' : 'open',
			'note'   => $public ? __( 'Search engines may index the site.', 'shd-performance' ) : __( 'Search engines are asked not to index the site (staging).', 'shd-performance' ),
		);
	}

	public static function auto_server( array $task ) {
		$status = self::auto_health( $task );
		$report = Profiler::last();
		if ( null !== $report ) {
			/* translators: 1: page address, 2: milliseconds */
			$status['note'] = trim( $status['note'] . ' ' . sprintf( __( 'Last measurement: %1$s built in %2$s ms.', 'shd-performance' ), wp_make_link_relative( $report['url'] ), number_format_i18n( (int) $report['total_ms'] ) ) );
		}
		return $status;
	}

	public static function auto_page_cache() {
		$status = Page_Cache::status();
		if ( 'active' === $status['state'] ) {
			return array(
				'status' => 'plugin' === $status['delivery'] ? 'open' : 'done',
				'note'   => 'htaccess' === $status['delivery'] ? __( 'Active; pages are sent directly by the web server.', 'shd-performance' ) : ( 'php' === $status['delivery'] ? __( 'Active; pages are sent before WordPress loads.', 'shd-performance' ) : implode( ' ', (array) $status['notes'] ) ),
			);
		}
		return array(
			'status' => 'open',
			/* translators: %s: plugin name */
			'note'   => 'blocked' === $status['state'] && '' !== $status['blocker'] ? sprintf( __( '%s is still active.', 'shd-performance' ), $status['blocker'] ) : implode( ' ', (array) $status['notes'] ),
		);
	}

	public static function auto_campaign() {
		$on = ! empty( Settings::get()['cache_campaign'] );
		return array(
			'status' => $on ? 'done' : 'open',
			'note'   => $on ? __( 'Ad and campaign links get the cached page.', 'shd-performance' ) : __( 'Ad and campaign links wait for WordPress.', 'shd-performance' ),
		);
	}

	public static function auto_preview() {
		$test = array();
		foreach ( Settings::features() as $id => $feature ) {
			if ( 'test' === Settings::state( $id ) ) {
				$test[] = $feature['title'];
			}
		}
		return array(
			'status' => empty( $test ) ? 'done' : 'open',
			/* translators: %d: number of features */
			'note'   => empty( $test ) ? __( 'No feature is waiting in Test.', 'shd-performance' ) : sprintf( _n( '%d feature is in Test.', '%d features are in Test.', count( $test ), 'shd-performance' ), count( $test ) ),
		);
	}

	public static function auto_images() {
		$scan = Site_Scanner::last();
		if ( null === $scan ) {
			return array(
				'status' => 'unknown',
				'note'   => __( 'Run “Scan pages” on the Tools tab.', 'shd-performance' ),
			);
		}
		$big = 0;
		foreach ( $scan['images'] as $image ) {
			if ( $image['bytes'] >= 300 * KB_IN_BYTES ) {
				++$big;
			}
		}
		return array(
			'status' => 0 === $big ? 'done' : 'open',
			/* translators: %d: files */
			'note'   => sprintf( _n( '%d image file over 300 KB.', '%d image files over 300 KB.', $big, 'shd-performance' ), $big ),
		);
	}

	public static function auto_field() {
		$summary = Rum::summary();
		$mobile  = $summary['m'];
		if ( $mobile['count'] < 50 ) {
			return array(
				'status' => 'unknown',
				/* translators: %d: page views */
				'note'   => sprintf( __( '%d mobile page views measured so far; about 50 are needed for a stable result.', 'shd-performance' ), $mobile['count'] ),
			);
		}
		$pass = 'good' === Rum::rating( 'lcp', $mobile['lcp'] ) && 'good' === Rum::rating( 'inp', $mobile['inp'] ) && 'good' === Rum::rating( 'cls', $mobile['cls'] );
		return array(
			'status' => $pass ? 'done' : 'open',
			'note'   => $pass ? __( 'Mobile LCP, INP and CLS are all good.', 'shd-performance' ) : __( 'At least one mobile Core Web Vital is not good yet.', 'shd-performance' ),
		);
	}
}
