<?php
/**
 * Trait Cost_Calculation
 * Handles cost calculation and formatting
 *
 * @package WP_Configurator_Wizard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait Cost_Calculation {

	/**
	 * AJAX handler for cost calculation
	 */
	public function ajax_calculate_cost() {
		// Check nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp_configurator_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed' ) );
		}

		$selected_items = isset( $_POST['selected_items'] ) ? $_POST['selected_items'] : array();

		if ( ! is_array( $selected_items ) ) {
			wp_send_json_error( array( 'message' => 'Invalid selected items' ) );
		}

		// Transform to category => selection map (keep first per category)
		$options = array();
		foreach ( $selected_items as $item ) {
			if ( isset( $item['category_id'] ) && isset( $item['id'] ) ) {
				$cat = $item['category_id'];
				if ( ! array_key_exists( $cat, $options ) ) {
					$options[ $cat ] = $item['id'];
				}
			}
		}

		$total = $this->calculate_cost( $options );
		$formatted = $this->format_currency( $total );

		wp_send_json_success( array(
			'total'     => $total,
			'formatted' => $formatted,
		) );
	}

	/**
	 * Calculate cost based on selected options
	 *
	 * @param array $options Selected options (category_id => feature_id)
	 * @return float Total cost
	 */
	private function calculate_cost( array $options ): float {
		// Base cost
		$total = 0;

		// Get saved pricing from options
		$saved_options = $this->settings_manager->get_options();

		// Build pricing arrays
		$pricing = array();

		// Packages (pages) pricing
		if ( ! empty( $saved_options['packages'] ) ) {
			foreach ( $saved_options['packages'] as $pkg ) {
				if ( $pkg['enabled'] ) {
					$pricing['pages'][ $pkg['id'] ] = $pkg['price'];
				}
			}
		}

		// Features pricing by category
		if ( ! empty( $saved_options['features'] ) ) {
			foreach ( $saved_options['features'] as $feat ) {
				if ( $feat['enabled'] ) {
					$pricing[ $feat['category_id'] ][ $feat['id'] ] = $feat['price'];
				}
			}
		}

		// Calculate based on selected options
		foreach ( $options as $category => $selection ) {
			if ( isset( $pricing[ $category ] ) && isset( $pricing[ $category ][ $selection ] ) ) {
				$total += $pricing[ $category ][ $selection ];
			}
		}

		return apply_filters( 'wp_configurator_total_cost', $total, $options );
	}

	/**
	 * Format currency
	 *
	 * @param float $amount
	 * @return string
	 */
	private function format_currency( float $amount ): string {
		return '€' . number_format( $amount, 2 );
	}
}
