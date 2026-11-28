<?php
/**
 * Site banner (plan 1.4): the strip above the header on every page, set on
 * the Site banner options page. One banner shows at a time: the first row
 * that is switched On and whose window (Eastern time) contains now, and never
 * on the page its button leads to.
 *
 * Liveness is decided on every request. Production has no page cache; if one
 * is added, this check has to move client-side.
 *
 * @package blankslate-child
 */

const WT_SITE_BANNER_TZ = 'America/New_York';

/**
 * Parse a stored banner time (ACF `Y-m-d H:i:s`) as Eastern time.
 *
 * @param mixed $value Stored value; empty means no bound.
 */
function wt_site_banner_time( $value ): ?DateTimeImmutable {
	if ( ! is_string( $value ) || '' === trim( $value ) ) {
		return null;
	}
	$value = trim( $value );
	$time  = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $value, new DateTimeZone( WT_SITE_BANNER_TZ ) );
	// Reject overflowed dates (2026-13-45) as well as unparseable ones.
	if ( false === $time || $time->format( 'Y-m-d H:i:s' ) !== $value ) {
		return null;
	}
	return $time;
}

/**
 * Whether a stored bound is blank (open), as opposed to set but unreadable.
 *
 * @param mixed $value Stored value.
 */
function wt_site_banner_time_is_blank( $value ): bool {
	return null === $value || false === $value || ( is_string( $value ) && '' === trim( $value ) );
}

/**
 * Whether a banner row is live at $now: switched On, with now in
 * [start, end). A blank start or end leaves that side open; one that is
 * set but unreadable keeps the banner hidden.
 *
 * @param array<string, mixed> $row One `site_banners` row.
 */
function wt_site_banner_is_live( array $row, DateTimeImmutable $now ): bool {
	if ( empty( $row['enabled'] ) ) {
		return false;
	}
	$start = wt_site_banner_time( $row['start'] ?? '' );
	$end   = wt_site_banner_time( $row['end'] ?? '' );
	// Fail closed: a bound that is set but unreadable hides the banner.
	if ( ( ! $start && ! wt_site_banner_time_is_blank( $row['start'] ?? '' ) ) || ( ! $end && ! wt_site_banner_time_is_blank( $row['end'] ?? '' ) ) ) {
		return false;
	}
	if ( $start && $now < $start ) {
		return false;
	}
	if ( $end && $now >= $end ) {
		return false;
	}
	return true;
}

/**
 * Normalise a path for comparison: leading and trailing slash, lowercase.
 */
function wt_site_banner_normalise_path( string $path ): string {
	$path = strtolower( trim( $path, '/' ) );
	return '' === $path ? '/' : '/' . $path . '/';
}

/**
 * A URL or path as a normalised path relative to the site's home, so that
 * /wikitongues/donate/ on a subdirectory install compares equal to /donate/.
 * Query strings and fragments are dropped.
 *
 * @param string $url      A URL, or a path with an optional query string.
 * @param string $home_url The site's home URL.
 */
function wt_site_banner_site_path( string $url, string $home_url ): string {
	$path      = wt_site_banner_normalise_path( (string) wp_parse_url( $url, PHP_URL_PATH ) );
	$home_path = wt_site_banner_normalise_path( (string) wp_parse_url( $home_url, PHP_URL_PATH ) );
	if ( '/' !== $home_path && str_starts_with( $path, $home_path ) ) {
		$path = '/' . substr( $path, strlen( $home_path ) );
	}
	return wt_site_banner_normalise_path( $path );
}

/**
 * The site-relative path a banner's button leads to, or '' when it leads
 * off the site. A donation form's page is /donate.
 *
 * @param array<string, mixed> $row      One `site_banners` row.
 * @param string               $home_url The site's home URL.
 */
