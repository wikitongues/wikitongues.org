/**
 * Site banner admin (plan 1.4): a status pill beside each banner's Schedule
 * heading, saying whether its switch, start and end would show it right now.
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
		if ( ! toggle || ! toggle.checked ) {
			return { state: 'off', text: 'Off' };
		}
		if ( start && now < start ) {
			return { state: 'scheduled', text: 'Scheduled · starts ' + readable( start ) };
		}
		if ( end && now >= end ) {
			return { state: 'ended', text: 'Ended ' + readable( end ) };
		}
		return { state: 'live', text: end ? 'Live now · until ' + readable( end ) : 'Live now' };
	}

	function refresh() {
		var now = nowEastern();
		document.querySelectorAll( '[data-key="field_wt_site_banner_rows"] .acf-row:not(.acf-clone)' ).forEach( function ( row ) {
			var label = field( row, KEYS.heading, '.acf-label' );
			if ( ! label ) {
				return;
			}
			var pill = label.querySelector( '.wt-site-banner-status' );
			if ( ! pill ) {
				pill = document.createElement( 'span' );
				pill.className = 'wt-site-banner-status';
				pill.setAttribute( 'role', 'status' );
				label.appendChild( pill );
			}
			var s = status( row, now );
			if ( pill.textContent !== s.text ) {
				pill.textContent = s.text;
			}
			pill.setAttribute( 'data-state', s.state );
		} );
	}

	refresh();
	setInterval( refresh, 1000 );
}() );
