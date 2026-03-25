<?php
/**
 * Trait Interaction_Tracking
 * Handles tracking user interactions via AJAX
 *
 * @package WP_Configurator_Wizard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait Interaction_Tracking {

	/**
	 * AJAX handler for tracking interactions
	 */
	public function ajax_track_interaction() {
		// Debug: log that we're called
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'track_interaction called. POST: ' . print_r( $_POST, true ) );
		}

		// Check nonce (optional, but good for frontend)
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp_configurator_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed' ) );
		}

		// Prevent caching
		nocache_headers();
		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
		header( 'Pragma: no-cache' );

		$event_type = isset( $_POST['event_type'] ) ? sanitize_text_field( $_POST['event_type'] ) : '';
		$feature_id = isset( $_POST['feature_id'] ) ? sanitize_text_field( $_POST['feature_id'] ) : null;
		$category_id = isset( $_POST['category_id'] ) ? sanitize_text_field( $_POST['category_id'] ) : null;
		$metadata = isset( $_POST['metadata'] ) ? $_POST['metadata'] : array();
		$session_id = isset( $_POST['session_id'] ) ? sanitize_text_field( $_POST['session_id'] ) : '';

		// Ensure metadata is an array
		if ( ! is_array( $metadata ) ) {
			$metadata = array();
		}

		// Generate session ID if not provided (use IP + User Agent hash as fallback)
		if ( empty( $session_id ) ) {
			$ip = $_SERVER['REMOTE_ADDR'] ?? '';
			$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
			$session_id = md5( $ip . $ua . time() );
		}

		global $wpdb;
		$table_name = $this->database_manager->get_interactions_table();

		// Debug: log table name and existence
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Using table: ' . $table_name );
			error_log( 'Table exists check: ' . ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) ?: 'no' ) );
		}

		// Ensure table exists
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) != $table_name ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Table missing, creating...' );
			}
			$this->database_manager->ensure_interactions_table_exists();
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Table creation attempted. Now exists: ' . ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) ? 'yes' : 'no' ) );
			}
		}

		// Check if this IP should be excluded (admin setting)
		$options = $this->settings_manager->get_options();
		$exclude_admin_ip = ! empty( $options['settings']['exclude_admin_ip'] );
		$admin_ip = ! empty( $options['settings']['admin_ip_address'] ) ? trim( $options['settings']['admin_ip_address'] ) : '';
		$visitor_ip = $_SERVER['REMOTE_ADDR'] ?? '';

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( "Admin IP check: excluded=$exclude_admin_ip, admin_ip='$admin_ip', visitor_ip='$visitor_ip'" );
		}

		if ( $exclude_admin_ip && $admin_ip && $visitor_ip === $admin_ip ) {
			// Silently ignore this interaction
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Interaction excluded due to admin IP match' );
			}
			wp_send_json_success( array( 'message' => 'Interaction excluded (admin IP)' ) );
			return;
		}

		// Check if this is a bot (skip tracking if enabled)
		$exclude_bots = ! empty( $options['settings']['exclude_bot_user_agents'] );
		$bot_patterns = ! empty( $options['settings']['bot_user_agents'] ) ? explode( "\n", $options['settings']['bot_user_agents'] ) : array();

		if ( $exclude_bots && ! empty( $bot_patterns ) ) {
			$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
			foreach ( $bot_patterns as $pattern ) {
				$pattern = trim( $pattern );
				if ( $pattern !== '' && stripos( $user_agent, $pattern ) !== false ) {
					// Silently ignore bot interaction
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( "Interaction excluded due to bot detection: $pattern" );
					}
					wp_send_json_success( array( 'message' => 'Bot interaction excluded' ) );
					return;
				}
			}
		}

		// Debug: about to insert
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Attempting insert into ' . $table_name . ' with data: event=' . $event_type . ', feature=' . $feature_id . ', category=' . $category_id );
		}

		$result = $wpdb->insert(
			$table_name,
			array(
				'session_id'   => $session_id,
				'event_type'   => $event_type,
				'feature_id'   => $feature_id,
				'category_id'  => $category_id,
				'metadata'     => wp_json_encode( $metadata ),
				'ip_address'   => $_SERVER['REMOTE_ADDR'] ?? '',
				'user_agent'   => $_SERVER['HTTP_USER_AGENT'] ?? '',
				'created_at'   => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		// Debug: insert result
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Insert result: ' . ( $result === false ? 'FALSE' : 'true' ) . ( $result === false ? ' Error: ' . $wpdb->last_error : ' ID: ' . $wpdb->insert_id ) );
			error_log( 'Rows affected: ' . $wpdb->rows_affected );
		}

		if ( $result === false ) {
			error_log( 'Interaction tracking failed: ' . $wpdb->last_error );
			wp_send_json_error( array( 'message' => 'Database error' ) );
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Interaction tracked successfully, inserted ID: ' . $wpdb->insert_id );
		}

		// If this is a feature_added event, check if this session already has an initial_engagement
		// and log one if not
		if ( $event_type === 'feature_added' && $result ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( "Checking for existing initial_engagement for session $session_id" );
			}
			$has_initial_engagement = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM $table_name WHERE session_id = %s AND event_type = %s",
				$session_id, 'initial_engagement'
			) );
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( "Initial_engagement count: " . ( $has_initial_engagement ?: 0 ) );
			}
			if ( ! $has_initial_engagement ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( "Inserting initial_engagement for session $session_id" );
				}
				$insert_result = $wpdb->insert(
					$table_name,
					array(
						'session_id'   => $session_id,
						'event_type'   => 'initial_engagement',
						'feature_id'   => $feature_id,
						'category_id'  => $category_id,
						'metadata'     => wp_json_encode( array( 'triggered_by' => 'feature_added' ) ),
						'ip_address'   => $_SERVER['REMOTE_ADDR'] ?? '',
						'user_agent'   => $_SERVER['HTTP_USER_AGENT'] ?? '',
						'created_at'   => current_time( 'mysql' ),
					),
					array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
				);
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'Initial engagement insert result: ' . ( $insert_result ? 'success' : 'FAIL' ) . ( $insert_result === false ? ' Error: ' . $wpdb->last_error : '' ) );
				}
			}
		}

		wp_send_json_success( array( 'message' => 'Interaction tracked' ) );
	}
}
