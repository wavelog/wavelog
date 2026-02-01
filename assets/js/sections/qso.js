var favs = {};
var selected_sat;
var selected_sat_mode;
var scps = [];
let lookupCall = null;
let preventLookup = false;
var submitTimeout = null; // Debounce timer for QSO submission

// Calculate local time based on GMT offset
function calculateLocalTime(gmtOffset) {
	let now = new Date();
	let utcTime = now.getTime() + (now.getTimezoneOffset() * 60000);
	let localTime = new Date(utcTime + (3600000 * gmtOffset));

	let hours = ("0" + localTime.getHours()).slice(-2);
	let minutes = ("0" + localTime.getMinutes()).slice(-2);

	return hours + ':' + minutes;
}

// Check and update profile info visibility based on available width
function checkProfileInfoVisibility() {
	if ($('#callsign-image').is(':visible') && $('#callsign-image-info').html().trim() !== '') {
		// Additional validation: check if profile data matches current callsign
		let currentCallsign = $('#callsign').val().toUpperCase().replaceAll('Ø', '0');
		let profileCallsign = $('#callsign-image').attr('data-profile-callsign') || '';

		if (currentCallsign !== profileCallsign) {
			// Callsign mismatch, hide the profile panel
			console.log('Profile callsign mismatch during visibility check - hiding panel');
			$('#callsign-image').attr('style', 'display: none;');
			$('#callsign-image-info').html("");
			$('#callsign-image-info').hide();
			return;
		}

		let cardBody = $('.card-body.callsign-image');
		let imageWidth = $('#callsign-image-content').outerWidth() || 0;
		let cardWidth = cardBody.width() || 0;
		let availableWidth = cardWidth - imageWidth - 24; // Subtract gap (24px for gap-3)

		if (availableWidth >= 200) {
			$('#callsign-image-info').show();
		} else {
			$('#callsign-image-info').hide();
		}
	}
}

// Attach resize listener for profile info visibility
$(window).on('resize', function() {
	checkProfileInfoVisibility();
});
// Create and add banner control
window.mapBanner = L.control({ position: "bottomleft" }); // You can change position: "topleft", "bottomleft", etc.

window.mapBanner.onAdd = function () {
	const div = L.DomUtil.create("div", "info legend");
	div.style.background = "rgba(0, 0, 0, 0.7)";
	div.style.color = "white";
	div.style.padding = "8px 12px";
	div.style.borderRadius = "8px";
	div.style.fontSize = "13px";
	div.style.boxShadow = "0 2px 6px rgba(0,0,0,0.3)";
	div.innerHTML = bannerText;
	return div;
};

// if the dxcc id changes we need to update the state dropdown and clear the county value to avoid wrong data
$("#dxcc_id").on('change', function () {
	updateStateDropdown('#dxcc_id', '#stateInputLabel', '#location_us_county', '#stationCntyInputQso');
	$('#stationCntyInputQso').val('');
	$('#dxcc_id').multiselect('refresh');
});

function resetTimers(qso_manual) {
	if (typeof qso_manual !== 'undefined' && qso_manual != 1) {
		handleStart = setInterval(function () { getUTCTimeStamp($('.input_start_time')); }, 500);
		handleEnd = setInterval(function () { getUTCTimeStamp($('.input_end_time')); }, 500);
		handleDate = setInterval(function () { getUTCDateStamp($('.input_date')); }, 1000);
	}
}

function getUTCTimeStamp(el) {
	var now = new Date();
	$(el).attr('value', ("0" + now.getUTCHours()).slice(-2) + ':' + ("0" + now.getUTCMinutes()).slice(-2) + ':' + ("0" + now.getUTCSeconds()).slice(-2));
}

function getUTCDateStamp(el) {
	var now = new Date();
	var day = ("0" + now.getUTCDate()).slice(-2);
	var month = ("0" + (now.getUTCMonth() + 1)).slice(-2);
	var year = now.getUTCFullYear();
	var short_year = year.toString().slice(-2);

	// Format the date based on user_date_format passed from PHP
	var formatted_date;
	switch (user_date_format) {
		case "d/m/y":
			formatted_date = day + "/" + month + "/" + short_year;
			break;
		case "d/m/Y":
			formatted_date = day + "/" + month + "/" + year;
			break;
		case "m/d/y":
			formatted_date = month + "/" + day + "/" + short_year;
			break;
		case "m/d/Y":
			formatted_date = month + "/" + day + "/" + year;
			break;
		case "d.m.Y":
			formatted_date = day + "." + month + "." + year;
			break;
		case "y/m/d":
			formatted_date = short_year + "/" + month + "/" + day;
			break;
		case "Y-m-d":
			formatted_date = year + "-" + month + "-" + day;
			break;
		case "M d, Y":
			// Need to get the month name abbreviation
			var monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
			formatted_date = monthNames[now.getUTCMonth()] + " " + parseInt(day) + ", " + year;
			break;
		case "M d, y":
			var monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
			formatted_date = monthNames[now.getUTCMonth()] + " " + parseInt(day) + ", " + short_year;
			break;
		case "d M y":
			var monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
			formatted_date = parseInt(day) + " " + monthNames[now.getUTCMonth()] + " " + short_year;
			break;
		default:
			// Default to d-m-Y format as shown in the PHP code
			formatted_date = day + "-" + month + "-" + year;
	}

	$(el).attr('value', formatted_date);
}

// Note card state logic including EasyMDE initialization and handling
function setNotesVisibility(state, noteText = "",show_notes = user_show_notes) {
	var $noteCard = $('#callsign-notes');
	var $saveBtn = $('#callsign-note-save-btn');
	var $editorElem = $('#callsign_note_content');
	var noteEditor = $editorElem.data('easymde');
	var $editBtn = $('#callsign-note-edit-btn');

	// Do nothing if user preference is to hide notes
	if (!show_notes) {
		$noteCard.hide();
		return;
	}

	// Initialize EasyMDE if not already done
	if (!noteEditor && typeof EasyMDE !== 'undefined') {
		noteEditor = new EasyMDE({
			element: $editorElem[0],
			spellChecker: false,
			toolbar: [
				"bold", "italic", "heading", "|","preview", "|",
				"quote", "unordered-list", "ordered-list", "|",
				"link", "image", "|",
				"guide"
			],
			forceSync: true,
			status: false,
			maxHeight: '150px',
			autoDownloadFontAwesome: false,
			autoRefresh: { delay: 250 },
		});
		$editorElem.data('easymde', noteEditor);
	}

	if (state === 0) {
		// No callsign - Hide note card
		$noteCard.hide();
		$('#callsign-notes-body').removeClass('show');

	} else if (state === 1) {
		// Callsign, no note yet - show note card with message
		$noteCard.show();
		$('#callsign-notes-body').removeClass('show');

		// Hide editor toolbar, set value and show preview
		document.querySelector('.EasyMDEContainer .editor-toolbar').style.display = 'none';
		noteEditor.value(lang_qso_note_missing);
		noteEditor.togglePreview();
		noteEditor.codemirror.setOption('readOnly', true);

	} else if (state === 2) {
		// Callsign with existing notes - show note card with notes
		$noteCard.show();

		// Automatically expand the panel when note exists
		$('#callsign-notes-body').addClass('show');

		// Hide editor toolbar, set value and show preview
		document.querySelector('.EasyMDEContainer .editor-toolbar').style.display = 'none';
		noteEditor.value(noteText);
		noteEditor.togglePreview();
		noteEditor.codemirror.setOption('readOnly', true);
	}

	// Hide buttons per default here
	$saveBtn.addClass('d-none').hide();
	$editBtn.addClass('d-none').hide();

	// Show Edit button for states 1 and 2
    if (state === 1 || state === 2) {
        $editBtn.removeClass('d-none').show();
    } else {
        $editBtn.addClass('d-none').hide();
    }
}

$('#stationProfile').on('change', function () {
	var stationProfile = $('#stationProfile').val();
	$.ajax({
		url: base_url + 'index.php/qso/get_station_power',
		type: 'post',
		data: { 'stationProfile': stationProfile },
		success: function (res) {
			$('#transmit_power').val(res.station_power);
			latlng=[res.lat,res.lng];
			station_callsign = res.station_callsign;
			$("#sat_name").change();
		},
		error: function () {
			$('#transmit_power').val('');
		},
	});
	// [eQSL default msg] change value on change station profle //
	qso_set_eqsl_qslmsg(stationProfile, false, '.qso_panel');
});

// [eQSL default msg] change value on clic //
$('.qso_panel .qso_eqsl_qslmsg_update').off('click').on('click', function () {
	qso_set_eqsl_qslmsg($('.qso_panel #stationProfile').val(), true, '.qso_panel');
	$('#charsLeft').text(" ");
});

$("#callsign").on("compositionstart", function(){ this.isComposing = true; });
$("#callsign").on("compositionend", function(e){
	this.isComposing = false;
	$(this).trigger("input");
});

$(document).on("keydown", function (e) {
	if (e.key === "Escape" && $('#callsign').val() != '') { // escape key maps to keycode `27`
		preventLookup = true;

		if (lookupCall) {
			lookupCall.abort();
		}

		reset_fields();

		// make sure the focusout event is finished before we allow a new lookup
		setTimeout(() => {
			preventLookup = false;
		}, 100);
		// console.log("Escape key pressed");
		$('#callsign').trigger("focus");
	}
});

// Sanitize some input data
$('#callsign').on('input', function () {
	// Prevent checking when the user's composing in IME
	if (this.isComposing) return;

	$(this).val($(this).val().replace(/\s/g, ''));
	$(this).val($(this).val().replace(/0/g, 'Ø'));
	$(this).val($(this).val().replace(/\./g, '/P'));
	$(this).val($(this).val().replace(/\ /g, ''));
});

$('#locator').on('input', function () {
	$(this).val($(this).val().replace(/\s/g, ''));
});

$("#check_cluster").on("click", function () {
	$.ajax({ url: dxcluster_provider + "/qrg_lookup/" + $("#frequency").val() / 1000, cache: false, dataType: "json" }).done(
		function (dxspot) {
			reset_fields();
			$("#callsign").val(dxspot.spotted);
			$("#callsign").trigger("blur");
		}
	);
});

function set_timers() {
	setTimeout(function () {
		var callsignValue = localStorage.getItem("quicklogCallsign");
		if (callsignValue !== null && callsignValue !== undefined) {
			$("#callsign").val(callsignValue);
			$("#mode").trigger("focus");
			localStorage.removeItem("quicklogCallsign");
		}
	}, 100);
}

function invalidAntEl() {
	var saveQsoButtonText = $("#saveQso").html();
	showToast(lang_general_word_warning, lang_invalid_ant_el+" "+parseFloat($("#ant_el").val()).toFixed(1), 'bg-warning text-dark', 5000);
	$("#saveQso").html(saveQsoButtonText).prop("disabled", false);
}

$("#qso_input").off('submit').on('submit', function (e) {
	e.preventDefault();

	// Check for rapid submission attempts (debounce)
	if (submitTimeout) {
		showToast(lang_general_word_warning, lang_qso_wait_before_saving, 'bg-info text-dark', 3000);
		return false;
	}

	// Prevent submission if Save button is disabled (fetch in progress)
	if ($('#saveQso').prop('disabled')) {
		return false;
	}

	var _submit = true;
	if ((typeof qso_manual !== "undefined") && (qso_manual == "1")) {
		if ($('#qso_input input[name="end_time"]').length == 1) { _submit = testTimeOffConsistency(); }
	}
	if (_submit) {
		// Set debounce timer (1 second)
		submitTimeout = setTimeout(function() {
			submitTimeout = null;
		}, 3000);

		var saveQsoButtonText = $("#saveQso").html();
		$("#saveQso").html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> ' + saveQsoButtonText + '...').prop('disabled', true);
		manual_addon = '?manual=' + qso_manual;

		// Capture form data before AJAX call for WebSocket transmission
		var formDataObj = {};
		$("#qso_input").serializeArray().map(function(x) {
			formDataObj[x.name] = x.value;
		});

		$.ajax({
			url: base_url + 'index.php/qso' + manual_addon,
			method: 'POST',
			type: 'post',
			timeout: 10000,
			data: $(this).serialize(),
			dataType: 'json',
			success: function (result) {
				if (result.message == 'success') {
					activeStationId = result.activeStationId;
					activeStationOP = result.activeStationOP;
					activeStationTXPower = result.activeStationTXPower;

					// Build dynamic success message
					var contactCallsign = $("#callsign").val().toUpperCase();
					var operatorCallsign = activeStationOP || station_callsign;
					var successMessage = lang_qso_added
						.replace('%s', contactCallsign)
						.replace('%s', operatorCallsign);

					showToast(lang_general_word_success, successMessage, 'bg-success text-white', 5000);

					// Send QSO data via WebSocket if CAT is enabled via WebSocket
					if (typeof sendQSOViaWebSocket === 'function') {
						// Add additional context to captured form data
						formDataObj.station_id = activeStationId;
						formDataObj.operator_callsign = operatorCallsign;
						formDataObj.timestamp = new Date().toISOString();

						// Include ADIF if available
						if (result.adif) {
							formDataObj.adif = result.adif;
						}

						// Send via WebSocket (function checks if WS is connected)
						var wsSent = sendQSOViaWebSocket(formDataObj);
						if (wsSent) {
							console.log('QSO sent via WebSocket with ADIF');
						}
					}

					prepare_next_qso(saveQsoButtonText);
					processBacklog();	// If we have success with the live-QSO, we could also process the backlog
					// Clear debounce timer on success to allow immediate next submission
					if (submitTimeout) {
						clearTimeout(submitTimeout);
						submitTimeout = null;
					}
				} else {
					showToast(lang_general_word_error, result.errors, 'bg-danger text-white', 5000);
					$("#saveQso").html(saveQsoButtonText).prop("disabled", false);
					// Clear debounce timer on error to allow retry
					if (submitTimeout) {
						clearTimeout(submitTimeout);
						submitTimeout = null;
					}
				}
			},
			error: function () {
				saveToBacklog(JSON.stringify(this.data),manual_addon);
				prepare_next_qso(saveQsoButtonText);
				showToast(lang_general_word_info, lang_qso_added_to_backlog, 'bg-info text-dark', 5000);
				// Clear debounce timer on error to allow retry
				if (submitTimeout) {
					clearTimeout(submitTimeout);
					submitTimeout = null;
				}
			}
		});
	}
	return false;
});

function prepare_next_qso(saveQsoButtonText) {
	reset_fields();
	htmx.trigger("#qso-last-table", "qso_event")
	$("#saveQso").html(saveQsoButtonText).prop("disabled", false);
	$("#callsign").val("");
	var triggerEl = document.querySelector('#myTab a[href="#qso"]')
	bootstrap.Tab.getInstance(triggerEl).show() // Select tab by name
	$("#callsign").trigger("focus");
}

