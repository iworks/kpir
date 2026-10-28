<?php
/**
 * Unit tests for iworks_kpir_jpk::normalize_money().
 *
 * Characterization tests of the money normalization used by JPK reports,
 * run without WordPress or database. The base class is abstract with a
 * protected constructor, so a concrete test double exposes the method.
 *
 * @package KPIR
 */

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 3 ) . '/includes/iworks/kpir/jpk.php';

/**
 * Concrete test double exposing the protected JPK base class methods.
 */
class IworksKpirJpkTestDouble extends iworks_kpir_jpk {

	/**
	 * Public constructor delegating to the protected parent constructor.
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Expose normalize_money() for testing.
	 *
	 * @param array $money Money array with integer and fractional parts.
	 * @return array Normalized money array.
	 */
	public function normalize_money_public( array $money ): array {
		return $this->normalize_money( $money );
	}
}

/**
 * Test cases for JPK money normalization.
 */
class IworksJpkTest extends TestCase {

	/**
	 * Test double instance.
	 *
	 * @var IworksKpirJpkTestDouble
	 */
	private $jpk;

	/**
	 * Prepare a fresh double before every test.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->jpk = new IworksKpirJpkTestDouble();
	}

	/**
	 * Fractional part of 100 or more carries over into the integer part.
	 */
	public function test_carry_over(): void {
		$result = $this->jpk->normalize_money_public(
			array(
				'integer'    => 5,
				'fractional' => 150,
			)
		);
		$this->assertSame( 6, $result['integer'] );
		$this->assertSame( 50, $result['fractional'] );
		$this->assertEqualsWithDelta( 7.0, $result['intval'], 0.001 );
	}

	/**
	 * Multiple hundreds in the fractional part carry over at once.
	 */
	public function test_multiple_carry_over(): void {
		$result = $this->jpk->normalize_money_public(
			array(
				'integer'    => 2,
				'fractional' => 250,
			)
		);
		$this->assertSame( 4, $result['integer'] );
		$this->assertSame( 50, $result['fractional'] );
		$this->assertEqualsWithDelta( 5.0, $result['intval'], 0.001 );
	}

	/**
	 * Whole amounts pass through unchanged.
	 */
	public function test_whole_amount_unchanged(): void {
		$result = $this->jpk->normalize_money_public(
			array(
				'integer'    => 10,
				'fractional' => 0,
			)
		);
		$this->assertSame( 10, $result['integer'] );
		$this->assertSame( 0, $result['fractional'] );
		$this->assertEqualsWithDelta( 10.0, $result['intval'], 0.001 );
	}

	/**
	 * Cents-only amounts keep the integer part at zero.
	 */
	public function test_cents_only(): void {
		$result = $this->jpk->normalize_money_public(
			array(
				'integer'    => 0,
				'fractional' => 23,
			)
		);
		$this->assertSame( 0, $result['integer'] );
		$this->assertSame( 23, $result['fractional'] );
		$this->assertEqualsWithDelta( 0.0, $result['intval'], 0.001 );
	}

	/**
	 * The intval field is a rounded sum of both parts.
	 */
	public function test_intval_rounding(): void {
		$result = $this->jpk->normalize_money_public(
			array(
				'integer'    => 1,
				'fractional' => 99,
			)
		);
		$this->assertSame( 1, $result['integer'] );
		$this->assertSame( 99, $result['fractional'] );
		$this->assertEqualsWithDelta( 2.0, $result['intval'], 0.001 );
	}
}
