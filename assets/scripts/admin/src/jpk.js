(function( $ ) {
    iWorksKPiR.JPK_VAT = function() {
        var form = $('form#kpir-jpk-vat-3');
        var data = {
            action: 'kpir_jpk_vat_3',
            nonce: $( '#kpir-jpk-vat-3-nonce', form ).val(),
            purpose: $( 'input[name="purpose"]', form ).val(),
            m: $( 'select[name="m"]', form ).val()
        };
        jQuery.ajax({
            method: 'get',
            url: ajaxurl,
            async: false,
            dataType: 'xml',
            mimeType: 'text/xml',
            data: data
        });
    }

    $(document)
        .on( "click", "input[name=iworks_kpir_jpk_vat_3]", iWorksKPiR.JPK_VAT );

})(jQuery);
