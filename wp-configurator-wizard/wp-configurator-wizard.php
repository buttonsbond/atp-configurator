<?php
/**
 * Plugin Name: ATP Quote Configurator
 * Description: Comprehensive cost estimation wizard with real-time analytics. Track interactions, manage quote requests, and gain insights with engagement metrics, revenue trends, and feature popularity.
 * Version: 3.6.3
 * Author: All Tech Plus, Rojales (https://all-tech-plus.com)
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: wp-configurator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Define plugin file constant for use in other classes
define( 'WP_CONFIGURATOR_WIZARD_FILE', __FILE__ );

// Load component classes
require_once __DIR__ . '/includes/class-database-manager.php';
require_once __DIR__ . '/includes/class-settings-manager.php';
require_once __DIR__ . '/includes/class-admin-ui.php';

/**
 * Main Plugin Class
 */
class WP_Configurator_Wizard {

	/**
	 * Plugin version
	 */
	const VERSION = '3.6.3';

	/**
	 * Database manager instance
	 *
	 * @var Database_Manager
	 */
	private $database_manager;

	/**
	 * Settings manager instance
	 *
	 * @var Settings_Manager
	 */
	private $settings_manager;

	/**
	 * Admin UI instance
	 *
	 * @var Admin_UI
	 */
	private $admin_ui;

	/**
	 * Stats Renderer instance
	 *
	 * @var Stats_Renderer
	 */
	private $stats_renderer;

	/**
	 * AJAX Handler instance
	 *
	 * @var Ajax_Handler
	 */
	private $ajax_handler;

	/**
	 * Asset Manager instance
	 *
	 * @var Asset_Manager
	 */
	private $asset_manager;

	/**
	 * Quote Requests View instance
	 *
	 * @var Quote_Requests_View
	 */
	private $quote_requests_view;

	/**
	 * System Status View instance
	 *
	 * @var System_Status_View
	 */
	private $system_status_view;

	/**
	 * Get Quote Requests View instance
	 *
	 * @return Quote_Requests_View
	 */
	public function get_quote_requests_view(): Quote_Requests_View {
		return $this->quote_requests_view;
	}

	/**
	 * Get System Status View instance
	 *
	 * @return System_Status_View
	 */
	public function get_system_status_view(): System_Status_View {
		return $this->system_status_view;
	}

	/**
	 * Get plugin version
	 *
	 * @return string
	 */
	public function get_version(): string {
		return self::VERSION;
	}

	/**
	 * Render the stats tab (delegates to Stats_Renderer)
	 */
	public function render_stats_tab(): void {
		$this->stats_renderer->render();
	}

	/**
	 * Plugin constructor
	 */
	public function __construct() {
		// Initialize component managers
		$this->database_manager = new Database_Manager();
		$this->settings_manager = new Settings_Manager( self::VERSION );
		$this->admin_ui = new Admin_UI( $this->settings_manager, $this );
		$this->stats_renderer = new Stats_Renderer( $this->settings_manager, $this->database_manager );
		$this->system_status_view = new System_Status_View( $this->settings_manager, self::VERSION );
		$this->ajax_handler = new Ajax_Handler( self::VERSION, $this->settings_manager, $this->database_manager, $this->system_status_view );
		$this->asset_manager = new Asset_Manager( self::VERSION, $this->settings_manager );
		$this->quote_requests_view = new Quote_Requests_View( $this->settings_manager );

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_init', array( $this->settings_manager, 'maybe_restore_defaults' ) );
		add_action( 'admin_notices', array( $this, 'maybe_show_update_notice' ) );
		add_action( 'wp_ajax_wp_configurator_force_github_check', array( $this, 'force_github_check' ) );

		// Interaction Data Purge AJAX handlers
		add_action( 'wp_ajax_preview_interaction_purge', array( $this->system_status_view, 'ajax_preview_interaction_purge' ) );
		add_action( 'wp_ajax_execute_interaction_purge', array( $this->system_status_view, 'ajax_execute_interaction_purge' ) );
	}

	/**
	 * Plugin activation hook
	 */
	public static function activate() {
		Database_Manager::activate();
	}


	/**
	 * Plugin deactivation hook
	 */
	public static function deactivate() {
		// Optionally, you could drop the table on deactivation, but usually you want to keep data
		// Uncomment below to drop table on deactivation (data will be lost!)
		/*
		global $wpdb;
		$table_name = $wpdb->prefix . 'configurator_quote_requests';
		$wpdb->query( "DROP TABLE IF EXISTS $table_name" );
		*/
	}



