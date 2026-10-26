<?php
/**
 * kpir - Database Class
 *
 * Handles all database operations for the kpir plugin.
 *
 * @package WordPress_Plugin_Stub
 * @author  Marcin Pietrzak <marcin@iworks.pl>
 * @since   1.2.1
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'iworks_kpir_db' ) ) {
	return;
}

/**
 * Database functionality for kpir.
 *
 * This class handles all database-specific functionality including:
 * - Database table creation and updates
 * - Database version tracking
 * - Table registration with $wpdb
 * - Table cleanup on uninstall
 *
 * @since 1.2.1
 */
class iworks_kpir_db {

	/**
	 * Option name for storing the database version.
	 *
	 * This is used to track the current version of the database schema
	 * and to determine if database updates are needed.
	 *
	 * @since 1.2.1
	 * @var string
	 */
	private string $_db_version = 'iworks_kpir_db_version';

	/**
	 * Array of database table names without prefix.
	 *
	 * Contains the names of all custom database tables used by the plugin.
	 * These names will be automatically prefixed with the WordPress table prefix.
	 *
	 * @since 1.2.1
	 * @var string[]
	 */
	private array $table_names = array();
	/**
	 * Class constructor.
	 *
	 * @since 1.2.1
	 */
	public function __construct() {
		$this->register_tables();
		/**
		 * WordPress hooks.
		 */
		add_action( 'shutdown', array( $this, 'db_install' ) );
		/**
		 * kpir hooks.
		 */
		add_action( 'iworks/kpir/register_uninstall_hook', array( $this, 'drop_tables' ) );
		add_action( 'iworks/kpir/register_activation_hook', array( $this, 'db_install' ) );
	}

	/**
	 * Register plugin tables with $wpdb.
	 *
	 * This allows the tables to be referenced as $wpdb->{table_name} throughout the plugin.
	 * Tables are registered with the WordPress database prefix.
	 *
	 * @since 1.2.1
	 */
	private function register_tables() {
		global $wpdb;

		// Register each table.
		foreach ( $this->table_names as $key => $table_name ) {
			$wpdb->$table_name = $wpdb->prefix . $table_name;
		}
	}

	/**
	 * Create the database table if it doesn't exist.
	 *
	 * @since 1.2.1
	 */
	public function db_install() {
		/**
		 * Get current DB version.
		 */
		$db_version = intval( get_option( $this->_db_version ) );
		if ( 1 > $db_version ) {
			add_option( $this->_db_version, 0, '', 'no' );
		}
		/**
		 * chemistry table
		 */
		$version_to_update = 2;
		if ( $db_version < $version_to_update ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			/**
			 * remove " " "-" and "^PL" from NIP field to normalize it to KSEF import
			 */
			global $wpdb;
			foreach ( array( ' ', '-', 'PL' ) as $string_to_remove ) {
				$wpdb->query( "UPDATE {$wpdb->postmeta} SET meta_value = REPLACE(meta_value, '{$string_to_remove}', '') WHERE meta_key = 'iworks_kpir_contractor_data_nip'" );
			}
			update_option( $this->_db_version, $version_to_update );
			$db_version = intval( get_option( $this->_db_version ) );
		}
	}

	/**
	 * Drop all plugin database tables
	 *
	 * @since 1.2.1
	 * @return void
	 */
	public function drop_tables() {
		global $wpdb;

		// Disable foreign key checks to avoid issues with table dependencies
		$wpdb->query( 'SET FOREIGN_KEY_CHECKS = 0' );

		// Drop each table
		foreach ( $this->table_names as $table_name ) {
			$sql   = 'DROP TABLE IF EXISTS %s';
			$query = $wpdb->prepare( $sql, $wpdb->$table_name );
			$wpdb->query( $query );
		}

		// Re-enable foreign key checks
		$wpdb->query( 'SET FOREIGN_KEY_CHECKS = 1' );

		// Delete the database version option
		delete_option( $this->_db_version );
	}
}
