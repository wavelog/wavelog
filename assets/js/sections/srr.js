function ExportSrr(station_id, propmode_los, without_rda) {
	if ($(".alert").length > 0) {
		$(".alert").remove();
	}
	if ($(".errormessages").length > 0) {
		$(".errormessages").remove();
	}
	$(".ld-ext-right-"+station_id).addClass('running');
	$(".ld-ext-right-"+station_id).prop('disabled', true);

	$.ajax({
		url: base_url + 'index.php/srr/upload_station',
		type: 'post',
		data: {'station_id': station_id, 'propmode_los': (propmode_los ? 1 : 0), 'without_rda': (without_rda ? 1 : 0)},
		success: function (data) {
			$(".ld-ext-right-"+station_id).removeClass('running');
			$(".ld-ext-right-"+station_id).prop('disabled', false);
			$.each(data.info, function(index, value){
				$('#modcount'+value.station_id).html(value.modcount);
				$('#notcount'+value.station_id).html(value.notcount);
				$('#totcount'+value.station_id).html(value.totcount);
			});
			if (data.status == 'rda') {
				RdaSrr(station_id, propmode_los, data.rda_qsos);
				return;
			}
			if (data.status == 'propmode') {
				PropmodeSrr(station_id, without_rda, data.qsos);
				return;
			}
			if (data.status == 'OK') {
				$("#export").append('<div class="alert alert-success" role="alert">' + data.infomessage + '</div>');
			}

			if (data.hasOwnProperty("errormessages") && data.errormessages.length > 0) {
				$("#export").append(
					'<div class="errormessages">\n' +
					'    <div class="card mt-2">\n' +
					'        <div class="card-header bg-danger">\n' +
					'            Error Message\n' +
					'        </div>\n' +
					'        <div class="card-body">\n' +
					'            <div class="errors"></div>\n' +
					'        </div>\n' +
					'    </div>\n' +
					'</div>'
				);
				$.each(data.errormessages, function (index, value) {
					$(".errors").append('<li>' + value);
				});
			}
		}
	});
}

function RdaSrr(station_id, propmode_los, qsos) {
	let message = '<p>' + lang_srr_rda_warning + '</p>' +
		'<p>' + lang_srr_rda_message + '</p>' +
		'<table class="table table-sm table-striped">' +
		'<thead><tr><th>' + lang_srr_callsign + '</th><th>' + lang_srr_date + '</th><th>' + lang_srr_band + '</th><th>' + lang_srr_mode + '</th><th>' + lang_srr_rda + '</th></tr></thead><tbody>';
	$.each(qsos, function (index, qso) {
		message += '<tr><td>' + qso.call + '</td><td>' + qso.date + '</td><td>' + qso.band + '</td><td>' + qso.mode + '</td><td>' + $('<div>').text(qso.rda).html() + '</td></tr>';
	});
	message += '</tbody></table>';

	BootstrapDialog.show({
		title: lang_srr_rda_title,
		type: BootstrapDialog.TYPE_WARNING,
		size: BootstrapDialog.SIZE_NORMAL,
		cssClass: 'srr-rda-dialog',
		nl2br: false,
		message: message,
		buttons: [{
			label: lang_srr_propmode_cancel,
			cssClass: 'btn-primary',
			action: function(dialogItself) {
				dialogItself.close();
			}
		}, {
			label: lang_srr_rda_upload,
			action: function(dialogItself) {
				dialogItself.close();
				ExportSrr(station_id, propmode_los, true);
			}
		}]
	});
}

function PropmodeSrr(station_id, without_rda, qsos) {
	let message = '<p>' + lang_srr_propmode_message + '</p>' +
		'<table class="table table-sm table-striped">' +
		'<thead><tr><th>' + lang_srr_callsign + '</th><th>' + lang_srr_date + '</th><th>' + lang_srr_band + '</th><th>' + lang_srr_mode + '</th></tr></thead><tbody>';
	$.each(qsos, function (index, qso) {
		message += '<tr><td>' + qso.call + '</td><td>' + qso.date + '</td><td>' + qso.band + '</td><td>' + qso.mode + '</td></tr>';
	});
	message += '</tbody></table>';

	BootstrapDialog.show({
		title: lang_srr_propmode_title,
		type: BootstrapDialog.TYPE_WARNING,
		size: BootstrapDialog.SIZE_WIDE,
		cssClass: 'srr-propmode-dialog',
		nl2br: false,
		message: message,
		buttons: [{
			label: lang_srr_propmode_los,
			cssClass: 'btn-primary',
			action: function(dialogItself) {
				dialogItself.close();
				ExportSrr(station_id, true, without_rda);
			}
		}, {
			label: lang_srr_propmode_cancel,
			action: function(dialogItself) {
				dialogItself.close();
			}
		}]
	});
}

function importSrr() {
	if ($(".alert").length > 0) {
		$(".alert").remove();
	}
	if ($(".errormessages").length > 0) {
		$(".errormessages").remove();
	}
	$(".ld-ext-right-import").addClass('running');
	$(".ld-ext-right-import").prop('disabled', true);

	$.ajax({
		url: base_url + 'index.php/srr/download',
		type: 'post',
		data: {'date': $(".importdate").val()},
		success: function (data) {
			$(".ld-ext-right-import").removeClass('running');
			$(".ld-ext-right-import").prop('disabled', false);
			$("#import").append('<div class="alert alert-success" role="alert">' + data + '</div>');
		}
	});
}
