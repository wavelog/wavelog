// Callsign always has focus on load
$("#callsign").focus();

var sessiondata={};
$(document).ready(async function () {
	sessiondata=await getSession();			// save sessiondata global (we need it later, when adding qso)
	await restoreContestSession(sessiondata);	// wait for restoring until finished
	setRst($("#mode").val());
	$('#contestname').val($('#contestname_select').val());
});

// Always update the contestname
$('#contestname_select').change(function () {
	$('#contestname').val($('#contestname_select').val());
});

function disabledContestnameSelect(disabled) {
	if (disabled) {
		$("#contestname_select")
			.prop('disabled', true)
			.attr({
				'title': lang_contestname_warning,
				'data-bs-toggle': 'tooltip',
				'data-bs-html': 'true',
				'data-bs-placement': 'top'
			})
			.tooltip();
	} else {
		$("#contestname_select")
			.prop('disabled', false)
			.removeAttr('title data-bs-toggle data-bs-html data-bs-placement')
			.tooltip('dispose');
	}
}

// Resets the logging form and deletes session from database
async function reset_contest_session() {
	await $.ajax({
		url: base_url + 'index.php/contesting/deleteSession',
		type: 'post',
		success: function (data) {

		}
	});
	// the user is now allowed again to change the contest name
	disabledContestnameSelect(false);
	// reset the form
	$('#name').val("");
	$('.callsign-suggestions').text("");
	$('#callsign').val("");
	$('#comment').val("");

	$("#exch_serial_s").val("1");
	$("#exch_serial_r").val("");
	$('#exch_sent').val("");
	$('#exch_rcvd').val("");
	$("#exch_gridsquare_r").val("");

	$("#callsign").focus();
	setRst($("#mode").val());
	$("#exchangetype").val("None");
	setExchangetype("None");
	$("#contestname_select").val("Other").change();
	$(".contest_qso_table_contents").empty();
 	$('#copyexchangeto').val("None");

	if (!$.fn.DataTable.isDataTable('.qsotable')) {
		$.fn.dataTable.moment('DD-MM-YYYY HH:mm:ss');
		$('.qsotable').DataTable({
			"stateSave": true,
			"pageLength": 25,
			responsive: false,
			"scrollY": "400px",
			"scrollCollapse": true,
			"paging": false,
			"scrollX": true,
			"language": {
				url: getDataTablesLanguageUrl(),
			},
			order: [0, 'desc'],
			"columnDefs": [
				{
					"render": function ( data, type, row ) {
						return row[8] !== null && row[8] !== '' ? pad(row[8], 3) : '';
					},
					"targets" : 8
				},
				{
					"render": function ( data, type, row ) {
						return row[9] !== null && row[9] !== '' ? pad(row[9], 3) : '';
					},
					"targets" : 9
				}
			]
		});
	}
	var table = $('.qsotable').DataTable();
	table.clear();

}

function sort_exchange() {

	// Get the selected sequence
	let exchangeSelect = $('#exchangesequence_select');

	// If the sequence is not set, we need to set one to prevent errors
	if (!exchangeSelect.val()) {
		exchangeSelect.val('s-g-e');
	}

	// Split the squence into an array
	let selectedOrder = exchangeSelect.val().split('-'); 

	// Map sequence to corresponding SENT elements
	let mapping = {
		"g": ".gridsquares",
		"s": ".serials",
		"e": ".exchanges",
	};

	// Reorder the elements in the DOM
	selectedOrder.forEach(function(item) {
		$('#sent_exchange').append($(mapping[item]));
	});

	// Map sequence to corresponding RECEIVED elements
	mapping = {
		"g": ".gridsquarer",
		"s": ".serialr",
		"e": ".exchanger",
	};

	// Reorder the elements in the DOM
	selectedOrder.forEach(function(item) {
		$('#rcvd_exchange').append($(mapping[item]));
	});
}

