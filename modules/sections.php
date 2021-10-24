<section class="wt_section">
	<aside class="wt_section__image"
		   style="background-image:url(<?php echo $section_image['url']; ?>);"
		   role="img"
		   aria-label="<?php echo $section_image['alt']; ?>">
	</aside>
	<aside class="wt_section__text">
		<div class="wt_aligncenter">
			<h1>
				<?php echo $section_header; ?>
			</h1>
			<h2>
				<?php echo $section_copy; ?>
			</h2>
			<a href="<?php echo $section_call_to_action['cta_link']; ?>" 
			   class="wt_action__primary">
				<?php echo $section_call_to_action['cta_text']; ?>   	
			</a>
			<a href="<?php echo $section_secondary_action['cta_link']; ?>" 
			   class="wt_action__secondary">
				<?php echo $section_secondary_action['cta_text']; ?>   	
			</a>
		</div>
	</aside>
	<div class="clear"></div>
</section>