<tns:ZakupWiersz>
	<tns:LpZakupu><?php echo $args['counter']; ?></tns:LpZakupu>
	<tns:NrDostawcy><?php echo $args['contractor']['nip']; ?></tns:NrDostawcy>
	<tns:NazwaDostawcy><?php echo $args['contractor']['name']; ?></tns:NazwaDostawcy>
	<tns:DowodZakupu><?php echo $args['invoice_number']; ?></tns:DowodZakupu>
	<tns:DataZakupu><?php echo $args['create_date']; ?></tns:DataZakupu>
	<tns:DataWplywu><?php echo $args['sale_date']; ?></tns:DataWplywu>
<?php
for ( $i = 40; $i < 48; $i++ ) {
	$key = sprintf( 'K_%d', $i );
	if ( isset( $args[ $key ] ) ) {
		?>
	<tns:<?php echo $key; ?>><?php echo $args[ $key ]; ?></tns:<?php echo $key; ?>>
		<?php
	}
}
?>
</tns:ZakupWiersz>
