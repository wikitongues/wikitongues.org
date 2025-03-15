<?php
get_header();
echo '<h1>' . get_the_title() . '</h1>';
$description = get_field('description');
if ($description) {
	echo '<div class="document-description">' . wp_kses_post(esc_html($description)) . '</div>';
	wpautop(wp_kses_post(get_sub_field('text_area')));
}

function render_download_ui($document_id) {
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
	]);

	if (!$files) {
			echo '<p>No files available for download.</p>';
			return;
	}

	echo '<h3>Available Downloads</h3>';
	echo '<table id="downloadsTable">';
	echo '<thead><tr><th>Language</th><th>Version</th><th>Download</th></tr></thead>';
	echo '<tbody>';

	foreach ($files as $file) {
		$version = get_field('version', $file->ID);
		$file_id = $file->ID;

		$language = get_field('language', $file->ID);
		$language_name = 'Unknown';

		if (is_object($language) && isset($language->ID)) {
				$standard_name = get_field('standard_name', $language->ID);
				$language_name = !empty($standard_name) ? esc_html($standard_name) : get_the_title($language->ID);
		}

		echo '<tr>';
		echo '<td>' . esc_html($language_name) . '</td>';
		echo '<td>' . esc_html($version) . '</td>';
		echo '<td><button class="download-btn" data-file-id="' . esc_attr($file_id) . '">Download</button></td>';
		echo '</tr>';
	}

	echo '</tbody>';
	echo '</table>';

	// Include the AJAX script
	?>
	<script>
	document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".download-btn").forEach(button => {
        button.addEventListener("click", function (event) {
            event.preventDefault(); // Prevent any accidental redirections

            const fileId = this.getAttribute("data-file-id");

            fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: new URLSearchParams({
                    action: "download_document",
                    file_id: fileId
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log("AJAX Response:", data); // Debugging log

                if (data.success && data.data.file_url) {
                    console.log("Triggering download: " + data.data.file_url);
                    window.location.href = data.data.file_url; // Initiate file download
                } else {
                    alert("Error downloading file: " + (data.message || "Unknown error."));
                }
            })
            .catch(error => {
                console.error("AJAX Error:", error);
                alert("Request failed. Please try again.");
            });
        });
    });
});


	</script>
	<?php
}


// Call the function to render the UI
render_download_ui(get_the_ID());

// Process user selection
if (isset($_GET['language']) || isset($_GET['version'])) {
    $selected_language_id = isset($_GET['language']) ? intval($_GET['language']) : null;
    $selected_version = isset($_GET['version']) ? $_GET['version'] : null;

    $file = get_latest_document_file(get_the_ID(), $selected_language_id);

    if ($file && (!$selected_version || get_field('version', $file->ID) == $selected_version)) {
        $file_url = get_field('file', $file->ID);
        echo '<p><a href="' . esc_url($file_url) . '" download>Download File</a></p>';
    }
}

include('modules/newsletter.php');
get_footer();
