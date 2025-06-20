<?php
/**
 * Plugin Name: KPIR - Invoices Post Type
 * Plugin URI: https://iworks.pl/
 * Description: Handles the custom post type for Invoices in KPIR (Księga Przychodów i Rozchodów) plugin.
 * Version: 1.0.0
 * Author: Marcin Pietrzak
 * Author URI: https://iworks.pl/
 * License: GPLv3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package KPIR
 * @category PostTypes
 * @author Marcin Pietrzak <marcin@iworks.pl>
 */

/*
Copyright 2017-PLUGIN_TILL_YEAR Marcin Pietrzak (marcin@iworks.pl)

this program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 3, as
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

require_once dirname( __DIR__, 1 ) . '/posttypes.php';

/**
 * Handles the Invoices custom post type for KPIR plugin.
 *
 * This class manages the registration and functionality of the Invoices post type,
 * including custom meta boxes, columns, and sorting for invoice data.
 *
 * @package KPIR
 * @subpackage PostTypes
 * @since 1.0.0
 */
class iworks_kpir_posttypes_invoice extends iworks_kpir_posttypes {

	/**
	 * Post type name.
	 *
	 * @var string
	 */
	protected $post_type_name = 'iworks_kpir_invoice';

	/**
	 * Custom field name for storing the year.
	 *
	 * @var string
	 */
	private $custom_field_year = 'year';

	/**
	 * Contractor post type object.
	 *
	 * @var object|null
	 */
	private $contractor_post_type_object = null;

