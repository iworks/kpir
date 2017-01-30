<?php

function iworks_kpir_options() {

	$iworks_kpir_options = array();

	/**
	 * main settings
	 */
	$iworks_kpir_options['index'] = array(
		'use_tabs' => true,
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
		'enqueue_scripts' => array(
			'admin' => array(
				'kpir-admin-js',
			),
		),
		'enqueue_styles' => array(
			'admin' => array(
				'kpir-admin',
			),
			'frontend' => array(
				'kpir',
			),
		),
		'options'  => array(
			array(
				'name'              => 'last_used_tab',
				'type'              => 'hidden',
				'dynamic'           => true,
				'autoload'          => false,
				'default'           => 0,
			),
			array(
				'name'              => 'configuration',
				'type'              => 'special',
				'default'           => 'simple',
				'sanitize_callback' => 'iworks_kpir_sanitize_callback_configuration',
			),
			/**
			 * Appearance: simple
			 */
			array(
				'type'              => 'heading',
				'label'             => __( 'Appearance', 'kpir' ),
				'configuration'     => 'simple',
			),
			array(
				'name'              => 'layout',
				'type'              => 'serialize',
				'th'                => __( 'Layout', 'kpir' ),
				'default'           => 'simple',
				'callback'          => 'iworks_kpir_callback_layout',
			),
			/**
			 * Position: simple
			 */
			array(
				'type'              => 'heading',
				'label'             => __( 'Position', 'kpir' ),
				'configuration'     => 'simple',
			),
			array(
				'name'              => 'position',
				'type'              => 'radio',
				'th'                => __( 'Position', 'kpir' ),
				'default'           => 'right',
				'radio'             => array(
					'right'         => array( 'label' => __( 'bottom right', 'kpir' ) ),
					'left'          => array( 'label' => __( 'bottom left',  'kpir' ) ),
					'bottom'        => array( 'label' => __( 'bottom',       'kpir' ), 'need_pro' => true ),
					'right-top'     => array( 'label' => __( 'top right',    'kpir' ), 'need_pro' => true ),
					'top'           => array( 'label' => __( 'top',          'kpir' ), 'need_pro' => true ),
					'left-top'      => array( 'label' => __( 'top left',     'kpir' ), 'need_pro' => true ),
					'right-middle'  => array( 'label' => __( 'middle right', 'kpir' ), 'need_pro' => true ),
					'left-middle'   => array( 'label' => __( 'middle left',  'kpir' ), 'need_pro' => true ),
				),
				'configuration'     => 'both',
				'sanitize_callback' => 'esc_html',
			),
			/**
			 * Appearance: advance
			 */
			array(
				'type'              => 'heading',
				'label'             => __( 'Appearance', 'kpir' ),
				'configuration'     => 'advance',
			),
			array(
				'name'              => 'animation',
				'type'              => 'radio',
				'th'                => __( 'Animation style', 'kpir' ),
				'default'           => 'flyout',
				'radio'             => array(
					'flyout' => array( 'label' => __( 'flyout', 'kpir' ) ),
					'fade'   => array( 'label' => __( 'fade in/out', 'kpir' ) ),
				),
				'sanitize_callback' => 'esc_html',
			),
		),
		'metaboxes' => array(),
	);
	return $iworks_kpir_options;
}

