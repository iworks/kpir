<?php
defined( 'ABSPATH' ) || exit;
?>
<ZakupCtrl>
	<LiczbaWierszyZakupow><?php echo esc_xml( $args['rows'] ); ?></LiczbaWierszyZakupow>
	<PodatekNaliczony><?php echo esc_xml( $args['sum'] ); ?></PodatekNaliczony>
</ZakupCtrl>

