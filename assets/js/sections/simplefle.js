var textarea = $("#sfle_textarea");
var qsodate = "";
var qsotime = "";
var band = "";
var mode = "";
var freq = "";
var callsign = "";
var gridsquare = "";
var errors = [];
var qsoList = [];
var modes_regex = modes_regex(Modes);

$(document).ready(function () {
	setInterval(updateUTCTime, 1000);
	updateUTCTime();
	var tabledata = localStorage.getItem(`user_${user_id}_tabledata`);
	var mycall = localStorage.getItem(`user_${user_id}_my-call`);
	var operator = localStorage.getItem(`user_${user_id}_operator`);
	var mysotawwff = localStorage.getItem(`user_${user_id}_my-sota-wwff`);
	var qsoarea = localStorage.getItem(`user_${user_id}_qso-area`);
	var qsodate = localStorage.getItem(`user_${user_id}_qsodate`);
	var myPower = localStorage.getItem(`user_${user_id}_my-power`);
	var myGrid = localStorage.getItem(`user_${user_id}_my-grid`);

	if (mycall != null) {
		$("#stationProfile").val(mycall);
	}

	if (operator != null) {
		$("#operator").val(operator);
	}

	if (mysotawwff != null) {
		$("#my-sota-wwff").val(mysotawwff);
	}

	if (qsoarea != null) {
		$(".qso-area").val(qsoarea);
	}

	if (qsodate != null) {
		$("#qsodate").val(qsodate);
	}

	if (myPower != null) {
		$("#my-power").val(myPower);
	}

	if (myGrid != null) {
		$("#my-grid").val(myGrid);
	}

	if (tabledata != null) {
		$("#qsoTable").html(tabledata);
		handleInput();
	}

	$(window).on('resize', resizeElements);
	$(document).ready(resizeElements);

});

$("#simpleFleInfoButton").click(function (event) {
	var awardInfoLines = [
		lang_qso_simplefle_info_ln2,
		lang_qso_simplefle_info_ln3,
		lang_qso_simplefle_info_ln4,
	];
	var simpleFleInfo = "";
	awardInfoLines.forEach(function (line) {
		simpleFleInfo += line + "<br><br>";
	});
	BootstrapDialog.alert({
		title: "<h4>" + lang_qso_simplefle_info_ln1 + "</h4>",
		message: simpleFleInfo,
	});
});

$("#js-syntax").click(function (event) {
	$("#js-syntax").prop("disabled", false);
	$.ajax({
		url: base_url + "index.php/simplefle/displaySyntax",
		type: "post",
		success: function (html) {
			BootstrapDialog.show({
				title: "<h4>" + lang_qso_simplefle_syntax_help_title + "</h4>",
				type: BootstrapDialog.TYPE_INFO,
				size: BootstrapDialog.SIZE_WIDE,
				nl2br: false,
				message: html,
				buttons: [
					{
						label: lang_qso_simplefle_syntax_help_close_w_sample,
						action: function () {
							BootstrapDialog.confirm({
								title: lang_general_word_warning,
								message: lang_qso_simplefle_warning_reset,
								type: BootstrapDialog.TYPE_DANGER,
								btnCancelLabel: lang_general_word_cancel,
								btnOKLabel: lang_general_word_ok,
								btnOKClass: "btn-warning",
								callback: function (result) {
									if (result) {
										clearSession();

										const logData = `
*example-data*
date 2023-05-14
80m cw
1212 m0abc okff-1234
3 hb9hil
4 ok1tn
20 dl6kva 7 8
5 dl5cw
day ++
ssb
32 ok7wa ol/zl-071 5 8
33 ok1xxx  4 3
									`;

										textarea.val(logData.trim());
										handleInput();
										BootstrapDialog.closeAll();
									}
								},
							});
						},
					},
					{
						label: lang_admin_close,
						cssClass: "btn-primary",
						action: function (dialogItself) {
							dialogItself.close();
						},
					},
				],
			});
		},
	});
});

$("#js-options").click(function (event) {
	$("#js-options").prop("disabled", false);
	$.ajax({
		url: base_url + "index.php/simplefle/displayOptions",
		type: "post",
		success: function (html) {
			BootstrapDialog.show({
				title: "<h4>" + lang_qso_simplefle_options + "</h4>",
				nl2br: false,
				message: html,
				buttons: [
					{
						label: lang_admin_save,
						cssClass: 'btn-primary btn-sm',
						id: 'saveButton',
						action: function (dialogItself) {
							$('#optionButton').prop("disabled", false);
							$('#closeButton').prop("disabled", true);
							saveOptions();
							dialogItself.close();
							location.reload();
						}
					},
					{
						label: lang_admin_close,
						cssClass: 'btn-sm',
						id: 'closeButton',
						action: function (dialogItself) {
							$('#optionButton').prop("disabled", false);
							dialogItself.close();
						}
					},
				],
			});
		},
	});
});