function wt_site_banner_target_path( array $row, string $home_url ): string {
	if ( 'form' === ( $row['cta_type'] ?? '' ) ) {
		return '/donate/';
	}
	$url = (string) ( $row['cta_url'] ?? '' );
	if ( '' === $url ) {
		return '';
	}
	$parts = wp_parse_url( $url );
	$home  = wp_parse_url( $home_url );
	if ( ! is_array( $parts ) || ! is_array( $home ) ) {
		return '';
	}
	if ( isset( $parts['host'] ) && strtolower( $parts['host'] ) !== strtolower( $home['host'] ?? '' ) ) {
		return '';
	}
	return wt_site_banner_site_path( $url, $home_url );
}

/**
 * Pick the banner to show, or null.
 *
 * @param array<int, mixed> $rows         `site_banners` rows, in list order.
 * @param DateTimeImmutable $now          The current time.
 * @param string            $current_path The request path, relative to home.
 * @param string            $home_url     The site's home URL.
 * @return array<string, mixed>|null
 */
function wt_site_banner_pick( array $rows, DateTimeImmutable $now, string $current_path, string $home_url ): ?array {
	$current = wt_site_banner_normalise_path( $current_path );
	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) || ! wt_site_banner_is_live( $row, $now ) ) {
			continue;
		}
		if ( '' === trim( (string) ( $row['message'] ?? '' ) ) || '' === wt_site_banner_href( $row ) ) {
			continue;
		}
		// Rule 1: the first live banner wins, even when rule 6 then hides it.
		return wt_site_banner_target_path( $row, $home_url ) === $current ? null : $row;
	}
	return null;
}

/**
 * The FundraiseUp campaign key in an editor's input, uppercased, or ''.
 * Campaign keys start with FUN (FUNFLYUUBYT is the General Fund); a pasted
 * `?form=FUN…` URL works too. The X… codes in donorsupport.co links are
 * elements, not campaigns, and `?form=` does nothing with them.
 *
 * @param string $input What the editor entered.
 */
function wt_site_banner_campaign_key( string $input ): string {
	return preg_match( '/\bFUN[A-Z0-9]{6,12}\b/i', $input, $m ) ? strtoupper( $m[0] ) : '';
}

/**
 * The button's href: the link, or `?form=KEY`, which FundraiseUp opens as
 * a modal over the current page.
 *
 * @param array<string, mixed> $row One `site_banners` row.
 */
function wt_site_banner_href( array $row ): string {
	if ( 'form' === ( $row['cta_type'] ?? '' ) ) {
		$key = wt_site_banner_campaign_key( (string) ( $row['cta_form'] ?? '' ) );
		return '' === $key ? '' : '?form=' . rawurlencode( $key );
	}
	return trim( (string) ( $row['cta_url'] ?? '' ) );
}

/**
 * The banner to show on this request, or null. Memoised per request.
 *
 * @return array<string, mixed>|null
 */
function wt_site_banner(): ?array {
	static $banner = false;
	if ( false !== $banner ) {
		return $banner;
	}
	$banner = null;
	if ( ! function_exists( 'get_field' ) || is_admin() ) {
		return $banner;
	}
	$rows = get_field( 'site_banners', 'option' );
	if ( ! is_array( $rows ) || ! $rows ) {
		return $banner;
	}
	$request = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
	$home    = home_url( '/' );
	$now     = new DateTimeImmutable( 'now', new DateTimeZone( WT_SITE_BANNER_TZ ) );
	$banner  = wt_site_banner_pick( $rows, $now, wt_site_banner_site_path( $request, $home ), $home );
	return $banner;
}

/**
 * Split a message into its bold lead-in and the rest: the text before the
 * first colon, when that colon comes within the first 40 characters and
 * something follows it ("Giving Tuesday:" + " 9 in 10 languages…").
 *
 * @param string $message The banner message.
 * @return array{lead: string, rest: string}
 */
