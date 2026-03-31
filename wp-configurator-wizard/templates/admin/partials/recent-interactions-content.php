	<?php
	global $wpdb;
	$interactions_table = $wpdb->prefix . 'configurator_interactions';
	$date_conditions = '';
	$date_args = array();

	// Fetch recent interactions (last 10) including metadata
	if ( ! empty( $date_args ) ) {
		$recent_events = $wpdb->get_results( $wpdb->prepare(
			"SELECT event_type, feature_id, category_id, created_at, user_agent, metadata FROM $interactions_table WHERE 1=1 $date_conditions ORDER BY created_at DESC LIMIT 10",
			$date_args
		) );
	} else {
		$recent_events = $wpdb->get_results(
			"SELECT event_type, feature_id, category_id, created_at, user_agent, metadata FROM $interactions_table ORDER BY created_at DESC LIMIT 10"
		);
	}

	// Filter out bot interactions if bot filtering is enabled
	$exclude_bots = ! empty( $options['settings']['exclude_bot_user_agents'] );
	$bot_patterns = ! empty( $options['settings']['bot_user_agents'] ) ? explode( "\n", $options['settings']['bot_user_agents'] ) : array();

	if ( $exclude_bots && ! empty( $bot_patterns ) && ! empty( $recent_events ) ) {
		$filtered_events = array();
		foreach ( $recent_events as $event ) {
			$user_agent = $event->user_agent ?? '';
			$is_bot = false;
			foreach ( $bot_patterns as $pattern ) {
				$pattern = trim( $pattern );
				if ( $pattern !== '' && stripos( $user_agent, $pattern ) !== false ) {
					$is_bot = true;
					break;
				}
			}
			if ( ! $is_bot ) {
				$filtered_events[] = $event;
			}
		}
		$recent_events = $filtered_events;
	}
	// Build lookup arrays for names
	$feature_names = array();
	$category_names = array();
	if ( ! empty( $options['features'] ) ) {
		foreach ( $options['features'] as $feat ) {
			$feature_names[ $feat['id'] ] = $feat['name'];
		}
	}
	if ( ! empty( $options['categories'] ) ) {
		foreach ( $options['categories'] as $cat ) {
			$category_names[ $cat['id'] ] = $cat['name'];
		}
	}
	?>
	<style type="text/css">
		.wp-configurator-url-params {
			display: flex;
			flex-wrap: wrap;
			gap: 2px;
			margin-bottom: 4px;
		}
		.wp-configurator-url-param-badge {
			background: #f0f0f1;
			color: #2c3338;
			padding: 2px 6px;
			border-radius: 3px;
			font-size: 11px;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}
		.wp-configurator-page-context {
			font-size: 11px;
			color: #666;
		}
		.wp-configurator-page-context div {
			margin-bottom: 2px;
		}
		.wp-configurator-page-context .label {
			font-weight: 600;
			color: #444;
		}
	</style>
	<table class="widefat fixed striped" style="font-size: 13px; margin-bottom: 24px;">
		<thead>
			<tr>
				<th>Event Type</th>
				<th>Feature/Category</th>
				<th>Time</th>
				<th>URL Params</th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $recent_events ) ) : ?>
				<tr><td colspan="4"><?php esc_html_e( 'No interactions recorded yet.', 'wp-configurator' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $recent_events as $event ) : ?>
					<tr>
						<td><?php echo esc_html( $event->event_type ); ?></td>
						<td>
							<?php
							if ( $event->feature_id && isset( $feature_names[ $event->feature_id ] ) ) {
								$cat_name = ( $event->category_id && isset( $category_names[ $event->category_id ] ) ) ? $category_names[ $event->category_id ] : '';
								if ( $cat_name ) {
									echo esc_html( $cat_name . ': ' . $feature_names[ $event->feature_id ] );
								} else {
									echo esc_html( $feature_names[ $event->feature_id ] );
								}
							} elseif ( $event->category_id && isset( $category_names[ $event->category_id ] ) ) {
								echo esc_html( $category_names[ $event->category_id ] );
							} else {
								echo '—';
							}
							?>
						</td>
						<td><?php echo esc_html( $event->created_at ); ?></td>
						<td>
							<?php
							$metadata = ! empty( $event->metadata ) ? json_decode( $event->metadata, true ) : array();
							// Show URL params as badges
							if ( ! empty( $metadata['url_params'] ) && is_array( $metadata['url_params'] ) ) {
								echo '<div class="wp-configurator-url-params">';
								foreach ( $metadata['url_params'] as $key => $value ) {
									$display_value = esc_html( $key . '=' . $value );
									echo '<span class="wp-configurator-url-param-badge" title="' . $display_value . '">' . $display_value . '</span> ';
								}
								echo '</div>';
							}
							// Show page URL and referrer
							if ( ! empty( $metadata['page_url'] ) || ! empty( $metadata['referrer_url'] ) ) {
								echo '<div class="wp-configurator-page-context">';
								if ( ! empty( $metadata['page_url'] ) ) {
									// Extract just the path from full URL (without query)
									$page_url = $metadata['page_url'];
									$parsed = parse_url( $page_url );
									$display_url = isset( $parsed['path'] ) ? $parsed['path'] : $page_url;
									echo '<div><span class="label">Page:</span> ' . esc_html( $display_url ) . '</div>';
								}
								if ( ! empty( $metadata['referrer_url'] ) ) {
									// Extract just the path from referrer for brevity
									$referrer_url = $metadata['referrer_url'];
									$parsed = parse_url( $referrer_url );
									$display_ref = isset( $parsed['path'] ) ? $parsed['path'] : ( $referrer_url ?: '' );
									if ( $display_ref ) {
										echo '<div><span class="label">From:</span> ' . esc_html( $display_ref ) . '</div>';
									}
								}
								echo '</div>';
							}
							if ( empty( $metadata['url_params'] ) && empty( $metadata['page_url'] ) && empty( $metadata['referrer_url'] ) ) {
								echo '—';
							}
							?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
