function deleteTheme(id, name) {
	BootstrapDialog.confirm({
		title: "DANGER",
		message:
			"Warning! Are you sure you want to delete the following theme: " + name + "?",
		type: BootstrapDialog.TYPE_DANGER,
		closable: true,
		draggable: true,
		btnOKClass: "btn-danger",
		callback: function (result) {
			if (result) {
				$.ajax({
					url: base_url + "index.php/themes/delete",
					type: "post",
					data: { id: id },
					success: function (data) {
						$(".theme_" + id)
							.parent("tr:first")
							.remove(); // removes mode from table
					},
				});
			}
		},
	});
}

function addThemeDialog() {
	$.ajax({
		url: base_url + "index.php/themes/add",
		type: "post",
		success: function (html) {
			BootstrapDialog.show({
				title: "Create Theme",
				size: BootstrapDialog.SIZE_NORMAL,
				cssClass: "create-theme-dialog",
				nl2br: false,
				message: html,
				buttons: [
					{
						label: lang_admin_close,
						action: function (dialogItself) {
							dialogItself.close();
						},
					},
				],
			});
		},
	});
}

function addTheme(form) {
    if (formValidation()) {
        if (form.name.value != "") {
            $.ajax({
                url: base_url + "index.php/themes/add",
                type: "post",
                data: {
                    name: form.name.value,
                    foldername: form.foldername.value,
                    theme_mode: form.theme_mode.value,
                    header_logo: form.header_logo.value,
                    main_logo: form.main_logo.value,
                },
                success: function (html) {
                    location.reload();
                },
            });
        }
    }
}

function editThemeDialog(theme_id) {
	$.ajax({
		url: base_url + "index.php/themes/edit/" + theme_id,
		type: "post",
		success: function (html) {
			BootstrapDialog.show({
				title: "Edit Theme",
				size: BootstrapDialog.SIZE_NORMAL,
				cssClass: "edit-theme-dialog",
				nl2br: false,
				message: html,
				buttons: [
					{
						label: lang_admin_close,
						action: function (dialogItself) {
							dialogItself.close();
						},
					},
				],
			});
		},
	});
}

function editTheme(form, theme_id) {
    if (formValidation()) {
        if (form.name.value != "") {
            $.ajax({
                url: base_url + "index.php/themes/edit/" + theme_id,
                type: "post",
                data: {
                    name: form.name.value,
                    foldername: form.foldername.value,
                    theme_mode: form.theme_mode.value,
                    header_logo: form.header_logo.value,
                    main_logo: form.main_logo.value,
                },
                success: function (html) {
                    location.reload();
                },
            });
        }
    }
}

function printWarning(input, warning) {
    $('#warningMessageTheme').show();
    $(input).css('border', '2px solid rgb(217, 83, 79)');
    $('#warningMessageTheme').text(warning);
}

function removeWarning(input) {
    $(input).css('border', '');
    $('#warningMessageTheme').hide();
}

function formValidation() {
	let name = $("#nameInput");
    let foldername = $("#foldernameInput");
    let theme_mode = $("#themeModeInput");
    let header_logo = $("#headerLogoInput");
    let main_logo = $("#mainLogoInput");

    var unwantedCharacters = ['@', '.', '/', '\\', '$'];

    // name
	if (name.val() == "") {
		printWarning(name, "Please enter a name for the theme.");
		name.focus();
        $(name).css('border-color', 'red');
		return false;
	}
    removeWarning(name);
    
	if (unwantedCharacters.some(char => name.val().includes(char))) {
		printWarning(name, "The name contains unwanted characters. Only '()_-' are allowed.");
		name.focus();
        $(name).css('border-color', 'red');
		return false;
	}
    removeWarning(name);
    

    // foldername
    if (foldername.val() == "") {
		printWarning(foldername, "Please enter the name of the folder for that theme placed under assets/css.");
		foldername.focus();
        $(foldername).css('border-color', 'red');
		return false;
	}
    removeWarning(foldername);
    
	if (unwantedCharacters.some(char => foldername.val().includes(char))) {
		printWarning(foldername, "The foldername contains unwanted characters. Only '_' and '-' are allowed.");
		foldername.focus();
        $(foldername).css('border-color', 'red');
		return false;
	}
    removeWarning(foldername);
    

    // theme_mode
    if (theme_mode.val() == "") {
		printWarning(theme_mode, "Please select the theme_mode.");
		theme_mode.focus();
        $(theme_mode).css('border-color', 'red');
		return false;
	}
    removeWarning(theme_mode);
    

    // header_logo
    if (header_logo.val() == "") {
		printWarning(header_logo, "Please enter the filename of the header_logo for that theme placed under assets/logo without the file extension '.png'");
		header_logo.focus();
        $(header_logo).css('border-color', 'red');
		return false;
	}
    removeWarning(header_logo);
    
	if (unwantedCharacters.some(char => header_logo.val().includes(char))) {
		printWarning(header_logo, "The header_logo contains unwanted characters. Only '_' and '-' are allowed. Only PNG files are allowed. Remove the file extension '.png'");
		header_logo.focus();
        $(header_logo).css('border-color', 'red');
		return false;
	}
    removeWarning(header_logo);
    

    // main_logo
    if (main_logo.val() == "") {
		printWarning(main_logo, "Please enter the filename of the main_logo for that theme placed under assets/logo without the file extension '.png'");
		main_logo.focus();
        $(main_logo).css('border-color', 'red');
		return false;
	} else {
        removeWarning(main_logo);
    }
	if (unwantedCharacters.some(char => main_logo.val().includes(char))) {
		printWarning(main_logo, "The main_logo contains unwanted characters. Only '_' and '-' are allowed. Only PNG files are allowed. Remove the file extension '.png'");
		main_logo.focus();
        $(main_logo).css('border-color', 'red');
		return false;
	} else {
        removeWarning(main_logo);
    }

    return true;
}

$(document).ready(function() {
    $("#submitButton").click(function() {  // Validate the form when the submit button is clicked
        formValidation();
    });
});
