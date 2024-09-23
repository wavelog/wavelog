$( document ).ready(function() {
	clearTimeout();
	set_timers();
	updateStateDropdown('#dxcc_id', '#stateInputLabel', '#location_us_county', '#stationCntyInputQso');

	// if the dxcc id changes we need to update the state dropdown and clear the county value to avoid wrong data
	$("#dxcc_id").change(function () {
		updateStateDropdown('#dxcc_id', '#stateInputLabel', '#location_us_county', '#stationCntyInputQso');
		$('#stationCntyInputQso').val('');
		$('#dxcc_id').multiselect('refresh');
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
		'position': 'sticky', 'top': '0px', 'z-index': 1, 'background-color':'inherit', 'width':'100%', 'height':'37px'
	})

	$('#notice-alerts').delay(1000).fadeOut(5000);

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


	$('#stationProfile').change(function() {
		var stationProfile = $('#stationProfile').val();
		$.ajax({
			url: base_url+'index.php/qso/get_station_power',
			type: 'post',
			data: {'stationProfile': stationProfile},
			success: function(res) {
				$('#transmit_power').val(res.station_power);
			},
			error: function() {
				$('#transmit_power').val('');
			},
		});
		// [eQSL default msg] change value on change station profle //
		qso_set_eqsl_qslmsg(stationProfile,false,'.qso_panel');
	});
	// [eQSL default msg] change value on clic //
	$('.qso_panel .qso_eqsl_qslmsg_update').off('click').on('click',function() {
		qso_set_eqsl_qslmsg($('.qso_panel #stationProfile').val(),true,'.qso_panel');
		$('#charsLeft').text(" ");
	});

	$(document).keyup(function(e) {
		if (e.charCode === 0) {
			let fixedcall = $('#callsign').val();
			$('#callsign').val(fixedcall.replace('Ø', '0'));
		}
		if (e.key === "Escape") { // escape key maps to keycode `27`
			reset_fields();
			if ( ! manual ) {
				resetTimers(0)
			}
			$('#callsign').val("");
			$("#callsign").focus();
		}
	});

	// Sanitize some input data
	$('#callsign').on('input', function() {
		$(this).val($(this).val().replace(/\s/g, ''));
	});
	$('#locator').on('input', function() {
		$(this).val($(this).val().replace(/\s/g, ''));
	});

	$('.callsign-suggest').hide();

	setRst($(".mode").val());

	/* On Page Load */
	var catcher = function() {
		var changed = false;
		$('form').each(function() {
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
	$("#callsign").focus();

	if ( ! manual ) {
		$(function($) {
			resetTimers(0);
		});
	}

	$("#check_cluster").on("click", function() {
		$.ajax({ url: dxcluster_provider+"/qrg_lookup/"+$("#frequency").val()/1000, cache: false, dataType: "json" }).done(
			function(dxspot) {
				reset_fields();
				$("#callsign").val(dxspot.spotted);
				$("#callsign").trigger("blur");
			}
		);
	});

	function set_timers() {
		setTimeout(function() {
			var callsignValue = localStorage.getItem("quicklogCallsign");
			if (callsignValue !== null && callsignValue !== undefined) {
				$("#callsign").val(callsignValue);
				$("#mode").focus();
				localStorage.removeItem("quicklogCallsign");
			}
		}, 100);
	}

	$("#qso_input").off('submit').on('submit', function(e){
		var _submit = true;
		if ((typeof qso_manual !== "undefined")&&(qso_manual == "1")) {
			if ($('#qso_input input[name="end_time"]').length == 1) { _submit = testTimeOffConsistency(); }
		}
		if ( _submit) {
			var saveQsoButtonText = $("#saveQso").html();
			$("#saveQso").html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> ' + saveQsoButtonText + '...').prop('disabled', true);
			manual_addon='?manual='+qso_manual;
			e.preventDefault();
			$.ajax({
				url: base_url+'index.php/qso'+manual_addon,
				method: 'POST',
				type: 'post',
				data: $(this).serialize(),
				success: function(resdata) {
					result = JSON.parse(resdata);
					if (result.message == 'success') {
						activeStationId=result.activeStationId;
						activeStationOP=result.activeStationOP;
						activeStationTXPower=result.activeStationTXPower;
						$("#noticer").removeClass("");
						$("#noticer").addClass("alert alert-info");
						$("#noticer").html("QSO Added");
						$("#noticer").show();
						reset_fields();
						htmx.trigger("#qso-last-table", "qso_event")
						$("#saveQso").html(saveQsoButtonText).prop("disabled",false);
						$("#callsign").val("");
						$("#noticer").fadeOut(2000);
						var triggerEl = document.querySelector('#myTab a[href="#qso"]')
						bootstrap.Tab.getInstance(triggerEl).show() // Select tab by name
						$("#callsign").focus();
					} else {
						$("#noticer").removeClass("");
						$("#noticer").addClass("alert alert-warning");
						$("#noticer").html(result.errors);
						$("#noticer").show();
						$("#saveQso").html(saveQsoButtonText).prop("disabled",false);
					}
				},
				error: function() {
					$("#noticer").removeClass("");
					$("#noticer").addClass("alert alert-warning");
					$("#noticer").html("Timeout while adding QSO. NOT added");
					$("#noticer").show();
					$("#saveQso").html(saveQsoButtonText).prop("disabled",false);
				}
			});
		}
		return false;
	});
	$('#reset_time').click(function() {
		var now = new Date();
		var localTime = now.getTime();
		var utc = localTime + (now.getTimezoneOffset() * 60000);
		$('#start_time').val(("0" + now.getUTCHours()).slice(-2)+':'+("0" + now.getUTCMinutes()).slice(-2)+':'+("0" + now.getUTCSeconds()).slice(-2));
		$("[id='start_time']").each(function() {
			$(this).attr("value", ("0" + now.getUTCHours()).slice(-2)+':'+("0" + now.getUTCMinutes()).slice(-2)+':'+("0" + now.getUTCSeconds()).slice(-2));
		});
	});
	$('#reset_start_time').click(function() {
		var now = new Date();
		var localTime = now.getTime();
		var utc = localTime + (now.getTimezoneOffset() * 60000);
		$('#start_time').val(("0" + now.getUTCHours()).slice(-2)+':'+("0" + now.getUTCMinutes()).slice(-2));
		$("[id='start_time']").each(function() {
			$(this).attr("value", ("0" + now.getUTCHours()).slice(-2)+':'+("0" + now.getUTCMinutes()).slice(-2)+':'+("0" + now.getUTCSeconds()).slice(-2));
		});
		$('#end_time').val(("0" + now.getUTCHours()).slice(-2)+':'+("0" + now.getUTCMinutes()).slice(-2));
		$("[id='end_time']").each(function() {
			$(this).attr("value", ("0" + now.getUTCHours()).slice(-2)+':'+("0" + now.getUTCMinutes()).slice(-2)+':'+("0" + now.getUTCSeconds()).slice(-2));
		});
		// update date (today, for "post qso") //
		$('#start_date').val(("0" + now.getUTCDate()).slice(-2)+'-'+("0" + (now.getUTCMonth()+1)).slice(-2)+'-'+now.getUTCFullYear());
	});
	$('#reset_end_time').click(function() {
		var now = new Date();
		var localTime = now.getTime();
		var utc = localTime + (now.getTimezoneOffset() * 60000);
		$('#end_time').val(("0" + now.getUTCHours()).slice(-2)+':'+("0" + now.getUTCMinutes()).slice(-2));
		$("[id='end_time']").each(function() {
			$(this).attr("value", ("0" + now.getUTCHours()).slice(-2)+':'+("0" + now.getUTCMinutes()).slice(-2)+':'+("0" + now.getUTCSeconds()).slice(-2));
		});
	});
	var favs={};
	get_fav();

	$('#fav_add').click(function (event) {
		save_fav();
	});

	$(document).on("click", "#fav_del", function (event) {
		del_fav($(this).attr('name'));
	});

	$(document).on("click", "#fav_recall", function (event) {
		$('#sat_name').val(favs[this.innerText].sat_name);
		$('#sat_mode').val(favs[this.innerText].sat_mode);
		$('#band_rx').val(favs[this.innerText].band_rx);
		$('#band').val(favs[this.innerText].band);
		$('#frequency_rx').val(favs[this.innerText].frequency_rx);
		$('#frequency').val(favs[this.innerText].frequency);
		$('#selectPropagation').val(favs[this.innerText].prop_mode);
		$('#mode').val(favs[this.innerText].mode).change();
	});


	function del_fav(name) {
		if (confirm("Are you sure to delete Fav?")) {
			$.ajax({
				url: base_url+'index.php/user_options/del_fav',
				method: 'POST',
				dataType: 'json',
				contentType: "application/json; charset=utf-8",
				data: JSON.stringify({ "option_name": name }),
				success: function(result) {
					get_fav();
				}
			});
		}
	}

	function get_fav() {
		$.ajax({
			url: base_url+'index.php/user_options/get_fav',
			method: 'GET',
			dataType: 'json',
			contentType: "application/json; charset=utf-8",
			success: function(result) {
				$("#fav_menu").empty();
				for (const key in result) {
					$("#fav_menu").append('<label class="dropdown-item" style="display: flex; justify-content: space-between;"><span id="fav_recall">' + key + '</span><span class="badge bg-danger" id="fav_del" name="' + key + '"><i class="fas fa-trash-alt"></i></span></label>');
				}
				favs=result;
			}
		});
	}

	function save_fav() {
		var payload={};
		payload.sat_name=$('#sat_name').val();
		payload.sat_mode=$('#sat_mode').val();
		payload.band_rx=$('#band_rx').val();
		payload.band=$('#band').val();
		payload.frequency_rx=$('#frequency_rx').val();
		payload.frequency=$('#frequency').val();
		payload.prop_mode=$('#selectPropagation').val();
		payload.mode=$('#mode').val();
		$.ajax({
			url: base_url+'index.php/user_options/add_edit_fav',
			method: 'POST',
			dataType: 'json',
			contentType: "application/json; charset=utf-8",
			data: JSON.stringify(payload),
			success: function(result) {
				get_fav();
			}
		});
	}


	var bc_bandmap = new BroadcastChannel('qso_window');
	bc_bandmap.onmessage = function (ev) {
		if (ev.data == 'ping') {
			bc_bandmap.postMessage('pong');
		}
	}

	var bc = new BroadcastChannel('qso_wish');
	bc.onmessage = function (ev) {
		if (ev.data.ping) {
			let message={};
			message.pong=true;
			bc.postMessage(message);
		} else {
			$('#frequency').val(ev.data.frequency);
			$("#band").val(frequencyToBand(ev.data.frequency));
			if (ev.data.frequency_rx != "") {
				$('#frequency_rx').val(ev.data.frequency_rx);
				$("#band_rx").val(frequencyToBand(ev.data.frequency_rx));
			}
			$("#callsign").val(ev.data.call);
			$("#callsign").focusout();
			$("#callsign").blur();
		}
	} /* receive */

	$("#locator")
		.popover({ placement: 'top', title: 'Gridsquare Formatting', content: "Enter multiple (4-digit) grids separated with commas. For example: IO77,IO78" })
		.focus(function () {
			$('#locator').popover('show');
		})
		.blur(function () {
			$('#locator').popover('hide');
		});

	$("#sat_name").change(function(){
		var sat = $("#sat_name").val();
		if (sat == "") {
			$("#sat_mode").val("");
			$("#selectPropagation").val("");
		}
	});

	$('#stateDropdown').change(function(){
		var state = $("#stateDropdown option:selected").text();
		if (state != "") {
			$("#stationCntyInputQso").prop('disabled', false);

			$('#stationCntyInputQso').selectize({
				maxItems: 1,
				closeAfterSelect: true,
				loadThrottle: 250,
				valueField: 'name',
				labelField: 'name',
				searchField: 'name',
				options: [],
				create: false,
				load: function(query, callback) {
					var state = $("#stateDropdown option:selected").text();

					if (!query || state == "") return callback();
					$.ajax({
						url: base_url+'index.php/qso/get_county',
						type: 'GET',
						dataType: 'json',
						data: {
							query: query,
							state: state,
						},
						error: function() {
							callback();
						},
						success: function(res) {
							callback(res);
						}
					});
				}
			});

		} else {
			$("#stationCntyInputQso").prop('disabled', true);
			//$('#stationCntyInputQso')[0].selectize.destroy();
			$("#stationCntyInputQso").val("");
		}
	});

	$('#sota_ref').selectize({
		maxItems: 1,
		closeAfterSelect: true,
		loadThrottle: 250,
		valueField: 'name',
		labelField: 'name',
		searchField: 'name',
		options: [],
		create: true,
		load: function(query, callback) {
			if (!query || query.length < 3) return callback();  // Only trigger if 3 or more characters are entered
			$.ajax({
				url: base_url+'index.php/qso/get_sota',
				type: 'GET',
				dataType: 'json',
				data: {
					query: query,
				},
				error: function() {
					callback();
				},
				success: function(res) {
					callback(res);
				}
			});
		},
		onChange: function(value) {
			if (value !== '') {
				$('#sota_info').show();
				$('#sota_info').html('<a target="_blank" href="https://summits.sota.org.uk/summit/'+ value +'"><img width="32" height="32" src="'+base_url+'images/icons/sota.org.uk.png"></a>');
				$('#sota_info').attr('title', 'Lookup '+ value +' summit info on sota.org.uk');
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
		load: function(query, callback) {
			if (!query || query.length < 3) return callback();  // Only trigger if 3 or more characters are entered
			$.ajax({
				url: base_url+'index.php/qso/get_wwff',
				type: 'GET',
				dataType: 'json',
				data: {
					query: query,
				},
				error: function() {
					callback();
				},
				success: function(res) {
					callback(res);
				}
			});
		},
		onChange: function(value) {
			if (value !== '') {
				$('#wwff_info').show();
				$('#wwff_info').html('<a target="_blank" href="https://www.cqgma.org/zinfo.php?ref='+ value +'"><img width="32" height="32" src="'+base_url+'images/icons/wwff.co.png"></a>');
				$('#wwff_info').attr('title', 'Lookup '+ value +' reference info on cqgma.org');
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
		load: function(query, callback) {
			if (!query || query.length < 3) return callback();  // Only trigger if 3 or more characters are entered
			$.ajax({
				url: base_url+'index.php/qso/get_pota',
				type: 'GET',
				dataType: 'json',
				data: {
					query: query,
				},
				error: function() {
					callback();
				},
				success: function(res) {
					callback(res);
				}
			});
		},
		onChange: function(value) {
			if (value !== '' && value.indexOf(',') === -1) {
				$('#pota_info').show();
				$('#pota_info').html('<a target="_blank" href="https://pota.app/#/park/'+ value +'"><img width="32" height="32" src="'+base_url+'images/icons/pota.app.png"></a>');
				$('#pota_info').attr('title', 'Lookup '+ value +' reference info on pota.co');
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
		load: function(query, callback) {
			if (!query) return callback();  // Only trigger if at least 1 character is entered
			$.ajax({
				url: base_url+'index.php/qso/get_dok',
				type: 'GET',
				dataType: 'json',
				data: {
					query: query,
				},
				error: function() {
					callback();
				},
				success: function(res) {
					callback(res);
				}
			});
		}
	});

	/*
	  Populate the Satellite Names Field on the QSO Panel
	  */
	$.getJSON(site_url + "/satellite/satellite_data", function( data ) {

		// Build the options array
		var items = [];
		$.each( data, function( key, val ) {
			items.push(
				'<option value="' + key + '">' + key + '</option>'
			);
		});

		// Add to the datalist
		$('.satellite_names_list').append(items.join( "" ));
	});


	var selected_sat;
	var selected_sat_mode;

	$(document).on('change', 'input', function(){
		var optionslist = $('.satellite_modes_list')[0].options;
		var value = $(this).val();
		for (var x=0;x<optionslist.length;x++){
			if (optionslist[x].value === value) {

				// Store selected sat mode
				selected_sat_mode = value;

				// get Json file
				$.getJSON(site_url + "/satellite/satellite_data", function( data ) {

					// Build the options array
					var sat_modes = [];
					$.each( data, function( key, val ) {
						if (key == selected_sat) {
							$.each( val.Modes, function( key1, val2 ) {
								if(key1 == selected_sat_mode) {

									if ( (val2[0].Downlink_Mode == "LSB" && val2[0].Uplink_Mode == "USB") || (val2[0].Downlink_Mode == "USB" && val2[0].Uplink_Mode == "LSB") )   { // inverting Transponder? set to SSB
										$("#mode").val("SSB");
									} else {
										$("#mode").val(val2[0].Uplink_Mode);
									}
									$("#band").val(frequencyToBand(val2[0].Uplink_Freq));
									$("#band_rx").val(frequencyToBand(val2[0].Downlink_Freq));
									$("#frequency").val(val2[0].Uplink_Freq);
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

	$(document).on('change', 'input', function(){
		var optionslist = $('.satellite_names_list')[0].options;
		var value = $(this).val();
		for (var x=0;x<optionslist.length;x++){
			if (optionslist[x].value === value) {
				$("#sat_mode").val("");
				$('.satellite_modes_list').find('option').remove().end();
				selected_sat = value;
				// get Json file
				$.getJSON(site_url + "/satellite/satellite_data", function( data ) {

					// Build the options array
					var sat_modes = [];
					$.each( data, function( key, val ) {
						if (key == value) {
							$.each( val.Modes, function( key1, val2 ) {
								//console.log (key1);
								sat_modes.push('<option value="' + key1 + '">' + key1 + '</option>');
							});
						}
					});

					// Add to the datalist
					$('.satellite_modes_list').append(sat_modes.join( "" ));

				});
			}
		}
	});

	function changebadge(entityname) {
		if($("#sat_name" ).val() != "") {
			$.getJSON(base_url + 'index.php/logbook/jsonlookupdxcc/' + convert_case(entityname) + '/SAT/0/0', function(result)
				{

					$('#callsign_info').removeClass("lotw_info_orange");
					$('#callsign_info').removeClass("text-bg-secondary");
					$('#callsign_info').removeClass("text-bg-success");
					$('#callsign_info').removeClass("text-bg-danger");
					$('#callsign_info').attr('title', '');

					if (result.confirmed) {
						$('#callsign_info').addClass("text-bg-success");
						$('#callsign_info').attr('title', 'DXCC was already worked and confirmed in the past on this band and mode!');
					} else if (result.workedBefore) {
						$('#callsign_info').addClass("text-bg-success");
						$('#callsign_info').addClass("lotw_info_orange");
						$('#callsign_info').attr('title', 'DXCC was already worked in the past on this band and mode!');
					} else {
						$('#callsign_info').addClass("text-bg-danger");
						$('#callsign_info').attr('title', 'New DXCC, not worked on this band and mode!');
					}
				})
		} else {
			$.getJSON(base_url + 'index.php/logbook/jsonlookupdxcc/' + convert_case(entityname) + '/0/' + $("#band").val() +'/' + $("#mode").val(), function(result)
				{
					// Reset CSS values before updating
					$('#callsign_info').removeClass("lotw_info_orange");
					$('#callsign_info').removeClass("text-bg-secondary");
					$('#callsign_info').removeClass("text-bg-success");
					$('#callsign_info').removeClass("text-bg-danger");
					$('#callsign_info').attr('title', '');

					if (result.confirmed) {
						$('#callsign_info').addClass("text-bg-success");
						$('#callsign_info').attr('title', 'DXCC was already worked and confirmed in the past on this band and mode!');
					} else if (result.workedBefore) {
						$('#callsign_info').addClass("text-bg-success");
						$('#callsign_info').addClass("lotw_info_orange");
						$('#callsign_info').attr('title', 'DXCC was already worked in the past on this band and mode!');
					} else {
						$('#callsign_info').addClass("text-bg-danger");
						$('#callsign_info').attr('title', 'New DXCC, not worked on this band and mode!');
					}
				})
		}
	}

	$('#btn_reset').click(function() {
		reset_fields();
	});

	$('#btn_fullreset').click(function() {
		reset_to_default();
	});

	function reset_to_default() {
		reset_fields();
		$("#stationProfile").val(activeStationId);
		$("#selectPropagation").val("");
		$("#frequency_rx").val("");
 		$("#band_rx").val("");
		$("#transmit_power").val(activeStationTXPower);
		$("#sat_name").val("");
		$("#sat_mode").val("");
		$("#ant_az").val("");
		$("#ant_el").val("");
	}

	/* Function: reset_fields is used to reset the fields on the QSO page */
	function reset_fields() {
		$('#locator_info').text("");
		$('#comment').val("");
		$('#country').val("");
		$('#continent').val("");
		$('#lotw_info').text("");
		$('#lotw_info').removeClass("lotw_info_red");
		$('#lotw_info').removeClass("lotw_info_yellow");
		$('#lotw_info').removeClass("lotw_info_orange");
		$('#qrz_info').text("").hide();
		$('#hamqth_info').text("").hide();
		$('#dxcc_id').val("").multiselect('refresh');
		$('#cqz').val("");
		$('#ituz').val("");
		$('#name').val("");
		$('#qth').val("");
		$('#locator').val("");
		$('#iota_ref').val("");
		$("#locator").removeClass("confirmedGrid");
		$("#locator").removeClass("workedGrid");
		$("#locator").removeClass("newGrid");
		$("#callsign").val("");
		$("#callsign").removeClass("confirmedGrid");
		$("#callsign").removeClass("workedGrid");
		$("#callsign").removeClass("newGrid");
		$('#callsign_info').removeClass("text-bg-secondary");
		$('#callsign_info').removeClass("text-bg-success");
		$('#callsign_info').removeClass("text-bg-danger");
		$('#callsign-image').attr('style', 'display: none;');
		$('#callsign-image-content').text("");
		$("#operator_callsign").val(activeStationOP);
		$('#qsl_via').val("");
		$('#callsign_info').text("");
		$('#stateDropdown').val("");
		$('#qso-last-table').show();
		$('#partial_view').hide();
		$('.callsign-suggest').hide();
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
		$('.callsign-suggest').hide();
		$('.dxccsummary').remove();
		$('#timesWorked').html(lang_qso_title_previous_contacts);
		updateStateDropdown('#dxcc_id', '#stateInputLabel', '#location_us_county', '#stationCntyInputEdit');
		clearTimeout();
		set_timers();
		resetTimers(qso_manual);
	}

	function resetTimers(manual) {
		if (typeof manual !== 'undefined' && manual != 1) {
			handleStart = setInterval(function() { getUTCTimeStamp($('.input_start_time')); }, 500);
			handleEnd = setInterval(function() { getUTCTimeStamp($('.input_end_time')); }, 500);
			handleDate = setInterval(function() { getUTCDateStamp($('.input_date')); }, 1000);
		}
	}

	$("#callsign").focusout(function() {
		if ($(this).val().length >= 3) {

			// Temp store the callsign
			var temp_callsign = $(this).val();

			/* Find and populate DXCC */
			$('.callsign-suggest').hide();

			if($("#sat_name").val() != ""){
				var json_band = "SAT";
			} else {
				var json_band = $("#band").val();
			}
			var json_mode = $("#mode").val();

			var find_callsign = $(this).val().toUpperCase();
			var callsign = find_callsign;

			find_callsign=find_callsign.replace(/\//g, "-");
				find_callsign=find_callsign.replace('Ø', '0');

				// Replace / in a callsign with - to stop urls breaking
				$.getJSON(base_url + 'index.php/logbook/json/' + find_callsign + '/' + json_band + '/' + json_mode + '/' + $('#stationProfile').val() + '/' + $('#start_date').val(), async function(result)
					{

						// Make sure the typed callsign and json result match
						if($('#callsign').val = result.callsign) {

							// Reset QSO fields
							resetDefaultQSOFields();

							if(result.dxcc.entity != undefined) {
								$('#country').val(convert_case(result.dxcc.entity));
								$('#callsign_info').text(convert_case(result.dxcc.entity));

								if($("#sat_name" ).val() != "") {
									//logbook/jsonlookupgrid/io77/SAT/0/0
									await $.getJSON(base_url + 'index.php/logbook/jsonlookupcallsign/' + find_callsign + '/SAT/0/0', function(result)
										{
											// Reset CSS values before updating
											$('#callsign').removeClass("workedGrid");
											$('#callsign').removeClass("confirmedGrid");
											$('#callsign').removeClass("newGrid");
											$('#callsign').attr('title', '');

											if (result.confirmed) {
												$('#callsign').addClass("confirmedGrid");
												$('#callsign').attr('title', 'Callsign was already worked and confirmed in the past on this band and mode!');
											} else if (result.workedBefore) {
												$('#callsign').addClass("workedGrid");
												$('#callsign').attr('title', 'Callsign was already worked in the past on this band and mode!');
											}
											else
											{
												$('#callsign').addClass("newGrid");
												$('#callsign').attr('title', 'New Callsign!');
											}
										})
								} else {
									await $.getJSON(base_url + 'index.php/logbook/jsonlookupcallsign/' + find_callsign + '/0/' + $("#band").val() +'/' + $("#mode").val(), function(result)
										{
											// Reset CSS values before updating
											$('#callsign').removeClass("confirmedGrid");
											$('#callsign').removeClass("workedGrid");
											$('#callsign').removeClass("newGrid");
											$('#callsign').attr('title', '');

											if (result.confirmed) {
												$('#callsign').addClass("confirmedGrid");
												$('#callsign').attr('title', 'Callsign was already worked and confirmed in the past on this band and mode!');
											} else if (result.workedBefore) {
												$('#callsign').addClass("workedGrid");
												$('#callsign').attr('title', 'Callsign was already worked in the past on this band and mode!');
											} else {
												$('#callsign').addClass("newGrid");
												$('#callsign').attr('title', 'New Callsign!');
											}

										})
								}

								changebadge(result.dxcc.entity);

							}

							if(result.lotw_member == "active") {
								$('#lotw_info').text("LoTW");
								if (result.lotw_days > 365) {
									$('#lotw_info').addClass('lotw_info_red');
								} else if (result.lotw_days > 30) {
									$('#lotw_info').addClass('lotw_info_orange');
									$lotw_hint = ' lotw_info_orange';
								} else if (result.lotw_days > 7) {
									$('#lotw_info').addClass('lotw_info_yellow');
								}
								$('#lotw_link').attr('href',"https://lotw.arrl.org/lotwuser/act?act="+callsign);
								$('#lotw_link').attr('target',"_blank");
								$('#lotw_info').attr('data-bs-toggle',"tooltip");
								$('#lotw_info').attr('title',"LoTW User. Last upload was "+result.lotw_days+" days ago");
								$('[data-bs-toggle="tooltip"]').tooltip();
							}
							$('#qrz_info').html('<a target="_blank" href="https://www.qrz.com/db/'+callsign+'"><img width="30" height="30" src="'+base_url+'images/icons/qrz.com.png"></a>');
							$('#qrz_info').attr('title', 'Lookup '+callsign+' info on qrz.com').removeClass('d-none');
							$('#qrz_info').show();
							$('#hamqth_info').html('<a target="_blank" href="https://www.hamqth.com/'+callsign+'"><img width="30" height="30" src="'+base_url+'images/icons/hamqth.com.png"></a>');
							$('#hamqth_info').attr('title', 'Lookup '+callsign+' info on hamqth.com').removeClass('d-none');
							$('#hamqth_info').show();

							var $dok_select = $('#darc_dok').selectize();
							var dok_selectize = $dok_select[0].selectize;
							if ((result.dxcc.adif == '230') && (($("#callsign").val().trim().length)>0)) {
								$.get(base_url + 'index.php/lookup/dok/' + $('#callsign').val().toUpperCase(), function(result) {
									if (result) {
										dok_selectize.addOption({name: result});
										dok_selectize.setValue(result, false);
									}
								});
							} else {
								dok_selectize.clear();
							}

							$('#dxcc_id').val(result.dxcc.adif).multiselect('refresh');
							await updateStateDropdown('#dxcc_id', '#stateInputLabel', '#location_us_county', '#stationCntyInputEdit');
							if (result.callsign_cqz != '') {
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
								iconSize:     [18, 18], // size of the icon
							});

							// Set Map to Lat/Long
							markers.clearLayers();
							mymap.setZoom(8);
							if (typeof result.latlng !== "undefined" && result.latlng !== false) {
								var marker = L.marker([result.latlng[0], result.latlng[1]], {icon: redIcon});
								mymap.panTo([result.latlng[0], result.latlng[1]]);
								mymap.setView([result.latlng[0], result.latlng[1]], 8);
							} else {
								var marker = L.marker([result.dxcc.lat, result.dxcc.long], {icon: redIcon});
								mymap.panTo([result.dxcc.lat, result.dxcc.long]);
								mymap.setView([result.dxcc.lat, result.dxcc.long], 8);
							}

							markers.addLayer(marker).addTo(mymap);


							/* Find Locator if the field is empty */
							if($('#locator').val() == "") {
								$('#locator').val(result.callsign_qra);
								$('#locator_info').html(result.bearing);

								if (result.callsign_distance != "" && result.callsign_distance != 0)
								{
									document.getElementById("distance").value = result.callsign_distance;
								}

								if (result.callsign_qra != "")
								{
									if (result.confirmed) {
										$('#locator').addClass("confirmedGrid");
										$('#locator').attr('title', 'Grid was already worked and confirmed in the past');
									} else if (result.workedBefore) {
										$('#locator').addClass("workedGrid");
										$('#locator').attr('title', 'Grid was already worked in the past');
									} else {
										$('#locator').addClass("newGrid");
										$('#locator').attr('title', 'New grid!');
									}
								} else {
									$('#locator').removeClass("workedGrid");
									$('#locator').removeClass("confirmedGrid");
									$('#locator').removeClass("newGrid");
									$('#locator').attr('title', '');
								}

							}

							/* Find Operators Name */
							if($('#qsl_via').val() == "") {
								$('#qsl_via').val(result.qsl_manager);
							}

							/* Find Operators Name */
							if($('#name').val() == "") {
								$('#name').val(result.callsign_name);
							}

							if($('#continent').val() == "") {
								$('#continent').val(result.dxcc.cont);
							}

							if($('#qth').val() == "") {
								$('#qth').val(result.callsign_qth);
							}

							/* Find link to qrz.com picture */
							if (result.image != "n/a") {
								$('#callsign-image-content').html('<img class="callsign-image-pic" href="'+result.image+'" data-fancybox="images" src="'+result.image+'" style="cursor: pointer;">');
								$('#callsign-image').attr('style', 'display: true;');
							}

							/*
							 * Update state with returned value
							 */
							if($("#stateDropdown").val() == "") {
								$("#stateDropdown").val(result.callsign_state);
							}

							/*
							 * Update county with returned value
							 */
							selectize_usa_county('#stateDropdown', '#stationCntyInputQso');
							if( $('#stationCntyInputQso').has('option').length == 0 && result.callsign_us_county != "") {
								var county_select = $('#stationCntyInputQso').selectize();
								var county_selectize = county_select[0].selectize;
								county_selectize.addOption({name: result.callsign_us_county});
								county_selectize.setValue(result.callsign_us_county, false);
							}

							if(result.timesWorked != "") {
								if (result.timesWorked == '0') {
									$('#timesWorked').html(lang_qso_title_not_worked_before);
								} else {
									$('#timesWorked').html(result.timesWorked + ' ' + lang_qso_title_times_worked_before);
								}
							} else {
								$('#timesWorked').html(lang_qso_title_previous_contacts);
							}
							if($('#iota_ref').val() == "") {
								$('#iota_ref').val(result.callsign_iota);
							}
							// Hide the last QSO table
							$('#qso-last-table').hide();
							$('#partial_view').show();
							/* display past QSOs */
							$('#partial_view').html(result.partial);

							// Get DXX Summary
							getDxccResult(result.dxcc.adif, convert_case(result.dxcc.entity));
						}
					});
		} else {
			// Reset QSO fields
			resetDefaultQSOFields();
		}
	})

			// Only set the frequency when not set by userdata/PHP.
			if ($('#frequency').val() == "")
			{
				$.get(base_url + 'index.php/qso/band_to_freq/' + $('#band').val() + '/' + $('.mode').val(), function(result) {
					$('#frequency').val(result);
					$('#frequency_rx').val("");
				});
			}

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
			$('#end_time').change(function() {
				var raw_time = $(this).val();
				if(raw_time.match(/^\d\[0-6]d$/)) {
					raw_time = "0"+raw_time;
				}
				if(raw_time.match(/^[012]\d[0-5]\d$/)) {
					raw_time = raw_time.substring(0,2)+":"+raw_time.substring(2,4);
					$('#end_time').val(raw_time);
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

	/* on mode change */
	$('.mode').change(function() {
		if ($('#radio').val() == 0) {
			$.get(base_url + 'index.php/qso/band_to_freq/' + $('#band').val() + '/' + $('.mode').val(), function(result) {
				$('#frequency').val(result);
			});
		}
		$('#frequency_rx').val("");
	});

	/* Calculate Frequency */
	/* on band change */
	$('#band').change(function() {
		if ($('#radio').val() == 0) {
			$.get(base_url + 'index.php/qso/band_to_freq/' + $(this).val() + '/' + $('.mode').val(), function(result) {
				$('#frequency').val(result);
			});
		}
		$('#frequency_rx').val("");
		$('#band_rx').val("");
		$("#selectPropagation").val("");
		$("#sat_name").val("");
		$("#sat_mode").val("");
	});

	/* On Key up Calculate Bearing and Distance */
	$("#locator").keyup(function(){
		if ($(this).val()) {
			var qra_input = $(this).val();

			var qra_lookup = qra_input.substring(0, 4);

			if(qra_lookup.length >= 4) {

				// Check Log if satname is provided
				if($("#sat_name" ).val() != "") {

					//logbook/jsonlookupgrid/io77/SAT/0/0

					$.getJSON(base_url + 'index.php/logbook/jsonlookupgrid/' + qra_lookup.toUpperCase() + '/SAT/0/0', function(result)
						{
							// Reset CSS values before updating
							$('#locator').removeClass("confirmedGrid");
							$('#locator').removeClass("workedGrid");
							$('#locator').removeClass("newGrid");
							$('#locator').attr('title', '');

							if (result.confirmed) {
								$('#locator').addClass("confirmedGrid");
								$('#locator').attr('title', 'Grid was already worked and confirmed in the past');
							} else if (result.workedBefore) {
								$('#locator').addClass("workedGrid");
								$('#locator').attr('title', 'Grid was already worked in the past');
							} else {
								$('#locator').addClass("newGrid");
								$('#locator').attr('title', 'New grid!');
							}
						})
				} else {
					$.getJSON(base_url + 'index.php/logbook/jsonlookupgrid/' + qra_lookup.toUpperCase() + '/0/' + $("#band").val() +'/' + $("#mode").val(), function(result)
						{
							// Reset CSS values before updating
							$('#locator').removeClass("confirmedGrid");
							$('#locator').removeClass("workedGrid");
							$('#locator').removeClass("newGrid");
							$('#locator').attr('title', '');

							if (result.confirmed) {
								$('#locator').addClass("confirmedGrid");
								$('#locator').attr('title', 'Grid was already worked and confimred in the past');
							} else if (result.workedBefore) {
								$('#locator').addClass("workedGrid");
								$('#locator').attr('title', 'Grid was already worked in the past');
							} else {
								$('#locator').addClass("newGrid");
								$('#locator').attr('title', 'New grid!');
							}

						})
				}
			}

			if(qra_input.length >= 4 && $(this).val().length > 0) {
				$.ajax({
					url: base_url + 'index.php/logbook/qralatlngjson',
					type: 'post',
					data: {
						qra: $(this).val(),
					},
					success: function(data) {
						// Set Map to Lat/Long
						result = JSON.parse(data);
						markers.clearLayers();
						if (typeof result[0] !== "undefined" && typeof result[1] !== "undefined") {
							var redIcon = L.icon({
								iconUrl: icon_dot_url,
								iconSize:     [18, 18], // size of the icon
							});

							var marker = L.marker([result[0], result[1]], {icon: redIcon});
							mymap.setZoom(8);
							mymap.panTo([result[0], result[1]]);
							mymap.setView([result[0], result[1]], 8);
							markers.addLayer(marker).addTo(mymap);
						}
					},
					error: function() {
					},
				});

				$.ajax({
					url: base_url + 'index.php/logbook/searchbearing',
					type: 'post',
					data: {
						grid: $(this).val(),
						stationProfile: $('#stationProfile').val()
					},
					success: function(data) {
						$('#locator_info').html(data).fadeIn("slow");
					},
					error: function() {
						$('#locator_info').text("Error loading bearing!").fadeIn("slow");
					},
				});
				$.ajax({
					url: base_url + 'index.php/logbook/searchdistance',
					type: 'post',
					data: {
						grid: $(this).val(),
						stationProfile: $('#stationProfile').val()
					},
					success: function(data) {
						document.getElementById("distance").value = data;
					},
					error: function() {
						document.getElementById("distance").value = null;
					},
				});
			}
		}
	});

	// Change report based on mode
	$('.mode').change(function(){
		setRst($('.mode').val());
	});

	function convert_case(str) {
		var lower = str.toLowerCase();
		return lower.replace(/(^| )(\w)/g, function(x) {
			return x.toUpperCase();
		});
	}

	$('#dxcc_id').on('change', function() {
		$.getJSON(base_url + 'index.php/logbook/jsonentity/' + $(this).val(), function (result) {
			if (result.dxcc.name != undefined) {

				$('#country').val(convert_case(result.dxcc.name));
				$('#cqz').val(convert_case(result.dxcc.cqz));

				$('#callsign_info').removeClass("text-bg-secondary");
				$('#callsign_info').removeClass("text-bg-success");
				$('#callsign_info').removeClass("text-bg-danger");
				$('#callsign_info').attr('title', '');
				$('#callsign_info').text(convert_case(result.dxcc.name));

				changebadge(result.dxcc.name);

				// Set Map to Lat/Long it locator is not empty
				if($('#locator').val() == "") {
					var redIcon = L.icon({
						iconUrl: icon_dot_url,
						iconSize:     [18, 18], // size of the icon
					});

					markers.clearLayers();
					var marker = L.marker([result.dxcc.lat, result.dxcc.long], {icon: redIcon});
					mymap.setZoom(8);
					mymap.panTo([result.dxcc.lat, result.dxcc.long]);
					markers.addLayer(marker).addTo(mymap);
				}
			}
		});
	});

	//Spacebar moves to the name field when you're entering a callsign
	//Similar to contesting ux, good for pileups.
	$("#callsign").on("keypress", function(e) {
		if (e.which == 32){
			$("#name").focus();
			return false; //Eliminate space char
		}
	});

	var scps=[];
	// On Key up check and suggest callsigns
	$("#callsign").keyup(function() {
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
					success: function(result) {
						$('.callsign-suggestions').text(result);
						scps=result.split(" ");
						highlight(ccall.toUpperCase());
					}
				});
			} else {
				$('.callsign-suggestions').text(scps.filter((call) => call.includes($(this).val().toUpperCase())).join(' '));
				highlight(ccall.toUpperCase());
			}
		} else {
			$('.callsign-suggest').hide();
			scps=[];
		}
	});

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


	//Reset QSO form Fields function
	function resetDefaultQSOFields() {
		$('#callsign_info').text("");
		$('#locator_info').text("");
		$('#country').val("");
		$('#continent').val("");
		$('#dxcc_id').val("").multiselect('refresh');
		$('#cqz').val("");
		$('#ituz').val("");
		$('#name').val("");
		$('#qth').val("");
		$('#locator').val("");
		$('#iota_ref').val("");
		$('#sota_ref').val("");
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
		$('.dxccsummary').remove();
		$('#timesWorked').html(lang_qso_title_previous_contacts);
	}

	function closeModal() {
		var container = document.getElementById("modals-here")
		var backdrop = document.getElementById("modal-backdrop")
		var modal = document.getElementById("modal")

		modal.classList.remove("show")
		backdrop.classList.remove("show")

		setTimeout(function() {
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
		if ( !( (parseInt(_start_time.replaceAll(':','')) <= parseInt(_end_time.replaceAll(':','')))
			|| ((_start_time.substring(0,2)=="23")&&(_end_time.substring(0,2)=="00")) ) ) {
			$('#qso_input input[name="end_time"]').addClass('inputError');
			$('#qso_input .warningOnSubmit_txt').html(text_error_timeoff_less_timeon);
			$('#qso_input .warningOnSubmit').show();
			$('#qso_input input[name="end_time"]').off('change').on('change',function(){ testTimeOffConsistency(); });
			return false;
		}
		return true;
	}

});
