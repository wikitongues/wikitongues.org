<?php
/**
 * A region of the Report on the Global State of Language Rights.
 *
 * Before release the public sees the release date and the list of nations;
 * press (while embargoed) and admins see the region in full: its overview
 * (findings and evidence cards from the region Doc), the indicator
 * comparison, the country reports, and the director's letter.
 */
get_header();

$wt_region      = get_queried_object();
$wt_readable    = wt_rights_can_read_region( $wt_region->term_id );
$wt_reports     = wt_rights_region_reports( $wt_region->term_id );
$wt_structure   = wt_rights_structure();
$wt_order       = (int) get_term_meta( $wt_region->term_id, 'order', true );
$wt_tally       = wt_rights_tally( $wt_reports );
$wt_overview    = wt_rights_region_overview( $wt_region->term_id );
$wt_front       = wt_rights_front_matter();
$wt_names       = wt_rights_region_names( $wt_region->term_id );
$wt_map         = wt_rights_region_map( $wt_region );
$wt_forthcoming = wt_rights_region_forthcoming( $wt_region->term_id );
$wt_answers     = array( 'yes', 'no', 'unclear', 'not applicable' );

// Evidence cards: the region Doc's own, else each country's story card.
$wt_cards = array();
$wt_links = array();
foreach ( $wt_reports as $wt_post ) {
	$wt_links[ (string) get_post_meta( $wt_post->ID, '_airtable_record_id', true ) ] = get_permalink( $wt_post );
}
if ( ! empty( $wt_overview['cards'] ) ) {
	foreach ( $wt_overview['cards'] as $wt_card ) {
		$wt_card['url'] = $wt_links[ $wt_card['report_id'] ] ?? '';
		$wt_cards[]     = $wt_card;
	}
} else {
	foreach ( $wt_reports as $wt_post ) {
		$wt_story = wt_rights_report( $wt_post->ID )['content']['story_card'] ?? null;
		if ( is_array( $wt_story ) ) {
			$wt_cards[] = array(
				'country' => get_the_title( $wt_post ),
				'url'     => get_permalink( $wt_post ),
				'title'   => (string) $wt_story['title'],
				'html'    => (string) $wt_story['html'],
				'links'   => (array) ( $wt_story['links'] ?? array() ),
			);
		}
	}
}

