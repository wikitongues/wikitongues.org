<?php
/**
 * WP-CLI: create the FAQ posts the Donate page's FAQ Accordion lists.
 *
 * The launch FAQ block (wt_donate_default_blocks()) picks FAQ posts by exact
 * title. "Are donations tax-deductible?" already exists; this creates the
 * others so the block shows all five. Run it before the Donate page is first
 * saved in admin: a saved page keeps whatever FAQ entries it was saved with.
 *
 * @package Wikitongues
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * Create the Donate page's missing FAQ posts.
 *
 * Idempotent: a question that already exists as a published FAQ post is left
 * alone, so the command is safe to re-run on localhost, staging and production.
 *
 * ## OPTIONS
 *
 * [--execute]
 * : Create the posts. Without it the command only reports what it would do.
 *
 * ## EXAMPLES
 *
 *     wp wt create-donate-faqs --allow-root
 *     wp wt create-donate-faqs --execute --allow-root
 *
 * @param array<int,string>    $args       Positional arguments (unused).
 * @param array<string,string> $assoc_args Associative arguments.
 * @return void
 */
function wt_create_donate_faqs( $args, $assoc_args ) {
	$execute = isset( $assoc_args['execute'] );
	WP_CLI::log( $execute ? 'Creating Donate FAQ posts.' : 'Dry run — pass --execute to write.' );

	$created = 0;
	foreach ( wt_donate_faqs() as $question => $answer ) {
		if ( wt_donate_faq_ids( array( $question ) ) ) {
			WP_CLI::log( "Exists: {$question}" );
			continue;
		}
		if ( '' === $answer ) {
			WP_CLI::warning( "Missing and has no answer to create it with: {$question}" );
			continue;
		}
		if ( ! $execute ) {
			WP_CLI::log( "Would create: {$question}" );
			continue;
		}
		$id = wp_insert_post(
			array(
				'post_type'    => 'faq',
				'post_status'  => 'publish',
				'post_title'   => $question,
				'post_content' => $answer,
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			WP_CLI::warning( "Could not create “{$question}”: " . $id->get_error_message() );
			continue;
		}
		WP_CLI::log( "Created #{$id}: {$question}" );
		++$created;
	}

	WP_CLI::success( $execute ? "Created {$created} FAQ posts." : 'Dry run complete.' );
}

WP_CLI::add_command( 'wt create-donate-faqs', 'wt_create_donate_faqs' );
