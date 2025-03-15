jQuery(document).ready(function ($) {
	function checkDuplicateFile() {
			let parentDownload = $(`select[name="${ajax_object.acf_fields.parent_download}"]`).val();
			let language = $(`select[name="${ajax_object.acf_fields.language}"]`).val();
			let version = $(`input[name="${ajax_object.acf_fields.version}"]`).val();

			if (!parentDownload || !language || !version) {
					$('#duplicate-warning').remove();
					$('#publish').prop('disabled', false);
					return;
			}

			$.ajax({
					url: ajax_object.ajax_url,
					type: 'POST',
					data: {
							action: 'check_duplicate_document_file',
							parent_download: parentDownload,
							language: language,
							version: version
					},
					success: function (response) {
							$('#duplicate-warning').remove();
							if (response.exists) {
									let warning = $('<div id="duplicate-warning" style="color: red; margin-top: 10px;">' +
											'<strong>Error:</strong> This Language-Version combination already exists.' +
											'</div>');
									$('#acf-group').prepend(warning);
									$('#publish').prop('disabled', true);
							} else {
									$('#publish').prop('disabled', false);
							}
					}
			});
	}

	$(document).on('change', `select[name^="acf["], input[name^="acf["]`, checkDuplicateFile);
});
