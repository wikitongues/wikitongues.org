/**
 * Site banner (plan 1.4): push site_banner_click to the data layer; GTM
 * forwards it to GA4. Donation-form clicks also carry data-cta-location, so
 * they count in donate_cta_click too.
 */
( function () {
	document.addEventListener( 'click', function ( event ) {
		var link = event.target.closest ? event.target.closest( '.wt_site-banner__button' ) : null;
		if ( ! link ) {
			return;
		}
		var banner = link.closest( '[data-site-banner]' );
		window.dataLayer = window.dataLayer || [];
		window.dataLayer.push( {
			event: 'site_banner_click',
			banner_id: banner ? banner.getAttribute( 'data-site-banner' ) : '',
			cta_type: banner ? banner.getAttribute( 'data-site-banner-cta' ) : '',
		} );
	} );
}() );
