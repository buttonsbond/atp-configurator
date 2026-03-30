<?php
/**
 * Database Manager class
 * Handles table creation, migrations, and upgrades
 *
 * @package WP_Configurator_Wizard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Database_Manager {

	/**
	 * Plugin prefix for table names
	 */
	private $table_prefix;

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		$this->table_prefix = $wpdb->prefix;
	}

	/**
	 * Get quote requests table name
	 */
	public function get_quote_requests_table(): string {
		return $this->table_prefix . 'configurator_quote_requests';
	}

	/**
	 * Get interactions table name
	 */
	public function get_interactions_table(): string {
		return $this->table_prefix . 'configurator_interactions';
	}

	/**
	 * Plugin activation hook - creates all tables
	 */
	public static function activate(): void {
		// Log start of activation
		if (defined('WP_DEBUG') && WP_DEBUG) {
			error_log('WP Configurator: Activation started');
		}

		global $wpdb;

		// Create quote requests table
		$table_name = $wpdb->prefix . 'configurator_quote_requests';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			business varchar(255) DEFAULT '',
			email varchar(255) NOT NULL,
			phone varchar(100) DEFAULT '',
			items longtext NOT NULL,
			totals longtext NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			ip_address varchar(45) DEFAULT '',
			user_agent text DEFAULT '',
			status varchar(50) DEFAULT 'pending',
			admin_email_sent tinyint(1) DEFAULT 0,
			client_email_sent tinyint(1) DEFAULT 0,
			webhook_sent tinyint(1) DEFAULT 0,
			webhook_response text DEFAULT NULL,
			metadata longtext DEFAULT NULL,
			PRIMARY KEY (id),
			KEY email (email),
			KEY created_at (created_at)
		) $charset_collate;";

		if (defined('WP_DEBUG') && WP_DEBUG) {
			error_log('WP Configurator: Creating table SQL: ' . $sql);
		}

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$result = dbDelta( $sql );

		if (defined('WP_DEBUG') && WP_DEBUG) {
			error_log('WP Configurator: dbDelta result: ' . print_r($result, true));
		}

		// Ensure new columns exist (dbDelta can be unreliable with schema changes)
		self::ensure_status_columns();

		// Create interactions tracking table
		self::create_interactions_table();

		if (defined('WP_DEBUG') && WP_DEBUG) {
			error_log('WP Configurator: Activation completed successfully');
		}
	}

	/**
	 * Add status tracking columns if they're missing (upgrade path)
	 * Made public to allow automatic upgrades on admin_init
	 */
	public static function ensure_status_columns(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'configurator_quote_requests';
		$columns = $wpdb->get_col( "SHOW COLUMNS FROM $table_name", 0 );
		$missing = array();
		$expected = array( 'admin_email_sent', 'client_email_sent', 'webhook_sent', 'webhook_response', 'metadata' );
		foreach ( $expected as $col ) {
			if ( ! in_array( $col, $columns ) ) {
				$missing[] = $col;
			}
		}
		if ( ! empty( $missing ) ) {
			foreach ( $missing as $col ) {
				$type = ( $col === 'webhook_response' || $col === 'metadata' ) ? 'TEXT DEFAULT NULL' : 'TINYINT(1) DEFAULT 0';
				$wpdb->query( "ALTER TABLE $table_name ADD COLUMN $col $type" );
			}
		}
	}

	/**
	 * Create interactions tracking table
	 */
	private static function create_interactions_table(): void {
		global $wpdb;
		$table_name = $wpdb->prefix . 'configurator_interactions';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			session_id varchar(64) NOT NULL,
			event_type varchar(50) NOT NULL,
			feature_id varchar(100) DEFAULT NULL,
			category_id varchar(100) DEFAULT NULL,
			metadata longtext DEFAULT NULL,
			ip_address varchar(45) DEFAULT '',
			user_agent text DEFAULT '',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY session_id (session_id),
			KEY event_type (event_type),
			KEY created_at (created_at),
			KEY feature_id (feature_id(64)),
			KEY category_id (category_id(64))
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	/**
	 * Ensure interactions table exists (proactive check)
	 * Can be called on admin_init to guarantee table exists before any AJAX calls
	 */
	public function ensure_interactions_table_exists(): void {
		global $wpdb;
		$table_name = $this->get_interactions_table();

		// Check if table exists
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) != $table_name ) {
			// Table missing - create it
			self::create_interactions_table();
		}
	}
}
