<?php
/**
 * Feature registry and stored settings.
 *
 * Every automatic optimization is a feature with one of three states:
 * - off:  never applied.
 * - test: applied only in the preview (admins with "Test view" on, or the secret preview link).
 * - live: applied for every visitor.
 *
 * @package SHD\Performance
 */

namespace SHD\Performance;

defined( 'ABSPATH' ) || exit;

final class Settings {

	const OPTION = 'shd_perf_settings';

	const STATES = array( 'off', 'test', 'live' );

	/** @var array|null */
	private static $cache = null;

	/**
	 * Feature definitions shown on the Optimizations tab.
	 *
	 * @return array<string,array>
	 */
	public static function features() {
		return array(
			'animations'          => array(
				'group'   => 'content',
				'default' => 'live',
				'risk'    => 'low',
				'title'   => __( 'Show the header and the first screen immediately (no fade-in)', 'shd-performance' ),
				'fixes'   => __( 'SH Motion fades headings, texts, images, buttons and list items in as they scroll into view. It did this for the header and the first screen too: they were painted, hidden by its script when the page had been read and faded in over 0.7 s, on every page view (the logo zoomed in each time). Elements in the header and the first screen now carry SH Motion’s own opt-out (data-fx="none") and stay visible from the first frame; everything further down still fades in as designed. Elementor entrance animations (none on the site today) are shown at once in the same way. The opening animation of the mobile menu is not touched.', 'shd-performance' ),
			),
			'lcp_image'           => array(
				'group'   => 'content',
				'default' => 'live',
				'risk'    => 'low',
				'title'   => __( 'Prioritize the main image', 'shd-performance' ),
				'fixes'   => __( 'Picks the largest image in the first screen (or the post’s featured image), removes lazy-loading from it and gives it fetchpriority="high". Takes the high priority away from the header logo, which is printed twice on every page and competed with the main image. Background images of the first sections are preloaded from the top of the page, because the browser otherwise finds them only after reading the CSS that names them. Phones and desktops are handled separately: a section shown only on phones gets its image preloaded for phones, and an image hidden on phones is never downloaded there.', 'shd-performance' ),
			),
			'image_sizes'         => array(
				'group'   => 'content',
				'default' => 'live',
				'risk'    => 'low',
				'title'   => __( 'Download images at the size they are shown', 'shd-performance' ),
				'fixes'   => __( 'Adds sizes="auto" to lazy-loaded images so the browser picks the smallest file from the srcset that is still sharp at the displayed size. Nothing is re-compressed, so image quality is unchanged.', 'shd-performance' ),
			),
			'image_dimensions'    => array(
				'group'   => 'layout',
				'default' => 'live',
				'risk'    => 'low',
				'title'   => __( 'Reserve the space of images while they load', 'shd-performance' ),
				'fixes'   => __( 'The site’s custom CSS (Elementor → Site Settings → Custom CSS) contains img { aspect-ratio: unset !important; }. It switches off the space browsers reserve for an image from its width and height, so every image is 0 px high until its file has arrived, and everything below it jumps down when it does (layout shift 0.21 on /mobile-abo-vergleich/ on computers in Lighthouse). Images with a width and height get that space back while they load; once loaded they are sized exactly as before, because the space is only a placeholder (aspect-ratio: auto). Images without dimensions get their real width and height.', 'shd-performance' ),
			),
			'hero_steady'         => array(
				'group'   => 'layout',
				'default' => 'live',
				'risk'    => 'low',
				'title'   => __( 'Homepage hero slider: no jumps on phones', 'shd-performance' ),
				'fixes'   => __( 'On phones the hero leaves room for the header that floats over it. The LGnet Hero Slider measures that room only when the page has been read, after the browser has painted the hero without it, so the hero jumped down by 72 px; Elementor then starts the slider a second time, which removes the room and measures again: two more jumps. Each jump counts as a layout shift of about 0.09 for Google (0.26 together, more than the 0.1 limit). The room is now measured the same way right where the hero starts, before it is painted, and kept while the slider is started again. The slider still measures by itself afterwards (rotation, resize). The look is unchanged.', 'shd-performance' ),
			),
			'lazy_backgrounds'    => array(
				'group'   => 'content',
				'default' => 'live',
				'risk'    => 'low',
				'title'   => __( 'Load images, background images and carousel slides further down when they come into view', 'shd-performance' ),
				'fixes'   => __( 'Browsers download every CSS background image and every carousel slide during page load, even far below the screen, and every image without loading="lazy". The post cards on /ratgeber/ and under the articles show the full-size featured images (the PNG of “Telefonanbieter Schweiz” alone is 1.9 MB), and on /ratgeber/ all six card images shared the connection with the main image. Images and backgrounds of post cards after the first row, of sections below the first screen and of the footer, and the slides of carousels below the first section, now load when they come near the screen (images with the browser’s own lazy loading; not in the mobile menu, popups, tabs or accordions, which would only start loading when opened). The mobile menu’s background loads after the page has loaded or on the first tap. Elementor already does this for backgrounds from the 4th section on; this extends it. Image files and quality are unchanged.', 'shd-performance' ),
			),
			'lazy_iframes'        => array(
				'group'   => 'content',
				'default' => 'live',
				'risk'    => 'low',
				'title'   => __( 'Lazy-load iframes below the first screen', 'shd-performance' ),
				'fixes'   => __( 'Not used on the site today; applies by itself when a map, a booking tool or another embed is added. Embeds load when they come near the viewport instead of during page load. Iframes from other sites start on the visitor’s first scroll, tap, key press or mouse movement and then load when they are half a screen away; without any interaction they start after the timeout of the delayed scripts (10 s). The iframe keeps its size, so nothing moves.', 'shd-performance' ),
			),
			'lazy_videos'         => array(
				'group'   => 'content',
				'default' => 'live',
				'risk'    => 'low',
				'title'   => __( 'Start autoplaying videos further down when they come into view', 'shd-performance' ),
				'fixes'   => __( 'Not used on the site today; applies by itself when an Elementor video widget plays automatically below the first screen. Such a video then loads and starts when it comes within about one screen of view instead of during page load. The video box keeps its size, so nothing moves.', 'shd-performance' ),
			),
			'youtube_facade'      => array(
				'group'   => 'third-party',
				'default' => 'live',
				'risk'    => 'low',
				'title'   => __( 'Click-to-play YouTube previews', 'shd-performance' ),
				'fixes'   => __( 'Not used on the site today; applies by itself when a YouTube video is added. Elementor’s YouTube widget loads the full player (about 1 MB) before anyone presses play. This shows the video thumbnail with a play button instead; the player loads on click and starts playing with that click.', 'shd-performance' ),
			),
			'recaptcha_on_demand' => array(
				'group'   => 'third-party',
				'default' => 'live',
				'risk'    => 'low',
				'title'   => __( 'Load reCAPTCHA when a form is used', 'shd-performance' ),
				'fixes'   => __( 'Not used on the site today; applies by itself when reCAPTCHA v3 is switched on for the Elementor forms. It then loads as soon as a visitor focuses or touches a form instead of with every page. A submit that happens before it is ready is held until the token exists, so spam protection is unchanged.', 'shd-performance' ),
			),
			'delay_tracking'      => array(
				'group'   => 'third-party',
				'default' => 'live',
				'risk'    => 'low',
				'title'   => __( 'Start tracking scripts after the first interaction', 'shd-performance' ),
				'fixes'   => __( 'No tracking script is on the site today. When Google Analytics, Google Tag Manager, Meta, LinkedIn, TikTok, Hotjar, Clarity or another script from the list in the advanced settings is added, it starts on the first scroll, tap, click or key press, or after the timeout below, instead of competing with the page load.', 'shd-performance' ),
			),
			'cookie_banner'       => array(
				'group'   => 'third-party',
				'default' => 'live',
				'risk'    => 'low',
				'title'   => __( 'Show the cookie banner as soon as the page has been read', 'shd-performance' ),
				'fixes'   => __( 'Cookiez, the cookie banner, waits for the browser’s “load” event, that is until every image, font and script of the page has arrived, then downloads its texts and draws the banner. The banner covers half of a phone screen, so its text is the largest element of most pages, and Google measures the page as loaded only when it appears (Largest Contentful Paint up to 4.1 s on phones in Lighthouse). Its language file is now downloaded together with the page. As soon as “Stop JavaScript from blocking the first paint” or “Start all non-essential JavaScript on the first interaction” is active too, the banner script runs when the page has been read (defer), before the other scripts, which Cookiez treats like a page that has finished loading (Largest Contentful Paint about 2 s instead of 4 s on phones in Lighthouse). On its own that would not help: the scripts would still run first, and on pages like /kontakt/ the earlier banner would replace the photo as the largest element. Consent, texts and design are unchanged; the banner script is never delayed until an interaction.', 'shd-performance' ),
			),
			'async_widgets'       => array(
				'group'   => 'third-party',
				'default' => 'live',
				'risk'    => 'low',
				'title'   => __( 'Load the accessibility button without stopping the page', 'shd-performance' ),
				'fixes'   => __( 'Ally’s accessibility widget (cdn.elementor.com/a11y/widget.js, 46 KB) is a blocking script at the end of every page: the browser stops reading the page until it has connected to Elementor’s server, downloaded and run it, and only then starts the scripts after it (Elementor, the menu, the cookie banner). It now downloads in parallel (async) and appears as soon as it has arrived, as before.', 'shd-performance' ),
			),
			'dedupe_scripts'      => array(
				'group'   => 'javascript',
				'default' => 'live',
				'risk'    => 'low',
				'title'   => __( 'Load duplicate scripts only once', 'shd-performance' ),
				'fixes'   => __( 'Identical script files printed twice by plugins are loaded once.', 'shd-performance' ),
			),
			'defer_scripts'       => array(
				'group'   => 'javascript',
				'default' => 'test',
				'risk'    => 'medium',
				'title'   => __( 'Stop JavaScript from blocking the first paint', 'shd-performance' ),
				'fixes'   => __( 'jQuery and jQuery Migrate are loaded in the <head> and hold back the first paint. This loads them and the remaining scripts (Elementor, SH Motion, the hero slider, the language switcher) with defer and keeps their order; inline scripts that depend on them are deferred too. Everything still runs before the page’s DOMContentLoaded event. Check the Test view (menu, language switcher, slider, forms, order wizard) before switching to Live.', 'shd-performance' ),
			),
			'delay_all_scripts'   => array(
				'group'   => 'javascript',
				'default' => 'test',
				'risk'    => 'medium',
				'title'   => __( 'Start all non-essential JavaScript on the first interaction', 'shd-performance' ),
				'fixes'   => __( 'Biggest win for Total Blocking Time on phones: Elementor, jQuery and plugin scripts start on the first scroll, tap, click or key press, or after the timeout, in their original order. Taps on menus, buttons and forms made before that are replayed once the scripts are ready. The site’s own script files are pre-downloaded a few seconds after loading, so a later first tap does not wait for the network. The hero slider starts turning and the accessibility button appears after the first interaction; the cookie banner is never delayed. Check menus, the language switcher, the slider, forms and the order wizard in the Test view before switching to Live.', 'shd-performance' ),
			),
			'combine_css'         => array(
				'group'   => 'css',
				'default' => 'live',
				'risk'    => 'low',
				'title'   => __( 'Load the page’s stylesheets as one file', 'shd-performance' ),
				'fixes'   => __( 'WordPress links every plugin’s and every Elementor widget’s stylesheet separately, and nothing appears until all of them have arrived: 17 to 19 files per page here, plus 100 to 235 KB of CSS that Elementor writes into each page. They are joined, with the inline styles between them, in exactly the same order into one cached file, so the page looks the same. Stylesheets that could behave differently when joined stay separate (other sites, print styles, @import, Elementor’s Swiper, lightbox and dialog styles, which Elementor loads itself).', 'shd-performance' ),
			),
			'block_css'           => array(
				'group'   => 'css',
				'default' => 'live',
				'risk'    => 'low',
				'title'   => __( 'Leave out WordPress’s block styles on pages without blocks', 'shd-performance' ),
				'fixes'   => __( 'WordPress links its block library stylesheet (141 KB, one of the files the browser waits for before it paints anything) when a page’s content contains blocks, even if Elementor shows that page and no block appears on it: /tv/ loads it this way. It is now left out on pages whose HTML has no block markup at all (no wp-block-… element, no block layout, colour, font-size or alignment classes); pages with blocks keep it.', 'shd-performance' ),
			),
			'fonts'               => array(
				'group'   => 'fonts',
				'default' => 'live',
				'risk'    => 'low',
				'title'   => __( 'Fonts: size-matched fallback', 'shd-performance' ),
				'fixes'   => __( 'Archivo and Public Sans come from Elementor’s local copies of Google Fonts and are already WOFF2 with font-display: swap, so the text is first drawn in another font. While they load, the text now uses Arial scaled to each font (Archivo, Public Sans and Work Sans, also in the stylesheets of the hero slider and the other LGnet plugins). The sizes were tuned on the site’s own texts so that headings break into the same lines before and after the font arrives. Fonts are found by themselves; the Fonts tab shows them and can preload chosen weights (off: preloads delayed the main image on phones).', 'shd-performance' ),
			),
			'google_fonts'        => array(
				'group'   => 'fonts',
				'default' => 'live',
				'risk'    => 'low',
				'title'   => __( 'Serve Google Fonts from this site', 'shd-performance' ),
				'fixes'   => __( 'HTML widgets on /mobile/, /tv/ and /mobile-abo-vergleich/ (and their translations) load Work Sans from fonts.googleapis.com: a stylesheet in the middle of the page that holds back everything after it (the cookie banner included) until two Google servers have answered, and that sends every visitor’s IP address to Google. The stylesheet and its font files are copied to this site once (uploads/shd-performance/fonts/google) and served from there; the text looks the same. Until the copy is ready the original link stays.', 'shd-performance' ),
			),
			'browser_cache'       => array(
				'group'   => 'domain',
				'default' => 'live',
				'risk'    => 'low',
				'title'   => __( 'Browser cache lifetimes for CSS, scripts, fonts and images', 'shd-performance' ),
				'fixes'   => __( 'The server sends no cache lifetime (Cache-Control or Expires) for the site’s files, so browsers keep them only for a short guessed time and ask the server again on later page views; files Elementor updates get almost no caching at all. The web server now sends lifetimes by file type: CSS, JavaScript and fonts a year (WordPress and Elementor give them a new address, ?ver=…, whenever they change), images and videos 30 days. Pages themselves are not affected. Written as rules at the top of .htaccess, checked right after writing and removed again if the site does not answer.', 'shd-performance' ),
			),
			'domain_hygiene'      => array(
				'group'   => 'domain',
				'default' => 'live',
				'risk'    => 'low',
				'title'   => __( 'robots.txt and redirects of the other addresses', 'shd-performance' ),
				'fixes'   => __( 'Applies to the addresses listed in the advanced settings (none yet): sitemap lines in robots.txt that point to one of them are removed (the file is backed up first, nothing else in it changes), and requests for them that reach this server are sent to the same page on the site’s address by the web server in one step, before WordPress starts. Certificate checks (/.well-known/) are left alone; the rules are checked right after they are written and removed again if the site does not answer.', 'shd-performance' ),
			),
			'emoji'               => array(
				'group'   => 'javascript',
				'default' => 'live',
				'risk'    => 'low',
				'title'   => __( 'Disable the WordPress emoji script', 'shd-performance' ),
				'fixes'   => __( 'Modern browsers display emoji natively; the detection script and its extra download are not needed.', 'shd-performance' ),
			),
			'jquery_migrate'      => array(
				'group'   => 'javascript',
				'default' => 'test',
				'risk'    => 'medium',
				'title'   => __( 'Remove jQuery Migrate', 'shd-performance' ),
				'fixes'   => __( 'jQuery Migrate only keeps very old plugin code working; on phones it showed up in Lighthouse as a long task of about 120 ms on every page. Check the Test view (browser console, menu, slider, forms, order wizard) before switching to Live.', 'shd-performance' ),
			),
			'toc_prefill'         => array(
				'group'   => 'layout',
				'default' => 'live',
				'risk'    => 'low',
				'title'   => __( 'Blog table of contents: filled in from the start', 'shd-performance' ),
				'fixes'   => __( 'Elementor Pro fills the table of contents of the articles with JavaScript after the page has loaded, so the box grows and pushes the article down. The same list is now written into the page, so the box has its final size from the first paint. It looks and works as before.', 'shd-performance' ),
			),
			'preconnect'          => array(
				'group'   => 'third-party',
				'default' => 'live',
				'risk'    => 'low',
				'title'   => __( 'Connect early to required third parties', 'shd-performance' ),
				'fixes'   => __( 'Adds preconnect hints for third-party scripts that still load during page load (Ally’s widget on cdn.elementor.com).', 'shd-performance' ),
			),
			'a11y_names'          => array(
				'group'   => 'agents',
				'default' => 'live',
				'risk'    => 'low',
				'title'   => __( 'Accessibility: link names, list roles, skip link, heading levels, logo text', 'shd-performance' ),
				'fixes'   => __( 'Links that contain only an icon (the phone and menu buttons in the header, the back-to-top arrow) give screen readers, AI agents and Lighthouse (“Links must have discernible text”) no name. They get one from what they do, in the language of the page: “Menü öffnen”, “Nach oben”, a phone number, an e-mail address, the social network or the title of the linked page. Links with text are never renamed. Elementor Pro marks its loop grid and loop carousel as a list without list items: the cards become list items and the carousel loses the wrong list role. Headings that skip levels keep their tag and look and get the level they have in the outline (aria-level). A logo whose alternative text is its file name (“logo-mark-dark”) gets the brand name from the advanced settings (“LGnet”). Nothing visible changes.', 'shd-performance' ),
			),
			'agent_probes'        => array(
				'group'   => 'agents',
				'default' => 'live',
				'risk'    => 'low',
				'title'   => __( 'Answer AI catalogue look-ups at once', 'shd-performance' ),
				'fixes'   => __( 'Lighthouse (“Agentic browsing”) and AI agents look for /.well-known/ard.json and /.well-known/ai-catalog.json, files this site does not publish. WordPress needs about a second to build its “page not found” page for them. They now get a short “not found” immediately and the check counts as not applicable. Switch this off only if another plugin publishes such a catalogue.', 'shd-performance' ),
			),
			'rum'                 => array(
				'group'   => 'monitoring',
				'default' => 'live',
				'risk'    => 'low',
				'title'   => __( 'Measure real-visitor Core Web Vitals', 'shd-performance' ),
				'fixes'   => __( 'Records LCP, INP, CLS, FCP and TTFB from real visits (no cookies; no IP address or user agent stored) and shows the 75th percentile on the Web Vitals tab. Google has no real-visitor data for this site yet, so this is how you can see whether the Core Web Vitals pass.', 'shd-performance' ),
			),
		);
	}

