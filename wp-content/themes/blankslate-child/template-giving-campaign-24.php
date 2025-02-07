<?php

/* Template name: Giving Campaign 24 */

// header
get_header();

// banner
$page_banner = get_field('banner');
$impact_photo = get_field('impact_photo');
$fundraising_photo = get_field('fundraising_photo');
$fundraising_link = home_url('/2024-fundraiser/?element=XESPGTCJ&form=FUNQMUDJDGQ', 'relative');

include( 'modules/banner--main.php' );
?>

<div class="content">
	<h4>Can you help us grow?</h4>
	<div class="cta-group"><a class="cta" href="<?php echo $fundraising_link?>">Donate Now</a><a href="<?php echo $fundraising_link?>">Or set up automatic monthly donations</a></div>

	<ul class="stats">
		<li>
			<h1>45</h1>
			<p>Languages Revitalized</p>
		</li>
		<li>
			<h1>838</h1>
			<p>Languages Documented</p>
		</li>
		<li>
			<h1>178</h1>
			<p>Nations Impacted</p>
		</li>
	</ul>
	<h4>What Our Impact Looks Like</h4>
	<p>Through our Language Revitalization Fellowship, we help language activists identify and implement their communities’ linguistic needs, supplementing their work with micro-grants, in-kind services, and volunteer labor. Wikitongues fellows grow their languages with arts and culture programs, mother-tongue education, and technology.</p>

	<?php
		echo '<div class="image" style="background-image:url('.esc_url($impact_photo['url']).');" alt="' . get_the_title() . '"></div>';
	?>
	<h4>Case Studies</h4>
	<p>Here are some of the impactful projects developed by our fellows.</p>
	<br><br>
	<?php
	$custom_posts = get_field('custom_gallery');
	if ($custom_posts) {
		$post_ids = implode(',', wp_list_pluck($custom_posts['custom_gallery_posts'], 'ID'));
	}
	// Gallery
	$params = [
		'title' => get_sub_field('custom_gallery_title'),
		'post_type' => 'fellows',
		'custom_class' => 'custom fundraiser',
		'columns' => 1,
		'posts_per_page' => 5,
		'orderby' => 'rand',
		'order' => 'asc',
		'pagination' => 'false',
		'meta_key' => '',
		'meta_value' => '',
		'selected_posts' => esc_attr($post_ids),
		'display_blank' => 'false',
		'taxonomy' => '',
		'term' => '',
	];
	echo create_gallery_instance($params);
	?>
	<div class="donations-button-group">
		<h4>Make a Lasting Impact</h4>
		<div class="cta-group"><a class="cta" href="<?php echo $fundraising_link?>">Donate Now</a><a href="<?php echo $fundraising_link?>">Or set up automatic monthly donations</a></div>
		<?php
			echo '<div class="image" style="background-image:url('.esc_url($fundraising_photo['url']).');" alt="' . get_the_title() . '"></div>';
		?>
		<section class="secondary">
			<div class="option">
				<strong>Monthly Giving</strong>
				<p>Want to grow your impact impact? Become a monthly supporter and help us give stable support to language activists worldwide.</p>
				<a class="cta" href="<?php echo $fundraising_link?>">Schedule monthly donation</a>
			</div>
			<div class="option">
				<strong>Share Our Campaign</strong>
				<p>Spread the word! Share this campaign with friends and family and bring more voices into the language diversity movement.</p>
				<button>Share</button>
			</div>
		</section>
		<p>Wikitongues is a 501(c)(3) non-profit organization.<br>Donations are tax-deductible in the United States and other applicable jurisdictions.</p>
	</div>
</div> <!-- Closes Content -->

<?php

include( 'modules/newsletter.php' );

get_footer();