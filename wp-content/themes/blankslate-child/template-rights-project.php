<?php
/**
 * Template Name: Language Rights project
 *
 * The project page for the Report on the Global State of Language Rights, at
 * /language-rights/: the front door for first-time visitors (plan 2.2, launch
 * page). One action in the hero, the page's own content (why it matters, the
 * white paper), the donate band and the publication schedule. The methodology
 * lives on the hub.
 *
 * The page content is edited in WordPress; the deck, the announcement date,
 * the white paper link and the schedule come from the imported release.
 */
get_header();

$wt_front        = wt_rights_front_matter();
$wt_announcement = (array) ( $wt_front['announcement'] ?? array() );
$wt_announced_on = (string) ( $wt_announcement['release_date'] ?? '' );
$wt_paper_url    = (string) ( $wt_announcement['paper_url'] ?? '' );
$wt_has_schedule = (bool) wt_rights_regions();

$wt_toc = array( 'about' => 'About the project' );
if ( '' !== $wt_paper_url ) {
	$wt_toc['paper'] = 'White paper';
}
$wt_toc['support'] = 'Support the project';
if ( $wt_has_schedule ) {
	$wt_toc['releases'] = 'Release schedule';
}
?>
<main class="wt-rights wt-rights--project">
	<header class="wt-rights__hero">
		<div class="wt-rights__wrap">
			<p class="wt-rights__kicker"><?php echo esc_html( '' !== $wt_announced_on ? 'Project announcement · ' . wp_date( 'F j, Y', (int) strtotime( $wt_announced_on . ' 12:00:00' ) ) : 'Wikitongues · First edition' ); ?></p>
			<h1>The Global State of Language Rights</h1>
			<?php if ( ! empty( $wt_front['summary'] ) ) : ?>
				<p class="wt-rights__deck"><?php echo esc_html( (string) $wt_front['summary'] ); ?></p>
			<?php endif; ?>
			<p><?php echo wt_rights_updates_button( 'project' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the function. ?></p>
		</div>
	</header>

	<?php get_template_part( 'modules/rights/jumpnav', null, array( 'links' => $wt_toc ) ); ?>

	<section id="about" class="wt-rights__section">
		<div class="wt-rights__wrap">
			<div class="wt-rights__intro wt_prose">
				<?php
				while ( have_posts() ) {
					the_post();
					the_content();
				}
				?>
			</div>
			<?php if ( '' !== $wt_paper_url ) : ?>
				<p id="paper"><a class="wt-rights__hero-link" href="<?php echo esc_url( $wt_paper_url ); ?>" data-rights-paper="project">Read the white paper (PDF)</a></p>
			<?php endif; ?>
		</div>
	</section>

	<?php echo wt_rights_donate_band( 'rights-project', true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the function. ?>

	<?php
	get_template_part(
		'modules/rights/schedule',
		null,
		array(
			'id'      => 'releases',
			'heading' => 'Seven regional releases. One global report.',
			'intro'   => 'The project begins with its announcement and white paper, then rolls out across seven regions before the complete global synthesis. Planned dates may change.',
		)
	);
	?>
</main>
<?php
get_footer();
