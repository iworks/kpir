<form id="<?php echo esc_attr( $args['name'] ); ?>" action="edit.php">
<?php
$args['this']->options_get_field_by_type( 'hidden', 'action', $args['page'] );
$args['this']->options_get_field_by_type( 'hidden', 'post_type', 'iworks_kpir_invoice' );
$args['this']->options_get_field_by_type( 'hidden', 'page', $args['page'] );
wp_nonce_field( $args['name'], 'nonce', false );
?>
	<table class="form-table">
		<tbody>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( 'excess' ); ?>"><?php esc_html_e( 'Excess tax', 'kpir' ); ?></label></th>
			<td>
			<?php
			$args['this']->options_get_field_by_type(
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
$atts = array(
	'options' => array(
		'1' => __( 'submission of a declaration', 'kpir' ),
		'2' => __( 'correction of the declaration', 'kpir' ),
	),
);
$args['this']->options_get_field_by_type( 'select', 'purpose', 1, $atts );
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
$atts = array(
	'options' => array(
		'-' => __( 'Select month', 'kpir' ),
	),
);
foreach ( $args['months'] as $value ) {
	$atts['options'][ $value ] = $value;
}
$args['this']->options_get_field_by_type( 'select', 'm', null, $atts );
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
$atts = array(
	'class' => array( 'button', 'button-primary' ),
);
$args['this']->options_get_field_by_type( 'submit', $args['page'], __( 'Get XML', 'kpir' ), $atts );
?>
</form>
