<?php
/**
 * Donate page content: default copy, ACF pre-fill and render helpers.
 *
 * Every piece of copy on template-donate.php is an ACF field (group
 * "Template: Donate"). Until an editor saves the page, each field loads the
 * default below, both on the page and in the admin form, so the admin opens
 * pre-filled and no data migration is needed. Once a field is saved, the
 * saved value wins, including an emptied repeater.
 */

/**
 * Default copy, keyed by the field's meta name. Repeater defaults are lists
 * of rows keyed by sub field name.
 *
 * @return array<string, mixed>
 */
function wt_donate_defaults() {
	$email = '<a href="mailto:hello@wikitongues.org">hello@wikitongues.org</a>';

	return array(
		'donate_hero_eyebrow'             => 'Support Wikitongues',
		'donate_hero_headline'            => 'Back the people revitalizing *their languages.*',
		'donate_hero_intro'               => 'Language activists around the world are teaching classes, publishing dictionaries and bringing their languages online. Your gift helps fund their work.',
		'donate_hero_trust_note'          => '501(c)(3) nonprofit. Gifts are tax-deductible in the US.',
		'donate_hero_financials_label'    => 'Our financials',
		'donate_hero_jump_text'           => 'Scroll down to see what your gift makes possible.',

		'donate_gift_title'               => 'Make a gift',
		'donate_gift_campaign_id'         => '',
		'donate_gift_checkout_link'       => 'https://wikitongues.donorsupport.co/-/XTRAFEBU',
		'donate_gift_once_amounts'        => '50, 100, 250',
		'donate_gift_monthly_amounts'     => '5, 10, 25',
		'donate_gift_note_once'           => 'Your gift supports all of Wikitongues’ work, including the fellowship.',
		'donate_gift_note_monthly'        => 'Monthly supporters give fellows steady support they can plan around.',
		'donate_gift_fallback_button'     => 'Continue to checkout',
		'donate_gift_fallback_note'       => 'Choose your amount and whether to give once or monthly at checkout.',
		'donate_gift_legal'               => 'Payments are completed at our secure checkout. Wikitongues is a 501(c)(3) nonprofit. Gifts are tax-deductible in the US.',

		'donate_reach_fellows_label'      => 'published fellowship projects',
		'donate_reach_languages_label'    => 'languages documented in our archive',
		'donate_reach_countries_label'    => 'countries and territories represented in our archive',

		'donate_where_eyebrow'            => 'Gifts.',
		'donate_where_title'              => 'Where your gift goes',
		'donate_where_intro'              => 'Your gift is unrestricted, so it goes where language activists need it most. It supports all of our work:',
		'donate_programs'                 => array(
			array(
				'title' => 'Language Revitalization Fellowship',
				'text'  => 'Micro-grants, in-kind services and volunteer support for language activists running their own projects, from mother-tongue schools and dictionaries to apps and arts programs.',
			),
			array(
				'title' => 'Language Archive',
				'text'  => 'Videos and resources recorded by speakers in {languages} languages, shared openly and preserved with partners including the U.S. Library of Congress, the Arctic World Archive in Svalbard, and Wikimedia Commons.',
			),
			array(
				'title' => 'Toolkit and training',
				'text'  => 'Free guides and training so anyone can start a revitalization project in their community, on their own terms.',
			),
		),

		'donate_stories_intro_eyebrow'    => 'Fellows.',
		'donate_stories_intro_title'      => 'What your gift makes possible',
		'donate_stories_intro_link_label' => 'Meet all our fellows',
		'donate_stories_intro_link_url'   => '/revitalization/fellows/',
		'donate_featured_art_text'        => 'Maayat’aan',
		'donate_featured_eyebrow'         => 'Fellow since 2022 · Mexico',
		'donate_featured_title'           => 'Science classes in Maayat’aan',
		'donate_featured_text'            => 'Hilario Poot Cahun is building a science curriculum grounded in Maayat’aan (Yucatec Maya) language and culture, with a focus on ecology. He has published *biology textbooks* in the language, tested the curriculum with teachers and parents at five Indigenous schools, and raised more funding to grow it.',
		'donate_stories'                  => array(
			array(
				'art_text' => 'Kihunde',
				'eyebrow'  => 'Kihunde · DR Congo · 2021',
				'name'     => 'Hangi Bulebe',
				'text'     => 'Founded a Kihunde research institute in Goma that runs children’s classes, trains new teachers, and has published a dictionary and an Android app.',
			),
			array(
				'art_text' => 'Chakma',
				'eyebrow'  => 'Chakma · Bangladesh · 2023',
				'name'     => 'Bivuti Chakma',
				'text'     => 'Started an online Chakma school, worked toward Unicode support for its writing system, and designed a web font used for the Chakma Wikipedia.',
			),
			array(
				'art_text' => 'Choctaw',
				'eyebrow'  => 'Choctaw · US · 2023',
				'name'     => 'Jacqueline Brixey',
				'text'     => 'Expanded a Choctaw text collection, improved English–Choctaw machine translation, and built bilingual chatbots for learners.',
			),
		),

		'donate_now_eyebrow'              => 'Now.',
		'donate_now_title'                => 'More activists apply than we can fund',
		'donate_now_text'                 => 'In 2027, we plan to launch a peer-to-peer fellowship model. Alongside our intensive fellowship, language activists will learn from one another through free training and mentoring, with rapid grants to put their plans into action. Your gift helps make that possible.',
		'donate_now_button_label'         => 'Donate now',
		'donate_monthly_eyebrow'          => 'Monthly giving',
		'donate_monthly_title'            => 'Become a monthly supporter',
		'donate_monthly_text'             => '$10 a month is $120 a year. Monthly supporters give fellows steady support they can plan around.',
		'donate_monthly_button_label'     => 'Give monthly',

		'donate_faq_intro_eyebrow'        => 'Questions.',
		'donate_faq_intro_title'          => 'Giving to Wikitongues',
		'donate_faq'                      => array(
			array(
				'question' => 'Is my gift tax-deductible?',
				'answer'   => '<p>Yes, in the United States. Wikitongues is a 501(c)(3) nonprofit, EIN 47-1463955.</p>',
			),
			array(
				'question' => 'Can I give from outside the US?',
				'answer'   => "<p>For help with currency or giving from your country, email {$email}.</p>",
			),
			array(
				'question' => 'How else can I give?',
				'answer'   => "<p>To ask about other ways to give, email {$email}.</p>",
			),
			array(
				'question' => 'How do I change or cancel a monthly gift?',
				'answer'   => "<p>For help managing your monthly gift, email {$email}.</p>",
			),
			array(
				'question' => 'Can I give to one fellow or project?',
				'answer'   => "<p>Gifts on this page are unrestricted. To talk about a larger or directed gift, email {$email}.</p>",
			),
		),

		'donate_close_title'              => 'Every language *in the world.*',
		'donate_close_text'               => 'Join us in supporting the language activists doing this work. Will you give $100 today?',
		'donate_close_button_label'       => 'Donate now',
		'donate_close_share_label'        => 'Share this page',
	);
}

