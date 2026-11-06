<?php
/* Template name: Donate */

$checkout         = 'https://wikitongues.donorsupport.co/-/XTRAFEBU';
$campaign_id      = defined( 'WT_DONATE_CAMPAIGN_ID' ) ? WT_DONATE_CAMPAIGN_ID : '';
$checkout_page    = get_permalink();
$stats            = wt_get_archive_stats();
$initiative_count = (int) wp_count_posts( 'fellows' )->publish;
$financials_url   = get_post_type_archive_link( 'form_990' );

$once_url    = $campaign_id ? add_query_arg(
	array(
		'form'      => $campaign_id,
		'amount'    => '100',
		'currency'  => 'USD',
		'recurring' => 'once',
	),
	$checkout_page
) : $checkout;
$monthly_url = $campaign_id ? add_query_arg(
	array(
		'form'      => $campaign_id,
		'amount'    => '10',
		'currency'  => 'USD',
		'recurring' => 'monthly',
	),
	$checkout_page
) : $checkout;
wp_enqueue_script( 'wt-donate', get_stylesheet_directory_uri() . '/js/donate.js', array(), '1.0.0', true );

get_header();
?>

<main class="wt-donate">
	<section id="give" class="wt-donate__wrap wt-donate__hero" aria-labelledby="gift-headline">
		<div class="wt-donate__intro">
			<p class="wt-donate__eyebrow">Support Wikitongues</p>
			<h1 id="gift-headline">Back the people revitalizing <span class="wt-donate__highlight">their languages.</span></h1>
			<p>Language activists around the world are teaching classes, publishing dictionaries and bringing their languages online. Your gift helps fund their work.</p>
			<div class="wt-donate__trust">
				<a href="https://www.guidestar.org/profile/shared/8eef7d54-d184-4013-aad1-0e3ada544f64">
					<img src="https://widgets.guidestar.org/TransparencySeal/9488075" alt="Wikitongues’ Candid transparency seal" width="88" height="88">
				</a>
				<ul>
					<li>501(c)(3) nonprofit. Gifts are tax-deductible in the US.</li>
					<li><a href="<?php echo esc_url( $financials_url ); ?>">Our financials</a></li>
				</ul>
			</div>
			<a class="wt-donate__jump" href="#where">Scroll down to see what your gift makes possible, and exactly where it goes.</a>
		</div>
		<?php require __DIR__ . '/modules/donate/gift-box.php'; ?>
	</section>

	<section class="wt-donate__reach" aria-label="Our reach">
		<ul class="wt-donate__wrap wt-donate__grid">
			<li><strong><?php echo esc_html( number_format_i18n( $initiative_count ) ); ?></strong> published fellowship projects</li>
			<li><strong><?php echo esc_html( number_format_i18n( (int) $stats['language_count'] ) ); ?></strong> languages documented in our archive</li>
			<li><strong><?php echo esc_html( number_format_i18n( (int) $stats['nations_count'] ) ); ?></strong> countries and territories represented in our archive</li>
		</ul>
	</section>

	<section id="where" class="wt-donate__wrap wt-donate__section" aria-labelledby="where-title">
		<div class="wt-donate__heading">
			<p class="wt-donate__eyebrow">Gifts.</p>
			<h2 id="where-title">Where your gift goes</h2>
			<p>Your gift is unrestricted, so it goes where language activists need it most. It supports all of our work:</p>
		</div>
		<ol class="wt-donate__grid wt-donate__programs">
			<li>
				<p class="wt-donate__eyebrow">01</p>
				<h3>Language Revitalization Fellowship</h3>
				<p>Micro-grants, in-kind services and volunteer support for language activists running their own projects, from mother-tongue schools and dictionaries to apps and arts programs.</p>
			</li>
			<li>
				<p class="wt-donate__eyebrow">02</p>
				<h3>Language Archive</h3>
				<p>Videos and resources recorded by speakers in <?php echo esc_html( number_format_i18n( (int) $stats['language_count'] ) ); ?> languages, shared openly and preserved with partners including the U.S. Library of Congress, the Arctic World Archive in Svalbard, and Wikimedia Commons.</p>
			</li>
			<li>
				<p class="wt-donate__eyebrow">03</p>
				<h3>Toolkit and training</h3>
				<p>Free guides and training so anyone can start a revitalization project in their community, on their own terms.</p>
			</li>
		</ol>
		<div class="wt-donate__spending">
			<h3 class="wt-donate__eyebrow">How each dollar is spent</h3>
			<div class="wt-donate__bar" aria-hidden="true">
				<span>Programs 69%</span><span>Operations 16%</span><span>Fundraising 14%</span>
			</div>
			<ul class="wt-donate__legend">
				<li>Programs 69%</li>
				<li>Operations 16%</li>
				<li>Fundraising 14%</li>
			</ul>
			<p>From our 2024 Form 990. Total expenses were $248,205. Percentages are rounded. <a href="<?php echo esc_url( $financials_url ); ?>">See our financials and Form 990.</a></p>
		</div>
	</section>

	<?php require __DIR__ . '/modules/donate/stories.php'; ?>

	<section class="wt-donate__rule" aria-label="Why give now">
		<div class="wt-donate__wrap wt-donate__section wt-donate__pair">
			<div>
				<p class="wt-donate__eyebrow">Now.</p>
				<h2>More activists apply than we can fund</h2>
				<p>In 2027, we plan to launch a peer-to-peer fellowship model. Alongside our intensive fellowship, language activists will learn from one another through free training and mentoring, with rapid grants to put their plans into action. Your gift helps make that possible.</p>
				<a class="wt-donate__button" data-cta-location="donate-page-middle-single" href="<?php echo esc_url( $once_url ); ?>"><?php echo $campaign_id ? 'Donate $100' : 'Donate now'; ?></a>
			</div>
			<div class="wt-donate__monthly">
				<p class="wt-donate__eyebrow">Monthly giving</p>
				<h2>Become a monthly supporter</h2>
				<p>$10 a month is $120 a year. Monthly supporters give fellows steady support they can plan around.</p>
				<a class="wt-donate__button" data-cta-location="donate-page-middle-recurring" href="<?php echo esc_url( $monthly_url ); ?>"><?php echo $campaign_id ? 'Give $10 a month' : 'Choose monthly giving at checkout'; ?></a>
			</div>
		</div>
	</section>

	<section class="wt-donate__rule" aria-labelledby="gift-faq">
		<div class="wt-donate__wrap wt-donate__section wt-donate__faq">
			<div class="wt-donate__heading">
				<p class="wt-donate__eyebrow">Questions.</p>
				<h2 id="gift-faq">Giving to Wikitongues</h2>
			</div>
			<details open>
				<summary>Is my gift tax-deductible?</summary>
				<p>Yes, in the United States. Wikitongues is a 501(c)(3) nonprofit, EIN 47-1463955.</p>
			</details>
			<details>
				<summary>Can I give from outside the US?</summary>
				<p>For help with currency or giving from your country, email <a href="mailto:hello@wikitongues.org">hello@wikitongues.org</a>.</p>
			</details>
			<details>
				<summary>How else can I give?</summary>
				<p>To ask about other ways to give, email <a href="mailto:hello@wikitongues.org">hello@wikitongues.org</a>.</p>
			</details>
			<details>
				<summary>How do I change or cancel a monthly gift?</summary>
				<p>For help managing your monthly gift, email <a href="mailto:hello@wikitongues.org">hello@wikitongues.org</a>.</p>
			</details>
			<details>
				<summary>Can I give to one fellow or project?</summary>
				<p>Gifts on this page are unrestricted. To talk about a larger or directed gift, email <a href="mailto:hello@wikitongues.org">hello@wikitongues.org</a>.</p>
			</details>
		</div>
	</section>

	<section class="wt-donate__rule" aria-label="Give today">
		<div class="wt-donate__wrap wt-donate__section wt-donate__close">
			<h2>Every language <span class="wt-donate__highlight">in the world.</span></h2>
			<p>Join us in supporting the language activists doing this work. Will you give $100 today?</p>
			<div class="wt-donate__actions">
				<a class="wt-donate__button" href="#give" data-cta-location="donate-page-bottom">Donate now</a>
				<button type="button" class="wt-donate__button wt-donate__button--outline" data-donate-share hidden>Share this page</button>
			</div>
			<p role="status" data-donate-share-status></p>
		</div>
	</section>
</main>

<?php
require __DIR__ . '/modules/newsletter.php';
get_footer();
