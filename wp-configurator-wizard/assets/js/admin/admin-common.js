// Admin Common Utilities
console.log('✅ admin-common.js loaded');

(function() {
    var STORAGE_KEY = 'wp_configurator_admin_state';

    function saveAdminState(state) {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
        } catch (e) {
            console.warn('Could not save admin state to localStorage:', e);
        }
    }

    function loadAdminState() {
        try {
            var saved = localStorage.getItem(STORAGE_KEY);
            return saved ? JSON.parse(saved) : {};
        } catch (e) {
            console.warn('Could not load admin state from localStorage:', e);
            return {};
        }
    }

    window.saveAdminState = saveAdminState;
    window.loadAdminState = loadAdminState;
    console.log('✅ saveAdminState and loadAdminState exposed globally');

    // Make showToast globally available
    window.showToast = function(message, type) {
        type = type || 'success';
        var $toast = jQuery('<div class="wp-configurator-toast ' + type + '">' + message + '</div>');
        jQuery('body').append($toast);
        $toast[0].offsetHeight;
        $toast.addClass('show');
        setTimeout(function() {
            $toast.removeClass('show');
            setTimeout(function() {
                $toast.remove();
            }, 300);
        }, 3000);
    };
    console.log('✅ showToast exposed globally');
})();
