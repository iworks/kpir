<?php
defined( 'ABSPATH' ) || exit;
?>
<Podmiot1 rola="Podatnik">
	<OsobaNiefizyczna>
		<NIP><?php echo esc_xml( $kpir_args['taxpayer']['nip'] ); ?></NIP>
		<PelnaNazwa><?php echo esc_xml( $kpir_args['taxpayer']['name'] ); ?></PelnaNazwa>
		<Email><?php echo esc_xml( $kpir_args['taxpayer']['email'] ); ?></Email>
<?php if ( isset( $kpir_args['taxpayer']['phone'] ) && ! empty( $kpir_args['taxpayer']['phone'] ) ) { ?>
		<Telefon><?php echo esc_xml( $kpir_args['taxpayer']['phone'] ); ?></Telefon>
<?php } ?>
	</OsobaNiefizyczna>
</Podmiot1>

