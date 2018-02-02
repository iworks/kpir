(function( $ ) {
    iWorksKPiR.JPK_VAT = function() {
        var form = $('form#kpir-jpk-vat-3');
        var month = $( 'select[name="m"]', form ).val();
        if ( '-' == month ) {
            alert( iworks_kpir.messages.jpk.vat.select_month );
            return false;
        }
    }

    $(document)
        .on( 'submit', 'form#kpir-jpk-vat-3', iWorksKPiR.JPK_VAT )
        ;

})(jQuery);
