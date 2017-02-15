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
	protected $post_type_name;
	protected $options;
	protected $fields;
	protected $post_type_objects = array();

	public function __construct() {
		global $iworks_kpir_options;
		$this->options = $iworks_kpir_options;
		add_action( 'init', array( $this, 'register' ) );
		/**
		 * save post
		 */
		add_action( 'save_post', array( $this, 'save_post_meta' ), 10, 3 );
	}

	public function get_name() {
		return $this->post_type_name;
	}

	protected function get_meta_box_content( $post, $fields, $group ) {

		$content = '';
		$basename = $this->options->get_option_name( $group );
		foreach ( $fields[ $group ] as $key => $data ) {
			$args = isset( $data['args'] )? $data['args']:array();
			/**
			 * ID
			 */
			$args['id'] = $this->options->get_option_name( $group.'_'.$key );
			/**
			 * name
			 */
			$name = sprintf( '%s[%s]', $basename, $key );
			/**
			 * sanitize type
			 */
			$type = isset( $data['type'] ) ? $data['type'] : 'text';
			/**
			 * get value
			 */
			$value = get_post_meta( $post->ID, $args['id'], true );
			/**
			 * Handle select2
			 */
			if ( ! empty( $value ) && 'select2' == $type ) {
				$value = array(
					'value' => $value,
					'label' => get_the_title( $value ),
				);
			}
			/**
			 * Handle date
			 */
			if ( ! empty( $value ) && 'date' == $type ) {
				$value = date_i18n( 'Y-m-d', $value );
			}
			/**
			 * build
			 */
			$content .= sprintf( '<div class="iworks-kpir-row iworks-kpir-row-%s">', esc_attr( $key ) );
			if ( isset( $data['label'] ) && ! empty( $data['label'] ) ) {
				$content .= sprintf( '<label for=%s">%s</label>', esc_attr( $args['id'] ), esc_html( $data['label'] ) );
			}
			$content .= $this->options->get_field_by_type( $type, $name, $value, $args );
			if ( isset( $data['description'] ) ) {
				$content .= sprintf( '<p class="description">%s</p>', $data['description'] );
			}
			$content .= '</div>';
		}
		echo $content;
	}

	/**
	 * Save post metadata when a post is saved.
	 *
	 * @param int $post_id The post ID.
	 * @param post $post The post object.
	 * @param bool $update Whether this is an existing post being updated or not.
	 */
	public function save_post_meta_fields( $post_id, $post, $update, $fields ) {

		/*
         * In production code, $slug should be set only once in the plugin,
         * preferably as a class property, rather than in each function that needs it.
         */
		$post_type = get_post_type( $post_id );

		// If this isn't a Copyricorrect post, don't update it.
		if ( $this->post_type_name != $post_type ) {
			return;
		}

		foreach ( $fields as $group => $group_data ) {
			$post_key = $this->options->get_option_name( $group );
			if ( isset( $_POST[ $post_key ] ) ) {
				foreach ( $group_data as $key => $data ) {
					$value = isset( $_POST[ $post_key ][ $key ] )? $_POST[ $post_key ][ $key ]:null;
					if ( is_string( $value ) ) {
						$value = trim( $value );
					} else if ( is_array( $value ) ) {
						if (
							isset( $value['integer'] ) && 0 == $value['integer']
							&& isset( $value['fractional'] ) && 0 == $value['fractional']
						) {
							$value = null;
						}
					}
					$option_name = $this->options->get_option_name( $group.'_'.$key );
					if ( empty( $value ) ) {
						delete_post_meta( $post->ID, $option_name );
					} else {
						if ( isset( $data['type'] ) && 'date' == $data['type'] ) {
							$value = strtotime( $value );
						}
						$result = add_post_meta( $post->ID, $option_name, $value, true );
						if ( ! $result ) {
							update_post_meta( $post->ID, $option_name, $value );
						}
						do_action( 'iworks_kpir_posttype_update_post_meta', $post->ID, $option_name, $value, $key, $data );
					}
				}
			}
		}
	}
}


