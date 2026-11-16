<?php
defined( 'ABSPATH' ) || exit;
?>
<tns:Podmiot1 rola="Podatnik">
	<tns:OsobaNiefizyczna>
		<tns:NIP><?php echo esc_xml( $args['taxpayer']['nip'] ); ?></tns:NIP>
		<tns:PelnaNazwa><?php echo esc_xml( $args['taxpayer']['name'] ); ?></tns:PelnaNazwa>
		<tns:Email><?php echo esc_xml( $args['taxpayer']['email'] ); ?></tns:Email>
<?php if ( isset( $args['taxpayer']['phone'] ) && ! empty( $args['taxpayer']['phone'] ) ) { ?>
		<tns:Telefon><?php echo esc_xml( $args['taxpayer']['phone'] ); ?></tns:Telefon>
<?php } ?>
	</tns:OsobaNiefizyczna>
</tns:Podmiot1>

