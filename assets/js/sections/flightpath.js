var satmarker;
let maidenhead;
let leafletMap;
let saticon = L.divIcon({
    html: '<i class="fa-solid fa-satellite" style="font-size: 24px; color: black; -webkit-text-stroke: 1px white;"></i>',
    className: '', // Prevents default Leaflet styles
    iconSize: [30, 30],
    iconAnchor: [15, 15] // Center the icon
});
let homeicon = L.icon({ iconUrl: icon_home_url, iconSize: [15, 15] });

let observerGd = {
	longitude: satellite.degreesToRadians(homelon),
	latitude: satellite.degreesToRadians(homelat),
	height: 0.370
};

let sats = (function (L, d3, satelliteJs) {
  let RADIANS = Math.PI / 180;
  let DEGREES = 180 / Math.PI;
  let R_EARTH = 6378.137; // equatorial radius (km)

  /* =============================================== */
  /* =============== CLOCK ========================= */
  /* =============================================== */

  /**
   * Factory function for keeping track of elapsed time and rates.
   */
  function Clock() {
    this._rate = 60; // 1ms elapsed : 60sec simulated
    this._date = d3.now();
    this._elapsed = 0;
  };

  Clock.prototype.date = function (timeInMs) {
    if (!arguments.length) return this._date + (this._elapsed * this._rate);
    this._date = timeInMs;
    return this;
  };

  Clock.prototype.elapsed = function (ms) {
    if (!arguments.length) return this._date - d3.now(); // calculates elapsed
    this._elapsed = ms;
    return this;
  };

  Clock.prototype.rate = function (secondsPerMsElapsed) {
    if (!arguments.length) return this._rate;
    this._rate = secondsPerMsElapsed;
    return this;
  };

  /* ==================================================== */
  /* =============== CONVERSION ========================= */
  /* ==================================================== */

	function satrecToFeature(satrec, date, props) {		// DJ7NT: This is never called
		let properties = props || {};
		let positionAndVelocity = satelliteJs.propagate(satrec, date);
		let gmst = satelliteJs.gstime(date);
		let positionGd = satelliteJs.eciToGeodetic(positionAndVelocity.position, gmst);
		properties.height = positionGd.height;
		return {
			type: "FeatureCollection",
			"features": [ {
				type: 'Feature',
				properties: properties,
				geometry: {
					type: 'Point',
					coordinates: [
						positionGd.longitude * DEGREES,
						positionGd.latitude * DEGREES
					]
				}
			},
			{
				type: 'Feature',
				properties: {infoText: 'blabla'},
				geometry: {
					type: 'Point',
					coordinates: [
						positionGd.longitude * DEGREES,
						positionGd.latitude * DEGREES
					]
				}
			}]
		};
	};
  /* ==================================================== */
  /* =============== TLE ================================ */
  /* ==================================================== */

  /**
   * Factory function for working with TLE.
   */
  function TLE() {
    this._properties;
    this._date;
  };
  TLE.prototype._lines = function (arry) {
    return arry.slice(0, 2);
  };

  TLE.prototype.satrecs = function (tles) {
    return tles.map(function (d) {
      return satelliteJs.twoline2satrec.apply(null, this._lines(d));
    });
  };

  TLE.prototype.features = function (tles) {
    let date = this._date || d3.now();

    return tles.map(function (d) {
      let satrec = satelliteJs.twoline2satrec.apply(null, this._lines(d));
      return satrecToFeature(satrec, date, this._properties(d));
    });
  };

  TLE.prototype.lines = function (func) {
    if (!arguments.length) return this._lines;
    this._lines = func;
    return this;
  };

  TLE.prototype.properties = function (func) {
    if (!arguments.length) return this._properties;
    this._properties = func;
    return this;
  };

  TLE.prototype.date = function (ms) {
    if (!arguments.length) return this._date;
    this._date = ms;
    return this;
  };


  /* ==================================================== */
  /* =============== PARSE ============================== */
  /* ==================================================== */

  /**
   * Parses text file string of tle into groups.
   * @return {string[][]} Like [['tle line 1', 'tle line 2'], ...]
   */
  function parseTle(tleString) {
    // remove last newline so that we can properly split all the lines
    let lines = tleString.replace(/\r?\n$/g, '').split(/\r?\n/);

    return lines.reduce(function (acc, cur, index) {
      if (index % 2 === 0) acc.push([]);
      acc[acc.length - 1].push(cur);
      return acc;
    }, []);
  };


  /* ==================================================== */
  /* =============== SATELLITE ========================== */
  /* ==================================================== */

  /**
   * Satellite factory function that wraps satellitejs functionality
   * and can compute footprints based on TLE and date
   *
   * @param {string[][]} tle two-line element
   * @param {Date} date date to propagate with TLE
   */
  function Satellite(tle, date) {
    this._satrec = satelliteJs.twoline2satrec(tle[0], tle[1]);
    this._satNum = this._satrec.satnum; // NORAD Catalog Number

    this._altitude; // km
    this._position = {
      lat: null,
      lng: null
    };
    this._halfAngle; // degrees
    this._date;
    this._gmst;

    this.setDate(date);
    this.update();
    this._orbitType = this.orbitTypeFromAlt(this._altitude); // LEO, MEO, or GEO
  };

  /**
   * Updates satellite position and altitude based on current TLE and date
   */
	Satellite.prototype.update = function () {
		try {
			let positionAndVelocity = satelliteJs.propagate(this._satrec, this._date);
			let positionGd = satelliteJs.eciToGeodetic(positionAndVelocity.position, this._gmst);
			let positionEcf = satelliteJs.eciToEcf(positionAndVelocity.position, this._gmst);
			let lA = satelliteJs.ecfToLookAngles(observerGd, positionEcf);

			this._lookAngles = {
				azimuth: lA.azimuth * DEGREES,
				elevation: lA.elevation * DEGREES,
				rangeSat: lA.rangeSat
			};
			this._position = {
				lat: positionGd.latitude * DEGREES,
				lng: positionGd.longitude * DEGREES
			};
			this._altitude = positionGd.height;
			satmarker.setLatLng(this._position);
		} catch (e) {
			// Malicious // non-calcable SAT Found
		} finally  {
			return this;
		}
	};

  /**
   * @returns {GeoJSON.Polygon} GeoJSON describing the satellite's current footprint on the Earth
   */
  Satellite.prototype.getFootprint = function () {
    let theta = this._halfAngle * RADIANS;

    coreAngle = this._coreAngle(theta, this._altitude, R_EARTH) * DEGREES;

    return d3.geoCircle()
      .center([this._position.lng, this._position.lat])
      .radius(coreAngle)();
  };

  Satellite.prototype.getLocation = function () {
    return d3.geoCircle()
      .center([this._position.lng, this._position.lat])
      .radius(1)();
  };

  /**
   * A conical satellite with half angle casts a circle on the Earth. Find the angle
   * from the center of the earth to the radius of this circle
   * @param {number} theta: Satellite half angle in radians
   * @param {number} altitude Satellite altitude
   * @param {number} r Earth radius
   * @returns {number} core angle in radians
   */
  Satellite.prototype._coreAngle = function (theta, altitude, r) {
    // if FOV is larger than Earth, assume it goes to the tangential point
    // if (Math.sin(theta) != r / (altitude + r)) {
      return Math.acos(r / (r + altitude));
    // }
    // return Math.abs(Math.asin((r + altitude) * Math.sin(theta) / r)) - theta;
  };

  Satellite.prototype.halfAngle = function (halfAngle) {
    if (!arguments.length) return this._halfAngle;
    this._halfAngle = halfAngle;
    return this;
  };

  Satellite.prototype.satNum = function (satNum) {
    if (!arguments.length) return this._satNum;
    this._satNum = satNum;
    return this;
  };

  Satellite.prototype.altitude = function (altitude) {
    if (!arguments.length) return this._altitude;
    this._altitude = altitude;
    return this;
  };

  Satellite.prototype.position = function (position) {
    if (!arguments.length) return this._position;
    this._position = position;
    return this;
  };

  Satellite.prototype.getOrbitType = function () {
    return this._orbitType;
  };

  /**
   * sets both the date and the Greenwich Mean Sidereal Time
   * @param {Date} date
   */
  Satellite.prototype.setDate = function (date) {
    this._date = date;
    this._gmst = satelliteJs.gstime(date);
    return this;
  };

  /**
   * Maps an altitude to a type of satellite
   * @param {number} altitude (in KM)
   * @returns {'LEO' | 'MEO' | 'GEO'}
   */
  Satellite.prototype.orbitTypeFromAlt = function (altitude) {
    this._altitude = altitude || this._altitude;
    return this._altitude < 2000 ? 'LEO' : this._altitude > 22000 ? 'GEO' : 'MEO';
  };


  /* =============================================== */
  /* =============== LEAFLET MAP =================== */
  /* =============================================== */

  // Approximate date the tle data was aquired from https://www.space-track.org/#recent
  // let TLE_DATA_DATE = new Date(2024, 04, 18).getTime();
  let TLE_DATA_DATE = Date.now();

  let attributionControl;
  let activeClock;
  let sats;
  let svgLayer;

  function projectPointCurry(map) {
    return function (x, y) {
      const point = map.latLngToLayerPoint(L.latLng(y, x));
      this.stream.point(point.x, point.y);
    }
  };

  function init(satellite) {
    svgLayer = L.svg();
    leafletMap = L.map('sat_map', {
      zoom: 3,
      center: [20, 0],
    //   attributionControl: false,
      layers: [
        L.tileLayer(tileUrl, {
        //   noWrap: false,
        }),
        svgLayer
      ]
    });

	satmarker = L.marker(
		[0, 0], {
			icon: saticon,
			title: satellite,
			zIndex: 1000,
		}
	).addTo(leafletMap).on('click', displayUpComingPasses);

	// Add an always-visible label (tooltip)
	satmarker.bindTooltip(satellite, {
		permanent: true,  // Always visible
		direction: "top", // Position label above the marker
		offset: [0, -20], // Adjust position
		className: "satellite-label" // Optional: Custom CSS
		// className: "leaflet-popup-content-wrapper" // Optional: Custom CSS
	});

	L.marker(
		[homelat, homelon], {
			icon: homeicon,
			title: 'Home',
			zIndex: 1000,
		}
	).addTo(leafletMap);

	/*Legend specific*/
    let legend = L.control({ position: "topright" });

    legend.onAdd = function(map) {
        let div = L.DomUtil.create("div", "legend");
        let html = "<h4>Satellite Details</h4>";
        html += "<table>";
        html += '<tr><td><span>Satellite</span></td><td align="right"><span id="satname"></span></td></tr>';
        html += '<tr><td><span>Orbit</span></td><td align="right"><span id="satorbit"></span></td></tr>';
        html += '<tr><td><span>Altitude</span></td><td align="right"><span id="satalt"></span></td></tr>';
        html += '<tr><td><span>Azimuth</span></td><td align="right"><span id="az"></span></td></tr>';
        html += '<tr><td><span>Elevation</span></td><td align="right"><span id="ele"></span></td></tr>';
		html += '<tr><td><span>Gridsquare</span></td><td align="right"><span id="grid"></span></td></tr>';
        html += '<tr><td><input type="checkbox" onclick="toggleGridsquares(this.checked)" checked="checked" style="outline: none;"></td><td><span> ' + lang_gen_hamradio_gridsquares + '</span></td></tr>';
        html += "</table>";
        div.innerHTML = html;
        return div;
    };

    legend.addTo(leafletMap);

    maidenhead = L.maidenhead().addTo(leafletMap);

    attributionControl = L.control.attribution({
      prefix: ''
    }).addTo(leafletMap);

    let transform = d3.geoTransform({
      point: projectPointCurry(leafletMap)
    });

    path = d3.geoPath()
      .projection(transform)
      .pointRadius(2.5);
  };

	function updateSats(date) {
		sats.forEach(function (sat) {
			sat.setDate(date).update();
			let az = (Math.round((sat._lookAngles.azimuth*100),2)/100).toFixed(2);
			let ele = (Math.round((sat._lookAngles.elevation*100),2)/100).toFixed(2);
			if (ele > 0) {
				az = "<b>"+az+"°</b>";
				ele = "<b>"+ele+"°</b>";
			} else {
				az = az+"°";
				ele = ele+"°";
			}
			$("#az").html(az);
			$("#ele").html(ele);
			$("#satorbit").html(sat.getOrbitType());
			$("#satalt").html(Math.round(sat.altitude() * 1,60934)+" km");
			$("#grid").html(latLngToLocator(sat._position.lat,sat._position.lng));
		});
		return;
	};

  /**
   * Create satellite objects for each record in the TLEs and begin animation
   * @param {string[][]} parsedTles
   */
  function initSats(parsedTles) {
    activeClock = new Clock()
      .rate(1)
      .date(TLE_DATA_DATE);
    sats = parsedTles.map(function (tle) {
      let sat = new Satellite(tle, new Date());
      sat.halfAngle(30);
      // sat.halfAngle(sat.getOrbitType() === 'LEO' ? Math.random() * (30 - 15) + 15 : Math.random() * 4 + 1);
      return sat;
    });

    leafletMap.on('zoom', draw);

    window.requestAnimationFrame(animateSats);
    return sats;
  };

  function invertProjection(projection) {
    return function (x, y) {
      const point = projection.invert([x, y]);
      this.stream.point(point[0], point[1]);
    };
  }

  function clipMercator(geoJson) {
    const mercator = d3.geoMercator();
    const inverseMercator = d3.geoTransform({
      point: invertProjection(mercator)
    });
    // D3 geoProject handles Mercator clipping
    const newJson = d3.geoProject(geoJson, mercator);
    return d3.geoProject(newJson, inverseMercator);
  }

  function draw() {
    let transform = d3.geoTransform({
      point: projectPointCurry(leafletMap)
    });
    let geoPath = d3.geoPath()
      .projection(transform);

    d3.select(svgLayer._container)
      .selectAll('.footprint')
      .data(sats, function (sat) {
        return sat._satNum;		// DJ7NT: This is the Number of the SAT
      })
      .join(
        function (enter) {
	  return enter.append('path').attr('class', function (sat) {
            return 'footprint footprint--' + sat.getOrbitType();
          });
        },
        function (update) {
          return update;
        },
        function (exit) {
          return exit.remove();
        }
      ).attr('d', function (sat) {
        // return geoPath(clipMercator(sat.getLocation()));	// DJ7NT: this is the "point" of the SAT
        let xx= geoPath(clipMercator(sat.getFootprint()));
        return xx;
      });
  };

  function animateSats(elapsed) {
    let dateInMs = activeClock.elapsed(elapsed).date();
    let date = new Date(dateInMs);
    attributionControl.setPrefix(date);

    updateSats(date);
    draw();
    window.requestAnimationFrame(animateSats);
  }

  function start(data) {
	init(data.satellite);
	initSats(parseTle(data.tle));
  }

  return {
	start: start
};


 }(window.L, window.d3, window.satellite))

