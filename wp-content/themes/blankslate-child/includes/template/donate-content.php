<?php
/**
 * Donate blocks: launch content, ACF pre-fill and render helpers.
 *
 * The Donate page is built from editorial `main_content` blocks, like the
 * homepage. Seven of the layouts in "Global: Editorial page" are donation
 * blocks (rendered by modules/flexible-content/donate-*.php and friends) and
 * can be used on any editorial page.
 *
 * Until the Donate page saves its blocks, `main_content` loads the launch
 * blocks below, both on the page and in the admin form, so the editor opens
 * pre-filled and no data migration is needed. Once saved, the saved blocks win.
 */

/** Key of the editorial `main_content` flexible content field. */
const WT_MAIN_CONTENT_KEY = 'field_679e98a9c94e7';

/** Page template whose unsaved `main_content` loads the launch blocks. */
const WT_DONATE_TEMPLATE = 'template-donate.php';

/**
 * Finds published posts by slug, keeping the given order and skipping misses.
 *
 * @param string        $post_type Post type.
 * @param array<string> $slugs     Slugs.
 * @return array<int> Post IDs.
 */
function wt_donate_post_ids( $post_type, $slugs ) {
	$ids = array();
	foreach ( $slugs as $slug ) {
		$post = get_page_by_path( $slug, OBJECT, $post_type );
		if ( $post instanceof WP_Post && 'publish' === $post->post_status ) {
			$ids[] = $post->ID;
		}
	}
	return $ids;
}

/**
 * Finds published FAQ posts by exact question, keeping the given order.
 *
 * @param array<string> $questions FAQ titles.
 * @return array<int> Post IDs.
 */
