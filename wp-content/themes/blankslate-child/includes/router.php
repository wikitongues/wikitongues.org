<?php
// Route
function wikitongues_custom_template_redirects() {
	if ( get_query_var( 'region' ) || is_singular( 'territories' ) || is_post_type_archive( 'territories' ) || is_tax( 'region' ) ) {
		return; // Don't redirect territories or regions
	}

	// Archive redirects
	if ( is_post_type_archive( 'fellows' ) ) {
		if ( isset( $_GET['territory'] ) ) {
			return; // Serve archive-fellows.php with territory filter.
		}
		wp_redirect( home_url( '/revitalization/fellows', 'relative' ) );
		exit;
	}

	if ( is_post_type_archive( array( 'languages', 'videos', 'lexicons', 'resources', 'captions' ) ) ) {
		if ( is_post_type_archive( 'languages' ) &&
			( isset( $_GET['territory'] ) || isset( $_GET['genealogy'] ) || isset( $_GET['writing_system'] ) ) ) {
			return; // Serve archive-languages.php with filter.
		}
		if ( is_post_type_archive( 'videos' ) && isset( $_GET['language'] ) ) {
			return; // Serve archive-videos.php with language filter.
		}
		wp_redirect( home_url( '/archive', 'relative' ) );
		exit;
	}

	if ( is_post_type_archive( array( 'team' ) ) ) {
		wp_redirect( home_url( '/about/staff-and-volunteers/', 'relative' ) );
		exit;
	}

	if ( is_post_type_archive( array( 'partners' ) ) ) {
		wp_redirect( home_url( '/', 'relative' ) );
		exit;
	}

	if ( is_post_type_archive( array( 'documents' ) ) ) {
		wp_redirect( home_url( '/revitalization', 'relative' ) );
		exit;
	}

	// Single redirects

	// Redirect captions to their source_video page
	if ( is_singular( 'captions' ) ) {
		$source_video = get_field( 'source_video' );

		if ( $source_video && get_post_status( $source_video ) === 'publish' ) {
			wp_redirect( get_permalink( $source_video ) );
			exit;
		} else {
			wp_redirect( home_url( '/archive', 'relative' ) );
			exit;
		}
	}

	// Redirect lexicons to their source_language page
	if ( is_singular( 'lexicons' ) ) {
		$language_post = get_field( 'source_languages' );

		if ( $language_post && get_post_status( $language_post ) === 'publish' ) {
			wp_redirect( get_permalink( $language_post ) );
			exit;
		} else {
			wp_redirect( home_url( '/archive', 'relative' ) );
			exit;
		}
	}

	// Redirect resources to their resource_language page
	if ( is_singular( 'resources' ) ) {
		$language_post = get_field( 'resource_language' );

		if ( $language_post && get_post_status( $language_post ) === 'publish' ) {
			wp_redirect( get_permalink( $language_post ) );
			exit;
		} else {
			wp_redirect( home_url( '/archive', 'relative' ) );
			exit;
		}
	}

	// Redirect /fellow-category/ to first fellow-category term
	$request_path = trim( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ), '/' );
	// For local development, remove the subdirectory from the request path (if WP is installed in one)
	$home_path = trim( parse_url( home_url(), PHP_URL_PATH ), '/' );
	if ( $home_path && str_starts_with( $request_path, $home_path ) ) {
			$request_path = trim( substr( $request_path, strlen( $home_path ) ), '/' );
	}
	if ( is_404() && $request_path === 'fellow-category' ) {
		// log_data( '404 fellow-category root detected' );

		$terms = get_terms(
			array(
				'taxonomy'   => 'fellow-category',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
				'number'     => 1,
			)
		);

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			wp_redirect( get_term_link( $terms[0] ) );
			exit;
		}
	}

	if ( is_singular( 'partners' ) ) {
		wp_redirect( home_url( '/', 'relative' ) );
		exit;
	}

	if ( is_singular( 'events' ) ) {
		wp_redirect( home_url( '/events', 'relative' ) );
		exit;
	}

	if ( is_singular( 'reports' ) ) {
		wp_redirect( home_url( '/reports', 'relative' ) );
		exit;
	}
	if ( is_singular( 'team' ) ) {
		wp_redirect( home_url( '/about/staff-and-volunteers/', 'relative' ) );
		exit;
	}
}
add_action( 'template_redirect', 'wikitongues_custom_template_redirects' );