function wt_site_banner_message_parts( string $message ): array {
	$message = trim( $message );
	$colon   = strpos( $message, ':' );
	if ( false === $colon || $colon > 40 || '' === trim( substr( $message, $colon + 1 ) ) ) {
		return array(
			'lead' => '',
			'rest' => $message,
		);
	}
	return array(
		'lead' => substr( $message, 0, $colon + 1 ),
		'rest' => ' ' . ltrim( substr( $message, $colon + 1 ) ),
	);
}

/**
 * The inline script that hides a dismissed banner before the page paints:
 * if this banner's ID is in localStorage, <html> gets wt-site-banner-dismissed.
 *
 * @param string $banner_id The banner shown on this page.
 */
function wt_site_banner_dismiss_check( string $banner_id ): string {
	return '(function(){try{var l=JSON.parse(localStorage.getItem("wt_site_banner_dismissed")||"[]");'
		. 'if(Array.isArray(l)&&l.indexOf(' . wp_json_encode( $banner_id ) . ')!==-1){document.documentElement.classList.add("wt-site-banner-dismissed");}}catch(e){}})();';
}

/**
 * Print the dismiss check early in <head>, only when a banner shows.
 */
function wt_site_banner_head_script(): void {
	$banner = wt_site_banner();
	if ( null === $banner || '' === (string) ( $banner['banner_id'] ?? '' ) ) {
		return;
	}
	wp_print_inline_script_tag( wt_site_banner_dismiss_check( (string) $banner['banner_id'] ) );
}
add_action( 'wp_head', 'wt_site_banner_head_script', 1 );

/**
 * Admin validation for a banner's end: it can't come before its start
 * (rule 11). The sibling start is read from the same repeater row.
 *
 * @param mixed                $valid Current validity.
 * @param mixed                $value The end value.
 * @param array<string, mixed> $field The field.
 * @param string               $input The input name, e.g. acf[field_x][row-0][field_y].
 * @return mixed
 */
function wt_site_banner_validate_end( $valid, $value, $field, $input ) {
	if ( true !== $valid || ! is_string( $value ) || '' === $value ) {
		return $valid;
	}
	if ( ! preg_match( '/^acf\[([^\]]+)\]\[([^\]]+)\]/', (string) $input, $m ) ) {
		return $valid;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- ACF verifies the form nonce before validating.
	$posted = isset( $_POST['acf'][ $m[1] ][ $m[2] ]['field_wt_site_banner_start'] ) ? sanitize_text_field( wp_unslash( $_POST['acf'][ $m[1] ][ $m[2] ]['field_wt_site_banner_start'] ) ) : '';
	$start  = wt_site_banner_time( str_replace( 'T', ' ', $posted ) );
	$end    = wt_site_banner_time( str_replace( 'T', ' ', $value ) );
	if ( $start && $end && $end <= $start ) {
		return 'The end has to come after the start.';
	}
	return $valid;
}
add_filter( 'acf/validate_value/key=field_wt_site_banner_end', 'wt_site_banner_validate_end', 10, 4 );

/**
 * Admin validation for a banner ID: lowercase letters, numbers and hyphens.
 *
 * @param mixed $valid Current validity.
 * @param mixed $value The ID.
 * @return mixed
 */
function wt_site_banner_validate_id( $valid, $value ) {
	if ( true !== $valid || ! is_string( $value ) || '' === $value ) {
		return $valid;
	}
	return preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value ) ? $valid : 'Use lowercase letters, numbers and hyphens, like giving-tuesday-2026.';
}
add_filter( 'acf/validate_value/key=field_wt_site_banner_id', 'wt_site_banner_validate_id', 10, 2 );

/**
 * Admin validation for the campaign key: it must contain a FUN… key. An X…
 * code from a donorsupport.co link gets its own message, since it looks right.
 *
 * @param mixed $valid Current validity.
 * @param mixed $value What the editor entered.
 * @return mixed
 */
