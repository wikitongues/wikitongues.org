<li class="gallery-item">
  <?php
    $source_languages = array(get_field('source_languages'));
		$target_languages = get_field('target_languages');
    $dropbox_link = get_field('dropbox_link');
    $external_link = get_field('external_link');
		$url = '';
		if ($dropbox_link) {
			$url = $dropbox_link;
		} elseif ($external_link) {
			$url = $external_link;
		}
;
		echo "<a href={$url}>";

		echo '<section><p>Lexicon</p>';

		foreach ($source_languages as $language_post_id) {
			echo get_language_gallery_html($language_post_id);
		}
		echo '</section>';

		echo '<section><p>to</p><span class="target-languages">';
		foreach ($target_languages as $language_post_id) {
			echo get_language_gallery_html($language_post_id);
		}
		echo '</span></section></a>';

  ?>
</li>
