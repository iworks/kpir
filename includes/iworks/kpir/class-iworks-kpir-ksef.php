<?php
defined( 'ABSPATH' ) || exit;

if ( class_exists( 'iworks_kpir_ksef' ) ) {
	return;
}

/**
 * KSeF (Krajowy System e-Faktur) Import Handler.
 *
 * Handles the import of invoices from the Polish National e-Invoice System (KSeF).
 * Processes XML files from KSeF and creates corresponding invoice posts in WordPress.
 *
 * @since 1.2.1
 */
class iworks_kpir_ksef {


	/**
	 * Options object instance.
	 *
	 * @var iworks_kpir_options
	 */
	private $options;

	/**
	 * Filename for the import file field.
	 *
	 * @var string
	 */
	private string $filename;

	/**
	 * Objects container.
	 *
	 * @var array
	 */
	private array $objects;


	/**
	 * Notices array.
	 *
	 * @var array
	 */
	private array $notices = array();

	/**
	 * Constructor.
	 *
	 * Initializes the KSeF handler and hooks into the import page actions.
	 *
	 * @since 1.2.1
	 *
	 * @param array $objects Container of plugin objects including post type classes.
	 */
	public function __construct( $objects ) {
		$this->objects = $objects;
		add_action( 'iworks/kpir/admin/page/ksef/import', array( $this, 'run' ) );
		add_action( 'wp_loaded', array( $this, 'handle_import' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}

	/**
	 * Run the KSeF import process.
	 *
	 * @since 1.2.1
	 */
	public function run() {
		$this->init();
		$this->render_form();
	}

	/**
	 * Initialize the KSeF handler.
	 *
	 * Sets up the options instance and retrieves the import file field name.
	 *
	 * @since 1.2.1
	 */
	private function init() {
		$this->options  = iworks_kpir_get_options();
		$this->filename = $this->options->get_option_name( 'import_file' );
	}

	/**
	 * Handle the KSeF import form submission.
	 *
	 * Processes the uploaded XML file, validates the nonce, extracts the KSeF ID,
	 * parses the XML, and delegates to process_xml for invoice creation.
	 * Displays success or error messages based on the import result.
	 *
	 * @since 1.2.1
	 */
	public function handle_import() {
		if ( ! isset( $_POST['ksef_import_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( $_POST['ksef_import_nonce'], 'ksef_import_action' ) ) {
			$this->add_notice(
				new WP_Error( 'invalid_nonce', __( 'Invalid security token. Please try again.', 'kpir' ) ),
				'error'
			);
			return;
		}
		$this->init();
		if ( isset( $_FILES[ $this->filename ] ) ) {
			$file = $_FILES[ $this->filename ];
			if ( $file['error'] === UPLOAD_ERR_OK ) {
				$ksef_id      = basename( $file['name'], '.xml' );
				$file_content = file_get_contents( $file['tmp_name'] );
				$xml          = simplexml_load_string( $file_content );
				$this->process_xml( $xml, $ksef_id );
			}
		}
	}

	/**
	 * Process the KSeF XML data.
	 *
	 * Extracts seller and buyer data from the XML, validates NIP (tax ID) numbers,
	 * and determines whether to import as a sale or purchase invoice.
	 *
	 * @since 1.2.1
	 *
	 * @param SimpleXMLElement $xml    The parsed XML object from KSeF.
	 * @param string           $ksef_id The KSeF invoice identifier.
	 * @return int|WP_Error|void Post ID on success, WP_Error on failure, or void for sales.
	 */
	private function process_xml( $xml, $ksef_id ) {
		$seller = $xml->Podmiot1;
		$buyer  = $xml->Podmiot2;
		if ( ! $seller || ! $buyer ) {
			$this->add_notice(
				new WP_Error( 'missing_data', __( 'Missing seller or buyer data in the XML file.', 'kpir' ) ),
				'error'
			);
			return;
		}
		$nip        = $this->options->get_option( 'nip' );
		$seller_nip = (string) $seller->DaneIdentyfikacyjne->NIP[0];
		$buyer_nip  = (string) $buyer->DaneIdentyfikacyjne->NIP[0];

		/**
		 * sale
		 */
		if ( $seller_nip === $nip ) {
			if ( $this->is_invoice_already_imported( $ksef_id ) ) {
				return;
			}
			$this->import_income( $xml, $ksef_id );
			return;
		}
		/**
		 * purchase
		 */
		if ( $buyer_nip === $nip ) {
			if ( $this->is_invoice_already_imported( $ksef_id ) ) {
				return;
			}
			$this->import_expense( $xml, $ksef_id );
			return;
		}
		/**
		 * handle mismatch
		 */
			$this->add_notice(
				new WP_Error(
					'mismatch_nip',
					esc_html__( 'Mismatch in the seller\'s tax identification number.', 'kpir' )
				),
				'error'
			);
	}

	/**
	 * Import a purchase invoice from KSeF XML.
	 *
	 * Creates a new invoice post from the KSeF XML data for purchase invoices.
	 * Sets the post status to 'imported' and maps XML fields to invoice meta fields
	 * using the ksef_xml_name configuration. Handles special field types like dates
	 * and monetary amounts.
	 *
	 * @since 1.2.1
	 *
	 * @param SimpleXMLElement $xml    The parsed XML object from KSeF.
	 * @param string           $ksef_id The KSeF invoice identifier.
	 * @return int|WP_Error Post ID on success, WP_Error on failure.
	 */
	private function import_expense( $xml, $ksef_id ) {
		$seller_nip    = (string) $xml->Podmiot1->DaneIdentyfikacyjne->NIP[0];
		$invoice_class = $this->objects['post_type_invoice'];
		$fields        = $invoice_class->get_fields();

		$post_data = array(
			'post_title'  => (string) $xml->Fa->P_2,
			'post_type'   => $invoice_class->get_name(),
			'post_status' => 'imported',
			'meta_input'  => array(
				'iworks_kpir_basic_contractor'  => $invoice_class->get_contractor_id_by_nip( $seller_nip ),
				'iworks_kpir_basic_ksef_type'   => 'BFK',
				'iworks_kpir_basic_ksef_number' => $ksef_id,
			),
		);
		foreach ( $fields as $group => $fields ) {
			/**
			 * Skip income group for purchase invoices.
			 */
			if ( 'income' === $group ) {
				continue;
			}
			foreach ( $fields as $slug => $field ) {
				if ( ! isset( $field['ksef_xml_name'] ) ) {
					continue;
				}
				$names = explode( ',', $field['ksef_xml_name'] );
				$value = $xml;
				while ( ! empty( $names ) ) {
					$name  = array_shift( $names );
					$value = $value->$name;
				}
				$value = (string) $value;
				$meta  = sprintf( 'iworks_kpir_%s_%s', $group, $slug );
				switch ( $meta ) {
					case 'iworks_kpir_basic_date_of_issue':
					case 'iworks_kpir_basic_date':
						$value = strtotime( $value );
						break;
					case 'iworks_kpir_expense_purchase':
					case 'iworks_kpir_expense_vat':
						$parts = explode( '.', number_format( $value, 2, '.', '' ) );
						$value = array(
							'integer'    => (int) $parts[0],
							'fractional' => (int) $parts[1],
						);
						break;
				}
				if ( ! empty( $value ) ) {
					$post_data['meta_input'][ $meta ] = $value;
				}
			}
		}
		$post_id = wp_insert_post( $post_data );
		if ( is_wp_error( $post_id ) ) {
			$this->add_notice( $post_id, 'error' );
			return;
		}
		$this->add_notice(
			sprintf(
				esc_html__( 'Imported KSeF invoice: %s.', 'kpir' ),
				sprintf(
					'<a href="%s">%s - KSeF: %s</a>',
					get_edit_post_link( $post_id ),
					get_the_title( $post_id ),
					$ksef_id
				)
			),
			'success'
		);
	}

	/**
	 * Import a sale invoice from KSeF XML.
	 *
	 * Creates a new invoice post from the KSeF XML data for sale invoices.
	 * Sets the post status to 'imported' and maps XML fields to invoice meta fields
	 * using the ksef_xml_name configuration. Handles special field types like dates
	 * and monetary amounts.
	 *
	 * @since 1.2.1
	 *
	 * @param SimpleXMLElement $xml    The parsed XML object from KSeF.
	 * @param string           $ksef_id The KSeF invoice identifier.
	 * @return void
	 */
	private function import_income( $xml, $ksef_id ) {
		$buyer_nip     = (string) $xml->Podmiot2->DaneIdentyfikacyjne->NIP[0];
		$invoice_class = $this->objects['post_type_invoice'];
		$fields        = $invoice_class->get_fields();

		$post_data = array(
			'post_title'  => (string) $xml->Fa->P_2,
			'post_type'   => $invoice_class->get_name(),
			'post_status' => 'imported',
			'meta_input'  => array(
				'iworks_kpir_basic_contractor'  => $invoice_class->get_contractor_id_by_nip( $buyer_nip ),
				'iworks_kpir_basic_ksef_type'   => 'BFK',
				'iworks_kpir_basic_ksef_number' => $ksef_id,
				'iworks_kpir_basic_type'        => 'income',
			),
		);
		foreach ( $fields as $group => $fields ) {
			if ( 'expense' === $group ) {
				continue;
			}
			foreach ( $fields as $slug => $field ) {
				if ( ! isset( $field['ksef_xml_name'] ) ) {
					continue;
				}
				$names = explode( ',', $field['ksef_xml_name'] );
				$value = $xml;
				while ( ! empty( $names ) ) {
					$name  = array_shift( $names );
					$value = $value->$name;
				}
				$value = (string) $value;
				$meta  = sprintf( 'iworks_kpir_%s_%s', $group, $slug );
				switch ( $meta ) {
					case 'iworks_kpir_basic_date_of_issue':
					case 'iworks_kpir_basic_date':
						$value = strtotime( $value );
						break;
					case 'iworks_kpir_income_sale':
					case 'iworks_kpir_income_vat':
						$parts = explode( '.', number_format( $value, 2, '.', '' ) );
						$value = array(
							'integer'    => (int) $parts[0],
							'fractional' => (int) $parts[1],
						);
						break;
				}
				if ( ! empty( $value ) ) {
					$post_data['meta_input'][ $meta ] = $value;
				}
			}
		}
		$post_id = wp_insert_post( $post_data );
		if ( is_wp_error( $post_id ) ) {
			$this->add_notice( $post_id, 'error' );
			return;
		}
		$this->add_notice(
			sprintf(
				esc_html__( 'Imported KSeF invoice: %s.', 'kpir' ),
				sprintf(
					'<a href="%s">%s - KSeF: %s</a>',
					get_edit_post_link( $post_id ),
					get_the_title( $post_id ),
					$ksef_id
				)
			),
			'success'
		);
	}

	/**
	 * Add a notice to be displayed in the admin.
	 *
	 * Handles string, array, or WP_Error objects and stores them for later display.
	 *
	 * @since 1.2.1
	 *
	 * @param string|array|WP_Error $notice The notice message(s) to add.
	 * @param string                $type   The notice type (error, warning, success, info).
	 * @return void
	 */
	private function add_notice( $notice, $type = 'error' ) {
		if ( is_wp_error( $notice ) ) {
			$this->notices[] = array(
				'type'    => 'error',
				'message' => $notice->get_error_message(),
			);
		} elseif ( is_array( $notice ) ) {
			foreach ( $notice as $message ) {
				$this->notices[] = array(
					'type'    => $type,
					'message' => $message,
				);
			}
		} else {
			$this->notices[] = array(
				'type'    => $type,
				'message' => $notice,
			);
		}
	}

	/**
	 * Display admin notices.
	 *
	 * Outputs all stored notices in the WordPress admin area.
	 *
	 * @since 1.2.1
	 *
	 * @return void
	 */
	public function admin_notices() {
		if ( empty( $this->notices ) ) {
			return;
		}
		foreach ( $this->notices as $notice ) {
			$class = 'notice notice-' . $notice['type'];
			printf(
				'<div class="%s"><p>%s</p></div>',
				esc_attr( $class ),
				wp_kses_post( $notice['message'] )
			);
		}
		$this->notices = array();
	}

	/**
	 * Render the KSeF import form.
	 *
	 * Displays the file upload form for importing KSeF XML invoices.
	 * Includes nonce field for security and accepts .xml files only.
	 *
	 * @since 1.0.0
	 */
	public function render_form() {
		echo '<div class="wrap">';
		echo '<h1>' . __( 'KSeF Import', 'kpir' ) . '</h1>';
		echo '<form method="post" enctype="multipart/form-data">';
		wp_nonce_field( 'ksef_import_action', 'ksef_import_nonce' );
		echo '<table class="form-table">';
		echo '<tr>';
		echo '<th scope="row"><label for="' . esc_attr( $this->filename ) . '">' . __( 'XML File', 'kpir' ) . '</label></th>';
		echo '<td><input type="file" name="' . esc_attr( $this->filename ) . '" accept=".xml" required /></td>';
		echo '</tr>';
		echo '</table>';
		echo '<p class="submit">';
		echo '<input type="submit" name="ksef_import_submit" class="button button-primary" value="' . __( 'Import', 'kpir' ) . '" />';
		echo '</p>';
		echo '</form>';
		echo '</div>';
	}

	/**
	 * Check if an invoice is already imported.
	 *
	 * Queries for existing invoices with the same KSeF ID to prevent duplicates.
	 * Checks against publish, draft, and imported post statuses.
	 *
	 * @since 1.0.0
	 *
	 * @param string $ksef_id The KSeF invoice identifier.
	 * @return false|WP_Error False if not imported, WP_Error if already imported.
	 */
	private function is_invoice_already_imported( $ksef_id ) {
		$invoice_class = $this->objects['post_type_invoice'];
		$args          = array(
			'post_type'      => $invoice_class->get_name(),
			'post_status'    => array( 'publish', 'draft', 'imported' ),
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => 'iworks_kpir_basic_ksef_number',
					'value'   => $ksef_id,
					'compare' => '=',
				),
			),
		);
		$query         = new WP_Query( $args );
		if ( $query->have_posts() ) {
			$this->add_notice(
				new WP_Error(
					'invoice_already_imported',
					sprintf(
						esc_html__( 'Error: Invoice no. %1$s (KSeF: %2$s) is already imported.', 'kpir' ),
						sprintf(
							'<a href="%s">%s</a>',
							get_edit_post_link( $query->posts[0] ),
							get_the_title( $query->posts[0] )
						),
						$ksef_id
					)
				),
				'error'
			);
			return true;
		}
		return false;
	}
}
