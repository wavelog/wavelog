$(document).ready(function() {
    loadPassSettingsList();

	$('#satlist').multiselect({
		// template is needed for bs5 support
		enableFiltering: true,
		enableCaseInsensitiveFiltering: true,
		filterPlaceholder: lang_general_word_search,
		templates: {
		  button: '<button type="button" class="multiselect dropdown-toggle btn btn-sm btn-secondary me-2 w-auto" data-bs-toggle="dropdown" aria-expanded="false"><span class="multiselect-selected-text"></span></button>',
		},
		numberDisplayed: 1,
		inheritClass: true,
		includeSelectAllOption: true
	});

	if (localStorage.hasOwnProperty(`user_${user_id}_selectedsatellites`)) {
		const selectedSatellites = localStorage.getItem(`user_${user_id}_selectedsatellites`);
		const satelliteArray = selectedSatellites ? selectedSatellites.split(',') : [];
		// First, deselect all options
		$('#satlist').multiselect('deselectAll', false);

		// Then, select the stored locations
		$('#satlist').multiselect('select', satelliteArray);
	}

	var countsats = $('#satlist').val().length;
	if (countsats > 0) {
		$('#addsked').prop('disabled', false);
	}
});

function searchpasses() {
	localStorage.setItem(`user_${user_id}_selectedsatellites`, $('#satlist').val());
	if ($("#satlist").val().length > 0) {;
		$(".ld-ext-right-plot").addClass('running');
		$(".ld-ext-right-plot").prop('disabled', true);
		$('#searchpass').prop("disabled", true);
		if ($('#addskedpartner').is(':hidden')) {
			loadPasses();
		} else {
			let skedgrid = $("#skedgrid").val();
			if (skedgrid == '') {
				$(".ld-ext-right-plot").removeClass('running');
				$(".ld-ext-right-plot").prop('disabled', false);
				$('#searchpass').prop("disabled", false);
				return;
			}
			loadSkedPasses();
		}
	}
	return;

}

function loadPasses() {
	$.ajax({
		url: base_url + 'index.php/satellite/searchPasses',
		type: 'post',
		data: {'sat': $("#satlist").val(),
			'yourgrid': $("#yourgrid").val(),
			'minelevation': $("#minelevation").val(),
			'minazimuth': $("#minazimuth").val(),
			'maxazimuth': $("#maxazimuth").val(),
			'date': $("#date").val(),
			'mintime': $("#mintime").val(),
			'maxtime': $("#maxtime").val(),
		},
		success: function (html) {
			$("#resultpasses").html(html);
			$(".ld-ext-right-plot").removeClass('running');
			$(".ld-ext-right-plot").prop('disabled', false);
			$('#searchpass').prop("disabled", false);
			$('.satelliteinfo').click(function (event) {
				getSatelliteInfo(this);
			});
		},
		error: function(e) {
			modalloading=false;
		}
	});
}

function getSatelliteInfo(element) {
	var satname = $(element).closest('td').contents().first().text().trim();
	$.ajax({
        url: base_url + 'index.php/satellite/getSatelliteInfo',
        type: 'post',
        data: {'sat': satname,
        },
        success: function (html) {
			BootstrapDialog.show({
				title: lang_gen_hamradio_sat_info,
				size: BootstrapDialog.SIZE_WIDE,
				cssClass: 'information-dialog',
				nl2br: false,
				message: html,
				buttons: [{
					label: lang_admin_close,
					action: function (dialogItself) {
						dialogItself.close();
					}
				}]
			});
        },
        error: function(e) {

        }
    });
}