// Change the sequence of the exchange fields
$('#exchangesequence_select').change(function () {
	sort_exchange();
});

// Show Settings modal on click
$('#moreSettingsButton').click(function () {
	$('#moreSettingsModal').modal('show');
});

// Storing the contestid in contest session
$('#contestname, #copyexchangeto, #exchangesequence_select, #band, #mode, #frequency, #radio').change(function () {
	var formdata = new FormData(document.getElementById("qso_input"));
	setSession(formdata);
});

// Storing the exchange type in contest session
$('#exchangetype').change(function () {
	var exchangetype = $("#exchangetype").val();
	setExchangetype(exchangetype);
	var formdata = new FormData(document.getElementById("qso_input"));
	setSession(formdata);
});

async function setSession(formdata) {
    formdata.set('copyexchangeto',$("#copyexchangeto option:selected").index());
	await $.ajax({
		url: base_url + 'index.php/contesting/setSession',
		type: 'post',
		data: formdata,
		processData: false,
		contentType: false,
		success: function (data) {
			sessiondata=data;
		}
	});
}

// realtime clock
if ( ! manual ) {
	$(function ($) {
		handleStart = setInterval(function() { getUTCTimeStamp($('.input_time')); }, 500);
	});

	$(function ($) {
		          handleDate = setInterval(function() { getUTCDateStamp($('.input_date')); }, 1000);
	});
}

// We don't want spaces to be written in callsign
// We don't want spaces to be written in exchange
// We don't want spaces to be written in gridsquare
// We don't want spaces to be written in time :)
$(function () {
	$('#callsign, #exch_rcvd, #exch_gridsquare_r, #start_time').on('keypress', function (e) {
		if (e.which == 32) {
			return false;
		}
	});
});

// We don't want spaces to be written in serial
$(function () {
	$('#exch_serial_r').on('keypress', function (e) {
		if (e.which == 32) {
			return false;
		}
	});
});

// Some Browsers (Firefox...) allow Chars in Serial Input. We don't want that.
// reference: https://stackoverflow.com/questions/49923588/input-type-number-with-pattern-0-9-allows-letters-in-firefox
$(function () {
    $('#exch_serial_s, #exch_serial_r').on('keypress', function (e) {
        var charCode = e.which || e.keyCode;
        var charStr = String.fromCharCode(charCode);

        if (!/^[0-9]+$/.test(charStr)) {
            e.preventDefault();
        }
    });
});


