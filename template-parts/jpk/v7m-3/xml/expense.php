<ZakupWiersz>
	<LpZakupu><?php echo $args['counter']; ?></LpZakupu>
	<KodKrajuNadaniaTIN>PL</KodKrajuNadaniaTIN>
	<NrDostawcy><?php echo $args['contractor']['nip']; ?></NrDostawcy>
	<NazwaDostawcy><?php echo $args['contractor']['name']; ?></NazwaDostawcy>
	<DowodZakupu><?php echo $args['invoice_number']; ?></DowodZakupu>
	<DataZakupu><?php echo $args['create_date']; ?></DataZakupu>
	<DataWplywu><?php echo $args['sale_date']; ?></DataWplywu>
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
for ( $i = 40; $i < 48; $i++ ) {
	$key = sprintf( 'K_%d', $i );
	if ( isset( $args[ $key ] ) ) {
		print "\t";
		printf( '<%1$s>%2$s</%1$s>', $key, $args[ $key ]);
		print PHP_EOL;
	}
}
?></ZakupWiersz>