function wt_donate_faq_ids( $questions ) {
	$ids = array();
	foreach ( $questions as $question ) {
		$found = get_posts(
			array(
				'post_type'      => 'faq',
				'post_status'    => 'publish',
				'title'          => $question,
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);
		if ( $found ) {
			$ids[] = (int) $found[0];
		}
	}
	return $ids;
}

/**
 * The FAQ posts the launch FAQ block lists, as question => answer.
 *
 * "Are donations tax-deductible?" already exists. `wp wt create-donate-faqs`
 * creates the others; until it runs, the block lists the ones that exist.
 *
 * @return array<string, string>
 */
function wt_donate_faqs() {
	$email = '<a href="mailto:hello@wikitongues.org">hello@wikitongues.org</a>';
	return array(
		'Are donations tax-deductible?'             => '',
		'Can I give from outside the US?'           => "For help with currency or giving from your country, email {$email}.",
		'How else can I give?'                      => "To ask about other ways to give, email {$email}.",
		'How do I change or cancel a monthly gift?' => "For help managing your monthly gift, email {$email}.",
		'Can I give to one fellow or project?'      => "Gifts on this page are unrestricted. To talk about a larger or directed gift, email {$email}.",
	);
}

/**
 * The Donate page's launch blocks, as `main_content` rows keyed by field name.
 *
 * @return array<int, array<string, mixed>>
 */
function wt_donate_default_blocks() {
	$checkout   = 'https://wikitongues.donorsupport.co/-/XTRAFEBU';
	$link       = static function ( $title, $url ) {
		return array(
			'title'  => $title,
			'url'    => $url,
			'target' => '',
		);
	};
	$fellows    = wt_donate_post_ids( 'fellows', array( 'hilario-poot-cahun', 'methodes-hangi-bulebe', 'bivuti-chakma', 'jacqueline-brixey' ) );
	$stories    = array(
		array(
			'story_header' => 'Science classes in Maayat’aan',
			'story_copy'   => 'Hilario Poot Cahun is building a science curriculum grounded in Maayat’aan (Yucatec Maya) language and culture, with a focus on ecology. He has published *biology textbooks* in the language, tested the curriculum with teachers and parents at five Indigenous schools, and raised more funding to grow it.',
		),
		array(
			'story_header' => '',
			'story_copy'   => 'Founded a Kihunde research institute in Goma that runs children’s classes, trains new teachers, and has published a dictionary and an Android app.',
		),
		array(
			'story_header' => '',
			'story_copy'   => 'Started an online Chakma school, worked toward Unicode support for its writing system, and designed a web font used for the Chakma Wikipedia.',
		),
		array(
			'story_header' => '',
			'story_copy'   => 'Expanded a Choctaw text collection, improved English–Choctaw machine translation, and built bilingual chatbots for learners.',
		),
	);
	$story_rows = array();
	foreach ( $fellows as $i => $fellow_id ) {
		$story_rows[] = array( 'fellow' => $fellow_id ) + $stories[ $i ];
	}

	return array(
		array(
			'acf_fc_layout'   => 'donate_hero_layout',
			'section_eyebrow' => 'Support Wikitongues',
			'trust_note'      => '501(c)(3) nonprofit. Gifts are tax-deductible in the US.',
			'section_header'  => 'Back the people revitalizing *their languages.*',
			'section_copy'    => 'Language activists around the world are teaching classes, publishing dictionaries and bringing their languages online. Your gift helps fund their work.',
			'financials_link' => $link( 'Our financials', (string) get_post_type_archive_link( 'form_990' ) ),
			'jump_text'       => 'Scroll down to see what your gift makes possible.',
			'gift_box'        => array(
				'gift_header'     => 'Make a gift',
				'campaign_id'     => '',
				'once_amounts'    => '50, 100, 250',
				'monthly_amounts' => '5, 10, 25',
				'note_once'       => 'Your gift supports all of Wikitongues’ work, including the fellowship.',
				'note_monthly'    => 'Monthly supporters give fellows steady support they can plan around.',
				'checkout_link'   => $link( 'Continue to checkout', $checkout ),
				'checkout_note'   => 'Choose your amount and whether to give once or monthly at checkout.',
				'legal_copy'      => 'Payments are completed at our secure checkout. Wikitongues is a 501(c)(3) nonprofit. Gifts are tax-deductible in the US.',
			),
		),
		array(
			'acf_fc_layout' => 'stats_layout',
			'stats'         => array(
				array(
					'stat_type'  => 'live',
					'stat'       => 'fellows',
					'stat_label' => 'languages whose communities we have funded since 2021.',
				),
				array(
					'stat_type'  => 'live',
					'stat'       => 'fellow_countries',
					'stat_label' => 'countries our fellows have come from so far. Activists anywhere can apply.',
				),
				array(
					'stat_type'   => 'custom',
					'stat_prefix' => 'Up to',
					'stat_value'  => '100',
					'stat_label'  => 'language projects a year we’ll support when our new peer-to-peer fellowship launches in 2027.',
				),
			),
		),
		array(
			'acf_fc_layout'   => 'numbered_list_layout',
			'section_eyebrow' => 'Gifts.',
			'section_header'  => 'Where your gift goes',
			'section_copy'    => 'Your gift is unrestricted, so it goes where language activists need it most. It supports all of our work:',
			'items'           => array(
				array(
					'item_header' => 'Language Revitalization Fellowship',
					'item_copy'   => 'Micro-grants, in-kind services and volunteer support for language activists running their own projects, from mother-tongue schools and dictionaries to apps and arts programs.',
				),
				array(
					'item_header' => 'Language Archive',
					'item_copy'   => 'Videos and resources recorded by speakers in {languages} languages, shared openly and preserved with partners including the U.S. Library of Congress, the Arctic World Archive in Svalbard, and Wikimedia Commons.',
				),
				array(
					'item_header' => 'Toolkit and training',
					'item_copy'   => 'Free guides and training so anyone can start a revitalization project in their community, on their own terms.',
				),
			),
		),
		array(
			'acf_fc_layout'   => 'stories_layout',
			'section_eyebrow' => 'Fellows.',
			'section_header'  => 'What your gift makes possible',
			'stories'         => $story_rows,
			'display_photos'  => 'Yes',
			'footer_link'     => $link( 'Meet all our fellows', home_url( '/revitalization/fellows/' ) ),
		),
		array(
			'acf_fc_layout' => 'give_now_layout',
			'give_once'     => array(
				'section_eyebrow' => 'Now.',
				'section_header'  => 'More activists apply than we can fund',
				'section_copy'    => 'In 2027, we plan to launch a peer-to-peer fellowship model. Alongside our intensive fellowship, language activists will learn from one another through free training and mentoring, with rapid grants to put their plans into action. Your gift helps make that possible.',
				'section_cta'     => $link( 'Donate now', $checkout ),
			),
			'give_monthly'  => array(
				'section_eyebrow' => 'Monthly giving',
				'section_header'  => 'Become a monthly supporter',
				'section_copy'    => '$10 a month is $120 a year. Monthly supporters give fellows steady support they can plan around.',
				'section_cta'     => $link( 'Give monthly', $checkout ),
			),
		),
		array(
			'acf_fc_layout'   => 'faq_accordion_layout',
			'section_eyebrow' => 'Questions.',
			'section_header'  => 'Giving to Wikitongues',
			'faq_entries'     => wt_donate_faq_ids( array_keys( wt_donate_faqs() ) ),
		),
		array(
			'acf_fc_layout'  => 'closing_cta_layout',
			'section_header' => 'Every language *in the world.*',
			'section_copy'   => 'Join us in supporting the language activists doing this work. Will you give $100 today?',
			'section_cta'    => $link( 'Donate now', '#give' ),
			'display_share'  => 'Yes',
			'share_label'    => 'Share this page',
		),
	);
}

/**
 * Loads the launch blocks into a Donate page's `main_content` until it is saved.
 *
 * Runs after ACF's own load filters (priority 20), so the value arrives as
 * false and leaves as rows keyed by field key, as ACF expects.
 *
 * @param mixed                $value   Loaded value.
 * @param int|string           $post_id ACF post ID.
 * @param array<string, mixed> $field   ACF field.
 * @return mixed
 */
function wt_donate_default_value( $value, $post_id, $field ) {
	if ( WT_MAIN_CONTENT_KEY !== ( $field['key'] ?? '' ) || ! is_numeric( $post_id ) ) {
		return $value;
	}
	if ( WT_DONATE_TEMPLATE !== get_page_template_slug( (int) $post_id ) ) {
		return $value;
	}
	if ( metadata_exists( 'post', (int) $post_id, 'main_content' ) ) {
		return $value;
	}
	return wt_acf_value_by_key( wt_donate_default_blocks(), $field );
}
add_filter( 'acf/load_value', 'wt_donate_default_value', 20, 3 );

/**
 * Re-keys a value written with field names into the field-key form ACF loads.
 *
 * Walks flexible content, repeater and group fields; other values pass through.
 *
 * @param mixed                $value Value keyed by field name.
 * @param array<string, mixed> $field The field the value belongs to.
 * @return mixed
 */
function wt_acf_value_by_key( $value, $field ) {
	$type = $field['type'] ?? '';

	if ( 'flexible_content' === $type && is_array( $value ) ) {
		$layouts = array();
		foreach ( (array) ( $field['layouts'] ?? array() ) as $layout ) {
			$layouts[ $layout['name'] ] = $layout;
		}
		$rows = array();
		foreach ( $value as $row ) {
			$name = $row['acf_fc_layout'] ?? '';
			if ( ! isset( $layouts[ $name ] ) ) {
				continue;
			}
			$rows[] = array( 'acf_fc_layout' => $name ) + wt_acf_row_by_key( $row, $layouts[ $name ]['sub_fields'] );
		}
		return $rows;
	}

	if ( 'repeater' === $type && is_array( $value ) ) {
		$rows = array();
		foreach ( $value as $row ) {
			$rows[] = wt_acf_row_by_key( $row, $field['sub_fields'] ?? array() );
		}
		return $rows;
	}

	if ( 'group' === $type && is_array( $value ) ) {
		return wt_acf_row_by_key( $value, $field['sub_fields'] ?? array() );
	}

	return $value;
}

/**
 * Re-keys one row (or group) from sub field names to sub field keys.
 *
 * @param array<string, mixed>             $row        Row keyed by sub field name.
 * @param array<int, array<string, mixed>> $sub_fields Sub fields.
 * @return array<string, mixed>
 */
function wt_acf_row_by_key( $row, $sub_fields ) {
	$keyed = array();
	foreach ( $sub_fields as $sub_field ) {
		if ( array_key_exists( $sub_field['name'], $row ) ) {
			$keyed[ $sub_field['key'] ] = wt_acf_value_by_key( $row[ $sub_field['name'] ], $sub_field );
		}
	}
	return $keyed;
}

/**
 * The current flexible content row, formatted and keyed by field name.
 *
 * @return array<string, mixed>
 */
function wt_donate_row() {
	$row = function_exists( 'get_row' ) ? get_row( true ) : array();
	return is_array( $row ) ? $row : array();
}

/**
 * A live count for the Stats Band block.
 *
 * @param string $stat fellows, fellow_countries, languages or countries.
 * @return int
 */
function wt_donate_stat( $stat ) {
	if ( 'fellows' === $stat || 'fellow_countries' === $stat ) {
		$fellow_stats = wt_get_fellow_stats();
		return (int) ( 'fellows' === $stat ? $fellow_stats['fellow_count'] : $fellow_stats['country_count'] );
	}
	$stats = wt_get_archive_stats();
	return (int) ( 'countries' === $stat ? $stats['nations_count'] : $stats['language_count'] );
}

/**
 * The number a Stats Band row shows: its typed text, or a formatted live count.
 *
 * @param array<string, mixed> $stat Stat row (stat_type, stat, stat_value).
 * @return string
 */
function wt_donate_stat_display( $stat ) {
	if ( 'custom' === ( $stat['stat_type'] ?? 'live' ) ) {
		return trim( (string) ( $stat['stat_value'] ?? '' ) );
	}
	return number_format_i18n( wt_donate_stat( (string) ( $stat['stat'] ?? '' ) ) );
}

/**
 * What a Fellow Stories block shows for one story.
 *
 * Reads the fellow's page; the story's own header and copy, when set, win.
 *
 * @param array<string, mixed> $story Story row (fellow, story_header, story_copy).
 * @return array<string, string|int> name, language, location, year, header, copy, photo_id.
 */
function wt_donate_story( $story ) {
	$fellow = $story['fellow'] ?? null;
	$id     = $fellow instanceof WP_Post ? $fellow->ID : (int) $fellow;
	if ( ! $id ) {
		return array();
	}
	$meta = static function ( $name ) use ( $id ) {
		return function_exists( 'get_field' ) ? trim( (string) get_field( $name, $id ) ) : '';
	};

	$copy = trim( (string) ( $story['story_copy'] ?? '' ) );
	return array(
		'name'     => get_the_title( $id ),
		'language' => $meta( 'fellow_language_preferred_name' ),
		'location' => $meta( 'fellow_location' ),
		'year'     => $meta( 'fellow_year' ),
		'header'   => trim( (string) ( $story['story_header'] ?? '' ) ),
		'copy'     => '' !== $copy ? $copy : $meta( 'marketing_text' ),
		'photo_id' => (int) ( function_exists( 'get_field' ) ? get_field( 'fellow_headshot', $id, false ) : 0 ),
	);
}

/**
 * Escapes text and turns *marked words* into a decorated span.
 *
 * Editors mark the hand-drawn highlight or underline with asterisks, so the
 * field stays plain text.
 *
 * @param string $text       Field text.
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
 * @param list<int> $fallback    Used unless the list has exactly three amounts.
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
 * Prints a link field as a link with the given class, or nothing when empty.
 *
 * @param mixed  $link       ACF link array (title, url, target).
 * @param string $link_class Class attribute.
 * @param string $location   data-cta-location value for analytics; empty for none.
 */
function wt_donate_link( $link, $link_class = '', $location = '' ) {
	if ( ! is_array( $link ) || empty( $link['url'] ) ) {
		return;
	}
	printf(
		'<a%s href="%s"%s%s>%s</a>',
		$link_class ? ' class="' . esc_attr( $link_class ) . '"' : '',
		esc_url( $link['url'] ),
		! empty( $link['target'] ) ? ' target="' . esc_attr( $link['target'] ) . '" rel="noopener"' : '',
		$location ? ' data-cta-location="' . esc_attr( $location ) . '"' : '',
		esc_html( $link['title'] ?? '' )
	);
}
