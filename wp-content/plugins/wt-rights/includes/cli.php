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
	} catch ( \Throwable $e ) {
		WP_CLI::error( $e->getMessage() );
	}
	$rows = array_map( static fn( array $r ): array => \WT\Rights\Exporter::row( $r, $config['reports']['fields'] ), $records );

	WP_CLI::log( 'Airtable (' . $config['name'] . '): ' . count( $rows ) . ' Reports rows. Reading Docs as ' . $google->account() . '…' );
	WP_CLI::log( 'Not yet in Airtable, so treated as empty: release dates (nothing counts as published) and Changes notes.' );

	$exporter = new \WT\Rights\Exporter(
		$structure,
		static fn( string $id ): array => $google->document( $id ),
		'sanitize_title'
	);
	return $exporter->build(
		$rows,
		$previous,
		array(
			'release' => (string) ( $assoc_args['release'] ?? '' ),
			'regions' => array_values( $asked ),
			'preview' => isset( $assoc_args['preview'] ),
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

WP_CLI::add_command( 'wt rights ping', 'wt_rights_ping_command' );
WP_CLI::add_command( 'wt rights check', 'wt_rights_check_command' );
WP_CLI::add_command( 'wt rights export', 'wt_rights_export_command' );
