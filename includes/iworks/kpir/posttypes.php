<?php
/*
Copyright 2017-PLUGIN_TILL_YEAR Marcin Pietrzak (marcin@iworks.pl)

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

/**
 * iworks_kpir_posttypes class.
 * Handles post type registration, meta box rendering, and metadata saving.
 */
class iworks_kpir_posttypes {
	/**
	 * The post type name.
	 *
	 * @var string
	 */
	protected $post_type_name;
	/**
	 * Plugin options.
	 *
	 * @var iworks_kpir_options
	 */
	protected $options;
	/**
	 * Array of field definitions.
	 *
	 * @var array
	 */
	protected $fields;
	/**
	 * Array of post type objects.
	 *
	 * @var array
	 */
	protected $post_type_objects = array();
	/**
	 * Whether to use cash PIT.
	 *
	 * @var bool
	 */
	protected bool $use_cash_pit = false;

	/**
	 * Class constructor.
	 * Initializes the post type and sets up necessary hooks.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
		add_action( 'init', array( $this, 'action_init_set_options' ), 7020 );
		/**
		 * save post
		 */
		add_action( 'save_post', array( $this, 'save_post_meta' ), 10, 3 );
	}

	/**
	 * Initialize and set plugin options.
	 * Hooked to 'init' action with priority 7020.
	 */
	public function action_init_set_options() {
		$this->options      = iworks_kpir_get_options();
		$this->use_cash_pit = boolval( $this->options->get_option( 'cash_pit' ) );
	}

	/**
	 * Get the post type name.
	 *
	 * @return string The post type name.
	 */
	public function get_name() {
		return $this->post_type_name;
	}

	/**
	 * Generate HTML content for meta box fields.
	 *
	 * @param WP_Post $post The post object.
	 * @param array $fields Array of field definitions.
	 * @param string $group The field group name.
	 * @return string Generated HTML content.
	 */
	protected function get_meta_box_content( $post, $fields, $group ) {
		$content  = '';
		$basename = $this->options->get_option_name( $group );
		foreach ( $fields[ $group ] as $key => $data ) {
			$args = isset( $data['args'] ) ? $data['args'] : array();
			/**
			 * ID
			 */
			$args['id'] = $this->options->get_option_name( $group . '_' . $key );
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
			$content .= '<div class="iworks-kpir-row-value">';
			$content .= $this->options->get_field_by_type( $type, $name, $value, $args );
			if ( isset( $data['description'] ) ) {
				$content .= sprintf( '<p class="description">%s</p>', $data['description'] );
			}
			$content .= '</div>';
			$content .= '</div>';
		}
		echo $content;
	}

	/**
	 * Save post metadata when a post is saved.
	 *
	 * @param int $post_id The post ID.
	 * @param WP_Post $post The post object.
	 * @param bool $update Whether this is an existing post being updated.
	 * @param array $fields Array of field definitions to process.
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
					$value = isset( $_POST[ $post_key ][ $key ] ) ? $_POST[ $post_key ][ $key ] : null;
					if ( is_string( $value ) ) {
						$value = trim( $value );
					} elseif ( is_array( $value ) ) {
						if (
							isset( $value['integer'] ) && 0 == $value['integer']
							&& isset( $value['fractional'] ) && 0 == $value['fractional']
						) {
							$value = null;
						}
					}
					$option_name = $this->options->get_option_name( $group . '_' . $key );
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

	/**
	 * Check if the given post ID belongs to the current post type.
	 *
	 * @param int $post_ID The post ID to check.
	 * @return bool Whether the post ID belongs to the current post type.
	 */
	public function check_post_type_by_id( $post_ID ) {
		$post = get_post( $post_ID );
		if ( empty( $post ) ) {
			return false;
		}
		if ( $this->post_type_name == $post->post_type ) {
			return true;
		}
		return false;
	}
}
