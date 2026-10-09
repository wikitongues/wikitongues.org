<?php
/*
 * Abilities for the Language Revitalization Resources page.
 *
 * Registers two abilities (Abilities API, WordPress 6.9+) that the MCP Adapter
 * plugin can hand to an AI client: list the cards on the Resources page, and
 * append one card. Nothing here edits or removes an existing card, and nothing
 * touches any other page.
 *
 * The cards are rows of the `block_group` repeater inside the first Card-type
 * `block_layout` of the page's `main_content` flexible content field
 * (acf-json/group_678fbe627d614.json). The field keys below come from that file.
 *
 * On WordPress < 6.9 the hooks never fire, so this file registers nothing.
 */

if ( ! defined( 'WT_RESOURCES_PAGE_PATH' ) ) {
	define( 'WT_RESOURCES_PAGE_PATH', 'revitalization/resources' );
}

if ( ! defined( 'WT_MAIN_CONTENT_FIELD' ) ) {
	define( 'WT_MAIN_CONTENT_FIELD', 'field_679e98a9c94e7' );
}

if ( ! defined( 'WT_BLOCK_GROUP_FIELD' ) ) {
	define( 'WT_BLOCK_GROUP_FIELD', 'field_67d69cc1ec928' );
}

/**
 * ACF keys of the card sub fields, by field name.
 *
 * @return array<string, string>
 */
function wt_resource_card_field_keys() {
	return array(
		'thumbnail'         => 'field_67b738d82921d',
		'header'            => 'field_67b738f42921e',
		'display_caption'   => 'field_67da8def62ea0',
		'link_type'         => 'field_67b744c6c4a2c',
		'link'              => 'field_67b745bf6f3df',
		'display_secondary' => 'field_67d9fc06dd981',
		'text'              => 'field_67b739042921f',
	);
}

/**
 * 1-based position of the first Card block in a `main_content` value, the row
 * number ACF selectors expect. Null when the page has no Card block.
 *
 * @param mixed $main_content Formatted `main_content` value.
 * @return int|null
 */
function wt_resource_cards_block_position( $main_content ) {
	if ( ! is_array( $main_content ) ) {
		return null;
	}
	foreach ( array_values( $main_content ) as $index => $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$layout = isset( $row['acf_fc_layout'] ) ? $row['acf_fc_layout'] : '';
		$type   = isset( $row['block_type'] ) ? $row['block_type'] : '';
		if ( $layout === 'block_layout' && $type === 'Card' ) {
			return $index + 1;
		}
	}
	return null;
}

/**
 * The cards of the Card block, flattened to plain strings.
 *
 * @param mixed $main_content Formatted `main_content` value.
 * @return array<int, array<string, string>>
 */
function wt_resource_cards_from_content( $main_content ) {
	$position = wt_resource_cards_block_position( $main_content );
	if ( $position === null || ! is_array( $main_content ) ) {
		return array();
	}
	$rows  = array_values( $main_content );
	$group = isset( $rows[ $position - 1 ]['block_group'] ) ? $rows[ $position - 1 ]['block_group'] : array();
	if ( ! is_array( $group ) ) {
		return array();
	}

	$cards = array();
	foreach ( $group as $card ) {
		if ( ! is_array( $card ) ) {
			continue;
		}
		$link    = ( isset( $card['link'] ) && is_array( $card['link'] ) ) ? $card['link'] : array();
		$cards[] = array(
			'header'    => isset( $card['header'] ) ? (string) $card['header'] : '',
			'text'      => isset( $card['text'] ) ? (string) $card['text'] : '',
			'link_type' => isset( $card['link_type'] ) ? (string) $card['link_type'] : '',
			'url'       => isset( $link['url'] ) ? (string) $link['url'] : '',
			'link_text' => isset( $link['title'] ) ? (string) $link['title'] : '',
		);
	}
	return $cards;
}

/**
 * Comparable form of a URL: trimmed, lowercased, no trailing slash.
 *
 * @param string $url URL.
 * @return string
 */
function wt_resource_cards_normalize_url( $url ) {
	return rtrim( strtolower( trim( (string) $url ) ), '/' );
}

/**
 * Whether any card already links to this URL.
 *
 * @param array<int, array<string, string>> $cards Cards from wt_resource_cards_from_content().
 * @param string                            $url   URL to look for.
 * @return bool
 */
function wt_resource_cards_has_url( array $cards, $url ) {
	$needle = wt_resource_cards_normalize_url( $url );
	foreach ( $cards as $card ) {
		$card_url = isset( $card['url'] ) ? $card['url'] : '';
		if ( $card_url !== '' && wt_resource_cards_normalize_url( $card_url ) === $needle ) {
			return true;
		}
	}
	return false;
}

