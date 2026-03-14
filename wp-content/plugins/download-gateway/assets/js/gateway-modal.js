/* global gatewaySettings */
( function () {
	'use strict';

	// gatewaySettings is localized by PHP: { nonce, apiUrl, downloadBase }
	if ( typeof gatewaySettings === 'undefined' ) {
		return;
	}

	// -------------------------------------------------------------------------
	// Modal DOM
	// -------------------------------------------------------------------------

	var modal, overlay, form, nameField, emailField, consentField,
		honeypotField, errorMsg, submitBtn, skipBtn, closeBtn;

	function buildModal() {
		overlay = document.createElement( 'div' );
		overlay.id = 'gateway-overlay';
		overlay.setAttribute( 'role', 'dialog' );
		overlay.setAttribute( 'aria-modal', 'true' );
		overlay.setAttribute( 'aria-labelledby', 'gateway-modal-title' );

		modal = document.createElement( 'div' );
		modal.id = 'gateway-modal';

		modal.innerHTML =
			'<button id="gateway-close" aria-label="Close">&times;</button>' +
			'<h2 id="gateway-modal-title">Download this resource</h2>' +
			'<p id="gateway-modal-desc">Share your name and email to download. Wikitongues will occasionally send you updates about our work — you can unsubscribe at any time.</p>' +
			'<form id="gateway-form" novalidate>' +
				'<label for="gateway-name">Name <span aria-hidden="true">*</span></label>' +
				'<input id="gateway-name" name="name" type="text" autocomplete="name" required />' +
				'<label for="gateway-email">Email <span aria-hidden="true">*</span></label>' +
				'<input id="gateway-email" name="email" type="email" autocomplete="email" required />' +
				'<label class="gateway-consent">' +
					'<input id="gateway-consent" name="consent_download" type="checkbox" />' +
					' I agree to receive occasional updates from Wikitongues' +
				'</label>' +
				// Honeypot — hidden from real users
				'<div style="display:none" aria-hidden="true">' +
					'<input id="gateway-hp" name="_hp" type="text" tabindex="-1" autocomplete="off" />' +
				'</div>' +
				'<p id="gateway-error" role="alert" aria-live="assertive"></p>' +
				'<div class="gateway-actions">' +
					'<button id="gateway-submit" type="submit">Download</button>' +
					'<button id="gateway-skip" type="button">Skip &mdash; download without sharing</button>' +
				'</div>' +
			'</form>';

		overlay.appendChild( modal );
		document.body.appendChild( overlay );

		form        = document.getElementById( 'gateway-form' );
		nameField   = document.getElementById( 'gateway-name' );
		emailField  = document.getElementById( 'gateway-email' );
		consentField = document.getElementById( 'gateway-consent' );
		honeypotField = document.getElementById( 'gateway-hp' );
		errorMsg    = document.getElementById( 'gateway-error' );
		submitBtn   = document.getElementById( 'gateway-submit' );
		skipBtn     = document.getElementById( 'gateway-skip' );
		closeBtn    = document.getElementById( 'gateway-close' );
	}

	// -------------------------------------------------------------------------
	// Modal open / close
	// -------------------------------------------------------------------------

	var currentPostId = null;
	var currentDirectUrl = null;

	function openModal( postId, policy, directUrl ) {
		if ( ! modal ) {
			buildModal();
			attachModalEvents();
		}

		currentPostId    = postId;
		currentDirectUrl = directUrl;

		errorMsg.textContent = '';
		form.reset();
		setLoading( false );

		// Skip button only shown for soft gate
		skipBtn.style.display = policy === 'soft' ? '' : 'none';
		closeBtn.style.display = policy === 'soft' ? '' : 'none';

		overlay.classList.add( 'is-open' );
		nameField.focus();
	}

	function closeModal() {
		overlay.classList.remove( 'is-open' );
		currentPostId    = null;
		currentDirectUrl = null;
	}

	function setLoading( loading ) {
		submitBtn.disabled = loading;
		submitBtn.textContent = loading ? 'Downloading\u2026' : 'Download';
	}

	function showError( msg ) {
		errorMsg.textContent = msg;
	}

	// -------------------------------------------------------------------------
	// Event wiring
	// -------------------------------------------------------------------------

	function attachModalEvents() {
		// Close on overlay click (soft gate only — hard gate has no close btn)
		overlay.addEventListener( 'click', function ( e ) {
			if ( e.target === overlay && skipBtn.style.display !== 'none' ) {
				closeModal();
			}
		} );

		closeBtn.addEventListener( 'click', closeModal );

		skipBtn.addEventListener( 'click', function () {
			var url = currentDirectUrl;
			closeModal();
			redirect( url );
		} );

		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			handleSubmit();
		} );
	}

	// -------------------------------------------------------------------------
	// Form submission
	// -------------------------------------------------------------------------

	function handleSubmit() {
		var name    = nameField.value.trim();
		var email   = emailField.value.trim();
		var consent = consentField.checked;

		if ( ! name ) {
			showError( 'Please enter your name.' );
			nameField.focus();
			return;
		}
		if ( ! email ) {
			showError( 'Please enter your email address.' );
			emailField.focus();
			return;
		}

		setLoading( true );
		showError( '' );

		var body = new URLSearchParams( {
			post_id:          currentPostId,
			name:             name,
			email:            email,
			consent_download: consent ? '1' : '0',
			nonce:            gatewaySettings.nonce,
			_hp:              honeypotField.value,
		} );

		fetch( gatewaySettings.apiUrl, {
			method:      'POST',
			credentials: 'same-origin',
			headers:     { 'Content-Type': 'application/x-www-form-urlencoded' },
			body:        body.toString(),
		} )
			.then( function ( res ) { return res.json().then( function ( data ) { return { ok: res.ok, data: data }; } ); } )
			.then( function ( result ) {
				setLoading( false );
				if ( ! result.ok ) {
					showError( result.data.message || 'Something went wrong. Please try again.' );
					return;
				}
				var directUrl = currentDirectUrl;
				closeModal();
				var token = result.data.token;
				if ( token ) {
					redirect( gatewaySettings.downloadBase + '/' + token );
				} else {
					// Honeypot hit — silently redirect to direct URL
					redirect( directUrl );
				}
			} )
			.catch( function () {
				setLoading( false );
				showError( 'A network error occurred. Please try again.' );
			} );
	}

	// -------------------------------------------------------------------------
	// Click interception
	// -------------------------------------------------------------------------

	function redirect( url ) {
		window.location.href = url;
	}

	document.addEventListener( 'click', function ( e ) {
		var link = e.target.closest( '.gateway-download-link' );
		if ( ! link ) {
			return;
		}

		var policy = link.dataset.policy;
		if ( ! policy || policy === 'none' ) {
			// No gate — let browser follow the link naturally
			return;
		}

		e.preventDefault();
		openModal( link.dataset.postId, policy, link.href );
	} );
}() );
