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

class iworks_kpir_jpk_vat_3 {

	private $options;
	private $contractors = array();
	private $sum = array(
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

	public function __construct() {
		global $iworks_kpir_options;
		$this->options = $iworks_kpir_options;
	}

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
		$data = '';

		$data .= $this->template_header();
		$data .= $this->template_section_head( $_REQUEST['purpose'], $_REQUEST['m'] );
		$data .= $this->template_section_company();

		$post_type_object = $kpir->get_post_type_invoice();

		$cf_date_name = $post_type_object->get_custom_field_basic_date_name();
		$cf_contractor_name = $post_type_object->get_custom_field_basic_contractor_name();
		$date_format = get_option( 'date_format' );

		$query = $kpir->get_month_query( $_REQUEST['m'] );

		if ( $query->have_posts() ) {
			$incomes_counter = $expenses_counter = 0;
			$expenses = $incomes = '';
			while ( $query->have_posts() ) {
				$query->the_post();
				$ID = get_the_ID();
				$type = get_post_meta( $ID, 'iworks_kpir_basic_type', true );
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
		$data .= $expenses;
		$data .= $this->template_summary_expenses( $expenses_counter );

		$data .= $incomes;
		$data .= $this->template_summary_incomes( $incomes_counter );

		$data .= $this->template_footer();

		$filename = sprintf( '%s.xml', $_REQUEST['m'] );

		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/xml' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		echo $data;
		exit;
	}

	public function show( $kpir ) {
		global $wpdb;

		$post_type_object = $kpir->get_post_type_invoice();
		$sql = "select distinct meta_value from {$wpdb->postmeta} where meta_key = '{$post_type_object->get_custom_field_year_month_name()}'order by meta_value desc";

		$values = $wpdb->get_col( $sql );

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
			'min' => 0,
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
		//        echo $this->options->get_field_by_type( 'button', 'iworks_kpir_jpk_vat_3', __( 'Get XML', 'kpir' ), $args );
		echo '</form>';
	}

	private function template_header() {
		$data = '<?xml version="1.0" encoding="UTF-8"?>';
		$data .= PHP_EOL;
		$data .= '<JPK xmlns="http://jpk.mf.gov.pl/wzor/2016/10/26/10261/" xmlns:etd="http://crd.gov.pl/xml/schematy/dziedzinowe/mf/2016/01/25/eD/DefinicjeTypy/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
		$data .= PHP_EOL;
		return $data;
	}

	private function template_footer() {
		$data = '</JPK>';
		return $data;
	}

	/**
	 * CelZlozenia - 1- złożenie pliku JPK_VAT
	 *               2- korekta pliku JPK_VAT
	 *               3... kolejny numer korekty
	 * DataOd - Miesiąc, za który składany jest JPK_VAT
	 * DataOd - Miesiąc, za który składany jest JPK_VAT
	 */
	private function template_section_head( $purpose, $month ) {
		$date = strtotime( $month );
		$data = '   <Naglowek>
        <KodFormularza wersjaSchemy="1-1" kodSystemowy="JPK_VAT (3)">JPK_VAT</KodFormularza>
        <WariantFormularza>3</WariantFormularza>
        <CelZlozenia>%d</CelZlozenia>
        <DataWytworzeniaJPK>%s</DataWytworzeniaJPK>
        <DataOd>%s</DataOd>
        <DataDo>%s</DataDo>
        <NazwaSystemu>KPiR Base PLUGIN_VERSION</NazwaSystemu>
    </Naglowek>';
		$data .= PHP_EOL;
		$data .= PHP_EOL;
		$data = sprintf(
			$data,
			$purpose,
			date( 'Y-m-d\TH:m:s\Z', time() ),
			date( 'Y-m-01', $date ),
			date( 'Y-m-t', $date )
		);

		return $data;
	}

	private function template_section_company() {
		$data = '   <Podmiot1>
        <IdentyfikatorPodmiotu>
            <etd:NIP>%s</etd:NIP>
            <etd:PelnaNazwa>%s</etd:PelnaNazwa>
            <etd:EMAIL>%s</etd:EMAIL>
        </IdentyfikatorPodmiotu>
    </Podmiot1>';
		$data .= PHP_EOL;
		$data .= PHP_EOL;
		$data = sprintf(
			$data,
			preg_replace( '/[^\d]+/', '', get_option( 'iworks_kpir_nip' ) ),
			get_option( 'iworks_kpir_name' ),
			get_option( 'iworks_kpir_email' )
		);
		return $data;
	}

	/**
	 * K_43 Kwota netto – Nabycie towarów i usług zaliczanych u podatnika do środków trwałych (pole opcjonalne)
	 * K_44 Kwota podatku naliczonego – Nabycie towarów i usług zaliczanych u podatnika do środków trwałych (pole opcjonalne) * K_45 Kwota netto – Nabycie towarów i usług pozostałych (pole opcjonalne)
	 * K_46 Kwota podatku naliczonego – Nabycie towarów i usług pozostałych (pole opcjonalne)
	 * K_47 Korekta podatku naliczonego od nabycia środków trwałych (pole opcjonalne)
	 * K_48 Korekta podatku naliczonego od pozostałych nabyć (pole opcjonalne)
	 * K_49 Korekta podatku naliczonego, o której mowa w art. 89b ust. 1 ustawy (pole opcjonalne)
	 * K_50 Korekta podatku naliczonego, o której mowa w art. 89b ust. 4 ustawy (pole opcjonalne)
	 *
	 */
	private function template_row_expense( $counter, $ID, $contractor_id ) {
		$data = '<ZakupWiersz>
    <LpZakupu>%d</LpZakupu>
    <NrDostawcy>%s</NrDostawcy>
    <NazwaWystawcy>%s</NazwaWystawcy>
    <AdresWystawcy>%s</AdresWystawcy>
    <DowodZakupu>%s</DowodZakupu>
    <DataZakupu>%s</DataZakupu>
%s</ZakupWiersz>';
		$data .= PHP_EOL;
		$data .= PHP_EOL;
		$contractor = $this->get_contractor( $contractor_id );
		$sale_date = $create_date = date( 'Y-m-d', get_post_meta( $ID, 'iworks_kpir_basic_date', true ) );
		$number = get_the_title();
		$k = '';

		$is_car_related = get_post_meta( $ID, 'iworks_kpir_expense_car', true );

		$money = get_post_meta( $ID, 'iworks_kpir_expense_purchase', true );
		$k .= sprintf(
			'   <K_45>%d.%d</K_45>%s',
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
		if ( 'yes' == $is_car_related ) {
			$v = round( ($money['integer'] / 2) * 100 + $money['fractional'] / 2 );
			$money['fractional'] = $v % 100;
			$money['integer'] = round( ($v - $money['fractional']) / 100 );
		}
		$k .= sprintf(
			'   <K_46>%d.%d</K_46>%s',
			$money['integer'],
			$money['fractional'],
			PHP_EOL
		);
		$this->sum['vat_expense']['integer'] += $money['integer'];
		$this->sum['vat_expense']['fractional'] += $money['fractional'];

		$data = sprintf(
			$data,
			$counter,
			$contractor['nip'],
			$contractor['name'],
			$contractor['address'],
			$number,
			$create_date,
			$k
		);

		return $data;

	}

	private function template_row_income( $counter, $ID, $contractor_id ) {
		$data = '<SprzedazWiersz>
    <LpSprzedazy>%d</LpSprzedazy>
    <NrKontrahenta>%s</NrKontrahenta>
    <NazwaKontrahenta>%s</NazwaKontrahenta>
    <AdresKontrahenta>%s</AdresKontrahenta>
    <DowodSprzedazy>%s</DowodSprzedazy>
    <DataWystawienia>%s</DataWystawienia>
    <DataSprzedazy></DataSprzedazy>
%s</SprzedazWiersz>';
		$data .= PHP_EOL;
		$data .= PHP_EOL;
		$contractor = $this->get_contractor( $contractor_id );
		$sale_date = $create_date = date( 'Y-m-d', get_post_meta( $ID, 'iworks_kpir_basic_date', true ) );
		$number = get_the_title();
		$k = '';

		$type = get_post_meta( $ID, 'iworks_kpir_income_vat_type', true );

		switch ( $type ) {
			case 'c01':
				$money = get_post_meta( $ID, 'iworks_kpir_income_sale', true );
				$k .= sprintf(
					'   <K_11>%d.%d</K_11>%s',
					$money['integer'],
					$money['fractional'],
					PHP_EOL
				);
				$this->sum['income']['integer'] += $money['integer'];
				$this->sum['income']['fractional'] += $money['fractional'];
			break;
			case 'c06':
				$money = get_post_meta( $ID, 'iworks_kpir_income_sale', true );
				$k .= sprintf(
					'   <K_19>%d.%d</K_19>%s',
					$money['integer'],
					$money['fractional'],
					PHP_EOL
				);
				$this->sum['income']['integer'] += $money['integer'];
				$this->sum['income']['fractional'] += $money['fractional'];
				/**
			 * VAT
			 */
				$money = get_post_meta( $ID, 'iworks_kpir_income_vat', true );
				$k .= sprintf(
					'   <K_20>%d.%d</K_20>%s',
					$money['integer'],
					$money['fractional'],
					PHP_EOL
				);
				$this->sum['vat_income']['integer'] += $money['integer'];
				$this->sum['vat_income']['fractional'] += $money['fractional'];
			break;
		}

		$data = sprintf(
			$data,
			$counter,
			$contractor['nip'],
			$contractor['name'],
			$contractor['address'],
			$number,
			$create_date,
			$k
		);

		return $data;
	}

	private function template_summary_expenses( $counter ) {
		$data = '<SprzedazCtrl>
    <LiczbaWierszySprzedazy>%d</LiczbaWierszySprzedazy>
    <PodatekNalezny>%d.%d</PodatekNalezny>
</SprzedazCtrl>';
		$data .= PHP_EOL;
		$data .= PHP_EOL;

		$integer = $this->sum['vat_expense']['integer'];
		$fractional = $this->sum['vat_expense']['fractional'];

		$data = sprintf(
			$data,
			$counter,
			$integer,
			$fractional
		);
		return $data;
	}

	private function template_summary_incomes( $counter ) {
		$data = '<SprzedazCtrl>
    <LiczbaWierszySprzedazy>%d</LiczbaWierszySprzedazy>
    <PodatekNalezny>%d.%d</PodatekNalezny>
</SprzedazCtrl>';
		$data .= PHP_EOL;
		$data .= PHP_EOL;

		$integer = $this->sum['vat_income']['integer'];
		$fractional = $this->sum['vat_income']['fractional'];

		$data = sprintf(
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
				$nip = preg_replace( '/\-/', '', $nip );
			}
			$name = get_post_meta( $contractor_id, 'iworks_kpir_contractor_data_full_name', true );
			$address = get_post_meta( $contractor_id, 'iworks_kpir_contractor_data_street1', true );
			$address .= ', '.get_post_meta( $contractor_id, 'iworks_kpir_contractor_data_zip', true );
			$address .= ' '.get_post_meta( $contractor_id, 'iworks_kpir_contractor_data_city', true );
			$address .= ', '.get_post_meta( $contractor_id, 'iworks_kpir_contractor_data_country', true );
			$this->contractors[ $contractor_id ] = array(
				'nip' => $nip,
				'name' => $name,
				'address' => preg_replace( '/ {2,}/', ' ', $address ),
			);
		}
		return $this->contractors[ $contractor_id ];
	}
}
