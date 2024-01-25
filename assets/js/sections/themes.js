function deleteTheme(id, name) {
    BootstrapDialog.confirm({
        title: 'DANGER',
        message: 'Warning! Are you sure you want to delete the following theme: ' + name + '?'  ,
        type: BootstrapDialog.TYPE_DANGER,
        closable: true,
        draggable: true,
        btnOKClass: 'btn-danger',
        callback: function(result) {
            if(result) {
                $.ajax({
                    url: base_url + 'index.php/themes/delete',
                    type: 'post',
                    data: {'id': id
                    },
                    success: function(data) {
                        $(".theme_" + id).parent("tr:first").remove(); // removes mode from table
                    }
                });
            }
        }
    });
}

function addThemeDialog() {
    $.ajax({
        url: base_url + 'index.php/themes/add',
        type: 'post',
        success: function(html) {
            BootstrapDialog.show({
                title: 'Create Theme',
                size: BootstrapDialog.SIZE_NORMAL,
                cssClass: 'create-theme-dialog',
                nl2br: false,
                message: html,
                buttons: [{
                    label: lang_admin_close,
                    action: function (dialogItself) {
                        dialogItself.close();
                    }
                }]
            });
        }
    });
}

function addTheme(form) {
    if (form.name.value != '') {
        $.ajax({
            url: base_url + 'index.php/themes/add',
            type: 'post',
            data: {
                'name': form.name.value,
                'foldername': form.foldername.value,
                'theme_mode': form.theme_mode.value,
                'header_logo': form.header_logo.value,
                'main_logo': form.main_logo.value,
            },
            success: function(html) {
                location.reload();
            }
        });
    }
}

function editThemeDialog(theme_id) {
    $.ajax({
        url: base_url + 'index.php/themes/edit/' + theme_id,
        type: 'post',
        success: function(html) {
            BootstrapDialog.show({
                title: 'Edit Theme',
                size: BootstrapDialog.SIZE_NORMAL,
                cssClass: 'edit-theme-dialog',
                nl2br: false,
                message: html,
                buttons: [{
                    label: lang_admin_close,
                    action: function (dialogItself) {
                        dialogItself.close();
                    }
                }]
            });
        }
    });
}

function editTheme(form, theme_id) {
    if (form.name.value != '') {
        $.ajax({
            url: base_url + 'index.php/themes/edit/' + theme_id,
            type: 'post',
            data: {
                'name': form.name.value,
                'foldername': form.foldername.value,
                'theme_mode': form.theme_mode.value,
                'header_logo': form.header_logo.value,
                'main_logo': form.main_logo.value,
            },
            success: function(html) {
                location.reload();
            }
        });
    }
}