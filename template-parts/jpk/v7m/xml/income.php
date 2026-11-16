<?php
defined( 'ABSPATH' ) || exit;
?>
<tns:SprzedazWiersz>
	<tns:LpSprzedazy><?php echo esc_xml( $kpir_args['counter'] ); ?></tns:LpSprzedazy>
	<tns:NrKontrahenta><?php echo esc_xml( $kpir_args['contractor']['nip'] ); ?></tns:NrKontrahenta>
	<tns:NazwaKontrahenta><?php echo esc_xml( $kpir_args['contractor']['name'] ); ?></tns:NazwaKontrahenta>
	<tns:DowodSprzedazy><?php echo esc_xml( $kpir_args['invoice_number'] ); ?></tns:DowodSprzedazy>
	<tns:DataWystawienia><?php echo esc_xml( $kpir_args['create_date'] ); ?></tns:DataWystawienia>
	<tns:DataSprzedazy><?php echo esc_xml( $kpir_args['sale_date'] ); ?></tns:DataSprzedazy>
<?php
for ( $kpir_i = 10; $i < 37; $kpir_i++ ) {
	$kpir_key = sprintf( 'K_%d', $kpir_i );
	if ( isset( $kpir_args[ $kpir_key ] ) ) {
		?>
	<tns:<?php echo esc_xml( $kpir_key ); ?>><?php echo esc_xml( $kpir_args[ $kpir_key ] ); ?></tns:<?php echo esc_xml( $kpir_key ); ?>><?php
	}
}
?>
</tns:SprzedazWiersz>
