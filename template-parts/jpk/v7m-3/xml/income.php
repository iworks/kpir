<SprzedazWiersz>
	<LpSprzedazy><?php echo $args['counter']; ?></LpSprzedazy>
	<KodKrajuNadaniaTIN>PL</KodKrajuNadaniaTIN>
	<NrKontrahenta><?php echo $args['contractor']['nip']; ?></NrKontrahenta>
	<NazwaKontrahenta><?php echo $args['contractor']['name']; ?></NazwaKontrahenta>
	<DowodSprzedazy><?php echo $args['invoice_number']; ?></DowodSprzedazy>
	<DataWystawienia><?php echo $args['create_date']; ?></DataWystawienia>
	<DataSprzedazy><?php echo $args['sale_date']; ?></DataSprzedazy>
<?php
/**
 * KSeF
 */
echo "\t";
switch( $args['ksef_number'] ) {
	case 'OFF':
	case 'BFK':
	case 'DI':
		printf( '<%1$s>1</%1$s>', $args['ksef_number'] );
		break;
	default:
		printf( '<NrKSeF>%1$s</NrKSeF>', $args['ksef_number'] );
	break;
}
echo PHP_EOL;
/**
 * numbers
 */
for ( $i = 10; $i < 37; $i++ ) {
	$key = sprintf( 'K_%d', $i );
	if ( isset( $args[ $key ] ) ) {
		echo "\t";
		printf( '<%s>%s</%s>', $key, $args[ $key ], $key );
		echo PHP_EOL;
	}
}
?>
</SprzedazWiersz>

