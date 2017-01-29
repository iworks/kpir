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

class iworks_kpir
{
	private $base;
	private $capability;
	private $post_type_company;
	private $post_type_invoice;

	public function __construct() {

		$this->base              = dirname( dirname( __FILE__ ) );
		$this->capability        = apply_filters( 'iworks_upprev_capability', 'manage_options' );

		/**
		 * post_types
		 */
		$post_types = array( 'company', 'invoice' );
		foreach ( $post_types as $post_type ) {
			include_once( $this->base.'/iworks/kpir/posttypes/'.$post_type.'.php' );
			$class = sprintf( 'iworks_kpir_posttypes_%s', $post_type );
			$value = sprintf( 'post_type_%s', $post_type );
			$this->$value = new $class();
		}
	}
}
