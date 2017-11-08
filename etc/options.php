<?php

function iworks_kpir_options() {
	$iworks_kpir_options = array();
	/**
	 * main settings
	 */
	$iworks_kpir_options['index'] = array(
		'version'  => '0.0',
		'page_title' => __( 'Configuration', 'kpir' ),
		'menu' => 'submenu',
		'parent' => add_query_arg(
			array(
				'post_type' => 'iworks_kpir_invoice',
			),
			'edit.php'
		),
		'options'  => array(
			array(
				'name'              => 'nip',
				'type'              => 'text',
				'class'             => 'regular-text',
				'th'             => __( 'NIP', 'kpir' ),
				'sanitize_callback' => 'esc_html',
			),
			array(
				'name'              => 'name',
				'type'              => 'text',
				'class'             => 'regular-text',
				'th'             => __( 'Full Name', 'kpir' ),
				'sanitize_callback' => 'esc_html',
			),
		),
		//      'metaboxes' => array(),
		'pages' => array(
			'report_monthly' => array(
				'page_title' => __( 'Monthly Report', 'kpir' ),
				'menu_title' => __( 'Monthly', 'kpir' ),
				'menu' => 'submenu',
				'parent' => add_query_arg(
					array(
						'post_type' => 'iworks_kpir_invoice',
					),
					'edit.php'
				),
				'show_page_callback' => 'iworks_kpir_report_monthly',
			),
		),
	);
	return $iworks_kpir_options;
}

function iworks_kpir_report_monthly() {
	global $iworks_kpir;
	$iworks_kpir->show_page_reports( 'monthly' );
}
