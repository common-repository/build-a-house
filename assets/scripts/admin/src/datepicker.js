jQuery( document ).ready(function($) {
    $( function() {
        $( ".iworks-build_a_house-row .datepicker" ).each( function() {
            var format = $(this).data('date-format') || 'yy-mm-dd';
            $(this).datepicker({ dateFormat: format });
        });
    } );
});
