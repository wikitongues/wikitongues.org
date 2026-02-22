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
}
