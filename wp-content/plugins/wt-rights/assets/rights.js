/**
 * Language Rights report.
 *
 * - "Get release updates" pushes rights_updates_signup (GTM forwards it to
 *   GA4) and opens the signup dialog; without scripts the link goes to the
 *   newsletter signup.
 * - "Read the white paper" pushes rights_paper_click.
 * - The dialog's form signs up in the page, through Mailchimp's JSONP
 *   endpoint, and pushes rights_updates_subscribed on success. If Mailchimp
 *   can't be reached, the next submit posts the form in a new tab as before.
 * - The sticky "On this page" bar sits under the site's sticky masthead.
 */
( function () {
	'use strict';

	var dialog = document.getElementById( 'wt-rights-updates' );

	document.addEventListener( 'click', function ( event ) {
		var paper = event.target.closest ? event.target.closest( '[data-rights-paper]' ) : null;
		if ( paper ) {
			window.dataLayer = window.dataLayer || [];
			window.dataLayer.push( { event: 'rights_paper_click', cta_location: paper.getAttribute( 'data-rights-paper' ) || '' } );
			return;
		}
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
		signUpInPage( dialog.querySelector( 'form' ), dialog.querySelector( '[role="status"]' ) );
	}

	/**
	 * Submits the signup form to Mailchimp without leaving the page.
	 *
	 * @param {HTMLFormElement|null} form   The dialog's form.
	 * @param {Element|null}         status Where to say what happened.
	 */
	function signUpInPage( form, status ) {
		if ( ! form || ! status || typeof URLSearchParams !== 'function' || typeof FormData !== 'function' ) {
			return;
		}
		var inPage = true;
		form.addEventListener( 'submit', function ( event ) {
			if ( ! inPage ) {
				return; // Mailchimp failed once: post the form in a new tab.
			}
			event.preventDefault();
			var button   = form.querySelector( '[type="submit"]' );
			var callback = 'wtRightsSignup' + Date.now();
			var script   = document.createElement( 'script' );
			var timer;
			function done( message, ok ) {
				clearTimeout( timer );
				delete window[ callback ];
				script.remove();
				button.disabled = false;
				status.textContent = message;
				status.classList.toggle( 'is-error', ! ok );
			}
			window[ callback ] = function ( response ) {
				var ok = response && 'success' === response.result;
				if ( ok ) {
					window.dataLayer = window.dataLayer || [];
					window.dataLayer.push( { event: 'rights_updates_subscribed' } );
					form.reset();
				}
				done( ok ? 'Almost done: check your inbox to confirm your subscription.' : plainText( response && response.msg ), ok );
			};
			function unreachable() {
				inPage = false;
				done( 'We couldn’t reach our mailing list. Press Sign up again to finish in a new tab.', false );
			}
			script.onerror = unreachable;
			timer          = setTimeout( unreachable, 10000 );
			script.src     = form.action.replace( '/post?', '/post-json?' ) + '&' + new URLSearchParams( new FormData( form ) ).toString() + '&c=' + callback;
			button.disabled    = true;
			status.textContent = 'Signing you up…';
			document.body.appendChild( script );
		} );
	}

	/**
	 * Mailchimp's message without its markup or its "0 - " field prefix.
	 *
	 * @param {string|undefined} html The message.
	 * @return {string} Plain text.
	 */
	function plainText( html ) {
		var text = new DOMParser().parseFromString( String( html || '' ), 'text/html' ).body.textContent || '';
		text     = text.replace( /^\d+ - /, '' ).trim();
		return text || 'Something went wrong. Please try again.';
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
