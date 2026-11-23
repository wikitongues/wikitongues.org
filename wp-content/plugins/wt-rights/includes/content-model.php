<?php
/**
 * The report on the site: the `rights_reports` post type, the `rights_region`
 * taxonomy, their URLs, and the press role.
 *
 *   /language-rights/report/            the hub
 *   /language-rights/report/{region}/   a region (a `rights_region` term)
 *   /language-rights/report/{country}/  a country report (a `rights_reports` post)
 *
 * Regions and countries share the one URL segment; the import refuses a country
 * slug that equals a region key, so a slug always means one thing.
 *
 * Reports are written only by the importer. Their post status carries the
 * embargo (see Release_Status), with its own capability type so the press role
 * can read private reports without reading any other private post.
 *
 * @package WT\Rights
 */

/**
 * The URL base, without slashes.
 */
const WT_RIGHTS_BASE = 'language-rights/report';

/**
 * Bump when the press role or the capabilities change, so they are set again.
 */
const WT_RIGHTS_ROLES_VERSION = '1';

/**
 * Registers the post type and the taxonomy.
 */
function wt_rights_register_content_model(): void {
	register_post_type(
		'rights_reports',
		array(
			'labels'              => array(
				'name'          => __( 'Language Rights Reports' ),
				'singular_name' => __( 'Language Rights Report' ),
				'edit_item'     => __( 'Language Rights Report' ),
				'search_items'  => __( 'Search reports' ),
			),
			'description'         => 'Country reports from the Report on the Global State of Language Rights. Written by `wp wt rights import`; edit the Google Doc and Airtable, not this post.',
			'public'              => true,
			'exclude_from_search' => false,
			'show_in_rest'        => false,
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => 'rights_reports',
			'menu_icon'           => 'dashicons-shield',
			'supports'            => array( 'title' ),
			'capability_type'     => array( 'rights_report', 'rights_reports' ),
			'map_meta_cap'        => true,
			'taxonomies'          => array( 'rights_region' ),
		)
	);

	register_taxonomy(
		'rights_region',
		array( 'rights_reports' ),
		array(
			'labels'            => array(
				'name'          => __( 'Report regions' ),
				'singular_name' => __( 'Report region' ),
			),
			'public'            => true,
			'hierarchical'      => false,
			'show_in_rest'      => false,
			'show_admin_column' => true,
			'rewrite'           => false,
			'query_var'         => 'rights_region',
			'capabilities'      => array(
				'manage_terms' => 'edit_rights_reports',
				'edit_terms'   => 'edit_rights_reports',
				'delete_terms' => 'edit_rights_reports',
				'assign_terms' => 'edit_rights_reports',
			),
		)
	);
}
add_action( 'init', 'wt_rights_register_content_model' );

/**
 * The URL rules: the hub, and one segment for a region or a country.
 */
function wt_rights_add_rewrite_rules(): void {
	add_rewrite_rule( '^' . WT_RIGHTS_BASE . '/?$', 'index.php?wt_rights_hub=1', 'top' );
	add_rewrite_rule( '^' . WT_RIGHTS_BASE . '/([^/]+)/?$', 'index.php?wt_rights_slug=$matches[1]', 'top' );
}
add_action( 'init', 'wt_rights_add_rewrite_rules' );

/**
 * Registers the query vars.
 *
 * @param list<string> $vars Query vars.
 * @return list<string>
 */
function wt_rights_query_vars( array $vars ): array {
	$vars[] = 'wt_rights_hub';
	$vars[] = 'wt_rights_slug';
	return $vars;
}
add_filter( 'query_vars', 'wt_rights_query_vars' );

/**
 * Resolves the shared segment to a region or a country.
 *
 * @param array<string, mixed> $query_vars The request's query vars.
 * @return array<string, mixed>
 */
