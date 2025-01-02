// 
// Javascript for User Section
//

function clearRefSwitches() {
    var iotaSwitch = document.getElementById("iotaToQsoTab");
    iotaSwitch.checked = false;
    var sotaSwitch = document.getElementById("sotaToQsoTab");
    sotaSwitch.checked = false;
    var wwffSwitch = document.getElementById("wwffToQsoTab");
    wwffSwitch.checked = false;
    var potaSwitch = document.getElementById("potaToQsoTab");
    potaSwitch.checked = false;
    var sigSwitch = document.getElementById("sigToQsoTab");
    sigSwitch.checked = false;
    var dokSwitch = document.getElementById("dokToQsoTab");
    dokSwitch.checked = false;
}

function actions_modal(user_id, modal) {
    $.ajax({
        url: base_url + 'index.php/user/actions_modal',
        type: 'POST',
        data: { 
            modal: modal,
            user_id: user_id 
        },
        success: function(response) {
            $('#actionsModal-container').html(response);
            $('#actionsModal').modal('show');
        },
        error: function() {
            alert(lang_general_word_error);
        }
    });
    $(window).on('blur', function() {
        $('#actionsModal').modal('hide');
    }); 
}

function send_passwort_reset(user_id) {
    $('#pwd_reset_message').hide().removeClass('alert-success alert-danger');
    $('#send_resetlink_btn').prop('disabled', true).addClass('running');
    $('#passwordreset_sent').hide().removeClass('fa-check fa-times text-success text-danger');

    $.ajax({
        url: base_url + 'index.php/user/admin_send_password_reset',
        type: 'POST',
        data: { 
            user_id: user_id,
            submit_allowed: true
        },
        success: function(result) {
            if (result) {
                $('#pwd_reset_message').show().text(lang_admin_password_reset_processed).addClass('alert-success');
                $('#send_resetlink_btn').prop('disabled', false).removeClass('running');
                $('#passwordreset_sent').show().addClass('fa-check text-success');
            } else {
                $('#pwd_reset_message').show().text(lang_admin_email_settings_incorrect).addClass('alert-danger');
                $('#send_resetlink_btn').prop('disabled', false).removeClass('running');
                $('#passwordreset_sent').show().addClass('fa-times text-danger');
            }
        },
        error: function() {
            $('#pwd_reset_message').show().text(lang_admin_password_reset_failed).addClass('alert-danger');
            $('#send_resetlink_btn').prop('disabled', false).removeClass('running');
            $('#passwordreset_sent').show().addClass('fa-times text-danger');
        }
    });
}

function convert_user(user_id, convert_to) {
    $('#user_converted_message').hide().removeClass('alert-success alert-danger');
    $('#convert_user_btn').prop('disabled', true).removeClass('btn-secondary').addClass('btn-danger running');
    $('#user_converted').hide().removeClass('fa-check fa-times text-success text-danger');

    $.ajax({
        url: base_url + 'index.php/user/convert',
        type: 'POST',
        data: { 
            user_id: user_id,
            convert_to: convert_to,
        },
        success: function(result) {
            if (result) {
                $('#user_converted_message').show().text(lang_account_conversion_processed).addClass('alert-success');
                $('#convert_user_btn').removeClass('running btn-danger').addClass('btn-secondary');
                $('#user_converted').show().addClass('fa-check text-success');
            } else {
                $('#user_converted_message').show().text(lang_account_conversion_failed).addClass('alert-danger');
                $('#convert_user_btn').prop('disabled', false).removeClass('running');
                $('#user_converted').show().addClass('fa-times text-danger');
            }
        },
        error: function() {
            $('#user_converted_message').show().text(lang_account_conversion_failed).addClass('alert-danger');
            $('#convert_user_btn').prop('disabled', false).removeClass('running');
            $('#user_converted').show().addClass('fa-times text-danger');
        }
    });
}


