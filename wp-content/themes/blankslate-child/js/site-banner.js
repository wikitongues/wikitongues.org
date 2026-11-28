/**
 * Site banner (plan 1.4). Pushes site_banner_view (once per page, when the
 * banner isn't dismissed), site_banner_click and site_banner_dismiss to the
 * data layer; GTM forwards them to GA4. Donation-form clicks also carry
 * data-cta-location, so they count in donate_cta_click too.
 *
 * Dismissing stores the banner ID in localStorage (wt_site_banner_dismissed,
 * a JSON list of the last 20 IDs) and hides it; on later pages the inline
 * head script hides it before paint. A new ID shows again.
 */
( function () {
	var STORE = 'wt_site_banner_dismissed';

	function remember( id ) {
		try {
			var list = JSON.parse( window.localStorage.getItem( STORE ) || '[]' );
			if ( ! Array.isArray( list ) ) {
				list = [];
			}
			if ( list.indexOf( id ) === -1 ) {
				list.push( id );
			}
			window.localStorage.setItem( STORE, JSON.stringify( list.slice( -20 ) ) );
		} catch {
			// Storage blocked: the banner still hides for this page.
		}
	}

	function push( event, banner ) {
		window.dataLayer = window.dataLayer || [];
		window.dataLayer.push( {
			event: event,
			banner_id: banner ? banner.getAttribute( 'data-site-banner' ) : '',
			cta_type: banner ? banner.getAttribute( 'data-site-banner-cta' ) : '',
		} );
	}

	// One view per page, only when the visitor can see it.
	var shown = document.querySelector( '.wt_site-banner' );
	if ( shown && ! document.documentElement.classList.contains( 'wt-site-banner-dismissed' ) ) {
		push( 'site_banner_view', shown );
	}

	document.addEventListener( 'click', function ( event ) {
		if ( ! event.target.closest ) {
			return;
		}
		var link = event.target.closest( '.wt_site-banner__button' );
		if ( link ) {
			push( 'site_banner_click', link.closest( '[data-site-banner]' ) );
			return;
		}
		var close = event.target.closest( '.wt_site-banner__close' );
		if ( ! close ) {
			return;
		}
		var banner = close.closest( '[data-site-banner]' );
		var id = banner ? banner.getAttribute( 'data-site-banner' ) : '';
		if ( id ) {
			remember( id );
		}
		document.documentElement.classList.add( 'wt-site-banner-dismissed' );
		push( 'site_banner_dismiss', banner );
		// Keep keyboard focus on the page once the banner is gone.
		var next = document.querySelector( '.wt_header a' );
		if ( next ) {
			next.focus();
		}
	} );
}() );
