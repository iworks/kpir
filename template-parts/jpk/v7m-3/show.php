<?php
defined( 'ABSPATH' ) || exit;
?>
<form id="<?php echo esc_attr( $kpir_args['name'] ); ?>" action="edit.php">
<?php
$kpir_args['this']->options_get_field_by_type( 'hidden', 'action', $kpir_args['page'] );
$kpir_args['this']->options_get_field_by_type( 'hidden', 'post_type', 'iworks_kpir_invoice' );
$kpir_args['this']->options_get_field_by_type( 'hidden', 'page', $kpir_args['page'] );
wp_nonce_field( $kpir_args['name'], 'nonce', false );
?>
	<table class="form-table">
		<tbody>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( 'excess' ); ?>"><?php esc_html_e( 'Excess tax', 'kpir' ); ?></label></th>
			<td>
			<?php
			$kpir_args['this']->options_get_field_by_type(
				'number',
				'excess',
				0,
				array(
					'min-val' => 0,
					'class'   => array( 'small-text' ),
				)
			);
			?>
			</td>
		</tr>
<?php
/**
 * the purpose of the declaration
 */
?>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( 'purpose' ); ?>"><?php esc_html_e( 'Purpose', 'kpir' ); ?></label></th>
			<td>
<?php
$kpir_atts = array(
	'options' => array(
		'1' => __( 'submission of a declaration', 'kpir' ),
		'2' => __( 'correction of the declaration', 'kpir' ),
	),
);
$kpir_args['this']->options_get_field_by_type( 'select', 'purpose', 1, $kpir_atts );
?>
				</td>
			</tr>
<?php
/**
 * month
 */
?>
			<tr>
			<th scope="row"><label for="<?php echo esc_attr( 'm' ); ?>"><?php esc_html_e( 'Month', 'kpir' ); ?></label></th>
			<td>
<?php
$kpir_atts = array(
	'options' => array(
		'-' => __( 'Select month', 'kpir' ),
	),
);
foreach ( $kpir_args['months'] as $kpir_value ) {
	$kpir_atts['options'][ $kpir_value ] = $kpir_value;
}
$kpir_args['this']->options_get_field_by_type( 'select', 'm', null, $kpir_atts );
?>
				</td>
			</tr>
<?php
/**
 * close table
 */
?>
		</tbody>
	</table>
<?php
$kpir_atts = array(
	'class' => array( 'button', 'button-primary' ),
);
$kpir_args['this']->options_get_field_by_type( 'submit', $kpir_args['page'], __( 'Get XML', 'kpir' ), $kpir_atts );
?>
</form>
