<?php
defined( 'ABSPATH' ) || exit;
?>
<tns:SprzedazCtrl>
	<tns:LiczbaWierszySprzedazy><?php echo esc_xml( $args['rows'] ); ?></tns:LiczbaWierszySprzedazy>
	<tns:PodatekNalezny><?php echo esc_xml( $args['sum'] ); ?></tns:PodatekNalezny>
</tns:SprzedazCtrl>
