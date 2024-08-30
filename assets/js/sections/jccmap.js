var osmUrl = tileUrl;

function load_jcc_map() {
    $('.nav-tabs a[href="#jccmaptab"]').tab('show');
    $.ajax({
        url: base_url + 'index.php/awards/jcc_map',
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
            clublog: +$('#clublog').prop('checked'),
            eqsl: +$('#eqsl').prop('checked'),
        },
        success: function(data) {
            load_jcc_map2(data, worked, confirmed, notworked);
        },
        error: function() {

        },
    });
}

function load_jcc_map2(data, worked, confirmed, notworked) {

    // If map is already initialized
    var container = L.DomUtil.get('jccmap');

    if(container != null){
        container._leaflet_id = null;
        container.remove();
        $("#jccmaptab").append('<div id="jccmap" class="map-leaflet" ></div>');
    }

    var map = new L.Map('jccmap', {
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

    var jccstuff = {};
    $.ajax({
       dataType: "json",
       url: base_url + 'index.php/awards/jcc_cities',
       async: false,
       success: function(result) {
          for (var item in result) {
             var name = item.toString();
             jccstuff[name] = [result[item]['name'], result[item]['lat'], result[item]['lon']];
          }
       }
    });
    for (const [key, value] of Object.entries(jccstuff)) {
       var D = [];
       if (key in data) {
          if (confirmed.checked == true) {
             if (data[key][1] == 1) {
                mapColor = 'green';
                D['prefix'] = key;
                D['name'] = value[0];
                D['lat'] = value[1];
                D['long'] = value[2];
                addMarker(L, D, mapColor, map);
                confirmedcount++;
                continue;
             }
          }
          if (worked.checked == true) {
             mapColor = 'orange';
             D['prefix'] = key;
             D['name'] = value[0];
             D['lat'] = value[1];
             D['long'] = value[2];
             addMarker(L, D, mapColor, map);
             workednotconfirmedcount++;
          }
       } else {
          if (notworked.checked == true) {
             mapColor = 'red';
             D['prefix'] = key;
             D['name'] = value[0];
             D['lat'] = value[1];
             D['long'] = value[2];
             addMarker(L, D, mapColor, map);
             notworkedcount++;
          }
       }
    };

    /*Legend specific*/
    var legend = L.control({ position: "topright" });

    legend.onAdd = function(map) {
        var div = L.DomUtil.create("div", "legend");
        div.innerHTML += "<h4>" + lang_general_word_colors + "</h4>";
        div.innerHTML += "<i style='background: green'></i><span>" + lang_general_word_confirmed + " (" + confirmedcount + ")</span><br>";
        div.innerHTML += "<i style='background: orange'></i><span>" + lang_general_word_worked_not_confirmed + " (" + workednotconfirmedcount + ")</span><br>";
        div.innerHTML += "<i style='background: red'></i><span>" + lang_general_word_not_worked + " (" + notworkedcount + ")</span><br>";
        return div;
    };

    legend.addTo(map);

    map.setView([37.460, 139.452], 5);
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
        prefix: D['prefix'],
        title: D['prefix'] + ' - ' + D['name'],
    }
    ).addTo(map).on('click', onClick);

    L.marker(
        [D['lat'], D['long']], {
            icon: icon,
            prefix: D['prefix'],
            title: D['prefix'] + ' - ' + D['name'],
        }
        ).addTo(map).on('click', onClick);
}

function onClick(e) {
    var marker = e.target;
    displayContactsOnMap($("#jccmap"),marker.options.prefix, $('#band2').val(), 'All', 'All', $('#mode').val(), 'JCC');
}
