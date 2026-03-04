<?php
/**
 * ACF_Fields — programmatic field group registration for wt-airtable-sync.
 *
 * Registers a read-only "_airtable_record_id" field on every synced CPT so
 * the Airtable record ID is visible in the post edit screen without DB access.
 *
 * The field is read-only via the 'readonly' wrapper attribute; it is never
 * written by this group — the Sync_Controller stamps the value directly via
 * update_post_meta() using the AIRTABLE_ID_KEY constant.
 *
 * Also registers an ACF options subpage ("Airtable Sync") under the main ACF
 * options menu, with fields for the Airtable base ID and per-CPT table IDs.
 * These values are used to generate direct record links on post edit screens.
 *
 * @package WT\AirtableSync
 */

namespace WT\AirtableSync;

class ACF_Fields {

	/** Slug for the ACF options subpage. */
	const OPTIONS_PAGE_SLUG = 'wt-airtable-sync-settings';

	/**
	 * Register local field groups and the options subpage with ACF.
	 *
	 * Called on acf/init — do nothing if ACF is not active.
	 */
	public static function register(): void {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		self::register_options_page();
		self::register_settings_fields();
		self::register_record_id_field();

		add_action( 'acf/render_field/key=field_wt_airtable_record_id', array( __CLASS__, 'render_record_link' ) );
	}

	/**
	 * Register the Airtable Sync options subpage under the ACF options menu.
	 */
	private static function register_options_page(): void {
		if ( ! function_exists( 'acf_add_options_sub_page' ) ) {
			return;
		}

		acf_add_options_sub_page(
			array(
				'page_title'  => 'Airtable Sync',
				'menu_title'  => 'Airtable Sync',
				'menu_slug'   => self::OPTIONS_PAGE_SLUG,
				'parent_slug' => 'acf-options',
				'capability'  => 'manage_options',
			)
		);
	}

	/**
	 * Register the settings field group on the options subpage.
	 *
	 * Stores: Airtable base ID and per-CPT table IDs.
	 * Read via: get_field( 'wt_airtable_base_id', 'option' ) etc.
	 */
	private static function register_settings_fields(): void {
		acf_add_local_field_group(
			array(
				'key'                   => 'group_wt_airtable_sync_settings',
				'title'                 => 'Airtable Table Configuration',
				'fields'                => array(
					array(
						'key'          => 'field_wt_airtable_base_id',
						'label'        => 'Base ID',
						'name'         => 'wt_airtable_base_id',
						'type'         => 'text',
						'instructions' => 'Found in your Airtable URL: airtable.com/{baseId}/...',
						'required'     => 0,
						'wrapper'      => array(
							'width' => '',
							'class' => 'code',
							'id'    => '',
						),
						'placeholder'  => 'appXXXXXXXXXXXXXX',
					),
					array(
						'key'          => 'field_wt_airtable_table_id_languages',
						'label'        => 'Table ID — Languages',
						'name'         => 'wt_airtable_table_id_languages',
						'type'         => 'text',
						'instructions' => '',
						'required'     => 0,
						'placeholder'  => 'tblXXXXXXXXXXXXXX',
					),
					array(
						'key'          => 'field_wt_airtable_table_id_videos',
						'label'        => 'Table ID — Videos (Oral Histories)',
						'name'         => 'wt_airtable_table_id_videos',
						'type'         => 'text',
						'instructions' => '',
						'required'     => 0,
						'placeholder'  => 'tblXXXXXXXXXXXXXX',
					),
					array(
						'key'          => 'field_wt_airtable_table_id_captions',
						'label'        => 'Table ID — Captions (Oral History Captions)',
						'name'         => 'wt_airtable_table_id_captions',
						'type'         => 'text',
						'instructions' => '',
						'required'     => 0,
						'placeholder'  => 'tblXXXXXXXXXXXXXX',
					),
					array(
						'key'          => 'field_wt_airtable_table_id_lexicons',
						'label'        => 'Table ID — Lexicons',
						'name'         => 'wt_airtable_table_id_lexicons',
						'type'         => 'text',
						'instructions' => '',
						'required'     => 0,
						'placeholder'  => 'tblXXXXXXXXXXXXXX',
					),
				),
				'location'              => array(
					array(
						array(
							'param'    => 'options_page',
							'operator' => '==',
							'value'    => self::OPTIONS_PAGE_SLUG,
						),
					),
				),
				'menu_order'            => 0,
				'position'              => 'normal',
				'style'                 => 'default',
				'label_placement'       => 'left',
				'instruction_placement' => 'label',
				'active'                => true,
				'description'           => 'Airtable IDs used to generate direct record links on post edit screens.',
			)
		);
	}

