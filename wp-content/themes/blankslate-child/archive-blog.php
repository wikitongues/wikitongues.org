<?php get_header(); ?>
	<section class="blog-archive">
		<h1>Words on Speaking</h1>
		<h5>The Wikitongues Blog</h5>
		<?php if ( have_posts() ) : ?>
			<div class="blog-posts">
				<?php
				while ( have_posts() ) :
					the_post();
					?>
					<?php if ( has_post_thumbnail() ) : ?>
						<a href="<?php the_permalink(); ?>" class="blog-post" style="background-image: linear-gradient(#00000047, #000000b8), url('<?php echo get_the_post_thumbnail_url( get_the_ID(), 'full' ); ?>');">
							<h2><?php the_title(); ?></h2>
							<div class="excerpt"><?php the_excerpt(); ?></div>
							<p class="meta"><?php echo get_the_date(); ?></p>
						</a>
						<?php endif; ?>
				<?php endwhile; ?>
			</div>
			<div class="pagination">
				<?php the_posts_pagination(); ?>
			</div>
		<?php else : ?>
			<p>No blog posts found.</p>
		<?php endif; ?>
	</section>
<?php
	require 'modules/newsletter.php';
	get_footer(); ?>