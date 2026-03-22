<?php
/**
 * Admin page wrapper template
 * Displays intro, recent interactions, tab navigation, and includes tab content templates
 *
 * Expected variables:
 * - $options: array of plugin options
 * - $wp_configurator_wizard_instance: main plugin instance
 */
?>
<div class="wrap">

	<!-- Enhanced Admin Header (Collapsible) -->
	<div class="wp-configurator-admin-header" id="wp-configurator-header-toggle">

		<!-- Header Top Row: Title + Donate Button -->
		<div class="wp-configurator-header-top">
			<div class="wp-configurator-title-section">
				<span class="dashicons dashicons-arrow-down wp-configurator-header-toggle-icon"></span>
				<span class="dashicons dashicons-admin-generic"></span>
				<h1>
					<?php esc_html_e( 'ATP Quote Configurator', 'wp-configurator' ); ?>
					<span class="wp-configurator-header-version">v<?php echo esc_html( $wp_configurator_wizard_instance->get_version() ); ?></span>
				</h1>
			</div>
			<div class="wp-configurator-header-actions">
				<a href="https://www.paypal.com/paypalme/alltechplus" target="_blank" rel="noopener noreferrer" class="button button-primary wp-configurator-donate-btn">
					<?php esc_html_e( 'Donate via PayPal', 'wp-configurator' ); ?>
				</a>
			</div>
		</div>

		<!-- Header Body (Collapsible) -->
		<div class="wp-configurator-header-body">
			<div class="wp-configurator-header-info-row">
				<p class="wp-configurator-header-description">
					<?php esc_html_e( 'Comprehensive cost estimation wizard with real-time analytics. Track user interactions, monitor quote requests, and gain insights with detailed statistics including engagement rates, revenue trends, and feature popularity. Supports one-time and recurring payments, compulsory categories, and email/webhook notifications.', 'wp-configurator' ); ?>
				</p>
				<div class="wp-configurator-support-badge-mini">
					<span class="dashicons dashicons-heart"></span>
					<div class="wp-configurator-support-text-mini">
						<strong><?php esc_html_e( '100% Free & Open Source (GPLv3)', 'wp-configurator' ); ?></strong>
						<span class="wp-configurator-support-desc">
							<?php esc_html_e( 'If you find it valuable, please consider making a donation to help maintain and improve it.', 'wp-configurator' ); ?>
						</span>
					</div>
				</div>
			</div>

			<div class="wp-configurator-header-meta">
				<div class="wp-configurator-meta-item">
					<strong><?php esc_html_e( 'Shortcode:', 'wp-configurator' ); ?></strong>
					<code>[wp_configurator_wizard]</code>
				</div>
				<div class="wp-configurator-meta-item">
					<strong><?php esc_html_e( 'Developed by:', 'wp-configurator' ); ?></strong>
					<a href="https://all-tech-plus.com" target="_blank" rel="noopener noreferrer">All Tech Plus</a>
					<?php esc_html_e( 'and', 'wp-configurator' ); ?>
					<a href="https://aicognitio.com" target="_blank" rel="noopener noreferrer">AICognitio</a>
				</div>
			</div>

			<!-- Collapsible Sections Container -->
			<div class="wp-configurator-collapsible-sections">

				<!-- Donors Wall (Collapsible) -->
				<?php
				$plugin_root = dirname( dirname( dirname( __FILE__ ) ) );
				$donors_file = $plugin_root . '/donors.txt';
				if ( file_exists( $donors_file ) ) :
					$donors_raw = array_filter( array_map( 'trim', file( $donors_file ) ) );
					// Parse CSV format: Name,Count,DemoURL (DemoURL optional)
					$donors = array();
					$positive_emojis = array('★', '✨', '🎉', '🏆', '🌟', '💫', '🔥', '💎', '👏', '🥇', '🎯', '💝', '🌺', '🦋', '🌈');
					foreach ( $donors_raw as $index => $line ) {
						$parts = str_getcsv( $line );
						if ( count( $parts ) >= 1 ) {
							$name = trim( $parts[0] );
							$count = isset( $parts[1] ) ? intval( trim( $parts[1] ) ) : 1;
							$count = max( 1, min( $count, 10 ) ); // Clamp between 1-10
							$demo_url = isset( $parts[2] ) ? trim( $parts[2] ) : '';
							// Pick a random emoji for this donor (consistent per donor index)
							$emoji = $positive_emojis[ $index % count( $positive_emojis ) ];
							$donors[] = array(
								'name' => $name,
								'count' => $count,
								'demo_url' => $demo_url,
								'emoji' => $emoji,
							);
						}
					}
					if ( ! empty( $donors ) ) :
				?>
				<div class="wp-configurator-collapsible-section wp-configurator-donors-section collapsed">
					<div class="wp-configurator-collapsible-header">
						<span class="dashicons dashicons-groups"></span>
						<strong><?php esc_html_e( 'Thank you to our supporters!', 'wp-configurator' ); ?></strong>
						<span class="dashicons dashicons-arrow-down wp-configurator-chevron"></span>
					</div>
					<div class="wp-configurator-collapsible-content">
						<p class="wp-configurator-donors-subtitle">
							<?php esc_html_e( 'These generous individuals have contributed to the development of this plugin:', 'wp-configurator' ); ?>
						</p>
						<div class="wp-configurator-donors-grid-compact">
							<?php foreach ( $donors as $donor_index => $donor ) : ?>
								<?php
								// Generate a sequence of different emojis based on count
								$emojis = '';
								for ( $i = 0; $i < $donor['count']; $i++ ) {
									$emoji_index = ( $donor_index + $i ) % count( $positive_emojis );
									$emojis .= $positive_emojis[ $emoji_index ];
								}
								?>
								<span class="wp-configurator-donor-badge" style="background: linear-gradient(135deg, <?php echo esc_attr( '#' . dechex(rand(100, 200)) . dechex(rand(150, 255)) . dechex(rand(180, 240)) ); ?>, <?php echo esc_attr( '#' . dechex(rand(180, 240)) . dechex(rand(200, 255)) . dechex(rand(220, 255)) ); ?>);">
									<span class="wp-configurator-donor-emojis"><?php echo esc_html( $emojis ); ?></span>
									<span class="wp-configurator-donor-name"><?php echo esc_html( $donor['name'] ); ?></span>
									<?php if ( ! empty( $donor['demo_url'] ) ) : ?>
										<a href="<?php echo esc_url( $donor['demo_url'] ); ?>" target="_blank" rel="noopener noreferrer" class="wp-configurator-donor-demo-badge">demo</a>
									<?php endif; ?>
								</span>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
				<?php
					endif;
				endif;
				?>

				<!-- Recent Interactions (Collapsible, collapsed by default) -->
				<div class="wp-configurator-collapsible-section wp-configurator-interactions-section collapsed">
					<div class="wp-configurator-collapsible-header">
						<span class="dashicons dashicons-clock"></span>
						<strong><?php esc_html_e( 'Recent Interactions', 'wp-configurator' ); ?></strong>
						<button type="button" id="refresh-interactions" class="button button-secondary wp-configurator-refresh-btn">
							<span class="dashicons dashicons-refresh"></span>
							<?php esc_html_e( 'Refresh', 'wp-configurator' ); ?>
						</button>
						<span class="dashicons dashicons-arrow-down wp-configurator-chevron"></span>
					</div>
					<div class="wp-configurator-collapsible-content">
						<?php include __DIR__ . '/partials/recent-interactions-content.php'; ?>
					</div>
				</div>

			</div>
		</div>
	</div>

	<form method="post" action="options.php">
		<?php
		settings_fields( 'wp-configurator-settings' );
		?>

		<?php include __DIR__ . '/tabs/navigation.php'; ?>

		<!-- Tab content: Categories & Features -->
		<?php include __DIR__ . '/tabs/categories-features.php'; ?>

		<!-- Tab content: Miscellaneous Settings -->
		<?php include __DIR__ . '/tabs/miscellaneous.php'; ?>

		<!-- Global action buttons -->
		<?php include __DIR__ . '/partials/global-actions.php'; ?>

	</form>

	<!-- Tab content: Quote Requests (outside form) -->
	<?php include __DIR__ . '/tabs/quote-requests.php'; ?>

	<!-- Tab content: Stats (outside form) -->
	<?php include __DIR__ . '/tabs/stats.php'; ?>

	<!-- Tab content: System Status (outside form) -->
	<?php include __DIR__ . '/tabs/system-status.php'; ?>

	<!-- Modals -->
	<?php include __DIR__ . '/partials/modals.php'; ?>

</div> <!-- .wrap -->
