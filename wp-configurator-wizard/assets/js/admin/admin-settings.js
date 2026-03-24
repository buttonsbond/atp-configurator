jQuery(document).ready(function($) {
	// Range slider value displays
	$('.range-slider').on('input', function() {
		var valueDisplay = $(this).siblings('label').find('.value-display');
		if (valueDisplay.length) {
			valueDisplay.text($(this).val());
		}
	});

	// IP Detection
	$('#detect_ip_btn').on('click', function() {
		var $btn = $(this);
		var $ipField = $('#admin_ip_address');
		var $currentIpDisplay = $('#current_ip');

		$btn.prop('disabled', true).text('Detecting...');

		// Use a free IP detection API
		$.ajax({
			url: 'https://api.ipify.org?format=json',
			method: 'GET',
			timeout: 5000,
			success: function(response) {
				if (response && response.ip) {
					$ipField.val(response.ip);
					$currentIpDisplay.text(response.ip);
					showToast('IP address detected: ' + response.ip);
				} else {
					showToast('Could not detect IP address', 'error');
				}
			},
			error: function() {
				// The current IP is already displayed from server-side rendering
				showToast('Could not auto-detect. Please enter manually.', 'error');
			},
			complete: function() {
				$btn.prop('disabled', false).text('Detect My IP');
			}
		});
	});

	// Accordion mode dependency: disable when collapsible is off
	$('#collapsible_categories').on('change', function() {
		if (!$(this).is(':checked')) {
			$('#accordion_mode').prop('checked', false).prop('disabled', true);
		} else {
			$('#accordion_mode').prop('disabled', false);
		}
	}).trigger('change');

	// Initialize accordion mode state on page load
	if (!$('#collapsible_categories').is(':checked')) {
		$('#accordion_mode').prop('disabled', true);
	}

	// Collapsible sections (Donors & Recent Interactions)
	$('.wp-configurator-collapsible-header').on('click', function(e) {
		// Don't toggle if clicking the Refresh button or any interactive element inside
		if ($(e.target).closest('.wp-configurator-refresh-btn, .button').length) {
			return;
		}
		var $section = $(this).closest('.wp-configurator-collapsible-section');
		$section.toggleClass('collapsed');

		// Save collapsed state
		var state = loadAdminState();
		setTimeout(function() {
			if ($section.hasClass('collapsed')) {
				if ($section.hasClass('wp-configurator-donors-section')) {
					state.donorsCollapsed = true;
				} else if ($section.hasClass('wp-configurator-interactions-section')) {
					state.interactionsCollapsed = true;
				}
			} else {
				if ($section.hasClass('wp-configurator-donors-section')) {
					state.donorsCollapsed = false;
				} else if ($section.hasClass('wp-configurator-interactions-section')) {
					state.interactionsCollapsed = false;
				}
			}
			saveAdminState(state);
		}, 50);
	});

	// Refresh interactions button
	$('.wp-configurator-refresh-btn').on('click', function(e) {
		e.preventDefault();
		e.stopPropagation();
		var $btn = $(this);
		$btn.addClass('updating');
		// Simple reload to refresh interactions (they're loaded server-side)
		location.reload();
	});

	// Capture header collapse/expand
	$('.wp-configurator-header-top').on('click', function(e) {
		if ($(e.target).closest('.wp-configurator-donate-btn').length) {
			return;
		}
		var $header = $('#wp-configurator-header-toggle');
		$header.toggleClass('collapsed');
		var state = loadAdminState();
		state.headerCollapsed = $header.hasClass('collapsed');
		saveAdminState(state);
	});

});
