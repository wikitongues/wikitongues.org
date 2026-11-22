<?php
/**
 * Importer — writes a release into WordPress.
 *
 * Reads the site's reports, gets a plan from Import_Plan, and applies it. It
 * never calls Google or Airtable: the release file is its only input.
 *
 * @package WT\Rights
 */

namespace WT\Rights;

/**
 * Applies releases and region statuses.
 *
 * @phpstan-import-type Existing from Import_Plan
 * @phpstan-import-type Action from Import_Plan
 */
final class Importer {

	/**
	 * The site's reports, by Airtable record ID.
	 *
	 * @return array<string, Existing>
	 */
	public static function existing(): array {
		$ids = get_posts(
			array(
				'post_type'        => 'rights_reports',
				'post_status'      => array( 'publish', 'private', 'draft' ),
				'numberposts'      => -1,
				'fields'           => 'ids',
				'suppress_filters' => true,
			)
		);
		$out = array();
		foreach ( $ids as $id ) {
			$post      = get_post( (int) $id );
			$record_id = (string) get_post_meta( (int) $id, '_airtable_record_id', true );
			if ( ! $post || '' === $record_id ) {
				continue;
			}
			$regions           = wp_get_object_terms( (int) $id, 'rights_region', array( 'fields' => 'slugs' ) );
			$out[ $record_id ] = array(
				'post_id' => (int) $id,
				'slug'    => $post->post_name,
				'title'   => $post->post_title,
				'status'  => $post->post_status,
				'hash'    => (string) get_post_meta( (int) $id, '_rights_hash', true ),
				'version' => (string) get_post_meta( (int) $id, '_rights_version', true ),
				'region'  => is_array( $regions ) && $regions ? (string) $regions[0] : '',
				'draft'   => '1' === get_post_meta( (int) $id, '_rights_draft', true ),
			);
		}
		return $out;
	}

	/**
	 * Region key → release status on this site, for every region term.
	 *
	 * @return array<string, string>
	 */
	public static function region_statuses(): array {
		$terms = get_terms(
			array(
				'taxonomy'   => 'rights_region',
				'hide_empty' => false,
			)
		);
		$out   = array();
		foreach ( is_array( $terms ) ? $terms : array() as $term ) {
			$out[ $term->slug ] = wt_rights_region_status( $term->term_id );
		}
		return $out;
	}

	/**
	 * Creates or updates the release's regions. A new region starts upcoming;
	 * an existing region keeps its status.
	 *
	 * @param array<string, mixed> $release A release file.
	 * @return array<string, int> Region key → term ID.
	 */
	public static function upsert_regions( array $release ): array {
		$ids = array();
		foreach ( (array) $release['regions'] as $region ) {
			$term = get_term_by( 'slug', (string) $region['key'], 'rights_region' );
			if ( $term instanceof \WP_Term ) {
				if ( $term->name !== $region['name'] ) {
					wp_update_term( $term->term_id, 'rights_region', array( 'name' => (string) $region['name'] ) );
				}
				$term_id = $term->term_id;
			} else {
				$created = wp_insert_term( (string) $region['name'], 'rights_region', array( 'slug' => (string) $region['key'] ) );
				if ( is_wp_error( $created ) ) {
					throw new \RuntimeException( 'Could not create region ' . $region['key'] . ': ' . $created->get_error_message() );
				}
				$term_id = (int) $created['term_id'];
				update_term_meta( $term_id, 'release_status', Release_Status::UPCOMING );
			}
			update_term_meta( $term_id, 'order', (int) $region['order'] );
			update_term_meta( $term_id, 'release_date', (string) ( $region['release_date'] ?? '' ) );
			update_term_meta( $term_id, 'forthcoming', wp_slash( (string) wp_json_encode( $region['forthcoming'] ) ) );
			$ids[ (string) $region['key'] ] = $term_id;
		}
		return $ids;
	}

	/**
	 * Applies one planned action.
	 *
	 * @param Action               $action  From Import_Plan::plan().
	 * @param array<string, mixed> $report  The report from the release, or empty for an unpublish.
	 * @param array<string, int>   $regions Region key → term ID.
	 */
	public static function apply( array $action, array $report, array $regions ): void {
		if ( 'unchanged' === $action['action'] ) {
			return;
		}
		if ( 'unpublish' === $action['action'] ) {
			wp_update_post(
				array(
					'ID'          => $action['post_id'],
					'post_status' => 'draft',
				)
			);
			return;
		}

		$postarr = array(
			'post_type'    => 'rights_reports',
			'post_title'   => (string) $report['name'],
			'post_name'    => (string) $report['slug'],
			'post_status'  => $action['post_status'],
			'post_content' => self::searchable_html( $report ),
			'post_excerpt' => (string) ( $report['content']['rating']['headline'] ?? '' ),
			'meta_input'   => array(
				'_airtable_record_id' => (string) $report['record_id'],
				'_rights_nation_id'   => (string) $report['nation_id'],
				'_rights_version'     => (string) $report['version'],
				'_rights_hash'        => (string) $report['hash'],
				'_rights_draft'       => empty( $report['draft'] ) ? '0' : '1',
				'_rights_report'      => wp_slash( (string) wp_json_encode( $report ) ),
			),
		);
		if ( 'update' === $action['action'] ) {
			$postarr['ID'] = $action['post_id'];
		}
		$post_id = wp_insert_post( wp_slash( $postarr ), true );
		if ( is_wp_error( $post_id ) ) {
			throw new \RuntimeException( 'Could not write ' . $report['name'] . ': ' . $post_id->get_error_message() );
		}
		wp_set_object_terms( (int) $post_id, array( $regions[ $report['region'] ] ?? 0 ), 'rights_region' );
	}

	/**
	 * Sets a region's release status and its reports' post statuses.
	 *
	 * @param int    $term_id The region term.
	 * @param string $status  A release status.
	 * @return int How many reports changed post status.
	 */
	public static function set_region_status( int $term_id, string $status ): int {
		update_term_meta( $term_id, 'release_status', $status );
		$ids     = get_posts(
			array(
				'post_type'        => 'rights_reports',
				'post_status'      => array( 'publish', 'private', 'draft' ),
				'numberposts'      => -1,
				'fields'           => 'ids',
				'suppress_filters' => true,
				'tax_query'        => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- at most a few dozen posts, CLI only.
					array(
						'taxonomy' => 'rights_region',
						'terms'    => array( $term_id ),
					),
				),
			)
		);
		$changed = 0;
		foreach ( $ids as $id ) {
			$want = Release_Status::post_status( $status, '1' === get_post_meta( (int) $id, '_rights_draft', true ) );
			if ( get_post_status( (int) $id ) !== $want ) {
				wp_update_post(
					array(
						'ID'          => (int) $id,
						'post_status' => $want,
					)
				);
				++$changed;
			}
		}
		return $changed;
	}

	/**
	 * The report's sections as one HTML body, so site search and excerpts work.
	 * Templates render from the `_rights_report` meta, not from this.
	 *
	 * @param array<string, mixed> $report The report from the release.
	 */
	private static function searchable_html( array $report ): string {
		$content = (array) ( $report['content'] ?? array() );
		$html    = '';
		foreach ( (array) ( $content['sections'] ?? array() ) as $section ) {
			$html .= (string) $section['html'];
		}
		foreach ( (array) ( $content['dimensions'] ?? array() ) as $dimension ) {
			$html .= (string) $dimension['html'];
		}
		$html .= (string) ( $content['rating']['html'] ?? '' );
		return wp_kses_post( $html );
	}
}
