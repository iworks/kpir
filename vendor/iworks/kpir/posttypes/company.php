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

if ( class_exists( 'iworks_kpir_posttypes_company' ) ) {
	return;
}

class iworks_kpir_posttypes_company {

	private $post_type_name = 'iworks_kpir_company';

	public function __construct() {
		add_action( 'init', array( $this, 'register' ), 0 );
	}

	public function get_name() {
		return $this->post_type_name;
	}

	public function register() {

		$labels = array(
			'name'                  => _x( 'Post Types', 'Post Type General Name', 'kpir' ),
			'singular_name'         => _x( 'Post Type', 'Post Type Singular Name', 'kpir' ),
			'menu_name'             => __( 'Post Types', 'kpir' ),
			'name_admin_bar'        => __( 'Post Type', 'kpir' ),
			'archives'              => __( 'Item Archives', 'kpir' ),
			'attributes'            => __( 'Item Attributes', 'kpir' ),
			'parent_item_colon'     => __( 'Parent Item:', 'kpir' ),
			'all_items'             => __( 'All Items', 'kpir' ),
			'add_new_item'          => __( 'Add New Item', 'kpir' ),
			'add_new'               => __( 'Add New', 'kpir' ),
			'new_item'              => __( 'New Item', 'kpir' ),
			'edit_item'             => __( 'Edit Item', 'kpir' ),
			'update_item'           => __( 'Update Item', 'kpir' ),
			'view_item'             => __( 'View Item', 'kpir' ),
			'view_items'            => __( 'View Items', 'kpir' ),
			'search_items'          => __( 'Search Item', 'kpir' ),
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
			'label'                 => __( 'Post Type', 'kpir' ),
			'description'           => __( 'Post Type Description', 'kpir' ),
			'labels'                => $labels,
			'supports'              => array(),
			'taxonomies'            => array( 'category', 'post_tag' ),
			'hierarchical'          => false,
			'public'                => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'menu_position'         => 5,
			'show_in_admin_bar'     => true,
			'show_in_nav_menus'     => true,
			'can_export'            => true,
			'has_archive'           => true,
			'exclude_from_search'   => false,
			'publicly_queryable'    => true,
			'capability_type'       => 'page',
		);
		register_post_type( $this->post_type_name, $args );

	}
}

