/**
 * Site banner admin (plan 1.4): a status pill beside each banner's Schedule
 * heading, saying whether its switch, start and end would show it right now,
 * and a Now showing line at the top: the first live banner in list order.
 * Same rule as wt_site_banner_is_live(): On, and start <= now < end, in
 * Eastern time. It reads the form as it is, before saving, and refreshes
 * every second so it changes as the clock passes a start or end.
 */
( function () {
	var KEYS = {
		enabled: 'field_wt_site_banner_enabled',
		start: 'field_wt_site_banner_start',
		end: 'field_wt_site_banner_end',
		heading: 'field_wt_site_banner_section_schedule',
	};
	var MONTHS = [ 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' ];
	var easternParts = new Intl.DateTimeFormat( 'en-CA', {
		timeZone: 'America/New_York',
		year: 'numeric',
		month: '2-digit',
		day: '2-digit',
		hour: '2-digit',
		minute: '2-digit',
		second: '2-digit',
		hourCycle: 'h23',
	} );

	// Now in Eastern, as 'Y-m-d H:i:s', so it compares as a string with ACF's stored values.
	function nowEastern() {
		var p = {};
		easternParts.formatToParts( new Date() ).forEach( function ( part ) {
			p[ part.type ] = part.value;
		} );
		return p.year + '-' + p.month + '-' + p.day + ' ' + p.hour + ':' + p.minute + ':' + p.second;
	}

	function readable( value ) {
		var m = /^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2})/.exec( value );
		if ( ! m ) {
			return value;
		}
		var hour = parseInt( m[ 4 ], 10 );
		var suffix = hour < 12 ? 'am' : 'pm';
		hour = hour % 12 || 12;
		return parseInt( m[ 3 ], 10 ) + ' ' + MONTHS[ parseInt( m[ 2 ], 10 ) - 1 ] + ', ' + hour + ':' + m[ 5 ] + ' ' + suffix;
	}

	function field( row, key, selector ) {
		return row.querySelector( '[data-key="' + key + '"] ' + selector );
	}

	function status( row, now ) {
		var toggle = field( row, KEYS.enabled, 'input[type="checkbox"]' );
		var start = ( field( row, KEYS.start, 'input.input-alt' ) || {} ).value || '';
		var end = ( field( row, KEYS.end, 'input.input-alt' ) || {} ).value || '';
		if ( start && end && end <= start ) {
			return { state: 'error', text: 'End is before start' };
		}
		// Archived (rule 13) whatever the switch says: the end has passed.
		if ( end && now >= end ) {
			return { state: 'archived', text: 'Archived · ended ' + readable( end ) };
		}
		if ( ! toggle || ! toggle.checked ) {
			return { state: 'off', text: 'Off' };
		}
		if ( start && now < start ) {
			return { state: 'scheduled', text: 'Scheduled · starts ' + readable( start ) };
		}
		return { state: 'live', text: end ? 'Live now · until ' + readable( end ) : 'Live now' };
	}

	function bannerId( row ) {
		var input = field( row, 'field_wt_site_banner_id', 'input' );
		return ( input && input.value.trim() ) || 'untitled banner';
	}

	function pillFor( container ) {
		var pill = container.querySelector( '.wt-site-banner-status' );
		if ( ! pill ) {
			pill = document.createElement( 'span' );
			pill.className = 'wt-site-banner-status';
			pill.setAttribute( 'role', 'status' );
			container.appendChild( pill );
		}
		return pill;
	}

	function show( pill, s ) {
		if ( pill.textContent !== s.text ) {
			pill.textContent = s.text;
		}
		pill.setAttribute( 'data-state', s.state );
	}

	// Rule 1: the first live banner in list order shows; later live ones wait.
	function refresh() {
		var now = nowEastern();
		var winner = null;
		var next = null;
		document.querySelectorAll( '[data-key="field_wt_site_banner_rows"] .acf-row:not(.acf-clone)' ).forEach( function ( row ) {
			var s = status( row, now );
			var start = ( field( row, KEYS.start, 'input.input-alt' ) || {} ).value || '';
			if ( 'live' === s.state ) {
				if ( winner ) {
					s = { state: 'outranked', text: 'Live, but ' + winner.id + ' shows first' };
				} else {
					winner = { id: bannerId( row ), text: s.text };
				}
			} else if ( 'scheduled' === s.state && ( ! next || start < next.start ) ) {
				next = { id: bannerId( row ), start: start };
			}
			var label = field( row, KEYS.heading, '.acf-label' );
			if ( label ) {
				show( pillFor( label ), s );
			}
		} );

		var intro = document.querySelector( '[data-key="field_wt_site_banner_intro"] .acf-input' );
		if ( intro ) {
			var line = { state: 'off', text: 'Nothing showing' };
			if ( winner ) {
				line = { state: 'live', text: 'Showing now: ' + winner.id + winner.text.replace( /^Live now/, '' ) };
			} else if ( next ) {
				line = { state: 'scheduled', text: 'Nothing showing · next: ' + next.id + ', from ' + readable( next.start ) };
			}
			show( pillFor( intro ), line );
		}
	}

	// Rule 13: archived banners (end passed) are hidden behind a toggle. Decided
	// once, on load, so a banner never vanishes while it is being edited.
	function archiveOnLoad() {
		var list = document.querySelector( '[data-key="field_wt_site_banner_rows"]' );
		if ( ! list ) {
			return;
		}
		var now = nowEastern();
		var count = 0;
		list.querySelectorAll( '.acf-row:not(.acf-clone)' ).forEach( function ( row ) {
			var end = ( field( row, KEYS.end, 'input.input-alt' ) || {} ).value || '';
			if ( end && now >= end ) {
				row.classList.add( 'wt-sb-archived' );
				count++;
			}
		} );
		if ( ! count ) {
			return;
		}
		var button = document.createElement( 'button' );
		button.type = 'button';
		button.className = 'button wt-sb-archive-toggle';
		var label = function () {
			button.textContent = ( list.classList.contains( 'wt-sb-show-archived' ) ? 'Hide archived (' : 'Show archived (' ) + count + ')';
		};
		label();
		button.addEventListener( 'click', function () {
			list.classList.toggle( 'wt-sb-show-archived' );
			label();
		} );
		var input = list.querySelector( '.acf-input' ) || list;
		input.appendChild( button );
	}

	archiveOnLoad();
	refresh();
	setInterval( refresh, 1000 );
}() );
