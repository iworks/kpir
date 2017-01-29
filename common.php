<?php

$base = dirname( dirname( __FILE__ ) );
$vendor = $base.'/vendor';

/**
 * require: IworksUpprev Class
 */
if ( ! class_exists( 'IworksUpprev' ) ) {
	require_once $vendor.'/iworks/upprev.php';
}
/**
 * configuration
 */
require_once dirname( dirname( __FILE__ ) ).'/etc/options.php';
/**
 * require: IworksOptions Class
 */
if ( ! class_exists( 'iworks_options' ) ) {
	require_once $vendor.'/iworks/options.php';
}
require_once dirname( __FILE__ ).'/show_thumbnail_on_admin_post_list.php';

/**
 * i18n
 */
load_plugin_textdomain( 'upprev', false, dirname( dirname( plugin_basename( __FILE__ ) ) ).'/languages' );

/**
 * load options
 */
$iworks_upprev_options = new iworks_options();
$iworks_upprev_options->set_option_function_name( 'iworks_upprev_options' );
$iworks_upprev_options->set_option_prefix( IWORKS_UPPREV_PREFIX );

function iworks_upprev_options_init() {

	global $iworks_upprev_options;
	$iworks_upprev_options->options_init();
}

function iworks_upprev_activate() {

	$iworks_upprev_options = new iworks_options();
	$iworks_upprev_options->set_option_function_name( 'iworks_upprev_options' );
	$iworks_upprev_options->set_option_prefix( IWORKS_UPPREV_PREFIX );
	$iworks_upprev_options->activate();
}

function iworks_upprev_deactivate() {

	global $iworks_upprev_options;
	$iworks_upprev_options->deactivate();
}
