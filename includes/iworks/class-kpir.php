<?php
/**
 * Plugin Name: KPIR - Main Plugin Class
 * Plugin URI: https://iworks.pl/
 * Description: Main plugin class for KPIR (Księga Przychodów i Rozchodów) - Polish Revenue and Expense Ledger.
 * Version: 1.0.0
 * Author: Marcin Pietrzak
 * Author URI: https://iworks.pl/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package KPIR
 * @category Core
 * @author Marcin Pietrzak <marcin@iworks.pl>
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'iworks_kpir' ) ) {
	return;
}

require_once dirname( __DIR__, 1 ) . '/iworks.php';

/**
 * Main plugin class for KPIR (Księga Przychodów i Rozchodów).
 *
 * This class serves as the main controller for the KPIR plugin,
 * handling initialization, post type registration, and core functionality.
 *
 * @package KPIR
 * @subpackage Core
 * @since 1.0.0
 */
class iworks_kpir extends iworks {

	/**
	 * User capability required to access plugin features.
	 *
	 * @var string
	 */
	private $capability;

	/**
	 * Instance of the contractor post type handler.
	 *
	 * @var iworks_kpir_posttypes_contractor
	 */
	private $post_type_contractor;

	/**
	 * Instance of the invoice post type handler.
	 *
	 * @var iworks_kpir_posttypes_invoice
	 */
	private $post_type_invoice;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	protected $version = 'PLUGIN_VERSION';

	/**
	 * Base plugin directory path.
	 *
	 * @var string
	 */
	protected $base;

	/**
	 * Plugin directory name.
	 *
	 * @var string
	 */
	protected $dir;

