<?php
/**
 * Unit tests for iworks_kpir_posttypes_contractor meta CRUD.
 *
 * Simulates the save_post flow of the contractor post type
 * (iworks_kpir_contract) without WordPress or database: post/meta
 * functions are backed by in-memory stores defined here, and the
 * options object is replaced by a lightweight double exposing
 * get_option_name(). Covers create, read, update, delete, post-type
 * mismatch handling and the columns helper.
 *
 * @package KPIR
 */

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 3 ) . '/includes/iworks/kpir/posttypes.php';
require_once dirname( __DIR__, 3 ) . '/includes/iworks/kpir/posttypes/contractor.php';

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

if ( ! function_exists( 'add_query_arg' ) ) {
	/**
	 * Stub: build URL with query args (test-only minimal version).
	 *
	 * @param array  $args Query args.
	 * @param string $url  Base URL.
	 * @return string URL with args appended.
	 */
	function add_query_arg( array $args, $url = '' ) {
		return $url . '?' . http_build_query( $args );
	}
}

/**
 * Options double: mirrors iworks_options::get_option_name() with the
 * real plugin prefix, without loading the full options module.
 */
class IworksKpirContractorOptionsTestDouble {

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
 * Test cases for contractor post type meta CRUD.
 */
#[TestDox( 'iWorks KPiR contractor post type meta CRUD' )]
class IworksKpirContractorCrudTest extends TestCase {

	/**
	 * Contractor post type handler under test.
	 *
	 * @var iworks_kpir_posttypes_contractor
	 */
	private $contractor;

	/**
	 * Prepare a fresh handler, empty stores and a fake contractor post
	 * before every test.
	 */
	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['kpir_test_meta']  = array();
		$GLOBALS['kpir_test_posts'] = array();
		$this->contractor           = new iworks_kpir_posttypes_contractor();
		$options                    = new ReflectionProperty(
			$this->contractor,
			'options'
		);
		$options->setValue(
			$this->contractor,
			new IworksKpirContractorOptionsTestDouble()
		);
		$this->contractor->action_init_set_fields();
		$this->make_post( 1 );
	}

	/**
	 * Register a fake post in the in-memory post store.
	 *
	 * @param int    $id   Post ID.
	 * @param string $type Post type.
	 */
	private function make_post( int $id, string $type = 'iworks_kpir_contract' ): void {
		$post                              = new stdClass();
		$post->ID                          = $id;
		$post->post_type                   = $type;
		$GLOBALS['kpir_test_posts'][ $id ] = $post;
	}

	/**
	 * Build a contractor_data $_POST payload.
	 *
	 * @param array $overrides Values keyed by field name.
	 * @return array Payload shaped like the admin form.
	 */
	private function payload( array $overrides = array() ): array {
		return array(
			'iworks_kpir_contractor_data' => array_merge(
				array(
					'full_name' => 'Acme sp. z o.o.',
					'street1'   => 'ul. Testowa 1',
					'zip'       => '00-001',
					'city'      => 'Warszawa',
					'country'   => 'Polska',
					'nip'       => '1234567890',
				),
				$overrides
			),
		);
	}

	/**
	 * Invoke save_post_meta() with a payload.
	 *
	 * @param int   $post_id   Post ID.
	 * @param array $post_data Simulated $_POST contents.
	 */
	private function save( int $post_id, array $post_data ): void {
		$_POST = $post_data;
		$post  = get_post( $post_id );
		$this->contractor->save_post_meta( $post_id, $post, false );
		$_POST = array();
	}

	/**
	 * Create: saving a form stores all contractor_data fields as post meta.
	 */
	#[TestDox( 'Create stores meta' )]
	public function test_create_stores_meta(): void {
		$this->save( 1, $this->payload() );
		$this->assertSame(
			'Acme sp. z o.o.',
			get_post_meta( 1, 'iworks_kpir_contractor_data_full_name', true )
		);
		$this->assertSame(
			'ul. Testowa 1',
			get_post_meta( 1, 'iworks_kpir_contractor_data_street1', true )
		);
		$this->assertSame(
			'00-001',
			get_post_meta( 1, 'iworks_kpir_contractor_data_zip', true )
		);
		$this->assertSame(
			'Warszawa',
			get_post_meta( 1, 'iworks_kpir_contractor_data_city', true )
		);
		$this->assertSame(
			'1234567890',
			get_post_meta( 1, 'iworks_kpir_contractor_data_nip', true )
		);
	}