// Here we capture keystrokes to execute functions
document.onkeyup = function (e) {
	// ALT-W wipe
	if (e.altKey && e.key == "w") {
		reset_log_fields();
		// CTRL-Enter logs QSO
	} else if ((e.key === "Enter") && (e.ctrlKey || e.metaKey)) {
		$("#callsign").blur();
		logQso();
		// Enter in received exchange logs QSO
	} else if ((e.key == "Enter") && (
		($(document.activeElement).attr("id") == "exch_rcvd")
		|| ($(document.activeElement).attr("id") == "exch_gridsquare_r")
		|| ($(document.activeElement).attr("id") == "exch_serial_r")
		|| (($(document.activeElement).attr("id") == "callsign") && ($("#exchangetype").val() == "None"))
	)) {
		logQso();
	} else if (e.key == "Escape") {
		reset_log_fields();
		// Space to jump to either callsign or the various exchanges
	} else if (e.key == " ") {
		let exchangetype = $("#exchangetype").val();
		let sequence = $('#exchangesequence_select').val().split('-');

		let mapping = {
			"g": "exch_gridsquare_r",
			"s": "exch_serial_r",
			"e": "exch_rcvd",
		};

		if (manual && $(document.activeElement).attr("id") == "start_time") {
			$("#callsign").focus();
			return false;
		}
        
		if (exchangetype == 'Exchange') {
			if ($(document.activeElement).attr("id") == "callsign") {
				$("#exch_rcvd").focus();
				return false;
			} else if ($(document.activeElement).attr("id") == "exch_rcvd") {
				$("#callsign").focus();
				return false;
			}
		}
		else if (exchangetype == 'Serial') {
			if ($(document.activeElement).attr("id") == "callsign") {
				$("#exch_serial_r").focus();
				return false;
			} else if ($(document.activeElement).attr("id") == "exch_serial_r") {
				$("#callsign").focus();
				return false;
			}
		}
		else if (exchangetype == 'Gridsquare') {
			if ($(document.activeElement).attr("id") == "callsign") {
				$("#exch_gridsquare_r").focus();
				return false;
			} else if ($(document.activeElement).attr("id") == "exch_gridsquare_r") {
				$("#callsign").focus();
				return false;
			}
		}
		else if (exchangetype == 'Serialexchange') {
			let filteredSequence = sequence.filter(key => key !== 'g');
		
			if ($(document.activeElement).attr("id") == "callsign") {
				$(`#${mapping[filteredSequence[0]]}`).focus();
				return false;
			} else if ($(document.activeElement).attr("id") == mapping[filteredSequence[0]]) {
				$(`#${mapping[filteredSequence[1]]}`).focus();
				return false;
			} else if ($(document.activeElement).attr("id") == mapping[filteredSequence[1]]) {
				$("#callsign").focus();
				return false;
			}
		}
		else if (exchangetype == 'Serialgridsquare') {
			let filteredSequence = sequence.filter(key => key !== 'e');

			if ($(document.activeElement).attr("id") == "callsign") {
				$(`#${mapping[filteredSequence[0]]}`).focus();
				return false;
			} else if ($(document.activeElement).attr("id") == mapping[filteredSequence[0]]) {
				$(`#${mapping[filteredSequence[1]]}`).focus();
				return false;
			} else if ($(document.activeElement).attr("id") == mapping[filteredSequence[1]]) {
				$("#callsign").focus();
				return false;
			}
		}
		else if (exchangetype == 'SerialGridExchange') {
			if ($(document.activeElement).attr("id") == "callsign") {
				$(`#${mapping[sequence[0]]}`).focus();
				return false;
			} else if ($(document.activeElement).attr("id") == mapping[sequence[0]]) {
				$(`#${mapping[sequence[1]]}`).focus();
				return false;
			} else if ($(document.activeElement).attr("id") == mapping[sequence[1]]) {
				$(`#${mapping[sequence[2]]}`).focus();
				return false;
			} else if ($(document.activeElement).attr("id") == mapping[sequence[2]]) {
				$("#callsign").focus();
				return false;
			}
		}
	}

};

/* time input shortcut */
$('#start_time').change(function() {
	var raw_time = $(this).val();
	if(raw_time.match(/^\d\[0-6]d$/)) {
		raw_time = "0"+raw_time;
	}
	if(raw_time.match(/^[012]\d[0-5]\d$/)) {
		raw_time = raw_time.substring(0,2)+":"+raw_time.substring(2,4);
		$('#start_time').val(raw_time);
	}
});

/* date input shortcut */
$('#start_date').change(function() {
	 raw_date = $(this).val();
	if(raw_date.match(/^[12]\d{3}[01]\d[0123]\d$/)) {
		raw_date = raw_date.substring(0,4)+"-"+raw_date.substring(4,6)+"-"+raw_date.substring(6,8);
		$('#start_date').val(raw_date);
	}
});


$("#callsign").on( "blur", function() {
	var call = $(this).val();
	if ((call.length>=3) && ($("#bearing_info").html().length == 0)) {
		getCallbook();
	}
});

var scps=[];

