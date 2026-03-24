<?php
/**
 * Asset Manager class
 * Handles all frontend and admin asset enqueueing
 *
 * @package WP_Configurator_Wizard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Asset_Manager {

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
	 * @param string           $version
	 * @param Settings_Manager $settings_manager
	 */
	public function __construct( $version, Settings_Manager $settings_manager ) {
		$this->version = $version;
		$this->settings_manager = $settings_manager;

		$this->register_hooks();
	}

	/**
	 * Register asset hooks
	 */
	private function register_hooks(): void {
		// Frontend assets
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );

		// Admin assets
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// Inline responsive CSS (frontend)
		add_action( 'wp_head', array( $this, 'output_responsive_css' ) );
	}

	/**
	 * Enqueue frontend assets
	 */
	public function enqueue_frontend_assets() {
		// Only on frontend, not admin
		if ( is_admin() ) {
			return;
		}

		// Enqueue frontend styles
		wp_enqueue_style(
			'wp-configurator-style',
			plugins_url( 'assets/css/style.css', WP_CONFIGURATOR_WIZARD_FILE ),
			array(),
			$this->version
		);

		// Enqueue frontend script
		wp_enqueue_script(
			'wp-configurator-wizard-script',
			plugins_url( 'assets/js/wizard.js', WP_CONFIGURATOR_WIZARD_FILE ),
			array( 'jquery' ),
			filemtime( plugin_dir_path( WP_CONFIGURATOR_WIZARD_FILE ) . 'assets/js/wizard.js' ),
			true
		);

		// Localize script with data
		$options = $this->settings_manager->get_options();
		wp_localize_script(
			'wp-configurator-wizard-script',
			'wpConfigurator',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'wp_configurator_nonce' ),
				'currency' => '€',
				'decimal'  => 2,
				'options'  => $options,
				'i18n'     => array(
					'collapse_all' => __( 'Collapse All', 'wp-configurator' ),
					'expand_all'   => __( 'Expand All', 'wp-configurator' ),
				),
			)
		);
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Admin page hook
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only load on our settings page
		// Temporarily commented for debugging
		// if ( $hook !== 'toplevel_page_wp-configurator-settings' ) {
		// 	return;
		// }

		// Debug: log that we're enqueueing
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'wp-configurator-wizard: enqueue_admin_assets called on hook: ' . $hook );
		}

		// Debug: check if settings manager has options
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$test_options = $this->settings_manager->get_options();
			error_log( 'wp-configurator-wizard: options count - categories: ' . count( $test_options['categories'] ?? [] ) . ', features: ' . count( $test_options['features'] ?? [] ) );
		}

		// Enqueue Chart.js from CDN
		wp_enqueue_script(
			'chartjs',
			'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
			array(),
			'4.4.1',
			true
		);

		// Custom admin CSS for stats
		wp_enqueue_style(
			'wp-configurator-admin-stats',
			plugins_url( 'assets/css/admin-stats.css', WP_CONFIGURATOR_WIZARD_FILE ),
			array(),
			$this->version
		);

		// Enqueue admin CSS
		wp_enqueue_style(
			'wp-configurator-admin',
			plugins_url( 'assets/css/admin.css', WP_CONFIGURATOR_WIZARD_FILE ),
			array( 'wp-configurator-admin-stats' ),
			$this->version
		);

		// Enqueue admin common utilities (must load before admin.js)
		wp_enqueue_script(
			'wp-configurator-admin-common',
			plugins_url( 'assets/js/admin/admin-common.js', WP_CONFIGURATOR_WIZARD_FILE ),
			array( 'jquery' ),
			$this->version,
			true
		);

		// Enqueue admin tabs module (depends on common for state utilities)
		wp_enqueue_script(
			'wp-configurator-admin-tabs',
			plugins_url( 'assets/js/admin/admin-tabs.js', WP_CONFIGURATOR_WIZARD_FILE ),
			array( 'jquery', 'wp-configurator-admin-common' ),
			$this->version,
			true
		);

		// Enqueue admin emoji picker module
		wp_enqueue_script(
			'wp-configurator-admin-emoji',
			plugins_url( 'assets/js/admin/admin-emoji.js', WP_CONFIGURATOR_WIZARD_FILE ),
			array( 'jquery' ),
			$this->version,
			true
		);

		// Enqueue admin settings module (depends on common for state utilities)
		wp_enqueue_script(
			'wp-configurator-admin-settings',
			plugins_url( 'assets/js/admin/admin-settings.js', WP_CONFIGURATOR_WIZARD_FILE ),
			array( 'jquery', 'wp-configurator-admin-common' ),
			$this->version,
			true
		);

		// Enqueue admin import/export module (depends on common and tabs)
		wp_enqueue_script(
			'wp-configurator-admin-import-export',
			plugins_url( 'assets/js/admin/admin-import-export.js', WP_CONFIGURATOR_WIZARD_FILE ),
			array( 'jquery', 'wp-configurator-admin-common', 'wp-configurator-admin-tabs' ),
			$this->version,
			true
		);

		// Enqueue admin JS (depends on all modular components)
		wp_enqueue_script(
			'wp-configurator-admin',
			plugins_url( 'assets/js/admin.js', WP_CONFIGURATOR_WIZARD_FILE ),
			array(
				'jquery',
				'wp-configurator-admin-common',
				'wp-configurator-admin-tabs',
				'wp-configurator-admin-settings',
				'wp-configurator-admin-emoji',
				'wp-configurator-admin-import-export'
			),
			$this->version,
			true
		);

		// Localize admin data
		$options = $this->settings_manager->get_options();
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'wp-configurator-wizard: Localizing admin data. Categories count: ' . count( $options['categories'] ?? array() ) . ', Features count: ' . count( $options['features'] ?? array() ) );
		}
		$admin_data = array(
			'categoryIndex' => count( $options['categories'] ?? array() ),
			'featureIndex'  => count( $options['features'] ?? array() ),
			'categories'    => $options['categories'],
			'features'      => $options['features'],
			'exportNonce'   => wp_create_nonce( 'wp_configurator_nonce' ),
			'settings'      => $options['settings'] ?? array(),
		);
		wp_localize_script( 'wp-configurator-admin', 'wpConfiguratorAdmin', $admin_data );

		// Inline script for toast notification on settings saved
		if ( isset( $_GET['settings-updated'] ) ) {
			$js = "jQuery(document).ready(function($) { if (typeof showToast === 'function') { showToast('Settings saved successfully!'); } });";
			wp_add_inline_script( 'wp-configurator-admin', $js );
		}
	}

	/**
	 * Output responsive tile layout CSS inline
	 */
	public function output_responsive_css() {
		// Only output on frontend where wizard may appear
		if ( is_admin() ) {
			return;
		}

		$options = $this->settings_manager->get_options();
		$desktop = isset( $options['settings']['tiles_per_row_desktop'] ) ? intval( $options['settings']['tiles_per_row_desktop'] ) : 4;
		$tablet = isset( $options['settings']['tiles_per_row_tablet'] ) ? intval( $options['settings']['tiles_per_row_tablet'] ) : 3;
		$mobile = isset( $options['settings']['tiles_per_row_mobile'] ) ? intval( $options['settings']['tiles_per_row_mobile'] ) : 1;

		// Ensure reasonable values
		$desktop = max( 1, min( 12, $desktop ) );
		$tablet = max( 1, min( 8, $tablet ) );
		$mobile = max( 1, min( 4, $mobile ) );

		// Calculate percentages
		$desktop_width = floor( 100 / $desktop );
		$tablet_width = floor( 100 / $tablet );
		$mobile_width = floor( 100 / $mobile );

		// Output CSS
		?>
		<style id="wp-configurator-responsive-css">
			/* Responsive tile layout for ATP Quote Configurator */
			@media (min-width: 1200px) {
				.tiles-grid {
					grid-template-columns: repeat(<?php echo $desktop; ?>, 1fr) !important;
				}
				.tiles-grid .tile {
					width: auto !important;
					max-width: none !important;
				}
			}
			@media (min-width: 768px) and (max-width: 1199px) {
				.tiles-grid {
					grid-template-columns: repeat(<?php echo $tablet; ?>, 1fr) !important;
				}
				.tiles-grid .tile {
					width: auto !important;
					max-width: none !important;
				}
			}
			@media (max-width: 767px) {
				.tiles-grid {
					grid-template-columns: repeat(<?php echo $mobile; ?>, 1fr) !important;
				}
				.tiles-grid .tile {
					width: auto !important;
					max-width: none !important;
				}
			}
		</style>
		<?php
	}
}
