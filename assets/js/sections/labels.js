$('.labeltable').on('click', 'input[type="checkbox"]', function() {
	var clickedlabelid = $(this).closest('tr').attr("class");
	clickedlabelid = clickedlabelid.match(/\d+/)[0];
	saveDefault(clickedlabelid);
    $('input:checkbox').not(this).prop('checked', false);
});

$(document).on('click','#button_markprint', function (e) {
	e.preventDefault();
	$('#button_markprint').attr("disabled", true).addClass("running");
	murl=base_url + 'index.php/qslprint/qsl_printed/' + $('#sid2print').val();
	$.ajax({
		url: murl,
		type: 'get',
		success: function (html) {
			$('#button_markprint').removeClass("running");
			$('#button1id').attr("disabled", true);		// Disable printing as well, since every QSO for this station has been marked
		},
		error: function (html) {
			$('#button_markprint').prop("disabled", false).removeClass("running");
		}
	});
});

function saveDefault(id) {
	$.ajax({
		url: base_url + 'index.php/labels/saveDefaultLabel',
		type: 'post',
		data: {'id': id},
		success: function (html) {
		}
	});
}

function printat(stationid) {
	$.ajax({
		url: base_url + 'index.php/labels/startAtLabel',
		type: 'post',
		data: {'stationid': stationid},
		success: function (html) {
			BootstrapDialog.show({
				title: 'Start printing at which label?',
				size: BootstrapDialog.SIZE_NORMAL,
				cssClass: 'qso-dialog',
				nl2br: false,
				message: html,
				onshown: function(dialog) {
					dialog.getButton('button_markprint').disable()
				},
				buttons: [
				{
					label: lang_print_queue,
					id: "button1id",
					cssClass: "btn btn-primary",
					action: function() { 
						$('#button_markprint').removeClass("disabled");
						$('#button_markprint').attr("disabled", false);
						$("#pform").submit(); 
					}
				},
				{
					label: lang_mark_qsl_as_printed+'<div class="ld ld-ring ld-spin"></div>',
					cssClass: "btn btn-secondary me-3 ld-ext-right",
					id: "button_markprint"
				},
				{
					label: lang_admin_close,
					action: function (dialogItself) {
						dialogItself.close();
						location.reload(); 	// Refresh Mainpage, because labels could have been marked as sent
					}
				}]
			});
		}
	});
}

function deletelabel(id) {
	BootstrapDialog.confirm({
		title: 'DANGER',
		message: 'Warning! Are you sure you want this label?',
		type: BootstrapDialog.TYPE_DANGER,
		closable: true,
		draggable: true,
		btnOKClass: 'btn-danger',
		callback: function(result) {
			if (result) {
				window.location.replace(base_url + 'index.php/labels/delete/'+id);
			}
		}
	});
}

function deletepaper(id) {
	var message = 'Warning! Are you sure you want delete this paper type?';
	var currentRow = $(".paper_"+id).first().closest('tr');
	var inuse = currentRow.find("td:eq(4)").text();
	if (inuse > 0) {
		message = 'Warning! This paper type is in use. Are you really sure you want delete this paper type?';
	}
	BootstrapDialog.confirm({
		title: 'DANGER',
		message: message,
		type: BootstrapDialog.TYPE_DANGER,
		closable: true,
		draggable: true,
		btnOKClass: 'btn-danger',
		callback: function(result) {
			if (result) {
				window.location.replace(base_url + 'index.php/labels/deletePaper/'+id);
			}
		}
	});
}
