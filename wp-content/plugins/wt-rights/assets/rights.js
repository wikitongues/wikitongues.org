/**
 * Language Rights report.
 *
 * - "Get release updates" pushes rights_updates_signup (GTM forwards it to
 *   GA4) and opens the signup dialog; without scripts the link goes to the
 *   newsletter signup.
 * - The sticky "On this page" bar sits under the site's sticky masthead.
 */
( function () {
	'use strict';

	var dialog = document.getElementById( 'wt-rights-updates' );

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
		if ( dialog && typeof dialog.showModal === 'function' ) {
			event.preventDefault();
			dialog.showModal();
		}
	} );

	if ( dialog ) {
		dialog.querySelector( '.wt-rights-dialog__close' ).addEventListener( 'click', function () {
			dialog.close();
		} );
		// A click on the backdrop closes the dialog.
		dialog.addEventListener( 'click', function ( event ) {
			if ( event.target === dialog ) {
				dialog.close();
			}
		} );
	}

	var header = document.querySelector( '.wt_masthead' );
	function setOffset() {
		if ( header ) {
			document.documentElement.style.setProperty( '--wt-rights-header', header.offsetHeight + 'px' );
		}
	}
	setOffset();
	window.addEventListener( 'resize', setOffset );
}() );
