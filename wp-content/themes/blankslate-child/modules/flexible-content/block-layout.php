<?php
	$image = get_sub_field('thumbnail');
	$header = get_sub_field('header');
	$block_type = get_sub_field('block_type');
	$link_type = get_sub_field('link_type');
	$text = get_sub_field('text');
	$link = get_sub_field('link');
	$document = get_sub_field('document_download');
	$documentGroup = [];
	$form_id = 'download-form-' . uniqid();
	if($link_type === "Download" && !empty($document) ) {
		$document_name = $document['name'];
		$document_versions = $document['version_container'];
		foreach($document_versions as $version) {
			$document_version = $version['version'];
			$document_languages = $version['language_container'];
			foreach($document_languages as $language) {
				$language_post = $language['language'];
				$document_language = get_field('standard_name', $language_post->ID);
				if (!$document_language) {
					$document_language = $language_post->post_title;
				}
				$document_formats = $language['format_container'];
				foreach($document_formats as $format) {
					$document_format = $format['format'];
					$document_url = $format['file'];
					$documentGroup[] = [
						'name' => $document_name,
						'version' => $document_version,
						'language' => $document_language,
						'format' => $document_format,
						'url' => $document_url
					];
				}
			}
		}
	};

	$anchorElement = '';
	switch($link_type) {
		case 'Link' :
			$anchorElement = '<a href="'. $link['url'] . '">' . $link['title'] . '</a>';
			break;
		case 'Download' :
			$anchorElement = '<button class="'.$form_id.'" type="button">Download</button>';
			$anchorElement .=  do_shortcode('[wikitongues_form]');
			if ($link_type === "Download" && !empty($document) ):
				$anchorElement .= '<form id="download-selector-'.$form_id.'" class="wikitongues-form form-element-2">';
				$anchorElement .= '<fieldset>';
				$anchorElement .= '<legend>'.$document_name.'</legend>';
				foreach(['version', 'language', 'format'] as $option) {
					$anchorElement .= '<label for="'.$option.'-select-'.$form_id.'">'.ucfirst($option).':';
					$anchorElement .= '</label>';
					$anchorElement .= '<select id="'.$option.'-select-'.$form_id.'" class="'.$option.'-select"></select>';
				}
				$anchorElement .= '</fieldset>';
				$anchorElement .= '<button id="download-btn-'.$form_id.'" class="download-btn" disabled>Download</button>';
				$anchorElement .= '</form>';
			endif;
			break;
	}

	$class = 'block';
	switch($block_type) {
		case 'Card' :
			$class .= ' thirds';
			break;
		case 'Block' :
			$class .= ' wide';
			break;
	}

	echo '<section class="'.$class.'">';
?>
<?php if ( $image ): ?>
	<div class="thumbnail"
		role="img"
		aria-label="<?php echo get_post_meta($image, '_wp_attachment_image_alt', TRUE); ?>"
		style="background-image:url(<?php echo wp_get_attachment_url($image) ?>);">
	</div>
<?php elseif ( !$image && $post->post_type !== 'lexicons' && $post->post_type !== 'resources' ): ?>
	<div class="thumbnail" role="img" aria-label="<?php echo $image['alt']; ?>" style="background-image:url(<?php echo $image['url']; ?>);"></div>
<?php else: ?>
	<!-- show nothing -->
<?php endif; ?>
	<aside class="copy">
		<?php
			echo $block_type === 'Block' ? '<h1>' . $header . '</h1>' : '<strong>' . $header . '</strong>';
			echo $text ? '<p>'.$text.'</p>' : '';
			echo $anchorElement
		?>
	</aside>
</section>

<script>
	const documents = <?php echo json_encode($documentGroup); ?>;

