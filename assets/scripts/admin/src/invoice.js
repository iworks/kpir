var iWorksKPiR = {};

(function( $ ) {

    iWorksKPiR.ShowMetaBoxes = function() {
        var value = $( "#basic .iworks-kpir-row-type input[type=radio]:checked" ).val();
        $('.iworks-type').addClass( "closed" );
        $("body").removeClass( "kpir-not-set" );
        $(".iworks-type").each( function() {
            $("body").removeClass( $(this).attr("id") );
        });
        if ( "undefined" == typeof( value ) ) {
            $("body").addClass( "kpir-not-set" );
        } else {
            $("#"+value).removeClass( "closed" );
            $("body").addClass( value );
        }
    }

    iWorksKPiR.BindDuplicate = function() {
        $( '.duplicate-invoice-link' ).on( 'click', function( e ) {
            var data;
            e.stopImmediatePropagation();
            e.preventDefault();
            var link = $(this);
            var parentTR = link.parents( 'tr' ).first();
            if ( window.confirm( iworks_kpir.messages.duplicate_confirm ) ) {
                data = {
                    action: 'kpir_duplicate_invoice',
                    nonce: $( this ).attr('data-nonce'),
                    ID: $( this ).attr('data-id')
                };
                parentTR.addClass( 'duplicating' );
                jQuery.post(ajaxurl, data, function(response) {
                    if ( response.success ) {
                        window.location.reload();
                    } else {
                        html = '<div class="notice notice-error"><p>' + iworks_kpir.messages.duplicate_error + '</p></div>';
                        $('.wp-header-end').after( html );
                    }
                });
            }
        });
    }

    $(document)
        .ready( iWorksKPiR.ShowMetaBoxes )
        .ready( iWorksKPiR.BindDuplicate )
        .on( "change", "#basic .iworks-kpir-row-type input[type=radio]", iWorksKPiR.ShowMetaBoxes );

})(jQuery);
