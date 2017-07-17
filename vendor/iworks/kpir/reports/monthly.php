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

if ( class_exists( 'iworks_kpir_reports_monthly' ) ) {
	return;
}

class iworks_kpir_reports_monthly {

	private $show_fractional_separetly = false;
	private $contractors = array();
	private $debug = false;

	public function __construct() {
		$this->debug = defined( 'WP_DEBUG' ) && WP_DEBUG;
	}

	public function show( $post_type_object ) {

		/**
		 * get current month
		 */
		$current = isset( $_GET['m'] )? $_GET['m']:'';
		if ( ! preg_match( '/^\d{4}\-\d{2}$/', $current ) ) {
			$current = date( 'Y-m' );
		}
		/**
		 * show filter
		 */
		$this->show_filter( $post_type_object, $current );

		$cf_date_name = $post_type_object->get_custom_field_basic_date_name();
		$cf_contractor_name = $post_type_object->get_custom_field_basic_contractor_name();
		$date_format = get_option( 'date_format' );

		$args = array(
			'post_type' => $post_type_object->get_name(),
			'nopaging' => true,
			'suppress_filters' => true,
			'orderby' => $cf_date_name,
			'order' => 'ASC',
			'meta_query' => array(
				array(
					'key' => $post_type_object->get_custom_field_year_month_name(),
					'value' => $current,
				),
				array(
					'key' => $post_type_object->get_custom_field_basic_date_name(),
					'compare' => 'EXISTS',
				),
			),
		);
		$sum = array(
			'income' => array(
				'integer' => 0,
				'fractional' => 0,
			),
			'expense' => array(
				'integer' => 0,
				'fractional' => 0,
			),
		);
		$query = new WP_Query( $args );
		if ( $query->have_posts() ) {
			$i = 1;
			echo '<table class="kpir-report kpir-report-monthly">';
			echo $this->html_table_thead();
			echo '<tbody>';
			while ( $query->have_posts() ) {
				$query->the_post();
				$ID = get_the_ID();
				$contractor_id = get_post_meta( $ID, $cf_contractor_name, true );

				echo '<tr>';
				/**
				 * ID
				 */
				echo $this->html_table_td( $i++, 'id' );
				/**
				 * Date
				 */
				echo $this->html_table_td( date_i18n( $date_format, get_post_meta( $ID, $cf_date_name, true ) ), 'date' );
				/**
				 * Invoice ID
				 */
				$title = sprintf(
					'<a href="%s">%s</a>',
					get_edit_post_link( $ID ),
					get_the_title()
				);
				echo $this->html_table_td( $title, 'title' );
				/**
				 * contractor name
				 */
				$full_name = sprintf(
					'<a href="%s">%s</a>',
					add_query_arg(
						array(
							'contractor' => $contractor_id,
							'post_type' => 'iworks_kpir_invoice',
						),
						admin_url( 'edit.php' )
					),
					get_post_meta( $contractor_id, 'iworks_kpir_contractor_data_full_name', true )
				);
				echo $this->html_table_td( $full_name );
				/**
				 * contractor address
				 */
				echo $this->html_table_td(
					sprintf(
						'<address>%s</address><address>%s %s</address>',
						get_post_meta( $contractor_id, 'iworks_kpir_contractor_data_street1', true ),
						get_post_meta( $contractor_id, 'iworks_kpir_contractor_data_zip', true ),
						get_post_meta( $contractor_id, 'iworks_kpir_contractor_data_city', true )
					)
				);
				/**
				 * Opis zdarzenia gospodarczego
				 */
				echo $this->html_table_td( get_post_meta( $ID, 'iworks_kpir_basic_description', true ) );

				/**
				 * type
				 */
				$type = get_post_meta( $ID, 'iworks_kpir_basic_type', true );
				$value = array( 'integer' => 0, 'fractional' => 0 );

				switch ( $type ) {
					case 'expense':
					case 'asset':
					case 'salary':
						echo $this->html_table_td( '&nbsp;' );
						echo $this->html_table_td( '&nbsp;' );
						echo $this->html_table_td( '&nbsp;' );
						if ( $this->show_fractional_separetly ) {
							echo $this->html_table_td( '&nbsp;' );
							echo $this->html_table_td( '&nbsp;' );
							echo $this->html_table_td( '&nbsp;' );
						}
						switch ( $type ) {
							case 'asset':
								echo $this->html_table_td( '&nbsp;' );
								if ( $this->show_fractional_separetly ) {
									echo $this->html_table_td( '&nbsp;' );
								}
								$value = get_post_meta( $ID, 'iworks_kpir_asset_depreciation', true );
								echo $this->html_helper_money( $value );
							break;
							case 'expense':
								echo $this->html_table_td( '&nbsp;' );
								if ( $this->show_fractional_separetly ) {
									echo $this->html_table_td( '&nbsp;' );
								}
								$value = get_post_meta( $ID, 'iworks_kpir_expense_purchase', true );
								if ( empty( $value ) ) {
									$value = get_post_meta( $ID, 'iworks_kpir_expense_other', true );
								}
								if ( empty( $value ) ) {
									$value = get_post_meta( $ID, 'iworks_kpir_expense_cost_of_purchase', true );
								}
								echo $this->html_helper_money( $value );
							break;
							case 'salary':
								$value = get_post_meta( $ID, 'iworks_kpir_salary_salary', true );
								echo $this->html_helper_money( $value );
								echo $this->html_table_td( '&nbsp;' );
								if ( $this->show_fractional_separetly ) {
									echo $this->html_table_td( '&nbsp;' );
								}
							break;
						}
						echo $this->html_helper_money( $value );
						if ( isset( $value['integer'] ) ) {
							$sum['expense']['integer'] += intval( $value['integer'] );
						} else if ( $this->debug ) {
							error_log( sprintf( 'Missing $value[\'integer\'] for invoice %d.', $ID ) );
						}
						if ( isset( $value['fractional'] ) ) {
							$sum['expense']['fractional'] += intval( $value['fractional'] );
						} else if ( $this->debug ) {
							error_log( sprintf( 'Missing $value[\'fractional\'] for invoice %d.', $ID ) );
						}
				break;
					case 'income':
						switch ( $type ) {
							case 'income':
								$value = get_post_meta( $ID, 'iworks_kpir_income_sale', true );
								echo $this->html_helper_money( $value );
								echo $this->html_table_td( '&nbsp;' );
								if ( $this->show_fractional_separetly ) {
									echo $this->html_table_td( '&nbsp;' );
								}
								echo $this->html_helper_money( $value );
								echo $this->html_table_td( '&nbsp;' );
								echo $this->html_table_td( '&nbsp;' );
								echo $this->html_table_td( '&nbsp;' );
								if ( $this->show_fractional_separetly ) {
									echo $this->html_table_td( '&nbsp;' );
									echo $this->html_table_td( '&nbsp;' );
									echo $this->html_table_td( '&nbsp;' );
								}
								$sum['income']['integer'] += intval( $value['integer'] );
								$sum['income']['fractional'] += intval( $value['fractional'] );
							break;
						}
				break;
					default:
						error_log( $ID );
				}

				echo '</tr>';
			}
			echo '</tbody>';
			/**
			 * sum
			 */
			echo '<tbody class="sum">';
			echo '<tr>';
			echo $this->html_table_td( '&nbsp;', null, 1, 6 );
			echo $this->html_table_td( '&nbsp;', null, 1, $this->show_fractional_separetly? 4:2 );
			echo $this->html_helper_money( $sum['income'] );
			echo $this->html_table_td( '&nbsp;', null, 1, $this->show_fractional_separetly? 4:2 );
			echo $this->html_helper_money( $sum['expense'] );
			echo '</tr>';
			echo '</tbody>';

			echo '</table>';
			/* Restore original Post Data */
			wp_reset_postdata();
		} else {
			echo '<p>';
			_e( 'There is no invoices in this month.', 'kpir' );
			echo '</p>';
		}
	}

