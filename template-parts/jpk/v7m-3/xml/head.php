<?php
defined( 'ABSPATH' ) || exit;
?>
<Naglowek>
	<KodFormularza kodSystemowy="JPK_V7M (3)" wersjaSchemy="1-0E">JPK_VAT</KodFormularza>
	<WariantFormularza>3</WariantFormularza>
	<DataWytworzeniaJPK><?php echo esc_xml( $args['created'] ); ?></DataWytworzeniaJPK>
	<NazwaSystemu>KPiR PLUGIN_VERSION</NazwaSystemu>
	<CelZlozenia poz="P_7"><?php echo esc_xml( $args['purpose'] ); ?></CelZlozenia>
	<KodUrzedu><?php echo esc_xml( $args['taxpayer']['department_of_revenue'] ); ?></KodUrzedu>
	<Rok><?php echo esc_xml( $args['year'] ); ?></Rok>
	<Miesiac><?php echo esc_xml( $args['month'] ); ?></Miesiac>
</Naglowek>