	/**
	 * Initialize plugin
	 */
	public function init() {
		// Load text domain
		load_plugin_textdomain( 'wp-configurator', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		// Register shortcode with attribute support
		add_shortcode( 'wp_configurator_wizard', function( $atts ) {
			return $this->render_wizard( $atts );
		} );

		// Register settings
		add_action( 'admin_init', array( $this->settings_manager, 'register_settings' ) );

		// Ensure interactions table exists (proactive check on admin pages)
		add_action( 'admin_init', array( $this->database_manager, 'ensure_interactions_table_exists' ) );

		// Ensure quote requests table has all columns (upgrade path for metadata, etc.)
		add_action( 'admin_init', array( 'Database_Manager', 'ensure_status_columns' ) );

		// Fix any missing IDs in existing data (run once on every init, but only updates if needed)
		add_action( 'init', array( $this, 'fix_missing_ids' ) );

		// Schedule weekly donors sync cron
		add_action( 'init', array( $this->system_status_view, 'schedule_donors_sync_cron' ) );
		add_action( 'wp_configurator_weekly_donors_sync', array( $this->system_status_view, 'cron_sync_donors' ) );

		// Customize TinyMCE for our admin content fields
		add_filter( 'tiny_mce_before_init', array( $this, 'configure_tinymce' ) );
	}


	/**
	 * Fix missing IDs in saved options (one-time migration)
	 */
	public function fix_missing_ids() {
		$options = $this->settings_manager->get_options();
		$changed = false;

		if ( ! empty( $options['categories'] ) ) {
			foreach ( $options['categories'] as &$cat ) {
				if ( empty( $cat['id'] ) ) {
					$cat['id'] = sanitize_title( $cat['name'] ?? 'category' );
					$changed = true;
				}
				// Add compulsory field if missing (default 0)
				if ( ! array_key_exists( 'compulsory', $cat ) ) {
					$cat['compulsory'] = 0;
					$changed = true;
				}
			}
		}

		if ( ! empty( $options['features'] ) ) {
			foreach ( $options['features'] as $index => &$feat ) {
				if ( empty( $feat['id'] ) ) {
					$feat['id'] = 'feat_' . sanitize_title( $feat['name'] ?? 'feature' ) . '_' . $index;
					$changed = true;
				}
				// Add billing_type if missing (default to one-off)
				if ( empty( $feat['billing_type'] ) ) {
					$feat['billing_type'] = 'one-off';
					$changed = true;
				}
				// Add order if missing (default to index+1)
				if ( ! isset( $feat['order'] ) ) {
					$feat['order'] = $index + 1;
					$changed = true;
				}
				// Add incompatible_with if missing (default to empty array)
				if ( ! isset( $feat['incompatible_with'] ) ) {
					$feat['incompatible_with'] = array();
					$changed = true;
				}
			}
		}

		// Ensure settings defaults exist for new installations/upgrades
		if ( ! isset( $options['settings'] ) || ! is_array( $options['settings'] ) ) {
			$options['settings'] = array();
		}
		$default_settings = array(
			'webhook_url' => '',
			'quote_button_text' => 'Convert to Quote',
			'notification_email' => '',
			'collapsible_categories' => 0,
			'accordion_mode' => 0,
			'client_message' => 'Many thanks {{name}} for requesting your formal quote. Here is a copy of what you have sent us. If we need any further information we will get in touch. In the meantime we will prepare your quote and send for your consideration in the next 2 business days.',
			'send_client_email' => 1,
		);
		foreach ( $default_settings as $key => $default_value ) {
			if ( ! array_key_exists( $key, $options['settings'] ) ) {
				$options['settings'][ $key ] = $default_value;
				$changed = true;
			}
		}

		// Migration: convert packages to a compulsory "Page Packages" category
		if ( ! empty( $options['packages'] ) && empty( $options['migrated_packages_to_category'] ) ) {
			// Create a "Page Packages" category if it doesn't exist
			$page_pkg_category_id = 'page-packages';
			$category_exists = false;
			foreach ( $options['categories'] as &$cat ) {
				if ( $cat['id'] === $page_pkg_category_id ) {
					$category_exists = true;
					// Mark as compulsory
					$cat['compulsory'] = 1;
					break;
				}
			}
			if ( ! $category_exists ) {
				$options['categories'][] = array(
					'id'         => $page_pkg_category_id,
					'name'       => 'Page Packages',
					'icon'       => '📄',
					'order'      => count( $options['categories'] ) + 1,
					'compulsory' => 1,
				);
			}

			// Convert each package to a feature in the "Page Packages" category
			foreach ( $options['packages'] as $pkg ) {
				$options['features'][] = array(
					'id'               => 'pkg-' . sanitize_title( $pkg['id'] ?? $pkg['name'] ),
					'category_id'      => $page_pkg_category_id,
					'name'             => $pkg['name'],
					'description'      => $pkg['description'],
					'icon'             => '📄',
					'price'            => $pkg['price'],
					'enabled'          => $pkg['enabled'],
					'incompatible_with' => array(),
				);
			}

			// Mark migration as complete
			$options['migrated_packages_to_category'] = 1;
			$changed = true;
		}

		if ( $changed ) {
			update_option( 'wp_configurator_options', $options );
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'ATP Quote Configurator: Fixed missing IDs and/or migrated packages' );
			}
		}
	}







	/**
	 * Customize TinyMCE settings for admin content fields
	 *
	 * @param array $init_array TinyMCE initialization settings
	 * @return array Modified settings
	 */
	public function configure_tinymce( $init_array ) {
		// Only apply on our plugin's settings page
		if ( ! is_admin() ) {
			return $init_array;
		}

		$screen = get_current_screen();
		if ( ! $screen || $screen->id !== 'toplevel_page_wp-configurator-settings' ) {
			return $init_array;
		}

		// Custom toolbar with formatselect and styleselect always visible
		$init_array['toolbar1'] = 'formatselect,styleselect,bold,italic,underline,bullist,numlist,blockquote,forecolor,backcolor,link,unlink,removeformat,undo,redo,wp_fullscreen';
		$init_array['toolbar2'] = '';

		// Collect custom style formats (NOT merging with WordPress defaults)
		$custom_formats = array();

		// Add Elementor global styles to styleselect if Elementor is active
		if ( did_action( 'elementor/loaded' ) || class_exists( '\Elementor\Plugin' ) ) {
			$elementor_styles = array();

			// Try to get Elementor's global typography and button styles
			if ( class_exists( '\Elementor\Plugin' ) ) {
				try {
					$elementor_instance = \Elementor\Plugin::instance();
					if ( $elementor_instance ) {
						// Method 1: Try to get from kits manager (Elementor Pro)
						if ( method_exists( $elementor_instance, 'kits_manager' ) && $elementor_instance->kits_manager ) {
							$kit = $elementor_instance->kits_manager->get_active_kit();
							if ( $kit ) {
								// Get typography settings - different structures for different Elementor versions
								if ( method_exists( $kit, 'get_settings' ) ) {
									$typography = $kit->get_settings( 'typography' );
									if ( ! empty( $typography ) && is_array( $typography ) ) {
										foreach ( $typography as $key => $settings ) {
											// Try different key names for title
											$title = ! empty( $settings['_title'] ) ? $settings['_title'] : ( ! empty( $settings['title'] ) ? $settings['title'] : '' );
											if ( $title ) {
												$elementor_styles[] = array(
													'title'  => $title . ' (Elementor)',
													'block'  => 'p',
													'classes'=> 'elementor-' . sanitize_html_class( $key ),
												);
											}
										}
									}

									// Get button styles
									$buttons = $kit->get_settings( 'buttons' );
									if ( ! empty( $buttons ) && is_array( $buttons ) ) {
										foreach ( $buttons as $key => $settings ) {
											$title = ! empty( $settings['_title'] ) ? $settings['_title'] : ( ! empty( $settings['title'] ) ? $settings['title'] : '' );
											if ( $title ) {
												$elementor_styles[] = array(
													'title'  => $title . ' Button',
													'block'  => 'a',
													'classes'=> 'elementor-button elementor-size-' . sanitize_html_class( $key ),
												);
											}
										}
									}
								}
							}
						}

						// Method 2: Get from global CSS (fallback) - look for common Elementor classes
						if ( empty( $elementor_styles ) ) {
							// Add common Elementor global classes that are typically available
							$common_elementor_classes = array(
								array(
									'title'  => 'Elementor Button',
									'block'  => 'a',
									'classes'=> 'elementor-button',
								),
								array(
									'title'  => 'Elementor Button (Large)',
									'block'  => 'a',
									'classes'=> 'elementor-button-lg',
								),
								array(
									'title'  => 'Elementor Button (Medium)',
									'block'  => 'a',
									'classes'=> 'elementor-button-md',
								),
								array(
									'title'  => 'Elementor Button (Small)',
									'block'  => 'a',
									'classes'=> 'elementor-button-sm',
								),
								array(
									'title'  => 'Elementor Image',
									'block'  => 'img',
									'classes'=> 'elementor-image',
								),
							);
							$elementor_styles = $common_elementor_classes;
						}
					}
				} catch ( Exception $e ) {
					// Elementor not ready or inaccessible, skip
				}
			}

			// Merge Elementor styles
			$custom_formats = array_merge( $custom_formats, $elementor_styles );
		}

		// Add user-defined custom styles from settings
		$custom_styles_text = $this->settings_manager->get_options();
		if ( ! empty( $custom_styles_text['settings']['custom_styles'] ) ) {
			$custom_parsed = $this->parse_custom_styles( $custom_styles_text['settings']['custom_styles'] );
			$custom_formats = array_merge( $custom_formats, $custom_parsed );
		}

		// If we have custom formats, add them to style_formats
		if ( ! empty( $custom_formats ) ) {
			// Get existing style_formats (WordPress defaults) but filter out any with invalid multi-tag block values
			$existing_formats = isset( $init_array['style_formats'] ) ? json_decode( $init_array['style_formats'], true ) : array();
			if ( ! is_array( $existing_formats ) ) {
				$existing_formats = array();
			}

			// Filter existing formats to remove any with comma-separated block values (those are formatselect items, not style_formats)
			$filtered_existing = array();
			foreach ( $existing_formats as $format ) {
				if ( isset( $format['block'] ) && strpos( $format['block'], ',' ) !== false ) {
					// Skip this invalid format (it's probably a formatselect item that doesn't belong here)
					continue;
				}
				$filtered_existing[] = $format;
			}

			// Merge: existing clean formats first, then our custom ones
			$merged_formats = array_merge( $filtered_existing, $custom_formats );
			$init_array['style_formats'] = wp_json_encode( $merged_formats );
		}

		return $init_array;
	}

	/**
	 * Parse custom styles textarea input into TinyMCE style_formats array
	 *
	 * Format: "Style Name: css-class-name" (one per line)
	 *
	 * @param string $text Custom styles textarea content
	 * @return array Array of style format arrays
	 */
	private function parse_custom_styles( $text ) {
		$styles = array();
		$lines = preg_split( '/\r\n|\r|\n/', $text );

		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( empty( $line ) ) {
				continue;
			}

			// Check if line contains a colon separator
			if ( strpos( $line, ':' ) === false ) {
				continue; // Skip malformed lines
			}

			// Split at first colon only
			$parts = explode( ':', $line, 2 );
			if ( count( $parts ) !== 2 ) {
				continue;
			}

			$title = trim( $parts[0] );
			$classes = trim( $parts[1] );

			if ( empty( $title ) || empty( $classes ) ) {
				continue;
			}

			// Determine block type based on first character of classes string
			// If it starts with "h" and is followed by a digit, it's likely a heading
			$block = 'span'; // default
			$class_parts = explode( ' ', $classes );
			$first_class = strtolower( $class_parts[0] );

			if ( preg_match( '/^h[1-6]$/', $first_class ) ) {
				$block = $first_class;
			} elseif ( strpos( $first_class, 'button' ) !== false || in_array( $first_class, array( 'a', '.a', 'a.btn' ) ) ) {
				$block = 'a';
			} elseif ( $first_class === 'img' || $first_class === '.img' ) {
				$block = 'img';
			} elseif ( $first_class === 'p' || $first_class === '.p' ) {
				$block = 'p';
			} elseif ( $first_class === 'div' || $first_class === '.div' ) {
				$block = 'div';
			}

			$styles[] = array(
				'title'  => $title,
				'block'  => $block,
				'classes'=> $classes,
			);
		}

		return $styles;
	}


	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'ATP Configurator Settings', 'wp-configurator' ),
			__( 'ATP Configurator', 'wp-configurator' ),
			'manage_options',
			'wp-configurator-settings',
			array( $this, 'settings_page' ),
			'dashicons-admin-settings',
			30
		);
	}

	/**
	 * Settings page
	 */
	public function settings_page() {
		$this->admin_ui->render_page();
	}




	/**
	 * Render the wizard via shortcode
	 *
	 * Supports attributes:
	 * - `template` (string): Custom template name to load from `templates/frontend/{template}.php`.
	 *   If not provided, uses the classic template at `templates/wizard.php` (backward compatible).
	 *
	 * @param array $atts Shortcode attributes
	 * @return string
	 */
	public function render_wizard( $atts = [] ) {
		$options = $this->settings_manager->get_options();
		// Validate structure; if corrupted, reset to defaults
		if ( ! is_array( $options ) || ! isset( $options['categories'] ) || ! isset( $options['features'] ) ) {
			$options = $this->settings_manager->get_default_options();
			$this->settings_manager->update_options( $options );
		}

		// Enrich categories with image URLs for frontend display
		foreach ( $options['categories'] as &$cat ) {
			if ( ! empty( $cat['category_image_id'] ) ) {
				$url = wp_get_attachment_image_url( $cat['category_image_id'], 'medium' );
				$cat['image_url'] = $url ? $url : '';
			} else {
				$cat['image_url'] = '';
			}
		}
		unset($cat); // Break the reference to prevent corruption later

		// Enrich features with image URLs for frontend display
		foreach ( $options['features'] as &$feat ) {
			if ( ! empty( $feat['feature_image_id'] ) ) {
				$url = wp_get_attachment_image_url( $feat['feature_image_id'], 'medium' );
				$feat['image_url'] = $url ? $url : '';
			} else {
				$feat['image_url'] = '';
			}
		}
		unset($feat); // Break the reference to prevent corruption later

		// Determine template path
		$template_file = '';

		// If template attribute is provided, try to load from frontend subdirectory
		if ( isset( $atts['template'] ) && ! empty( $atts['template'] ) ) {
			$template = sanitize_file_name( $atts['template'] );
			$candidate = plugin_dir_path( __FILE__ ) . 'templates/frontend/' . $template . '.php';
			if ( file_exists( $candidate ) ) {
				$template_file = $candidate;
			}
		}

		// Fallback to classic wizard template if no custom template specified or not found
		if ( empty( $template_file ) ) {
			$template_file = plugin_dir_path( __FILE__ ) . 'templates/wizard.php';
		}

		ob_start();
		include $template_file;
		return ob_get_clean();
	}

	/**
	 * Show admin notice if newer GitHub release available
	 */
	public function maybe_show_update_notice() {
		// Only show on our plugin's admin page
		$screen = get_current_screen();
		if ( ! $screen || $screen->id !== 'toplevel_page_wp-configurator-settings' ) {
			return;
		}

		// Check if user dismissed the notice
		$user_id = get_current_user_id();
		$dismissed = get_user_meta( $user_id, 'wp_configurator_update_notice_dismissed', true );
		if ( $dismissed ) {
			return;
		}

		// Get GitHub release check result from transient (same one used in System Status)
		$transient_key = 'wp_configurator_github_release_check';
		$github_check = get_transient( $transient_key );

		// If we have a cached result
		if ( false !== $github_check && is_array( $github_check ) ) {
			$latest_version = $github_check['latest_version'];
			$release_url = $github_check['release_url'];
			$message = $github_check['message'];
			$is_warning = $github_check['status'] === 'warning';

			// Only show banner for warning status (update available), not for info/success
			if ( $is_warning ) {
				?>
				<div class="notice notice-warning is-dismissible wp-configurator-update-notice">
					<p><strong><?php esc_html_e( 'New version available:', 'wp-configurator' ); ?></strong> <?php echo esc_html( $message ); ?> <a href="<?php echo esc_url( $release_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View Release', 'wp-configurator' ); ?></a></p>
				</div>
				<?php
			}
		}
	}

	/**
	 * AJAX handler: Force fresh GitHub release check
	 */
	public function force_github_check() {
		// Verify nonce
		if ( ! check_ajax_referer( 'wp_configurator_force_github_check_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid nonce.', 'wp-configurator' ) ) );
		}

		// Check capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'wp-configurator' ) ) );
		}

		// Clear the transient
		delete_transient( 'wp_configurator_github_release_check' );

		// Get fresh check result (this will also cache it again)
		$result = $this->system_status_view->check_github_release();

		// Return the fresh result
		wp_send_json_success( $result );
	}

}

// Load dependent classes (after main plugin definition)
require_once __DIR__ . '/includes/traits/trait-cost-calculation.php';
require_once __DIR__ . '/includes/traits/trait-quote-management.php';
require_once __DIR__ . '/includes/traits/trait-interaction-tracking.php';
require_once __DIR__ . '/includes/traits/trait-data-io.php';
require_once __DIR__ . '/includes/class-ajax-handler.php';
require_once __DIR__ . '/includes/class-stats-renderer.php';
require_once __DIR__ . '/includes/class-asset-manager.php';
require_once __DIR__ . '/includes/class-quote-requests-view.php';
require_once __DIR__ . '/includes/class-system-status-view.php';

// Activation/Deactivation hooks
register_activation_hook( __FILE__, array( 'WP_Configurator_Wizard', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WP_Configurator_Wizard', 'deactivate' ) );

// Initialize plugin
function wp_configurator_wizard_init() {
	return new WP_Configurator_Wizard();
}
$GLOBALS['wp_configurator_wizard'] = wp_configurator_wizard_init();
