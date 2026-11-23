<?php
/**
 * WP-CLI commands for wt-rights.
 *
 * Export commands need the two credentials in the environment. Run them
 * through 1Password so the secrets exist only for the command:
 *
 *     op run --env-file ~/.config/wikitongues/rights.env -- wp wt rights ping
 *
 * @package WT\Rights
 */

/**
 * The selected Airtable base's config (WT_RIGHTS_AIRTABLE_BASE, default sandbox).
 *
 * @return array<string, mixed>
 */
function wt_rights_airtable_config(): array {
	$bases = require dirname( __DIR__ ) . '/config/airtable.php';
	$name  = (string) ( getenv( 'WT_RIGHTS_AIRTABLE_BASE' ) ?: 'sandbox' );
	if ( ! isset( $bases[ $name ] ) ) {
		WP_CLI::error( 'Unknown Airtable base "' . $name . '". Known: ' . implode( ', ', array_keys( $bases ) ) . '.' );
	}
	return $bases[ $name ] + array( 'name' => $name );
}

/**
 * Checks both connections with one read each, printing no content.
 *
 * Reads the Reports table (row count by Research Status) and one country Doc
 * (its tab names and how it parses). Writes nothing.
 *
 * ## OPTIONS
 *
 * [--country=<name>]
 * : The country whose Doc to read.
 * ---
 * default: Afghanistan
 * ---
 *
 * ## EXAMPLES
 *
 *     op run --env-file ~/.config/wikitongues/rights.env -- wp wt rights ping
 *
 * @param list<string>          $args       Unused.
 * @param array<string, string> $assoc_args Options.
 */
function wt_rights_ping_command( array $args, array $assoc_args ): void {
	$config = wt_rights_airtable_config();
	$fields = $config['reports']['fields'];

	try {
		$airtable = \WT\Rights\Airtable::from_env( \WT\Rights\Http::wordpress() );
		$google   = \WT\Rights\Google_Docs::from_env( \WT\Rights\Http::wordpress() );

		$rows = $airtable->records( $config['base'], $config['reports']['table'], array( $fields['name'], $fields['status'], $fields['report_link'] ) );
	} catch ( \Throwable $e ) {
		WP_CLI::error( $e->getMessage() );
	}

	$by_status = array();
	$wanted    = null;
	foreach ( $rows as $row ) {
		$status               = (string) ( $row['fields'][ $fields['status'] ]['name'] ?? $row['fields'][ $fields['status'] ] ?? '(none)' );
		$by_status[ $status ] = ( $by_status[ $status ] ?? 0 ) + 1;
		if ( 0 === strcasecmp( (string) ( $row['fields'][ $fields['name'] ] ?? '' ), $assoc_args['country'] ) ) {
			$wanted = $row;
		}
	}
	arsort( $by_status );
	WP_CLI::log( 'Airtable (' . $config['name'] . '): ' . count( $rows ) . ' Reports rows.' );
	foreach ( $by_status as $status => $count ) {
		WP_CLI::log( '  ' . $status . ': ' . $count );
	}

	if ( null === $wanted ) {
		WP_CLI::error( 'No Reports row named "' . $assoc_args['country'] . '".' );
	}
	$doc_id = \WT\Rights\Google_Docs::document_id( (string) ( $wanted['fields'][ $fields['report_link'] ] ?? '' ) );
	if ( '' === $doc_id ) {
		WP_CLI::error( $assoc_args['country'] . "'s Report link is not a Google Doc." );
	}

	try {
		$document = $google->document( $doc_id );
	} catch ( \Throwable $e ) {
		WP_CLI::error( $e->getMessage() . ' (signed in as ' . $google->account() . '; is the Doc shared with it?)' );
	}

	$tabs   = \WT\Rights\Docs_Adapter::tabs( $document );
	$parsed = ( new \WT\Rights\Doc_Parser( wt_rights_structure() ) )->parse( $tabs );
	WP_CLI::log( 'Google Docs, as ' . $google->account() . ':' );
	WP_CLI::log( '  Doc: ' . (string) ( $document['title'] ?? '' ) );
	foreach ( $tabs as $tab ) {
		WP_CLI::log( '  Tab "' . $tab['title'] . '": ' . count( $tab['paragraphs'] ) . ' paragraphs' );
	}
	WP_CLI::log( '  Parsed: ' . count( $parsed['sections'] ) . ' prose sections, ' . count( $parsed['dimensions'] ) . ' answered questions, story card ' . ( $parsed['story_card'] ? 'found' : 'none' ) . ', ' . count( $parsed['problems'] ) . ' problems.' );
	foreach ( $parsed['problems'] as $problem ) {
		WP_CLI::log( '    ' . $problem['severity'] . ': ' . $problem['message'] );
	}
	WP_CLI::success( 'Both connections work.' );
}

