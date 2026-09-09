<article class="wt_person--grid">
	<aside class="wt_person--grid__img" role="img" aria-label="<?php echo esc_attr( $profile_picture['alt'] ?? '' ); ?>" style="background-image:url(<?php echo esc_url( $profile_picture['url'] ?? '' ); ?>);"></aside>
	<aside class="wt_person--grid__meta">
		<strong><?php echo $name; ?></strong>
		<p><?php echo $title; ?></p>
		<ul class="social">
				<?php foreach ( $social_links as $platform => $data ) : ?>
						<?php if ( $data['url'] ) : ?>
								<li>
										<a href="<?php echo esc_url( $data['url'] ); ?>">
												<?php echo wt_icon( $data['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										</a>
								</li>
						<?php endif; ?>
				<?php endforeach; ?>
		</ul>
	</aside>
</article>