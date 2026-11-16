<?php
defined( 'ABSPATH' ) || exit;
?>
<ZakupCtrl>
	<LiczbaWierszyZakupow><?php echo esc_xml( $kpir_args['rows'] ); ?></LiczbaWierszyZakupow>
	<PodatekNaliczony><?php echo esc_xml( $kpir_args['sum'] ); ?></PodatekNaliczony>
</ZakupCtrl>