$(document).ready(function(){

    $('#adminusertable').DataTable({
        "pageLength": 25,
        responsive: true,
        ordering: true,
        "scrollY": "100%",
        "scrollCollapse": true,
        "paging": true,
        "language": {
            url: getDataTablesLanguageUrl(),
        },
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'csv',
                exportOptions: {
                    columns: [ 0, 1, 2, 3, 4, 5 ]
                }
            }
        ]
    });

    $('#adminclubusertable').DataTable({
        "pageLength": 25,
        responsive: true,
        ordering: true,
        "scrollY": "100%",
        "scrollCollapse": true,
        "paging": true,
        "language": {
            url: getDataTablesLanguageUrl(),
        },
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'csv',
                exportOptions: {
                    columns: [ 0, 1, 2, 3, 4, 5 ]
                }
            }
        ]
    });

    $(function () {
        $('.btn-tooltip').tooltip();
    });

    $('.icon_selectBox').off('click').on('click', function(){
        var boxcontent = $(this).attr('data-boxcontent');
        if ($('.icon_selectBox_data[data-boxcontent="'+boxcontent+'"]').is(":hidden")) { $('.icon_selectBox_data[data-boxcontent="'+boxcontent+'"]').show(); } else { $('.icon_selectBox_data[data-boxcontent="'+boxcontent+'"]').hide(); }
    });
    $('.icon_selectBox_data').off('mouseleave').on('mouseleave', function(){ if ($(this).is(":visible")) { $(this).hide(); } });
    $('.icon_selectBox_data label').off('click').on('click', function(){
        var boxcontent = $(this).closest('.icon_selectBox_data').attr('data-boxcontent');
        $('input[name="user_map_'+boxcontent+'_icon"]').attr('value',$(this).attr('data-value'));
        if ($(this).attr('data-value') != "0") {
            $('.user_icon_color[data-icon="'+boxcontent+'"]').show();
            $('.icon_selectBox[data-boxcontent="'+boxcontent+'"] .icon_overSelect').html($(this).html());
        } else {
            $('.user_icon_color[data-icon="'+boxcontent+'"]').hide();
            $('.icon_selectBox[data-boxcontent="'+boxcontent+'"] .icon_overSelect').html($(this).html().substring(0,10)+'.');
        }
        $('.icon_selectBox_data[data-boxcontent="'+boxcontent+'"]').hide();
    });

    $('.collapse').on('shown.bs.collapse', function(e) {
        var $card = $(this).closest('.accordion-item');
        var $open = $($(this).data('parent')).find('.collapse.show');

        var additionalOffset = 0;
        if($card.prevAll().filter($open.closest('.accordion-item')).length !== 0)
        {
            additionalOffset =  $open.height();
        }
        $('html,body').animate({
            scrollTop: $card.offset().top - additionalOffset
        }, 300);
    });

	$('#lotw_test_btn').click(function() {
		var btn_div = $('#lotw_test_btn');
		var msg_div = $('#lotw_test_txt');

		msg_div.removeClass('alert-success alert-danger').text('').hide();
		btn_div.removeClass('alert-success alert-danger').addClass('running').prop('disabled', true);

		$.ajax({
			url: base_url + 'index.php/lotw/check_lotw_credentials',
			type: 'POST',
			contentType: "application/json",
			data: JSON.stringify({lotw_user: $("#user_lotw_name").val(), lotw_pass: $("#user_lotw_password").val()}),
			success: function(res) {
				if(res.status == 'OK') {
					btn_div.addClass('alert-success').removeClass('running').prop('disabled', false);
					msg_div.addClass('alert-success').text(res.details).show();
				} else {
					btn_div.addClass('alert-danger').removeClass('running').prop('disabled', false);
					msg_div.addClass('alert-danger').text('Error: '+res.details).show();
				}
			},
			error: function(res) {
                btn_div.addClass('alert-danger').removeClass('running').prop('disabled', false);;
				msg_div.addClass('alert-danger').text('ERROR').show();
			},
		})
	});

    $('.admin_pwd_reset').click(function() {
        var pwd_reset_user_name = $(this).data('username');
        var pwd_reset_user_callsign = $(this).data('callsign');
        var pwd_reset_user_id = $(this).data('userid');
        var pwd_reset_user_email = $(this).data('usermail');

        BootstrapDialog.confirm({
            title: lang_general_word_warning,
            message:
                lang_admin_confirm_pwd_reset + "\n\n" + 
                lang_admin_user + ": " + pwd_reset_user_name + "\n" + 
                lang_gen_hamradio_callsign + ": " + pwd_reset_user_callsign,
            type: BootstrapDialog.TYPE_DANGER,
            btnCancelLabel: lang_general_word_cancel,
            btnOKLabel: lang_general_word_ok,
            btnOKClass: "btn-warning",
            closable: false,  // set closable: false, to prevent closing during ajax call
            callback: function (result) {
                if (result) {
                    var wait_dialog = BootstrapDialog.show({
                        title: lang_general_word_please_wait,
                        message: '<div class="text-center"><i class="fas fa-spinner fa-spin fa-3x"></i></div>',
                        closable: false,
                        buttons: []
                    });
                    $.ajax({
                        url: base_url + 'index.php/user/admin_send_password_reset',
                        type: 'POST',
                        data: { 
                            user_id: pwd_reset_user_id,
                            submit_allowed: true
                        },
                        success: function(result) {
                            wait_dialog.close();

                            if (result) {
                                $('#pwd_reset_message').addClass('alert-success');
                                $('#pwd_reset_message').text(lang_admin_password_reset_processed + " " + pwd_reset_user_name + " (" + pwd_reset_user_email + ")");
                                $('#pwd_reset_message').show();
                            } else {
                                $('#pwd_reset_message').addClass('alert-danger');
                                $('#pwd_reset_message').text(lang_admin_email_settings_incorrect);
                                $('#pwd_reset_message').show();
                            }
                        },
                        error: function() {
                            wait_dialog.close();

                            $('#pwd_reset_message').addClass('alert-danger');
                            $('#pwd_reset_message').text('Error! Description: admin_send_password_reset failed');
                            $('#pwd_reset_message').show();
                        }
                    });
                }
            },
        }).getModalHeader().find('.modal-title').after('<i class="fas fa-spinner fa-spin fa-2x"></i>');

    });
});
