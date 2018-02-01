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

		$post_type_object = $kpir->get_post_type_invoice();

		$cf_date_name = $post_type_object->get_custom_field_basic_date_name();
		$cf_contractor_name = $post_type_object->get_custom_field_basic_contractor_name();
		$date_format = get_option( 'date_format' );

		$query = $kpir->get_month_query( $_REQUEST['m'] );

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
			while ( $query->have_posts() ) {
				$query->the_post();
				$ID = get_the_ID();

				l( $ID );

				$contractor_id = get_post_meta( $ID, $cf_contractor_name, true );
				$data .= $this->template_row_sale( $i, $ID, $contractor_id );
				$i++;
			}
		}

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
			'class' => 'small-text',
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
			'class' => 'button button-primary',
		);
		echo $this->options->get_field_by_type( 'submit', '=iworks_kpir_jpk_vat_3', __( 'Get XML', 'kpir' ), $args );
		//        echo $this->options->get_field_by_type( 'button', 'iworks_kpir_jpk_vat_3', __( 'Get XML', 'kpir' ), $args );
		echo '</form>';
	}

	private function template_header() {
		$data = '<?xml version="1.0" encoding="UTF-8"?>';
		$data .= '<JPK xmlns="http://jpk.mf.gov.pl/wzor/2016/10/26/10261/" xmlns:etd="http://crd.gov.pl/xml/schematy/dziedzinowe/mf/2016/01/25/eD/DefinicjeTypy/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
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
		$data = '<Naglowek>
            <KodFormularza wersjaSchemy="1-1" kodSystemowy="JPK_VAT (3)">JPK_VAT</KodFormularza>
            <WariantFormularza>3</WariantFormularza>
            <CelZlozenia>%d</CelZlozenia>
            <DataWytworzeniaJPK>%s</DataWytworzeniaJPK>
            <DataOd>%s</DataOd>
            <DataDo>%s</DataDo>
            <NazwaSystemu>KPiR Base PLUGIN_VERSION</NazwaSystemu>
            </Naglowek>';
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
		$data = '
    <Podmiot1>
        <IdentyfikatorPodmiotu>
            <etd:NIP>%NIP%</etd:NIP>
            <etd:PelnaNazwa>%PelnaNazwa%</etd:PelnaNazwa>
        </IdentyfikatorPodmiotu>
    </Podmiot1>';
		return $data;
	}

	private function template_row_sale( $counter, $ID, $contractor_id ) {
		$data = '<SprzedazWiersz>
            <LpSprzedazy>%d</LpSprzedazy>
            <NrKontrahenta>%s</NrKontrahenta>
            <NazwaKontrahenta>%s</NazwaKontrahenta>
            <AdresKontrahenta>%s</AdresKontrahenta>
            <DowodSprzedazy>%s</DowodSprzedazy>
            <DataWystawienia>%s</DataWystawienia>
            <DataSprzedazy>%s</DataSprzedazy>
            </SprzedazWiersz>';

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

		$sale_date = $create_date = date( 'Y-m-d', get_post_meta( $ID, $cf_date_name, true ) );

		$number = get_the_title();

		$data = sprintf(
			$data,
			$counter, $nip, $name, $address, $number, $create_date, $sale_date
		);

		return $data;
	}

	private function template_summary_sale() {
		$data = '<SprzedazCtrl>
<LiczbaWierszySprzedazy>2</LiczbaWierszySprzedazy>
<PodatekNalezny>12.36</PodatekNalezny>
</SprzedazCtrl>';
		return $data;
	}

	/**
	 * K_43 – kwota netto nabycie środków trwałych
	 * K_44 – kwota podatku naliczonego nabycie środków trwałych
	 * K_45 – kwota netto nabycie towarów i usług pozostałych
	 * K_46 – kwota podatku naliczonego nabycie towarów i usług pozostałych
	 */
	private function template_row_purchase() {
		$data = '<SprzedazWiersz>
            <LpSprzedazy>1</LpSprzedazy>
            <NrKontrahenta>96121309455</NrKontrahenta>
            <NazwaKontrahenta>Mieczysława ZĄBKOWSKA</NazwaKontrahenta>
            <AdresKontrahenta>43-430 SKOCZÓW, ul. Szkolna 21 C</AdresKontrahenta>
            <DowodSprzedazy>R/20/18</DowodSprzedazy>
            <DataWystawienia>2018-01-04</DataWystawienia>
            <DataSprzedazy>2018-01-04</DataSprzedazy>
            <K_17>140.27</K_17>
            <K_18>11.22</K_18>
            </SprzedazWiersz>';
		return $data;
	}
}
