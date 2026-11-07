/**
 * SHD Performance: the LGnet hero slider without jumps on phones.
 *
 * On phones the hero leaves room for the header that floats over it. The slider
 * measures that room (--lgnet-hero-header-h) only when the page has been read, after
 * the browser has already painted the hero without it, so the hero jumped down by the
 * header's height (72 px). Elementor then starts the slider a second time, which first
 * removes the room and measures again: two more jumps.
 *
 * This runs at the start of the hero (this script is its first child): it measures the
 * room the same way the slider does (assets/hero-slider.js, measureHeader()) before
 * anything of the hero is painted, and keeps the measured value while the slider is
 * started again. The slider still measures on its own afterwards (resize, load, fonts).
 */
( function ( d, w ) {
	'use strict';

	var KEY = '--lgnet-hero-header-h';
	var MAX_HEADER = 240;
	var MAX_HEADER_INSET = 40;
	var script = d.currentScript;
	var root = script && script.parentElement;

	function measure( hero ) {
		if ( ( w.pageYOffset || d.documentElement.scrollTop || 0 ) > 4 || ! d.elementsFromPoint ) {
			return null;
		}
		var width = w.innerWidth;
		var height = w.innerHeight;
		var xs = [ Math.round( width * 0.2 ), Math.round( width * 0.5 ), Math.round( width * 0.8 ) ];
		var ys = [ 2, 10, 20, 34, 50 ];
		var top = hero.getBoundingClientRect().top;
		var overlap = 0;
		for ( var i = 0; i < xs.length; i++ ) {
			for ( var j = 0; j < ys.length; j++ ) {
				var list = d.elementsFromPoint( xs[ i ], ys[ j ] ) || [];
				for ( var k = 0; k < list.length; k++ ) {
					var el = list[ k ];
					if ( hero.contains( el ) || 'wpadminbar' === el.id || 'static' === w.getComputedStyle( el ).position ) {
						continue;
					}
					var box = el.getBoundingClientRect();
					if ( box.height < 8 || box.top - top > MAX_HEADER_INSET || box.height > height * 0.35 ) {
						continue;
					}
					var over = box.bottom - top;
					if ( over > overlap && over <= MAX_HEADER ) {
						overlap = over;
					}
				}
			}
		}
		return Math.max( 0, Math.round( overlap ) );
	}

	if ( root && root.hasAttribute( 'data-hero' ) && ! root.style.getPropertyValue( KEY ) ) {
		try {
			var px = measure( root );
			if ( null !== px ) {
				root.style.setProperty( KEY, px + 'px' );
			}
		} catch ( e ) {
			// The slider measures on its own as before.
		}
	}

	// A second start (Elementor's element_ready) tears the slider down first; keep the
	// measured room meanwhile. Installed once, before hero-slider.js defines the slider.
	if ( w.shdHeroSteady || Object.prototype.hasOwnProperty.call( w, 'LGnetHeroSlider' ) ) {
		return;
	}
	w.shdHeroSteady = true;
	try {
		Object.defineProperty( w, 'LGnetHeroSlider', {
			configurable: true,
			enumerable: true,
			get: function () {
				return undefined;
			},
			set: function ( value ) {
				var slider = value && value.Slider;
				if ( slider && slider.prototype && 'function' === typeof slider.prototype.destroy ) {
					var destroy = slider.prototype.destroy;
					slider.prototype.destroy = function () {
						var el = this.root;
						var kept = el && el.style ? el.style.getPropertyValue( KEY ) : '';
						var result = destroy.apply( this, arguments );
						if ( kept && el && ! el.style.getPropertyValue( KEY ) ) {
							el.style.setProperty( KEY, kept );
						}
						return result;
					};
				}
				Object.defineProperty( w, 'LGnetHeroSlider', { value: value, writable: true, configurable: true, enumerable: true } );
			}
		} );
	} catch ( e ) {
		// Without the wrapper the slider works as before.
	}
}( document, window ) );