	/**
	 * Class constructor.
	 *
	 * Sets up the invoice post type and related hooks including:
	 * - Title placeholder text
	 * - Custom fields initialization
	 * - Meta boxes configuration
	 * - Admin columns and sorting
	 * - Default sort order
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();
		add_filter( 'enter_title_here', array( $this, 'enter_title_here' ), 10, 2 );
		add_action( 'init', array( $this, 'action_init_set_fields' ), 9823 );
		add_action( 'init', array( $this, 'action_init_set_filters' ), 9824 );
		/**
		 * save extra field
		 */
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
		/**
		 * change default columns
		 */
		add_filter( "manage_{$this->get_name()}_posts_columns", array( $this, 'add_columns' ) );
		add_filter( "manage_edit-{$this->get_name()}_sortable_columns", array( $this, 'filter_add_sortable_columns' ) );
		add_action( 'manage_posts_custom_column', array( $this, 'custom_columns' ), 10, 2 );
		/**
		 * apply default sort order
		 */
		add_action( 'pre_get_posts', array( $this, 'apply_default_sort_order' ) );
		add_action( 'pre_get_posts', array( $this, 'apply_filter_order_date_of_payment' ) );
	}

	public function filter_add_sortable_columns( $columns ) {
		$columns['date_of_payment'] = $this->get_custom_field_date_of_cash_name();
		return $columns;
	}

	/**
	 * set filters
	 *
	 * @since 1.1.0
	 */
	public function action_init_set_filters() {
		/**
		 * add class to metaboxes
		 */
		foreach ( array_keys( $this->fields ) as $name ) {
			if ( 'basic' == $name ) {
				continue;
			}
			$key = sprintf( 'postbox_classes_%s_%s', $this->get_name(), $name );
			add_filter( $key, array( $this, 'add_defult_class_to_postbox' ) );
		}
	}

	/**
	 * set fields
	 */
	public function action_init_set_fields() {
		/**
		 * fields
	 *
	 * @since 1.1.0
		 */
		$this->fields = array(
			'basic'     => array(
				'date_of_issue' => array(
					'type'  => 'date',
					'label' => __( 'Date of issue', 'kpir' ),
					'args'  => array(
						'class'   => array( 'medium-text' ),
						'default' => date_i18n( 'Y-m-d', time() ),
						'after'   => sprintf( ' <a class="button" id="kpir-copy-date-button">%s</a>', esc_html__( 'Copy to event date', 'kpir' ) ),
					),
				),
				'date'          => array(
					'type'  => 'date',
					'label' => __( 'Event date', 'kpir' ),
					'args'  => array(
						'class'   => array( 'medium-text' ),
						'default' => date_i18n( 'Y-m-d', time() ),
					),
				),
				'date_of_cash'  => array(
					'type'  => 'date',
					'label' => __( 'Cash-in Date', 'kpir' ),
					'args'  => array(
						'class' => array( 'medium-text' ),
					),
				),
				'contractor'    => array(
					'type'  => 'select2',
					'label' => __( 'Contractor', 'kpir' ),
					'args'  => array(
						'data-source'       => 'contractor',
						'data-nonce-action' => 'get-contractors-list',
					),
				),
				'description'   => array(
					'label' => __( 'Invoice description', 'kpir' ),
				),
				'type'          => array(
					'type'  => 'radio',
					'args'  => array(
						'options' => array(
							'income'    => array(
								'label' => __( 'Income', 'kpir' ),
							),
							'expense'   => array(
								'label' => __( 'Expense', 'kpir' ),
							),
							'salary'    => array(
								'label' => __( 'Salary', 'kpir' ),
							),
							'asset'     => array(
								'label' => __( 'Asset', 'kpir' ),
							),
							'insurance' => array(
								'label' => __( 'Insurance', 'kpir' ),
							),
						),
						'default' => 'expense',
					),
					'label' => __( 'Type', 'kpir' ),
				),
			),
			'income'    => array(
				'description' => array(
					'type' => 'description',
					'args' => array(
						'value' => __( 'Please first choose invoice type.', 'kpir' ),
						'class' => array( 'description' ),
					),
				),
				'sale'        => array(
					'type'  => 'money',
					'label' => __( 'Value of goods and services sold', 'kpir' ),
				),
				'other'       => array(
					'type'  => 'money',
					'label' => __( 'Other income', 'kpir' ),
				),
				'vat'         => array(
					'type'  => 'money',
					'label' => __( 'VAT', 'kpir' ),
				),
				'vat_type'    => array(
					'type' => 'radio',
					'args' => array(
						'options' => array(
							'c00' => array(
								'label' => __( 'Wysokość podstawy opodatkowania wynikająca z dostawy towarów oraz świadczenia usług na terytorium kraju, zwolnionych od podatku', 'kpir' ),
							),
							'c01' => array(
								'label' => __( 'Wysokość podstawy opodatkowania wynikająca z dostawy towarów oraz świadczenia usług poza terytorium kraju', 'kpir' ),
							),
							'c06' => array(
								'label' => __( 'Wysokość podstawy opodatkowania wynikająca z dostawy towarów oraz świadczenia usług na terytorium kraju, opodatkowanych stawką 22% albo 23%, z uwzględnieniem korekty dokonanej zgodnie z art. 89a ust. 1 i 4 ustawy', 'kpir' ),
							),
						),
						'default' => 'c06',
					),
				),
			),
			'expense'   => array(
				'description'      => array(
					'type' => 'description',
					'args' => array(
						'value' => __( 'Please first choose invoice type.', 'kpir' ),
						'class' => array( 'description' ),
					),
				),
				'purchase'         => array(
					'type'  => 'money',
					'label' => __( 'The purchase of commercial goods and materials, according to the purchase price', 'kpir' ),
				),
				'cost_of_purchase' => array(
					'type'  => 'money',
					'label' => __( 'Incidental costs of purchase', 'kpir' ),
				),
				'other'            => array(
					'type'  => 'money',
					'label' => __( 'Other expenses', 'kpir' ),
				),
				'vat'              => array(
					'type'  => 'money',
					'label' => __( 'VAT', 'kpir' ),
				),
				'vat_rate'         => array(
					'type'  => 'checkbox',
					'label' => __( 'VAT rate', 'kpir' ),
					'type'  => 'radio',
					'args'  => array(
						'options' => array(
							'r23' => array(
								'label' => __( 'Base 23%', 'kpir' ),
							),
							'r08' => array(
								'label' => __( '8%', 'kpir' ),
							),
							'r05' => array(
								'label' => __( '5%', 'kpir' ),
							),
							'r00' => array(
								'label' => __( '0%', 'kpir' ),
							),
							'rzw' => array(
								'label' => __( 'No VAT', 'kpir' ),
							),
						),
						'default' => 'r23',
					),
				),
				'car'              => array(
					'type'        => 'checkbox',
					'label'       => __( 'Car related', 'kpir' ),
					'description' => __( 'It will be calculated as half VAT return.', 'kpir' ),
					'type'        => 'radio',
					'args'        => array(
						'options' => array(
							'100' => array(
								'label' => __( '100%', 'kpir' ),
							),
							'75'  => array(
								'label' => __( '75%', 'kpir' ),
							),
							'20'  => array(
								'label' => __( '20%', 'kpir' ),
							),
							'yes' => array(
								'label' => __( 'Yes (before 2019)', 'kpir' ),
							),
							'no'  => array(
								'label' => __( 'No', 'kpir' ),
							),
						),
						'default' => 'no',
					),
				),
			),
			'salary'    => array(
				'salary' => array(
					'type'  => 'money',
					'label' => __( 'Salary in cash and in kind', 'kpir' ),
				),
			),
			'asset'     => array(
				'depreciation' => array(
					'type'  => 'money',
					'label' => __( 'Depreciation of asset', 'kpir' ),
				),
			),
			'insurance' => array(
				'zus51' => array(
					'type'  => 'money',
					'label' => __( 'ZUS 51', 'kpir' ),
				),
				'zus52' => array(
					'type'  => 'money',
					'label' => __( 'ZUS 52', 'kpir' ),
				),
				'zus53' => array(
					'type'  => 'money',
					'label' => __( 'ZUS 53', 'kpir' ),
				),
			),
		);
		/**
		 * remove date_of_cash if not cash-in personal income tax method
		 *
		 * @since 1.1.0
		 */
		if ( false === $this->use_cash_pit ) {
			unset( $this->fields['basic']['date_of_cash'] );
		}
	}
	/**
	 * Add default class to postbox,
	 */
	public function add_defult_class_to_postbox( $classes ) {
		$classes[] = 'iworks-type';
		return $classes;
	}

	public function register() {
		$labels = array(
			'name'                  => _x( 'Invoices', 'Invoice General Name', 'kpir' ),
			'singular_name'         => _x( 'Invoice', 'Invoice Singular Name', 'kpir' ),
			'menu_name'             => __( 'KPiR', 'kpir' ),
			'name_admin_bar'        => __( 'Invoice', 'kpir' ),
			'archives'              => __( 'Invoice Archives', 'kpir' ),
			'attributes'            => __( 'Invoice Attributes', 'kpir' ),
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
			'insert_into_item'      => __( 'Insert into invoice', 'kpir' ),
			'uploaded_to_this_item' => __( 'Uploaded to this invoice', 'kpir' ),
			'items_list'            => __( 'Invoices list', 'kpir' ),
			'items_list_navigation' => __( 'Invoices list navigation', 'kpir' ),
			'filter_items_list'     => __( 'Filter invoices list', 'kpir' ),
		);
		$args   = array(
			'label'                => __( 'Invoice', 'kpir' ),
			'description'          => __( 'Invoice Description', 'kpir' ),
			'labels'               => $labels,
			'supports'             => array( 'title' ),
			'taxonomies'           => array(),
			'hierarchical'         => false,
			'public'               => false,
			'show_ui'              => true,
			'show_in_menu'         => true,
			'show_in_admin_bar'    => true,
			'show_in_nav_menus'    => false,
			'can_export'           => true,
			'has_archive'          => true,
			'exclude_from_search'  => true,
			'publicly_queryable'   => false,
			'capability_type'      => 'page',
			'menu_icon'            => 'dashicons-book',
			'register_meta_box_cb' => array( $this, 'register_meta_boxes' ),
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
		add_meta_box( 'salary', __( 'Salaries', 'kpir' ), array( $this, 'salary' ), $this->post_type_name );
		add_meta_box( 'asset', __( 'Assets', 'kpir' ), array( $this, 'asset' ), $this->post_type_name );
		add_meta_box( 'insurance', __( 'Insurances (ZUS)', 'kpir' ), array( $this, 'insurance' ), $this->post_type_name );
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

	public function salary( $post ) {
		$this->get_meta_box_content( $post, $this->fields, __FUNCTION__ );
	}

	public function asset( $post ) {
		$this->get_meta_box_content( $post, $this->fields, __FUNCTION__ );
	}

	public function insurance( $post ) {
		$this->get_meta_box_content( $post, $this->fields, __FUNCTION__ );
	}

	public function save_year_month_to_extra_field( $post_id, $option_name, $value, $key, $data ) {
		if ( 'date' == $key ) {
			$name   = $this->get_custom_field_year_month_name();
			$value  = date( 'Y-m', $value );
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
		$args      = array(
			'post_type'   => $this->get_name(),
			'meta_value'  => $month,
			'meta_key'    => $this->get_custom_field_year_month_name(),
			'nopaging'    => true,
			'fields'      => 'ids',
			'post_status' => array( 'published' ),
		);
		$the_query = new WP_Query( $args );
		$data      = array(
			'income'      => 0,
			'expense'     => 0,
			'expense_vat' => 0,
			'vat_income'  => 0,
			'vat_expense' => 0,
			'vat_zero'    => 0,
			'salary'      => 0,
			'asset'       => 0,
		);
		foreach ( $the_query->posts as $post_id ) {
			/**
		 * check is car related cost
		 */
			$is_car_related = get_post_meta( $post_id, $this->options->get_option_name( 'expense_car' ), true );
			// $is_car_related = 'yes' == $is_car_related;
			$data['income']     += $this->add_value( $post_id, 'income_sale' );
			$data['income']     += $this->add_value( $post_id, 'income_other' );
			$data['vat_income'] += $this->add_value( $post_id, 'income_vat' );
			$expense             = 0;
			$expense            += $this->add_value( $post_id, 'expense_purchase' );
			$expense            += $this->add_value( $post_id, 'expense_cost_of_purchase' );
			$expense            += $this->add_value( $post_id, 'expense_other' );
			$data['expense']    += $expense;
			$salary              = 0;
			$salary             += $this->add_value( $post_id, 'salary_salary' );
			$data['salary']     += $salary;
			$data['expense']    += $salary;
			$asset               = 0;
			$asset              += $this->add_value( $post_id, 'asset_depreciation' );
			$data['asset']      += $asset;
			$data['expense']    += $asset;
			$vat_expense         = $this->add_value( $post_id, 'expense_vat' );
			if ( $vat_expense ) {
				if ( 'no' !== $is_car_related ) {
					$vat_expense         /= 2;
					$data['vat_expense'] += $vat_expense;
					$data['expense_vat'] += ( $expense + $vat_expense ) * intval( $is_car_related ) / 100;
					$data['expense']     += $vat_expense * intval( $is_car_related ) / 100;
				} else {
					$data['vat_expense'] += $vat_expense;
					$data['expense_vat'] += $expense;
				}
			} else {
				$data['vat_zero'] += $expense;
			}
		}
		$labels = array(
			'income'      => __( 'Incomes', 'kpir' ),
			'expense'     => __( 'Expenses', 'kpir' ),
			'expense_vat' => __( 'Expenses (VAT)', 'kpir' ),
			'vat_income'  => __( 'VAT (Income) ', 'kpir' ),
			'vat_expense' => __( 'VAT (Expense)', 'kpir' ),
			'vat_zero'    => __( 'VAT (zero)', 'kpir' ),
			'salary'      => __( 'Salaries', 'kpir' ),
			'asset'       => __( 'Depreciation of assets', 'kpir' ),
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
		$v     = get_post_meta( $post_id, $this->options->get_option_name( $meta_name ), true );
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

	/**
	 * Get custom column values.
	 *
	 * @since 1.0.0
	 *
	 * @param string  $column Column name,
	 * @param integer $post_id Current post id (Invoice),
	 */
	public function custom_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'contractor':
				$id = get_post_meta( $post_id, $this->get_custom_field_basic_contractor_name(), true );
				if ( empty( $id ) ) {
					echo '-';
				} else {
					printf(
						'<a href="%s">%s</a>',
						add_query_arg(
							array(
								'contractor' => $id,
								'post_type'  => 'iworks_kpir_invoice',
							),
							admin_url( 'edit.php' )
						),
						get_post_meta( $id, 'iworks_kpir_contractor_data_full_name', true )
					);
				}
				break;
			case 'expense':
				$expense  = 0;
				$expense += $this->add_value( $post_id, 'expense_purchase' );
				$expense += $this->add_value( $post_id, 'expense_cost_of_purchase' );
				$expense += $this->add_value( $post_id, 'expense_other' );
				$expense += $this->add_value( $post_id, 'expense_vat' );
				$expense += $this->add_value( $post_id, 'salary_salary' );
				if ( 0 == $expense ) {
					echo '&nbsp;';
				} else {
					printf( '%0.2f', $expense / 100 );
				}
				break;
			case 'income':
				$income  = 0;
				$income += $this->add_value( $post_id, 'income_sale' );
				$income += $this->add_value( $post_id, 'income_other' );
				$income += $this->add_value( $post_id, 'income_vat' );
				if ( 0 == $income ) {
					echo '&nbsp;';
				} else {
					printf( '%0.2f', $income / 100 );
				}
				break;
			case 'date_of_invoice':
				$timestamp = get_post_meta( $post_id, $this->get_custom_field_basic_date_name(), true );
				if ( empty( $timestamp ) ) {
					echo '-';
				} else {
					echo date( 'Y-m-d', $timestamp );
				}
				break;
			case 'date_of_payment':
				$basic_type = get_post_meta( $post_id, $this->get_custom_field_basic_type_name(), true );
				if ( 'income' == $basic_type ) {
					$timestamp = get_post_meta( $post_id, $this->get_custom_field_date_of_cash_name(), true );
					if ( empty( $timestamp ) ) {
						echo '<small>';
						esc_html_e( '&mdash; not paid yet &mdash;', 'kpir' );
						echo '</small>';
					} else {
						echo date( 'Y-m-d', $timestamp );
					}
				} else {
					echo '&mdash;';
				}
				break;
			case 'description':
				echo get_post_meta( $post_id, $this->options->get_option_name( 'basic_description' ), true );
				break;
			case 'symbol':
				$is_car_related = get_post_meta( $post_id, $this->options->get_option_name( 'expense_car' ), true );
				$is_car_related = 'no' !== $is_car_related;
				if ( $is_car_related ) {
					echo '<span class="dashicons dashicons-admin-generic"></span>';
				} else {
					echo '&nbsp;';
				}
		}
	}

	/**
	 * change default columns
	 *
	 * @since 1.0.0
	 *
	 * @param array $columns list of columns.
	 * @return array $columns list of columns.
	 */
	public function add_columns( $columns ) {
		unset( $columns['date'] );
		$columns['contractor']      = __( 'Contractor', 'kpir' );
		$columns['date_of_invoice'] = __( 'Date of invoice', 'kpir' );
		if ( $this->use_cash_pit ) {
			$columns['date_of_payment'] = __( 'Payment Date', 'kpir' );
		}
		$columns['symbol']      = '<span class="dashicons dashicons-admin-generic"></span>';
		$columns['description'] = __( 'Description', 'kpir' );
		$columns['expense']     = __( 'Expense', 'kpir' );
		$columns['income']      = __( 'Income', 'kpir' );
		$columns['title']       = __( 'Invoice Number', 'kpir' );
		return $columns;
	}

	/**
	 * Add default sorting
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Query $query WP Query object.
	 */
	public function apply_default_sort_order( $query ) {
		/**
		 * do not change if it is already set by request
		 */
		if ( isset( $_REQUEST['orderby'] ) ) {
			return $query;
		}
		/**
		 * do not change outsite th admin area
		 */
		if ( ! is_admin() ) {
			return $query;
		}
		/**
		 * check screen post type
		 */
		if ( ! function_exists( 'get_current_screen' ) ) {
			return $query;
		}
		/**
		 * query post type
		 */
		if ( isset( $query->query['post_type'] ) && $this->get_name() != $query->query['post_type'] ) {
			return $query;
		}
		/**
		 * screen post type
		 */
		$screen = get_current_screen();
		if ( isset( $screen->post_type ) && $this->get_name() == $screen->post_type ) {
			$query->set( 'orderby', 'meta_value_num' );
			if ( isset( $_REQUEST['contractor'] ) && $_REQUEST['contractor'] ) {
				$query->set(
					'meta_query',
					array(
						'relation' => 'AND',
						array(
							'key'     => $this->get_custom_field_basic_date_name(),
							'compare' => 'EXISTS',
						),
						array(
							'key'   => $this->options->get_option_name( 'basic_contractor' ),
							'value' => intval( $_REQUEST['contractor'] ),
						),
					)
				);
			} else {
				$query->set( 'meta_key', $this->get_custom_field_basic_date_name() );
			}
		}
		return $query;
	}

	/**
	 * Get "basic_type" custom filed name.
	 *
	 * @since 1.0.0
	 *
	 * @return string Custom Field meta_key.
	 */
	public function get_custom_field_basic_type_name() {
		return $this->options->get_option_name( 'basic_type' );
	}

	/**
	 * Get "basic_date" custom filed name.
	 *
	 * @since 1.0.0
	 *
	 * @return string Custom Field meta_key.
	 */
	public function get_custom_field_basic_date_name() {
		return $this->options->get_option_name( 'basic_date' );
	}

	/**
	 * Get "date_of_cash" custom filed name.
	 *
	 * @since 1.1.0
	 *
	 * @return string Custom Field meta_key.
	 */
	public function get_custom_field_date_of_cash_name() {
		return $this->options->get_option_name( 'basic_date_of_cash' );
	}

	/**
	 * Get "year_month" custom filed name.
	 *
	 * @since 1.0.0
	 *
	 * @return string Custom Field meta_key.
	 */
	public function get_custom_field_year_month_name() {
		return $this->options->get_option_name( 'year_month' );
	}

	/**
	 * Get "basic_contractor" custom filed name.
	 *
	 * @since 1.0.0
	 *
	 * @return string Custom Field meta_key.
	 */
	public function get_custom_field_basic_contractor_name() {
		return $this->options->get_option_name( 'basic_contractor' );
	}

	public function apply_filter_order_date_of_payment( $query ) {
		if ( ! is_admin() ) {
			return $query;
		}
		if ( ! function_exists( 'get_current_screen' ) ) {
			return $query;
		}
		$key = $this->get_custom_field_date_of_cash_name();
		if ( get_query_var( 'orderby' ) !== $key ) {
			return $query;
		}
		$query->set( 'orderby', $key );
		$query->set(
			'meta_query',
			array(
				'relation' => 'AND',
				$key       => array(
					'key'     => $key,
					'compare' => 'EXISTS',
				),
			)
		);
		return $query;
	}
}
