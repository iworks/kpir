<tns:SprzedazWiersz>
	<tns:LpSprzedazy><?php echo $args['counter']; ?></tns:LpSprzedazy>
	<tns:NrKontrahenta><?php echo $args['contractor']['nip']; ?></tns:NrKontrahenta>
	<tns:NazwaKontrahenta><?php echo $args['contractor']['name']; ?></tns:NazwaKontrahenta>
	<tns:DowodSprzedazy><?php echo $args['invoice_number']; ?></tns:DowodSprzedazy>
	<tns:DataWystawienia><?php echo $args['create_date']; ?></tns:DataWystawienia>
	<tns:DataSprzedazy><?php echo $args['sale_date']; ?></tns:DataSprzedazy>
<?php
for ( $i = 10; $i < 37; $i++ ) {
	$key = sprintf( 'K_%d', $i );
	if ( isset( $args[ $key ] ) ) {
		?>
	<tns:<?php echo $key; ?>><?php echo $args[ $key ]; ?></tns:<?php echo $key; ?>>
		<?php
	}
}
?>
</tns:SprzedazWiersz>
