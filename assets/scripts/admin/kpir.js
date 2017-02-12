/*! Księga Przychodi i Rozchodu - v0.0.0
 * https://iworks.pl/
 * Copyright (c) 2017; * Licensed GPLv2+
 */
jQuery( document ).ready(function($) {
    $( function() {
        $( ".iworks-kpir-row .datepicker" ).datepicker({ dateFormat: 'dd-mm-yy' });
    } );
});

var iWorksKPiR = {};

(function( $ ) {
    iWorksKPiR.ShowMetaBoxes = function() {
        var value = $( "#basic .iworks-kpir-row-type input[type=radio]:checked" ).val();
        $("#expense, #income").addClass( "closed" );
        $("body").removeClass( "income expense kpir-not-set" );
        if ( "undefined" == typeof( value ) ) {
            $("body").addClass( "kpir-not-set" );
        } else if ( "income" == value ) {
            $("body").addClass( value );
            $("#income").removeClass( "closed" );
            $("#expense").addClass( "closed" );
        } else if ( "expense" == value ) {
            $("body").addClass( value );
            $("#income").addClass( "closed" );
            $("#expense").removeClass( "closed" );
        }
    }

    $(document)
        .ready( iWorksKPiR.ShowMetaBoxes )
        .on( "change", "#basic .iworks-kpir-row-type input[type=radio]", iWorksKPiR.ShowMetaBoxes );

})(jQuery);

jQuery( document ).ready(function($) {
    $( function() {
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

            markup += 
                "</div></div>";

            return markup;
        }

        function iworksFormatContractorSelection (contractor) {
            return contractor.full_name || contractor.text;
        }

    } );
});
