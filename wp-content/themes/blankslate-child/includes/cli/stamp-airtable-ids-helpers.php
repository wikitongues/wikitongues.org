<?php
/**
 * Pure planning logic for `wp wt stamp-airtable-ids`.
 *
 * Kept free of WordPress calls so it can be unit tested.
 *
 * @package Wikitongues
 */

/**
 * Plan which posts get which Airtable record ID.
 *
 * Matching is by exact title, the one time names have to agree; after the stamp
 * the sync matches by record ID. The plan never overwrites: a post that already
 * holds a different ID, or an ID already held by another post, is reported as a
 * conflict and left alone. Running it again after a stamp plans nothing.
 *
 * @param array<int, array{id: string, name: string}>         $records Airtable records.
 * @param array<int, array{title: string, record_id: string}> $posts   Post ID => title and current stamped ID ('' if none).
 * @return array{
 *     stamp: array<int, string>,
 *     already: int,
 *     conflicts: array<int, string>,
 *     unmatched: array<int, string>,
 *     ambiguous: array<int, string>,
 *     unstamped_posts: array<int, string>
 * }
 */
function wt_plan_airtable_id_stamps( array $records, array $posts ): array {
	$by_title = array();
	$holder   = array();
	foreach ( $posts as $post_id => $post ) {
		$by_title[ $post['title'] ][] = $post_id;
		if ( '' !== $post['record_id'] ) {
			$holder[ $post['record_id'] ] = $post_id;
		}
	}

	$plan = array(
		'stamp'           => array(),
		'already'         => 0,
		'conflicts'       => array(),
		'unmatched'       => array(),
		'ambiguous'       => array(),
		'unstamped_posts' => array(),
	);

	foreach ( $records as $record ) {
		$id    = $record['id'];
		$name  = $record['name'];
		$match = $by_title[ $name ] ?? array();

		if ( ! $match ) {
			$plan['unmatched'][] = $name;
			continue;
		}
		if ( count( $match ) > 1 ) {
			$plan['ambiguous'][] = $name;
			continue;
		}

		$post_id = $match[0];
		$current = $posts[ $post_id ]['record_id'];

		if ( $current === $id ) {
			++$plan['already'];
		} elseif ( '' !== $current ) {
			$plan['conflicts'][] = sprintf( '%s: post %d holds %s, Airtable has %s', $name, $post_id, $current, $id );
		} elseif ( isset( $holder[ $id ] ) ) {
			$plan['conflicts'][] = sprintf( '%s: %s is already on post %d', $name, $id, $holder[ $id ] );
		} else {
			$plan['stamp'][ $post_id ] = $id;
			$holder[ $id ]             = $post_id;
		}
	}

	foreach ( $posts as $post_id => $post ) {
		if ( '' === $post['record_id'] && ! isset( $plan['stamp'][ $post_id ] ) ) {
			$plan['unstamped_posts'][] = $post['title'];
		}
	}

	return $plan;
}
