<?php
/**
 * Admin UI class
 * Renders all admin page templates
 *
 * @package WP_Configurator_Wizard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Admin_UI {

	/**
	 * Settings manager instance
	 *
	 * @var Settings_Manager
	 */
	private $settings_manager;

	/**
	 * Main plugin instance (for delegating method calls)
	 *
	 * @var WP_Configurator_Wizard
	 */
	private $plugin;

	/**
	 * Constructor
	 */
	public function __construct( Settings_Manager $settings_manager, WP_Configurator_Wizard $plugin ) {
		$this->settings_manager = $settings_manager;
		$this->plugin = $plugin;
	}

	/**
	 * Render the main admin page
	 * Includes page-wrapper template which in turn includes tab templates
	 */
	public function render_page(): void {
		$options = $this->settings_manager->get_options();

		// Make $wp_configurator_wizard_instance available to templates for method calls
		global $wp_configurator_wizard_instance;
		$wp_configurator_wizard_instance = $this->plugin;

		// Load the page wrapper template
		include __DIR__ . '/../templates/admin/page-wrapper.php';
	}
}
