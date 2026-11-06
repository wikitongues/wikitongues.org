<?php
/**
 * Pure helpers for the content-bundle WP-CLI commands.
 *
 * Kept free of the WP_CLI guard so the unit tests can load them. They only
 * define functions, so loading them on a normal request costs nothing.
 *
 * @package Wikitongues
 */

/**
 * Placeholder that stands in for the site's home URL inside a bundle.
 */
const WT_BUNDLE_HOME_TOKEN = '{{home}}';

/**
 * Replace every occurrence of one string with another, recursively.
 *
 * Walks arrays (ACF link fields, repeaters) and leaves non-string scalars alone.
 *
 * @param mixed  $value   Value to walk.
 * @param string $search  String to find.
 * @param string $replace Replacement.
 * @return mixed
 */
function wt_bundle_replace_recursive( $value, $search, $replace ) {
	if ( '' === $search ) {
		return $value;
	}
	if ( is_string( $value ) ) {
		return str_replace( $search, $replace, $value );
	}
	if ( is_array( $value ) ) {
		foreach ( $value as $k => $v ) {
			$value[ $k ] = wt_bundle_replace_recursive( $v, $search, $replace );
		}
	}
	return $value;
}

/**
 * Swap the source site's home URL for the bundle token.
 *
 * @param mixed  $value Value to walk.
 * @param string $home  Source home URL, without a trailing slash.
 * @return mixed
 */
function wt_bundle_tokenize_urls( $value, $home ) {
	return wt_bundle_replace_recursive( $value, rtrim( $home, '/' ), WT_BUNDLE_HOME_TOKEN );
}

/**
 * Swap the bundle token for the target site's home URL.
 *
 * @param mixed  $value Value to walk.
 * @param string $home  Target home URL, without a trailing slash.
 * @return mixed
 */
function wt_bundle_detokenize_urls( $value, $home ) {
	return wt_bundle_replace_recursive( $value, WT_BUNDLE_HOME_TOKEN, rtrim( $home, '/' ) );
}

/**
 * Which kind of object an ACF field type stores IDs for.
 *
 * IDs differ per environment, so these values travel as slugs or file paths.
 *
 * @param string $field_type ACF field type.
 * @return string|null 'post', 'attachment', 'term', or null for plain values.
 */
function wt_bundle_ref_kind( $field_type ) {
	switch ( $field_type ) {
		case 'post_object':
		case 'relationship':
		case 'page_link':
			return 'post';
		case 'image':
		case 'file':
		case 'gallery':
			return 'attachment';
		case 'taxonomy':
			return 'term';
		default:
			return null;
	}
}

/**
 * Normalise a stored reference value to a list of IDs.
 *
 * ACF stores a single reference as a scalar and multiple as an array; empty
 * values come back as '' or false.
 *
 * @param mixed $value Stored meta value.
 * @return array<int,int>
 */
function wt_bundle_ref_ids( $value ) {
	$items = is_array( $value ) ? $value : array( $value );
	$ids   = array();
	foreach ( $items as $item ) {
		if ( is_numeric( $item ) && (int) $item > 0 ) {
			$ids[] = (int) $item;
		}
	}
	return $ids;
}
