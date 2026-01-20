let confirmedColor = 'rgba(144,238,144)';
if (typeof(user_map_custom.qsoconfirm) !== 'undefined') {
      confirmedColor = user_map_custom.qsoconfirm.color;
}
let workedColor = 'rgba(229, 165, 10)';
if (typeof(user_map_custom.qso) !== 'undefined') {
      workedColor = user_map_custom.qso.color;
}
let unworkedColor = 'rgba(204, 55, 45)';
if (typeof(user_map_custom.unworked) !== 'undefined') {
	unworkedColor = user_map_custom.unworked.color;
}

document.addEventListener("DOMContentLoaded", function() {
  document.querySelectorAll('.dropdown').forEach(dd => {
		dd.addEventListener('hide.bs.dropdown', function (e) {
			if (e.clickEvent && e.clickEvent.target.closest('.dropdown-menu')) {
				e.preventDefault(); // stop Bootstrap from closing
			}
		});
	});
});

$(document).on('submit', 'form', function(e) {
    if ($(e.target).find('.bootstrap-dialog').length) {
        e.preventDefault();
    }
});

var osmUrl = $('#dxccmapjs').attr("tileUrl");


$('#band2').change(function(){
   var band = $("#band2 option:selected").text();
   if (band != "SAT") {
      $("#sats").val('All');
      $("#orbits").val('All');
      $("#satrow").hide();
      $("#orbitrow").hide();
   } else {
      $("#satrow").show();
      $("#orbitrow").show();
   }
});

$('#band2').change();	// trigger the change on fresh-load to hide/show SAT-Params

$('#sats').change(function(){
   var sat = $("#sats option:selected").text();
      $("#band2").val('SAT');
   if (sat != "All") {
   }
});

function load_dxcc_map() {
    $('.nav-tabs a[href="#dxccmaptab"]').tab('show');
    $.ajax({
        url: base_url + 'index.php/awards/dxcc_map',
        type: 'post',
        data: {
            band: $('#band2').val(),
            mode: $('#mode').val(),
            worked: +$('#worked').prop('checked'),
            confirmed: +$('#confirmed').prop('checked'),
            notworked: +$('#notworked').prop('checked'),
            qsl: +$('#qsl').prop('checked'),
            lotw: +$('#lotw').prop('checked'),
            qrz: +$('#qrz').prop('checked'),
            eqsl: +$('#eqsl').prop('checked'),
            includedeleted: +$('#includedeleted').prop('checked'),
            Africa: +$('#Africa').prop('checked'),
            Asia: +$('#Asia').prop('checked'),
            Europe: +$('#Europe').prop('checked'),
            NorthAmerica: +$('#NorthAmerica').prop('checked'),
            SouthAmerica: +$('#SouthAmerica').prop('checked'),
            Oceania: +$('#Oceania').prop('checked'),
            Antarctica: +$('#Antarctica').prop('checked'),
            sat: $("#sats").val(),
            orbit: $("#orbits").val(),
			dateFrom: $('#dateFrom').val(),
			dateTo: $('#dateTo').val(),
        },
        success: function(data) {
            load_dxcc_map2(data, worked, confirmed, notworked);
        },
        error: function() {

        },
    });
}

function load_dxcc_map2(data, worked, confirmed, notworked) {

    // If map is already initialized
    var container = L.DomUtil.get('dxccmap');

    if(container != null){
        container._leaflet_id = null;
        container.remove();
        $("#dxccmaptab").append('<div id="dxccmap" class="map-leaflet" ></div>');
    }

    var map = new L.Map('dxccmap', {
        fullscreenControl: true,
        fullscreenControlOptions: {
          position: 'topleft'
        },
      });

    L.tileLayer(
        osmUrl,
        {
            attribution: option_map_tile_server_copyright,
            maxZoom: 18
        }
    ).addTo(map);

    var notworkedcount = 0;
    var confirmedcount = 0;
    var workednotconfirmedcount = 0;

    for (var i = 0; i < data.length; i++) {
        var D = data[i];
        if (D['status'] != 'x') {
            var mapColor = 'red';

            if (D['status'] == 'C') {
                mapColor = confirmedColor;
                if (confirmed != '0') {
                    addMarker(L, D, mapColor, map);
                    confirmedcount++;
                }
            }
            if (D['status'] == 'W') {
                mapColor = workedColor;
                if (worked != '0') {
                    addMarker(L, D, mapColor, map);
                    workednotconfirmedcount++;
                }
            }
            if (D['status'] == '-') {
                mapColor = unworkedColor;
                if (notworked != '0') {
                    addMarker(L, D, mapColor, map);
                    notworkedcount++;
                }
            }
        }
    }

    /*Legend specific*/
    var legend = L.control({ position: "topright" });

    if (notworked.checked == false) {
        notworkedcount = 0;
    }

    legend.onAdd = function(map) {
        var div = L.DomUtil.create("div", "legend");
        div.innerHTML += "<h4>Colors</h4>";
        div.innerHTML += '<i style="background: ' + confirmedColor + '"></i><span>' + lang_general_word_confirmed + ' ('+confirmedcount+')</span><br>';
        div.innerHTML += '<i style="background: ' + workedColor + '"></i><span>' + lang_general_word_worked_not_confirmed + ' ('+workednotconfirmedcount+')</span><br>';
        div.innerHTML += '<i style="background: ' + unworkedColor + '"></i><span>' + lang_general_word_not_worked + ' ('+notworkedcount+')</span><br>';
        return div;
    };

    legend.addTo(map);

    map.setView([20, 0], 2);
}

