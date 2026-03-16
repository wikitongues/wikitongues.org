<?php
/**
 * Acf_Fields — registers gateway ACF fields programmatically.
 *
 * Adds a "Download Gateway" field group to all post types that have a
 * registered FileResolver. The group exposes:
 *   - _gateway_gate_policy  per-resource override (none / soft / hard / inherit)
 *
 * Registered via acf_add_local_field_group() so no ACF JSON is required
 * in the theme, and no DB write happens on activation.
 *
 * @package WT\DownloadGateway
 */

namespace WT\DownloadGateway;

class Acf_Fields {

	public static function register(): void {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		$post_types = FileResolverRegistry::registered_post_types();
		if ( empty( $post_types ) ) {
			return;
		}

		$location = array_map(
			fn( string $pt ) => array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => $pt,
				),
			),
			$post_types
		);

		acf_add_local_field_group(
			array(
				'key'      => 'group_gateway_resource',
				'title'    => 'Download Gateway',
				'fields'   => array(
					array(
						'key'           => 'field_gateway_gate_policy',
						'label'         => 'Gate policy override',
						'name'          => '_gateway_gate_policy',
						'type'          => 'select',
						'instructions'  => 'Override the global gate policy for this resource. "Inherit" uses the global default.',
						'required'      => 0,
						'choices'       => array(
							''     => 'Inherit (use global default)',
							'none' => 'None — direct redirect, no gate',
							'soft' => 'Soft gate — skippable email prompt',
							'hard' => 'Hard gate — email required',
						),
						'default_value' => '',
						'allow_null'    => 1,
						'multiple'      => 0,
						'ui'            => 0,
						'return_format' => 'value',
					),
				),
				'location' => $location,
				'position' => 'side',
				'style'    => 'default',
				'active'   => true,
			)
		);
	}
}
