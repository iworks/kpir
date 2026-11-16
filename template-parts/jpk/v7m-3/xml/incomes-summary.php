<?php
defined( 'ABSPATH' ) || exit;
?>
<SprzedazCtrl>
	<LiczbaWierszySprzedazy><?php echo esc_xml( $kpir_args['rows'] ); ?></LiczbaWierszySprzedazy>
	<PodatekNalezny><?php echo esc_xml( $kpir_args['sum'] ); ?></PodatekNalezny>
</SprzedazCtrl>

