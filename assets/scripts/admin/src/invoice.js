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

    $(document)
        .ready( iWorksKPiR.ShowMetaBoxes )
        .on( "change", "#basic .iworks-kpir-row-type input[type=radio]", iWorksKPiR.ShowMetaBoxes );

})(jQuery);
