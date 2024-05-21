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
                    var state = $("#stateDropdown option:selected").text();
                    if (state != "") {
                        $("#stationCntyInputEdit").prop('disabled', false);
                        selectize_usa_county('#stateDropdown', '#stationCntyInputEdit');
                    }

                    $('#stateDropdown').change(function(){
                        var state = $("#stateDropdown option:selected").text();
                        if (state != "") {
                            $("#stationCntyInputEdit").prop('disabled', false);

                            selectize_usa_county('#stateDropdown', '#stationCntyInputEdit');

                        } else {
                            $("#stationCntyInputEdit").prop('disabled', true);
                            $("#stationCntyInputEdit").val("");
                        }
                    });

                    $('#locator').change(function(){
                        if ($(this).val().length >= 4) {
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
                        } else if ($(this).val().length == 0) {
                           $('#locator_info').fadeOut("slow");
                           document.getElementById("distance").value = null;
                        }
                    });

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
                        } else if ($(this).val().length == 0) {
                           $('#locator_info').fadeOut("slow");
                           document.getElementById("distance").value = null;
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

                    $("#dxcc_id").change(async function () {
                        await updateStateDropdown('#dxcc_id', '#stateInputLabel', '#location_us_county', '#stationCntyInputEdit');
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

async function updateStateDropdown(dxcc_field, state_label, county_div, county_input) {
    var selectedDxcc = $(dxcc_field);

    if (selectedDxcc.val() !== "") {
        await $.ajax({
            url: base_url + "index.php/lookup/get_state_list",
            type: "POST",
            data: { dxcc: selectedDxcc.val() },
            success: function (response) {
                if (response.status === "ok") {
                    statesDropdown(response, set_state);
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
				title: 'Compute QRB and QTF',
				size: BootstrapDialog.SIZE_WIDE,
				cssClass: 'lookup-dialog',
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
				title: 'Activators Map',
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

                var result = "<h5>Negative latitudes are south of the equator, negative longitudes are west of Greenwich. <br/>";
                result += ' ' + locator1.toUpperCase() + ' Latitude = ' + html['latlng1'][0] + ' Longitude = ' + html['latlng1'][1] + '<br/>';
                result += ' ' + locator2.toUpperCase() + ' Latitude = ' + html['latlng2'][0] + ' Longitude = ' + html['latlng2'][1] + '<br/>';
                result += 'Distance between ' + locator1.toUpperCase() + ' and ' + locator2.toUpperCase() + ' is ' + html['distance'] + '.<br />';
                result += 'The bearing is ' + html['bearing'] + '.</h5>';

                $(".qrbResult").html(result);
                newpath(html['latlng1'], html['latlng2'], locator1, locator2);
            }
        });
    } else {
        $('.qrbResult').html('<div class="qrbalert alert alert-danger" role="alert">Error in locators. Please check.</div>');
    }
}

function validateLocator(locator) {
    vucc_gridno = locator.split(",").length;
    if(vucc_gridno == 3 || vucc_gridno > 4) {
        return false;
    }
    if(locator.length < 4 && !(/^[a-rA-R]{2}[0-9]{2}[a-xA-X]{0,2}[0-9]{0,2}[a-xA-X]{0,2}$/.test(locator))) {
        return false;
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
    if (type == "dxcc") {
        $('#quicklookupdxcc').show();
        $('#quicklookupiota').hide();
        $('#quicklookupcqz').hide();
        $('#quicklookupwas').hide();
        $('#quicklookuptext').hide();
    } else if (type == "iota") {
        $('#quicklookupiota').show();
        $('#quicklookupdxcc').hide();
        $('#quicklookupcqz').hide();
        $('#quicklookupwas').hide();
        $('#quicklookuptext').hide();
    } else if (type == "vucc" || type == "sota" || type == "wwff" || type == "lotw") {
        $('#quicklookuptext').show();
        $('#quicklookupiota').hide();
        $('#quicklookupdxcc').hide();
        $('#quicklookupcqz').hide();
        $('#quicklookupwas').hide();
    } else if (type == "cq") {
        $('#quicklookupcqz').show();
        $('#quicklookupiota').hide();
        $('#quicklookupdxcc').hide();
        $('#quicklookupwas').hide();
        $('#quicklookuptext').hide();
    } else if (type == "was") {
        $('#quicklookupwas').show();
        $('#quicklookupcqz').hide();
        $('#quicklookupiota').hide();
        $('#quicklookupdxcc').hide();
        $('#quicklookuptext').hide();
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
		},
		success: function (html) {
            $('.dxccsummary').remove();
            $('.qsopane').append('<div class="dxccsummary col-sm-12"><br><div class="card"><div class="card-header dxccsummaryheader" data-bs-toggle="collapse" data-bs-target=".dxccsummarybody">DXCC Summary for '+name+'</div><div class="card-body collapse dxccsummarybody"></div></div></div>');
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
function statesDropdown(states, set_state = null) {
    var dropdown = $('#stateDropdown');
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
            text: 'No states for this DXCC available'
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

                // If the user is in the QSO view we change the station in the QSO input aswell
                if (window.location.pathname.indexOf("qso") !== -1) {
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


console.log("Ready to unleash your coding prowess and join the fun?\n\n" +
    "Check out our GitHub Repository and dive into the coding adventure:\n\n" +
    "🚀 https://www.github.com/wavelog/wavelog");
