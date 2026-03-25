	<?php
	global $wpdb;
	$interactions_table = $wpdb->prefix . 'configurator_interactions';
	$date_conditions = '';
	$date_args = array();

	// Fetch recent interactions (last 10)
	if ( ! empty( $date_args ) ) {
		$recent_events = $wpdb->get_results( $wpdb->prepare(
			"SELECT event_type, feature_id, category_id, created_at, user_agent FROM $interactions_table WHERE 1=1 $date_conditions ORDER BY created_at DESC LIMIT 10",
			$date_args
		) );
	} else {
		$recent_events = $wpdb->get_results(
			"SELECT event_type, feature_id, category_id, created_at, user_agent FROM $interactions_table ORDER BY created_at DESC LIMIT 10"
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
	<table class="widefat fixed striped" style="font-size: 13px; margin-bottom: 24px;">
		<thead>
			<tr>
				<th>Event Type</th>
				<th>Feature/Category</th>
				<th>Time</th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $recent_events ) ) : ?>
				<tr><td colspan="3"><?php esc_html_e( 'No interactions recorded yet.', 'wp-configurator' ); ?></td></tr>
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
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