var processingBL=false;

async function processBacklog() {
	if (!processingBL) {
		processingBL=true;
		const Qsobacklog = JSON.parse(localStorage.getItem('qso-backlog')) || [];
		for (const entry of [...Qsobacklog]) {
			try {
				await $.ajax({url: base_url + 'index.php/qso' + entry.manual_addon,  method: 'POST', type: 'post', data: JSON.parse(entry.data),
					success: function(resdata) {
						// Send QSO data via WebSocket if CAT is enabled via WebSocket
						if (typeof sendQSOViaWebSocket === 'function' && resdata) {
							try {
								const result = JSON.parse(resdata);
								if (result.message === 'success') {
									const qsoData = JSON.parse(entry.data);
									// Add additional context
									qsoData.station_id = result.activeStationId;
									qsoData.operator_callsign = result.activeStationOP || station_callsign;
									qsoData.timestamp = new Date().toISOString();
									qsoData.backlog_processed = true;

									sendQSOViaWebSocket(qsoData);
								}
							} catch (e) {
								// Ignore JSON parse errors
							}
						}
						Qsobacklog.splice(Qsobacklog.findIndex(e => e.id === entry.id), 1);
					},
					error: function() {
						entry.attempts++;
					}});
			} catch (error) {
				entry.attempts++;
			}
		}
		localStorage.setItem('qso-backlog', JSON.stringify(Qsobacklog));
		processingBL=false;
	}
}

function saveToBacklog(formData,manual_addon) {
	const backlog = JSON.parse(localStorage.getItem('qso-backlog')) || [];
	const entry = {
		id: Date.now(),
		timestamp: new Date().toISOString(),
		data: formData,
		manual_addon: manual_addon,
		attempts: 0
	};
	backlog.push(entry);
	localStorage.setItem('qso-backlog', JSON.stringify(backlog));
}

window.addEventListener('beforeunload', processBacklog());	// process possible QSO-Backlog on unload of page
window.addEventListener('pagehide', processBacklog());		// process possible QSO-Backlog on Hide of page (Mobile-Browsers)

$('#reset_time').on("click", function () {
	var now = new Date();
	$('#start_time').attr('value', ("0" + now.getUTCHours()).slice(-2) + ':' + ("0" + now.getUTCMinutes()).slice(-2) + ':' + ("0" + now.getUTCSeconds()).slice(-2));
	$("[id='start_time']").each(function () {
		$(this).attr("value", ("0" + now.getUTCHours()).slice(-2) + ':' + ("0" + now.getUTCMinutes()).slice(-2) + ':' + ("0" + now.getUTCSeconds()).slice(-2));
	});
});



// Function to format the current time as HH:MM or HH:MM:SS
function formatTime(date, includeSeconds) {
	let time = ("0" + date.getUTCHours()).slice(-2) + ":" + ("0" + date.getUTCMinutes()).slice(-2);
	if (includeSeconds) {
		time += ":" + ("0" + date.getUTCSeconds()).slice(-2);
	}
	return time;
}

// Event listener for resetting start time
$("#reset_start_time").on("click", function () {
	var now = new Date();

	// Format start and end times
	let startTime = formatTime(now, qso_manual != 1);
	let endTime = formatTime(now, qso_manual != 1);

	// Update all elements with id 'start_time'
	$("[id='start_time']").each(function () {
		$(this).val(startTime);
	});

	// Update all elements with id 'end_time'
	$("[id='end_time']").each(function () {
		$(this).val(endTime);
	});

	// Update the start date
	var day = ("0" + now.getUTCDate()).slice(-2);
	var month = ("0" + (now.getUTCMonth() + 1)).slice(-2);
	var year = now.getUTCFullYear();
	var short_year = year.toString().slice(-2);
	var formatted_date;
	switch (user_date_format) {
		case "d/m/y":
			formatted_date = day + "/" + month + "/" + short_year;
			break;
		case "d/m/Y":
			formatted_date = day + "/" + month + "/" + year;
			break;
		case "m/d/y":
			formatted_date = month + "/" + day + "/" + short_year;
			break;
		case "m/d/Y":
			formatted_date = month + "/" + day + "/" + year;
			break;
		case "d.m.Y":
			formatted_date = day + "." + month + "." + year;
			break;
		case "y/m/d":
			formatted_date = short_year + "/" + month + "/" + day;
			break;
		case "Y-m-d":
			formatted_date = year + "-" + month + "-" + day;
			break;
		case "M d, Y":
			// Need to get the month name abbreviation
			var monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
			formatted_date = monthNames[now.getUTCMonth()] + " " + parseInt(day) + ", " + year;
			break;
		case "M d, y":
			var monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
			formatted_date = monthNames[now.getUTCMonth()] + " " + parseInt(day) + ", " + short_year;
			break;
		case "d M y":
			var monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
			formatted_date = parseInt(day) + " " + monthNames[now.getUTCMonth()] + " " + short_year;
			break;	
		default:
			// Default to d-m-Y format as shown in the PHP code
			formatted_date = day + "-" + month + "-" + year;
	}
	$("#start_date").val(formatted_date);
});

function parseUserDate(user_provided_date) {	// creates JS-Date out of user-provided date with user_date_format
	var parts, day, month, year;
	var monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
	switch (user_date_format) {
		case "d/m/y":
			parts = user_provided_date.split("/");
			day = parseInt(parts[0], 10);
			month = parseInt(parts[1], 10) - 1;
			year = 2000 + parseInt(parts[2], 10);
			break;
		case "d/m/Y":
			parts = user_provided_date.split("/");
			day = parseInt(parts[0], 10);
			month = parseInt(parts[1], 10) - 1;
			year = parseInt(parts[2], 10);
			break;
		case "m/d/y":
			parts = user_provided_date.split("/");
			month = parseInt(parts[0], 10) - 1;
			day = parseInt(parts[1], 10);
			year = 2000 + parseInt(parts[2], 10);
			break;
		case "m/d/Y":
			parts = user_provided_date.split("/");
			month = parseInt(parts[0], 10) - 1;
			day = parseInt(parts[1], 10);
			year = parseInt(parts[2], 10);
			break;
		case "d.m.Y":
			parts = user_provided_date.split(".");
			day = parseInt(parts[0], 10);
			month = parseInt(parts[1], 10) - 1;
			year = parseInt(parts[2], 10);
			break;
		case "y/m/d":
			parts = user_provided_date.split("/");
			year = 2000 + parseInt(parts[0], 10);
			month = parseInt(parts[1], 10) - 1;
			day = parseInt(parts[2], 10);
			break;
		case "Y-m-d":
			parts = user_provided_date.split("-");
			year = parseInt(parts[0], 10);
			month = parseInt(parts[1], 10) - 1;
			day = parseInt(parts[2], 10);
			break;
		case "M d, Y":
			// Example: Jul 28, 2025
			parts = user_provided_date.replace(',', '').split(' ');
			month = monthNames.indexOf(parts[0]);
			if (month === -1) return null;
			day = parseInt(parts[1], 10);
			year = parseInt(parts[2], 10);
			break;
		case "M d, y":
			// Example: Jul 28, 25
			parts = user_provided_date.replace(',', '').split(' ');
			month = monthNames.indexOf(parts[0]);
			if (month === -1) return null;
			day = parseInt(parts[1], 10);
			year = 2000 + parseInt(parts[2], 10);
			break;
		case "d M y":
			// Example: 28 Jul 25
			parts = user_provided_date.split(' ');
			day = parseInt(parts[0], 10);
			month = monthNames.indexOf(parts[1]);
			if (month === -1) return null;
			year = 2000 + parseInt(parts[2], 10);
			break;
		default: // fallback "d-m-Y"
			parts = user_provided_date.split("-");
			day = parseInt(parts[0], 10);
			month = parseInt(parts[1], 10) - 1;
			year = parseInt(parts[2], 10);
	}
	if (isNaN(day) || day < 1 || day > 31 || isNaN(month) || month < 0 || month > 11 || isNaN(year)) return null;
	return new Date(year, month, day);
}

// Event listener for resetting end time
$("#reset_end_time").on("click", function () {
	var now = new Date();

	// Format end time
	let endTime = formatTime(now, qso_manual != 1);

	// Update all elements with id 'end_time'
	$("[id='end_time']").each(function () {
		$(this).val(endTime);
	});
});

$('#fav_add').on("click", function (event) {
	save_fav();
});

$(document).on("click", "#fav_del", function (event) {
	del_fav($(this).attr('name'));
});

$(document).on("click", "#fav_recall", function (event) {
	$('#sat_name').val(favs[this.innerText].sat_name);
	if (favs[this.innerText].sat_name) {
		$("#sat_name").change();
	}
	$('#sat_mode').val(favs[this.innerText].sat_mode);
	$('#band_rx').val(favs[this.innerText].band_rx);
	$('#band').val(favs[this.innerText].band);
	$('#frequency_rx').val(favs[this.innerText].frequency_rx);
	$('#frequency').val(favs[this.innerText].frequency).trigger("change");
	$('#selectPropagation').val(favs[this.innerText].prop_mode);
	$('#mode').val(favs[this.innerText].mode).on("change");
	setRst($('.mode').val());
});


function del_fav(name) {
	if (confirm(lang_qso_delete_fav_confirm)) {
		$.ajax({
			url: base_url + 'index.php/user_options/del_fav',
			method: 'POST',
			dataType: 'json',
			contentType: "application/json; charset=utf-8",
			data: JSON.stringify({ "option_name": name }),
			success: function (result) {
				get_fav();
			}
		});
	}
}

function get_fav() {
	$.ajax({
		url: base_url + 'index.php/user_options/get_fav',
		method: 'GET',
		dataType: 'json',
		contentType: "application/json; charset=utf-8",
		success: function (result) {
			$("#fav_menu").empty();
			for (const key in result) {
				$("#fav_menu").append('<label class="dropdown-item" style="display: flex; justify-content: space-between;"><span id="fav_recall">' + key + '</span><span class="badge bg-danger" id="fav_del" name="' + key + '"><i class="fas fa-trash-alt"></i></span></label>');
			}
			favs = result;
		}
	});
}

function save_fav() {
	var payload = {};
	payload.sat_name = $('#sat_name').val();
	payload.sat_mode = $('#sat_mode').val();
	payload.band_rx = $('#band_rx').val();
	payload.band = $('#band').val();
	payload.frequency_rx = $('#frequency_rx').val();
	payload.frequency = $('#frequency').val();
	payload.prop_mode = $('#selectPropagation').val();
	payload.mode = $('#mode').val();
	$.ajax({
		url: base_url + 'index.php/user_options/add_edit_fav',
		method: 'POST',
		dataType: 'json',
		contentType: "application/json; charset=utf-8",
		data: JSON.stringify(payload),
		success: function (result) {
			get_fav();
		}
	});
}


if (qso_manual == 0) {
	var bc_bandmap = new BroadcastChannel('qso_window');
	bc_bandmap.onmessage = function (ev) {
		// respond ONLY to ping if we've an open live-window
		// Otherwise a spot will be filled accidently into an open POST-QSO Window
		if (ev.data == 'ping') {
			bc_bandmap.postMessage('pong');
		}
	}
}

// Store pending references from bandmap to populate AFTER callsign lookup completes
// Map structure: callsign -> {seq, refs, timestamp, populated}
var pendingReferencesMap = new Map();
var referenceSequence = 0;

// Track last lookup to prevent duplicate calls
var lastLookupCallsign = null;
var lookupInProgress = false;

// Helper function to populate reference fields after callsign lookup completes
// Uses Map-based storage to prevent race conditions
function populatePendingReferences(callsign, expectedSeq) {
	// Handle legacy call without parameters
	if (!callsign) {
		callsign = $('#callsign').val();
	}

	const entry = pendingReferencesMap.get(callsign);

	if (!entry) {
		// No references for this callsign - this is normal for non-POTA/SOTA/WWFF spots
		return;
	}

	// Validate sequence only if expectedSeq was provided
	// This prevents stale data from being populated
	if (expectedSeq !== null && expectedSeq !== undefined && entry.seq !== expectedSeq) {
		console.warn('Sequence mismatch - ignoring stale references', {
			callsign: callsign,
			expected: expectedSeq,
			actual: entry.seq
		});
		return;
	}

	// Check if already populated - prevent double-population for same instance
	if (entry.populated) {
		return;
	}
	entry.populated = true;

	const refs = entry.refs;

	// POTA - set without triggering change initially (silent = true)
	if (refs.pota_ref && $('#pota_ref').length) {
		try {
			var $select = $('#pota_ref').selectize();
			if ($select.length && $select[0].selectize) {
				var selectize = $select[0].selectize;
				selectize.addOption({name: refs.pota_ref});
				selectize.setValue(refs.pota_ref, true); // Silent = true
				// Manually show icon since onChange doesn't fire in silent mode
				if (refs.pota_ref.indexOf(',') === -1) {
					$('#pota_info').show();
					$('#pota_info').html('<a target="_blank" href="https://pota.app/#/park/' + refs.pota_ref + '"><img width="32" height="32" src="' + base_url + 'images/icons/pota.app.png"></a>');
					$('#pota_info').attr('title', lang_qso_lookup_reference_info.replace('%s', refs.pota_ref).replace('%s', 'pota.co'));
				}
			}
		} catch (e) {
			console.warn('Could not set POTA reference:', e);
		}
	}

	// SOTA - set without triggering change initially (silent = true)
	if (refs.sota_ref && $('#sota_ref').length) {
		try {
			var $select = $('#sota_ref').selectize();
			if ($select.length && $select[0].selectize) {
				var selectize = $select[0].selectize;
				selectize.addOption({name: refs.sota_ref});
				selectize.setValue(refs.sota_ref, true); // Silent = true
				// Manually show icon since onChange doesn't fire in silent mode
				$('#sota_info').show();
				$('#sota_info').html('<a target="_blank" href="https://summits.sota.org.uk/summit/' + refs.sota_ref + '"><img width="32" height="32" src="' + base_url + 'images/icons/sota.org.uk.png"></a>');
				$('#sota_info').attr('title', lang_qso_lookup_summit_info.replace('%s', refs.sota_ref).replace('%s', 'sota.org.uk'));
			}
		} catch (e) {
			console.warn('Could not set SOTA reference:', e);
		}
	}

	// WWFF - set without triggering change initially (silent = true)
	if (refs.wwff_ref && $('#wwff_ref').length) {
		try {
			var $select = $('#wwff_ref').selectize();
			if ($select.length && $select[0].selectize) {
				var selectize = $select[0].selectize;
				selectize.addOption({name: refs.wwff_ref});
				selectize.setValue(refs.wwff_ref, true); // Silent = true
				// Manually show icon since onChange doesn't fire in silent mode
				$('#wwff_info').show();
				$('#wwff_info').html('<a target="_blank" href="https://www.cqgma.org/zinfo.php?ref=' + refs.wwff_ref + '"><img width="32" height="32" src="' + base_url + 'images/icons/wwff.co.png"></a>');
				$('#wwff_info').attr('title', lang_qso_lookup_reference_info.replace('%s', refs.wwff_ref).replace('%s', 'cqgma.org'));
			}
		} catch (e) {
			console.warn('Could not set WWFF reference:', e);
		}
	}

	// IOTA - set silently (no change trigger yet)
	if (refs.iota_ref && $('#iota_ref').length) {
		try {
			let $iotaSelect = $('#iota_ref');
			if ($iotaSelect.find('option[value="' + refs.iota_ref + '"]').length === 0) {
				$iotaSelect.append(new Option(refs.iota_ref, refs.iota_ref));
			}
			$iotaSelect.val(refs.iota_ref); // Don't trigger change yet
		} catch (e) {
			console.warn('Could not set IOTA reference:', e);
		}
	}

	// NOW trigger gridsquare lookup ONLY ONCE for the highest priority reference
	// Priority: POTA > SOTA > WWFF (most commonly used)
	// This prevents multiple simultaneous AJAX gridsquare lookups that can race
	setTimeout(function() {
		if (refs.pota_ref && $('#pota_ref').length) {
			$('#pota_ref').trigger('change');
		} else if (refs.sota_ref && $('#sota_ref').length) {
			$('#sota_ref').trigger('change');
		} else if (refs.wwff_ref && $('#wwff_ref').length) {
			$('#wwff_ref').trigger('change');
		}

		// Cleanup immediately after triggering - we're done with these references
		pendingReferencesMap.delete(callsign);
	}, 100); // Small delay to let form settle
}

