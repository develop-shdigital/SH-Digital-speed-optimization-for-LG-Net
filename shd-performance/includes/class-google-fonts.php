<?php
/**
 * Google Fonts served from this site.
 *
 * HTML widgets that link fonts.googleapis.com (Work Sans on /mobile/, /tv/ and
 * /mobile-abo-vergleich/) make every visitor's browser wait for two Google servers in the
 * middle of the page, and send their IP address to Google. The stylesheet and its WOFF2
 * files are copied once to uploads/shd-performance/fonts/google, in the background, and
 * pages link the copy from then on. Until the copy exists, pages keep the original link.
 *
 * @package SHD\Performance
 */

namespace SHD\Performance;

defined( 'ABSPATH' ) || exit;

final class Google_Fonts {

	const SUBDIR    = 'shd-performance/fonts/google';
	const QUEUE_OPT = 'shd_perf_google_fonts';
	const HOOK      = 'shd_perf_google_fonts';

	/** Google sends WOFF2 only to browsers it knows support it. */
	const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36';

	/** A stylesheet that could not be copied is tried again after this long. */
	const RETRY = DAY_IN_SECONDS;

	public static function register() {
		add_action( self::HOOK, array( __CLASS__, 'process_queue' ) );
	}

	/**
	 * URL of the local copy of a fonts.googleapis.com stylesheet, or null (then the copy is
	 * made in the background and the page keeps the original link this time).
	 *
	 * @param string $href Stylesheet URL as linked (entities decoded).
	 * @return string|null
	 */
	public static function local_url( $href ) {
		$href = self::normalize( $href );
		if ( '' === $href ) {
			return null;
		}
		$name = self::css_name( $href );
		$file = self::dir() . '/' . $name;
		if ( is_file( $file ) ) {
			return self::url() . '/' . $name . '?ver=' . (int) filemtime( $file );
		}
		self::enqueue( $href );
		return null;
	}

	/**
	 * The stylesheet URL with an explicit https scheme, or '' when it is not a Google Fonts CSS URL.
	 *
	 * @param string $href URL.
	 * @return string
	 */
	public static function normalize( $href ) {
		$href = trim( (string) $href );
		if ( 0 === strpos( $href, '//' ) ) {
			$href = 'https:' . $href;
		}
		$href = (string) preg_replace( '#^http://#i', 'https://', $href );
		return preg_match( '#^https://fonts\.googleapis\.com/css2?\?[^\s"\'<>]+$#i', $href ) ? $href : '';
	}

	/**
	 * @param string $href Normalized URL.
	 * @return string File name of the local stylesheet.
	 */
	private static function css_name( $href ) {
		return 'gf-' . substr( md5( $href ), 0, 16 ) . '.css';
	}

	/**
	 * @return string
	 */
	public static function dir() {
		return trailingslashit( wp_get_upload_dir()['basedir'] ) . self::SUBDIR;
	}

	/**
	 * @return string
	 */
	private static function url() {
		return trailingslashit( wp_get_upload_dir()['baseurl'] ) . self::SUBDIR;
	}

