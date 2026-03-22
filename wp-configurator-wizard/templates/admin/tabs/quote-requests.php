<?php
/**
 * Quote Requests tab template
 *
 * Expected variables:
 * - $wp_configurator_wizard_instance: the main plugin instance (to call quote_requests_page)
 */
?>
<!-- Tab: Quote Requests (outside main form to avoid nested forms) -->
<div id="quote-requests" class="wp-configurator-tab-content">
	<div class="wp-configurator-settings-section">
		<p class="description"><?php esc_html_e( 'View and manage quote requests submitted through the frontend wizard.', 'wp-configurator' ); ?></p>
		<?php
		// Render quote requests using dedicated view
		$wp_configurator_wizard_instance->get_quote_requests_view()->render();
		?>
	</div>
</div>