	/**
	 * Default option values (feature states plus feature options).
	 *
	 * @return array
	 */
	public static function defaults() {
		$states = array();
		foreach ( self::features() as $id => $feature ) {
			$states[ $id ] = $feature['default'];
		}

		return array(
			'states'                => $states,
			'first_screen_sections' => 2,
			'animation_mode'        => 'all',
			'toc_phones'            => 'expanded',
			'delay_timeout'         => 10,
			'tracking_patterns'     => array(
				'googletagmanager.com/gtag/js',
				'googletagmanager.com/gtm.js',
				'google-analytics.com/analytics.js',
				'google-analytics-for-wordpress/assets/js/frontend-gtag',
				'scripts.simpleanalyticscdn.com',
				'/shd-tracking-manager/',
				'/shd-analytics/',
				'connect.facebook.net',
				'snap.licdn.com',
				'static.hotjar.com',
				'analytics.tiktok.com',
				'cdn.leadinfo.net',
				'bzrcdn.openai.com',
				'clarity.ms',
			),
			'delay_exclusions'      => array(),
			'keep_inline'           => array(
				'lazyloadRunObserver',
				'dataLayer',
			),
			// Cookie banner scripts: never delayed; Cookiez's banner is started when the page has been read.
			'consent_scripts'       => array(
				'/plugins/cookiez/',
			),
			// Third-party widgets that are loaded with async instead of blocking the page.
			'async_scripts'         => array(
				'cdn.elementor.com/a11y/widget.js',
			),
			'brand_name'            => 'LGnet',
			'youtube_label'         => '',
			'rum_sample'            => 100,
			'redirect_domains'      => array(),
			'page_cache'            => true,
			'cache_ttl'             => 10,
			'cache_campaign'        => false,
			'cache_brotli'          => true,
			'cache_exclude'         => array(),
			'preview_token'         => '',
			'fonts'                 => array(
				'faces'   => array(),
				'woff2'   => array(),
				// Size-matched Arial fallbacks. Vertical metrics from the fonts' TrueType files (Google
				// Fonts) with Font_Metrics::fallback_overrides(), the same line heights at every size.
				// The widths are tuned on the site's own texts (15 pages, phone and desktop, November
				// 2026): the size at which the fewest texts break into a different number of lines
				// than in the web font. Values measured from the font files (weight 400) made the
				// Archivo headings, which are all 600/700, 5-7 % too narrow: "Wählen Sie Ihr
				// Internet-Abo" on /internet/ fitted on one line until Archivo arrived.
				// "weights": extra faces (Arial Bold from 600) for Public Sans buttons and menus.
				// Archivo and Work Sans keep one face, which the browser makes bold: with Arial Bold,
				// headings limited to a width in "ch" (digit widths) broke differently.
				// Detection on the Fonts tab replaces the regular face when it finds TrueType sources.
				'metrics' => array(
					'archivo'     => array(
						'family'      => 'Archivo',
						'size_adjust' => 105.4,
						'ascent'      => 83.31,
						'descent'     => 19.93,
						'line_gap'    => 0,
					),
					'public sans' => array(
						'family'      => 'Public Sans',
						'size_adjust' => 104,
						'ascent'      => 91.35,
						'descent'     => 21.64,
						'line_gap'    => 0,
						'weights'     => array(
							600 => 101,
							700 => 100.56,
						),
					),
					'work sans'   => array(
						'family'      => 'Work Sans',
						'size_adjust' => 107.5,
						'ascent'      => 86.52,
						'descent'     => 22.61,
						'line_gap'    => 0,
					),
				),
				// No font preloads by default: Archivo and Public Sans already swap in with a
				// size-matched fallback, and preloading them (62 KB at high priority) delayed the
				// main image on slow phones (Lighthouse, November 2026).
				'preload' => array(),
			),
			// Preload the cookie banner's font (Noto Sans, 36 KB) for visitors who have not chosen
			// yet: avoids the banner growing by 22 px when the font arrives, but competes with the
			// main image on slow phones.
			'cookie_banner_font'    => false,
		);
	}