function wt_site_banner_validate_campaign( $valid, $value ) {
	if ( true !== $valid || ! is_string( $value ) || '' === trim( $value ) ) {
		return $valid;
	}
	if ( '' !== wt_site_banner_campaign_key( $value ) ) {
		return $valid;
	}
	if ( preg_match( '/donorsupport|\bX[A-Z]{7}\b/i', $value ) ) {
		return 'That code is from a donorsupport.co link, which is a link, not a campaign. Use the campaign key, which starts with FUN, like FUNFLYUUBYT.';
	}
	return 'Use the campaign key, which starts with FUN, like FUNFLYUUBYT.';
}
add_filter( 'acf/validate_value/key=field_wt_site_banner_cta_form', 'wt_site_banner_validate_campaign', 10, 2 );

/**
 * Save the campaign key on its own, so a pasted URL is stored as FUN….
 *
 * @param mixed $value What the editor entered.
 * @return mixed
 */
function wt_site_banner_save_campaign( $value ) {
	if ( ! is_string( $value ) ) {
		return $value;
	}
	$key = wt_site_banner_campaign_key( $value );
	return '' === $key ? $value : $key;
}
add_filter( 'acf/update_value/key=field_wt_site_banner_cta_form', 'wt_site_banner_save_campaign' );

/**
 * Load the click tracker only when a banner shows.
 */
function wt_site_banner_enqueue(): void {
	if ( null === wt_site_banner() ) {
		return;
	}
	wp_enqueue_script( 'wt-site-banner', get_stylesheet_directory_uri() . '/js/site-banner.js', array(), '1.0.0', true );
}
add_action( 'wp_enqueue_scripts', 'wt_site_banner_enqueue' );

/**
 * Admin styles for the Site banner page: Content and Schedule read as
 * sections of one screen, and "Add banner" disappears once the row limit is
 * reached (ACF disables it but still shows a "Maximum rows reached" notice
 * when it is clicked).
 */
function wt_site_banner_admin_styles(): void {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || false === strpos( (string) $screen->id, 'site-banner' ) ) {
		return;
	}
	?>
	<style>
		[data-key="field_wt_site_banner_rows"] .acf-actions .acf-button.disabled { display: none; }
		[data-key="field_wt_site_banner_rows"] .acf-repeater:has(> .acf-actions .acf-button.disabled) .acf-row-handle :is([data-event="add-row"], [data-event="duplicate-row"]) { display: none; }
		.acf-field.wt-site-banner-section { padding-top: 20px; padding-bottom: 0; border-top: 1px solid #dcdcde; }
		.acf-field.wt-site-banner-section:first-child { border-top: 0; padding-top: 12px; }
		.acf-field.wt-site-banner-section > .acf-label label { font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: #50575e; }
		.acf-field.wt-site-banner-section > .acf-input { display: none; }
		.acf-field.wt-site-banner-section > .acf-label { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
		.wt-site-banner-status { padding: 2px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; line-height: 20px; white-space: nowrap; background: #f0f0f1; color: #50575e; }
		.wt-site-banner-status::before { content: ""; display: inline-block; width: 8px; height: 8px; margin-right: 6px; border-radius: 50%; background: currentColor; vertical-align: 0; }
		.wt-site-banner-status[data-state="live"] { background: #edfaef; color: #00701a; }
		.wt-site-banner-status[data-state="scheduled"] { background: #f0f6fc; color: #2271b1; }
		.wt-site-banner-status[data-state="error"] { background: #fcf0f1; color: #b32d2e; }
	</style>
	<?php
}
add_action( 'acf/input/admin_head', 'wt_site_banner_admin_styles' );

/**
 * Load the schedule status pill on the Site banner admin page.
 */
function wt_site_banner_admin_script(): void {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || false === strpos( (string) $screen->id, 'site-banner' ) ) {
		return;
	}
	wp_enqueue_script( 'wt-site-banner-admin', get_stylesheet_directory_uri() . '/js/site-banner-admin.js', array(), '1.0.0', true );
}
add_action( 'acf/input/admin_enqueue_scripts', 'wt_site_banner_admin_script' );
