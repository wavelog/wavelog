$(document).ready(function() {
	loadPassSettingsList();

	$('#satlist').multiselect({
		// template is needed for bs5 support
		enableFiltering: true,
		enableCaseInsensitiveFiltering: true,
		filterPlaceholder: lang_general_word_search,
		templates: {
		  button: '<button type="button" class="multiselect dropdown-toggle btn btn-sm btn-secondary" data-bs-toggle="dropdown" aria-expanded="false"><span class="multiselect-selected-text"></span></button>',
		},
		numberDisplayed: 1,
		inheritClass: true,
		includeSelectAllOption: true,
		buttonTextAlignment: 'left'
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
	if ($("#yourgrid").val() != '') {
		$('#nogridhint').attr('style', 'display: none;');
	}
	localStorage.setItem(`user_${user_id}_selectedsatellites`, $('#satlist').val());
	if ($("#satlist").val().length > 0) {;
		$(".ld-ext-right-plot").addClass('running');
		$(".ld-ext-right-plot").prop('disabled', true);
		$('#searchpass').prop("disabled", true);
		let skedgrid = $("#skedgrid").val();
		if (skedgrid != '') {
			loadSkedPasses();
		} else {
			loadPasses();
		}
		return;
	}

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
			$("#resultsCard").show();
			$("#resultpasses").html(html);
			$(".ld-ext-right-plot").removeClass('running');
			$(".ld-ext-right-plot").prop('disabled', false);
			$('#searchpass').prop("disabled", false);
			$('.satelliteinfo').click(function (event) {
				getSatelliteInfo(this);
			});
			$('.hamsatposting').click(function (event) {
				prepHamsAtPosting(this);
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

function prepHamsAtPosting(element) {
	var satname = $(element).closest('td').contents().first().text().trim();
	var row = $(element).closest('tr');
	var aos = row.find('.aos').data('aos');
	var tca = row.find('.tca').data('tca');
	var los = row.find('.los').data('los');
	var duration = row.find('.duration').contents().text().trim();
	$.ajax({
		url: base_url + 'index.php/satellite/prepHamsAtPosting',
		type: 'post',
		data: {
			'sat': satname,
			'aos': aos,
			'tca': tca,
			'los': los,
			'duration': duration,
		},
		success: function (html) {
			BootstrapDialog.show({
				title: lang_gen_hamradio_sat_hamsat_post,
				size: BootstrapDialog.SIZE_WIDE,
				cssClass: 'preparehamsat-dialog bg-opacity-50',
				nl2br: false,
				message: html,
				onshown: function(){
					toggleTpx();
				},
				buttons: [{
					icon: 'fas fa-arrow-up-right-from-square',
					label: lang_admin_post,
					autospin: true,
					cssClass: 'btn-primary',
					action: function () {
						post_hamsat();
					},
				},
				{
					label: lang_admin_close,
					cssClass: 'btn-secondary',
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
			$("#resultsCard").show();
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

function toggleTpx() {
	const mode = $("#mode option:selected").text();
	const dir = $("input[name='mhz_direction']:checked").val();
	const ssb = ['SSB', 'USB', 'LSB'];
	const matchers = {
		Data: {
			up: t => t.uplink_mode == 'PKT' || ssb.includes(t.uplink_mode),
			down: t => t.downlink_mode == 'PKT' || ssb.includes(t.uplink_mode),
		},
		FM: {
			up: t => t.uplink_mode == 'FM',
			down: t => t.downlink_mode == 'FM',
		},
	};
	matchers.SSB = matchers.CW = { up: t => ssb.includes(t.uplink_mode), down: t => ssb.includes(t.uplink_mode) };
	for (const tpx of JSON.parse($("#tpxdata").val())) {
		if (!matchers[mode] || !matchers[mode][dir] || !matchers[mode][dir](tpx)) {
			continue;
		}
		$("#tpx_center_freq").html(dir == 'up' ? tpx.uplink_freq : tpx.downlink_freq);
		const fixed = mode == 'Data' && tpx.uplink_freq == tpx.downlink_freq;
		$("#mhz").prop('disabled', fixed);
		$("#mhz_direction_up").prop('disabled', fixed);
		$("#mhz_direction_down").prop('disabled', fixed);
		if (fixed) {
			$("#mhz_direction_down").prop('checked', true);
		}
	}
}