function saveOptions() {
	$('#saveButton').prop("disabled", true);
	$('#closeButton').prop("disabled", true);
	$.ajax({
		url: base_url + 'index.php/simplefle/saveOptions',
		type: 'post',
		data: {
			callbook_lookup: $('input[name="callbook_lookup"]').is(':checked') ? true : false,
		},
		success: function(data) {
			$('#saveButton').prop("disabled", false);
			$('#closeButton').prop("disabled", false);
		},
		error: function() {
			$('#saveButton').prop("disabled", false);
		},
	});
}

function updateUTCTime() {
	const utcTimeElement = document.getElementById("utc-time");
	const now = new Date();
	const utcTimeString = now.toISOString().split("T")[1].split(".")[0];
	utcTimeElement.textContent = utcTimeString;
}

function handleInput() {
	var qsodate = "";
	if ($("#qsodate").val()) {
		qsodate = new Date($("#qsodate").val()).toISOString().split("T")[0];
	} else {
		qsodate = new Date().toISOString().split("T")[0];
	}

	var operator = $("#operator").val();
	operator = operator.toUpperCase();
	var ownCallsign = $("#stationProfile").val().toUpperCase();
	ownCallsign = ownCallsign.toUpperCase();

	var extraQsoDate = qsodate;
	var band = "";
	var prevMode = "";
	var mode = "";
	var freq = "";
	var callsign = "";
	var gridsquare = "";
	var sotaWwff = "";
	var srx = "";
	var stx = "";
	var stx_incr_mode = 0;
	var prev_stx = "";
	var prev_stx_string = "";
	qsoList = [];
	$("#qsoTable tbody").empty();
	errors = [];
	checkMainFieldsErrors();

	var text = textarea.val().trim();
	lines = text.split("\n");
	lines.forEach((row) => {
		var rst_s = null;
		var rst_r = null;
		var gridsquare = "";
		var srx = "";
		var stx = "";
		var call_rec = false;
		var add_info = {};

		// First, search for <...>- and [...]-Patterns, which may contain comments (... or additional fields) / qsl-notes
		let addInfoMatches = row.matchAll(/<([^>]*)>|\[([^\]]*)\]/g);
		for (const item of addInfoMatches) {
			row = row.replace(item[0], "");
			let kv;
			if (item[0][0] == '<' && (kv = item[1].match(/^([a-z_]+): *(.*)$/))) {
				add_info[kv[1]] = kv[2];
			} else if (item[0][0] == '[') {
				add_info.qslmsg = item[2];
			} else {
				add_info.comment = (('comment' in add_info)?add_info.comment+' ': '')+item[1];
			}
		}

		// Now split the remaining line by spaces and match patterns on those
		var itemNumber = 0;
		items = row.startsWith("day ") ? [row] : row.split(" ");
		items.forEach((item) => {
			var parts;
			if (item === "") {
				return;
			}
			if (item.trim().match(/^day (\+)+$/)) {
				var plusCount = item.match(/\+/g).length;
				var originalDate = new Date(extraQsoDate);
				originalDate.setDate(originalDate.getDate() + plusCount);
				extraQsoDate = originalDate.toISOString().split("T")[0];
			} else if (
				item.match(/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])$/)
			) {
				extraQsoDate = item;
			} else if (item.match(/^[0-2][0-9][0-5][0-9]$/)) {
				qsotime = item;
			} else if (
				item.match(modes_regex)
			) {
				if (mode != "") {
					freq = 0;
				}
				mode = item.toUpperCase();
			} else if (
				item.match(/^[0-9]{1,4}(?:m|cm|mm)$/) ||
				item.match(/^(sat)$/)
			) {
				band = item;
				freq = 0;
			} else if (item.match(/^\d+\.\d+$/)) {
				freq = item;
				band = getBandFromFreq(freq);
			} else if (
				item.match(/^[1-9]{1}$/) &&
				qsotime &&
				itemNumber === 0
			) {
				qsotime = qsotime.replace(/.$/, item);
			} else if (
				item.match(/^[0-5][0-9]{1}$/) &&
				qsotime &&
				itemNumber === 0
			) {
				qsotime = qsotime.slice(0, -2) + item;
			} else if (
				item.match(
					//          SOTA               |         IOTA          |                           POTA                                   |           WWFF            //
					/^[A-Z0-9]{1,3}\/[A-Z]{2}-\d{3}|[AENOS]*[FNSUACA]-\d{3}|(?!.*FF)[A-Z0-9]{1,3}-\d{4,5}(?:,((?!.*FF)[A-Z0-9]{1,3}-\d{4,5}))*|[A-Z0-9]{1,3}[F]{2}-\d{4}$/i
				)
			) {
				sotaWwff = item.toUpperCase();
			} else if (
				item.match(
					/([a-zA-Z0-9]{1,3}[0-9][a-zA-Z0-9]{0,3}[a-zA-Z])|.*\/([a-zA-Z0-9]{1,3}[0-9][a-zA-Z0-9]{0,3}[a-zA-Z])|([a-zA-Z0-9]{1,3}[0-9][a-zA-Z0-9]{0,3}[a-zA-Z])\/.*/
				) && call_rec !== true
			) {
				callsign = item.toUpperCase();
				call_rec = true;
			} else if (
				parts = item.match(/(?<=^#?)[A-R]{2}[0-9]{2}([A-X]{2}([0-9]{2}([A-X]{2})?)?)?$/i)
			) {
				gridsquare = parts[0].toUpperCase();
			} else if (itemNumber > 0 && item.match(/^[-+]\d{1,2}$|^\d{1,3}$|^\d{1,3}[-+]d{1,2}$/)) {
				if (rst_s === null) {
					rst_s = item;
				} else {
					rst_r = item;
				}
			} else if (itemNumber > 0 && (parts = item.match(/^(([\.,])((\d*|\+[\+0]|-)|([A-Za-z0-9\/]+)))+$/))) { // Contest ,*** .***
				// Caution! May be entered multiple times, and may contain empty tokens
				// Iterate over all parts -- take care to behave exactly like entered with spaces
				item.matchAll(/([\.,])((\d*|\+[\+0]|-)|([A-Za-z0-9\/]+))(?=$|[\.,])/g).forEach((exch) => {
					var pre_stx = stx;
					var pre_srx = srx;

					switch (exch[1]+((exch[3]===undefined)?'s':'n')) { // [.,][sn]
						case ".n": // Received serial
							srx = exch[2];
							break;
						case ",n": // Sent serial
							stx = exch[2];
							break;
						case ".s": // Received exchange
							add_info.srx_string = exch[2];
							break;
						case ",s": // Sent exchange
							add_info.stx_string = exch[2];
					}

					// Mode swith
					if (stx == "++") {
						stx = pre_stx;
						stx_incr_mode = 1;
						return; // no further processing of this (sub-)token here jumps to next pattern, if available
					} else if (stx == "+0") {
						stx = pre_stx;
						stx_incr_mode = 0;
						return; // no further processing of this (sub-)token here jumps to next pattern, if available
					}

					if (stx == '-') { // Wipe all sent exchange if '-'
						stx = '';
						prev_stx = '';
						delete add_info.stx_string;
						prev_stx_string = '';
					}

					// FLE paradima: Previous if not set - we have some sort of contest exchange, so 
					// re-apply previously set stx / stx_string if not already populated
					if (prev_stx != '' && stx == '') {
						stx = (prev_stx*1) + stx_incr_mode;
					}

					if (prev_stx_string != '' && add_info.stx_string === undefined) {
						add_info.stx_string = prev_stx_string;
					}

					// Sanity check
					if (srx == "++" || srx == "+0" || srx == '-') srx = pre_srx;
				});


			} else if (itemNumber > 0 && (parts = item.match(/(?<=^@)[A-Za-z]+/))) {
				add_info.name = parts[0];
			}

			itemNumber = itemNumber + 1;
		});

		if (callsign) {
			if (freq === 0) {
				freq = getFreqFromBand(band, mode);
			} else if (band === "") {
				band = getBandFromFreq(freq);
			}

			if (band === "") {
				addErrorMessage(lang_qso_simplefle_error_band);
			}
			if (mode === "") {
				addErrorMessage(lang_qso_simplefle_error_mode);
			}
			if (qsotime === "") {
				addErrorMessage(lang_qso_simplefle_error_time);
			}

			if (isValidDate(extraQsoDate) === false) {
				addErrorMessage(
					lang_qso_simplefle_error_date + " " + extraQsoDate
				);
				extraQsoDate = qsodate;
			}

			rst_s = getReportByMode(rst_s, mode);
			rst_r = getReportByMode(rst_r, mode);

			qsoList.push([
				extraQsoDate,
				qsotime,
				callsign,
				freq,
				band,
				mode,
				gridsquare, // 6
				rst_s,
				rst_r,
				sotaWwff,
				stx,
				srx,
				add_info, // 12
			]);

			let sotaWwffText = "";

			if (isSOTA(sotaWwff)) {
				sotaWwffText = `S: ${sotaWwff}`;
			} else if (isPOTA(sotaWwff)) {
				sotaWwffText = `P: ${sotaWwff}`;
			} else if (isIOTA(sotaWwff)) {
				sotaWwffText = `I: ${sotaWwff}`;
			} else if (isWWFF(sotaWwff)) {
				sotaWwffText = `W: ${sotaWwff}`;
			}

			// Contest exchange info: sent
			let stx_info = "";
			if (stx != '') {
				stx_info += `<span data-bs-toggle="tooltip" class="badge text-bg-light">${stx}</span>`;
			}
			if (add_info.stx_string !== undefined && add_info.stx_string.length > 0) {
				stx_info += `<span data-bs-toggle="tooltip" class="badge text-bg-light">${add_info.stx_string}</span>`;
			}

			// Contest exchange info: received
			let srx_info = "";
			if (srx != '') {
				srx_info += `<span data-bs-toggle="tooltip" title="" class="badge text-bg-light">${srx}</span>`;
			}
			if (add_info.srx_string !== undefined && add_info.srx_string.length > 0) {
				srx_info += `<span data-bs-toggle="tooltip" title="" class="badge text-bg-light">${add_info.srx_string}</span>`;
			}

			const tableRow = $(`<tr>
			<td>${extraQsoDate}</td>
			<td>${qsotime}</td>
			<td>${callsign}</td>
			<td><span data-bs-toggle="tooltip" data-placement="left" title="${freq}">${band}</span></td>
			<td>${mode}</td>
			<td>${rst_s} ${stx_info}</td>
			<td>${rst_r} ${srx_info}</td>
			<td>${gridsquare}</td>
			<td>${sotaWwffText}</td>
			</tr>`);

			$("#qsoTable > tbody:last-child").append(tableRow);

			localStorage.setItem(
				`user_${user_id}_tabledata`,
				$("#qsoTable").html()
			);
			localStorage.setItem(
				`user_${user_id}_my-call`,
				$("#stationProfile").val()
			);
			localStorage.setItem(
				`user_${user_id}_operator`,
				$("#operator").val()
			);
			localStorage.setItem(
				`user_${user_id}_my-sota-wwff`,
				$("#my-sota-wwff").val()
			);
			localStorage.setItem(
				`user_${user_id}_qso-area`,
				$(".qso-area").val()
			);
			localStorage.setItem(
				`user_${user_id}_qsodate`,
				$("#qsodate").val()
			);
			localStorage.setItem(
				`user_${user_id}_my-power`,
				$("#my-power").val()
			);
			localStorage.setItem(
				`user_${user_id}_my-grid`,
				$("#my-grid").val()
			);

			callsign = "";
			sotaWwff = "";
		}

		prevMode = mode;
		prev_stx = stx;
		prev_stx_string = add_info.stx_string ?? '';
	});

	// Scroll to the bototm of #qsoTableBody (scroll by the value of its scrollheight property)
	$("#qsoTableBody").scrollTop($("#qsoTableBody").get(0).scrollHeight);

	var qsoCount = qsoList.length;
	if (qsoCount) {
		$(".js-qso-count").html(
			"<strong>" +
				lang_qso_simplefle_qso_list_total +
				":</strong> " +
				qsoCount +
				" " +
				lang_gen_hamradio_qso
		);
	} else {
		$(".js-qso-count").html("");
	}

	showErrors();
}

