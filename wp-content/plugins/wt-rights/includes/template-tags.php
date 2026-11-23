<?php
/**
 * Template tags for the report's pages. The theme's templates call these;
 * they hold the data access and the visibility rules, so templates stay markup.
 *
 * @package WT\Rights
 */

/**
 * Sends the hub URL to the theme's hub template and gives it a title.
 *
 * @param string $template The template WordPress chose.
 */
function wt_rights_hub_template( string $template ): string {
	if ( ! get_query_var( 'wt_rights_hub' ) ) {
		return $template;
	}
	$hub = locate_template( 'template-rights-hub.php' );
	return '' !== $hub ? $hub : $template;
}
add_filter( 'template_include', 'wt_rights_hub_template' );

/**
 * The hub's document title.
 *
 * @param array<string, string> $parts Title parts.
 * @return array<string, string>
 */
function wt_rights_hub_title( array $parts ): array {
	if ( get_query_var( 'wt_rights_hub' ) ) {
		$parts['title'] = wt_rights_report_title();
	}
	return $parts;
}
add_filter( 'document_title_parts', 'wt_rights_hub_title' );

/**
 * The hub is not a 404, though the main query behind it lists nothing.
 *
 * @param bool $preempt Whether another filter already handled the 404.
 */
function wt_rights_hub_not_404( bool $preempt ): bool {
	return get_query_var( 'wt_rights_hub' ) ? true : $preempt;
}
add_filter( 'pre_handle_404', 'wt_rights_hub_not_404' );

/**
 * Keeps upcoming and embargoed regions out of search engines.
 */
function wt_rights_noindex_unreleased(): void {
	$term = is_tax( 'rights_region' ) ? get_queried_object() : null;
	if ( is_singular( 'rights_reports' ) && 'publish' !== get_post_status() ) {
		echo '<meta name="robots" content="noindex, nofollow">' . "\n";
	} elseif ( $term instanceof WP_Term && \WT\Rights\Release_Status::RELEASED !== wt_rights_region_status( $term->term_id ) ) {
		echo '<meta name="robots" content="noindex">' . "\n";
	}
}
add_action( 'wp_head', 'wt_rights_noindex_unreleased', 1 );

/**
 * The report's name.
 */
function wt_rights_report_title(): string {
	return 'Report on the Global State of Language Rights';
}

/**
 * A report post's stored report (from the release), or an empty array.
 *
 * @param int $post_id A rights_reports post.
 * @return array<string, mixed>
 */
function wt_rights_report( int $post_id ): array {
	$report = json_decode( (string) get_post_meta( $post_id, '_rights_report', true ), true );
	return is_array( $report ) ? $report : array();
}

/**
 * A report's region term, or null.
 *
 * @param int $post_id A rights_reports post.
 */
function wt_rights_report_region( int $post_id ): ?WP_Term {
	$terms = get_the_terms( $post_id, 'rights_region' );
	return is_array( $terms ) && $terms ? $terms[0] : null;
}

/**
 * Every region, in release order.
 *
 * @return list<WP_Term>
 */
function wt_rights_regions(): array {
	$terms = get_terms(
		array(
			'taxonomy'   => 'rights_region',
			'hide_empty' => false,
			'meta_key'   => 'order', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- seven terms.
			'orderby'    => 'meta_value_num',
		)
	);
	return is_array( $terms ) ? array_values( $terms ) : array();
}

/**
 * The reports in a region the current visitor may read, by name.
 *
 * @param int $term_id A region term.
 * @return list<WP_Post>
 */
function wt_rights_region_reports( int $term_id ): array {
	if ( ! wt_rights_can_read_region( $term_id ) ) {
		return array();
	}
	$statuses = array( 'publish' );
	if ( current_user_can( 'read_private_rights_reports' ) ) {
		$statuses[] = 'private';
	}
	if ( current_user_can( 'edit_rights_reports' ) ) {
		$statuses[] = 'draft';
	}
	return get_posts(
		array(
			'post_type'   => 'rights_reports',
			'post_status' => $statuses,
			'numberposts' => -1,
			'orderby'     => 'title',
			'order'       => 'ASC',
			'tax_query'   => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- one region, at most ~53 posts.
				array(
					'taxonomy' => 'rights_region',
					'terms'    => array( $term_id ),
				),
			),
		)
	);
}

/**
 * Every country name in a region, whatever its status, for the public list
 * of nations before release. Names only: no links, no findings.
 *
 * @param int $term_id A region term.
 * @return list<string>
 */
