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

if ( class_exists( 'iworks_kpir_posttypes_invoice' ) ) {
	return;
}

class iworks_kpir_posttypes {
	protected $post_type_name = 'iworks_kpir_invoice';
	protected $options;

	public function __construct() {
		global $iworks_kpir_options;
		$this->options = $iworks_kpir_options;
		add_action( 'init', array( $this, 'register' ) );
	}

	public function get_name() {
		return $this->post_type_name;
	}

	protected function get_meta_box_content( $post, $fields ) {
		$content = '';
		foreach ( $fields as $key => $data ) {
			$id = $this->options->get_option_name( $key );

			$type = isset( $data['type'] ) ? $data['type'] : 'text';
			$args = isset( $data['args'] ) ? $data['args'] : array();

			$value = '';

			$content .= sprintf( '<div class="iworks-kpir-row iworks-kpir-row-%s">', esc_attr( $key ) );
			$content .= sprintf( '<label for=%s">%s</label>', esc_attr( $id ), esc_html( $data['label'] ) );
			$content .= $this->options->get_field_by_type( $type, $key, $value, $args );
			if ( isset( $data['description'] ) ) {
				$content .= sprintf( '<p class="description">%s</p>', $data['description'] );
			}
			$content .= '</div>';
		}
		echo $content;
	}
}


