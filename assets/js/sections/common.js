function setRst(mode) {
	if(mode == 'JT65' || mode == 'JT65B' || mode == 'JT6C' || mode == 'JTMS' || mode == 'ISCAT' || mode == 'MSK144' || mode == 'JTMSK' || mode == 'QRA64' || mode == 'FT8' || mode == 'FT4' || mode == 'JS8' || mode == 'JT9' || mode == 'JT9-1' || mode == 'ROS'){
		$('#rst_sent').val('-5');
		$('#rst_rcvd').val('-5');
	} else if (mode == 'FSK441' || mode == 'JT6M') {
		$('#rst_sent').val('26');
		$('#rst_rcvd').val('26');
	} else if (mode == 'CW' || mode == 'RTTY' || mode == 'PSK31' || mode == 'PSK63') {
		$('#rst_sent').val('599');
		$('#rst_rcvd').val('599');
	} else {
		$('#rst_sent').val('59');
		$('#rst_rcvd').val('59');
	}
}

function qsl_rcvd(id, method) {
    $(".ld-ext-right-r-"+method).addClass('running');
    $(".ld-ext-right-r-"+method).prop('disabled', true);
    $.ajax({
        url: base_url + 'index.php/qso/qsl_rcvd_ajax',
        type: 'post',
        data: {'id': id,
            'method': method
        },
        success: function(data) {
            $(".ld-ext-right-r-"+method).removeClass('running');
            $(".ld-ext-right-r-"+method).prop('disabled', false);
            if (data.message == 'OK') {
                $("#qsl_" + id).find("span:eq(1)").attr('class', 'qsl-green'); // Paints arrow green
                $("#qrz_" + id).find("span:eq(0)").attr('class', 'qsl-yellow'); // marks the QRZ Upload as modified
                $(".qsl_rcvd_" + id).remove(); // removes choice from menu
            }
            else {
                $(".bootstrap-dialog-message").append('<div class="alert alert-danger"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>You are not allowed to update QSL status!</div>');
            }
        }
    });
}

function qsl_sent(id, method) {
    $.ajax({
        url: base_url + 'index.php/qso/qsl_sent_ajax',
        type: 'post',
        data: {'id': id,
            'method': method
        },
        success: function(data) {
            if (data.message == 'OK') {
                $("#qsl_" + id).find("span:eq(0)").attr('class', 'qsl-green'); // Paints arrow green
                $("#qrz_" + id).find("span:eq(0)").attr('class', 'qsl-yellow'); // marks the QRZ Upload as modified
                $(".qsl_sent_" + id).remove(); // removes choice from menu
            }
            else {
                $(".bootstrap-dialog-message").append('<div class="alert alert-danger"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>You are not allowed to update QSL status!</div>');
            }
        }
    });
}

// Function: qsl_requested
// Marks QSL card requested against the QSO.
function qsl_requested(id, method) {
    $(".ld-ext-right-t-"+method).addClass('running');
    $(".ld-ext-right-t-"+method).prop('disabled', true);
    $.ajax({
        url: base_url + 'index.php/qso/qsl_requested_ajax',
        type: 'post',
        data: {'id': id,
            'method': method
        },
        success: function(data) {
            $(".ld-ext-right-t-"+method).removeClass('running');
            $(".ld-ext-right-t-"+method).prop('disabled', false);
            if (data.message == 'OK') {
                $("#qsl_" + id).find("span:eq(0)").attr('class', 'qsl-yellow'); // Paints arrow yellow
                $("#qrz_" + id).find("span:eq(0)").attr('class', 'qsl-yellow'); // marks the QRZ Upload as modified
            }
            else {
                $(".bootstrap-dialog-message").append('<div class="alert alert-danger"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>You are not allowed to update QSL status!</div>');
            }
        }
    });
}

// Function: qsl_ignore
// Marks QSL card ignore against the QSO.
function qsl_ignore(id, method) {
    $(".ld-ext-right-ignore").addClass('running');
    $(".ld-ext-right-ignore").prop('disabled', true);
    $.ajax({
        url: base_url + 'index.php/qso/qsl_ignore_ajax',
        type: 'post',
        data: {'id': id,
            'method': method
        },
        success: function(data) {
            $(".ld-ext-right-ignore").removeClass('running');
            $(".ld-ext-right-ignore").prop('disabled', false);
            if (data.message == 'OK') {
                $("#qsl_" + id).find("span:eq(0)").attr('class', 'qsl-grey'); // Paints arrow grey
                $("#qrz_" + id).find("span:eq(0)").attr('class', 'qsl-yellow'); // marks the QRZ Upload as modified
            }
            else {
                $(".bootstrap-dialog-message").append('<div class="alert alert-danger"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>You are not allowed to update QSL status!</div>');
            }
        }
    });
}