function checkMainFieldsErrors() {
	if ($("#stationProfile").val() === "-") {
		$("#warningStationCall").show();
		$("#stationProfile").css("border", "2px solid rgb(217, 83, 79)");
		$("#warningStationCall").text(lang_qso_simplefle_error_stationcall);
	} else {
		$("#stationProfile").css("border", "");
		$("#warningStationCall").hide();
	}

	if ($("#operator").val() === "") {
		$("#warningOperatorField").show();
		$("#operator").css("border", "2px solid rgb(217, 83, 79)");
		$("#warningOperatorField").text(lang_qso_simplefle_error_operator);
	} else {
		$("#operator").css("border", "");
		$("#warningOperatorField").hide();
	}
	if (textarea.val() === "") {
		textarea.css("border", "2px solid rgb(217, 83, 79)");
		setTimeout(function () {
			textarea.css("border", "");
		}, 2000);
	} else {
		textarea.css("border", "");
	}
}

textarea.keydown(function (event) {
	if (event.which == 13) {
		handleInput();
	}
});

textarea.focus(function () {
	errors = [];
	checkMainFieldsErrors();
	showErrors();
});

function addErrorMessage(errorMessage) {
	errorMessage = '<div class="alert alert-danger">' + errorMessage + "</div>";
	if (errors.includes(errorMessage) == false) {
		errors.push(errorMessage);
	}
}

