<?php
/**
 * Unit tests for iworks_kpir_posttypes_invoice meta CRUD.
 *
 * Simulates the save_post flow of the invoice post type without
 * WordPress or database: post/meta/option functions are backed by
 * in-memory stores defined here, and the options object is replaced
 * by a lightweight double exposing get_option_name(). Covers create,
 * update, delete (empty and zero money values), post-type mismatch
 * handling and the year_month derived field.
 *
 * @package KPIR
 */

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 3 ) . '/includes/iworks/kpir/posttypes.php';
require_once dirname( __DIR__, 3 ) . '/includes/iworks/kpir/posttypes/invoice.php';

/**
 * In-memory meta store shared by the meta function stubs.
 *
 * @var array<string, array<string, mixed>> $GLOBALS['kpir_test_meta']
 */
$GLOBALS['kpir_test_meta'] ??= array();

/**
 * In-memory post store shared by the post function stubs.
 *
 * @var array<int, object> $GLOBALS['kpir_test_posts']
 */
$GLOBALS['kpir_test_posts'] ??= array();

if ( ! function_exists( 'get_post_meta' ) ) {
	/**
	 * Stub: read a value from the in-memory meta store.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 * @param bool   $single  Ignored (kept for signature parity).
	 * @return mixed Stored value or empty string.
	 */
	function get_post_meta( $post_id, $key, $single = true ) {
		return $GLOBALS['kpir_test_meta'][ $post_id ][ $key ] ?? '';
	}
}

if ( ! function_exists( 'update_post_meta' ) ) {
	/**
	 * Stub: write a value into the in-memory meta store.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 * @param mixed  $value   Value to store.
	 * @return true
	 */
	function update_post_meta( $post_id, $key, $value ) {
		$GLOBALS['kpir_test_meta'][ $post_id ][ $key ] = $value;
		return true;
	}
}

