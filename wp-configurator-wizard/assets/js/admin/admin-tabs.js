// Admin Tab Navigation Module
console.log('✅ admin-tabs.js loaded');

jQuery(function($) {
	console.log('✅ Tab navigation ready');

	// Restore UI state on page load (runs once on DOM ready)
	(function restoreState() {
		var state = loadAdminState();

		// Restore active tab
		if (state.activeTab) {
			var $tab = $('.nav-tab[data-tab="' + state.activeTab + '"]');
			if ($tab.length) {
				$('.nav-tab-wrapper .nav-tab').removeClass('nav-tab-active');
				$tab.addClass('nav-tab-active');
				$('.wp-configurator-tab-content').removeClass('active');
				$('#' + state.activeTab).addClass('active');
			}
		}

		// Restore header collapsed state
		if (state.headerCollapsed) {
			$('.wp-configurator-admin-header').addClass('collapsed');
		} else {
			$('.wp-configurator-admin-header').removeClass('collapsed');
		}

		// Restore donors section state
		if (state.donorsCollapsed) {
			$('.wp-configurator-donors-section').addClass('collapsed');
		} else {
			$('.wp-configurator-donors-section').removeClass('collapsed');
		}

		// Restore recent interactions section state
		if (state.interactionsCollapsed) {
			$('.wp-configurator-interactions-section').addClass('collapsed');
		} else {
			$('.wp-configurator-interactions-section').removeClass('collapsed');
		}
	})();

	try {
		$('.nav-tab-wrapper .nav-tab').on('click', function() {
			var tabId = $(this).data('tab');
			console.log('🖱️ Tab clicked:', tabId);
			$('.nav-tab-wrapper .nav-tab').removeClass('nav-tab-active');
			$(this).addClass('nav-tab-active');
			$('.wp-configurator-tab-content').removeClass('active');
			$('#' + tabId).addClass('active');

			// Save active tab to localStorage
			var state = loadAdminState();
			state.activeTab = tabId;
			saveAdminState(state);
		});
	} catch(e) {
		console.error('❌ Error setting up tab switching:', e);
	}
});
