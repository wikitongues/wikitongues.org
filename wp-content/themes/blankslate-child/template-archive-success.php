<?php  /* Template name: Success */

get_header();

$page_banner = get_field('archive_success_banner');

include( 'modules/editorial-content.php' );

?>
<div class="success_cta">
	<h2>Have more to say?</h2>
		<section>
			<a href="<?php echo home_url('/archive/submit-a-video/', 'relative'); ?>">Submit another video here.</a>
		</section>
	</div>
<?php
include( 'modules/newsletter.php' );

get_footer();
