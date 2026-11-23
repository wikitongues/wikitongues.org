/**
 * Language Rights report: pushes rights_updates_signup when a "Get release
 * updates" link is clicked. GTM forwards it to GA4.
 */
( function () {
	'use strict';

	document.addEventListener( 'click', function ( event ) {
		var link = event.target.closest ? event.target.closest( '[data-rights-updates]' ) : null;
		if ( ! link ) {
			return;
		}
		window.dataLayer = window.dataLayer || [];
		window.dataLayer.push( {
			event: 'rights_updates_signup',
			rights_region: link.getAttribute( 'data-rights-updates' ) || '',
		} );
	} );
}() );
