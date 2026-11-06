<?php
/* Template name: Donate */

// All copy is edited in the page's "Template: Donate" fields; defaults live in includes/template/donate-content.php.
$donate_id = (int) get_the_ID();
$hero      = (array) wt_donate_field( 'donate_hero', $donate_id );
$gift      = (array) wt_donate_field( 'donate_gift', $donate_id );
$reach     = (array) wt_donate_field( 'donate_reach', $donate_id );
$where     = (array) wt_donate_field( 'donate_where', $donate_id );
$programs  = (array) wt_donate_field( 'donate_programs', $donate_id );
$now       = (array) wt_donate_field( 'donate_now', $donate_id );
$monthly   = (array) wt_donate_field( 'donate_monthly', $donate_id );
$faq_intro = (array) wt_donate_field( 'donate_faq_intro', $donate_id );
$faqs      = (array) wt_donate_field( 'donate_faq', $donate_id );
$closing   = (array) wt_donate_field( 'donate_close', $donate_id );

$campaign_id     = trim( (string) ( $gift['campaign_id'] ?? '' ) );
$checkout        = (string) ( $gift['checkout_link'] ?? '' );
$checkout_page   = (string) get_permalink();
$once_amounts    = wt_donate_amounts( (string) ( $gift['once_amounts'] ?? '' ), array( 50, 100, 250 ) );
$monthly_amounts = wt_donate_amounts( (string) ( $gift['monthly_amounts'] ?? '' ), array( 5, 10, 25 ) );

$stats            = wt_get_archive_stats();
$language_count   = number_format_i18n( (int) $stats['language_count'] );
$initiative_count = (int) wp_count_posts( 'fellows' )->publish;
$financials_url   = (string) get_post_type_archive_link( 'form_990' );

$once_url    = $campaign_id ? add_query_arg(
	array(
		'form'      => $campaign_id,
		'amount'    => $once_amounts[1],
		'currency'  => 'USD',
		'recurring' => 'once',
	),
	$checkout_page
) : $checkout;
$monthly_url = $campaign_id ? add_query_arg(
	array(
		'form'      => $campaign_id,
		'amount'    => $monthly_amounts[1],
		'currency'  => 'USD',
		'recurring' => 'monthly',
	),
	$checkout_page
) : $checkout;
wp_enqueue_script( 'wt-donate', get_stylesheet_directory_uri() . '/js/donate.js', array(), '1.1.0', true );

get_header();
?>