	private function html_table_thead() {

		$show_currency_symbol = false;

		$fix_row = $show_currency_symbol? 0: -1;
		$fix_col = $this->show_fractional_separetly? 0: -1;

		$content = '<thead>';
		$content .= '<tr>';
		$content .= $this->html_table_th( 'Lp.', null, 3 + $fix_row );
		$content .= $this->html_table_th( 'Data zdarzenia gospodarczego', null, 3 + $fix_row );
		$content .= $this->html_table_th( 'Nr dowodu księgowego', null, 3 + $fix_row );
		$content .= $this->html_table_th( 'Kontrahent', null, null, 2 );
		$content .= $this->html_table_th( 'Opis zdarzenia gospodarczego', null, 3 + $fix_row );
		$content .= $this->html_table_th( 'Przychód', null, null, $this->show_fractional_separetly? 6:3 );
		$content .= $this->html_table_th( 'Wydatki', null, null, $this->show_fractional_separetly? 6:3 );
		$content .= '</tr>';
		$content .= '<tr>';
		$content .= $this->html_table_th( 'imię i nazwisko (firma)', null, 2 + $fix_row );
		$content .= $this->html_table_th( 'adres', null, 2 + $fix_row );
		$content .= $this->html_table_th( 'wartość sprzedanych towarów i usług', null, null, 2 + $fix_col );
		$content .= $this->html_table_th( 'pozostałe przychody', null, null, 2 + $fix_col );
		$content .= $this->html_table_th( 'razem przychód (7+8)', null, null, 2 + $fix_col );
		$content .= $this->html_table_th( 'wynagrodzenie', null, null, 2 + $fix_col );
		$content .= $this->html_table_th( 'pozostale', null, null, 2 + $fix_col );
		$content .= $this->html_table_th( 'razem wydatki (10+11)', null, null, 2 + $fix_col );
		$content .= '</tr>';
		if ( $show_currency_symbol ) {
			$content .= '<tr>';
			$content .= $this->html_table_th( 'zł' );
			$content .= $this->html_table_th( 'gr' );
			$content .= $this->html_table_th( 'zł' );
			$content .= $this->html_table_th( 'gr' );
			$content .= $this->html_table_th( 'zł' );
			$content .= $this->html_table_th( 'gr' );
			$content .= $this->html_table_th( 'zł' );
			$content .= $this->html_table_th( 'gr' );
			$content .= $this->html_table_th( 'zł' );
			$content .= $this->html_table_th( 'gr' );
			$content .= $this->html_table_th( 'zł' );
			$content .= $this->html_table_th( 'gr' );
			$content .= '</tr>';
		}
		$content .= '<tr>';
		$content .= $this->html_table_th( 1 );
		$content .= $this->html_table_th( 2 );
		$content .= $this->html_table_th( 3 );
		$content .= $this->html_table_th( 4 );
		$content .= $this->html_table_th( 5 );
		$content .= $this->html_table_th( 6 );
		$content .= $this->html_table_th( 7, null, null, 2 + $fix_col );
		$content .= $this->html_table_th( 8, null, null, 2 + $fix_col );
		$content .= $this->html_table_th( 9, null, null, 2 + $fix_col );
		$content .= $this->html_table_th( 10, null, null, 2 + $fix_col );
		$content .= $this->html_table_th( 11, null, null, 2 + $fix_col );
		$content .= $this->html_table_th( 12, null, null, 2 + $fix_col );
		$content .= '</tr>';
		$content .= '</thead>';
		return $content;
	}

