<?php

/* Template name: Giving Campaign 24 */

// header
get_header();

// banner
$page_banner = get_field('banner');
$impact_photo = get_field('impact_photo');
$fundraising_photo = get_field('fundraising_photo');

include( 'modules/banner--main.php' );

?>
<div class="content">
<h1>Can you help us grow?</h1>
<div class="cta-group"><a class="cta" href="https://wikitongues.org/?element=XESPGTCJ&form=FUNQMUDJDGQ">Donate Now</a><a href="https://wikitongues.org/?element=XESPGTCJ&form=FUNQMUDJDGQ">Or set up automatic monthly donations</a></div>
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
<h1>What Our Impact Looks Like</h1>
<p>Through our Language Revitalization Fellowship, we help language activists identify and implement their communities’ linguistic needs, supplementing their work with micro-grants, in-kind services, and volunteer labor. Wikitongues fellows grow their languages with arts and culture programs, mother-tongue education, and technology.</p>

<?php
	echo '<div class="image" style="background-image:url('.esc_url($impact_photo['url']).');" alt="' . get_the_title() . '"></div>';
?>
<h1>Case Studies</h1>
<p>Here are some of the impactful projects developed by our fellows.</p>
<br><br>
<?php
$custom_posts = get_field('custom_gallery');
if ($custom_posts) {
	$post_ids = implode(',', wp_list_pluck($custom_posts['custom_gallery_posts'], 'ID'));
}
$custom_title = get_sub_field('custom_gallery_title');
$custom_post_type = 'fellows';
$custom_class = 'custom fundraiser';
$custom_columns = 1;
$custom_posts_per_page = 5;
$custom_orderby = 'rand';
$custom_order = 'asc';
$custom_pagination = 'false';
$custom_meta_key = '';
$custom_meta_value = '';
$custom_selected_posts = esc_attr($post_ids);
echo do_shortcode('[custom_gallery title="'.$custom_title.'" custom_class="'.$custom_class.'" post_type="'.$custom_post_type.'" columns="'.$custom_columns.'" posts_per_page="'.$custom_posts_per_page.'" orderby="'.$custom_orderby.'" order="'.$custom_order.'" pagination="'.$custom_pagination.'" meta_key="'.$custom_meta_key.'" meta_value="'.$custom_meta_value.'" selected_posts="'.$custom_selected_posts.'"]');
?>
<div class="donations-button-group">
	<h1>Make a Lasting Impact</h1>
	<div class="cta-group"><a class="cta" href="https://wikitongues.org/?element=XESPGTCJ&form=FUNQMUDJDGQ">Donate Now</a><a href="https://wikitongues.org/?element=XESPGTCJ&form=FUNQMUDJDGQ">Or set up automatic monthly donations</a></div>
	<?php
		echo '<div class="image" style="background-image:url('.esc_url($fundraising_photo['url']).');" alt="' . get_the_title() . '"></div>';
	?>
	<section class="secondary">
		<div class="option">
			<h2>Monthly Giving</h2>
			<p>Want to grow your impact impact? Become a monthly supporter and help us give stable support to language activists worldwide.</p>
			<a class="cta" href="https://wikitongues.org/?element=XESPGTCJ&form=FUNQMUDJDGQ">Schedule monthly donation</a>
		</div>
		<div class="option">
			<h2>Share Our Campaign</h2>
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