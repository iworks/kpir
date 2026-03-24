<Deklaracja>
	<Naglowek>
		<KodFormularzaDekl kodSystemowy="VAT-7 (23)" kodPodatku="VAT" rodzajZobowiazania="Z" wersjaSchemy="1-0E">VAT-7</KodFormularzaDekl>
		<WariantFormularzaDekl>23</WariantFormularzaDekl>
	</Naglowek>
	<PozycjeSzczegolowe>
<?php
for ( $i = 10; $i < 70; $i++ ) {
	$key = sprintf( 'P_%d', $i );
	if ( isset( $args[ $key ] ) ) {
?>
		<<?php echo $key; ?>><?php echo $args[ $key ]; ?></<?php echo $key; ?>>
<?php
	}
}
?>
	</PozycjeSzczegolowe>
	<Pouczenia>1</Pouczenia>
</Deklaracja>

