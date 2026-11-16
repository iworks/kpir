<?php
defined( 'ABSPATH' ) || exit;
?>
<Deklaracja>
	<Naglowek>
		<KodFormularzaDekl kodSystemowy="VAT-7 (23)" kodPodatku="VAT" rodzajZobowiazania="Z" wersjaSchemy="1-0E">VAT-7</KodFormularzaDekl>
		<WariantFormularzaDekl>23</WariantFormularzaDekl>
	</Naglowek>
	<PozycjeSzczegolowe>
<?php
for ( $kpir_i = 10; $i < 70; $kpir_i++ ) {
	$kpir_key = sprintf( 'P_%d', $kpir_i );
	if ( isset( $kpir_args[ $kpir_key ] ) ) {
?>
		<<?php echo esc_xml( $kpir_key ); ?>><?php echo esc_xml( $kpir_args[ $kpir_key ] ); ?></<?php echo esc_xml( $kpir_key ); ?>>
<?php
	}
}
?>
	</PozycjeSzczegolowe>
	<Pouczenia>1</Pouczenia>
</Deklaracja>

