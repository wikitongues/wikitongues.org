<?php
// Rendered for every person a gallery returns, so nothing here can assume
// a populated profile picture or language list.
$personal_languages = (array) ( $personal_languages ?? array() );
?>
<article class="wt_person--wide">
	<aside class="wt_person--wide__img" role="img" aria-label="<?php echo esc_attr( $profile_picture['alt'] ?? '' ); ?>" style="background-image:url(<?php echo esc_url( $profile_picture['url'] ?? '' ); ?>);"></aside>

	<aside class="wt_person--wide__meta">
		<h4><?php echo $name; ?></h4>
		<strong><?php echo $title; ?></strong>
		<?php
		$first_name = explode( ' ', trim( $name ) )[0];
		echo '<p>' . $first_name . ' speaks</p>';
		?>

		<ul>
			<?php
			foreach ( $personal_languages as $index => $post ) :
				setup_postdata( $post );
				?>
				<li>
					<a href="<?php the_permalink(); ?>"><?php the_field( 'standard_name' ); ?></a>
					<?php
					if ( $index < count( $personal_languages ) - 1 ) {
						echo ',';}
					?>
				</li>
				<?php
			endforeach;
			wp_reset_postdata();
			?>
		</ul>

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

		<p>
			<?php echo $bio; ?>
		</p>
	</aside>
</article>