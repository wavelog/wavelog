let propagationMap = null;
let propagationTerminatorLayer = null;
let propagationHomeMarker = null;
let heardMeLayer = null;
let heardMeRequest = null;
let mufLayer = null;
let mufRequest = null;

function escapeHtml(value) {
    return $('<div>').text(value === null || value === undefined ? '' : String(value)).html();
}

function messageWithCount(message, count) {
    return message.replace('%d', count);
}

function setPropagationStatus(elementId, type, message) {
    $('#' + elementId)
        .removeClass('d-none alert-info alert-success alert-warning alert-danger alert-secondary')
        .addClass('alert-' + type)
        .text(message);
}

function clearPropagationStatus(elementId) {
    $('#' + elementId).addClass('d-none').text('');
}

function propagationBand(frequency) {
    const mhz = Number(frequency) / 1000000;
    const bands = [
        { min: 1.8, max: 2.0, name: '160m', color: '#6f42c1' },
        { min: 3.5, max: 4.0, name: '80m', color: '#0d6efd' },
        { min: 5.0, max: 5.5, name: '60m', color: '#20c997' },
        { min: 7.0, max: 7.3, name: '40m', color: '#198754' },
        { min: 10.1, max: 10.15, name: '30m', color: '#84cc16' },
        { min: 14.0, max: 14.35, name: '20m', color: '#ffc107' },
        { min: 18.068, max: 18.168, name: '17m', color: '#fd7e14' },
        { min: 21.0, max: 21.45, name: '15m', color: '#dc3545' },
        { min: 24.89, max: 24.99, name: '12m', color: '#d63384' },
        { min: 28.0, max: 29.7, name: '10m', color: '#e83e8c' },
        { min: 50.0, max: 54.0, name: '6m', color: '#6610f2' },
        { min: 70.0, max: 71.0, name: '4m', color: '#6c757d' },
        { min: 144.0, max: 148.0, name: '2m', color: '#212529' }
    ];

    return bands.find(band => mhz >= band.min && mhz <= band.max) || {
        name: 'Other',
        color: '#6c757d'
    };
}

function addPropagationBandLegend() {
    const legendBands = [
        propagationBand(1900000),
        propagationBand(3700000),
        propagationBand(7100000),
        propagationBand(14100000),
        propagationBand(18100000),
        propagationBand(21100000),
        propagationBand(24920000),
        propagationBand(28100000),
        propagationBand(50100000),
        propagationBand(145000000)
    ];
    const legend = L.control({ position: 'bottomright' });

    legend.onAdd = function () {
        const container = L.DomUtil.create('div', 'propagation-band-legend');
        container.innerHTML = '<strong>Bands</strong><br>' + legendBands.map(function (band) {
            return '<span style="background:' + band.color + '"></span>' + band.name;
        }).join(' &nbsp;');
        return container;
    };

    legend.addTo(propagationMap);
}

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

    addPropagationBandLegend();

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

            clearPropagationStatus('heardMeStatus');
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

            clearPropagationStatus('mufStatus');
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
    setPropagationStatus('mufStatus', 'info', propagationMessages.muf_loading);

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
                setPropagationStatus('mufStatus', 'danger', propagationMessages.muf_error);
                return;
            }

            if (!data.points || data.points.length === 0) {
                setPropagationStatus('mufStatus', 'secondary', propagationMessages.muf_empty);
                return;
            }

            setPropagationStatus(
                'mufStatus',
                'success',
                messageWithCount(propagationMessages.muf_loaded, data.points.length)
            );

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
            setPropagationStatus('mufStatus', 'danger', propagationMessages.muf_error);
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
            'Grid: ' + escapeHtml(locator)
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
    setPropagationStatus('heardMeStatus', 'info', propagationMessages.heard_loading);

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
                setPropagationStatus('heardMeStatus', 'danger', propagationMessages.heard_error);
                return;
            }

            if (!data.reports ||
                data.reports.length === 0) {

                console.log(
                    'No Heard Me reports'
                );

                setPropagationStatus('heardMeStatus', 'secondary', propagationMessages.heard_empty);

                return;
            }

            console.log(
                'Heard Me reports:',
                data.reports.length
            );

            if (data.reports.length >= 500) {
                setPropagationStatus('heardMeStatus', 'warning', propagationMessages.heard_limited);
            } else {
                setPropagationStatus(
                    'heardMeStatus',
                    'success',
                    messageWithCount(propagationMessages.heard_loaded, data.reports.length)
                );
            }

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

                const frequencyHz = Number(report.frequency) || 0;
                const band = propagationBand(frequencyHz);


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
                            color: band.color,
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
                    frequencyHz
                        ? (frequencyHz /
                           1000000).toFixed(3) +
                          ' MHz'
                        : 'n/a';

                const mode = report.mode
                    ? escapeHtml(report.mode)
                    : 'n/a';

                L.circleMarker(center, {
                    color: band.color,
                    fillColor: band.color,
                    radius: 6,
                    weight: 2,
                    fillOpacity: 0.8
                })

                .bindPopup(

                    '<strong>' +
                    escapeHtml(report.receiver_callsign) +
                    '</strong><br>' +

                    'Grid: ' +
                    escapeHtml(report.receiver_locator) +
                    '<br>' +

                    'Band: ' +
                    escapeHtml(band.name) +
                    '<br>' +

                    'Mode: ' +
                    mode +
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

            setPropagationStatus('heardMeStatus', 'danger', propagationMessages.heard_error);

        });
}


$(document).ready(function () {

    initPropagationMap();

});
