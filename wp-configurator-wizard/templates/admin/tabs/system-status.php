<?php
/**
 * System Status tab template
 *
 * Expected variables:
 * - $wp_configurator_wizard_instance: the main plugin instance (to call render_system_status_tab)
 */
?>
<!-- System Status Tab -->
<div id="system-status" class="wp-configurator-tab-content">
	<div class="wp-configurator-settings-section">
		<?php $wp_configurator_wizard_instance->get_system_status_view()->render(); ?>
	</div>
</div>
