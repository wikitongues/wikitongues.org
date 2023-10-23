<li>
	<a href="<?php echo $link_url; ?>">
		<span>
			<?php if ( $link_type === 'youtube' ): ?>
				<i class="fa-brands fa-youtube-square"></i>
			<?php elseif ( $link_type === 'facebook' ): ?>
				<i class="fa-brands fa-facebook-square"></i>
			<?php elseif ( $link_type === 'twitter' ): ?>
				<i class="fa-brands fa-twitter-square"></i>
			<?php elseif ( $link_type === 'linkedin' ): ?>
				<i class="fa-brands fa-linkedin"></i>
			<?php elseif ( $link_type === 'instagram' ): ?>
				<i class="fa-brands fa-instagram"></i>
			<?php elseif ( $link_type === 'github' ): ?>
				<i class="fa-brands fa-github-square"></i>
			<?php elseif ( $link_type === 'tiktok' ): ?>
				<i class="fa-brands fa-tiktok"></i>
			<?php else: ?>
				<i class="fa-solid fa-link"></i>
			<?php endif; ?>
		</span>
		<span>
			<?php echo $link_name; ?>
		</span>
	</a>
</li>