$wt_toc = array();
if ( $wt_readable ) {
	if ( $wt_overview || $wt_cards ) {
		$wt_toc['findings'] = 'Findings';
	}
	if ( $wt_reports ) {
		$wt_toc['comparison'] = 'Regional comparison';
	}
	$wt_toc['countries'] = 'Country reports';
	if ( ! empty( $wt_overview['letter'] ) ) {
		$wt_toc['letter'] = 'Director’s letter';
	}
}
?>
<main class="wt-rights wt-rights--region" data-rights-region="<?php echo esc_attr( $wt_region->slug ); ?>">
	<header class="wt-rights__hero">
		<div class="wt-rights__wrap wt-rights__hero-grid">
			<div>
				<?php echo wt_rights_embargo_notice( $wt_region->term_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the function. ?>
				<p class="wt-rights__kicker"><a href="<?php echo esc_url( home_url( '/' . WT_RIGHTS_BASE . '/' ) ); ?>"><?php echo esc_html( wt_rights_report_title() ); ?></a><?php echo $wt_order ? esc_html( ' · ' . sprintf( '%02d / %02d', wt_rights_milestone_number( $wt_order ), wt_rights_milestone_total( count( $wt_structure['regions'] ) ) ) ) : ''; ?></p>
				<h1><?php echo esc_html( $wt_region->name ); ?></h1>
				<?php if ( ! empty( $wt_front['summary'] ) ) : ?>
					<p class="wt-rights__deck"><?php echo esc_html( (string) $wt_front['summary'] ); ?></p>
				<?php endif; ?>
				<?php if ( $wt_readable ) : ?>
					<p><a class="wt-rights__hero-link" href="#countries">Explore the reports</a></p>
				<?php else : ?>
					<p class="wt-rights__release-line"><?php echo esc_html( wt_rights_release_line( $wt_region->term_id ) ); ?></p>
				<?php endif; ?>
			</div>
			<?php if ( '' !== $wt_map ) : ?>
				<div class="wt-rights__locator">
					<?php echo $wt_map; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wt-maps escapes its SVG. ?>
					<p class="wt-rights__locator-caption"><?php echo esc_html( sprintf( '%d countries in this release. Map data from Natural Earth.', count( $wt_names ) ) ); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</header>

	<?php get_template_part( 'modules/rights/jumpnav', null, array( 'links' => $wt_toc ) ); ?>

	<?php if ( ! $wt_readable ) : ?>
		<section class="wt-rights__section" id="countries">
			<div class="wt-rights__wrap">
				<p class="wt-rights__kicker">In this release</p>
				<h2><?php echo esc_html( sprintf( '%d %s', count( $wt_names ), 1 === count( $wt_names ) ? 'nation' : 'nations' ) ); ?></h2>
				<ol class="wt-rights__countries">
					<?php foreach ( $wt_names as $wt_name ) : ?>
						<li><span><?php echo esc_html( $wt_name ); ?></span></li>
					<?php endforeach; ?>
				</ol>
				<p><?php echo wt_rights_updates_button( $wt_region->slug ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the function. ?></p>
			</div>
		</section>
	<?php else : ?>
		<?php if ( $wt_overview || $wt_cards ) : ?>
			<section id="findings" class="wt-rights__section wt-rights__section--wash">
				<div class="wt-rights__wrap">
					<p class="wt-rights__kicker">Regional findings</p>
					<?php if ( ! empty( $wt_overview['headline'] ) ) : ?>
						<h2><?php echo esc_html( (string) $wt_overview['headline'] ); ?></h2>
					<?php else : ?>
						<h2>From the reports</h2>
					<?php endif; ?>
					<?php if ( ! empty( $wt_overview['html'] ) ) : ?>
						<div class="wt-rights__intro wt_prose"><?php echo wp_kses_post( wt_rights_link_languages( (string) $wt_overview['html'] ) ); ?></div>
						<?php echo wt_rights_links_line( (array) $wt_overview['sources'], 'wt-rights__links wt-rights__links--small' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the function. ?>
					<?php endif; ?>
					<?php foreach ( (array) ( $wt_overview['findings'] ?? array() ) as $wt_finding ) : ?>
						<div class="wt-rights__finding">
							<h3><?php echo esc_html( (string) $wt_finding['title'] ); ?></h3>
							<div class="wt_prose"><?php echo wp_kses_post( (string) $wt_finding['html'] ); ?></div>
							<?php echo wt_rights_sources( (array) $wt_finding['sources'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the function. ?>
						</div>
					<?php endforeach; ?>
					<?php if ( $wt_cards ) : ?>
						<div class="wt-rights__cards">
							<?php foreach ( $wt_cards as $wt_card ) : ?>
								<article class="wt-rights__card">
									<p class="wt-rights__kicker">
										<?php if ( '' !== (string) $wt_card['url'] ) : ?>
											<a href="<?php echo esc_url( (string) $wt_card['url'] ); ?>"><?php echo esc_html( (string) $wt_card['country'] ); ?></a>
										<?php else : ?>
											<?php echo esc_html( (string) $wt_card['country'] ); ?>
										<?php endif; ?>
									</p>
									<h3><?php echo esc_html( (string) $wt_card['title'] ); ?></h3>
									<div class="wt_prose"><?php echo wp_kses_post( (string) $wt_card['html'] ); ?></div>
									<?php echo wt_rights_links_line( (array) $wt_card['links'], 'wt-rights__links wt-rights__links--small wt-rights__card-links' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the function. ?>
								</article>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $wt_reports ) : ?>
			<?php
			$wt_none_na = 0 === array_sum( array_map( static fn( array $t ): int => (int) ( $t['not applicable'] ?? 0 ), $wt_tally ) );
			$wt_groups  = array(
				'Restrictions and repression' => array_slice( $wt_structure['indicators'], 0, 2 ),
				'Recognition and support'     => array_slice( $wt_structure['indicators'], 2 ),
			);
			?>
			<section id="comparison" class="wt-rights__section">
				<div class="wt-rights__wrap">
					<p class="wt-rights__kicker"><?php echo esc_html( sprintf( 'Evidence from %d countries in %s', count( $wt_reports ), $wt_region->name ) ); ?></p>
					<h2>What the assessments found</h2>
					<div class="wt-rights__split">
						<div class="wt-rights__box">
							<h3>How to read this report</h3>
							<p>Each bar shows how many countries received each answer. A Yes on the first two questions means the assessment found restrictions or repression affecting language users. A Yes on the last three means it found official recognition or public support for historically rooted languages: spoken or signed languages with a longstanding presence in a place.</p>
							<ol>
								<?php foreach ( $wt_structure['indicators'] as $wt_item ) : ?>
									<li><?php echo esc_html( $wt_item['question'] ); ?></li>
								<?php endforeach; ?>
							</ol>
							<p><strong>Unclear</strong> means the evidence did not resolve whether the threshold was met. <strong>Not applicable</strong> means the indicator does not apply.<?php echo $wt_none_na ? ' No indicator in this region was marked Not applicable.' : ''; ?></p>
						</div>
						<figure class="wt-rights__chart">
							<figcaption>
								<h3>Country counts by indicator</h3>
								<p class="wt-rights__key">
									<?php foreach ( $wt_answers as $wt_answer ) : ?>
										<span data-answer="<?php echo esc_attr( str_replace( ' ', '-', $wt_answer ) ); ?>"><?php echo esc_html( ucfirst( $wt_answer ) ); ?></span>
									<?php endforeach; ?>
								</p>
							</figcaption>
							<?php foreach ( $wt_groups as $wt_group => $wt_items ) : ?>
								<h4><?php echo esc_html( $wt_group ); ?></h4>
								<?php foreach ( $wt_items as $wt_item ) : ?>
									<div class="wt-rights__bar-row">
										<span><?php echo esc_html( $wt_item['label'] ); ?></span>
										<span class="wt-rights__bar" aria-hidden="true">
											<?php foreach ( $wt_answers as $wt_answer ) : ?>
												<?php $wt_n = (int) ( $wt_tally[ $wt_item['key'] ][ $wt_answer ] ?? 0 ); ?>
												<?php if ( $wt_n ) : ?>
													<span data-answer="<?php echo esc_attr( str_replace( ' ', '-', $wt_answer ) ); ?>" style="flex-grow:<?php echo esc_attr( (string) $wt_n ); ?>"><?php echo esc_html( (string) $wt_n ); ?></span>
												<?php endif; ?>
											<?php endforeach; ?>
										</span>
									</div>
								<?php endforeach; ?>
							<?php endforeach; ?>
							<details class="wt-rights__chart-table">
								<summary>Read chart as a table</summary>
								<div class="table-scroll">
									<table>
										<caption>Assessment counts</caption>
										<thead><tr><th scope="col">Indicator</th><th scope="col">Yes</th><th scope="col">No</th><th scope="col">Unclear</th><th scope="col">Not applicable</th></tr></thead>
										<tbody>
											<?php foreach ( $wt_structure['indicators'] as $wt_item ) : ?>
												<tr>
													<th scope="row"><?php echo esc_html( $wt_item['label'] ); ?></th>
													<?php foreach ( $wt_answers as $wt_answer ) : ?>
														<td><?php echo esc_html( (string) ( $wt_tally[ $wt_item['key'] ][ $wt_answer ] ?? 0 ) ); ?></td>
													<?php endforeach; ?>
												</tr>
											<?php endforeach; ?>
										</tbody>
									</table>
								</div>
							</details>
						</figure>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<section id="countries" class="wt-rights__section">
			<div class="wt-rights__wrap">
				<p class="wt-rights__kicker">Explore the evidence</p>
				<h2>Country reports</h2>
				<p class="wt-rights__intro">Each profile traces the language landscape, history, current law and practice, and community testimony where sources are available.</p>
				<ol class="wt-rights__countries">
					<?php foreach ( $wt_reports as $wt_post ) : ?>
						<li>
							<a href="<?php echo esc_url( get_permalink( $wt_post ) ); ?>"><?php echo esc_html( get_the_title( $wt_post ) ); ?></a>
							<?php echo wt_rights_trend_badge( (string) ( wt_rights_report( $wt_post->ID )['answers']['trend'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the function. ?>
						</li>
					<?php endforeach; ?>
					<?php foreach ( $wt_forthcoming as $wt_country ) : ?>
						<li class="wt-rights__forthcoming"><span><?php echo esc_html( (string) $wt_country['name'] ); ?></span> <em>Report forthcoming</em></li>
					<?php endforeach; ?>
				</ol>
			</div>
		</section>
	<?php endif; ?>

	<?php echo wt_rights_donate_band( 'rights-region' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the function. ?>

	<?php if ( $wt_readable && ! empty( $wt_overview['letter'] ) ) : ?>
		<section id="letter" class="wt-rights__section wt-rights__section--ink">
			<div class="wt-rights__wrap">
				<p class="wt-rights__kicker"><?php echo esc_html( $wt_region->name ); ?></p>
				<h2>A letter from the director</h2>
				<?php echo wt_rights_letter( (array) $wt_overview['letter'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the function. ?>
			</div>
		</section>
	<?php endif; ?>
</main>
<?php
get_footer();
