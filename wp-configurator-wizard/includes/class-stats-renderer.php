<?php
/**
 * Stats Renderer class
 * Handles all statistics calculations and output for the admin dashboard
 *
 * @package WP_Configurator_Wizard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Stats_Renderer {

	/**
	 * Settings manager instance
	 *
	 * @var Settings_Manager
	 */
	private $settings_manager;

	/**
	 * Database manager instance
	 *
	 * @var Database_Manager
	 */
	private $database_manager;

	/**
	 * Constructor
	 */
	public function __construct( Settings_Manager $settings_manager, Database_Manager $database_manager ) {
		$this->settings_manager = $settings_manager;
		$this->database_manager = $database_manager;
	}

	/**
	 * Render the stats dashboard
	 * Outputs HTML and JavaScript for charts
	 */
	public function render(): void {
		global $wpdb;

		$table_name = $this->database_manager->get_quote_requests_table();
		$interactions_table = $this->database_manager->get_interactions_table();

		// Get date filter from request (default to all_time), fallback to cookie
		$date_filter = 'all_time';
		if ( isset( $_GET['stats_filter'] ) ) {
			$date_filter = sanitize_text_field( $_GET['stats_filter'] );
		} elseif ( isset( $_COOKIE['wp_configurator_stats_filter'] ) ) {
			$date_filter = sanitize_text_field( $_COOKIE['wp_configurator_stats_filter'] );
		}
		$total_requests = 0; // Initialize to prevent undefined variable notices

		// Compute date range conditions
		list( $date_conditions, $date_args, $range_start, $num_days ) = $this->get_date_conditions( $date_filter );

		// Get filtered quote requests count and data
		if ( ! empty( $date_args ) ) {
			$total_requests = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table_name WHERE 1=1 $date_conditions", $date_args ) );
			$requests = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name WHERE 1=1 $date_conditions ORDER BY created_at DESC", $date_args ) );
		} else {
			$total_requests = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );
			$requests = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY created_at DESC" );
		}

		// Calculate all metrics
		$metrics = $this->calculate_metrics( $requests, $date_conditions, $date_args, $table_name, $interactions_table, $range_start, $num_days, $date_filter );

		// Output HTML
		?>
		<!-- Date Filter -->
		<div class="stats-filter" style="margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
			<label for="stats-date-filter"><strong><?php esc_html_e( 'Date Range:', 'wp-configurator' ); ?></strong></label>
			<form method="get" action="" style="display: inline;">
				<input type="hidden" name="page" value="wp-configurator-settings">
				<select id="stats-date-filter" name="stats_filter" onchange="this.form.submit()" style="min-width: 150px;">
					<option value="today" <?php selected( $date_filter, 'today' ); ?>>Today</option>
					<option value="yesterday" <?php selected( $date_filter, 'yesterday' ); ?>>Yesterday</option>
					<option value="last_7_days" <?php selected( $date_filter, 'last_7_days' ); ?>>Last 7 Days</option>
					<option value="last_30_days" <?php selected( $date_filter, 'last_30_days' ); ?>>Last 30 Days</option>
					<option value="all_time" <?php selected( $date_filter, 'all_time' ); ?>>All Time</option>
				</select>
				<noscript><button type="submit" class="button button-secondary">Apply</button></noscript>
			</form>
		</div>
		<div class="wp-configurator-stats-dashboard">
			<!-- Summary Cards - Grouped (Compact) -->
			<div class="stats-summary-cards">

				<!-- Revenue Summary -->
				<h3 class="group-title">Revenue Summary</h3>
				<div class="group-cards">
					<div class="stat-card highlight-revenue">
						<div class="stat-value">€<?php echo number_format( $metrics['total_value'], 2 ); ?></div>
						<div class="stat-value sub" style="color: #46b450;">€<?php echo number_format( $metrics['cash_in_bag'], 2 ); ?></div>
						<div class="stat-label">Quote Value (Total / Collected)</div>
					</div>
					<div class="stat-card highlight-mrr">
						<div class="stat-value">€<?php echo number_format( $metrics['monthly_total'], 2 ); ?></div>
						<div class="stat-value sub" style="color: #46b450;">€<?php echo number_format( $metrics['invoiced_monthly'], 2 ); ?></div>
						<div class="stat-label">Monthly (MRR)</div>
					</div>
					<div class="stat-card highlight-qrr">
						<div class="stat-value">€<?php echo number_format( $metrics['quarterly_total'], 2 ); ?></div>
						<div class="stat-value sub" style="color: #46b450;">€<?php echo number_format( $metrics['invoiced_quarterly'], 2 ); ?></div>
						<div class="stat-label">Quarterly (QRR)</div>
					</div>
					<div class="stat-card highlight-arr">
						<div class="stat-value">€<?php echo number_format( $metrics['annual_total'], 2 ); ?></div>
						<div class="stat-value sub" style="color: #46b450;">€<?php echo number_format( $metrics['invoiced_annual'], 2 ); ?></div>
						<div class="stat-label">Annual (ARR)</div>
					</div>
				</div>

				<!-- Conversion & Engagement -->
				<h3 class="group-title">Conversion & Engagement</h3>
				<div class="group-cards">
					<div class="stat-card funnel-start">
						<div class="stat-value"><?php echo number_format( $metrics['checkout_starts'] ); ?></div>
						<div class="stat-label">Checkout Started</div>
					</div>
					<div class="stat-card funnel-quote">
						<div class="stat-value"><?php echo number_format( $metrics['quotes_submitted_count'] ); ?></div>
						<div class="stat-label">Quotes Submitted</div>
					</div>
					<div class="stat-card funnel-abandon">
						<div class="stat-value"><?php echo number_format( $metrics['checkout_abandoned'] ); ?></div>
						<div class="stat-label">Checkout Abandoned</div>
					</div>
					<div class="stat-card">
						<div class="stat-value"><?php echo $metrics['quote_to_confirmed_rate']; ?>%</div>
						<div class="stat-label">Quote→Confirmed</div>
					</div>
					<div class="stat-card">
						<div class="stat-value"><?php echo $metrics['quote_to_invoiced_rate']; ?>%</div>
						<div class="stat-label">Quote→Invoiced</div>
					</div>
					<div class="stat-card">
						<div class="stat-value"><?php echo number_format( $metrics['unique_initial_engagement'] ); ?></div>
						<div class="stat-label">Initial Engagement</div>
					</div>
					<div class="stat-card">
						<div class="stat-value"><?php echo $metrics['engagement_rate']; ?>%</div>
						<div class="stat-label">Engagement Rate</div>
					</div>
				</div>

			</div>

			<!-- Charts Row 1: Revenue Trend (full width) -->
			<div class="stats-charts-row revenue-row">
				<div class="chart-container">
					<h3>Revenue Trend (<?php echo esc_html( $metrics['date_filter_display'] ); ?>)</h3>
					<canvas id="revenue-chart"></canvas>
				</div>
			</div>

			<!-- Charts Row 2: Quote Requests + Billing Breakdown (side-by-side) -->
			<div class="stats-charts-row">
				<div class="chart-container">
					<h3>Quote Requests (<?php echo esc_html( $metrics['date_filter_display'] ); ?>)</h3>
					<canvas id="requests-time-chart"></canvas>
				</div>
				<div class="chart-container">
					<h3>Billing Breakdown</h3>
					<canvas id="billing-chart"></canvas>
				</div>
			</div>

			<!-- Charts Row 3: Marketing Attribution (URL Parameters) with Event Selector & Tabs -->
			<?php
			// Determine if any attribution data exists for any event type
			$has_attribution = false;
			if ( ! empty( $metrics['attribution_stats'] ) && is_array( $metrics['attribution_stats'] ) ) {
				foreach ( $metrics['attribution_stats'] as $event_data ) {
					if ( ! empty( $event_data['source'] ) || ! empty( $event_data['medium'] ) || ! empty( $event_data['campaign'] ) || ! empty( $event_data['client'] ) || ! empty( $event_data['bot'] ) ) {
						$has_attribution = true;
						break;
					}
				}
			}
			?>
			<?php if ( $has_attribution ) : ?>
			<div class="stats-charts-row attribution-row">
				<div class="chart-container full-width" style="grid-column: 1 / -1;">
					<!-- Event Type Selector + Dimension Tabs -->
					<div class="attribution-controls">
						<select id="attribution-event-select" class="attribution-select">
							<option value="wizard_view">Wizard Views</option>
							<option value="feature_added">Feature Added</option>
							<option value="checkout_start">Checkout Started</option>
							<option value="quote_submitted" selected>Quote Submitted</option>
						</select>
						<div class="attribution-tabs" role="tablist">
							<button class="attr-tab active" data-dimension="source">Sources</button>
							<button class="attr-tab" data-dimension="medium">Mediums</button>
							<button class="attr-tab" data-dimension="campaign">Campaigns</button>
							<button class="attr-tab" data-dimension="client">Clients</button>
							<button class="attr-tab" data-dimension="bot">Bots</button>
						</div>
					</div>

					<!-- Single Chart Display Area -->
					<div class="attribution-display">
						<!-- Bar Chart Canvas (for source, medium, campaign) -->
						<div id="attribution-chart-wrapper" style="display: none; height: 220px;">
							<canvas id="attribution-chart"></canvas>
						</div>
						<!-- List Display (for client, bot) -->
						<div id="attribution-list-wrapper" class="wp-configurator-attribution-list" style="max-height: 220px; overflow-y: auto; padding: 8px 0;">
						</div>
						<!-- Empty State -->
						<div id="attribution-empty" style="display: none; text-align: center; color: #666; padding: 40px 0;">
							No data available for the selected event and dimension.
						</div>
					</div>

					<!-- Chart Legend/Info -->
					<div class="attribution-info">
						<span class="attr-total-count">Total: <strong id="attr-total-count">0</strong></span>
						<span class="attr-total-value">Revenue: <strong id="attr-total-value">€0</strong></span>
					</div>
				</div>
			</div>
			<?php endif; ?>

		</div>

		<style type="text/css">
			/* Attribution Controls */
			.attribution-controls {
				display: flex;
				gap: 12px;
				margin-bottom: 12px;
				align-items: center;
				flex-wrap: wrap;
			}
			.attribution-select {
				padding: 6px 12px;
				border: 1px solid #ddd;
				border-radius: 4px;
				background: #fff;
				font-size: 13px;
				min-width: 160px;
			}
			.attribution-tabs {
				display: flex;
				gap: 4px;
				flex-wrap: wrap;
			}
			.attr-tab {
				padding: 6px 14px;
				border: 1px solid #ddd;
				background: #f9f9f9;
				border-radius: 4px;
				cursor: pointer;
				font-size: 13px;
				color: #2c3338;
				transition: all 0.15s;
			}
			.attr-tab:hover {
				background: #f0f0f1;
			}
			.attr-tab.active {
				background: #2271b1;
				color: #fff;
				border-color: #2271b1;
			}
			.attribution-display {
				min-height: 200px;
				position: relative;
			}
			.attribution-info {
				margin-top: 8px;
				font-size: 12px;
				color: #666;
				display: flex;
				gap: 16px;
			}
			.attribution-info strong {
				color: #2c3338;
			}

			/* URL Attribution List Styles */
			.wp-configurator-attribution-list {
				display: flex;
				flex-direction: column;
				gap: 4px;
				max-height: 180px;
				overflow-y: auto;
				padding-right: 8px;
			}
			.wp-configurator-attribution-list .attr-item {
				display: flex;
				align-items: center;
				padding: 4px 8px;
				background: #f9f9f9;
				border-radius: 3px;
				font-size: 12px;
			}
			.wp-configurator-attribution-list .attr-label {
				font-weight: 600;
				color: #2c3338;
				margin-right: auto;
				overflow: hidden;
				text-overflow: ellipsis;
				white-space: nowrap;
				max-width: 40%;
			}
			.wp-configurator-attribution-list .attr-count {
				color: #666;
				margin-right: 12px;
			}
			.wp-configurator-attribution-list .attr-value {
				color: #2271b1;
				font-weight: 600;
			}
			/* Attribution row should have same card height */
			.attribution-row .chart-container {
				min-height: 280px;
			}
			.attribution-row .chart-container.full-width {
				min-height: auto;
			}
		</style>

		<script type="text/javascript">
		(function($) {
			$(document).ready(function() {
				// Only load charts if Chart.js is available
				if ( typeof Chart === 'undefined' ) {
					console.warn('Chart.js not loaded');
					return;
				}

				// Common chart options
				Chart.defaults.font.family = '-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif';
				Chart.defaults.color = '#666';

				// Requests over time (line chart)
				var timeCtx = document.getElementById('requests-time-chart');
				if (timeCtx) {
					new Chart(timeCtx, {
						type: 'line',
						data: {
							labels: <?php echo json_encode( $metrics['date_labels'] ); ?>,
							datasets: [{
								label: 'Quote Requests',
								data: <?php echo json_encode( $metrics['date_counts_array'] ); ?>,
								borderColor: '#2271b1',
								backgroundColor: 'rgba(34,113,177,0.1)',
								fill: true,
								tension: 0.2
							}]
						},
						options: {
							responsive: true,
							plugins: {
								legend: { display: false }
							},
							scales: {
								y: {
									beginAtZero: true,
									ticks: { stepSize: 1 }
								}
							}
						}
					});
				}

				// Billing breakdown (horizontal bar chart showing monetary values)
				var billingCtx = document.getElementById('billing-chart');
				if (billingCtx) {
					new Chart(billingCtx, {
						type: 'bar',
						data: {
							labels: <?php echo json_encode( $metrics['billing_labels'] ); ?>,
							datasets: [{
								label: 'Amount (€)',
								data: <?php echo json_encode( $metrics['billing_data'] ); ?>,
								backgroundColor: [
									'#2271b1',
									'#72aee6',
									'#46b450',
									'#f56b22'
								]
							}]
						},
						options: {
							indexAxis: 'y',
							responsive: true,
							plugins: {
								legend: { display: false },
								tooltip: {
									callbacks: {
										label: function(context) {
											return '€' + context.parsed.x.toLocaleString();
										}
									}
								}
							},
							scales: {
								x: {
									beginAtZero: true,
									ticks: {
										callback: function(value) {
											return '€' + value.toLocaleString();
										}
									}
								},
								y: {
									grid: { display: false }
								}
							}
						}
					});
				}

				// Sources attribution (horizontal bar chart)
				var sourcesCtx = document.getElementById('sources-chart');
				if (sourcesCtx) {
					var sourceData = <?php echo json_encode( $metrics['source_stats'] ); ?>;
					new Chart(sourcesCtx, {
						type: 'bar',
						data: {
							labels: Object.keys(sourceData),
							datasets: [{
								label: 'Quotes',
								data: Object.values(sourceData).map(function(s) { return s.count; }),
								backgroundColor: '#2271b1'
							}]
						},
						options: {
							indexAxis: 'y',
							responsive: true,
							plugins: {
								legend: { display: false },
								tooltip: {
									callbacks: {
										label: function(context) {
											var item = Object.values(sourceData)[context.dataIndex];
											return [
												'Quotes: ' + item.count,
												'Revenue: €' + item.value.toLocaleString()
											];
										}
									}
								}
							},
							scales: {
								x: {
									beginAtZero: true,
									ticks: { stepSize: 1 }
								},
								y: {
									grid: { display: false }
								}
							}
						}
					});
				}

				// Mediums attribution (horizontal bar chart)
				var mediumsCtx = document.getElementById('mediums-chart');
				if (mediumsCtx) {
					var mediumData = <?php echo json_encode( $metrics['medium_stats'] ); ?>;
					new Chart(mediumsCtx, {
						type: 'bar',
						data: {
							labels: Object.keys(mediumData),
							datasets: [{
								label: 'Quotes',
								data: Object.values(mediumData).map(function(s) { return s.count; }),
								backgroundColor: '#f56b22'
							}]
						},
						options: {
							indexAxis: 'y',
							responsive: true,
							plugins: {
								legend: { display: false },
								tooltip: {
									callbacks: {
										label: function(context) {
											var item = Object.values(mediumData)[context.dataIndex];
											return [
												'Quotes: ' + item.count,
												'Revenue: €' + item.value.toLocaleString()
											];
										}
									}
								}
							},
							scales: {
								x: {
									beginAtZero: true,
									ticks: { stepSize: 1 }
								},
								y: {
									grid: { display: false }
								}
							}
						}
					});
				}

				// Campaigns attribution (horizontal bar chart)
				var campaignsCtx = document.getElementById('campaigns-chart');
				if (campaignsCtx) {
					var campaignData = <?php echo json_encode( $metrics['campaign_stats'] ); ?>;
					new Chart(campaignsCtx, {
						type: 'bar',
						data: {
							labels: Object.keys(campaignData),
							datasets: [{
								label: 'Quotes',
								data: Object.values(campaignData).map(function(s) { return s.count; }),
								backgroundColor: '#46b450'
							}]
						},
						options: {
							indexAxis: 'y',
							responsive: true,
							plugins: {
								legend: { display: false },
								tooltip: {
									callbacks: {
										label: function(context) {
											var item = Object.values(campaignData)[context.dataIndex];
											return [
												'Quotes: ' + item.count,
												'Revenue: €' + item.value.toLocaleString()
											];
										}
									}
								}
							},
							scales: {
								x: {
									beginAtZero: true,
									ticks: { stepSize: 1 }
								},
								y: {
									grid: { display: false }
								}
							}
						}
					});
				}

				// Revenue trend (line chart)
				var revenueCtx = document.getElementById('revenue-chart');
				if (revenueCtx) {
					new Chart(revenueCtx, {
						type: 'line',
						data: {
							labels: <?php echo json_encode( $metrics['date_labels'] ); ?>,
							datasets: [{
								label: 'Revenue (€)',
								data: <?php echo json_encode( $metrics['revenue_data'] ); ?>,
								borderColor: '#46b450',
								backgroundColor: 'rgba(70,180,80,0.1)',
								fill: true,
								tension: 0.2
							}]
						},
						options: {
							responsive: true,
							plugins: {
								legend: { position: 'top' }
							},
							scales: {
								y: {
									beginAtZero: true,
									ticks: {
										callback: function(value) {
											return '€' + value.toLocaleString();
										}
									}
								}
							}
						}
					});
				}

				// Attribution Analytics: Event Selector + Tabs
				var attributionData = <?php echo json_encode( $metrics['attribution_stats'] ?? array() ); ?>;
				var attributionEventSelect = document.getElementById('attribution-event-select');
				var attributionTabs = document.querySelectorAll('.attr-tab');
				var chartWrapper = document.getElementById('attribution-chart-wrapper');
				var listWrapper = document.getElementById('attribution-list-wrapper');
				var emptyState = document.getElementById('attribution-empty');
				var totalCountEl = document.getElementById('attr-total-count');
				var totalValueEl = document.getElementById('attr-total-value');
				var attributionChart = null;

				function renderAttribution() {
					var eventType = attributionEventSelect.value;
					var activeTab = document.querySelector('.attr-tab.active').dataset.dimension;
					var dataForEvent = attributionData[eventType] || {};
					var dataForDim = dataForEvent[activeTab] || {};

					// Calculate totals
					var totalCount = 0;
					var totalValue = 0;
					Object.values(dataForDim).forEach(function(item) {
						totalCount += item.count;
						totalValue += item.value;
					});

					totalCountEl.textContent = totalCount.toLocaleString();
					totalValueEl.textContent = '€' + totalValue.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

					// Hide empty state
					emptyState.style.display = 'none';
					chartWrapper.style.display = 'none';
					listWrapper.style.display = 'none';

					if (Object.keys(dataForDim).length === 0) {
						emptyState.style.display = 'block';
						return;
					}

					// Decide display type: bar chart for source/medium/campaign, list for client/bot
					if (activeTab === 'source' || activeTab === 'medium' || activeTab === 'campaign') {
						// Render horizontal bar chart
						var labels = Object.keys(dataForDim);
						var counts = Object.values(dataForDim).map(function(item) { return item.count; });
						var values = Object.values(dataForDim).map(function(item) { return item.value; });

						// Destroy previous chart
						if (attributionChart) {
							attributionChart.destroy();
						}

						var ctx = document.getElementById('attribution-chart');
						if (ctx) {
							attributionChart = new Chart(ctx, {
								type: 'bar',
								data: {
									labels: labels,
									datasets: [{
										label: 'Quotes',
										data: counts,
										backgroundColor: (function() {
											var colors = {
												source: '#2271b1',
												medium: '#f56b22',
												campaign: '#46b450'
											};
											return colors[activeTab] || '#2271b1';
										})()
									}]
								},
								options: {
									indexAxis: 'y',
									responsive: true,
									plugins: {
										legend: { display: false },
										tooltip: {
											callbacks: {
												label: function(context) {
													var idx = context.dataIndex;
													var item = dataForDim[labels[idx]];
													return [
														'Quotes: ' + item.count,
														'Revenue: €' + item.value.toLocaleString()
													];
												}
											}
										}
									},
									scales: {
										x: {
											beginAtZero: true,
											ticks: { stepSize: 1 }
										},
										y: {
											grid: { display: false }
										}
									}
								}
							});
						}
						chartWrapper.style.display = 'block';
					} else {
						// Render list for clients or bots
						var html = '';
						Object.entries(dataForDim).forEach(function([label, item]) {
							html += '<div class="attr-item">' +
								'<span class="attr-label" title="' + label + '">' + label + '</span>' +
								'<span class="attr-count">' + item.count + ' quotes</span>' +
								'<span class="attr-value">€' + item.value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</span>' +
								'</div>';
						});
						listWrapper.innerHTML = html;
						listWrapper.style.display = 'block';
					}
				}

				// Event listener for event selector
				if (attributionEventSelect) {
					attributionEventSelect.addEventListener('change', renderAttribution);
				}

				// Event listeners for tabs
				attributionTabs.forEach(function(tab) {
					tab.addEventListener('click', function() {
						attributionTabs.forEach(t => t.classList.remove('active'));
						tab.classList.add('active');
						renderAttribution();
					});
				});

				// Initial render
				renderAttribution();

				// Activate stats tab if filter present
				const urlParams = new URLSearchParams(window.location.search);
				if (urlParams.has('stats_filter')) {
					const statsTab = document.querySelector('.nav-tab[data-tab="stats"]');
					const statsContent = document.getElementById('stats');
					if (statsTab && statsContent) {
						document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('nav-tab-active'));
						document.querySelectorAll('.wp-configurator-tab-content').forEach(c => c.classList.remove('active'));
						statsTab.classList.add('nav-tab-active');
						statsContent.classList.add('active');
					}
				}
				// Toggle recent interactions collapsible
				$('.interactions-header').on('click', function(){
					$('.interactions-content').slideToggle(200, function(){
						// Toggle chevron direction
						$('.interactions-header .chevron').toggleClass('collapsed');
					});
				});
				$('#refresh-interactions').on('click', function(e){
					e.stopPropagation(); // prevent toggle
					window.location.reload();
				});

				// Persist stats filter in cookie (30 days)
				$('#stats-date-filter').on('change', function() {
					var value = this.value;
					var expires = new Date();
					expires.setTime(expires.getTime() + (30*24*60*60*1000));
					document.cookie = 'wp_configurator_stats_filter=' + value + '; expires=' + expires.toUTCString() + '; path=/';
				});
			});
		})(jQuery);
		</script>
		<?php
	}

	/**
	 * Get date conditions based on filter
	 *
	 * @param string $date_filter Date filter value
	 * @return array [$where_clause, $args, $range_start, $num_days]
	 */
	private function get_date_conditions( string $date_filter ): array {
		global $wpdb;

		$where_clause = '';
		$args = array();
		$range_start = null;
		$num_days = 0;

		switch ( $date_filter ) {
			case 'yesterday':
				$where_clause = " AND created_at >= %s AND created_at < %s";
				$args[] = date( 'Y-m-d 00:00:00', strtotime( '-1 day' ) );
				$args[] = date( 'Y-m-d 00:00:00' );
				$range_start = date( 'Y-m-d', strtotime( '-1 day' ) );
				$num_days = 1;
				break;
			case 'last_7_days':
				$where_clause = " AND created_at >= %s";
				$args[] = date( 'Y-m-d 00:00:00', strtotime( '-6 days' ) );
				$range_start = date( 'Y-m-d', strtotime( '-6 days' ) );
				$num_days = 7;
				break;
			case 'last_30_days':
				$where_clause = " AND created_at >= %s";
				$args[] = date( 'Y-m-d 00:00:00', strtotime( '-29 days' ) );
				$range_start = date( 'Y-m-d', strtotime( '-29 days' ) );
				$num_days = 30;
				break;
			case 'all_time':
				$range_start = null;
				$num_days = 0;
				break;
			case 'today':
			default:
				$where_clause = " AND created_at >= %s";
				$args[] = date( 'Y-m-d 00:00:00' );
				$range_start = date( 'Y-m-d' );
				$num_days = 1;
				break;
		}

		return array( $where_clause, $args, $range_start, $num_days );
	}

	/**
	 * Calculate all metrics for the stats dashboard
	 *
	 * @param array $requests Quote requests data
	 * @param string $date_conditions SQL date conditions
	 * @param array $date_args Date arguments for prepare
	 * @param string $table_name Quote requests table name
	 * @param string $interactions_table Interactions table name
	 * @param string|null $range_start Start date for range
	 * @param int $num_days Number of days in range
	 * @param string $date_filter Date filter selected
	 * @return array Metrics array
	 */
	private function calculate_metrics( array $requests, string $date_conditions, array $date_args, string $table_name, string $interactions_table, ?string $range_start, int $num_days, string $date_filter ): array {
		global $wpdb;

		// Defensive initialization to prevent undefined variable notices
		$total_requests = count( $requests );
		$date_filter = $date_filter ?? 'today';

		// Parse items to get feature statistics
		$feature_counts = array();
		$unique_feature_ids = array();
		$total_value = 0;
		$one_time_total = 0;
		$monthly_total = 0;
		$quarterly_total = 0;
		$annual_total = 0;
		$invoiced_monthly = 0;
		$invoiced_quarterly = 0;
		$invoiced_annual = 0;

		// Determine date range for time series labels
		if ( $date_filter === 'all_time' ) {
			// Compute earliest date from both tables
			$min_requests = $wpdb->get_var( "SELECT MIN(DATE(created_at)) as min_date FROM $table_name" );
			$min_interactions = $wpdb->get_var( "SELECT MIN(DATE(created_at)) as min_date FROM $interactions_table" );
			$min_dates = array_filter( array( $min_requests, $min_interactions ) );
			if ( ! empty( $min_dates ) ) {
				$range_start = min( $min_dates );
			} else {
				$range_start = date( 'Y-m-d', strtotime( '-30 days' ) );
			}
			$num_days = floor( ( strtotime( 'today' ) - strtotime( $range_start ) ) / 86400 ) + 1;
			if ( $num_days <= 0 ) {
				$num_days = 1;
			}
		}

		// Build date labels and counts dictionary
		$date_labels = array();
		$date_counts = array();
		for ( $i = 0; $i < $num_days; $i++ ) {
			$date = date( 'Y-m-d', strtotime( "+$i days", strtotime( $range_start ) ) );
			$date_labels[] = date( 'M j', strtotime( $date ) );
			$date_counts[ $date ] = 0;
		}

		// Get options for category name lookup (not currently used but kept for compatibility)
		$options = $this->settings_manager->get_options();
		$exclude_zero_cost = ! empty( $options['settings']['exclude_zero_cost_from_stats'] );

		// Count statuses
		$status_counts = array(
			'pending'   => 0,
			'quoted'    => 0,
			'confirmed' => 0,
			'invoiced'  => 0,
			'cancelled' => 0,
			'rejected'  => 0,
		);

		foreach ( $requests as $req ) {
			$status = $req->status ?? 'pending';
			if ( isset( $status_counts[ $status ] ) ) {
				$status_counts[ $status ]++;
			} else {
				$status_counts['pending']++;
			}

			$items = json_decode( $req->items, true );
			$totals = json_decode( $req->totals, true );

			if ( is_array( $totals ) ) {
				$one_time_total += $totals['one_time'] ?? 0;
				$monthly_total += $totals['monthly_ongoing'] ?? 0;
				$quarterly_total += $totals['quarterly_ongoing'] ?? 0;
				$annual_total += $totals['annual_ongoing'] ?? 0;
				if ( $status === 'invoiced' ) {
					$invoiced_monthly += $totals['monthly_ongoing'] ?? 0;
					$invoiced_quarterly += $totals['quarterly_ongoing'] ?? 0;
					$invoiced_annual += $totals['annual_ongoing'] ?? 0;
				}
			}
			$total_value += $totals['grand_total'] ?? 0;

			if ( is_array( $items ) ) {
				foreach ( $items as $item ) {
					$feat_id = $item['id'] ?? '';
					$cat_id = $item['category_id'] ?? '';
					$item_price = floatval( $item['price'] ?? 0 );

					// Skip zero-cost items if setting is enabled
					if ( $exclude_zero_cost && $item_price == 0 ) {
						continue;
					}

					if ( $feat_id ) {
						$unique_feature_ids[ $feat_id ] = true;

						if ( ! isset( $feature_counts[ $feat_id ] ) ) {
							$feature_counts[ $feat_id ] = array(
								'count' => 0,
								'name'  => $item['name'] ?? 'Unknown',
								'price' => $item_price,
							);
						}
						$feature_counts[ $feat_id ]['count']++;
					}
				}
			}

			// Track date for time series
			$dateonly = date( 'Y-m-d', strtotime( $req->created_at ) );
			if ( isset( $date_counts[ $dateonly ] ) ) {
				$date_counts[ $dateonly ]++;
			}
		}

		$date_counts_array = array_values( $date_counts );

		// Prepare top features
		arsort( $feature_counts );
		$top_features = array_slice( $feature_counts, 0, 10, true );
		$unique_features_used = count( $unique_feature_ids );

		// Cash in bag (invoiced quotes total)
		$cash_in_bag = 0;
		foreach ( $requests as $req ) {
			if ( $req->status === 'invoiced' ) {
				$totals = json_decode( $req->totals, true );
				$cash_in_bag += $totals['grand_total'] ?? 0;
			}
		}

		// Conversion rates
		$quote_to_confirmed_rate = 0;
		$quote_to_invoiced_rate = 0;
		if ( $total_requests > 0 ) {
			$quote_to_confirmed_rate = round( ( $status_counts['confirmed'] / $total_requests ) * 100, 1 );
			$quote_to_invoiced_rate = round( ( $status_counts['invoiced'] / $total_requests ) * 100, 1 );
		}

		// Interaction metrics
		if ( ! empty( $date_args ) ) {
			$total_interactions = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $interactions_table WHERE 1=1 $date_conditions", $date_args ) );
		} else {
			$total_interactions = $wpdb->get_var( "SELECT COUNT(*) FROM $interactions_table" );
		}

		$total_wizard_views = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $interactions_table WHERE event_type = %s $date_conditions", array_merge( array( 'wizard_view' ), $date_args ) ) );
		$feature_added_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $interactions_table WHERE event_type = %s $date_conditions", array_merge( array( 'feature_added' ), $date_args ) ) );
		$initial_engagement_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $interactions_table WHERE event_type = %s $date_conditions", array_merge( array( 'initial_engagement' ), $date_args ) ) );
		$checkout_starts = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $interactions_table WHERE event_type = %s $date_conditions", array_merge( array( 'checkout_start' ), $date_args ) ) );
		$quotes_submitted_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $interactions_table WHERE event_type = %s $date_conditions", array_merge( array( 'quote_submitted' ), $date_args ) ) );
		$checkout_abandoned = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $interactions_table WHERE event_type = %s $date_conditions", array_merge( array( 'checkout_abandoned' ), $date_args ) ) );

		// Derived interaction metrics
		$unique_feature_adders = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT session_id) FROM $interactions_table WHERE event_type = %s $date_conditions", array_merge( array( 'feature_added' ), $date_args ) ) );
		$unique_viewers = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT session_id) FROM $interactions_table WHERE event_type = %s $date_conditions", array_merge( array( 'wizard_view' ), $date_args ) ) );
		$unique_converters = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT session_id) FROM $interactions_table WHERE event_type = %s $date_conditions", array_merge( array( 'quote_submitted' ), $date_args ) ) );
		$unique_initial_engagement = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT session_id) FROM $interactions_table WHERE event_type = %s $date_conditions", array_merge( array( 'initial_engagement' ), $date_args ) ) );
		$view_to_quote_rate = $unique_viewers > 0 ? round( ( $unique_converters / $unique_viewers ) * 100, 1 ) : 0;
		$engagement_rate = $unique_viewers > 0 ? round( ( $unique_feature_adders / $unique_viewers ) * 100, 1 ) : 0;
		$initial_engagement_rate = $unique_viewers > 0 ? round( ( $unique_initial_engagement / $unique_viewers ) * 100, 1 ) : 0;

		// Average items per request (respecting zero-cost exclusion)
		$total_filtered_items = 0;
		foreach ( $requests as $req ) {
			$items = json_decode( $req->items, true );
			if ( is_array( $items ) ) {
				foreach ( $items as $item ) {
					$price = floatval( $item['price'] ?? 0 );
					if ( ! $exclude_zero_cost || $price != 0 ) {
						$total_filtered_items++;
					}
				}
			}
		}
		$avg_items = $total_requests > 0 ? round( $total_filtered_items / $total_requests, 1 ) : 0;

		// Billing breakdown data
		$billing_labels = array( 'One-time', 'Monthly', 'Quarterly', 'Annual' );
		$billing_data = array( $one_time_total, $monthly_total, $quarterly_total, $annual_total );

		// Revenue trend data
		$daily_revenue = array_fill_keys( array_keys( $date_counts ), 0 );
		foreach ( $requests as $req ) {
			$dateonly = date( 'Y-m-d', strtotime( $req->created_at ) );
			if ( isset( $daily_revenue[ $dateonly ] ) ) {
				$totals = json_decode( $req->totals, true );
				$daily_revenue[ $dateonly ] += $totals['grand_total'] ?? 0;
			}
		}
		$revenue_data = array_values( $daily_revenue );

		// Date filter display labels
		$date_filter_labels = array(
			'today'         => 'Today',
			'yesterday'     => 'Yesterday',
			'last_7_days'   => 'Last 7 Days',
			'last_30_days'  => 'Last 30 Days',
			'all_time'      => 'All Time',
		);
		$date_filter_display = isset( $date_filter_labels[ $date_filter ] ) ? $date_filter_labels[ $date_filter ] : 'Last 30 Days';

		// URL Parameter Attribution Stats (Multi-Event)
		// Aggregate url_params by event type: wizard_view, feature_added, checkout_start, quote_submitted
		$event_types_for_attribution = array( 'wizard_view', 'feature_added', 'checkout_start', 'quote_submitted' );
		$attribution_stats = array();
		foreach ( $event_types_for_attribution as $et ) {
			$attribution_stats[ $et ] = array(
				'source'  => array(),
				'medium'  => array(),
				'campaign' => array(),
				'client'  => array(),
				'bot'     => array(),
			);
		}

		// 1) Quote submissions: from quote_requests (include revenue)
		foreach ( $requests as $req ) {
			$metadata = ! empty( $req->metadata ) ? json_decode( $req->metadata, true ) : array();
			if ( empty( $metadata['url_params'] ) || ! is_array( $metadata['url_params'] ) ) {
				continue;
			}
			$url_params = $metadata['url_params'];
			$quote_value = floatval( $req->totals ? ( json_decode( $req->totals, true )['grand_total'] ?? 0 ) : 0 );

			$source = isset( $url_params['utm_source'] ) ? $url_params['utm_source'] : ( isset( $url_params['source'] ) ? $url_params['source'] : null );
			$medium = isset( $url_params['utm_medium'] ) ? $url_params['utm_medium'] : ( isset( $url_params['medium'] ) ? $url_params['medium'] : null );
			$campaign = isset( $url_params['utm_campaign'] ) ? $url_params['utm_campaign'] : ( isset( $url_params['campaign'] ) ? $url_params['campaign'] : null );
			$webURL = isset( $url_params['webURL'] ) ? $url_params['webURL'] : ( isset( $url_params['weburl'] ) ? $url_params['weburl'] : null );
			$botID = isset( $url_params['botID'] ) ? $url_params['botID'] : ( isset( $url_params['bot_id'] ) ? $url_params['bot_id'] : null );

			if ( $source ) {
				$source = strtolower( $source );
				if ( ! isset( $attribution_stats['quote_submitted']['source'][$source] ) ) {
					$attribution_stats['quote_submitted']['source'][$source] = array( 'count' => 0, 'value' => 0 );
				}
				$attribution_stats['quote_submitted']['source'][$source]['count']++;
				$attribution_stats['quote_submitted']['source'][$source]['value'] += $quote_value;
			}
			if ( $medium ) {
				$medium = strtolower( $medium );
				if ( ! isset( $attribution_stats['quote_submitted']['medium'][$medium] ) ) {
					$attribution_stats['quote_submitted']['medium'][$medium] = array( 'count' => 0, 'value' => 0 );
				}
				$attribution_stats['quote_submitted']['medium'][$medium]['count']++;
				$attribution_stats['quote_submitted']['medium'][$medium]['value'] += $quote_value;
			}
			if ( $campaign ) {
				$campaign = strtolower( $campaign );
				if ( ! isset( $attribution_stats['quote_submitted']['campaign'][$campaign] ) ) {
					$attribution_stats['quote_submitted']['campaign'][$campaign] = array( 'count' => 0, 'value' => 0 );
				}
				$attribution_stats['quote_submitted']['campaign'][$campaign]['count']++;
				$attribution_stats['quote_submitted']['campaign'][$campaign]['value'] += $quote_value;
			}
			if ( $webURL ) {
				if ( ! isset( $attribution_stats['quote_submitted']['client'][$webURL] ) ) {
					$attribution_stats['quote_submitted']['client'][$webURL] = array( 'count' => 0, 'value' => 0 );
				}
				$attribution_stats['quote_submitted']['client'][$webURL]['count']++;
				$attribution_stats['quote_submitted']['client'][$webURL]['value'] += $quote_value;
			}
			if ( $botID ) {
				$botID = strtolower( $botID );
				if ( ! isset( $attribution_stats['quote_submitted']['bot'][$botID] ) ) {
					$attribution_stats['quote_submitted']['bot'][$botID] = array( 'count' => 0, 'value' => 0 );
				}
				$attribution_stats['quote_submitted']['bot'][$botID]['count']++;
				$attribution_stats['quote_submitted']['bot'][$botID]['value'] += $quote_value;
			}
		}

		// 2) Interactions (wizard_view, feature_added, checkout_start): count only, no revenue
		$interaction_event_types = array( 'wizard_view', 'feature_added', 'checkout_start' );
		foreach ( $interaction_event_types as $event_type ) {
			$sql = $wpdb->prepare(
				"SELECT metadata FROM $interactions_table WHERE event_type = %s $date_conditions",
				array_merge( array( $event_type ), $date_args )
			);
			$rows = $wpdb->get_results( $sql );
			foreach ( $rows as $row ) {
				$metadata = ! empty( $row->metadata ) ? json_decode( $row->metadata, true ) : array();
				if ( empty( $metadata['url_params'] ) || ! is_array( $metadata['url_params'] ) ) {
					continue;
				}
				$url_params = $metadata['url_params'];
				$source = isset( $url_params['utm_source'] ) ? $url_params['utm_source'] : ( isset( $url_params['source'] ) ? $url_params['source'] : null );
				$medium = isset( $url_params['utm_medium'] ) ? $url_params['utm_medium'] : ( isset( $url_params['medium'] ) ? $url_params['medium'] : null );
				$campaign = isset( $url_params['utm_campaign'] ) ? $url_params['utm_campaign'] : ( isset( $url_params['campaign'] ) ? $url_params['campaign'] : null );
				$webURL = isset( $url_params['webURL'] ) ? $url_params['webURL'] : ( isset( $url_params['weburl'] ) ? $url_params['weburl'] : null );
				$botID = isset( $url_params['botID'] ) ? $url_params['botID'] : ( isset( $url_params['bot_id'] ) ? $url_params['bot_id'] : null );

				if ( $source ) {
					$source = strtolower( $source );
					if ( ! isset( $attribution_stats[ $event_type ]['source'][$source] ) ) {
						$attribution_stats[ $event_type ]['source'][$source] = array( 'count' => 0, 'value' => 0 );
					}
					$attribution_stats[ $event_type ]['source'][$source]['count']++;
				}
				if ( $medium ) {
					$medium = strtolower( $medium );
					if ( ! isset( $attribution_stats[ $event_type ]['medium'][$medium] ) ) {
						$attribution_stats[ $event_type ]['medium'][$medium] = array( 'count' => 0, 'value' => 0 );
					}
					$attribution_stats[ $event_type ]['medium'][$medium]['count']++;
				}
				if ( $campaign ) {
					$campaign = strtolower( $campaign );
					if ( ! isset( $attribution_stats[ $event_type ]['campaign'][$campaign] ) ) {
						$attribution_stats[ $event_type ]['campaign'][$campaign] = array( 'count' => 0, 'value' => 0 );
					}
					$attribution_stats[ $event_type ]['campaign'][$campaign]['count']++;
				}
				if ( $webURL ) {
					if ( ! isset( $attribution_stats[ $event_type ]['client'][$webURL] ) ) {
						$attribution_stats[ $event_type ]['client'][$webURL] = array( 'count' => 0, 'value' => 0 );
					}
					$attribution_stats[ $event_type ]['client'][$webURL]['count']++;
				}
				if ( $botID ) {
					$botID = strtolower( $botID );
					if ( ! isset( $attribution_stats[ $event_type ]['bot'][$botID] ) ) {
						$attribution_stats[ $event_type ]['bot'][$botID] = array( 'count' => 0, 'value' => 0 );
					}
					$attribution_stats[ $event_type ]['bot'][$botID]['count']++;
				}
			}
		}

		// Sort and limit to top 10 for each event + dimension
		foreach ( $event_types_for_attribution as $et ) {
			foreach ( array( 'source', 'medium', 'campaign', 'client', 'bot' ) as $dim ) {
				arsort( $attribution_stats[ $et ][ $dim ] );
				$attribution_stats[ $et ][ $dim ] = array_slice( $attribution_stats[ $et ][ $dim ], 0, 10, true );
			}
		}

		// For backward compatibility, expose quote_submitted stats as top-level variables
		$source_stats  = $attribution_stats['quote_submitted']['source']  ?? array();
		$medium_stats  = $attribution_stats['quote_submitted']['medium']  ?? array();
		$campaign_stats = $attribution_stats['quote_submitted']['campaign'] ?? array();
		$client_stats  = $attribution_stats['quote_submitted']['client']  ?? array();
		$bot_stats     = $attribution_stats['quote_submitted']['bot']     ?? array();

		return array(
			'total_requests'          => $total_requests,
			'total_value'             => $total_value,
			'cash_in_bag'             => $cash_in_bag,
			'monthly_total'           => $monthly_total,
			'quarterly_total'         => $quarterly_total,
			'annual_total'            => $annual_total,
			'invoiced_monthly'        => $invoiced_monthly,
			'invoiced_quarterly'      => $invoiced_quarterly,
			'invoiced_annual'         => $invoiced_annual,
			'unique_features_used'    => $unique_features_used,
			'quote_to_confirmed_rate' => $quote_to_confirmed_rate,
			'quote_to_invoiced_rate'  => $quote_to_invoiced_rate,
			'total_wizard_views'      => $total_wizard_views,
			'total_interactions'      => $total_interactions,
			'feature_added_count'     => $feature_added_count,
			'checkout_starts'         => $checkout_starts,
			'quotes_submitted_count'  => $quotes_submitted_count,
			'checkout_abandoned'      => $checkout_abandoned,
			'unique_initial_engagement' => $unique_initial_engagement,
			'view_to_quote_rate'      => $view_to_quote_rate,
			'engagement_rate'         => $engagement_rate,
			'avg_items'               => $avg_items,
			'top_features'            => $top_features,
			'date_labels'             => $date_labels,
			'date_counts_array'       => $date_counts_array,
			'billing_labels'          => $billing_labels,
			'billing_data'            => $billing_data,
			'revenue_data'            => $revenue_data,
			'status_counts'           => $status_counts,
			'date_filter_display'     => $date_filter_display,
			// New multi-event attribution stats
			'attribution_stats'       => $attribution_stats,
			// Backward compatibility: quote_submitted stats also at top level
			'source_stats'            => $source_stats,
			'medium_stats'            => $medium_stats,
			'campaign_stats'          => $campaign_stats,
			'client_stats'            => $client_stats,
			'bot_stats'               => $bot_stats,
		);
	}
}
