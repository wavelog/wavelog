$(document).ready(function () {
	reloadStations();

	$("#station_locations_table").DataTable({
		stateSave: true,
		language: {
			url: getDataTablesLanguageUrl(),
		},
	});

	$(document).on('click','.newLogbook', function (e) {	// Dynamic binding, since element doesn't exists when loading this JS
		$.ajax({
			url: base_url + 'index.php/stationsetup/newLogbook_json',
			type: 'post',
			data: {
				'stationLogbook_Name': $("#logbook_name").val(),
			},
			success: function(data) {
				jdata=JSON.parse(data);
				if (jdata.success == 1) {
					$("#NewStationLogbookModal").modal('hide');
					reloadLogbooks();
				} else {
					$("#flashdata").html(jdata.flashdata);
				}
			},
			error: function(e) {
				$("#flashdata").html("An unknown Error occured");
			}
		});

	});

	$(document).on('click', '.deleteLogbook', async function (e) {	// Dynamic binding, since element doesn't exists when loading this JS
		await do_ajax('deleteLogbook_json', 'id2delete', reloadLogbooks,e);
	});

	$(document).on('click', '.setActiveLogbook', async function (e) {	// Dynamic binding, since element doesn't exists when loading this JS
		await do_ajax('setActiveLogbook_json', 'id2setActive', reloadLogbooks,e);
	});

	$(document).on('click', '.setActiveStation', async function (e) {	// Dynamic binding, since element doesn't exists when loading this JS
		await do_ajax('setActiveStation_json', 'id2setActive', reloadStations,e);
	});

	$(document).on('click', '.DeleteStation', async function (e) {	// Dynamic binding, since element doesn't exists when loading this JS
		await do_ajax('DeleteStation_json', 'id2del', reloadStations,e);
	});

	$(document).on('click', '.EmptyStation', async function (e) {	// Dynamic binding, since element doesn't exists when loading this JS
		await do_ajax('EmptyStation_json', 'id2Empty', reloadStations,e);
	});

	$(document).on('click', '.setFavorite', async function (e) {	// Dynamic binding, since element doesn't exists when loading this JS
		await do_ajax('setFavorite_json', 'id2Favorite', reloadStations,e);
	});

	$(document).on('click', '.editContainerName', async function (e) {	// Dynamic binding, since element doesn't exists when loading this JS
		editContainerDialog(e);
	});

	$(document).on('click', '.editLinkedLocations', async function (e) {	// Dynamic binding, since element doesn't exists when loading this JS
		editLinkedLocations(e);
	});

	$(document).on('click', '.editVisitorLink', async function (e) {	// Dynamic binding, since element doesn't exists when loading this JS
		editVisitorLink(e);
	});

	$(document).on('click', '.deletePublicSlug', async function (e) {	// Dynamic binding, since element doesn't exists when loading this JS
		await do_ajax('remove_publicslug', 'id', reloadLogbooks, e);
	});

	$(document).on('click', '.publicSearchCheckbox', async function (e) {	// Dynamic binding, since element doesn't exists when loading this JS
		togglePublicSearch(e.currentTarget.id, this);
	});
	$(document).on('click', '.editExportmapOptions', async function (e) {	// Dynamic binding, since element doesn't exists when loading this JS
		editExportmapDialog(e.currentTarget.id);
	});

	$("#station_logbooks_table").DataTable({
		stateSave: true,
		language: {
			url: getDataTablesLanguageUrl(),
		},
	});
});

	async function do_ajax(ajax_uri, ajax_field, succ_callback, event_target) {
		if (event_target.currentTarget.attributes.cnftext) {
			if (!(confirm(event_target.currentTarget.attributes.cnftext.value))) {
				return false;
			}
		}
		$.ajax({
			url: base_url + 'index.php/stationsetup/' + ajax_uri,
			type: 'post',
			data: {
				[ajax_field]: event_target.currentTarget.id,
			},
			success: function(data) {
				jdata=JSON.parse(data);
				if (jdata.success == 1) {
					succ_callback();

					// we also want to switch the active badge in the quickswitcher if a new active location is set
					if (ajax_uri == 'setActiveStation_json') {
						set_active_loc_quickswitcher(event_target.currentTarget.id);
					}
				} else {
					$("#flashdata").html(jdata.flashdata);
				}
			},
			error: function(e) {
				$("#flashdata").html("An unknown Error occured");
			}
		});
	}

	function togglePublicSearch(id, thisvar) {
		$.ajax({
			url: base_url + 'index.php/stationsetup/togglePublicSearch',
			type: 'post',
			data: {
				id: id,
				checked: $(thisvar).is(':checked')
			},
			success: function (data) {
				reloadLogbooks();
			},
			error: function (data) {

			},
		});
		return false;
	}

	function editExportmapDialog(id) {
		$.ajax({
			url: base_url + 'index.php/stationsetup/editExportmapOptions',
			type: 'post',
			data: {
				id: id,
			},
			success: function (data) {
				BootstrapDialog.show({
					title: 'Edit Export Map options',
					size: BootstrapDialog.SIZE_NORMAL,
					cssClass: 'options',
					id: "NewStationLogbookModal",
					nl2br: false,
					message: data,
					onshown: function(dialog) {
					},
					buttons: [{
						label: 'Save',
						cssClass: 'btn-primary btn-sm saveExportmapOptions',
						action: function (dialogItself) {
							saveExportmapOptions();
							dialogItself.close();
						}
					},
					{
						label: lang_admin_close,
						cssClass: 'btn-sm',
						id: 'closeButton',
						action: function (dialogItself) {
							dialogItself.close();
						}
					}],
				});
			},
			error: function (data) {

			},
		});
		return false;
	}

	function saveExportmapOptions() {
		$('#saveButton').prop("disabled", true);
		$('#closeButton').prop("disabled", true);
		$.ajax({
			url: base_url + 'index.php/stationsetup/saveExportmapOptions',
			type: 'post',
			data: {
				gridsquare_layer: $('input[name="gridsquare_layer"]').is(':checked') ? true : false,
				path_lines: $('input[name="path_lines"]').is(':checked') ? true : false,
				cqzone_layer: $('input[name="cqzone_layer"]').is(':checked') ? true : false,
				nightshadow_layer: $('input[name="nightshadow_layer"]').is(':checked') ? true : false,
				qsocount: $('#qsocount').val(),
				band: $('#band').val(),
				id: $('#logbookid').val(),
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


	function editContainerDialog(e) {
		$.ajax({
			url: base_url + 'index.php/stationsetup/editContainerName',
			type: 'post',
			data: {
				id: e.currentTarget.id,
			},
			success: function (data) {
				BootstrapDialog.show({
					title: 'Edit container name',
					size: BootstrapDialog.SIZE_NORMAL,
					cssClass: 'options',
					id: "NewStationLogbookModal",
					nl2br: false,
					message: data,
					onshown: function(dialog) {
					},
					buttons: [{
						label: 'Save',
						cssClass: 'btn-primary btn-sm saveContainerName',
						action: function (dialogItself) {
							saveContainerName();
							dialogItself.close();
						}
					},
					{
						label: lang_admin_close,
						cssClass: 'btn-sm',
						id: 'closeButton',
						action: function (dialogItself) {
							dialogItself.close();
						}
					}],
				});
			},
			error: function (data) {

			},
		});
		return false;
	}

	function saveContainerName() {
		$.ajax({
			url: base_url + 'index.php/stationsetup/saveContainerName',
			type: 'post',
			data: {
				id: $('#logbookid').val(),
				name: $('#logbook_name').val()
			},
			success: function (data) {
				reloadLogbooks();
			},
			error: function (data) {

			},
		});
	}

	function editLinkedLocations(e) {
		$.ajax({
			url: base_url + 'index.php/stationsetup/editLinkedLocations',
			type: 'post',
			data: {
				id: e.currentTarget.id,
			},
			success: function (data) {
				BootstrapDialog.show({
					title: 'Edit linked locations',
					size: BootstrapDialog.SIZE_WIDE,
					cssClass: 'options',
					id: "NewStationLogbookModal",
					nl2br: false,
					message: data,
					closable: false,
					closeByBackdrop: false,
					closeByKeyboard: false,
					onshown: function(dialog) {
					},
					buttons: [{
						label: lang_admin_close,
						cssClass: 'btn-sm',
						id: 'closeButton',
						action: function (dialogItself) {
							dialogItself.close();
							reloadStations();
						}
					}],
				});
			},
			error: function (data) {

			},
		});
		return false;
	}

	function editVisitorLink(e) {
		$.ajax({
			url: base_url + 'index.php/stationsetup/editVisitorLink',
			type: 'post',
			data: {
				id: e.currentTarget.id,
			},
			success: function (data) {
				BootstrapDialog.show({
					title: 'Edit visitor link',
					size: BootstrapDialog.SIZE_NORMAL,
					cssClass: 'options',
					id: "NewStationLogbookModal",
					nl2br: false,
					message: data,
					onshown: function(dialog) {
					},
					buttons: [{
						label: 'Save',
						cssClass: 'btn-primary btn-sm visitorSaveButton',
						action: function (dialogItself) {
							saveVisitorLink(dialogItself);
						}
					},
					{
						label: lang_admin_close,
						cssClass: 'btn-sm',
						id: 'closeButton',
						action: function (dialogItself) {
							dialogItself.close();
						}
					}],
				});
			},
			error: function (data) {

			},
		});
		return false;
	}

	function saveVisitorLink(dialogItself) {
		$('.visitorSaveButton').prop("disabled", true);
		$('.alertvisitor').remove();
		if (/^([a-zA-Z0-9-]+)$/.test($('#publicSlugInput').val())) {
			$.ajax({
				url: base_url + 'index.php/stationsetup/saveVisitorLink',
				type: 'post',
				data: {
					id: $('#logbook_id').val(),
					name: $('#publicSlugInput').val()
				},
				success: function (data) {
					jdata=JSON.parse(data);
					if (jdata.success == 1) {
						dialogItself.close();
						reloadLogbooks();
					} else {
						$('#visitorLinkInfo').append('<div class="alertvisitor alert alert-danger" role="alert">'+jdata.flashdata+'</div>');
						$('.visitorSaveButton').prop("disabled", false);
					}

				},
				error: function (data) {
					$('.visitorSaveButton').prop("disabled", false);
				},
			});
		} else {
			$('.visitorSaveButton').prop("disabled", false);
			$('#visitorLinkInfo').append('<div class="alertvisitor alert alert-danger" role="alert">Invalid characters entered in link!</div>');
		}
		return false;
	}


function reloadLogbooks() {
	$.ajax({
		url: base_url + 'index.php/stationsetup/fetchLogbooks',
		type: 'post',
		dataType: 'json',
		success: function (data) {
			loadLogbookTable(data);
			reloadStations();
		},
		error: function (data) {
			BootstrapDialog.alert({
				title: 'ERROR',
				message: 'An error ocurred while making the request',
				type: BootstrapDialog.TYPE_DANGER,
				closable: false,
				draggable: false,
				callback: function (result) {
				}
			});
		},
	});
	return false;
}

function reloadStations() {
	$.ajax({
		url: base_url + 'index.php/stationsetup/fetchLocations',
		type: 'post',
		dataType: 'json',
		success: function (data) {
			loadLocationTable(data);
		},
		error: function (data) {
			BootstrapDialog.alert({
				title: 'ERROR',
				message: 'An error ocurred while making the request',
				type: BootstrapDialog.TYPE_DANGER,
				closable: false,
				draggable: false,
				callback: function (result) {
				}
			});
		},
	});
	return false;
}

function createStationLogbook() {
	$.ajax({
		url: base_url + 'index.php/stationsetup/newLogbook',
		type: 'post',
		success: function (html) {
			BootstrapDialog.show({
				title: 'Create a new station logbook',
				size: BootstrapDialog.SIZE_NORMAL,
				cssClass: 'options',
				id: "NewStationLogbookModal",
				nl2br: false,
				message: html,
				onshown: function(dialog) {
				},
				buttons: [{
					label: 'Save',
					cssClass: 'btn-primary btn-sm newLogbook',
					id: 'saveButtonNewLogbook',
				},
				{
					label: lang_admin_close,
					cssClass: 'btn-sm',
					id: 'closeButton',
					action: function (dialogItself) {
						dialogItself.close();
					}
				}],
				onhide: function(dialogRef){
					$('#optionButton').prop("disabled", false);
				},
			});
		}
	});
}

function createStationLocation() {
	$.ajax({
		url: base_url + 'index.php/stationsetup/newLocation',
		type: 'post',
		success: function (html) {
			BootstrapDialog.show({
				title: 'Create a new station location',
				size: BootstrapDialog.SIZE_EXTRAWIDE,
				cssClass: 'options',
				nl2br: false,
				message: html,
				onshown: function(dialog) {
				},
				buttons: [{
					label: 'Save',
					cssClass: 'btn-primary btn-sm',
					id: 'saveButton',
					action: function (dialogItself) {
						dialogItself.close();
					}
				},
				{
					label: lang_admin_close,
					cssClass: 'btn-sm',
					id: 'closeButton',
					action: function (dialogItself) {
						dialogItself.close();
					}
				}],
				onhide: function(dialogRef){
					$('#optionButton').prop("disabled", false);
				},
			});
		}
	});
}

function loadLogbookTable(rows) {
	var uninitialized = $('#station_logbooks_table').filter(function() {
		return !$.fn.DataTable.isDataTable(this);
	});

	uninitialized.each(function() {
		$(this).DataTable({
			searching: false,
			responsive: false,
			ordering: true,
			"scrollY": window.innerHeight - $('#searchForm').innerHeight() - 250,
			"scrollCollapse": true,
			"paging":         false,
			"scrollX": true,
			"language": {
				url: getDataTablesLanguageUrl(),
			},
		});
	});

	var table = $('#station_logbooks_table').DataTable();

	table.clear();

	for (i = 0; i < rows.length; i++) {
		let logbook = rows[i];

		var data = [];
		data.push(logbook.logbook_name);
		data.push(logbook.logbook_state);
		data.push(logbook.logbook_edit);
		data.push(logbook.logbook_delete);
		data.push(logbook.logbook_link);
		data.push(logbook.logbook_publicsearch);

		let createdRow = table.row.add(data).index();
	}
	table.draw();
	$('[data-bs-toggle="tooltip"]').tooltip();
}

function loadLocationTable(rows) {
	var uninitialized = $('#station_locations_table').filter(function() {
		return !$.fn.DataTable.isDataTable(this);
	});

	uninitialized.each(function() {
		$(this).DataTable({
			searching: false,
			responsive: false,
			ordering: true,
			"scrollY": window.innerHeight - $('#searchForm').innerHeight() - 250,
			"scrollCollapse": true,
			"paging":         false,
			"scrollX": true,
			"language": {
				url: getDataTablesLanguageUrl(),
			},
			'columnDefs': [
				{ 'targets':0,
				  'createdCell':  function (td, cellData, rowData, row, col) {
				  			(td).attr('data-order', 1);	// not sure how to add ID dynamic here
						  }
				}
			]
		});
	});

	var table = $('#station_locations_table').DataTable();

	table.clear();

	for (i = 0; i < rows.length; i++) {
		let locations = rows[i];

		var data = [];

		data.push(locations.station_id);
		data.push(locations.station_name);
		data.push(locations.station_callsign);
		data.push(locations.station_country);
		data.push(locations.station_gridsquare);
		data.push(locations.station_badge);
		data.push(locations.station_linked);
		data.push(locations.station_edit);
		data.push(locations.station_copylog);
		if (locations.station_favorite != ''){
			data.push(locations.station_favorite);
		}
		data.push(locations.station_emptylog);
		data.push(locations.station_delete);

		let createdRow = table.row.add(data).index();
	}
	table.draw();
	$('[data-bs-toggle="tooltip"]').tooltip();
}

function linkLocations() {
	$('.linkLocationButton').prop("disabled", true);
	var locationid = $('#StationLocationSelect').val();
	var containerid = $('#station_logbook_id').val();
	var locationtext = $('#StationLocationSelect option:selected').text();
	var locationarray = locationtext.split(" ");

	if (locationid == null) return;

	$.ajax({
		url: base_url + 'index.php/stationsetup/linkLocations',
		type: 'post',
		data: {
			containerid: containerid,
			locationid: locationid
		},
		success: function(data) {
			jdata=JSON.parse(data);
			if (jdata.success == 1) {
				$("#StationLocationSelect").find('[value="'+ locationid +'"]').remove();
				// add to table
				$('#station_logbooks_linked_table').append($('<tr id="locationid_'+locationid+'">')
					.append($('<td style="text-align: center; vertical-align: middle;">').append(jdata.locationdata[0].station_profile_name))
					.append($('<td style="text-align: center; vertical-align: middle;">').append(jdata.locationdata[0].station_callsign))
					.append($('<td style="text-align: center; vertical-align: middle;">').append(jdata.locationdata[0].station_country+(jdata.locationdata[0].dxcc_end == null ? '' : ' <span class="badge bg-danger">Deleted DXCC</span>')))
					.append($('<td style="text-align: center; vertical-align: middle;">').append('<button class="btn btn-sm btn-danger unlinkbutton" onclick="unLinkLocations('+containerid+','+locationid+');"><i class="fas fa-unlink"></i></button>'))
				)
			} else {
				$("#flashdata").html(jdata.flashdata);
			}
			$('.linkLocationButton').prop("disabled", false);
		},
		error: function(e) {
			$("#flashdata").html("An unknown Error occured");
			$('.linkLocationButton').prop("disabled", false);
		}
	});
}

function unLinkLocations(containerid, locationid) {
	$('.unlinkbutton').prop("disabled", true);
	$.ajax({
		url: base_url + 'index.php/stationsetup/unLinkLocations',
		type: 'post',
		data: {
			containerid: containerid,
			locationid: locationid
		},
		success: function (data) {
			jdata=JSON.parse(data);
			if (jdata.success == 1) {
				let row = $('#locationid_'+locationid);
				let cells = row.find('td');
				var items = [];
				items.push(
					'<option value="' + locationid + '">' + cells.eq(0).text() + ' (Callsign: ' + cells.eq(1).text() + ' DXCC: ' + cells.eq(2).text() + ')</option>'
				);
				$('#StationLocationSelect').append(items.join( "" ));
				$('#locationid_'+locationid).remove();
				$('.unlinkbutton').prop("disabled", false);
			} else {
				$("#flashdata").data(jdata.flashdata);
				$('.unlinkbutton').prop("disabled", false);
			}
		},
		error: function(e) {
			$("#flashdata").html("An unknown Error occured");
		}
	});
}

