<?php
get_header();

$description   = get_field( 'description' );
$selected_file = get_field( 'selected_file' );
if ( $selected_file ) {
	$file_field = get_field( 'file', $selected_file->ID );
}
$file_cta = get_field( 'file_cta' );

require 'modules/editorial-content.php';

echo '<div class="main-content">';
render_download_ui( get_the_ID() );
echo '</div>';

function render_download_ui( $document_id ) {
	// Fetch all files
	$files = get_posts(
		array(
			'post_type'      => 'document_files',
			'meta_query'     => array(
				array(
					'key'     => 'parent_download',
					'value'   => $document_id,
					'compare' => '=',
				),
			),
			'orderby'        => 'meta_value_num',
			'meta_key'       => 'version',
			'order'          => 'DESC',
			'posts_per_page' => -1,
		)
	);

	if ( ! $files ) {
			echo '<p>No files available for download.</p>';
			return;
	}

	// Determine gateway availability once for all rows.
	$gateway_active = shortcode_exists( 'gateway_download' ) && GATEWAY_ENABLED;

	// Build flat list of file data and collect unique languages for the selector.
	$file_rows       = array();
	$lang_index      = array(); // lang_id => ['name' => ..., 'iso' => ...]
	$default_lang_id = null;

	foreach ( $files as $file ) {
		$lang_obj  = get_field( 'language', $file->ID );
		$lang_id   = is_object( $lang_obj ) ? $lang_obj->ID : null;
		$iso_code  = $lang_id ? get_the_title( $lang_id ) : 'unknown';
		$lang_name = $lang_id ? ( get_field( 'standard_name', $lang_id ) ?: get_the_title( $lang_id ) ) : 'Unknown';
		$version   = get_field( 'version', $file->ID );
		$format    = get_field( 'format', $file->ID );

		if ( $lang_id && ! isset( $lang_index[ $lang_id ] ) ) {
			$lang_index[ $lang_id ] = array(
				'name' => $lang_name,
				'iso'  => $iso_code,
			);
			if ( 'eng' === $iso_code ) {
				$default_lang_id = $lang_id;
			}
		}

		// Resolve gateway link or direct file URL.
		if ( $gateway_active ) {
			$policy   = \WT\DownloadGateway\PolicyResolver::resolve( $file->ID );
			$disabled = ( $policy === \WT\DownloadGateway\SettingsRepository::POLICY_DISABLED );
			$intake   = $disabled ? null : \WT\DownloadGateway\IntakeResolver::resolve( $file->ID );
			$dl_url   = $disabled ? null : rest_url( GATEWAY_REST_NAMESPACE . '/download/' . $file->ID );
		} else {
			$policy   = null;
			$disabled = false;
			$intake   = null;
			$dl_url   = get_field( 'file', $file->ID ) ?: null;
		}

		$file_rows[] = array(
			'file_id'   => $file->ID,
			'lang_id'   => $lang_id,
			'lang_name' => $lang_name,
			'version'   => $version,
			'format'    => $format,
			'policy'    => $policy,
			'disabled'  => $disabled,
			'intake'    => $intake,
			'dl_url'    => $dl_url,
		);
	}

	// Fall back to first language if English not present.
	if ( null === $default_lang_id && ! empty( $lang_index ) ) {
		$default_lang_id = array_key_first( $lang_index );
	}

	?>
	<h3>Other available versions</h3>
	<p>Wikitongues releases updates and translations to our resources periodically.<br>Below you can see all available versions of this document.</p>
	<div class="download-container">
		<div class="language-selector">
			<label for="language-filter">Filter other downloads by language:</label>
			<select id="language-filter">
				<?php foreach ( $lang_index as $lid => $info ) : ?>
					<option value="<?php echo esc_attr( $lid ); ?>"<?php selected( $lid, $default_lang_id ); ?>>
						<?php echo esc_html( $info['name'] ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<table id="downloads-table">
			<thead>
				<tr>
					<th>Language</th>
					<th>Version</th>
					<th>Format</th>
					<th>Download</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $file_rows as $row ) : ?>
					<tr data-lang-id="<?php echo esc_attr( $row['lang_id'] ); ?>"<?php echo ( $row['lang_id'] !== $default_lang_id ) ? ' style="display:none"' : ''; ?>>
						<td><?php echo esc_html( $row['lang_name'] ); ?></td>
						<td class="version"><?php echo esc_html( $row['version'] ); ?></td>
						<td><?php echo esc_html( $row['format'] ); ?></td>
						<td>
							<?php if ( $row['disabled'] ) : ?>
								<span>Unavailable</span>
							<?php elseif ( $row['dl_url'] && $gateway_active ) : ?>
								<a href="<?php echo esc_url( $row['dl_url'] ); ?>"
									class="gateway-download-link"
									data-post-id="<?php echo esc_attr( $row['file_id'] ); ?>"
									data-policy="<?php echo esc_attr( $row['policy'] ); ?>"
									data-post-type="document_files"
									data-intake-set="<?php echo esc_attr( $row['intake']['set'] ?? '' ); ?>"
									data-intake-always="<?php echo ( $row['intake']['always'] ?? false ) ? '1' : '0'; ?>"
									data-download-source="resource-page">Download</a>
							<?php elseif ( $row['dl_url'] ) : ?>
								<a href="<?php echo esc_url( $row['dl_url'] ); ?>">Download</a>
							<?php else : ?>
								<span>Unavailable</span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<script>
	document.addEventListener("DOMContentLoaded", function () {
		const select = document.getElementById("language-filter");
		function filterTable(langId) {
			document.querySelectorAll("#downloads-table tbody tr").forEach(function (row) {
				row.style.display = row.dataset.langId === langId ? "" : "none";
			});
		}
		select.addEventListener("change", function () { filterTable(String(this.value)); });
	});
	</script>
	<?php
}

require 'modules/newsletter.php';
get_footer();
