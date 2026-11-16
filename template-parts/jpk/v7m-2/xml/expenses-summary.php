<?php
defined( 'ABSPATH' ) || exit;
?>
<tns:ZakupCtrl>
	<tns:LiczbaWierszyZakupow><?php echo esc_xml( $kpir_args['rows'] ); ?></tns:LiczbaWierszyZakupow>
	<tns:PodatekNaliczony><?php echo esc_xml( $kpir_args['sum'] ); ?></tns:PodatekNaliczony>
</tns:ZakupCtrl>
