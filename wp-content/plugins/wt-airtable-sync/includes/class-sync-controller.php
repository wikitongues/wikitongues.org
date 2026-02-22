<?php
/**
 * Sync_Controller — upsert pipeline for a single Airtable record.
 *
 * Responsibilities:
 *   1. Locate the existing WP post (by _airtable_record_id → iso_code → post_title).
 *   2. Create or update the post.
 *   3. Write all meta fields defined in the CPT's field map.
 *   4. Stamp _airtable_record_id on every write so future syncs use the stable ID.
 *
 * Expected payload keys (all optional except airtable_id):
 *   - airtable_id  (string)  Airtable record ID, e.g. "recXXXXXXXX"
 *   - post_title   (string)  WP post title
 *   - post_status  (string)  publish / draft / etc. Defaults to 'publish'.
 *   - <field_key>  (mixed)   Any key present in the CPT's field map entry.
 *
 * @package WT\AirtableSync
 */

namespace WT\AirtableSync;

class Sync_Controller {

	private const AIRTABLE_ID_KEY = '_airtable_record_id';

	/**
	 * Process a sync payload for a given post type.
	 *
	 * When $dry_run is true the method performs all read-only steps (post
	 * lookup, post_object resolution) but skips every write. It returns a
	 * preview of the action and meta values that would have been written,
	 * which lets callers validate Make.com payloads without touching the DB.
	 *
	 * @param string               $post_type WP CPT slug.
	 * @param array<string, mixed> $payload   Decoded JSON body from the request.
	 * @param array<string, mixed> $field_map CPT entry from config/field-maps.php.
	 * @param bool                 $dry_run   When true, no writes are made.
	 * @return array{status: string, action: string, post_id: int}|\WP_Error
	 */
	public function sync(
		string $post_type,
		array $payload,
		array $field_map,
		bool $dry_run = false
	): array|\WP_Error {
		$airtable_id = isset( $payload['airtable_id'] ) ? sanitize_text_field( (string) $payload['airtable_id'] ) : '';
		$post_title  = isset( $payload['post_title'] ) ? sanitize_text_field( (string) $payload['post_title'] ) : '';
		$post_status = isset( $payload['post_status'] ) ? sanitize_text_field( (string) $payload['post_status'] ) : 'publish';

		// 1. Locate existing post (read-only — runs in both modes).
		$post_id = $this->find_post( $post_type, $airtable_id, $post_title, $payload );
		$action  = $post_id ? 'updated' : 'created';

		if ( $dry_run ) {
			$would_write = $this->preview_meta( $payload, $field_map );

			if ( $airtable_id ) {
				$would_write = array_merge(
					array( self::AIRTABLE_ID_KEY => $airtable_id ),
					$would_write
				);
			}

			Logger::info(
				sprintf( 'dry_run post_type=%s post_id=%d airtable_id=%s', $post_type, $post_id, $airtable_id )
			);

			return array(
				'status'      => 'dry_run',
				'action'      => $action,
				'post_id'     => $post_id,
				'post_title'  => $post_title,
				'post_status' => $post_status,
				'would_write' => $would_write,
			);
		}

		// 2. Create or update the core WP post.
		$post_id = $this->upsert_post( $post_id, $post_type, $post_title, $post_status );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		// 3. Stamp the stable Airtable record ID.
		if ( $airtable_id ) {
			update_post_meta( $post_id, self::AIRTABLE_ID_KEY, $airtable_id );
		}

		// 4. Write meta fields.
		$this->write_meta( $post_id, $payload, $field_map );

		Logger::info(
			sprintf( '%s post_type=%s post_id=%d airtable_id=%s', $action, $post_type, $post_id, $airtable_id )
		);

		return array(
			'status'  => 'ok',
			'action'  => $action,
			'post_id' => $post_id,
		);
	}

	// -------------------------------------------------------------------------
	// Lookup
	// -------------------------------------------------------------------------

	/**
	 * Find an existing post using a three-step priority lookup:
	 *   1. _airtable_record_id (stable, preferred)
	 *   2. iso_code meta key   (languages-specific fallback)
	 *   3. post_title          (last resort)
	 *
	 * Returns 0 if no match is found.
	 *
	 * @param string               $post_type   WP CPT slug.
	 * @param string               $airtable_id Airtable record ID.
	 * @param string               $post_title  Post title from payload.
	 * @param array<string, mixed> $payload     Full payload (used for iso_code fallback).
	 * @return int WP post ID, or 0.
	 */
	private function find_post(
		string $post_type,
		string $airtable_id,
		string $post_title,
		array $payload
	): int {
		// 1. Stable Airtable record ID.
		if ( $airtable_id ) {
			$id = $this->find_by_meta( $post_type, self::AIRTABLE_ID_KEY, $airtable_id );
			if ( $id ) {
				return $id;
			}
		}

		// 2. iso_code fallback (languages CPT only).
		$iso_code = isset( $payload['iso_code'] ) ? sanitize_text_field( (string) $payload['iso_code'] ) : '';
		if ( $iso_code ) {
			$id = $this->find_by_meta( $post_type, 'iso_code', $iso_code );
			if ( $id ) {
				return $id;
			}
		}

		// 3. Post title fallback.
		if ( $post_title ) {
			$id = $this->find_by_title( $post_type, $post_title );
			if ( $id ) {
				return $id;
			}
		}

		return 0;
	}

