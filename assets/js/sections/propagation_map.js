let propagationMap = null;
let propagationTerminatorLayer = null;
let propagationHomeMarker = null;
let heardMeLayer = null;
let heardMeRequest = null;
let mufLayer = null;
let mufRequest = null;

function maidenheadToLatLng(locator) {
    locator = locator.trim().toUpperCase();

    if (locator.length < 4) {
        return null;
    }

    const A = 'A'.charCodeAt(0);

    let lon = -180;
    let lat = -90;

    lon += (locator.charCodeAt(0) - A) * 20;
    lat += (locator.charCodeAt(1) - A) * 10;

    lon += parseInt(locator[2], 10) * 2;
    lat += parseInt(locator[3], 10);

    let lonSize = 2;
    let latSize = 1;

    if (locator.length >= 6) {
        lonSize = 2 / 24;
        latSize = 1 / 24;

        lon += (locator.charCodeAt(4) - A) * lonSize;
        lat += (locator.charCodeAt(5) - A) * latSize;
    }

    return L.latLng(
        lat + latSize / 2,
        lon + lonSize / 2
    );
}

function initPropagationMap() {

    propagationMap = L.map('propagationMap', {
        worldCopyJump: true
    }).setView([50, 10], 3);

    L.tileLayer(option_map_tile_server, {
        maxZoom: 18,
        attribution: option_map_tile_server_copyright
    }).addTo(propagationMap);

    // Day / Night terminator
    if (typeof L.terminator === 'function') {
        propagationTerminatorLayer = L.terminator();
        propagationTerminatorLayer.addTo(propagationMap);
    }

    // Own station
    addPropagationHomeMarker();

    // PSK Reporter "Heard Me"
    if ($('#propHeardMe').is(':checked')) {
        loadHeardMe();
    }

    // QLog-style MUF station values
    if ($('#propMuf').is(':checked')) {
        loadMuf();
    }

    // Day / Night checkbox
    $('#propTerminator').on('change', function () {

        if (!propagationTerminatorLayer) {
            return;
        }

        if ($(this).is(':checked')) {
            propagationTerminatorLayer.addTo(propagationMap);
        } else {
            propagationMap.removeLayer(propagationTerminatorLayer);
        }
    });

    // Heard Me layer
    $('#propHeardMe').on('change', function () {
        if ($(this).is(':checked')) {
            loadHeardMe();
        } else {
            if (heardMeRequest) {
                heardMeRequest.abort();
                heardMeRequest = null;
            }

            if (heardMeLayer && propagationMap.hasLayer(heardMeLayer)) {
                propagationMap.removeLayer(heardMeLayer);
            }
        }
    });

    // MUF layer
    $('#propMuf').on('change', function () {
        if ($(this).is(':checked')) {
            loadMuf();
        } else {
            if (mufRequest) {
                mufRequest.abort();
                mufRequest = null;
            }

            if (mufLayer && propagationMap.hasLayer(mufLayer)) {
                propagationMap.removeLayer(mufLayer);
            }
        }
    });

    // Reload the layer for the selected time range when it is enabled.
    $('#propTimeRange').on('change', function () {
        if ($('#propHeardMe').is(':checked')) {
            loadHeardMe();
        }
    });
}


function mufColor(value) {
    const low = [0, 131, 255];
    const medium = [255, 162, 0];
    const high = [255, 255, 0];
    const normalized = Math.max(0, Math.min(1, value / 50));
    const start = normalized < 0.5 ? low : medium;
    const end = normalized < 0.5 ? medium : high;
    const amount = normalized < 0.5
        ? normalized * 2
        : (normalized - 0.5) * 2;

    const color = start.map(function (channel, index) {
        return Math.round(channel + (end[index] - channel) * amount);
    });

    return 'rgb(' + color.join(',') + ')';
}


