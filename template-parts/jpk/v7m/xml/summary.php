<?php
defined( 'ABSPATH' ) || exit;
?>
<tns:Deklaracja>
	<tns:Naglowek>
		<tns:KodFormularzaDekl kodSystemowy="VAT-7 (21)" kodPodatku="VAT" rodzajZobowiazania="Z" wersjaSchemy="1-2E">VAT-7</tns:KodFormularzaDekl>
		<tns:WariantFormularzaDekl>21</tns:WariantFormularzaDekl>
	</tns:Naglowek>
	<tns:PozycjeSzczegolowe>
<?php
for ( $kpir_i = 10; $i < 70; $kpir_i++ ) {
	$kpir_key = sprintf( 'P_%d', $kpir_i );
	if ( isset( $args[ $kpir_key ] ) ) {
?>
		<tns:<?php echo esc_xml( $kpir_key ); ?>><?php echo esc_xml( $args[ $kpir_key ] ); ?></tns:<?php echo esc_xml( $kpir_key ); ?>>
<?php
	}
}
?>
	</tns:PozycjeSzczegolowe>
	<tns:Pouczenia>1</tns:Pouczenia>
</tns:Deklaracja>

