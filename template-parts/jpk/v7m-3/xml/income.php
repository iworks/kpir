<?php
defined( 'ABSPATH' ) || exit;
?>
<SprzedazWiersz>
	<LpSprzedazy><?php echo esc_xml( $args['counter'] ); ?></LpSprzedazy>
	<KodKrajuNadaniaTIN>PL</KodKrajuNadaniaTIN>
	<NrKontrahenta><?php echo esc_xml( $args['contractor']['nip'] ); ?></NrKontrahenta>
	<NazwaKontrahenta><?php echo esc_xml( $args['contractor']['name'] ); ?></NazwaKontrahenta>
	<DowodSprzedazy><?php echo esc_xml( $args['invoice_number'] ); ?></DowodSprzedazy>
	<DataWystawienia><?php echo esc_xml( $args['create_date'] ); ?></DataWystawienia>
	<DataSprzedazy><?php echo esc_xml( $args['sale_date'] ); ?></DataSprzedazy>
<?php
/**
 * KSeF
 */
echo "\t";
switch( $args['ksef_number'] ) {
	case 'OFF':
	case 'BFK':
	case 'DI':
		printf( '<%1$s>1</%1$s>', esc_xml( $args['ksef_number'] ) );
		break;
	default:
		printf( '<NrKSeF>%1$s</NrKSeF>', esc_xml( $args['ksef_number'] ) );
	break;
}
echo PHP_EOL;
/**
 * numbers
 */
for ( $kpir_i = 10; $kpir_i < 37; $kpir_i++ ) {
	$kpir_key = sprintf( 'K_%d', $kpir_i );
	if ( isset( $args[ $kpir_key ] ) ) {
		echo "\t";
		printf( '<%s>%s</%s>', esc_xml( $kpir_key ), esc_xml( $args[ $kpir_key ] ), esc_xml( $kpir_key ) );
		echo PHP_EOL;
	}
}
?>
</SprzedazWiersz>

