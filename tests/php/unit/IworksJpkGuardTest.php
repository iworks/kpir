<?php
/**
 * Unit tests for iworks_kpir_jpk meta guards and contractor parsing.
 *
 * Covers the guards added to expense_row()/income_row() for invoices with
 * missing or malformed money/VAT meta, and the foreign-NIP country code
 * detection in get_contractor(). Run without WordPress or database:
 * get_post_meta() and get_option() are backed by a simple in-memory
 * stub store defined in this file.
 *
 * @package KPIR
 */

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 3 ) . '/includes/iworks/kpir/jpk.php';

/**
 * In-memory meta store shared by the get_post_meta()/update_post_meta()
 * stubs, so tests can simulate contractor and invoice meta.
 *
 * @var array<string, array<string, mixed>> $GLOBALS['kpir_test_meta']
 */
$GLOBALS['kpir_test_meta'] = array();

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
	 * Stub: read an option (always false — cash PIT off).
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
 * Concrete test double exposing the protected JPK base class methods.
 */
class IworksKpirJpkGuardTestDouble extends iworks_kpir_jpk {

	/**
	 * Public constructor delegating to the protected parent constructor.
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Expose expense_row() for testing.
	 *
	 * @param array $data Invoice data.
	 * @return array Processed row data.
	 */
	public function expense_row_public( array $data ): array {
		return $this->expense_row( $data );
	}

	/**
	 * Expose income_row() for testing.
	 *
	 * @param array $data Invoice data.
	 * @return array Processed row data.
	 */
	public function income_row_public( array $data ): array {
		return $this->income_row( $data );
	}

	/**
	 * Expose get_contractor() for testing.
	 *
	 * @param int $contractor_id Contractor post ID.
	 * @return array Contractor data.
	 */
	public function get_contractor_public( int $contractor_id ): array {
		return $this->get_contractor( $contractor_id );
	}
}

/**
 * Test cases for the JPK meta guards and contractor NIP parsing.
 */
#[TestDox('iWorks KPiR JPK meta guards and contractor parsing')]
class IworksJpkGuardTest extends TestCase {

	/**
	 * Test double instance.
	 *
	 * @var IworksKpirJpkGuardTestDouble
	 */
	private $jpk;

	/**
	 * Prepare a fresh double and empty meta store before every test.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->jpk                 = new IworksKpirJpkGuardTestDouble();
		$GLOBALS['kpir_test_meta'] = array();
		$reset                     = new ReflectionProperty(
			$this->jpk,
			'contractors'
		);
		$reset->setValue( $this->jpk, array() );
	}

	/**
	 * Build a row-data array with a valid basic date meta value.
	 *
	 * @param int   $id   Invoice post ID.
	 * @param array $meta Extra meta to set.
	 * @return array Row data.
	 */
	private function row_data( int $id, array $meta = array() ): array {
		update_post_meta( $id, 'iworks_kpir_basic_date', time() );
		foreach ( $meta as $key => $value ) {
			update_post_meta( $id, $key, $value );
		}
		return array( 'ID' => $id );
	}

	/**
	 * expense_row() falls back to zero amounts when purchase meta is missing.
	 */
	public function test_expense_row_missing_money_meta(): void {
		$data = $this->jpk->expense_row_public( $this->row_data( 1 ) );
		$this->assertSame(
			array(
				'integer'    => 0,
				'fractional' => 0,
			),
			$data['money']
		);
		$this->assertSame( '0.00', $data['K_42'] );
	}

	/**
	 * expense_row() falls back to zero amounts when purchase meta is a string.
	 */
	public function test_expense_row_string_money_meta(): void {
		update_post_meta( 2, 'iworks_kpir_expense_purchase', 'garbage' );
		$data = $this->jpk->expense_row_public( $this->row_data( 2 ) );
		$this->assertSame( '0.00', $data['K_42'] );
	}

