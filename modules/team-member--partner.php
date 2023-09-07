<article class="wt_team-member--partner">
	<!-- team member image -->
	<aside class="wt_team-member--partner__img" role="img" aria-label="<?php echo $partner_logo['alt']; ?>" style="background-image:url(<?php echo $partner_logo['url']; ?>);"></aside>
	
	<!-- team member meta -->
	<aside class="wt_team-member--partner__meta">
		<!-- team member name and title -->
		<strong>
			<span><?php echo $name; ?></span><br/>
		</strong>

		<!-- contact information -->
		<ul>
		<?php if ( $partner_website ): ?>
			<li>
				<a href="<?php echo $partner_website; ?>">
					<i class="fa-regular fa-link"></i>
				</a>
			</li>
		<?php endif; ?>

		<?php if ( $partner_email ): ?>
			<li>
				<a href="mailto:<?php echo $partner_email; ?>">
					<i class="fa-sharp fa-solid fa-envelope"></i>
				</a>
			</li>
		<?php endif; ?>
		</ul>

		<!-- team member bio -->
		<p>
			<?php echo $partner_bio; ?>
		</p>
	</aside>
</article>