function plot_sat() {
	let container = L.DomUtil.get('sat_map');
	if(container != null){
		container._leaflet_id = null;
		container.remove();
	}

	amap = $('#sat_map').val();
	if (amap == undefined) {
		$("#satcontainer").append('<div id="sat_map" class="map-leaflet" style="width: 100%; height: 85vh"></div>');
	}

	$.ajax({
		url: base_url + 'index.php/satellite/get_tle',
		type: 'post',
		data: {
			sat: $("#sats").val(),
		},
		success: function (data) {
			sats.start(data);
			$("#satname").html($("#sats").val());
		},
		error: function (data) {
			alert('Something went wrong!');
		},
	});
}

function toggleGridsquares(bool) {
	if(!bool) {
		leafletMap.removeLayer(maidenhead);
	} else {
		maidenhead.addTo(leafletMap);
	}
};

$( document ).ready(function() {
	if ($("#sats").val() != '') {
		plot_sat();
	}
});

function displayUpComingPasses(e) {
	$.ajax({
		url: base_url + 'index.php/satellite/searchPasses',
        type: 'post',
        data: {'sat': $("#sats").val(),
            'yourgrid': homegrid,
            'minelevation': 0,
            'minazimuth': 0,
            'maxazimuth': 360,
            'date': new Date().toISOString().slice(0, 10),
            'mintime': new Date().toISOString().slice(11, 13),
        },
		success: function (html) {
			let dialog = new BootstrapDialog({
			title: lang_gen_hamradio_upcoming_passes + ' ' + $("#sats").val(),
				size: BootstrapDialog.SIZE_WIDE,
				cssClass: 'qso-dialog',
				nl2br: false,
				message: html,
				onshown: function(dialog) {
					$('[data-bs-toggle="tooltip"]').tooltip();
					$('.satpasstable').DataTable({
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
							{
								extend: 'csv',
								className: 'mb-1 btn btn-primary', // Bootstrap classes
									init: function(api, node, config) {
										$(node).removeClass('dt-button').addClass('btn btn-primary'); // Ensure Bootstrap class applies
								},
							}
						]
					});
				},
				buttons: [{
				label: lang_admin_close,
					action: function (dialogItself) {
						dialogItself.close();
					}
					}]
			});
			dialog.realize();
			$('#satcontainer').append(dialog.getModal());
			dialog.open();
		}
	});
}