function isValidDate(d) {
	return new Date(d) !== "Invalid Date" && !isNaN(new Date(d));
}

$(".js-reload-qso").click(function () {
	handleInput();
});

$(".js-empty-qso").click(function () {
	BootstrapDialog.confirm({
		title: lang_general_word_warning,
		message: lang_qso_simplefle_warning_reset,
		type: BootstrapDialog.TYPE_DANGER,
		btnCancelLabel: lang_general_word_cancel,
		btnOKLabel: lang_general_word_ok,
		btnOKClass: "btn-warning",
		callback: function (result) {
			if (result) {
				clearSession();
			}
		},
	});
});

function clearSession() {
	localStorage.removeItem(`user_${user_id}_tabledata`);
	localStorage.removeItem(`user_${user_id}_my-call`);
	localStorage.removeItem(`user_${user_id}_operator`);
	localStorage.removeItem(`user_${user_id}_my-sota-wwff`);
	localStorage.removeItem(`user_${user_id}_qso-area`);
	localStorage.removeItem(`user_${user_id}_qsodate`);
	localStorage.removeItem(`user_${user_id}_my-grid`);
	$("#qsodate").val("");
	$("#qsoTable tbody").empty();
	$("#my-sota-wwff").val("");
	$(".qso-area").val("");
	$("#my-grid").val("");
	$("#contest").val("");
	qsoList = [];
	$(".js-qso-count").html("");
	errors = [];
	$(".js-status").html("");
	window.location.reload();
}

