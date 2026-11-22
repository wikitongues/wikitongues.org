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
	$bases = require WT_RIGHTS_DIR . 'config/airtable.php';
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

WP_CLI::add_command( 'wt rights ping', 'wt_rights_ping_command' );