if (qso_manual == 0) {
	var bc = new BroadcastChannel('qso_wish');
	bc.onmessage = function (ev) {
		// Handle ping/pong only when manual mode is disabled (qso_manual == 0)
		if (ev.data.ping) {
			if (qso_manual == 0) {
				let message = {};
				message.pong = true;
				bc.postMessage(message);
			}
		} else {
			// Always process frequency, callsign, and reference data from bandmap
			// (regardless of manual mode - bandmap should control the form)
			const callsign = ev.data.call;
			const seq = ++referenceSequence;
			let delay = 0;

			// Only reset if callsign is different from what we're about to set
			if ($("#callsign").val() != "" && $("#callsign").val() != callsign) {
				reset_fields();
				delay = 600;
			}

			// Store references with metadata in Map (prevents race conditions)
			pendingReferencesMap.set(callsign, {
				seq: seq,
				refs: {
					pota_ref: ev.data.pota_ref,
					sota_ref: ev.data.sota_ref,
					wwff_ref: ev.data.wwff_ref,
					iota_ref: ev.data.iota_ref
				},
				timestamp: Date.now(),
				populated: false
			});

			// Cleanup old entries (> 30 seconds)
			for (let [key, value] of pendingReferencesMap) {
				if (Date.now() - value.timestamp > 30000) {
					pendingReferencesMap.delete(key);
				}
			}

			setTimeout(() => {
				if (ev.data.frequency != null) {
					$('#frequency').val(ev.data.frequency).trigger("change");
					$("#band").val(frequencyToBand(ev.data.frequency));
				}
				if (ev.data.frequency_rx != "") {
					$('#frequency_rx').val(ev.data.frequency_rx);
					$("#band_rx").val(frequencyToBand(ev.data.frequency_rx));
				}
				// Set mode if provided (backward compatible - optional field)
				if (ev.data.mode) {
					$("#mode").val(ev.data.mode);
				}

				// Store sequence for validation in populatePendingReferences
				$("#callsign").data('expected-refs-seq', seq);

				$("#callsign").val(callsign);
				$("#callsign").focusout();
				$("#callsign").blur();
			}, delay);
		}
	} /* receive */

}
$("#sat_name").on('change', function () {
	var sat = $("#sat_name").val();
	if (sat == "") {
		$("#sat_mode").val("");
		$("#selectPropagation").val("");
		stop_az_ele_ticker();
	} else {
		$('#lotw_support').text("");
		$('#lotw_support').removeClass();
		get_sat_info();
	}
});

$("#sat_name").on('focusout', function () {
	if ($(this).val().length == 0) {
		$('#lotw_support').text("");
		$('#lotw_support').removeClass();
	}
});

var satupdater;

function stop_az_ele_ticker() {
	if (satupdater) {
		clearInterval(satupdater);
	}
	$("#ant_az").val('');
	$("#ant_el").val('');
}

function start_az_ele_ticker(tle) {
	const lines = tle.tle.trim().split('\n');

	// Initialize a satellite record
	var satrec = satellite.twoline2satrec(lines[0], lines[1]);

	// Define the observer's location in radians
	var observerGd = {
		longitude: satellite.degreesToRadians(latlng[1]),
		latitude: satellite.degreesToRadians(latlng[0]),
		height: 0.370
	};

	function updateAzEl() {
		let dateParts=parseUserDate($('#start_date').val());
		let timeParts=$("#start_time").val().split(":");
		try {
			var time = new Date(Date.UTC(
				dateParts.getFullYear(),dateParts.getMonth(),dateParts.getDate(),
				parseInt(timeParts[0]),parseInt(timeParts[1]),(parseInt(timeParts[2] ?? 0))
			));
			if (isNaN(time.getTime())) {
				throw new Error("Invalid date");
			}
			var positionAndVelocity = satellite.propagate(satrec, time);
			var gmst = satellite.gstime(time);
			var positionEcf = satellite.eciToEcf(positionAndVelocity.position, gmst);
			var observerEcf = satellite.geodeticToEcf(observerGd);
			var lookAngles = satellite.ecfToLookAngles(observerGd, positionEcf);
			let az=(satellite.radiansToDegrees(lookAngles.azimuth).toFixed(2));
			let el=(satellite.radiansToDegrees(lookAngles.elevation).toFixed(2));
			$("#ant_az").val(parseFloat(az).toFixed(1));
			$("#ant_el").val(parseFloat(el).toFixed(1));

			// Send real-time azimuth/elevation via WebSocket if using WebSocket CAT and working satellite
			if (typeof sendSatellitePositionViaWebSocket === 'function') {
				var satName = $("#sat_name").val();
				if (satName && satName !== '') {
					sendSatellitePositionViaWebSocket(satName, parseFloat(az).toFixed(1), parseFloat(el).toFixed(1));
				}
			}
		} catch(e) {
			$("#ant_az").val('');
			$("#ant_el").val('');
		}
	}
	satupdater=setInterval(updateAzEl, 1000);
}

function get_sat_info() {
	stop_az_ele_ticker();
	$.ajax({
		url: base_url + 'index.php/satellite/get_sat_info',
		type: 'post',
		data: {
			sat: $("#sat_name").val(),
		},
		success: function (data) {
			if (data !== null) {
				if (data.tle) {
					start_az_ele_ticker(data);
				}
				if (data.lotw_support == 'Y') {
					$('#lotw_support').html(lang_qso_sat_lotw_supported).fadeIn("slow");
					$('#lotw_support').addClass('badge bg-success');
				} else if (data.lotw_support == 'N') {
					$('#lotw_support').html(lang_qso_sat_lotw_not_supported).fadeIn("slow");
					$('#lotw_support').addClass('badge bg-danger');
				}
			} else {
				$('#lotw_support').html(lang_qso_sat_lotw_support_not_found).fadeIn("slow");
				$('#lotw_support').addClass('badge bg-warning');
			}
		},
		error: function (data) {
			console.log('Something went wrong while trying to fetch info for sat: '+$("#sat_name"));
		},
	});
}

if ($("#sat_name").val() !== '') {
	get_sat_info();
}

$('#stateDropdown').on('change', function () {
	var state = $("#stateDropdown option:selected").text();
	var dxcc = $("#dxcc_id option:selected").val();

	if (state != "") {
		switch (dxcc) {
			case '6':
			case '110':
			case '291':
				$("#stationCntyInputQso").prop('disabled', false);
				selectize_usa_county('#stateDropdown', '#stationCntyInputQso');
				break;
			case '15':
			case '54':
			case '61':
			case '126':
			case '151':
			case '288':
			case '339':
			case '170':
			case '21':
			case '29':
			case '32':
			case '281':
				$("#stationCntyInputQso").prop('disabled', false);
				break;
			default:
				$("#stationCntyInputQso").prop('disabled', true);
		}

	} else {
		$("#stationCntyInputQso").prop('disabled', true);
		//$('#stationCntyInputQso')[0].selectize.destroy();
		$("#stationCntyInputQso").val("");
	}
});

$(document).on('change', 'input', function () {
	var optionslist = $('.satellite_modes_list')[0].options;
	var value = $(this).val();
	for (var x = 0; x < optionslist.length; x++) {
		if (optionslist[x].value === value) {

			// Store selected sat mode
			selected_sat_mode = value;

			// get Json file
			$.getJSON(site_url + "/satellite/satellite_data", function (data) {

				// Build the options array
				var sat_modes = [];
				$.each(data, function (key, val) {
					if (key == selected_sat) {
						$.each(val.Modes, function (key1, val2) {
							if (key1 == selected_sat_mode) {

								if ((val2[0].Downlink_Mode == "LSB" && val2[0].Uplink_Mode == "USB") || (val2[0].Downlink_Mode == "USB" && val2[0].Uplink_Mode == "LSB")) { // inverting Transponder? set to SSB
									$("#mode").val("SSB");
								} else {
									$("#mode").val(val2[0].Uplink_Mode);
								}
								$("#band").val(frequencyToBand(val2[0].Uplink_Freq));
								$("#band_rx").val(frequencyToBand(val2[0].Downlink_Freq));
								$("#frequency").val(val2[0].Uplink_Freq).trigger("change");
								$("#frequency_rx").val(val2[0].Downlink_Freq);
								$("#selectPropagation").val('SAT');
							}
						});
					}
				});

			});
		}
	}
});

$(document).on('change', 'input', function () {
	var optionslist = $('.satellite_names_list')[0].options;
	var value = $(this).val();
	for (var x = 0; x < optionslist.length; x++) {
		if (optionslist[x].value === value) {
			$("#sat_mode").val("");
			$('.satellite_modes_list').find('option').remove().end();
			selected_sat = value;
			// get Json file
			$.getJSON(site_url + "/satellite/satellite_data", function (data) {

				// Build the options array
				var sat_modes = [];
				$.each(data, function (key, val) {
					if (key == value) {
						$.each(val.Modes, function (key1, val2) {
							//console.log (key1);
							sat_modes.push('<option value="' + key1 + '">' + key1 + '</option>');
						});
					}
				});

				// Add to the datalist
				$('.satellite_modes_list').append(sat_modes.join(""));

			});
		}
	}
});

function changebadge(entityval) {
	if ($("#sat_name").val() != "") {
		$.getJSON(base_url + 'index.php/logbook/jsonlookupdxcc/' + entityval + '/SAT/0/0', function (result) {

			$('#callsign_info').removeClass("lotw_info_orange");
			$('#callsign_info').removeClass("text-bg-secondary");
			$('#callsign_info').removeClass("text-bg-success");
			$('#callsign_info').removeClass("text-bg-danger");
			$('#callsign_info').attr('title', '');

			if (result.confirmed) {
				$('#callsign_info').addClass("text-bg-success");
				$('#callsign_info').attr('title', decodeHtml(lang_qso_dxcc_confirmed));
			} else if (result.workedBefore) {
				$('#callsign_info').addClass("text-bg-success");
				$('#callsign_info').addClass("lotw_info_orange");
				$('#callsign_info').attr('title', decodeHtml(lang_qso_dxcc_worked));
			} else {
				$('#callsign_info').addClass("text-bg-danger");
				$('#callsign_info').attr('title', decodeHtml(lang_qso_dxcc_new));
			}
		})
	} else {
		$.getJSON(base_url + 'index.php/logbook/jsonlookupdxcc/' + entityval + '/0/' + $("#band").val() + '/' + $("#mode").val(), function (result) {
			// Reset CSS values before updating
			$('#callsign_info').removeClass("lotw_info_orange");
			$('#callsign_info').removeClass("text-bg-secondary");
			$('#callsign_info').removeClass("text-bg-success");
			$('#callsign_info').removeClass("text-bg-danger");
			$('#callsign_info').attr('title', '');

			if (result.confirmed) {
				$('#callsign_info').addClass("text-bg-success");
				$('#callsign_info').attr('title', decodeHtml(lang_qso_dxcc_confirmed));
			} else if (result.workedBefore) {
				$('#callsign_info').addClass("text-bg-success");
				$('#callsign_info').addClass("lotw_info_orange");
				$('#callsign_info').attr('title', decodeHtml(lang_qso_dxcc_worked));
			} else {
				$('#callsign_info').addClass("text-bg-danger");
				$('#callsign_info').attr('title', decodeHtml(lang_qso_dxcc_new));
			}
		})
	}
}

$('#btn_reset').on("click", function () {
	preventLookup = true;

	if (lookupCall) {
		lookupCall.abort();
	}

	reset_fields();

	// make sure the focusout event is finished before we allow a new lookup
	setTimeout(() => {
		preventLookup = false;
	}, 100);
});

$('#btn_fullreset').on("click", function () {
	reset_to_default();
});

function reset_to_default() {
	reset_fields();
	panMap(activeStationId);
	$("#stationProfile").val(activeStationId);
	$("#selectPropagation").val("");
	$("#frequency_rx").val("");
	$("#band_rx").val("");
	$("#transmit_power").val(activeStationTXPower);
	$("#sat_name").val("");
	$("#sat_mode").val("");
	$("#ant_az").val("");
	$("#ant_el").val("");
	$("#distance").val("");
	stop_az_ele_ticker();
}

