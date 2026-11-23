<?php
/**
 * The typeahead's search index: one JSON file of every published language,
 * built from WordPress and searched in the browser. See plan.md 7.9.
 *
 * Absolute state: each rebuild writes the whole file from scratch (temp file,
 * then rename). Rows are sorted by post ID, so unchanged data gives a
 * byte-identical file.
 *
 * @package typeahead
 */

class WT_Typeahead_Index {

	const DIR            = 'typeahead';
	const FILE           = 'index.json';
	const SAMPLES_OPTION = 'wt_typeahead_samples';
	const SAMPLE_COUNT   = 50;
	const REBUILD_HOOK   = 'wt_typeahead_rebuild';
	const REBUILD_DELAY  = 60;

	/**
	 * Hooks the scheduled rebuild and the triggers that schedule it.
	 */
	public static function init() {
		add_action( self::REBUILD_HOOK, array( __CLASS__, 'rebuild_scheduled' ) );
		add_action( 'save_post_languages', array( __CLASS__, 'schedule' ) );
		add_action( 'deleted_post', array( __CLASS__, 'on_deleted_post' ), 10, 2 );
	}

	/**
	 * Schedules one rebuild a minute from now, unless one is already pending.
	 * A Make batch that saves hundreds of languages causes a single rebuild.
	 */
	public static function schedule() {
		if ( ! wp_next_scheduled( self::REBUILD_HOOK ) ) {
			wp_schedule_single_event( time() + self::REBUILD_DELAY, self::REBUILD_HOOK );
		}
	}

	/**
	 * Schedules a rebuild when a language is deleted outright.
	 *
	 * @param int          $post_id Post ID.
	 * @param WP_Post|null $post    Post.
	 */
	public static function on_deleted_post( $post_id, $post = null ) {
		if ( $post instanceof WP_Post && 'languages' === $post->post_type ) {
			self::schedule();
		}
	}

	/**
	 * Cron callback.
	 */
	public static function rebuild_scheduled() {
		self::rebuild( true );
	}

	/**
	 * Reads every published language with the fields the index needs.
	 *
	 * @return array<int, object>
	 */
	public static function fetch_raw() {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- one bulk read of ~8,000 rows; get_posts + meta would load every meta row.
		$rows = $wpdb->get_results(
			"SELECT p.ID, p.post_name, p.post_title,
				MAX(CASE WHEN m.meta_key = 'alternate_names' THEN m.meta_value END) AS alternate_names,
				MAX(CASE WHEN m.meta_key = 'standard_name' THEN m.meta_value END) AS standard_name,
				MAX(CASE WHEN m.meta_key = 'speakers_recorded' THEN m.meta_value END) AS speakers_recorded
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} m
				ON m.post_id = p.ID AND m.meta_key IN ('alternate_names', 'standard_name', 'speakers_recorded')
			WHERE p.post_type = 'languages' AND p.post_status = 'publish'
			GROUP BY p.ID"
		);
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Whether an ACF relationship meta value holds at least one post.
	 *
	 * @param mixed $value Raw meta value.
	 * @return bool
	 */
	public static function has_relation( $value ) {
		if ( null === $value || '' === $value ) {
			return false;
		}
		$data = maybe_unserialize( $value );
		return is_array( $data ) ? count( $data ) > 0 : '' !== trim( (string) $data );
	}

	/**
	 * Turns raw language rows into index rows.
	 *
	 * identifier is the post slug, the URL the result links to (the Airtable
	 * Identifier). label is alternate_names, then standard_name, then the title.
	 * Rows without a slug or a label are skipped. Sorted by id.
	 *
	 * @param array<int, object> $raw Rows from fetch_raw().
	 * @return array<int, array{id:int,label:string,identifier:string,resourced:bool}>
	 */
	public static function build_rows( array $raw ) {
		$rows = array();
		foreach ( $raw as $r ) {
			$identifier = trim( (string) ( $r->post_name ?? '' ) );
			$label      = '';
			foreach ( array( 'alternate_names', 'standard_name', 'post_title' ) as $field ) {
				$candidate = trim( (string) ( $r->$field ?? '' ) );
				if ( '' !== $candidate ) {
					$label = $candidate;
					break;
				}
			}
			if ( '' === $identifier || '' === $label ) {
				continue;
			}
			$rows[] = array(
				'id'         => (int) $r->ID,
				'label'      => $label,
				'identifier' => $identifier,
				'resourced'  => self::has_relation( $r->speakers_recorded ?? null ),
			);
		}
		usort(
			$rows,
			static function ( $a, $b ) {
				return $a['id'] <=> $b['id'];
			}
		);
		return $rows;
	}

