<?php
defined( 'ABSPATH' ) || exit;
?>
<tns:SprzedazCtrl>
	<tns:LiczbaWierszySprzedazy><?php echo esc_xml( $kpir_args['rows'] ); ?></tns:LiczbaWierszySprzedazy>
	<tns:PodatekNalezny><?php echo esc_xml( $kpir_args['sum'] ); ?></tns:PodatekNalezny>
</tns:SprzedazCtrl>