function showErrors() {
	if (errors) {
		$(".js-status").html(errors.join("\n"));
		resizeElements();
	}
}

$(".js-download-qso").click(function () {
	handleInput();
});

function getBandFromFreq(freq) {
    if (freq >= 0.13 && freq <= 0.14) {
        return "2190m";
    } else if (freq >= 0.4 && freq <= 0.49) {
        return "630m";
    } else if (freq >= 0.5 && freq <= 0.51) {
        return "560m";
    } else if (freq >= 1.6 && freq <= 2.2) {
        return "160m";
    } else if (freq >= 3.4 && freq <= 4.0) {
        return "80m";
    } else if (freq >= 5.0 && freq <= 5.5) {
        return "60m";
    } else if (freq >= 7.0 && freq <= 7.3) {
        return "40m";
    } else if (freq >= 10.0 && freq <= 10.2) {
        return "30m";
    } else if (freq >= 14.0 && freq <= 14.4) {
        return "20m";
    } else if (freq >= 18.0 && freq <= 18.2) {
        return "17m";
    } else if (freq >= 21.0 && freq <= 21.5) {
        return "15m";
    } else if (freq >= 24.8 && freq <= 25.0) {
        return "12m";
    } else if (freq >= 28.0 && freq <= 30.0) {
        return "10m";
    } else if (freq >= 50 && freq <= 54) {
        return "6m";
    } else if (freq >= 69 && freq <= 72) {
        return "4m";
    } else if (freq >= 144 && freq <= 148) {
        return "2m";
    } else if (freq >= 222 && freq <= 225) {
        return "1.25m";
    } else if (freq >= 420 && freq <= 450) {
        return "70cm";
    } else if (freq >= 902 && freq <= 928) {
        return "33cm";
    } else if (freq >= 1240 && freq <= 1300) {
        return "23cm";
    } else if (freq >= 2300 && freq <= 2450) {
        return "13cm";
    } else if (freq >= 3300 && freq <= 3500) {
        return "9cm";
    } else if (freq >= 5650 && freq <= 5925) {
        return "6cm";
    } else if (freq >= 10000 && freq <= 10500) {
        return "3cm";
    } else if (freq >= 24000 && freq <= 24250) {
        return "1.25cm";
    } else if (freq >= 47000 && freq <= 47200) {
        return "6mm";
    } else if (freq >= 75500 && freq <= 81000) {
        return "4mm";
    } else if (freq >= 119980 && freq <= 123000) {
        return "2.5mm";
    } else if (freq >= 134000 && freq <= 149000) {
        return "2mm";
    } else if (freq >= 241000 && freq <= 250000) {
        return "1mm";
    } else if (freq >= 300000 && freq <= 7500000) {
        return "submm";
    } else {
        return "";
    }
}

