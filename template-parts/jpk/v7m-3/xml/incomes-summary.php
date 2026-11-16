<?php
defined( 'ABSPATH' ) || exit;
?>
<SprzedazCtrl>
	<LiczbaWierszySprzedazy><?php echo esc_xml( $args['rows'] ); ?></LiczbaWierszySprzedazy>
	<PodatekNalezny><?php echo esc_xml( $args['sum'] ); ?></PodatekNalezny>
</SprzedazCtrl>

