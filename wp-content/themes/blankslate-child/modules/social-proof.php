<?php
	function renderPartners($type) {
		$args = array(
			'post_type' => 'partners',
			'tax_query' => array(
        array(
            'taxonomy' => 'category',
            'field' => 'slug',
            'terms' => $type,
        ),
			),
		);
		$query = get_custom_gallery_query($args);
		$output = '';
		$contents = '';

		while ($query->have_posts()) {
			$query->the_post();
			$partner_name = get_the_title();
			$partner_logo = get_field('partner_logo');
			$partner_link = get_field('partner_website');

			if ($partner_logo) {
				if (is_array($partner_logo)) {
					$partner_logo = $partner_logo['url'];
				} else {
					$partner_logo = $partner_logo;
				}
			}

			$contents .= '<li><a href="'. $partner_link .'"><img src="'.$partner_logo.'" title="'.$partner_name.'" alt="'.$partner_name.'"></a></li>';
		}

		if ($query->have_posts()) {
			$output .= '<section>';
			// $output .= '<h2>'.ucfirst($type).'</h2>';
			$output .= '<ul>'.$contents.'</ul>';
			$output .= '</section>';
		}
		echo $output;
	}
?>


<div id="wt_socialproof">

	<h2>Funders and partners</h2>
	<p>Our work would not be possible without the support of our core partners</p>
	<?php renderPartners('Funder');?>
	<?php renderPartners('Partner');?>
</div>