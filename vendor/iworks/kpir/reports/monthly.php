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
					'value' => '2017-02',
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
				echo $this->html_table_td( get_the_title(), 'title' );
				echo '</tr>';
			}
			echo '</table>';
			/* Restore original Post Data */
			wp_reset_postdata();
		} else {
			echo '<p>';
			_e( 'There is no invoices in this month.', 'kpir' );
			echo '</p>';
		}
	}

	private function html_table_td( $value, $class = '' ) {
		if ( ! is_string( $class ) ) {
			$class = '';
		} else if ( ! empty( $class ) ) {
			$class = sprintf( ' class="%s"', esc_attr( $class ) );
		}
		return sprintf( '<td%s>%s</td>', $class, $value );
	}

	private function get_contractor( $contractor_id ) {
		if ( ! isset( $this->contractors[ $contractor_id ] ) ) {
			$this->contractors[ $contractor_id ] = get_post( $contractor_id );
		}
		return $this->contractors[ $contractor_id ];
	}
}