/**
 * Validates and trims the add-card input. The Abilities API already checks the
 * input schema; this repeats the checks that matter so the function is safe
 * to call on its own.
 *
 * @param mixed $input Raw ability input.
 * @return array{header: string, text: string, url: string, link_text: string, thumbnail_id: int}|WP_Error
 */
function wt_resource_card_clean_input( $input ) {
	$input     = is_array( $input ) ? $input : array();
	$header    = isset( $input['header'] ) ? trim( (string) $input['header'] ) : '';
	$text      = isset( $input['text'] ) ? trim( (string) $input['text'] ) : '';
	$url       = isset( $input['url'] ) ? trim( (string) $input['url'] ) : '';
	$link_text = isset( $input['link_text'] ) ? trim( (string) $input['link_text'] ) : '';
	$thumbnail = isset( $input['thumbnail_id'] ) ? (int) $input['thumbnail_id'] : 0;

	if ( $header === '' || $text === '' ) {
		return new WP_Error( 'wt_resource_card_missing', 'A card needs a header and a text.' );
	}
	if ( strlen( $header ) > 120 || strlen( $text ) > 300 || strlen( $link_text ) > 40 ) {
		return new WP_Error( 'wt_resource_card_too_long', 'Header max 120, text max 300, link text max 40 characters.' );
	}
	if ( strpos( $url, 'https://' ) !== 0 || filter_var( $url, FILTER_VALIDATE_URL ) === false ) {
		return new WP_Error( 'wt_resource_card_url', 'The url must be an absolute https:// address.' );
	}

	return array(
		'header'       => $header,
		'text'         => $text,
		'url'          => $url,
		'link_text'    => $link_text === '' ? 'Learn more' : $link_text,
		'thumbnail_id' => max( 0, $thumbnail ),
	);
}

/**
 * The repeater row for a new card, keyed by ACF field key. A navigation link
 * card: one button to the URL, no download gate, no secondary link.
 *
 * @param array{header: string, text: string, url: string, link_text: string, thumbnail_id: int} $clean Output of wt_resource_card_clean_input().
 * @return array<string, mixed>
 */
function wt_resource_card_row( array $clean ) {
	$keys = wt_resource_card_field_keys();
	return array(
		$keys['thumbnail']         => $clean['thumbnail_id'] > 0 ? $clean['thumbnail_id'] : '',
		$keys['header']            => $clean['header'],
		$keys['display_caption']   => 'No',
		$keys['link_type']         => 'link',
		$keys['link']              => array(
			'title'  => $clean['link_text'],
			'url'    => $clean['url'],
			'target' => '',
		),
		$keys['display_secondary'] => 'No',
		$keys['text']              => $clean['text'],
	);
}

/**
 * ID of the Resources page, or 0 when it does not exist.
 *
 * @return int
 */
function wt_resource_cards_page_id() {
	$page = get_page_by_path( WT_RESOURCES_PAGE_PATH );
	return is_object( $page ) ? (int) $page->ID : 0;
}

/**
 * Execute callback: list the cards on the Resources page.
 *
 * @return array{page_url: string, cards: array<int, array<string, string>>}|WP_Error
 */
function wt_resource_cards_execute_list() {
	$page_id = wt_resource_cards_page_id();
	if ( $page_id === 0 ) {
		return new WP_Error( 'wt_resource_page_missing', 'The Resources page was not found.' );
	}
	return array(
		'page_url' => (string) get_permalink( $page_id ),
		'cards'    => wt_resource_cards_from_content( get_field( 'main_content', $page_id ) ),
	);
}

/**
 * Execute callback: append one card to the Resources page.
 *
 * Refuses a URL that is already on the page, so a retried call cannot create a
 * duplicate. Reads the page back afterwards and only reports success when the
 * new card is really there.
 *
 * @param mixed $input Ability input.
 * @return array{added: bool, page_url: string, cards: array<int, array<string, string>>}|WP_Error
 */
function wt_resource_cards_execute_add( $input = null ) {
	$clean = wt_resource_card_clean_input( $input );
	if ( is_wp_error( $clean ) ) {
		return $clean;
	}

	$page_id = wt_resource_cards_page_id();
	if ( $page_id === 0 ) {
		return new WP_Error( 'wt_resource_page_missing', 'The Resources page was not found.' );
	}

	if ( $clean['thumbnail_id'] > 0 && ! wp_attachment_is_image( $clean['thumbnail_id'] ) ) {
		return new WP_Error( 'wt_resource_card_thumbnail', 'thumbnail_id is not an image in the Media Library.' );
	}

	$content  = get_field( 'main_content', $page_id );
	$position = wt_resource_cards_block_position( $content );
	if ( $position === null ) {
		return new WP_Error( 'wt_resource_cards_block_missing', 'The Resources page has no Card block to add to.' );
	}
	if ( wt_resource_cards_has_url( wt_resource_cards_from_content( $content ), $clean['url'] ) ) {
		return new WP_Error( 'wt_resource_card_exists', 'A card already links to that URL.' );
	}

	$added = add_sub_row(
		array( WT_MAIN_CONTENT_FIELD, $position, WT_BLOCK_GROUP_FIELD ),
		wt_resource_card_row( $clean ),
		$page_id
	);

	$cards = wt_resource_cards_from_content( get_field( 'main_content', $page_id ) );
	if ( $added === false || ! wt_resource_cards_has_url( $cards, $clean['url'] ) ) {
		return new WP_Error( 'wt_resource_card_not_saved', 'The card could not be saved.' );
	}

	return array(
		'added'    => true,
		'page_url' => (string) get_permalink( $page_id ),
		'cards'    => $cards,
	);
}

