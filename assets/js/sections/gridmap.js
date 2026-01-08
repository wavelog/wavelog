var modalloading=false;

let confirmedColor = 'rgba(144,238,144)';
if (typeof(user_map_custom.qsoconfirm) !== 'undefined') {
      confirmedColor = user_map_custom.qsoconfirm.color;
}
let workedColor = 'rgba(229, 165, 10)';
if (typeof(user_map_custom.qso) !== 'undefined') {
      workedColor = user_map_custom.qso.color;
}

document.addEventListener("DOMContentLoaded", function() {
  document.querySelectorAll('.dropdown').forEach(dd => {
		dd.addEventListener('hide.bs.dropdown', function (e) {
			if (e.clickEvent && e.clickEvent.target.closest('.dropdown-menu')) {
				e.preventDefault(); // stop Bootstrap from closing
			}
		});
	});

	if(typeof visitor === 'undefined' || visitor != true) {
		$('#dxcc').multiselect({
			// template is needed for bs5 support
			templates: {
				button: '<button type="button" class="multiselect dropdown-toggle btn btn-sm btn-secondary form-select form-select-sm" data-bs-toggle="dropdown" aria-expanded="false"><span class="multiselect-selected-text"></span></button>',
				option: '<button type="button" class="multiselect-option dropdown-item-sm dropdown-item"></button>',
				popupContainer: '<div class="multiselect-container dropdown-menu dropdown-menu-sm"></div>',
			},
			enableFiltering: true,
			enableFullValueFiltering: false,
			enableCaseInsensitiveFiltering: true,
			filterPlaceholder: lang_general_word_search,
			numberDisplayed: 1,
			inheritClass: true,
			buttonWidth: '100%',
			maxHeight: 600,
			buttonContainer: '<div class="btn-group-sm" />',
		});

			$('.multiselect-container .multiselect-filter', $('#dxcc').parent()).css({
			'position': 'sticky', 'top': '0px', 'z-index': 1, 'background-color':'inherit', 'height':'37px'
		})
	}

});

$('#band').change(function(){
	var band = $("#band option:selected").text();
	if (band != "SAT") {
		$("#sat").val('All');
		$("#orbits").val('All');
		$("#sats_div").hide();
        $("#sats").hide(); // used in activated_gridmap
		$("#orbits_div").hide();
        $("#orbits").hide(); // used in activated_gridmap
		$("#satslabel").hide();
		$("#orbitslabel").hide();
        $('#propagation').val('').prop('disabled', false);
	} else {
		$("#sats_div").show();
        $("#sats").show(); // used in activated_gridmap
		$("#orbits_div").show();
        $("#orbits").show(); // used in activated_gridmap
		$("#orbitslabel").show();
		$("#satslabel").show();
        $('#propagation').val('SAT').prop('disabled', true);
	}
});

var map;
if (typeof(visitor) !== 'undefined' && visitor != true) {
   var grid_two = '';
   var grid_four = '';
   var grid_six = '';
   var grid_two_confirmed = '';
   var grid_four_confirmed = '';
   var grid_six_confirmed = '';
}

