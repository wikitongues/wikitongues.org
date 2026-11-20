<?php
/**
 * WP-CLI: stamp Airtable record IDs on posts the sync links to but never creates.
 *
 * The Airtable sync stamps `_airtable_record_id` on every post it writes, and
 * resolves links by that ID. Territories are not synced, so their pages carry
 * no ID and links to them could only match by title, which breaks on renames.
 * This command stamps the IDs once, from an Airtable export.
 *
 * @package Wikitongues
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * Stamp `_airtable_record_id` on posts, matched by exact title.
 *
 * Idempotent: posts already holding the same ID are counted and skipped, and
 * nothing is ever overwritten. Conflicts, unmatched names and titles shared by
 * several posts are reported to fix by hand. A second run plans no changes.
 *
 * ## OPTIONS
 *
 * --file=<path>
 * : JSON array of Airtable records: [{"id": "recXXXXXXXXXXXXXX", "name": "Greece"}, ...].
 *   The name is the record's primary field, which must equal the post title.
 *
 * [--post-type=<post-type>]
 * : Post type to stamp.
 * ---
 * default: territories
 * ---
 *
 * [--execute]
 * : Write the IDs. Without it the command only reports what it would do.
 *
 * ## EXAMPLES
 *
 *     wp wt stamp-airtable-ids --file=nations.json --allow-root
 *     wp wt stamp-airtable-ids --file=nations.json --execute --allow-root
 *
 * @param array<int,string>    $args       Positional arguments (unused).
 * @param array<string,string> $assoc_args Associative arguments.
 * @return void
 */
function wt_stamp_airtable_ids( $args, $assoc_args ) {
	global $wpdb;
	$execute   = isset( $assoc_args['execute'] );
	$post_type = $assoc_args['post-type'] ?? 'territories';
	$file      = $assoc_args['file'] ?? '';

	if ( ! is_readable( $file ) ) {
		WP_CLI::error( "Cannot read {$file}." );
	}
	$decoded = json_decode( (string) file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	if ( ! is_array( $decoded ) ) {
		WP_CLI::error( 'The file is not a JSON array.' );
	}

	$records = array();
	foreach ( $decoded as $row ) {
		$id   = trim( (string) ( $row['id'] ?? '' ) );
		$name = trim( (string) ( $row['name'] ?? '' ) );
		if ( ! preg_match( '/^rec[A-Za-z0-9]{14}$/', $id ) || '' === $name ) {
			WP_CLI::error( 'Every record needs an Airtable record ID and a name: ' . wp_json_encode( $row ) );
		}
		$records[] = array(
			'id'   => $id,
			'name' => $name,
		);
	}

	$rows  = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT p.ID, p.post_title, COALESCE(m.meta_value, '') AS record_id
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_airtable_record_id'
			WHERE p.post_type = %s AND p.post_status NOT IN ('trash', 'auto-draft', 'inherit')",
			$post_type
		)
	);
	$posts = array();
	foreach ( $rows as $row ) {
		$posts[ (int) $row->ID ] = array(
			'title'     => $row->post_title,
			'record_id' => (string) $row->record_id,
		);
	}

	$plan = wt_plan_airtable_id_stamps( $records, $posts );

	foreach ( $plan['stamp'] as $post_id => $record_id ) {
		WP_CLI::log( sprintf( '%s %s → post %d (%s)', $execute ? 'Stamped' : 'Would stamp', $record_id, $post_id, $posts[ $post_id ]['title'] ) );
		if ( $execute ) {
			update_post_meta( $post_id, '_airtable_record_id', $record_id );
		}
	}
	foreach ( array( 'conflicts', 'ambiguous', 'unmatched' ) as $kind ) {
		foreach ( $plan[ $kind ] as $line ) {
			WP_CLI::warning( "{$kind}: {$line}" );
		}
	}
	if ( $plan['unstamped_posts'] ) {
		WP_CLI::log( sprintf( '%d %s posts have no Airtable record: %s', count( $plan['unstamped_posts'] ), $post_type, implode( ', ', $plan['unstamped_posts'] ) ) );
	}

	WP_CLI::success(
		sprintf(
			'%d %s, %d already stamped, %d conflicts, %d ambiguous, %d unmatched.',
			count( $plan['stamp'] ),
			$execute ? 'stamped' : 'to stamp (dry run)',
			$plan['already'],
			count( $plan['conflicts'] ),
			count( $plan['ambiguous'] ),
			count( $plan['unmatched'] )
		)
	);
}

WP_CLI::add_command( 'wt stamp-airtable-ids', 'wt_stamp_airtable_ids' );
