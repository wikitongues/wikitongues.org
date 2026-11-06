<?php
// Donation Hero: headline, trust signals and the gift box. See donate-content.php.
$row  = wt_donate_row();
$gift = (array) ( $row['gift_box'] ?? array() );

$campaign_id     = trim( (string) ( $gift['campaign_id'] ?? '' ) );
$checkout_page   = (string) get_permalink();
$once_amounts    = wt_donate_amounts( (string) ( $gift['once_amounts'] ?? '' ), array( 50, 100, 250 ) );
$monthly_amounts = wt_donate_amounts( (string) ( $gift['monthly_amounts'] ?? '' ), array( 5, 10, 25 ) );
wp_enqueue_script( 'wt-donate', get_stylesheet_directory_uri() . '/js/donate.js', array(), '1.2.0', true );
?>
<section id="give" class="wt-donate" aria-labelledby="gift-headline">
	<div class="wt-donate__wrap wt-donate__hero">
		<div class="wt-donate__intro">
			<p class="wt-donate__eyebrow"><?php echo esc_html( $row['section_eyebrow'] ?? '' ); ?></p>
			<h1 id="gift-headline"><?php echo wt_donate_marked( $row['section_header'] ?? '', 'wt-donate__highlight' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in wt_donate_marked(). ?></h1>
			<p><?php echo esc_html( $row['section_copy'] ?? '' ); ?></p>
			<div class="wt-donate__trust">
				<a href="https://www.guidestar.org/profile/shared/8eef7d54-d184-4013-aad1-0e3ada544f64">
					<img src="https://widgets.guidestar.org/TransparencySeal/9488075" alt="Wikitongues’ Candid transparency seal" width="88" height="88">
				</a>
				<ul>
					<li><?php echo esc_html( $row['trust_note'] ?? '' ); ?></li>
					<li><?php wt_donate_link( $row['financials_link'] ?? null ); ?></li>
				</ul>
			</div>
			<?php if ( ! empty( $row['jump_text'] ) ) : ?>
				<a class="wt-donate__jump" href="#where"><?php echo esc_html( $row['jump_text'] ); ?></a>
			<?php endif; ?>
		</div>
		<?php require locate_template( 'modules/donate/gift-box.php' ); ?>
	</div>
</section>
