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
                mapColor = 'green';
                if (confirmed != '0') {
                    addMarker(L, D, mapColor, map);
                    confirmedcount++;
                }
            }
            if (D['status'] == 'W') {
                mapColor = 'orange';
                if (worked != '0') {
                    addMarker(L, D, mapColor, map);
                    workednotconfirmedcount++;
                }
            }
            if (D['status'] == '-') {
                mapColor = 'red';
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
        div.innerHTML += '<i style="background: green"></i><span>Confirmed ('+confirmedcount+')</span><br>';
        div.innerHTML += '<i style="background: orange"></i><span>Worked not confirmed ('+workednotconfirmedcount+')</span><br>';
        div.innerHTML += '<i style="background: red"></i><span>Not worked ('+notworkedcount+')</span><br>';
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
    displayContactsOnMap($("#dxccmap"),marker.options.adif, $('#band2').val(), $('#sats').val(), $('#orbits').val(), $('#mode').val(), 'DXCC2');
}
