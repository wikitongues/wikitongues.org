<?php
// FAQ Accordion: FAQ posts as an accordion, the first open. See donate-content.php.
$row  = wt_donate_row();
$faqs = (array) ( $row['faq_entries'] ?? array() );
if ( ! $faqs ) {
	return;
}
$faq_title_id = 'faq-' . wp_unique_id();
?>
<section class="wt-donate wt-donate__rule" aria-labelledby="<?php echo esc_attr( $faq_title_id ); ?>">
	<div class="wt-donate__wrap wt-donate__section wt-donate__faq">
		<div class="wt-donate__heading">
			<p class="wt-donate__label"><?php echo esc_html( $row['section_eyebrow'] ?? '' ); ?></p>
			<h2 id="<?php echo esc_attr( $faq_title_id ); ?>"><?php echo esc_html( $row['section_header'] ?? '' ); ?></h2>
		</div>
		<?php foreach ( array_values( $faqs ) as $i => $faq ) : ?>
			<?php
			if ( ! $faq instanceof WP_Post ) {
				continue;
			}
			?>
			<details<?php echo 0 === $i ? ' open' : ''; ?>>
				<summary><?php echo esc_html( get_the_title( $faq ) ); ?><span class="wt-donate__faq-toggle" aria-hidden="true">+</span></summary>
				<?php echo wp_kses_post( wpautop( $faq->post_content ) ); ?>
			</details>
		<?php endforeach; ?>
	</div>
</section>
