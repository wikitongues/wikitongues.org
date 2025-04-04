<?php
// Register navigation menus
add_action('init', 'wt_archive_menu');
add_action('init', 'wt_revitalization_menu');
add_action('init', 'wt_about_menu');
add_action('init', 'wt_footer_menu');
add_action('init', 'wt_mobile_menu');

function wt_archive_menu() {
    register_nav_menu('archive-menu', __('Archive Menu'));
}

function wt_revitalization_menu() {
    register_nav_menu('revitalization-menu', __('Revitalization Menu'));
}

function wt_about_menu() {
    register_nav_menu('about-menu', __('About Menu'));
}

function wt_footer_menu() {
    register_nav_menu('footer-menu', __('Footer Menu'));
}

function wt_mobile_menu() {
    register_nav_menu('mobile-menu', __('Mobile Menu'));
}

function wt_highlight_menu_items($classes, $item) {
	$request_uri = $_SERVER['REQUEST_URI'];

	$is_fellow = is_singular('fellows') || is_tax('fellow-category');
	$is_revitalization = (
		strpos($request_uri, '/revitalization') !== false ||
		strpos($request_uri, '/documents') !== false ||
		$is_fellow
	);

	// Highlight top-level Revitalization
	if (strpos($item->url, '/revitalization') !== false && $is_revitalization) {
		$classes[] = 'current-menu-item';
	}

	// Highlight "Fellows" in the revitalization subnav
	if (
		strpos($item->url, '/revitalization/fellows') !== false &&
		(
			is_singular('fellows') ||
			is_tax('fellow-category') ||
			strpos($request_uri, '/revitalization/fellows') !== false
		)
	) {
		$classes[] = 'current_page_item';
	}

	return $classes;
}
add_filter('nav_menu_css_class', 'wt_highlight_menu_items', 10, 2);