<?php
/**
 * Field_Resolver — converts Airtable references to WP post IDs.
 *
 * Used for ACF post_object fields. The payload holds Airtable record IDs
 * (preferred) or post titles, as an array or a comma-separated string.
 *
 * A value shaped like a record ID (recXXXXXXXXXXXXXX) is matched against the
 * `_airtable_record_id` postmeta the sync stamps on every post, so a rename in
 * either system never breaks the link. Anything else is matched by exact title,
 * which is kept as a fallback for payloads that still send names.
 *
 * Replaces the deprecated get_page_by_title() used by the old
 * post-object-helpers.php integration. Uses WP_Query instead.
 *
 * @package WT\AirtableSync
 */

namespace WT\AirtableSync;

class Field_Resolver {

	/**
	 * Postmeta key holding a post's Airtable record ID.
	 */
	public const RECORD_ID_KEY = '_airtable_record_id';

	/**
	 * Shape of an Airtable record ID.
	 */
	private const RECORD_ID_PATTERN = '/^rec[A-Za-z0-9]{14}$/';

	/**
	 * Whether a value is an Airtable record ID.
	 *
	 * @param string $value Candidate value.
	 * @return bool
	 */
	public static function is_record_id( string $value ): bool {
		return 1 === preg_match( self::RECORD_ID_PATTERN, $value );
	}

	/**
	 * Resolve record IDs or post titles to an array of WP post IDs.
	 *
	 * @param string|string[]       $value     One reference, comma-separated references, or an array.
	 * @param string                $post_type The CPT to search within.
	 * @param array<string, string> $aliases   Incoming title => WP title, for names that differ.
	 * @return int[]                           Matched WP post IDs, in order. Unmatched references are logged and skipped.
	 */
	public static function resolve( string|array $value, string $post_type, array $aliases = array() ): array {
		$ids = array();

		foreach ( self::normalise_titles( $value ) as $reference ) {
			if ( self::is_record_id( $reference ) ) {
				$id   = self::find_post_by_record_id( $reference, $post_type );
				$kind = 'record ID';
			} else {
				$reference = $aliases[ $reference ] ?? $reference;
				$id        = self::find_post_by_title( $reference, $post_type );
				$kind      = 'title';
			}

			if ( $id ) {
				$ids[] = $id;
			} else {
				Logger::error(
					sprintf(
						'Field_Resolver: no %s post found for %s "%s".',
						$post_type,
						$kind,
						$reference
					)
				);
			}
		}

		return array_values( array_unique( $ids ) );
	}

	/**
	 * Find a single post ID by its stamped Airtable record ID within a given CPT.
	 *
	 * Returns 0 if no match is found.
	 *
	 * @param string $record_id Airtable record ID.
	 * @param string $post_type CPT slug to search within.
	 * @return int
	 */
	private static function find_post_by_record_id( string $record_id, string $post_type ): int {
		$query = new \WP_Query(
			array(
				'post_type'              => $post_type,
				'post_status'            => 'any',
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => self::RECORD_ID_KEY,
						'value' => $record_id,
					),
				),
			)
		);

		return $query->posts ? (int) $query->posts[0] : 0;
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
