<?php
defined( 'ABSPATH' ) || exit;
?>
<tns:ZakupWiersz>
	<tns:LpZakupu><?php echo esc_xml( $kpir_args['counter'] ); ?></tns:LpZakupu>
	<tns:NrDostawcy><?php echo esc_xml( $kpir_args['contractor']['nip'] ); ?></tns:NrDostawcy>
	<tns:NazwaDostawcy><?php echo esc_xml( $kpir_args['contractor']['name'] ); ?></tns:NazwaDostawcy>
	<tns:DowodZakupu><?php echo esc_xml( $kpir_args['invoice_number'] ); ?></tns:DowodZakupu>
	<tns:DataZakupu><?php echo esc_xml( $kpir_args['create_date'] ); ?></tns:DataZakupu>
	<tns:DataWplywu><?php echo esc_xml( $kpir_args['sale_date'] ); ?></tns:DataWplywu>
<?php
for ( $kpir_i = 40; $kpir_i < 48; $kpir_i++ ) {
	$kpir_key = sprintf( 'K_%d', $kpir_i );
	if ( isset( $kpir_args[ $kpir_key ] ) ) {
		?>
	<tns:<?php echo esc_xml( $kpir_key ); ?>><?php echo esc_xml( $kpir_args[ $kpir_key ] ); ?></tns:<?php echo esc_xml( $kpir_key ); ?>>
		<?php
	}
}
?>
</tns:ZakupWiersz>
