<tns:Podmiot1 rola="Podatnik">
	<tns:OsobaFizyczna>
		<tns:NIP><?php echo $args['taxpayer']['nip']; ?></tns:NIP>
		<tns:ImiePierwsze><?php echo $args['taxpayer']['surname']; ?></tns:ImiePierwsze>
		<tns:Nazwisko><?php echo $args['taxpayer']['last_name']; ?></tns:Nazwisko>
		<tns:Email><?php echo $args['taxpayer']['email']; ?></tns:Email>
<?php if ( isset( $args['taxpayer']['phone'] ) && ! empty( $args['taxpayer']['phone'] ) ) { ?>
		<tns:Telefon><?php echo $args['taxpayer']['phone']; ?></tns:Telefon>
<?php } ?>
	</tns:OsobaFizyczna>
</tns:Podmiot1>