/**
 * Builds a release from live Airtable and Docs, for `check` and `export`.
 *
 * @param array<string, string> $assoc_args Command options.
 * @return array{release: array<string, mixed>, countries: list<array<string, mixed>>, errors: int}
 */
function wt_rights_build( array $assoc_args ): array {
	$config    = wt_rights_airtable_config();
	$structure = wt_rights_structure();
	$keys      = array_column( $structure['regions'], 'key' );
	$asked     = 'all' === ( $assoc_args['region'] ?? '' ) ? $keys : array_filter( array_map( 'trim', explode( ',', (string) ( $assoc_args['region'] ?? '' ) ) ) );
	$unknown   = array_diff( $asked, $keys );
	if ( ! $asked || $unknown ) {
		WP_CLI::error( 'Give --region as "all" or a list of: ' . implode( ', ', $keys ) . '.' );
	}

	$previous = null;
	if ( ! empty( $assoc_args['previous'] ) ) {
		$previous = json_decode( (string) file_get_contents( $assoc_args['previous'] ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file.
		if ( ! is_array( $previous ) || \WT\Rights\Exporter::SCHEMA !== ( $previous['schema'] ?? null ) ) {
			WP_CLI::error( 'The previous release is not a schema ' . \WT\Rights\Exporter::SCHEMA . ' release file.' );
		}
	}

	try {
		$airtable = \WT\Rights\Airtable::from_env( \WT\Rights\Http::wordpress() );
		$google   = \WT\Rights\Google_Docs::from_env( \WT\Rights\Http::wordpress() );
		$records  = $airtable->records( $config['base'], $config['reports']['table'], array_values( $config['reports']['fields'] ) );
		$publish  = $airtable->records( $config['base'], $config['publishing']['table'], array_values( $config['publishing']['fields'] ) );
		$changes  = $airtable->records( $config['base'], $config['changes']['table'], array_values( $config['changes']['fields'] ) );
	} catch ( \Throwable $e ) {
		WP_CLI::error( $e->getMessage() );
	}
	$rows = array_map( static fn( array $r ): array => \WT\Rights\Exporter::row( $r, $config['reports']['fields'] ), $records );

	$exporter = new \WT\Rights\Exporter(
		$structure,
		static fn( string $id ): array => $google->document( $id ),
		'sanitize_title'
	);
	$dates    = $exporter->release_dates( $publish, $config['publishing']['fields'] );
	$notes    = \WT\Rights\Exporter::notes_since( $changes, $config['changes']['fields'], $previous );
	$today    = gmdate( 'Y-m-d' );
	$released = array_keys( array_filter( $dates, static fn( string $d ): bool => $d <= $today ) );

	WP_CLI::log( 'Airtable (' . $config['name'] . '): ' . count( $rows ) . ' Reports rows, ' . count( $dates ) . ' release dates, ' . count( $notes ) . ' reports with Changes notes since the previous release.' );
	WP_CLI::log( 'Published (release date passed): ' . ( $released ? implode( ', ', $released ) : 'none' ) . '. Reading Docs as ' . $google->account() . '…' );

	return $exporter->build(
		$rows,
		$previous,
		array(
			'release'       => (string) ( $assoc_args['release'] ?? '' ),
			'regions'       => array_values( $asked ),
			'preview'       => isset( $assoc_args['preview'] ),
			'today'         => $today,
			'release_dates' => $dates,
			'notes'         => $notes,
		)
	);
}

/**
 * Prints the per-country table and a summary.
 *
 * @param array{release: array<string, mixed>, countries: list<array<string, mixed>>, errors: int} $result From wt_rights_build().
 * @param bool                                                                                     $all    Show countries with no problems too.
 */
function wt_rights_print_result( array $result, bool $all ): void {
	$items = array();
	$tally = array();
	foreach ( $result['countries'] as $country ) {
		$tally[ $country['outcome'] ] = ( $tally[ $country['outcome'] ] ?? 0 ) + 1;
		if ( ! $all && ! $country['problems'] ) {
			continue;
		}
		$messages = array_map( static fn( array $p ): string => ( 'error' === $p['severity'] ? '✗ ' : '· ' ) . $p['message'], $country['problems'] );
		$items[]  = array(
			'Region'   => $country['region'],
			'Country'  => $country['name'],
			'Status'   => $country['status'],
			'Outcome'  => $country['outcome'],
			'Problems' => $messages ? implode( "\n", $messages ) : 'none',
		);
	}
	if ( $items ) {
		WP_CLI\Utils\format_items( 'table', $items, array( 'Region', 'Country', 'Status', 'Outcome', 'Problems' ) );
	}
	$parts = array();
	foreach ( $tally as $outcome => $count ) {
		$parts[] = $count . ' ' . $outcome;
	}
	WP_CLI::log( implode( ', ', $parts ) . '. ' . $result['errors'] . ' errors.' );
}

/**
 * Checks regions against the editorial contract, writing nothing.
 *
 * Reads Airtable and every Completed country's Doc in the regions, and lists
 * every problem. Exits 1 when there is an error.
 *
 * ## OPTIONS
 *
 * --region=<keys>
 * : "all", or region keys separated by commas, e.g. south-central-asia.
 *
 * [--previous=<file>]
 * : The previous release file, for versions and slugs.
 *
 * [--preview]
 * : Also read unfinished countries, as drafts.
 *
 * [--all]
 * : List countries with no problems too.
 *
 * ## EXAMPLES
 *
 *     op run --env-file ~/.config/wikitongues/rights.env -- wp wt rights check --region=south-central-asia --preview
 *
 * @param list<string>          $args       Unused.
 * @param array<string, string> $assoc_args Options.
 */
function wt_rights_check_command( array $args, array $assoc_args ): void {
	$result = wt_rights_build( $assoc_args );
	wt_rights_print_result( $result, isset( $assoc_args['all'] ) );
	if ( $result['errors'] ) {
		WP_CLI::halt( 1 );
	}
	WP_CLI::success( 'No errors.' );
}

/**
 * Writes a release file.
 *
 * Refuses when there is an error, unless --preview. Keep release files in the
 * private releases repository, never in this one: they hold embargoed text.
 *
 * ## OPTIONS
 *
 * --region=<keys>
 * : Regions to add to the previous release's: "all", or keys separated by commas.
 *
 * --release=<number>
 * : The release number, e.g. 2027.1.
 *
 * --out=<file>
 * : Where to write the release file.
 *
 * [--previous=<file>]
 * : The previous release file.
 *
 * [--preview]
 * : Include unfinished countries as drafts, and write despite errors. For staging only.
 *
 * ## EXAMPLES
 *
 *     op run --env-file ~/.config/wikitongues/rights.env -- wp wt rights export --region=south-central-asia --release=2027.1 --out=../language-rights-releases/rights-2027.1.json
 *
 * @param list<string>          $args       Unused.
 * @param array<string, string> $assoc_args Options.
 */
function wt_rights_export_command( array $args, array $assoc_args ): void {
	if ( ! preg_match( '/^\d{4}\.\d+(\.\d+)?$/', (string) $assoc_args['release'] ) ) {
		WP_CLI::error( 'Give --release as a number like 2027.1 or 2027.1.1.' );
	}
	$out = (string) $assoc_args['out'];
	if ( str_starts_with( realpath( dirname( $out ) ) ?: '', (string) realpath( ABSPATH ) ) ) {
		WP_CLI::error( 'Refusing to write a release file inside the WordPress folder: it holds embargoed text and this repository is public.' );
	}

	$result = wt_rights_build( $assoc_args );
	wt_rights_print_result( $result, false );
	if ( $result['errors'] && ! isset( $assoc_args['preview'] ) ) {
		WP_CLI::error( 'Not written: fix the errors above first.' );
	}

	$json = wp_json_encode( $result['release'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	if ( false === $json || false === file_put_contents( $out, $json . "\n" ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- local file.
		WP_CLI::error( 'Could not write ' . $out . '.' );
	}
	WP_CLI::success( 'Wrote release ' . $assoc_args['release'] . ' (' . count( $result['release']['reports'] ) . ' reports' . ( isset( $assoc_args['preview'] ) ? ', preview' : '' ) . ') to ' . $out . '.' );
}

/**
 * Imports a release file into this site.
 *
 * Dry run by default: prints what would change. Matches reports by Airtable
 * record ID; a report no longer in the release is set to draft, never deleted.
 * A second run changes nothing. Needs no credentials.
 *
 * ## OPTIONS
 *
 * <file>
 * : The release file.
 *
 * [--execute]
 * : Write the changes.
 *
 * [--pdfs=<dir>]
 * : The folder `wp wt rights pdf` wrote to. For regions already embargoed or
 * released, each report's PDF for its version is copied into
 * WT_RIGHTS_PRIVATE_DIR (published once the region is released). Upcoming
 * regions get none: stage them with `status <region> embargoed --pdfs=<dir>`.
 *
 * ## EXAMPLES
 *
 *     wp wt rights import ~/rights/rights-2027.1.json
 *     wp wt rights import ~/rights/rights-2027.1.json --execute
 *
 * @param list<string>          $args       The file.
 * @param array<string, string> $assoc_args Options.
 */
function wt_rights_import_command( array $args, array $assoc_args ): void {
	$raw     = (string) file_get_contents( $args[0] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file.
	$release = json_decode( $raw, true );
	if ( ! is_array( $release ) || \WT\Rights\Exporter::SCHEMA !== ( $release['schema'] ?? null ) ) {
		WP_CLI::error( 'Not a schema ' . \WT\Rights\Exporter::SCHEMA . ' release file: ' . $args[0] );
	}
	$execute = isset( $assoc_args['execute'] );

	$statuses = \WT\Rights\Importer::region_statuses();
	foreach ( $release['regions'] as $region ) {
		if ( ! isset( $statuses[ $region['key'] ] ) ) {
			WP_CLI::log( 'New region: ' . $region['name'] . ' (starts upcoming).' );
			$statuses[ $region['key'] ] = \WT\Rights\Release_Status::UPCOMING;
		}
	}
	$plan = \WT\Rights\Import_Plan::plan( $release, \WT\Rights\Importer::existing(), $statuses );

	$rows = array();
	foreach ( $plan as $action ) {
		if ( 'unchanged' !== $action['action'] ) {
			$rows[] = array(
				'Action'  => $action['action'],
				'Report'  => $action['name'],
				'Status'  => $action['post_status'],
				'Changes' => implode( '; ', $action['changes'] ),
			);
		}
	}
	if ( $rows ) {
		WP_CLI\Utils\format_items( 'table', $rows, array( 'Action', 'Report', 'Status', 'Changes' ) );
	}
	$summary = \WT\Rights\Import_Plan::summary( $plan );
	$counts  = array();
	foreach ( $summary as $what => $count ) {
		$counts[] = $count . ' ' . $what;
	}
	WP_CLI::log( 'Release ' . $release['release'] . ( ! empty( $release['preview'] ) ? ' (preview)' : '' ) . ': ' . implode( ', ', $counts ) . '.' );

	if ( ! $execute ) {
		WP_CLI::log( 'Dry run: nothing written. Add --execute to apply.' );
		return;
	}

	$by_record = array();
	foreach ( $release['reports'] as $report ) {
		$by_record[ $report['record_id'] ] = $report;
	}
	try {
		$regions = \WT\Rights\Importer::upsert_regions( $release );
		foreach ( $plan as $action ) {
			\WT\Rights\Importer::apply( $action, $by_record[ $action['record_id'] ] ?? array(), $regions );
		}
		foreach ( $regions as $key => $term_id ) {
			$pdfs = \WT\Rights\Importer::stage_region_pdfs( $term_id, empty( $assoc_args['pdfs'] ) ? null : (string) $assoc_args['pdfs'] );
			if ( array_sum( $pdfs ) ) {
				WP_CLI::log( 'PDFs, ' . $key . ': ' . $pdfs['staged'] . ' staged, ' . $pdfs['missing'] . ' missing, ' . $pdfs['removed'] . ' removed.' );
			}
			wt_rights_sync_region_pdfs( $term_id );
		}
	} catch ( \Throwable $e ) {
		WP_CLI::error( $e->getMessage() );
	}
	update_option(
		'wt_rights_release',
		array(
			'release'     => (string) $release['release'],
			'preview'     => ! empty( $release['preview'] ),
			'built_at'    => (string) $release['built_at'],
			'imported_at' => gmdate( 'c' ),
			'sha1'        => sha1( $raw ),
		),
		false
	);
	WP_CLI::success( 'Imported release ' . $release['release'] . '.' );
}

/**
 * Shows or sets a region's release status.
 *
 * Setting it also sets its reports' post status: upcoming = draft (admins
 * only), embargoed = private (admins and press), released = publish. Running
 * it again changes nothing.
 *
 * ## OPTIONS
 *
 * [<region>]
 * : A region key, e.g. south-central-asia. Omit to list every region.
 *
 * [<status>]
 * : upcoming, embargoed or released.
 *
 * [--pdfs=<dir>]
 * : With embargoed or released: copy the region's PDFs into the private folder
 * from here. Setting a region to upcoming deletes its PDFs from the server.
 *
 * ## EXAMPLES
 *
 *     wp wt rights status
 *     wp wt rights status south-central-asia embargoed --pdfs=~/rights/pdf
 *
 * @param list<string>          $args       Region and status.
 * @param array<string, string> $assoc_args Unused.
 */
function wt_rights_status_command( array $args, array $assoc_args ): void {
	if ( ! $args ) {
		$terms = get_terms(
			array(
				'taxonomy'   => 'rights_region',
				'hide_empty' => false,
				'meta_key'   => 'order', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- seven terms.
				'orderby'    => 'meta_value_num',
			)
		);
		$rows  = array();
		foreach ( is_array( $terms ) ? $terms : array() as $term ) {
			$rows[] = array(
				'Region'       => $term->slug,
				'Status'       => wt_rights_region_status( $term->term_id ),
				'Release date' => (string) get_term_meta( $term->term_id, 'release_date', true ),
				'Reports'      => count(
					get_posts(
						array(
							'post_type'        => 'rights_reports',
							'post_status'      => array( 'publish', 'private', 'draft' ),
							'numberposts'      => -1,
							'fields'           => 'ids',
							'suppress_filters' => true,
							'tax_query'        => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- seven regions, CLI only.
								array(
									'taxonomy' => 'rights_region',
									'terms'    => array( $term->term_id ),
								),
							),
						)
					)
				),
			);
		}
		if ( ! $rows ) {
			WP_CLI::log( 'No regions yet: import a release first.' );
			return;
		}
		WP_CLI\Utils\format_items( 'table', $rows, array( 'Region', 'Status', 'Release date', 'Reports' ) );
		$release = get_option( 'wt_rights_release' );
		if ( is_array( $release ) ) {
			WP_CLI::log( 'Release ' . $release['release'] . ( $release['preview'] ? ' (preview)' : '' ) . ', imported ' . $release['imported_at'] . '.' );
		}
		return;
	}

	if ( 2 !== count( $args ) || ! \WT\Rights\Release_Status::is_valid( $args[1] ) ) {
		WP_CLI::error( 'Give a region and one of: ' . implode( ', ', \WT\Rights\Release_Status::all() ) . '.' );
	}
	$term = get_term_by( 'slug', $args[0], 'rights_region' );
	if ( ! $term instanceof WP_Term ) {
		WP_CLI::error( 'No region "' . $args[0] . '" on this site.' );
	}
	$was     = wt_rights_region_status( $term->term_id );
	$changed = \WT\Rights\Importer::set_region_status( $term->term_id, $args[1] );
	if ( ! empty( $assoc_args['pdfs'] ) ) {
		try {
			$pdfs = \WT\Rights\Importer::stage_region_pdfs( $term->term_id, (string) $assoc_args['pdfs'] );
		} catch ( \Throwable $e ) {
			WP_CLI::error( $e->getMessage() );
		}
		wt_rights_sync_region_pdfs( $term->term_id );
		WP_CLI::log( 'PDFs: ' . $pdfs['staged'] . ' staged, ' . $pdfs['missing'] . ' missing.' );
	}
	WP_CLI::success( $term->name . ': ' . $was . ' → ' . $args[1] . '; ' . $changed . ' reports changed post status.' );
}

/**
 * Prints each report's PDF with headless Chrome, from its print view.
 *
 * Writes {slug}-v{version}-{hash}.pdf for every report on this site (any
 * status), skipping files that already exist, so a re-run prints only new
 * versions. Run it on the machine that cuts releases, after importing the
 * release locally, and keep the folder with the release files: never inside
 * the web root.
 *
 * ## OPTIONS
 *
 * --out=<dir>
 * : Where to write the PDFs.
 *
 * [--chrome=<path>]
 * : Chrome's binary. Default: Google Chrome in /Applications.
 *
 * [--region=<key>]
 * : Only this region's reports.
 *
 * ## EXAMPLES
 *
 *     wp wt rights pdf --out=../language-rights-releases/pdf
 *
 * @param list<string>          $args       Unused.
 * @param array<string, string> $assoc_args Options.
 */
function wt_rights_pdf_command( array $args, array $assoc_args ): void {
	$out = rtrim( (string) $assoc_args['out'], '/' );
	if ( ! wp_mkdir_p( $out ) || ! wt_rights_outside_web_root( (string) realpath( $out ), (string) realpath( ABSPATH ) ) ) {
		WP_CLI::error( 'Give an --out folder outside the web root: PDFs of unreleased reports must never be reachable by URL.' );
	}
	$out    = (string) realpath( $out );
	$chrome = (string) ( $assoc_args['chrome'] ?? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome' );
	if ( ! is_executable( $chrome ) ) {
		WP_CLI::error( 'Chrome not found at ' . $chrome . '. Pass --chrome=<path>.' );
	}

	$query = array(
		'post_type'        => 'rights_reports',
		'post_status'      => array( 'publish', 'private', 'draft' ),
		'numberposts'      => -1,
		'orderby'          => 'name',
		'order'            => 'ASC',
		'suppress_filters' => true,
	);
	if ( ! empty( $assoc_args['region'] ) ) {
		$query['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- CLI only.
			array(
				'taxonomy' => 'rights_region',
				'field'    => 'slug',
				'terms'    => array( $assoc_args['region'] ),
			),
		);
	}
	// A throwaway profile: never the user's own Chrome, and no first-run delays.
	$profile = get_temp_dir() . 'wt-rights-chrome-' . getmypid();
	$printed = 0;
	$skipped = 0;
	foreach ( get_posts( $query ) as $post ) {
		$name = wt_rights_pdf_name( $post->post_name, (string) get_post_meta( $post->ID, '_rights_version', true ), (string) get_post_meta( $post->ID, '_rights_hash', true ) );
		$file = $out . '/' . $name;
		if ( file_exists( $file ) ) {
			++$skipped;
			continue;
		}
		$chrome_args = ' --headless=new --disable-gpu --no-first-run --no-pdf-header-footer --user-data-dir=' . escapeshellarg( $profile ) . ' --print-to-pdf=' . escapeshellarg( $file ) . ' ' . escapeshellarg( wt_rights_print_url( $post ) );
		$app         = (string) preg_replace( '#\.app/Contents/MacOS/.*$#', '.app', $chrome );
		// On macOS, launch through LaunchServices: started directly by PHP, Chrome
		// waits about 25 seconds per page (2026-11-22); through `open`, about 4.
		$command = 'Darwin' === PHP_OS_FAMILY && str_ends_with( $app, '.app' )
			? 'open -W -n -a ' . escapeshellarg( $app ) . ' --args' . $chrome_args . ' >/dev/null 2>&1 </dev/null'
			: escapeshellarg( $chrome ) . $chrome_args . ' 2>&1 </dev/null';
		$output  = array();
		exec( $command, $output, $code ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec -- CLI-only call to a local browser with escaped arguments.
		if ( 0 !== $code || ! file_exists( $file ) ) {
			WP_CLI::warning( 'Could not print ' . $post->post_title . ': ' . implode( ' ', array_slice( (array) $output, -2 ) ) );
			continue;
		}
		WP_CLI::log( 'Printed ' . $name );
		++$printed;
	}
	if ( is_dir( $profile ) ) {
		exec( 'rm -rf ' . escapeshellarg( $profile ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec -- removes the throwaway profile made above.
	}
	WP_CLI::success( $printed . ' printed, ' . $skipped . ' already there, in ' . $out . '.' );
}

WP_CLI::add_command( 'wt rights ping', 'wt_rights_ping_command' );
WP_CLI::add_command( 'wt rights pdf', 'wt_rights_pdf_command' );
WP_CLI::add_command( 'wt rights import', 'wt_rights_import_command' );
WP_CLI::add_command( 'wt rights status', 'wt_rights_status_command' );
WP_CLI::add_command( 'wt rights check', 'wt_rights_check_command' );
WP_CLI::add_command( 'wt rights export', 'wt_rights_export_command' );
