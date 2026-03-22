<?php
/**
 * Trait Data_IO
 * Handles settings export and import via AJAX
 *
 * @package WP_Configurator_Wizard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait Data_IO {

	/**
	 * AJAX handler for exporting settings
	 */
	public function ajax_export_settings() {
		// Check nonce and capabilities
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp_configurator_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed' ) );
		}

		// Prevent caching
		nocache_headers();
		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
		header( 'Pragma: no-cache' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		}

		// Get current settings
		$options = $this->settings_manager->get_options();

		// Prepare export data
		$export_data = array(
			'version'   => $this->version,
			'exported'  => current_time( 'mysql' ),
			'categories' => $options['categories'] ?? array(),
			'features'   => $options['features'] ?? array(),
			'settings'   => $options['settings'] ?? array(),
		);

		// Generate filename
		$filename = 'wp-configurator-export-' . date( 'Y-m-d-H-i-s' ) . '.json';

		// Set headers for file download
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// Output JSON
		echo json_encode( $export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

		wp_die();
	}

	/**
	 * AJAX handler for importing settings
	 */
	public function ajax_import_settings() {
		// Check nonce and capabilities
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp_configurator_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed' ) );
		}

		// Prevent caching
		nocache_headers();
		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
		header( 'Pragma: no-cache' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		}

		// Get import data
		$import_data_raw = isset( $_POST['import_data'] ) ? stripslashes( $_POST['import_data'] ) : '';
		$import_data = json_decode( $import_data_raw, true );

		if ( ! $import_data || ! is_array( $import_data ) ) {
			wp_send_json_error( array( 'message' => 'Invalid import data. Please upload a valid JSON file.' ) );
		}

		// Check required fields exist
		if ( empty( $import_data['categories'] ) && empty( $import_data['features'] ) && empty( $import_data['settings'] ) ) {
			wp_send_json_error( array( 'message' => 'Import file contains no data to import.' ) );
		}

		// Get current options
		$current_options = $this->settings_manager->get_options();
		$changes = array();

		// Import categories
		if ( ! empty( $_POST['import_categories'] ) && ! empty( $import_data['categories'] ) ) {
			// Sanitize and import
			$sanitized_categories = $this->settings_manager->sanitize_categories( $import_data['categories'] );
			$current_options['categories'] = $sanitized_categories;
			$changes['categories'] = count( $sanitized_categories );
		}

		// Import features
		if ( ! empty( $_POST['import_features'] ) && ! empty( $import_data['features'] ) ) {
			// Sanitize and import
			$sanitized_features = $this->settings_manager->sanitize_features( $import_data['features'] );
			$current_options['features'] = $sanitized_features;
			$changes['features'] = count( $sanitized_features );
		}

		// Import settings
		if ( ! empty( $_POST['import_settings'] ) && ! empty( $import_data['settings'] ) ) {
			// Sanitize and merge/update settings
			$sanitized_settings = $this->settings_manager->sanitize_settings( $import_data['settings'] );
			if ( ! isset( $current_options['settings'] ) ) {
				$current_options['settings'] = array();
			}
			$current_options['settings'] = array_merge( $current_options['settings'], $sanitized_settings );
			$changes['settings'] = count( $sanitized_settings );
		}

		// Save updated options
		update_option( 'wp_configurator_options', $current_options );

		// Build success message
		$message_parts = array();
		if ( isset( $changes['categories'] ) ) {
			$message_parts[] = $changes['categories'] . ' categories';
		}
		if ( isset( $changes['features'] ) ) {
			$message_parts[] = $changes['features'] . ' features';
		}
		if ( isset( $changes['settings'] ) ) {
			$message_parts[] = $changes['settings'] . ' settings';
		}
		$message = 'Imported: ' . implode( ', ', $message_parts );

		wp_send_json_success( array(
			'message' => $message,
			'changes' => $changes,
		) );
	}
}
