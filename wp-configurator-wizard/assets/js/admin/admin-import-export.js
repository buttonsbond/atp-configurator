// Admin Import/Export Module
console.log('✅ admin-import-export.js loaded');

jQuery(document).ready(function($) {
	console.log('✅ Import/Export ready');
	// Import/Export functionality
	var parsedImportData = null;

	// Export Settings
	$('#export-settings').on('click', function() {
		var exportData = {
			action: 'export_settings',
			nonce: wpConfiguratorAdmin.exportNonce,
			options: {
				categories: window.WPConfiguratorAdmin.categories || wpConfiguratorAdmin.categories,
				features: window.WPConfiguratorAdmin.features || wpConfiguratorAdmin.features,
				settings: wpConfiguratorAdmin.settings
			}
		};

		var form = $('<form>', {
			'method': 'POST',
			'action': ajaxurl
		});

		$.each(exportData, function(key, value) {
			if (typeof value === 'object') {
				value = JSON.stringify(value);
			}
			$('<input>', {
				'type': 'hidden',
				'name': key,
				'value': value
			}).appendTo(form);
		});

		form.appendTo('body').submit();
	});

	// Import - Open modal
	$('#import-settings-btn').on('click', function() {
		$('#import-file').val('');
		$('#import-preview, #import-options').hide();
		$('#submit-import').prop('disabled', true);
		parsedImportData = null;
		$('#import-settings-modal').addClass('is-visible').find('.modal-content').scrollTop(0);
	});

	// Import - Close modal
	$('#cancel-import, #import-settings-modal .close-modal').on('click', function() {
		$('#import-settings-modal').removeClass('is-visible');
	});

	// Import - File selection
	$('#import-file').on('change', function(e) {
		var file = e.target.files[0];
		if (!file) return;

		var reader = new FileReader();
		reader.onload = function(e) {
			try {
				var data = JSON.parse(e.target.result);
				parsedImportData = data;

				if (!data.categories && !data.features && !data.settings) {
					alert('Invalid import file: missing required data sections.');
					return;
				}

				$('#preview-version').text(data.version || 'N/A');
				$('#preview-date').text(data.exported || 'N/A');
				$('#preview-categories').text((data.categories ? data.categories.length : 0) + ' categories');
				$('#preview-features').text((data.features ? data.features.length : 0) + ' features');
				$('#preview-settings').text(data.settings ? 'Yes' : 'No');

				$('#import-preview').show();
				$('#import-options').show();
				$('#submit-import').prop('disabled', false);
			} catch (err) {
				alert('Error parsing import file: ' + err.message);
			}
		};
		reader.readAsText(file);
	});

	// Import - Submit
	$('#import-settings-form').on('submit', function(e) {
		e.preventDefault();
		if (!parsedImportData) {
			alert('No data to import. Please select a file first.');
			return;
		}

		var importCategories = $('input[name="import_categories"]').is(':checked');
		var importFeatures = $('input[name="import_features"]').is(':checked');
		var importSettings = $('input[name="import_settings"]').is(':checked');

		if (!importCategories && !importFeatures && !importSettings) {
			alert('Please select at least one type of data to import.');
			return;
		}

		if (!confirm('Are you sure you want to import? This will overwrite existing data according to your selection.')) {
			return;
		}

		var formData = new FormData();
		formData.append('action', 'import_settings');
		formData.append('nonce', wpConfiguratorAdmin.exportNonce);
		formData.append('import_data', JSON.stringify(parsedImportData));
		formData.append('import_categories', importCategories ? '1' : '0');
		formData.append('import_features', importFeatures ? '1' : '0');
		formData.append('import_settings', importSettings ? '1' : '0');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			success: function(response) {
				if (response.success) {
					alert('Import successful! ' + response.data.message);
					location.reload();
				} else {
					alert('Import failed: ' + response.data.message);
				}
			},
			error: function() {
				alert('An error occurred during import. Please try again.');
			}
		});
	});
});