function addMarker(L, D, mapColor, map) {
    var title = '<span><font style="color: ' +mapColor+ '; text-shadow: 1px 0 #fff, -1px 0 #fff, 0 1px #fff, 0 -1px #fff, 1px 1px #fff, -1px -1px #fff, 1px -1px #fff, -1px 1px #fff;font-size: 14px; font-weight: 900;">' + D['prefix'] + '</font></span>';
    var myIcon = L.divIcon({className: 'my-div-icon', html: title});

    const markerHtmlStyles = `
    background-color: ${mapColor};
    width: 1rem;
    height: 1rem;
    display: block;
    position: relative;
    border-radius: 3rem 3rem 0;
    transform: rotate(45deg);
    border: 1px solid #FFFFFF`

    const icon = L.divIcon({
        className: "my-custom-pin",
        iconAnchor: [0, 24],
        labelAnchor: [-6, 0],
        popupAnchor: [0, -36],
        html: `<span style="${markerHtmlStyles}" />`
    })

    L.marker(
    [D['lat'], D['long']], {
        icon: myIcon,
        adif: D['adif'],
        title: D['prefix'] + ' - ' + D['name'],
    }
    ).addTo(map).on('click', onClick);

    L.marker(
        [D['lat'], D['long']], {
            icon: icon,
            adif: D['adif'],
            title: D['prefix'] + ' - ' + D['name'],
        }
        ).addTo(map).on('click', onClick);
}

function onClick(e) {
    var marker = e.target;
    displayContactsOnMap($("#dxccmap"),marker.options.adif, $('#band2').val(), $('#sats').val(), $('#orbits').val(), $('#mode').val(), 'DXCC2', '', $('#dateFrom').val(), $('#dateTo').val());
}

// Preset functionality
    function applyPreset(preset) {
        const dateFrom = document.getElementById('dateFrom');
        const dateTo = document.getElementById('dateTo');
        const today = new Date();

        // Format date as YYYY-MM-DD
        function formatDate(date) {
            const year = date.getUTCFullYear();
            const month = String(date.getUTCMonth() + 1).padStart(2, '0');
            const day = String(date.getUTCDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        switch(preset) {
            case 'today':
                dateFrom.value = formatDate(today);
                dateTo.value = formatDate(today);
                break;

            case 'yesterday':
                const yesterday = new Date(today);
                yesterday.setDate(yesterday.getUTCDate() - 1);
                dateFrom.value = formatDate(yesterday);
                dateTo.value = formatDate(yesterday);
                break;

            case 'last7days':
                const sevenDaysAgo = new Date(today);
                sevenDaysAgo.setDate(sevenDaysAgo.getUTCDate() - 7);
                dateFrom.value = formatDate(sevenDaysAgo);
                dateTo.value = formatDate(today);
                break;

            case 'last30days':
                const thirtyDaysAgo = new Date(today);
                thirtyDaysAgo.setDate(thirtyDaysAgo.getUTCDate() - 30);
                dateFrom.value = formatDate(thirtyDaysAgo);
                dateTo.value = formatDate(today);
                break;

            case 'thismonth':
                const firstDayOfMonth = new Date(today.getUTCFullYear(), today.getUTCMonth(), 1);
                dateFrom.value = formatDate(firstDayOfMonth);
                dateTo.value = formatDate(today);
                break;

            case 'lastmonth':
                const firstDayOfLastMonth = new Date(today.getUTCFullYear(), today.getUTCMonth() - 1, 1);
                const lastDayOfLastMonth = new Date(today.getUTCFullYear(), today.getUTCMonth(), 0);
                dateFrom.value = formatDate(firstDayOfLastMonth);
                dateTo.value = formatDate(lastDayOfLastMonth);
                break;

            case 'thisyear':
                const firstDayOfYear = new Date(today.getUTCFullYear(), 0, 1);
                dateFrom.value = formatDate(firstDayOfYear);
                dateTo.value = formatDate(today);
                break;

            case 'lastyear':
                const lastYear = today.getUTCFullYear() - 1;
                const firstDayOfLastYear = new Date(lastYear, 0, 1);
                const lastDayOfLastYear = new Date(lastYear, 11, 31);
                dateFrom.value = formatDate(firstDayOfLastYear);
                dateTo.value = formatDate(lastDayOfLastYear);
                break;

            case 'alltime':
                dateFrom.value = '';
                dateTo.value = '';
                break;
        }
    }

    // Reset dates function
    function resetDates() {
        const dateFrom = document.getElementById('dateFrom');
        const dateTo = document.getElementById('dateTo');
        dateFrom.value = '';
        dateTo.value = '';
    }
