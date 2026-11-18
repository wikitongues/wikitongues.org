<?php
/**
 * Place_Sync — upserts one Airtable Territories record as a WordPress place.
 *
 * Handles POST /sync/territories. Every Airtable Territories record is a
 * place below a nation (Guam, Hawaii, Catalonia). Its WordPress post type
 * follows from its ISO 3166-1 code:
 *
 *   - with a code    → a `territories` post (Guam, Greenland, Hong Kong);
 *   - without a code → a `subdivisions` post (Hawaii, Oaxaca, Wales).
 *
 * Idempotent: the post is found by its Airtable record ID across both types,
 * so a re-run updates the same post and changes nothing. If a record gains or
 * loses its code, the existing post changes type rather than being duplicated,
 * and its post ID, and so every link to it, is kept.
 *
 * An existing post keeps its slug and status: the territory pages that predate
 * the sync are edited in WordPress, and slugs only change with redirects. A new
 * `territories` post starts as a draft, because it has no WordPress region yet;
 * a new `subdivisions` post is published (the type is never public).
 *
 * After a write, languages that already link to this record by ID but could
 * not resolve it, because the post did not exist yet, are relinked.
 *
 * @package WT\AirtableSync
 */

namespace WT\AirtableSync;

class Place_Sync {

	public const TERRITORY = 'territories';

	public const SUBDIVISION = 'subdivisions';

	/**
	 * Language postmeta that keeps the Territories record IDs a language links to.
	 */
	public const LANGUAGE_RAW_IDS = '_subdivision_record_ids';

	/**
	 * Language ACF field holding the resolved links.
	 */
	public const LANGUAGE_FIELD = 'subdivisions';

	/**
	 * Post type for a Territories record: `territories` when it has an ISO code.
	 *
	 * @param string $iso_code The record's ISO 3166-1 code, or ''.
	 * @return string
	 */
	public static function target_type( string $iso_code ): string {
		return '' !== trim( $iso_code ) ? self::TERRITORY : self::SUBDIVISION;
	}

	/**
	 * Status for a new post of the given type.
	 *
	 * @param string $post_type `territories` or `subdivisions`.
	 * @return string
	 */
	public static function new_post_status( string $post_type ): string {
		return self::TERRITORY === $post_type ? 'draft' : 'publish';
	}

	/**
	 * Upsert one Territories record.
	 *
	 * @param array<string, mixed> $payload   Decoded JSON body: airtable_id, post_title, iso_3166_code, nation.
	 * @param array<string, mixed> $field_map The 'territories' entry from config/field-maps.php.
	 * @param bool                 $dry_run   When true, no writes are made.
	 * @return array<string, mixed>|\WP_Error
	 */
	public function sync( array $payload, array $field_map, bool $dry_run = false ): array|\WP_Error {
		$airtable_id = trim( (string) ( $payload['airtable_id'] ?? '' ) );
		if ( ! Field_Resolver::is_record_id( $airtable_id ) ) {
			return new \WP_Error(
				'wt_sync_missing_record_id',
				'A Territories record needs its Airtable record ID in airtable_id.',
				array( 'status' => 400 )
			);
		}

		$iso_code                 = strtoupper( trim( (string) ( $payload['iso_3166_code'] ?? '' ) ) );
		$payload['iso_3166_code'] = $iso_code;
		$target                   = self::target_type( $iso_code );

		$existing_id   = Field_Resolver::find_post_by_record_id( $airtable_id, array( self::TERRITORY, self::SUBDIVISION ) );
		$existing_type = $existing_id ? (string) get_post_type( $existing_id ) : '';
		$type_changed  = $existing_id && $existing_type !== $target;

		if ( $type_changed && ! $dry_run ) {
			set_post_type( $existing_id, $target );
			Logger::info( sprintf( 'Place_Sync: post %d changed type %s → %s (%s)', $existing_id, $existing_type, $target, $airtable_id ) );
		}

		$payload['post_status'] = self::new_post_status( $target );

		$result = ( new Sync_Controller() )->sync(
			$target,
			$payload,
			$field_map,
			$dry_run,
			array(
				'keep_existing_slug'    => true,
				'keep_existing_status'  => true,
				'title_match_unstamped' => true,
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( $dry_run && $existing_id ) {
			// The controller looked only in the target type; report the post
			// that will actually be updated.
			$result['action']      = 'updated';
			$result['post_id']     = $existing_id;
			$result['post_status'] = (string) get_post_status( $existing_id );
		}

		$result['post_type']    = $target;
		$result['type_changed'] = $type_changed ? $existing_type . ' → ' . $target : '';

		if ( ! $dry_run ) {
			$result['relinked_languages'] = $this->relink_languages( $airtable_id );
		}

		return $result;
	}

	/**
	 * Re-resolve the subdivisions of every language that links to a record ID.
	 *
	 * Writes the full resolved list from the language's kept record IDs, so the
	 * result is the same however many times it runs.
	 *
	 * @param string $record_id Airtable Territories record ID.
	 * @return int Number of languages rewritten.
	 */
	public function relink_languages( string $record_id ): int {
		if ( ! function_exists( 'update_field' ) ) {
			return 0;
		}

		$query = new \WP_Query(
			array(
				'post_type'              => 'languages',
				'post_status'            => 'any',
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
				'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => self::LANGUAGE_RAW_IDS,
						'value'   => '"' . $record_id . '"',
						'compare' => 'LIKE',
					),
				),
			)
		);

		$count = 0;
		foreach ( $query->posts as $language_id ) {
			$record_ids = get_post_meta( (int) $language_id, self::LANGUAGE_RAW_IDS, true );
			if ( ! is_array( $record_ids ) ) {
				continue;
			}

			$linked = Field_Resolver::resolve( $record_ids, array( self::TERRITORY, self::SUBDIVISION ) );
			update_field( self::LANGUAGE_FIELD, $linked, (int) $language_id );
			++$count;
		}

		return $count;
	}
}
