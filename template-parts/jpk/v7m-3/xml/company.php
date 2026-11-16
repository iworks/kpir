<?php
defined( 'ABSPATH' ) || exit;
?>
<Podmiot1 rola="Podatnik">
	<OsobaNiefizyczna>
		<NIP><?php echo esc_xml( $args['taxpayer']['nip'] ); ?></NIP>
		<PelnaNazwa><?php echo esc_xml( $args['taxpayer']['name'] ); ?></PelnaNazwa>
		<Email><?php echo esc_xml( $args['taxpayer']['email'] ); ?></Email>
<?php if ( isset( $args['taxpayer']['phone'] ) && ! empty( $args['taxpayer']['phone'] ) ) { ?>
		<Telefon><?php echo esc_xml( $args['taxpayer']['phone'] ); ?></Telefon>
<?php } ?>
	</OsobaNiefizyczna>
</Podmiot1>

