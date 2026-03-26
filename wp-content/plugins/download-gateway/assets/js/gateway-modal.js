/* global gatewaySettings */
( function () {
	'use strict';

	// gatewaySettings is localized by PHP:
	//   { nonce, restNonce, apiUrl, downloadBase, intakeUrl, intakeSets }
	if ( typeof gatewaySettings === 'undefined' ) {
		return;
	}

	// -------------------------------------------------------------------------
	// Constants
	// -------------------------------------------------------------------------

	var RESOURCE_LABELS = {
		videos:         'oral history',
		captions:       'caption file',
		document_files: 'document',
	};

	// Fields in the intake step shown only to first-time visitors.
	// Returning visitors (gateway_gated cookie present → passthrough) skip these.
	var PERSON_LEVEL_KEYS = [ 'community', 'organization' ];

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
		var expiry  = days ? '; expires=' + new Date( Date.now() + days * 864e5 ).toUTCString() : '';
		var secure  = location.protocol === 'https:' ? '; Secure' : '';
		document.cookie = name + '=' + encodeURIComponent( value ) + expiry + '; path=/; SameSite=Lax' + secure;
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
		honeypotField, errorMsg, submitBtn, skipBtn, closeBtn, modalDesc;
	var intakeContainer, intakeForm, intakeErrorMsg, intakeSubmitBtn;
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
			// ── Header band ───────────────────────────────────────────────
			'<div id="gateway-modal-header">' +
				'<h2 id="gateway-modal-title" class="gateway-modal-title">Download and support the archive</h2>' +
				'<button id="gateway-close" aria-label="Close">&times;</button>' +
			'</div>' +

			// ── Modal body ─────────────────────────────────────────────────
			'<div class="gateway-modal-body">' +

			// ── Step 1: gate form ──────────────────────────────────────────
			'<div id="gateway-gate-container" class="gateway-container gate">' +
			'<p id="gateway-modal-desc" class="gateway-modal-desc"></p>' +
			'<form id="gateway-form" novalidate>' +
				'<label for="gateway-name">Name<span class="gateway-required" aria-hidden="true"> *</span></label>' +
				'<input id="gateway-name" name="name" type="text" autocomplete="name" required class="gateway-name-input" />' +
				'<label for="gateway-email">Email<span class="gateway-required" aria-hidden="true"> *</span></label>' +
				'<input id="gateway-email" name="email" type="email" autocomplete="email" required />' +
				'<div class="gateway-consent-section">' +
					'<p class="gateway-consent-heading">Stay in touch</p>' +
					'<label class="gateway-consent-row">' +
						'<input id="gateway-consent" name="consent_download" type="checkbox" checked />' +
						'<span class="gateway-custom-checkbox"></span>' +
						'Receive updates on new languages and resources' +
					'</label>' +
				'</div>' +
				// Honeypot — hidden from real users
				'<div style="display:none" aria-hidden="true">' +
					'<input id="gateway-hp" name="_hp" type="text" tabindex="-1" autocomplete="off" />' +
				'</div>' +
				'<p id="gateway-error" role="alert" aria-live="assertive"></p>' +
				'<div class="gateway-actions">' +
					'<button id="gateway-submit" type="submit" disabled>Download</button>' +
					'<button id="gateway-skip" type="button" style="display:none">Skip and download</button>' +
				'</div>' +
			'</form>' +
			'</div>' +

			// ── Step 2: intake form (hidden until gate passes) ─────────────
			'<div id="gateway-intake-container" class="gateway-container intake" style="display:none">' +
			'<form id="gateway-intake-form" novalidate></form>' +
			'<p id="gateway-intake-error" role="alert" aria-live="assertive"></p>' +
			'<div class="gateway-actions">' +
				'<button id="gateway-intake-submit" type="button">Download</button>' +
			'</div>' +
			'</div>' +

			// ── Step 3: loading state (shown while resolving download URL) ──
			'<div id="gateway-loading-container" role="status" aria-live="polite" style="display:none">' +
				'<div class="gateway-spinner" aria-hidden="true"></div>' +
				'<p>Preparing your download\u2026</p>' +
			'</div>' +

			'</div>'; // .gateway-modal-body

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
		modalDesc     = document.getElementById( 'gateway-modal-desc' );

		// Intake step
		intakeContainer  = document.getElementById( 'gateway-intake-container' );
		intakeForm       = document.getElementById( 'gateway-intake-form' );
		intakeErrorMsg   = document.getElementById( 'gateway-intake-error' );
		intakeSubmitBtn  = document.getElementById( 'gateway-intake-submit' );
		loadingContainer = document.getElementById( 'gateway-loading-container' );
	}

	// -------------------------------------------------------------------------
	// Modal open / close
	// -------------------------------------------------------------------------

	var currentPostId       = null;
	var currentPostType     = null;
	var currentDirectUrl    = null;
	var currentIsExternal   = false;
	var currentIntakeSet    = '';
	var currentIntakeAlways = false;
	var currentPolicy       = '';

	// Element focused before the modal opened — restored when it closes.
	var lastFocusedElement  = null;

	// Person-level fields (community, organization) already answered in a prior
	// intake. Populated from the passthrough response; reset on new gate submissions.
	var completedPersonFields = [];

	// Pending state — populated when gate passes and intake step is active.
	var pendingToken          = null;
	var pendingPersonCookie   = null;
	var pendingPostId         = null;
	var pendingPostType       = null;
	var pendingDirectUrl      = null;
	var pendingIsExternal     = false;

	function openModal( postId, policy, directUrl, postType, isExternal, intakeSet, intakeAlways ) {
		if ( ! modal ) {
			buildModal();
			attachModalEvents();
		}

		lastFocusedElement    = document.activeElement || null;
		completedPersonFields = [];
		currentPostId         = postId;
		currentPostType     = postType || '';
		currentDirectUrl    = directUrl;
		currentIsExternal   = !! isExternal;
		currentIntakeSet    = intakeSet    || '';
		currentIntakeAlways = !! intakeAlways;
		currentPolicy       = policy || '';

		// Reset to gate step.
		errorMsg.textContent           = '';
		form.reset();
		gateContainer.style.display    = '';
		intakeContainer.style.display  = 'none';
		loadingContainer.style.display = 'none';
		clearPending();

		// Description copy per resource type.
		var typeLabel = RESOURCE_LABELS[ currentPostType ] || 'resource';
		modalDesc.innerHTML =
			'This ' + typeLabel + ' is part of a global effort to preserve and share human language.' +
			'&nbsp; Add your name to support the archive and receive updates on new languages, stories, and tools.';

		// Required asterisks and aria-required: hard gate only.
		var isHard = policy === 'hard';
		var requiredSpans = gateContainer.querySelectorAll( '.gateway-required' );
		for ( var i = 0; i < requiredSpans.length; i++ ) {
			requiredSpans[ i ].style.display = isHard ? 'inline' : 'none';
		}
		nameField.setAttribute( 'aria-required', isHard ? 'true' : 'false' );
		emailField.setAttribute( 'aria-required', isHard ? 'true' : 'false' );

		// Button labels and secondary skip button.
		var hasIntake = currentIntakeSet &&
		                gatewaySettings.intakeSets &&
		                gatewaySettings.intakeSets[ currentIntakeSet ] &&
		                gatewaySettings.intakeSets[ currentIntakeSet ].length;
		submitBtn.textContent    = hasIntake ? 'Next' : 'Download';
		// Skip shown for soft gate + intake only.
		skipBtn.style.display    = ( policy === 'soft' && hasIntake ) ? '' : 'none';

		// Enable/disable based on policy + intake (re-validates on each keystroke).
		validateGateForm();

		closeBtn.style.display = '';
		overlay.classList.add( 'is-open' );
		nameField.focus();
	}

	function closeModal() {
		if ( overlay ) {
			overlay.classList.remove( 'is-open' );
		}
		currentPostId       = null;
		currentPostType     = null;
		currentDirectUrl    = null;
		currentIsExternal   = false;
		currentIntakeSet    = '';
		currentIntakeAlways = false;
		currentPolicy       = '';
		clearPending();
		// Return focus to wherever the user was before opening the modal.
		if ( lastFocusedElement && typeof lastFocusedElement.focus === 'function' ) {
			lastFocusedElement.focus();
		}
		lastFocusedElement = null;
	}

	function clearPending() {
		pendingToken        = null;
		pendingPersonCookie = null;
		pendingPostId       = null;
		pendingPostType     = null;
		pendingDirectUrl    = null;
		pendingIsExternal   = false;
	}

	/**
	 * Enable/disable the gate submit button.
	 *
	 * Rules:
	 *  - Intake defined        → require name + email regardless of policy.
	 *  - Hard gate, no intake  → require name + email.
	 *  - Soft/none, no intake  → always enabled.
	 */
	function validateGateForm() {
		if ( ! nameField ) { return; }
		var name     = nameField.value.trim();
		var email    = emailField.value.trim();
		var hasIntake = currentIntakeSet &&
		                gatewaySettings.intakeSets &&
		                gatewaySettings.intakeSets[ currentIntakeSet ] &&
		                gatewaySettings.intakeSets[ currentIntakeSet ].length;

		if ( hasIntake || currentPolicy === 'hard' ) {
			submitBtn.disabled = ! ( name && email );
		} else {
			submitBtn.disabled = false;
		}
	}

	function setLoading( loading ) {
		if ( loading ) {
			submitBtn.disabled = true;
		} else {
			validateGateForm();
		}
	}

	function showError( msg ) {
		errorMsg.textContent = msg;
	}

	// -------------------------------------------------------------------------
	// Event wiring
	// -------------------------------------------------------------------------

	/**
	 * Return focusable elements within the modal that are currently visible
	 * (i.e. no ancestor has display:none set as an inline style).
	 */
	function getFocusableElements() {
		var candidates = modal.querySelectorAll(
			'button:not([disabled]), input:not([tabindex="-1"]), select, textarea, [tabindex]:not([tabindex="-1"])'
		);
		return Array.prototype.filter.call( candidates, function ( el ) {
			var node = el.parentElement;
			while ( node && node !== modal ) {
				if ( node.style.display === 'none' ) { return false; }
				node = node.parentElement;
			}
			return true;
		} );
	}

	function attachModalEvents() {
		// Escape closes the modal; Tab is trapped inside.
		document.addEventListener( 'keydown', function ( e ) {
			if ( ! overlay || ! overlay.classList.contains( 'is-open' ) ) { return; }

			if ( e.key === 'Escape' ) {
				e.preventDefault();
				closeModal();
				return;
			}

			if ( e.key === 'Tab' ) {
				var focusable = getFocusableElements();
				if ( focusable.length === 0 ) { return; }
				var first = focusable[ 0 ];
				var last  = focusable[ focusable.length - 1 ];
				if ( e.shiftKey && document.activeElement === first ) {
					e.preventDefault();
					last.focus();
				} else if ( ! e.shiftKey && document.activeElement === last ) {
					e.preventDefault();
					first.focus();
				}
			}
		} );

		// Close on overlay click — always abort, never trigger download.
		overlay.addEventListener( 'click', function ( e ) {
			if ( e.target !== overlay ) { return; }
			closeModal();
		} );

		// Close button — always abort, never trigger download.
		closeBtn.addEventListener( 'click', function () {
			closeModal();
		} );

		// Gate form submit.
		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			handleGateSubmit();
		} );

		// Skip — soft gate only; download directly without capturing visitor data.
		skipBtn.addEventListener( 'click', function () {
			var url = currentDirectUrl;
			closeModal();
			redirect( url );
		} );

		// Re-validate on each keystroke so the button enables as soon as both
		// name and email are non-empty.
		nameField.addEventListener( 'input', validateGateForm );
		emailField.addEventListener( 'input', validateGateForm );

		// Intake download button.
		intakeSubmitBtn.addEventListener( 'click', handleIntakeSubmit );
	}

	// -------------------------------------------------------------------------
	// Gate form submission
	// -------------------------------------------------------------------------

	function handleGateSubmit() {
		completedPersonFields = [];

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
				setCookie( 'gateway_gated', result.data.person_cookie, 0 );
				proceedAfterGate(
					result.data.token,
					result.data.person_cookie,
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
	 * Navigate to the token URL; the server issues a 302 to the file host.
	 * Chrome intercepts Content-Disposition: attachment without leaving the page.
	 * Safari follows the redirect and briefly navigates to the file host before
	 * the download starts — accepted limitation, file still downloads.
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
	function proceedAfterGate( token, personCookie, postId, postType, directUrl, isExternal ) {
		var fields = currentIntakeSet &&
		             gatewaySettings.intakeSets &&
		             gatewaySettings.intakeSets[ currentIntakeSet ];
		if ( token && fields && fields.length ) {
			pendingToken        = token;
			pendingPersonCookie = personCookie;
			pendingPostId       = postId;
			pendingPostType     = postType;
			pendingDirectUrl    = directUrl;
			pendingIsExternal   = !! isExternal;
			showIntakeStep( fields );
		} else if ( token ) {
			proceedToDownload( token, directUrl, isExternal );
		} else {
			// Honeypot hit — close silently, no redirect.
			closeModal();
		}
	}

	// -------------------------------------------------------------------------
	// Intake step
	// -------------------------------------------------------------------------

	function showIntakeStep( fields ) {
		// Hide person-level fields only if the visitor has answered them before.
		var visibleFields = fields.filter( function ( field ) {
			return completedPersonFields.indexOf( field.key ) === -1;
		} );

		// Skip step 2 entirely when no visible fields remain.
		if ( visibleFields.length === 0 ) {
			finishDownload( pendingToken, pendingDirectUrl );
			return;
		}

		intakeForm.innerHTML          = buildIntakeFieldsHtml( visibleFields );
		intakeErrorMsg.textContent    = '';
		intakeSubmitBtn.disabled      = false;
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
							'<input type="' + type + '" name="' + escAttr( key ) + '" value="' + escAttr( optKey ) + '" />' +
							'<span class="gateway-custom-' + type + '"></span>' +
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
					html += '<option value="" disabled selected>Select</option>';
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
		var token        = pendingToken;
		var personCookie = pendingPersonCookie;
		var postId       = pendingPostId;
		var directUrl    = pendingDirectUrl;

		// Collect responses from intake form fields.
		var responses = {};
		var elements  = intakeForm.elements;
		for ( var i = 0; i < elements.length; i++ ) {
			var el = elements[ i ];
			if ( ! el.name ) { continue; }
			if ( ( el.type === 'radio' || el.type === 'checkbox' ) && ! el.checked ) { continue; }
			responses[ el.name ] = el.value;
		}

		intakeSubmitBtn.disabled   = true;
		intakeErrorMsg.textContent = '';

		fetch( gatewaySettings.intakeUrl, {
			method:      'POST',
			credentials: 'same-origin',
			headers:     {
				'Content-Type': 'application/json',
				'X-WP-Nonce':   gatewaySettings.restNonce,
			},
			body: JSON.stringify( {
				post_id:       postId,
				person_cookie: personCookie,
				nonce:         gatewaySettings.nonce,
				responses:     responses,
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
	 * Close the modal and trigger the download. Called after intake submit.
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
	function doSilentPassthrough( postId, policy, personCookie, directUrl, postType, isExternal, intakeSet, intakeAlways ) {
		var body = new URLSearchParams( {
			post_id:      postId,
			_passthrough: personCookie,
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
					openModal( postId, policy, directUrl, postType, isExternal, intakeSet, intakeAlways );
					return;
				}
				completedPersonFields = result.data.completed_person_fields || [];
				setCookie( 'gateway_gated', result.data.person_cookie, 0 );

				// If intakeAlways is set and a field set is configured, show intake
				// step even for passthrough (repeat) downloads.
				var fields = intakeAlways && intakeSet &&
				             gatewaySettings.intakeSets &&
				             gatewaySettings.intakeSets[ intakeSet ];
				if ( fields && fields.length && ! isExternal ) {
					if ( ! modal ) {
						buildModal();
						attachModalEvents();
					}
					currentPostId       = postId;
					currentPostType     = postType || '';
					currentDirectUrl    = directUrl;
					currentIsExternal   = false;
					currentIntakeSet    = intakeSet;
					currentIntakeAlways = true;
					currentPolicy       = policy || '';
					pendingToken        = result.data.token;
					pendingPersonCookie = result.data.person_cookie;
					pendingPostId       = postId;
					pendingPostType     = postType;
					pendingDirectUrl    = directUrl;
					pendingIsExternal   = false;
					errorMsg.textContent           = '';
					gateContainer.style.display    = 'none';
					loadingContainer.style.display = 'none';
					overlay.classList.add( 'is-open' );
					showIntakeStep( gatewaySettings.intakeSets[ intakeSet ] );
				} else {
					proceedToDownload( result.data.token, directUrl, isExternal );
				}
			} )
			.catch( function () {
				// Network error — fall back to modal.
				openModal( postId, policy, directUrl, postType, isExternal, intakeSet, intakeAlways );
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

		var isExternal   = !! link.dataset.fileUrl;
		var directUrl    = link.dataset.fileUrl || link.href;
		var intakeSet    = link.dataset.intakeSet    || '';
		var intakeAlways = link.dataset.intakeAlways === '1';
		var personCookie = getCookie( 'gateway_gated' );
		if ( personCookie ) {
			doSilentPassthrough( link.dataset.postId, policy, personCookie, directUrl, link.dataset.postType, isExternal, intakeSet, intakeAlways );
		} else {
			openModal( link.dataset.postId, policy, directUrl, link.dataset.postType, isExternal, intakeSet, intakeAlways );
		}
	} );
}() );
