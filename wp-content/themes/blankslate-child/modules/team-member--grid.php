<article class="wt_team-member--grid">
	<!-- team member image -->
	<aside class="wt_team-member--grid__img" role="img" aria-label="<?php echo $profile_picture['alt']; ?>" style="background-image:url(<?php echo $profile_picture['url']; ?>);"></aside>

	<!-- team member meta -->
	<aside class="wt_team-member--grid__meta">
		<!-- team member name and title -->
		<strong>
			<span><?php echo $name; ?></span><br/>
			<span><?php echo $title; ?></span>
		</strong>

		<!-- contact information -->
		<ul>
		<?php if ( $website ): ?>
			<li>
				<a href="<?php echo $website; ?>">
					<i class="fa-regular fa-link"></i>
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


		<?php if ( $website ): ?>
			<li>
				<a href="<?php echo $twitter; ?>">
					<i class="fa-brands fa-x-twitter"></i>/<i class="fa-brands fa-x-twitter"></i>
				</a>
			</li>
		<?php endif; ?>
		</ul>
	</aside>
</article>