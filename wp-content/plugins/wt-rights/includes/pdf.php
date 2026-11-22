<?php
/**
 * Country report PDFs.
 *
 * Rendered by headless Chrome from the report page's print view, so the PDF
 * and the page never disagree. The print view is reachable for any report,
 * whatever its status, with a token only this site's salts can make, so
 * `wp wt rights pdf` can print embargoed reports before release.
 *
 * Files are named {slug}-v{version}-{hash}.pdf, so each version has its own.
 * Until a region is released its PDFs sit in WT_RIGHTS_PRIVATE_DIR, outside
 * the web root, and exist at no public URL; releasing the region copies them
 * into the media library, and un-releasing removes them again.
 *
 * @package WT\Rights
 */

/**
 * The print token for a report.
 *
 * @param string $slug    The report's slug.
 * @param string $version Its version.
 */
function wt_rights_print_token( string $slug, string $version ): string {
	return substr( hash_hmac( 'sha256', 'wt-rights-print|' . $slug . '|' . $version, wp_salt( 'auth' ) ), 0, 32 );
}

/**
 * The print view's URL for a report post.
 *
 * @param WP_Post $post A report.
 */
function wt_rights_print_url( WP_Post $post ): string {
	$version = (string) get_post_meta( $post->ID, '_rights_version', true );
	return add_query_arg( 'wt_rights_print', wt_rights_print_token( $post->post_name, $version ), home_url( '/' . WT_RIGHTS_BASE . '/' . $post->post_name . '/' ) );
}

/**
 * Registers the print query var.
 *
 * @param list<string> $vars Query vars.
 * @return list<string>
 */
function wt_rights_print_query_var( array $vars ): array {
	$vars[] = 'wt_rights_print';
	return $vars;
}
add_filter( 'query_vars', 'wt_rights_print_query_var' );

/**
 * With a valid print token, lets the report be read whatever its status.
 *
 * @param WP_Query $query A query.
 */
function wt_rights_print_any_status( WP_Query $query ): void {
	if ( ! $query->is_main_query() || '' === (string) $query->get( 'wt_rights_print' ) || 'rights_reports' !== $query->get( 'post_type' ) ) {
		return;
	}
	$slug = (string) $query->get( 'name' );
	$ids  = get_posts(
		array(
			'post_type'        => 'rights_reports',
			'name'             => $slug,
			'post_status'      => array( 'publish', 'private', 'draft' ),
			'numberposts'      => 1,
			'fields'           => 'ids',
			'suppress_filters' => true,
		)
	);
	if ( ! $ids ) {
		return;
	}
	$version = (string) get_post_meta( (int) $ids[0], '_rights_version', true );
	if ( hash_equals( wt_rights_print_token( $slug, $version ), (string) $query->get( 'wt_rights_print' ) ) ) {
		$query->set( 'post_status', array( 'publish', 'private', 'draft' ) );
		add_filter( 'body_class', static fn( array $classes ): array => array_merge( $classes, array( 'wt-rights-print' ) ) );
		add_action( 'wp_head', static fn() => print( '<meta name="robots" content="noindex, nofollow">' . "\n" ), 0 );
		// The official PDF prints every source list open; Cmd+P keeps them collapsed.
		add_action( 'wp_footer', static fn() => print( '<script>document.querySelectorAll(".wt-rights details").forEach(function(d){d.open=true;});</script>' . "\n" ), 99 );
	}
}
add_action( 'pre_get_posts', 'wt_rights_print_any_status' );

/**
 * A report's PDF file name.
 *
 * @param string $slug    Slug.
 * @param string $version Version.
 * @param string $hash    Content hash.
 */
function wt_rights_pdf_name( string $slug, string $version, string $hash ): string {
	return sanitize_file_name( $slug . '-v' . $version . '-' . substr( $hash, 0, 8 ) . '.pdf' );
}

/**
 * The private PDF folder, or an error saying why it can't be used. It must be
 * set in wp-config.php and sit outside the web root.
 *
 * @return string|WP_Error
 */
function wt_rights_private_dir() {
	if ( ! defined( 'WT_RIGHTS_PRIVATE_DIR' ) || '' === (string) WT_RIGHTS_PRIVATE_DIR ) {
		return new WP_Error( 'no_private_dir', 'Define WT_RIGHTS_PRIVATE_DIR in wp-config.php: a folder outside the web root, e.g. ~/rights-private.' );
	}
	$dir = rtrim( (string) WT_RIGHTS_PRIVATE_DIR, '/' );
	if ( ! wp_mkdir_p( $dir ) ) {
		return new WP_Error( 'private_dir_unwritable', 'Cannot create ' . $dir . '.' );
	}
	$real = (string) realpath( $dir );
	if ( ! wt_rights_outside_web_root( $real, (string) realpath( ABSPATH ) ) ) {
		return new WP_Error( 'private_dir_public', $real . ' is inside the web root. Unreleased PDFs must never be reachable by URL.' );
	}
	return $real;
}