function loadSkedPasses() {
	$.ajax({
        url: base_url + 'index.php/satellite/searchSkedPasses',
        type: 'post',
        data: {'sat': $("#satlist").val(),
            'yourgrid': $("#yourgrid").val(),
            'minelevation': $("#minelevation").val(),
            'minazimuth': $("#minazimuth").val(),
            'maxazimuth': $("#maxazimuth").val(),
            'date': $("#date").val(),
            'mintime': $("#mintime").val(),
            'maxtime': $("#maxtime").val(),
			'skedgrid': $("#skedgrid").val(),
			'minskedelevation': $("#minskedelevation").val(),
        },
        success: function (html) {
            $("#resultpasses").html(html);
			$(".ld-ext-right-plot").removeClass('running');
            $(".ld-ext-right-plot").prop('disabled', false);
            $('#searchpass').prop("disabled", false);
        },
        error: function(e) {
            modalloading=false;
        }
    });
}

function addskedpartner() {
	if ($('#addskedpartner').is(':hidden')) {
		$('#addskedpartner').show();
		$('#satlist option[value=""]').remove();
	} else {
		$('#addskedpartner').hide();
		$('#satlist').prepend('<option value="">' + lang_general_word_all + '</option>');
	}
}

$('#satlist').change(function () {
    if ($('#satlist').val() === "") {
		$('#addsked').prop('disabled', true);
    } else {
		$('#addsked').prop('disabled', false);
    }
});

function savePassSettings() {
    $.ajax({
        url: base_url + 'index.php/satellite/savePassSettings',
        type: 'post',
        data: {
            'setting_name': $("#settingsName").val(),
            'minelevation': $("#minelevation").val(),
            'minazimuth': $("#minazimuth").val(),
            'maxazimuth': $("#maxazimuth").val(),
            'grid': $("#yourgrid").val(),
            'sat': $("#satlist").val(),
            'sked_minelevation': $("#minskedelevation").val(),
            'sked_minazimuth': $("#minskedazimuth").val(),
            'sked_maxazimuth': $("#maxskedazimuth").val(),
            'sked_grid': $("#skedgrid").val(),
        },
        success: function (result) {
            loadPassSettingsList();
            $('#saveSettingsModal').modal('hide');
            $("#settingsName").val('');
        },
        error: function(e) {
            alert('Error saving settings');
            console.log(e);
        }
    });
}

function loadPassSettings(settings_id) {
    $.ajax({
        url: base_url + 'index.php/satellite/loadPassSettings',
        type: 'post',
        data: {
            'settings_id': settings_id,
        },
        success: function (result) {
            let settings = JSON.parse(result);
            $("#minelevation").val(settings.minelevation);
            $("#minazimuth").val(settings.minazimuth);
            $("#maxazimuth").val(settings.maxazimuth);
            $("#yourgrid").val(settings.grid);
            $("#satlist").val(settings.sat);
            if (settings.sat != '' && settings.sked_grid != '') {
                $('#addskedpartner').show();
                $("#minskedelevation").val(settings.sked_minelevation);
                $("#minskedazimuth").val(settings.sked_minazimuth);
                $("#maxskedazimuth").val(settings.sked_maxazimuth);
                $("#skedgrid").val(settings.sked_grid);
            } else {
                $('#addskedpartner').hide();
            }
            searchpasses();
        },
        error: function(e) {
            alert('Error loading settings');
            console.log(e);
        }
    });
}

function delPassSettings(settings_id) {
    if (!confirm('Are you sure you want to delete this settings?')) {
        return;
    }
    $.ajax({
        url: base_url + 'index.php/satellite/delPassSettings',
        type: 'post',
        data: {
            'settings_id': settings_id,
        },
        success: function (result) {
            loadPassSettingsList();
        },
        error: function(e) {
            alert('Error deleting settings');
            console.log(e);
        }
    });
}

function loadPassSettingsList() {
    $("#passSettingsList").html('');
    $.ajax({
        url: base_url + 'index.php/satellite/getPassSettingsList',
        success: function (result) {
            $("#passSettingsList").html(result);
        },
        error: function(e) {
            alert('Error loading settings list');
            console.log(e);
        }
    });
}
