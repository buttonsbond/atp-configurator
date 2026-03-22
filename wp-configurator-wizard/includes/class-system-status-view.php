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
		echo '<div style="margin-bottom: 16px;">';
		echo '<button type="button" class="button button-secondary" id="refresh-system-status">';
		esc_html_e( 'Refresh Checks', 'wp-configurator' );
		echo '</button>';
		echo '<span class="description" style="margin-left: 12px;">Last checked: ' . current_time( 'mysql' ) . '</span>';
		echo '</div>';

		// Status legend
		echo '<div style="margin-bottom: 20px; display: flex; gap: 16px; flex-wrap: wrap;">';
		echo '<div><span class="system-status-icon system-status-success">✓</span> <span class="description">Good</span></div>';
		echo '<div><span class="system-status-icon system-status-warning">⚠</span> <span class="description">Warning (may affect functionality)</span></div>';
		echo '<div><span class="system-status-icon system-status-error">✕</span> <span class="description">Error (needs attention)</span></div>';
		echo '<div><span class="system-status-icon system-status-info">i</span> <span class="description">Info</span></div>';
		echo '</div>';

		// Checks table
		echo '<table class="widefat fixed striped" style="border: 1px solid #ccd0d4;">';
		echo '<thead><tr><th style="width: 150px;">Component</th><th>Status</th><th>Details</th><th style="width: 120px;">Action</th></tr></thead>';
		echo '<tbody>';

		foreach ( $checks as $check ) {
			$icon_class = 'system-status-icon system-status-' . esc_attr( $check['status'] );
			$status_label = ucfirst( $check['status'] );
			$action_html = isset( $check['action'] ) ? $check['action'] : '';

			echo '<tr>';
			echo '<td><strong>' . esc_html( $check['label'] ) . '</strong></td>';
			echo '<td><span class="' . $icon_class . '">' . esc_html( $status_label ) . '</span></td>';
			echo '<td>' . esc_html( $check['description'] ) . '</td>';
			echo '<td>' . $action_html . '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';

		// Inline CSS for icons and code blocks
		?>
		<style>
			.system-status-icon {
				display: inline-block;
				min-width: 70px;
				height: 28px;
				line-height: 28px;
				text-align: center;
				border-radius: 4px;
				font-weight: 600;
				color: #fff;
				padding: 0 12px;
				font-size: 13px;
				vertical-align: middle;
			}
			.system-status-success { background-color: #46b450; }
			.system-status-warning { background-color: #f56e28; }
			.system-status-error { background-color: #dc3232; }
			.system-status-info { background-color: #72aee6; }

			.system-status-pre {
				background: #f1f1f1;
				padding: 12px;
				border-radius: 4px;
				overflow-x: auto;
				position: relative;
				margin: 8px 0;
				border: 1px solid #ddd;
			}
			.system-status-pre code {
				font-family: 'Monaco', 'Consolas', 'Courier New', monospace;
				font-size: 12px;
				line-height: 1.4;
				color: #333;
			}
			.system-status-copy-btn {
				position: absolute;
				top: 8px;
				right: 8px;
				background: #fff;
				border: 1px solid #ccc;
				border-radius: 3px;
				padding: 4px 10px;
				font-size: 11px;
				cursor: pointer;
				color: #666;
				transition: all 0.2s;
			}
			.system-status-copy-btn:hover {
				background: #e6f7ff;
				border-color: #1e6ba8;
				color: #1e6ba8;
			}
			.system-status-copy-btn.copied {
				background: #46b450;
				color: #fff;
				border-color: #46b450;
			}
		</style>
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

				// Test Email button
				window.sendTestEmail = function(email) {
					if ( ! confirm( 'Send test email to: ' + email + '?' ) ) {
						return;
					}

					var button = event.target;
					button.disabled = true;
					button.textContent = 'Sending...';

					jQuery.post(ajaxurl, {
						action: 'send_test_email',
						email: email,
						nonce: wpConfiguratorAdmin.exportNonce
					}, function(response) {
						button.disabled = false;
						button.textContent = 'Send Test Email';
						if ( response.success ) {
							alert( 'Success: ' + response.data.message );
						} else {
							alert( 'Error: ' + response.data.message );
						}
					}).fail(function(xhr, status, error) {
						button.disabled = false;
						button.textContent = 'Send Test Email';
						alert( 'AJAX error: ' + error );
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
					}, function(response) {
						button.disabled = false;
						button.textContent = 'Test Webhook';
						if ( response.success ) {
							var msg = response.data.message;
							if ( response.data.response ) {
								msg += '\n\nResponse body:\n' + response.data.response;
							}
							alert( 'Success:\n\n' + msg );
						} else {
							alert( 'Error: ' + response.data.message );
						}
					}).fail(function(xhr, status, error) {
						button.disabled = false;
						button.textContent = 'Test Webhook';
						alert( 'AJAX error: ' + error );
					});
				};
			});
		</script>
		<?php
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

		// 9. Plugin version
		$plugin_version = $this->version;
		$checks['plugin_version'] = array(
			'status'      => 'info',
			'label'       => 'Plugin Version',
			'description' => "ATP Quote Configurator v$plugin_version",
			'action'      => '',
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
		$email_desc = 'Email notifications are configured. Test email sends a sample client email with dummy data.';
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
		// Test email button uses test_email if set, otherwise admin email
		$test_target = $test_email ?: $admin_email;
		if ( $test_target && is_email( $test_target ) ) {
			$action_buttons .= '<button type="button" class="button button-small" onclick="sendTestEmail(\'' . esc_js( $test_target ) . '\')">Send Test Email</button> ';
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

		return $checks;
	}
}
