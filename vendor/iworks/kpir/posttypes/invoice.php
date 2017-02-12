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

require_once( dirname( dirname( __FILE__ ) ) . '/posttypes.php' );

class iworks_kpir_posttypes_invoice extends iworks_kpir_posttypes {

	protected $post_type_name = 'iworks_kpir_invoice';
	private $custom_field_year_month = 'year_month';
	private $custom_field_year = 'year';

	public function __construct() {
		parent::__construct();
		add_filter( 'enter_title_here', array( $this, 'enter_title_here' ), 10, 2 );
		/**
		 * fields
		 */
		$this->fields = array(
			'basic' => array(
				'date' => array(
					'type' => 'date',
					'label' => __( 'Event date', 'kpir' ),
					'args' => array(
						'class' => array( 'medium-text' ),
						'default' => date_i18n( 'Y-m-d', time() ),
					),
				),
				'contractor' => array(
					'type' => 'select2',
					'label' => __( 'Contractor', 'kpir' ),
					'args' => array(
						'data-source' => 'contractor',
						'data-nonce-action' => 'get-contractors-list',
					),
				),
				'description' => array(
					'label' => __( 'Invoice description', 'kpir' ),
				),
				'type' => array(
					'type' => 'radio',
					'args' => array(
						'options' => array(
							'income' => array(
								'label' => __( 'Income', 'kpir' ),
							),
							'expense' => array(
								'label' => __( 'Expense', 'kpir' ),
							),
						),
					),
					'label' => __( 'Type', 'kpir' ),
				),
			),
			'income' => array(
				'description' => array(
					'type' => 'description',
					'args' => array(
						'value' => __( 'Please first choose invoice type.', 'kpir' ),
						'class' => 'description',
					),
				),
				'sale' => array(
					'type' => 'money',
					'label' => __( 'Value of goods and services sold', 'kpir' ),
				),
				'other' => array(
					'type' => 'money',
					'label' => __( 'Other income', 'kpir' ),
				),
				'vat' => array(
					'type' => 'money',
					'label' => __( 'VAT', 'kpir' ),
				),
			),
			'expense' => array(
				'description' => array(
					'type' => 'description',
					'args' => array(
						'value' => __( 'Please first choose invoice type.', 'kpir' ),
						'class' => 'description',
					),
				),
				'purchase' => array(
					'type' => 'money',
					'label' => __( 'The purchase of commercial goods and materials, according to the purchase price', 'kpir' ),
				),
				'cost_of_purchase' => array(
					'type' => 'money',
					'label' => __( 'Incidental costs of purchase', 'kpir' ),
				),
				'salary' => array(
					'type' => 'money',
					'label' => __( 'Salary in cash and in kind', 'kpir' ),
				),
				'other' => array(
					'type' => 'money',
					'label' => __( 'Other expenses', 'kpir' ),
				),
				'vat' => array(
					'type' => 'money',
					'label' => __( 'VAT', 'kpir' ),
				),
				'car' => array(
					'type' => 'checkbox',
					'label' => __( 'Spend associated with the car.', 'kpir' ),
					'description' => __( 'It will be calculated as half VAT return.', 'kpir' ),
				),
			),
		);

		$this->post_type_objects[ $this->get_name() ] = $this;
		add_action( 'iworks_kpir_posttype_update_post_meta', array( $this, 'save_year_month_to_extra_field' ), 10, 5 );

		/**
		 * Meta Boxes to close by default
		 */
		$meta_boxes_to_close = array( 'income', 'expense' );
		foreach ( $meta_boxes_to_close as $meta_box ) {
			$filter = sprintf( 'postbox_classes_%s_%s', $this->get_name(), $meta_box );
			add_filter( $filter, array( $this, 'close_meta_boxes' ) );
		}

	}