function loadMuf() {
    if (mufRequest) {
        mufRequest.abort();
    }

    mufRequest = new AbortController();

    fetch('/index.php/map/get_muf', {
        signal: mufRequest.signal
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP error ' + response.status);
            }

            return response.json();
        })
        .then(data => {
            if (!mufLayer) {
                mufLayer = L.layerGroup();
            }

            if (!$('#propMuf').is(':checked')) {
                return;
            }

            if (!propagationMap.hasLayer(mufLayer)) {
                mufLayer.addTo(propagationMap);
            }

            mufLayer.clearLayers();

            if (data.error) {
                console.error('MUF API error:', data.error);
                return;
            }

            (data.points || []).forEach(point => {
                const value = Number(point.muf);

                if (!Number.isFinite(value)) {
                    return;
                }

                const label = Math.round(value) + ' MHz';
                const marker = L.circleMarker(
                    [point.latitude, point.longitude],
                    {
                        radius: 1,
                        opacity: 0,
                        fillOpacity: 0
                    }
                );

                marker.bindTooltip(
                    '<span class="muf-value" style="background-color:' +
                    mufColor(value) +
                    '">' +
                    label +
                    '</span>',
                    {
                        permanent: true,
                        direction: 'bottom',
                        className: 'muf-tooltip'
                    }
                );

                marker.addTo(mufLayer);
            });
        })
        .catch(error => {
            if (error.name === 'AbortError') {
                return;
            }

            console.error('MUF error:', error);
        });
}


function addPropagationHomeMarker() {

    if (!propagationHomegrid) {
        return;
    }

    try {

        const locator = propagationHomegrid.trim().toUpperCase();

	const center = maidenheadToLatLng(locator);

if (!center) {
    console.warn(
        'Could not determine center of home gridsquare:',
        locator
    );
    return;
}
        propagationHomeMarker = L.circleMarker(center, {
            radius: 7,
            weight: 2,
            fillOpacity: 1
        })
        .bindPopup(
            '<strong>Station</strong><br>' +
            'Grid: ' + locator
        )
        .addTo(propagationMap);

        propagationMap.setView(center, 4);

    } catch (e) {

        console.error(
            'Error creating home station marker:',
            e
        );
    }
}


function loadHeardMe() {

    const minutes = $('#propTimeRange').val() || '15';

    if (heardMeRequest) {
        heardMeRequest.abort();
    }

    heardMeRequest = new AbortController();

    /*
     * Use a relative URL here.
     *
     * base_url is not available on the propagation view,
     * while /index.php/... works with our current Wavelog setup.
     */
    fetch(
        '/index.php/map/get_heard_me?minutes=' +
        encodeURIComponent(minutes),
        { signal: heardMeRequest.signal }
    )

        .then(response => {

            if (!response.ok) {
                throw new Error(
                    'HTTP error ' + response.status
                );
            }

            return response.json();
        })

        .then(data => {

            if (!heardMeLayer) {
                heardMeLayer = L.layerGroup();
            }

            if (!$('#propHeardMe').is(':checked')) {
                return;
            }

            if (!propagationMap.hasLayer(heardMeLayer)) {
                heardMeLayer.addTo(propagationMap);
            }

            heardMeLayer.clearLayers();

            if (data.error) {
                console.error(
                    'Heard Me API error:',
                    data.error
                );
                return;
            }

            if (!data.reports ||
                data.reports.length === 0) {

                console.log(
                    'No Heard Me reports'
                );

                return;
            }

            console.log(
                'Heard Me reports:',
                data.reports.length
            );

            data.reports.forEach(report => {

                const locator =
                    report.receiver_locator;

                if (!locator) {
                    return;
                }

	const center = maidenheadToLatLng(locator);

if (!center) {
    console.warn(
        'Invalid locator:',
        locator
    );
    return;
}


                /*
                 * Draw propagation path from our
                 * station to the receiving station.
                 */
                if (propagationHomeMarker) {

                    L.polyline(
                        [
                            propagationHomeMarker
                                .getLatLng(),

                            center
                        ],
                        {
                            weight: 1,
                            opacity: 0.4,
                            dashArray: '4,6'
                        }
                    )
                    .addTo(heardMeLayer);
                }


                /*
                 * Receiver marker
                 */
                const snr =
                    report.snr === null
                        ? 'n/a'
                        : report.snr + ' dB';

                const frequency =
                    report.frequency
                        ? (report.frequency /
                           1000000).toFixed(3) +
                          ' MHz'
                        : 'n/a';

                L.circleMarker(center, {
                    radius: 6,
                    weight: 2,
                    fillOpacity: 0.8
                })

                .bindPopup(

                    '<strong>' +
                    report.receiver_callsign +
                    '</strong><br>' +

                    'Grid: ' +
                    report.receiver_locator +
                    '<br>' +

                    'SNR: ' +
                    snr +
                    '<br>' +

                    'Freq: ' +
                    frequency
                )

                .addTo(heardMeLayer);

            });

        })

        .catch(error => {

            if (error.name === 'AbortError') {
                return;
            }

            console.error(
                'Heard Me error:',
                error
            );

        });
}


$(document).ready(function () {

    initPropagationMap();

});
