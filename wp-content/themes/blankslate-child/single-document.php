<?php
get_header();

$description = get_field('description');
$selected_file = get_field('selected_file');
$file_field = get_field('file', $selected_file->ID);
$file_cta = get_field('file_cta');

include( 'modules/editorial-content.php' );

echo '<div class="main-content">';
render_download_ui(get_the_ID());
echo '</div>';

function render_download_ui($document_id) {
	// Fetch all files
	$files = get_posts([
			'post_type'  => 'document_files',
			'meta_query' => [
					[
							'key'     => 'parent_download',
							'value'   => $document_id,
							'compare' => '=',
					],
			],
			'orderby' => 'meta_value_num',
			'meta_key' => 'version',
			'order'   => 'DESC',
			'posts_per_page' => -1,
	]);

	if (!$files) {
			echo '<p>No files available for download.</p>';
			return;
	}

	// Group files by language
	$grouped = [];
	$primary_download = null;

	foreach ($files as $file) {
			$lang_obj = get_field('language', $file->ID);
			$language_field = get_field('language', $file->ID);
			$version = get_field('version', $file->ID);
			// $lang_id = is_object($lang_obj) ? $lang_obj->ID : null;
			$lang_id = is_object($language_field) ? $language_field->ID : (is_numeric($language_field) ? intval($language_field) : null);


			if (!isset($grouped[$lang_id])) {
					$grouped[$lang_id] = [];
			}

			$grouped[$lang_id][] = [
					'file_id' => $file->ID,
					'version' => $version,
					'language_id' => $lang_id,
					'language_name' => $lang_id ? (get_field('standard_name', $lang_id) ?: get_the_title($lang_id)) : 'Unknown',
			];

			// Identify the primary download (latest English version)
			if (!$primary_download && $lang_id && get_field('standard_name', $lang_id) === 'eng') {
					$primary_download = $file;
			}
	}

	// Fallback if no English file is found
	if (!$primary_download) {
			$primary_download = $files[0];
	}

	// Render UI
	?>
	<h3>Download</h3>
	<div class="primary-download">
			<button id="primary-download-btn" data-file-id="<?php echo esc_attr($primary_download->ID); ?>">
					Download latest English version
			</button>
	</div>

	<div class="language-selector">
			<label for="language-filter">Filter other downloads by language:</label>
			<select id="language-filter">
				<?php
				foreach ($grouped as $lang_id => $docs) {
						$lang_obj = get_post($lang_id);

						echo '<!-- DEBUG: lang_id = ' . esc_html($lang_id) . ', lang_obj = ' . json_encode([
								'post_id' => $lang_obj->ID ?? 'N/A',
								'post_title' => $lang_obj->post_title ?? 'N/A',
								'standard_name' => get_field('standard_name', $lang_id) ?: 'N/A',
						]) . ' -->';

						$iso_code = $lang_obj->post_title ?? 'no_iso';
						$lang_name = get_field('standard_name', $lang_id);
						$selected = ($iso_code === 'eng') ? 'selected' : '';

						echo '<option value="' . esc_attr($lang_id) . '" ' . $selected . '>' . esc_html($lang_name) . ' (' . esc_html($iso_code) . ')</option>';
				}
				?>
		</select>


	</div>

	<table id="downloadsTable">
			<thead><tr><th>Language</th><th>Version</th><th>Download</th></tr></thead>
			<tbody>
			</tbody>
	</table>

	<script>
	document.addEventListener("DOMContentLoaded", function () {
    const ajaxUrl = "<?php echo admin_url('admin-ajax.php'); ?>";
    const languageSelect = document.getElementById("language-filter");
    const primaryButton = document.getElementById("primary-download-btn");

    // Primary download button logic
    primaryButton.addEventListener("click", function (e) {
        e.preventDefault();
        triggerDownload(this.dataset.fileId);
    });

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
                parent_id: "<?php echo esc_attr($document_id); ?>",
                lang_iso: isoCode
            })
        })
        .then(response => response.json())
        .then(data => {
            document.querySelector("#downloadsTable tbody").innerHTML = data.html;
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

include('modules/newsletter.php');
get_footer();
