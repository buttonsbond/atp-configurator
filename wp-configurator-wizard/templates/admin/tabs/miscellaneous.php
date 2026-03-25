<?php
/**
 * Miscellaneous Settings tab template - Modern UI
 *
 * Expected variables:
 * - $options: array of plugin options
 */
$frontend_title = $options['settings']['frontend_title'] ?? 'Website Configuration Wizard';
$frontend_subtitle = $options['settings']['frontend_subtitle'] ?? 'Drag & drop features to build your custom package';
?>
<!-- Tab: Miscellaneous Settings -->
<div id="miscellaneous" class="wp-configurator-tab-content">
	<div class="wp-configurator-settings-section">

		<!-- Section: Email & Notifications (Collapsible) -->
		<div class="settings-section collapsed" id="section-email">
			<div class="section-header" onclick="toggleSection('section-email')">
				<span class="section-icon">📧</span>
				<div class="section-title-group">
					<h2 class="section-title"><?php esc_html_e( 'Email & Notifications', 'wp-configurator' ); ?></h2>
					<p class="section-description"><?php esc_html_e( 'Configure email notifications and webhook integrations for quote requests.', 'wp-configurator' ); ?></p>
				</div>
				<span class="section-toggle">▶</span>
			</div>
			<div class="section-content" style="display: none;">

				<!-- Integration Settings Card -->
				<div class="settings-card">
					<div class="settings-card-header">
						<span class="settings-card-icon">⚙️</span>
						<h3 class="settings-card-title"><?php esc_html_e( 'Integration Settings', 'wp-configurator' ); ?></h3>
					</div>
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
				</div>

				<!-- Client Email Settings Card -->
				<div class="settings-card">
					<div class="settings-card-header">
						<span class="settings-card-icon">📨</span>
						<h3 class="settings-card-title"><?php esc_html_e( 'Client Confirmation Email', 'wp-configurator' ); ?></h3>
					</div>
					<div class="wp-configurator-field-row">
						<div class="wp-configurator-field-group">
							<label for="send_client_email" class="toggle-label">
								<div class="toggle-switch">
									<input type="checkbox" id="send_client_email" name="wp_configurator_options[settings][send_client_email]" value="1" <?php checked( $options['settings']['send_client_email'] ?? 1 ); ?> role="switch" aria-checked="<?php echo ! empty( $options['settings']['send_client_email'] ) ? 'true' : 'false'; ?>">
									<span class="toggle-track"></span>
								</div>
								<span class="toggle-label-text"><?php esc_html_e( 'Send confirmation email to client', 'wp-configurator' ); ?></span>
							</label>
							<p class="toggle-description"><?php esc_html_e( 'When enabled, clients receive an email confirmation with their configuration.', 'wp-configurator' ); ?></p>
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
			</div>
		</div>

		<!-- Section: Frontend Content (Collapsible) -->
		<div class="settings-section collapsed" id="section-frontend">
			<div class="section-header" onclick="toggleSection('section-frontend')">
				<span class="section-icon">🎨</span>
				<div class="section-title-group">
					<h2 class="section-title"><?php esc_html_e( 'Frontend Content', 'wp-configurator' ); ?></h2>
					<p class="section-description"><?php esc_html_e( 'Customize the text and headings displayed on the frontend wizard.', 'wp-configurator' ); ?></p>
				</div>
				<span class="section-toggle">▶</span>
			</div>
			<div class="section-content" style="display: none;">

				<!-- Page Header Settings Card -->
				<div class="settings-card">
					<div class="settings-card-header">
						<span class="settings-card-icon">📄</span>
						<h3 class="settings-card-title"><?php esc_html_e( 'Page Header', 'wp-configurator' ); ?></h3>
					</div>

					<!-- Live Preview Toggle -->
					<div class="wp-configurator-field-row">
						<div class="wp-configurator-field-group" style="flex-direction: row; align-items: center; gap: 12px;">
							<label for="enable_live_preview" class="toggle-label">
								<div class="toggle-switch">
									<input type="checkbox" id="enable_live_preview" <?php checked( $options['settings']['enable_live_preview'] ?? 1 ); ?> role="switch" aria-checked="<?php echo ! empty( $options['settings']['enable_live_preview'] ) ? 'true' : 'false'; ?>">
									<span class="toggle-track"></span>
								</div>
								<span class="toggle-label-text"><?php esc_html_e( 'Live Preview', 'wp-configurator' ); ?></span>
							</label>
							<span class="description"><?php esc_html_e( 'Preview changes in real-time', 'wp-configurator' ); ?></span>
						</div>
					</div>

					<div class="wp-configurator-field-row">
						<div class="wp-configurator-field-group">
							<label for="frontend_title">
								<?php esc_html_e( 'Page Title', 'wp-configurator' ); ?>
								<span class="tooltip-trigger" data-tooltip="<?php esc_attr_e( 'Main heading displayed at the top of the wizard', 'wp-configurator' ); ?>">ⓘ</span>
							</label>
							<input type="text" id="frontend_title" name="wp_configurator_options[settings][frontend_title]" value="<?php echo esc_attr( $options['settings']['frontend_title'] ?? 'Website Configuration Wizard' ); ?>" class="regular-text" placeholder="Website Configuration Wizard">
						</div>
					</div>

					<div class="wp-configurator-field-row full-width">
						<div class="wp-configurator-field-group">
							<label for="frontend_subtitle">
								<?php esc_html_e( 'Page Subtitle', 'wp-configurator' ); ?>
								<span class="tooltip-trigger" data-tooltip="<?php esc_attr_e( 'Text displayed directly under the page title. Supports HTML formatting.', 'wp-configurator' ); ?>">ⓘ</span>
							</label>
							<?php
							$frontend_subtitle = $options['settings']['frontend_subtitle'] ?? 'Drag & drop features to build your custom package - you\'ll need at least one of the items from each compulsory section before you can request a quote. This will give you an estimate of how much your new website might cost you. If you are more or less happy you can request a formal quote.\n\n<strong>If you use the site planner for sites with more than 1 page you may be eligible for a discount.</strong>';
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
						</div>
					</div>
				</div>

				<!-- Live Preview Pane -->
				<div class="preview-pane">
					<div class="preview-header">
						<span>👁️</span>
						<?php esc_html_e( 'Live Preview', 'wp-configurator' ); ?>
					</div>
					<div class="preview-content" id="preview-content">
						<h1 id="preview-title"><?php echo esc_html( $options['settings']['frontend_title'] ?? 'Website Configuration Wizard' ); ?></h1>
						<p id="preview-subtitle"><?php echo wp_kses_post( $options['settings']['frontend_subtitle'] ?? 'Drag & drop features to build your custom package - you\'ll need at least one of the items from each compulsory section before you can request a quote. This will give you an estimate of how much your new website might cost you. If you are more or less happy you can request a formal quote.' ); ?></p>
					</div>
				</div>

				<!-- Dropzone Settings Card -->
				<div class="settings-card">
					<div class="settings-card-header">
						<span class="settings-card-icon">🎯</span>
						<h3 class="settings-card-title"><?php esc_html_e( 'Dropzone Area', 'wp-configurator' ); ?></h3>
					</div>
					<div class="wp-configurator-field-row full-width">
						<div class="wp-configurator-field-group">
							<label for="dropzone_footer_text"><?php esc_html_e( 'Footer Text', 'wp-configurator' ); ?></label>
							<?php
							$dropzone_footer = $options['settings']['dropzone_footer_text'] ?? 'Final price may vary based on specific requirements. Prices do not include IVA.\n\n<em>Once you request your quote we may contact you for additional information before sending you your quote.</em>';
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
						</div>
					</div>
					<p class="description"><?php esc_html_e( 'Text displayed below the totals in the dropzone area. Supports <strong>HTML formatting</strong>.', 'wp-configurator' ); ?></p>
				</div>

				<div class="wp-configurator-field-row">
					<div class="wp-configurator-field-group">
						<label for="quote_button_text"><?php esc_html_e( 'Submit Button Text', 'wp-configurator' ); ?></label>
						<input type="text" id="quote_button_text" name="wp_configurator_options[settings][quote_button_text]" value="<?php echo esc_attr( $options['settings']['quote_button_text'] ?? 'Convert to Quote' ); ?>" class="regular-text" placeholder="Convert to Quote">
						<p class="description"><?php esc_html_e( 'Text displayed on the button that submits the quote request.', 'wp-configurator' ); ?></p>
					</div>
				</div>
			</div>
		</div>

		<!-- Section: Display & Layout (Collapsible) -->
		<div class="settings-section collapsed" id="section-display">
			<div class="section-header" onclick="toggleSection('section-display')">
				<span class="section-icon">📱</span>
				<div class="section-title-group">
					<h2 class="section-title"><?php esc_html_e( 'Display & Layout', 'wp-configurator' ); ?></h2>
					<p class="section-description"><?php esc_html_e( 'Control the appearance and behavior of the frontend wizard.', 'wp-configurator' ); ?></p>
				</div>
				<span class="section-toggle">▼</span>
			</div>
			<div class="section-content">

				<!-- Category Behavior Card -->
				<div class="settings-card">
					<div class="settings-card-header">
						<span class="settings-card-icon">📂</span>
						<h3 class="settings-card-title"><?php esc_html_e( 'Category Behavior', 'wp-configurator' ); ?></h3>
					</div>
					<div class="wp-configurator-field-row">
						<div class="wp-configurator-field-group">
							<label for="collapsible_categories" class="toggle-label">
								<div class="toggle-switch">
									<input type="checkbox" id="collapsible_categories" name="wp_configurator_options[settings][collapsible_categories]" value="1" <?php checked( $options['settings']['collapsible_categories'] ?? 1 ); ?> role="switch" aria-checked="<?php echo ! empty( $options['settings']['collapsible_categories'] ) ? 'true' : 'false'; ?>">
									<span class="toggle-track"></span>
								</div>
								<span class="toggle-label-text"><?php esc_html_e( 'Enable collapsible categories', 'wp-configurator' ); ?></span>
							</label>
							<p class="toggle-description"><?php esc_html_e( 'Allow users to collapse and expand feature categories on the frontend.', 'wp-configurator' ); ?></p>
						</div>
						<div class="wp-configurator-field-group">
							<label for="accordion_mode" class="toggle-label">
								<div class="toggle-switch">
									<input type="checkbox" id="accordion_mode" name="wp_configurator_options[settings][accordion_mode]" value="1" <?php checked( $options['settings']['accordion_mode'] ?? 1 ); ?> role="switch" aria-checked="<?php echo ! empty( $options['settings']['accordion_mode'] ) ? 'true' : 'false'; ?>">
									<span class="toggle-track"></span>
								</div>
								<span class="toggle-label-text"><?php esc_html_e( 'Accordion mode', 'wp-configurator' ); ?></span>
							</label>
							<p class="toggle-description"><?php esc_html_e( 'When enabled, only one category can be open at a time. Requires collapsible categories to be enabled.', 'wp-configurator' ); ?></p>
						</div>
					</div>
				</div>

				<!-- Responsive Layout Card -->
				<div class="settings-card">
					<div class="settings-card-header">
						<span class="settings-card-icon">📐</span>
						<h3 class="settings-card-title"><?php esc_html_e( 'Responsive Tile Layout', 'wp-configurator' ); ?></h3>
					</div>
					<p class="description" style="margin-bottom: 16px;"><?php esc_html_e( 'Configure how many feature tiles are displayed per row on different screen sizes.', 'wp-configurator' ); ?></p>
					<div class="wp-configurator-field-row layout-row">
						<div class="wp-configurator-field-group">
							<label for="tiles_per_row_desktop">
								<?php esc_html_e( 'Desktop (≥1200px)', 'wp-configurator' ); ?>
								<span class="value-display" id="desktop-count"><?php echo esc_html( $options['settings']['tiles_per_row_desktop'] ?? 3 ); ?></span>
							</label>
							<input type="range" id="tiles_per_row_desktop" name="wp_configurator_options[settings][tiles_per_row_desktop]" value="<?php echo esc_attr( $options['settings']['tiles_per_row_desktop'] ?? 3 ); ?>" min="1" max="12" step="1" class="range-slider">
							<p class="description"><?php esc_html_e( 'Number of tiles per row on large screens. Default: 4', 'wp-configurator' ); ?></p>
						</div>
						<div class="wp-configurator-field-group">
							<label for="tiles_per_row_tablet">
								<?php esc_html_e( 'Tablet (768px - 1199px)', 'wp-configurator' ); ?>
								<span class="value-display" id="tablet-count"><?php echo esc_html( $options['settings']['tiles_per_row_tablet'] ?? 2 ); ?></span>
							</label>
							<input type="range" id="tiles_per_row_tablet" name="wp_configurator_options[settings][tiles_per_row_tablet]" value="<?php echo esc_attr( $options['settings']['tiles_per_row_tablet'] ?? 2 ); ?>" min="1" max="8" step="1" class="range-slider">
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
			</div>
		</div>

		<!-- Section: Advanced Settings (Collapsible) -->
		<div class="settings-section collapsed" id="section-advanced">
			<div class="section-header" onclick="toggleSection('section-advanced')">
				<span class="section-icon">⚙️</span>
				<div class="section-title-group">
					<h2 class="section-title"><?php esc_html_e( 'Advanced Settings', 'wp-configurator' ); ?></h2>
					<p class="section-description"><?php esc_html_e( 'Advanced configuration options for developers and power users.', 'wp-configurator' ); ?></p>
				</div>
				<span class="section-toggle">▼</span>
			</div>
			<div class="section-content">
				<!-- Custom Text Styles Card -->
				<div class="settings-card">
					<div class="settings-card-header">
						<span class="settings-card-icon">🎨</span>
						<h3 class="settings-card-title"><?php esc_html_e( 'Custom Text Styles', 'wp-configurator' ); ?></h3>
					</div>
					<div class="wp-configurator-field-row full-width">
						<div class="wp-configurator-field-group">
							<label for="custom_styles"><?php esc_html_e( 'Available Styles', 'wp-configurator' ); ?></label>
							<p class="description"><?php esc_html_e( 'Enter one style per line in format: "Style Name: css-class-name". Example: "My Button: my-button-class".', 'wp-configurator' ); ?></p>
							<textarea id="custom_styles" name="wp_configurator_options[settings][custom_styles]" rows="4" class="large-text" placeholder="Elementor Heading: elementor-heading-title&#10;Primary Button: btn btn-primary"><?php echo esc_textarea( $options['settings']['custom_styles'] ?? '' ); ?></textarea>
							<p class="description" style="margin-top: 4px;"><?php esc_html_e( 'Tip: View your site source to find Elementor-generated class names (look for class="elementor-...").', 'wp-configurator' ); ?></p>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Section: Tracking & Privacy (Collapsible) -->
		<div class="settings-section collapsed" id="section-tracking">
			<div class="section-header" onclick="toggleSection('section-tracking')">
				<span class="section-icon">🔒</span>
				<div class="section-title-group">
					<h2 class="section-title"><?php esc_html_e( 'Tracking & Privacy', 'wp-configurator' ); ?></h2>
					<p class="section-description"><?php esc_html_e( 'Control interaction tracking and data collection for analytics.', 'wp-configurator' ); ?></p>
				</div>
				<span class="section-toggle">▼</span>
			</div>
			<div class="section-content">

				<!-- Two-column layout for simple settings -->
				<div class="settings-cards-grid">
					<!-- IP Exclusion Card -->
					<div class="settings-card">
						<div class="settings-card-header">
							<span class="settings-card-icon">🚫</span>
							<h3 class="settings-card-title"><?php esc_html_e( 'IP Address Exclusion', 'wp-configurator' ); ?></h3>
						</div>
						<div class="wp-configurator-field-row">
							<div class="wp-configurator-field-group">
								<label for="exclude_admin_ip" class="toggle-label">
									<div class="toggle-switch">
										<input type="checkbox" id="exclude_admin_ip" name="wp_configurator_options[settings][exclude_admin_ip]" value="1" <?php checked( $options['settings']['exclude_admin_ip'] ?? 0 ); ?> role="switch" aria-checked="<?php echo ! empty( $options['settings']['exclude_admin_ip'] ) ? 'true' : 'false'; ?>">
										<span class="toggle-track"></span>
									</div>
									<span class="toggle-label-text"><?php esc_html_e( 'Exclude admin IP from interaction tracking', 'wp-configurator' ); ?></span>
								</label>
								<p class="toggle-description"><?php esc_html_e( 'Prevent your visits from being tracked in analytics. Useful during development and testing.', 'wp-configurator' ); ?></p>
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
								<p class="description" style="margin-top: 4px;"><?php esc_html_e( 'Your current IP:', 'wp-configurator' ); ?> <strong id="current_ip"><?php echo esc_html( $_SERVER['REMOTE_ADDR'] ?? 'Unknown' ); ?></strong></p>
							</div>
						</div>
					</div>

					<!-- Statistics Settings Card -->
					<div class="settings-card">
						<div class="settings-card-header">
							<span class="settings-card-icon">📊</span>
							<h3 class="settings-card-title"><?php esc_html_e( 'Statistics', 'wp-configurator' ); ?></h3>
						</div>
						<div class="wp-configurator-field-row">
							<div class="wp-configurator-field-group">
								<label for="exclude_zero_cost_from_stats" class="toggle-label">
									<div class="toggle-switch">
										<input type="checkbox" id="exclude_zero_cost_from_stats" name="wp_configurator_options[settings][exclude_zero_cost_from_stats]" value="1" <?php checked( $options['settings']['exclude_zero_cost_from_stats'] ?? 0 ); ?> role="switch" aria-checked="<?php echo ! empty( $options['settings']['exclude_zero_cost_from_stats'] ) ? 'true' : 'false'; ?>">
										<span class="toggle-track"></span>
									</div>
									<span class="toggle-label-text"><?php esc_html_e( 'Exclude zero-cost items from statistics', 'wp-configurator' ); ?></span>
								</label>
								<p class="toggle-description"><?php esc_html_e( 'When enabled, features with a price of €0 will be excluded from statistics (top features, unique features count, revenue totals). This gives a cleaner view of paid features only.', 'wp-configurator' ); ?></p>
							</div>
						</div>
					</div>
				</div>

				<!-- Bot Filtering Card -->
				<div class="settings-card">
					<div class="settings-card-header">
						<span class="settings-card-icon">🤖</span>
						<h3 class="settings-card-title"><?php esc_html_e( 'Bot & Crawler Filtering', 'wp-configurator' ); ?></h3>
					</div>
					<div class="wp-configurator-field-row">
						<div class="wp-configurator-field-group">
							<label for="exclude_bot_user_agents" class="toggle-label">
								<div class="toggle-switch">
									<input type="checkbox" id="exclude_bot_user_agents" name="wp_configurator_options[settings][exclude_bot_user_agents]" value="1" <?php checked( $options['settings']['exclude_bot_user_agents'] ?? 0 ); ?> role="switch" aria-checked="<?php echo ! empty( $options['settings']['exclude_bot_user_agents'] ) ? 'true' : 'false'; ?>">
									<span class="toggle-track"></span>
								</div>
								<span class="toggle-label-text"><?php esc_html_e( 'Exclude bots & crawlers from interaction tracking', 'wp-configurator' ); ?></span>
							</label>
							<p class="toggle-description"><?php esc_html_e( 'Automatically filter out known bots, crawlers, and automated agents from analytics data. Improves accuracy of engagement metrics.', 'wp-configurator' ); ?></p>
						</div>
					</div>

					<div class="wp-configurator-field-row full-width">
						<div class="wp-configurator-field-group">
							<label for="bot_user_agents"><?php esc_html_e( 'Bot User Agent Patterns', 'wp-configurator' ); ?></label>
							<p class="description"><?php esc_html_e( 'Enter one user agent pattern per line. These patterns are matched against visitor user agents. Matching is case-insensitive substring match.', 'wp-configurator' ); ?></p>
							<?php
							$default_bot_patterns = "Googlebot\nBingbot\nYahoo! Slurp\nDuckDuckBot\nBaiduspider\nYandexBot\nSogou\nExabot\nfacebot\nIA_Archiver\nTwitterbot\nLinkedInBot\nSlackbot\nDiscordbot\nWhatsApp\nTelegram\ncurl\nwget\npython-requests\nScrapy";
							$bot_user_agents = $options['settings']['bot_user_agents'] ?? '';
							// If empty and bot filtering is enabled, show defaults for convenience
							if ( empty( $bot_user_agents ) && ! empty( $options['settings']['exclude_bot_user_agents'] ) ) {
								$bot_user_agents = $default_bot_patterns;
							}
							?>
							<textarea id="bot_user_agents" name="wp_configurator_options[settings][bot_user_agents]" rows="6" class="large-text" placeholder="<?php esc_attr_e( 'One pattern per line, e.g., Googlebot', 'wp-configurator' ); ?>"><?php echo esc_textarea( $bot_user_agents ); ?></textarea>
							<p class="description" style="margin-top: 4px;"><?php esc_html_e( 'Pre-populated with common bots. Add custom patterns as needed. Each line is a separate pattern.', 'wp-configurator' ); ?>
								<button type="button" class="button button-small restore-bot-patterns" data-defaults="<?php echo esc_attr( $default_bot_patterns ); ?>" style="margin-left: 8px;"><?php esc_html_e( 'Restore defaults', 'wp-configurator' ); ?></button>
							</p>
						</div>
					</div>
				</div>
			</div>
		</div>

	</div>
