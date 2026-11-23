<?php
defined( 'ABSPATH' ) || exit;
?>
<tns:SprzedazWiersz>
	<tns:LpSprzedazy><?php echo esc_xml( $args['counter'] ); ?></tns:LpSprzedazy>
	<tns:NrKontrahenta><?php echo esc_xml( $args['contractor']['nip'] ); ?></tns:NrKontrahenta>
	<tns:NazwaKontrahenta><?php echo esc_xml( $args['contractor']['name'] ); ?></tns:NazwaKontrahenta>
	<tns:DowodSprzedazy><?php echo esc_xml( $args['invoice_number'] ); ?></tns:DowodSprzedazy>
	<tns:DataWystawienia><?php echo esc_xml( $args['create_date'] ); ?></tns:DataWystawienia>
	<tns:DataSprzedazy><?php echo esc_xml( $args['sale_date'] ); ?></tns:DataSprzedazy>
<?php
for ( $kpir_i = 10; $kpir_i < 37; $kpir_i++ ) {
	$kpir_key = sprintf( 'K_%d', $kpir_i );
	if ( isset( $args[ $kpir_key ] ) ) {
		?>
	<tns:<?php echo esc_xml( $kpir_key ); ?>><?php echo esc_xml( $args[ $kpir_key ] ); ?></tns:<?php echo esc_xml( $kpir_key ); ?>><?php
	}
}
?>
</tns:SprzedazWiersz>
