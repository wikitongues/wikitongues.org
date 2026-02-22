<?php
/**
 * Field_Resolver — converts Airtable title strings to WP post IDs.
 *
 * Used for ACF post_object fields where the payload contains the post title
 * (or a comma-separated list of titles) rather than a WP post ID.
 *
 * Replaces the deprecated get_page_by_title() used by the old
 * post-object-helpers.php integration. Uses WP_Query instead.
 *
 * @package WT\AirtableSync
 */

namespace WT\AirtableSync;

class Field_Resolver {

	/**
	 * Resolve one or more post titles to an array of WP post IDs.
	 *
	 * @param string|string[] $value     Single title, comma-separated titles, or array of titles.
	 * @param string          $post_type The CPT to search within.
	 * @return int[]                     Array of matched WP post IDs. Unmatched titles are skipped.
	 */
	public static function resolve( string|array $value, string $post_type ): array {
		$titles = self::normalise_titles( $value );

		if ( empty( $titles ) ) {
			return array();
		}

		$ids = array();

		foreach ( $titles as $title ) {
			$id = self::find_post_by_title( $title, $post_type );

			if ( $id ) {
				$ids[] = $id;
			} else {
				Logger::error(
					sprintf(
						'Field_Resolver: no %s post found for title "%s".',
						$post_type,
						$title
					)
				);
			}
		}

		return $ids;
	}

	/**
	 * Find a single post ID by exact title within a given CPT.
	 *
	 * Returns 0 if no match is found.
	 *
	 * @param string $title     Post title to search for.
	 * @param string $post_type CPT slug to search within.
	 * @return int
	 */
	private static function find_post_by_title( string $title, string $post_type ): int {
		$query = new \WP_Query(
			array(
				'post_type'              => $post_type,
				'title'                  => $title,
				'post_status'            => 'any',
				'posts_per_page'         => 1,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		if ( $query->have_posts() ) {
			return (int) $query->posts[0]->ID;
		}

		return 0;
	}

	/**
	 * Normalise the incoming value to a flat array of trimmed, non-empty title strings.
	 *
	 * Handles:
	 *   - Array of strings
	 *   - Comma-separated string  (e.g. "Chichewa, Tonga")
	 *   - Non-breaking spaces (chr 194 + chr 160) from Airtable copy-paste
	 *
	 * @param string|string[] $value Raw value from the payload.
	 * @return string[]
	 */
	private static function normalise_titles( string|array $value ): array {
		$raw = is_array( $value ) ? $value : explode( ',', $value );

		return array_values(
			array_filter(
				array_map(
					static function ( string $title ): string {
						// Strip Airtable non-breaking spaces before trimming.
						$title = str_replace( "\xc2\xa0", ' ', $title );
						return trim( $title );
					},
					$raw
				)
			)
		);
	}
}