/* Function: reset_fields is used to reset the fields on the QSO page */
function reset_fields() {
	// Clear all pending references to avoid they get prefilled in the next QSO after clear
	// we do this first to avoid race conditions for slow javascript
	pendingReferencesMap.clear();

	$('#locator_info').text("");
	$('#lotw_support').text("");
	$('#lotw_support').removeClass();
	$('#comment').val("");
	$('#country').val("");
	$('#continent').val("");
	$('#email').val("");
	$('#region').val("");
	$('#ham_of_note_info').text("");
	$('#ham_of_note_link').html("");
	$('#ham_of_note_link').removeAttr('href');
	$('#ham_of_note_line').hide();
	$('#lotw_info').text("");
	$('#lotw_info').attr('data-bs-original-title', "");
	$('#lotw_info').removeClass("lotw_info_red");
	$('#lotw_info').removeClass("lotw_info_yellow");
	$('#lotw_info').removeClass("lotw_info_orange");
	$('#qrz_info').text("").hide();
	$('#hamqth_info').text("").hide();
	$('#email_info').html("").addClass('d-none').hide();
	$('#dxcc_id').val("").multiselect('refresh');
	$('#cqz').val("");
	$('#ituz').val("");
	$('#name').val("");
	$('#qth').val("");
	$('#locator').val("");
	$('#ant_path').val("");
	$('#iota_ref').val("");
	$("#locator").removeClass("confirmedGrid");
	$("#locator").removeClass("workedGrid");
	$("#locator").removeClass("newGrid");
	$('#locator').attr('title', '');
	$("#callsign").val("");
	$("#callsign").removeClass("confirmedGrid");
	$("#callsign").removeClass("workedGrid");
	$("#callsign").removeClass("newGrid");
	$('#callsign').attr('title', '');
	$('#callsign_info').removeClass("text-bg-secondary");
	$('#callsign_info').removeClass("text-bg-success");
	$('#callsign_info').removeClass("text-bg-danger");
	$('#callsign-image').attr('style', 'display: none;');
	$('#callsign-image-content').text("");
	$('#callsign-image-info').html("");
	$('#callsign-image-info').hide();
	$("#operator_callsign").val(activeStationOP);
	$('#qsl_via').val("");
	$('#callsign_info').text("");
	$('#stateDropdown').val("");
	$('#qso-last-table').show();
	$('#partial_view').hide();
	$('.callsign-suggest').hide();
	$("#distance").val("");
	setRst($(".mode").val());
	var $select = $('#sota_ref').selectize();
	var selectize = $select[0].selectize;
	selectize.clear();
	var $select = $('#wwff_ref').selectize();
	var selectize = $select[0].selectize;
	selectize.clear();
	var $select = $('#pota_ref').selectize();
	var selectize = $select[0].selectize;
	selectize.clear();
	var $select = $('#darc_dok').selectize();
	var selectize = $select[0].selectize;
	selectize.clear();
	$('#stationCntyInputQso').val("");
	$select = $('#stationCntyInputQso').selectize();
	selectize = $select[0].selectize;
	selectize.clear();

	var $select = $('#sota_ref').selectize();
	var selectize = $select[0].selectize;
	selectize.clear();

	$('#notes').val("");

	$('#sig').val("");
	$('#sig_info').val("");
	$('#sent').val("N");
	$('#sent-method').val("");
	$('#qsl_via').val("");

	mymap.setView(pos, 12);
	mymap.removeLayer(markers);
	if (window.mapBanner) {
		mymap.removeControl(window.mapBanner);
	}
	$('.callsign-suggest').hide();
	$('.awardpane').remove();
	$('#timesWorked').html(lang_qso_title_previous_contacts);
	updateStateDropdown('#dxcc_id', '#stateInputLabel', '#location_us_county', '#stationCntyInputEdit');
	clearTimeout();
	set_timers();
	resetTimers(qso_manual);
	setNotesVisibility(0); // Set note card to hidden
}

// Get status of notes for this callsign
function get_note_status(callsign){
		$.get(
			window.base_url + 'index.php/notes/check_duplicate',
			{
				category: 'Contacts',
				title: callsign
			},
			function(data) {
				if (typeof data === 'string') {
					try { data = JSON.parse(data); } catch (e) { data = {}; }
				}
				if (data && data.exists === true && data.id) {
					// Get the note content using the note ID
					$.get(
						window.base_url + 'index.php/notes/get/' + data.id,
						function(noteData) {
							if (typeof noteData === 'string') {
								try { noteData = JSON.parse(noteData); } catch (e) { noteData = {}; }
							}
							if (noteData && noteData.content) {
								$('#callsign-note-id').val(data.id);
								setNotesVisibility(2, noteData.content);
							} else {
								$('#callsign-note-id').val('');
								setNotesVisibility(2, lang_general_word_error);
							}
						}
					).fail(function() {
						$('#callsign-note-id').val('');
						setNotesVisibility(2, lang_general_word_error);
					});
				} else {
					$('#callsign-note-id').val('');
					setNotesVisibility(1);
				}
			}
		);
}