// On Key up check and suggest callsigns
$("#callsign").keyup(async function (e) {
	var call = $(this).val();
	if ((!((e.keyCode == 10 || e.keyCode == 13) && (e.ctrlKey || e.metaKey))) && (call.length >= 3)) {	// prevent checking again when pressing CTRL-Enter

		if ($(this).val().length >= 3) {
			$callsign = $(this).val().replace('Ø', '0');
			if (scps.filter((call => call.includes($(this).val().toUpperCase()))).length <= 0) {
				$.ajax({
					url: 'lookup/scp',
					method: 'POST',
					data: {
						callsign: $callsign.toUpperCase()
					},
					success: function(result) {
						$('.callsign-suggestions').text(result);
						scps=result.split(" ");
						highlight(call.toUpperCase());
					}
				});
			} else {
				$('.callsign-suggestions').text(scps.filter((call) => call.includes($(this).val().toUpperCase())).join(' '));
				highlight(call.toUpperCase());
			}
		}

		await checkIfWorkedBefore();
		var qTable = $('.qsotable').DataTable();
		qTable.search(call).draw();
	}
	else if (call.length <= 2) {
		$('.callsign-suggestions').text("");
		$('#bearing_info').html("");
	}
});

async function getCallbook() {
	var call = $("#callsign").val();
	if (call.length >= 3) {
		$.getJSON(base_url + 'index.php/logbook/json/' + call + '/'+$("#band").val()+'/'+$("#band").val() + '/' + current_active_location, function(result) {
			try {
				$('#bearing_info').html(result.bearing);
			} catch {}
		});
	}
}

async function checkIfWorkedBefore() {
	var call = $("#callsign").val();
	if (call.length >= 3) {
		$('#callsign_info').text("");
		$.ajax({
			url: base_url + 'index.php/contesting/checkIfWorkedBefore',
			type: 'post',
			data: {
				'call': call,
				'mode': $("#mode").val(),
				'band': $("#band").val(),
				'contest': $("#contestname").val()
			},
			success: function (result) {
				if (result.message.substr(0,6) == 'Worked') {
					$('#callsign_info').text(result.message);
				}
			}
		});
	}
}

async function reset_log_fields() {
	$('#name').val("");
	$('.callsign-suggestions').text("");
	$('#callsign').val("");
	$('#comment').val("");
	$('#exch_rcvd').val("");
	$('#exch_serial_r').val("");
	$('#exch_gridsquare_r').val("");
	$("#callsign").focus();
	setRst($("#mode").val());
	$('#callsign_info').text("");
	$('#bearing_info').text("");

	await refresh_qso_table(sessiondata);
	var qTable = $('.qsotable').DataTable();
	qTable.search('').draw();
}

RegExp.escape = function (text) {
	return text.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&");
}

function highlight(term, base) {
	if (!term) return;
	base = base || document.body;
	var re = new RegExp("(" + RegExp.escape(term) + ")", "gi");
	var replacement = "<span class=\"text-primary\">" + term + "</span>";
	$(".callsign-suggestions", base).contents().each(function (i, el) {
		if (el.nodeType === 3) {
			var data = el.data;
			if (data = data.replace(re, replacement)) {
				var wrapper = $("<span>").html(data);
				$(el).before(wrapper.contents()).remove();
			}
		}
	});
}

// Only set the frequency when not set by userdata/PHP.
if ($('#frequency').val() == "") {
	$.get('qso/band_to_freq/' + $('#band').val() + '/' + $('.mode').val(), function (result) {
		$('#frequency').val(result);
		$('#frequency_rx').val("");
	});
}

/* on mode change */
$('#mode').change(function () {
		if ($('#radio').val() == '0') {
	$.get('qso/band_to_freq/' + $('#band').val() + '/' + $('.mode').val(), function (result) {
		$('#frequency').val(result);
		$('#frequency_rx').val("");
	});
	}
	setRst($("#mode").val());
	checkIfWorkedBefore();
});

/* Calculate Frequency */
/* on band change */
$('#band').change(function () {
		if ($('#radio').val() == '0') {
	$.get('qso/band_to_freq/' + $(this).val() + '/' + $('.mode').val(), function (result) {
		$('#frequency').val(result);
		$('#frequency_rx').val("");
	});
	}
	checkIfWorkedBefore();
});

