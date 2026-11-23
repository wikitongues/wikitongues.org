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
			update_term_meta( $term_id, 'nations', wp_slash( (string) wp_json_encode( $region['nations'] ?? array() ) ) );
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
				'_airtable_record_id'       => (string) $report['record_id'],
				'_rights_nation_id'         => (string) $report['nation_id'],
				'_rights_archive_nation_id' => (string) ( $report['archive_nation_id'] ?? '' ),
				'_rights_version'           => (string) $report['version'],
				'_rights_hash'              => (string) $report['hash'],
				'_rights_draft'             => empty( $report['draft'] ) ? '0' : '1',
				'_rights_report'            => (string) wp_json_encode( $report ), // Slashed with the rest of $postarr below.
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
	 * Also publishes or removes the region's PDFs to match (see pdf.php).
	 *
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
		self::stage_region_pdfs( $term_id, null );
		wt_rights_sync_region_pdfs( $term_id );
		return $changed;
	}

	/**
	 * Puts a region's PDFs in the private folder, or takes them out.
	 *
	 * PDFs reach the server only once their region is embargoed: while it is
	 * upcoming, its PDFs are deleted from the private folder and none can be
	 * staged. Otherwise each report's PDF for its current version is copied
	 * from $source, when given; a report whose PDF isn't there has none
	 * recorded, so an older version's PDF is never published for it.
	 *
	 * @param int         $term_id A region term.
	 * @param string|null $source  The folder `wp wt rights pdf` wrote to, or null.
	 * @return array{staged: int, missing: int, removed: int}
	 */
	public static function stage_region_pdfs( int $term_id, ?string $source ): array {
		$counts  = array(
			'staged'  => 0,
			'missing' => 0,
			'removed' => 0,
		);
		$private = wt_rights_private_dir();
		if ( is_wp_error( $private ) ) {
			if ( null === $source ) {
				return $counts;
			}
			throw new \RuntimeException( $private->get_error_message() );
		}
		$upcoming = Release_Status::UPCOMING === wt_rights_region_status( $term_id );
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
			$id      = (int) $id;
			$current = (string) get_post_meta( $id, '_rights_pdf_file', true );
			if ( $upcoming ) {
				if ( '' !== $current ) {
					wp_delete_file( $private . '/' . $current );
					delete_post_meta( $id, '_rights_pdf_file' );
					++$counts['removed'];
				}
				continue;
			}
			if ( null === $source ) {
				continue;
			}
			$name = wt_rights_pdf_name( (string) get_post_field( 'post_name', $id ), (string) get_post_meta( $id, '_rights_version', true ), (string) get_post_meta( $id, '_rights_hash', true ) );
			$from = rtrim( $source, '/' ) . '/' . $name;
			if ( ! is_readable( $from ) ) {
				delete_post_meta( $id, '_rights_pdf_file' );
				++$counts['missing'];
				continue;
			}
			if ( realpath( $from ) !== realpath( $private . '/' . $name ) && ! copy( $from, $private . '/' . $name ) ) {
				throw new \RuntimeException( 'Could not copy ' . $name . ' into the private folder.' );
			}
			if ( '' !== $current && $current !== $name ) {
				wp_delete_file( $private . '/' . $current ); // The previous version's file.
			}
			update_post_meta( $id, '_rights_pdf_file', $name );
			++$counts['staged'];
		}
		return $counts;
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
