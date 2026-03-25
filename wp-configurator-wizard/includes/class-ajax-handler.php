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
	 * System Status View instance
	 *
	 * @var System_Status_View
	 */
	private $system_status_view;

	/**
	 * Constructor
	 *
	 * @param string             $version
	 * @param Settings_Manager   $settings_manager
	 * @param Database_Manager   $database_manager
	 * @param System_Status_View $system_status_view
	 */
	public function __construct( $version, Settings_Manager $settings_manager, Database_Manager $database_manager, System_Status_View $system_status_view ) {
		$this->version = $version;
		$this->settings_manager = $settings_manager;
		$this->database_manager = $database_manager;
		$this->system_status_view = $system_status_view;

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
		add_action( 'wp_ajax_send_test_admin_email', array( $this, 'ajax_send_test_admin_email' ) );
		add_action( 'wp_ajax_test_webhook', array( $this, 'ajax_test_webhook' ) );
		add_action( 'wp_ajax_sync_donors', array( $this, 'ajax_sync_donors' ) );
	}

	/**
	 * AJAX handler for syncing donors from GitHub
	 */
	public function ajax_sync_donors() {
		// Debug: log that we're here
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'wp-configurator: ajax_sync_donors called' );
		}

		try {
			// Verify nonce (matches wp_localize_script action 'wp_configurator_nonce')
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp_configurator_nonce' ) ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'wp-configurator: Nonce verification failed' );
				}
				wp_send_json_error( array( 'message' => 'Security check failed.' ) );
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'wp-configurator: Insufficient permissions' );
				}
				wp_send_json_error( array( 'message' => 'Insufficient permissions.' ), 403 );
			}

			if ( ! isset( $this->system_status_view ) ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'wp-configurator: system_status_view is not set!' );
				}
				wp_send_json_error( array( 'message' => 'System status view not initialized.' ) );
			}

			$result = $this->system_status_view->sync_donors_from_github();
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'wp-configurator: Sync result: ' . print_r( $result, true ) );
			}
			wp_send_json( $result );
		} catch ( Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'wp-configurator: Exception in ajax_sync_donors: ' . $e->getMessage() . '\n' . $e->getTraceAsString() );
			}
			wp_send_json_error( array( 'message' => 'Server error: ' . $e->getMessage() ) );
		}
	}

}