/* on radio change */
// in the footer is defined that we want to clear the frequency when changing the radio
// this may fit for QSOs, but not for the contesting module
// so we want atleast to set the frequency to the default frequency of the band
$('#radio').change(function () {
	if ($('#radio').val() == '0') {
		$.get('qso/band_to_freq/' + $('#band').val() + '/' + $('.mode').val(), function (result) {
			$('#frequency').val(result);
			$('#frequency_rx').val("");
		});
	}
});

function setSerial(data) {
	var serialsent = 1;
	if (data.serialsent != "") {
		serialsent = parseInt(data.serialsent);
	}
	$("#exch_serial_s").val(serialsent);
}

function setExchangetype(exchangetype) {
	// Perhaps a better approach is to hide everything, then just enable the things you need
	$('#sent_exchange').hide().removeClass();
	$('#rcvd_exchange').hide().removeClass();
	$(".exchanger").hide();
	$(".exchanges").hide();
	$(".serials").hide();
	$(".serialr").hide();
	$(".gridsquarer").hide();
	$(".gridsquares").hide();

    // To track the transition, the code for the exchangecopy is kept
    // separate.
	switch(exchangetype) {
      case 'None':
		$('#sent_exchange').hide().removeClass();
		$('#rcvd_exchange').hide().removeClass();
		break;

      case 'Serial':
		$('#sent_exchange').show().addClass('d-flex gap-2 col-md-2');
		$('#rcvd_exchange').show().addClass('d-flex gap-2 col-md-2');
		$(".serials").show();
		$(".serialr").show();
		break;

      case 'Serialgridsquare':
		$('#sent_exchange').show().addClass('d-flex gap-2 col-md-3');
		$('#rcvd_exchange').show().addClass('d-flex gap-2 col-md-3');
		$(".serials").show();
		$(".serialr").show();
		$(".gridsquarer").show();
		$(".gridsquares").show();
		break;

      case 'Gridsquare':
		$('#sent_exchange').show().addClass('d-flex gap-2 col-md-2');
		$('#rcvd_exchange').show().addClass('d-flex gap-2 col-md-2');
		$(".gridsquarer").show();
		$(".gridsquares").show();
        if ($("#copyexchangeto").prop('disabled') == false) {
          $("#copyexchangeto").prop('disabled','disabled');
          $("#copyexchangeto").data('oldValue',$("#copyexchangeto").val());
          $("#copyexchangeto").val('None');
        } else {
          // Do nothing
        }
        break;

      case 'Exchange':
		$('#sent_exchange').show().addClass('d-flex gap-2 col-md-2');
		$('#rcvd_exchange').show().addClass('d-flex gap-2 col-md-2');
		$(".exchanger").show();
		$(".exchanges").show();
		break;

      case 'Serialexchange':
		$('#sent_exchange').show().addClass('d-flex gap-2 col-md-3');
		$('#rcvd_exchange').show().addClass('d-flex gap-2 col-md-3');
		$(".exchanger").show();
		$(".exchanges").show();
		$(".serials").show();
		$(".serialr").show();
        if ($("#copyexchangeto").prop('disabled') == false) {
          // Do nothing
        } else {
          $("#copyexchangeto").val($("#copyexchangeto").data('oldValue') ?? 'None');
          $("#copyexchangeto").prop('disabled',false);
        }
        break;

	  case 'SerialGridExchange':
		$('#sent_exchange').show().addClass('d-flex gap-2 col-md-4');
		$('#rcvd_exchange').show().addClass('d-flex gap-2 col-md-4');
		$(".serials").show();
		$(".serialr").show();
		$(".gridsquarer").show();
		$(".gridsquares").show();
		$(".exchanger").show();
		$(".exchanges").show();
		if ($("#copyexchangeto").prop('disabled') == false) {
			// Do nothing
		} else {
			$("#copyexchangeto").val($("#copyexchangeto").data('oldValue') ?? 'None');
			$("#copyexchangeto").prop('disabled',false);
		}
		break;

      default:
    }
}

