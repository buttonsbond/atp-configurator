<?php
/**
 * Quote Requests View class
 * Renders the quote requests management table
 *
 * @package WP_Configurator_Wizard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Quote_Requests_View {

	/**
	 * Settings manager instance
	 *
	 * @var Settings_Manager
	 */
	private $settings_manager;

	/**
	 * Constructor
	 *
	 * @param Settings_Manager $settings_manager
	 */
	public function __construct( Settings_Manager $settings_manager ) {
		$this->settings_manager = $settings_manager;
	}

	/**
	 * Render the quote requests page
	 */
	public function render(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'configurator_quote_requests';
		$nonce_action = 'wp_configurator_quote_requests';

		// Handle bulk actions
		if ( isset( $_POST['action'] ) && isset( $_POST['action2'] ) && ! isset( $_POST['single_delete'] ) && ! isset( $_POST['single_resend'] ) ) {
			$action = $_POST['action'] !== '-1' ? $_POST['action'] : $_POST['action2'];
			if ( $action === 'delete' && ! empty( $_POST['quote_request_ids'] ) ) {
				check_admin_referer( $nonce_action );
				$ids = array_map( 'intval', $_POST['quote_request_ids'] );
				foreach ( $ids as $id ) {
					$wpdb->delete( $table_name, array( 'id' => $id ), array( '%d' ) );
				}
				echo '<div class="notice notice-success"><p>Deleted ' . count( $ids ) . ' quote request(s).</p></div>';
			} elseif ( $action === 'resend_webhook' && ! empty( $_POST['quote_request_ids'] ) ) {
				check_admin_referer( $nonce_action );
				$ids = array_map( 'intval', $_POST['quote_request_ids'] );
				$resent = 0;
				foreach ( $ids as $id ) {
					$req = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id ) );
					if ( $req ) {
						$result = $this->resend_webhook_for_request( $req );
						if ( $result ) {
							$resent++;
						}
					}
				}
				echo '<div class="notice notice-success"><p>Resent webhook for ' . $resent . ' request(s).</p></div>';
			} elseif ( in_array( $action, array( 'mark_pending', 'mark_quoted', 'mark_confirmed', 'mark_invoiced', 'mark_cancelled', 'mark_rejected' ), true ) && ! empty( $_POST['quote_request_ids'] ) ) {
				check_admin_referer( $nonce_action );
				$new_status = str_replace( 'mark_', '', $action );
				$ids = array_map( 'intval', $_POST['quote_request_ids'] );
				$updated = 0;
				foreach ( $ids as $id ) {
					$result = $wpdb->update(
						$table_name,
						array( 'status' => $new_status ),
						array( 'id' => $id ),
						array( '%s' ),
						array( '%d' )
					);
					if ( $result !== false ) {
						$updated++;
					}
				}
				echo '<div class="notice notice-success"><p>Updated ' . $updated . ' request(s) to status: ' . esc_html( $new_status ) . '.</p></div>';
			}
		}

		// Handle single delete (fallback for non-JS)
		if ( isset( $_POST['single_delete'] ) ) {
			check_admin_referer( $nonce_action );
			$id = intval( $_POST['single_delete'] );
			$wpdb->delete( $table_name, array( 'id' => $id ), array( '%d' ) );
			echo '<div class="notice notice-success"><p>Quote request deleted.</p></div>';
		}

		// Handle single resend
		if ( isset( $_POST['single_resend'] ) ) {
			check_admin_referer( $nonce_action );
			$id = intval( $_POST['single_resend'] );
			$req = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id ) );
			if ( $req ) {
				$result = $this->resend_webhook_for_request( $req );
				if ( $result ) {
					echo '<div class="notice notice-success"><p>Webhook resent successfully.</p></div>';
				} else {
					echo '<div class="notice notice-error"><p>Webhook resend failed. Check debug log.</p></div>';
				}
			} else {
				echo '<div class="notice notice-error"><p>Request not found.</p></div>';
			}
		}

		// Fetch requests
		$requests = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY created_at DESC" );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'ATP Quote Requests', 'wp-configurator' ); ?></h1>

			<?php if ( empty( $requests ) ) : ?>
				<p>No quote requests yet.</p>
			<?php else : ?>
				<form method="post" id="quote-requests-form">
					<?php wp_nonce_field( $nonce_action ); ?>
					<input type="hidden" name="action2" value="-1">
					<div class="tablenav top">
						<div class="alignleft actions">
							<select name="action" id="bulk-action-selector-top">
								<option value="-1">Bulk actions</option>
								<option value="delete">Delete</option>
								<option value="resend_webhook">Resend Webhook</option>
								<option value="mark_pending">Mark as Pending</option>
								<option value="mark_quoted">Mark as Quoted</option>
								<option value="mark_confirmed">Mark as Confirmed</option>
								<option value="mark_invoiced">Mark as Invoiced</option>
								<option value="mark_cancelled">Mark as Cancelled</option>
								<option value="mark_rejected">Mark as Rejected</option>
							</select>
							<input type="submit" name="apply" class="button action" value="Apply">
						</div>
					</div>
					<table class="wp-list-table widefat striped">
						<thead>
							<tr>
								<th>Date</th>
								<th>Name</th>
								<th>Business</th>
								<th>Email</th>
								<th>Phone</th>
								<th>Total</th>
								<th>Status</th>
								<th>Admin Email</th>
								<th>Client Email</th>
								<th>Webhook</th>
								<th>Actions</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $requests as $req ) :
								$items = json_decode( $req->items, true );
								$totals = json_decode( $req->totals, true );
								$grand_total = $totals['grand_total'] ?? 0;
								$item_count = is_array( $items ) ? count( $items ) : 0;
								// Build a simple list of item names for display
								$item_names = array();
								if ( is_array( $items ) ) {
									foreach ( $items as $it ) {
										$item_names[] = esc_html( $it['name'] ?? '' );
									}
								}
								$items_summary = $item_count . ' item' . ( $item_count === 1 ? '' : 's' ) . ( $item_names ? ': ' . implode( ', ', $item_names ) : '' );
							?>
								<tr>
									<td data-label="Date">
										<?php
										$dt = strtotime( $req->created_at );
										echo date( 'Y-m-d', $dt ) . ' ' . date( 'H:i:s', $dt );
										?>
									</td>
									<td data-label="Name"><?php echo esc_html( $req->name ); ?></td>
									<td data-label="Business"><?php echo esc_html( $req->business ); ?></td>
									<td data-label="Email"><?php echo esc_html( $req->email ); ?></td>
									<td data-label="Phone"><?php echo esc_html( $req->phone ); ?></td>
									<td data-label="Total">€<?php echo number_format( $grand_total, 2 ); ?></td>
									<td data-label="Status">
										<select class="status-select" data-id="<?php echo esc_attr( $req->id ); ?>" data-original="<?php echo esc_attr( $req->status ); ?>">
											<option value="pending" <?php selected( $req->status, 'pending' ); ?>>Pending</option>
											<option value="quoted" <?php selected( $req->status, 'quoted' ); ?>>Quoted</option>
											<option value="confirmed" <?php selected( $req->status, 'confirmed' ); ?>>Confirmed</option>
											<option value="invoiced" <?php selected( $req->status, 'invoiced' ); ?>>Invoiced</option>
											<option value="cancelled" <?php selected( $req->status, 'cancelled' ); ?>>Cancelled</option>
											<option value="rejected" <?php selected( $req->status, 'rejected' ); ?>>Rejected</option>
										</select>
										<span class="status-spinner" style="display:none; margin-left: 5px;">⟳</span>
									</td>
									<td data-label="Admin Email">
										<?php
										$admin_sent = property_exists( $req, 'admin_email_sent' ) ? ! empty( $req->admin_email_sent ) : false;
										if ( $admin_sent ) : ?>
											<span style="color:green; font-weight:bold;">✓</span>
										<?php else : ?>
											<span style="color:red;">✗</span>
										<?php endif; ?>
									</td>
									<td data-label="Client Email">
										<?php
										$client_sent = property_exists( $req, 'client_email_sent' ) ? ! empty( $req->client_email_sent ) : false;
										if ( $client_sent ) : ?>
											<span style="color:green; font-weight:bold;">✓</span>
										<?php else : ?>
											<span style="color:red;">✗</span>
										<?php endif; ?>
									</td>
									<td data-label="Webhook">
										<?php
										$webhook_sent = property_exists( $req, 'webhook_sent' ) && ! empty( $req->webhook_sent );
										if ( $webhook_sent ) : ?>
											<span style="color:green; font-weight:bold;">✓</span>
										<?php else : ?>
											<?php $webhook_response = property_exists( $req, 'webhook_response' ) ? $req->webhook_response : 'Not configured or failed'; ?>
											<span title="<?php echo esc_attr( $webhook_response ); ?>">
												<span style="color:red;">✗</span>
											</span>
										<?php endif; ?>
									</td>
									<td data-label="Actions">
										<input type="checkbox" name="quote_request_ids[]" value="<?php echo esc_attr( $req->id ); ?>" style="margin-right: 8px; vertical-align: middle;">
										<button type="submit" name="single_delete" value="<?php echo esc_attr( $req->id ); ?>" class="button button-small" onclick="return confirm('Delete this request?');">Delete</button>
										<button type="submit" name="single_resend" value="<?php echo esc_attr( $req->id ); ?>" class="button button-small" style="margin-left:4px;" onclick="return confirm('Resend webhook for this request?');">Resend Webhook</button>
									</td>
								</tr>
								<tr class="items-row">
									<td colspan="11" data-label="Items" style="white-space: normal; line-height: 1.4; font-size: 0.9em; padding: 8px 12px; background: #f9f9f9; border-top: 1px solid #eee;">
										<?php echo $items_summary; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</form>
				<style type="text/css">
					/* Base table styles - override widefat fixed layout */
					.wp-list-table { table-layout: auto !important; width: 100%; }
					.wp-list-table td, .wp-list-table th { padding: 6px 10px; vertical-align: middle; }
					/* Column widths - 11 columns */
					th:nth-child(1), td:nth-child(1) { width: 9%; } /* Date */
					th:nth-child(2), td:nth-child(2) { width: 10%; } /* Name */
					th:nth-child(3), td:nth-child(3) { width: 10%; } /* Business */
					th:nth-child(4), td:nth-child(4) { width: 18%; white-space: normal; line-height: 1.3; } /* Email - longest */
					th:nth-child(5), td:nth-child(5) { width: 8%; } /* Phone */
					th:nth-child(6), td:nth-child(6) { width: 6%; text-align: right; } /* Total */
					th:nth-child(7), td:nth-child(7) { width: 6%; } /* Status */
					th:nth-child(8), td:nth-child(8) { width: 5%; text-align: center; } /* Admin Email */
					th:nth-child(9), td:nth-child(9) { width: 5%; text-align: center; } /* Client Email */
					th:nth-child(10), td:nth-child(10) { width: 5%; text-align: center; } /* Webhook */
					th:nth-child(11), td:nth-child(11) { width: 18%; } /* Actions */
					/* Header wrapping */
					thead th { white-space: normal; height: auto; padding: 6px 10px; font-weight: 600; }
					/* Button spacing in Actions column */
					td:nth-child(11) button { margin: 1px 3px 1px 0; padding: 3px 6px; font-size: 0.85em; }
					td:nth-child(11) input[type="checkbox"] { margin-right: 6px; vertical-align: middle; }
					/* Status badges */
					.status-badge {
						display: inline-block;
						padding: 3px 10px;
						border-radius: 12px;
						font-size: 12px;
						font-weight: 600;
						text-transform: uppercase;
						letter-spacing: 0.5px;
					}
					.status-pending { background-color: #f56e28; color: #fff; }
					.status-quoted { background-color: #2271b1; color: #fff; }
					.status-confirmed { background-color: #46b450; color: #fff; }
					.status-invoiced { background-color: #93588c; color: #fff; }
					.status-cancelled { background-color: #646970; color: #fff; }
					.status-rejected { background-color: #dc3232; color: #fff; }
					/* Inline status select */
					.status-select {
						padding: 4px 8px;
						border: 1px solid #ddd;
						border-radius: 4px;
						background: #fff;
						font-size: 12px;
						cursor: pointer;
					}
					/* Responsive card layout - switches at 992px */
					@media screen and (max-width: 992px) {
						.wp-list-table thead { display: none; }
						.wp-list-table tr { display: block; margin-bottom: 1rem; border: 1px solid #ccd0d4; border-radius: 4px; overflow: hidden; background: #fff; }
						.wp-list-table tbody tr { border: none; margin-bottom: 0; }
						/* Each data cell becomes a two-column grid: label (auto width) | value (rest) */
						.wp-list-table tbody tr:not(.items-row) td {
							display: grid !important;
							grid-template-columns: max-content 1fr !important;
							gap: 0 0.75rem;
							padding: 8px 10px;
							border: none;
							border-bottom: 1px solid #e8e8e8;
							font-size: 0.9em;
							align-items: start;
						}
						.wp-list-table tbody tr:not(.items-row) td::before {
							content: attr(data-label);
							display: block;
							grid-column: 1;
							font-weight: 600;
							font-size: 0.8em;
							color: #666;
							white-space: nowrap;
							line-height: 1.4;
							padding-top: 2px;
						}
						.wp-list-table tbody tr:not(.items-row) td > * {
							grid-column: 2;
							text-align: left;
							word-wrap: break-word;
							overflow-wrap: break-word;
						}
						.wp-list-table tbody tr:not(.items-row) td:last-child { border-bottom: none; }
						/* Actions cell: full-width buttons with checkbox */
						.wp-list-table tbody tr:not(.items-row) td[data-label="Actions"] {
							grid-template-columns: 1fr !important;
						}
						.wp-list-table tbody tr:not(.items-row) td[data-label="Actions"]::before { display: none; }
						.wp-list-table tbody tr:not(.items-row) td[data-label="Actions"] > * {
							grid-column: 1;
							display: flex;
							flex-wrap: wrap;
							align-items: center;
							gap: 6px;
						}
						/* Items row: full width */
						.items-row td {
							grid-column: 1 / -1;
							display: block !important;
							padding: 10px;
							background: #f7f7f7;
							border-top: 1px solid #e8e8e8;
							border-bottom: none;
							font-size: 0.9em;
						}
						.items-row td::before {
							display: inline;
							font-weight: 600;
							color: #666;
							margin-right: 6px;
							font-size: 0.85em;
						}
					}
				</style>
			<?php endif; ?>
		</div>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				$('#cb-select-all').on('change', function() {
					$('input[name="quote_request_ids[]"]').prop('checked', this.checked);
				});
				// Update select all checkbox when individual checkboxes change
				$('input[name="quote_request_ids[]"]').on('change', function() {
					var allChecked = $('input[name="quote_request_ids[]"]').length === $('input[name="quote_request_ids[]"]:checked').length;
					$('#cb-select-all').prop('checked', allChecked);
				});

				// Inline status update
				$('.status-select').on('change', function() {
					var $select = $(this);
					var $spinner = $select.siblings('.status-spinner');
					var requestId = $select.data('id');
					var newStatus = $select.val();
					var originalStatus = $select.data('original');

					// Show spinner
					$spinner.show();

					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'update_quote_status',
							nonce: '<?php echo wp_create_nonce( 'wp_configurator_nonce' ); ?>',
							request_id: requestId,
							status: newStatus
						},
						success: function(response) {
							$spinner.hide();
							if (response.success) {
								$select.data('original', newStatus);
								// Show brief success indicator
								$select.css('border-color', '#46b450');
								setTimeout(function() {
									$select.css('border-color', '#ddd');
								}, 1500);
							} else {
								alert('Error: ' + (response.data ? response.data.message : 'Unknown error'));
								$select.val(originalStatus); // revert
							}
						},
						error: function() {
							$spinner.hide();
							alert('AJAX error. Could not update status.');
							$select.val(originalStatus); // revert
						}
					});
				});
			});
		</script>
		<?php
	}

	/**
	 * Resend webhook for a specific quote request
	 *
	 * @param object $req Quote request database row
	 * @return bool Success or failure
	 */
	private function resend_webhook_for_request( $req ) {
		$options = $this->settings_manager->get_options();
		$webhook_url = $options['settings']['webhook_url'] ?? '';

		if ( ! $webhook_url || ! is_email( $req->email ) ) {
			return false;
		}

		$payload = array(
			'request_id' => $req->id,
			'name'       => $req->name,
			'business'   => $req->business,
			'email'      => $req->email,
			'phone'      => $req->phone,
			'selected_items' => json_decode( $req->items, true ),
			'totals'     => json_decode( $req->totals, true ),
			'timestamp'  => $req->created_at,
		);

		$response = wp_remote_post( $webhook_url, array(
			'method'  => 'POST',
			'body'    => wp_json_encode( $payload ),
			'headers' => array( 'Content-Type' => 'application/json' ),
			'timeout' => 15,
		) );

		if ( is_wp_error( $response ) ) {
			$error_msg = $response->get_error_message();
			$this->log_webhook_status( $req->id, 0, $error_msg );
			return false;
		} else {
			$response_body = wp_remote_retrieve_body( $response );
			$response_code = wp_remote_retrieve_response_code( $response );
			$response_msg = "HTTP $response_code: $response_body";
			$this->log_webhook_status( $req->id, 1, $response_msg );
			return true;
		}
	}

	/**
	 * Log webhook delivery status to database
	 *
	 * @param int    $request_id Quote request ID
	 * @param int    $sent 1 for success, 0 for failure
	 * @param string $response Optional response message
	 */
	private function log_webhook_status( $request_id, $sent, $response = null ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'configurator_quote_requests';

		$wpdb->update(
			$table_name,
			array(
				'webhook_sent'      => $sent,
				'webhook_response'  => $response,
			),
			array( 'id' => $request_id ),
			array( '%d', '%s' ),
			array( '%d' )
		);
	}
}
