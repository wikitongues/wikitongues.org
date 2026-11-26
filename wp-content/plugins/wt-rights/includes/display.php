<?php
/**
 * Display helpers for the hub, region and country templates: the editorial
 * parts (region overview, front matter), maps, letters, and small pieces of
 * markup the three templates share.
 *
 * @package WT\Rights
 */

/**
 * The front matter from the last import, or null.
 *
 * @return array<string, mixed>|null
 */
function wt_rights_front_matter(): ?array {
	$front = get_option( 'wt_rights_front_matter' );
	return is_array( $front ) ? $front : null;
}

/**
 * A region's overview, for visitors who may read the region; null otherwise.
 * Like the reports, it is embargoed text: the public sees it only once released.
 *
 * @param int $term_id A region term.
 * @return array<string, mixed>|null
 */
function wt_rights_region_overview( int $term_id ): ?array {
	if ( ! wt_rights_can_read_region( $term_id ) ) {
		return null;
	}
	$overview = json_decode( (string) get_term_meta( $term_id, 'overview', true ), true );
	return is_array( $overview ) ? $overview : null;
}

/**
 * The archive Nation record IDs of a region's reports, whatever their status.
 * IDs only, so nothing embargoed shows; the map draws the region's outline.
 *
 * @param int $term_id A region term.
 * @return list<string> Sorted.
 */
