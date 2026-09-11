<?php
// /financials — ordering lives in wt_form_990_archive_query() (includes/taxonomies/form-990.php).
get_header();
?>

<main class="wt_financials">
	<h1>Form 990s</h1>
	<p class="wt_financials__intro">Wikitongues is a 501(c)(3) nonprofit. Our annual Form 990 filings with the IRS are below, most recent first.</p>

	<?php if ( have_posts() ) : ?>
		<ul class="wt_financials__filings">
			<?php
			while ( have_posts() ) :
				the_post();
				$filing = wt_form_990_file( get_the_ID() );
				if ( ! $filing ) {
					continue;
				}
				?>
				<li>
					<a href="<?php echo esc_url( $filing['url'] ); ?>"><?php the_title(); ?></a>
					<?php if ( $filing['size'] ) : ?>
						<span>PDF, <?php echo esc_html( $filing['size'] ); ?></span>
					<?php endif; ?>
				</li>
			<?php endwhile; ?>
		</ul>
	<?php else : ?>
		<p class="wt_financials__empty">No filings have been posted yet.</p>
	<?php endif; ?>
</main>

<?php
require 'modules/newsletter.php';
get_footer();
