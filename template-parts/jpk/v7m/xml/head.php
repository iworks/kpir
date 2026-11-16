<?php
defined( 'ABSPATH' ) || exit;
?>
<tns:Naglowek>
	<tns:KodFormularza kodSystemowy="JPK_V7M (1)" wersjaSchemy="1-2E">JPK_VAT</tns:KodFormularza>
	<tns:WariantFormularza>1</tns:WariantFormularza>
	<tns:DataWytworzeniaJPK><?php echo esc_xml( $kpir_args['created'] ); ?></tns:DataWytworzeniaJPK>
	<tns:NazwaSystemu>KPiR PLUGIN_VERSION</tns:NazwaSystemu>
	<tns:CelZlozenia poz="P_7"><?php echo esc_xml( $kpir_args['purpose'] ); ?></tns:CelZlozenia>
	<tns:KodUrzedu><?php echo esc_xml( $kpir_args['taxpayer']['department_of_revenue'] ); ?></tns:KodUrzedu>
	<tns:Rok><?php echo esc_xml( $kpir_args['year'] ); ?></tns:Rok>
	<tns:Miesiac><?php echo esc_xml( $kpir_args['month'] ); ?></tns:Miesiac>
</tns:Naglowek>
