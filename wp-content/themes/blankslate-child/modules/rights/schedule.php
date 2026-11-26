<?php
/**
 * The Language Rights publication schedule, on the hub and the project page.
 *
 * Milestones in order: the project announcement (01, when the release's front
 * matter has its date), the seven regional releases, the Complete Edition.
 * Dates come from the imported release, never typed into a page.
 *
 * @var array<string, mixed> $args `id` (section anchor), `heading`, `intro`.
 */

$wt_regions      = wt_rights_regions();
$wt_front        = wt_rights_front_matter();
$wt_announcement = (array) ( $wt_front['announcement'] ?? array() );
$wt_announced_on = (string) ( $wt_announcement['release_date'] ?? '' );
$wt_paper_url    = (string) ( $wt_announcement['paper_url'] ?? '' );
$wt_synthesis    = (string) ( $wt_front['synthesis']['release_date'] ?? '' );
$wt_date         = static fn( string $ymd ): string => wp_date( 'F j, Y', (int) strtotime( $ymd . ' 12:00:00' ) );
$wt_number       = static fn( int $n ): string => sprintf( '%02d', $n );

if ( ! $wt_regions ) {
	return; // No release imported yet.
}
?>
<section id="<?php echo esc_attr( (string) ( $args['id'] ?? 'releases' ) ); ?>" class="wt-rights__section wt-rights__section--wash">
	<div class="wt-rights__wrap">
		<p class="wt-rights__kicker">Publication schedule</p>
		<h2><?php echo esc_html( (string) ( $args['heading'] ?? 'Regional releases' ) ); ?></h2>
		<p class="wt-rights__intro"><?php echo esc_html( (string) ( $args['intro'] ?? 'The report will roll out in seven regional installments. Browse the coverage below, or sign up for release announcements. Planned dates may change.' ) ); ?></p>
		<ol class="wt-rights__releases">
			<?php if ( '' !== $wt_announced_on ) : ?>
				<li class="wt-rights__release" id="release-announcement">
					<span class="wt-rights__release-index" aria-hidden="true"><?php echo esc_html( $wt_number( 1 ) ); ?></span>
					<div>
						<p class="wt-rights__kicker">Project announcement</p>
						<h3>Project announcement and white paper</h3>
						<p><?php echo esc_html( $wt_date( $wt_announced_on ) . ( '12-10' === substr( $wt_announced_on, 5 ) ? ' · Human Rights Day' : '' ) ); ?></p>
						<?php if ( '' !== $wt_paper_url ) : ?>
							<div class="wt-rights__release-actions">
								<a href="<?php echo esc_url( $wt_paper_url ); ?>" data-rights-paper="schedule">Read the white paper →</a>
							</div>
						<?php endif; ?>
					</div>
				</li>
			<?php endif; ?>
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
					<span class="wt-rights__release-index" aria-hidden="true"><?php echo esc_html( $wt_number( wt_rights_milestone_number( $wt_order ) ) ); ?></span>
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
				<span class="wt-rights__release-index" aria-hidden="true"><?php echo esc_html( $wt_number( wt_rights_milestone_total( count( $wt_regions ) ) ) ); ?></span>
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
