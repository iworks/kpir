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

if ( class_exists( 'iworks_kpir_reports_montly' ) ) {
	return;
}

class iworks_kpir_reports_montly {

	public function __construct() {
	}

	public function show( $post_type_object ) {
		$args = array(
			'post_type' => $post_type_object->get_name(),
			'nopaging' => true,
			'meta_key' => $post_type_object->get_custom_field_name(),
			'meta_value' => '2017-01',
			'orderby' => 'meta_value',
		);
		$the_query = new WP_Query( $args );
		if ( $the_query->have_posts() ) {
			echo '<table>';
			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				echo '<tr>';
				echo '<td>';
				the_title();
				echo '</td>';
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
}