// Utility: return an array with unique values
(function() {
    // Unique identifier for this form instance.
    var formId = "<?php echo $form_id; ?>";
    // Container for this form.
    var container = document.getElementById("download-selector-" + formId);

    // Grab references to the dropdowns and button inside this container.
    var formStart = document.querySelector("."+formId);

		formStart.addEventListener("click", function() {
			let flow = document.querySelectorAll('form.wikitongues-form');
			flow[0].style.display="flex";
			console.log(document.querySelector(flow[0] "button"))

    });

    var versionSelect = container.querySelector(".version-select");
    var languageSelect = container.querySelector(".language-select");
    var formatSelect = container.querySelector(".format-select");
    var downloadBtn = container.querySelector(".download-btn");
    var downloadBtn = container.querySelector(".download");

    // Utility: return an array with unique values.
    function unique(arr) {
        return Array.from(new Set(arr));
    }

    // Utility: get unique values for a given property from an array of objects.
    function getUniqueValues(array, property) {
        return unique(array.map(function(item) { return item[property]; }));
    }

    // Populate a select element, disabling options not in the available list.
    function populateSelectWithAvailability(select, allOptions, availableOptions) {
        select.innerHTML = "";
        allOptions.forEach(function(opt) {
            var option = document.createElement("option");
            option.value = opt;
            option.textContent = opt;
            if (availableOptions.indexOf(opt) === -1) {
                option.disabled = true;
								option.title = 'This option is not available for your selection.';
            }
            select.appendChild(option);
        });
    }

    // Build master lists from the global documents array.
    var allVersions = getUniqueValues(documents, 'version');
    var allLanguages = getUniqueValues(documents, 'language');
    var allFormats = getUniqueValues(documents, 'format');

    // Populate the version dropdown.
    function updateVersionOptions() {
        versionSelect.innerHTML = "";
        allVersions.forEach(function(ver) {
            var option = document.createElement("option");
            option.value = ver;
            option.textContent = ver;
            versionSelect.appendChild(option);
        });
    }

    // Update language dropdown based on selected version.
    function updateLanguageOptions() {
        var selectedVersion = versionSelect.value;
        var docsForVersion = documents.filter(function(doc) {
            return doc.version == selectedVersion;
        });
        var availableLanguages = getUniqueValues(docsForVersion, 'language');
        populateSelectWithAvailability(languageSelect, allLanguages, availableLanguages);

        // Log current value before adjustment.
        // If current selection is not available, select the first available option.
        if (availableLanguages.indexOf(languageSelect.value) === -1 && availableLanguages.length > 0) {
            languageSelect.value = availableLanguages[0];
        }
    }

    // Update format dropdown based on selected version and language.
    function updateFormatOptions() {
        var selectedVersion = versionSelect.value;
        var selectedLanguage = languageSelect.value;
        var docsForSelection = documents.filter(function(doc) {
            return doc.version == selectedVersion && doc.language === selectedLanguage;
        });
        var availableFormats = getUniqueValues(docsForSelection, 'format');
        populateSelectWithAvailability(formatSelect, allFormats, availableFormats);

        // Log current format before adjustment.
        // If current selection is not available, select the first available option.
        if (availableFormats.indexOf(formatSelect.value) === -1 && availableFormats.length > 0) {
            formatSelect.value = availableFormats[0];
        }
        downloadBtn.disabled = availableFormats.length === 0;
    }

    // Initialize dropdowns.
    updateVersionOptions();
    updateLanguageOptions();
    updateFormatOptions();

    // Update dependent dropdowns on change.
    versionSelect.addEventListener("change", function() {
        updateLanguageOptions();
        updateFormatOptions();
    });

    languageSelect.addEventListener("change", function() {
        updateFormatOptions();
    });

    // Download button: when clicked, open the URL for the selected document.
    downloadBtn.addEventListener("click", function() {
        var selectedVersion = versionSelect.value;
        var selectedLanguage = languageSelect.value;
        var selectedFormat = formatSelect.value;
        var selectedDoc = documents.find(function(doc) {
            return doc.version === selectedVersion &&
                   doc.language === selectedLanguage &&
                   doc.format === selectedFormat;
        });
        if (selectedDoc && selectedDoc.url) {
            window.open(selectedDoc.url, '_blank');
        } else {
            alert("This document is not available in the selected combination.");
        }
    });


})();

</script>