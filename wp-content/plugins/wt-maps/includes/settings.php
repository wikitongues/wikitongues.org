<?php
/**
 * Settings → Maps: turn the sidebar map or the map interaction off, and pick
 * the style, without a deploy.
 *
 * @package WT\Maps
 */

use WT\Maps\Renderer;

/**
 * Keep only known values.
 *
 * @param mixed $input Submitted option.
 * @return array{sidebar: bool, style: string, interactive: bool}
 */
function wt_maps_sanitize_settings( $input ): array {
	$input = is_array( $input ) ? $input : array();
	$style = isset( $input['style'] ) && isset( Renderer::STYLES[ $input['style'] ] ) ? (string) $input['style'] : Renderer::DEFAULT_STYLE;
	return array(
		'sidebar'     => ! empty( $input['sidebar'] ),
		'style'       => $style,
		'interactive' => ! empty( $input['interactive'] ),
	);
}

add_action(
	'admin_init',
	static function (): void {
		register_setting(
			'wt_maps',
			'wt_maps',
			array(
				'type'              => 'array',
				'sanitize_callback' => 'wt_maps_sanitize_settings',
				'default'           => array(
					'sidebar'     => true,
					'style'       => Renderer::DEFAULT_STYLE,
					'interactive' => true,
				),
			)
		);
	}
);

add_action(
	'admin_menu',
	static function (): void {
		add_options_page( 'Maps', 'Maps', 'manage_options', 'wt-maps', 'wt_maps_settings_page' );
	}
);

/**
 * Render the settings page.
 */
function wt_maps_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$settings = wt_maps_settings();
	?>
	<div class="wrap">
		<h1>Maps</h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'wt_maps' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">Language pages</th>
					<td>
						<label>
							<input type="checkbox" name="wt_maps[sidebar]" value="1" <?php checked( $settings['sidebar'] ); ?>>
							Show the locator map in the language sidebar
						</label>
						<p class="description">Languages show a map only when one has been built for their places.</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Interaction</th>
					<td>
						<label>
							<input type="checkbox" name="wt_maps[interactive]" value="1" <?php checked( $settings['interactive'] ); ?>>
							Label places on hover and open their pages on click
						</label>
						<p class="description">On every map: language, territory and region pages. Off, the maps are still images.</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wt-maps-style">Style</label></th>
					<td>
						<select id="wt-maps-style" name="wt_maps[style]">
							<?php foreach ( Renderer::STYLES as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['style'], $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description">Light or dark world; full frame, or focused on the language's places.</p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
