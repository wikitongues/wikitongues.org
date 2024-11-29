<?php
get_header();

// Fetch the global fields from the Careers list page
$list_page_id = get_page_by_path('careers')->ID; // Dynamically get the ID
// Alternatively: $list_page_id = 123; // Static ID

$why_wikitongues = get_field('why_wikitongues', $list_page_id);
$about_wikitongues = get_field('about_wikitongues', $list_page_id);
$dei = get_field('dei', $list_page_id);

// Start the WordPress Loop
if (have_posts()) :
	while (have_posts()) : the_post();
		?>
		<div class="career-post">
			<!-- Post-Specific Fields -->
			<a href="<?php echo home_url('/careers', 'relative'); ?>">Back to Careers</a>
			<h1 class="career-title"><?php the_title(); ?></h1>

			<div class="career-location">
				<h2>Location</h2>
				<p><?php echo esc_html(get_field('location')); ?></p>
			</div>
			<?php if ($why_wikitongues): ?>
				<div class="why-wikitongues">
					<h2>Why Wikitongues</h2>
					<?php echo wpautop(wp_kses_post(get_field('why_wikitongues', $list_page_id))); ?>
				</div>
			<?php endif; ?>
			<div class="career-team-description">
				<h2>About this team and role</h2>
				<?php echo wpautop(wp_kses_post(get_field('team_description'))); ?>
			</div>

			<div class="career-role-description">
				<h2>What you’ll do</h2>
				<?php echo wpautop(wp_kses_post(get_field('role_description'))); ?>
			</div>

			<div class="career-candidate-background">
				<h2>What you’ll bring</h2>
				<?php echo wpautop(wp_kses_post(get_field('candidate_background'))); ?>
			</div>

			<div class="career-compensation">
				<h2>What you’ll get</h2>
				<?php echo wpautop(wp_kses_post(get_field('compensation'))); ?>
			</div>
			<?php if ($about_wikitongues): ?>
				<div class="about-wikitongues">
					<h2>About Wikitongues</h2>
					<?php echo wpautop(wp_kses_post(get_field('about_wikitongues', $list_page_id))); ?>
				</div>
			<?php endif; ?>

			<?php if ($dei): ?>
				<div class="dei">
					<h2>Our commitment to diversity, equity, inclusion, and belonging</h2>
					<?php echo wpautop(wp_kses_post(get_field('dei', $list_page_id))); ?>
				</div>
			<?php endif; ?>
			<div class="career-application">
				<h2>Application</h2>
				<a href="<?php echo esc_url(get_field('application')); ?>" target="_blank">
					Apply Here
				</a>
			</div>
		</div>
		<?php
	endwhile;
endif;

get_footer();