function displayQso(id) {
    $.ajax({
        url: base_url + 'index.php/logbook/view/' + id,
        type: 'post',
        success: function(html) {
            BootstrapDialog.show({
                title: lang_general_word_qso_data,
                cssClass: 'qso-dialog',
                size: BootstrapDialog.SIZE_WIDE,
                nl2br: false,
                message: html,
                onshown: function(dialog) {
                    var qsoid = $("#qsoid").text();
                    $(".editButton").html('<a class="btn btn-primary" id="edit_qso" href="javascript:qso_edit('+qsoid+')"><i class="fas fa-edit"></i>'+lang_general_edit_qso+'</a>');
                    var lat = $("#lat").text();
                    var long = $("#long").text();
                    var callsign = $("#callsign").text();
                    var mymap = L.map('mapqso').setView([lat,long], 5);

                    var tiles = L.tileLayer(option_map_tile_server, {
                        maxZoom: 18,
                        attribution: option_map_tile_server_copyright,
                    }).addTo(mymap);


                    var printer = L.easyPrint({
                        tileLayer: tiles,
                        sizeModes: ['Current'],
                        filename: 'myMap',
                        exportOnly: true,
                        hideControlContainer: true
                    }).addTo(mymap);

                    var redIcon = L.icon({
                        iconUrl: icon_dot_url,
                        iconSize:     [18, 18], // size of the icon
                    });

                    L.marker([lat,long], {icon: redIcon}).addTo(mymap)
                        .bindPopup(callsign);

                },
            });

        }
    });
}

// used in edit_ajax.php to update the currently editing QSO
function single_callbook_update() {

    var callsign = $('#edit_callsign').val();
    var band = $('#edit_band').val();
    var mode = $('#edit_mode').val();

    $('#update_from_callbook').prop("disabled", true).addClass("running");

    $.ajax({
        url: site_url + '/logbook/json/' + callsign + '/' + band + '/' + mode,
        dataType: 'json',
        success: function (data) {
            // console.log(data);
            fill_if_empty('#qth_edit', data.callsign_qth);
            fill_if_empty('#dxcc_id_edit', data.dxcc.adif);
            fill_if_empty('#continent_edit', data.dxcc.cont);
            fill_if_empty('#cqz_edit', data.dxcc.cqz);
            if (data.callsign_ituz != '') {
                fill_if_empty('#ituz_edit', data.callsign_ituz);
            } else {
                fill_if_empty('#ituz_edit', data.dxcc.ituz);
            }
            fill_if_empty('#locator_edit', data.callsign_qra);
            // fill_if_empty('#image', data.image);  Not in use yet, but may in future
            fill_if_empty('#iota_ref_edit', data.callsign_iota);
            fill_if_empty('#name_edit', data.callsign_name);
            fill_if_empty('#qsl-via', data.qsl_manager);
            fill_if_empty('select[name="input_state_edit"]', data.callsign_state);
            fill_if_empty('#stationCntyInputEdit', data.callsign_us_county);

            $('#update_from_callbook').prop("disabled", false).removeClass("running");
        },
        error: function () {
            console.error("Sorry, something went wrong to get the callbook data.");

            $('#update_from_callbook').prop("disabled", false).removeClass("running");
        },
    });
}
// used with single_callbook_update() to only fill fields which are empty
async function fill_if_empty(field, data) {
    var border_color = '2px solid green';

    // catch special case for dxcc
    if (field == "#dxcc_id_edit" && $(field).val() == 0) {
        $(field).val(data).css('border', border_color);
        return;
    }

    if (field == 'select[name="input_state_edit"]') {
        await updateStateDropdown('#dxcc_id_edit', '#stateInputLabelEdit', '#location_us_county_edit', '#stationCntyInputEdit', '#stateDropdownEdit');
        $(field).val(data).css('border', border_color);
        return;
    }

    // catch special case for grid
    if (field == "#locator_edit") {
        $(field).val(data.toUpperCase()).css('border', border_color).trigger('change');
        return;
    }

    if ($(field).val() == '' && data != '') {
        $(field).val(data).css('border', border_color);
        return;
    }
}

