
$(document).ready(function () {
    if ($('#station_dxcc').length) {
        $('#station_dxcc').multiselect({
            // template is needed for bs5 support
            templates: {
                button: '<button type="button" style="text-align: left !important;" class="multiselect dropdown-toggle btn btn-secondary w-auto" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false"><span class="multiselect-selected-text"></span></button>',
            },
            enableFiltering: true,
            enableFullValueFiltering: false,
            enableCaseInsensitiveFiltering: true,
            filterPlaceholder: lang_general_word_search,
            widthSynchronizationMode: 'always',
            numberDisplayed: 1,
            inheritClass: true,
            buttonWidth: '100%',
            maxHeight: 300,
        });
        $('.multiselect-container .multiselect-filter', $('#station_dxcc').parent()).css({
            'position': 'sticky', 'top': '0px', 'z-index': 1, 'background-color': 'inherit', 'width': '100%', 'height': '37px'
        });
    }
});