<main class="wt-donate">
	<section id="give" class="wt-donate__wrap wt-donate__hero" aria-labelledby="gift-headline">
		<div class="wt-donate__intro">
			<p class="wt-donate__eyebrow"><?php echo esc_html( $hero['eyebrow'] ?? '' ); ?></p>
			<h1 id="gift-headline"><?php echo wt_donate_marked( $hero['headline'] ?? '', 'wt-donate__highlight' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in wt_donate_marked(). ?></h1>
			<p><?php echo esc_html( $hero['intro'] ?? '' ); ?></p>
			<div class="wt-donate__trust">
				<a href="https://www.guidestar.org/profile/shared/8eef7d54-d184-4013-aad1-0e3ada544f64">
					<img src="https://widgets.guidestar.org/TransparencySeal/9488075" alt="Wikitongues’ Candid transparency seal" width="88" height="88">
				</a>
				<ul>
					<li><?php echo esc_html( $hero['trust_note'] ?? '' ); ?></li>
					<li><a href="<?php echo esc_url( $financials_url ); ?>"><?php echo esc_html( $hero['financials_label'] ?? '' ); ?></a></li>
				</ul>
			</div>
			<a class="wt-donate__jump" href="#where"><?php echo esc_html( $hero['jump_text'] ?? '' ); ?></a>
		</div>
		<?php require __DIR__ . '/modules/donate/gift-box.php'; ?>
	</section>

	<section class="wt-donate__reach" aria-label="Our reach">
		<ul class="wt-donate__wrap wt-donate__grid">
			<li><strong><?php echo esc_html( number_format_i18n( $initiative_count ) ); ?></strong> <?php echo esc_html( $reach['fellows_label'] ?? '' ); ?></li>
			<li><strong><?php echo esc_html( $language_count ); ?></strong> <?php echo esc_html( $reach['languages_label'] ?? '' ); ?></li>
			<li><strong><?php echo esc_html( number_format_i18n( (int) $stats['nations_count'] ) ); ?></strong> <?php echo esc_html( $reach['countries_label'] ?? '' ); ?></li>
		</ul>
	</section>

	<section id="where" class="wt-donate__wrap wt-donate__section" aria-labelledby="where-title">
		<div class="wt-donate__heading">
			<p class="wt-donate__eyebrow"><?php echo esc_html( $where['eyebrow'] ?? '' ); ?></p>
			<h2 id="where-title"><?php echo esc_html( $where['title'] ?? '' ); ?></h2>
			<p><?php echo esc_html( $where['intro'] ?? '' ); ?></p>
		</div>
		<?php if ( $programs ) : ?>
			<ol class="wt-donate__grid wt-donate__programs">
				<?php foreach ( array_values( $programs ) as $i => $program ) : ?>
					<li>
						<p class="wt-donate__eyebrow"><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></p>
						<h3><?php echo esc_html( $program['title'] ?? '' ); ?></h3>
						<p><?php echo esc_html( str_replace( '{languages}', $language_count, (string) ( $program['text'] ?? '' ) ) ); ?></p>
					</li>
				<?php endforeach; ?>
			</ol>
		<?php endif; ?>
	</section>

	<?php require __DIR__ . '/modules/donate/stories.php'; ?>

	<section class="wt-donate__rule" aria-label="Why give now">
		<div class="wt-donate__wrap wt-donate__section wt-donate__pair">
			<div>
				<p class="wt-donate__eyebrow"><?php echo esc_html( $now['eyebrow'] ?? '' ); ?></p>
				<h2><?php echo esc_html( $now['title'] ?? '' ); ?></h2>
				<p><?php echo esc_html( $now['text'] ?? '' ); ?></p>
				<a class="wt-donate__button" data-cta-location="donate-page-middle-single" href="<?php echo esc_url( $once_url ); ?>"><?php echo esc_html( $now['button_label'] ?? '' ); ?></a>
			</div>
			<div class="wt-donate__monthly">
				<p class="wt-donate__eyebrow"><?php echo esc_html( $monthly['eyebrow'] ?? '' ); ?></p>
				<h2><?php echo esc_html( $monthly['title'] ?? '' ); ?></h2>
				<p><?php echo esc_html( $monthly['text'] ?? '' ); ?></p>
				<a class="wt-donate__button" data-cta-location="donate-page-middle-recurring" href="<?php echo esc_url( $monthly_url ); ?>"><?php echo esc_html( $monthly['button_label'] ?? '' ); ?></a>
			</div>
		</div>
	</section>

	<?php if ( $faqs ) : ?>
		<section class="wt-donate__rule" aria-labelledby="gift-faq">
			<div class="wt-donate__wrap wt-donate__section wt-donate__faq">
				<div class="wt-donate__heading">
					<p class="wt-donate__eyebrow"><?php echo esc_html( $faq_intro['eyebrow'] ?? '' ); ?></p>
					<h2 id="gift-faq"><?php echo esc_html( $faq_intro['title'] ?? '' ); ?></h2>
				</div>
				<?php foreach ( array_values( $faqs ) as $i => $faq ) : ?>
					<details<?php echo 0 === $i ? ' open' : ''; ?>>
						<summary><?php echo esc_html( $faq['question'] ?? '' ); ?></summary>
						<?php echo wp_kses_post( $faq['answer'] ?? '' ); ?>
					</details>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

	<section class="wt-donate__rule" aria-label="Give today">
		<div class="wt-donate__wrap wt-donate__section wt-donate__close">
			<h2><?php echo wt_donate_marked( $closing['title'] ?? '', 'wt-donate__highlight' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in wt_donate_marked(). ?></h2>
			<p><?php echo esc_html( $closing['text'] ?? '' ); ?></p>
			<div class="wt-donate__actions">
				<a class="wt-donate__button" href="#give" data-cta-location="donate-page-bottom"><?php echo esc_html( $closing['button_label'] ?? '' ); ?></a>
				<button type="button" class="wt-donate__button wt-donate__button--outline" data-donate-share hidden><?php echo esc_html( $closing['share_label'] ?? '' ); ?></button>
			</div>
			<p role="status" data-donate-share-status></p>
		</div>
	</section>
</main>

<?php
require __DIR__ . '/modules/newsletter.php';
get_footer();
