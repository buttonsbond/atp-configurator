<?php
/**
 * AJAX Handler class
 * Manages all AJAX requests for the plugin
 *
 * @package WP_Configurator_Wizard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Ajax_Handler {

	use Cost_Calculation;
	use Quote_Management;
	use Interaction_Tracking;
	use Data_IO;

	/**
	 * Plugin version
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Settings manager instance
	 *
	 * @var Settings_Manager
	 */
	private $settings_manager;

	/**
	 * Database manager instance
	 *
	 * @var Database_Manager
	 */
	private $database_manager;

	/**
	 * Constructor
	 *
	 * @param string           $version
	 * @param Settings_Manager $settings_manager
	 * @param Database_Manager $database_manager
	 */
	public function __construct( $version, Settings_Manager $settings_manager, Database_Manager $database_manager ) {
		$this->version = $version;
		$this->settings_manager = $settings_manager;
		$this->database_manager = $database_manager;

		$this->register_hooks();
	}

	/**
	 * Register AJAX hooks
	 */
	private function register_hooks(): void {
		// Cost calculation (frontend)
		add_action( 'wp_ajax_nopriv_calculate_cost', array( $this, 'ajax_calculate_cost' ) );
		add_action( 'wp_ajax_calculate_cost', array( $this, 'ajax_calculate_cost' ) );

		// Quote submission (frontend)
		add_action( 'wp_ajax_nopriv_submit_quote_request', array( $this, 'ajax_submit_quote_request' ) );
		add_action( 'wp_ajax_submit_quote_request', array( $this, 'ajax_submit_quote_request' ) );

		// Interaction tracking (frontend)
		add_action( 'wp_ajax_nopriv_track_interaction', array( $this, 'ajax_track_interaction' ) );
		add_action( 'wp_ajax_track_interaction', array( $this, 'ajax_track_interaction' ) );

		// Admin-only quote management
		add_action( 'wp_ajax_delete_quote_request', array( $this, 'ajax_delete_quote_request' ) );
		add_action( 'wp_ajax_update_quote_status', array( $this, 'ajax_update_quote_status' ) );

		// Settings import/export (admin)
		add_action( 'wp_ajax_export_settings', array( $this, 'ajax_export_settings' ) );
		add_action( 'wp_ajax_import_settings', array( $this, 'ajax_import_settings' ) );

		// System status tests (admin)
		add_action( 'wp_ajax_send_test_email', array( $this, 'ajax_send_test_email' ) );
		add_action( 'wp_ajax_test_webhook', array( $this, 'ajax_test_webhook' ) );
	}


}
