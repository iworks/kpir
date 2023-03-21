<?php
/*
Copyright 2020-PLUGIN_TILL_YEAR Marcin Pietrzak (marcin@iworks.pl)

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
require_once dirname( dirname( __FILE__ ) ) . '/jpk.php';

class iworks_kpir_jpk_v7m extends iworks_kpir_jpk {

	protected $name = 'kpir-jpk-v7m';

	public function __construct() {
		parent::__construct();
		add_action( 'send_headers', array( $this, 'get_xml' ) );
	}

	public function show( $kpir ) {
		global $wpdb;
		$post_type_object = $kpir->get_post_type_invoice();
		$sql              = "select distinct meta_value from {$wpdb->postmeta} where meta_key = '{$post_type_object->get_custom_field_year_month_name()}'order by meta_value desc";
		$values           = $wpdb->get_col( $sql );
		if ( empty( $values ) ) {
			$this->get_template( 'jpk', 'no-entries' );
			return;
		}
		$data = array(
			'this'   => $this,
			'name'   => $this->name,
			'page'   => 'iworks_kpir_jpk_v7m',
			'months' => $values,
		);
		$this->get_template( 'jpk/v7m', 'show', $data );
	}

	/**
	 *
	 */
	public function get_xml( $kpir ) {
		if (
			! isset( $_REQUEST['nonce'] )
			|| ! isset( $_REQUEST['purpose'] )
			|| ! isset( $_REQUEST['m'] )
		) {
			return;
		}
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], $this->name ) ) {
			return;
		}
		/**
		 * validate input
		 */
		$year_month = filter_input( INPUT_GET, 'm', FILTER_SANITIZE_STRING );
		$year_month = $this->validate_year_month( $year_month );
		/**
		 * Choose template
		 */
		$template = 'jpk/v7m/xml';
		if ( 2021 < intval( preg_replace( '/\-.+$/', '', $year_month ) ) ) {
			$template = 'jpk/v7m-2/xml';
		}
		/**
		 * error
		 */
		if ( is_wp_error( $year_month ) ) {
			die( $year_month->get_error_message() );
		}
		$purpose            = filter_input( INPUT_GET, 'purpose', FILTER_VALIDATE_INT );
		$date               = explode( '-', $year_month );
		$args               = array(
			'purpose'    => $purpose,
			'year_month' => $year_month,
			'year'       => $date[0],
			'month'      => $date[1],
			'created'    => preg_replace( '/\+00:00$/', '.000Z', date( 'c' ) ),
			'taxpayer'   => array(
				'department_of_revenue' => get_option( $this->options->get_option_name( 'department_of_revenue' ) ),
				'email'                 => get_option( $this->options->get_option_name( 'email' ) ),
				'last_name'             => $this->convert_chars( get_option( $this->options->get_option_name( 'last_name' ) ) ),
				'name'                  => $this->convert_chars( get_option( $this->options->get_option_name( 'name' ) ) ),
				'nip'                   => preg_replace( '/[^\d]+/', '', get_option( $this->options->get_option_name( 'nip' ) ) ),
				'phone'                 => get_option( $this->options->get_option_name( 'phone' ) ),
				'surname'               => $this->convert_chars( get_option( $this->options->get_option_name( 'surname' ) ) ),
			),
			'incomes'    => array(),
			'expenses'   => array(),
		);
		$post_type_object   = $kpir->get_post_type_invoice();
		$cf_date_name       = $post_type_object->get_custom_field_basic_date_name();
		$cf_contractor_name = $post_type_object->get_custom_field_basic_contractor_name();
		$date_format        = get_option( 'date_format' );
		$query              = $kpir->get_month_query( $year_month );
		if ( $query->have_posts() ) {
			$incomes_counter = $expenses_counter = 0;
			$expenses        = $incomes = '';
			while ( $query->have_posts() ) {
				$query->the_post();
				$contractor_id    = get_post_meta( get_the_ID(), $cf_contractor_name, true );
				$transaction_data = array(
					'ID'            => get_the_ID(),
					'contractor_id' => $contractor_id,
					'contractor'    => $this->get_contractor( $contractor_id ),
				);
				switch ( get_post_meta( get_the_ID(), 'iworks_kpir_basic_type', true ) ) {
					case 'expense':
						$transaction_data['counter'] = ++$expenses_counter;
						$args['expenses'][]          = $this->expense_row( $transaction_data );
						break;
					case 'income':
						$transaction_data['counter'] = ++$incomes_counter;
						$args['incomes'][]           = $this->income_row( $transaction_data );
						break;
				}
			}
		}
		/**
		 * summary
		 */
		$excess = filter_input( INPUT_GET, 'excess', FILTER_VALIDATE_INT );
		if ( 0 < $excess ) {
			$args['P_39'] = $excess;
		}
		foreach ( $this->sum as $key => $money ) {
			$this->sum[ $key ] = $this->normalize_money( $money );
		}
		/**
		 * sumarize
		 *
		 * P_10
		 * Wysokość podstawy opodatkowania z tytułu dostawy towarów oraz
		 * świadczenia usług na terytorium kraju, zwolnionych od podatku
		 */
		/**
		 * P_11
		 * Wysokość podstawy opodatkowania z tytułu dostawy towarów oraz
		 * świadczenia usług poza terytorium kraju
		 */
		/**
		 * P_12
		 * Wysokość podstawy opodatkowania z tytułu świadczenia usług,
		 * o których mowa w art. 100 ust. 1 pkt 4 ustawy
		 */
		/**
		 * P_13
		 * Wysokość podstawy opodatkowania z tytułu dostawy towarów oraz
		 * świadczenia usług na terytorium kraju, opodatkowanych stawką 0%
		 */
		/**
		 * P_14
		 * Wysokość podstawy opodatkowania z tytułu dostawy towarów, o której
		 * mowa w art. 129 ustawy
		 */
		/**
		 * P_15
		 * Wysokość podstawy opodatkowania z tytułu dostawy towarów oraz
		 * świadczenia usług na terytorium kraju, opodatkowanych stawką 5%,
		 * oraz korekty dokonanej zgodnie z art. 89a ust. 1 i 4 ustawy
		 */
		/**
		 * P_16
		 * Wysokość podatku należnego z tytułu dostawy towarów oraz
		 * świadczenia usług na terytorium kraju, opodatkowanych stawką 5%,
		 * oraz korekty dokonanej zgodnie z art. 89a ust. 1 i 4 ustawy
		 */
		/**
		 * P_17
		 * Wysokość podstawy opodatkowania z tytułu dostawy towarów oraz
		 * świadczenia usług na terytorium kraju, opodatkowanych stawką 7%
		 * albo 8%, oraz korekty dokonanej zgodnie z art. 89a ust.
		 * 1 i 4 ustawy
		 */
		/**
		 * P_18
		 * Wysokość podatku należnego z tytułu dostawy towarów oraz
		 * świadczenia usług na terytorium kraju, opodatkowanych stawką 7%
		 * albo 8%, oraz korekty dokonanej zgodnie z art. 89a ust.
		 * 1 i 4 ustawy
		 */
		/**
		 * P_19
		 *
		 * Wysokość podstawy opodatkowania z tytułu dostawy towarów oraz
		 * świadczenia usług na terytorium kraju, opodatkowanych stawką 22%
		 * albo 23%, oraz korekty dokonanej zgodnie z art. 89a ust.
		 * 1 i 4 ustawy
		 */
		for ( $income_vat_rate_binding = 10; $income_vat_rate_binding < 20; $income_vat_rate_binding++ ) {
			$field_name                 = sprintf( 'K_%d', $income_vat_rate_binding );
			$target_field_name          = sprintf( 'P_%d', $income_vat_rate_binding );
			$args[ $target_field_name ] = array(
				'integer'    => 0,
				'fractional' => 0,
			);
			foreach ( $args['incomes'] as $one_income ) {
				if ( isset( $one_income[ $field_name ] ) ) {
					foreach ( array( 'integer', 'fractional' ) as $money_part ) {
						if ( isset( $one_income['money'][ $money_part ] ) ) {
							$args[ $target_field_name ][ $money_part ] += $one_income['money'][ $money_part ];
						}
					}
				}
			}
			$s = $args[ $target_field_name ]['integer'] + intval( $args[ $target_field_name ]['fractional'] / 100 );
			if ( 50 < $args[ $target_field_name ]['fractional'] % 100 ) {
				$s += 1;
			}
			if ( empty( $s ) ) {
				unset( $args[ $target_field_name ] );
			} else {
				$args[ $target_field_name ] = $s;
			}
		}
		/**
		 * P_37
		 *
		 * Łączna wysokość podstawy opodatkowania. Suma kwot z P_10, P_11,
		 * P_13, P_15, P_17, P_19, P_21, P_22, P_23, P_25, P_27, P_29, P_31
		 */
		$args['P_37'] = 0;
		$keys         = array( 'P_10', 'P_11', 'P_13', 'P_15', 'P_17', 'P_18', 'P_19', 'P_21', 'P_22', 'P_23', 'P_25', 'P_27', 'P_29', 'P_31' );
		foreach ( $keys as $key ) {
			if ( isset( $args[ $key ] ) ) {
				$args['P_37'] += $args[ $key ];
			}
		}
		/**
		 * P_20
		 *
		 * Wysokość podatku należnego z tytułu dostawy towarów oraz
		 * świadczenia usług na terytorium kraju, opodatkowanych stawką 22%
		 * albo 23%, oraz korekty dokonanej zgodnie z art. 89a ust.
		 * 1 i 4 ustawy
		 */
		$args['P_20'] = $this->sum['vat_income']['intval'];
		/**
		 * P_38
		 *
		 * Łączna wysokość podatku należnego. Suma kwot z P_16, P_18, P_20,
		 * P_24, P_26, P_28, P_30, P_32, P_33, P_34 pomniejszona o kwotę
		 * z P_35 i P_36
		 */
		$args['P_38'] = 0;
		$keys         = array( 'P_16', 'P_18', 'P_20', 'P_24', 'P_26', 'P_28', 'P_30', 'P_32', 'P_33', 'P_34' );
		foreach ( $keys as $key ) {
			if ( isset( $args[ $key ] ) ) {
				$args['P_38'] += $args[ $key ];
			}
		}
		$keys = array( 'P_35', 'P_36' );
		foreach ( $keys as $key ) {
			if ( isset( $args[ $key ] ) ) {
				$args['P_38'] -= $args[ $key ];
			}
		}
		/**
		 * P_42
		 *
		 * Wartość netto z tytułu nabycia pozostałych towarów i usług
		 */
		$args['P_42'] = $this->sum['expense_netto']['intval'];
		$args['P_43'] = $this->sum['vat_expense']['intval'];
		/**
		 * P_48
		 *
		 * Łączna wysokość podatku naliczonego do odliczenia. Suma kwot
		 * z P_39, P_41, P_43, P_44, P_45, P_46 i P_47
		 */
		$args['P_48'] = 0;
		foreach ( array( 'P_39', 'P_41', 'P_43', 'P_44', 'P_45', 'P_46', 'P_47' ) as $key ) {
			if ( isset( $args[ $key ] ) ) {
				$args['P_48'] += $args[ $key ];
			}
		}
		/**
		 * P_51
		 *
		 * Wysokość podatku podlegająca wpłacie do urzędu skarbowego
		 *
		 * Jeżeli różnica kwot pomiędzy P_38 i P_48 jest większa od 0, wówczas
		 * P_51 = P_38 - P_48 - P_49 - P_50, w przeciwnym wypadku należy wykazać 0.
		 */
		$args['P_51'] = $args['P_38'];
		foreach ( array( 'P_48', 'P_49', 'P_50' ) as $key ) {
			if ( isset( $args[ $key ] ) ) {
				$args['P_51'] -= $args[ $key ];
			}
		}
		$args['P_51'] = max( 0, $args['P_51'] );
		/**
		 * P_53
		 *
		 * Wysokość nadwyżki podatku naliczonego nad należnym
		 *
		 * Jeżeli różnica kwot pomiędzy P_48 i P_38 jest większa lub równa 0,
		 * wówczas P_53 = P_48 - P_38 + P_52, w przeciwnym wypadku należy
		 * wykazać 0.
		 */
		$args['P_53'] = $args['P_48'];
		if ( isset( $args['P_38'] ) ) {
			$args['P_53'] -= $args['P_38'];
		}
		if ( isset( $args['P_52'] ) ) {
			$args['P_53'] += $args['P_52'];
		}
		$args['P_53'] = max( 0, $args['P_53'] );
		/**
		 * P_62
		 *
		 * Wysokość nadwyżki podatku naliczonego nad należnym do przeniesienia
		 * na następny okres rozliczeniowy
		 *
		 * Od kwoty wykazanej w P_53 należy odjąć kwotę z P_54
		 */
		if ( 0 < $args['P_53'] ) {
			$args['P_62'] = $args['P_53'];
			if ( isset( $args['P_54'] ) ) {
				$args['P_62'] -= $args['P_54'];
			}
		}
		ob_start();
		$this->get_template( $template, 'header', $args );
		$this->get_template( $template, 'head', $args );
		if ( $this->is_person() ) {
			$this->get_template( $template, 'person', $args );
		} else {
			$this->get_template( $template, 'company', $args );
		}
		$this->get_template( $template, 'summary', $args );
		echo '<tns:Ewidencja>';
		/**
		 * income
		 */
		if ( empty( $args['incomes'] ) ) {
				$this->get_template( $template, 'incomes-empty', $args );
		} else {
			foreach ( $args['incomes'] as $one ) {
				$this->get_template( $template, 'income', $one );
			}
			if ( 0 < count( $args['incomes'] ) ) {
				$integer    = $this->sum['vat_income']['integer'];
				$fractional = $this->sum['vat_income']['fractional'];
				$integer   += ( $fractional - $fractional % 100 ) / 100;
				$fractional = $fractional % 100;
				$atts       = array(
					'sum'  => sprintf( '%d.%02d', $integer, $fractional ),
					'rows' => count( $args['incomes'] ),
				);
				$this->get_template( $template, 'incomes-summary', $atts );
			}
		}
		/**
		 * expense
		 */
		foreach ( $args['expenses'] as $one ) {
			$this->get_template( $template, 'expense', $one );
		}
		if ( 0 < count( $args['expenses'] ) ) {
			$integer    = $this->sum['vat_expense']['integer'];
			$fractional = $this->sum['vat_expense']['fractional'];
			$integer   += ( $fractional - $fractional % 100 ) / 100;
			$fractional = $fractional % 100;
			$atts       = array(
				'sum'  => sprintf( '%d.%02d', $integer, $fractional ),
				'rows' => count( $args['expenses'] ),
			);
			$this->get_template( $template, 'expenses-summary', $atts );
		}
		echo '</tns:Ewidencja>';
		$this->get_template( $template, 'footer', $args );
		/**
		 * file
		 */
		$filename = sprintf( 'jpk-v7m-%s.xml', $year_month );
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/xml' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		ob_get_contents();
		exit;
	}
}

