<Podmiot1 rola="Podatnik">
	<OsobaNiefizyczna>
		<NIP><?php echo $args['taxpayer']['nip']; ?></NIP>
		<PelnaNazwa><?php echo $args['taxpayer']['name']; ?></PelnaNazwa>
		<Email><?php echo $args['taxpayer']['email']; ?></Email>
<?php if ( isset( $args['taxpayer']['phone'] ) && ! empty( $args['taxpayer']['phone'] ) ) { ?>
		<Telefon><?php echo $args['taxpayer']['phone']; ?></Telefon>
<?php } ?>
	</OsobaNiefizyczna>
</Podmiot1>


