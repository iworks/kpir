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

class iworks_kpir_jpk_vat_3 {

	private $options;
	private $contractors = array();
	private $sum         = array(
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

	public function __construct() {
		global $iworks_kpir_options;
		$this->options = $iworks_kpir_options;
	}

	/**
	 * Get JPK VAT(3) xml
	 *
	 * @since 1.0.0
	 */
	public function get_xml( $kpir ) {
		if (
			! isset( $_REQUEST['nonce'] )
			|| ! isset( $_REQUEST['purpose'] )
			|| ! isset( $_REQUEST['m'] )
		) {
			return;
		}
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'kpir-jpk-vat-3' ) ) {
			return;
		}
		/**
		 * validate input
		 */
		$year_month = filter_input( INPUT_GET, 'm', FILTER_SANITIZE_STRING );
		$year_month = $this->validate_year_month( $year_month );
		if ( is_wp_error( $year_month ) ) {
			die( $year_month->get_error_message() );
		}
		$purpose = filter_input( INPUT_GET, 'purpose', FILTER_VALIDATE_INT );
		/**
		 * produce
		 */
		$data               = '';
		$data              .= $this->template_header();
		$data              .= $this->template_section_head( $purpose, $year_month );
		$data              .= $this->template_section_company();
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
				$ID            = get_the_ID();
				$type          = get_post_meta( $ID, 'iworks_kpir_basic_type', true );
				$contractor_id = get_post_meta( $ID, $cf_contractor_name, true );
				switch ( $type ) {
					case 'expense':
						$expenses_counter++;
						$expenses .= $this->template_row_expense( $expenses_counter, $ID, $contractor_id );
						break;
					case 'income':
						$incomes_counter++;
						$incomes .= $this->template_row_income( $incomes_counter, $ID, $contractor_id );
						break;
				}
			}
		}
		$data    .= $incomes;
		$data    .= $this->template_summary_incomes( $incomes_counter );
		$data    .= $expenses;
		$data    .= $this->template_summary_expenses( $expenses_counter );
		$data    .= $this->template_footer();
		$filename = sprintf( 'jpk-vat-%s.xml', $year_month );
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/xml' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		echo $data;
		exit;
	}

	public function show( $kpir ) {
		global $wpdb;
		$post_type_object = $kpir->get_post_type_invoice();
		$sql              = "select distinct meta_value from {$wpdb->postmeta} where meta_key = '{$post_type_object->get_custom_field_year_month_name()}'order by meta_value desc";
		$values           = $wpdb->get_col( $sql );
		if ( empty( $values ) ) {
			_e( 'There is no entries to create JPK VAT file.', 'kpir' );
			return;
		}
		echo '<form id="kpir-jpk-vat-3" action="edit.php">';
		echo $this->options->get_field_by_type( 'hidden', 'action', 'iworks_kpir_jpk_vat_3' );
		echo $this->options->get_field_by_type( 'hidden', 'post_type', 'iworks_kpir_invoice' );
		echo $this->options->get_field_by_type( 'hidden', 'page', 'iworks_kpir_jpk_vat_3' );
		wp_nonce_field( 'kpir-jpk-vat-3', 'nonce', false );
		echo '<table class="form-table">';
		echo '<tbody>';
		/**
		 * the purpose of the declaration
		 */
		echo '<tr>';
		printf(
			'<th scope="row"><label for="%s">%s</label></th>',
			esc_attr( 'purpose' ),
			esc_html__( 'Purpose', 'kpir' )
		);
		echo '<td>';
		$args = array(
			'min'   => 0,
			'class' => array( 'small-text' ),
		);
		echo $this->options->get_field_by_type( 'number', 'purpose', 0, $args );
		echo '</td>';
		echo '</tr>';
		/**
		 * month
		 */
		echo '<tr>';
		printf(
			'<th scope="row"><label for="%s">%s</label></th>',
			esc_attr( 'm' ),
			esc_html__( 'Month', 'kpir' )
		);
		echo '<td>';
		$args = array(
			'options' => array(
				'-' => __( 'Select month', 'kpir' ),
			),
		);
		foreach ( $values as $value ) {
			$args['options'][ $value ] = $value;
		}
		echo $this->options->get_field_by_type( 'select', 'm', null, $args );
		echo '</td>';
		echo '</tr>';
		/**
		 * close table
		 */
		echo '</tbody>';
		echo '</table>';
		$args = array(
			'class' => array( 'button', 'button-primary' ),
		);
		echo $this->options->get_field_by_type( 'submit', '=iworks_kpir_jpk_vat_3', __( 'Get XML', 'kpir' ), $args );
		// echo $this->options->get_field_by_type( 'button', 'iworks_kpir_jpk_vat_3', __( 'Get XML', 'kpir' ), $args );
		echo '</form>';
	}

	private function template_header() {
		$data  = '<?xml version="1.0" encoding="UTF-8"?>';
		$data .= PHP_EOL;
		$data  = '<tns:JPK xmlns:etd="http://crd.gov.pl/xml/schematy/dziedzinowe/mf/2016/01/25/eD/DefinicjeTypy/" xmlns:tns="http://jpk.mf.gov.pl/wzor/2017/11/13/1113/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
		$data .= PHP_EOL;
		return $data;
	}

	private function template_footer() {
		$data = '</tns:JPK>';
		return $data;
	}

	/**
	 * tns:CelZlozenia - 1- złożenie pliku JPK_VAT
	 *               2- korekta pliku JPK_VAT
	 *               3... kolejny numer korekty
	 * tns:DataOd - Miesiąc, za który składany jest JPK_VAT
	 * tns:DataOd - Miesiąc, za który składany jest JPK_VAT
	 */
	private function template_section_head( $purpose, $month ) {
		$date  = strtotime( $month );
		$data  = '   <tns:Naglowek>
        <tns:KodFormularza wersjaSchemy="1-1" kodSystemowy="JPK_VAT (3)">JPK_VAT</tns:KodFormularza>
        <tns:WariantFormularza>3</tns:WariantFormularza>
        <tns:CelZlozenia>%d</tns:CelZlozenia>
        <tns:DataWytworzeniaJPK>%s</tns:DataWytworzeniaJPK>
        <tns:DataOd>%s</tns:DataOd>
        <tns:DataDo>%s</tns:DataDo>
        <tns:NazwaSystemu>KPiR PLUGIN_VERSION</tns:NazwaSystemu>
    </tns:Naglowek>';
		$data .= PHP_EOL;
		$data .= PHP_EOL;
		$data  = sprintf(
			$data,
			$purpose,
			date( 'Y-m-d\TH:m:s\Z', time() ),
			date( 'Y-m-01', $date ),
			date( 'Y-m-t', $date )
		);
		return $data;
	}

	private function template_section_company() {
		$data  = '   <tns:Podmiot1>
            <tns:NIP>%s</tns:NIP>
            <tns:PelnaNazwa>%s</tns:PelnaNazwa>
            <tns:Email>%s</tns:Email>
    </tns:Podmiot1>';
		$data .= PHP_EOL;
		$data .= PHP_EOL;
		$data  = sprintf(
			$data,
			preg_replace( '/[^\d]+/', '', get_option( 'iworks_kpir_nip' ) ),
			$this->convert_chars( get_option( 'iworks_kpir_name' ) ),
			get_option( 'iworks_kpir_email' )
		);
		return $data;
	}

	/**
	 * tns:K_43 Kwota netto – Nabycie towarów i usług zaliczanych u podatnika do środków trwałych (pole opcjonalne)
	 * tns:K_44 Kwota podatku naliczonego – Nabycie towarów i usług zaliczanych u podatnika do środków trwałych (pole opcjonalne) * tns:K_45 Kwota netto – Nabycie towarów i usług pozostałych (pole opcjonalne)
	 * tns:K_46 Kwota podatku naliczonego – Nabycie towarów i usług pozostałych (pole opcjonalne)
	 * tns:K_47 Korekta podatku naliczonego od nabycia środków trwałych (pole opcjonalne)
	 * tns:K_48 Korekta podatku naliczonego od pozostałych nabyć (pole opcjonalne)
	 * tns:K_49 Korekta podatku naliczonego, o której mowa w art. 89b ust. 1 ustawy (pole opcjonalne)
	 * tns:K_50 Korekta podatku naliczonego, o której mowa w art. 89b ust. 4 ustawy (pole opcjonalne)
	 */
	private function template_row_expense( $counter, $ID, $contractor_id ) {
		$data                                   = '<tns:ZakupWiersz>
    <tns:LpZakupu>%d</tns:LpZakupu>
    <tns:NrDostawcy>%s</tns:NrDostawcy>
    <tns:NazwaDostawcy>%s</tns:NazwaDostawcy>
    <tns:AdresDostawcy>%s</tns:AdresDostawcy>
    <tns:DowodZakupu>%s</tns:DowodZakupu>
    <tns:DataZakupu>%s</tns:DataZakupu>
%s</tns:ZakupWiersz>';
		$data                                  .= PHP_EOL;
		$data                                  .= PHP_EOL;
		$contractor                             = $this->get_contractor( $contractor_id );
		$sale_date                              = $create_date = date( 'Y-m-d', get_post_meta( $ID, 'iworks_kpir_basic_date', true ) );
		$number                                 = get_the_title();
		$k                                      = '';
		$is_car_related                         = get_post_meta( $ID, 'iworks_kpir_expense_car', true );
		$money                                  = get_post_meta( $ID, 'iworks_kpir_expense_purchase', true );
		$k                                     .= sprintf(
			'   <tns:K_45>%d.%02d</tns:K_45>%s',
			$money['integer'],
			$money['fractional'],
			PHP_EOL
		);
		$this->sum['expense_netto']['integer'] += $money['integer'];
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
		$k .= sprintf(
			'   <tns:K_46>%d.%02d</tns:K_46>%s',
			isset( $money['integer'] ) ? $money['integer'] : 0,
			isset( $money['fractional'] ) ? $money['fractional'] : 0,
			PHP_EOL
		);
		if ( isset( $money['integer'] ) ) {
			$this->sum['vat_expense']['integer'] += $money['integer'];
		}
		if ( isset( $money['fractional'] ) ) {
			$this->sum['vat_expense']['fractional'] += $money['fractional'];
		}
		$money[] = $this->convert( $this->sum['vat_expense'] );
		$data    = sprintf(
			$data,
			$counter,
			$contractor['nip'],
			$this->convert_chars( $contractor['name'] ),
			$this->convert_chars( $contractor['address'] ),
			$number,
			$create_date,
			$k
		);
		return $data;
	}

	private function convert( $value ) {
		$integer    = $value['integer'];
		$fractional = $value['fractional'];
		/**
		 * recalculate fractional over 100
		 */
		$integer   += ( $fractional - $fractional % 100 ) / 100;
		$fractional = $fractional % 100;
		return array(
			'integer'    => $integer,
			'fractional' => $fractional,
		);
	}

	private function template_row_income( $counter, $ID, $contractor_id ) {
		$data       = '<tns:SprzedazWiersz>
    <tns:LpSprzedazy>%d</tns:LpSprzedazy>
    <tns:NrKontrahenta>%s</tns:NrKontrahenta>
    <tns:NazwaKontrahenta>%s</tns:NazwaKontrahenta>
    <tns:AdresKontrahenta>%s</tns:AdresKontrahenta>
    <tns:DowodSprzedazy>%s</tns:DowodSprzedazy>
    <tns:DataWystawienia>%s</tns:DataWystawienia>
%s</tns:SprzedazWiersz>';
		$data      .= PHP_EOL;
		$data      .= PHP_EOL;
		$contractor = $this->get_contractor( $contractor_id );
		$sale_date  = $create_date = date( 'Y-m-d', get_post_meta( $ID, 'iworks_kpir_basic_date', true ) );
		$number     = get_the_title();
		$k          = '';
		$type       = get_post_meta( $ID, 'iworks_kpir_income_vat_type', true );
		switch ( $type ) {
			case 'c01':
				$money                              = get_post_meta( $ID, 'iworks_kpir_income_sale', true );
				$k                                 .= sprintf(
					'   <tns:K_11>%d.%02d</tns:K_11>%s',
					$money['integer'],
					$money['fractional'],
					PHP_EOL
				);
				$this->sum['income']['integer']    += $money['integer'];
				$this->sum['income']['fractional'] += $money['fractional'];
				break;
			case 'c06':
				$money                              = get_post_meta( $ID, 'iworks_kpir_income_sale', true );
				$k                                 .= sprintf(
					'   <tns:K_19>%d.%02d</tns:K_19>%s',
					$money['integer'],
					$money['fractional'],
					PHP_EOL
				);
				$this->sum['income']['integer']    += $money['integer'];
				$this->sum['income']['fractional'] += $money['fractional'];
				/**
			 * VAT
			 */
				$money                                  = get_post_meta( $ID, 'iworks_kpir_income_vat', true );
				$k                                     .= sprintf(
					'   <tns:K_20>%d.%02d</tns:K_20>%s',
					$money['integer'],
					$money['fractional'],
					PHP_EOL
				);
				$this->sum['vat_income']['integer']    += $money['integer'];
				$this->sum['vat_income']['fractional'] += $money['fractional'];
				break;
		}
		$data = sprintf(
			$data,
			$counter,
			$contractor['nip'],
			$this->convert_chars( $contractor['name'] ),
			$this->convert_chars( $contractor['address'] ),
			$number,
			$create_date,
			$k
		);
		return $data;
	}

	private function template_summary_incomes( $counter ) {
		$data  = '<tns:SprzedazCtrl>
    <tns:LiczbaWierszySprzedazy>%d</tns:LiczbaWierszySprzedazy>
    <tns:PodatekNalezny>%d.%d</tns:PodatekNalezny>
</tns:SprzedazCtrl>';
		$data .= PHP_EOL;
		$data .= PHP_EOL;
		/**
		 * get
		 */
		$integer    = $this->sum['vat_income']['integer'];
		$fractional = $this->sum['vat_income']['fractional'];
		/**
		 * recalculate fractional over 100
		 */
		$integer   += ( $fractional - $fractional % 100 ) / 100;
		$fractional = $fractional % 100;
		$data       = sprintf(
			$data,
			$counter,
			$integer,
			$fractional
		);
		return $data;
	}

	private function template_summary_expenses( $counter ) {
		$data  = '<tns:ZakupCtrl>
    <tns:LiczbaWierszyZakupow>%d</tns:LiczbaWierszyZakupow>
    <tns:PodatekNaliczony>%d.%d</tns:PodatekNaliczony>
</tns:ZakupCtrl>';
		$data .= PHP_EOL;
		$data .= PHP_EOL;
		/**
		 * get
		 */
		$integer    = $this->sum['vat_expense']['integer'];
		$fractional = $this->sum['vat_expense']['fractional'];
		/**
		 * recalculate fractional over 100
		 */
		$integer   += ( $fractional - $fractional % 100 ) / 100;
		$fractional = $fractional % 100;
		$data       = sprintf(
			$data,
			$counter,
			$integer,
			$fractional
		);
		return $data;
	}

	private function get_contractor( $contractor_id ) {
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

	private function validate_year_month( $data ) {
		if ( is_string( $data ) && preg_match( '/^\d{4}\-\d{2}$/', $data ) ) {
			return $data;
		}
		return new WP_Error( 'wrong-input', __( 'Wrong date, please try again!', 'kpir' ) );
	}

	/**
	 * convert special chars
	 */
	private function convert_chars( $text ) {
		$text = preg_replace( '/\&/', '&amp;', $text );
		return $text;
	}
}
