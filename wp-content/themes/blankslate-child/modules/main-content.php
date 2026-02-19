<?php if ( have_posts() ) : ?>
	<main class="main-content">
	<?php
	while ( have_posts() ) {

		the_post();

		the_content();
	}
	?>
	</main>
<?php endif; ?>