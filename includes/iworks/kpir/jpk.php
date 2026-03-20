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

/**
 * Abstract base class for JPK (Jednolity Plik Kontrolny) report generation.
 *
 * This class provides common functionality for generating Polish tax reports
 * including JPK VAT and JPK V7M formats. Handles contractor data, money calculations,
 * and XML output formatting.
 *
 * @package KPIR
 * @subpackage JPK
 * @since 1.0.0
 */
abstract class iworks_kpir_jpk {

	/**
	 * Base path for template files.
	 *
	 * @var string
	 */
	protected $template_base = '';

	/**
	 * Plugin options instance.
	 *
	 * @var object
	 */
	protected $options;

	/**
	 * Cached contractor data.
	 *
	 * @var array
	 */
	protected $contractors = array();

	/**
	 * Financial sums for report calculations.
	 *
	 * @var array
	 */
	protected $sum = array(
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

	/**
	 * Class constructor.
	 *
	 * Initializes the JPK base class with global options and template path.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function __construct() {
		global $iworks_kpir_options;
		$this->options       = $iworks_kpir_options;
		$this->template_base = dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/template-parts';
	}

	/**
	 * Load and display template file.
	 *
	 * Attempts to load template from theme first, then falls back to plugin templates.
	 *
	 * @since 1.0.0
	 *
	 * @param string $_template_base Base template name.
	 * @param string $_template_file Template file name (optional).
	 * @param array  $args           Arguments to pass to template.
	 * @return void
	 */
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

	/**
	 * Output form field by type.
	 *
	 * Wrapper method to generate form fields using the options class.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type  Field type.
	 * @param string $name  Field name.
	 * @param string $value Field value (optional).
	 * @param array  $args  Additional field arguments.
	 * @return void
	 */
	public function options_get_field_by_type( $type, $name, $value = '', $args = array() ) {
		echo $this->options->get_field_by_type( $type, $name, $value, $args );
	}

	/**
	 * Validate year-month format.
	 *
	 * Checks if the input string matches the YYYY-MM format.
	 *
	 * @since 1.0.0
	 *
	 * @param string $data Date string to validate.
	 * @return string|WP_Error Validated date string or WP_Error on failure.
	 */
	protected function validate_year_month( $data ) {
		if ( is_string( $data ) && preg_match( '/^\d{4}\-\d{2}$/', $data ) ) {
			return $data;
		}
		return new WP_Error( 'wrong-input', __( 'Wrong date, please try again!', 'kpir' ) );
	}

	/**
	 * Convert special characters to XML-safe format.
	 *
	 * Replaces ampersands with XML entities to prevent parsing errors.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text Text to convert.
	 * @return string Converted text with XML-safe characters.
	 */
	protected function convert_chars( $text ) {
		$text = preg_replace( '/\&/', '&amp;', $text );
		return $text;
	}

	/**
	 * Check if entity is a person (not a company).
	 *
	 * Abstract method to be implemented by child classes.
	 *
	 * @since 1.0.0
	 *
	 * @return bool False by default, to be overridden in child classes.
	 */
	protected function is_person() {
		return false;
	}

	/**
	 * Get contractor data by ID.
	 *
	 * Retrieves and caches contractor information including NIP, name, and address.
	 * Cleans up NIP to contain only digits and formats address properly.
	 *
	 * @since 1.0.0
	 *
	 * @param int $contractor_id Contractor post ID.
	 * @return array Contractor data with nip, name, and address fields.
	 */
	protected function get_contractor( $contractor_id ) {
		if ( ! isset( $this->contractors[ $contractor_id ] ) ) {
			$nip = get_post_meta( $contractor_id, 'iworks_kpir_contractor_data_nip', true );
			if ( empty( $nip ) ) {
				$nip = 'brak';
			} else {
				$nip = preg_replace( '/[^\d]+/', '', $nip );
			}
			$name     = get_post_meta( $contractor_id, 'iworks_kpir_contractor_data_full_name', true );
			$address  = get_post_meta( $contractor_id, 'iworks_kpir_contractor_data_street1', true );
			$address .= ', ' . get_post_meta( $contractor_id, 'iworks_kpir_contractor_data_zip', true );
			$address .= ' ' . get_post_meta( $contractor_id, 'iworks_kpir_contractor_data_city', true );
			$address .= ', ' . get_post_meta( $contractor_id, 'iworks_kpir_contractor_data_country', true );
			/**
			 * add
			 */
			$this->contractors[ $contractor_id ] = array(
				'nip'     => $nip,
				'name'    => $name,
				'address' => preg_replace( '/ {2,}/', ' ', $address ),
			);
		}
		return $this->contractors[ $contractor_id ];
	}

	/**
	 * Process expense row data for JPK reports.
	 *
	 * Calculates expense amounts, VAT, and handles car-related expense adjustments.
	 * Applies different VAT deduction rates based on car usage percentage.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Invoice data containing ID and other information.
	 * @return array Processed expense data with calculated values.
	 */
	protected function expense_row( $data ) {
		$ID                     = $data['ID'];
		$data['sale_date']      = $data['create_date'] = date( 'Y-m-d', get_post_meta( $ID, 'iworks_kpir_basic_date', true ) );
		$data['invoice_number'] = get_the_title();
		$is_car_related         = get_post_meta( $ID, 'iworks_kpir_expense_car', true );
		/**
		 * vat rate
		 */
		$data['vat_rate'] = get_post_meta( $ID, 'iworks_kpir_expense_vat_rate', true );
		if (
			empty( $data['vat_rate'] )
			|| ! preg_match( '/^r(23|08|05|00|zw)$/', $data['vat_rate'] )
		) {
			$data['vat_rate'] = 'r23';
		}
		/**
		 * money & VAT
		 */
		$data['money'] = $money = get_post_meta( $ID, 'iworks_kpir_expense_purchase', true );
		$data['vat']   = $vat   = get_post_meta( $ID, 'iworks_kpir_expense_vat', true );
		if ( empty( $vat ) || is_string( $vat ) ) {
			$vat = $data['vat'] = array(
				'integer'    => 0,
				'fractional' => 0,
			);
		}
		switch ( $is_car_related ) {
			case '20':
			case '75':
				/**
				 * Cut vat to half at first and add it into base
				 * we can return only half of VAT
				 */
				$vat_half_rest        = intval( 50 * ( $vat['integer'] % 2 ) );
				$money['integer']    += intval( $vat['integer'] / 2 );
				$money['fractional'] += intval( $vat['fractional'] / 2 ) + $vat_half_rest;
				/**
				 * VAT
				 */
				$vat['integer']    = intval( $vat['integer'] / 2 );
				$vat['fractional'] = intval( $vat['fractional'] / 2 ) + $vat_half_rest;
				/**
				 * recalculate if rest > 100
				 */
				if ( 100 < $vat['fractional'] ) {
					$vat['fractional'] = $v % 100;
					$vat['integer']    = round( ( $v - $vat['fractional'] ) / 100 );
				}
				/**
				 * Cut costs by factor
				 */
				$factor              = intval( $is_car_related );
				$v                   = round( $factor * $money['integer'] + $factor * $money['fractional'] / 100 );
				$money['fractional'] = $v % 100;
				$money['integer']    = round( ( $v - $money['fractional'] ) / 100 );
				break;
			case 'yes':
				if ( is_array( $vat ) ) {
					$v                 = round( ( $vat['integer'] / 2 ) * 100 + $vat['fractional'] / 2 );
					$vat['fractional'] = $v % 100;
					$vat['integer']    = round( ( $v - $vat['fractional'] ) / 100 );
				}
				break;
		}
		$data['K_42'] = sprintf( '%d.%02d', $money['integer'], $money['fractional'] );
		$data['K_43'] = sprintf( '%d.%02d', isset( $vat['integer'] ) ? $vat['integer'] : 0, isset( $vat['fractional'] ) ? $vat['fractional'] : 0 );
		/**
		 * Sum
		 */
		if ( isset( $money['integer'] ) ) {
			$this->sum['expense_netto']['integer'] += intval( $money['integer'] );
		}
		if ( isset( $money['fractional'] ) ) {
			$this->sum['expense_netto']['fractional'] += intval( $money['fractional'] );
		}
		if ( isset( $vat['integer'] ) ) {
			$this->sum['vat_expense']['integer'] += intval( $vat['integer'] );
		}
		if ( isset( $vat['fractional'] ) ) {
			$this->sum['vat_expense']['fractional'] += intval( $vat['fractional'] );
		}
		return $data;
	}

	/**
	 * Process income row data for JPK reports.
	 *
	 * Calculates income amounts and VAT based on VAT type.
	 * Handles cash method PIT by using cash date instead of invoice date.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Invoice data containing ID and other information.
	 * @return array Processed income data with calculated values.
	 */
	protected function income_row( $data ) {
		$ID                     = $data['ID'];
		$data['sale_date']      = $data['create_date'] = date( 'Y-m-d', get_post_meta( $ID, 'iworks_kpir_basic_date', true ) );
		/**
		 * sale date for cash method is a cash date
		 */
		if ( boolval( get_option( 'iworks_kpir_cash_pit', false ) ) ) {
			$data['sale_date'] = date( 'Y-m-d', get_post_meta( $ID, 'iworks_kpir_basic_date_of_cash', true ) );
		}
		$data['invoice_number'] = get_the_title();
		$type                   = get_post_meta( $ID, 'iworks_kpir_income_vat_type', true );
		$data['money']          = $money                              = get_post_meta( $ID, 'iworks_kpir_income_sale', true );
		switch ( $type ) {
			case 'c00':
				$data['K_10']                       = sprintf( '%d.%02d', $money['integer'], $money['fractional'] );
				$this->sum['income']['integer']    += $money['integer'];
				$this->sum['income']['fractional'] += $money['fractional'];
				break;
			case 'c01':
				$data['K_11']                       = sprintf( '%d.%02d', $money['integer'], $money['fractional'] );
				$this->sum['income']['integer']    += $money['integer'];
				$this->sum['income']['fractional'] += $money['fractional'];
				break;
			case 'c06':
				$data['K_19']                       = sprintf( '%d.%02d', $money['integer'], $money['fractional'] );
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

	/**
	 * Normalize money values to proper decimal format.
	 *
	 * Converts money array with separate integer and fractional parts
	 * into a normalized format and calculates the integer value.
	 *
	 * @since 1.0.0
	 *
	 * @param array $money Money array with integer and fractional parts.
	 * @return array Normalized money array with additional intval field.
	 */
	protected function normalize_money( $money ) {
		$money['integer']   += ( $money['fractional'] - $money['fractional'] % 100 ) / 100;
		$money['fractional'] = $money['fractional'] % 100;
		$money['intval']     = round( $money['integer'] + $money['fractional'] / 100 );
		return $money;
	}
}

