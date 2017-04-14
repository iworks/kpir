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

	private $contractors = array();

	public function __construct() {
	}

	public function show( $post_type_object ) {

		$this->show_filter( $post_type_object );

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
					'value' => get_query_var( 'm', '' ),
				),
				array(
					'key' => $post_type_object->get_custom_field_basic_date_name(),
					'compare' => 'EXISTS',
				),
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
				echo $this->html_table_td( get_the_title(), 'title' );
				/**
				 * contractor name
				 */
				echo $this->html_table_td( get_post_meta( $contractor_id, 'iworks_kpir_contractor_data_full_name', true ) );
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
				echo '</tr>';
			}
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
		$content = '<thead>';
		$content .= '<tr>';
		$content .= $this->html_table_th( 'Lp.', null, 3 );
		$content .= $this->html_table_th( 'Data zdarzenia gospodarczego', null, 3 );
		$content .= $this->html_table_th( 'Nr dowodu księgowego', null, 3 );
		$content .= $this->html_table_th( 'Kontrahent', null, null, 2 );
		$content .= $this->html_table_th( 'Opis zdarzenia gospodarczego', null, 3 );
		$content .= $this->html_table_th( 'Przychód', null, null, 6 );
		$content .= '</tr>';
		$content .= '<tr>';
		$content .= $this->html_table_th( 'imię i nazwisko (firma)', null, 2 );
		$content .= $this->html_table_th( 'adres', null, 2 );
		$content .= $this->html_table_th( 'wartość sprzedanych towarów i usług', null, null, 2 );
		$content .= $this->html_table_th( 'pozostałe przychody', null, null, 2 );
		$content .= $this->html_table_th( 'razem przychód (7+8)', null, null, 2 );
		$content .= '</tr>';
		$content .= '<tr>';
		$content .= $this->html_table_th( 'zł' );
		$content .= $this->html_table_th( 'gr' );
		$content .= $this->html_table_th( 'zł' );
		$content .= $this->html_table_th( 'gr' );
		$content .= $this->html_table_th( 'zł' );
		$content .= $this->html_table_th( 'gr' );
		$content .= '</tr>';
		$content .= '<tr>';
		$content .= $this->html_table_th( 1 );
		$content .= $this->html_table_th( 2 );
		$content .= $this->html_table_th( 3 );
		$content .= $this->html_table_th( 4 );
		$content .= $this->html_table_th( 5 );
		$content .= $this->html_table_th( 6 );
		$content .= $this->html_table_th( 7, null, null, 2 );
		$content .= $this->html_table_th( 8, null, null, 2 );
		$content .= $this->html_table_th( 9, null, null, 2 );
		$content .= '</tr>';
		$content .= '</thead>';
		return $content;
	}

	private function html_table_td( $value, $class = '' ) {
		$args = array(
			'tag' => 'td',
			'value' => $value,
			'class' => $class,
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

	public function show_filter( $post_type_object ) {
		global $wpdb;

		$sql = $wpdb->prepare( "select distinct meta_value from {$wpdb->postmeta} where meta_key = '{$post_type_object->get_custom_field_year_month_name()}'order by meta_value desc" );

		$values = $wpdb->get_col( $sql );

		if ( empty( $values ) ) {
			return;
		}

		$current = get_query_var( 'm', '' );

		echo '<form id="posts-filter" method="get">';
		printf( '<input name="post_type" type="hidden" value="%s" />', esc_attr( $post_type_object->get_name() ) );
		printf( '<input name="page" type="hidden" value="%s" />', esc_attr( __CLASS__ ) );
		echo '<div class="tablenav top">';
		echo '<div class="alignleft actions">';
		printf( '<label for="filter-by-date" class="screen-reader-text">%s</label>', esc_html__( 'Filter by date', 'kpir' ) );
		echo '<select name="m" id="filter-by-date">';
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
}

