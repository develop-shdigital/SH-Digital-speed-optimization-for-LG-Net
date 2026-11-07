<?php
/**
 * Cookiez, the cookie banner of the site.
 *
 * banner.js is a webpack bundle. When the banner is drawn it loads the texts of the page's
 * language as a separate file ("lang-de-json.js?ver=…", chosen by cookiezBannerSettings.language).
 * The file name and its version hash are read from the local banner.js, so the file can be
 * preloaded together with the page instead of after the banner script has run.
 *
 * @package SHD\Performance
 */

namespace SHD\Performance;

defined( 'ABSPATH' ) || exit;

final class Consent {

	/** Cookie in which Cookiez stores the visitor's choice; with it the banner is not shown. */
	const COOKIE = 'cookiez-user-consent';

	/** Languages written in Latin script (the banner then uses the Latin file of Noto Sans). */
	const LATIN = array( 'de', 'en', 'fr', 'it', 'es', 'pt', 'nl', 'da', 'sv', 'nb', 'nn', 'fi', 'et', 'cs', 'sk', 'sl', 'hr', 'hu', 'pl', 'ro', 'tr' );

	/** @var array<string,array<string,string>> Language => file name, per banner.js file. */
	private static $maps = array();

	/**
	 * URL of the language file the banner will load, or '' when it cannot be determined.
	 *
	 * @param string $src  banner.js URL as written in the page.
	 * @param string $lang Language of the banner (cookiezBannerSettings.language).
	 * @return string
	 */
	public static function language_file( $src, $lang ) {
		$lang = strtolower( trim( (string) $lang ) );
		if ( '' === $lang || ! preg_match( '/^[a-z]{2,3}(?:-[a-z]{2,4})?$/', $lang ) ) {
			return '';
		}
		$src  = html_entity_decode( trim( $src ), ENT_QUOTES );
		$url  = 0 === strpos( $src, '//' ) ? ( is_ssl() ? 'https:' : 'http:' ) . $src : $src;
		$path = Fonts::local_path( (string) strtok( $url, '?#' ) );
		if ( null === $path ) {
			return '';
		}
		$map = self::chunk_map( $path );
		if ( ! isset( $map[ $lang ] ) ) {
			return '';
		}
		// webpack's public path is the folder of banner.js (taken from the script's own URL).
		$base = substr( (string) strtok( $url, '?#' ), 0, strrpos( (string) strtok( $url, '?#' ), '/' ) + 1 );
		return $base . $map[ $lang ];
	}

	/**
	 * URL of the Noto Sans file the banner uses for a language written in Latin script, or ''.
	 *
	 * @param string $src  banner.js URL as written in the page.
	 * @param string $lang Language of the banner.
	 * @return string
	 */
	public static function font_file( $src, $lang ) {
		if ( ! in_array( strtolower( substr( (string) $lang, 0, 2 ) ), self::LATIN, true ) ) {
			return '';
		}
		$src  = html_entity_decode( trim( $src ), ENT_QUOTES );
		$url  = (string) strtok( 0 === strpos( $src, '//' ) ? ( is_ssl() ? 'https:' : 'http:' ) . $src : $src, '?#' );
		$path = Fonts::local_path( $url );
		if ( null === $path ) {
			return '';
		}
		$map = self::chunk_map( $path );
		return isset( $map['@font'] ) ? substr( $url, 0, strrpos( $url, '/' ) + 1 ) . $map['@font'] : '';
	}

	/**
	 * Language => "lang-de-json.js?ver=<hash>" from a banner.js file (cached per file version),
	 * plus "@font" => "fonts/NotoSans-latin.<hash>.woff2" when the bundle names that file.
	 *
	 * @param string $path Local path of banner.js.
	 * @return array<string,string>
	 */
	public static function chunk_map( $path ) {
		$key = md5( $path . '|' . (int) @filemtime( $path ) . '|' . (int) @filesize( $path ) . '|' . SHD_PERF_VERSION ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( isset( self::$maps[ $key ] ) ) {
			return self::$maps[ $key ];
		}
		$cached = get_transient( 'shd_perf_cookiez_' . $key );
		if ( is_array( $cached ) ) {
			self::$maps[ $key ] = $cached;
			return $cached;
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local plugin file.
		$code = filesize( $path ) <= 2 * MB_IN_BYTES ? (string) file_get_contents( $path ) : '';
		$map  = self::parse( $code );
		set_transient( 'shd_perf_cookiez_' . $key, $map, WEEK_IN_SECONDS );
		self::$maps[ $key ] = $map;
		return $map;
	}

	/**
	 * Reads webpack's chunk tables:
	 *   "./de.json":[95310,[5708]]                 language file => chunk id
	 *   .u=e=>({…,5708:"lang-de-json",…})[e]+".js?ver="+{…,5708:"<hash>",…}[e]
	 *
	 * @param string $code banner.js.
	 * @return array<string,string>
	 */
	public static function parse( $code ) {
		$map = array();
		if ( '' === $code || ! preg_match( '/\.u\s*=\s*\w+\s*=>\s*\(?\{([^{}]*)\}\)?\[\w+\]\s*\+\s*"\.js\?ver="\s*\+\s*\{([^{}]*)\}\[\w+\]/', $code, $tables ) ) {
			return $map;
		}
		if ( preg_match( '#fonts/NotoSans-latin\.[0-9a-f]+\.woff2#', $code, $font ) ) {
			$map['@font'] = $font[0];
		}
		$names    = self::pairs( $tables[1] );
		$versions = self::pairs( $tables[2] );
		if ( ! preg_match_all( '/"\.\/([a-z]{2,3}(?:-[a-z]{2,4})?)\.json"\s*:\s*\[\s*\d+\s*,\s*\[\s*(\d+)\s*\]\s*\]/i', $code, $files, PREG_SET_ORDER ) ) {
			return $map;
		}
		foreach ( $files as $file ) {
			$id = $file[2];
			if ( isset( $names[ $id ], $versions[ $id ] ) && preg_match( '/^[a-z0-9_-]+$/i', $names[ $id ] ) && preg_match( '/^[a-f0-9]+$/i', $versions[ $id ] ) ) {
				$map[ strtolower( $file[1] ) ] = $names[ $id ] . '.js?ver=' . $versions[ $id ];
			}
		}
		return $map;
	}

	/**
	 * @param string $object Inside of a JS object literal with numeric keys and string values.
	 * @return array<string,string>
	 */
	private static function pairs( $object ) {
		$pairs = array();
		if ( preg_match_all( '/(\d+)\s*:\s*"([^"]*)"/', $object, $m, PREG_SET_ORDER ) ) {
			foreach ( $m as $pair ) {
				$pairs[ $pair[1] ] = $pair[2];
			}
		}
		return $pairs;
	}
}
