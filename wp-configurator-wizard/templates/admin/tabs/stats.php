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
		<p class="description"><?php esc_html_e( 'Statistics and insights about your configurator usage.', 'wp-configurator' ); ?></p>

		<?php $wp_configurator_wizard_instance->render_stats_tab(); ?>
	</div>
</div>
