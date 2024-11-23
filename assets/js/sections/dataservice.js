function test_dataservice(url, wl_id, wl_version) {

    let ds_url = url.endsWith('/') ? url : url + '/';
    let testvalue = Date.now();
    let result_el = $('#ds_testresult');
    let result_text = $('#ds_testresult_text');

    result_text.text("");
    result_el.removeClass();
    result_el.addClass('fas fa-spinner fa-spin');
    result_el.show();

    $.ajax({
        url: ds_url + 'test',
        type: 'POST',
        data: {
            testvalue: testvalue,
            wl_id: wl_id,
            wl_version: wl_version
        },
        success: function (r) {
            if (r.status == 'success' && r.data.testvalue == testvalue) {
                result_el.removeClass();
                result_el.addClass('fas fa-check text-success');
                result_text.text(lang_general_word_available);
            } else {
                result_el.removeClass();
                result_el.addClass('fas fa-times text-danger');
                result_text.text(lang_general_word_unavailable);
            }
        },
        error: function (r) {
            result_el.removeClass();
            result_el.addClass('fas fa-times text-danger');
            result_text.text(lang_general_word_unavailable);
        }
    });
}

$('#dataservice_url').on('change input focus', function () {
    $('#ds_testresult').hide();
    $('#ds_testresult_text').text("");
});

$('#dataservice_enabled').on('change', function () {
    if ($(this).is(':checked')) {
        $('#dataservice_settings_div').show();
    } else {
        $('#dataservice_settings_div').hide();
    }
});

$(document).ready(function () {
    $('#dataservice_enabled').trigger('change');
    if ($('#dataservice_enabled').is(':checked')) {
        test_dataservice($('#dataservice_url').val(), $('#wavelog_id').val(), $('#wavelog_version').val());
    }
    $('[data-bs-toggle="insecureinfo"]').tooltip();
});