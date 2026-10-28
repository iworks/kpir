<?php
/**
 * PHPUnit bootstrap for the KPiR plugin test suite.
 *
 * Defines the constants that plugin files guard on (ABSPATH, WPINC) and
 * loads the minimal WordPress function stubs required by the classes
 * under test. No WordPress installation or database is required.
 *
 * @package KPIR
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__, 2 ) . '/' );
}

if ( ! defined( 'WPINC' ) ) {
	define( 'WPINC', 'wp-includes' );
}

require_once __DIR__ . '/wp-stubs.php';
