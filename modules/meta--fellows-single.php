<section class="wt_fellow__meta">
	<p>
		<strong><?php echo $fellow_name; ?> is a member of the Wikitongues Language Revitalization Fellowship's <?php echo $fellow_year; ?> cohort.</strong>
	</p>

	<!-- contact? ask permission -->
	
	<p>
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

		<?php if ( $youtube ): ?>
			<li>
				<a href="<?php echo $youtube; ?>">
					<i class="fa-brands fa-twitter"></i>/<i class="fa-brands fa-x-twitter"></i>
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
		</ul>
	</p>
</section>