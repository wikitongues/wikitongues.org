<?php
// Register navigation menus
add_action( 'init', 'wt_archive_menu' );
add_action( 'init', 'wt_revitalization_menu' );
add_action( 'init', 'wt_about_menu' );
add_action( 'init', 'wt_footer_menu' );
add_action( 'init', 'wt_mobile_menu' );

function wt_archive_menu() {
	register_nav_menu( 'archive-menu', __( 'Archive Menu' ) );
}

function wt_revitalization_menu() {
	register_nav_menu( 'revitalization-menu', __( 'Revitalization Menu' ) );
}

function wt_about_menu() {
	register_nav_menu( 'about-menu', __( 'About Menu' ) );
}

function wt_footer_menu() {
	register_nav_menu( 'footer-menu', __( 'Footer Menu' ) );
}

function wt_mobile_menu() {
	register_nav_menu( 'mobile-menu', __( 'Mobile Menu' ) );
}

/**
 * Which secondary (sub) navigation menu the current page shows, if any.
 *
 * Single source of truth for the subnav: header.php renders the menu this
 * returns and nothing otherwise. Page spacing needs no matching list in CSS,
 * because the header is sticky and takes up its own height in the layout.
 *
 * @return string|null Registered menu location, or null for no subnav.
 */
function wt_secondary_menu_location() {
	if ( is_front_page() || is_page_template( 'template-giving-campaign.php' ) ) {
		return null;
	}

	$template_slug = (string) get_page_template_slug();

	if (
		strpos( $template_slug, 'revitalization' ) !== false ||
		is_singular( array( 'documents', 'fellows' ) ) ||
		is_tax( 'fellow-category' )
	) {
		return 'revitalization-menu';
	}

	if (
		strpos( $template_slug, 'archive' ) !== false ||
		is_post_type_archive( array( 'languages', 'videos', 'fellows', 'territories' ) ) ||
		is_singular( array( 'languages', 'videos', 'territories' ) ) ||
		is_tax( 'region' ) ||
		is_search()
	) {
		return 'archive-menu';
	}

	// template-people.php replaced template-about-board/-staff, which matched
	// on 'about'. Match it explicitly so Board, Advisors and Staff keep the
	// About sub-navigation.
	if ( strpos( $template_slug, 'about' ) !== false || strpos( $template_slug, 'people' ) !== false ) {
		return 'about-menu';
	}

	return null;
}

function wt_highlight_menu_items( $classes, $item ) {
	$request_uri = $_SERVER['REQUEST_URI'];

	$is_fellow         = is_singular( 'fellows' ) || is_tax( 'fellow-category' );
	$is_revitalization = (
		strpos( $request_uri, '/revitalization' ) !== false ||
		strpos( $request_uri, '/documents' ) !== false ||
		$is_fellow
	);

	// Highlight top-level Revitalization
	if ( strpos( $item->url, '/revitalization' ) !== false && $is_revitalization ) {
		$classes[] = 'current-menu-item';
	}

	// Highlight "Fellows" in the revitalization subnav
	if (
		strpos( $item->url, '/revitalization/fellows' ) !== false &&
		(
			is_singular( 'fellows' ) ||
			is_tax( 'fellow-category' ) ||
			strpos( $request_uri, '/revitalization/fellows' ) !== false
		)
	) {
		$classes[] = 'current_page_item';
	}

	return $classes;
}
add_filter( 'nav_menu_css_class', 'wt_highlight_menu_items', 10, 2 );

add_filter(
	'nav_menu_link_attributes',
	function ( $atts, $item ) {
		if ( strpos( $item->url, 'donorsupport.co' ) !== false ) {
			$atts['data-cta-location'] = 'nav';
		}
		return $atts;
	},
	10,
	2
);
