/*! Księga Przychodi i Rozchodu - v1.0.1
 * http://iworks.pl/
 * Copyright (c) 2024
 * Licensed GPL-3.0
 */
jQuery( document ).ready(function($) {
    $( function() {
        $( ".iworks-kpir-row .datepicker" ).datepicker({
            showOtherMonths: true,
            selectOtherMonths: true,
            dateFormat: 'dd-mm-yy'
        });
    } );
});

var iWorksKPiR = {};

(function( $ ) {

    iWorksKPiR.ShowMetaBoxes = function() {
        var value = $( "#basic .iworks-kpir-row-type input[type=radio]:checked" ).val();
        $('.iworks-type').addClass( "closed" ).hide();
        $("body").removeClass( "kpir-not-set" );
        $(".iworks-type").each( function() {
            $("body").removeClass( $(this).attr("id") );
        });
        if ( "undefined" == typeof( value ) ) {
            $("body").addClass( "kpir-not-set" );
        } else {
            $("#"+value).removeClass( "closed" ).show();
            $("body").addClass( value );
            $('#asset-'+value).prop('checked', true );
            if ( 'salary' === value ) {
                $('#asset-expense').prop('checked', true );
                $('#expense').removeClass( "closed" ).show();
            }
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

    iWorksKPiR.CopyDateOfIssueToEventDate = function() {
        jQuery( '#iworks_kpir_basic_date' ).val( jQuery( '#iworks_kpir_basic_date_of_issue' ).val() );
    }

    $(document)
        .ready( iWorksKPiR.ShowMetaBoxes )
        .ready( iWorksKPiR.BindDuplicate )
        .on( "change", "#basic .iworks-kpir-row-type input[type=radio]", iWorksKPiR.ShowMetaBoxes )
        .on( "click", "#basic #kpir-copy-date-button", iWorksKPiR.CopyDateOfIssueToEventDate );

})(jQuery);

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

jQuery( document ).ready(function($) {
    $('select[name=contractor]').select2();
    $(".iworks-kpir-row .select2").select2({
        ajax: {
            url: ajaxurl,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term, // search term
                    page: params.page,
                    action: "iworks_get_contractors",
                    _wpnonce: $(this).data("nonce")
                };
            },
            processResults: function (data, params) {
                params.page = params.page || 1;
                return {
                    results: data.items,
                    pagination: {
                        more: (params.page * 30) < data.total_count
                    }
                };
            },
            cache: true
        },
        escapeMarkup: function (markup) { return markup; }, // let our custom formatter work
        minimumInputLength: 1,
        templateResult: iworksFormatContractor, // omitted for brevity, see the source of this page
        templateSelection: iworksFormatContractorSelection // omitted for brevity, see the source of this page
    });

    function iworksFormatContractor (contractor) {
        if (contractor.loading) return contractor.text;
        var markup = "<div class='select2-result-contractor clearfix'>" +
            "<div class='select2-result-contractor__meta'>" +
            "<div class='select2-result-contractor__title'>" + contractor.full_name + "</div>";
        if (contractor.description) {
            markup += "<div class='select2-result-contractor__description'>" + contractor.description + "</div>";
        }
        if (contractor.nip) {
            markup += "<div class='select2-result-contractor__nip'>" + contractor.nip + "</div>";
        }
        markup += "</div></div>";
        return markup;
    }

    function iworksFormatContractorSelection (contractor) {
        return contractor.full_name || contractor.text;
    }

});
