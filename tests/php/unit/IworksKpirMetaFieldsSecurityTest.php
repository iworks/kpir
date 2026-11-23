<?php
/**
 * Unit tests for post type meta field definitions against security.
 *
 * Verifies that the field definitions of the invoice and contractor
 * post types declare known types only, that option names generated
 * from them are prefix-safe, and that the save path (save_post_meta)
 * neutralizes XSS/SQLi probe payloads before they reach post meta.
 *
 * @package KPIR
 */

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 3 ) . '/includes/iworks/kpir/posttypes.php';
require_once dirname( __DIR__, 3 ) . '/includes/iworks/kpir/posttypes/invoice.php';
require_once dirname( __DIR__, 3 ) . '/includes/iworks/kpir/posttypes/contractor.php';

/**
 * Options double: mirrors iworks_options::get_option_name() with the
 * real plugin prefix, without loading the full options module.
 */
class IworksKpirMetaFieldsOptionsTestDouble {

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
 * Test cases for post type meta field security.
 */
#[TestDox( 'iWorks KPiR post type meta fields security' )]
class IworksKpirMetaFieldsSecurityTest extends TestCase {

	/**
	 * Known field types across the plugin's post types.
	 *
	 * @var array
	 */
	private array $known_types = array(
		'text',
		'textarea',
		'radio',
		'checkbox',
		'checkbox_group',
		'email',
		'select2',
		'date',
		'money',
		'description',
		'wpColorPicker',
	);

	/**
	 * XSS/SQLi probe payloads for the save path.
	 *
	 * @var array
	 */
	private array $probes = array(
		'script' => '<script>alert("x")</script>',
		'img'    => '<img src=x onerror=alert(1)>',
		'sqli'   => "'; DROP TABLE wp_postmeta; --",
	);

	/**
	 * Invoice post type handler.
	 *
	 * @var iworks_kpir_posttypes_invoice
	 */
	private iworks_kpir_posttypes_invoice $invoice;

	/**
	 * Contractor post type handler.
	 *
	 * @var iworks_kpir_posttypes_contractor
	 */
	private iworks_kpir_posttypes_contractor $contractor;

	/**
	 * Prepare fresh handlers, stores and fake posts.
	 */
	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['kpir_test_meta']  = array();
		$GLOBALS['kpir_test_posts'] = array();
		$this->invoice              = new iworks_kpir_posttypes_invoice();
		$this->contractor           = new iworks_kpir_posttypes_contractor();
		$set                        = new ReflectionProperty(
			$this->invoice,
			'options'
		);
		$set->setValue(
			$this->invoice,
			new IworksKpirMetaFieldsOptionsTestDouble()
		);
		$set = new ReflectionProperty(
			$this->contractor,
			'options'
		);
		$set->setValue(
			$this->contractor,
			new IworksKpirMetaFieldsOptionsTestDouble()
		);
		$this->invoice->action_init_set_fields();
		$this->contractor->action_init_set_fields();
	}

	/**
	 * Every invoice field declares a known type (or none — defaults
	 * to text in get_meta_box_content).
	 */
	#[TestDox( 'Invoice fields declare known types' )]
	public function test_invoice_field_types(): void {
		$fields = new ReflectionProperty( $this->invoice, 'fields' );
		foreach ( $fields->getValue( $this->invoice ) as $group => $group_data ) {
			foreach ( $group_data as $key => $data ) {
				if ( 'description' === ( $data['type'] ?? '' ) ) {
					continue;
				}
				$this->assertContains(
					$data['type'] ?? 'text',
					$this->known_types,
					sprintf(
						'Invoice field %s.%s has unknown type "%s".',
						$group,
						$key,
						$data['type'] ?? '(none)'
					)
				);
			}
		}
	}

	/**
	 * Meta keys derived from field names are prefix-safe.
	 */
	#[TestDox( 'Meta keys derived from fields are prefix-safe' )]
	public function test_meta_keys_are_prefix_safe(): void {
		$fields = new ReflectionProperty( $this->invoice, 'fields' );
		foreach ( $fields->getValue( $this->invoice ) as $group => $group_data ) {
			$this->assertMatchesRegularExpression(
				'/^[a-z0-9_]+$/',
				$group,
				sprintf( 'Invoice field group "%s" is not prefix-safe.', $group )
			);
			foreach ( array_keys( $group_data ) as $key ) {
				$this->assertMatchesRegularExpression(
					'/^[a-z0-9_]+$/',
					$key,
					sprintf(
						'Invoice field key "%s.%s" is not prefix-safe.',
						$group,
						$key
					)
				);
			}
		}
		$fields = new ReflectionProperty( $this->contractor, 'fields' );
		foreach ( $fields->getValue( $this->contractor ) as $group => $group_data ) {
			$this->assertMatchesRegularExpression(
				'/^[a-z0-9_]+$/',
				$group
			);
			foreach ( array_keys( $group_data ) as $key ) {
				$this->assertMatchesRegularExpression(
					'/^[a-z0-9_]+$/',
					$key
				);
			}
		}
	}

