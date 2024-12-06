<article class="wt_team-member--wide">
	<aside class="wt_team-member--wide__img" role="img" aria-label="<?php echo $profile_picture['alt']; ?>" style="background-image:url(<?php echo $profile_picture['url']; ?>);"></aside>

	<aside class="wt_team-member--wide__meta">
		<h2><?php echo $name; ?></h2>
		<strong><?php echo $title; ?></strong>
		<?php
		$first_name = explode(' ', trim($name))[0];
		echo '<h3>'.$first_name . ' speaks</h3>';
		?>

		<ul>
			<?php foreach( $personal_languages as $index => $post ): setup_postdata( $post ); ?>
				<li>
					<a href="<?php the_permalink(); ?>"><?php the_field('standard_name'); ?></a><?php if ($index < count($personal_languages) - 1) echo ',';?>
				</li>
			<?php endforeach; wp_reset_postdata(); ?>
		</ul>

		<ul class="social">
				<?php foreach ($social_links as $platform => $data): ?>
						<?php if ($data['url']): ?>
								<li>
										<a href="<?php echo esc_url($data['url']); ?>">
												<i class="<?php echo esc_attr($data['icon']); ?>"></i>
										</a>
								</li>
						<?php endif; ?>
				<?php endforeach; ?>
		</ul>

		<p>
			<?php echo $bio; ?>
		</p>
	</aside>
</article>