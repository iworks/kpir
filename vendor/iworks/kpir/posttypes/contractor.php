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

if ( class_exists( 'iworks_kpir_posttypes_contractor' ) ) {
	return;
}

require_once( dirname( dirname( __FILE__ ) ) . '/posttypes.php' );

class iworks_kpir_posttypes_contractor extends iworks_kpir_posttypes {

	protected $post_type_name = 'iworks_kpir_contract'; // iworks_kpir_contractor (varchar(20))

	public function __construct() {
		parent::__construct();
		$this->fields = array(
			'contractor_data' => array(
				'full_name' => array(
					'label' => __( 'Full Name:', 'kpir' ),
				),
				'street1' => array(
					'label' => __( 'Street Address 1:', 'kpir' ),
				),
				'street2' => array(
					'label' => __( 'Street Address 2:', 'kpir' ),
				),
				'zip' => array(
					'label' => __( 'ZIP Code:', 'kpir' ),
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
				'bank' => array(
					'label' => __( 'Bank', 'kpir' ),
				),
				'bank_account' => array(
					'label' => __( 'Bank account', 'kpir' ),
				),
            ),
            'contact' => array(
                'website' => array('label' => __('Website', 'kpir' )),
                'email' => array('label' => __('email', 'kpir' )),
                'mobile' => array('label' => __('mobile', 'kpir' )),
                'phone' => array('label' => __('phone', 'kpir' )),
                'website' => array('label' => __('Website', 'kpir' )),
                'website' => array('label' => __('Website', 'kpir' )),
            ),
		);
		$this->post_type_objects[ $this->get_name() ] = $this;

		add_action( 'wp_ajax_iworks_get_contractors', array( $this, 'get_contractors_json' ) );

	}

	public function register() {

		$labels = array(
			'name'                  => _x( 'Contractors', 'Contractor General Name', 'kpir' ),
			'singular_name'         => _x( 'Contractor', 'Contractor Singular Name', 'kpir' ),
			'menu_name'             => __( 'Contractors', 'kpir' ),
			'name_admin_bar'        => __( 'Contractor', 'kpir' ),
			'archives'              => __( 'Item Archives', 'kpir' ),
			'attributes'            => __( 'Item Attributes', 'kpir' ),
			'parent_item_colon'     => __( 'Parent Contractor:', 'kpir' ),
			'all_items'             => __( 'Contractors', 'kpir' ),
			'add_new_item'          => __( 'Add New Contractor', 'kpir' ),
			'add_new'               => __( 'Add New', 'kpir' ),
			'new_item'              => __( 'New Contractor', 'kpir' ),
			'edit_item'             => __( 'Edit Contractor', 'kpir' ),
			'update_item'           => __( 'Update Contractor', 'kpir' ),
			'view_item'             => __( 'View Contractor', 'kpir' ),
			'view_items'            => __( 'View Contractors', 'kpir' ),
			'search_items'          => __( 'Search Contractor', 'kpir' ),
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
			'label'                 => __( 'Contractor', 'kpir' ),
			'description'           => __( 'Contractor Description', 'kpir' ),
			'labels'                => $labels,
			'supports'              => array( 'title', 'thumbnail' ),
			'taxonomies'            => array(),
			'hierarchical'          => false,
			'public'                => false,
			'show_ui'               => true,
			'show_in_menu'          => add_query_arg( array( 'post_type' => 'iworks_kpir_invoice' ), 'edit.php' ),
			'show_in_admin_bar'     => false,
			'show_in_nav_menus'     => false,
			'can_export'            => true,
			'has_archive'           => true,
			'exclude_from_search'   => true,
			'publicly_queryable'    => false,
			'capability_type'       => 'page',
			'register_meta_box_cb'  => array( $this, 'register_meta_boxes' ),
		);
		register_post_type( $this->post_type_name, $args );

	}

	public function register_meta_boxes( $post ) {
		add_meta_box( 'contractor-data', __( 'Contractor Data', 'kpir' ), array( $this, 'contractor_data' ), $this->post_type_name );
	}

	public function contractor_data( $post ) {
		$this->get_meta_box_content( $post, $this->fields, __FUNCTION__ );
	}

	public function save_post_meta( $post_id, $post, $update ) {
		$this->save_post_meta_fields( $post_id, $post, $update, $this->fields );
	}

	public function get_contractors_json() {
		$data = array(
			'total_count' => 0,
			'incomplete_results' => false,
			'items' => array(),
		);
		$args = array(
			'post_type' => $this->get_name(),
			'nopaging' => true,
		);
		if ( isset( $_REQUEST['q'] ) ) {
			$args['s'] = $_REQUEST['q'];
		}
		$the_query = new WP_Query( $args );
		if ( $the_query->have_posts() ) {
			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				$one = array(
					'id' => get_the_ID(),
					'full_name' => get_the_title(),
					'nip' => get_post_meta( get_the_ID(), $this->options->get_option_name( 'nip' ), true ),
				);
				$data['items'][] = $one;
			}
			wp_reset_postdata();
		}
		echo wp_json_encode( $data );
		die;
	}
}

