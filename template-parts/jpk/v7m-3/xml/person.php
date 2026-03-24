<Podmiot1 rola="Podatnik">
	<OsobaFizyczna>
		<etd:NIP><?php echo $args['taxpayer']['nip']; ?></etd:NIP>
		<etd:ImiePierwsze><?php echo $args['taxpayer']['surname']; ?></etd:ImiePierwsze>
		<etd:Nazwisko><?php echo $args['taxpayer']['last_name']; ?></etd:Nazwisko>
		<etd:DataUrodzenia><?php echo $args['taxpayer']['date_of_birth']; ?></etd:DataUrodzenia>
		<Email><?php echo $args['taxpayer']['email']; ?></Email>
<?php if ( isset( $args['taxpayer']['phone'] ) && ! empty( $args['taxpayer']['phone'] ) ) { ?>
		<Telefon><?php echo $args['taxpayer']['phone']; ?></Telefon>
<?php } ?>
	</OsobaFizyczna>
</Podmiot1>

