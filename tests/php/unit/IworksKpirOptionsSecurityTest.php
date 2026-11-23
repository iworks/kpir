<?php
/**
 * Unit tests for the plugin option definitions (etc/options.php).
 *
 * Security-oriented checks: every registered option must have a name
 * with the plugin prefix applied by the options module, a known type,
 * and a callable sanitize_callback — so no option can be stored from
 * unsanitized request data. Also runs each sanitize callback against
 * XSS/SQLi probe payloads.
 *
 * @package KPIR
 */

use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 3 ) . '/etc/options.php';

/**
 * Test cases for plugin option security.
 */
#[TestDox( 'iWorks KPiR plugin option security' )]
class IworksKpirOptionsSecurityTest extends TestCase {

	/**
	 * The option definitions from etc/options.php.
	 *
	 * @var array
	 */
	private array $options;

	/**
	 * Load the option definitions once.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->options = iworks_kpir_options();
	}

	/**
	 * The options file returns a non-empty option group.
	 */
	#[TestDox( 'Options file returns a non-empty index group' )]
	public function test_options_structure(): void {
		$this->assertArrayHasKey( 'index', $this->options );
		$this->assertArrayHasKey( 'options', $this->options['index'] );
		$this->assertNotEmpty( $this->options['index']['options'] );
	}

	/**
	 * Every option must declare a name and a known type.
	 */
	#[TestDox( 'Every option has a name and a known type' )]
	public function test_every_option_has_name_and_type(): void {
		$known = array(
			'text',
			'textarea',
			'radio',
			'checkbox',
			'checkbox_group',
			'email',
			'select2',
			'wpColorPicker',
			'string',
			'boolean',
			'integer',
			'number',
			'array',
			'object',
			'subheading',
			'heading',
		);
		foreach ( $this->options['index']['options'] as $option ) {
			$this->assertIsArray( $option );
			$this->assertArrayHasKey(
				'name',
				$option,
				'Option without a name found.'
			);
			$this->assertNotEmpty( $option['name'] );
			$this->assertArrayHasKey(
				'type',
				$option,
				sprintf( 'Option "%s" has no type.', $option['name'] )
			);
			$this->assertContains(
				$option['type'],
				$known,
				sprintf(
					'Option "%s" has unknown type "%s".',
					$option['name'],
					$option['type']
				)
			);
		}
	}

	/**
	 * Every option must have a callable sanitize_callback so that
	 * register_setting() never stores unsanitized request data.
	 */
	#[TestDox( 'Every option has a callable sanitize callback' )]
	public function test_every_option_has_sanitize_callback(): void {
		foreach ( $this->options['index']['options'] as $option ) {
			$this->assertArrayHasKey(
				'sanitize_callback',
				$option,
				sprintf(
					'Option "%s" is missing a sanitize_callback.',
					$option['name'] ?? '(unknown)'
				)
			);
			$this->assertTrue(
				is_callable( $option['sanitize_callback'] ),
				sprintf(
					'Option "%s" has a non-callable sanitize_callback.',
					$option['name'] ?? '(unknown)'
				)
			);
		}
	}

	/**
	 * Every sanitize callback must neutralize XSS and SQL injection
	 * probes: the stored value must not contain raw markup or quotes.
	 */
	#[TestDox( 'Sanitize callbacks neutralize XSS and SQLi probes' )]
	public function test_sanitize_callbacks_against_probes(): void {
		$probes = array(
			'script' => '<script>alert("x")</script>',
			'img'    => '<img src=x onerror=alert(1)>',
			'quote'  => "'; DROP TABLE wp_options; --",
			'attrs'  => '" onload="alert(1)',
		);
		foreach ( $this->options['index']['options'] as $option ) {
			foreach ( $probes as $probe ) {
				$sanitized = call_user_func(
					$option['sanitize_callback'],
					$probe
				);
				$this->assertStringNotContainsString(
					'<',
					(string) $sanitized,
					sprintf(
						'Option "%s": markup survived sanitization.',
						$option['name']
					)
				);
				$this->assertStringNotContainsString(
					'"',
					(string) $sanitized,
					sprintf(
						'Option "%s": raw quotes survived sanitization.',
						$option['name']
					)
				);
			}
		}
	}

	/**
	 * Checkbox options must sanitize to integers only.
	 */
	#[TestDox( 'Checkbox options sanitize to integers' )]
	public function test_checkbox_options_sanitize_to_int(): void {
		foreach ( $this->options['index']['options'] as $option ) {
			if ( 'checkbox' !== $option['type'] ) {
				continue;
			}
			$sanitized = call_user_func(
				$option['sanitize_callback'],
				'1; DROP TABLE wp_posts'
			);
			$this->assertIsInt(
				$sanitized,
				sprintf(
					'Checkbox option "%s" did not sanitize to an integer.',
					$option['name']
				)
			);
		}
	}

	/**
	 * Option names must be lowercase keys suitable for the
	 * iworks_kpir_ prefix (no spaces, no unexpected characters).
	 */
	#[TestDox( 'Option names are prefix-safe' )]
	public function test_option_names_are_prefix_safe(): void {
		foreach ( $this->options['index']['options'] as $option ) {
			$this->assertMatchesRegularExpression(
				'/^[a-z0-9_]+$/',
				$option['name'],
				sprintf(
					'Option name "%s" contains unexpected characters.',
					$option['name']
				)
			);
		}
	}
}
