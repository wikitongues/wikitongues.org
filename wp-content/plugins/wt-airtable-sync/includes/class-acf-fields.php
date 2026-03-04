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
 * Also renders a "View in Airtable" link below the record ID field. The link
 * is built from the "Admin: Airtable Links" ACF options page (group_69a8bc1fac0cc),
 * which is UI-defined and synced via acf-json. Field structure:
 *
 *   airtable_table_configurations (group)
 *     base_id (text)
 *     languages (group) → table_id, view_id
 *     videos   (group) → table_id, view_id
 *     captions (group) → table_id, view_id
 *     lexicons (group) → table_id, view_id
 *
 * @package WT\AirtableSync
 */

namespace WT\AirtableSync;

class ACF_Fields {

	/**
	 * Register local field groups with ACF.
	 *
	 * Called on acf/init — do nothing if ACF is not active.
	 */
	public static function register(): void {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		self::register_record_id_field();

		add_action( 'acf/render_field/key=field_wt_airtable_record_id', array( __CLASS__, 'render_record_link' ) );
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
	 * Reads from the "Admin: Airtable Links" ACF options page (group_69a8bc1fac0cc).
	 * URL format: airtable.com/{baseId}/{tableId}/{viewId}/{recordId}
	 * (view segment omitted when view_id is not configured).
	 *
	 * Returns null if the base ID or table ID is not configured, or if
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

		$supported = array( 'languages', 'videos', 'captions', 'lexicons' );
		if ( ! in_array( $post_type, $supported, true ) ) {
			return null;
		}

		$config = get_field( 'airtable_table_configurations', 'option' );
		if ( ! is_array( $config ) ) {
			return null;
		}

		$base_id  = (string) ( $config['base_id'] ?? '' );
		$cpt_cfg  = $config[ $post_type ] ?? array();
		$table_id = (string) ( $cpt_cfg['table_id'] ?? '' );

		if ( ! $base_id || ! $table_id ) {
			return null;
		}

		$view_id = (string) ( $cpt_cfg['view_id'] ?? '' );

		if ( $view_id ) {
			return sprintf(
				'https://airtable.com/%s/%s/%s/%s',
				rawurlencode( $base_id ),
				rawurlencode( $table_id ),
				rawurlencode( $view_id ),
				rawurlencode( $record_id )
			);
		}

		return sprintf(
			'https://airtable.com/%s/%s/%s',
			rawurlencode( $base_id ),
			rawurlencode( $table_id ),
			rawurlencode( $record_id )
		);
	}
}
