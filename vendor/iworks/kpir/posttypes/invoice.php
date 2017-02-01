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

	public function __construct() {
		parent::__construct();
		add_filter( 'enter_title_here', array( $this, 'enter_title_here' ), 10, 2 );

		$this->fields = array(
			'type' => array(
				'type' => 'radio',
				'options' => array(
					'income' => __( 'Income', 'kpir' ),
					'expense' => __( 'Expense', 'kpir' ),
				),
				'label' => __( 'Type', 'kpir' ),
			),
			'street1' => array(
				'label' => __( 'Street', 'kpir' ),
			),
			'street2' => array(
				'label' => __( 'Street', 'kpir' ),
			),
			'zip' => array(
				'label' => __( 'ZIP Code', 'kpir' ),
			),
			'city' => array(
				'label' => __( 'City', 'kpir' ),
			),
			'country' => array(
				'label' => __( 'Country', 'kpir' ),
			),
			'nip' => array(
				'label' => __( 'NIP', 'kpir' ),
			),
		);
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
}

