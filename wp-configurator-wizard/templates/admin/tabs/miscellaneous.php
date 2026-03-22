<?php
/**
 * Miscellaneous Settings tab template
 *
 * Expected variables:
 * - $options: array of plugin options
 */
?>
<!-- Tab: Miscellaneous Settings -->
<div id="miscellaneous" class="wp-configurator-tab-content">
	<div class="wp-configurator-settings-section">

		<!-- Section: Email & Notifications -->
		<div class="settings-section">
			<h2 class="section-title">
				<span class="section-icon">📧</span>
				<?php esc_html_e( 'Email & Notifications', 'wp-configurator' ); ?>
			</h2>
			<p class="section-description"><?php esc_html_e( 'Configure email notifications and webhook integrations for quote requests.', 'wp-configurator' ); ?></p>

			<div class="wp-configurator-field-row">
				<div class="wp-configurator-field-group">
					<label for="webhook_url"><?php esc_html_e( 'Webhook URL', 'wp-configurator' ); ?></label>
					<input type="url" id="webhook_url" name="wp_configurator_options[settings][webhook_url]" value="<?php echo esc_attr( $options['settings']['webhook_url'] ?? '' ); ?>" class="regular-text" placeholder="https://example.com/webhook">
					<p class="description"><?php esc_html_e( 'Enter a webhook URL to receive real-time notifications when a configuration is completed.', 'wp-configurator' ); ?></p>
				</div>
				<div class="wp-configurator-field-group">
					<label for="notification_email"><?php esc_html_e( 'Admin Notification Email', 'wp-configurator' ); ?></label>
					<input type="email" id="notification_email" name="wp_configurator_options[settings][notification_email]" value="<?php echo esc_attr( $options['settings']['notification_email'] ?? '' ); ?>" class="regular-text" placeholder="admin@example.com">
					<p class="description"><?php esc_html_e( 'Email address to receive notifications for new quote requests. Leave empty to disable.', 'wp-configurator' ); ?></p>
				</div>
				<div class="wp-configurator-field-group">
					<label for="test_email_address"><?php esc_html_e( 'Test Email Address', 'wp-configurator' ); ?></label>
					<input type="email" id="test_email_address" name="wp_configurator_options[settings][test_email_address]" value="<?php echo esc_attr( $options['settings']['test_email_address'] ?? '' ); ?>" class="regular-text" placeholder="test@example.com">
					<p class="description"><?php esc_html_e( 'Optional: Dedicated email address for sending test emails from System Status. If left empty, the Admin Notification Email will be used.', 'wp-configurator' ); ?></p>
				</div>
			</div>

			<div class="wp-configurator-field-row">
				<div class="wp-configurator-field-group">
					<label for="send_client_email">
						<input type="checkbox" id="send_client_email" name="wp_configurator_options[settings][send_client_email]" value="1" <?php checked( $options['settings']['send_client_email'] ?? 1 ); ?>>
						<?php esc_html_e( 'Send confirmation email to client', 'wp-configurator' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'When enabled, clients receive an email confirmation with their configuration.', 'wp-configurator' ); ?></p>
				</div>
			</div>

			<div class="wp-configurator-field-row full-width">
				<div class="wp-configurator-field-group">
					<label for="client_message"><?php esc_html_e( 'Client Email Message', 'wp-configurator' ); ?></label>
					<p class="description"><?php esc_html_e( 'Custom message sent to the client in the confirmation email. Use {{name}} as a placeholder for the client\'s name.', 'wp-configurator' ); ?></p>
					<?php
					$client_message = $options['settings']['client_message'] ?? 'Many thanks {{name}} for requesting your formal quote. Here is a copy of what you have sent us. If we need any further information we will get in touch. In the meantime we will prepare your quote and send for your consideration in the next 2 business days.';
					wp_editor( $client_message, 'client_message_editor', array(
						'textarea_name' => 'wp_configurator_options[settings][client_message]',
						'teeny' => false,
						'textarea_rows' => 6,
						'media_buttons' => false,
						'tinymce' => array(
							'toolbar1' => 'formatselect,styleselect,bold,italic,underline,bullist,numlist,blockquote,forecolor,backcolor,link,unlink,removeformat,undo,redo,wp_fullscreen',
							'toolbar2' => '',
						),
					) );
					?>
					<p class="description" style="margin-top: 8px;"><?php esc_html_e( 'This message will be included in the email sent to the client after they submit a quote request.', 'wp-configurator' ); ?></p>
				</div>
			</div>
		</div>

		<!-- Section: Frontend Content -->
		<div class="settings-section">
			<h2 class="section-title">
				<span class="section-icon">🎨</span>
				<?php esc_html_e( 'Frontend Content', 'wp-configurator' ); ?>
			</h2>
			<p class="section-description"><?php esc_html_e( 'Customize the text and headings displayed on the frontend wizard.', 'wp-configurator' ); ?></p>

			<!-- Page Header -->
			<h3 class="subsection-title"><?php esc_html_e( 'Page Header', 'wp-configurator' ); ?></h3>

			<div class="wp-configurator-field-row">
				<div class="wp-configurator-field-group">
					<label for="frontend_title"><?php esc_html_e( 'Page Title', 'wp-configurator' ); ?></label>
					<input type="text" id="frontend_title" name="wp_configurator_options[settings][frontend_title]" value="<?php echo esc_attr( $options['settings']['frontend_title'] ?? 'Website Configuration Wizard' ); ?>" class="regular-text" placeholder="Website Configuration Wizard">
					<p class="description"><?php esc_html_e( 'Main heading displayed at the top of the wizard.', 'wp-configurator' ); ?></p>
				</div>
			</div>

			<div class="wp-configurator-field-row full-width">
				<div class="wp-configurator-field-group">
					<label for="frontend_subtitle"><?php esc_html_e( 'Page Subtitle', 'wp-configurator' ); ?></label>
					<?php
					$frontend_subtitle = $options['settings']['frontend_subtitle'] ?? 'Drag & drop features to build your custom package';
					wp_editor( $frontend_subtitle, 'frontend_subtitle_editor', array(
						'textarea_name' => 'wp_configurator_options[settings][frontend_subtitle]',
						'teeny' => false,
						'textarea_rows' => 6,
						'media_buttons' => false,
						'tinymce' => array(
							'toolbar1' => 'formatselect,styleselect,bold,italic,underline,bullist,numlist,blockquote,forecolor,backcolor,link,unlink,removeformat,undo,redo,wp_fullscreen',
							'toolbar2' => '',
						),
					) );
					?>
					<p class="description"><?php esc_html_e( 'Text displayed directly under the page title. Supports <strong>HTML formatting</strong>.', 'wp-configurator' ); ?></p>
				</div>
			</div>

			<!-- Dropzone Area -->
			<h3 class="subsection-title" style="margin-top: 24px;"><?php esc_html_e( 'Dropzone Area', 'wp-configurator' ); ?></h3>

			<div class="wp-configurator-field-row full-width">
				<div class="wp-configurator-field-group">
					<label for="dropzone_footer_text"><?php esc_html_e( 'Footer Text', 'wp-configurator' ); ?></label>
					<?php
					$dropzone_footer = $options['settings']['dropzone_footer_text'] ?? 'Final price may vary based on specific requirements';
					wp_editor( $dropzone_footer, 'dropzone_footer_text_editor', array(
						'textarea_name' => 'wp_configurator_options[settings][dropzone_footer_text]',
						'teeny' => false,
						'textarea_rows' => 6,
						'media_buttons' => false,
						'tinymce' => array(
							'toolbar1' => 'formatselect,styleselect,bold,italic,underline,bullist,numlist,blockquote,forecolor,backcolor,link,unlink,removeformat,undo,redo,wp_fullscreen',
							'toolbar2' => '',
						),
					) );
					?>
					<p class="description"><?php esc_html_e( 'Text displayed below the totals in the dropzone area. Supports <strong>HTML formatting</strong>.', 'wp-configurator' ); ?></p>
				</div>
			</div>

			<div class="wp-configurator-field-row">
				<div class="wp-configurator-field-group">
					<label for="quote_button_text"><?php esc_html_e( 'Submit Button Text', 'wp-configurator' ); ?></label>
					<input type="text" id="quote_button_text" name="wp_configurator_options[settings][quote_button_text]" value="<?php echo esc_attr( $options['settings']['quote_button_text'] ?? 'Convert to Quote' ); ?>" class="regular-text" placeholder="Convert to Quote">
					<p class="description"><?php esc_html_e( 'Text displayed on the button that submits the quote request.', 'wp-configurator' ); ?></p>
				</div>
			</div>
		</div>

		<!-- Section: Display & Layout -->
		<div class="settings-section">
			<h2 class="section-title">
				<span class="section-icon">📱</span>
				<?php esc_html_e( 'Display & Layout', 'wp-configurator' ); ?>
			</h2>
			<p class="section-description"><?php esc_html_e( 'Control the appearance and behavior of the frontend wizard.', 'wp-configurator' ); ?></p>

			<div class="wp-configurator-field-row">
				<div class="wp-configurator-field-group">
					<label for="collapsible_categories">
						<input type="checkbox" id="collapsible_categories" name="wp_configurator_options[settings][collapsible_categories]" value="1" <?php checked( $options['settings']['collapsible_categories'] ?? 0 ); ?>>
						<?php esc_html_e( 'Enable collapsible categories', 'wp-configurator' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Allow users to collapse and expand feature categories on the frontend.', 'wp-configurator' ); ?></p>
				</div>
				<div class="wp-configurator-field-group">
					<label for="accordion_mode">
						<input type="checkbox" id="accordion_mode" name="wp_configurator_options[settings][accordion_mode]" value="1" <?php checked( $options['settings']['accordion_mode'] ?? 0 ); ?>>
						<?php esc_html_e( 'Accordion mode', 'wp-configurator' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'When enabled, only one category can be open at a time. Requires collapsible categories to be enabled.', 'wp-configurator' ); ?></p>
				</div>
			</div>

			<!-- Custom Text Styles for TinyMCE -->
			<h3 class="subsection-title"><?php esc_html_e( 'Custom Text Styles', 'wp-configurator' ); ?></h3>
			<p class="description subsection-description"><?php esc_html_e( 'Define custom CSS classes to appear in the TinyMCE Styles dropdown. Useful for Elementor global styles or custom theme classes.', 'wp-configurator' ); ?></p>

			<div class="wp-configurator-field-row full-width">
				<div class="wp-configurator-field-group">
					<label for="custom_styles"><?php esc_html_e( 'Available Styles', 'wp-configurator' ); ?></label>
					<p class="description"><?php esc_html_e( 'Enter one style per line in format: "Style Name: css-class-name". Example: "My Button: my-button-class". The style will appear in the TinyMCE Styles dropdown with the given name and apply the specified CSS class(es).', 'wp-configurator' ); ?></p>
					<textarea id="custom_styles" name="wp_configurator_options[settings][custom_styles]" rows="4" class="large-text" placeholder="Elementor Heading: elementor-heading-title&#10;Primary Button: btn btn-primary"><?php echo esc_textarea( $options['settings']['custom_styles'] ?? '' ); ?></textarea>
					<p class="description" style="margin-top: 4px;"><?php esc_html_e( 'Tip: View your site source to find Elementor-generated class names (look for class="elementor-..."). Add them here to make them available in the editor.', 'wp-configurator' ); ?></p>
				</div>
			</div>


			<h3 class="subsection-title"><?php esc_html_e( 'Responsive Tile Layout', 'wp-configurator' ); ?></h3>
			<p class="description subsection-description"><?php esc_html_e( 'Configure how many feature tiles are displayed per row on different screen sizes.', 'wp-configurator' ); ?></p>

			<div class="wp-configurator-field-row layout-row">
				<div class="wp-configurator-field-group">
					<label for="tiles_per_row_desktop">
						<?php esc_html_e( 'Desktop (≥1200px)', 'wp-configurator' ); ?>
						<span class="value-display" id="desktop-count"><?php echo esc_html( $options['settings']['tiles_per_row_desktop'] ?? 4 ); ?></span>
					</label>
					<input type="range" id="tiles_per_row_desktop" name="wp_configurator_options[settings][tiles_per_row_desktop]" value="<?php echo esc_attr( $options['settings']['tiles_per_row_desktop'] ?? 4 ); ?>" min="1" max="12" step="1" class="range-slider">
					<p class="description"><?php esc_html_e( 'Number of tiles per row on large screens. Default: 4', 'wp-configurator' ); ?></p>
				</div>
				<div class="wp-configurator-field-group">
					<label for="tiles_per_row_tablet">
						<?php esc_html_e( 'Tablet (768px - 1199px)', 'wp-configurator' ); ?>
						<span class="value-display" id="tablet-count"><?php echo esc_html( $options['settings']['tiles_per_row_tablet'] ?? 3 ); ?></span>
					</label>
					<input type="range" id="tiles_per_row_tablet" name="wp_configurator_options[settings][tiles_per_row_tablet]" value="<?php echo esc_attr( $options['settings']['tiles_per_row_tablet'] ?? 3 ); ?>" min="1" max="8" step="1" class="range-slider">
					<p class="description"><?php esc_html_e( 'Number of tiles per row on tablets. Default: 3', 'wp-configurator' ); ?></p>
				</div>
				<div class="wp-configurator-field-group">
					<label for="tiles_per_row_mobile">
						<?php esc_html_e( 'Mobile (<768px)', 'wp-configurator' ); ?>
						<span class="value-display" id="mobile-count"><?php echo esc_html( $options['settings']['tiles_per_row_mobile'] ?? 1 ); ?></span>
					</label>
					<input type="range" id="tiles_per_row_mobile" name="wp_configurator_options[settings][tiles_per_row_mobile]" value="<?php echo esc_attr( $options['settings']['tiles_per_row_mobile'] ?? 1 ); ?>" min="1" max="4" step="1" class="range-slider">
					<p class="description"><?php esc_html_e( 'Number of tiles per row on mobile devices. Default: 1', 'wp-configurator' ); ?></p>
				</div>
			</div>
		</div>

		<!-- Section: Tracking & Privacy -->
		<div class="settings-section">
			<h2 class="section-title">
				<span class="section-icon">🔒</span>
				<?php esc_html_e( 'Tracking & Privacy', 'wp-configurator' ); ?>
			</h2>
			<p class="section-description"><?php esc_html_e( 'Control interaction tracking and data collection for analytics.', 'wp-configurator' ); ?></p>

			<div class="wp-configurator-field-row">
				<div class="wp-configurator-field-group">
					<label for="exclude_admin_ip">
						<input type="checkbox" id="exclude_admin_ip" name="wp_configurator_options[settings][exclude_admin_ip]" value="1" <?php checked( $options['settings']['exclude_admin_ip'] ?? 0 ); ?>>
						<?php esc_html_e( 'Exclude admin IP from interaction tracking', 'wp-configurator' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Prevent your visits from being tracked in analytics. Useful during development and testing.', 'wp-configurator' ); ?></p>
				</div>
			</div>

			<div class="wp-configurator-field-row ip-row">
				<div class="wp-configurator-field-group">
					<label for="admin_ip_address"><?php esc_html_e( 'Admin IP Address', 'wp-configurator' ); ?></label>
					<div class="input-with-button">
						<input type="text" id="admin_ip_address" name="wp_configurator_options[settings][admin_ip_address]" value="<?php echo esc_attr( $options['settings']['admin_ip_address'] ?? '' ); ?>" class="regular-text" placeholder="e.g., 192.168.1.1">
						<button type="button" id="detect_ip_btn" class="button button-secondary"><?php esc_html_e( 'Detect My IP', 'wp-configurator' ); ?></button>
					</div>
					<p class="description"><?php esc_html_e( 'Your IP address will be excluded from tracking when the option above is enabled.', 'wp-configurator' ); ?></p>
					<p class="description" style="margin-top: 4px;"><?php esc_html_e( 'Your current IP: <strong id="current_ip">', 'wp-configurator' ); ?><?php echo esc_html( $_SERVER['REMOTE_ADDR'] ?? 'Unknown' ); ?></strong></p>
				</div>
			</div>

			<!-- Exclude Zero-Cost Items from Stats -->
			<div class="wp-configurator-field-row">
				<div class="wp-configurator-field-group">
					<label for="exclude_zero_cost_from_stats">
						<input type="checkbox" id="exclude_zero_cost_from_stats" name="wp_configurator_options[settings][exclude_zero_cost_from_stats]" value="1" <?php checked( $options['settings']['exclude_zero_cost_from_stats'] ?? 0 ); ?>>
						<?php esc_html_e( 'Exclude zero-cost items from statistics', 'wp-configurator' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'When enabled, features with a price of €0 will be excluded from statistics (top features, unique features count, revenue totals). This gives a cleaner view of paid features only.', 'wp-configurator' ); ?></p>
				</div>
			</div>
		</div>

	</div>
</div>
