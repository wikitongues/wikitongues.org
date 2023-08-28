<!-- possible to sort results by language first, then video? -->
<main class="wt_search-results">
<?php if ( have_posts() ): ?>
	<header class="wt_search-results__title">
		<span>Showing results for '<?php echo get_search_query(); ?>'</span>
	</header>	
	<section class="wt_search-results__results">
	<?php while ( have_posts() ): the_post(); ?>
		<article class="wt_search-results__thumbnail">
			<?php include('search-results__thumbnail.php'); ?>
		</article>
	<?php endwhile; else: ?>
		<article>
			<span>No results for <?php echo get_search_query(); ?></span><!-- down the line: "did you mean?" or recommend something -->
		</article>
	</section>
<?php endif; ?>
</main>