function qso_delete(id, call) {
    BootstrapDialog.confirm({
        title: lang_general_word_danger,
        message: lang_qso_delete_warning + call + '?' ,
        type: BootstrapDialog.TYPE_DANGER,
        closable: true,
        draggable: true,
        btnOKClass: 'btn-danger',
        callback: function(result) {
            if(result) {
                $(".edit-dialog").modal('hide');
                $(".qso-dialog").modal('hide');
                $.ajax({
                    url: base_url + 'index.php/qso/delete_ajax',
                    type: 'post',
                    data: {'id': id
                    },
                    success: function(data) {
                        $(".alert").remove();
                        $(".bootstrap-dialog-message").prepend('<div class="alert alert-danger"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>The contact with ' + call + ' has been deleted!</div>');
                        $("#qso_" + id).remove(); // removes qso from table in dialog
                    }
                });
            }
        }
    });
}

function qso_edit(id) {
    $.ajax({
        url: base_url + 'index.php/qso/edit_ajax',
        type: 'post',
        data: {'id': id
        },
        success: function(html) {
            // remove actions QSO menu //
            $('.menuOnResultTab').hide();
            $('.menuOnBody').remove();
            BootstrapDialog.show({
                title: lang_general_word_qso_data,
                cssClass: 'edit-dialog bg-black bg-opacity-50',
                size: BootstrapDialog.SIZE_WIDE,
                nl2br: false,
                message: html,
                onshown: function(dialog) {

                    $('[data-bs-toggle="tooltip"]').tooltip();

                    if ($('#dxcc_id_edit').val() == '291' || $('#dxcc_id_edit').val() == '110' || $('#dxcc_id_edit').val() == '6') {
                        $('#location_us_county_edit').show();
                    } else {    
                        $('#location_us_county_edit').hide();    
                    }

                    var state = $("#stateDropdownEdit option:selected").text();
                    if (state != "") {
                        $("#stationCntyInputEdit").prop('disabled', false);
                        selectize_usa_county('#stateDropdown', '#stationCntyInputEdit');
                    }

                    var unsupported_lotw_prop_modes = [];
                    $.ajax({
                       url: base_url + 'index.php/qso/unsupported_lotw_prop_modes',
                       type: 'get',
                       async: false,
                       success: function(data) {
                          unsupported_lotw_prop_modes = $.parseJSON(data);
                       },
                    });

                    $('#prop_mode_edit').change(function(){
                       if (unsupported_lotw_prop_modes.includes($('#prop_mode_edit').val())) {
                          $('#lotw_sent').prop('disabled', true);
                          $('#lotw_rcvd').prop('disabled', true);
                          $('*[id=lotw_propmode_hint]').each(function() {
                             $(this).html(lang_lotw_propmode_hint).fadeIn("slow");
                          });
                       } else {
                          $('#lotw_sent').prop('disabled', false);
                          $('#lotw_rcvd').prop('disabled', false);
                          $('*[id=lotw_propmode_hint]').each(function() {
                             $(this).html("&nbsp;").fadeIn("fast");
                          });
                       }
                    });

                    $('#stateDropdownEdit').change(function(){
                        var state = $("#stateDropdownEdit option:selected").text();
                        if (state != "") {
                            $("#stationCntyInputEdit").prop('disabled', false);

                            selectize_usa_county('#stateDropdownEdit', '#stationCntyInputEdit');

                        } else {
                            $("#stationCntyInputEdit").prop('disabled', true);
                            $("#stationCntyInputEdit").val("");
                        }
                    });

                    $('#locator_edit, #ant_path_edit').on('change', function(){
                        if ($('#locator_edit').val().length >= 4) {
                            $.ajax({
                               url: base_url + 'index.php/logbook/searchbearing',
                               type: 'post',
                               data: {
                                  grid: $('#locator_edit').val(),
                                  ant_path: $('#ant_path_edit').val(),
                                  stationProfile: $('#stationProfile').val()
                               },
                               success: function(data) {
                                  $('#locator_info_edit').html(data).fadeIn("slow");
                               },
                               error: function() {
                                  $('#locator_info_edit').text("Error loading bearing!").fadeIn("slow");
                               },
                            });
                            $.ajax({
                               url: base_url + 'index.php/logbook/searchdistance',
                               type: 'post',
                               data: {
                                  grid: $('#locator_edit').val(),
                                  ant_path: $('#ant_path_edit').val(),
                                  stationProfile: $('#stationProfile').val()
                               },
                               success: function(data) {
                                  $("#distance").val(parseFloat(data));
                               },
                               error: function() {
                                  $('#distance').val('');
                               },
                            });
                        } else if ($('#locator_edit').val().length == 0) {
                           $('#locator_info_edit').fadeOut("slow");
                           $('#distance').val('');
                        }
                    }).trigger('change'); // we also run this when the dom is ready, Trick 17 ;-)

                    $('#vucc_grids').change(function(){
                        if ($(this).val().length >= 9) {
                            $.ajax({
                               url: base_url + 'index.php/logbook/searchbearing',
                               type: 'post',
                               data: {
                                  grid: $(this).val(),
                                  stationProfile: $('#stationProfile').val()
                               },
                               success: function(data) {
                                  $('#locator_info_edit').html(data).fadeIn("slow");
                               },
                               error: function() {
                                  $('#locator_info_edit').text("Error loading bearing!").fadeIn("slow");
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
                                  $("#distance").val(parseFloat(data));
                               },
                               error: function() {
                                  $("#distance").val('');
                               },
                            });
                        } else if ($(this).val().length == 0) {
                           $('#locator_info_edit').fadeOut("slow");
                           $("#distance").val('');
                        }
                    });

                    $('#sota_ref_edit').selectize({
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
                        }
                    });

                    $('#wwff_ref_edit').selectize({
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
                        }
                    });

                    $('#pota_ref_edit').selectize({
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
                        }
                    });

                    $('#darc_dok_edit').selectize({
                        maxItems: 1,
                        closeAfterSelect: true,
                        loadThrottle: 250,
                        valueField: 'name',
                        labelField: 'name',
                        searchField: 'name',
                        options: [],
                        create: true,
                        load: function(query, callback) {
                            if (!query) return callback();  // Only trigger if 3 or more characters are entered
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
                    // [eQSL default msg] change value (for qso edit page) //
                    $('.modal-content #stationProfile').change(function() {
                        qso_set_eqsl_qslmsg($('.modal-content #stationProfile').val(),false,'.modal-content');
                    });
                    $('.modal-content .qso_eqsl_qslmsg_update').off('click').on('click',function() {
                        qso_set_eqsl_qslmsg($('.modal-content #stationProfile').val(),true,'.modal-content');
                        $('.modal-content #charsLeft').text(" ");
                    });
                    $('.modal-content #qslmsg').keyup(function(event) {
                        calcRemainingChars(event, '.modal-content');
                    });

                    $("#dxcc_id_edit").change(async function () {
                        await updateStateDropdown('#dxcc_id_edit', '#stateInputLabelEdit', '#location_us_county_edit', '#stationCntyInputEdit', '#stateDropdownEdit');
                    });
                },
            });
        }
    });
}

