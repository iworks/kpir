<?php
defined( 'ABSPATH' ) || exit;
?>
<tns:Podmiot1 rola="Podatnik">
	<tns:OsobaFizyczna>
		<etd:NIP><?php echo esc_xml( $args['taxpayer']['nip'] ); ?></etd:NIP>
		<etd:ImiePierwsze><?php echo esc_xml( $args['taxpayer']['surname'] ); ?></etd:ImiePierwsze>
		<etd:Nazwisko><?php echo esc_xml( $args['taxpayer']['last_name'] ); ?></etd:Nazwisko>
		<etd:DataUrodzenia><?php echo esc_xml( $args['taxpayer']['date_of_birth'] ); ?></etd:DataUrodzenia>
		<tns:Email><?php echo esc_xml( $args['taxpayer']['email'] ); ?></tns:Email>
<?php if ( isset( $args['taxpayer']['phone'] ) && ! empty( $args['taxpayer']['phone'] ) ) { ?>
		<tns:Telefon><?php echo esc_xml( $args['taxpayer']['phone'] ); ?></tns:Telefon>
<?php } ?>
	</tns:OsobaFizyczna>
</tns:Podmiot1>