/*
	Function: logQso
	Job: this handles the logging done in the contesting module.
 */
function logQso() {
	if ($("#callsign").val().length > 0) {

		// To prevent changing the contest name while logging we disable the select
		// Only "Start a new contest session" will enable it again
		disabledContestnameSelect(true);

		$('.callsign-suggestions').text("");
		$('#callsign_info').text("");

		var table = $('.qsotable').DataTable();
		var exchangetype = $("#exchangetype").val();

		var gridsquare = $("#exch_gridsquare_r").val();
		var vucc = '';

		if (gridsquare.indexOf(',') != -1) {
			vucc = gridsquare;
			gridsquare = '';
		}

		var gridr = '';
		var vuccr = '';
		var exchsent = '';
		var exchrcvd = '';
		var serials = '';
		var serialr = '';

		switch (exchangetype) {
			case 'Exchange':
				exchsent = $("#exch_sent").val();
				exchrcvd = $("#exch_rcvd").val();
			break;

			case 'Gridsquare':
				gridr = gridsquare;
				vuccr = vucc;
			break;

			case 'Serial':
				serials = $("#exch_serial_s").val();
				serialr = $("#exch_serial_r").val();
			break;

			case 'Serialexchange':
				exchsent = $("#exch_sent").val();
				exchrcvd = $("#exch_rcvd").val();
				serials = $("#exch_serial_s").val();
				serialr = $("#exch_serial_r").val();
			break;

			case 'Serialgridsquare':
				gridr = gridsquare;
				vuccr = vucc;
				serials = $("#exch_serial_s").val();
				serialr = $("#exch_serial_r").val();
			break;

			case 'Serialgridsquare':
				gridr = gridsquare;
				vuccr = vucc;
				exchsent = $("#exch_sent").val();
				exchrcvd = $("#exch_rcvd").val();
				serials = $("#exch_serial_s").val();
				serialr = $("#exch_serial_r").val();
			break;
		}

		var formdata = new FormData(document.getElementById("qso_input"));
		$.ajax({
			url: base_url + 'index.php/qso/saveqso',
			type: 'post',
			data: formdata,
			processData: false,
			contentType: false,
			enctype: 'multipart/form-data',
			success: async function (html) {
				var exchangetype = $("#exchangetype").val();
				if (exchangetype == "Serial" || exchangetype == 'Serialexchange' || exchangetype == 'Serialgridsquare' || exchangetype == 'SerialGridExchange') {
					$("#exch_serial_s").val(+$("#exch_serial_s").val() + 1);
					formdata.set('exch_serial_s', $("#exch_serial_s").val());
				}

				$('#name').val("");
				$('#bearing_info').html("");
				$('#callsign').val("");
				$('#comment').val("");
				$('#exch_rcvd').val("");
				$('#exch_gridsquare_r').val("");
				$('#exch_serial_r').val("");
				if (manual) {
					$("#start_time").focus().select();
				} else {
					$("#callsign").focus();
				}
				await setSession(formdata);

				await refresh_qso_table(sessiondata);
				var qTable = $('.qsotable').DataTable();
				qTable.search('').order([0, 'desc']).draw();

			}
		});
	}
}

async function getSession() {
	return await $.ajax({
		url: base_url + 'index.php/contesting/getSession',
		type: 'post',
	});
}