	/**
	 * Find a post by a meta key/value pair.
	 *
	 * @param string $post_type WP CPT slug.
	 * @param string $meta_key  Meta key to match.
	 * @param string $meta_value Meta value to match.
	 * @return int Post ID, or 0.
	 */
	private function find_by_meta( string $post_type, string $meta_key, string $meta_value ): int {
		$query = new \WP_Query(
			array(
				'post_type'              => $post_type,
				'post_status'            => 'any',
				'posts_per_page'         => 1,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => array(
					array(
						'key'   => $meta_key,
						'value' => $meta_value,
					),
				),
			)
		);

		if ( $query->have_posts() ) {
			return (int) $query->posts[0]->ID;
		}

		return 0;
	}

	/**
	 * Find a post by exact post_title match.
	 *
	 * @param string $post_type  WP CPT slug.
	 * @param string $post_title Exact title to match.
	 * @return int Post ID, or 0.
	 */
	private function find_by_title( string $post_type, string $post_title ): int {
		$query = new \WP_Query(
			array(
				'post_type'              => $post_type,
				'title'                  => $post_title,
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

	// -------------------------------------------------------------------------
	// Create / Update
	// -------------------------------------------------------------------------

	/**
	 * Insert a new post or update an existing one.
	 *
	 * @param int    $post_id     0 to create; existing ID to update.
	 * @param string $post_type   WP CPT slug.
	 * @param string $post_title  Post title.
	 * @param string $post_status Post status.
	 * @return int|\WP_Error New or existing post ID on success; WP_Error on failure.
	 */
	private function upsert_post(
		int $post_id,
		string $post_type,
		string $post_title,
		string $post_status
	): int|\WP_Error {
		$args = array(
			'post_type'   => $post_type,
			'post_status' => $post_status,
		);

		if ( $post_title ) {
			$args['post_title'] = $post_title;
			$args['post_name']  = sanitize_title( $post_title );
		}

		if ( $post_id ) {
			$args['ID'] = $post_id;
			$result     = wp_update_post( $args, true );
		} else {
			$result = wp_insert_post( $args, true );
		}

		if ( is_wp_error( $result ) ) {
			Logger::error( 'upsert_post failed: ' . $result->get_error_message() );
		}

		return $result;
	}

	// -------------------------------------------------------------------------
	// Meta writes
	// -------------------------------------------------------------------------

	/**
	 * Resolve all mapped meta fields without writing anything.
	 *
	 * Runs the same value resolution as write_meta() — including post_object
	 * title-to-ID lookups — and returns the resolved values keyed by meta_key.
	 * Used by dry-run mode so callers can inspect exactly what would be written.
	 *
	 * @param array<string, mixed> $payload   Full request payload.
	 * @param array<string, mixed> $field_map CPT field map from config/field-maps.php.
	 * @return array<string, mixed> Resolved meta values keyed by meta_key.
	 */
	private function preview_meta( array $payload, array $field_map ): array {
		$preview = array();

		foreach ( $field_map as $payload_key => $field ) {
			if ( ! isset( $payload[ $payload_key ] ) ) {
				continue;
			}

			$raw_value = $payload[ $payload_key ];
			$meta_key  = $field['meta_key'];
			$acf_type  = $field['acf_type'] ?? null;
			$cpt       = $field['post_type'] ?? null;

			if ( 'post_object' === $acf_type && $cpt ) {
				$preview[ $meta_key ] = Field_Resolver::resolve( $raw_value, $cpt );
			} else {
				$preview[ $meta_key ] = $raw_value;
			}
		}

		return $preview;
	}

	/**
	 * Write all mapped meta fields from the payload to the post.
	 *
	 * post_object fields are resolved to WP post IDs before writing.
	 * Fields absent from the payload are skipped (no clearing of existing values).
	 *
	 * @param int                  $post_id   WP post ID.
	 * @param array<string, mixed> $payload   Full request payload.
	 * @param array<string, mixed> $field_map CPT field map from config/field-maps.php.
	 */
	private function write_meta( int $post_id, array $payload, array $field_map ): void {
		foreach ( $field_map as $payload_key => $field ) {
			if ( ! isset( $payload[ $payload_key ] ) ) {
				continue;
			}

			$raw_value = $payload[ $payload_key ];
			$meta_key  = $field['meta_key'];
			$is_acf    = (bool) $field['acf'];
			$acf_type  = $field['acf_type'] ?? null;
			$cpt       = $field['post_type'] ?? null;

			if ( 'post_object' === $acf_type && $cpt ) {
				$value = Field_Resolver::resolve( $raw_value, $cpt );
			} else {
				$value = $raw_value;
			}

			if ( $is_acf && function_exists( 'update_field' ) ) {
				update_field( $meta_key, $value, $post_id );
			} else {
				update_post_meta( $post_id, $meta_key, $value );
			}
		}
	}
}
