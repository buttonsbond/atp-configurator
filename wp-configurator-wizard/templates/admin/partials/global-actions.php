<?php
/**
 * Global action buttons partial
 * Displays Save, Restore, Export, Import buttons
 *
 * Expected variables:
 * - $options: array of plugin options (for nonce generation)
 */
?>
<p class="submit">
	<?php submit_button( __( 'Save', 'wp-configurator' ), 'primary', 'submit', false ); ?>
	<?php
	// Restore to defaults button
	$restore_nonce = wp_create_nonce( 'restore_defaults' );
	?>
	<a href="<?php echo esc_url( add_query_arg( array( 'restore_defaults' => 1, 'restore_nonce' => $restore_nonce ) ) ); ?>"
	   class="button"
	   onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to restore all categories and features to the default settings? This will overwrite any custom changes.', 'wp-configurator' ); ?>');">
	   <?php esc_html_e( 'Restore to Defaults', 'wp-configurator' ); ?>
	</a>
	<button type="button" id="export-settings" class="button button-secondary"><?php esc_html_e( 'Export Settings', 'wp-configurator' ); ?></button>
	<button type="button" id="import-settings-btn" class="button button-secondary"><?php esc_html_e( 'Import Settings', 'wp-configurator' ); ?></button>
</p>
