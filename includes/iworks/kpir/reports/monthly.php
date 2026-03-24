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

if ( class_exists( 'iworks_kpir_reports_monthly' ) ) {
	return;
}

/**
 * Monthly reports generation class for KPIR.
 *
 * Handles the generation and display of monthly financial reports
 * including income, expenses, VAT calculations, and contractor information.
 * Supports both standard and cash PIT (Personal Income Tax) methods.
 *
 * @package KPIR
 * @subpackage Reports
 * @since 1.0.0
 */
class iworks_kpir_reports_monthly {

	/**
	 * Whether to show fractional parts separately.
	 *
	 * @var bool
	 */
	private $show_fractional_separetly = false;

	/**
	 * Cached contractor data.
	 *
	 * @var array
	 */
	private $contractors = array();

	/**
	 * Debug mode flag.
	 *
	 * @var bool
	 */
	private $debug = false;

	/**
	 * Whether cash PIT method is enabled.
	 *
	 * @since 1.1.4
	 * @var bool
	 */
	private $is_use_cash_pit = false;

	/**
	 * Class constructor.
	 *
	 * Initializes the monthly reports class and sets up debug mode.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function __construct() {
		$this->debug = defined( 'WP_DEBUG' ) && WP_DEBUG;
	}

	/**
	 * Display the monthly report.
	 *
	 * Generates and displays a comprehensive monthly financial report
	 * including income, expenses, VAT, and contractor information.
	 * Supports both standard and cash PIT reporting methods.
	 *
	 * @since 1.0.0
	 *
	 * @param iworks_kpir $kpir Main KPIR plugin instance.
	 * @return void
	 */
	public function show( $kpir ) {
		$post_type_object = $kpir->get_post_type_invoice();
		$this->is_use_cash_pit = $kpir->is_use_cash_pit();
		/**
		 * check vat 
		 * @since 1.2.0
		 */
		$check_vat = boolval( get_option( 'iworks_kpir_check_vat_on_monothly', 0 ) );
		/**
		 * get current month
		 */
		$current = isset( $_GET['m'] ) ? $_GET['m'] : '';
		if ( ! preg_match( '/^\d{4}\-\d{2}$/', $current ) ) {
			$current = date( 'Y-m' );
		}
		/**
		 * show filter
		 */
		$this->show_filter( $post_type_object, $current );

		$cf_date_name       = $post_type_object->get_custom_field_basic_date_name();
		$cf_cash_date_name       = $post_type_object->get_custom_field_date_of_cash_name();
		$cf_contractor_name = $post_type_object->get_custom_field_basic_contractor_name();
		$date_format        = get_option( 'date_format' );

		$query = $kpir->get_month_query( $current );

		if ( $query->have_posts() ) {
			$sum = array(
				'income'        => array(
					'integer'    => 0,
					'fractional' => 0,
				),
				'expense'       => array(
					'integer'    => 0,
					'fractional' => 0,
				),
				'expense_netto' => array(
					'integer'    => 0,
					'fractional' => 0,
				),
				'vat_income'    => array(
					'integer'    => 0,
					'fractional' => 0,
				),
				'vat_expense'   => array(
					'integer'    => 0,
					'fractional' => 0,
				),
			);
			$i   = 1;
			echo '<table class="kpir-report kpir-report-monthly">';
			echo $this->html_table_thead();
			echo '<tbody>';
			while ( $query->have_posts() ) {
				$query->the_post();
				$ID            = get_the_ID();
				$contractor_id = get_post_meta( $ID, $cf_contractor_name, true );
				/**
				 * type
				 */
				$type  = get_post_meta( $ID, 'iworks_kpir_basic_type', true );
				/**
				 * start produce row
				 */
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
				 * cash flow date
				 */
				if ( $this->is_use_cash_pit ) {
					if ( 'income' === $type ) {
						$cash_flow_date = get_post_meta( $ID, $cf_cash_date_name, true );
						echo $this->html_table_td( date_i18n( $date_format, $cash_flow_date ), 'date' );
					} else {
						echo $this->html_table_td( '&mdash;', 'date' );
					}
				}
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
							'post_type'  => 'iworks_kpir_invoice',
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
				$description    = get_post_meta( $ID, 'iworks_kpir_basic_description', true );
				if ( 'no' !== $is_car_related ) {
					$description .= sprintf( ' <small class="car">%s</small>', esc_html__( '(car related)', 'kpir' ) );
				}
				echo $this->html_table_td( $description );
				$netto = $value = array(
					'integer'    => 0,
					'fractional' => 0,
				);

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
										$v *= $is_car_related / 100;
									}
									/**
									 * fractional
									 */
									if ( isset( $value['fractional'] ) ) {
										$v += $value['fractional'];
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
								/**
								 * echo
								 */
								echo $this->html_helper_money( $value );
								break;
							case 'salary':
								$netto = get_post_meta( $ID, 'iworks_kpir_salary_salary', true );
								echo $this->html_helper_money( $netto );
								$value = get_post_meta( $ID, 'iworks_kpir_expense_other', true );
								echo $this->html_helper_money( $value );
								if ( $this->show_fractional_separetly ) {
									echo $this->html_table_td( '&nbsp;' );
								}
								$value = $this->sum( $netto, $value );
								break;
						}
						echo $this->html_helper_money( $value );
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
								$sum['income']['integer']    += intval( $value['integer'] );
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
						if ( 'no' !== $is_car_related ) {
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
						if ( 'no' === $is_car_related ) {
							echo $this->html_table_td( '&nbsp;' );
							if ( $this->show_fractional_separetly ) {
								echo $this->html_table_td( '&nbsp;' );
							}
						}
						break;
					case 'income':
						$check_vat_class = array();
						$vat = get_post_meta( $ID, 'iworks_kpir_income_vat', true );
						if ( is_array( $vat ) ) {
							$sum['vat_income']['integer']    += intval( $vat['integer'] );
							$sum['vat_income']['fractional'] += intval( $vat['fractional'] );
							if ( $check_vat ) {
								$value = get_post_meta( $ID, 'iworks_kpir_income_sale', true );
								$n     = ( $value['integer'] * 100 + $value['fractional'] ) * 0.23;
								$v     = ( $vat['integer'] * 100 + $vat['fractional'] );
								$check_vat_class[] = intval(abs($n - $v)) ? 'vat-failed' : 'vat-passed';
							}
						}
						echo $this->html_helper_money( $vat, $check_vat_class );
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
				if ( $this->is_use_cash_pit ) {
			echo $this->html_table_td( '&nbsp;', null, 1, 7 );
				} else {
			echo $this->html_table_td( '&nbsp;', null, 1, 6 );

				}
			echo $this->html_table_td( '&nbsp;', null, 1, $this->show_fractional_separetly ? 4 : 2 );
			echo $this->html_helper_money( $sum['income'] );
			echo $this->html_table_td( '&nbsp;', null, 1, $this->show_fractional_separetly ? 4 : 2 );
			echo $this->html_helper_money( $sum['expense'] );
			echo $this->html_helper_money( $sum['expense_netto'] );
			/**
			 * check vat
			 */
			$check_vat_class = array();
			if ( $check_vat ) {
				$n = ( $sum['income']['integer'] * 100 + $sum['income']['fractional'] ) * .23;
				$v = ( $sum['vat_income']['integer'] * 100 + $sum['vat_income']['fractional'] );
				$check_vat_class[] = intval(abs($n-$v)) ? 'vat-failed' : 'vat-passed';
			}
			echo $this->html_helper_money( $sum['vat_income'], $check_vat_class );
			if ( $this->show_fractional_separetly ) {
				echo $this->html_table_td( '&nbsp;' );
			}
			echo $this->html_helper_money( $sum['vat_expense'] );
			if ( $this->show_fractional_separetly ) {
				echo $this->html_table_td( '&nbsp;' );
			}
			echo $this->html_table_td( '&nbsp;', null, 1, $this->show_fractional_separetly ? 2 : 1 );
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

	/**
	 * Generate the table header HTML.
	 *
	 * Creates the complex table header structure for the monthly report,
	 * including column spans and proper labeling for Polish tax requirements.
	 *
	 * @since 1.0.0
	 *
	 * @return string HTML content for the table header.
	 */
	private function html_table_thead() {

		$show_currency_symbol = false;

		$fix_row = $show_currency_symbol ? 0 : -1;
		$fix_col = $this->show_fractional_separetly ? 0 : -1;

		$content  = '<thead>';
		$content .= '<tr>';
		$content .= $this->html_table_th( 'Lp.', null, 3 + $fix_row );
		if ( $this->is_use_cash_pit ) {
		$content .= $this->html_table_th( 'Data', null, null, 2 );
		} else {
			$content .= $this->html_table_th( 'Data zdarzenia', null, 3 + $fix_row );
		}
		$content .= $this->html_table_th( 'Nr dowodu księgowego', null, 3 + $fix_row );
		$content .= $this->html_table_th( 'Kontrahent', null, null, 2 );
		$content .= $this->html_table_th( 'Opis zdarzenia gospodarczego', null, 3 + $fix_row );
		$content .= $this->html_table_th( 'Przychód', null, null, $this->show_fractional_separetly ? 6 : 3 );
		$content .= $this->html_table_th( 'Wydatki', null, null, $this->show_fractional_separetly ? 8 : 4 );
		$content .= $this->html_table_th( 'VAT', null, null, $this->show_fractional_separetly ? 6 : 3 );
		$content .= '</tr>';
		$content .= '<tr>';
		if ( $this->is_use_cash_pit ) {
			$content .= $this->html_table_th( 'zdarzenia', null, 2 + $fix_row );
			$content .= $this->html_table_th( 'zapłaty', null, 2 + $fix_row );
		}
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
		if ( $this->is_use_cash_pit ) {
			$content .= $this->html_table_th( '2 (MK)' );
		}
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

	/**
	 * Generate table cell HTML.
	 *
	 * Creates a table cell (td) with optional attributes.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value   Cell content.
	 * @param string $class   CSS class for the cell.
	 * @param int    $rowspan Number of rows to span.
	 * @param int    $colspan Number of columns to span.
	 * @return string HTML content for the table cell.
	 */
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

	/**
	 * Generate table header cell HTML.
	 *
	 * Creates a table header cell (th) with optional attributes.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value   Cell content.
	 * @param string $class   CSS class for the cell.
	 * @param int    $rowspan Number of rows to span.
	 * @param int    $colspan Number of columns to span.
	 * @return string HTML content for the table header cell.
	 */
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

	/**
	 * Generate generic table cell HTML.
	 *
	 * Creates a table cell with specified tag and attributes.
	 * Used internally by html_table_td and html_table_th methods.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Cell arguments including tag, value, and attributes.
	 * @return string HTML content for the table cell.
	 */
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

	/**
	 * Get contractor data by ID.
	 *
	 * Retrieves and caches contractor post data for efficient access
	 * during report generation.
	 *
	 * @since 1.0.0
	 *
	 * @param int $contractor_id Contractor post ID.
	 * @return WP_Post|null Contractor post object or null if not found.
	 */
	private function get_contractor( $contractor_id ) {
		if ( ! isset( $this->contractors[ $contractor_id ] ) ) {
			$this->contractors[ $contractor_id ] = get_post( $contractor_id );
		}
		return $this->contractors[ $contractor_id ];
	}

	/**
	 * Display the month filter form.
	 *
	 * Creates and displays a form for filtering reports by month.
	 * Retrieves available months from the database and generates a dropdown.
	 *
	 * @since 1.0.0
	 *
	 * @param iworks_kpir_posttypes_invoice $post_type_object Invoice post type object.
	 * @param string                         $current        Currently selected month.
	 * @return void
	 */
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

	/**
	 * Format money values for display.
	 *
	 * Handles the formatting of money values in the report table,
	 * supporting both combined and separate integer/fractional display.
	 *
	 * @since 1.0.0
	 *
	 * @param array $value Money array with integer and fractional parts.
	 * @return string HTML content for formatted money display.
	 */
	private function html_helper_money( $value, $classes = array() ) {
		$classes[] = 'money';
		$class     = implode( ' ', array_unique( $classes ) );
		if ( ! is_array( $value ) ) {
			return $this->html_table_td( '0,00', $class );
		}
		$content = '';
		if ( ! isset( $value['fractional'] ) && ! isset( $value['integer'] ) ) {
			return $this->html_table_td( '0,00', $class );
		}
		if ( $value['fractional'] > 99 ) {
			$value['integer']   += intval( $value['fractional'] / 100 );
			$value['fractional'] = $value['fractional'] % 100;
		}
		if ( $this->show_fractional_separetly ) {
			$content .= $this->html_table_td( $value['integer'], $class );
			$content .= $this->html_table_td( $value['fractional'], $class );
		} else {
			$val      = sprintf( '%d,%02d', $value['integer'], $value['fractional'] );
			$content .= $this->html_table_td( $val, $class );
		}
		return $content;
	}

	/**
	 * Sum two money values.
	 *
	 * Adds two money arrays together, handling proper carry-over
	 * between fractional and integer parts.
	 *
	 * @since 0.0.7
	 *
	 * @param array $value1 First money array with integer and fractional parts.
	 * @param array $value2 Second money array with integer and fractional parts.
	 * @return array Summed money array with normalized integer and fractional parts.
	 */
	private function sum( $value1, $value2 ) {
		$value['integer']    = $value1['integer'] + $value2['integer'];
		$value['fractional'] = $value1['fractional'] + $value2['fractional'];
		if ( 100 > $value['fractional'] ) {
			$value['integer']   += intval( $value['fractional'] / 100 );
			$value['fractional'] = $value['fractional'] % 100;
		}
		return $value;
	}
}

