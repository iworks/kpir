<?php
/*
Copyright 2019-PLUGIN_TILL_YEAR Marcin Pietrzak (marcin@iworks.pl)

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

if ( class_exists( 'iworks_kpir_reports_annually' ) ) {
	return;
}

class iworks_kpir_reports_annually {

	private $show_fractional_separetly = false;
	private $contractors               = array();
	private $debug                     = false;

	public function __construct() {
		$this->debug = defined( 'WP_DEBUG' ) && WP_DEBUG;
	}

	public function show( $kpir ) {
		$post_type_object = $kpir->get_post_type_invoice();

		/**
		 * get current month
		 */
		$current = isset( $_GET['m'] ) ? $_GET['m'] : '';
		if ( ! preg_match( '/^\d{4}$/', $current ) ) {
			$current = date( 'Y' );
		}
		/**
		 * show filter
		 */
		$this->show_filter( $post_type_object, $current );

		$cf_date_name         = $post_type_object->get_custom_field_basic_date_name();
		$cf_cash_in_date_name = $post_type_object->get_custom_field_date_of_cash_name();
		$cf_contractor_name   = $post_type_object->get_custom_field_basic_contractor_name();
		$date_format          = get_option( 'date_format' );

		$query = $kpir->get_annual_query( $current );

		$data = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$sum = $kpir->zero_sum_table();
				$query->the_post();
				$ID = get_the_ID();
				/**
				 * type
				 */
				$type  = get_post_meta( $ID, 'iworks_kpir_basic_type', true );
				$netto = $value = array(
					'integer'    => 0,
					'fractional' => 0,
				);

				switch ( $type ) {
					case 'expense':
					case 'asset':
					case 'salary':
						switch ( $type ) {
							case 'asset':
								$netto = $value = get_post_meta( $ID, 'iworks_kpir_asset_depreciation', true );
								break;
							case 'expense':
								$netto = $value = get_post_meta( $ID, 'iworks_kpir_expense_purchase', true );
								if ( empty( $value ) ) {
									$value = get_post_meta( $ID, 'iworks_kpir_expense_other', true );
								}
								if ( empty( $value ) ) {
									$value = get_post_meta( $ID, 'iworks_kpir_expense_cost_of_purchase', true );
								}
								$vat            = get_post_meta( $ID, 'iworks_kpir_expense_vat', true );
								$is_car_related = get_post_meta( $ID, 'iworks_kpir_expense_car', true );
								/**
								 * car related
								 */
								if ( 'no' != $is_car_related ) {
									$v = 0;
									/**
									 * integer
									 */
									if ( isset( $value['integer'] ) ) {
										$v += $value['integer'];
									}
									if ( isset( $vat['integer'] ) ) {
										$v += $vat['integer'] / 2;
									}
									$v *= 100;
									if ( 'yes' !== $is_car_related ) {
										$v *= intval( $is_car_related ) / 100;
									}
									/**
									 * fractional
									 */
									if ( isset( $value['fractional'] ) ) {
										$v += intval( $value['fractional'] );
									}
									if ( isset( $vat['fractional'] ) ) {
										$v += $vat['fractional'] / 2;
									}
									/**
									 * round
									 */
									$v = round( $v );
									/**
									 * split to integer and fractional
									 */
									$value['fractional'] = $v % 100;
									$value['integer']    = round( ( $v - $value['fractional'] ) / 100 );
								}
								break;
							case 'salary':
								$netto = get_post_meta( $ID, 'iworks_kpir_salary_salary', true );
								$value = get_post_meta( $ID, 'iworks_kpir_expense_other', true );
								$value = $this->sum( $netto, $value );
								break;
						}
						if ( 'expense' === $type ) {
							if ( isset( $value['integer'] ) ) {
								$sum['expense']['integer'] += intval( $value['integer'] );
								if ( isset( $netto['integer'] ) ) {
									$sum['expense_netto']['integer'] += intval( $netto['integer'] );
								}
							} elseif ( $this->debug ) {
								error_log( sprintf( 'Missing $value[\'integer\'] for invoice %d.', $ID ) );
							}
							if ( isset( $value['fractional'] ) ) {
								$sum['expense']['fractional'] += intval( $value['fractional'] );
								if ( isset( $netto['fractional'] ) ) {
									$sum['expense_netto']['fractional'] += intval( $netto['fractional'] );
								}
							} elseif ( $this->debug ) {
								error_log( sprintf( 'Missing $value[\'fractional\'] for invoice %d.', $ID ) );
							}
						}
						break;
					case 'income':
						switch ( $type ) {
							case 'income':
								$value                        = get_post_meta( $ID, 'iworks_kpir_income_sale', true );
								$sum['income']['integer']    += intval( $value['integer'] );
								$sum['income']['fractional'] += intval( $value['fractional'] );
								break;
						}
						break;
					default:
						error_log( $ID );
				}

				/**
				 * vat
				 */
				switch ( $type ) {
					case 'asset':
						break;
					case 'salary':
						/**
						 * Salary
						 */
						$value                                = get_post_meta( $ID, 'iworks_kpir_salary_salary', true );
						$sum['expense_salary']['integer']    += intval( $value['integer'] );
						$sum['expense_salary']['fractional'] += intval( $value['fractional'] );
						/**
						 * salary Other cost
						 */
						$value = get_post_meta( $ID, 'iworks_kpir_expense_other', true );
						if ( is_array( $value ) ) {
							$sum['expense_salary']['integer']    += intval( $value['integer'] );
							$sum['expense_salary']['fractional'] += intval( $value['fractional'] );
						}
						break;
					case 'expense':
						$vat = get_post_meta( $ID, 'iworks_kpir_expense_vat', true );
						/**
						 * car related
						 */
						if ( 'no' !== $is_car_related ) {
							$v = 0;
							/**
							 * integer
							 */
							if ( isset( $vat['integer'] ) ) {
								$v += $vat['integer'] * 100;
							}
							/**
							 * fractional
							 */
							if ( isset( $vat['fractional'] ) ) {
								$v += $vat['fractional'];
							}
							/**
							 * recalculate
							 */
							$v                     = round( $v / 2 );
							$vat_car               = array(
								'integer'    => 0,
								'fractional' => 0,
							);
							$vat_car['fractional'] = $v % 100;
							$vat_car['integer']    = ( $v - $vat_car['fractional'] ) / 100;
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
						break;
					case 'income':
						$vat = get_post_meta( $ID, 'iworks_kpir_income_vat', true );
						if ( is_array( $vat ) ) {
							$sum['vat_income']['integer']    += intval( $vat['integer'] );
							$sum['vat_income']['fractional'] += intval( $vat['fractional'] );
						}
						break;
				}
				$month = date_i18n( 'F', get_post_meta( $ID, $cf_date_name, true ) );
				if ( ! isset( $data[ $month ] ) ) {
					$data[ $month ] = $kpir->zero_sum_table();
				}
				foreach ( $sum as $type => $parts ) {
					foreach ( $parts as $part => $value ) {
						$data[ $month ][ $type ][ $part ] += $value;
					}
				}
			}
			/* Restore original Post Data */
			wp_reset_postdata();
			/**
			 * options
			 */
			$options = iworks_kpir_get_options();
			/**
			 * cash-in PIT
			 *
			 * @since 1.1.0
			 */
			if ( $options->get_option( 'cash_pit' ) ) {
				$query = $kpir->get_annual_cash_in_query( $current );
				if ( $query->have_posts() ) {
					while ( $query->have_posts() ) {
						$query->the_post();
						$ID                             = get_the_ID();
						$value                          = get_post_meta( $ID, 'iworks_kpir_income_sale', true );
						$sum['cash_pit']['integer']    += intval( $value['integer'] );
						$sum['cash_pit']['fractional'] += intval( $value['fractional'] );
						/**
						 * month
						 */
						$month = date_i18n( 'F', get_post_meta( $ID, $cf_cash_in_date_name, true ) );
						if ( ! isset( $data[ $month ] ) ) {
							$data[ $month ] = $kpir->zero_sum_table();
						}
						foreach ( $sum as $type => $parts ) {
							foreach ( $parts as $part => $value ) {
								$data[ $month ][ $type ][ $part ] += $value;
							}
						}
					}
					/* Restore original Post Data */
					wp_reset_postdata();
				}
			}
			/**
			 * print
			 */
			echo '<table class="kpir-report kpir-report-annually">';
			echo $this->html_table_thead();
			$cumulative = $kpir->zero_sum_table();
			foreach ( $data as $month => $sum ) {
				foreach ( $sum as $type => $parts ) {
					foreach ( $parts as $part => $value ) {
						$cumulative[ $type ][ $part ] += $value;
					}
				}
				echo '<tbody>';
				echo '<tr>';
				echo $this->html_table_td( $month );
				echo $this->html_helper_money( $sum['income'] );
				echo $this->html_helper_money( array( 0, 0 ) );
				echo $this->html_helper_money( $sum['income'] );
				if ( $options->get_option( 'cash_pit' ) ) {
					echo $this->html_helper_money( $sum['cash_pit'] );
				}
				echo $this->html_helper_money( $sum['expense_netto'] );
				echo $this->html_helper_money( $sum['expense_other'] );
				echo $this->html_helper_money( $sum['expense_salary'] );
				echo $this->html_helper_money( array( 0, 0 ) );
				echo $this->html_helper_money( $sum['expense'] );
				echo '</tr>';
				echo '<tr>';
				echo $this->html_table_td( $month . ' narastająco' );
				echo $this->html_helper_money( $cumulative['income'] );
				echo $this->html_helper_money( array( 0, 0 ) );
				echo $this->html_helper_money( $cumulative['income'] );
				if ( $options->get_option( 'cash_pit' ) ) {
					echo $this->html_helper_money( $cumulative['cash_pit'] );
				}
				echo $this->html_helper_money( $cumulative['expense_netto'] );
				echo $this->html_helper_money( $cumulative['expense_other'] );
				echo $this->html_helper_money( $cumulative['expense_salary'] );
				echo $this->html_helper_money( array( 0, 0 ) );
				echo $this->html_helper_money( $cumulative['expense'] );
				echo '</tr>';
				echo '</tbody>';
			}
			/**
			 * sum
			 */
			echo '<tfoot>';
			echo '<tr>';
			echo $this->html_table_td( 'Razem' );
			echo $this->html_helper_money( $cumulative['income'] );
			echo $this->html_helper_money( array( 0, 0 ) );
			echo $this->html_helper_money( $cumulative['income'] );
			if ( $options->get_option( 'cash_pit' ) ) {
				echo $this->html_helper_money( $cumulative['cash_pit'] );
			}
			echo $this->html_helper_money( $cumulative['expense_netto'] );
			echo $this->html_helper_money( $cumulative['expense_other'] );
			echo $this->html_helper_money( $cumulative['expense_salary'] );
			echo $this->html_helper_money( array( 0, 0 ) );
			echo $this->html_helper_money( $cumulative['expense'] );
			echo '</tr>';
			echo '</tfoot>';
			echo '</table>';
		} else {
			echo '<p>';
			_e( 'There is no invoices in this year.', 'kpir' );
			echo '</p>';
		}
	}

	private function html_table_thead() {
		$options              = iworks_kpir_get_options();
		$show_currency_symbol = false;

		$fix_row = $show_currency_symbol ? 0 : -1;
		$fix_col = $this->show_fractional_separetly ? 0 : -1;

		$content  = '<thead>';
		$content .= '<tr>';
		$content .= $this->html_table_th( 'Miesiąc', null, 0 + $fix_row );
		$content .= $this->html_table_th( 'Sprzedane towary i usługi', null, 0 + $fix_row );
		$content .= $this->html_table_th( 'Pozostałe przychody', null, 0 + $fix_row );
		$content .= $this->html_table_th( 'Przychód razem', null, 0 + $fix_row );
		if ( $options->get_option( 'cash_pit' ) ) {
			$content .= $this->html_table_th( esc_html__( 'Cash-in', 'kpir' ), null, 0 + $fix_row );
		}
		$content .= $this->html_table_th( 'Zakup towarów i materiałów', null, 0 + $fix_row );
		$content .= $this->html_table_th( 'Koszty uboczne', null, 0 + $fix_row );
		$content .= $this->html_table_th( 'Koszty wynagrodzenia', null, 0 + $fix_row );
		$content .= $this->html_table_th( 'Pozostałe wydatki', null, 0 + $fix_row );
		$content .= $this->html_table_th( 'Koszty razem', null, 0 + $fix_row );
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
		$content .= '</thead>';
		return $content;
	}

	private function html_table_td( $value, $class = '', $rowspan = '', $colspan = '' ) {
		$args = array(
			'tag'     => 'td',
			'value'   => $value,
			'class'   => $class,
			'rowspan' => $rowspan,
			'colspan' => $colspan,
		);
		return $this->html_table_cell( $args );
	}

	private function html_table_th( $value, $class = '', $rowspan = '', $colspan = '' ) {
		$args = array(
			'tag'     => 'th',
			'value'   => $value,
			'class'   => $class,
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
			'<%s%s>%s</%s>',
			$tag,
			$attributes,
			$args['value'],
			$tag
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

		$sql = "select distinct substr( meta_value, 1, 4 ) as meta_value from {$wpdb->postmeta} where meta_key = '{$post_type_object->get_custom_field_year_month_name()}'order by meta_value desc";

		$values = $wpdb->get_col( $sql );

		if ( empty( $values ) ) {
			return;
		}

		echo '<form id="posts-filter" method="get">';
		printf( '<input name="post_type" type="hidden" value="%s" />', esc_attr( $post_type_object->get_name() ) );
		echo '<input name="page" type="hidden" value="iworks_kpir_report_annually" />';
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
			$value['integer']   += intval( $value['fractional'] / 100 );
			$value['fractional'] = $value['fractional'] % 100;
		}
		if ( $this->show_fractional_separetly ) {
			$content .= $this->html_table_td( $value['integer'], 'money' );
			$content .= $this->html_table_td( $value['fractional'], 'money' );
		} else {
			$val      = sprintf( '%d,%02d', $value['integer'], $value['fractional'] );
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
		if ( empty( $value2 ) ) {
			return $value1;
		}
		$value['integer']    = $value1['integer'] + $value2['integer'];
		$value['fractional'] = $value1['fractional'] + $value2['fractional'];
		if ( 100 > $value['fractional'] ) {
			$value['integer']   += intval( $value['fractional'] / 100 );
			$value['fractional'] = $value['fractional'] % 100;
		}
		return $value;
	}
}
