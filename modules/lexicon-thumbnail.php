<?php
	$lexicon_title = get_the_title(); // should make a custom field
	$source_languages = get_field('source_languages');
	$target_languages = get_field('target_languages');
	$external_link = get_field('external_link');
	$dropbox_link = get_field('dropbox_link');
?>
<div class="wt_thumbnails__lexicon wt_masonry">
	<ul class="wt_thumbnails__lexicon--metadata">
		<li>
			<strong><?php echo $lexicon_title; ?></strong>
		</li>
		<li>
			<strong>Source Languages</strong>
			<?php
				foreach ( $source_languages as $post ) {
					setup_postdata( $post );

					echo '<span>' .
						 get_field('standard_name') .
						 '</span>';

				} wp_reset_postdata();
			?>
		</li>
		<li>
			<strong>Target Languages</strong>
			<?php
				foreach ( $target_languages as $post ) {
					setup_postdata( $post );

					echo '<span>' .
						 get_field('standard_name') .
						 '</span>';

				} wp_reset_postdata();
			?>
		</li>
		<li>
			<strong>Access</strong>
			<?php
				if ( $external_link ) {
					echo '<a href="' . $external_link . '">' .
						 'Living Dictionaries' .
						 '</a>';
				}

				if ( $dropbox_link ) {
					echo '<a href="' . $dropbox_link . '">' .
						 'Download' .
						 '</a>';
				}

				if ( !$external_link && !$dropbox_link ) {
					echo '<span>'.
						 'These materials are still being processed.'.
						 '</span>';
				}
			?>
		</li>
	</ul>
</div>