<?php
defined( 'ABSPATH' ) || exit;
?>
<Podmiot1 rola="Podatnik">
	<OsobaFizyczna>
		<etd:NIP><?php echo esc_xml( $kpir_args['taxpayer']['nip'] ); ?></etd:NIP>
		<etd:ImiePierwsze><?php echo esc_xml( $kpir_args['taxpayer']['surname'] ); ?></etd:ImiePierwsze>
		<etd:Nazwisko><?php echo esc_xml( $kpir_args['taxpayer']['last_name'] ); ?></etd:Nazwisko>
		<etd:DataUrodzenia><?php echo esc_xml( $kpir_args['taxpayer']['date_of_birth'] ); ?></etd:DataUrodzenia>
		<Email><?php echo esc_xml( $kpir_args['taxpayer']['email'] ); ?></Email>
<?php if ( isset( $kpir_args['taxpayer']['phone'] ) && ! empty( $kpir_args['taxpayer']['phone'] ) ) { ?>
		<Telefon><?php echo esc_xml( $kpir_args['taxpayer']['phone'] ); ?></Telefon>
<?php } ?>
	</OsobaFizyczna>
</Podmiot1>