/**
 * Whether a folder is outside the web root: not inside the WordPress folder,
 * and not inside a folder a web server conventionally serves. Staging lives
 * inside production's public_html, so the WordPress folder alone isn't enough.
 *
 * @param string $dir     A real path.
 * @param string $abspath WordPress's real path.
 */
function wt_rights_outside_web_root( string $dir, string $abspath ): bool {
	if ( '' === $dir ) {
		return false;
	}
	if ( str_starts_with( $dir . '/', rtrim( $abspath, '/' ) . '/' ) ) {
		return false;
	}
	return ! preg_match( '#/(public_html|htdocs|www|html)(/|$)#', $dir );
}

/**
 * Publishes or unpublishes a region's PDFs to match its status: released
 * reports get their current version's PDF in the media library; everything
 * else has none. Idempotent.
 *
 * @param int $term_id A region term.
 * @return array{published: int, removed: int, missing: int}
 */
function wt_rights_sync_region_pdfs( int $term_id ): array {
	$counts   = array(
		'published' => 0,
		'removed'   => 0,
		'missing'   => 0,
	);
	$released = \WT\Rights\Release_Status::RELEASED === wt_rights_region_status( $term_id );
	$ids      = get_posts(
		array(
			'post_type'        => 'rights_reports',
			'post_status'      => array( 'publish', 'private', 'draft' ),
			'numberposts'      => -1,
			'fields'           => 'ids',
			'suppress_filters' => true,
			'tax_query'        => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- one region, CLI only.
				array(
					'taxonomy' => 'rights_region',
					'terms'    => array( $term_id ),
				),
			),
		)
	);
	foreach ( $ids as $id ) {
		$id       = (int) $id;
		$want     = (string) get_post_meta( $id, '_rights_pdf_file', true );
		$current  = (int) get_post_meta( $id, '_rights_pdf_attachment', true );
		$has_name = $current ? (string) get_post_meta( $current, '_rights_pdf_file', true ) : '';
		$publish  = $released && 'publish' === get_post_status( $id ) && '' !== $want;

		if ( $current && ( ! $publish || $has_name !== $want ) ) {
			wp_delete_attachment( $current, true );
			delete_post_meta( $id, '_rights_pdf_attachment' );
			$current = 0;
			++$counts['removed'];
		}
		if ( ! $publish || $current ) {
			continue;
		}
		$attachment = wt_rights_publish_pdf( $id, $want );
		if ( $attachment ) {
			update_post_meta( $id, '_rights_pdf_attachment', $attachment );
			++$counts['published'];
		} else {
			++$counts['missing'];
		}
	}
	return $counts;
}

/**
 * Copies a PDF from the private folder into uploads/rights/ as an attachment.
 *
 * @param int    $post_id The report.
 * @param string $name    The PDF's file name.
 * @return int The attachment ID, or 0 when the file isn't in the private folder.
 */
function wt_rights_publish_pdf( int $post_id, string $name ): int {
	$dir = wt_rights_private_dir();
	if ( is_wp_error( $dir ) || ! is_readable( $dir . '/' . $name ) ) {
		return 0;
	}
	$uploads = wp_upload_dir();
	$target  = trailingslashit( $uploads['basedir'] ) . 'rights/' . $name;
	wp_mkdir_p( dirname( $target ) );
	if ( ! copy( $dir . '/' . $name, $target ) ) {
		return 0;
	}
	$attachment = wp_insert_attachment(
		array(
			'post_mime_type' => 'application/pdf',
			'post_title'     => get_the_title( $post_id ) . ' — language rights report (PDF)',
			'post_status'    => 'inherit',
			'post_parent'    => $post_id,
		),
		$target,
		$post_id,
		true
	);
	if ( is_wp_error( $attachment ) ) {
		wp_delete_file( $target );
		return 0;
	}
	update_post_meta( (int) $attachment, '_rights_pdf_file', $name );
	return (int) $attachment;
}

/**
 * The download link for a report's PDF, through the download gateway, or ''
 * when there is no published PDF or the gateway is off. Never an ungated link.
 *
 * @param int    $post_id A report.
 * @param string $source  data-download-source for analytics.
 */
function wt_rights_pdf_link( int $post_id, string $source ): string {
	if ( 'publish' !== get_post_status( $post_id ) || ! get_post_meta( $post_id, '_rights_pdf_attachment', true ) || ! shortcode_exists( 'gateway_download' ) ) {
		return '';
	}
	return '<p class="wt-rights__pdf">' . do_shortcode( '[gateway_download id="' . $post_id . '" label="Download the report (PDF)" source="' . esc_attr( $source ) . '"]' ) . '</p>';
}
