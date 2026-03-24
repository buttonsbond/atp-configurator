<?php
/**
 * Stats tab template
 *
 * Expected variables:
 * - $wp_configurator_wizard_instance: the main plugin instance (to call render_stats_tab)
 */
?>
<!-- Tab: Stats -->
<div id="stats" class="wp-configurator-tab-content">
	<div class="wp-configurator-settings-section">
		<?php $wp_configurator_wizard_instance->render_stats_tab(); ?>
	</div>
</div>