	/**
	 * Remember a stylesheet to copy and schedule the copy (once).
	 *
	 * @param string $href Normalized URL.
	 */
	private static function enqueue( $href ) {
		$queue = get_option( self::QUEUE_OPT, array() );
		$queue = is_array( $queue ) ? $queue : array();
		$entry = isset( $queue[ $href ] ) && is_array( $queue[ $href ] ) ? $queue[ $href ] : array( 'tries' => 0, 'last' => 0 );
		if ( $entry['last'] > time() - self::RETRY && $entry['tries'] > 0 ) {
			return;
		}
		if ( ! isset( $queue[ $href ] ) ) {
			$queue[ $href ] = $entry;
			update_option( self::QUEUE_OPT, $queue, false );
		}
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_single_event( time() + 5, self::HOOK );
		}
	}

	/**
	 * Copy every queued stylesheet; pages are rebuilt afterwards so they link the copies.
	 */
	public static function process_queue() {
		$queue = get_option( self::QUEUE_OPT, array() );
		if ( ! is_array( $queue ) || empty( $queue ) ) {
			return;
		}
		$copied = 0;
		foreach ( $queue as $href => $entry ) {
			$entry = is_array( $entry ) ? $entry : array( 'tries' => 0, 'last' => 0 );
			if ( $entry['tries'] > 0 && $entry['last'] > time() - self::RETRY ) {
				continue;
			}
			if ( true === self::copy( $href ) ) {
				unset( $queue[ $href ] );
				++$copied;
				continue;
			}
			$queue[ $href ] = array(
				'tries' => $entry['tries'] + 1,
				'last'  => time(),
			);
		}
		update_option( self::QUEUE_OPT, $queue, false );
		if ( $copied > 0 ) {
			Page_Cache::purge();
		}
	}

	/**
	 * Download a stylesheet and its font files and store them with local URLs.
	 *
	 * @param string $href Normalized URL.
	 * @return true|\WP_Error
	 */
	public static function copy( $href ) {
		$href = self::normalize( $href );
		if ( '' === $href ) {
			return new \WP_Error( 'url', 'Not a Google Fonts stylesheet.' );
		}
		$response = wp_remote_get(
			$href,
			array(
				'timeout'    => 15,
				'user-agent' => self::UA,
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$css = (string) wp_remote_retrieve_body( $response );
		if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) || false === stripos( $css, '@font-face' ) || strlen( $css ) > 512 * KB_IN_BYTES || preg_match( '#<\s*/?\s*(?:style|script)\b#i', $css ) ) {
			return new \WP_Error( 'css', 'Unexpected answer from fonts.googleapis.com.' );
		}
		if ( ! wp_mkdir_p( self::dir() ) ) {
			return new \WP_Error( 'dir', 'The fonts folder could not be created.' );
		}
		if ( ! preg_match_all( '#url\(\s*[\'"]?(https://fonts\.gstatic\.com/[^\'")\s]+)[\'"]?\s*\)#i', $css, $urls ) ) {
			return new \WP_Error( 'fonts', 'No font files in the stylesheet.' );
		}
		$map = array();
		foreach ( array_unique( $urls[1] ) as $font ) {
			$path = (string) wp_parse_url( $font, PHP_URL_PATH );
			if ( ! preg_match( '#^/[a-z0-9/_.-]+\.woff2$#i', $path ) ) {
				return new \WP_Error( 'fonts', 'Unexpected font file.' );
			}
			// Google's paths are unique per font version: /s/worksans/v24/<hash>.woff2.
			$name   = sanitize_file_name( str_replace( '/', '-', trim( (string) preg_replace( '#^/s/#', '', $path ), '/' ) ) );
			$target = self::dir() . '/' . $name;
			if ( ! is_file( $target ) ) {
				$file = wp_remote_get(
					$font,
					array(
						'timeout'    => 20,
						'user-agent' => self::UA,
					)
				);
				$body = is_wp_error( $file ) ? '' : (string) wp_remote_retrieve_body( $file );
				if ( is_wp_error( $file ) || 200 !== (int) wp_remote_retrieve_response_code( $file ) || 'wOF2' !== substr( $body, 0, 4 ) || strlen( $body ) > 2 * MB_IN_BYTES ) {
					return new \WP_Error( 'font', 'A font file could not be downloaded.' );
				}
				if ( ! self::write( $target, $body ) ) {
					return new \WP_Error( 'write', 'A font file could not be saved.' );
				}
			}
			$map[ $font ] = self::url() . '/' . rawurlencode( $name );
		}
		$local = strtr( $css, $map );
		$local = '/* ' . str_replace( '*/', '', $href ) . " (copy served from this site) */\n" . Css_Combiner::font_display( $local );
		if ( ! self::write( self::dir() . '/' . self::css_name( $href ), $local ) ) {
			return new \WP_Error( 'write', 'The stylesheet could not be saved.' );
		}
		return true;
	}

	/**
	 * @param string $target File.
	 * @param string $data   Content.
	 * @return bool
	 */
	private static function write( $target, $data ) {
		$temp = $target . '.' . wp_generate_password( 6, false, false ) . '.tmp';
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- cache file.
		if ( false === file_put_contents( $temp, $data ) || ! @rename( $temp, $target ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			wp_delete_file( $temp );
			return false;
		}
		@chmod( $target, 0644 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		return true;
	}

	/**
	 * Local copies (for the dashboard).
	 *
	 * @return array<int,array{file:string,size:int,time:int}>
	 */
	public static function copies() {
		$list = array();
		foreach ( (array) glob( self::dir() . '/gf-*.css' ) as $file ) {
			if ( is_string( $file ) ) {
				$list[] = array(
					'file' => basename( $file ),
					'size' => (int) filesize( $file ),
					'time' => (int) filemtime( $file ),
				);
			}
		}
		return $list;
	}

	/**
	 * Delete the copies (they are made again when pages link Google Fonts).
	 */
	public static function clear() {
		foreach ( (array) glob( self::dir() . '/*' ) as $file ) {
			if ( is_string( $file ) && is_file( $file ) ) {
				wp_delete_file( $file );
			}
		}
		delete_option( self::QUEUE_OPT );
	}
}
