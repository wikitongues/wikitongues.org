<?php
// Lexicons: create language object
if ( ! function_exists( 'get_language_gallery_html' ) ) {
	function get_language_gallery_html( $language_post_id ) {
		if ( ! $language_post_id ) {
				return '';
		}
		if ( is_array( $language_post_id ) ) {
			$language_post_id = $language_post_id['ID'];
			// log_data("is array", "dom");
		} else {
			$language_post_id = $language_post_id;
			// log_data("not array", "dom");
		}
		$post_title    = get_the_title( $language_post_id );
		$standard_name = get_field( 'standard_name', $language_post_id );
		return "<div class='language'><span class='identifier'>{$post_title}</span><p>{$standard_name}</p></div>";
	}
}

// Resources: Get Domain
if ( ! function_exists( 'getDomainFromUrl' ) ) {
	function getDomainFromUrl( $url ) {
		$host = parse_url( $url, PHP_URL_HOST );
		if ( substr( $host, 0, 4 ) === 'www.' ) {
			$host = substr( $host, 4 );
		}
		return $host;
	}
}