function gridPlot(form, visitor=true) {
    $(".ld-ext-right-plot").addClass('running');
    $(".ld-ext-right-plot").prop('disabled', true);
    $('#plot').prop("disabled", true);
    // If map is already initialized
    var container = L.DomUtil.get('gridsquare_map');

    if(container != null){
        container._leaflet_id = null;
        container.remove();
        $("#gridmapcontainer").append('<div id="gridsquare_map" class="map-leaflet" style="width: 100%;"></div>');
        set_map_height(50);
    }

    if (typeof type == 'undefined') { type=''; }
    if (type == "activated") {
        ajax_url = site_url + '/activated_gridmap/getGridsjs';
    } else if (type == "worked") {
        ajax_url = site_url + '/gridmap/getGridsjs';
    } else {
        ajax_url = site_url + '/gridmap/getGridsjs';
    }

    if (visitor != true) {
		$.ajax({
			url: ajax_url,
			type: 'post',
			data: {
				band: $("#band").val(),
				mode: $("#mode").val(),
				qsl:  $("#qsl").is(":checked"),
				lotw: $("#lotw").is(":checked"),
				eqsl: $("#eqsl").is(":checked"),
				qrz: $("#qrz").is(":checked"),
				sat: $("#sat").val(),
				orbit: $("#orbits").val(),
				propagation: $('#propagation').val(),
				dxcc: $('#dxcc').val(),
				datefrom: $('#dateFrom').val(),
				dateto: $('#dateTo').val(),
			},
			success: function (data) {
				$('.cohidden').show();
				set_map_height(25);
				$(".ld-ext-right-plot").removeClass('running');
				$(".ld-ext-right-plot").prop('disabled', false);
				$('#plot').prop("disabled", false);
				grid_two = data.grid_2char;
				grid_four = data.grid_4char;
				grid_six = data.grid_6char;
				grid_two_confirmed = data.grid_2char_confirmed;
				grid_four_confirmed = data.grid_4char_confirmed;
				grid_six_confirmed = data.grid_6char_confirmed;
				grids = data.grids;
				grid_max = data.grid_count;
				plot(visitor, grid_two, grid_four, grid_six, grid_two_confirmed, grid_four_confirmed, grid_six_confirmed, grids, grid_max, data.country_coords);

			},
			error: function (data) {
			},
		});
   } else {
      plot(visitor, grid_two, grid_four, grid_six, grid_two_confirmed, grid_four_confirmed, grid_six_confirmed, '', 0);
   };
}

function plot(visitor, grid_two, grid_four, grid_six, grid_two_confirmed, grid_four_confirmed, grid_six_confirmed, grids, grid_max, country_coords) {
            let layer = L.tileLayer(jslayer, {
                maxZoom: 12,
                attribution: jsattribution,
                id: 'mapbox.streets'
            });

            // Set map center and zoom based on country selection
            let mapCenter = [19, 0];
            let mapZoom = 3;

            if (country_coords && country_coords.lat && country_coords.long) {
                mapCenter = [country_coords.lat, country_coords.long];
                mapZoom = 5; // Zoom in closer for country view
            }

            map = L.map('gridsquare_map', {
            layers: [layer],
            center: mapCenter,
            zoom: mapZoom,
            minZoom: 2,
            fullscreenControl: true,
                fullscreenControlOptions: {
                    position: 'topleft'
                },
            });

            if (visitor != true) {
               let printer = L.easyPrint({
                   tileLayer: layer,
                   sizeModes: ['Current'],
                   filename: 'myMap',
                   exportOnly: true,
                   hideControlContainer: true
               }).addTo(map);
            }

            /*Legend specific*/
            let legend = L.control({ position: "topright" });


			if (grids != '') {
				legend.onAdd = function(map) {
					let div = L.DomUtil.create("div", "legend");
					div.setAttribute('id', 'gridmapLegend');
					html = '<div align="right" class="legendClose"><small><a href="javascript: hideLegend();">X</a></small></div>';
					 // Add country name if selected
					const countryName = getSelectedCountryName();
					if (countryName) {
						html += '<h4>DXCC: ' + countryName + '</h4>';
					}

					html += "<table border=\"0\">";
					html += '<i style="background: green"></i><span>' + gridsquares_gridsquares_confirmed + ' ('+grid_four_confirmed.length+')</span><br>';
					html += '<i style="background: red"></i><span>' + gridsquares_gridsquares_not_confirmed + ' ('+(grid_four.length - grid_four_confirmed.length)+')</span><br>';
					html += '<tr><td><i style="background: #ffd757"></i><span>' + gridsquares_gridsquares_total_worked + ' ('+(Math.round((grid_four.length / grid_max) * 10000) / 100)+'%):</span></td><td style=\"padding-left: 1em; text-align: right;\"><span>'+(grid_four.length)+' / '+grid_max+'</span></td></tr>';
					html += "</table>";
					div.innerHTML = html;
					return div;
				};
			} else {
				legend.onAdd = function(map) {
					let div = L.DomUtil.create("div", "legend");
					div.setAttribute('id', 'gridmapLegend');
					div.innerHTML += '<div align="right" class="legendClose"><small><a href="javascript: hideLegend();">X</a></small></div>';
					div.innerHTML += "<h4>" + gridsquares_gridsquares + "</h4>";
					div.innerHTML += '<i class="grid-confirmed" style="background: ' + confirmedColor + '"></i><span>' + gridsquares_gridsquares_confirmed + ' ('+grid_four_confirmed.length+')</span><br>';
					div.innerHTML += '<i class="grid-worked" style="background: ' + workedColor + '"></i><span>' + gridsquares_gridsquares_not_confirmed + ' ('+(grid_four.length - grid_four_confirmed.length)+')</span><br>';
					div.innerHTML += '<i></i><span>' + gridsquares_gridsquares_total_worked + ' ('+grid_four.length+')</span><br>';
					div.innerHTML += "<h4>Fields</h4>";
					div.innerHTML += '<i class="grid-confirmed" style="background: ' + confirmedColor + '"></i><span>Fields confirmed ('+grid_two_confirmed.length+')</span><br>';
					div.innerHTML += '<i class="grid-worked" style="background: ' + workedColor + '"></i><span>Fields not confirmed ('+(grid_two.length - grid_two_confirmed.length)+')</span><br>';
					div.innerHTML += '<i></i><span>Total fields worked ('+grid_two.length+')</span><br>';
					return div;
				};
			}

            legend.addTo(map);

            var maidenhead = L.maidenhead().addTo(map);
            if (visitor != true) {
               map.on('mousemove', onMapMove);
               map.on('click', onMapClick);
            }
}