</div>

<script>
(function() {
	// Toggle switch enhancement - add aria attributes
	document.querySelectorAll('#miscellaneous .toggle-switch input[type="checkbox"]').forEach(function(toggle) {
		if (!toggle.getAttribute('role')) {
			toggle.setAttribute('role', 'switch');
			toggle.setAttribute('aria-checked', toggle.checked ? 'true' : 'false');
		}
	});

	// Section collapse/expand functionality
	window.toggleSection = function(sectionId) {
		const section = document.getElementById(sectionId);
		if (!section) return;

		const content = section.querySelector('.section-content');
		const toggle = section.querySelector('.section-toggle');
		const isCollapsed = section.classList.contains('collapsed');

		if (isCollapsed) {
			section.classList.remove('collapsed');
			content.style.display = 'block';
			if (toggle) toggle.textContent = '▼';
			// Save state to localStorage
			localStorage.setItem('wp-configurator-section-' + sectionId, 'expanded');
		} else {
			section.classList.add('collapsed');
			content.style.display = 'none';
			if (toggle) toggle.textContent = '▶';
			// Save state to localStorage
			localStorage.setItem('wp-configurator-section-' + sectionId, 'collapsed');
		}
	};

	// Restore section states on page load
	document.addEventListener('DOMContentLoaded', function() {
		['section-email', 'section-frontend', 'section-display', 'section-advanced', 'section-tracking'].forEach(function(sectionId) {
			const savedState = localStorage.getItem('wp-configurator-section-' + sectionId);
			const section = document.getElementById(sectionId);
			if (section && savedState === 'collapsed') {
				section.classList.add('collapsed');
				const content = section.querySelector('.section-content');
				const toggle = section.querySelector('.section-toggle');
				if (content) content.style.display = 'none';
				if (toggle) toggle.textContent = '▶';
			}
		});
	});

	// Live Preview functionality
	const frontendTitleInput = document.getElementById('frontend_title');
	const frontendSubtitleEditor = document.getElementById('frontend_subtitle_editor');
	const previewTitle = document.getElementById('preview-title');
	const previewSubtitle = document.getElementById('preview-subtitle');
	const enablePreviewCheckbox = document.getElementById('enable_live_preview');

	function updatePreview() {
		if (enablePreviewCheckbox && !enablePreviewCheckbox.checked) {
			return;
		}

		if (frontendTitleInput && previewTitle) {
			previewTitle.textContent = frontendTitleInput.value || '<?php echo esc_js( $frontend_title ); ?>';
		}

		// Handle subtitle - try TinyMCE first, then fallback to textarea
		if (previewSubtitle) {
			if (typeof tinymce !== 'undefined' && tinymce.get('frontend_subtitle_editor') && tinymce.get('frontend_subtitle_editor').getContent) {
				previewSubtitle.innerHTML = tinymce.get('frontend_subtitle_editor').getContent();
			} else if (frontendSubtitleEditor) {
				previewSubtitle.innerHTML = frontendSubtitleEditor.value || '<?php echo esc_js( $frontend_subtitle ); ?>';
			}
		}
	}

	// Initialize preview on page load
	setTimeout(updatePreview, 100);

	if (frontendTitleInput) {
		frontendTitleInput.addEventListener('input', updatePreview);
	}

	if (frontendSubtitleEditor) {
		frontendSubtitleEditor.addEventListener('input', updatePreview);
	}

	// Hook into TinyMCE if it's already loaded
	if (typeof tinymce !== 'undefined') {
		tinymce.on('AddEditor', function(event) {
			if (event.editor.id === 'frontend_subtitle_editor') {
				event.editor.on('change keyup', updatePreview);
			}
		});

		// Check if editor is already available
		const existingEditor = tinymce.get('frontend_subtitle_editor');
		if (existingEditor) {
			existingEditor.on('change keyup', updatePreview);
		}
	}

	if (enablePreviewCheckbox) {
		enablePreviewCheckbox.addEventListener('change', updatePreview);
	}

	// Accordion toggle
	window.toggleAccordion = function(header) {
		const section = header.closest('.accordion-section');
		if (section) {
			section.classList.toggle('collapsed');
		}
	};

	// Restore default bot patterns
	document.querySelectorAll('.restore-bot-patterns').forEach(function(btn) {
		btn.addEventListener('click', function() {
			const textarea = document.getElementById('bot_user_agents');
			if (textarea && this.dataset.defaults) {
				textarea.value = this.dataset.defaults;
				// Trigger input event for any listeners
				textarea.dispatchEvent(new Event('input', { bubbles: true }));
			}
		});
	});

	// Auto-fill bot patterns when enabling exclusion if field is empty
	const botToggle = document.getElementById('exclude_bot_user_agents');
	const botTextarea = document.getElementById('bot_user_agents');
	if (botToggle && botTextarea) {
		botToggle.addEventListener('change', function() {
			if (this.checked && botTextarea.value.trim() === '') {
				const btn = document.querySelector('.restore-bot-patterns');
				if (btn && btn.dataset.defaults) {
					botTextarea.value = btn.dataset.defaults;
				}
			}
		});
	}
})();
</script>
