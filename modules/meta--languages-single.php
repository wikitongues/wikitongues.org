<div class="wt_meta">
	<p>
		<strong>
			<?php the_field('standard_name'); ?> resources
		</strong>
	</p>
	<ul>
		<li>
			<a href="#wt_single-languages__videos">
				<strong>Videos (<?php echo $videos_count; ?>)</strong><br />
			</a>
			<a href="<?php bloginfo('url'); ?>/submit-a-video">
				<span>Submit a video</span>
			</a>
		</li>	
			<a href="#wt_single-languages__lexicons">
			<?php if ( $lexicon_count > 0 ): ?>
				<strong>Dictionaries, phrase books, and lexicons (<?php echo $lexicons_count; ?>)</strong><br />
			<?php else: ?>
				<strong>Dictionaries, phrase books, and lexicons (0)</strong><br />
			<?php endif; ?>
			</a>
			<a href="<?php bloginfo('url'); ?>/submit-a-lexicon">
				<span>Submit a lexicon</span>
			</a>
		</li>
	</ul>
</div>