	/**
	 * Saving a form with XSS probes must not store raw markup in
	 * post meta — strings pass through wp_kses_post() + trim().
	 */
	#[TestDox( 'Save path strips raw markup from string fields' )]
	public function test_save_strips_markup(): void {
		$this->make_post( 1 );
		foreach ( $this->probes as $probe ) {
			$_POST = array(
				'iworks_kpir_basic' => array(
					'ksef_number' => $probe,
				),
			);
			$this->invoice->save_post_meta( 1, get_post( 1 ), false );
			$stored = get_post_meta( 1, 'iworks_kpir_basic_ksef_number', true );
			$this->assertStringNotContainsString(
				'<',
				(string) $stored,
				sprintf( 'Markup survived in ksef_number: %s', $probe )
			);
		}
		$_POST = array();
	}

	/**
	 * Money fields accept only integer/fractional arrays; a probe
	 * string must not be stored raw (money arrays are not string
	 * sanitized, so anything non-array is stored as-is only when the
	 * form sends an array — verify the contract: an array of
	 * integers is stored, zero arrays are deleted).
	 */
	#[TestDox( 'Money fields store only integer/fractional arrays' )]
	public function test_money_field_contract(): void {
		$this->make_post( 1 );
		// valid money array is stored
		$_POST = array(
			'iworks_kpir_expense' => array(
				'purchase' => array(
					'integer'    => 100,
					'fractional' => 50,
				),
			),
		);
		$this->invoice->save_post_meta( 1, get_post( 1 ), false );
		$stored = get_post_meta( 1, 'iworks_kpir_expense_purchase', true );
		$this->assertIsArray( $stored );
		$this->assertSame( 100, $stored['integer'] );
		$this->assertSame( 50, $stored['fractional'] );
		// probe markup inside a money array is cast to int: with
		// fractional 0 the all-zero rule deletes the meta instead of
		// storing raw markup
		$_POST = array(
			'iworks_kpir_expense' => array(
				'purchase' => array(
					'integer'    => '<script>x</script>',
					'fractional' => 0,
				),
			),
		);
		$this->invoice->save_post_meta( 1, get_post( 1 ), true );
		$stored = get_post_meta( 1, 'iworks_kpir_expense_purchase', true );
		$this->assertStringNotContainsString(
			'<',
			(string) $stored,
			'Markup survived in a money array integer part.'
		);
		// probe with non-zero fractional part is stored as integers
		update_post_meta( 1, 'iworks_kpir_expense_purchase', '' );
		$_POST = array(
			'iworks_kpir_expense' => array(
				'purchase' => array(
					'integer'    => '<script>x</script>',
					'fractional' => 50,
				),
			),
		);
		$this->invoice->save_post_meta( 1, get_post( 1 ), true );
		$stored = get_post_meta( 1, 'iworks_kpir_expense_purchase', true );
		$this->assertSame(
			array(
				'integer'    => 0,
				'fractional' => 50,
			),
			$stored,
			'Money array parts were not cast to integers.'
		);
		$_POST = array();
	}

	/**
	 * Date fields store Unix timestamps only — a probe string is
	 * rejected by strtotime() and never stored raw.
	 */
	#[TestDox( 'Date fields store timestamps only' )]
	public function test_date_field_contract(): void {
		$this->make_post( 1 );
		$_POST = array(
			'iworks_kpir_basic' => array(
				'date' => '2026-03-15',
			),
		);
		$this->invoice->save_post_meta( 1, get_post( 1 ), false );
		$this->assertSame(
			strtotime( '2026-03-15' ),
			get_post_meta( 1, 'iworks_kpir_basic_date', true )
		);
		$_POST = array(
			'iworks_kpir_basic' => array(
				'date' => "2026-03-15'; DROP TABLE wp_postmeta; --",
			),
		);
		$this->invoice->save_post_meta( 1, get_post( 1 ), true );
		$stored = get_post_meta( 1, 'iworks_kpir_basic_date', true );
		// strtotime() converts the probe to an int timestamp or fails;
		// either way the SQLi text must never be stored raw.
		$this->assertStringNotContainsString(
			'DROP',
			(string) $stored,
			'SQLi probe survived in a date field.'
		);
		$this->assertStringNotContainsString(
			'wp_postmeta',
			(string) $stored,
			'SQLi probe survived in a date field.'
		);
		$_POST = array();
	}

	/**
	 * Register a fake post in the in-memory post store.
	 *
	 * @param int    $id   Post ID.
	 * @param string $type Post type.
	 */
	private function make_post( int $id, string $type = 'iworks_kpir_invoice' ): void {
		$post                              = new stdClass();
		$post->ID                          = $id;
		$post->post_type                   = $type;
		$GLOBALS['kpir_test_posts'][ $id ] = $post;
	}
}