	/**
	 * Read: fields not present in the payload are not stored.
	 */
	#[TestDox( 'Fields absent from payload are not stored' )]
	public function test_absent_fields_not_stored(): void {
		$this->save( 1, $this->payload() );
		$this->assertSame(
			'',
			get_post_meta( 1, 'iworks_kpir_contractor_data_regon', true )
		);
		$this->assertSame(
			'',
			get_post_meta( 1, 'iworks_kpir_contractor_data_krs', true )
		);
	}

	/**
	 * Update: saving again overwrites previously stored meta.
	 */
	#[TestDox( 'Update overwrites meta' )]
	public function test_update_overwrites_meta(): void {
		$this->save( 1, $this->payload() );
		$this->save(
			1,
			$this->payload( array( 'nip' => '9876543210' ) )
		);
		$this->assertSame(
			'9876543210',
			get_post_meta( 1, 'iworks_kpir_contractor_data_nip', true )
		);
		$this->assertSame(
			'Acme sp. z o.o.',
			get_post_meta( 1, 'iworks_kpir_contractor_data_full_name', true )
		);
	}

	/**
	 * Delete: saving an empty value removes the stored meta.
	 */
	#[TestDox( 'Empty value deletes meta' )]
	public function test_empty_value_deletes_meta(): void {
		$this->save( 1, $this->payload() );
		$this->assertArrayHasKey(
			'iworks_kpir_contractor_data_nip',
			$GLOBALS['kpir_test_meta'][1]
		);
		$this->save(
			1,
			$this->payload( array( 'nip' => '' ) )
		);
		$this->assertArrayNotHasKey(
			'iworks_kpir_contractor_data_nip',
			$GLOBALS['kpir_test_meta'][1]
		);
	}

	/**
	 * Wrong post type: nothing is saved for other post types.
	 */
	#[TestDox( 'Wrong post type is ignored' )]
	public function test_wrong_post_type_is_ignored(): void {
		$this->make_post( 2, 'post' );
		$this->save( 2, $this->payload() );
		$this->assertSame(
			'',
			get_post_meta( 2, 'iworks_kpir_contractor_data_nip', true )
		);
		$this->assertArrayNotHasKey(
			2,
			$GLOBALS['kpir_test_meta']
		);
	}

	/**
	 * The contact group saves into its own prefixed meta keys.
	 */
	#[TestDox( 'Contact group saves into its own meta keys' )]
	public function test_contact_group_saved(): void {
		$this->save(
			1,
			array(
				'iworks_kpir_contact' => array(
					'email'  => 'biuro@example.com',
					'phone'  => '+48 600 000 000',
					'website' => 'https://example.com',
				),
			)
		);
		$this->assertSame(
			'biuro@example.com',
			get_post_meta( 1, 'iworks_kpir_contact_email', true )
		);
		$this->assertSame(
			'+48 600 000 000',
			get_post_meta( 1, 'iworks_kpir_contact_phone', true )
		);
		$this->assertSame(
			'https://example.com',
			get_post_meta( 1, 'iworks_kpir_contact_website', true )
		);
	}

	/**
	 * add_columns() replaces the date column with full_name and NIP.
	 */
	#[TestDox( 'Columns helper adds full name and NIP' )]
	public function test_add_columns(): void {
		$columns = $this->contractor->add_columns(
			array(
				'title'   => 'Title',
				'date'    => 'Date',
				'cb'      => '<input type="checkbox" />',
			)
		);
		$this->assertArrayNotHasKey( 'date', $columns );
		$this->assertArrayHasKey( 'full_name', $columns );
		$this->assertArrayHasKey( 'nip', $columns );
		$this->assertArrayHasKey( 'title', $columns );
	}

	/**
	 * custom_columns() renders the full name and NIP meta values.
	 */
	#[TestDox( 'Custom columns render full name and NIP' )]
	public function test_custom_columns_render_meta(): void {
		update_post_meta( 1, 'iworks_kpir_contractor_data_full_name', 'Acme sp. z o.o.' );
		update_post_meta( 1, 'iworks_kpir_contractor_data_nip', '1234567890' );
		ob_start();
		$this->contractor->custom_columns( 'full_name', 1 );
		$this->assertSame( 'Acme sp. z o.o.', ob_get_clean() );
		ob_start();
		$this->contractor->custom_columns( 'nip', 1 );
		$this->assertSame( '1234567890', ob_get_clean() );
		ob_start();
		$this->contractor->custom_columns( 'unknown', 1 );
		$this->assertSame( '', ob_get_clean() );
	}
}
