<?php
/**
 * Trait Quote_Management
 * Handles AJAX quote request submission, status updates, and deletion
 *
 * @package WP_Configurator_Wizard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait Quote_Management {

	/**
	 * AJAX handler for submitting quote requests
	 */
	public function ajax_submit_quote_request() {
		// Check nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp_configurator_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed' ) );
		}

		// Prevent caching
		nocache_headers();
		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
		header( 'Pragma: no-cache' );

		// Get and sanitize data
		$name = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';
		$business = isset( $_POST['business'] ) ? sanitize_text_field( $_POST['business'] ) : '';
		$email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
		$phone = isset( $_POST['phone'] ) ? sanitize_text_field( $_POST['phone'] ) : '';
		$selected_items = isset( $_POST['selected_items'] ) ? $_POST['selected_items'] : array();
		$totals = isset( $_POST['totals'] ) ? $_POST['totals'] : array();

		// Debug logging
		error_log( 'Quote request received: name=' . $name . ', email=' . $email . ', selected_items count=' . ( is_array( $selected_items ) ? count( $selected_items ) : 'not array' ) );

		// Validate required fields
		if ( empty( $name ) || empty( $email ) ) {
			wp_send_json_error( array( 'message' => 'Name and email are required' ) );
		}

		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => 'Invalid email address' ) );
		}

		// Ensure selected_items is an array
		if ( ! is_array( $selected_items ) ) {
			$selected_items = array();
		}

		// Sanitize selected items and totals for storage
		$sanitized_items = array();
		foreach ( $selected_items as $item ) {
			if ( is_array( $item ) ) {
				$sanitized_items[] = array(
					'id'           => sanitize_text_field( $item['id'] ?? '' ),
					'name'         => sanitize_text_field( $item['name'] ?? '' ),
					'price'        => floatval( $item['price'] ?? 0 ),
					'icon'         => sanitize_text_field( $item['icon'] ?? '' ),
					'category_id'  => sanitize_text_field( $item['category_id'] ?? '' ),
					'billing_type' => sanitize_text_field( $item['billing_type'] ?? 'one-off' ),
					'sku'          => sanitize_text_field( $item['sku'] ?? '' ),
				);
			}
		}

		$sanitized_totals = array();
		foreach ( (array) $totals as $key => $value ) {
			$sanitized_totals[ $key ] = floatval( $value );
		}

		// Store in database
		global $wpdb;
		$table_name = $this->database_manager->get_quote_requests_table();

		// Ensure table exists (fallback)
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) != $table_name ) {
			Database_Manager::activate();
		}

		$result = $wpdb->insert(
			$table_name,
			array(
				'name'               => $name,
				'business'           => $business,
				'email'              => $email,
				'phone'              => $phone,
				'items'              => wp_json_encode( $sanitized_items ),
				'totals'             => wp_json_encode( $sanitized_totals ),
				'created_at'         => current_time( 'mysql' ),
				'ip_address'         => $_SERVER['REMOTE_ADDR'] ?? '',
				'user_agent'         => $_SERVER['HTTP_USER_AGENT'] ?? '',
				'status'             => 'pending',
				'admin_email_sent'   => 0,
				'client_email_sent'  => 0,
				'webhook_sent'       => 0,
				'webhook_response'   => null,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s' )
		);

		if ( $result === false ) {
			error_log( 'Quote request insert failed: ' . $wpdb->last_error );
			wp_send_json_error( array( 'message' => 'Database error' ) );
		}

		$request_id = $wpdb->insert_id;

		// Send notifications (emails and webhook) asynchronously/after successful insert
		$this->send_notifications( $request_id, $sanitized_items, $sanitized_totals, $name, $email, $business, $phone );

		wp_send_json_success( array(
			'message' => 'Quote request submitted successfully',
			'request_id' => $request_id,
		) );
	}

	/**
	 * Send email and webhook notifications for a quote request
	 *
	 * @param int    $request_id
	 * @param array  $items
	 * @param array  $totals
	 * @param string $name
	 * @param string $email
	 * @param string $business
	 * @param string $phone
	 */
	private function send_notifications( $request_id, $items, $totals, $name, $email, $business, $phone ) {
		$options = $this->settings_manager->get_options();
		$settings = $options['settings'] ?? array();

		$admin_email = $settings['notification_email'] ?? '';
		$webhook_url = $settings['webhook_url'] ?? '';
		$send_client_email = ! empty( $settings['send_client_email'] );
		$client_message = $settings['client_message'] ?? '';

		$admin_sent = 0;
		$client_sent = 0;
		$webhook_sent = 0;
		$webhook_response = null;

		// Send admin email if configured
		if ( $admin_email && is_email( $admin_email ) ) {
			$admin_sent = $this->send_admin_email( $admin_email, $request_id, $name, $email, $business, $phone, $items, $totals );
		}

		// Send client email if enabled and client email is valid
		if ( $send_client_email && is_email( $email ) ) {
			$client_sent = $this->send_client_email( $email, $name, $client_message, $request_id, $items, $totals, $business );
		}

		// Fire webhook if configured
		if ( $webhook_url ) {
			list( $webhook_sent, $webhook_response ) = $this->send_webhook( $request_id, $name, $email, $business, $phone, $items, $totals );
		}

		// Update database with sent statuses
		global $wpdb;
		$table_name = $this->database_manager->get_quote_requests_table();

		$wpdb->update(
			$table_name,
			array(
				'admin_email_sent'   => $admin_sent,
				'client_email_sent'  => $client_sent,
				'webhook_sent'       => $webhook_sent,
				'webhook_response'   => $webhook_response,
			),
			array( 'id' => $request_id ),
			array( '%d', '%d', '%d', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Send admin notification email
	 *
	 * @param string $to_email
	 * @param int    $request_id
	 * @param string $name
	 * @param string $email
	 * @param string $business
	 * @param string $phone
	 * @param array  $items
	 * @param array  $totals
	 * @return int 1 if sent, 0 if failed
	 */
	private function send_admin_email( $to_email, $request_id, $name, $email, $business, $phone, $items, $totals ) {
		$subject = sprintf( '[%s] New Quote Request #%d', get_bloginfo( 'name' ), $request_id );

		$message = "A new quote request has been submitted through your configurator wizard.\n\n";
		$message .= "Request ID: #{$request_id}\n";
		$message .= "Submitted: " . current_time( 'mysql' ) . "\n\n";
		$message .= "Client Details:\n";
		$message .= "Name: {$name}\n";
		$message .= "Email: {$email}\n";
		$message .= "Business: {$business}\n";
		$message .= "Phone: {$phone}\n\n";
		$message .= "Selected Items:\n";

		foreach ( $items as $item ) {
			$message .= sprintf( "- %s (%s): %s%s\n", $item['name'], $item['billing_type'], '€', number_format( $item['price'], 2 ) );
		}

		$message .= "\nTotals:\n";
		foreach ( $totals as $key => $value ) {
			$message .= sprintf( "%s: %s%s\n", ucfirst( str_replace( '_', ' ', $key ) ), '€', number_format( $value, 2 ) );
		}

		$message .= "\nYou can manage this quote request in your WordPress admin: " . admin_url( 'admin.php?page=wp-configurator' ) . "\n";

		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

		$sent = wp_mail( $to_email, $subject, $message, $headers );

		if ( ! $sent ) {
			error_log( "Failed to send admin email for quote request #{$request_id}" );
		}

		return $sent ? 1 : 0;
	}

	/**
	 * Send client confirmation email
	 *
	 * @param string $to_email
	 * @param string $name
	 * @param string $client_message
	 * @param int    $request_id
	 * @param array  $items
	 * @param array  $totals
	 * @param string $business
	 * @return int 1 if sent, 0 if failed
	 */
	private function send_client_email( $to_email, $name, $client_message, $request_id, $items, $totals, $business ) {
		$subject = sprintf( '[%s] Your Quote Request #%d - Confirmation', get_bloginfo( 'name' ), $request_id );

		// Replace placeholder in client message
		$personalized_message = str_replace( '{{name}}', $name, $client_message );

		// Site info
		$site_name = get_bloginfo( 'name' );
		$site_url = home_url();

		// Build beautiful HTML email with inline CSS
		$message = '<!DOCTYPE html>';
		$message .= '<html lang="en">';
		$message .= '<head>';
		$message .= '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
		$message .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
		$message .= '<title>' . esc_html( $subject ) . '</title>';
		$message .= '</head>';
		$message .= '<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; background-color: #f5f7fa; color: #333;">';

		// Email container
		$message .= '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #f5f7fa; padding: 40px 20px;">';
		$message .= '<tr><td align="center">';

		// Main email card
		$message .= '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); overflow: hidden;">';

		// Header with brand color
		$message .= '<tr>';
		$message .= '<td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 30px; text-align: center;">';
		$message .= '<h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700; letter-spacing: -0.5px;">' . esc_html( $site_name ) . '</h1>';
		$message .= '<p style="margin: 10px 0 0 0; color: rgba(255,255,255,0.9); font-size: 16px;">Quote Confirmation</p>';
		$message .= '</td>';
		$message .= '</tr>';

		// Content section
		$message .= '<tr>';
		$message .= '<td style="padding: 40px 30px;">';

		// Personalized message
		$message .= '<div style="background-color: #f8fafc; border-left: 4px solid #667eea; padding: 20px; margin-bottom: 30px; border-radius: 0 8px 8px 0;">';
		$message .= '<p style="margin: 0; font-size: 16px; line-height: 1.7; color: #4a5568;">' . nl2br( esc_html( $personalized_message ) ) . '</p>';
		$message .= '</div>';

		// Configuration details heading
		$message .= '<h2 style="margin: 0 0 20px 0; font-size: 20px; color: #2d3748; font-weight: 600;">Your Configuration Details</h2>';

		// Items table
		$message .= '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom: 30px;">';
		$message .= '<thead>';
		$message .= '<tr>';
		$message .= '<th style="background-color: #edf2f7; padding: 14px 16px; text-align: left; font-size: 13px; font-weight: 600; color: #718096; text-transform: uppercase; letter-spacing: 0.5px; border-top: 2px solid #667eea;">Feature</th>';
		$message .= '<th style="background-color: #edf2f7; padding: 14px 16px; text-align: left; font-size: 13px; font-weight: 600; color: #718096; text-transform: uppercase; letter-spacing: 0.5px; border-top: 2px solid #667eea;">Billing</th>';
		$message .= '<th style="background-color: #edf2f7; padding: 14px 16px; text-align: right; font-size: 13px; font-weight: 600; color: #718096; text-transform: uppercase; letter-spacing: 0.5px; border-top: 2px solid #667eea;">Price</th>';
		$message .= '</tr>';
		$message .= '</thead>';
		$message .= '<tbody>';

		foreach ( $items as $item ) {
			$price_display = '€' . number_format( $item['price'], 2 );
			$message .= '<tr>';
			$message .= '<td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0; font-size: 15px; color: #2d3748;">' . esc_html( $item['name'] ) . '</td>';
			$message .= '<td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0; font-size: 14px; color: #718096;">' . esc_html( $item['billing_type'] ) . '</td>';
			$message .= '<td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0; text-align: right; font-size: 15px; font-weight: 600; color: #2d3748;">' . $price_display . '</td>';
			$message .= '</tr>';
		}

		$message .= '</tbody>';
		$message .= '</table>';

		// Summary section
		$message .= '<h2 style="margin: 0 0 15px 0; font-size: 20px; color: #2d3748; font-weight: 600;">Summary</h2>';
		$message .= '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom: 30px;">';

		foreach ( $totals as $key => $value ) {
			$formatted_value = '€' . number_format( $value, 2 );
			$label = ucfirst( str_replace( '_', ' ', $key ) );
			$message .= '<tr>';
			$message .= '<td style="padding: 10px 0; font-size: 15px; color: #4a5568;"><strong>' . esc_html( $label ) . '</strong></td>';
			$message .= '<td style="padding: 10px 0; text-align: right; font-size: 18px; font-weight: 700; color: #667eea;">' . $formatted_value . '</td>';
			$message .= '</tr>';
		}

		$message .= '</table>';

		// Call to action / footer
		$message .= '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">';
		$message .= '<tr>';
		$message .= '<td style="background-color: #f7fafc; padding: 25px; border-radius: 8px; text-align: center; border: 1px dashed #cbd5e0;">';
		$message .= '<p style="margin: 0 0 15px 0; font-size: 15px; color: #4a5568;">We will prepare your formal quote and send it within <strong>2 business days</strong>.</p>';
		$message .= '<a href="' . esc_url( $site_url ) . '" style="display: inline-block; background-color: #667eea; color: #ffffff; padding: 14px 32px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 15px;">Visit Our Site</a>';
		$message .= '</td>';
		$message .= '</tr>';
		$message .= '</table>';

		$message .= '</td>';
		$message .= '</tr>';

		// Footer
		$message .= '<tr>';
		$message .= '<td style="background-color: #edf2f7; padding: 30px; text-align: center; border-top: 1px solid #e2e8f0;">';
		$message .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #718096;">Best regards,</p>';
		$message .= '<p style="margin: 0; font-size: 16px; font-weight: 600; color: #2d3748;">' . esc_html( $site_name ) . '</p>';
		$message .= '<p style="margin: 15px 0 0 0; font-size: 12px; color: #a0aec0;">' . esc_html( $site_url ) . '</p>';
		$message .= '</td>';
		$message .= '</tr>';

		$message .= '</table>'; // Close main card
		$message .= '</td></tr>'; // Close center cell and row
		$message .= '</table>'; // Close outer container

		$message .= '</body></html>';

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		$sent = wp_mail( $to_email, $subject, $message, $headers );

		if ( ! $sent ) {
			error_log( "Failed to send client email for quote request #{$request_id}" );
		}

		return $sent ? 1 : 0;
	}

	/**
	 * Send webhook notification
	 *
	 * @param int    $request_id
	 * @param string $name
	 * @param string $email
	 * @param string $business
	 * @param string $phone
	 * @param array  $items
	 * @param array  $totals
	 * @return array [0 => success flag (0/1), 1 => response message]
	 */
	private function send_webhook( $request_id, $name, $email, $business, $phone, $items, $totals ) {
		$options = $this->settings_manager->get_options();
		$webhook_url = $options['settings']['webhook_url'] ?? '';

		if ( ! $webhook_url || ! is_email( $email ) ) {
			return array( 0, 'Webhook URL not configured or invalid email' );
		}

		$payload = array(
			'request_id'     => $request_id,
			'name'           => $name,
			'business'       => $business,
			'email'          => $email,
			'phone'          => $phone,
			'selected_items' => $items,
			'totals'         => $totals,
			'timestamp'      => current_time( 'mysql' ),
			'source'         => 'wp-configurator-wizard',
		);

		$response = wp_remote_post( $webhook_url, array(
			'method'  => 'POST',
			'body'    => wp_json_encode( $payload ),
			'headers' => array( 'Content-Type' => 'application/json' ),
			'timeout' => 15,
		) );

		if ( is_wp_error( $response ) ) {
			$error_msg = $response->get_error_message();
			error_log( "Webhook failed for quote request #{$request_id}: {$error_msg}" );
			return array( 0, $error_msg );
		} else {
			$response_body = wp_remote_retrieve_body( $response );
			$response_code = wp_remote_retrieve_response_code( $response );
			$response_msg = "HTTP {$response_code}: {$response_body}";
			return array( 1, $response_msg );
		}
	}

	/**
	 * AJAX handler for updating quote request status (admin only)
	 */
	public function ajax_update_quote_status() {
		// Check nonce and capabilities
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp_configurator_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		}

		$request_id = isset( $_POST['request_id'] ) ? intval( $_POST['request_id'] ) : 0;
		$new_status = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : '';

		if ( ! $request_id || ! $new_status ) {
			wp_send_json_error( array( 'message' => 'Invalid request ID or status' ) );
		}

		$valid_statuses = array( 'pending', 'quoted', 'confirmed', 'invoiced', 'cancelled', 'rejected' );
		if ( ! in_array( $new_status, $valid_statuses, true ) ) {
			wp_send_json_error( array( 'message' => 'Invalid status value' ) );
		}

		global $wpdb;
		$table_name = $this->database_manager->get_quote_requests_table();

		$result = $wpdb->update(
			$table_name,
			array( 'status' => $new_status ),
			array( 'id' => $request_id ),
			array( '%s' ),
			array( '%d' )
		);

		if ( $result === false ) {
			wp_send_json_error( array( 'message' => 'Database error: ' . $wpdb->last_error ) );
		}

		wp_send_json_success( array( 'message' => 'Status updated to ' . $new_status ) );
	}

	/**
	 * AJAX handler for deleting quote requests (admin only)
	 */
	public function ajax_delete_quote_request() {
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

		$request_id = isset( $_POST['request_id'] ) ? intval( $_POST['request_id'] ) : 0;
		if ( ! $request_id ) {
			wp_send_json_error( array( 'message' => 'Invalid request ID' ) );
		}

		global $wpdb;
		$table_name = $this->database_manager->get_quote_requests_table();
		$result = $wpdb->delete( $table_name, array( 'id' => $request_id ), array( '%d' ) );

		if ( $result === false ) {
			wp_send_json_error( array( 'message' => 'Failed to delete request' ) );
		}

		wp_send_json_success( array( 'message' => 'Request deleted' ) );
	}

	/**
	 * AJAX handler for sending test email
	 */
	public function ajax_send_test_email() {
		// Check nonce and capabilities
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp_configurator_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		}

		// Get test email address from settings if available, otherwise use the passed email
		$options = $this->settings_manager->get_options();
		$test_email = $options['settings']['test_email_address'] ?? '';
		$to_email = $test_email ? $test_email : ( isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '' );

		if ( ! $to_email || ! is_email( $to_email ) ) {
			wp_send_json_error( array( 'message' => 'No test email address configured. Please set a Test Email Address in Miscellaneous settings, or provide an email address.' ) );
		}

		// Send a sample client email with dummy data to test formatting
		$sent = $this->send_test_client_email( $to_email );

		if ( $sent ) {
			wp_send_json_success( array( 'message' => "Test email sent successfully to {$to_email}. This is a sample client email with dummy data showing the actual email template formatting." ) );
		} else {
			wp_send_json_error( array( 'message' => 'Failed to send test email. Check your SMTP configuration (Post SMTP plugin logs) and server error logs.' ) );
		}
	}

	/**
	 * Send a test client email with dummy data to preview the template
	 *
	 * @param string $to_email
	 * @return int 1 if sent, 0 if failed
	 */
	private function send_test_client_email( $to_email ) {
		$site_name = get_bloginfo( 'name' );
		$site_url = home_url();
		$request_id = 999999; // Dummy ID for test

		$subject = sprintf( '[%s] Your Quote Request #%d - Confirmation', $site_name, $request_id );

		// Dummy personalized message (from settings or default)
		$client_message = "Many thanks {{name}} for requesting your formal quote. Here is a copy of what you have sent us. If we need any further information we will get in touch. In the meantime we will prepare your quote and send for your consideration in the next 2 business days.";
		$personalized_message = str_replace( '{{name}}', 'Test Customer', $client_message );

		// Build beautiful HTML email with inline CSS (same as real client email)
		$message = '<!DOCTYPE html>';
		$message .= '<html lang="en">';
		$message .= '<head>';
		$message .= '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
		$message .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
		$message .= '<title>' . esc_html( $subject ) . '</title>';
		$message .= '</head>';
		$message .= '<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; background-color: #f5f7fa; color: #333;">';

		// Email container
		$message .= '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #f5f7fa; padding: 40px 20px;">';
		$message .= '<tr><td align="center">';

		// Main email card
		$message .= '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); overflow: hidden;">';

		// Header with brand color
		$message .= '<tr>';
		$message .= '<td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 30px; text-align: center;">';
		$message .= '<h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700; letter-spacing: -0.5px;">' . esc_html( $site_name ) . '</h1>';
		$message .= '<p style="margin: 10px 0 0 0; color: rgba(255,255,255,0.9); font-size: 16px;">Quote Confirmation (TEST EMAIL)</p>';
		$message .= '</td>';
		$message .= '</tr>';

		// Content section
		$message .= '<tr>';
		$message .= '<td style="padding: 40px 30px;">';

		// Notice that this is a test email
		$message .= '<div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin-bottom: 20px; border-radius: 0 8px 8px 0;">';
		$message .= '<p style="margin: 0; font-size: 14px; color: #856404;"><strong>TEST EMAIL:</strong> This is a sample email to verify your SMTP configuration. The data below is dummy data for preview purposes.</p>';
		$message .= '</div>';

		// Personalized message
		$message .= '<div style="background-color: #f8fafc; border-left: 4px solid #667eea; padding: 20px; margin-bottom: 30px; border-radius: 0 8px 8px 0;">';
		$message .= '<p style="margin: 0; font-size: 16px; line-height: 1.7; color: #4a5568;">' . nl2br( esc_html( $personalized_message ) ) . '</p>';
		$message .= '</div>';

		// Configuration details heading
		$message .= '<h2 style="margin: 0 0 20px 0; font-size: 20px; color: #2d3748; font-weight: 600;">Your Configuration Details</h2>';

		// Items table with dummy data
		$message .= '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom: 30px;">';
		$message .= '<thead>';
		$message .= '<tr>';
		$message .= '<th style="background-color: #edf2f7; padding: 14px 16px; text-align: left; font-size: 13px; font-weight: 600; color: #718096; text-transform: uppercase; letter-spacing: 0.5px; border-top: 2px solid #667eea;">Feature</th>';
		$message .= '<th style="background-color: #edf2f7; padding: 14px 16px; text-align: left; font-size: 13px; font-weight: 600; color: #718096; text-transform: uppercase; letter-spacing: 0.5px; border-top: 2px solid #667eea;">Billing</th>';
		$message .= '<th style="background-color: #edf2f7; padding: 14px 16px; text-align: right; font-size: 13px; font-weight: 600; color: #718096; text-transform: uppercase; letter-spacing: 0.5px; border-top: 2px solid #667eea;">Price</th>';
		$message .= '</tr>';
		$message .= '</thead>';
		$message .= '<tbody>';

		// Dummy items
		$dummy_items = array(
			array( 'name' => 'Basic Website (5 pages)', 'billing_type' => 'one-off', 'price' => 50.00 ),
			array( 'name' => 'E-commerce Store', 'billing_type' => 'one-off', 'price' => 299.00 ),
			array( 'name' => 'SEO Optimization', 'billing_type' => 'monthly', 'price' => 99.00 ),
			array( 'name' => 'Priority Support', 'billing_type' => 'monthly', 'price' => 49.00 ),
		);

		foreach ( $dummy_items as $item ) {
			$price_display = '€' . number_format( $item['price'], 2 );
			$message .= '<tr>';
			$message .= '<td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0; font-size: 15px; color: #2d3748;">' . esc_html( $item['name'] ) . '</td>';
			$message .= '<td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0; font-size: 14px; color: #718096;">' . esc_html( $item['billing_type'] ) . '</td>';
			$message .= '<td style="padding: 14px 16px; border-bottom: 1px solid #e2e8f0; text-align: right; font-size: 15px; font-weight: 600; color: #2d3748;">' . $price_display . '</td>';
			$message .= '</tr>';
		}

		$message .= '</tbody>';
		$message .= '</table>';

		// Summary section with dummy totals
		$message .= '<h2 style="margin: 0 0 15px 0; font-size: 20px; color: #2d3748; font-weight: 600;">Summary</h2>';
		$message .= '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom: 30px;">';

		$dummy_totals = array(
			'one_time_total' => 349.00,
			'monthly_equivalent' => 148.00,
			'quarterly_equivalent' => 444.00,
			'annual_equivalent' => 1776.00,
		);

		foreach ( $dummy_totals as $key => $value ) {
			$formatted_value = '€' . number_format( $value, 2 );
			$label = ucfirst( str_replace( '_', ' ', $key ) );
			$message .= '<tr>';
			$message .= '<td style="padding: 10px 0; font-size: 15px; color: #4a5568;"><strong>' . esc_html( $label ) . '</strong></td>';
			$message .= '<td style="padding: 10px 0; text-align: right; font-size: 18px; font-weight: 700; color: #667eea;">' . $formatted_value . '</td>';
			$message .= '</tr>';
		}

		$message .= '</table>';

		// Call to action / footer
		$message .= '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">';
		$message .= '<tr>';
		$message .= '<td style="background-color: #f7fafc; padding: 25px; border-radius: 8px; text-align: center; border: 1px dashed #cbd5e0;">';
		$message .= '<p style="margin: 0 0 15px 0; font-size: 15px; color: #4a5568;">We will prepare your formal quote and send it within <strong>2 business days</strong>.</p>';
		$message .= '<a href="' . esc_url( $site_url ) . '" style="display: inline-block; background-color: #667eea; color: #ffffff; padding: 14px 32px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 15px;">Visit Our Site</a>';
		$message .= '</td>';
		$message .= '</tr>';
		$message .= '</table>';

		$message .= '</td>';
		$message .= '</tr>';

		// Footer
		$message .= '<tr>';
		$message .= '<td style="background-color: #edf2f7; padding: 30px; text-align: center; border-top: 1px solid #e2e8f0;">';
		$message .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #718096;">Best regards,</p>';
		$message .= '<p style="margin: 0; font-size: 16px; font-weight: 600; color: #2d3748;">' . esc_html( $site_name ) . '</p>';
		$message .= '<p style="margin: 15px 0 0 0; font-size: 12px; color: #a0aec0;">' . esc_html( $site_url ) . '</p>';
		$message .= '</td>';
		$message .= '</tr>';

		$message .= '</table>'; // Close main card
		$message .= '</td></tr>'; // Close center cell and row
		$message .= '</table>'; // Close outer container

		$message .= '</body></html>';

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		$sent = wp_mail( $to_email, $subject, $message, $headers );

		if ( ! $sent ) {
			error_log( "Failed to send test client email to {$to_email}" );
		}

		return $sent ? 1 : 0;
	}

	/**
	 * AJAX handler for testing webhook
	 */
	public function ajax_test_webhook() {
		// Check nonce and capabilities
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp_configurator_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions' ) );
		}

		$webhook_url = isset( $_POST['webhook_url'] ) ? esc_url_raw( $_POST['webhook_url'] ) : '';
		if ( ! $webhook_url || ! filter_var( $webhook_url, FILTER_VALIDATE_URL ) ) {
			wp_send_json_error( array( 'message' => 'Invalid webhook URL' ) );
		}

		$payload = array(
			'test'          => true,
			'message'       => 'Test webhook from WP Configurator Wizard',
			'site'          => home_url(),
			'timestamp'     => current_time( 'mysql' ),
			'php_version'   => PHP_VERSION,
			'user_agent'    => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
		);

		$response = wp_remote_post( $webhook_url, array(
			'method'  => 'POST',
			'body'    => wp_json_encode( $payload ),
			'headers' => array( 'Content-Type' => 'application/json' ),
			'timeout' => 15,
		) );

		if ( is_wp_error( $response ) ) {
			$error_msg = $response->get_error_message();
			wp_send_json_error( array( 'message' => "Webhook test failed: {$error_msg}" ) );
		} else {
			$response_body = wp_remote_retrieve_body( $response );
			$response_code = wp_remote_retrieve_response_code( $response );
			wp_send_json_success( array(
				'message'  => "Webhook test successful! HTTP {$response_code}",
				'response' => $response_body,
			) );
		}
	}
}