function wt_rights_region_names( int $term_id ): array {
	$listed = json_decode( (string) get_term_meta( $term_id, 'nations', true ), true );
	if ( is_array( $listed ) && $listed ) {
		return array_values( array_map( 'strval', $listed ) );
	}
	$ids   = get_posts(
		array(
			'post_type'        => 'rights_reports',
			'post_status'      => array( 'publish', 'private', 'draft' ),
			'numberposts'      => -1,
			'fields'           => 'ids',
			'suppress_filters' => true,
			'tax_query'        => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- one region.
				array(
					'taxonomy' => 'rights_region',
					'terms'    => array( $term_id ),
				),
			),
		)
	);
	$names = array_map( 'get_the_title', $ids );
	foreach ( wt_rights_region_forthcoming( $term_id ) as $country ) {
		$names[] = (string) $country['name'];
	}
	sort( $names );
	return array_values( array_unique( $names ) );
}

/**
 * A region's countries without a report yet.
 *
 * @param int $term_id A region term.
 * @return list<array{record_id: string, name: string, slug: string}>
 */
function wt_rights_region_forthcoming( int $term_id ): array {
	$list = json_decode( (string) get_term_meta( $term_id, 'forthcoming', true ), true );
	return is_array( $list ) ? $list : array();
}

/**
 * How many reports gave each answer to each indicator and rating.
 *
 * @param list<WP_Post> $posts Reports.
 * @return array<string, array<string, int>> Answer key → answer → count.
 */
function wt_rights_tally( array $posts ): array {
	$tally = array();
	foreach ( $posts as $post ) {
		foreach ( (array) ( wt_rights_report( $post->ID )['answers'] ?? array() ) as $key => $answer ) {
			if ( '' === $answer ) {
				continue;
			}
			$tally[ $key ][ $answer ] = ( $tally[ $key ][ $answer ] ?? 0 ) + 1;
		}
	}
	return $tally;
}

/**
 * The reports in every released region, for the hub's global totals.
 *
 * @return list<WP_Post>
 */
function wt_rights_released_reports(): array {
	$posts = array();
	foreach ( wt_rights_regions() as $region ) {
		if ( \WT\Rights\Release_Status::RELEASED === wt_rights_region_status( $region->term_id ) ) {
			$posts = array_merge( $posts, wt_rights_region_reports( $region->term_id ) );
		}
	}
	return $posts;
}

/**
 * The next readable report in the same region, alphabetically, wrapping round.
 *
 * @param WP_Post $post A report.
 */
function wt_rights_next_report( WP_Post $post ): ?WP_Post {
	$region = wt_rights_report_region( $post->ID );
	if ( ! $region ) {
		return null;
	}
	$posts = wt_rights_region_reports( $region->term_id );
	foreach ( $posts as $i => $candidate ) {
		if ( $candidate->ID === $post->ID ) {
			$next = $posts[ ( $i + 1 ) % count( $posts ) ];
			return $next->ID === $post->ID ? null : $next;
		}
	}
	return null;
}

/**
 * Links each language code written as "(iso)" to its language page, when the
 * language exists. One query for the whole text.
 *
 * @param string $html Report HTML.
 */
function wt_rights_link_languages( string $html ): string {
	if ( ! preg_match_all( '/\(([a-z]{3})\)/', $html, $m ) ) {
		return $html;
	}
	$codes = array_values( array_unique( $m[1] ) );
	$found = get_posts(
		array(
			'post_type'        => 'languages',
			'post_name__in'    => $codes,
			'numberposts'      => count( $codes ),
			'fields'           => 'ids',
			'suppress_filters' => true,
		)
	);
	$known = array();
	foreach ( $found as $id ) {
		$known[ get_post_field( 'post_name', $id ) ] = get_permalink( $id );
	}
	return (string) preg_replace_callback(
		'/\(([a-z]{3})\)/',
		static fn( array $c ): string => isset( $known[ $c[1] ] ) ? '(<a href="' . esc_url( (string) $known[ $c[1] ] ) . '">' . $c[1] . '</a>)' : $c[0],
		$html
	);
}

/**
 * The territory post for a report, matched on the archive Nation's record ID.
 * Null until the report carries an Archive Nation ID (see docs/language-rights.md).
 *
 * @param array<string, mixed> $report A stored report.
 */
function wt_rights_report_territory( array $report ): ?WP_Post {
	$record_id = (string) ( $report['archive_nation_id'] ?? '' );
	if ( '' === $record_id ) {
		return null;
	}
	$ids = get_posts(
		array(
			'post_type'   => 'territories',
			'numberposts' => 1,
			'fields'      => 'ids',
			'meta_key'    => '_airtable_record_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- one indexed lookup.
			'meta_value'  => $record_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- as above.
		)
	);
	return $ids ? get_post( (int) $ids[0] ) : null;
}

/**
 * The released report for a territory, for the card on its page; null when
 * there is none or it isn't released.
 *
 * @param int $territory_id A territories post.
 */
function wt_rights_territory_report( int $territory_id ): ?WP_Post {
	$record_id = (string) get_post_meta( $territory_id, '_airtable_record_id', true );
	if ( '' === $record_id ) {
		return null;
	}
	$ids = get_posts(
		array(
			'post_type'   => 'rights_reports',
			'post_status' => 'publish',
			'numberposts' => 1,
			'fields'      => 'ids',
			'meta_key'    => '_rights_archive_nation_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- one lookup.
			'meta_value'  => $record_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- as above.
		)
	);
	return $ids ? get_post( (int) $ids[0] ) : null;
}