async function restoreContestSession(data) {
	if (data) {
		let settings = JSON.parse(data.settings);

		if (settings.copyexchangeto != "") {
			$('#copyexchangeto option')[settings.copyexchangeto].selected = true;
		}

		if (data.contestid != "") {
			$("#contestname_select").val(data.contestid);
		}

		if (settings.exchangetype != "") {
			$("#exchangetype").val(settings.exchangetype);
			setExchangetype(settings.exchangetype);
			setSerial(data);
		}

		if (settings.exchangesequence != "") {
			$("#exchangesequence_select").val(settings.exchangesequence);
			sort_exchange();
		}

		if (data.exchangesent != "") {
			$("#exch_sent").val(data.exchangesent);
		}

		if (settings.radio != "0") {
			$("#radio").val(settings.radio);
		} else {
			$("#radio").val(settings.radio);
			$("#band").val(settings.band);
			$("#mode").val(settings.mode);
			if (settings.freq_display != "") {
				$("#frequency").val(settings.freq_display);
			} else {
				$.get('qso/band_to_freq/' + settings.band + '/' + settings.mode, function (result) {
					$('#frequency').val(result);
				});
			}
		}

		if (data.qso != "") {
			disabledContestnameSelect(true);
			await refresh_qso_table(data);
		} else {
			disabledContestnameSelect(false);
			$("#contestname_select").val("Other").change();
		}
	} else {
		$("#exch_serial_s").val("1");
	}
}

async function refresh_qso_table(data) {
	if (data !== null) {
		$.ajax({
			url: base_url + 'index.php/contesting/getSessionQsos',
			type: 'post',
			data: { 'qso': data.qso, },
			success: function (html) {
				if (!$.fn.DataTable.isDataTable('.qsotable')) {
					$.fn.dataTable.moment('DD-MM-YYYY HH:mm:ss');
					$('.qsotable').DataTable({
						"stateSave": true,
						"pageLength": 25,
						responsive: false,
						"scrollY": "400px",
						"scrollCollapse": true,
						"paging": false,
						"scrollX": true,
						"language": {
							url: getDataTablesLanguageUrl(),
						},
						order: [0, 'desc'],
						"columnDefs": [
							{
								"render": function ( data, type, row ) {
									return row[8] !== null && row[8] !== '' ? pad(row[8], 3) : '';
								},
								"targets" : 8
							},
							{
								"render": function ( data, type, row ) {
									return row[9] !== null && row[9] !== '' ? pad(row[9], 3) : '';
								},
								"targets" : 9
							}
						]
					});
				}
				var table = $('.qsotable').DataTable();
				table.clear();

				var mode = '';
				var data = [];
                $.each(html, function () {
                    if (this.col_submode == null || this.col_submode == '') {
                        mode = this.col_mode;
                    } else {
                        mode = this.col_submode;
                    }

                    data.push([
                        this.col_time_on,
                        this.col_call,
                        this.col_band,
                        mode,
                        this.col_rst_sent,
                        this.col_rst_rcvd,
                        this.col_stx_string,
                        this.col_srx_string,
                        this.col_stx,
                        this.col_srx,
                        this.col_gridsquare,
                        this.col_vucc_grids
                    ]);
                });

                if (data.length > 0) {
                    table.rows.add(data).draw();
                }

			}
		});
	}
}

function pad (str, max) {
	str = str.toString();
	return str.length < max ? pad("0" + str, max) : str;
}

function getUTCTimeStamp(el) {
	var now = new Date();
	var localTime = now.getTime();
	var utc = localTime + (now.getTimezoneOffset() * 60000);
	$(el).attr('value', ("0" + now.getUTCHours()).slice(-2)+':'+("0" + now.getUTCMinutes()).slice(-2)+':'+("0" + now.getUTCSeconds()).slice(-2));
}

function getUTCDateStamp(el) {
	var now = new Date();
	var localTime = now.getTime();
	var utc = localTime + (now.getTimezoneOffset() * 60000);
	$(el).attr('value', ("0" + now.getUTCDate()).slice(-2)+'-'+("0" + (now.getUTCMonth()+1)).slice(-2)+'-'+now.getUTCFullYear());
}