/**
 * Supplies a Donate field's default while the page has never saved it.
 *
 * Runs after ACF's own load filters (priority 20), so a repeater arrives
 * here as false and leaves as rows keyed by sub field key, as ACF expects.
 *
 * @param mixed                $value   Loaded value.
 * @param int|string           $post_id ACF post ID.
 * @param array<string, mixed> $field   ACF field.
 * @return mixed
 */
function wt_donate_default_value( $value, $post_id, $field ) {
	$name     = isset( $field['name'] ) ? (string) $field['name'] : '';
	$defaults = wt_donate_defaults();

	if ( ! isset( $defaults[ $name ] ) || ! is_numeric( $post_id ) ) {
		return $value;
	}
	if ( metadata_exists( 'post', (int) $post_id, $name ) ) {
		return $value;
	}

	$default = $defaults[ $name ];
	if ( ! is_array( $default ) ) {
		return $default;
	}

	return wt_donate_rows_by_key( $default, isset( $field['sub_fields'] ) ? $field['sub_fields'] : array() );
}
add_filter( 'acf/load_value', 'wt_donate_default_value', 20, 3 );

/**
 * Re-keys default repeater rows from sub field names to sub field keys.
 *
 * @param array<int, array<string, mixed>> $rows       Rows keyed by sub field name.
 * @param array<int, array<string, mixed>> $sub_fields The repeater's sub fields.
 * @return array<int, array<string, mixed>>
 */