function wt_rights_region_places( int $term_id ): array {
	$ids    = get_posts(
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
	$places = array();
	foreach ( $ids as $id ) {
		$record_id = (string) get_post_meta( (int) $id, '_rights_archive_nation_id', true );
		if ( '' !== $record_id ) {
			$places[] = $record_id;
		}
	}
	$places = array_values( array_unique( $places ) );
	sort( $places );
	return $places;
}

/**
 * Tells wt-maps to build a map for each region, so `wp wt-maps export` and
 * `doctor` include it alongside the language maps.
 *
 * @param array<string, list<string>> $sets Label → place record IDs.
 * @return array<string, list<string>>
 */
function wt_rights_map_sets( array $sets ): array {
	foreach ( wt_rights_regions() as $region ) {
		$places = wt_rights_region_places( $region->term_id );
		if ( count( $places ) > 1 ) {
			$sets[ 'Language rights region: ' . $region->name ] = $places;
		}
	}
	return $sets;
}
add_filter( 'wt_maps_extra_sets', 'wt_rights_map_sets' );

/**
 * A region's map: its countries highlighted. '' until the reports carry their
 * archive Nation IDs and the set is built (`node build.mjs --only=<key>`).
 *
 * @param WP_Term $region A region term.
 */
function wt_rights_region_map( WP_Term $region ): string {
	if ( ! function_exists( 'wt_map_places' ) ) {
		return '';
	}
	$places = wt_rights_region_places( $region->term_id );
	if ( count( $places ) < 2 ) {
		return '';
	}
	return wt_map_places(
		array(
			'places' => $places,
			'title'  => 'Countries in the ' . $region->name . ' release',
			'class'  => 'wt-rights__map',
		)
	);
}

/**
 * A country report's locator map, from its archive Nation ID.
 *
 * @param array<string, mixed> $report A stored report.
 * @param string               $name   The country's name.
 */
function wt_rights_report_map( array $report, string $name ): string {
	$record_id = (string) ( $report['archive_nation_id'] ?? '' );
	if ( '' === $record_id || ! function_exists( 'wt_map_places' ) ) {
		return '';
	}
	return wt_map_places(
		array(
			'places' => array( $record_id ),
			'title'  => 'Where ' . $name . ' is',
			'class'  => 'wt-rights__map',
		)
	);
}

/**
 * A rating (status quo ante or trend) as a bordered badge in its colour.
 *
 * @param string $value negative, neutral or positive.
 */
function wt_rights_trend_badge( string $value ): string {
	if ( '' === $value ) {
		return '';
	}
	return '<span class="wt-rights__trend" data-trend="' . esc_attr( $value ) . '">' . esc_html( ucfirst( $value ) ) . '</span>';
}

/**
 * Links as "Label · Label", for card footers and source lines.
 *
 * @param array<int, array{url: string, label: string}> $links Links.
 * @param string                                        $css_class Class on the paragraph.
 */
function wt_rights_links_line( array $links, string $css_class = 'wt-rights__links' ): string {
	if ( ! $links ) {
		return '';
	}
	$items = array();
	foreach ( $links as $link ) {
		$label   = '' !== $link['label'] ? $link['label'] : (string) preg_replace( '#^https?://(www\.)?#', '', $link['url'] );
		$items[] = '<a href="' . esc_url( $link['url'] ) . '" rel="noopener">' . esc_html( $label ) . '</a>';
	}
	return '<p class="' . esc_attr( $css_class ) . '">' . implode( ' · ', $items ) . '</p>';
}

/**
 * A letter from the director. Collapsed shows the first paragraph and puts
 * the rest behind "Read the full letter".
 *
 * @param array{html: string, name: string, role: string} $letter    From the release.
 * @param bool                                            $collapsed Whether to fold all but the first paragraph.
 */
function wt_rights_letter( array $letter, bool $collapsed = false ): string {
	$html      = wp_kses_post( $letter['html'] );
	$signature = '' !== $letter['name']
		? '<p class="wt-rights__signature"><strong>' . esc_html( $letter['name'] ) . '</strong>' . ( '' !== $letter['role'] ? '<br>' . esc_html( $letter['role'] ) : '' ) . '</p>'
		: '';
	$split     = strpos( $html, '</p>' );
	if ( ! $collapsed || false === $split ) {
		return '<div class="wt-rights__letter wt_prose">' . $html . $signature . '</div>';
	}
	$lead = substr( $html, 0, $split + 4 );
	$rest = trim( substr( $html, $split + 4 ) );
	if ( '' === $rest ) {
		return '<div class="wt-rights__letter wt_prose">' . $lead . $signature . '</div>';
	}
	return '<div class="wt-rights__letter wt_prose">' . $lead
		. '<details class="wt-rights__letter-more"><summary>Read the full letter</summary>' . $rest . $signature . '</details></div>';
}

/**
 * The previous readable report in the same region, alphabetically, wrapping round.
 *
 * @param WP_Post $post A report.
 */
function wt_rights_previous_report( WP_Post $post ): ?WP_Post {
	$region = wt_rights_report_region( $post->ID );
	if ( ! $region ) {
		return null;
	}
	$posts = wt_rights_region_reports( $region->term_id );
	$count = count( $posts );
	foreach ( $posts as $i => $candidate ) {
		if ( $candidate->ID === $post->ID ) {
			$previous = $posts[ ( $i - 1 + $count ) % $count ];
			return $previous->ID === $post->ID ? null : $previous;
		}
	}
	return null;
}

/**
 * The "Get release updates" trigger: a link to the newsletter that opens the
 * signup dialog when scripts run.
 *
 * @param string $region Region key, or 'synthesis'.
 */
function wt_rights_updates_button( string $region ): string {
	return '<a class="wt-rights__updates" href="' . esc_url( home_url( '/#signup' ) ) . '" data-rights-updates="' . esc_attr( $region ) . '">Get release updates</a>';
}

/**
 * The release-updates signup dialog: the site's Mailchimp list, as in the
 * newsletter module. Printed once, in the footer, on report pages.
 *
 * rights.js submits it in the page, through Mailchimp's JSONP endpoint; without
 * scripts it posts to Mailchimp in a new tab. WT_RIGHTS_MAILCHIMP_TAG, when
 * set to a Mailchimp tag ID, marks the subscriber as a Language Rights signup.
 */
function wt_rights_updates_dialog(): void {
	if ( null === wt_rights_page_context() || ! function_exists( 'get_field' ) ) {
		return;
	}
	$user = (string) get_field( 'mailchimp_newsletter_user', 'options' );
	$id   = (string) get_field( 'mailchimp_newsletter_id', 'options' );
	if ( '' === $user || '' === $id ) {
		return;
	}
	$action = 'https://wikitongues.us9.list-manage.com/subscribe/post?u=' . rawurlencode( $user ) . '&id=' . rawurlencode( $id );
	$tag    = defined( 'WT_RIGHTS_MAILCHIMP_TAG' ) ? (string) constant( 'WT_RIGHTS_MAILCHIMP_TAG' ) : '';
	?>
	<dialog class="wt-rights-dialog" id="wt-rights-updates" aria-labelledby="wt-rights-updates-title">
		<div class="wt-rights-dialog__head">
			<h2 id="wt-rights-updates-title">Get release updates</h2>
			<button class="wt-rights-dialog__close" type="button" aria-label="Close">✕</button>
		</div>
		<p>Join Wikitongues’ mailing list for regional and global release announcements.</p>
		<form class="wt-rights-dialog__form" action="<?php echo esc_url( $action ); ?>" method="post" target="_blank" rel="noopener">
			<label for="wt-rights-updates-email">Email address</label>
			<div class="wt-rights-dialog__fields">
				<input type="email" id="wt-rights-updates-email" name="EMAIL" autocomplete="email" required>
				<button type="submit" name="subscribe">Sign up</button>
			</div>
			<?php if ( '' !== $tag ) : ?>
				<input type="hidden" name="tags" value="<?php echo esc_attr( $tag ); ?>">
			<?php endif; ?>
			<div class="wt-rights-dialog__trap" aria-hidden="true"><input type="text" name="<?php echo esc_attr( 'b_' . $user . '_' . $id ); ?>" tabindex="-1" autocomplete="off"></div>
			<p class="wt-rights-dialog__note" role="status" aria-live="polite">We’ll ask you to confirm by email.</p>
		</form>
	</dialog>
	<?php
}
add_action( 'wp_footer', 'wt_rights_updates_dialog' );