	/**
	 * Class constructor.
	 *
	 * Initializes the plugin by setting up post types, actions, and filters.
	 * Sets up the plugin version, capability requirements, and directory paths.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();
		$this->version    = 'PLUGIN_VERSION';
		$this->capability = apply_filters( 'iworks_kpir_capability', 'manage_options' );
		$this->base       = dirname( __DIR__, 1 );
		$this->dir        = basename( dirname( $this->base ) );
		/**
		 * post_types
		 */
		$post_types = array( 'invoice', 'contractor' );
		foreach ( $post_types as $post_type ) {
			include_once $this->base . '/iworks/kpir/posttypes/' . $post_type . '.php';
			$class        = sprintf( 'iworks_kpir_posttypes_%s', $post_type );
			$value        = sprintf( 'post_type_%s', $post_type );
			$this->$value = new $class();
		}
		/**
		 * admin init
		 */
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'wp_loaded', array( $this, 'plugins_loaded' ), 1 );
		/**
		 * load github class
		 */
		$filename = __DIR__ . '/kpir/class-iworks-kpir-github.php';
		if ( is_file( $filename ) ) {
			include_once $filename;
			new iworks_kpir_github();
		}
	}

	public function plugins_loaded() {
		if ( isset( $_REQUEST['action'] ) ) {
			switch ( $_REQUEST['action'] ) {
				case 'iworks_kpir_jpk_vat_3';
					$file = $this->get_module_file( 'jpk/vat_3' );
					if ( is_readable( $file ) ) {
						include_once $file;
						$page = new iworks_kpir_jpk_vat_3();
						$page->get_xml( $this );
					}
				return;
				case 'iworks_kpir_jpk_v7m';
					$file = $this->get_module_file( 'jpk/v7m' );
					if ( is_readable( $file ) ) {
						include_once $file;
						$page = new iworks_kpir_jpk_v7m();
						$page->get_xml( $this );
					}
				return;
			}
		}
	}

	public function dashboard_widget_current_month( $post, $callback_args ) {
		if ( ! is_object( $this->post_type_invoice ) ) {
			return;
		}
		$date = date( 'Y-m', time() );
		$this->post_type_invoice->month_table( $date );
	}

	public function dashboard_widget_past_month( $post, $callback_args ) {
		if ( ! is_object( $this->post_type_invoice ) ) {
			return;
		}
		$date = sprintf( '%s -1 month', date( 'c', time() ) );
		$date = date( 'Y-m', strtotime( $date ) );
		$this->post_type_invoice->month_table( $date );
	}

	public function add_dashboard_widgets() {
		$current = date( _x( 'Y F', 'date admin dashbord widget', 'kpir' ), time() );
		$date    = strtotime( sprintf( '%s -1 month', date( 'c', time() ) ) );
		$past    = date( _x( 'Y F', 'date admin dashbord widget', 'kpir' ), $date );
		$widgets = array(
			'current_month' => sprintf( __( 'Current month: %s', 'kpir' ), $current ),
			'past_month'    => sprintf( __( 'Past month: %s', 'kpir' ), $past ),
		);
		foreach ( $widgets as $widget_id => $widget_name ) {
			$callback = array( $this, sprintf( 'dashboard_widget_%s', $widget_id ) );
			wp_add_dashboard_widget( $widget_id, $widget_name, $callback );
		}
	}

	public function admin_init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'wp_ajax_kpir_duplicate_invoice', array( $this, 'duplicate_invoice' ) );
		add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widgets' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
		add_filter( 'post_row_actions', array( $this, 'row_actions' ), 10, 2 );
	}

	public function admin_enqueue_scripts() {
		$screen = get_current_screen();
		/**
		 * off on not KPiR pages
		 */
		$re = sprintf( '/%s_/', __CLASS__ );
		if ( ! preg_match( $re, $screen->id ) ) {
			return;
		}
		/**
		 * datepicker
		 */
		$file = 'assets/externals/datepicker/css/jquery-ui-datepicker.css';
		$file = plugins_url( $file, $this->base );
		wp_register_style( 'jquery-ui-datepicker', $file, false, '1.12.1' );
		/**
		 * select2
		 */
		$file = 'assets/externals/select2/css/select2.min.css';
		$file = plugins_url( $file, $this->base );
		wp_register_style( 'select2', $file, false, '4.0.3' );
		/**
		 * Admin styles
		 */
		$file    = sprintf( '/assets/styles/kpir-admin%s.css', $this->dev );
		$version = $this->get_version( $file );
		$file    = plugins_url( $file, $this->base );
		wp_register_style( 'admin-kpir', $file, array( 'jquery-ui-datepicker', 'select2' ), $version );
		wp_enqueue_style( 'admin-kpir' );
		/**
		 * select2
		 */
		wp_register_script( 'select2', plugins_url( 'assets/externals/select2/js/select2.full.min.js', $this->base ), array(), '4.0.3' );
		/**
		 * Admin scripts
		 */
		$files = array(
			'kpir-admin' => sprintf( 'assets/scripts/admin/kpir%s.js', $this->dev ),
		);
		if ( '' == $this->dev ) {
			$files = array(
				'kpir-admin-datepicker' => 'assets/scripts/admin/src/datepicker.js',
				'kpir-admin-invoice'    => 'assets/scripts/admin/src/invoice.js',
				'kpir-admin-jpk'        => 'assets/scripts/admin/src/jpk.js',
				'kpir-admin-select2'    => 'assets/scripts/admin/src/select2.js',
			);
		}
		$deps = array(
			'jquery-ui-datepicker',
			'select2',
		);
		foreach ( $files as $handle => $file ) {
			wp_register_script(
				$handle,
				plugins_url( $file, $this->base ),
				$deps,
				$this->get_version(),
				true
			);
			wp_enqueue_script( $handle );
		}
		/**
		 * JavaScript messages
		 *
		 * @since 1.0.0
		 */
		wp_localize_script(
			'' == $this->dev ? 'kpir-admin-invoice' : 'kpir-admin',
			__CLASS__,
			array(
				'messages' => array(
					'duplicate_confirm' => __( 'Are you sure you want to create a duplicate copy of this invoice?', 'kpir' ),
					'duplicate_error'   => __( 'An error occurred duplicating invoice. Please try again.', 'kpir' ),
					'jpk'               => array(
						'vat' => array(
							'select_month' => __( 'Please select month first!', 'kpir' ),
						),
					),
				),
			)
		);
	}

	public function init() {
		if ( is_admin() ) {
		} else {
			$file = 'assets/styles/kpir' . $this->dev . '.css';
			wp_enqueue_style( 'kpir', plugins_url( $file, $this->base ), array(), $this->get_version( $file ) );
		}
	}

	public function get_post_type_invoice() {
		return $this->post_type_invoice;
	}

	/**
	 * Show reports page
	 */
	public function show_page_reports( $report = 'monthly' ) {
		echo '<div class="wrap">';
		switch ( $report ) {
			case 'monthly':
				$file = $this->get_module_file( 'reports/monthly' );
				include_once $file;
				$this->html_title( esc_html__( 'Monthly report', 'kpir' ) );
				$page = new iworks_kpir_reports_monthly();
				$page->show( $this );
				break;
			case 'annually':
				$file = $this->get_module_file( 'reports/annually' );
				include_once $file;
				$this->html_title( esc_html__( 'Annually report', 'kpir' ) );
				$page = new iworks_kpir_reports_annually();
				$page->show( $this );
				break;
			default:
				$this->html_title( esc_html__( 'Reports', 'kpir' ) );
				break;
		}
		echo '</div>';
	}

	/**
	 * Show JPK VAT(3) page
	 */
	public function show_page_jpk_vat_3() {
		echo '<div class="wrap">';
		$file = $this->get_module_file( 'jpk/vat_3' );
		if ( is_readable( $file ) ) {
			include_once $file;
			$this->html_title( esc_html__( 'JPK VAT(3)', 'kpir' ) );
			$page = new iworks_kpir_jpk_vat_3();
			$page->show( $this );
		} else {
			_e( 'Something went wrong!', 'kpir' );
		}
		echo '</div>';
	}

	/**
	 * Show JPK v7m page
	 *
	 * @since 0.1.4
	 */
	public function show_page_jpk_v7m() {
		echo '<div class="wrap">';
		$file = $this->get_module_file( 'jpk/v7m' );
		if ( is_readable( $file ) ) {
			include_once $file;
			$this->html_title( esc_html__( 'JPK V7M', 'kpir' ) );
			$page = new iworks_kpir_jpk_v7m();
			$page->show( $this );
		} else {
			_e( 'Something went wrong!', 'kpir' );
		}
		echo '</div>';
	}

	/**
	 * Plugin row data
	 */
	public function plugin_row_meta( $links, $file ) {
		if ( $this->dir . '/kpir.php' == $file ) {
			if ( ! is_multisite() && current_user_can( $this->capability ) ) {
				$links[] = '<a href="themes.php?page=' . $this->dir . '/admin/index.php">' . __( 'Settings', 'kpir' ) . '</a>';
			}
			/* start:free */
			$links[] = '<a href="http://iworks.pl/donate/kpir.php">' . __( 'Donate', 'kpir' ) . '</a>';
			/* end:free */
		}
		return $links;
	}

	/**
	 * Get wp nonce action name.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id Entry ID.
	 * @returns string $action WP nonce string
	 */
	private function get_nonce_action( $id ) {
		$action = sprintf( 'duplicate_invoice_%d', $id );
		return $action;
	}

	/**
	 * Modifies the row actions for the invoice post type in the admin list table.
	 *
	 * Adds a 'Duplicate' action link and removes the 'Quick Edit' action.
	 * Only affects the invoice post type.
	 *
	 * @since 1.0.0
	 *
	 * @param array    $actions An array of row action links.
	 * @param WP_Post  $item    The post object.
	 * @return array Modified array of row action links.
	 */
	public function row_actions( $actions, $item ) {
		$post_type = $this->post_type_invoice->get_name();
		if ( $post_type == $item->post_type ) {
			$nonce_action                 = $this->get_nonce_action( $item->ID );
			$duplicate_nonce              = wp_create_nonce( $nonce_action );
			$actions['duplicate_invoice'] = sprintf(
				'<a data-nonce="%s" data-id="%s" class="duplicate-invoice-link">%s</a>',
				$duplicate_nonce,
				$item->ID,
				__( 'Duplicate', 'kpir' )
			);
			/**
			 * remove quick edit
			 */
			if ( isset( $actions['inline hide-if-no-js'] ) ) {
				unset( $actions['inline hide-if-no-js'] );
			}
		}
		return $actions;
	}

	/**
	 * Handles the AJAX request to duplicate an invoice.
	 *
	 * Creates a copy of the specified invoice as a draft with 'Copy of' prefixed to the title.
	 * Copies all post meta data from the original invoice.
	 *
	 * @since 1.0.0
	 *
	 * @return void Sends JSON response indicating success or failure.
	 */
	public function duplicate_invoice() {
		/**
		 * check required data
		 */
		if ( ! isset( $_POST['ID'] ) || ! isset( $_POST['nonce'] ) ) {
			wp_send_json_error();
		}
		/**
		 * check nonce
		 */
		$nonce_action = $this->get_nonce_action( $_POST['ID'] );
		if ( ! wp_verify_nonce( $_POST['nonce'], $nonce_action ) ) {
			wp_send_json_error();
		}
		/**
		 * check post type
		 */
		$is_correct_post_type = $this->post_type_invoice->check_post_type_by_id( $_POST['ID'] );
		if ( ! $is_correct_post_type ) {
			wp_send_json_error();
		}
		$source_post_data = get_post( $_POST['ID'], ARRAY_A );
		$post_data        = array();
		/**
		 * data to add
		 */
		$keys_to_add = array(
			'post_author',
			'post_title',
			'post_type',
		);
		foreach ( $keys_to_add as $key ) {
			if ( isset( $source_post_data[ $key ] ) ) {
				$post_data[ $key ] = $source_post_data[ $key ];
			}
		}
		/**
		 * set post meta
		 */
		$post_meta = get_post_meta( $_POST['ID'] );
		foreach ( $post_meta as $key => $value ) {
			if ( preg_match( '/^iworks_kpir/', $key ) ) {
				$post_data['meta_input'][ $key ] = maybe_unserialize( array_shift( $value ) );
			}
		}
		$post_data['post_title']  = sprintf( __( 'Copy of %s', 'kpir' ), $post_data['post_title'] );
		$post_data['post_status'] = 'draft';
		$result                   = wp_insert_post( $post_data );
		if ( $result ) {
			wp_send_json_success();
		}
		wp_send_json_error();
	}

	/**
	 * Get month query
	 *
	 * @since 1.0.0
	 *
	 * @param string $month Month to prepre, format Y-m
	 *
	 * @return WP_Query $query WordPress Post Query Object
	 */
	public function get_month_query( $month ) {
		$cf_date_name = $this->post_type_invoice->get_custom_field_basic_date_name();
		$args         = array(
			'post_type'        => $this->post_type_invoice->get_name(),
			'nopaging'         => true,
			'suppress_filters' => true,
			'orderby'          => $cf_date_name,
			'order'            => 'ASC',
			'meta_query'       => array(
				array(
					'key'   => $this->post_type_invoice->get_custom_field_year_month_name(),
					'value' => $month,
				),
				array(
					'key'     => $this->post_type_invoice->get_custom_field_basic_date_name(),
					'compare' => 'EXISTS',
				),
			),
		);
		$query        = new WP_Query( $args );
		return $query;
	}

	/**
	 * Get annual query
	 *
	 * @since 1.0.0
	 *
	 * @param string $annual annual to prepre, format Y-m
	 *
	 * @return WP_Query $query WordPress Post Query Object
	 */
	public function get_annual_query( $annual ) {
		$cf_date_name = $this->post_type_invoice->get_custom_field_basic_date_name();
		$args         = array(
			'post_type'        => $this->post_type_invoice->get_name(),
			'nopaging'         => true,
			'suppress_filters' => true,
			'orderby'          => $cf_date_name,
			'order'            => 'ASC',
			'meta_query'       => array(
				'relation' => 'AND',
				array(
					'key'     => $this->post_type_invoice->get_custom_field_year_month_name(),
					'value'   => '^' . $annual,
					'compare' => 'REGEXP',
				),
				array(
					'key'     => $this->post_type_invoice->get_custom_field_basic_date_name(),
					'compare' => 'EXISTS',
				),
			),
		);
		$query        = new WP_Query( $args );
		return $query;
	}

	public function zero_sum_table() {
		return array(
			'income'         => array(
				'integer'    => 0,
				'fractional' => 0,
			),
			'cash_pit'       => array(
				'integer'    => 0,
				'fractional' => 0,
			),
			'expense'        => array(
				'integer'    => 0,
				'fractional' => 0,
			),
			'expense_other'  => array(
				'integer'    => 0,
				'fractional' => 0,
			),
			'expense_salary' => array(
				'integer'    => 0,
				'fractional' => 0,
			),
			'expense_netto'  => array(
				'integer'    => 0,
				'fractional' => 0,
			),
			'vat_income'     => array(
				'integer'    => 0,
				'fractional' => 0,
			),
			'vat_expense'    => array(
				'integer'    => 0,
				'fractional' => 0,
			),
		);
	}

	public function register_activation_hook() {
	}

	public function register_deactivation_hook() {
	}

	/**
	 * Get annual cash-in Query
	 *
	 * @since 1.1.0
	 *
	 * @param string $annual annual to prepre, format Y-m
	 *
	 * @return WP_Query $query WordPress Post Query Object
	 */
	public function get_annual_cash_in_query( $annual ) {
		$cf_date_name = $this->post_type_invoice->get_custom_field_date_of_cash_name();
		$args         = array(
			'post_type'        => $this->post_type_invoice->get_name(),
			'nopaging'         => true,
			'suppress_filters' => true,
			'orderby'          => $cf_date_name,
			'order'            => 'ASC',
			'meta_query'       => array(
				'relation' => 'AND',
				array(
					'key'     => $cf_date_name,
					'value'   => array(
						strtotime( sprintf( '%d-01-01 00:00:00', $annual ) ),
						strtotime( sprintf( '%d-12-31 23:59:59', $annual ) ),
					),
					'compare' => 'BETWEEN',
					'type'    => 'NUMERIC',
				),
				array(
					'key'   => 'iworks_kpir_basic_type',
					'value' => 'income',
				),
			),
		);
		$query        = new WP_Query( $args );
		return $query;
	}
}
