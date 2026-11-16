<?php
defined( 'ABSPATH' ) || exit;
?>
<SprzedazWiersz>
	<LpSprzedazy><?php echo esc_xml( $kpir_args['counter'] ); ?></LpSprzedazy>
	<KodKrajuNadaniaTIN>PL</KodKrajuNadaniaTIN>
	<NrKontrahenta><?php echo esc_xml( $kpir_args['contractor']['nip'] ); ?></NrKontrahenta>
	<NazwaKontrahenta><?php echo esc_xml( $kpir_args['contractor']['name'] ); ?></NazwaKontrahenta>
	<DowodSprzedazy><?php echo esc_xml( $kpir_args['invoice_number'] ); ?></DowodSprzedazy>
	<DataWystawienia><?php echo esc_xml( $kpir_args['create_date'] ); ?></DataWystawienia>
	<DataSprzedazy><?php echo esc_xml( $kpir_args['sale_date'] ); ?></DataSprzedazy>
<?php
/**
 * KSeF
 */
echo "\t";
switch( $kpir_args['ksef_number'] ) {
	case 'OFF':
	case 'BFK':
	case 'DI':
		printf( '<%1$s>1</%1$s>', esc_xml( $kpir_args['ksef_number'] ) );
		break;
	default:
		printf( '<NrKSeF>%1$s</NrKSeF>', esc_xml( $kpir_args['ksef_number'] ) );
	break;
}
echo PHP_EOL;
/**
 * numbers
 */
for ( $kpir_i = 10; $i < 37; $kpir_i++ ) {
	$kpir_key = sprintf( 'K_%d', $kpir_i );
	if ( isset( $kpir_args[ $kpir_key ] ) ) {
		echo "\t";
		printf( '<%s>%s</%s>', esc_xml( $kpir_key ), esc_xml( $kpir_args[ $kpir_key ] ), esc_xml( $kpir_key ) );
		echo PHP_EOL;
	}
}
?>
</SprzedazWiersz>