function getFreqFromBand(band, mode) {
	var settingsMode = getSettingsMode(mode.toUpperCase());
	var settingsBand = "b" + band.toUpperCase();
	var bandData = Bands[settingsBand];

	if (bandData) {
		return bandData[settingsMode] / 1000000;
	}
}

function getSettingsMode(mode, modesArray = Modes) {
	var settingsMode = 'DATA';

    for (var i = 0; i < modesArray.length; i++) {
        if (modesArray[i]['submode'] === mode) {
            settingsMode = modesArray[i]['qrgmode'];
        }else if (modesArray[i]['mode'] === mode) {
            settingsMode = modesArray[i]['qrgmode'];
        }
    }

	return settingsMode; 
}

function modes_regex(modesArray) {
    var regexPattern = '^';
    
    for (var i = 0; i < modesArray.length; i++) {

		var modeValue = modesArray[i]['mode'] + '$|^';
		var submodeValue = '';

		if (modesArray[i]['submode'] !== null) {
			submodeValue = modesArray[i]['submode'] + '$|^';
		}

		regexPattern += modeValue + submodeValue;
    }

	regexPattern = regexPattern.slice(0, -2);

    return new RegExp(regexPattern, 'i');
}

var htmlSettings = "";
for (const [key, value] of Object.entries(Bands)) {
	htmlSettings = `
      ${htmlSettings}
      <div class="row">
        <div class="col-3 mt-4">
          <strong>${key.slice(1)}</strong>
        </div>
        <div class="col-3">
          <div class="form-group">
            <label for="${key.slice(1)}CW">CW</label>
            <input type="text" class="form-control text-uppercase" id="${key.slice(
				1
			)}CW" value="${value.cw}">
          </div>
        </div>
        <div class="col-3">
          <div class="form-group">
            <label for="${key.slice(1)}SSB">SSB</label>
            <input type="text" class="form-control text-uppercase" id="${key.slice(
				1
			)}SSB" value="${value.ssb}">
          </div>
        </div>
        <div class="col-3">
          <div class="form-group">
            <label for="${key.slice(1)}DIGI">DIGI</label>
            <input type="text" class="form-control text-uppercase" id="${key.slice(
				1
			)}DIGI" value="${value.digi}">
          </div>
        </div>

      </div>
    `;
}
$(".js-band-settings").html(htmlSettings);

function isBandModeEntered() {
	let isBandModeOK = true;
	qsoList.forEach((item) => {
		if (item[4] === "" || item[5] === "") {
			isBandModeOK = false;
		}
	});

	return isBandModeOK;
}

function isTimeEntered() {
	let isTimeOK = true;
	qsoList.forEach((item) => {
		if (item[1] === "") {
			isTimeOK = false;
		}
	});

	return isTimeOK;
}

function isExampleDataEntered() {
    let isExampleData = false;
    if (textarea.val().startsWith("*example-data*")) {
        isExampleData = true;
    }
    return isExampleData;
}

function isAllContestDataWithContestId() {
		// true = allfine, false = something wrong
		let hasContestId = $("#contest").val() != '';
		let hasContestData = false;
		qsoList.forEach((item) => {
			hasContestData = hasContestData || (item[10] != '' || item[12].stx_string !== undefined || item[11] != '' || item[12].stx_string !== undefined);
		});
		return hasContestData == hasContestId;
}

function getAdifTag(tagName, value) {
	return "<" + tagName + ":" + value.length + ">" + value + " ";
}

