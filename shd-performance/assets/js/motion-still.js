/**
 * SHD Performance: keep what is on screen at load still for SH Motion.
 *
 * SH Motion (motion.js) hides headings, texts, images, buttons and list items when the
 * page has been read and fades them in as they come into view, including the header and
 * the first screen, which visitors have already seen at that moment. Right before it
 * starts, every element it would animate that is on screen gets SH Motion's own opt-out
 * (data-fx="none"). Everything further down still fades in as designed, on each device
 * as far as its first screen reaches. The off-canvas menu and popups keep their animations.
 */
( function ( d, w ) {
	'use strict';

	// SH Motion's RULES (motion.js).
	var selector = [
		'.elementor-widget-heading .elementor-heading-title',
		'.elementor-widget-text-editor',
		'.elementor-widget-image img',
		'.elementor-widget-image-box',
		'.elementor-widget-button .elementor-button',
		'.elementor-widget-icon-box',
		'.elementor-widget-counter',
		'.elementor-widget-progress',
		'.elementor-icon-list-items > .elementor-icon-list-item',
		'.elementor-widget-testimonial-carousel',
		'.elementor-widget-image-carousel'
	].join( ',' );

	function mark() {
		var height = w.innerHeight || d.documentElement.clientHeight;
		var list = d.querySelectorAll( selector );
		for ( var i = 0; i < list.length; i++ ) {
			var el = list[ i ];
			if ( el.hasAttribute( 'data-fx' ) || ( el.closest && el.closest( '.e-off-canvas, [data-elementor-type="popup"]' ) ) ) {
				continue;
			}
			var box = el.getBoundingClientRect();
			if ( ( box.width || box.height ) && box.bottom > 0 && box.top < height ) {
				el.setAttribute( 'data-fx', 'none' );
			}
		}
	}

	// Before motion.js: during reading, a DOMContentLoaded listener registered first runs
	// first; when both scripts are deferred, this one runs right before it.
	if ( 'loading' === d.readyState ) {
		d.addEventListener( 'DOMContentLoaded', mark );
	} else {
		mark();
	}
}( document, window ) );
