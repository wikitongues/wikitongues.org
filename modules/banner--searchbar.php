<div class="wt_banner--searchbar">
	<?php get_search_form(); ?><!-- search form needs to search by fields (i.e., standard name) and only apply to language and videos posts at first. We can then add lexicons, misc., and lastly, language revitalization projects, but that's going to require more work and may be a 2024 reframing		 -->
<!-- 
	<form id="searchform" action="<?php bloginfo('home'); ?>/" method="get" class="wt_archive-languages__searchform">
					<input id="languages_search" maxlength="150" name="languages_search" size="20" type="text" value="" class="txt" placeholder="Search by language name or ISO code" />
					<input name="post_type" type="hidden" value="languages" />
					<input id="searchsubmit" class="btn" type="submit" value="Search" />
				</form> -->
</div>

<?php
	// languages query args
		$args = array(
			'post_type' => 'languages',
			'posts_per_page' => '10',
			'order' => 'ASC'
		);

		$languages_search = get_query_var('languages_search'); 

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
		}?>