	/**
	 * Encodes rows as the index file's contents.
	 *
	 * @param array $rows Rows from build_rows().
	 * @return string
	 */
	public static function encode( array $rows ) {
		return (string) wp_json_encode( $rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	}

	/**
	 * Picks placeholder samples: the first name of evenly spaced rows.
	 * Deterministic, so a rebuild of unchanged data changes nothing.
	 *
	 * @param array $rows Rows from build_rows().
	 * @return string[]
	 */
	public static function samples( array $rows ) {
		$count = count( $rows );
		if ( 0 === $count ) {
			return array();
		}
		$step    = max( 1, intdiv( $count, self::SAMPLE_COUNT ) );
		$samples = array();
		for ( $i = 0; $i < $count && count( $samples ) < self::SAMPLE_COUNT; $i += $step ) {
			$samples[] = trim( explode( ',', $rows[ $i ]['label'] )[0] );
		}
		return $samples;
	}

	/**
	 * Counts added, removed and changed rows between two indexes, by id.
	 *
	 * @param array $previous Rows before.
	 * @param array $next     Rows after.
	 * @return array{added:int,removed:int,changed:int}
	 */
	public static function diff( array $previous, array $next ) {
		$old_by_id = array_column( $previous, null, 'id' );
		$new_by_id = array_column( $next, null, 'id' );
		$changed   = 0;
		foreach ( array_intersect_key( $new_by_id, $old_by_id ) as $id => $row ) {
			if ( $row !== $old_by_id[ $id ] ) {
				++$changed;
			}
		}
		return array(
			'added'   => count( array_diff_key( $new_by_id, $old_by_id ) ),
			'removed' => count( array_diff_key( $old_by_id, $new_by_id ) ),
			'changed' => $changed,
		);
	}

	/**
	 * Absolute path of the index file.
	 *
	 * @return string
	 */
	public static function path() {
		$uploads = wp_upload_dir( null, false );
		return trailingslashit( $uploads['basedir'] ) . self::DIR . '/' . self::FILE;
	}

	/**
	 * Public URL of the index, versioned by its modified time; '' if missing.
	 *
	 * @return string
	 */
	public static function url() {
		$path = self::path();
		if ( ! file_exists( $path ) ) {
			return '';
		}
		$uploads = wp_upload_dir( null, false );
		return trailingslashit( $uploads['baseurl'] ) . self::DIR . '/' . self::FILE . '?v=' . filemtime( $path );
	}

	/**
	 * Rows of the current index file, or [] if there is none.
	 *
	 * @return array
	 */
	public static function current_rows() {
		$path = self::path();
		if ( ! file_exists( $path ) ) {
			return array();
		}
		$data = json_decode( (string) file_get_contents( $path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		return is_array( $data ) ? $data : array();
	}

	/**
	 * Writes the index atomically: temp file, then rename.
	 *
	 * @param string $json Contents.
	 * @return bool
	 */
	public static function write( $json ) {
		$path = self::path();
		if ( ! wp_mkdir_p( dirname( $path ) ) ) {
			return false;
		}
		$tmp = $path . '.tmp';
		if ( false === file_put_contents( $tmp, $json ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			return false;
		}
		return rename( $tmp, $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
	}

	/**
	 * Builds the index from WordPress and, when $write is true, writes it.
	 *
	 * @param bool $write Write the file and samples; false is a dry run.
	 * @return array{added:int,removed:int,changed:int,total:int,written:bool}
	 */
	public static function rebuild( $write ) {
		$rows    = self::build_rows( self::fetch_raw() );
		$report  = self::diff( self::current_rows(), $rows );
		$json    = self::encode( $rows );
		$path    = self::path();
		$same    = file_exists( $path ) && file_get_contents( $path ) === $json; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$written = false;

		if ( $write && ! $same ) {
			$written = self::write( $json );
			if ( ! $written ) {
				error_log( 'wt-typeahead: could not write ' . $path ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
		}
		if ( $write ) {
			update_option( self::SAMPLES_OPTION, self::samples( $rows ), false );
		}

		$report['total']   = count( $rows );
		$report['written'] = $written;
		return $report;
	}
}
