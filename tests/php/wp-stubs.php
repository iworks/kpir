<?php
/**
 * Minimal WordPress function stubs for unit tests.
 *
 * Only the functions touched by the classes loaded during tests are
 * stubbed. Extend this file as test coverage grows.
 *
 * @package KPIR
 */

if ( ! function_exists( 'add_action' ) ) {
	/**
	 * Stub: register action hook (no-op).
	 *
	 * @return true
	 */
	function add_action( ...$args ) {
		return true;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	/**
	 * Stub: register filter hook (no-op).
	 *
	 * @return true
	 */
	function add_filter( ...$args ) {
		return true;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	/**
	 * Stub: apply filter hook (returns value unchanged).
	 *
	 * @param mixed $value Value to filter.
	 * @return mixed
	 */
	function apply_filters( $hook_name, $value ) {
		return $value;
	}
}

if ( ! function_exists( '__' ) ) {
	/**
	 * Stub: translate string (returns input).
	 *
	 * @param string $text   Text to translate.
	 * @param string $domain Text domain.
	 * @return string
	 */
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	/**
	 * Stub: translate and escape string (returns input).
	 *
	 * @param string $text   Text to translate.
	 * @param string $domain Text domain.
	 * @return string
	 */
	function esc_html__( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( 'esc_attr__' ) ) {
	/**
	 * Stub: translate and escape attribute (returns input).
	 *
	 * @param string $text   Text to translate.
	 * @param string $domain Text domain.
	 * @return string
	 */
	function esc_attr__( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	/**
	 * Stub: escape string (returns input).
	 *
	 * @param string $text Text to escape.
	 * @return string
	 */
	function esc_html( $text ) {
		return $text;
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	/**
	 * Stub: WP_Error check (always false in unit tests).
	 *
	 * @param mixed $thing Value to check.
	 * @return false
	 */
	function is_wp_error( $thing ) {
		return false;
	}
}
