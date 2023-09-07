<article class="wt_team-member--wide">
	<!-- team member image -->
	<aside role="img" aria-label="<?php echo $profile_picture['alt']; ?>" style="background-image:url(<?php echo $profile_picture['url']; ?>);"></aside>
	
	<!-- team member meta -->
	<aside>
		<!-- team member name and title -->
		<strong>
			<span><?php echo $name; ?></span><br/>
			<span><?php echo $title; ?></span>
		</strong>

		<!-- team member bio -->
		<p>
			<?php echo $bio; ?>
		</p>

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
					<i class="fa-brands fa-linkedin-in"></i>
				</a>
			</li>
		<?php endif; ?>


		<?php if ( $website ): ?>
			<li>
				<a href="<?php echo $twitter; ?>">
					<i class="fa-brands fa-twitter"></i>/<i class="fa-brands fa-x-twitter"></i>
				</a>
			</li>
		<?php endif; ?>

		<?php if ( $email ): ?>
			<li>
				<a href="<?php echo $email; ?>">
					<i class="fa-sharp fa-solid fa-envelope"></i>
				</a>
			</li>
		<?php endif; ?>
		</ul>
	</aside>
</article>