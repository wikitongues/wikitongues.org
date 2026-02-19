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

	// Group files by language
	$grouped = array();
	foreach ( $files as $file ) {
			$lang_obj       = get_field( 'language', $file->ID );
			$language_field = get_field( 'language', $file->ID );
			$version        = get_field( 'version', $file->ID );
			$lang_id        = is_object( $language_field ) ? $language_field->ID : ( is_numeric( $language_field ) ? intval( $language_field ) : null );

		if ( ! isset( $grouped[ $lang_id ] ) ) {
				$grouped[ $lang_id ] = array();
		}

			$grouped[ $lang_id ][] = array(
				'file_id'       => $file->ID,
				'version'       => $version,
				'language_id'   => $lang_id,
				'language_name' => $lang_id ? ( get_field( 'standard_name', $lang_id ) ?: get_the_title( $lang_id ) ) : 'Unknown',
			);
	}

	// Render UI
	?>
	<h3>Other available versions</h3>
	<p>Wikitongues releases updates and translations to our resources periodically.<br>Below you can see all available versions of this document.</p>
	<div class="download-container">
		<div class="language-selector">
			<label for="language-filter">Filter other downloads by language:</label>
			<select id="language-filter">
				<?php
				foreach ( $grouped as $lang_id => $docs ) {
						$lang_obj  = get_post( $lang_id );
						$iso_code  = $lang_obj->post_title ?? 'no_iso';
						$lang_name = get_field( 'standard_name', $lang_id );
						$selected  = ( $iso_code === 'eng' ) ? 'selected' : '';

						echo '<option value="' . esc_attr( $lang_id ) . '" ' . $selected . '>' . esc_html( $lang_name ) . '</option>';
				}
				?>
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
				<tbody></tbody>
		</table>
	</div>
	<script>
	document.addEventListener("DOMContentLoaded", function () {
	const ajaxUrl = "<?php echo admin_url( 'admin-ajax.php' ); ?>";
	const languageSelect = document.getElementById("language-filter");

	// Auto-load table with pre-selected language (e.g., "eng")
	if (languageSelect.value) {
		fetchTable(languageSelect.value);
	}

	// On language change, refresh table
	languageSelect.addEventListener("change", function () {
		fetchTable(this.value);
	});

	// Fetch & populate table based on selected ISO code
	function fetchTable(isoCode) {
		fetch(ajaxUrl, {
			method: "POST",
			headers: { "Content-Type": "application/x-www-form-urlencoded" },
			body: new URLSearchParams({
				action: "fetch_document_files",
				parent_id: "<?php echo esc_attr( $document_id ); ?>",
				lang_id: isoCode
			})
		})
				.then(response => response.json())
		.then(data => {
			document.querySelector("#downloads-table tbody").innerHTML = data.data;
		});
	}

	// Handle download clicks inside the table dynamically (delegation)
	document.addEventListener("click", function (e) {
		if (e.target.classList.contains('download-btn')) {
			e.preventDefault();
			triggerDownload(e.target.dataset.fileId);
		}
	});

	// Trigger download handler (AJAX fetch → file redirect)
	function triggerDownload(fileId) {
		fetch(ajaxUrl, {
			method: "POST",
			headers: { "Content-Type": "application/x-www-form-urlencoded" },
			body: new URLSearchParams({
				action: "download_document",
				file_id: fileId
			})
		})
		.then(response => response.json())
		.then(data => {
			if (data.success && data.data.file_url) {
				window.location.href = data.data.file_url;
			} else {
				alert("Error downloading file: " + (data.message || "Unknown error."));
			}
		});
	}
});
	</script>
	<?php
}

require 'modules/newsletter.php';
get_footer();
