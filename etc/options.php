<?php

function iworks_kpir_options() {

	$iworks_kpir_options = array();

	/**
	 * main settings
	 */
	$iworks_kpir_options['index'] = array(
		'version'  => '0.0',
		'page_title' => __( 'Configuration', 'kpir' ),
		'menu_title' => __( 'KPiR Pro!', 'kpir' ),
		'menu' => 'submenu',
		'parent' => add_query_arg(
			array(
				'post_type' => 'iworks_kpir_invoice',
			),
			'edit.php'
		),
		'options'  => array(
		),
//		'metaboxes' => array(),
		'pages' => array(
			'reports' => array(
				'page_title' => __( 'Reports', 'kpir' ),
				'menu_title' => __( 'Reports', 'kpir' ),
				'menu' => 'submenu',
				'parent' => add_query_arg(
					array(
						'post_type' => 'iworks_kpir_invoice',
					),
					'edit.php'
				),
				'show_page_callback' => 'iworks_kpir_raports',
			),
		),
	);
	return $iworks_kpir_options;
}

function iworks_kpir_raports() {
	global $iworks_kpir;
	$iworks_kpir->show_page_raports();
}
