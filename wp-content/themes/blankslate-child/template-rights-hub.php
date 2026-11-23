<?php
/**
 * The hub of the Report on the Global State of Language Rights, at
 * /language-rights/report/. Routed by the wt-rights plugin, not a page.
 *
 * Global findings appear only with the Complete Edition (plan 2.2), so the
 * hub shows the release schedule and the front matter until then.
 */
get_header();

$wt_structure  = wt_rights_structure();
$wt_regions    = wt_rights_regions();
$wt_front      = wt_rights_front_matter();
$wt_method     = (array) ( $wt_front['methodology'] ?? array() );
$wt_key        = (array) ( $wt_front['answer_key'] ?? array() );
$wt_letter     = $wt_front['letter'] ?? null;
$wt_synthesis  = (string) ( $wt_front['synthesis']['release_date'] ?? '' );
$wt_date       = static fn( string $ymd ): string => wp_date( 'F j, Y', (int) strtotime( $ymd . ' 12:00:00' ) );
$wt_has_method = '' !== (string) ( $wt_method['html'] ?? '' ) || ! empty( $wt_front['indicators'] );

$wt_toc = array(
	'releases' => 'Regional releases',
	'support'  => 'Support the project',
);
if ( $wt_has_method ) {
	$wt_toc['methodology'] = 'Methodology';
}
if ( is_array( $wt_letter ) ) {
	$wt_toc['letter'] = 'Director’s letter';
}
?>
<main class="wt-rights wt-rights--hub">
	<header class="wt-rights__hero">
		<div class="wt-rights__wrap">
			<p class="wt-rights__kicker">Wikitongues · First edition</p>
			<h1>The Global State of Language Rights</h1>
			<?php if ( ! empty( $wt_front['summary'] ) ) : ?>
				<p class="wt-rights__deck"><?php echo esc_html( (string) $wt_front['summary'] ); ?></p>
			<?php endif; ?>
			<p><a class="wt-rights__hero-link" href="#releases">See the release schedule</a></p>
		</div>
	</header>

	<?php get_template_part( 'modules/rights/jumpnav', null, array( 'links' => $wt_toc ) ); ?>

	<section id="releases" class="wt-rights__section wt-rights__section--wash">
		<div class="wt-rights__wrap">
			<p class="wt-rights__kicker">Publication schedule</p>
			<h2>Regional releases</h2>
			<p class="wt-rights__intro">The report will roll out in seven regional installments. Browse the coverage below, or sign up for release announcements. Planned dates may change.</p>
			<ol class="wt-rights__releases">
				<?php foreach ( $wt_regions as $wt_region ) : ?>
					<?php
					$wt_status   = wt_rights_region_status( $wt_region->term_id );
					$wt_names    = wt_rights_region_names( $wt_region->term_id );
					$wt_readable = wt_rights_can_read_region( $wt_region->term_id );
					$wt_released = \WT\Rights\Release_Status::RELEASED === $wt_status;
					$wt_order    = (int) get_term_meta( $wt_region->term_id, 'order', true );
					$wt_reports  = array();
					foreach ( wt_rights_region_reports( $wt_region->term_id ) as $wt_post ) {
						$wt_reports[ get_the_title( $wt_post ) ] = get_permalink( $wt_post );
					}
					if ( $wt_released ) {
						$wt_label = 'Released';
					} elseif ( $wt_readable ) {
						$wt_label = \WT\Rights\Release_Status::EMBARGOED === $wt_status ? 'Embargoed · preview available' : 'Preview available';
					} else {
						$wt_label = 'Upcoming';
					}
					?>
					<li class="wt-rights__release" id="release-<?php echo esc_attr( $wt_region->slug ); ?>">
						<span class="wt-rights__release-index" aria-hidden="true"><?php echo esc_html( sprintf( '%02d', $wt_order ) ); ?></span>
						<div>
							<p class="wt-rights__kicker"><?php echo esc_html( $wt_label ); ?></p>
							<h3><?php echo esc_html( $wt_region->name ); ?></h3>
							<p><?php echo esc_html( wt_rights_release_line( $wt_region->term_id ) ); ?></p>
							<div class="wt-rights__release-actions">
								<?php if ( $wt_readable ) : ?>
									<a href="<?php echo esc_url( (string) get_term_link( $wt_region ) ); ?>"><?php echo esc_html( $wt_released ? 'Read the regional report →' : 'Open regional preview →' ); ?></a>
								<?php else : ?>
									<?php echo wt_rights_updates_button( $wt_region->slug ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the function. ?>
								<?php endif; ?>
								<?php if ( $wt_names ) : ?>
									<details>
										<summary><?php echo esc_html( sprintf( 'Browse %d %s', count( $wt_names ), 1 === count( $wt_names ) ? 'nation' : 'nations' ) ); ?></summary>
										<ul>
											<?php foreach ( $wt_names as $wt_name ) : ?>
												<li>
													<?php if ( isset( $wt_reports[ $wt_name ] ) ) : ?>
														<a href="<?php echo esc_url( (string) $wt_reports[ $wt_name ] ); ?>"><?php echo esc_html( $wt_name ); ?></a>
													<?php else : ?>
														<?php echo esc_html( $wt_name ); ?>
													<?php endif; ?>
												</li>
											<?php endforeach; ?>
										</ul>
									</details>
								<?php endif; ?>
							</div>
						</div>
					</li>
				<?php endforeach; ?>
				<li class="wt-rights__release" id="release-synthesis">
					<span class="wt-rights__release-index" aria-hidden="true"><?php echo esc_html( sprintf( '%02d', count( $wt_regions ) + 1 ) ); ?></span>
					<div>
						<p class="wt-rights__kicker">Upcoming · Complete edition</p>
						<h3>Global synthesis</h3>
						<p><?php echo esc_html( '' !== $wt_synthesis ? 'Planned release ' . $wt_date( $wt_synthesis ) : 'Upcoming' ); ?></p>
						<p>At launch, this page will include data for all five indicators and a summary of our findings, highlighting progress and concerns around the world.</p>
						<div class="wt-rights__release-actions">
							<?php echo wt_rights_updates_button( 'synthesis' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the function. ?>
						</div>
					</div>
				</li>
			</ol>
		</div>
	</section>

	<?php echo wt_rights_donate_band( 'rights-hub', true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the function. ?>

	<?php if ( $wt_has_method ) : ?>
		<section id="methodology" class="wt-rights__section">
			<div class="wt-rights__wrap">
				<p class="wt-rights__kicker">How we assessed the evidence</p>
				<h2>Methodology</h2>
				<div class="wt-rights__intro wt_prose"><?php echo wp_kses_post( (string) ( $wt_method['html'] ?? '' ) ); ?></div>
				<div class="wt-rights__method">
					<div>
						<?php foreach ( (array) ( $wt_method['sections'] ?? array() ) as $wt_i => $wt_part ) : ?>
							<div class="<?php echo esc_attr( $wt_i ? 'wt-rights__method-note' : 'wt-rights__method-part' ); ?>">
								<h3><?php echo esc_html( (string) $wt_part['title'] ); ?></h3>
								<div class="wt_prose"><?php echo wp_kses_post( (string) $wt_part['html'] ); ?></div>
							</div>
						<?php endforeach; ?>
					</div>
					<div>
						<h3>Explore the five indicators</h3>
						<p>Open any indicator to see the full question, how it is assessed, and an example.</p>
						<?php foreach ( $wt_structure['indicators'] as $wt_i => $wt_item ) : ?>
							<?php $wt_note = (array) ( $wt_front['indicators'][ $wt_item['key'] ] ?? array() ); ?>
							<details class="wt-rights__indicator" id="indicator-<?php echo esc_attr( $wt_item['key'] ); ?>">
								<summary><span class="wt-rights__indicator-number"><?php echo esc_html( sprintf( '%02d', $wt_i + 1 ) ); ?></span><span><?php echo esc_html( $wt_item['label'] ); ?></span></summary>
								<div class="wt-rights__indicator-body">
									<h4><?php echo esc_html( $wt_item['question'] ); ?></h4>
									<div class="wt_prose"><?php echo wp_kses_post( (string) ( $wt_note['html'] ?? '' ) ); ?></div>
									<?php if ( ! empty( $wt_note['example'] ) ) : ?>
										<p class="wt-rights__example"><strong>Example</strong> <?php echo wp_kses_post( (string) $wt_note['example'] ); ?></p>
									<?php endif; ?>
								</div>
							</details>
						<?php endforeach; ?>
					</div>
				</div>
				<?php if ( ! empty( $wt_key['answers'] ) ) : ?>
					<div class="wt-rights__answer-key" aria-labelledby="answer-key">
						<h3 id="answer-key">What the four answers mean</h3>
						<div class="wt_prose"><?php echo wp_kses_post( (string) ( $wt_key['html'] ?? '' ) ); ?></div>
						<dl>
							<?php foreach ( (array) $wt_key['answers'] as $wt_answer => $wt_meaning ) : ?>
								<div>
									<dt><?php echo wt_rights_answer_pill( (string) $wt_answer ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the function. ?></dt>
									<dd><?php echo esc_html( (string) $wt_meaning ); ?></dd>
								</div>
							<?php endforeach; ?>
						</dl>
					</div>
				<?php endif; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( is_array( $wt_letter ) ) : ?>
		<section id="letter" class="wt-rights__section wt-rights__section--ink">
			<div class="wt-rights__wrap">
				<p class="wt-rights__kicker">The first edition</p>
				<h2>A letter from the director</h2>
				<?php echo wt_rights_letter( $wt_letter, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the function. ?>
			</div>
		</section>
	<?php endif; ?>
</main>
<?php
get_footer();
