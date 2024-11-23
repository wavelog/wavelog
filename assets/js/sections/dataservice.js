function test_dataservice(url, wl_id) {

    let ds_url = url.endsWith('/') ? url : url + '/';
    let ds_test_btn = $('#dataservice_url_tester');
    let testvalue = Date.now();

    $.ajax({
        url: ds_url + 'test',
        type: 'POST',
        data: {
            testvalue: Date.now(),
            wl_id: wl_id
        },
        success: function (r) {
            if (r.status == 'success' && r.data.testvalue == testvalue) {
                ds_test_btn.removeClass('btn-secondary btn-danger').addClass('btn-success');
            } else {
                ds_test_btn.removeClass('btn-secondary btn-success').addClass('btn-danger');
            }
        },
        error: function (r) {
            console.error('Test error: ' + r);
            ds_test_btn.removeClass('btn-secondary btn-success').addClass('btn-danger');
        }
    });
}

$('#dataservice_url').on('change input focus', function () {
    $('#dataservice_url_tester').removeClass('btn-success btn-danger').addClass('btn-secondary');
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
    $('[data-bs-toggle="insecureinfo"]').tooltip();
});