if ( ! function_exists( 'add_post_meta' ) ) {
	/**
	 * Stub: add a meta value; fails (false) when the key already exists,
	 * mirroring add_post_meta() with $unique = true.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 * @param mixed  $value   Value to store.
	 * @param bool   $unique  Whether the key must be unique.
	 * @return bool True when stored, false when the key already exists.
	 */
	function add_post_meta( $post_id, $key, $value, $unique = false ) {
		if ( isset( $GLOBALS['kpir_test_meta'][ $post_id ][ $key ] ) ) {
			return false;
		}
		$GLOBALS['kpir_test_meta'][ $post_id ][ $key ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_post_meta' ) ) {
	/**
	 * Stub: remove a meta value from the in-memory store.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 * @return bool True when a value was removed.
	 */
	function delete_post_meta( $post_id, $key ) {
		if ( ! isset( $GLOBALS['kpir_test_meta'][ $post_id ][ $key ] ) ) {
			return false;
		}
		unset( $GLOBALS['kpir_test_meta'][ $post_id ][ $key ] );
		return true;
	}
}

if ( ! function_exists( 'get_post' ) ) {
	/**
	 * Stub: fetch a fake post from the in-memory post store.
	 *
	 * @param int $post_id Post ID.
	 * @return object|null Fake post object or null.
	 */
	function get_post( $post_id ) {
		return $GLOBALS['kpir_test_posts'][ $post_id ] ?? null;
	}
}

if ( ! function_exists( 'get_post_type' ) ) {
	/**
	 * Stub: fetch the post type of a fake post.
	 *
	 * @param int $post_id Post ID.
	 * @return string|false Post type or false when not found.
	 */
	function get_post_type( $post_id ) {
		return $GLOBALS['kpir_test_posts'][ $post_id ]->post_type ?? false;
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	/**
	 * Stub: reverse slash-escaping (identity in tests).
	 *
	 * @param mixed $value Value to unslash.
	 * @return mixed The value unchanged.
	 */
	function wp_unslash( $value ) {
		return $value;
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	/**
	 * Stub: sanitize post content (strips HTML tags, like core).
	 *
	 * @param string $value Value to sanitize.
	 * @return string The value unchanged.
	 */
	function wp_kses_post( $value ) {
		// Mirror core wp_kses_post(): strip all HTML tags.
		return trim( preg_replace( '/<[^>]*>/', '', (string) $value ) );
	}
}

if ( ! function_exists( 'date_i18n' ) ) {
	/**
	 * Stub: local date formatting (delegates to gmdate).
	 *
	 * @param string $format    Date format.
	 * @param int    $timestamp Unix timestamp.
	 * @return string Formatted date.
	 */
	function date_i18n( $format, $timestamp = null ) {
		return gmdate( $format, $timestamp ?? time() );
	}
}

if ( ! function_exists( 'do_action' ) ) {
	/**
	 * Stub: fire registered action callbacks with arguments.
	 *
	 * @param string $hook_name Hook name.
	 * @param mixed  ...$args   Hook arguments.
	 * @return void
	 */
	function do_action( $hook_name, ...$args ) {
		foreach ( $GLOBALS['kpir_test_actions'][ $hook_name ] ?? array() as $callback ) {
			call_user_func_array( $callback, $args );
		}
	}
}

$GLOBALS['kpir_test_actions'] ??= array();

if ( ! function_exists( 'get_the_title' ) ) {
	/**
	 * Stub: post title.
	 *
	 * @return string Empty title.
	 */
	function get_the_title() {
		return '';
	}
}

if ( ! function_exists( 'get_option' ) ) {
	/**
	 * Stub: read an option (always the default).
	 *
	 * @param string $name    Option name.
	 * @param mixed  $default Default value.
	 * @return mixed The default.
	 */
	function get_option( $name, $default = false ) {
		return $default;
	}
}

/**
 * Options double: mirrors iworks_options::get_option_name() with the
 * real plugin prefix, without loading the full options module.
 */
class IworksKpirOptionsTestDouble {

	/**
	 * Build a prefixed option/field name.
	 *
	 * @param string $name Name to prefix.
	 * @return string Prefixed name.
	 */
	public function get_option_name( $name ) {
		return 'iworks_kpir_' . $name;
	}
}

/**
 * Test cases for invoice post type meta CRUD.
 */
#[TestDox('iWorks KPiR invoice post type meta CRUD')]
class IworksKpirInvoiceCrudTest extends TestCase {

	/**
	 * Invoice post type handler under test.
	 *
	 * @var iworks_kpir_posttypes_invoice
	 */
	private $invoice;

	/**
	 * Prepare a fresh handler, empty stores and a fake invoice post
	 * before every test.
	 */
	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['kpir_test_meta']  = array();
		$GLOBALS['kpir_test_posts'] = array();
		$this->invoice              = new iworks_kpir_posttypes_invoice();
		$options                    = new ReflectionProperty(
			$this->invoice,
			'options'
		);
		$options->setValue( $this->invoice, new IworksKpirOptionsTestDouble() );
		$this->invoice->action_init_set_fields();
		$this->make_post( 1 );
	}

	/**
	 * Register a fake post in the in-memory post store.
	 *
	 * @param int    $id   Post ID.
	 * @param string $type Post type.
	 */
	private function make_post( int $id, string $type = 'iworks_kpir_invoice' ): void {
		$post                        = new stdClass();
		$post->ID                    = $id;
		$post->post_type             = $type;
		$GLOBALS['kpir_test_posts'][ $id ] = $post;
	}

	/**
	 * Build a basic $_POST payload for the invoice form.
	 *
	 * @param array $overrides Values keyed by field name.
	 * @return array Payload shaped like the admin form.
	 */
	private function payload( array $overrides = array() ): array {
		return array(
			'iworks_kpir_basic' => array_merge(
				array(
					'date'         => '2026-03-15',
					'ksef_number'  => '1234567890-20260315-ABCDEF123456-77',
					'ksef_type'    => 'BFK',
				),
				$overrides
			),
		);
	}

	/**
	 * Invoke save_post_meta_fields() with a payload.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $post_data Simulated $_POST contents.
	 */
	private function save( int $post_id, array $post_data ): void {
		$_POST = $post_data;
		$post  = get_post( $post_id );
		$this->invoice->save_post_meta( $post_id, $post, false );
		$_POST = array();
	}

	/**
	 * Create: saving a form stores all basic fields as post meta.
	 */
	public function test_create_stores_meta(): void {
		$this->save( 1, $this->payload() );
		$this->assertSame(
			strtotime( '2026-03-15' ),
			get_post_meta( 1, 'iworks_kpir_basic_date', true )
		);
		$this->assertSame(
			'1234567890-20260315-ABCDEF123456-77',
			get_post_meta( 1, 'iworks_kpir_basic_ksef_number', true )
		);
		$this->assertSame(
			'BFK',
			get_post_meta( 1, 'iworks_kpir_basic_ksef_type', true )
		);
	}

	/**
	 * Update: saving again overwrites previously stored meta.
	 */
	public function test_update_overwrites_meta(): void {
		$this->save( 1, $this->payload() );
		$this->save(
			1,
			$this->payload( array( 'ksef_number' => 'NEW-NUMBER' ) )
		);
		$this->assertSame(
			'NEW-NUMBER',
			get_post_meta( 1, 'iworks_kpir_basic_ksef_number', true )
		);
	}

	/**
	 * Delete: saving an empty value removes the stored meta.
	 */
	public function test_empty_value_deletes_meta(): void {
		$this->save( 1, $this->payload() );
		$this->assertArrayHasKey(
			'iworks_kpir_basic_ksef_number',
			$GLOBALS['kpir_test_meta'][1]
		);
		$this->save(
			1,
			$this->payload( array( 'ksef_number' => '' ) )
		);
		$this->assertArrayNotHasKey(
			'iworks_kpir_basic_ksef_number',
			$GLOBALS['kpir_test_meta'][1]
		);
	}

	/**
	 * Delete: an all-zero money value counts as empty and is removed.
	 */
	public function test_zero_money_value_deletes_meta(): void {
		$money = array( 'iworks_kpir_expense' => array( 'purchase' => array( 'integer' => 100, 'fractional' => 50 ) ) );
		$_POST = $money;
		$this->invoice->save_post_meta( 1, get_post( 1 ), false );
		$this->assertSame(
			array(
				'integer'    => 100,
				'fractional' => 50,
			),
			get_post_meta( 1, 'iworks_kpir_expense_purchase', true )
		);
		$_POST = array( 'iworks_kpir_expense' => array( 'purchase' => array( 'integer' => 0, 'fractional' => 0 ) ) );
		$this->invoice->save_post_meta( 1, get_post( 1 ), true );
		$_POST = array();
		$this->assertArrayNotHasKey(
			'iworks_kpir_expense_purchase',
			$GLOBALS['kpir_test_meta'][1]
		);
	}

	/**
	 * Wrong post type: nothing is saved for other post types.
	 */
	public function test_wrong_post_type_is_ignored(): void {
		$this->make_post( 2, 'post' );
		$this->save( 2, $this->payload() );
		$this->assertSame(
			'',
			get_post_meta( 2, 'iworks_kpir_basic_ksef_number', true )
		);
		$this->assertArrayNotHasKey(
			2,
			$GLOBALS['kpir_test_meta']
		);
	}

	/**
	 * The derived year_month field is written from the event date.
	 */
	public function test_year_month_is_derived_from_date(): void {
		$timestamp = strtotime( '2026-03-15' );
		$this->invoice->save_year_month_to_extra_field(
			1,
			'iworks_kpir_basic_date',
			$timestamp,
			'date',
			array( 'type' => 'date' )
		);
		$this->assertSame(
			'2026-03',
			get_post_meta( 1, 'iworks_kpir_year_month', true )
		);
	}

	/**
	 * The derived year_month_cash field mirrors year_month for
	 * non-income entries.
	 */
	public function test_year_month_cash_for_non_income(): void {
		update_post_meta( 1, 'iworks_kpir_basic_type', 'expense' );
		$this->invoice->save_year_month_to_extra_field(
			1,
			'iworks_kpir_basic_date',
			strtotime( '2026-03-15' ),
			'date',
			array( 'type' => 'date' )
		);
		$this->assertSame(
			'2026-03',
			get_post_meta( 1, 'iworks_kpir_year_month_cash', true )
		);
	}

	/**
	 * The derived year_month_cash field is not written for income
	 * entries without a cash date.
	 */
	public function test_year_month_cash_skipped_for_income(): void {
		update_post_meta( 1, 'iworks_kpir_basic_type', 'income' );
		$this->invoice->save_year_month_to_extra_field(
			1,
			'iworks_kpir_basic_date',
			strtotime( '2026-03-15' ),
			'date',
			array( 'type' => 'date' )
		);
		$this->assertSame(
			'2026-03',
			get_post_meta( 1, 'iworks_kpir_year_month', true )
		);
		$this->assertSame(
			'',
			get_post_meta( 1, 'iworks_kpir_year_month_cash', true )
		);
	}

	/**
	 * check_post_type_by_id() recognizes invoice posts.
	 */
	public function test_check_post_type_by_id(): void {
		$this->assertTrue( $this->invoice->check_post_type_by_id( 1 ) );
		$this->make_post( 2, 'post' );
		$this->assertFalse( $this->invoice->check_post_type_by_id( 2 ) );
		$this->assertFalse( $this->invoice->check_post_type_by_id( 999 ) );
	}
}
