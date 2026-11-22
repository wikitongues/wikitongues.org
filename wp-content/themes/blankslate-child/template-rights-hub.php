<?php
/**
 * The hub of the Report on the Global State of Language Rights, at
 * /language-rights/report/. Routed by the wt-rights plugin, not a page.
 *
 * Global totals count released regions only, so no unreleased finding shows.
 */
get_header();

$wt_structure = wt_rights_structure();
$wt_regions   = wt_rights_regions();
$wt_released  = wt_rights_released_reports();
$wt_tally     = wt_rights_tally( $wt_released );
?>
<main class="wt-rights wt-rights--hub">
	<header class="wt-rights__hero">
		<p class="wt-rights__kicker"><a href="<?php echo esc_url( home_url( '/language-rights/' ) ); ?>">Language rights</a></p>
		<h1><?php echo esc_html( wt_rights_report_title() ); ?></h1>
	</header>

	<section class="wt-rights__section" aria-labelledby="releases">
		<h2 id="releases">Regional releases</h2>
		<ol class="wt-rights__releases">
			<?php foreach ( $wt_regions as $wt_region ) : ?>
				<?php
				$wt_status  = wt_rights_region_status( $wt_region->term_id );
				$wt_nations = count( wt_rights_region_names( $wt_region->term_id ) );
				?>
				<li class="wt-rights__release-item wt-rights__release-item--<?php echo esc_attr( $wt_status ); ?>">
					<a href="<?php echo esc_url( get_term_link( $wt_region ) ); ?>"><?php echo esc_html( $wt_region->name ); ?></a>
					<span><?php echo esc_html( wt_rights_release_line( $wt_region->term_id ) ); ?> · <?php echo esc_html( $wt_nations . ( 1 === $wt_nations ? ' nation' : ' nations' ) ); ?></span>
				</li>
			<?php endforeach; ?>
		</ol>
	</section>

	<?php if ( $wt_released ) : ?>
		<section class="wt-rights__section" aria-labelledby="at-a-glance">
			<h2 id="at-a-glance">Findings so far</h2>
			<p><?php echo esc_html( sprintf( 'Across %d released country reports.', count( $wt_released ) ) ); ?></p>
			<ul class="wt-rights__totals">
				<?php foreach ( $wt_structure['indicators'] as $wt_item ) : ?>
					<li><strong><?php echo esc_html( (string) ( $wt_tally[ $wt_item['key'] ]['yes'] ?? 0 ) ); ?></strong> <?php echo esc_html( $wt_item['label'] . ': yes' ); ?></li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>

	<?php echo wt_rights_donate_band( 'rights-hub' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the function. ?>
</main>
<?php
get_footer();