/**
 * An answer as a labelled pill.
 *
 * @param string $answer A normalized answer.
 */
function wt_rights_answer_pill( string $answer ): string {
	if ( '' === $answer ) {
		return '<span class="wt-rights__answer wt-rights__answer--none">Not recorded</span>';
	}
	return '<span class="wt-rights__answer wt-rights__answer--' . esc_attr( sanitize_html_class( str_replace( ' ', '-', $answer ) ) ) . '">' . esc_html( ucfirst( $answer ) ) . '</span>';
}

/**
 * A collapsible source list.
 *
 * @param array<int, array{url: string, label: string}> $sources Sources.
 * @param string                                        $summary The toggle's text.
 */
function wt_rights_sources( array $sources, string $summary = 'Sources' ): string {
	if ( ! $sources ) {
		return '';
	}
	$items = '';
	foreach ( $sources as $source ) {
		$label  = '' !== $source['label'] ? $source['label'] : preg_replace( '#^https?://(www\.)?#', '', $source['url'] );
		$items .= '<li><a href="' . esc_url( $source['url'] ) . '" rel="noopener">' . esc_html( (string) $label ) . '</a></li>';
	}
	return '<details class="wt-rights__sources"><summary>' . esc_html( $summary ) . ' (' . count( $sources ) . ')</summary><ol>' . $items . '</ol></details>';
}

/**
 * The donate band, linking to /donate/.
 *
 * @param string $location data-cta-location for analytics, e.g. rights-report.
 * @param bool   $dark     The dark band (the hub) rather than the light one.
 */
function wt_rights_donate_band( string $location, bool $dark = false ): string {
	return '<section class="wt-rights__support' . ( $dark ? ' wt-rights__support--dark' : '' ) . '" id="support" aria-labelledby="wt-rights-support-' . esc_attr( $location ) . '"><div class="wt-rights__wrap">'
		. '<p class="wt-rights__kicker">Support Wikitongues</p>'
		. '<h2 id="wt-rights-support-' . esc_attr( $location ) . '">Help sustain language rights research</h2>'
		. '<p class="wt-rights__intro">Give $25 to support Wikitongues’ work, including researching language rights and making the findings available to the communities and advocates who use them. You can give once or become a monthly supporter.</p>'
		. '<a class="wt-rights__donate" href="' . esc_url( home_url( '/donate/' ) ) . '" data-cta-location="' . esc_attr( $location ) . '">Donate to Wikitongues</a>'
		. '</div></section>';
}

/**
 * A compact donate link, for the report's sidebar.
 *
 * @param string $location data-cta-location for analytics.
 */
function wt_rights_donate_link_compact( string $location ): string {
	return '<p class="wt-rights__support-compact">This research is funded by donors. <a href="' . esc_url( home_url( '/donate/' ) ) . '" data-cta-location="' . esc_attr( $location ) . '">Support it →</a></p>';
}

/**
 * The embargo notice, for press and admins reading an unreleased page.
 *
 * @param int $term_id The region term.
 */
function wt_rights_embargo_notice( int $term_id ): string {
	$status = wt_rights_region_status( $term_id );
	if ( \WT\Rights\Release_Status::RELEASED === $status || ! wt_rights_can_read_region( $term_id ) ) {
		return ''; // Released, or a visitor who sees only the public release line.
	}
	$date = (string) get_term_meta( $term_id, 'release_date', true );
	$when = '' !== $date ? ' until ' . wp_date( 'F j, Y', (int) strtotime( $date . ' 12:00:00' ) ) : '';
	$text = \WT\Rights\Release_Status::EMBARGOED === $status
		? 'Embargoed' . $when . '. Not for publication.'
		: 'Preview: not yet released' . ( '' !== $date ? ' (planned for ' . wp_date( 'F j, Y', (int) strtotime( $date . ' 12:00:00' ) ) . ')' : '' ) . '. Only admins can see this page.';
	return '<p class="wt-rights__embargo" role="status">' . esc_html( $text ) . '</p>';
}

/**
 * A region's release line for the public: "Released …" or "Planned release …".
 *
 * @param int $term_id The region term.
 */
function wt_rights_release_line( int $term_id ): string {
	$date = (string) get_term_meta( $term_id, 'release_date', true );
	$when = '' !== $date ? wp_date( 'F j, Y', (int) strtotime( $date . ' 12:00:00' ) ) : '';
	if ( \WT\Rights\Release_Status::RELEASED === wt_rights_region_status( $term_id ) ) {
		return 'Released' . ( '' !== $when ? ' ' . $when : '' );
	}
	return '' !== $when ? 'Planned release ' . $when : 'Upcoming';
}
