<?php

/* Template name: Donate */

// header
get_header();

// banner
$page_banner       = get_field( 'banner' );
$impact_photo      = get_field( 'impact_photo' );
$fundraising_photo = get_field( 'fundraising_photo' );
$current_slug      = add_query_arg( array(), $wp->request );
$fundraising_link  = home_url( "{$current_slug}/?element=XESPGTCJ&form=FUNFLYUUBYT", 'relative' );

require 'modules/banners/banner--main.php';

// Stats — same source as /archive
$stats                 = wt_get_archive_stats();
$languages_documented  = (int) $stats['language_count'];
$nations_impacted      = (int) $stats['nations_count'];
$total_languages       = (int) $stats['total_languages'];
$total_territories     = (int) $stats['total_territories'];
$languages_revitalized = (int) wp_count_posts( 'fellows' )->publish;
$languages_pct         = ( $total_languages > 0 ) ? round( $languages_documented / $total_languages * 100 ) : 0;
$nations_pct           = ( $total_territories > 0 ) ? round( $nations_impacted / $total_territories * 100 ) : 0;
?>

<div class="content">
	<h4>Every language deserves to be heard.</h4>
	<div class="cta-group"><a class="cta" href="<?php echo $fundraising_link; ?>">Set up monthly giving</a><a href="<?php echo $fundraising_link; ?>">Donate Now</a></div>

	<ul class="stats">
		<li>
			<h1><?php echo number_format( $languages_revitalized ); ?> initiatives</h1>
			<p>active community revitalization projects</p>
		</li>
		<li>
			<h1><?php echo number_format( $languages_documented ); ?> languages</h1>
			<p>over <?php echo esc_html( (string) $languages_pct ); ?>% of every language in the world</p>
		</li>
		<li>
			<h1><?php echo number_format( $nations_impacted ); ?> nations</h1>
			<p>our work extends over <?php echo esc_html( (string) $nations_pct ); ?>% of the world</p>
		</li>
	</ul>
	<h4>What Our Impact Looks Like</h4>
	<p>Through our Language Revitalization Fellowship, we help language activists identify and implement their communities' linguistic needs, supplementing their work with micro-grants, in-kind services, and volunteer labor. Wikitongues fellows grow their languages with arts and culture programs, mother-tongue education, and technology.</p>

	<?php
		echo '<div class="image" style="background-image:url(' . esc_url( $impact_photo['url'] ) . ');" alt="' . get_the_title() . '"></div>';
	?>
	<h4>Case Studies</h4>
	<p>Here are some of the impactful projects developed by our fellows.</p>
	<br><br>
	<?php
	$custom_posts = get_field( 'custom_gallery' );
	if ( $custom_posts ) {
		$post_ids = implode( ',', wp_list_pluck( $custom_posts['custom_gallery_posts'], 'ID' ) );
	}
	// Gallery
	$params = wt_gallery_params(
		array(
			'title'          => get_sub_field( 'custom_gallery_title' ),
			'post_type'      => 'fellows',
			'custom_class'   => 'custom fundraiser',
			'show_total'     => 'false',
			'columns'        => 1,
			'posts_per_page' => 5,
			'orderby'        => 'rand',
			'pagination'     => 'false',
			'selected_posts' => esc_attr( $post_ids ),
			'exclude_self'   => 'true',
		)
	);
	echo create_gallery_instance( $params );
	?>
	<div class="donations-button-group">
		<h4>Make a Lasting Impact</h4>
		<div class="cta-group"><a class="cta" href="<?php echo $fundraising_link; ?>">Donate Now</a><a href="<?php echo $fundraising_link; ?>">Or set up automatic monthly donations</a></div>
		<?php
			echo '<div class="image" style="background-image:url(' . esc_url( $fundraising_photo['url'] ) . ');" alt="' . get_the_title() . '"></div>';
		?>
		<section class="secondary">
			<div class="option">
				<strong>Monthly Giving</strong>
				<p>Want to grow your impact? Become a monthly supporter and help us give stable support to language activists worldwide.</p>
				<a class="cta" href="<?php echo $fundraising_link; ?>">Schedule monthly donation</a>
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

require 'modules/newsletter.php';

get_footer();
