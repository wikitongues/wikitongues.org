/* global gatewaySettings */
( function () {
	'use strict';

	// gatewaySettings is localized by PHP:
	//   { nonce, restNonce, apiUrl, downloadBase, intakeUrl, intakeSteps }
	if ( typeof gatewaySettings === 'undefined' ) {
		return;
	}

	// -------------------------------------------------------------------------
	// Cookie helpers
	// -------------------------------------------------------------------------

	function getCookie( name ) {
		var match = document.cookie.match(
			new RegExp( '(?:^|; )' + name.replace( /[\.$?*|{}\(\)\[\]\\/\+^]/g, '\\$&' ) + '=([^;]*)' )
		);
		return match ? decodeURIComponent( match[ 1 ] ) : null;
	}

	function setCookie( name, value, days ) {
		var expires = new Date( Date.now() + days * 864e5 ).toUTCString();
		document.cookie = name + '=' + encodeURIComponent( value ) +
			'; expires=' + expires + '; path=/; SameSite=Lax';
	}

	// -------------------------------------------------------------------------
	// HTML helpers (used when building intake field markup from PHP-supplied data)
	// -------------------------------------------------------------------------

	function escHtml( str ) {
		return String( str )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' );
	}

	function escAttr( str ) {
		return String( str )
			.replace( /&/g, '&amp;' )
			.replace( /"/g, '&quot;' );
	}

	// -------------------------------------------------------------------------
	// Modal DOM
	// -------------------------------------------------------------------------

	var modal, overlay, gateContainer, form, nameField, emailField, consentField,
		honeypotField, errorMsg, submitBtn, skipBtn, closeBtn;
	var intakeContainer, intakeForm, intakeErrorMsg, intakeSubmitBtn, intakeSkipBtn;
	var loadingContainer;

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

			// ── Step 1: gate form ──────────────────────────────────────────
			'<div id="gateway-gate-container">' +
			'<h2 id="gateway-modal-title">Download this resource</h2>' +
			'<p id="gateway-modal-desc">Share your name and email to download. Wikitongues will occasionally send you updates about our work \u2014 you can unsubscribe at any time.</p>' +
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
					'<button id="gateway-skip" type="button">Download without sharing</button>' +
				'</div>' +
			'</form>' +
			'</div>' +

			// ── Step 2: intake form (hidden until gate passes) ─────────────
			'<div id="gateway-intake-container" style="display:none">' +
			'<h2 id="gateway-intake-title">One more thing</h2>' +
			'<p id="gateway-intake-desc">Help us understand how you\'ll use this resource. This is optional.</p>' +
			'<form id="gateway-intake-form" novalidate></form>' +
			'<p id="gateway-intake-error" role="alert" aria-live="assertive"></p>' +
			'<div class="gateway-actions">' +
				'<button id="gateway-intake-submit" type="button">Submit &amp; Download</button>' +
				'<button id="gateway-intake-skip" type="button">Skip &amp; Download</button>' +
			'</div>' +
			'</div>' +

		// ── Step 3: loading state (shown while resolving download URL) ──────────────
		'<div id="gateway-loading-container" style="display:none">' +
		'<div class="gateway-spinner" aria-hidden="true"></div>' +
		'<p>Preparing your download…</p>' +
		'</div>';

		overlay.appendChild( modal );
		document.body.appendChild( overlay );

		// Gate step
		gateContainer = document.getElementById( 'gateway-gate-container' );
		form          = document.getElementById( 'gateway-form' );
		nameField     = document.getElementById( 'gateway-name' );
		emailField    = document.getElementById( 'gateway-email' );
		consentField  = document.getElementById( 'gateway-consent' );
		honeypotField = document.getElementById( 'gateway-hp' );
		errorMsg      = document.getElementById( 'gateway-error' );
		submitBtn     = document.getElementById( 'gateway-submit' );
		skipBtn       = document.getElementById( 'gateway-skip' );
		closeBtn      = document.getElementById( 'gateway-close' );

		// Intake step
		intakeContainer = document.getElementById( 'gateway-intake-container' );
		intakeForm      = document.getElementById( 'gateway-intake-form' );
		intakeErrorMsg  = document.getElementById( 'gateway-intake-error' );
		intakeSubmitBtn = document.getElementById( 'gateway-intake-submit' );
		intakeSkipBtn   = document.getElementById( 'gateway-intake-skip' );
		loadingContainer = document.getElementById( 'gateway-loading-container' );
	}

	// -------------------------------------------------------------------------
	// Modal open / close
	// -------------------------------------------------------------------------

	var currentPostId      = null;
	var currentPostType    = null;
	var currentDirectUrl   = null;
	var currentIsExternal  = false;

	// Pending state — populated when gate passes and intake step is active.
	var pendingToken          = null;
	var pendingPersonId       = null;
	var pendingPostId         = null;
	var pendingPostType       = null;
	var pendingDirectUrl      = null;
	var pendingIsExternal     = false;

	function openModal( postId, policy, directUrl, postType, isExternal ) {
		if ( ! modal ) {
			buildModal();
			attachModalEvents();
		}

		currentPostId     = postId;
		currentPostType   = postType || '';
		currentDirectUrl  = directUrl;
		currentIsExternal = !! isExternal;

		// Reset to gate step.
		errorMsg.textContent           = '';
		form.reset();
		setLoading( false );
		gateContainer.style.display    = '';
		intakeContainer.style.display  = 'none';
		loadingContainer.style.display = 'none';
		clearPending();

		// Skip shown for soft gate only; close always available.
		skipBtn.style.display  = policy === 'soft' ? '' : 'none';
		closeBtn.style.display = '';

		overlay.classList.add( 'is-open' );
		nameField.focus();
	}

	function closeModal() {
		if ( overlay ) {
			overlay.classList.remove( 'is-open' );
		}
		currentPostId     = null;
		currentPostType   = null;
		currentDirectUrl  = null;
		currentIsExternal = false;
		clearPending();
	}

	function clearPending() {
		pendingToken      = null;
		pendingPersonId   = null;
		pendingPostId     = null;
		pendingPostType   = null;
		pendingDirectUrl  = null;
		pendingIsExternal = false;
	}

	function setLoading( loading ) {
		submitBtn.disabled    = loading;
		submitBtn.textContent = loading ? 'Downloading\u2026' : 'Download';
	}

	function showError( msg ) {
		errorMsg.textContent = msg;
	}

	// -------------------------------------------------------------------------
	// Event wiring
	// -------------------------------------------------------------------------

	function attachModalEvents() {
		// Close on overlay click — in intake step this finishes the download.
		overlay.addEventListener( 'click', function ( e ) {
			if ( e.target !== overlay ) { return; }
			if ( pendingToken !== null ) {
				finishDownload( pendingToken, pendingDirectUrl );
			} else if ( skipBtn.style.display !== 'none' ) {
				closeModal();
			}
		} );

		// Close button — in intake step this finishes the download.
		closeBtn.addEventListener( 'click', function () {
			if ( pendingToken !== null ) {
				finishDownload( pendingToken, pendingDirectUrl );
			} else {
				closeModal();
			}
		} );

		// Gate skip: download without sharing.
		skipBtn.addEventListener( 'click', function () {
			var url = currentDirectUrl;
			closeModal();
			redirect( url );
		} );

		// Gate form submit.
		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			handleGateSubmit();
		} );

		// Intake submit.
		intakeSubmitBtn.addEventListener( 'click', handleIntakeSubmit );

		// Intake skip: proceed to download without saving intake responses.
		intakeSkipBtn.addEventListener( 'click', function () {
			var token      = pendingToken;
			var url        = pendingDirectUrl;
			var isExternal = pendingIsExternal;
			proceedToDownload( token, url, isExternal );
		} );
	}

	// -------------------------------------------------------------------------
	// Gate form submission
	// -------------------------------------------------------------------------

	function handleGateSubmit() {
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
			headers:     {
				'Content-Type': 'application/x-www-form-urlencoded',
				'X-WP-Nonce':   gatewaySettings.restNonce,
			},
			body:        body.toString(),
		} )
			.then( function ( res ) { return res.json().then( function ( data ) { return { ok: res.ok, data: data }; } ); } )
			.then( function ( result ) {
				setLoading( false );
				if ( ! result.ok ) {
					showError( result.data.message || 'Something went wrong. Please try again.' );
					return;
				}
				setCookie( 'gateway_gated', result.data.person_id, 30 );
				proceedAfterGate(
					result.data.token,
					result.data.person_id,
					currentPostId,
					currentPostType,
					currentDirectUrl,
					currentIsExternal
				);
			} )
			.catch( function () {
				setLoading( false );
				showError( 'A network error occurred. Please try again.' );
			} );
	}

	// -------------------------------------------------------------------------
	// After-gate routing
	// -------------------------------------------------------------------------

	function showLoadingStep() {
		gateContainer.style.display    = 'none';
		intakeContainer.style.display  = 'none';
		loadingContainer.style.display = '';
	}

	/**
	 * Route to the file after gate or passthrough.
	 *
	 * External links (data-file-url, e.g. Wikimedia Commons) redirect directly —
	 * the gate already captured the visitor; no token consumption needed.
	 * Internal gateway downloads consume the token via the REST endpoint.
	 */
	function proceedToDownload( token, directUrl, isExternal ) {
		if ( isExternal || ! token ) {
			closeModal();
			redirect( directUrl );
		} else {
			downloadViaToken( token );
		}
	}

	/**
	 * Decide what happens after a successful gate submission.
	 *
	 * If the post type has intake fields registered via the gateway_intake_fields
	 * filter, show step 2. Otherwise download immediately.
	 */
	function proceedAfterGate( token, personId, postId, postType, directUrl, isExternal ) {
		var steps = gatewaySettings.intakeSteps &&
		            gatewaySettings.intakeSteps[ postType ];
		if ( token && steps && steps.length ) {
			pendingToken      = token;
			pendingPersonId   = personId;
			pendingPostId     = postId;
			pendingPostType   = postType;
			pendingDirectUrl  = directUrl;
			pendingIsExternal = !! isExternal;
			showIntakeStep( steps );
		} else if ( token ) {
			proceedToDownload( token, directUrl, isExternal );
		} else {
			// Honeypot hit — silently redirect to direct URL.
			closeModal();
			redirect( directUrl );
		}
	}

	// -------------------------------------------------------------------------
	// Intake step
	// -------------------------------------------------------------------------

	function showIntakeStep( fields ) {
		intakeForm.innerHTML          = buildIntakeFieldsHtml( fields );
		intakeErrorMsg.textContent    = '';
		intakeSubmitBtn.disabled      = false;
		intakeSubmitBtn.textContent   = 'Submit & Download';
		gateContainer.style.display   = 'none';
		intakeContainer.style.display = '';
		var firstInput = intakeForm.querySelector( 'input, textarea, select' );
		if ( firstInput ) {
			firstInput.focus();
		}
	}

	/**
	 * Build the HTML for the intake form fields from a field definitions array.
	 *
	 * Field definition shape (mirrors gateway_intake_fields PHP filter):
	 *   key      — machine name used as the form element name and response key
	 *   label    — human-readable label
	 *   type     — text | textarea | select | radio | checkbox
	 *   options  — object of { value: label } pairs (select and radio only)
	 */
	function buildIntakeFieldsHtml( fields ) {
		var html = '';
		for ( var i = 0; i < fields.length; i++ ) {
			var field = fields[ i ];
			var key   = field.key   || '';
			var label = field.label || '';
			var type  = field.type  || 'text';
			var id    = 'gateway-intake-' + key;
			var opts, optKey;

			html += '<div class="gateway-intake-field">';

			if ( type === 'radio' || type === 'checkbox' ) {
				html += '<fieldset><legend>' + escHtml( label ) + '</legend>';
				opts = field.options || {};
				for ( optKey in opts ) {
					if ( Object.prototype.hasOwnProperty.call( opts, optKey ) ) {
						html +=
							'<label class="gateway-intake-option">' +
							'<input type="' + type + '" name="' + escAttr( key ) + '" value="' + escAttr( optKey ) + '" /> ' +
							escHtml( opts[ optKey ] ) +
							'</label>';
					}
				}
				html += '</fieldset>';
			} else {
				html += '<label for="' + escAttr( id ) + '">' + escHtml( label ) + '</label>';
				if ( type === 'textarea' ) {
					html += '<textarea id="' + escAttr( id ) + '" name="' + escAttr( key ) + '"></textarea>';
				} else if ( type === 'select' ) {
					html += '<select id="' + escAttr( id ) + '" name="' + escAttr( key ) + '">';
					html += '<option value="">\u2014 Select \u2014</option>';
					opts = field.options || {};
					for ( optKey in opts ) {
						if ( Object.prototype.hasOwnProperty.call( opts, optKey ) ) {
							html += '<option value="' + escAttr( optKey ) + '">' + escHtml( opts[ optKey ] ) + '</option>';
						}
					}
					html += '</select>';
				} else {
					html += '<input type="text" id="' + escAttr( id ) + '" name="' + escAttr( key ) + '" />';
				}
			}

			html += '</div>';
		}
		return html;
	}

	function handleIntakeSubmit() {
		var token     = pendingToken;
		var personId  = pendingPersonId;
		var postId    = pendingPostId;
		var directUrl = pendingDirectUrl;

		// Collect responses from intake form fields.
		var responses = {};
		var elements  = intakeForm.elements;
		for ( var i = 0; i < elements.length; i++ ) {
			var el = elements[ i ];
			if ( ! el.name ) { continue; }
			if ( ( el.type === 'radio' || el.type === 'checkbox' ) && ! el.checked ) { continue; }
			responses[ el.name ] = el.value;
		}

		intakeSubmitBtn.disabled    = true;
		intakeSubmitBtn.textContent = 'Saving\u2026';
		intakeErrorMsg.textContent  = '';

		fetch( gatewaySettings.intakeUrl, {
			method:      'POST',
			credentials: 'same-origin',
			headers:     {
				'Content-Type': 'application/json',
				'X-WP-Nonce':   gatewaySettings.restNonce,
			},
			body: JSON.stringify( {
				post_id:   postId,
				person_id: personId,
				nonce:     gatewaySettings.nonce,
				responses: responses,
			} ),
		} )
			.then( function () {
				// Intake is supplementary — proceed regardless of server response.
				finishDownload( token, directUrl );
			} )
			.catch( function () {
				finishDownload( token, directUrl );
			} );
	}

	/**
	 * Close the modal and trigger the download. Called after intake submit or skip.
	 */
	function finishDownload( token, directUrl ) {
		proceedToDownload( token, directUrl, pendingIsExternal );
	}

	// -------------------------------------------------------------------------
	// Click interception
	// -------------------------------------------------------------------------

	function redirect( url ) {
		window.location.href = url;
	}

	/**
	 * Navigate to the token URL; the server issues a 302 to the file host.
	 * Chrome intercepts Content-Disposition: attachment without leaving the page.
	 * Safari follows the redirect and briefly navigates to the file host before
	 * the download starts — accepted limitation, file still downloads.
	 */
	function downloadViaToken( token ) {
		closeModal();
		redirect( gatewaySettings.downloadBase + '/' + token );
	}

	/**
	 * Silent passthrough for returning visitors who already submitted the gate form.
	 * Fires a token-request POST without showing the modal.
	 * Falls back to the modal if the server rejects the passthrough
	 * (e.g. person was anonymized).
	 */
	function doSilentPassthrough( postId, policy, personId, directUrl, postType, isExternal ) {
		var body = new URLSearchParams( {
			post_id:      postId,
			_passthrough: personId,
			nonce:        gatewaySettings.nonce,
			_hp:          '',
		} );

		fetch( gatewaySettings.apiUrl, {
			method:      'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
				'X-WP-Nonce':   gatewaySettings.restNonce,
			},
			body: body.toString(),
		} )
			.then( function ( res ) { return res.json().then( function ( data ) { return { ok: res.ok, data: data }; } ); } )
			.then( function ( result ) {
				if ( ! result.ok ) {
					// Passthrough rejected — show gate form.
					openModal( postId, policy, directUrl, postType, isExternal );
					return;
				}
				setCookie( 'gateway_gated', result.data.person_id, 30 );
				proceedToDownload( result.data.token, directUrl, isExternal );
			} )
			.catch( function () {
				// Network error — fall back to modal.
				openModal( postId, policy, directUrl, postType, isExternal );
			} );
	}

	document.addEventListener( 'click', function ( e ) {
		var link = e.target.closest( '.gateway-download-link' );
		if ( ! link ) {
			return;
		}

		var policy = link.dataset.policy;
		if ( ! policy || policy === 'none' ) {
			// No gate — let browser follow the link naturally.
			return;
		}

		e.preventDefault();

		var isExternal = !! link.dataset.fileUrl;
		var directUrl  = link.dataset.fileUrl || link.href;
		var personId   = getCookie( 'gateway_gated' );
		if ( personId ) {
			doSilentPassthrough( link.dataset.postId, policy, personId, directUrl, link.dataset.postType, isExternal );
		} else {
			openModal( link.dataset.postId, policy, directUrl, link.dataset.postType, isExternal );
		}
	} );
}() );