// Lookup callsign on focusout - if the callsign is 3 chars or longer
$("#callsign").on("focusout", function () {
	if ($(this).val().length >= 3 && preventLookup == false) {

		var currentCallsign = $(this).val().toUpperCase().replaceAll('Ø', '0');

		// Prevent duplicate lookups for the same callsign if already in progress
		if (lookupInProgress && lastLookupCallsign === currentCallsign) {
			return;
		}

		// If callsign changed, allow new lookup even if one is in progress
		lastLookupCallsign = currentCallsign;
		lookupInProgress = true;

		// Check if we have pending references from bandmap for this callsign
		// If yes, get the sequence; if no, we'll populate without sequence validation
		var hasPendingRefs = pendingReferencesMap.has(currentCallsign);
		var expectedSeq = hasPendingRefs ? pendingReferencesMap.get(currentCallsign).seq : null;

		// Disable Save QSO button and show fetch status
		$('#saveQso').prop('disabled', true);
		$('#fetch_status').show();

		// Set timeout to unlock form after 10 seconds
		var fetchTimeout = setTimeout(function() {
			$('#saveQso').prop('disabled', false);
			$('#fetch_status').hide();
		}, 10000);

		/* Find and populate DXCC */
		$('.callsign-suggest').hide();

		if ($("#sat_name").val() != "") {
			var json_band = "SAT";
		} else {
			var json_band = $("#band").val();
		}
		const json_mode = $("#mode").val();

		let find_callsign = $(this).val().toUpperCase();
		let callsign = find_callsign;
		let startDate = $('#start_date').val();
		// Characters '/' and ',' are not URL safe, so we replace
		// them with  '_' and '%'.
		if (startDate.includes('/')) {
			startDate = startDate.replaceAll('/', '_');
		}
		if (startDate.includes(',')) {
			startDate = startDate.replaceAll(',', '%');
		}
		startDate = encodeURIComponent(startDate);
		const stationProfile = $('#stationProfile').val();

		find_callsign = find_callsign.replace(/\//g, "-");
		find_callsign = find_callsign.replaceAll('Ø', '0');
		const url = `${base_url}index.php/logbook/json/${find_callsign}/${json_band}/${json_mode}/${stationProfile}/${startDate}/${last_qsos_count}`;

		// Replace / in a callsign with - to stop urls breaking
		lookupCall = $.getJSON(url, async function (result) {

			// Make sure the typed callsign and json result match
			if ($('#callsign').val().toUpperCase().replaceAll('Ø', '0') == result.callsign) {

				// Reset QSO fields
				resetDefaultQSOFields();

				// --- Added for WebSocket Integration (Rotators / External Displays) ---
				if (typeof window.broadcastLookupResult === 'function') {
					const broadcastData = {
						callsign: result.callsign,
						dxcc_id: result.dxcc.adif,
						name: result.callsign_name,
						gridsquare: result.callsign_qra,
						city: result.callsign_qth,
						iota: result.callsign_iota,
						state: result.callsign_state,
						us_county: result.callsign_us_county,
						bearing: result.bearing,
						distance: result.callsign_distance,
						lotw_member: result.lotw_member,
						lotw_days: result.lotw_days,
						eqsl_member: result.eqsl_member,
						qsl_manager: result.qsl_manager,
						slot_confirmed: result.dxcc_confirmed_on_band_mode
					};
					window.broadcastLookupResult(broadcastData);
				}
				// ---------------------------------------------------------

				// Set qso icon
				get_note_status(result.callsign);

				if (result.dxcc.entity != undefined) {
					$('#country').val(convert_case(result.dxcc.entity));
					$('#callsign_info').text(convert_case(result.dxcc.entity));

					if ($("#sat_name").val() != "") {
						//logbook/jsonlookupgrid/io77/SAT/0/0
						await $.getJSON(base_url + 'index.php/logbook/jsonlookupcallsign/' + find_callsign + '/SAT/0/0', function (result) {
							// Reset CSS values before updating
							$('#callsign').removeClass("workedGrid");
							$('#callsign').removeClass("confirmedGrid");
							$('#callsign').removeClass("newGrid");
							$('#callsign').attr('title', '');
							$('#ham_of_note_info').text("");
							$('#ham_of_note_link').html("");
							$('#ham_of_note_link').removeAttr('href');
							$('#ham_of_note_line').hide();

							if (result.confirmed) {
								$('#callsign').addClass("confirmedGrid");
								$('#callsign').attr('title', lang_qso_callsign_confirmed);
							} else if (result.workedBefore) {
								$('#callsign').addClass("workedGrid");
								$('#callsign').attr('title', lang_qso_callsign_worked);
							}
							else {
								$('#callsign').addClass("newGrid");
								$('#callsign').attr('title', lang_qso_callsign_new);
							}
						})
					} else {
						await $.getJSON(base_url + 'index.php/logbook/jsonlookupcallsign/' + find_callsign + '/0/' + $("#band").val() + '/' + $("#mode").val(), function (result) {
							// Reset CSS values before updating
							$('#callsign').removeClass("confirmedGrid");
							$('#callsign').removeClass("workedGrid");
							$('#callsign').removeClass("newGrid");
							$('#callsign').attr('title', '');
							$('#ham_of_note_info').text("");
							$('#ham_of_note_link').html("");
							$('#ham_of_note_link').removeAttr('href');
							$('#ham_of_note_line').hide();

						if (result.confirmed) {
							$('#callsign').addClass("confirmedGrid");
							$('#callsign').attr('title', lang_qso_callsign_confirmed);
						} else if (result.workedBefore) {
							$('#callsign').addClass("workedGrid");
							$('#callsign').attr('title', lang_qso_callsign_worked);
						} else {
							$('#callsign').addClass("newGrid");
							$('#callsign').attr('title', lang_qso_callsign_new);
						}
						})
					}

					changebadge(result.dxcc.adif);

					// Reload DXCC summary table if it was already loaded
					let $targetPane = $('#dxcc-summary');
					if ($targetPane.data("loaded")) {
						$targetPane.data("loaded", false);
						getDxccResult(result.dxcc.adif, convert_case(result.dxcc.entity));
					}

				}

				if (result.lotw_member == "active") {
					$('#lotw_info').text("LoTW");
					if (result.lotw_days > 365) {
						$('#lotw_info').addClass('lotw_info_red');
					} else if (result.lotw_days > 30) {
						$('#lotw_info').addClass('lotw_info_orange');
						$lotw_hint = ' lotw_info_orange';
					} else if (result.lotw_days > 7) {
						$('#lotw_info').addClass('lotw_info_yellow');
					}
					$('#lotw_link').attr('href', "https://lotw.arrl.org/lotwuser/act?act=" + callsign.replace('Ø', '0'));
					$('#lotw_link').attr('target', "_blank");
					$('#lotw_info').attr('data-bs-toggle', "tooltip");
					if (result.lotw_days == 1) {
						$('#lotw_info').attr('data-bs-original-title', decodeHtml(lang_lotw_upload_day_ago));
					} else {
						$('#lotw_info').attr('data-bs-original-title', decodeHtml(lang_lotw_upload_days_ago.replace('%x', result.lotw_days)));
					}
					$('[data-bs-toggle="tooltip"]').tooltip();
				}
				$('#qrz_info').html('<a target="_blank" href="https://www.qrz.com/db/' + callsign.replaceAll('Ø', '0') + '"><img width="30" height="30" src="' + base_url + 'images/icons/qrz.com.png"></a>');
				$('#qrz_info').attr('title', decodeHtml(lang_qso_lookup_info.replace('%s', callsign).replace('%s', 'qrz.com'))).removeClass('d-none');
				$('#qrz_info').show();
				$('#hamqth_info').html('<a target="_blank" href="https://www.hamqth.com/' + callsign.replaceAll('Ø', '0') + '"><img width="30" height="30" src="' + base_url + 'images/icons/hamqth.com.png"></a>');
				$('#hamqth_info').attr('title', decodeHtml(lang_qso_lookup_info.replace('%s', callsign).replace('%s', 'hamqth.com'))).removeClass('d-none');
				$('#hamqth_info').show();

				var $dok_select = $('#darc_dok').selectize();
				var dok_selectize = $dok_select[0].selectize;
				if ((result.dxcc.adif == '230') && (($("#callsign").val().trim().length) > 0)) {
					$.get(base_url + 'index.php/lookup/dok/' + $('#callsign').val().toUpperCase().replaceAll('Ø', '0').replaceAll('/','-'), function (result) {
						if (result) {
							dok_selectize.addOption({ name: result });
							dok_selectize.setValue(result, false);
						}
					});
				} else {
					dok_selectize.clear();
				}

				$.getJSON(base_url + 'index.php/lookup/ham_of_note/' + $('#callsign').val().toUpperCase().replaceAll('Ø', '0').replaceAll('/','-'), function (result) {
					if (result) {
						$('#ham_of_note_info').html('<span class="minimize">'+result.description+'</span>');
						if (result.link != null) {
							$('#ham_of_note_link').html(" "+result.linkname);
							$('#ham_of_note_link').attr('href', result.link);
						}
						$('#ham_of_note_line').show("slow");

						var minimized_elements = $('span.minimize');
						var maxlen = 50;

						minimized_elements.each(function(){
							var t = $(this).text();
							if(t.length < maxlen) return;
							$(this).html(
								t.slice(0,maxlen)+'<span>... </span><a href="#" class="more">'+lang_qso_more+'</a><span style="display:none;">'+ t.slice(maxlen,t.length)+' <a href="#" class="less">'+lang_qso_less+'</a></span>'
							);
						});

						$('a.more', minimized_elements).click(function(event){
							event.preventDefault();
							$(this).hide().prev().hide();
							$(this).next().show();
						});

						$('a.less', minimized_elements).click(function(event){
							event.preventDefault();
							$(this).parent().hide().prev().show().prev().show();
						});

					}
				});
				$('#dxcc_id').val(result.dxcc.adif).multiselect('refresh');
				await updateStateDropdown('#dxcc_id', '#stateInputLabel', '#location_us_county', '#stationCntyInputEdit');
				if (result.callsign_cqz != '' && (result.callsign_cqz >= 1 && result.callsign_cqz <= 40)) {
					$('#cqz').val(result.callsign_cqz);
				} else {
					$('#cqz').val(result.dxcc.cqz);
				}

				if (result.callsign_ituz != '') {
					$('#ituz').val(result.callsign_ituz);
				} else {
					$('#ituz').val(result.dxcc.ituz);
				}

				var redIcon = L.icon({
					iconUrl: icon_dot_url,
					iconSize: [18, 18], // size of the icon
				});

				// Set Map to Lat/Long
				markers.clearLayers();
				mymap.setZoom(8);
				// Remove previous banner (if any)
				if (window.mapBanner) {
					mymap.removeControl(window.mapBanner);
				}

				if (typeof result.latlng !== "undefined" && result.latlng !== false) {
					var marker = L.marker([result.latlng[0], result.latlng[1]], { icon: redIcon });
					mymap.panTo([result.latlng[0], result.latlng[1]]);
					mymap.setView([result.latlng[0], result.latlng[1]], 8);
					bannerText = "📡 "+lang_qso_location_is_fetched_from_provided_gridsquare+": " + result.callsign_qra.toUpperCase();
					markers.addLayer(marker).addTo(mymap);
				} else {
					mymap.panTo([result.dxcc.lat, result.dxcc.long]);
					mymap.setView([result.dxcc.lat, result.dxcc.long], 8);
					bannerText = "🌍 "+lang_qso_location_is_fetched_from_dxcc_coordinates+": " + $('#dxcc_id option:selected').text();
				}


				// Create and add banner control
				window.mapBanner = L.control({ position: "bottomleft" }); // You can change position: "topleft", "bottomleft", etc.

				window.mapBanner.onAdd = function () {
					const div = L.DomUtil.create("div", "info legend");
					div.style.background = "rgba(0, 0, 0, 0.7)";
					div.style.color = "white";
					div.style.padding = "8px 12px";
					div.style.borderRadius = "8px";
					div.style.fontSize = "13px";
					div.style.boxShadow = "0 2px 6px rgba(0,0,0,0.3)";
					div.innerHTML = bannerText;
					return div;
				};

				window.mapBanner.addTo(mymap);


				/* Find Locator if the field is empty */
				if ($('#locator').val() == "") {
					if (result.callsign_geoloc != 'grid' || result.timesWorked > 0) {
						$('#locator').val(result.callsign_qra);
						$('#locator_info').html(result.bearing);
					}

					if (result.callsign_distance != "" && result.callsign_distance != 0) {
						document.getElementById("distance").value = result.callsign_distance;
					}

					if (result.callsign_qra != "" && (result.callsign_geoloc != 'grid' || result.timesWorked > 0)) {
						if (result.confirmed) {
							$('#locator').addClass("confirmedGrid");
							$('#locator').attr('title', lang_qso_grid_confirmed);
						} else if (result.workedBefore) {
							$('#locator').addClass("workedGrid");
							$('#locator').attr('title', lang_qso_grid_worked);
						} else {
							$('#locator').addClass("newGrid");
							$('#locator').attr('title', lang_qso_grid_new);
						}
					} else {
						$('#locator').removeClass("workedGrid");
						$('#locator').removeClass("confirmedGrid");
						$('#locator').removeClass("newGrid");
						$('#locator').attr('title', '');
					}

				}

				/* Find Operators Name */
				if ($('#qsl_via').val() == "") {
					$('#qsl_via').val(result.qsl_manager);
				}

				/* Find Operators Name */
				if ($('#name').val() == "") {
					$('#name').val(result.callsign_name);
				}

			/* Find Operators E-mail */
			if ($('#email').val() == "") {
				// Validate that we're setting email for the correct callsign
				let currentCallsign = $('#callsign').val().toUpperCase().replaceAll('Ø', '0');
				let resultCallsign = result.callsign.toUpperCase();

				if (currentCallsign === resultCallsign) {
					$('#email').val(result.callsign_email);
				}
			}

			// Show email icon if email is available
			if (result.callsign_email && result.callsign_email.trim() !== "") {
				// Validate callsign match before showing email icon
				let currentCallsign = $('#callsign').val().toUpperCase().replaceAll('Ø', '0');
				let resultCallsign = result.callsign.toUpperCase();

				if (currentCallsign === resultCallsign) {
					$('#email_info').html('<a href="mailto:' + result.callsign_email + '" style="color: inherit; text-decoration: none;"><i class="fas fa-envelope" style="font-size: 20px;"></i></a>');
					$('#email_info').attr('title', lang_qso_send_email_to.replace('%s', result.callsign_email)).removeClass('d-none');
					$('#email_info').show();
				}
			}				if ($('#continent').val() == "") {
					$('#continent').val(result.dxcc.cont);
				}

				if ($('#qth').val() == "") {
					$('#qth').val(result.callsign_qth);
				}

			/* Find link to qrz.com picture */
			if (result.image != "n/a") {
				// Verify that the result still matches the current callsign to prevent stale data
				let currentCallsign = $('#callsign').val().toUpperCase().replaceAll('Ø', '0');
				let resultCallsign = result.callsign.toUpperCase();

				if (currentCallsign !== resultCallsign) {
					// Callsign changed, don't display stale profile data
					return;
				}

				$('#callsign-image-content').html('<img class="callsign-image-pic" href="' + result.image + '" data-fancybox="images" src="' + result.image + '" style="cursor: pointer;">');

				// Store which callsign this profile data belongs to
				$('#callsign-image').attr('data-profile-callsign', resultCallsign);

				// Build comprehensive profile information
				let profileInfo = '';				// Name line: Name Nickname Lastname
				let nameParts = [];
				if (result.profile_fname) nameParts.push(result.profile_fname);
				if (result.profile_nickname) nameParts.push('"' + result.profile_nickname + '"');
				if (result.profile_name_last) nameParts.push(result.profile_name_last);
				if (nameParts.length > 0) {
					profileInfo += '<p class="mb-1"><strong>' + nameParts.join(' ') + '</strong></p>';
				}

				// Aliases
				if (result.profile_aliases) {
					profileInfo += '<p class="mb-1 text-muted" style="font-size: 0.85rem;">' + lang_qso_profile_aliases + ': ' + result.profile_aliases + '</p>';
				}

				// Previous call
				if (result.profile_p_call) {
					profileInfo += '<p class="mb-1 text-muted" style="font-size: 0.85rem;">' + lang_qso_profile_previously + ': ' + result.profile_p_call + '</p>';
				}

				// Address information
				let addressParts = [];
				if (result.profile_addr1) addressParts.push(result.profile_addr1);
				// Zip code before city (addr2), no comma after zip
				if (result.profile_zip && result.profile_addr2) {
					addressParts.push(result.profile_zip + ' ' + result.profile_addr2);
				} else {
					if (result.profile_zip) addressParts.push(result.profile_zip);
					if (result.profile_addr2) addressParts.push(result.profile_addr2);
				}
				if (result.profile_state) addressParts.push(result.profile_state);
				if (result.profile_country) addressParts.push(result.profile_country);

				if (addressParts.length > 0) {
					let addressText = addressParts.join(', ');
					profileInfo += '<p class="mb-1" style="font-size: 0.875rem;"><i class="fas fa-map-marker-alt me-1"></i>' + addressText;

					// Google Maps link if coordinates available with pin marker
					if (result.profile_lat && result.profile_lon) {
						let mapsUrl = 'https://www.google.com/maps/place/' + result.profile_lat + ',' + result.profile_lon + '/@' + result.profile_lat + ',' + result.profile_lon + ',15z/data=!3m1!1e3';
						profileInfo += ' <a href="' + mapsUrl + '" target="_blank" title="' + lang_qso_profile_view_location_maps + '"><i class="fas fa-map"></i></a>';
					}
					profileInfo += '</p>';
				}
				// Email information
				if (result.callsign_email) {
					profileInfo += '<p class="mb-1" style="font-size: 0.875rem;"><i class="fas fa-envelope me-1"></i><a href="mailto:'+result.callsign_email+'">' + result.callsign_email + '</a></p>';
				}

				// Born (with age calculation)
				if (result.profile_born) {
					let currentYear = new Date().getFullYear();
					let age = currentYear - parseInt(result.profile_born);
					profileInfo += '<p class="mb-1" style="font-size: 0.875rem;"><i class="fas fa-birthday-cake me-1"></i>' + lang_qso_profile_born + ': ' + result.profile_born + ' (' + age + ' ' + lang_qso_profile_years_old + ')</p>';
				}

				// License information
				if (result.profile_class || result.profile_efdate || result.profile_expdate) {
					let licenseText = '<i class="fas fa-certificate me-1"></i>';
					if (result.profile_class) {
						// Map common license class codes to readable names
						let licenseMap = {
							'1': lang_qso_profile_license_novice,
							'2': lang_qso_profile_license_technician,
							'3': lang_qso_profile_license_general,
							'4': lang_qso_profile_license_advanced,
							'5': lang_qso_profile_license_extra,
							'E': lang_qso_profile_license_extra,
							'A': lang_qso_profile_license_advanced,
							'G': lang_qso_profile_license_general,
							'T': lang_qso_profile_license_technician,
							'N': lang_qso_profile_license_novice
						};
						let licenseDisplay = licenseMap[result.profile_class] || result.profile_class;
						licenseText += lang_qso_profile_license + ': ' + licenseDisplay;
					}

					if (result.profile_efdate) {
						let efYear = result.profile_efdate.substring(0, 4);
						let yearsLicensed = new Date().getFullYear() - parseInt(efYear);
						licenseText += ' ' + lang_qso_profile_from + ' ' + efYear + ' (' + yearsLicensed + ' ' + lang_qso_profile_years + ')';
					}

					if (result.profile_expdate) {
						let expYear = result.profile_expdate.substring(0, 4);
						let currentYear = new Date().getFullYear();
						if (parseInt(expYear) < currentYear) {
							licenseText += ' <span class="text-danger">' + lang_qso_profile_expired_on + ' ' + expYear + '</span>';
						}
					}

					profileInfo += '<p class="mb-1" style="font-size: 0.875rem;">' + licenseText + '</p>';
				}

					// Website link
					if (result.profile_url) {
						profileInfo += '<p class="mb-1" style="font-size: 0.875rem;"><i class="fas fa-globe me-1"></i><a href="' + result.profile_url + '" target="_blank">' + lang_qso_profile_website + '</a></p>';
					}

					// Local time (will be auto-updated)
					if (result.profile_GMTOffset) {
						let offsetHours = parseFloat(result.profile_GMTOffset);
						let localTime = calculateLocalTime(offsetHours);
						profileInfo += '<p class="mb-1" id="profile-local-time" style="font-size: 0.875rem;"><i class="fas fa-clock me-1"></i>' + lang_qso_profile_local_time + ': ' + localTime + '</p>';

						// Set up auto-update every minute
						setInterval(function() {
							let updatedTime = calculateLocalTime(offsetHours);
							$('#profile-local-time').html('<i class="fas fa-clock me-1"></i>' + lang_qso_profile_local_time + ': ' + updatedTime);
						}, 60000);
					}

					// QSL information
					let qslInfo = '<i class="fas fa-address-card me-1"></i>' + lang_qso_profile_qsl + ': ';
					let qslMethodsIcons = [];

					// Build QSL methods icons list
					// Green checkmark for 1, red cross for 0, question mark for empty
					let eqslIcon = result.profile_eqsl == '1' ? '<i class="fas fa-check-circle text-success"></i>' :
								result.profile_eqsl == '0' ? '<i class="fas fa-times-circle text-danger"></i>' :
								'<i class="fas fa-question-circle text-warning"></i>';
					let lotwIcon = result.profile_lotw == '1' ? '<i class="fas fa-check-circle text-success"></i>' :
								result.profile_lotw == '0' ? '<i class="fas fa-times-circle text-danger"></i>' :
								'<i class="fas fa-question-circle text-warning"></i>';
					let mqslIcon = result.profile_mqsl == '1' ? '<i class="fas fa-check-circle text-success"></i>' :
								result.profile_mqsl == '0' ? '<i class="fas fa-times-circle text-danger"></i>' :
								'<i class="fas fa-question-circle text-warning"></i>';

					qslMethodsIcons.push('QSL: ' + mqslIcon);
					qslMethodsIcons.push('LoTW: ' + lotwIcon);
					qslMethodsIcons.push('eQSL: ' + eqslIcon);

					// Display manager info as-is from QRZ (e.g., "QSL only via LOTW and QRZ.com")
					if (result.profile_qslmgr) {
						qslInfo += result.profile_qslmgr;
						qslInfo += '<br><span style="font-size: 0.85rem;">(' + qslMethodsIcons.join(', ') + ')</span>';
					} else {
						qslInfo += qslMethodsIcons.join(', ');
					}

					profileInfo += '<p class="mb-0" style="font-size: 0.875rem;">' + qslInfo + '</p>';

					// Email information
					if (result.callbook_source) {
						profileInfo += '<p class="mb-1" style="font-size: 0.875rem;"><i class="fas fa-address-book me-1"></i>'+result.callbook_source+'</p>';
					}
					$('#callsign-image-info').html(profileInfo);

					// Show the panel first so we can measure it
					$('#callsign-image').attr('style', 'display: true;');

					// Wait for next frame to ensure rendering, then check available width
					setTimeout(function() {
						checkProfileInfoVisibility();
					}, 10);
				}

					/*
					* Update state with returned value
					*/
				if ($("#stateDropdown").val() == "") {
					$("#stateDropdown").val(result.callsign_state);
				}

				/*
					* Update county with returned value for USA only for now
					* and make sure control is enabled for others
					* with cnty info
					*/
				var dxcc = $('#dxcc_id').val();
				switch (dxcc) {
					case '6':
					case '110':
					case '291':
						selectize_usa_county('#stateDropdown', '#stationCntyInputQso');
						if ($('#stationCntyInputQso').has('option').length == 0 && result.callsign_us_county != "") {
							var county_select = $('#stationCntyInputQso').selectize();
							var county_selectize = county_select[0].selectize;
							county_selectize.addOption({ name: result.callsign_us_county });
							county_selectize.setValue(result.callsign_us_county, false);
						}
						break;
					case '15':
					case '54':
					case '61':
					case '126':
					case '151':
					case '288':
					case '339':
					case '170':
					case '21':
					case '29':
					case '32':
					case '281':
						if (result.callsign_state == "") {
							$("#stationCntyInputQso").prop('disabled', true);
						} else {
							$("#stationCntyInputQso").prop('disabled', false);
							$("#stationCntyInputQso").val(result.callsign_us_county);
						}
						break;
					default:
						$("#stationCntyInputQso").prop('disabled', false);
					}


				if (result.timesWorked != "") {
					if (result.timesWorked == '0') {
						$('#timesWorked').html(lang_qso_title_not_worked_before);
					} else {
						$('#timesWorked').html(result.timesWorked + ' ' + lang_qso_title_times_worked_before);
					}
				} else {
					$('#timesWorked').html(lang_qso_title_previous_contacts);
				}
				if ($('#iota_ref').val() == "") {
					$('#iota_ref').val(result.callsign_iota);
				}
				// Hide the last QSO table
				$('#qso-last-table').hide();
				$('#partial_view').show();
				/* display past QSOs */
				$('#partial_view').html(result.partial);

				// Get DXCC Summary
				loadAwardTabs(function() {
					getDxccResult(result.dxcc.adif, convert_case(result.dxcc.entity));
				});

				// Re-enable Save QSO button and hide fetch status
				clearTimeout(fetchTimeout);
				$('#saveQso').prop('disabled', false);
				$('#fetch_status').hide();

				// Populate pending references from bandmap (after all lookup logic completes)
				// Small delay to ensure DOM is fully updated
				// Use the Map-based approach with the current callsign and expected sequence
				setTimeout(function() {
					populatePendingReferences(currentCallsign, expectedSeq);
				}, 100);
			}

			// Trigger custom event to notify that callsign lookup is complete
			$(document).trigger('callsignLookupComplete');

			// else {
			// 	console.log("Callsigns do not match, skipping lookup");
			// 	console.log("Typed Callsign: " + $('#callsign').val());
			// 	console.log("Returned Callsign: " + result.callsign);
			// }
		}).always(function() {
			// Always re-enable button even if there's an error
			clearTimeout(fetchTimeout);
			// Add short delay to ensure multiselect and all fields are properly populated
			setTimeout(function() {
				$('#saveQso').prop('disabled', false);
				$('#fetch_status').hide();
			}, 300);

			// Reset lookup in progress flag
			lookupInProgress = false;
		});
	} else {
		// Reset QSO fields
		resetDefaultQSOFields();
	}
})

// This function executes the call to the backend for fetching cq summary and inserted table below qso entry
function getCqResult() {
	satOrBand = $('#band').val();
	if ($('#selectPropagation').val() == 'SAT') {
		satOrBand = 'SAT';
	}
	$.ajax({
		url: base_url + 'index.php/lookup/search',
		type: 'post',
		data: {
			type: 'cq',
			cqz: $('#cqz').val(),
			reduced_mode: true,
			current_band: satOrBand,
			current_mode: $('#mode').val(),
		},
		success: function (html) {
            $('#cq-summary').empty();
			$('#cq-summary').append(lang_summary_cq + ' ' + $('#cqz').val() + '.');
            $('#cq-summary').append(html);
		}
	});
}

// This function executes the call to the backend for fetching was summary and inserted table below qso entry
function getWasResult() {
	$('#state-summary').empty();
	if ($('#stateDropdown').val() === '') {
		$('#state-summary').append(lang_summary_warning_empty_state);
		return;
	}

	let dxccid = $('#dxcc_id').val();
	if (!['291', '6', '110'].includes(dxccid)) {
		$('#state-summary').append(lang_summary_state_valid);
		return;
	}
	satOrBand = $('#band').val();
	if ($('#selectPropagation').val() == 'SAT') {
		satOrBand = 'SAT';
	}
	$.ajax({
		url: base_url + 'index.php/lookup/search',
		type: 'post',
		data: {
			type: 'was',
			was: $('#stateDropdown').val(),
			reduced_mode: true,
			current_band: satOrBand,
			current_mode: $('#mode').val(),
		},
		success: function (html) {
			$('#state-summary').append(lang_summary_state + ' ' + $('#stateDropdown').val() + '.');
            $('#state-summary').append(html);
		}
	});
}

// This function executes the call to the backend for fetching sota summary and inserted table below qso entry
function getSotaResult() {
	$('#sota-summary').empty();
	if ($('#sota_ref').val() === '') {
		$('#sota-summary').append(lang_summary_warning_empty_sota);
		return;
	}
	satOrBand = $('#band').val();
	if ($('#selectPropagation').val() == 'SAT') {
		satOrBand = 'SAT';
	}
	$.ajax({
		url: base_url + 'index.php/lookup/search',
		type: 'post',
		data: {
			type: 'sota',
			sota: $('#sota_ref').val(),
			reduced_mode: true,
			current_band: satOrBand,
			current_mode: $('#mode').val(),
		},
		success: function (html) {
			$('#sota-summary').append(lang_summary_sota + ' ' + $('#sota_ref').val() + '.');
            $('#sota-summary').append(html);
		}
	});
}

// This function executes the call to the backend for fetching pota summary and inserted table below qso entry
function getPotaResult() {
	let potaref = $('#pota_ref').val();
	$('#pota-summary').empty();
	if (potaref === '') {
		$('#pota-summary').append(lang_summary_warning_empty_pota);
		return;
	}
	satOrBand = $('#band').val();
	if ($('#selectPropagation').val() == 'SAT') {
		satOrBand = 'SAT';
	}
	if (potaref.includes(',')) {
		let values = potaref.split(',').map(function(v) {
            return v.trim();
        }).filter(function(v) {
            return v;
        });
		let tabContent = $('#pota-summary'); // Tab content container
		tabContent.append('<div class="card"><div class="card-header"><ul style="font-size: 15px;" class="nav nav-tabs card-header-tabs pull-right" id="awardPotaTab" role="tablist"></ul></div></div>');
		tabContent.append('<div class="card-body"><div class="tab-content potatablist"></div>');

		values.forEach(function(value, index) {
			let tabId = `pota-tab-${index}`;
			let contentId = `pota-content-${index}`;

			// Append new tab
			$('#awardPotaTab').append(`
				<li class="nav-item">
					<a class="nav-link ${index === 0 ? 'active' : ''}" id="${tabId}-tab" data-bs-toggle="tab" href="#${contentId}" role="tab" aria-controls="${contentId}" aria-selected="${index === 0}">
						${value.toUpperCase()}
					</a>
				</li>
			`);

			// Append new tab content
			$('.potatablist').append(`
				<div class="tab-pane fade ${index === 0 ? 'show active' : ''}" id="${contentId}" role="tabpanel" aria-labelledby="${tabId}-tab">
				</div>
			`);

			// Make AJAX request
			$.ajax({
				url: base_url + 'index.php/lookup/search',
				type: 'POST',
				data: { type: 'pota',
						pota: value.trim(),
						reduced_mode: true,
						current_band: satOrBand,
						current_mode: $('#mode').val()
					},
				success: function(response) {
					$(`#${contentId}`).html(response); // Append response to correct tab
				},
				error: function(xhr, status, error) {
					$(`#${contentId}`).html(`<div class="text-danger">Error loading data for ${value}</div>`);
				}
			});
		});
		return;
	}
	$.ajax({
		url: base_url + 'index.php/lookup/search',
		type: 'post',
		data: {
			type: 'pota',
			pota: potaref,
			reduced_mode: true,
			current_band: satOrBand,
			current_mode: $('#mode').val(),
		},
		success: function (html) {
			$('#pota-summary').append(lang_summary_pota + ' ' + potaref + '.');
            $('#pota-summary').append(html);
		}
	});
}

// This function executes the call to the backend for fetching continent summary and inserts table below qso entry
function getContinentResult() {
	satOrBand = $('#band').val();
	if ($('#selectPropagation').val() == 'SAT') {
		satOrBand = 'SAT';
	}
	$.ajax({
		url: base_url + 'index.php/lookup/search',
		type: 'post',
		data: {
			type: 'continent',
			continent: $('#continent').val(),
				reduced_mode: true,
				current_band: satOrBand,
				current_mode: $('#mode').val(),
		},
		success: function (html) {
            $('#continent-summary').empty();
			$('#continent-summary').append(lang_summary_continent + ' ' + $('#continent').val() + '.');
            $('#continent-summary').append(html);
		}
	});
}

// This function executes the call to the backend for fetching DOK summary and inserts table below qso entry
function getDokResult() {
	satOrBand = $('#band').val();
	if ($('#selectPropagation').val() == 'SAT') {
		satOrBand = 'SAT';
	}
	$('#dok-summary').empty();
	if ($('#darc_dok').val() === '') {
		$('#dok-summary').append(lang_summary_warning_empty_dok);
		return;
	}
	$.ajax({
		url: base_url + 'index.php/lookup/search',
		type: 'post',
		data: {
			type: 'dok',
			dok: $('#darc_dok').val(),
				reduced_mode: true,
				current_band: satOrBand,
				current_mode: $('#mode').val(),
		},
		success: function (html) {
			$('#dok-summary').append(lang_summary_dok + ' ' + $('#darc_dok').val() + '.');
            $('#dok-summary').append(html);
		}
	});
}

// This function executes the call to the backend for fetching SAT summary and inserts table below qso entry
function getSatResult() {
	$('#sat-summary').empty();
	if ($('#selectPropagation').val() != 'SAT') {
		$('#sat-summary').append(lang_summary_warning_empty_sat);
		return;
	}
	$.ajax({
		url: base_url + 'index.php/lookup/sat',
		type: 'post',
		data: {
			callsign: $('#callsign').val().replace('Ø', '0'),
		},
		success: function (html) {
			$('#sat-summary').append(lang_summary_sat + ' ' + $('#callsign').val().toUpperCase() + '.');
			$('#sat-summary').append(html);
		}
	});
}

// This function executes the call to the backend for fetching iota summary and inserts table below qso entry
function getIotaResult() {
	satOrBand = $('#band').val();
	if ($('#selectPropagation').val() == 'SAT') {
		satOrBand = 'SAT';
	}
	$('#iota-summary').empty();
	if ($('#iota_ref').val() === '') {
		$('#iota-summary').append(lang_summary_warning_empty_iota);
		return;
	}
	$.ajax({
		url: base_url + 'index.php/lookup/search',
		type: 'post',
		data: {
			type: 'iota',
			iota: $('#iota_ref').val(),
				reduced_mode: true,
				current_band: satOrBand,
				current_mode: $('#mode').val(),
		},
		success: function (html) {
			$('#iota-summary').append(lang_summary_iota + ' ' + $('#iota_ref').val() + '.');
            $('#iota-summary').append(html);
		}
	});
}

// This function executes the call to the backend for fetching wwff summary and inserts table below qso entry
function getWwffResult() {
	$('#wwff-summary').empty();
	if ($('#wwff_ref').val() === '') {
		$('#wwff-summary').append(lang_summary_warning_empty_wwff);
		return;
	}
	satOrBand = $('#band').val();
	if ($('#selectPropagation').val() == 'SAT') {
		satOrBand = 'SAT';
	}
	$.ajax({
		url: base_url + 'index.php/lookup/search',
		type: 'post',
		data: {
			type: 'wwff',
			wwff: $('#wwff_ref').val(),
				reduced_mode: true,
				current_band: satOrBand,
				current_mode: $('#mode').val(),
		},
		success: function (html) {
			$('#wwff-summary').append(lang_summary_wwff + ' ' + $('#wwff_ref').val() + '.');
            $('#wwff-summary').append(html);
		}
	});
}

// This function executes the call to the backend for fetching gridsquare summary and inserts table below qso entry
function getGridsquareResult() {
	$('#gridsquare-summary').empty();
	if ($('#locator').val() === '') {
		$('#gridsquare-summary').append(lang_summary_warning_empty_gridsquare);
		return;
	}
	satOrBand = $('#band').val();
	if ($('#selectPropagation').val() == 'SAT') {
		satOrBand = 'SAT';
	}
	if ($('#locator').val().includes(',')) {
		let values = $('#locator').val().split(',').map(function(v) {
            return v.trim();
        }).filter(function(v) {
            return v;
        });
		let tabContent = $('#gridsquare-summary'); // Tab content container
		tabContent.append('<div class="card"><div class="card-header"><ul style="font-size: 15px;" class="nav nav-tabs card-header-tabs pull-right" id="awardGridTab" role="tablist"></ul></div></div>');
		tabContent.append('<div class="card-body"><div class="tab-content gridtablist"></div>');

		values.forEach(function(value, index) {
			let tabId = `grid-tab-${index}`;
			let contentId = `grid-content-${index}`;

			// Append new tab
			$('#awardGridTab').append(`
				<li class="nav-item">
					<a class="nav-link ${index === 0 ? 'active' : ''}" id="${tabId}-tab" data-bs-toggle="tab" href="#${contentId}" role="tab" aria-controls="${contentId}" aria-selected="${index === 0}">
						${value.toUpperCase()}
					</a>
				</li>
			`);

			// Append new tab content
			$('.gridtablist').append(`
				<div class="tab-pane fade ${index === 0 ? 'show active' : ''}" id="${contentId}" role="tabpanel" aria-labelledby="${tabId}-tab">
				</div>
			`);

			// Make AJAX request
			$.ajax({
				url: base_url + 'index.php/lookup/search',
				type: 'POST',
				data: { type: 'vucc',
						grid: value.trim(),
						reduced_mode: true,
						current_band: satOrBand,
						current_mode: $('#mode').val()
					},
				success: function(response) {
					$(`#${contentId}`).html(response); // Append response to correct tab
				},
				error: function(xhr, status, error) {
					$(`#${contentId}`).html(`<div class="text-danger">Error loading data for ${value}</div>`);
				}
			});
		});
		return;
	}
	$.ajax({
		url: base_url + 'index.php/lookup/search',
		type: 'post',
		data: {
			type: 'vucc',
			grid: $('#locator').val(),
				reduced_mode: true,
				current_band: satOrBand,
				current_mode: $('#mode').val(),
		},
		success: function (html) {
			$('#gridsquare-summary').append(lang_summary_gridsquare + ' ' + $('#locator').val().substring(0, 4) + '.');
            $('#gridsquare-summary').append(html);
		}
	});
}

function loadAwardTabs(callback) {
    $.ajax({
        url: base_url + 'index.php/qso/getAwardTabs',
        type: 'post',
        data: {},
        success: function (html) {
            $('.awardpane').remove();
            $('.qsopane').append('<div class="awardpane col-sm-12"></div>');
            $('.awardpane').append(html);

            // Execute callback if provided
            if (typeof callback === "function") {
                callback();
            }

			$("a[href='#cq-summary']").on('shown.bs.tab', function (e) {
				let $targetPane = $('#cq-summary');

				if (!$targetPane.data("loaded")) {
					$targetPane.data("loaded", true); // Mark as loaded
					getCqResult();
				}
			});

			$("a[href='#state-summary']").on('shown.bs.tab', function(e) {
				let $targetPane = $('#state-summary');

				if (!$targetPane.data("loaded")) {
					$targetPane.data("loaded", true); // Mark as loaded
					getWasResult();
				}
			});

			$("a[href='#pota-summary']").on('shown.bs.tab', function(e) {
				let $targetPane = $('#pota-summary');

				if (!$targetPane.data("loaded")) {
					$targetPane.data("loaded", true); // Mark as loaded
					getPotaResult();
				}
			});

			$("a[href='#continent-summary']").on('shown.bs.tab', function(e) {
				let $targetPane = $('#continent-summary');

				if (!$targetPane.data("loaded")) {
					$targetPane.data("loaded", true); // Mark as loaded
					getContinentResult();
				}
			});

			$("a[href='#sota-summary']").on('shown.bs.tab', function(e) {
				let $targetPane = $('#sota-summary');

				if (!$targetPane.data("loaded")) {
					$targetPane.data("loaded", true); // Mark as loaded
					getSotaResult();
				}
			});

			$("a[href='#gridsquare-summary']").on('shown.bs.tab', function(e) {
				let $targetPane = $('#gridsquare-summary');

				if (!$targetPane.data("loaded")) {
					$targetPane.data("loaded", true); // Mark as loaded
					getGridsquareResult();
				}
			});

			$("a[href='#wwff-summary']").on('shown.bs.tab', function(e) {
				let $targetPane = $('#wwff-summary');

				if (!$targetPane.data("loaded")) {
					$targetPane.data("loaded", true); // Mark as loaded
					getWwffResult();
				}
			});

			$("a[href='#iota-summary']").on('shown.bs.tab', function(e) {
				let $targetPane = $('#iota-summary');

				if (!$targetPane.data("loaded")) {
					$targetPane.data("loaded", true); // Mark as loaded
					getIotaResult();
				}
			});

			$("a[href='#sat-summary']").on('shown.bs.tab', function(e) {
				let $targetPane = $('#sat-summary');

				if (!$targetPane.data("loaded")) {
					$targetPane.data("loaded", true); // Mark as loaded
					getSatResult();
				}
			});

			$("a[href='#dok-summary']").on('shown.bs.tab', function(e) {
				let $targetPane = $('#dok-summary');

				if (!$targetPane.data("loaded")) {
					$targetPane.data("loaded", true); // Mark as loaded
					getDokResult();
				}
			});

			$('.dxcc-summary-reload').click(function (event) {
				let $targetPane = $('#dxcc-summary');
				$targetPane.data("loaded", false); // Mark as loaded
				getDxccResult($('#dxcc_id').val(), $('#dxcc_id option:selected').text());
			});
			$('.iota-summary-reload').click(function (event) {
				getIotaResult();
			});
			$('.dok-summary-reload').click(function (event) {
				getDokResult();
			});
			$('.wwff-summary-reload').click(function (event) {
				getWwffResult();
			});
			$('.pota-summary-reload').click(function (event) {
				getPotaResult();
			});
			$('.sota-summary-reload').click(function (event) {
				getSotaResult();
			});
			$('.cq-summary-reload').click(function (event) {
				getCqResult();
			});
			$('.state-summary-reload').click(function (event) {
				getWasResult();
			});
			$('.continent-summary-reload').click(function (event) {
				getContinentResult();
			});
			$('.gridsquare-summary-reload').click(function (event) {
				getGridsquareResult();
			});
			$('.sat-summary-reload').click(function (event) {
				getSatResult();
			});
        }
    });
}


/* time input shortcut */
$('#start_time').on('change', function () {
	var raw_time = $(this).val();
	if (raw_time.match(/^\d\[0-6]d$/)) {
		raw_time = "0" + raw_time;
	}
	if (raw_time.match(/^[012]\d[0-5]\d$/)) {
		raw_time = raw_time.substring(0, 2) + ":" + raw_time.substring(2, 4);
		$('#start_time').val(raw_time);
	}
});

$('#end_time').on('change', function () {
	var raw_time = $(this).val();
	if (raw_time.match(/^\d\[0-6]d$/)) {
		raw_time = "0" + raw_time;
	}
	if (raw_time.match(/^[012]\d[0-5]\d$/)) {
		raw_time = raw_time.substring(0, 2) + ":" + raw_time.substring(2, 4);
		$('#end_time').val(raw_time);
	}
});

/* date input shortcut */
$('#start_date').on('change', function () {
	raw_date = $(this).val();
	if (raw_date.match(/^[12]\d{3}[01]\d[0123]\d$/)) {
		raw_date = raw_date.substring(0, 4) + "-" + raw_date.substring(4, 6) + "-" + raw_date.substring(6, 8);
		$('#start_date').val(raw_date);
	}
});

/* on mode change */
$('.mode').on('change', function () {
	if ($('#radio').val() == 0 && $('#sat_name').val() == '') {
		// Only fetch default frequency if frequency field is empty
		if ($('#frequency').val() == '' || $('#frequency').val() == null) {
			$.get(base_url + 'index.php/qso/band_to_freq/' + $('#band').val() + '/' + $('.mode').val(), function (result) {
				$('#frequency').val(result).trigger("change");
			});
		}
		$('#frequency_rx').val("");
	}
	$("#callsign").blur();
});

/* Calculate Frequency */
/* on band change */
$('#band').on('change', function () {
	if ($('#radio').val() == 0) {
		$.get(base_url + 'index.php/qso/band_to_freq/' + $(this).val() + '/' + $('.mode').val(), function (result) {
			$('#frequency').val(result).trigger("change");

			// Update virtual CAT state when not using CAT
			if (typeof isCATAvailable === 'function' && !isCATAvailable()) {
				if (typeof window.catState === 'undefined' || window.catState === null) {
					window.catState = {};
				}
				window.catState.frequency = parseFloat(result); // Hz
				window.catState.mode = $('.mode').val();
				window.catState.lastUpdate = Date.now();

				// Update relevant spots for the new band/frequency
				if (typeof dxWaterfall !== 'undefined' && dxWaterfall && typeof dxWaterfall.collectAllBandSpots === 'function') {
					dxWaterfall.collectAllBandSpots(true);
				}
			}
		});
	}
	$('#frequency_rx').val("");
	$('#band_rx').val("");
	$("#selectPropagation").val("");
	$("#sat_name").val("");
	$("#sat_mode").val("");
	set_qrg();
	$("#callsign").blur();
	stop_az_ele_ticker();
});

/* On Key up Calculate Bearing and Distance */
$("#locator").on("input focus", function () {
	if ($(this).val()) {
		var qra_input = $(this).val();

		var qra_lookup = qra_input.substring(0, 4);

		if (qra_lookup.length >= 4) {

			// Check Log if satname is provided
			if ($("#sat_name").val() != "") {

				//logbook/jsonlookupgrid/io77/SAT/0/0

				$.getJSON(base_url + 'index.php/logbook/jsonlookupgrid/' + qra_lookup.toUpperCase() + '/SAT/0/0', function (result) {
					// Reset CSS values before updating
					$('#locator').removeClass("confirmedGrid");
					$('#locator').removeClass("workedGrid");
					$('#locator').removeClass("newGrid");
					$('#locator').attr('title', '');

					if (result.confirmed) {
						$('#locator').addClass("confirmedGrid");
						$('#locator').attr('title', lang_qso_grid_confirmed);
					} else if (result.workedBefore) {
						$('#locator').addClass("workedGrid");
						$('#locator').attr('title', lang_qso_grid_worked);
					} else {
						$('#locator').addClass("newGrid");
						$('#locator').attr('title', lang_qso_grid_new);
					}
				})
			} else {
				$.getJSON(base_url + 'index.php/logbook/jsonlookupgrid/' + qra_lookup.toUpperCase() + '/0/' + $("#band").val() + '/' + $("#mode").val(), function (result) {
					// Reset CSS values before updating
					$('#locator').removeClass("confirmedGrid");
					$('#locator').removeClass("workedGrid");
					$('#locator').removeClass("newGrid");
					$('#locator').attr('title', '');

					if (result.confirmed) {
						$('#locator').addClass("confirmedGrid");
						$('#locator').attr('title', lang_qso_grid_confirmed);
					} else if (result.workedBefore) {
						$('#locator').addClass("workedGrid");
						$('#locator').attr('title', lang_qso_grid_worked);
					} else {
						$('#locator').addClass("newGrid");
						$('#locator').attr('title', lang_qso_grid_new);
					}

				})
			}
		}

		if (qra_input.length >= 4 && $(this).val().length > 0) {
			let qra = $(this).val().toUpperCase();
			$.ajax({
				url: base_url + 'index.php/logbook/qralatlngjson',
				type: 'post',
				data: {
					qra: qra,
				},
				success: function (data) {
					// Set Map to Lat/Long
					result = JSON.parse(data);
					markers.clearLayers();
					if (typeof result[0] !== "undefined" && typeof result[1] !== "undefined") {
						var redIcon = L.icon({
							iconUrl: icon_dot_url,
							iconSize: [18, 18], // size of the icon
						});

						var marker = L.marker([result[0], result[1]], { icon: redIcon });
						mymap.setZoom(8);
						mymap.panTo([result[0], result[1]]);
						mymap.setView([result[0], result[1]], 8);
						markers.addLayer(marker).addTo(mymap);
						bannerText = "📡 Location is fetched from provided gridsquare: " + qra;
						window.mapBanner.addTo(mymap);
					}
				},
				error: function () {
				},
			});

			$.ajax({
				url: base_url + 'index.php/logbook/searchbearing',
				type: 'post',
				data: {
					grid: $(this).val(),
					ant_path: $('#ant_path').val(),
					stationProfile: $('#stationProfile').val()
				},
				success: function (data) {
					$('#locator_info').html(data).fadeIn("slow");
				},
				error: function () {
					$('#locator_info').text(lang_qso_error_loading_bearing).fadeIn("slow");
				},
			});
			$.ajax({
				url: base_url + 'index.php/logbook/searchdistance',
				type: 'post',
				data: {
					grid: $(this).val(),
					ant_path: $('#ant_path').val(),
					stationProfile: $('#stationProfile').val()
				},
				success: function (data) {
					document.getElementById("distance").value = data;
				},
				error: function () {
					document.getElementById("distance").value = null;
				},
			});
		}
	}
});

$("#locator").on("focusout", function () {
	if ($(this).val().length == 0) {
		$('#locator_info').text("");
		document.getElementById("distance").value = null;
	}
});

// Update email icon when email field changes
$("#email").on("input focusout", function () {
	var emailValue = $(this).val().trim();
	if (emailValue !== "") {
		$('#email_info').html('<a href="mailto:' + emailValue + '" style="color: inherit; text-decoration: none;"><i class="fas fa-envelope" style="font-size: 20px;"></i></a>');
		$('#email_info').attr('title', lang_qso_send_email_to.replace('%s', emailValue)).removeClass('d-none');
		$('#email_info').show();
	} else {
		$('#email_info').addClass('d-none').hide();
		$('#email_info').html('');
	}
});

$("#ant_path").on("change", function () {
	if ($("#locator").val().length > 0) {
		$.ajax({
			url: base_url + 'index.php/logbook/searchbearing',
			type: 'post',
			data: {
				grid: $('#locator').val(),
				ant_path: $('#ant_path').val(),
				stationProfile: $('#stationProfile').val()
			},
			success: function (data) {
				$('#locator_info').html(data).fadeIn("slow");
			},
			error: function () {
				$('#locator_info').text(lang_qso_error_loading_bearing).fadeIn("slow");
			},
		});
		$.ajax({
			url: base_url + 'index.php/logbook/searchdistance',
			type: 'post',
			data: {
				grid: $('#locator').val(),
				ant_path: $('#ant_path').val(),
				stationProfile: $('#stationProfile').val()
			},
			success: function (data) {
				$('#distance').val(data);
			},
			error: function () {
				$('#distance').val("");
			},
		});
	}
});

// Change report based on mode
$('.mode').on('change', function () {
	setRst($('.mode').val());
});

function convert_case(str) {
	var lower = str.toLowerCase();
	return lower.replace(/(^| )(\w)/g, function (x) {
		return x.toUpperCase();
	});
}

$('#dxcc_id').on('change', function () {
	const dxccadif=$(this).val();
	$.getJSON(base_url + 'index.php/logbook/jsonentity/' + dxccadif, function (result) {
		if (result.dxcc.name != undefined) {

			$('#country').val(convert_case(result.dxcc.name));
			$('#cqz').val(convert_case(result.dxcc.cqz));

			$('#callsign_info').removeClass("text-bg-secondary");
			$('#callsign_info').removeClass("text-bg-success");
			$('#callsign_info').removeClass("text-bg-danger");
			$('#callsign_info').attr('title', '');
			$('#callsign_info').text(convert_case(result.dxcc.name));

			changebadge(dxccadif);

			// Set Map to Lat/Long it locator is not empty
			if ($('#locator').val() == "") {
				var redIcon = L.icon({
					iconUrl: icon_dot_url,
					iconSize: [18, 18], // size of the icon
				});

				markers.clearLayers();
				mymap.setZoom(8);
				mymap.panTo([result.dxcc.lat, result.dxcc.long]);
				bannerText = "🌍 Location is fetched from DXCC coordinates (no gridsquare provided): " + $('#dxcc_id option:selected').text();
				window.mapBanner.addTo(mymap);
			}
		}
	});
});

//Spacebar moves to the name field when you're entering a callsign
//Similar to contesting ux, good for pileups.
$("#callsign").on("keydown", function (e) {
	if (e.which == 32) {
		$("#name").trigger("focus");
		e.preventDefault(); //Eliminate space char
	}
});


$("#callsign").on("input focus", function () {
	var ccall = $(this).val();
	if ($(this).val().length >= 3) {
		$('.callsign-suggest').show();
		$callsign = $(this).val().replace('Ø', '0');
		if (scps.filter((call => call.includes($(this).val().toUpperCase()))).length <= 0) {
			$.ajax({
				url: 'lookup/scp',
				method: 'POST',
				data: {
					callsign: $callsign.toUpperCase()
				},
				success: function (result) {
					$('.callsign-suggestions').text(result);
					scps = result.split(" ");
					highlightSCP(ccall.toUpperCase());
				}
			});
		} else {
			$('.callsign-suggestions').text(scps.filter((call) => call.includes($(this).val().toUpperCase())).join(' '));
			highlightSCP(ccall.toUpperCase());
		}
	} else {
		$('.callsign-suggest').hide();
		scps = [];
	}
});

RegExp.escape = function (text) {
	return String(text).replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&");
}


function highlightSCP(term, base) {
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


//Reset QSO form Fields function
function resetDefaultQSOFields() {
	$('#callsign_info').text("");
	$('#locator_info').text("");
	$('#lotw_support').text("");
	$('#lotw_support').removeClass();
	$('#country').val("");
	$('#continent').val("");
	$("#distance").val("");
	$('#email').val("");
	$('#email_info').html("").addClass('d-none').hide();
	$('#region').val("");
	$('#dxcc_id').val("").multiselect('refresh');
	$('#cqz').val("");
	$('#ituz').val("");
	$('#name').val("");
	$('#qth').val("");
	$('#locator').val("");
	$('#iota_ref').val("");

	// Clear Selectize fields properly
	if ($('#sota_ref')[0] && $('#sota_ref')[0].selectize) {
		$('#sota_ref')[0].selectize.clear();
	}
	if ($('#pota_ref')[0] && $('#pota_ref')[0].selectize) {
		$('#pota_ref')[0].selectize.clear();
	}
	if ($('#wwff_ref')[0] && $('#wwff_ref')[0].selectize) {
		$('#wwff_ref')[0].selectize.clear();
	}

	$("#locator").removeClass("workedGrid");
	$("#locator").removeClass("confirmedGrid");
	$("#locator").removeClass("newGrid");
	$("#callsign").removeClass("workedGrid");
	$("#callsign").removeClass("confirmedGrid");
	$("#callsign").removeClass("newGrid");
	$('#callsign_info').removeClass("text-bg-secondary");
	$('#callsign_info').removeClass("text-bg-success");
	$('#callsign_info').removeClass("text-bg-danger");
	$('#stateDropdown').val("");
	$('#callsign-image').attr('style', 'display: none;');
	$('#callsign-image-content').text("");
	$('.awardpane').remove();
	$('#timesWorked').html(lang_qso_title_previous_contacts);

	setNotesVisibility(0); // Set default note card visibility to 0 (hidden)
}

function closeModal() {
	var container = document.getElementById("modals-here")
	var backdrop = document.getElementById("modal-backdrop")
	var modal = document.getElementById("modal")

	modal.classList.remove("show")
	backdrop.classList.remove("show")

	setTimeout(function () {
		container.removeChild(backdrop)
		container.removeChild(modal)
	}, 200)
}

// [TimeOff] test Consistency timeOff value (concidering start and end are between 23:00 and 00:59) //
function testTimeOffConsistency() {
	var _start_time = $('#qso_input input[name="start_time"]').val();
	var _end_time = $('#qso_input input[name="end_time"]').val();
	$('#qso_input input[name="end_time"]').removeClass('inputError');
	$('#qso_input .warningOnSubmit').hide();
	$('#qso_input .warningOnSubmit_txt').empty();
	if (!((parseInt(_start_time.replaceAll(':', '')) <= parseInt(_end_time.replaceAll(':', '')))
		|| ((_start_time.substring(0, 2) == "23") && (_end_time.substring(0, 2) == "00")))) {
		$('#qso_input input[name="end_time"]').addClass('inputError');
		$('#qso_input .warningOnSubmit_txt').html(text_error_timeoff_less_timeon);
		$('#qso_input .warningOnSubmit').show();
		$('#qso_input input[name="end_time"]').off('change').on('change', function () { testTimeOffConsistency(); });
		return false;
	}
	return true;
}

function panMap(stationProfileIndex) {
	$.ajax({
		url: base_url + 'index.php/station/stationProfileCoords/'+stationProfileIndex,
		type: 'get',
		success: function(data) {
			result = JSON.parse(data);
			if (typeof result[0] !== "undefined" && typeof result[1] !== "undefined") {
				mymap.panTo([result[0], result[1]]);
				pos = result;
			}
		},
		error: function() {
		},
	});
}

function clearQrgUnits() {
	Object.keys(localStorage)
		.filter(k => k.startsWith('qrgunit'))
		.forEach(k => localStorage.removeItem(k));
}

$(document).ready(function () {
	qrg_inputtype();
	clearTimeout();
	set_timers();
	updateStateDropdown('#dxcc_id', '#stateInputLabel', '#location_us_county', '#stationCntyInputQso');

	setNotesVisibility(0); // Set default note card visibility to 0 (hidden)

	// Clear the localStorage for the qrg units, except the quicklogCallsign and a possible backlog
	clearQrgUnits();
	set_qrg();

	$("#locator").popover({ placement: 'top', title: lang_qso_gridsquare_formatting, content: lang_qso_gridsquare_help })
	.focus(function () {
		$('#locator').popover('show');
	})
	.blur(function () {
		$('#locator').popover('hide');
	});

	$('#dxcc_id').multiselect({
		// template is needed for bs5 support
		templates: {
			button: '<button type="button" style="text-align: left !important;" class="multiselect dropdown-toggle btn btn-secondary w-auto" data-bs-toggle="dropdown" aria-expanded="false"><span class="multiselect-selected-text"></span></button>',
		},
		enableFiltering: true,
		enableFullValueFiltering: false,
		enableCaseInsensitiveFiltering: true,
		filterPlaceholder: lang_general_word_search,
		widthSynchronizationMode: 'always',
		numberDisplayed: 1,
		inheritClass: true,
		buttonWidth: '100%',
		maxHeight: 600
	});

	$('.multiselect-container .multiselect-filter', $('#dxcc_id').parent()).css({
		'position': 'sticky', 'top': '0px', 'z-index': 1, 'background-color': 'inherit', 'width': '100%', 'height': '37px'
	})

	$('#notice-alerts').delay(1000).fadeOut(5000);

	$('.callsign-suggest').hide();

	setRst($(".mode").val());

	/* On Page Load */
	var catcher = function () {
		var changed = false;
		$('form').each(function () {
			if ($(this).data('initialForm') != $(this).serialize()) {
				changed = true;
				$(this).addClass('changed');
			} else {
				$(this).removeClass('changed');
			}
		});
		if (changed) {
			return 'Unsaved QSO!';
		}
	};

	// Callsign always has focus on load
	$("#callsign").trigger("focus");

	// reset the timers on page load
	resetTimers(qso_manual);

	get_fav();

	$('#sota_ref').selectize({
		maxItems: 1,
		closeAfterSelect: true,
		loadThrottle: 250,
		valueField: 'name',
		labelField: 'name',
		searchField: 'name',
		options: [],
		create: true,
		load: function (query, callback) {
			if (!query || query.length < 3) return callback();  // Only trigger if 3 or more characters are entered
			$.ajax({
				url: base_url + 'index.php/qso/get_sota',
				type: 'GET',
				dataType: 'json',
				data: {
					query: query,
				},
				error: function () {
					callback();
				},
				success: function (res) {
					callback(res);
				}
			});
		},
		onChange: function (value) {
			if (value !== '') {
				$('#sota_info').show();
				$('#sota_info').html('<a target="_blank" href="https://summits.sota.org.uk/summit/' + value + '"><img width="32" height="32" src="' + base_url + 'images/icons/sota.org.uk.png"></a>');
				$('#sota_info').attr('title', lang_qso_lookup_summit_info.replace('%s', value).replace('%s', 'sota.org.uk'));
			} else {
				$('#sota_info').hide();
			}
		}
	});

	$('#wwff_ref').selectize({
		maxItems: 1,
		closeAfterSelect: true,
		loadThrottle: 250,
		valueField: 'name',
		labelField: 'name',
		searchField: 'name',
		options: [],
		create: true,
		load: function (query, callback) {
			if (!query || query.length < 3) return callback();  // Only trigger if 3 or more characters are entered
			$.ajax({
				url: base_url + 'index.php/qso/get_wwff',
				type: 'GET',
				dataType: 'json',
				data: {
					query: query,
				},
				error: function () {
					callback();
				},
				success: function (res) {
					callback(res);
				}
			});
		},
		onChange: function (value) {
			if (value !== '') {
				$('#wwff_info').show();
				$('#wwff_info').html('<a target="_blank" href="https://www.cqgma.org/zinfo.php?ref=' + value + '"><img width="32" height="32" src="' + base_url + 'images/icons/wwff.co.png"></a>');
				$('#wwff_info').attr('title', lang_qso_lookup_reference_info.replace('%s', value).replace('%s', 'cqgma.org'));
			} else {
				$('#wwff_info').hide();
			}
		}
	});

	$('#pota_ref').selectize({
		maxItems: null,
		closeAfterSelect: true,
		loadThrottle: 250,
		valueField: 'name',
		labelField: 'name',
		searchField: 'name',
		options: [],
		create: true,
		load: function (query, callback) {
			if (!query || query.length < 3) return callback();  // Only trigger if 3 or more characters are entered
			$.ajax({
				url: base_url + 'index.php/qso/get_pota',
				type: 'GET',
				dataType: 'json',
				data: {
					query: query,
				},
				error: function () {
					callback();
				},
				success: function (res) {
					callback(res);
				}
			});
		},
		onChange: function (value) {
			if (value !== '' && value.indexOf(',') === -1) {
				$('#pota_info').show();
				$('#pota_info').html('<a target="_blank" href="https://pota.app/#/park/' + value + '"><img width="32" height="32" src="' + base_url + 'images/icons/pota.app.png"></a>');
				$('#pota_info').attr('title', lang_qso_lookup_reference_info.replace('%s', value).replace('%s', 'pota.co'));
			} else {
				$('#pota_info').hide();
			}
		}
	});

	$('#darc_dok').selectize({
		maxItems: 1,
		closeAfterSelect: true,
		loadThrottle: 250,
		valueField: 'name',
		labelField: 'name',
		searchField: 'name',
		options: [],
		create: true,
		load: function (query, callback) {
			if (!query) return callback();  // Only trigger if at least 1 character is entered
			$.ajax({
				url: base_url + 'index.php/qso/get_dok',
				type: 'GET',
				dataType: 'json',
				data: {
					query: query,
				},
				error: function () {
					callback();
				},
				success: function (res) {
					callback(res);
				}
			});
		}
	});

	/*
	Populate the Satellite Names Field on the QSO Panel
	*/
	$.getJSON(site_url + "/satellite/satellite_data", function (data) {

		// Build the options array
		var items = [];
		$.each(data, function (key, val) {
			items.push(
				'<option value="' + key + '">' + key + '</option>'
			);
		});

		// Add to the datalist
		$('.satellite_names_list').append(items.join(""));
	});

	// Only set the frequency when not set by userdata/PHP.
	if ($('#frequency').val() == "") {
		$.get(base_url + 'index.php/qso/band_to_freq/' + $('#band').val() + '/' + $('.mode').val(), function (result) {
			$('#frequency').val(result).trigger("change");
			$('#frequency_rx').val("");
			set_qrg();
		});
	}

	// Handle manual frequency entry - DO NOT tune radio (form follows radio, not vice versa)
	$('#freq_calculated').on('change', function() {
		// Skip if CAT is currently updating - don't interfere with radio updates
		if (typeof cat_updating_frequency !== 'undefined' && cat_updating_frequency) {
			return;
		}

		// set_new_qrg() is defined in qrg_handler.js and will:
		// 1. Parse the frequency value and convert to Hz
		// 2. Update #frequency (hidden field)
		// 3. Update #band selector to match the frequency
		// NOTE: Does NOT tune the radio - manual form changes are display-only
		if (typeof set_new_qrg === 'function') {
			set_new_qrg();
		}
	});

	$('#freq_calculated').on('keydown', function(event) {
		// Check if Enter key was pressed
		if (event.key === 'Enter' || event.keyCode === 13) {
			event.preventDefault(); // Prevent form submission
			// Move focus to next field (optional - mimics typical form behavior)
			$(this).blur();
		}
	});

	// Edit button click handler for inline editing
	$(document).on('click', '#callsign-note-edit-btn', function() {
		var $editorElem = $('#callsign_note_content');
		var noteEditor = $editorElem.data('easymde');
		var $saveBtn = $('#callsign-note-save-btn');
		var $editBtn = $('#callsign-note-edit-btn');
		var noteId = $('#callsign-note-id').val();

		if (noteEditor) {
			// Switch to edit mode
			noteEditor.codemirror.setOption('readOnly', false);
			if (noteEditor.isPreviewActive()) {
				noteEditor.togglePreview(); // Exit preview mode
			}

			// If no note exists (state 1), set dynamic timestamp content
			if (!noteId || noteId === '') {
				var timestamp = new Date().toLocaleString();
				noteEditor.value('#' + timestamp + '\n');
				noteEditor.codemirror.refresh();
			}

			// Show toolbar and buttons
			document.querySelector('.EasyMDEContainer .editor-toolbar').style.display = '';
			$saveBtn.removeClass('d-none').show();
			$editBtn.addClass('d-none').hide();
		}
	});

	// Save button click handler for saving notes
	$(document).on('click', '#callsign-note-save-btn', function() {
		var $editorElem = $('#callsign_note_content');
		var noteEditor = $editorElem.data('easymde');
		var noteId = $('#callsign-note-id').val();
		var callsign = $('#callsign').val().trim();
		var noteContent = noteEditor ? noteEditor.value() : '';

		if (!callsign || callsign.length < 3) {
			return;
		}

		var isEdit = noteId && noteId !== '';
		var url = isEdit ?
			window.base_url + 'index.php/notes/save/' + noteId :
			window.base_url + 'index.php/notes/save';

		var postData = {
			category: 'Contacts',
			title: callsign,
			content: noteContent
		};

		if (isEdit) {
			postData.id = noteId;
		}

		$.post(url, postData)
			.done(function(response) {
				if (typeof response === 'string') {
					try { response = JSON.parse(response); } catch (e) { response = {}; }
				}

				if (response.success || response.status === 'ok') {
					// Check if note was deleted (empty content)
					if (response.deleted) {
						// Clear the note ID since note was deleted
						$('#callsign-note-id').val('');
						// Reset to state 1 (callsign, no note)
						setNotesVisibility(1);
						// Show success message
						showToast(lang_qso_note_toast_title, lang_qso_note_deleted);
					} else {
						// Success - switch back to preview mode
						if (noteEditor) {
							noteEditor.codemirror.setOption('readOnly', true);
							if (!noteEditor.isPreviewActive()) {
								noteEditor.togglePreview(); // Switch to preview mode
							}
							document.querySelector('.EasyMDEContainer .editor-toolbar').style.display = 'none';
						}
						$('#callsign-note-save-btn').addClass('d-none').hide();
						$('#callsign-note-edit-btn').removeClass('d-none').show();

						// If it was a new note, store the returned ID
						if (!isEdit && response.id) {
							$('#callsign-note-id').val(response.id);

							// Show success message briefly
							showToast(lang_qso_note_toast_title, lang_qso_note_created);
						} else {
							// Show success message briefly
							showToast(lang_qso_note_toast_title, lang_qso_note_saved);
						}


					}
				} else {
					alert(lang_qso_note_error_saving + ': ' + (response.message || lang_general_word_error));
				}
			})
			.fail(function() {
				alert(lang_qso_note_error_saving);
			});
	});

	// everything loaded and ready 2 go
	bc.postMessage('ready');
});
