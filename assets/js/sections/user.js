// 
// Javascript for User Section
//

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
