<?php
/*
Plugin Name: KPiR
Plugin URI: PLUGIN_URI
Text Domain: kpir
Description: Podatkowa Księga Przychodu i Rozchodu
Author: Marcin Pietrzak
Version: PLUGIN_VERSION
Author URI: http://iworks.pl/

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

/**
 * static options
 */
define( 'IWORKS_KPIR_VERSION', 'PLUGIN_VERSION' );
define( 'IWORKS_KPIR_PREFIX', 'iworks_kpir_' );
$base     = dirname( __FILE__ );
$includes = $base . '/includes';

/**
 * require: IworksKPiR Class
 */
if ( ! class_exists( 'iworks_kpir' ) ) {
	require_once $includes . '/iworks/kpir.php';
}
/**
 * configuration
 */
require_once $base . '/etc/options.php';
/**
 * require: IworksOptions Class
 */
if ( ! class_exists( 'iworks_options' ) ) {
	require_once $includes . '/iworks/options/options.php';
}

/**
 * load options
 */
global $iworks_kpir_options;
$iworks_kpir_options = new iworks_options();
$iworks_kpir_options->set_option_function_name( 'iworks_kpir_options' );
$iworks_kpir_options->set_option_prefix( IWORKS_KPIR_PREFIX );

function iworks_kpir_options_init() {
	global $iworks_kpir_options;
	$iworks_kpir_options->options_init();
}

function iworks_kpir_activate() {
	$iworks_kpir_options = new iworks_options();
	$iworks_kpir_options->set_option_function_name( 'iworks_kpir_options' );
	$iworks_kpir_options->set_option_prefix( IWORKS_KPIR_PREFIX );
	$iworks_kpir_options->activate();
}

function iworks_kpir_deactivate() {
	 global $iworks_kpir_options;
	$iworks_kpir_options->deactivate();
}

$iworks_kpir = new iworks_kpir();

/**
 * install & uninstall
 */
register_activation_hook( __FILE__, 'iworks_kpir_activate' );
register_deactivation_hook( __FILE__, 'iworks_kpir_deactivate' );
