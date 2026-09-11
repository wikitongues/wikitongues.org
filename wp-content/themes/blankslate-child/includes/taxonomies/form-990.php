<?php
/**
 * Form 990 CPT — the organisation's annual IRS filings.
 *
 * Replaces the retired `reports` CPT. An admin adds one entry per filing: a
 * title (the link text on the page), the tax year it covers, and the PDF.
 *
 * The public page is this post type's archive at /financials, listing every
 * filing newest tax year first. Entries have no page of their own — router.php
 * sends a single back to the archive — so the type is not `public`, which keeps
 * it out of search, sitemaps and the menu editor; `publicly_queryable` is what
 * keeps the archive routable.
 *
 * Files link straight to the media library rather than through the download
 * gateway: 990s are public documents.
 *
 * @package Wikitongues
 */

add_action( 'init', 'create_post_type_form_990' );
function create_post_type_form_990() {
	register_post_type(
		'form_990',
		array(
			'labels'             => array(
				'name'               => __( 'Form 990s', 'form_990' ),
				'singular_name'      => __( 'Form 990', 'form_990' ),
				'add_new'            => __( 'Add New', 'form_990' ),
				'add_new_item'       => __( 'Add New Form 990', 'form_990' ),
				'edit_item'          => __( 'Edit Form 990', 'form_990' ),
				'new_item'           => __( 'New Form 990', 'form_990' ),
				'view_item'          => __( 'View Form 990', 'form_990' ),
				'search_items'       => __( 'Search Form 990s', 'form_990' ),
				'not_found'          => __( 'No Form 990s found', 'form_990' ),
				'not_found_in_trash' => __( 'No Form 990s found in Trash', 'form_990' ),
			),
			'public'             => false,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_rest'       => false,
			'has_archive'        => 'financials',
			'rewrite'            => array(
				'slug'       => 'financials',
				'with_front' => false,
			),
			'menu_icon'          => 'dashicons-media-spreadsheet',
			'supports'           => array( 'title' ),
			'can_export'         => true,
		)
	);
}

// The archive lists every filing, newest tax year first.
add_action( 'pre_get_posts', 'wt_form_990_archive_query' );
function wt_form_990_archive_query( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_post_type_archive( 'form_990' ) ) {
		return;
	}

	$query->set( 'posts_per_page', -1 );
	$query->set( 'meta_key', 'tax_year' );
	$query->set( 'orderby', 'meta_value_num' );
	$query->set( 'order', 'DESC' );
}

/**
 * The PDF behind a filing, or null if none is attached.
 *
 * Reads the attachment ID from post meta rather than get_field(), so the
 * archive renders even where ACF's field reference meta is missing.
 *
 * @return array{url: string, size: string}|null
 */
function wt_form_990_file( int $post_id ): ?array {
	$file_id = (int) get_post_meta( $post_id, 'form_990_file', true );
	$url     = $file_id ? wp_get_attachment_url( $file_id ) : false;
	if ( ! $url ) {
		return null;
	}

	$path  = get_attached_file( $file_id );
	$bytes = ( $path && file_exists( $path ) ) ? filesize( $path ) : false;

	return array(
		'url'  => $url,
		'size' => $bytes ? (string) size_format( $bytes ) : '',
	);
}

// The title is the link text on /financials, so nudge admins towards one format.
add_filter( 'enter_title_here', 'wt_form_990_title_placeholder', 10, 2 );
function wt_form_990_title_placeholder( $placeholder, $post ) {
	return $post->post_type === 'form_990' ? __( 'e.g. 2023 Form 990', 'form_990' ) : $placeholder;
}
