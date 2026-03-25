<?php
/**
 * Settings Manager class
 * Handles plugin options: defaults, validation, sanitization, CRUD
 *
 * @package WP_Configurator_Wizard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Settings_Manager {

	/**
	 * Option name in wp_options table
	 */
	const OPTION_NAME = 'wp_configurator_options';

	/**
	 * Plugin version (for reference)
	 */
	private $version;

	/**
	 * Constructor
	 */
	public function __construct( $version ) {
		$this->version = $version;
	}

	/**
	 * Get all options with fallback to defaults
	 */
	public function get_options(): array {
		return get_option( self::OPTION_NAME, $this->get_default_options() );
	}

	/**
	 * Get a single option value
	 */
	public function get_option( string $key, $default = null ) {
		$options = $this->get_options();
		return $options[$key] ?? $default;
	}

	/**
	 * Update all options (will be validated and sanitized)
	 */
	public function update_options( array $options ): bool {
		$validated = $this->validate_options( $options );
		return update_option( self::OPTION_NAME, $validated );
	}

	/**
	 * Reset options to defaults
	 */
	public function reset_to_defaults(): void {
		update_option( self::OPTION_NAME, $this->get_default_options() );
	}

	/**
	 * Register setting with WordPress Settings API
	 */
	public function register_settings(): void {
		register_setting( 'wp-configurator-settings', self::OPTION_NAME, array( $this, 'validate_options' ) );
	}

	/**
	 * Validate and sanitize input from settings form
	 */
	public function validate_options( $input ) {
		$valid = array();

		// Propagate category ID changes to features
		if ( isset( $input['categories'] ) && is_array( $input['categories'] ) ) {
			// Get old categories to compare
			$old_options = get_option( self::OPTION_NAME, $this->get_default_options() );
			$old_categories = isset( $old_options['categories'] ) ? $old_options['categories'] : array();

			// Build map: old ID => new ID based on original_id tracking
			$id_change_map = array();
			foreach ( $input['categories'] as $cat ) {
				if ( isset( $cat['original_id'] ) && $cat['original_id'] !== '' && $cat['original_id'] != $cat['id'] ) {
					$old_id = $cat['original_id'];
					$new_id = $cat['id'];
					$id_change_map[ $old_id ] = $new_id;
				}
			}

			// Apply mapping to features before sanitization
			if ( ! empty( $id_change_map ) && isset( $input['features'] ) && is_array( $input['features'] ) ) {
				foreach ( $input['features'] as &$feat ) {
					if ( isset( $feat['category_id'] ) && isset( $id_change_map[ $feat['category_id'] ] ) ) {
						$feat['category_id'] = $id_change_map[ $feat['category_id'] ];
					}
				}
			}

			// Remove original_id to keep sanitization clean
			foreach ( $input['categories'] as &$cat ) {
				unset( $cat['original_id'] );
			}
		}

		// Categories
		if ( isset( $input['categories'] ) ) {
			$valid['categories'] = $this->sanitize_categories( $input['categories'] );
		} else {
			$valid['categories'] = array();
		}

		// Features
		if ( isset( $input['features'] ) ) {
			$valid['features'] = $this->sanitize_features( $input['features'] );
		} else {
			$valid['features'] = array();
		}

		// Packages (legacy, may be removed in future)
		if ( isset( $input['packages'] ) ) {
			$valid['packages'] = $this->sanitize_packages( $input['packages'] );
		}

		// Settings
		if ( isset( $input['settings'] ) ) {
			$valid['settings'] = $this->sanitize_settings( $input['settings'] );
		}

		return $valid;
	}

	/**
	 * Handle restore defaults request (called on admin_init)
	 */
	public function maybe_restore_defaults(): void {
		if ( isset( $_GET['page'] ) && $_GET['page'] === 'wp-configurator-settings' && isset( $_GET['restore_defaults'] ) ) {
			// Verify nonce
			if ( ! isset( $_GET['restore_nonce'] ) || ! wp_verify_nonce( $_GET['restore_nonce'], 'restore_defaults' ) ) {
				wp_die( __( 'Security check failed. Please try again.', 'wp-configurator' ) );
			}
			$this->reset_to_defaults();
			// Redirect to remove query args
			$redirect_url = remove_query_arg( array( 'restore_defaults', 'restore_nonce' ) );
			wp_redirect( $redirect_url );
			exit;
		}
	}

	/* ---------- Sanitization helpers (private) ---------- */

	public function sanitize_categories( $categories ) {
		$sanitized = array();
		foreach ( $categories as $cat ) {
			// Auto-generate ID if empty (from name)
			$id = ! empty( $cat['id'] ) ? sanitize_text_field( $cat['id'] ) : sanitize_title( $cat['name'] );

			$sanitized[] = array(
				'id'              => $id,
				'name'            => sanitize_text_field( $cat['name'] ),
				'icon'            => sanitize_text_field( $cat['icon'] ),
				'category_image_id'=> sanitize_text_field( $cat['category_image_id'] ?? '' ),
				'color'           => ! empty( $cat['color'] ) ? sanitize_hex_color( $cat['color'] ) : '',
				'order'           => intval( $cat['order'] ),
				'compulsory'      => ! empty( $cat['compulsory'] ) ? 1 : 0,
				'info'            => ! empty( $cat['info'] ) ? sanitize_textarea_field( $cat['info'] ) : '',
			);
		}
		return $sanitized;
	}

	public function sanitize_features( $features ) {
		$sanitized = array();

		// Debug: log incoming features if debug is enabled
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && isset( $_GET['debug'] ) ) {
			error_log( 'sanitize_features input: ' . print_r( $features, true ) );
		}

		foreach ( $features as $index => $feat ) {
			// Debug first few items
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && isset( $_GET['debug'] ) && $index < 3 ) {
				error_log( "Feature $index raw: " . print_r( $feat, true ) );
			}

			// Auto-generate ID if empty (from name)
			$id = ! empty( $feat['id'] ) ? sanitize_text_field( $feat['id'] ) : 'feat_' . sanitize_title( $feat['name'] ?? 'feature' ) . '_' . $index;

			// Sanitize incompatible_with as array of strings
			$incompatible_with = array();
			if ( isset( $feat['incompatible_with'] ) && is_array( $feat['incompatible_with'] ) ) {
				foreach ( $feat['incompatible_with'] as $conflict_id ) {
					$clean_id = sanitize_text_field( $conflict_id );
					if ( $clean_id !== '' && $clean_id !== $id ) { // exclude self-references
						$incompatible_with[] = $clean_id;
					}
				}
				// Remove duplicates
				$incompatible_with = array_unique( $incompatible_with );
			}

			$sanitized[] = array(
				'id'               => $id,
				'category_id'      => sanitize_text_field( $feat['category_id'] ?? '' ),
				'name'             => sanitize_text_field( $feat['name'] ?? '' ),
				'description'      => wp_kses_post( $feat['description'] ?? '' ),
				'icon'             => sanitize_text_field( $feat['icon'] ?? '' ),
				'feature_image_id' => sanitize_text_field( $feat['feature_image_id'] ?? '' ),
				'price'            => floatval( $feat['price'] ?? 0 ),
				'billing_type'     => sanitize_text_field( $feat['billing_type'] ?? 'one-off' ),
				'order'            => intval( $feat['order'] ?? $index ),
				'enabled'          => ! empty( $feat['enabled'] ) ? 1 : 0,
				'sku'              => sanitize_text_field( $feat['sku'] ?? '' ),
				'incompatible_with'=> $incompatible_with,
			);
		}

		return $sanitized;
	}

	public function sanitize_packages( $packages ) {
		$sanitized = array();
		foreach ( $packages as $pkg ) {
			$sanitized[] = array(
				'id'          => sanitize_text_field( $pkg['id'] ),
				'name'        => sanitize_text_field( $pkg['name'] ),
				'description' => sanitize_textarea_field( $pkg['description'] ),
				'price'       => floatval( $pkg['price'] ),
				'enabled'     => isset( $pkg['enabled'] ) ? 1 : 0,
			);
		}
		return $sanitized;
	}

	public function sanitize_settings( $settings ) {
		$sanitized = array();
		if ( is_array( $settings ) ) {
			foreach ( $settings as $key => $value ) {
				if ( $key === 'webhook_url' ) {
					$sanitized['webhook_url'] = esc_url_raw( $value );
				} elseif ( $key === 'quote_button_text' ) {
					$sanitized['quote_button_text'] = sanitize_text_field( $value );
				} elseif ( $key === 'notification_email' ) {
					$sanitized['notification_email'] = sanitize_email( $value );
				} elseif ( $key === 'collapsible_categories' ) {
					$sanitized['collapsible_categories'] = ! empty( $value ) ? 1 : 0;
				} elseif ( $key === 'accordion_mode' ) {
					$sanitized['accordion_mode'] = ! empty( $value ) ? 1 : 0;
				} elseif ( $key === 'frontend_title' ) {
					$sanitized['frontend_title'] = sanitize_text_field( $value );
				} elseif ( $key === 'frontend_subtitle' ) {
					$sanitized['frontend_subtitle'] = wp_kses_post( $value );
				} elseif ( $key === 'dropzone_footer_text' ) {
					$sanitized['dropzone_footer_text'] = wp_kses_post( $value );
				} elseif ( $key === 'client_message' ) {
					$sanitized['client_message'] = wp_kses_post( $value );
				} elseif ( $key === 'send_client_email' ) {
					$sanitized['send_client_email'] = ! empty( $value ) ? 1 : 0;
				} elseif ( $key === 'exclude_admin_ip' ) {
					$sanitized['exclude_admin_ip'] = ! empty( $value ) ? 1 : 0;
				} elseif ( $key === 'admin_ip_address' ) {
					$sanitized['admin_ip_address'] = sanitize_text_field( $value );
				} elseif ( $key === 'exclude_zero_cost_from_stats' ) {
					$sanitized['exclude_zero_cost_from_stats'] = ! empty( $value ) ? 1 : 0;
				} elseif ( $key === 'custom_styles' ) {
					$sanitized['custom_styles'] = sanitize_textarea_field( $value );
				} elseif ( $key === 'enable_live_preview' ) {
					$sanitized['enable_live_preview'] = ! empty( $value ) ? 1 : 0;
				} elseif ( $key === 'exclude_bot_user_agents' ) {
					$sanitized['exclude_bot_user_agents'] = ! empty( $value ) ? 1 : 0;
				} elseif ( $key === 'bot_user_agents' ) {
					$sanitized['bot_user_agents'] = sanitize_textarea_field( $value );
				} else {
					$sanitized[ $key ] = sanitize_text_field( $value );
				}
			}

			// Ensure all expected settings keys exist (fill defaults if missing)
			$defaults = $this->get_default_settings();
			foreach ( $defaults as $key => $default_value ) {
				if ( ! array_key_exists( $key, $sanitized ) ) {
					$sanitized[ $key ] = $default_value;
				}
			}
		}
		return $sanitized;
	}

	/**
	 * Get default settings array (subset of defaults)
	 * Used to ensure all keys present after sanitization
	 */
	private function get_default_settings(): array {
		$defaults = $this->get_default_options();
		return isset( $defaults['settings'] ) ? $defaults['settings'] : array();
	}

	/**
	 * Get default options (full structure)
	 */
	public function get_default_options(): array {
		return array(
			'categories' => array(
				array( 'id' => 'page-packages', 'name' => 'Page Packages', 'icon' => '📄', 'category_image_id' => '', 'order' => 1, 'compulsory' => 1, 'info' => '' ),
				array( 'id' => 'ecommerce', 'name' => 'E-commerce', 'icon' => '🛒', 'category_image_id' => '', 'order' => 2, 'compulsory' => 0, 'info' => '' ),
				array( 'id' => 'design', 'name' => 'Design', 'icon' => '🎨', 'category_image_id' => '', 'order' => 3, 'compulsory' => 0, 'info' => '' ),
				array( 'id' => 'seo', 'name' => 'SEO', 'icon' => '🔍', 'category_image_id' => '', 'order' => 4, 'compulsory' => 0, 'info' => '' ),
				array( 'id' => 'maintenance', 'name' => 'Maintenance', 'icon' => '🔧', 'category_image_id' => '', 'order' => 5, 'compulsory' => 0, 'info' => '' ),
			),
			'features' => array(
				// Page Packages (compulsory category) - all one-off
				array( 'id' => 'pages-basic', 'category_id' => 'page-packages', 'name' => 'Basic (1-5 pages)', 'description' => 'Simple website with up to 5 pages', 'icon' => '📄', 'feature_image_id' => '', 'price' => 50, 'billing_type' => 'one-off', 'order' => 1, 'enabled' => 1, 'incompatible_with' => array() ),
				array( 'id' => 'pages-standard', 'category_id' => 'page-packages', 'name' => 'Standard (6-15 pages)', 'description' => 'Medium-sized website with up to 15 pages', 'icon' => '📄', 'feature_image_id' => '', 'price' => 100, 'billing_type' => 'one-off', 'order' => 2, 'enabled' => 1, 'incompatible_with' => array() ),
				array( 'id' => 'pages-premium', 'category_id' => 'page-packages', 'name' => 'Premium (16+ pages)', 'description' => 'Large website with 16 or more pages', 'icon' => '📄', 'feature_image_id' => '', 'price' => 200, 'billing_type' => 'one-off', 'order' => 3, 'enabled' => 1, 'incompatible_with' => array() ),
				// E-commerce features
				array( 'id' => 'ecommerce-basic', 'category_id' => 'ecommerce', 'name' => 'Basic Store', 'description' => 'Simple product listings', 'icon' => '🛍️', 'feature_image_id' => '', 'price' => 150, 'billing_type' => 'one-off', 'order' => 1, 'enabled' => 1, 'incompatible_with' => array() ),
				array( 'id' => 'ecommerce-advanced', 'category_id' => 'ecommerce', 'name' => 'Advanced Store', 'description' => 'Full e-commerce with inventory', 'icon' => '💳', 'feature_image_id' => '', 'price' => 300, 'billing_type' => 'one-off', 'order' => 2, 'enabled' => 1, 'incompatible_with' => array() ),
				// Design features
				array( 'id' => 'design-custom', 'category_id' => 'design', 'name' => 'Custom Design', 'description' => 'Unique design tailored to brand', 'icon' => '✏️', 'feature_image_id' => '', 'price' => 250, 'billing_type' => 'one-off', 'order' => 1, 'enabled' => 1, 'incompatible_with' => array() ),
				array( 'id' => 'design-premium', 'category_id' => 'design', 'name' => 'Premium Design', 'description' => 'High-end custom design', 'icon' => '💎', 'feature_image_id' => '', 'price' => 500, 'billing_type' => 'one-off', 'order' => 2, 'enabled' => 1, 'incompatible_with' => array() ),
				// SEO features
				array( 'id' => 'seo-basic', 'category_id' => 'seo', 'name' => 'Basic SEO', 'description' => 'On-page optimization', 'icon' => '📈', 'feature_image_id' => '', 'price' => 100, 'billing_type' => 'one-off', 'order' => 1, 'enabled' => 1, 'incompatible_with' => array() ),
				array( 'id' => 'seo-advanced', 'category_id' => 'seo', 'name' => 'Advanced SEO', 'description' => 'Technical SEO + content strategy', 'icon' => '🚀', 'feature_image_id' => '', 'price' => 250, 'billing_type' => 'one-off', 'order' => 2, 'enabled' => 1, 'incompatible_with' => array() ),
				// Maintenance features - these could be recurring in real scenario
				array( 'id' => 'maintenance-monthly', 'category_id' => 'maintenance', 'name' => 'Monthly Maintenance', 'description' => 'Updates & backups', 'icon' => '📅', 'feature_image_id' => '', 'price' => 50, 'billing_type' => 'monthly', 'order' => 1, 'enabled' => 1, 'incompatible_with' => array() ),
				array( 'id' => 'maintenance-quarterly', 'category_id' => 'maintenance', 'name' => 'Quarterly Maintenance', 'description' => 'Priority support', 'icon' => '📆', 'feature_image_id' => '', 'price' => 120, 'billing_type' => 'quarterly', 'order' => 2, 'enabled' => 1, 'incompatible_with' => array() ),
				array( 'id' => 'maintenance-annually', 'category_id' => 'maintenance', 'name' => 'Annual Maintenance', 'description' => 'Full management', 'icon' => '🗓️', 'feature_image_id' => '', 'price' => 400, 'billing_type' => 'annual', 'order' => 3, 'enabled' => 1, 'incompatible_with' => array() ),
			),
			'settings' => array(
				'webhook_url' => '',
				'quote_button_text' => 'Convert to Quote',
				'notification_email' => '',
				'test_email_address' => '',
				'collapsible_categories' => 0,
				'accordion_mode' => 0,
				'client_message' => 'Many thanks {{name}} for requesting your formal quote. Here is a copy of what you have sent us. If we need any further information we will get in touch. In the meantime we will prepare your quote and send for your consideration in the next 2 business days.',
				'send_client_email' => 1,
				'exclude_admin_ip' => 0,
				'admin_ip_address' => '',
				'tiles_per_row_desktop' => 4,
				'tiles_per_row_tablet' => 3,
				'tiles_per_row_mobile' => 1,
				'custom_styles' => '',
				'exclude_zero_cost_from_stats' => 0,
				'enable_live_preview' => 1,
				'exclude_bot_user_agents' => 0,
				'bot_user_agents' => "Googlebot\nBingbot\nYahoo! Slurp\nDuckDuckBot\nBaiduspider\nYandexBot\nSogou\nExabot\nfacebot\nIA_Archiver\nTwitterbot\nLinkedInBot\nSlackbot\nDiscordbot\nWhatsApp\nTelegram\ncurl\nwget\npython-requests\nScrapy",
			),
		);
	}
}