	/**
	 * Register the read-only Airtable Record ID field on all synced CPTs.
	 */
	private static function register_record_id_field(): void {
		acf_add_local_field_group(
			array(
				'key'                   => 'group_wt_airtable_sync_id',
				'title'                 => 'Airtable Sync',
				'fields'                => array(
					array(
						'key'           => 'field_wt_airtable_record_id',
						'label'         => 'Airtable Record ID',
						'name'          => '_airtable_record_id',
						'type'          => 'text',
						'instructions'  => 'Set automatically by the Airtable sync. Do not edit manually.',
						'required'      => 0,
						'wrapper'       => array(
							'width' => '',
							'class' => '',
							'id'    => '',
						),
						'default_value' => '',
						'placeholder'   => '',
						'prepend'       => '',
						'append'        => '',
						'maxlength'     => '',
						'readonly'      => 1,
					),
				),
				'location'              => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => 'languages',
						),
					),
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => 'videos',
						),
					),
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => 'captions',
						),
					),
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => 'lexicons',
						),
					),
				),
				'menu_order'            => 100,
				'position'              => 'side',
				'style'                 => 'default',
				'label_placement'       => 'top',
				'instruction_placement' => 'label',
				'hide_on_screen'        => array(),
				'active'                => true,
				'description'           => 'Read-only Airtable sync metadata. Managed by the wt-airtable-sync plugin.',
			)
		);
	}

	/**
	 * Render a "View in Airtable" link below the record ID field.
	 *
	 * Fires on acf/render_field/key=field_wt_airtable_record_id.
	 * Outputs nothing if the options page is not yet configured.
	 *
	 * @param array<string, mixed> $field ACF field array including current value.
	 */
	public static function render_record_link( array $field ): void {
		$record_id = (string) ( $field['value'] ?? '' );
		if ( ! $record_id ) {
			return;
		}

		global $post;
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		$url = self::get_record_url( $post->post_type, $record_id );
		if ( ! $url ) {
			return;
		}

		printf(
			'<p style="margin-top:8px"><a href="%s" target="_blank" rel="noopener noreferrer" class="button button-primary button-large" style="width:100%%;text-align:center">View in Airtable</a></p>',
			esc_url( $url )
		);
	}

	/**
	 * Build a direct Airtable record URL for a given CPT and record ID.
	 *
	 * Returns null if the base ID or table ID is not yet configured, or if
	 * the post type is not one of the four synced types.
	 *
	 * @param string $post_type WP CPT slug.
	 * @param string $record_id Airtable record ID (recXXXX).
	 * @return string|null Full URL or null.
	 */
	public static function get_record_url( string $post_type, string $record_id ): ?string {
		if ( ! $record_id ) {
			return null;
		}

		$base_id = (string) get_field( 'wt_airtable_base_id', 'option' );

		$table_field_map = array(
			'languages' => 'wt_airtable_table_id_languages',
			'videos'    => 'wt_airtable_table_id_videos',
			'captions'  => 'wt_airtable_table_id_captions',
			'lexicons'  => 'wt_airtable_table_id_lexicons',
		);

		if ( ! isset( $table_field_map[ $post_type ] ) ) {
			return null;
		}

		$table_id = (string) get_field( $table_field_map[ $post_type ], 'option' );

		if ( ! $base_id || ! $table_id ) {
			return null;
		}

		return sprintf(
			'https://airtable.com/%s/%s/%s',
			rawurlencode( $base_id ),
			rawurlencode( $table_id ),
			rawurlencode( $record_id )
		);
	}
}
