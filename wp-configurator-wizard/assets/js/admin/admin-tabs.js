// Admin Tab Navigation Module
console.log('✅ admin-tabs.js loaded');

jQuery(function($) {
	console.log('✅ Tab navigation ready');
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
