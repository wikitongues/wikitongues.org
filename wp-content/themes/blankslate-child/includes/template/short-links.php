<?php
/**
 * Short links: memorable top-level paths that redirect to a page deeper in
 * the site, e.g. /ai for print, slides and conversation.
 *
 * They only apply when the path doesn't exist, so a real page at the same
 * path always wins. They run before WordPress's canonical redirect, which
 * would otherwise guess at a 404: /ai used to land on /languages/aia/.
 */

/**
 * Short paths and the site paths they redirect to.
 *
 * @return array<string, string>
 */
function wt_short_links() {
	return array(
		'ai' => '/revitalization/resources/native-language-ai/',
	);
}

/**
 * The target for a request path, or null when it isn't a short link.
 *
 * @param string $request_path Path relative to the site root, e.g. "ai" or "/AI/".
 * @return string|null
 */
function wt_short_link_target( $request_path ) {
	$key   = strtolower( trim( (string) $request_path, '/' ) );
	$links = wt_short_links();
	return $links[ $key ] ?? null;
}

function wt_redirect_short_links() {
	if ( ! is_404() ) {
		return;
	}

	$request_path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
	// Drop the subdirectory WordPress lives in locally (/wikitongues).
	$home_path = trim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );
	if ( $home_path && str_starts_with( $request_path, $home_path ) ) {
		$request_path = substr( $request_path, strlen( $home_path ) );
	}

	$target = wt_short_link_target( $request_path );
	if ( null === $target ) {
		return;
	}

	// 302, not 301: browsers cache a 301 for good, and a short link may be
	// pointed somewhere else later.
	$query = wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY );
	wp_safe_redirect( home_url( $target ) . ( $query ? '?' . $query : '' ), 302 );
	exit;
}
// Priority 1: ahead of redirect_canonical (10), which guesses at 404s.
add_action( 'template_redirect', 'wt_redirect_short_links', 1 );
