<?php
// Route
function wikitongues_custom_template_redirects() {
	// Archive redirects
	if (is_post_type_archive('fellows')) {
		wp_redirect(home_url('/revitalization/fellows', 'relative'));
		exit;
	}

	if (is_post_type_archive(['languages', 'videos', 'lexicons', 'resources'])) {
		wp_redirect(home_url('/archive', 'relative'));
		exit;
	}

	if (is_post_type_archive(['team'])) {
		wp_redirect(home_url('/about/staff-and-volunteers/', 'relative'));
		exit;
	}

	if (is_post_type_archive(['partners'])) {
		wp_redirect(home_url('/', 'relative'));
		exit;
	}

	// Single redirects

	// Redirect lexicons to their source_language page
	if ( is_singular('lexicons') ) {
		$language_post = get_field('source_languages');

		if ( $language_post && get_post_status($language_post) === 'publish' ) {
			wp_redirect( get_permalink($language_post) );
			exit;
		} else {
			wp_redirect(home_url('/archive', 'relative'));
			exit;
		}
	}

	// Redirect resources to their resource_language page
	if ( is_singular('resources') ) {
		$language_post = get_field('resource_language');

		if ( $language_post && get_post_status($language_post) === 'publish' ) {
			wp_redirect( get_permalink($language_post) );
			exit;
		} else {
			wp_redirect(home_url('/archive', 'relative'));
			exit;
		}
	}

	if (is_singular('partners')) {
		wp_redirect(home_url('/', 'relative'));
		exit;
	}

	if (is_singular('events')) {
		wp_redirect(home_url('/events', 'relative'));
		exit;
	}

	if (is_singular('reports')) {
		wp_redirect(home_url('/reports', 'relative'));
		exit;
	}
	if ( is_singular('team') ) {
		wp_redirect( home_url('/about/staff-and-volunteers/', 'relative') );
		exit;
	}
}
add_action('template_redirect', 'wikitongues_custom_template_redirects');
