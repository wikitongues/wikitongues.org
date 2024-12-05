<?php
get_header();

// Fetch the global fields from the Careers list page
$list_page_id = get_page_by_path('careers')->ID;
$headers = get_field("headers", $list_page_id);

// Helper function to render a section if a value exists
function render_section($field_name, $headers, $page_id = null) {
	$field_value = get_field($field_name, $page_id);
	$title_value = $headers[$field_name];
	if ($field_value) {
		echo "<section class='" . esc_attr($field_name) . "'>";
		echo "<h2>" . esc_attr($title_value) . "</h2>";
		echo wpautop(wp_kses_post($field_value));
		echo "</section>";
	}
}

if (have_posts()) :
	while (have_posts()) : the_post();
		?>
		<div class="career-post">
			<a href="<?php echo home_url('/careers', 'relative'); ?>">Back to Careers</a>
			<h1 class="career-title"><?php the_title(); ?></h1>
			<section>
				<?php echo "<h2>" . $headers["posted_date"] . "</h2>";?>
				<time datetime="<?php echo get_the_date('c'); ?>" itemprop="datePublished"><?php echo get_the_date(); ?></time>
			</section>
			<?php
			render_section('location', $headers);
			render_section('deadline', $headers);
			render_section('why_wikitongues', $headers, $list_page_id);
			render_section('team_description', $headers);
			render_section('role_description', $headers);
			render_section('requirements', $headers);
			render_section('compensation', $headers);
			render_section('about_wikitongues', $headers, $list_page_id);
			render_section('dei', $headers, $list_page_id);
			?>

			<section class="career-application">
				<?php echo "<h2>" . $headers["application"] . "</h2>";?>
				<a href="<?php echo esc_url(get_field('application')); ?>" target="_blank">
					Apply Here
				</a>
			</section>
		</div>
		<?php
	endwhile;
endif;

include('modules/newsletter.php');
get_footer();