	private function html_table_td( $value, $class = '', $rowspan = '', $colspan = '' ) {
		$args = array(
			'tag' => 'td',
			'value' => $value,
			'class' => $class,
			'rowspan' => $rowspan,
			'colspan' => $colspan,
		);
		return $this->html_table_cell( $args );
	}

	private function html_table_th( $value, $class = '', $rowspan = '', $colspan = '' ) {
		$args = array(
			'tag' => 'th',
			'value' => $value,
			'class' => $class,
			'rowspan' => $rowspan,
			'colspan' => $colspan,
		);
		return $this->html_table_cell( $args );
	}

	private function html_table_cell( $args ) {
		$attributes = '';
		foreach ( array( 'class', 'rowspan', 'colspan' ) as $key ) {
			if ( isset( $args[ $key ] ) ) {
				if ( ! empty( $args[ $key ] ) ) {
					$attributes .= sprintf( ' %s="%s"', $key, esc_attr( $args[ $key ] ) );
				}
			}
		}
		$tag = 'td';
		if ( isset( $args['tag'] ) ) {
			$tag = $args['tag'];
		}
		return sprintf(
			'<%s%s>%s</%s>', $tag, $attributes, $args['value'], $tag
		);
	}

	private function get_contractor( $contractor_id ) {
		if ( ! isset( $this->contractors[ $contractor_id ] ) ) {
			$this->contractors[ $contractor_id ] = get_post( $contractor_id );
		}
		return $this->contractors[ $contractor_id ];
	}

	public function show_filter( $post_type_object, $current ) {
		global $wpdb;

		$sql = "select distinct meta_value from {$wpdb->postmeta} where meta_key = '{$post_type_object->get_custom_field_year_month_name()}'order by meta_value desc";

		$values = $wpdb->get_col( $sql );

		if ( empty( $values ) ) {
			return;
		}

		echo '<form id="posts-filter" method="get">';
		printf( '<input name="post_type" type="hidden" value="%s" />', esc_attr( $post_type_object->get_name() ) );
		echo '<input name="page" type="hidden" value="iworks_kpir_report_monthly" />';
		echo '<div class="tablenav top">';
		echo '<div class="alignleft actions">';
		printf( '<label for="filter-by-date" class="screen-reader-text">%s</label>', esc_html__( 'Filter by date', 'kpir' ) );
		echo '<select name="m" id="filter-by-date">';
		printf( '<option value="-">%s</option>', esc_attr__( 'Select month', 'kpir' ) );
		foreach ( $values as $value ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $value ),
				selected( $value, $current, false ),
				esc_html( $value )
			);
		}
		echo '</select>';
		printf( '<input type="submit" name="filter_action" id="post-query-submit" class="button" value="%s">', esc_attr__( 'Filter', 'kpir' ) );
		echo '</div>';
		echo '</div>';
		echo '</form>';

	}

	private function html_helper_money( $value ) {
		if ( ! is_array( $value ) ) {
			return $this->html_table_td( '0,00', 'money' );
		}
		$content = '';
		if ( ! isset( $value['fractional'] ) && ! isset( $value['integer'] ) ) {
			return $this->html_table_td( '0,00', 'money' );
		}
		if ( $value['fractional'] > 99 ) {
			$value['integer'] += intval( $value['fractional'] / 100 );
			$value['fractional'] = $value['fractional'] % 100;
		}
		if ( $this->show_fractional_separetly ) {
			$content .= $this->html_table_td( $value['integer'], 'money' );
			$content .= $this->html_table_td( $value['fractional'], 'money' );
		} else {
			$val = sprintf( '%d,%02d', $value['integer'], $value['fractional'] );
			$content .= $this->html_table_td( $val, 'money' );
		}
		return $content;
	}
}