function wt_donate_rows_by_key( $rows, $sub_fields ) {
	$keys = array();
	foreach ( $sub_fields as $sub_field ) {
		$keys[ $sub_field['name'] ] = $sub_field['key'];
	}

	$keyed = array();
	foreach ( $rows as $row ) {
		$keyed_row = array();
		foreach ( $row as $name => $sub_value ) {
			if ( isset( $keys[ $name ] ) ) {
				$keyed_row[ $keys[ $name ] ] = $sub_value;
			}
		}
		$keyed[] = $keyed_row;
	}
	return $keyed;
}

/**
 * Reads a Donate field, falling back to the default copy when ACF is off.
 *
 * @param string $name    Field name (a group returns its sub fields).
 * @param int    $post_id Donate page ID.
 * @return mixed
 */
function wt_donate_field( $name, $post_id ) {
	if ( function_exists( 'get_field' ) && function_exists( 'acf_get_field' ) ) {
		// By key: get_field() only resolves a name the page has already saved.
		$field = acf_get_field( $name );
		return get_field( is_array( $field ) ? $field['key'] : $name, $post_id );
	}

	$defaults = wt_donate_defaults();
	if ( isset( $defaults[ $name ] ) ) {
		return $defaults[ $name ];
	}

	$prefix = $name . '_';
	$group  = array();
	foreach ( $defaults as $key => $default ) {
		if ( 0 === strpos( $key, $prefix ) ) {
			$group[ substr( $key, strlen( $prefix ) ) ] = $default;
		}
	}
	return $group;
}

/**
 * Escapes text and turns *marked words* into a decorated span.
 *
 * Editors mark the hand-drawn highlight or underline with asterisks, so the
 * field stays plain text.
 *
 * @param string $text  Field text.
 * @param string $span_class Class for the marked span.
 * @return string Safe HTML.
 */
function wt_donate_marked( $text, $span_class ) {
	return (string) preg_replace(
		'/\*([^*]+)\*/',
		'<span class="' . esc_attr( $span_class ) . '">$1</span>',
		esc_html( (string) $text )
	);
}

/**
 * Parses a comma-separated list of preset amounts.
 *
 * @param string    $amount_list Editor input, e.g. "50, 100, 250".
 * @param list<int> $fallback Used when the list has no valid amount.
 * @return list<int> Three positive whole-dollar amounts.
 */
function wt_donate_amounts( $amount_list, $fallback ) {
	$amounts = array();
	foreach ( explode( ',', (string) $amount_list ) as $part ) {
		$amount = (int) preg_replace( '/[^0-9]/', '', $part );
		if ( $amount > 0 ) {
			$amounts[] = $amount;
		}
	}
	return 3 === count( $amounts ) ? $amounts : $fallback;
}

/**
 * Turns a site path such as "/revitalization/fellows/" into a full URL.
 *
 * @param string $url Editor input: a path or a full URL.
 * @return string
 */
function wt_donate_url( $url ) {
	$url = trim( (string) $url );
	return ( 0 === strpos( $url, '/' ) && 0 !== strpos( $url, '//' ) ) ? home_url( $url ) : $url;
}
