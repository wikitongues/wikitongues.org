<?php
/**
 * A country report from the Report on the Global State of Language Rights.
 * Content comes from the release (`_rights_report`); see docs/language-rights.md.
 */
get_header();

$wt_post      = get_post();
$wt_report    = wt_rights_report( $wt_post->ID );
$wt_content   = (array) ( $wt_report['content'] ?? array() );
$wt_answers   = (array) ( $wt_report['answers'] ?? array() );
$wt_rating    = (array) ( $wt_content['rating'] ?? array() );
$wt_region    = wt_rights_report_region( $wt_post->ID );
$wt_structure = wt_rights_structure();
$wt_changelog = (array) ( $wt_report['changelog'] ?? array() );
$wt_territory = wt_rights_report_territory( $wt_report );
$wt_card      = $wt_content['story_card'] ?? null;
$wt_next      = wt_rights_next_report( $wt_post );
$wt_previous  = wt_rights_previous_report( $wt_post );
$wt_map       = wt_rights_report_map( $wt_report, get_the_title() );

$wt_labels = array();
foreach ( array_merge( $wt_structure['indicators'], $wt_structure['ratings'] ) as $wt_item ) {
	$wt_labels[ $wt_item['key'] ] = $wt_item;
}
// Section headings as readers see them; the rating section reads as an assessment.
$wt_headings = array(
	'rating' => 'Overall assessment',
);
$wt_toc      = array();
foreach ( $wt_structure['sections'] as $wt_section ) {
	if ( 'testimonies' === $wt_section['key'] && '' === trim( (string) ( $wt_content['sections']['testimonies']['html'] ?? '' ) ) ) {
		continue;
	}
	$wt_toc[ $wt_section['key'] ] = $wt_headings[ $wt_section['key'] ] ?? $wt_section['heading'];
}
?>
<main class="wt-rights wt-rights--report" data-rights-region="<?php echo esc_attr( $wt_region ? $wt_region->slug : '' ); ?>" data-rights-country="<?php echo esc_attr( $wt_post->post_name ); ?>" data-report-version="<?php echo esc_attr( (string) ( $wt_report['version'] ?? '' ) ); ?>">
	<header class="wt-rights__hero wt-rights__hero--report">
		<div class="wt-rights__wrap wt-rights__hero-grid">
			<div>
				<?php if ( $wt_region ) : ?>
					<a class="wt-rights__back" href="<?php echo esc_url( get_term_link( $wt_region ) . '#countries' ); ?>">← <?php echo esc_html( $wt_region->name ); ?> country reports</a>
					<?php echo wt_rights_embargo_notice( $wt_region->term_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the function. ?>
				<?php endif; ?>
				<p class="wt-rights__kicker"><a href="<?php echo esc_url( home_url( '/' . WT_RIGHTS_BASE . '/' ) ); ?>"><?php echo esc_html( wt_rights_report_title() ); ?></a> · Country profile</p>
				<h1><?php echo esc_html( get_the_title() ); ?></h1>
				<?php if ( $wt_territory ) : ?>
					<p class="wt-rights__territory"><a href="<?php echo esc_url( get_permalink( $wt_territory ) ); ?>">Languages of <?php echo esc_html( wt_prefix_the( get_the_title( $wt_territory ) ) ); ?> in the Wikitongues archive →</a></p>
				<?php endif; ?>
			</div>
			<?php if ( '' !== $wt_map ) : ?>
				<div class="wt-rights__locator">
					<?php echo $wt_map; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wt-maps escapes its SVG. ?>
					<p class="wt-rights__locator-caption">Map data from Natural Earth.</p>
				</div>
			<?php endif; ?>
		</div>
	</header>

	<?php get_template_part( 'modules/rights/jumpnav', null, array( 'links' => $wt_toc ) ); ?>

	<div class="wt-rights__wrap">
		<section class="wt-rights__glance" id="at-a-glance" aria-labelledby="at-a-glance-heading">
			<div class="wt-rights__glance-layout">
				<div>
					<h2 id="at-a-glance-heading">At a glance</h2>
					<p class="wt-rights__headline"><?php echo esc_html( (string) ( $wt_rating['headline'] ?? '' ) ); ?></p>
					<dl class="wt-rights__stats">
						<div>
							<dt>Historical baseline</dt>
							<dd><?php echo esc_html( ucfirst( (string) ( $wt_answers['ante'] ?? '' ) ) ); ?></dd>
						</div>
						<div>
							<dt>Current direction</dt>
							<dd><?php echo wt_rights_trend_badge( (string) ( $wt_answers['trend'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the function. ?></dd>
						</div>
					</dl>
				</div>
				<div class="wt-rights__glance-answers">
					<h3>Indicator answers</h3>
					<ul>
						<?php foreach ( $wt_structure['indicators'] as $wt_item ) : ?>
							<li><a href="#<?php echo esc_attr( $wt_item['key'] ); ?>"><?php echo esc_html( $wt_item['label'] ); ?></a> <?php echo wt_rights_answer_pill( (string) ( $wt_answers[ $wt_item['key'] ] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the function. ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
			<div class="wt-rights__glance-more">
				<details>
					<summary>About the figures</summary>
					<p>The historical baseline reflects conditions before the current government or regime. The current direction describes change under the current government or regime.</p>
				</details>
				<div class="wt-rights__glance-actions">
					<?php if ( ! empty( $wt_report['version'] ) ) : ?>
						<p class="wt-rights__version">
							Version <?php echo esc_html( (string) $wt_report['version'] ); ?>
							<?php if ( $wt_changelog ) : ?>
								· updated <?php echo esc_html( wp_date( 'F j, Y', (int) strtotime( $wt_changelog[0]['date'] . ' 12:00:00' ) ) ); ?> · <a href="#changes">Changes</a>
							<?php endif; ?>
						</p>
					<?php endif; ?>
					<?php echo wt_rights_pdf_link( $wt_post->ID, 'rights-report' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- gateway shortcode output. ?>
					<?php echo wt_rights_donate_link_compact( 'rights-report-sidebar' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the function. ?>
				</div>
			</div>
		</section>
	</div>

	<div class="wt-rights__wrap wt-rights__body">
		<?php foreach ( $wt_structure['sections'] as $wt_section ) : ?>
			<?php
			$wt_key   = $wt_section['key'];
			$wt_block = array();
			if ( 'prose' === $wt_section['type'] ) {
				$wt_block = (array) ( $wt_content['sections'][ $wt_key ] ?? array() );
				if ( '' === trim( (string) ( $wt_block['html'] ?? '' ) ) ) {
					continue;
				}
			}
			?>
			<section id="<?php echo esc_attr( $wt_key ); ?>" class="wt-rights__section">
				<h2><?php echo esc_html( $wt_headings[ $wt_key ] ?? $wt_section['heading'] ); ?></h2>

				<?php if ( 'dimensions' === $wt_section['type'] ) : ?>
					<?php foreach ( $wt_structure['indicators'] as $wt_item ) : ?>
						<?php $wt_dimension = (array) ( $wt_content['dimensions'][ $wt_item['key'] ] ?? array() ); ?>
						<div class="wt-rights__dimension" id="<?php echo esc_attr( $wt_item['key'] ); ?>">
							<h3><?php echo esc_html( $wt_item['question'] ); ?></h3>
							<p class="wt-rights__dimension-answer"><strong>Answer</strong> <?php echo wt_rights_answer_pill( (string) ( $wt_answers[ $wt_item['key'] ] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the function. ?></p>
							<div class="wt_prose"><?php echo wp_kses_post( wt_rights_link_languages( (string) ( $wt_dimension['html'] ?? '' ) ) ); ?></div>
							<?php echo wt_rights_sources( (array) ( $wt_dimension['sources'] ?? array() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the function. ?>
						</div>
					<?php endforeach; ?>

				<?php elseif ( 'rating' === $wt_section['type'] ) : ?>
					<div class="wt_prose">
						<p><strong><?php echo esc_html( (string) ( $wt_rating['headline'] ?? '' ) ); ?></strong></p>
						<?php echo wp_kses_post( wt_rights_link_languages( (string) ( $wt_rating['html'] ?? '' ) ) ); ?>
					</div>
					<?php echo wt_rights_sources( (array) ( $wt_rating['sources'] ?? array() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the function. ?>

				<?php else : ?>
					<div class="wt_prose"><?php echo wp_kses_post( wt_rights_link_languages( (string) $wt_block['html'] ) ); ?></div>
					<?php echo wt_rights_sources( (array) ( $wt_block['sources'] ?? array() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the function. ?>
				<?php endif; ?>
			</section>
			<?php if ( 'rating' === $wt_section['type'] ) : ?>
				<?php echo wt_rights_donate_band( 'rights-report' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the function. ?>
			<?php endif; ?>
		<?php endforeach; ?>

		<?php if ( is_array( $wt_card ) ) : ?>
			<aside class="wt-rights__card wt-rights__story">
				<p class="wt-rights__kicker">From the report</p>
				<h3><?php echo esc_html( (string) $wt_card['title'] ); ?></h3>
				<div class="wt_prose"><?php echo wp_kses_post( (string) $wt_card['html'] ); ?></div>
				<?php echo wt_rights_links_line( (array) ( $wt_card['links'] ?? array() ), 'wt-rights__links wt-rights__links--small wt-rights__card-links' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the function. ?>
			</aside>
		<?php endif; ?>

		<?php if ( $wt_changelog ) : ?>
			<section id="changes" class="wt-rights__section wt-rights__changes">
				<h2>Changes</h2>
				<ol>
					<?php foreach ( $wt_changelog as $wt_entry ) : ?>
						<li>
							<strong>Version <?php echo esc_html( (string) $wt_entry['version'] ); ?></strong>, <?php echo esc_html( wp_date( 'F j, Y', (int) strtotime( $wt_entry['date'] . ' 12:00:00' ) ) ); ?>.
							<?php echo esc_html( $wt_entry['notes'] ? implode( ' ', $wt_entry['notes'] ) : 'First published.' ); ?>
							<?php if ( ! empty( $wt_entry['changed_answers'] ) ) : ?>
								Answers changed: <?php echo esc_html( implode( ', ', array_map( static fn( string $k ): string => $wt_labels[ $k ]['label'] ?? $k, $wt_entry['changed_answers'] ) ) ); ?>.
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ol>
			</section>
		<?php endif; ?>
	</div>

	<nav class="wt-rights__pager" aria-label="Regional report navigation">
		<?php if ( $wt_previous ) : ?>
			<a class="wt-rights__pager-prev" rel="prev" href="<?php echo esc_url( get_permalink( $wt_previous ) ); ?>"><span>Previous report</span> <strong>← <?php echo esc_html( get_the_title( $wt_previous ) ); ?></strong></a>
		<?php endif; ?>
		<?php if ( $wt_region ) : ?>
			<a class="wt-rights__pager-region" href="<?php echo esc_url( get_term_link( $wt_region ) . '#countries' ); ?>">All <?php echo esc_html( $wt_region->name ); ?> reports</a>
		<?php endif; ?>
		<?php if ( $wt_next ) : ?>
			<a class="wt-rights__pager-next" rel="next" href="<?php echo esc_url( get_permalink( $wt_next ) ); ?>"><span>Next report</span> <strong><?php echo esc_html( get_the_title( $wt_next ) ); ?> →</strong></a>
		<?php endif; ?>
	</nav>
</main>
<?php
get_footer();
