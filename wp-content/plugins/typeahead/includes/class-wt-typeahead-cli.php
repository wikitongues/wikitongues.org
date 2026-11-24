<?php
/**
 * WP-CLI: wp typeahead rebuild [--execute]
 *
 * @package typeahead
 */

class WT_Typeahead_CLI {

	/**
	 * Rebuilds the typeahead search index from published languages.
	 *
	 * Dry run by default: prints what would change. A second run after
	 * --execute reports 0 changes.
	 *
	 * ## OPTIONS
	 *
	 * [--execute]
	 * : Write the index and placeholder samples.
	 *
	 * @param array $args       Positional args.
	 * @param array $assoc_args Flags.
	 */
	public function rebuild( $args, $assoc_args ) {
		$execute = ! empty( $assoc_args['execute'] );
		$report  = WT_Typeahead_Index::rebuild( $execute );

		WP_CLI::log(
			sprintf(
				'%d languages in the index: %d added, %d removed, %d changed.',
				$report['total'],
				$report['added'],
				$report['removed'],
				$report['changed']
			)
		);

		if ( ! $execute ) {
			WP_CLI::log( 'Dry run. Re-run with --execute to write ' . WT_Typeahead_Index::path() );
			return;
		}
		if ( $report['written'] ) {
			WP_CLI::success( 'Wrote ' . WT_Typeahead_Index::path() );
		} elseif ( 0 === $report['added'] + $report['removed'] + $report['changed'] && file_exists( WT_Typeahead_Index::path() ) ) {
			WP_CLI::success( 'Index already up to date; nothing written.' );
		} else {
			WP_CLI::error( 'Could not write ' . WT_Typeahead_Index::path() );
		}
	}
}
