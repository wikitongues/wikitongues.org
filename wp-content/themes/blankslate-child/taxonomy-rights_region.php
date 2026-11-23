<?php
/**
 * A region of the Report on the Global State of Language Rights.
 *
 * Before release the public sees the release date and the list of nations;
 * press (while embargoed) and admins see the region in full.
 */
get_header();

$wt_region    = get_queried_object();
$wt_readable  = wt_rights_can_read_region( $wt_region->term_id );
$wt_reports   = wt_rights_region_reports( $wt_region->term_id );
$wt_structure = wt_rights_structure();
$wt_order     = (int) get_term_meta( $wt_region->term_id, 'order', true );
$wt_tally     = wt_rights_tally( $wt_reports );
$wt_cards     = array();
foreach ( $wt_reports as $wt_post ) {
	$wt_card = wt_rights_report( $wt_post->ID )['content']['story_card'] ?? null;
	if ( is_array( $wt_card ) ) {
		$wt_cards[] = array( $wt_post, $wt_card );
	}
}
$wt_forthcoming = wt_rights_region_forthcoming( $wt_region->term_id );
?>
<main class="wt-rights wt-rights--region" data-rights-region="<?php echo esc_attr( $wt_region->slug ); ?>">
	<header class="wt-rights__hero">
		<?php echo wt_rights_embargo_notice( $wt_region->term_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the function. ?>
		<p class="wt-rights__kicker"><a href="<?php echo esc_url( home_url( '/' . WT_RIGHTS_BASE . '/' ) ); ?>"><?php echo esc_html( wt_rights_report_title() ); ?></a><?php echo $wt_order ? esc_html( ' · ' . sprintf( '%02d / %02d', $wt_order, count( $wt_structure['regions'] ) ) ) : ''; ?></p>
		<h1><?php echo esc_html( $wt_region->name ); ?></h1>
		<p class="wt-rights__release"><?php echo esc_html( wt_rights_release_line( $wt_region->term_id ) ); ?></p>
	</header>

	<?php if ( ! $wt_readable ) : ?>
		<section class="wt-rights__section">
			<h2>Nations in this release</h2>
			<ul class="wt-rights__nations">
				<?php foreach ( wt_rights_region_names( $wt_region->term_id ) as $wt_name ) : ?>
					<li><?php echo esc_html( $wt_name ); ?></li>
				<?php endforeach; ?>
			</ul>
			<p><a class="wt-rights__updates" href="<?php echo esc_url( home_url( '/#signup' ) ); ?>" data-rights-updates="<?php echo esc_attr( $wt_region->slug ); ?>">Get release updates</a></p>
		</section>
	<?php else : ?>
		<?php if ( $wt_cards ) : ?>
			<section class="wt-rights__section" aria-labelledby="findings">
				<h2 id="findings">From the reports</h2>
				<div class="wt-rights__cards">
					<?php foreach ( $wt_cards as [ $wt_post, $wt_card ] ) : ?>
						<article class="wt-rights__card">
							<p class="wt-rights__kicker"><a href="<?php echo esc_url( get_permalink( $wt_post ) ); ?>"><?php echo esc_html( get_the_title( $wt_post ) ); ?></a></p>
							<h3><?php echo esc_html( (string) $wt_card['title'] ); ?></h3>
							<div class="wt_prose"><?php echo wp_kses_post( (string) $wt_card['html'] ); ?></div>
						</article>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $wt_reports ) : ?>
			<section class="wt-rights__section" aria-labelledby="comparison">
				<h2 id="comparison">What the assessments found</h2>
				<div class="table-scroll">
					<table class="wt-rights__tally">
						<caption><?php echo esc_html( sprintf( 'Answers across %d country reports', count( $wt_reports ) ) ); ?></caption>
						<thead><tr><th scope="col">Indicator</th><th scope="col">Yes</th><th scope="col">No</th><th scope="col">Unclear</th><th scope="col">Not applicable</th></tr></thead>
						<tbody>
							<?php foreach ( $wt_structure['indicators'] as $wt_item ) : ?>
								<tr>
									<th scope="row"><?php echo esc_html( $wt_item['label'] ); ?></th>
									<?php foreach ( array( 'yes', 'no', 'unclear', 'not applicable' ) as $wt_answer ) : ?>
										<td><?php echo esc_html( (string) ( $wt_tally[ $wt_item['key'] ][ $wt_answer ] ?? 0 ) ); ?></td>
									<?php endforeach; ?>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</section>
		<?php endif; ?>

		<section class="wt-rights__section" aria-labelledby="countries">
			<h2 id="countries">Country reports</h2>
			<ul class="wt-rights__countries">
				<?php foreach ( $wt_reports as $wt_post ) : ?>
					<li><a href="<?php echo esc_url( get_permalink( $wt_post ) ); ?>"><?php echo esc_html( get_the_title( $wt_post ) ); ?></a></li>
				<?php endforeach; ?>
				<?php foreach ( $wt_forthcoming as $wt_country ) : ?>
					<li class="wt-rights__forthcoming"><?php echo esc_html( (string) $wt_country['name'] ); ?> <span>Report forthcoming</span></li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>

	<?php echo wt_rights_donate_band( 'rights-region' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the function. ?>
</main>
<?php
get_footer();