function getReportByMode(rst, mode) {
	settingsMode = getSettingsMode(mode);

	if (rst === null) {
		if (settingsMode === "SSB") {
			return "59";
		} else if (settingsMode === "DATA") {
			switch(mode) {
				// return +0 dB for Digimodes except for Digitalvoice Modes
				case "DIGITALVOICE": 	return "59";
				case "C4FM": 			return "59";
				case "DMR": 			return "59";
				case "DSTAR": 			return "59";
				case "FREEDV": 			return "59";
				case "M17": 			return "59";

				default: return "+0 dB";
			}
		}
	
		return "599";

	} else {

		if (settingsMode === "SSB") {
			if (rst.length === 1) {
				return "5" + rst;
			}
			if (rst.length === 3) {
				return rst.slice(0, 2);
			}

			return rst;

		} else if (rst.startsWith('+') || rst.startsWith('-')) {
			return rst + " dB";
		}
		
		if (rst.length === 1) {
			switch(mode) {
				case "CW": 				return "5" + rst + "9";
				case "DIGITALVOICE": 	return "5" + rst;
				case "C4FM": 			return "5" + rst;
				case "DMR": 			return "5" + rst;
				case "DSTAR": 			return "5" + rst;
				case "FREEDV": 			return "5" + rst;
				case "M17": 			return "5" + rst;

				default: 				return "+" + rst + " dB";
			};
		} else if (rst.length === 2) {
			switch(mode) {
				case "CW": 				return rst + "9";
				case "DIGITALVOICE": 	return rst;
				case "C4FM": 			return rst;
				case "DMR": 			return rst;
				case "DSTAR": 			return rst;
				case "FREEDV": 			return rst;
				case "M17": 			return rst;

				default: 				return "+" + rst + " dB";
			};
		} 
	}

	return rst;
}

function isSOTA(value) {
	if (value.match(/^[A-Z0-9]{1,3}\/[A-Z]{2}-\d{3}$/)) {
		return true;
	}

	return false;
}

function isIOTA(value) {
	if (value.match(/^[AENOS]*[FNSUACA]-\d{3}$/)) {
		return true;
	}
}

function isPOTA(value) {
	if (value.match(/^(?!.*FF)[A-Z0-9]{1,3}-\d{4,5}(?:,((?!.*FF)[A-Z0-9]{1,3}-\d{4,5}))*$/)) {
		return true;
	}
}

function isWWFF(value) {
	if (value.match(/^[A-Z0-9]{1,3}[F]{2}-\d{4}$/)) {
		return true;
	}

	return false;
}

function resizeElements() {
	var textarea = $('#sfle_textarea');
	var textareaOffset = 40;

	var errorMessagesContainer = $('#errorMessages');

	var tableFrame = $('.sfletable.table');
	var tableFrameOffset = 140;

	var table = $('#qsoTableBody');
	var tableoOffset = 160;

	if ($(window).width() >= 768) {
		var newHeight = $(window).height() - textarea.offset().top - textareaOffset - errorMessagesContainer.height();
		textarea.css('height', newHeight + 'px');

		var newHeight = $(window).height() - tableFrame.offset().top - tableFrameOffset;
		tableFrame.css('height', newHeight + 'px');

		var newHeight = $(window).height() - table.offset().top - tableoOffset;
		table.css('height', newHeight + 'px');

		$('.js-reload-qso').removeClass('btn-sm');
		$('.js-save-to-log').removeClass('btn-sm');
		$('.js-empty-qso').removeClass('btn-sm');
		$('#js-syntax').removeClass('btn-sm');
		$('#js-options').removeClass('btn-sm');

	} else {
		textarea.css('height', 'auto');
		tableFrame.css('height', '530px');
		table.css('height', '400px');

		$('.js-reload-qso').addClass('btn-sm');
		$('.js-save-to-log').addClass('btn-sm');
		$('.js-empty-qso').addClass('btn-sm');
		$('#js-syntax').addClass('btn-sm');
		$('#js-options').addClass('btn-sm');
	}
}

