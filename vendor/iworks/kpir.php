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

		$this->capability        = apply_filters( 'iworks_kpir_capability', 'manage_options' );

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
		add_action( 'admin_init',                 array( $this, 'admin_init' ) );
	}

	public function admin_init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
	}

	public function admin_enqueue_scripts() {
		/**
		 * Admin scripts
		 */
		$deps = array(
			'jquery-ui-datepicker',
		);
		$files = array(
			'kpir-admin-js' => sprintf( 'assets/scripts/admin/kpir%s.js', $this->dev ),
		);
		if ( '' == $this->dev ) {
			$files = array(
				'kpir-admin-js-common' => 'assets/scripts/admin/src/common.js',
			);
		}
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
}