	/**
	 * expense_row() keeps a valid money array untouched.
	 */
	public function test_expense_row_valid_money_meta(): void {
		update_post_meta(
			3,
			'iworks_kpir_expense_purchase',
			array(
				'integer'    => 12,
				'fractional' => 34,
			)
		);
		$data = $this->jpk->expense_row_public( $this->row_data( 3 ) );
		$this->assertSame( '12.34', $data['K_42'] );
	}

	/**
	 * expense_row() falls back to zero VAT when VAT meta is a string.
	 */
	#[TestDox('Expense row string VAT meta')]
	public function test_expense_row_string_vat_meta(): void {
		update_post_meta(
			4,
			'iworks_kpir_expense_purchase',
			array(
				'integer'    => 5,
				'fractional' => 0,
			)
		);
		update_post_meta( 4, 'iworks_kpir_expense_vat', 'oops' );
		$data = $this->jpk->expense_row_public( $this->row_data( 4 ) );
		$this->assertSame( '0.00', $data['K_43'] ?? '' );
	}

	/**
	 * income_row() falls back to zero amounts when sale meta is missing.
	 */
	public function test_income_row_missing_money_meta(): void {
		$data = $this->jpk->income_row_public( $this->row_data( 5, array( 'iworks_kpir_income_vat_type' => 'c00' ) ) );
		$this->assertSame(
			array(
				'integer'    => 0,
				'fractional' => 0,
			),
			$data['money']
		);
		$this->assertSame( '0.00', $data['K_10'] );
	}

	/**
	 * income_row() falls back to zero VAT when VAT meta is missing.
	 */
	#[TestDox('Income row missing VAT meta')]
	public function test_income_row_missing_vat_meta(): void {
		update_post_meta(
			6,
			'iworks_kpir_income_sale',
			array(
				'integer'    => 7,
				'fractional' => 89,
			)
		);
		$data = $this->jpk->income_row_public( $this->row_data( 6, array( 'iworks_kpir_income_vat_type' => 'c06' ) ) );
		$this->assertSame( '0.00', $data['K_20'] );
	}

	/**
	 * get_contractor() extracts a leading two-letter country code from the NIP.
	 */
	#[TestDox('Get contractor foreign NIP')]
	public function test_get_contractor_foreign_nip(): void {
		update_post_meta( 7, 'iworks_kpir_contractor_data_nip', 'CZ12345678' );
		$contractor = $this->jpk->get_contractor_public( 7 );
		$this->assertSame( 'CZ', $contractor['country'] );
		$this->assertSame( '12345678', $contractor['nip'] );
	}

	/**
	 * get_contractor() defaults the country to PL for a plain numeric NIP.
	 */
	#[TestDox('Get contractor PL NIP')]
	public function test_get_contractor_pl_nip(): void {
		update_post_meta( 8, 'iworks_kpir_contractor_data_nip', '1234567890' );
		$contractor = $this->jpk->get_contractor_public( 8 );
		$this->assertSame( 'PL', $contractor['country'] );
		$this->assertSame( '1234567890', $contractor['nip'] );
	}

	/**
	 * get_contractor() uppercases a lowercase country prefix.
	 */
	#[TestDox('Get contractor lowercase NIP prefix')]
	public function test_get_contractor_lowercase_prefix(): void {
		update_post_meta( 9, 'iworks_kpir_contractor_data_nip', 'de 123456' );
		$contractor = $this->jpk->get_contractor_public( 9 );
		$this->assertSame( 'DE', $contractor['country'] );
		$this->assertSame( '123456', $contractor['nip'] );
	}

	/**
	 * get_contractor() reports "brak" and keeps PL for an empty NIP.
	 */
	#[TestDox('Get contractor empty NIP')]
	public function test_get_contractor_empty_nip(): void {
		$contractor = $this->jpk->get_contractor_public( 10 );
		$this->assertSame( 'PL', $contractor['country'] );
		$this->assertSame( 'brak', $contractor['nip'] );
	}
}