$(".js-save-to-log").click(function () {
	if (textarea.val() === "") {
		textarea.css("border", "2px solid rgb(217, 83, 79)");
		setTimeout(function () {
			textarea.css("border", "");
		}, 2000);
		return false;
	}
	if (false === isBandModeEntered()) {
		BootstrapDialog.alert({
			title: lang_general_word_warning,
			message: lang_qso_simplefle_warning_missing_band_mode,
			type: BootstrapDialog.TYPE_DANGER,
			btnCancelLabel: lang_general_word_cancel,
			btnOKLabel: lang_general_word_ok,
			btnOKClass: "btn-warning",
		});
		return false;
	}
	if (false === isTimeEntered()) {
		BootstrapDialog.alert({
			title: lang_general_word_warning,
			message: lang_qso_simplefle_warning_missing_time,
			type: BootstrapDialog.TYPE_DANGER,
			btnCancelLabel: lang_general_word_cancel,
			btnOKLabel: lang_general_word_ok,
			btnOKClass: "btn-warning",
		});
		return false;
	}
	if (false === isAllContestDataWithContestId()) {
		BootstrapDialog.alert({
			title: lang_general_word_warning,
			message: lang_qso_simplefle_warning_missing_contestid,
			type: BootstrapDialog.TYPE_DANGER,
			btnCancelLabel: lang_general_word_cancel,
			btnOKLabel: lang_general_word_ok,
			btnOKClass: "btn-warning",
		});
		return false;
	}
	if (true === isExampleDataEntered()) {
		BootstrapDialog.alert({
			title: lang_general_word_warning,
			message: lang_qso_simplefle_warning_example_data,
			type: BootstrapDialog.TYPE_DANGER,
			btnCancelLabel: lang_general_word_cancel,
			btnOKLabel: lang_general_word_ok,
			btnOKClass: "btn-warning",
		});
		return false;
	} else {
		handleInput();
		BootstrapDialog.confirm({
			title: lang_general_word_attention,
			message: lang_qso_simplefle_confirm_save_to_log,
			type: BootstrapDialog.TYPE_INFO,
			btnCancelLabel: lang_general_word_cancel,
			btnOKLabel: lang_general_word_ok,
			btnOKClass: "btn-info",
			callback: function (result) {
				if (result) {
					const wait_dialog = BootstrapDialog.show({
                        title: lang_general_word_please_wait,
                        message: '<div class="text-center"><i class="fas fa-spinner fa-spin fa-3x"></i></div>',
                        closable: false,
                        buttons: []
                    });
					qsos = [];

					qsoList.forEach((item) => {
						var callsign = item[2];
						var gridsquare = item[6];
						var rst_sent = item[7].replace(/dB$/, ''); // we don't want 'dB' in the database
						var rst_rcvd = item[8].replace(/dB$/, ''); // *
						var start_date = item[0];
						var start_time =
							item[1][0] +
							item[1][1] +
							":" +
							item[1][2] +
							item[1][3];
						var band = item[4];
						var mode = item[5];
						var freq_display = item[3];
						var station_profile = $(".station_id").val();
						var operator = $("#operator").val().toUpperCase();
						var contest = $("#contest").val();
						var sota_ref = "";
						var iota_ref = "";
						var pota_ref = "";
						var wwff_ref = "";
						if (isSOTA(item[9])) {
							sota_ref = item[9];
						} else if (isIOTA(item[9])) {
							iota_ref = item[9];
						} else if (isPOTA(item[9])) {
							pota_ref = item[9];
						} else if (isWWFF(item[9])) {
							wwff_ref = item[9];
						}
						var stx = item[10];
						var srx = item[11];
						var add_info = item[12];

						qsos.push({ ...add_info, ...{
							call: callsign,
							gridsquare: gridsquare,
							rst_sent: rst_sent,
							rst_rcvd: rst_rcvd,
							qso_date: start_date,
							time_on: start_time,
							band: band,
							mode: mode,
							freq: freq_display,
							station_id: station_profile,
							operator: operator,
							contest_id: contest,
							sota_ref: sota_ref,
							iota: iota_ref,
							pota_ref: pota_ref,
							wwff_ref: wwff_ref,
							stx: stx,
							srx: srx,
						}});
					});

					$.ajax({
						url: base_url + "index.php/simplefle/save_qsos",
						type: "post",
						data: { qsos: JSON.stringify(qsos) },
						success: function (result) {
							if (result == 'success' || result.includes(lang_duplicate_for)) {
								BootstrapDialog.alert({
									title: lang_qso_simplefle_success_save_to_log_header,
									message: lang_qso_simplefle_success_save_to_log,
									type: BootstrapDialog.TYPE_SUCCESS,
									btnOKLabel: lang_general_word_ok,
									btnOKClass: "btn-info",
									callback: function (result) {
										wait_dialog.close();
										clearSession();
									}
								});
							} else {
								BootstrapDialog.alert({
									title: lang_general_word_error,
									message: lang_qso_simplefle_error_save_to_log + "<br><br><code><pre>" + JSON.stringify(result) + "</pre></code>",
									size: BootstrapDialog.SIZE_WIDE,
									type: BootstrapDialog.TYPE_DANGER,
									callback: function (result) {
										wait_dialog.close();
									}
								});
								console.error(result);
							}
						},
						error: function (result) {
							BootstrapDialog.alert({
								title: lang_general_word_error,
								message: lang_qso_simplefle_error_save_to_log + "<br><br><code><pre>" + JSON.stringify(result) + "</pre></code>",
								size: BootstrapDialog.SIZE_WIDE,
								type: BootstrapDialog.TYPE_DANGER,
								callback: function (result) {
									wait_dialog.close();
								}
							});
							console.error(result);
						},
					});
				}
			},
		});
	}
});
