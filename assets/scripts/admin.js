/*! Build a Houser - v1.0.7
 * https://iworks.pl/
 * Copyright (c) 2023; * Licensed GPLv2+
 */
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

jQuery( document ).ready(function($) {
    $( function() {
        $( ".iworks-build_a_house-row .datepicker" ).each( function() {
            var format = $(this).data('date-format') || 'yy-mm-dd';
            $(this).datepicker({ dateFormat: format });
        });
    } );
});

jQuery( document ).ready(function($) {
    $( function() {
    } );
});

jQuery( document ).ready(function($) {
    window.console.log('start select2.js');
    // $('select.iworks-select2').select2();
    $(".iworks-build_a_house-row select.select2").select2({
        ajax: {
            url: ajaxurl,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term, // search term
                    page: params.page,
                    action: $(this).attr('id'),
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
