<article class="wt_team-member--wide">
	<!-- team member image -->
	<aside class="wt_team-member--wide__img" role="img" aria-label="<?php echo $profile_picture['alt']; ?>" style="background-image:url(<?php echo $profile_picture['url']; ?>);"></aside>

	<!-- team member meta -->
	<aside class="wt_team-member--wide__meta">
		<!-- team member name and title -->
		<strong>
			<span><?php echo $name; ?></span><br/>
			<span><?php echo $title; ?></span>
		</strong>

		<!-- personal languages: think about clarifying fluency level? -->
		<ul>
			<li>Languages:</li>
			<?php foreach( $personal_languages as $post ): setup_postdata( $post ); ?>
				<li>
					<?php the_field('standard_name'); ?>
				</li>
			<?php endforeach; wp_reset_postdata(); ?>
		</ul>

		<!-- contact information -->
		<ul>
		<?php if ( $website ): ?>
			<li>
				<a href="<?php echo $website; ?>">
					<i class="fa-solid fa-link"></i>
				</a>
			</li>
		<?php endif; ?>

		<?php if ( $email ): ?>
			<li>
				<a href="mailto:<?php echo $email; ?>">
					<i class="fa-solid fa-envelope"></i>
				</a>
			</li>
		<?php endif; ?>

		<?php if ( $linkedin ): ?>
			<li>
				<a href="<?php echo $linkedin; ?>">
					<i class="fa-brands fa-linkedin-in"></i>
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
		</ul>

		<!-- team member bio -->
		<p>
			<?php echo $bio; ?>
		</p>
	</aside>
</article>