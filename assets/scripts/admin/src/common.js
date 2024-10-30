jQuery( document ).ready(function($) {
    $( function() {
        $('input[name="iworks_build_a_house_breakdowns"]').on( 'click', function(e) {
            var $button = $(this);
            e.preventDefault();
            if ( window.confirm( build_a_house.messages.import ) ) {
                var data = {
                    url: ajaxurl,
                    cache: false,
                    method: 'POST',
                    data: {
                        _wpnonce: $button.data('nonce'),
                        action: 'iworks_build_a_house_breakdowns_import',
                    },
                };
                $button.attr( 'disabled', 'disabled' ).addClass( 'disabled' );
                $.ajax(data)
                    .success( function( response ) {
                        $button.removeAttr( 'disabled' ).removeClass( 'disabled' );
                    });
            }
            return false;
        });
    } );
});
