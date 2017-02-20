<?php
/*

Copyright 2017 Marcin Pietrzak (marcin@iworks.pl)

this program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( class_exists( 'iworks_kpir' ) ) {
	return;
}

require_once( dirname( dirname( __FILE__ ) ) . '/iworks.php' );

class iworks_kpir extends iworks {

	private $capability;
	private $post_type_contractor;
	private $post_type_invoice;

	public function __construct() {
		parent::__construct();

		$this->version = 'PLUGIN_VERSION';

		$this->capability = apply_filters( 'iworks_kpir_capability', 'manage_options' );

		/**
		 * post_types
		 */
		$post_types = array( 'invoice', 'contractor' );
		foreach ( $post_types as $post_type ) {
			include_once( $this->base.'/iworks/kpir/posttypes/'.$post_type.'.php' );
			$class = sprintf( 'iworks_kpir_posttypes_%s', $post_type );
			$value = sprintf( 'post_type_%s', $post_type );
			$this->$value = new $class();
		}

		/**
		 * admin init
		 */
		add_action( 'admin_init', array( $this, 'admin_init' ) );
	}

	public function dashboard_widget_current_month( $post, $callback_args ) {
		$date = date( 'Y-m', time() );
		$this->post_type_invoice->month_table( $date );
	}

	public function dashboard_widget_past_month( $post, $callback_args ) {
		$date = sprintf( '%s -1 month', date( 'c', time() ) );
		$date = date( 'Y-m', strtotime( $date ) );
		$this->post_type_invoice->month_table( $date );
	}

	public function add_dashboard_widgets() {
		$current = date( _x( 'Y F', 'date admin dashbord widget', 'kpir' ), time() );
		$date = strtotime( sprintf( '%s -1 month', date( 'c', time() ) ) );
		$past = date( _x( 'Y F', 'date admin dashbord widget', 'kpir' ), $date );
		$widgets = array(
			'current_month' => sprintf( __( 'Current month: %s', 'kpir' ), $current ),
			'past_month' => sprintf( __( 'Past month: %s', 'kpir' ), $past ),
		);
		foreach ( $widgets as $widget_id => $widget_name ) {
			$callback = array( $this, sprintf( 'dashboard_widget_%s', $widget_id ) );
			wp_add_dashboard_widget( $widget_id, $widget_name, $callback );
		}
	}

	public function admin_init() {
		add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widgets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
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

		$file = sprintf( '/assets/styles/kpir-admin%s.css', $this->dev );
		$version = $this->get_version( $file );
		$file = plugins_url( $file, $this->base );
		wp_register_style( 'admin-kpir', $file, array( 'jquery-ui-datepicker', 'select2' ), $version );
		wp_enqueue_style( 'admin-kpir' );
	}

	public function admin_enqueue_scripts() {
		/**
		 * select2
		 */
		wp_register_script( 'select2', plugins_url( 'assets/externals/select2/js/select2.full.min.js', $this->base ), array(), '4.0.3' );

		/**
		 * Admin scripts
		 */
		$files = array(
			'kpir-admin-js' => sprintf( 'assets/scripts/admin/kpir%s.js', $this->dev ),
		);
		if ( '' == $this->dev ) {
			$files = array(
				'kpir-admin-js-datepicker' => 'assets/scripts/admin/src/datepicker.js',
				'kpir-admin-js-invoice' => 'assets/scripts/admin/src/invoice.js',
				'kpir-admin-js-select2' => 'assets/scripts/admin/src/select2.js',
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
		 * Admin styles
		 */
		$deps = array( 'jquery-ui-dialog' );
		$file = 'assets/styles/kpir-admin'.$this->dev.'.css';
		wp_enqueue_style( 'kpir-admin', plugins_url( $file, $this->base ), array(), $this->get_version( $file ) );
	}

	public function init() {
		if ( is_admin() ) {
		} else {
			$file = 'assets/styles/kpir'.$this->dev.'.css';
			wp_enqueue_style( 'kpir', plugins_url( $file, $this->base ), array(), $this->get_version( $file ) );
		}
	}

	public function show_page_reports( $report = 'monthly' ) {
		echo '<div class="wrap">';
		switch ( $report ) {
			case 'monthly':
				include_once $this->base .'/iworks/kpir/reports/montly.php';
				printf( '<h1 class="wp-heading-inline">%s</h1>', __( 'Montly report', 'kpir' ) );
				$report = new iworks_kpir_reports_montly();
				$report->show( $this->post_type_invoice );
			break;
			default:
				printf( '<h1 class="wp-heading-inline">%s</h1>', __( 'Reaports', 'kpir' ) );
			break;
		}

		echo '</div>';
	}
}