// Get selected country name from multiselect
function getSelectedCountryName() {
    const dxccSelect = $('#dxcc');
    const selectedValues = dxccSelect.val();

    if (!selectedValues || selectedValues[0] === 'All') {
        return null;
    }

    // Get the text of selected options
    const selectedText = dxccSelect.find('option:selected').map(function() {
        return $(this).text();
    }).get();

    return selectedText.join(', ');
}

function spawnGridsquareModal(loc_4char) {
	if (!(modalloading)) {
		var ajax_data = ({
			'Searchphrase': loc_4char,
			'Band': $("#band").val(),
			'Mode': $("#mode").val(),
			'Sat': $("#sat").val(),
			'Orbit': $("#orbits").val(),
            'Propagation': $('#propagation').val(),
			'Type': 'VUCC',
			'dateFrom': $('#dateFrom').val(),
			'dateTo': $('#dateTo').val()
		})
		if (type == 'activated') {
			ajax_data.searchmode = 'activated';
		}
		modalloading=true;
		$.ajax({
			url: base_url + 'index.php/awards/qso_details_ajax',
			type: 'post',
			data: ajax_data,
			success: function (html) {
		    		var dialog = new BootstrapDialog({
					title: lang_general_word_qso_data,
					cssClass: 'qso-dialog',
					size: BootstrapDialog.SIZE_WIDE,
					nl2br: false,
					message: html,
					onshown: function(dialog) {
						modalloading=false;
						$('[data-bs-toggle="tooltip"]').tooltip();
						$('.displaycontactstable').DataTable({
							"pageLength": 25,
							responsive: false,
							ordering: false,
							"scrollY":        "550px",
							"scrollCollapse": true,
							"paging":         false,
							"scrollX": true,
                            "language": {
                                url: getDataTablesLanguageUrl(),
                            },
							dom: 'Bfrtip',
							buttons: [
								'csv'
							]
						});
						// change color of csv-button if dark mode is chosen
						if (isDarkModeTheme()) {
							$(".buttons-csv").css("color", "white");
						}
                        $('.table-responsive .dropdown-toggle').off('mouseenter').on('mouseenter', function () {
                            showQsoActionsMenu($(this).closest('.dropdown'));
                        });
					},
                    onhide: function(dialog) {
                        enableMap();
                    },
					buttons: [{
						label: lang_admin_close,
						action: function(dialogItself) {
							dialogItself.close();
						}
					}]
				});
			    dialog.realize();
                    $('#gridsquare_map').append(dialog.getModal());
                    disableMap();
		    		dialog.open();
                },
			error: function(e) {
				modalloading=false;
			}
		});
	}
}