/**
 * Permission callback for adding: the user must be able to edit the page itself.
 *
 * @return bool
 */
function wt_resource_cards_can_edit() {
	$page_id = wt_resource_cards_page_id();
	return $page_id !== 0 && current_user_can( 'edit_page', $page_id );
}

/**
 * Permission callback for listing.
 *
 * @return bool
 */
function wt_resource_cards_can_list() {
	return current_user_can( 'edit_pages' );
}

/**
 * Registers the `wikitongues` ability category.
 */
function wt_register_ability_category() {
	wp_register_ability_category(
		'wikitongues',
		array(
			'label'       => 'Wikitongues',
			'description' => 'Site-specific actions for wikitongues.org.',
		)
	);
}
add_action( 'wp_abilities_api_categories_init', 'wt_register_ability_category' );

/**
 * Registers the Resources page abilities. `mcp.public` makes them visible to
 * the MCP Adapter; without it the adapter hides them.
 */
function wt_register_resource_card_abilities() {
	$card_schema = array(
		'type'       => 'object',
		'properties' => array(
			'header'    => array( 'type' => 'string' ),
			'text'      => array( 'type' => 'string' ),
			'link_type' => array( 'type' => 'string' ),
			'url'       => array( 'type' => 'string' ),
			'link_text' => array( 'type' => 'string' ),
		),
	);

	wp_register_ability(
		'wikitongues/list-resource-cards',
		array(
			'label'               => 'List Resources page cards',
			'description'         => 'Lists the cards on the Language Revitalization Resources page (wikitongues.org/revitalization/resources): header, text, link type, URL and link text of each.',
			'category'            => 'wikitongues',
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'page_url' => array( 'type' => 'string' ),
					'cards'    => array(
						'type'  => 'array',
						'items' => $card_schema,
					),
				),
			),
			'execute_callback'    => 'wt_resource_cards_execute_list',
			'permission_callback' => 'wt_resource_cards_can_list',
			'meta'                => array(
				'show_in_rest' => true,
				'mcp'          => array( 'public' => true ),
				'annotations'  => array(
					'readonly'    => true,
					'destructive' => false,
					'idempotent'  => true,
				),
			),
		)
	);

	wp_register_ability(
		'wikitongues/add-resource-card',
		array(
			'label'               => 'Add a Resources page card',
			'description'         => 'Appends one card to the Language Revitalization Resources page, with a single button linking to an https URL. Refuses a URL the page already links to. It cannot edit or remove existing cards.',
			'category'            => 'wikitongues',
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'header'       => array(
						'type'        => 'string',
						'minLength'   => 1,
						'maxLength'   => 120,
						'description' => 'Card title.',
					),
					'text'         => array(
						'type'        => 'string',
						'minLength'   => 1,
						'maxLength'   => 300,
						'description' => 'One or two sentences under the title.',
					),
					'url'          => array(
						'type'        => 'string',
						'format'      => 'uri',
						'description' => 'Absolute https URL the button opens.',
					),
					'link_text'    => array(
						'type'        => 'string',
						'maxLength'   => 40,
						'description' => 'Button label. Defaults to "Learn more".',
					),
					'thumbnail_id' => array(
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => 'Optional Media Library image ID for the card thumbnail.',
					),
				),
				'required'             => array( 'header', 'text', 'url' ),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'       => 'object',
				'properties' => array(
					'added'    => array( 'type' => 'boolean' ),
					'page_url' => array( 'type' => 'string' ),
					'cards'    => array(
						'type'  => 'array',
						'items' => $card_schema,
					),
				),
			),
			'execute_callback'    => 'wt_resource_cards_execute_add',
			'permission_callback' => 'wt_resource_cards_can_edit',
			'meta'                => array(
				'show_in_rest' => true,
				'mcp'          => array( 'public' => true ),
				'annotations'  => array(
					'readonly'    => false,
					'destructive' => false,
					'idempotent'  => true,
				),
			),
		)
	);
}
add_action( 'wp_abilities_api_init', 'wt_register_resource_card_abilities' );
