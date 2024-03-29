<section class="wt_fellow__meta">
	<article>
		<strong><?php echo $fellow_name; ?> is a member of the Wikitongues Language Revitalization Fellowship's <?php echo $fellow_year; ?> cohort.</strong>
	</article>

	<!-- social media / contact - ask permission for an explicit contact section with email-->
	<?php if ( $website || $linkedin || $tiktok || $youtube || $instagram || $facebook || $twitter ): ?>
	<article class="wt_fellow__meta--social">
		<strong>Follow <?php echo $first_name; ?></strong><br/>
		<!-- contact information -->
		<ul>
		<?php if ( $website ): ?>
			<li>
				<a href="<?php echo $website; ?>">
					<i class="fa-regular fa-link"></i>
				</a>
			</li>
		<?php endif; ?>

		<?php if ( $linkedin ): ?>
			<li>
				<a href="<?php echo $linkedin; ?>">
					<i class="fa-brands fa-linkedin"></i>
				</a>
			</li>
		<?php endif; ?>

		<?php if ( $tiktok ): ?>
			<li>
				<a href="<?php echo $tiktok; ?>">
					<i class="fa-brands fa-tiktok"></i>
				</a>
			</li>
		<?php endif; ?>

		<?php if ( $youtube ): ?>
			<li>
				<a href="<?php echo $youtube; ?>">
					<i class="fa-brands fa-youtube"></i>
				</a>
			</li>
		<?php endif; ?>

		<?php if ( $instagram ): ?>
			<li>
				<a href="<?php echo $linkedin; ?>">
					<i class="fa-brands fa-instagram"></i>
				</a>
			</li>
		<?php endif; ?>

		<?php if ( $facebook ): ?>
			<li>
				<a href="<?php echo $facebook; ?>">
					<i class="fa-brands fa-square-facebook"></i>
				</a>
			</li>
		<?php endif; ?>

		<?php if ( $twitter ): ?>
			<li>
				<a href="<?php echo $twitter; ?>">
					<i class="fa-brands fa-twitter"></i>/<i class="fa-brands fa-x-twitter"></i>
				</a>
			</li>
		<?php endif; ?>
		<!-- "other" link field with custom slot for favicon? -->
		</ul>
	</article>
	<?php endif; ?>
	
	<!-- links other than the fellow's personal social media or website -->
	<?php if ( have_rows('custom_links') ): ?>
	<article class="wt_fellow__meta--links">
		<strong>Links</strong><br/>
		<ul>
		<?php while ( have_rows('custom_links') ): the_row(); ?>
			<li>
				<a href="<?php echo get_sub_field('link_url'); ?>">
					<?php echo get_sub_field('link_name'); ?>
				</a>
			</li>		
		<?php endwhile; ?>
		</ul>
	</article>
	<?php endif; ?>
</section>