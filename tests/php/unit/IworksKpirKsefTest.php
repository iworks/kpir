<?php
/**
 * Unit tests for iworks_kpir_ksef::get_value_from_xml().
 *
 * Verifies the primary and fallback KSeF XML field mapping without
 * WordPress or database. The method is private, so it is invoked via
 * Reflection (supported since PHP 8.1 without setAccessible()).
 *
 * @package KPIR
 */

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 3 ) . '/includes/iworks/kpir/class-iworks-kpir-ksef.php';

/**
 * Test cases for the KSeF XML field value resolver.
 */
class IworksKpirKsefTest extends TestCase {

	/**
	 * KSeF handler instance under test.
	 *
	 * @var iworks_kpir_ksef
	 */
	private $ksef;

	/**
	 * Prepare a fresh handler before every test.
	 *
	 * The constructor only registers hooks, which are stubbed as no-ops.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->ksef = new iworks_kpir_ksef( array() );
	}

	/**
	 * Invoke the private get_value_from_xml() method.
	 *
	 * @param SimpleXMLElement $xml   Parsed XML document.
	 * @param array            $field Field definition.
	 * @return string Resolved value.
	 */
	private function resolve( SimpleXMLElement $xml, array $field ): string {
		$method = new ReflectionMethod( $this->ksef, 'get_value_from_xml' );
		return $method->invoke( $this->ksef, $xml, $field );
	}

	/**
	 * Primary mapping wins when the element exists.
	 */
	public function test_primary_mapping_wins(): void {
		$xml   = simplexml_load_string( '<F><Fa><P_1>2026-05-01</P_1><P_6>2026-04-28</P_6></Fa></F>' );
		$field = array(
			'ksef_xml_name'          => 'Fa,P_6',
			'ksef_xml_name_fallback' => 'Fa,P_1',
		);
		$this->assertSame( '2026-04-28', $this->resolve( $xml, $field ) );
	}

	/**
	 * Fallback mapping is used when the primary element is missing.
	 */
	public function test_fallback_mapping_when_primary_missing(): void {
		$xml   = simplexml_load_string( '<F><Fa><P_1>2026-05-01</P_1></Fa></F>' );
		$field = array(
			'ksef_xml_name'          => 'Fa,P_6',
			'ksef_xml_name_fallback' => 'Fa,P_1',
		);
		$this->assertSame( '2026-05-01', $this->resolve( $xml, $field ) );
	}

	/**
	 * Empty string is returned when neither mapping resolves.
	 */
	public function test_empty_when_nothing_matches(): void {
		$xml   = simplexml_load_string( '<F><Fa><P_2>FV/3</P_2></Fa></F>' );
		$field = array(
			'ksef_xml_name'          => 'Fa,P_6',
			'ksef_xml_name_fallback' => 'Fa,P_1',
		);
		$this->assertSame( '', $this->resolve( $xml, $field ) );
	}

	/**
	 * Fields without a fallback key use the primary mapping only.
	 */
	public function test_no_fallback_key_primary_hit(): void {
		$xml   = simplexml_load_string( '<F><Fa><P_2>FV/1</P_2></Fa></F>' );
		$field = array( 'ksef_xml_name' => 'Fa,P_2' );
		$this->assertSame( 'FV/1', $this->resolve( $xml, $field ) );
	}

	/**
	 * Fields without a fallback key return an empty string on a miss.
	 */
	public function test_no_fallback_key_primary_miss(): void {
		$xml   = simplexml_load_string( '<F><Fa></Fa></F>' );
		$field = array( 'ksef_xml_name' => 'Fa,P_2' );
		$this->assertSame( '', $this->resolve( $xml, $field ) );
	}

	/**
	 * Deeply nested paths are resolved (e.g. Fa,FaWiersz,P_7).
	 */
	public function test_nested_path(): void {
		$xml   = simplexml_load_string( '<F><Fa><FaWiersz><P_7>Widget</P_7></FaWiersz></Fa></F>' );
		$field = array( 'ksef_xml_name' => 'Fa,FaWiersz,P_7' );
		$this->assertSame( 'Widget', $this->resolve( $xml, $field ) );
	}

	/**
	 * A deeply nested missing element does not emit warnings.
	 */
	public function test_nested_path_miss_without_warnings(): void {
		$xml   = simplexml_load_string( '<F><Fa></Fa></F>' );
		$field = array( 'ksef_xml_name' => 'Fa,FaWiersz,P_7' );
		$this->assertSame( '', $this->resolve( $xml, $field ) );
	}
}
