<?php
defined( 'ABSPATH' ) || exit;
?>
<ZakupWiersz>
	<LpZakupu><?php echo esc_xml( $args['counter'] ); ?></LpZakupu>
	<KodKrajuNadaniaTIN><?php echo esc_xml( $args['contractor']['country'] ); ?></KodKrajuNadaniaTIN>
	<NrDostawcy><?php echo esc_xml( $args['contractor']['nip'] ); ?></NrDostawcy>
	<NazwaDostawcy><?php echo esc_xml( $args['contractor']['name'] ); ?></NazwaDostawcy>
	<DowodZakupu><?php echo esc_xml( $args['invoice_number'] ); ?></DowodZakupu>
	<DataZakupu><?php echo esc_xml( $args['create_date'] ); ?></DataZakupu>
	<DataWplywu><?php echo esc_xml( $args['sale_date'] ); ?></DataWplywu>
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
for ( $kpir_i = 40; $kpir_i < 48; $kpir_i++ ) {
	$kpir_key = sprintf( 'K_%d', $kpir_i );
	if ( isset( $args[ $kpir_key ] ) ) {
		print "\t";
		printf( '<%1$s>%2$s</%1$s>', esc_xml( $kpir_key ), esc_xml( $args[ $kpir_key ] ) );
		print PHP_EOL;
	}
}
?></ZakupWiersz>

