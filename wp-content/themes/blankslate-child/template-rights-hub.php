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
$wt_front      = wt_rights_front_matter();
$wt_method     = (array) ( $wt_front['methodology'] ?? array() );
$wt_key        = (array) ( $wt_front['answer_key'] ?? array() );
$wt_letter     = $wt_front['letter'] ?? null;
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

	<?php get_template_part( 'modules/rights/schedule', null, array( 'id' => 'releases' ) ); ?>

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