function wt_rights_resolve_request( array $query_vars ): array {
	if ( empty( $query_vars['wt_rights_slug'] ) ) {
		return $query_vars;
	}
	$slug = sanitize_title( (string) $query_vars['wt_rights_slug'] );
	unset( $query_vars['wt_rights_slug'] );
	if ( term_exists( $slug, 'rights_region' ) ) {
		$query_vars['rights_region'] = $slug;
		return $query_vars;
	}
	$query_vars['post_type']      = 'rights_reports';
	$query_vars['rights_reports'] = $slug;
	$query_vars['name']           = $slug;
	return $query_vars;
}
add_filter( 'request', 'wt_rights_resolve_request' );

/**
 * A report's URL.
 *
 * @param string  $url  The default URL.
 * @param WP_Post $post The post.
 */
function wt_rights_report_link( string $url, WP_Post $post ): string {
	if ( 'rights_reports' !== $post->post_type || '' === $post->post_name ) {
		return $url;
	}
	return home_url( '/' . WT_RIGHTS_BASE . '/' . $post->post_name . '/' );
}
add_filter( 'post_type_link', 'wt_rights_report_link', 10, 2 );

/**
 * A region's URL.
 *
 * @param string  $url      The default URL.
 * @param WP_Term $term     The term.
 * @param string  $taxonomy The taxonomy.
 */
function wt_rights_region_link( string $url, WP_Term $term, string $taxonomy ): string {
	return 'rights_region' === $taxonomy ? home_url( '/' . WT_RIGHTS_BASE . '/' . $term->slug . '/' ) : $url;
}
add_filter( 'term_link', 'wt_rights_region_link', 10, 3 );

/**
 * Drops WordPress's "Private:" prefix from embargoed report titles.
 *
 * @param string       $format The title format.
 * @param WP_Post|null $post   The post.
 */
function wt_rights_private_title_format( string $format, $post = null ): string {
	return ( $post instanceof WP_Post && 'rights_reports' === $post->post_type ) ? '%s' : $format;
}
add_filter( 'private_title_format', 'wt_rights_private_title_format', 10, 2 );

/**
 * The capabilities of the report post type, from its capability_type.
 *
 * @return list<string>
 */
function wt_rights_capabilities(): array {
	return array(
		'edit_rights_reports',
		'edit_others_rights_reports',
		'edit_private_rights_reports',
		'edit_published_rights_reports',
		'publish_rights_reports',
		'read_private_rights_reports',
		'delete_rights_reports',
		'delete_others_rights_reports',
		'delete_private_rights_reports',
		'delete_published_rights_reports',
	);
}

/**
 * Gives admins the report capabilities and creates the press role, once per
 * WT_RIGHTS_ROLES_VERSION. The press role can read embargoed (private)
 * reports and nothing else beyond a subscriber.
 */
function wt_rights_setup_roles(): void {
	if ( WT_RIGHTS_ROLES_VERSION === get_option( 'wt_rights_roles_version' ) ) {
		return;
	}
	$admin = get_role( 'administrator' );
	if ( $admin ) {
		foreach ( wt_rights_capabilities() as $cap ) {
			$admin->add_cap( $cap );
		}
	}
	remove_role( 'press' );
	add_role(
		'press',
		__( 'Press' ),
		array(
			'read'                        => true,
			'read_private_rights_reports' => true,
		)
	);
	update_option( 'wt_rights_roles_version', WT_RIGHTS_ROLES_VERSION, false );
}
add_action( 'init', 'wt_rights_setup_roles', 20 );

/**
 * A region's release status on this site.
 *
 * @param int $term_id The region term.
 */
function wt_rights_region_status( int $term_id ): string {
	$status = (string) get_term_meta( $term_id, 'release_status', true );
	return \WT\Rights\Release_Status::is_valid( $status ) ? $status : \WT\Rights\Release_Status::UPCOMING;
}

/**
 * Whether the current visitor may read a region's reports: everyone once it
 * is released, and admins and press while it is embargoed.
 *
 * @param int $term_id The region term.
 */
function wt_rights_can_read_region( int $term_id ): bool {
	$status = wt_rights_region_status( $term_id );
	if ( \WT\Rights\Release_Status::RELEASED === $status ) {
		return true;
	}
	if ( \WT\Rights\Release_Status::EMBARGOED === $status ) {
		return current_user_can( 'read_private_rights_reports' );
	}
	return current_user_can( 'edit_rights_reports' );
}
