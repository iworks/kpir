<tns:Deklaracja>
	<tns:Naglowek>
		<tns:KodFormularzaDekl kodSystemowy="VAT-7 (21)" kodPodatku="VAT" rodzajZobowiazania="Z" wersjaSchemy="1-2E">VAT-7</tns:KodFormularzaDekl>
		<tns:WariantFormularzaDekl>21</tns:WariantFormularzaDekl>
	</tns:Naglowek>
	<tns:PozycjeSzczegolowe>
<?php
for ( $i = 10; $i < 70; $i++ ) {
	$key = sprintf( 'P_%d', $i );
	if ( isset( $args[ $key ] ) ) {
?>
		<tns:<?php echo $key; ?>><?php echo $args[ $key ]; ?></tns:<?php echo $key; ?>>
<?php
	}
}
?>
	</tns:PozycjeSzczegolowe>
	<tns:Pouczenia>1</tns:Pouczenia>
</tns:Deklaracja>
