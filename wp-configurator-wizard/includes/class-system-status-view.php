<?php
/**
 * System Status View class
 * Renders the system health check tab
 *
 * @package WP_Configurator_Wizard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class System_Status_View {

	/**
	 * Settings manager instance
	 *
	 * @var Settings_Manager
	 */
	private $settings_manager;

	/**
	 * Plugin version
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Constructor
	 *
	 * @param Settings_Manager $settings_manager
	 * @param string           $version
	 */
	public function __construct( Settings_Manager $settings_manager, $version ) {
		$this->settings_manager = $settings_manager;
		$this->version = $version;
	}

	/**
	 * Render the system status tab
	 */
	public function render(): void {
		// Get cached results or run fresh checks
		$checks = $this->run_system_checks();

		// Refresh button
		echo '<div style="margin-bottom: 12px;">';
		echo '<button type="button" class="button button-secondary" id="refresh-system-status">';
		esc_html_e( 'Refresh Checks', 'wp-configurator' );
		echo '</button>';
		echo '<span class="description" style="margin-left: 12px;">Last checked: ' . current_time( 'mysql' ) . '</span>';
		echo '</div>';

		// Define check groups in logical order
		$groups = array(
			'core'       => 'Core Components',
			'caching'    => 'Caching & Performance',
			'comms'      => 'Communications',
			'env'        => 'Environment',
			'analytics'  => 'Analytics',
			'donors'     => 'Donors Management',
			'data'       => 'Data Integrity',
		);

		// Group checks by category
		$grouped_checks = array_fill_keys( array_keys( $groups ), array() );
		foreach ( $checks as $key => $check ) {
			switch ( $key ) {
				case 'database_tables':
				case 'plugin_version':
				case 'github_release':
				case 'dashboard_widget':
					$grouped_checks['core'][] = $check;
					break;
				case 'caching_plugins':
				case 'server_cache':
				case 'js_versioning':
					$grouped_checks['caching'][] = $check;
					break;
				case 'email_config':
				case 'webhook_config':
					$grouped_checks['comms'][] = $check;
					break;
				case 'php_version':
				case 'mysql_version':
				case 'wp_debug':
					$grouped_checks['env'][] = $check;
					break;
				case 'admin_ip_exclusion':
				case 'remote_http':
					$grouped_checks['analytics'][] = $check;
					break;
				case 'donors_sync':
					$grouped_checks['donors'][] = $check;
					break;
				case 'data_integrity':
					$grouped_checks['data'][] = $check;
					break;
				case 'interaction_purge':
					$grouped_checks['data'][] = $check;
					break;
				default:
					// Fallback: put in core
					$grouped_checks['core'][] = $check;
			}
		}

		// Calculate health summary
		$summary = array( 'success' => 0, 'warning' => 0, 'error' => 0, 'info' => 0 );
		foreach ( $checks as $check ) {
			$status = $check['status'];
			if ( isset( $summary[ $status ] ) ) {
				$summary[ $status ]++;
			}
		}

		// Output health summary bar
		echo '<div class="system-status-summary">';
		echo '<strong>Overall Health:</strong> ';
		echo '<span class="system-status-summary-item system-status-summary-item--success">✓ ' . $summary['success'] . ' passing</span> ';
		echo '<span class="system-status-summary-item system-status-summary-item--warning">⚠ ' . $summary['warning'] . ' warning</span> ';
		echo '<span class="system-status-summary-item system-status-summary-item--error">✕ ' . $summary['error'] . ' error</span> ';
		echo '<span class="system-status-summary-item system-status-summary-item--info">ℹ ' . $summary['info'] . ' info</span>';
		echo '</div>';

		// Output grouped cards
		foreach ( $groups as $group_key => $group_label ) {
			$checks_in_group = $grouped_checks[ $group_key ];
			if ( empty( $checks_in_group ) ) {
				continue;
			}

			// Group heading
			echo '<div class="system-status-group">';
			echo '<h3 class="system-status-group-title">' . esc_html( $group_label ) . '</h3>';

			// Cards grid
			echo '<div class="system-status-cards-grid">';

			foreach ( $checks_in_group as $check ) {
				$status = $check['status'];
				$card_class = 'system-status-card system-status-card--' . esc_attr( $status );

				echo '<div class="' . $card_class . '">';

				// Card header with icon and title
				echo '<div class="system-status-card-header">';
				echo '<span class="system-status-card-icon system-status-icon system-status-' . esc_attr( $status ) . '">' . $this->get_status_symbol( $status ) . '</span>';
				echo '<h4 class="system-status-card-title">' . esc_html( $check['label'] ) . '</h4>';
				echo '</div>';

				// Card body
				echo '<div class="system-status-card-body">';
				echo wp_kses_post( $check['description'] );
				echo '</div>';

				// Card actions (if any)
				if ( ! empty( $check['action'] ) ) {
					echo '<div class="system-status-card-actions">';
					echo $check['action']; // Action HTML is pre-escaped in run_system_checks()
					echo '</div>';
				}

				echo '</div>'; // .system-status-card
			}

			echo '</div>'; // .settings-cards-grid
			echo '</div>'; // .system-status-group
		}

		// Inline JavaScript for copy buttons and test functionality
		?>
		<script>
			jQuery(function($) {
				// Add copy buttons to code snippets in actions
				$('.wp-configurator-tab-content.active .system-status-pre').each(function() {
					var $pre = $(this);
					var $btn = $('<button class="system-status-copy-btn">Copy</button>');
					$pre.append($btn);

					$btn.on('click', function() {
						var text = $pre.find('code').text() || $pre.text();
						navigator.clipboard.writeText(text).then(function() {
							$btn.text('Copied!').addClass('copied');
							setTimeout(function() {
								$btn.text('Copy').removeClass('copied');
							}, 2000);
						}).catch(function(err) {
							console.error('Copy failed:', err);
							$btn.text('Failed');
						});
					});
				});

				// Refresh button
				$('#refresh-system-status').on('click', function() {
					location.reload();
				});

				// Test Client Email button
				window.sendTestClientEmail = function(email) {
					if ( ! confirm( 'Send test client email to: ' + email + '?' ) ) {
						return;
					}

					var button = event.target;
					button.disabled = true;
					button.textContent = 'Sending...';

					jQuery.post(ajaxurl, {
						action: 'send_test_email',
						email: email,
						nonce: wpConfiguratorAdmin.exportNonce
					})
					.done(function(response) {
						button.disabled = false;
						button.textContent = 'Send Test Client Email';
						console.log('Email test response:', response);
						if ( response && response.success ) {
							alert( 'Success: ' + (response.data ? response.data.message : 'Unknown success') );
						} else {
							var msg = response && response.data && response.data.message ? response.data.message : 'Unknown error';
							alert( 'Error: ' + msg );
						}
					})
					.fail(function(xhr, status, error) {
						button.disabled = false;
						button.textContent = 'Send Test Client Email';
						console.error('AJAX error:', status, error, xhr.responseText);
						alert( 'AJAX error: ' + error + '\n\nCheck console for details.' );
					});
				};

				// Test Admin Email button
				window.sendTestAdminEmail = function(email) {
					if ( ! confirm( 'Send test admin email to: ' + email + '?' ) ) {
						return;
					}

					var button = event.target;
					button.disabled = true;
					button.textContent = 'Sending...';

					jQuery.post(ajaxurl, {
						action: 'send_test_admin_email',
						email: email,
						nonce: wpConfiguratorAdmin.exportNonce
					})
					.done(function(response) {
						button.disabled = false;
						button.textContent = 'Send Test Admin Email';
						console.log('Admin email test response:', response);
						if ( response && response.success ) {
							alert( 'Success: ' + (response.data ? response.data.message : 'Unknown success') );
						} else {
							var msg = response && response.data && response.data.message ? response.data.message : 'Unknown error';
							alert( 'Error: ' + msg );
						}
					})
					.fail(function(xhr, status, error) {
						button.disabled = false;
						button.textContent = 'Send Test Admin Email';
						console.error('AJAX error:', status, error, xhr.responseText);
						alert( 'AJAX error: ' + error + '\n\nCheck console for details.' );
					});
				};

				// Test Webhook button
				window.sendTestWebhook = function(webhookUrl) {
					if ( ! confirm( 'Send test webhook to: ' + webhookUrl + '?' ) ) {
						return;
					}

					var button = event.target;
					button.disabled = true;
					button.textContent = 'Testing...';

					jQuery.post(ajaxurl, {
						action: 'test_webhook',
						webhook_url: webhookUrl,
						nonce: wpConfiguratorAdmin.exportNonce
					})
					.done(function(response) {
						button.disabled = false;
						button.textContent = 'Test Webhook';
						console.log('Webhook test response:', response);
						if ( response && response.success ) {
							var msg = response.data ? response.data.message : 'Success';
							if ( response.data && response.data.response ) {
								msg += '\n\nResponse body:\n' + response.data.response;
							}
							alert( 'Success:\n\n' + msg );
						} else {
							var msg = response && response.data && response.data.message ? response.data.message : 'Unknown error';
							alert( 'Error: ' + msg );
						}
					})
					.fail(function(xhr, status, error) {
						button.disabled = false;
						button.textContent = 'Test Webhook';
						console.error('AJAX error:', status, error, xhr.responseText);
						alert( 'AJAX error: ' + error + '\n\nCheck console for details.' );
					});
				};

				// Sync Donors from GitHub
				window.syncDonorsFromGitHub = function() {
					if ( ! confirm( 'Sync donors list from GitHub? This will replace the current list.' ) ) {
						return;
					}

					var button = event.target;
					button.disabled = true;
					button.textContent = 'Syncing...';

					jQuery.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'sync_donors',
							nonce: wpConfiguratorAdmin.exportNonce
						},
						dataType: 'json'
					})
					.done(function(response) {
						button.disabled = false;
						button.textContent = 'Sync Now';
						console.log('Sync response raw:', response);
						if ( response && response.success ) {
							alert( 'Success: ' + (response.data && response.data.message ? response.data.message : 'Sync completed') );
							// Reload to update status
							location.reload();
						} else {
							var msg = response && response.data && response.data.message ? response.data.message : 'Unknown error response';
							alert( 'Error: ' + msg );
						}
					})
					.fail(function(xhr, status, error) {
						button.disabled = false;
						button.textContent = 'Sync Now';
						console.error('AJAX error:', status, error);
						console.log('Response text:', xhr.responseText);
						alert( 'AJAX error: ' + error + '\n\nCheck console for details.' );
					});
				};

				// Force GitHub Release Check
				window.forceGithubCheck = function() {
					var button = event.target;
					var originalText = button.textContent;
					button.disabled = true;
					button.textContent = 'Checking...';

					jQuery.post(ajaxurl, {
						action: 'wp_configurator_force_github_check',
						nonce: wpConfiguratorAdmin.forceCheckNonce
					}, function(response) {
						button.disabled = false;
						button.textContent = originalText;

						if ( response && response.success ) {
							var data = response.data;
							// Show alert with result
							alert( 'GitHub Check: ' + data.message + '\n\nCurrent: v' + data.current_version + '\nLatest: v' + data.latest_version );

							// Update the card in place (optional: could reload page)
							// For simplicity, just reload after short delay
							setTimeout(function() {
								location.reload();
							}, 1000);
						} else {
							var msg = response && response.data && response.data.message ? response.data.message : 'Unknown error';
							alert( 'Error: ' + msg );
						}
					})
					.fail(function(xhr, status, error) {
						button.disabled = false;
						button.textContent = originalText;
						console.error('AJAX error:', status, error, xhr.responseText);
						alert( 'AJAX error: ' + error + '\n\nCheck console for details.' );
					});

					return false;
				};

				// Interaction Data Purge
				window.previewInteractionPurge = function() {
					var from = $('#purge-date-from').val();
					var to = $('#purge-date-to').val();
					var paramType = $('#purge-param-type').val();
					var paramValue = $('#purge-param-value').val();

					$('#purge-results').hide().html('<p>⏳ Loading...</p>').show();
					$('#purge-error').hide();

					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'preview_interaction_purge',
							nonce: wpConfiguratorAdmin.exportNonce,
							date_from: from,
							date_to: to,
							param_type: paramType,
							param_value: paramValue
						},
						dataType: 'json'
					})
					.done(function(response) {
						if (response.success) {
							var data = response.data;
							var html = '<div style="background: #fff; padding: 12px; border: 1px solid #ccc; border-radius: 4px;">';
							html += '<p><strong>✅ ' + data.count + ' interaction events matched</strong></p>';
							if (data.count > 0) {
								html += '<p>Breakdown by event type:</p><ul style="margin: 8px 0; padding-left: 20px;">';
								$.each(data.by_event_type, function(event, count) {
									html += '<li>' + event + ': ' + count + '</li>';
								});
								html += '</ul>';
								html += '<p style="color: #d63638; font-weight: bold;">⚠️ Deleting these records is permanent and cannot be undone.</p>';
								html += '<button type="button" class="button" id="purge-execute-btn" data-count="' + data.count + '" style="background:#dc3232; border-color:#dc3232; color:#fff; text-decoration:none;">Delete These ' + data.count + ' Events</button>';
							}
							html += '</div>';
							$('#purge-results').html(html);

							// Bind execute button
							$('#purge-execute-btn').on('click', window.executeInteractionPurge);
						} else {
							$('#purge-results').html('<p style="color: #d63638;">❌ ' + response.data.message + '</p>');
						}
					})
					.fail(function() {
						$('#purge-results').hide();
						$('#purge-error').text('Request failed. Please try again.').show();
					});

					return false;
				};

				window.executeInteractionPurge = function() {
					var $btn = $('#purge-execute-btn');
					var count = $btn.data('count');
					if (!confirm('Delete ' + count + ' interaction events? This cannot be undone.')) {
						return;
					}

					$btn.prop('disabled', true).text('Deleting...');

					var from = $('#purge-date-from').val();
					var to = $('#purge-date-to').val();
					var paramType = $('#purge-param-type').val();
					var paramValue = $('#purge-param-value').val();

					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'execute_interaction_purge',
							nonce: wpConfiguratorAdmin.exportNonce,
							date_from: from,
							date_to: to,
							param_type: paramType,
							param_value: paramValue
						},
						dataType: 'json'
					})
					.done(function(resp) {
						if (resp.success) {
							$('#purge-results').html('<p style="color: green;">✅ Successfully deleted ' + resp.data.deleted_count + ' events.</p>');
						} else {
							$('#purge-results').html('<p style="color: red;">❌ Error: ' + resp.data.message + '</p>');
							$btn.prop('disabled', false).text('Delete These ' + count + ' Events');
						}
					})
					.fail(function() {
						$('#purge-results').html('<p style="color: red;">❌ Request failed. Please try again.</p>');
						$btn.prop('disabled', false).text('Delete These ' + count + ' Events');
					});

					return false;
				};

				// Bind preview button
				$('#purge-preview-btn').on('click', window.previewInteractionPurge);

			});
		</script>
		<?php
	}

	/**
	 * Get status symbol for icon
	 *
	 * @param string $status Status key
	 * @return string Unicode symbol
	 */
	private function get_status_symbol( $status ) {
		switch ( $status ) {
			case 'success': return '✓';
			case 'warning': return '⚠';
			case 'error':   return '✕';
			case 'info':    return 'i';
			default:        return '?';
		}
	}

	/**
	 * Get donors list from GitHub (or fallback to local file)
	 *
	 * @return array List of donor names
	 */
	public function get_donors() {
		// Try option first (synced from GitHub)
		$donors = get_option( 'wp_configurator_donors_list', null );
		if ( $donors !== null && is_array( $donors ) ) {
			return $donors;
		}

		// Fallback to local file
		$plugin_dir = plugin_dir_path( WP_CONFIGURATOR_WIZARD_FILE );
		$donors_file = $plugin_dir . 'donors.txt';
		if ( file_exists( $donors_file ) && is_readable( $donors_file ) ) {
			$content = file_get_contents( $donors_file );
			$donors = array_filter( array_map( 'trim', explode( "\n", $content ) ) );
			return $donors;
		}

		return array();
	}

	/**
	 * Sync donors from GitHub
	 *
	 * @return array Success status and message
	 */
	public function sync_donors_from_github() {
		try {
			// Note: donors.txt is inside the plugin directory in the repo
			$url = 'https://raw.githubusercontent.com/buttonsbond/atp-configurator/main/wp-configurator-wizard/donors.txt';
			$response = wp_remote_get( $url, array(
				'timeout' => 10,
				'user-agent' => 'WP-Configurator-Wizard/1.0',
			) );

			if ( is_wp_error( $response ) ) {
				return array(
					'success' => false,
					'message' => 'Failed to fetch from GitHub: ' . $response->get_error_message(),
				);
			}

			$code = wp_remote_retrieve_response_code( $response );
			if ( $code !== 200 ) {
				return array(
					'success' => false,
					'message' => "GitHub returned HTTP $code",
				);
			}

			$body = wp_remote_retrieve_body( $response );
			if ( empty( $body ) ) {
				return array(
					'success' => false,
					'message' => 'GitHub returned empty content',
				);
			}

			// Parse donors (one per line)
			$donors = array_filter( array_map( 'trim', explode( "\n", $body ) ) );

			// Update option
			update_option( 'wp_configurator_donors_list', $donors );

			// Record sync timestamp
			update_option( 'wp_configurator_donors_last_sync', current_time( 'timestamp' ) );

			return array(
				'success' => true,
				'message' => sprintf( 'Successfully synced %d donors from GitHub.', count( $donors ) ),
				'count'   => count( $donors ),
			);
		} catch ( Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Donors sync error: ' . $e->getMessage() );
			}
			return array(
				'success' => false,
				'message' => 'Sync failed: ' . $e->getMessage(),
			);
		}
	}

	/**
	 * Get donors sync status
	 *
	 * @return array Status info
	 */
	public function get_donors_sync_status() {
		$last_sync = get_option( 'wp_configurator_donors_last_sync', false );
		$source = get_option( 'wp_configurator_donors_list', null ) !== null ? 'GitHub (synced)' : 'Local file';
		$status = 'info';

		if ( $last_sync ) {
			$age = round( ( current_time( 'timestamp' ) - $last_sync ) / 3600 / 24, 1 );
			if ( $age > 7 ) {
				$status = 'warning';
			}
		}

		return array(
			'source'  => $source,
			'last_sync' => $last_sync ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $last_sync ) : 'Never',
			'status'  => $status,
		);
	}

	/**
	 * Render Interaction Data Purge form
	 *
	 * @return string HTML form
	 */
	private function render_interaction_purge_form(): string {
		// Use current date range defaults: last 30 days
		$to_date = current_time( 'Y-m-d' );
		$from_date = date_i18n( 'Y-m-d', strtotime( '-30 days' ) );

		// URL parameter types we track
		$param_types = array(
			'utm_source'   => 'UTM Source',
			'utm_medium'   => 'UTM Medium',
			'utm_campaign' => 'UTM Campaign',
			'webURL'       => 'Web URL',
			'botID'        => 'Bot ID',
		);

		ob_start();
		?>
		<div class="interaction-purge-form" style="background: #f9f9f9; padding: 12px; border-radius: 6px; border: 1px solid #ddd;">
			<p style="margin-top: 0;"><strong>Filter by date range (optional):</strong></p>
			<div style="display: flex; gap: 12px; margin-bottom: 12px; align-items: center; flex-wrap: wrap;">
				<label>
					From: <input type="date" id="purge-date-from" value="<?php echo esc_attr( $from_date ); ?>" style="width: 140px;">
				</label>
				<label>
					To: <input type="date" id="purge-date-to" value="<?php echo esc_attr( $to_date ); ?>" style="width: 140px;">
				</label>
			</div>

			<p><strong>Filter by URL Parameter (optional):</strong></p>
			<div style="display: flex; gap: 12px; margin-bottom: 12px; align-items: center; flex-wrap: wrap;">
				<label>
					Parameter:
					<select id="purge-param-type" style="min-width: 160px;">
						<option value="">-- Select Parameter --</option>
						<?php foreach ( $param_types as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?> (<?php echo esc_html( $key ); ?>)</option>
						<?php endforeach; ?>
					</select>
				</label>
				<label>
					Value: <input type="text" id="purge-param-value" placeholder="e.g., facebook" style="width: 200px;">
				</label>
			</div>

			<button type="button" class="button button-secondary" id="purge-preview-btn">Preview Matches</button>

			<div id="purge-results" style="margin-top: 16px; display: none;">
				<!-- Preview results will appear here -->
			</div>

			<div id="purge-error" style="margin-top: 12px; color: #dc3232; display: none;"></div>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Schedule weekly cron if not already scheduled
	 */
	public function schedule_donors_sync_cron() {
		if ( ! wp_next_scheduled( 'wp_configurator_weekly_donors_sync' ) ) {
			wp_schedule_event( time(), 'weekly', 'wp_configurator_weekly_donors_sync' );
		}
	}

	/**
	 * Perform weekly donors sync (cron callback)
	 */
	public function cron_sync_donors() {
		$result = $this->sync_donors_from_github();
		// Log result for debugging
		if ( ! $result['success'] ) {
			error_log( '[WP Configurator] Weekly donors sync failed: ' . $result['message'] );
		}
	}

	/**
	 * AJAX preview interaction purge matches
	 */
	public function ajax_preview_interaction_purge() {
		global $wpdb;

		// Verify nonce and capability
		check_ajax_referer( 'wp_configurator_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions', 403 );
		}

		$date_from   = isset( $_POST['date_from'] ) ? sanitize_text_field( $_POST['date_from'] ) : '';
		$date_to     = isset( $_POST['date_to'] ) ? sanitize_text_field( $_POST['date_to'] ) : '';
		$param_type  = isset( $_POST['param_type'] ) ? sanitize_text_field( $_POST['param_type'] ) : '';
		$param_value = isset( $_POST['param_value'] ) ? sanitize_text_field( $_POST['param_value'] ) : '';

		$table = $wpdb->prefix . 'configurator_interactions';
		$where = array( '1=1' );
		$where_params = array();

		// Date range filter
		if ( $date_from ) {
			$where[] = 'created_at >= %s';
			$where_params[] = $date_from . ' 00:00:00';
		}
		if ( $date_to ) {
			$where[] = 'created_at <= %s';
			$where_params[] = $date_to . ' 23:59:59';
		}

		// URL parameter filter
		if ( $param_type && $param_value !== '' ) {
			$where[] = $wpdb->prepare( "JSON_UNQUOTE(JSON_EXTRACT(metadata, %s)) = %s", '$.url_params.' . $param_type, $param_value );
		}

		$where_sql = implode( ' AND ', $where );

		// Count total matches
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table WHERE $where_sql",
				$where_params
			)
		);

		if ( ! $count ) {
			wp_send_json_success( array(
				'count' => 0,
				'by_event_type' => array(),
			) );
		}

		// Get breakdown by event_type
		$breakdown = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT event_type, COUNT(*) as cnt FROM $table WHERE $where_sql GROUP BY event_type ORDER BY cnt DESC",
				$where_params
			),
			ARRAY_A
		);

		$by_event_type = array();
		foreach ( $breakdown as $row ) {
			$by_event_type[ $row['event_type'] ] = (int) $row['cnt'];
		}

		wp_send_json_success( array(
			'count'        => (int) $count,
			'by_event_type' => $by_event_type,
		) );
	}

	/**
	 * AJAX execute interaction purge (delete)
	 */
	public function ajax_execute_interaction_purge() {
		global $wpdb;

		// Verify nonce and capability
		check_ajax_referer( 'wp_configurator_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions', 403 );
		}

		$date_from   = isset( $_POST['date_from'] ) ? sanitize_text_field( $_POST['date_from'] ) : '';
		$date_to     = isset( $_POST['date_to'] ) ? sanitize_text_field( $_POST['date_to'] ) : '';
		$param_type  = isset( $_POST['param_type'] ) ? sanitize_text_field( $_POST['param_type'] ) : '';
		$param_value = isset( $_POST['param_value'] ) ? sanitize_text_field( $_POST['param_value'] ) : '';

		$table = $wpdb->prefix . 'configurator_interactions';
		$where = array( '1=1' );
		$where_params = array();

		// Date range filter
		if ( $date_from ) {
			$where[] = 'created_at >= %s';
			$where_params[] = $date_from . ' 00:00:00';
		}
		if ( $date_to ) {
			$where[] = 'created_at <= %s';
			$where_params[] = $date_to . ' 23:59:59';
		}

		// URL parameter filter
		if ( $param_type && $param_value !== '' ) {
			$where[] = $wpdb->prepare( "JSON_UNQUOTE(JSON_EXTRACT(metadata, %s)) = %s", '$.url_params.' . $param_type, $param_value );
		}

		$where_sql = implode( ' AND ', $where );

		// Delete matching records
		$deleted_count = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $table WHERE $where_sql",
				$where_params
			)
		);

		if ( false === $deleted_count ) {
			wp_send_json_error( array(
				'message' => 'Database error occurred. Please check logs.',
			) );
		}

		wp_send_json_success( array(
			'deleted_count' => $deleted_count,
		) );
	}

	/**
	 * Check GitHub for latest release
	 *
	 * @return array Status info: up_to_date (bool), latest_version (string), current_version (string), message (string), action (string)
	 */
	public function check_github_release() {
		$current_version = $this->version;
		$transient_key = 'wp_configurator_github_release_check';
		$cached = get_transient( $transient_key );

		// Use cached result if fresh (12 hours) AND it contains a non-empty action (i.e., has the Force Check button)
		// This ensures we invalidate old caches from before the Force Check button was added
		if ( false !== $cached && is_array( $cached ) && ! empty( $cached['action'] ) ) {
			return $cached;
		}

		// Default response for errors
		$default_response = array(
			'up_to_date'     => true,
			'latest_version' => $current_version,
			'current_version' => $current_version,
			'message'        => 'Unable to check GitHub releases. Will retry later.',
			'action'         => '',
			'status'         => 'info',
		);

		// Fetch latest release from GitHub
		$url = 'https://api.github.com/repos/buttonsbond/atp-configurator/releases/latest';
		$response = wp_remote_get( $url, array(
			'timeout'     => 10,
			'user-agent'  => 'WP-Configurator-Wizard/1.0',
		) );

		if ( is_wp_error( $response ) ) {
			$default_response['status'] = 'warning';
			set_transient( $transient_key, $default_response, 12 * HOUR_IN_SECONDS );
			return $default_response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code !== 200 ) {
			$default_response['status'] = 'warning';
			$default_response['message'] = "GitHub API returned HTTP $code. Unable to check for updates.";
			set_transient( $transient_key, $default_response, 12 * HOUR_IN_SECONDS );
			return $default_response;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( empty( $data['tag_name'] ) ) {
			$default_response['status'] = 'warning';
			$default_response['message'] = 'Invalid response from GitHub. Could not parse release version.';
			set_transient( $transient_key, $default_response, 12 * HOUR_IN_SECONDS );
			return $default_response;
		}

		// Extract version from tag (remove 'v' prefix if present)
		$latest_version = ltrim( $data['tag_name'], 'v' );
		$release_url = $data['html_url'] ?? "https://github.com/buttonsbond/atp-configurator/releases/latest";

		// Compare versions
		$is_dev_version = strpos( $current_version, '-dev' ) !== false;
		$is_newer = version_compare( $current_version, $latest_version, '>' );
		$is_equal = version_compare( $current_version, $latest_version, '==' );
		$is_up_to_date = $is_equal || ( $is_newer && $is_dev_version );

		$result = array(
			'up_to_date'      => $is_up_to_date,
			'latest_version'  => $latest_version,
			'current_version' => $current_version,
			'release_url'     => $release_url,
		);

		// Determine status and message based on comparison
		$force_check_btn = '<button type="button" class="button button-small" onclick="forceGithubCheck()">Force Check</button>';

		if ( $is_newer && ! $is_dev_version ) {
			// Current version is strictly newer than GitHub's latest, and it's not marked as -dev
			// This means we're working ahead of releases
			$result['status']  = 'info';
			$result['message'] = "Working on Dev. Version (v$current_version)";
			$result['action']  = $force_check_btn;
		} elseif ( $is_up_to_date ) {
			// Current version equals or is older but dev version is acceptable
			$result['status']  = 'success';
			$result['message'] = "Plugin is up to date (v$current_version)";
			$result['action']  = $force_check_btn;
		} else {
			// Current version is older than GitHub's latest
			$result['status']  = 'warning';
			$result['message'] = "New release v$latest_version available on GitHub";
			$result['action']  = $force_check_btn . ' <a href="' . esc_url( $release_url ) . '" target="_blank" rel="noopener noreferrer" class="button button-small">View Release</a>';
		}

		// Cache for 12 hours
		set_transient( $transient_key, $result, 12 * HOUR_IN_SECONDS );

		return $result;
	}

	/**
	 * Convert php.ini memory value to bytes
	 *
	 * @param string $value Memory limit string (e.g., "256M", "1G")
	 * @return int|false Bytes or false on parse failure
	 */
	private function memory_to_bytes( $value ) {
		if ( is_numeric( $value ) ) {
			return (int) $value;
		}

		$value = trim( $value );
		$last_char = strtolower( substr( $value, -1 ) );
		$num = (int) $value;

		switch ( $last_char ) {
			case 'g': $num *= 1024;
			// Intentional fallthrough
			case 'm': $num *= 1024;
			// Intentional fallthrough
			case 'k': $num *= 1024;
		}

		return $num;
	}

	/**
	 * Run all system health checks
	 *
	 * @return array Array of check results with keys: label, status, description, action (optional)
	 */
	private function run_system_checks() {
		global $wpdb;
		$checks = array();
		$active_plugins = get_option( 'active_plugins', array() );
		$options = $this->settings_manager->get_options();

		// 1. Database tables exist
		$interactions_table = $wpdb->prefix . 'configurator_interactions';
		$quote_requests_table = $wpdb->prefix . 'configurator_quote_requests';

		$interactions_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $interactions_table ) ) === $interactions_table;
		$quote_requests_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $quote_requests_table ) ) === $quote_requests_table;

		$checks['database_tables'] = array(
			'status'      => ( $interactions_exists && $quote_requests_exists ) ? 'success' : 'error',
			'label'       => 'Database Tables',
			'description' => $interactions_exists && $quote_requests_exists
				? 'All required tables exist (configurator_interactions, configurator_quote_requests).'
				: 'Missing database tables. Deactivate and reactivate the plugin to create them.',
			'action'      => ! $interactions_exists || ! $quote_requests_exists
				? '<button class="button button-small" onclick="alert(\'Please deactivate and reactivate the plugin to create missing tables.\');">Create Tables</button>'
				: '',
		);

		// 2. Caching plugins detection with specific instructions
		$caching_plugins = array(
			'wp-rocket/wp-rocket.php' => array(
				'name' => 'WP Rocket',
				'instructions' => '<strong>WP Rocket:</strong><br>Settings → Page Optimization → Exclusions → "Never Cache URLs" → add:<br><code>/wp-admin/admin-ajax.php</code><br><code>/wp-content/plugins/wp-configurator-wizard/</code><br><br>Also disable "Delay JavaScript Execution" for this plugin or add exception.'
			),
			'w3-total-cache/w3-total-cache.php' => array(
				'name' => 'W3 Total Cache',
				'instructions' => '<strong>W3 Total Cache:</strong><br>Performance → Page Cache → Advanced → "Never cache the following pages" → add:<br><code>/wp-admin/admin-ajax.php</code><br><code>/wp-content/plugins/wp-configurator-wizard/</code><br>Or use "Fragment Cache" to exclude AJAX.'
			),
			'autoptimize/autoptimize.php' => array(
				'name' => 'Autoptimize',
				'instructions' => '<strong>Autoptimize:</strong><br>Settings → Autoptimize → JavaScript Options → "Exclude scripts from Autoptimize" → add:<br><code>wp-configurator-wizard.js</code><br>Or exclude the entire plugin directory.'
			),
			'litespeed-cache/litespeed-cache.php' => array(
				'name' => 'LiteSpeed Cache',
				'instructions' => '<strong>LiteSpeed Cache:</strong><br>LiteSpeed Cache → Page Optimization → Tuning → "Do Not Cache URIs" → add:<br><code>/wp-admin/admin-ajax.php</code><br><code>/wp-content/plugins/wp-configurator-wizard/</code>'
			),
			'hummingbird-performance/wp-hummingbird.php' => array(
				'name' => 'Hummingbird',
				'instructions' => '<strong>Hummingbird:</strong><br>Hummingbird → Asset Optimization → Advanced → "Exclude JavaScript" → add:<br><code>wp-configurator-wizard.js</code>'
			),
			'fast-velocity-minify/fvm.php' => array(
				'name' => 'Fast Velocity Minify',
				'instructions' => '<strong>Fast Velocity Minify:</strong><br>Settings → Fast Velocity Minify → "Exclude URLs" → add:<br><code>/wp-content/plugins/wp-configurator-wizard/</code>'
			),
			'cache-enabler/cache-enabler.php' => array(
				'name' => 'Cache Enabler',
				'instructions' => '<strong>Cache Enabler:</strong><br>Settings → Cache Enabler → "Exclude URLs" → add:<br><code>/wp-admin/admin-ajax.php</code><br><code>/wp-content/plugins/wp-configurator-wizard/</code>'
			),
			'comet-cache/comet-cache.php' => array(
				'name' => 'Comet Cache',
				'instructions' => '<strong>Comet Cache:</strong><br>Comet Cache → Advanced → "Never Cache URLs" → add:<br><code>/wp-admin/admin-ajax.php</code><br><code>/wp-content/plugins/wp-configurator-wizard/</code>'
			),
			'hyper-cache/wp-hyper-cache.php' => array(
				'name' => 'Hyper Cache',
				'instructions' => '<strong>Hyper Cache:</strong><br>Settings → Hyper Cache → "Bypass Cache" → add:<br><code>/wp-admin/admin-ajax.php</code><br><code>/wp-content/plugins/wp-configurator-wizard/</code>'
			),
		);
		$found_caching = array_intersect( $active_plugins, array_keys( $caching_plugins ) );

		$caching_message = '';
		$instructions_html = '';
		$status = 'success';
		if ( ! empty( $found_caching ) ) {
			$plugin_names = array();
			foreach ( $found_caching as $plugin_key ) {
				$plugin_names[] = $caching_plugins[ $plugin_key ]['name'];
				$instructions_html .= $caching_plugins[ $plugin_key ]['instructions'] . '<hr style="margin: 12px 0; border: none; border-top: 1px solid #ddd;">';
			}
			$caching_message = 'Caching plugin(s) active: ' . implode( ', ', $plugin_names );
			$status = 'warning';
		} else {
			$caching_message = 'No known caching plugins detected.';
		}

		$checks['caching_plugins'] = array(
			'status'      => $status,
			'label'       => 'Caching Plugins',
			'description' => $caching_message,
			'action'      => $status === 'warning'
				? '<div class="description" style="background: #f9f9f9; padding: 12px; border-radius: 4px; border: 1px solid #ddd;">' . $instructions_html . '</div>'
				: '',
		);

		// 3. Server-side caching detection (Varnish, Pagespeed, LiteSpeed)
		$test_ajax_url = admin_url( 'admin-ajax.php' );
		$test_response = wp_remote_head( $test_ajax_url, array( 'timeout' => 5 ) );
		$headers = is_wp_error( $test_response ) ? array() : wp_remote_retrieve_headers( $test_response )->getAll();

		$varnish_detected = false;
		$pagespeed_detected = false;
		$litespeed_detected = false;
		if ( ! empty( $headers ) ) {
			// Check for Varnish
			if ( isset( $headers['x-varnish'] ) || ( isset( $headers['via'] ) && stripos( implode( ' ', $headers['via'] ), 'varnish' ) !== false ) ) {
				$varnish_detected = true;
			}
			// Check for Pagespeed
			if ( isset( $headers['x-pagespeed'] ) ) {
				$pagespeed_detected = true;
			}
			// Check for LiteSpeed cache
			if ( isset( $headers['x-litespeed-cache'] ) || isset( $headers['x-litespeed-cache-purge'] ) ) {
				$litespeed_detected = true;
			}
		}

		$cache_summary = array();
		if ( $varnish_detected ) {
			$cache_summary[] = 'Varnish detected';
		}
		if ( $pagespeed_detected ) {
			$cache_summary[] = 'Google Pagespeed detected';
		}
		if ( $litespeed_detected ) {
			$cache_summary[] = 'LiteSpeed cache detected';
		}
		if ( empty( $cache_summary ) ) {
			$cache_summary[] = 'No server-side cache detected in headers';
		}

		// Build detailed instructions based on what's detected
		$detailed_instructions = '';
		if ( $varnish_detected ) {
			$detailed_instructions .= '<strong>Varnish Configuration:</strong><br>';
			$detailed_instructions .= 'Add to your VCL (usually in /etc/varnish/default.vcl or via CloudPanel):<br>';
			$detailed_instructions .= '<pre class="system-status-pre"><code>sub vcl_recv {
    if (req.method == "POST" && req.url ~ "^/wp-admin/admin-ajax.php") {
        return (pass);
    }
    if (req.url ~ "^/wp-content/plugins/wp-configurator-wizard/") {
        return (pass);
    }
}</code></pre>';
			$detailed_instructions .= '<p><strong>CloudPanel shortcut:</strong> Add to custom Varnish config:<br><code>if (req.method == "POST" && req.url ~ "^/wp-admin/admin-ajax.php") { return (pass); }</code></p>';
			$detailed_instructions .= '<hr style="margin: 12px 0; border: none; border-top: 1px solid #ddd;">';
		}
		if ( $pagespeed_detected ) {
			$detailed_instructions .= '<strong>Google Pagespeed (mod_pagespeed):</strong><br>';
			$detailed_instructions .= 'Add to your .htaccess (Apache) or Nginx config:<br>';
			$detailed_instructions .= '<pre class="system-status-pre"><code>&lt;IfModule pagespeed_module&gt;
    ModPagespeedDisallow "*/wp-admin/admin-ajax.php"
    ModPagespeedDisallow "*/wp-content/plugins/wp-configurator-wizard/*"
&lt;/IfModule&gt;</code></pre>';
			$detailed_instructions .= '<p><strong>CloudPanel:</strong> Site Settings → Pagespeed → Add exclusion patterns.</p>';
			$detailed_instructions .= '<hr style="margin: 12px 0; border: none; border-top: 1px solid #ddd;">';
		}
		if ( $litespeed_detected ) {
			$detailed_instructions .= '<strong>LiteSpeed Cache:</strong><br>';
			$detailed_instructions .= 'In WordPress admin: LiteSpeed Cache → Cache → Settings → Exclusions:<br>';
			$detailed_instructions .= '<pre class="system-status-pre"><code>/wp-admin/admin-ajax.php
/wp-content/plugins/wp-configurator-wizard/</code></pre>';
			$detailed_instructions .= '<p>Also in "Do Not Cache Cookies" exclude: <code>wp_configurator_session_id</code></p>';
			$detailed_instructions .= '<hr style="margin: 12px 0; border: none; border-top: 1px solid #ddd;">';
		}
		if ( empty( $cache_summary ) || ( ! $varnish_detected && ! $pagespeed_detected && ! $litespeed_detected ) ) {
			$detailed_instructions = 'No server-side cache detected. If you\'re using a reverse proxy or CDN, ensure it bypasses <code>admin-ajax.php</code> POST requests and the plugin\'s asset directories.';
		}

		$checks['server_cache'] = array(
			'status'      => $varnish_detected || $pagespeed_detected || $litespeed_detected ? 'warning' : 'success',
			'label'       => 'Server Cache',
			'description' => implode( '; ', $cache_summary ),
			'action'      => $detailed_instructions ? '<div class="description" style="background: #f9f9f9; padding: 12px; border-radius: 4px; border: 1px solid #ddd; max-height: 300px; overflow-y: auto;">' . $detailed_instructions . '</div>' : '',
		);

		// 4. JavaScript versioning (dynamic filemtime)
		// Check if the wizard.js file exists and uses filemtime for versioning
		$plugin_dir = plugin_dir_path( WP_CONFIGURATOR_WIZARD_FILE );
		$wizard_js_path = $plugin_dir . 'assets/js/wizard.js';
		$wizard_js_exists = file_exists( $wizard_js_path );

		// Check if the enqueue code uses filemtime (by reading the plugin file)
		$uses_filemtime = false;
		if ( $wizard_js_exists && is_readable( $wp_configurator_wizard_php = $plugin_dir . 'wp-configurator-wizard.php' ) ) {
			$plugin_code = file_get_contents( $wp_configurator_wizard_php );
			// Look for the pattern: filemtime( ... wizard.js ... )
			if ( preg_match( '/filemtime\s*\(\s*plugin_dir_path\s*\(\s*WP_CONFIGURATOR_WIZARD_FILE\s*\)\s*\.\s*[\'"]assets\/js\/wizard\.js[\'"]\s*\)/', $plugin_code ) ) {
				$uses_filemtime = true;
			}
		}

		$js_status = ( $wizard_js_exists && $uses_filemtime ) ? 'success' : 'warning';
		$js_desc   = '';
		if ( ! $wizard_js_exists ) {
			$js_desc = 'wizard.js file not found at expected location.';
		} elseif ( ! $uses_filemtime ) {
			$js_desc = 'wizard.js is enqueued with static version. Should use filemtime() for dynamic cache busting.';
		} else {
			$js_desc = 'wizard.js uses dynamic versioning (filemtime). Assets will be reliably busted on update.';
		}

		$checks['js_versioning'] = array(
			'status'      => $js_status,
			'label'       => 'JavaScript Versioning',
			'description' => $js_desc,
			'action'      => $js_status === 'warning'
				? '<p class="description">The plugin should use dynamic versioning via filemtime() for wizard.js to ensure cache busting works after updates.</p>'
				: '',
		);

		// 5. Admin IP Exclusion (settings check)
		$exclude_enabled = ! empty( $options['settings']['exclude_admin_ip'] );
		$admin_ip        = trim( $options['settings']['admin_ip_address'] ?? '' );
		$current_ip      = $_SERVER['REMOTE_ADDR'] ?? '';
		$is_excluded     = $exclude_enabled && $admin_ip && $current_ip === $admin_ip;

		$ip_status = $exclude_enabled && $is_excluded ? 'warning' : 'success';
		$ip_desc  = $exclude_enabled
			? "Admin IP exclusion is enabled. Excluded IP: $admin_ip. Your IP: $current_ip " . ( $is_excluded ? '(EXCLUDED from tracking)' : '(not excluded)' )
			: 'Admin IP exclusion is disabled.';

		$checks['admin_ip_exclusion'] = array(
			'status'      => $ip_status,
			'label'       => 'Admin IP Exclusion',
			'description' => $ip_desc,
			'action'      => $is_excluded
				? '<p class="description">You are currently excluded from interaction tracking. Disable exclusion to test tracking.</p>'
				: '',
		);

		// 5. Remote HTTP Access (cURL/fsockopen)
		$remote_test_url = 'https://api.github.com/zen'; // lightweight, reliable
		$test_response = wp_remote_get( $remote_test_url, array( 'timeout' => 5, 'user-agent' => 'WP-Configurator-Wizard/1.0' ) );
		$remote_ok = ! is_wp_error( $test_response ) && wp_remote_retrieve_response_code( $test_response ) === 200;

		$checks['remote_http'] = array(
			'status'      => $remote_ok ? 'success' : 'warning',
			'label'       => 'Remote HTTP Access',
			'description' => $remote_ok
				? 'Outbound HTTP requests are working (cURL or fsockopen). Required for GitHub sync and webhook delivery.'
				: 'Cannot make outbound HTTP requests. GitHub donors sync and webhook tests may fail. Check firewall or host restrictions.',
			'action'      => '',
		);

		// 5.5. PHP Memory Limit
		$memory_limit = ini_get( 'memory_limit' );
		$memory_bytes = $this->memory_to_bytes( $memory_limit );
		$memory_mb = $memory_bytes ? round( $memory_bytes / 1048576 ) : 0;
		$memory_status = 'success';
		$memory_action = '';
		$memory_desc = "Current limit: {$memory_mb}M";

		if ( $memory_bytes === false || $memory_mb < 128 ) {
			$memory_status = 'error';
			$memory_desc = "Current limit: {$memory_mb}M - Too low for complex configurations.";
			$memory_action = '<div class="description" style="background: #f9f9f9; padding: 12px; border-radius: 4px; border: 1px solid #ddd;">'
				. '<p><strong>Recommended:</strong> 256M or higher</p>'
				. '<p><strong>How to increase:</strong></p>'
				. '<ol style="margin: 8px 0 0 20px; padding: 0;">'
				. '<li>Add to wp-config.php: <code>define(\'WP_MEMORY_LIMIT\', \'256M\');</code></li>'
				. '<li>Or set in php.ini: <code>memory_limit = 256M</code></li>'
				. '<li>Contact your host if you cannot modify these.</li>'
				. '</ol></div>';
		} elseif ( $memory_mb < 256 ) {
			$memory_status = 'warning';
			$memory_desc = "Current limit: {$memory_mb}M - May be insufficient for large configurations.";
			$memory_action = '<div class="description" style="background: #f9f9f9; padding: 12px; border-radius: 4px; border: 1px solid #ddd;">'
				. '<p><strong>Recommended:</strong> 256M or higher for optimal performance.</p>'
				. '<p>Increase via <code>wp-config.php</code>: <code>define(\'WP_MEMORY_LIMIT\', \'256M\');</code></p></div>';
		}

		$checks['memory_limit'] = array(
			'status'      => $memory_status,
			'label'       => 'PHP Memory Limit',
			'description' => $memory_desc,
			'action'      => $memory_action,
		);

		// 6. PHP Version
		$php_version = PHP_VERSION;
		$php_ok      = version_compare( $php_version, '8.0', '>=' );
		$checks['php_version'] = array(
			'status'      => $php_ok ? 'success' : 'warning',
			'label'       => 'PHP Version',
			'description' => $php_version . ( $php_ok ? '' : ' - PHP 8.0+ is recommended.' ),
			'action'      => ! $php_ok ? '<p class="description">Consider upgrading PHP for better performance and security.</p>' : '',
		);

		// 7. MySQL Version
		$mysql_version = $wpdb->db_version();
		$mysql_ok      = version_compare( $mysql_version, '5.7', '>=' ) || ( stripos( $mysql_version, 'mariadb' ) !== false && version_compare( $mysql_version, '10.2', '>=' ) );
		$checks['mysql_version'] = array(
			'status'      => $mysql_ok ? 'success' : 'warning',
			'label'       => 'MySQL Version',
			'description' => "MySQL/MariaDB version: $mysql_version. JSON support required.",
			'action'      => ! $mysql_ok ? '<p class="description">Upgrade to MySQL 5.7+ or MariaDB 10.2+ for full JSON support.</p>' : '',
		);

		// 8. Debug logging
		$debug_enabled = defined( 'WP_DEBUG' ) && WP_DEBUG;
		$checks['wp_debug'] = array(
			'status'      => $debug_enabled ? 'info' : 'success',
			'label'       => 'WordPress Debug',
			'description' => $debug_enabled ? 'WP_DEBUG is enabled. Debug logs are being generated.' : 'WP_DEBUG is disabled.',
			'action'      => '',
		);

		// 9. GitHub Release Check
		$github_check = $this->check_github_release();
		$checks['github_release'] = array(
			'status'      => $github_check['status'],
			'label'       => 'GitHub Update Check',
			'description' => $github_check['message'],
			'action'      => $github_check['action'],
		);

		// 10. Dashboard widget registration (just info)
		$checks['dashboard_widget'] = array(
			'status'      => 'info',
			'label'       => 'Dashboard Widget',
			'description' => 'Interaction stats appear on the Configurator admin dashboard above the tabs.',
			'action'      => '',
		);

		// 11. Email & Webhook Configuration Status
		$admin_email = $options['settings']['notification_email'] ?? '';
		$test_email = $options['settings']['test_email_address'] ?? '';
		$send_client_email = ! empty( $options['settings']['send_client_email'] );
		$webhook_url = $options['settings']['webhook_url'] ?? '';

		$email_status = 'success';
		$email_desc = 'Email notifications are configured. Use the buttons below to send test emails (client and admin formats).';
		$webhook_status = 'success';
		$webhook_desc = 'Webhook is configured.';

		if ( ! $admin_email ) {
			$email_status = 'warning';
			$email_desc = 'Admin notification email is not set. Set it in Miscellaneous settings.';
		} elseif ( ! is_email( $admin_email ) ) {
			$email_status = 'error';
			$email_desc = 'Admin notification email is invalid.';
		}

		if ( $send_client_email && ! is_email( $admin_email ) ) {
			$email_status = 'error';
			$email_desc = 'Client emails enabled but admin email is invalid (needed for sending).';
		}

		if ( $test_email && ! is_email( $test_email ) ) {
			$email_desc .= ' Test email address is invalid.';
		}

		if ( ! $webhook_url ) {
			$webhook_status = 'warning';
			$webhook_desc = 'Webhook URL is not configured. Set it in Miscellaneous settings.';
		} elseif ( ! filter_var( $webhook_url, FILTER_VALIDATE_URL ) ) {
			$webhook_status = 'error';
			$webhook_desc = 'Webhook URL is invalid.';
		}

		// Build action buttons for test functionality
		$action_buttons = '';

		// Test Client Email button (uses test_email if set, otherwise admin email)
		$test_client_target = $test_email ?: $admin_email;
		if ( $test_client_target && is_email( $test_client_target ) ) {
			$action_buttons .= '<button type="button" class="button button-small" onclick="sendTestClientEmail(\'' . esc_js( $test_client_target ) . '\')">Send Test Client Email</button> ';
		}

		// Test Admin Email button (uses admin email)
		if ( $admin_email && is_email( $admin_email ) ) {
			$action_buttons .= '<button type="button" class="button button-small" onclick="sendTestAdminEmail(\'' . esc_js( $admin_email ) . '\')">Send Test Admin Email</button> ';
		}

		if ( $webhook_url && filter_var( $webhook_url, FILTER_VALIDATE_URL ) ) {
			$action_buttons .= '<button type="button" class="button button-small" onclick="sendTestWebhook(\'' . esc_js( $webhook_url ) . '\')">Test Webhook</button>';
		}

		$checks['email_config'] = array(
			'status'      => $email_status,
			'label'       => 'Email Notifications',
			'description' => $email_desc,
			'action'      => $action_buttons ? '<div class="description" style="background: #f9f9f9; padding: 8px; border-radius: 4px; border: 1px solid #ddd;">' . $action_buttons . '</div>' : '',
		);

		$checks['webhook_config'] = array(
			'status'      => $webhook_status,
			'label'       => 'Webhook',
			'description' => $webhook_desc,
			'action'      => '',
		);

		// 13. Donors Sync
		$donors_status = 'info';
		$sync_status = $this->get_donors_sync_status();
		$donors_desc = 'Donors list source: ' . $sync_status['source'] . '. Last sync: ' . $sync_status['last_sync'];

		if ( $sync_status['status'] === 'warning' ) {
			$donors_status = 'warning';
			$donors_desc = 'Donors list has not been synced recently. Source: ' . $sync_status['source'] . '. Last sync: ' . $sync_status['last_sync'];
		}

		$donors_action = '<button type="button" class="button button-small" onclick="syncDonorsFromGitHub()">Sync Now</button>';
		$checks['donors_sync'] = array(
			'status'      => $donors_status,
			'label'       => 'Donors List',
			'description' => $donors_desc,
			'action'      => '<div class="description" style="background: #f9f9f9; padding: 8px; border-radius: 4px; border: 1px solid #ddd;">' . $donors_action . '</div>',
		);

		// 14. Data Integrity Check
		try {
			$integrity = $this->settings_manager->check_data_integrity();
		} catch (Throwable $e) {
			$integrity = [
				'status' => 'error',
				'issues' => ['Fatal error during integrity check: ' . $e->getMessage()],
				'summary' => [
					'total_categories' => 0,
					'total_features' => 0,
				],
				'checked_at' => current_time('mysql'),
			];
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log('WP Configurator: Integrity check fatal error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
			}
		}

		$integrity_status = $integrity['status'] === 'ok' ? 'success' : ( $integrity['status'] === 'error' ? 'error' : 'warning' );
		$integrity_desc = sprintf(
			'Categories: %d | Features: %d | Issues: %d',
			$integrity['summary']['total_categories'],
			$integrity['summary']['total_features'],
			count($integrity['issues'])
		);
		$integrity_action = '';
		if ($integrity['status'] !== 'ok') {
			$issues_list = '<ul style="margin: 8px 0; padding-left: 20px;">';
			foreach (array_slice($integrity['issues'], 0, 10) as $issue) { // Show first 10
				$issues_list .= '<li>' . esc_html($issue) . '</li>';
			}
			if (count($integrity['issues']) > 10) {
				$issues_list .= '<li>... and ' . (count($integrity['issues']) - 10) . ' more</li>';
			}
			$issues_list .= '</ul>';
			$integrity_desc .= '<br><strong>Issues found:</strong>' . $issues_list;
		}
		// Add "Run Repair" button if there are issues (calls repair_data_integrity automatically on save, but provide manual button)
		if ($integrity['status'] !== 'ok') {
			$integrity_action = '<button type="button" class="button button-small" onclick="alert(\'Data integrity issues will be automatically repaired when you save changes. To trigger repair now, make any change and save.\');">Auto-Repair on Save</button>';
		}

		$checks['data_integrity'] = array(
			'status'      => $integrity_status,
			'label'       => 'Data Integrity',
			'description' => $integrity_desc,
			'action'      => $integrity_action ? '<div class="description" style="background: #f9f9f9; padding: 8px; border-radius: 4px; border: 1px solid #ddd;">' . $integrity_action . '</div>' : '',
		);

		// Interaction Data Purge tool
		$purge_form = $this->render_interaction_purge_form();
		$checks['interaction_purge'] = array(
			'status'      => 'info',
			'label'       => 'Interaction Data Purge',
			'description' => $purge_form,
			'action'      => '',
		);

		return $checks;
	}
}
