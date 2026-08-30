let propagationMap = null;
let propagationTerminatorLayer = null;
let propagationHomeMarker = null;
let heardMeLayer = null;

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
    loadHeardMe();

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

    /*
     * Use a relative URL here.
     *
     * base_url is not available on the propagation view,
     * while /index.php/... works with our current Wavelog setup.
     */
    fetch('/index.php/map/get_heard_me')

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
                heardMeLayer = L.layerGroup()
                    .addTo(propagationMap);
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

            console.error(
                'Heard Me error:',
                error
            );

        });
}


$(document).ready(function () {

    initPropagationMap();

});
