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

abstract class iworks_kpir_jpk {

	protected $template_base = '';

	protected $options;

	protected $contractors = array();
	protected $sum         = array(
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

	protected  function __construct() {
		global $iworks_kpir_options;
		$this->options       = $iworks_kpir_options;
		$this->template_base = dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/template-parts';

	}

	protected function get_template( $_template_base, $_template_file = '', $args = array() ) {
		$value = get_template_part( 'kpir/' . $_template_base, $_template_file, $args );
		if ( ! empty( $value ) ) {
			echo $value;
			return;
		}
		$file = sprintf(
			'%s/%s%s%s.php',
			$this->template_base,
			$_template_base,
			empty( $_template_file ) ? '' : '/',
			$_template_file
		);
		load_template( $file, false, $args );
	}

	public function options_get_field_by_type( $type, $name, $value = '', $args = array() ) {
		echo $this->options->get_field_by_type( $type, $name, $value, $args );
	}

	protected function validate_year_month( $data ) {
		if ( is_string( $data ) && preg_match( '/^\d{4}\-\d{2}$/', $data ) ) {
			return $data;
		}
		return new WP_Error( 'wrong-input', __( 'Wrong date, please try again!', 'kpir' ) );
	}

	/**
	 * convert special chars
	 */
	protected function convert_chars( $text ) {
		$text = preg_replace( '/\&/', '&amp;', $text );
		return $text;
	}

	protected function is_person() {
		return true;
	}

	protected function get_contractor( $contractor_id ) {
		if ( ! isset( $this->contractors[ $contractor_id ] ) ) {
			$nip = get_post_meta( $contractor_id, 'iworks_kpir_contractor_data_nip', true );
			if ( empty( $nip ) ) {
				$nip = 'brak';
			} else {
				$nip = preg_replace( '/[^\d]+/', '', $nip );
			}
			$name                                = get_post_meta( $contractor_id, 'iworks_kpir_contractor_data_full_name', true );
			$address                             = get_post_meta( $contractor_id, 'iworks_kpir_contractor_data_street1', true );
			$address                            .= ', ' . get_post_meta( $contractor_id, 'iworks_kpir_contractor_data_zip', true );
			$address                            .= ' ' . get_post_meta( $contractor_id, 'iworks_kpir_contractor_data_city', true );
			$address                            .= ', ' . get_post_meta( $contractor_id, 'iworks_kpir_contractor_data_country', true );
			$this->contractors[ $contractor_id ] = array(
				'nip'     => $nip,
				'name'    => $name,
				'address' => preg_replace( '/ {2,}/', ' ', $address ),
			);
		}
		return $this->contractors[ $contractor_id ];
	}

	protected function expense_row( $data ) {
		$ID                                        = $data['ID'];
		$data['sale_date']                         = $data['create_date'] = date( 'Y-m-d', get_post_meta( $ID, 'iworks_kpir_basic_date', true ) );
		$data['invoice_number']                    = get_the_title();
		$is_car_related                            = get_post_meta( $ID, 'iworks_kpir_expense_car', true );
		$money                                     = get_post_meta( $ID, 'iworks_kpir_expense_purchase', true );
		$data['K_45']                              = sprintf( '%d.%02d', $money['integer'], $money['fractional'] );
		$this->sum['expense_netto']['integer']    += $money['integer'];
		$this->sum['expense_netto']['fractional'] += $money['fractional'];
		/**
		 * VAT
		 */
		$money = get_post_meta( $ID, 'iworks_kpir_expense_vat', true );
		if ( 'no' !== $is_car_related ) {
			$v                   = round( ( $money['integer'] / 2 ) * 100 + $money['fractional'] / 2 );
			$money['fractional'] = $v % 100;
			$money['integer']    = round( ( $v - $money['fractional'] ) / 100 );
		}
		$data['K_46'] = sprintf( '%d.%02d', isset( $money['integer'] ) ? $money['integer'] : 0, isset( $money['fractional'] ) ? $money['fractional'] : 0 );
		if ( isset( $money['integer'] ) ) {
			$this->sum['vat_expense']['integer'] += $money['integer'];
		}
		if ( isset( $money['fractional'] ) ) {
			$this->sum['vat_expense']['fractional'] += $money['fractional'];
		}
		return $data;
	}

	protected function income_row( $data ) {
		$ID                     = $data['ID'];
		$data['sale_date']      = $data['create_date'] = date( 'Y-m-d', get_post_meta( $ID, 'iworks_kpir_basic_date', true ) );
		$data['invoice_number'] = get_the_title();
		$type                   = get_post_meta( $ID, 'iworks_kpir_income_vat_type', true );
		switch ( $type ) {
			case 'c01':
				$money                              = get_post_meta( $ID, 'iworks_kpir_income_sale', true );
				$data['K_11']                       = sprintf( '%d.%02d', $money['integer'], $money['fractional'] );
				$this->sum['income']['integer']    += $money['integer'];
				$this->sum['income']['fractional'] += $money['fractional'];
				break;
			case 'c06':
				$money                              = get_post_meta( $ID, 'iworks_kpir_income_sale', true );
				$data['K_19']                       = sprintf( '%d.%02d', $money['integer'], $money['fractional'], );
				$this->sum['income']['integer']    += $money['integer'];
				$this->sum['income']['fractional'] += $money['fractional'];
				/**
				 * VAT
				 */
				$money                                  = get_post_meta( $ID, 'iworks_kpir_income_vat', true );
				$data['K_20']                           = sprintf( '%d.%02d', $money['integer'], $money['fractional'] );
				$this->sum['vat_income']['integer']    += $money['integer'];
				$this->sum['vat_income']['fractional'] += $money['fractional'];
				break;
		}
		return $data;
	}
}