	/**
	 * Stored settings merged over the defaults.
	 *
	 * @return array
	 */
	public static function get() {
		if ( null !== self::$cache ) {
			return self::$cache;
		}

		$stored   = get_option( self::OPTION, array() );
		$stored   = is_array( $stored ) ? $stored : array();
		$defaults = self::defaults();
		$settings = array_merge( $defaults, $stored );

		$settings['states'] = array_merge( $defaults['states'], isset( $stored['states'] ) && is_array( $stored['states'] ) ? $stored['states'] : array() );
		$settings['fonts']  = array_merge( $defaults['fonts'], isset( $stored['fonts'] ) && is_array( $stored['fonts'] ) ? $stored['fonts'] : array() );

		foreach ( $settings['states'] as $id => $state ) {
			if ( ! in_array( $state, self::STATES, true ) ) {
				$settings['states'][ $id ] = isset( $defaults['states'][ $id ] ) ? $defaults['states'][ $id ] : 'off';
			}
		}

		self::$cache = $settings;
		return $settings;
	}

	/**
	 * Merge and store new settings.
	 *
	 * @param array $changes Partial settings.
	 */
	public static function update( array $changes ) {
		$current = self::get();
		foreach ( $changes as $key => $value ) {
			if ( in_array( $key, array( 'states', 'fonts' ), true ) && is_array( $value ) ) {
				$current[ $key ] = array_merge( $current[ $key ], $value );
			} else {
				$current[ $key ] = $value;
			}
		}
		update_option( self::OPTION, $current, true );
		self::$cache = null;
	}

	/**
	 * @param string $feature Feature id.
	 * @return string off|test|live
	 */
	public static function state( $feature ) {
		$settings = self::get();
		return isset( $settings['states'][ $feature ] ) ? $settings['states'][ $feature ] : 'off';
	}

	/**
	 * Secret token for the anonymous preview link, created on first use.
	 *
	 * @return string
	 */
	public static function preview_token() {
		$settings = self::get();
		if ( empty( $settings['preview_token'] ) ) {
			$token = wp_generate_password( 20, false, false );
			self::update( array( 'preview_token' => $token ) );
			return $token;
		}
		return $settings['preview_token'];
	}

	/**
	 * Drop the in-memory copy (used after direct option changes).
	 */
	public static function flush() {
		self::$cache = null;
	}
}