function clearMarkers() {
	$(".ld-ext-right-clear").addClass('running');
	$(".ld-ext-right-clear").prop('disabled', true);
	clicklines.forEach(function (item) {
		map.removeLayer(item)
	});
	clickmarkers.forEach(function (item) {
		map.removeLayer(item)
	});
	$(".ld-ext-right-clear").removeClass('running');
	$(".ld-ext-right-clear").prop('disabled', false);
}

function hideLegend() {
	// Not defined in visitors view
	if (typeof clicklines !== 'undefined') {
		clearMarkers();
	}
	$("#gridmapLegend").hide();
}

function hexToRgba(hex, alpha = 1) {
	if (!hex) return null;
	// Remove the leading "#"
	hex = hex.replace(/^#/, '');

	// Expand short form (#f0a → #ff00aa)
	if (hex.length === 3) {
		hex = hex.split('').map(c => c + c).join('');
	}

	const num = parseInt(hex, 16);
	const r = (num >> 16) & 255;
	const g = (num >> 8) & 255;
	const b = num & 255;

	return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

$(document).ready(function(){
	gridPlot(this.form, visitor);
	$(window).resize(function () {
		set_map_height();
	});

	var target = document.body;
	var observer = new MutationObserver(function() {
		$('#dt-search-0').on('keyup', function (e) {
			tocrappyzero=$(this).val().toUpperCase().replaceAll(/0/g, 'Ø');
			$(this).val(tocrappyzero);
			$(this).trigger("input");
		});
	});
	var config = { childList: true, subtree: true};
	// pass in the target node, as well as the observer options
	observer.observe(target, config);
});


// Preset functionality
    function applyPreset(preset) {
        const dateFrom = document.getElementById('dateFrom');
        const dateTo = document.getElementById('dateTo');
        const today = new Date();

        // Format date as YYYY-MM-DD
        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        switch(preset) {
            case 'today':
                dateFrom.value = formatDate(today);
                dateTo.value = formatDate(today);
                break;

            case 'yesterday':
                const yesterday = new Date(today);
                yesterday.setDate(yesterday.getDate() - 1);
                dateFrom.value = formatDate(yesterday);
                dateTo.value = formatDate(yesterday);
                break;

            case 'last7days':
                const sevenDaysAgo = new Date(today);
                sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 7);
                dateFrom.value = formatDate(sevenDaysAgo);
                dateTo.value = formatDate(today);
                break;

            case 'last30days':
                const thirtyDaysAgo = new Date(today);
                thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
                dateFrom.value = formatDate(thirtyDaysAgo);
                dateTo.value = formatDate(today);
                break;

            case 'thismonth':
                const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                dateFrom.value = formatDate(firstDayOfMonth);
                dateTo.value = formatDate(today);
                break;

            case 'lastmonth':
                const firstDayOfLastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                const lastDayOfLastMonth = new Date(today.getFullYear(), today.getMonth(), 0);
                dateFrom.value = formatDate(firstDayOfLastMonth);
                dateTo.value = formatDate(lastDayOfLastMonth);
                break;

            case 'thisyear':
                const firstDayOfYear = new Date(today.getFullYear(), 0, 1);
                dateFrom.value = formatDate(firstDayOfYear);
                dateTo.value = formatDate(today);
                break;

            case 'alltime':
                dateFrom.value = '';
                dateTo.value = '';
                break;
        }

        // Trigger plot after applying preset
        setTimeout(() => {
            const plotButton = document.getElementById('plot');
            if (plotButton) {
                plotButton.click();
            }
        }, 100);
    }

    // Reset dates function
    function resetDates() {
        const dateFrom = document.getElementById('dateFrom');
        const dateTo = document.getElementById('dateTo');
        dateFrom.value = '';
        dateTo.value = '';

        // Trigger plot after resetting
        setTimeout(() => {
            const plotButton = document.getElementById('plot');
            if (plotButton) {
                plotButton.click();
            }
        }, 100);
    }
