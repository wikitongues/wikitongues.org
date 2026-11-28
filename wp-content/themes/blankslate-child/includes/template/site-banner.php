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
	$time = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', trim( $value ), new DateTimeZone( WT_SITE_BANNER_TZ ) );
	return false === $time ? null : $time;
}

/**
 * Whether a banner row is live at $now: switched On, with now in
 * [start, end). A missing start or end leaves that side open.
 *
 * @param array<string, mixed> $row One `site_banners` row.
 */
function wt_site_banner_is_live( array $row, DateTimeImmutable $now ): bool {
	if ( empty( $row['enabled'] ) ) {
		return false;
	}
	$start = wt_site_banner_time( $row['start'] ?? '' );
	$end   = wt_site_banner_time( $row['end'] ?? '' );
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
	$path      = wt_site_banner_normalise_path( $parts['path'] ?? '/' );
	$home_path = wt_site_banner_normalise_path( $home['path'] ?? '/' );
	if ( '/' !== $home_path && str_starts_with( $path, $home_path ) ) {
		$path = '/' . substr( $path, strlen( $home_path ) );
	}
	return wt_site_banner_normalise_path( $path );
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
 * The button's href: the link, or `?form=CODE`, which FundraiseUp opens as
 * a modal over the current page.
 *
 * @param array<string, mixed> $row One `site_banners` row.
 */
function wt_site_banner_href( array $row ): string {
	if ( 'form' === ( $row['cta_type'] ?? '' ) ) {
		$code = trim( (string) ( $row['cta_form'] ?? '' ) );
		return '' === $code ? '' : '?form=' . rawurlencode( $code );
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
	$path    = (string) wp_parse_url( $request, PHP_URL_PATH );
	$home_p  = wt_site_banner_normalise_path( (string) wp_parse_url( $home, PHP_URL_PATH ) );
	$path    = wt_site_banner_normalise_path( $path );
	if ( '/' !== $home_p && str_starts_with( $path, $home_p ) ) {
		$path = '/' . substr( $path, strlen( $home_p ) );
	}
	$now    = new DateTimeImmutable( 'now', new DateTimeZone( WT_SITE_BANNER_TZ ) );
	$banner = wt_site_banner_pick( $rows, $now, $path, $home );
	return $banner;
}

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
 * Load the click tracker only when a banner shows.
 */
function wt_site_banner_enqueue(): void {
	if ( null === wt_site_banner() ) {
		return;
	}
	wp_enqueue_script( 'wt-site-banner', get_stylesheet_directory_uri() . '/js/site-banner.js', array(), '1.0.0', true );
}
add_action( 'wp_enqueue_scripts', 'wt_site_banner_enqueue' );
