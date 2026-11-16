<?php
defined( 'ABSPATH' ) || exit;
?>
<Podmiot1 rola="Podatnik">
	<OsobaFizyczna>
		<etd:NIP><?php echo esc_xml( $args['taxpayer']['nip'] ); ?></etd:NIP>
		<etd:ImiePierwsze><?php echo esc_xml( $args['taxpayer']['surname'] ); ?></etd:ImiePierwsze>
		<etd:Nazwisko><?php echo esc_xml( $args['taxpayer']['last_name'] ); ?></etd:Nazwisko>
		<etd:DataUrodzenia><?php echo esc_xml( $args['taxpayer']['date_of_birth'] ); ?></etd:DataUrodzenia>
		<Email><?php echo esc_xml( $args['taxpayer']['email'] ); ?></Email>
<?php if ( isset( $args['taxpayer']['phone'] ) && ! empty( $args['taxpayer']['phone'] ) ) { ?>
		<Telefon><?php echo esc_xml( $args['taxpayer']['phone'] ); ?></Telefon>
<?php } ?>
	</OsobaFizyczna>
</Podmiot1>

