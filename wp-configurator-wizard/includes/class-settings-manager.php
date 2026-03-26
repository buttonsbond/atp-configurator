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

		// Data integrity repair: fix duplicates, orphaned features, etc.
		$repaired = $this->repair_data_integrity( $valid );
		$valid = $repaired['options'];
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'WP Configurator: Data integrity repair report - ' . print_r( $repaired['report'], true ) );
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
			// Trim whitespace to prevent mismatches
			$id = trim($id);

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
				'category_id'      => trim( sanitize_text_field( $feat['category_id'] ?? '' ) ),
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

	/**
	 * Repair data integrity: fix duplicate IDs, orphaned features, etc.
	 *
	 * @param array $options Options array to repair.
	 * @return array ['options' => array, 'report' => array] Repaired options and a report of fixes.
	 */
	public function repair_data_integrity(array $options): array {
		$report = [
			'fixed_duplicate_category_ids' => 0,
			'fixed_duplicate_feature_ids' => 0,
			'fixed_orphaned_features' => 0,
			'fixed_missing_category_ids' => 0,
			'fixed_missing_feature_ids' => 0,
		];

		// Ensure categories and features arrays exist
		if (!isset($options['categories']) || !is_array($options['categories'])) {
			$options['categories'] = [];
		}
		if (!isset($options['features']) || !is_array($options['features'])) {
			$options['features'] = [];
		}

		// Ensure categories have original_id for change tracking if missing
		foreach ($options['categories'] as &$cat) {
			if (empty($cat['original_id'])) {
				$cat['original_id'] = $cat['id'] ?? '';
			}
		}
		unset($cat); // BREAK reference

		// Step 1: Sanitize and deduplicate category IDs
		$category_ids = [];
		$renamed_categories = []; // original_id (from input) => new_id

		foreach ($options['categories'] as &$cat) {
			// Sanitize ID if present, or generate from name
			if (empty($cat['id'])) {
				$cat['id'] = sanitize_title($cat['name'] ?? 'category');
			} else {
				$cat['id'] = sanitize_text_field($cat['id']);
			}
			// Sanitize name
			$cat['name'] = sanitize_text_field($cat['name'] ?? $cat['id']);

			$original_id = $cat['id']; // Store BEFORE any renaming
			$counter = 1;
			while (in_array($cat['id'], $category_ids, true)) {
				$cat['id'] = $original_id . '-' . $counter++;
			}
			if ($cat['id'] !== $original_id) {
				$report['fixed_duplicate_category_ids']++;
				$renamed_categories[$original_id] = $cat['id'];
				if (defined('WP_DEBUG') && WP_DEBUG) {
					error_log('Duplicate resolved: ' . $original_id . ' -> ' . $cat['id']);
				}
			}
			$category_ids[] = $cat['id'];
		}
		if (defined('WP_DEBUG') && WP_DEBUG) {
			error_log('Renamed categories map: ' . print_r($renamed_categories, true));
		}
		unset($cat); // Break reference to avoid overwriting last element in subsequent loops

		// Build a map of valid category IDs
		$valid_category_ids = array_flip($category_ids);

		// Build mapping of original_id => final id for categories that changed (user edit or duplicate resolution)
		$id_changes = [];
		foreach ($options['categories'] as $cat) {
			$original = $cat['original_id'] ?? '';
			if ($original !== '' && $original !== $cat['id']) {
				$id_changes[$original] = $cat['id'];
				if (defined('WP_DEBUG') && WP_DEBUG) {
					error_log('Category ID change: ' . $original . ' -> ' . $cat['id']);
				}
			}
		}
		if (defined('WP_DEBUG') && WP_DEBUG) {
			error_log('ID changes mapping: ' . print_r($id_changes, true));
		}

		// Step 2: Sanitize and deduplicate feature IDs, and fix category references
		$feature_ids = [];
		$renamed_features = []; // old_id => new_id

		foreach ($options['features'] as &$feat) {
			// Ensure ID exists and is sanitized
			if (empty($feat['id'])) {
				$base = 'feat_' . sanitize_title($feat['name'] ?? 'feature');
				$feat['id'] = $base;
			} else {
				$feat['id'] = sanitize_text_field($feat['id']);
			}

			// Trim category_id to prevent whitespace mismatches
			if (isset($feat['category_id'])) {
				$feat['category_id'] = trim($feat['category_id']);
			}

			// Deduplicate feature ID
			$original_id = $feat['id']; // Store BEFORE any renaming
			$counter = 1;
			while (in_array($feat['id'], $feature_ids, true)) {
				$feat['id'] = $original_id . '-' . $counter++;
			}
			if ($feat['id'] !== $original_id) {
				$report['fixed_duplicate_feature_ids']++;
				$renamed_features[$original_id] = $feat['id'];
			}
			$feature_ids[] = $feat['id'];

			// Apply category ID changes mapping (if feature's category_id was an old ID, update to new)
			if (!empty($id_changes) && isset($feat['category_id']) && isset($id_changes[$feat['category_id']])) {
				$feat['category_id'] = $id_changes[$feat['category_id']];
			}

			// Ensure category_id is valid
			if (empty($feat['category_id']) || !isset($valid_category_ids[$feat['category_id']])) {
				// Assign to first available category if any
				if (!empty($category_ids[0])) {
					$feat['category_id'] = $category_ids[0];
					$report['fixed_orphaned_features']++;
				} else {
					$feat['category_id'] = '';
				}
			}

			// Sanitize other fields (basic)
			$feat['name'] = sanitize_text_field($feat['name'] ?? '');
			$feat['description'] = wp_kses_post($feat['description'] ?? '');
			$feat['icon'] = sanitize_text_field($feat['icon'] ?? '');
			$feat['feature_image_id'] = sanitize_text_field($feat['feature_image_id'] ?? '');
			$feat['price'] = floatval($feat['price'] ?? 0);
			$feat['billing_type'] = sanitize_text_field($feat['billing_type'] ?? 'one-off');
			$feat['order'] = intval($feat['order'] ?? 0);
			$feat['enabled'] = !empty($feat['enabled']) ? 1 : 0;
			$feat['sku'] = sanitize_text_field($feat['sku'] ?? '');
			// Incompatibility array
			if (isset($feat['incompatible_with']) && is_array($feat['incompatible_with'])) {
				$clean = [];
				foreach ($feat['incompatible_with'] as $cid) {
					$cid = sanitize_text_field($cid);
					if ($cid !== '' && $cid !== $feat['id']) {
						$clean[] = $cid;
					}
				}
				$feat['incompatible_with'] = array_unique($clean);
			} else {
				$feat['incompatible_with'] = array();
			}
		}
		unset($feat); // Break reference

		// Step 3: Update incompatible_with references to use new feature IDs if any were renamed
		if (!empty($renamed_features)) {
			foreach ($options['features'] as &$feat) {
				if (empty($feat['incompatible_with']) || !is_array($feat['incompatible_with'])) {
					continue;
				}
				$new_incompat = [];
				foreach ($feat['incompatible_with'] as $cid) {
					if (isset($renamed_features[$cid])) {
						$new_incompat[] = $renamed_features[$cid];
					} else {
						$new_incompat[] = $cid;
					}
				}
				$feat['incompatible_with'] = array_unique($new_incompat);
			}
		}
		unset($feat); // Break reference before next loop

		// Step 4: Update feature category references if categories were renamed
		if (!empty($renamed_categories)) {
			foreach ($options['features'] as &$feat) {
				if (isset($renamed_categories[$feat['category_id']])) {
					$feat['category_id'] = $renamed_categories[$feat['category_id']];
				}
			}
		}
		unset($feat); // Break reference

		// Clean up: remove temporary original_id from categories (should not be stored)
		foreach ($options['categories'] as &$cat) {
			unset($cat['original_id']);
		}
		unset($cat); // Break reference

		return [
			'options' => $options,
			'report' => $report
		];
	}

	/**
	 * Check data integrity without modifying.
	 *
	 * @param array $options Optional. Options array to check. If empty, uses current options from database.
	 * @return array ['status' => 'ok|issues', 'issues' => array of strings, 'summary' => array]
	 */
	public function check_data_integrity(array $options = []): array {
		if (empty($options)) {
			$options = $this->get_options();
		}

		$issues = [];
		$summary = [
			'total_categories' => 0,
			'total_features' => 0,
			'duplicate_category_ids' => 0,
			'duplicate_feature_ids' => 0,
			'orphaned_features' => 0,
			'missing_feature_ids' => 0,
		];

		// Ensure arrays exist and are actually arrays
		$categories = isset($options['categories']) && is_array($options['categories']) ? $options['categories'] : [];
		$features = isset($options['features']) && is_array($options['features']) ? $options['features'] : [];

		$summary['total_categories'] = count($categories);
		$summary['total_features'] = count($features);

		// If no data, return ok (nothing to check)
		if (empty($categories) && empty($features)) {
			return [
				'status' => 'ok',
				'issues' => [],
				'summary' => $summary,
				'checked_at' => current_time('mysql'),
			];
		}

		try {
			// === Category Checks ===

			// 1. Duplicate category IDs (only if categories have valid ID fields)
			$catIds = [];
			foreach ($categories as $cat) {
				if (isset($cat['id']) && $cat['id'] !== '') {
					$catIds[] = (string) $cat['id'];
				}
			}
			if (!empty($catIds)) {
				$idCounts = array_count_values($catIds);
				$duplicateCatIds = array_filter($idCounts, function($count) { return $count > 1; });
				if (!empty($duplicateCatIds)) {
					$summary['duplicate_category_ids'] = count($duplicateCatIds);
					$issues[] = 'Duplicate category IDs found: ' . implode(', ', array_keys($duplicateCatIds)) . '. Each category must have a unique ID.';
				}
			}

			// 2. Categories missing required fields (id, name)
			foreach ($categories as $idx => $cat) {
				if (empty($cat['id'])) {
					$issues[] = "Category at index $idx is missing an ID.";
				}
				if (empty($cat['name'])) {
					$catId = isset($cat['id']) ? $cat['id'] : '(no ID)';
					$issues[] = "Category '$catId' (index $idx) is missing a name.";
				}
			}

			// Build category ID map for feature validation (only valid IDs)
			$validCategoryIds = [];
			foreach ($categories as $cat) {
				if (!empty($cat['id'])) {
					$validCategoryIds[$cat['id']] = true;
				}
			}

			// === Feature Checks ===

			// 3. Duplicate feature IDs (only check features with valid IDs)
			$featIds = [];
			foreach ($features as $feat) {
				if (isset($feat['id']) && $feat['id'] !== '') {
					$featIds[] = (string) $feat['id'];
				}
			}
			if (!empty($featIds)) {
				$featIdCounts = array_count_values($featIds);
				$duplicateFeatIds = array_filter($featIdCounts, function($count) { return $count > 1; });
				if (!empty($duplicateFeatIds)) {
					$summary['duplicate_feature_ids'] = count($duplicateFeatIds);
					$issues[] = 'Duplicate feature IDs found: ' . implode(', ', array_keys($duplicateFeatIds)) . '. Each feature must have a unique ID.';
				}
			}

			// 4. Features missing required fields
			foreach ($features as $idx => $feat) {
				$featId = isset($feat['id']) && $feat['id'] !== '' ? $feat['id'] : "(no ID, index $idx)";
				if (empty($feat['id'])) {
					$summary['missing_feature_ids']++;
					$issues[] = "Feature at index $idx is missing an ID.";
				}
				if (empty($feat['name'] ?? '')) {
					$issues[] = "Feature '$featId' is missing a name.";
				}
				if (empty($feat['category_id'] ?? '')) {
					$issues[] = "Feature '$featId' is not assigned to any category.";
				} elseif (!isset($validCategoryIds[$feat['category_id']])) {
					$summary['orphaned_features']++;
					$issues[] = "Feature '$featId' references non-existent category '{$feat['category_id']}'.";
				}
				// Validate numeric fields (skip if not set or non-numeric)
				if (isset($feat['price']) && $feat['price'] !== '' && !is_numeric($feat['price'])) {
					$issues[] = "Feature '$featId' has invalid price: '{$feat['price']}'.";
				}
				if (isset($feat['order'])) {
					$order = $feat['order'];
					if (!is_int($order) && !(is_string($order) && ctype_digit($order))) {
						$issues[] = "Feature '$featId' has invalid order: '$order' (must be integer).";
					}
				}
			}

			// 5. Incompatibility self-references
			foreach ($features as $feat) {
				$featId = $feat['id'] ?? '(no ID)';
				if (!empty($feat['incompatible_with']) && is_array($feat['incompatible_with'])) {
					foreach ($feat['incompatible_with'] as $cid) {
						if ($cid === $featId) {
							$issues[] = "Feature '$featId' has self-reference in incompatible_with.";
							break;
						}
					}
				}
			}

			// 6. Category order consistency (basic check)
			$categoryOrders = [];
			foreach ($categories as $cat) {
				if (isset($cat['order'])) {
					$categoryOrders[] = (int) $cat['order'];
				}
			}
			if (!empty($categoryOrders)) {
				sort($categoryOrders, SORT_NUMERIC);
				$expectedOrder = 1;
				foreach ($categoryOrders as $order) {
					if ($order !== $expectedOrder) {
						$issues[] = "Category order sequence has gaps or duplicates. Expected order $expectedOrder, got $order.";
						break;
					}
					$expectedOrder++;
				}
			}

			// 7. Feature order consistency within categories (basic check)
			foreach ($categories as $cat) {
				$catId = $cat['id'] ?? '(no ID)';
				$catFeats = [];
				foreach ($features as $f) {
					if ($f['category_id'] === $catId) {
						$catFeats[] = $f;
					}
				}
				if (empty($catFeats)) continue;
				$orders = [];
				foreach ($catFeats as $f) {
					if (isset($f['order'])) {
						$orders[] = (int) $f['order'];
					}
				}
				if (!empty($orders)) {
					sort($orders, SORT_NUMERIC);
					$expected = 1;
					foreach ($orders as $ord) {
						if ($ord !== $expected) {
							$issues[] = "Category '$catId' has out-of-sequence feature orders.";
							break;
						}
						$expected++;
					}
				}
			}

		} catch (Throwable $e) {
			// Defensive: catch any unexpected errors and report
			$issues[] = 'Unexpected error during integrity check: ' . $e->getMessage();
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log('WP Configurator: Integrity check error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
			}
		}

		// Build result
		$status = empty($issues) ? 'ok' : 'issues';

		return [
			'status' => $status,
			'issues' => $issues,
			'summary' => $summary,
			'checked_at' => current_time('mysql'),
		];
	}

	/**
	 * Log integrity check results if WP_DEBUG is enabled.
	 *
	 * @param array $check_result Result from check_data_integrity().
	 */
	private function log_integrity_check(array $check_result): void {
		if (defined('WP_DEBUG') && WP_DEBUG) {
			if ($check_result['status'] === 'ok') {
				error_log('WP Configurator: Data integrity check passed - ' . $check_result['summary']['total_categories'] . ' categories, ' . $check_result['summary']['total_features'] . ' features.');
			} else {
				error_log('WP Configurator: Data integrity issues detected: ' . print_r($check_result['issues'], true));
			}
		}
	}
}