function qso_save() {
    var myform = $("#qsoform")[0];
    var fd = new FormData(myform);
    $.ajax({
        url: base_url + 'index.php/qso/qso_save_ajax',
        data: fd,
        cache: false,
        processData: false,
        contentType: false,
        type: 'POST',
        success: function (dataofconfirm) {
            $(".edit-dialog").modal('hide');
            $(".qso-dialog").modal('hide');
            if (reload_after_qso_safe == true) {
                location.reload();
            }
        },
        error: function(xhr, status, error) {
            console.log(xhr.responseText);
        }
    });
}

function selectize_usa_county(state_field, county_field) {
    $(county_field).selectize({
        delimiter: ';',
        maxItems: 1,
        closeAfterSelect: true,
        loadThrottle: 250,
        valueField: 'name',
        labelField: 'name',
        searchField: 'name',
        options: [],
        create: false,
        load: function(query, callback) {
            var state = $(state_field + ' option:selected').text();

            if (!query || state == "") return callback();
            $.ajax({
                url: base_url + 'index.php/lookup/get_county',
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
}

async function updateStateDropdown(dxcc_field, state_label, county_div, county_input, dropdown = '#stateDropdown') {
    var selectedDxcc = $(dxcc_field);

    if (selectedDxcc.val() !== "") {
        await $.ajax({
            url: base_url + "index.php/lookup/get_state_list",
            type: "POST",
            data: { dxcc: selectedDxcc.val() },
            success: function (response) {
                if (response.status === "ok") {
                    statesDropdown(response, set_state, dropdown);
                    $(state_label).html(response.subdivision_name);
                } else {
                    statesDropdown(response);
                    $(state_label).html('State');
                }
            },
            error: function () {
                console.log('ERROR', response.status);
            },
        });
    }

    if (selectedDxcc.val() == '291' || selectedDxcc.val() == '110' || selectedDxcc.val() == '6') {
        $(county_div).show();
    } else {
        $(county_div).hide();
        $(county_input).val();
    }
}

function spawnQrbCalculator(locator1, locator2) {
	$.ajax({
		url: base_url + 'index.php/qrbcalc',
		type: 'post',
		success: function (html) {
			BootstrapDialog.show({
				title: lang_qrbcalc_title,
				size: BootstrapDialog.SIZE_WIDE,
				cssClass: 'lookup-dialog bg-black bg-opacity-50',
				nl2br: false,
				message: html,
				onshown: function(dialog) {
                    if (locator1 !== undefined) {
                        $("#qrbcalc_locator1").val(locator1);
                    }
                    if (locator2 !== undefined) {
                        $("#qrbcalc_locator2").val(locator2);
                        calculateQrb();
                    }
				},
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

function spawnActivatorsMap(call, count, grids) {
	$.ajax({
		url: base_url + 'index.php/activatorsmap',
		type: 'post',
		success: function (html) {
			BootstrapDialog.show({
				title: lang_activators_map,
				size: BootstrapDialog.SIZE_WIDE,
				cssClass: 'lookup-dialog',
				nl2br: false,
				message: html,
				onshown: function(dialog) {
					showActivatorsMap(call, count, grids);
				},
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

function calculateQrb() {
    let locator1 = $("#qrbcalc_locator1").val();
    let locator2 = $("#qrbcalc_locator2").val();

    $(".qrbalert").remove();

    if (validateLocator(locator1) && validateLocator(locator2)) {
        $.ajax({
            url: base_url+'index.php/qrbcalc/calculate',
            type: 'post',
            data: {'locator1': locator1,
                    'locator2': locator2},
            success: function (html) {

                var result = "<h5>" + html['latlong_info_text'] + "<br><br>";
                result += html['text_latlng1'] + '<br>';
                result += html['text_latlng2'] + '<br><br>';
                result += html['distance'] + ' ';
                result += html['bearing'] + '</h5>';

                $(".qrbResult").html(result);
                newpath(html['latlng1'], html['latlng2'], locator1, locator2);
            }
        });
    } else {
        $("#mapqrb").hide();
        $('.qrbResult').html('<div class="qrbalert alert alert-danger" role="alert">' + lang_qrbcalc_errmsg + '</div>');
    }
}

function validateLocator(locator) {
    const regex = /^[A-R]{2}[0-9]{2}([A-X]{2}([0-9]{2}([A-X]{2})?)?)?$/i;
    const locators = locator.split(",");

    if (locators.length === 3 || locators.length > 4) {
        return false;
    }

    for (let i = 0; i < locators.length; i++) {
        let loc = locators[i].trim();

        if (!regex.test(loc)) {
            return false;
        }
    }

    return true;
}

// This displays the dialog with the form and it's where the resulttable is displayed
function spawnLookupModal(searchphrase, searchtype) {
	$.ajax({
		url: base_url + 'index.php/lookup',
		type: 'post',
		success: function (html) {
			BootstrapDialog.show({
				title: 'Quick lookup',
				size: BootstrapDialog.SIZE_WIDE,
				cssClass: 'lookup-dialog',
				nl2br: false,
				message: html,
				onshown: function(dialog) {
					$('#quicklookuptype').change(function(){
						var type = $('#quicklookuptype').val();
                        changeLookupType(type);
					});
                    if (searchtype !== undefined) {
                        $('#quicklookuptype').val(searchtype);
                        if (searchtype == 'dxcc') {
                            $("#quicklookupdxcc").val(searchphrase);
                        } else if (searchtype == 'iota') {
                            $("#quicklookupiota").val(searchphrase);
                        } else if (searchtype == 'cq') {
                            $("#quicklookupcqz").val(searchphrase);
						} else if (searchtype == 'itu') {
                            $("#quicklookupituz").val(searchphrase);
                        } else {
                            $("#quicklookuptext").val(searchphrase);
                        }
                        changeLookupType(searchtype);
                        getLookupResult(this.form);
                    }
				},
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

function changeLookupType(type) {
	$('#quicklookupdxcc').hide();
	$('#quicklookupiota').hide();
	$('#quicklookupcqz').hide();
	$('#quicklookupituz').hide();
	$('#quicklookupwas').hide();
	$('#quicklookuptext').hide();
    if (type == "dxcc") {
        $('#quicklookupdxcc').show();
    } else if (type == "iota") {
        $('#quicklookupiota').show();
    } else if (type == "vucc" || type == "sota" || type == "wwff" || type == "lotw") {
        $('#quicklookuptext').show();
    } else if (type == "cq") {
        $('#quicklookupcqz').show();
	} else if (type == "itu") {
        $('#quicklookupituz').show();
    } else if (type == "was") {
        $('#quicklookupwas').show();
    }
}

// This function executes the call to the backend for fetching queryresult and displays the table in the dialog
function getLookupResult() {
	$(".ld-ext-right").addClass('running');
	$(".ld-ext-right").prop('disabled', true);
	$.ajax({
		url: base_url + 'index.php/lookup/search',
		type: 'post',
		data: {
			type: $('#quicklookuptype').val(),
			dxcc: $('#quicklookupdxcc').val(),
			was:  $('#quicklookupwas').val(),
			grid: $('#quicklookuptext').val(),
			cqz:  $('#quicklookupcqz').val(),
			iota: $('#quicklookupiota').val(),
			sota: $('#quicklookuptext').val(),
			wwff: $('#quicklookuptext').val(),
			lotw: $('#quicklookuptext').val(),
			ituz: $('#quicklookupituz').val(),
		},
		success: function (html) {
			$('#lookupresulttable').html(html);
			$(".ld-ext-right").removeClass('running');
			$(".ld-ext-right").prop('disabled', false);
		}
	});
}

// This function executes the call to the backend for fetching dxcc summary and inserted table below qso entry
function getDxccResult(dxcc, name) {
	$.ajax({
		url: base_url + 'index.php/lookup/search',
		type: 'post',
		data: {
			type: 'dxcc',
			dxcc: dxcc,
            reduced_mode: true,
            current_band: $('#band').val(),
            current_mode: $('#mode').val(),
		},
		success: function (html) {
            $('.dxccsummary').remove();
            $('.qsopane').append('<div class="dxccsummary col-sm-12"><br><div class="card"><div class="card-header dxccsummaryheader" data-bs-toggle="collapse" data-bs-target=".dxccsummarybody">' + lang_dxccsummary_for + name + '</div><div class="card-body collapse dxccsummarybody"></div></div></div>');
            $('.dxccsummarybody').append(html);
			$('.dxccsummaryheader').click(function(){
				$('.dxccsummaryheader').toggleClass('dxccsummaryheaderopened');
			});
		}
	});
}

function displayQsl(id) {
    $.ajax({
		url: base_url + 'index.php/qsl/viewQsl',
		type: 'post',
        data: {
			id: id,
		},
		success: function (html) {
			BootstrapDialog.show({
				title: 'QSL Card',
				size: BootstrapDialog.SIZE_WIDE,
				cssClass: 'lookup-dialog',
				nl2br: false,
				message: html,
				onshown: function(dialog) {

				},
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


// [eQSL default msg] function to load default qslmsg to qslmsg field on qso add/edit //
function qso_set_eqsl_qslmsg(station_id, force_diff_to_origin=false, object='') {
    $.ajax({
        url: base_url+'index.php/qso/get_eqsl_default_qslmsg',
        type: 'post', data: {'option_key':station_id },
        success: function(res) {
            if (typeof res.eqsl_default_qslmsg !== "undefined") {
                object = (object!='')?(object+' '):'';
                if ((force_diff_to_origin) || ($(object+'#qslmsg').val()==$(object+'#qslmsg_hide').html())) {
                    $(object+'#qslmsg').val(res.eqsl_default_qslmsg);
                    $(object+'#qslmsg_hide').html(res.eqsl_default_qslmsg);
                }
            }
        },
        error: function() { },
    });
}

// [PWD] button show/hide //
function btn_pwd_showhide() {
	if ($(this).closest('div').find('input[type="password"]').length>0) {
        $(this).closest('div').find('input[type="password"]').attr('type','text');
        $(this).closest('div').find('.fa-eye-slash').removeClass('fa-eye-slash').addClass('fa-eye');
	} else {
        $(this).closest('div').find('input[type="text"]').attr('type','password');
        $(this).closest('div').find('.fa-eye').removeClass('fa-eye').addClass('fa-eye-slash');
	}
}
$('.user_edit .btn-pwd-showhide').off('click').on('click', btn_pwd_showhide );

// [QSO] show/hide actions menu on qso list  (_this = div.dropdown actived) //
function showQsoActionsMenu(_this) {
    $('.menuOnResultTab').hide();
    $('.menuOnBody').remove();
    var _id = _this.find('.menuOnResultTab').attr('data-qsoid');
    var _dropdownMenuClone = _this.find('.menuOnResultTab[data-qsoid="'+ _id +'"]').clone();
    _dropdownMenuClone.removeClass('menuOnResultTab').addClass('menuOnBody');
    $('body').append(_dropdownMenuClone);
    var _dropdownMenu = _this.find('.menuOnResultTab[data-qsoid="'+ _id +'"]');
    var eOffset = _this.offset();
    if ((eOffset.top - $(window).scrollTop() + _dropdownMenu.outerHeight()) >= ($(window).height()-50)) {
            _topMenu = eOffset.top - _dropdownMenu.outerHeight();
        } else {
            _topMenu = eOffset.top + _this.outerHeight();
        }
        _dropdownMenuClone.css({
        'top': _topMenu,
        'left': eOffset.left - _dropdownMenu.width() + _this.find('.dropdown-toggle').outerWidth(),
        'display': 'block',
    });
    _dropdownMenuClone.off('mouseenter').on('mouseenter', function () {
        _dropdownMenuClone.attr('data-mouseenteronmenu','1');
    });
    _this.off('mouseleave').on('mouseleave', function () {
        setTimeout(function(){ if (_dropdownMenuClone.attr('data-mouseenteronmenu')!='1') { _dropdownMenuClone.remove();}  }, 200);
    });
    _dropdownMenuClone.off('mouseleave').on('mouseleave', function () {
        $(this).remove();
    });
    _dropdownMenuClone.find('a').off('click').on('click', function () {
        if ($(this).is(':first-child') || $(this).is(':last-child')) { // Only for edit & delete action //
            $(this).closest('.menuOnResultTab').remove();
        }
    });
}

if ($('.table-responsive .dropdown-toggle').length>0) {
    $('.table-responsive .dropdown-toggle').off('mouseenter').on('mouseenter', function () {
        showQsoActionsMenu($(this).closest('.dropdown'));
    });
}

var set_state;
function statesDropdown(states, set_state = null, dropdown = '#stateDropdown') {
    var dropdown = $(dropdown);
    dropdown.empty();
    dropdown.append($('<option>', {
        value: ''
    }));
    if (states.status == 'ok') {
        dropdown.prop('disabled', false);
        $.each(states.data, function(index, state) {
            var option = $('<option>', {
                value: state.state,
                text: state.subdivision + ' (' + state.state + ')'
            });
            dropdown.append(option);
        });
        $(dropdown).val(set_state);
    } else {
        dropdown.empty();
        var option = $('<option>', {
            value: '',
            text: lang_no_states_for_dxcc_available
        });
        dropdown.append(option);
        dropdown.prop('disabled', true);
    }
}

// Location Quickswitcher
function quickswitcher_show_activebadge(current_active) {
    $('#quickswitcher_active_badge_' + current_active).removeClass('d-none');
    $('#quickswitcher_list_button_' + current_active).addClass('disabled');
}

function current_active_ajax(callback) {
    $.ajax({
        url: base_url + 'index.php/stationsetup/getActiveStation',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            var current_active = response;
            callback(current_active);
        }
    });
}

function set_active_loc_quickswitcher(new_active) {
    current_active_ajax(function(current_active) {
        $.ajax({
            url: base_url + 'index.php/stationsetup/setActiveStation_json',
            type: 'POST',
            dataType: 'json',
            data: {
                id2setActive: new_active
            },
            success: function(response) {
                $('[id^="quickswitcher_list_button_"]').not('#quickswitcher_list_button_' + new_active).removeClass('disabled');
                $('[id^="quickswitcher_active_badge_"]').not('#quickswitcher_active_badge_' + new_active).addClass('d-none');

                $('#quickswitcher_list_button_' + new_active).addClass('disabled');
                $('#quickswitcher_active_badge_' + new_active).removeClass('d-none');


                // if we are on the stationsetup page the function reloadStations exists and we can run it
                if (typeof reloadStations === 'function') {
                    reloadStations();
                }

                // If the user is in the QSO or SimpleFLE view we change the station in the QSO input aswell
                if (window.location.pathname.indexOf("qso") !== -1 ||
                    window.location.pathname.indexOf("simplefle") !== -1) {

                    if ($('#stationProfile option[value="' + new_active + '"]').length > 0) {
                        $('#stationProfile').val(new_active);
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Error while setting the new active location: ' + error);
            }
        });
    });
}

$(document).ready(function() {
    if ($('#utc_header').length > 0) {
        function getCurrentUTCTime() {
            var now = new Date();
            var hours = now.getUTCHours().toString().padStart(2, '0');
            var minutes = now.getUTCMinutes().toString().padStart(2, '0');
            var seconds = now.getUTCSeconds().toString().padStart(2, '0');
            return hours + ':' + minutes + ':' + seconds;
        }

        function updateUTCTime() {
            $('#utc_header').text(getCurrentUTCTime() + 'z');
        }

        setInterval(updateUTCTime, 1000);
        updateUTCTime();
    }
});

// auto setting of gridmap height
function set_map_height() {
    //header menu
    var headerNavHeight = $('nav').outerHeight();
    // console.log('nav: ' + headerNavHeight);

    // line with coordinates
    var coordinatesHeight = $('.coordinates').outerHeight();
    // console.log('.coordinates: ' + coordinatesHeight);

    // form for gridsquare map
    var gridsquareFormHeight = $('.gridsquare_map_form').outerHeight();
    // console.log('.gridsquare_map_form: ' + gridsquareFormHeight);

    // calculate correct map height
    var gridsquareMapHeight = window.innerHeight - headerNavHeight - coordinatesHeight - gridsquareFormHeight;

    // and set it
    $('#gridsquare_map').css('height', gridsquareMapHeight + 'px');
    // console.log('#gridsquare_map: ' + gridsquareMapHeight);
}

function newpath(latlng1, latlng2, locator1, locator2) {
    // If map is already initialized
    var container = L.DomUtil.get('mapqrbcontainer');

    if(container != null){
        container._leaflet_id = null;
        container.remove();
        $("#mapqrb").append('<div id="mapqrbcontainer" style="Height: 500px"></div>');
    }

    var map = new L.Map('mapqrbcontainer', {
        fullscreenControl: true,
        fullscreenControlOptions: {
          position: 'topleft'
        },
      }).setView([30, 0], 1.5);

    // Need to fix so that marker is placed at same place as end of line, but this only needs to be done when longitude is < -170
    if (latlng2[1] < -170) {
        latlng2[1] =  parseFloat(latlng2[1])+360;
    }
    if (latlng1[1] < -170) {
        latlng1[1] =  parseFloat(latlng1[1])+360;
    }

	if ((latlng1[1] - latlng2[1]) < -180) {
		latlng2[1] = parseFloat(latlng2[1]) -360;
	} else if ((latlng1[1] - latlng2[1]) > 180) {
		latlng2[1] = parseFloat(latlng2[1]) +360;
	}

    map.fitBounds([
        [latlng1[0], latlng1[1]],
        [latlng2[0], latlng2[1]]
    ]);

    var maidenhead = L.maidenheadqrb().addTo(map);

    var osmUrl = option_map_tile_server;
    var osmAttrib= option_map_tile_server_copyright;
    var osm = new L.TileLayer(osmUrl, {minZoom: 1, maxZoom: 12, attribution: osmAttrib});

    var redIcon = L.icon({
					iconUrl: icon_dot_url,
					iconSize:     [10, 10], // size of the icon
				});

    map.addLayer(osm);

    var marker = L.marker([latlng1[0], latlng1[1]], {closeOnClick: false, autoClose: false}).addTo(map).bindPopup(locator1);

    var marker2 = L.marker([latlng2[0], latlng2[1]], {closeOnClick: false, autoClose: false}).addTo(map).bindPopup(locator2);

    const multiplelines = [];
		multiplelines.push(
            new L.LatLng(latlng1[0], latlng1[1]),
            new L.LatLng(latlng2[0], latlng2[1])
        )

    const geodesic = L.geodesic(multiplelines, {
        weight: 3,
        opacity: 1,
        color: 'red',
        wrap: false,
        steps: 100
    }).addTo(map);
}

function disableMap() {
    // console.log('disable map');
    map.dragging.disable();
    map.scrollWheelZoom.disable();
    map.touchZoom.disable();
    map.doubleClickZoom.disable();
    map.boxZoom.disable();
    map.keyboard.disable();
}

function enableMap() {
    // console.log('enable map');
    map.dragging.enable();
    map.scrollWheelZoom.enable();
    map.touchZoom.enable();
    map.doubleClickZoom.enable();
    map.boxZoom.enable();
    map.keyboard.enable();
}

console.log("Ready to unleash your coding prowess and join the fun?\n\n" +
    "Check out our GitHub Repository and dive into the coding adventure:\n\n" +
    "🚀 https://www.github.com/wavelog/wavelog");
