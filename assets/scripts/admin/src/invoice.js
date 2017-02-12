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
