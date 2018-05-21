<?php
/*

Copyright 2017-2018 Marcin Pietrzak (marcin@iworks.pl)

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

	public function show( $kpir ) {
		$post_type_object = $kpir->get_post_type_invoice();

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

		$query = $kpir->get_month_query( $current );

		if ( $query->have_posts() ) {
			$sum = array(
				'income' => array(
					'integer' => 0,
					'fractional' => 0,
				),
				'expense' => array(
					'integer' => 0,
					'fractional' => 0,
				),
				'expense_netto' => array(
					'integer' => 0,
					'fractional' => 0,
				),
				'vat_income' => array(
					'integer' => 0,
					'fractional' => 0,
				),
				'vat_expense' => array(
					'integer' => 0,
					'fractional' => 0,
				),
			);
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
				$is_car_related = get_post_meta( $ID, 'iworks_kpir_expense_car', true );
				$description = get_post_meta( $ID, 'iworks_kpir_basic_description', true );
				if ( 'yes' === $is_car_related ) {
					$description .= sprintf( ' <small class="car">%s</small>', esc_html__( '(car related)', 'kpir' ) );
				}
				echo $this->html_table_td( $description );

				/**
				 * type
				 */
				$type = get_post_meta( $ID, 'iworks_kpir_basic_type', true );
				$netto = $value = array( 'integer' => 0, 'fractional' => 0 );

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
								$netto = $value = get_post_meta( $ID, 'iworks_kpir_asset_depreciation', true );
								echo $this->html_helper_money( $value );
							break;
							case 'expense':
								echo $this->html_table_td( '&nbsp;' );
								if ( $this->show_fractional_separetly ) {
									echo $this->html_table_td( '&nbsp;' );
								}
								$netto = $value = get_post_meta( $ID, 'iworks_kpir_expense_purchase', true );
								if ( empty( $value ) ) {
									$value = get_post_meta( $ID, 'iworks_kpir_expense_other', true );
								}
								if ( empty( $value ) ) {
									$value = get_post_meta( $ID, 'iworks_kpir_expense_cost_of_purchase', true );
								}
								$vat = get_post_meta( $ID, 'iworks_kpir_expense_vat', true );
								/**
						 * car related
						 */
								if ( 'yes' == $is_car_related ) {
									$v = round( ($value['integer'] + $vat['integer'] / 2) * 100 + $value['fractional'] + $vat['fractional'] / 2 );
									$value['fractional'] = $v % 100;
									$value['integer'] = round( ($v - $value['fractional']) / 100 );
								}
								/**
						 * echo
						 */
								echo $this->html_helper_money( $value );
							break;
							case 'salary':
								$netto = get_post_meta( $ID, 'iworks_kpir_salary_salary', true );

								l( $netto );

								echo $this->html_helper_money( $netto );
								$value = get_post_meta( $ID, 'iworks_kpir_expense_other', true );

								l( $value );

								echo $this->html_helper_money( $value );
								if ( $this->show_fractional_separetly ) {
									echo $this->html_table_td( '&nbsp;' );
								}
								$value = $this->sum( $netto, $value );
								l( $value );
							break;
						}
						echo $this->html_helper_money( $value );
						if ( isset( $value['integer'] ) ) {
							$sum['expense']['integer'] += intval( $value['integer'] );
							$sum['expense_netto']['integer'] += intval( $netto['integer'] );
						} else if ( $this->debug ) {
							error_log( sprintf( 'Missing $value[\'integer\'] for invoice %d.', $ID ) );
						}
						if ( isset( $value['fractional'] ) ) {
							$sum['expense']['fractional'] += intval( $value['fractional'] );
							$sum['expense_netto']['fractional'] += intval( $netto['fractional'] );
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
				/**
				 * netto
				 */
				switch ( $type ) {
					case 'expense':
						if ( 'yes' === $is_car_related ) {
							echo $this->html_helper_money( $netto );
						} else {
							echo $this->html_table_td( '&nbsp;' );
							if ( $this->show_fractional_separetly ) {
								echo $this->html_table_td( '&nbsp;' );
							}
						}
					break;
					default:
						echo $this->html_table_td( '&nbsp;' );
						if ( $this->show_fractional_separetly ) {
							echo $this->html_table_td( '&nbsp;' );
						}
					break;
				}

				/**
				 * vat
				 */
				switch ( $type ) {
					case 'asset':
					case 'salary':
						echo $this->html_table_td( '&nbsp;' );
						if ( $this->show_fractional_separetly ) {
							echo $this->html_table_td( '&nbsp;' );
						}
						echo $this->html_table_td( '&nbsp;' );
						if ( $this->show_fractional_separetly ) {
							echo $this->html_table_td( '&nbsp;' );
						}
						echo $this->html_table_td( '&nbsp;' );
						if ( $this->show_fractional_separetly ) {
							echo $this->html_table_td( '&nbsp;' );
						}
					break;
					case 'expense':
						echo $this->html_table_td( '&nbsp;' );
						if ( $this->show_fractional_separetly ) {
							echo $this->html_table_td( '&nbsp;' );
						}
						$vat = get_post_meta( $ID, 'iworks_kpir_expense_vat', true );
						/**
						 * car related
						 */
						if ( 'yes' == $is_car_related ) {
							$v = round( ($vat['integer'] * 100 + $vat['fractional']) / 2 );
							$vat_car = array(
							'integer' => 0,
							'fractional' => 0,
							);
							$vat_car['fractional'] = $v % 100;
							$vat_car['integer'] = ($v - $vat_car['fractional']) / 100;
							echo $this->html_helper_money( $vat_car );
							if ( $this->show_fractional_separetly ) {
								echo $this->html_table_td( '&nbsp;' );
							}
							if ( isset( $vat_car['integer'] ) ) {
								$sum['vat_expense']['integer'] += intval( $vat_car['integer'] );
							}
							if ( isset( $vat_car['fractional'] ) ) {
								$sum['vat_expense']['fractional'] += intval( $vat_car['fractional'] );
							}
						} else {
							if ( isset( $vat['integer'] ) ) {
								$sum['vat_expense']['integer'] += intval( $vat['integer'] );
							}
							if ( isset( $vat['fractional'] ) ) {
								$sum['vat_expense']['fractional'] += intval( $vat['fractional'] );
							}
						}
						echo $this->html_helper_money( $vat );
						if ( $this->show_fractional_separetly ) {
							echo $this->html_table_td( '&nbsp;' );
						}
						if ( 'yes' != $is_car_related ) {
							echo $this->html_table_td( '&nbsp;' );
							if ( $this->show_fractional_separetly ) {
								echo $this->html_table_td( '&nbsp;' );
							}
						}
					break;
					case 'income':
						$vat = get_post_meta( $ID, 'iworks_kpir_income_vat', true );
						if ( is_array( $vat ) ) {
							$sum['vat_income']['integer'] += intval( $vat['integer'] );
							$sum['vat_income']['fractional'] += intval( $vat['fractional'] );
						}
						echo $this->html_helper_money( $vat );
						if ( $this->show_fractional_separetly ) {
							echo $this->html_table_td( '&nbsp;' );
						}
						echo $this->html_table_td( '&nbsp;' );
						if ( $this->show_fractional_separetly ) {
							echo $this->html_table_td( '&nbsp;' );
						}
						echo $this->html_table_td( '&nbsp;' );
						if ( $this->show_fractional_separetly ) {
							echo $this->html_table_td( '&nbsp;' );
						}
					break;
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
			echo $this->html_helper_money( $sum['expense_netto'] );
			echo $this->html_helper_money( $sum['vat_income'] );
			if ( $this->show_fractional_separetly ) {
				echo $this->html_table_td( '&nbsp;' );
			}
			echo $this->html_helper_money( $sum['vat_expense'] );
			if ( $this->show_fractional_separetly ) {
				echo $this->html_table_td( '&nbsp;' );
			}
			echo $this->html_table_td( '&nbsp;', null, 1, $this->show_fractional_separetly? 2:1 );
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
		$content .= $this->html_table_th( 'Wydatki', null, null, $this->show_fractional_separetly? 8:4 );
		$content .= $this->html_table_th( 'VAT', null, null, $this->show_fractional_separetly? 6:3 );
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
		$content .= $this->html_table_th( 'netto', null, null, 2 + $fix_col );
		$content .= $this->html_table_th( 'sprzedaż', null, null, 2 + $fix_col );
		$content .= $this->html_table_th( 'zakup rozliczenie', null, null, 2 + $fix_col );
		$content .= $this->html_table_th( 'zakup', null, null, 2 + $fix_col );
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
		$content .= $this->html_table_th( 13, null, null, 2 + $fix_col );
		$content .= $this->html_table_th( 14, null, null, 2 + $fix_col );
		$content .= $this->html_table_th( 15, null, null, 2 + $fix_col );
		$content .= $this->html_table_th( 16, null, null, 2 + $fix_col );
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

	/**
	 * Sum ttwo values
	 *
	 * @since 0.0.7
	 */
	private function sum( $value1, $value2 ) {
		$value['integer'] = $value1['integer'] + $value2['integer'];
		$value['fractional'] = $value1['fractional'] + $value2['fractional'];
		if ( 100 > $value['fractional'] ) {
			$value['integer'] += intval( $value['fractional'] / 100 );
			$value['fractional'] = $value['fractional'] % 100;
		}
		return $value;
	}
}

