$(document).ready(function () {
    // Update the Radio Interfaces every 2 seconds
    $.get(base_url + 'index.php/radio/status/', function (result) {
        $('.status').html(result);
    });
    $('#radioResultsLoading').hide();
    setInterval(function () {
        // Update the Radio Interfaces every 2 seconds
        $.get(base_url + 'index.php/radio/status/', function (result) {
            $('.status').html(result);
        });
        $('#radioResultsLoading').hide();
    }, 2000);

});

function set_default_radio(radio_id) {
    $('#default_radio_btn_' + radio_id).addClass('running').prop('disable', true);
    $('#default_radio_btn_' + radio_id).removeClass('btn-outline-primary').addClass('btn-primary');
    $.ajax({
        type: 'POST',
        url: base_url + 'index.php/radio/set_default_radio',
        data: {
            radio_id: radio_id
        },
    });
}

function release_default_radio(radio_id) {
    $('#default_radio_btn_' + radio_id).addClass('running').prop('disable', true);
    $.ajax({
        type: 'POST',
        url: base_url + 'index.php/radio/release_default_radio',
        data: {
            radio_id: radio_id
        },
    });
}