	public function register() {

		$labels = array(
			'name'                  => _x( 'Invoices', 'Invoice General Name', 'kpir' ),
			'singular_name'         => _x( 'Invoice', 'Invoice Singular Name', 'kpir' ),
			'menu_name'             => __( 'KPiR', 'kpir' ),
			'name_admin_bar'        => __( 'Invoice', 'kpir' ),
			'archives'              => __( 'Item Archives', 'kpir' ),
			'attributes'            => __( 'Item Attributes', 'kpir' ),
			'parent_item_colon'     => __( 'Parent Invoice:', 'kpir' ),
			'all_items'             => __( 'All Invoices', 'kpir' ),
			'add_new_item'          => __( 'Add New Invoice', 'kpir' ),
			'add_new'               => __( 'Add New', 'kpir' ),
			'new_item'              => __( 'New Invoice', 'kpir' ),
			'edit_item'             => __( 'Edit Invoice', 'kpir' ),
			'update_item'           => __( 'Update Invoice', 'kpir' ),
			'view_item'             => __( 'View Invoice', 'kpir' ),
			'view_items'            => __( 'View Invoices', 'kpir' ),
			'search_items'          => __( 'Search Invoice', 'kpir' ),
			'not_found'             => __( 'Not found', 'kpir' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'kpir' ),
			'featured_image'        => __( 'Featured Image', 'kpir' ),
			'set_featured_image'    => __( 'Set featured image', 'kpir' ),
			'remove_featured_image' => __( 'Remove featured image', 'kpir' ),
			'use_featured_image'    => __( 'Use as featured image', 'kpir' ),
			'insert_into_item'      => __( 'Insert into item', 'kpir' ),
			'uploaded_to_this_item' => __( 'Uploaded to this item', 'kpir' ),
			'items_list'            => __( 'Items list', 'kpir' ),
			'items_list_navigation' => __( 'Items list navigation', 'kpir' ),
			'filter_items_list'     => __( 'Filter items list', 'kpir' ),
		);
		$args = array(
			'label'                 => __( 'Invoice', 'kpir' ),
			'description'           => __( 'Invoice Description', 'kpir' ),
			'labels'                => $labels,
			'supports'              => array( 'title' ),
			'taxonomies'            => array(),
			'hierarchical'          => false,
			'public'                => false,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'show_in_admin_bar'     => true,
			'show_in_nav_menus'     => false,
			'can_export'            => true,
			'has_archive'           => true,
			'exclude_from_search'   => true,
			'publicly_queryable'    => false,
			'capability_type'       => 'page',
			'menu_icon'             => 'dashicons-book',
			'register_meta_box_cb'  => array( $this, 'register_meta_boxes' ),
		);
		register_post_type( $this->post_type_name, $args );
	}

	public function save_post_meta( $post_id, $post, $update ) {
		$this->save_post_meta_fields( $post_id, $post, $update, $this->fields );
	}

	/**
	 * Change "Enter title here" to "Enter invoice number"
	 *
	 * @since 1.0
	 */
	public function enter_title_here( $title, $post ) {
		if ( $post->post_type == $this->post_type_name ) {
			return __( 'Enter invoice number', 'kpir' );
		}
		return $title;
	}

	public function register_meta_boxes( $post ) {
		add_meta_box( 'basic', __( 'Basic Data', 'kpir' ), array( $this, 'basic' ), $this->post_type_name );
		add_meta_box( 'income', __( 'Incomes', 'kpir' ), array( $this, 'income' ), $this->post_type_name );
		add_meta_box( 'expense', __( 'Expenses (costs)', 'kpir' ), array( $this, 'expense' ), $this->post_type_name );
	}

	public function basic( $post ) {
		$this->get_meta_box_content( $post, $this->fields, __FUNCTION__ );
	}

	public function income( $post ) {
		$this->get_meta_box_content( $post, $this->fields, __FUNCTION__ );
	}

	public function expense( $post ) {
		$this->get_meta_box_content( $post, $this->fields, __FUNCTION__ );
	}

	public function save_year_month_to_extra_field( $post_id, $option_name, $value, $key, $data ) {
		if ( 'date' == $key ) {
			$name = $this->options->get_option_name( $this->custom_field_year_month );
			$value = date( 'Y-m', $value );
			$result = add_post_meta( $post_id, $name, $value, true );
			if ( ! $result ) {
				update_post_meta( $post_id, $name, $value );
			}
		}
	}

	public function close_meta_boxes( $classes ) {
		$classes[] = 'closed';
		return $classes;
	}

	public function month_table( $month ) {
		$args = array(
			'post_type' => $this->get_name(),
			'meta_value' => $month,
			'meta_key' => $this->options->get_option_name( $this->custom_field_year_month ),
			'nopaging' => true,
			'fields' => 'ids',
			'post_status' => array( 'published' ),
		);
		$the_query = new WP_Query( $args );

		$data = array(
			'income' => 0,
			'expense' => 0,
			'expense_vat' => 0,
			'vat_income' => 0,
			'vat_expense' => 0,
		);
		foreach ( $the_query->posts as $post_id ) {
			$data['income'] += $this->add_value( $post_id, 'income_sale' );
			$data['income'] += $this->add_value( $post_id, 'income_other' );
			$data['vat_income'] += $this->add_value( $post_id, 'income_vat' );
		}

		$labels = array(
			'income' => __( 'Income', 'kpir' ),
			'expense' => __( 'Expense', 'kpir' ),
			'expense_vat' => __( 'Expense (VAT)', 'kpir' ),
			'vat_income' => __( 'VAT (Income) ', 'kpir' ),
			'vat_expense' => __( 'VAT (Expense)', 'kpir' ),
		);
		echo '<table class="striped">';
		echo '<tbody>';
		foreach ( $labels as $key => $label ) {
			echo '<tr>';
			printf( '<td>%s</td>', $label );
			printf( '<td class="textright">%0.2f</td>', $data[ $key ] / 100 );
			echo '</tr>';
		}
		echo '</tbody>';
		echo '</table>';
	}

	private function add_value( $post_id, $meta_name ) {
		$value = 0;
		$v = get_post_meta( $post_id, $this->options->get_option_name( $meta_name ), true );
		if ( is_array( $v ) ) {
			if ( isset( $v['integer'] ) ) {
				$value += 100 * $v['integer'];
			}
			if ( isset( $v['fractional'] ) ) {
				$value += $v['fractional'];
			}
		}
		return $value;
	}
}

