<!-- disable error reporting until sizeOf issue is resolved -->
<?php error_reporting(E_ERROR | E_PARSE); ?>

<div id="wt_search" class="wt_search wt_overlay<?php if (isset($_GET['languages_search'])): ?> wt_visible<?php endif; ?>">
	<button id="wt_closeoverlay">
		<i class="fad fa-times-circle"></i>
	</button>
	<div class="wt_search__form">
		<div class="wt_innerwrap">
			<div class="wt_aligncenter">
				<h2>Explore Languages</h2>
				<form id="searchform" action="<?php bloginfo('home'); ?>/" method="get" class="wt_archive-languages__searchform">
					<input id="languages_search" maxlength="150" name="languages_search" size="20" type="text" value="" class="txt" placeholder="Search by language name or ISO code" />
					<input name="post_type" type="hidden" value="languages" />
					<input id="searchsubmit" class="btn" type="submit" value="Search" />
				</form>
			</div>				
		</div>
	</div>	
	<div class="wt_search__results">
	<?php
		// languages query args
		$args = array(
			'post_type' => 'languages',
			'posts_per_page' => '10',
			'order' => 'ASC'
		);

		$languages_search = get_query_var('languages_search'); 

		if ($languages_search) {
			echo '<h2 class="wt_search__results--title">Showing results for \'' . $languages_search . '\'</h2>'; 

		} 

		if (!empty($languages_search)) {
			$args['meta_query'] = array(
				array(
					'key' => 'wt_id',
					'value' => $languages_search,
					'compare' => 'LIKE'
				),
				array(
					'key' => 'standard_name',
					'value' => $languages_search,
					'compare' => 'LIKE'
				),
				array(
					'key' => 'alternate_names',
					'value' => $languages_search,
					'compare' => 'LIKE'
				),
				'relation' => 'OR'
			);
		}

		// Get current page and append to custom query parameters array
		$args['paged'] = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;
		$languages = new WP_Query( $args );
		$temp_query = $wp_query;
		$wp_query   = NULL;
		$wp_query   = $languages;

		// Run languages loop
		if ( !empty( $languages_search ) ) {
		if ( $languages->have_posts() ) { ?>
		<ul class="wt_search__results--list">
			<?php
			while ( $languages->have_posts() ) { 
				$languages->the_post(); 

				$ISO_code = get_the_title();
				$language_name = get_field('standard_name');
				$language_url = get_the_permalink(); 
				$speakers_recorded = get_field('speakers_recorded');
				$lexicon_source = get_field('lexicon_source');
				// var_dump($speakers_recorded);
				$video_count = sizeOf($speakers_recorded);
				$lexicon_count = sizeOf($lexicon_source); ?>

			<li class="wt_search__results--item">
				<a href="<?php echo $language_url; ?>">
					<strong><?php echo $language_name; ?> [<?php echo $ISO_code; ?>]</strong>
					<span>
						<?php if ( $speakers_recorded ) {
							echo $video_count;	
						} else {
							echo '0';
						} ?> videos recorded
					</span><br>
					<span>
						<?php if ( $lexicon_source ) {
							echo $lexicon_count;
						} else {
							echo '0';
						} ?> lexicon documents
					</span>
				</a>
			</li>

			<?php		
				}
			} else { // No languages matching search query ?>
				<div class="wt_search__results--none">
					<p>No languages to show.</p>
					<strong>Are we missing something? Let us know.</strong>
					<p>hello@wikitongues.org</p>
				</div>
			<?php }
			wp_reset_postdata(); 
			?>
		</ul>
	<?php } ?>
	</div>
</div><!-- end wt_search -->