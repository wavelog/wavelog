let lastUpdateTime = 0; // Track the last update time
var satmarker;
let maidenhead;
let leafletMap;
let saticon = L.divIcon({
    html: '<i class="fa-solid fa-satellite" style="font-size: 24px; color: black; -webkit-text-stroke: 1px white;"></i>',
    className: '', // Prevents default Leaflet styles
    iconSize: [30, 30],
    iconAnchor: [15, 15] // Center the icon
});
let pasticon = L.divIcon({
    html: '<i class="fa-solid fa-satellite" style="font-size: 24px; opacity: 0.75; color: grey; -webkit-text-stroke: 1px white;"></i>',
    className: '',
    iconSize: [30, 30],
    iconAnchor: [15, 15]
});
let futureicon = L.divIcon({
    html: '<i class="fa-solid fa-satellite" style="font-size: 24px; opacity: 0.75; color: grey; -webkit-text-stroke: 1px white;"></i>',
    className: '',
    iconSize: [30, 30],
    iconAnchor: [15, 15]
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

function computePath(satrec, date, minutesBack, minutesAhead, stepSeconds) {
    let pastSegments = [[]]; // Store separate path segments for past
    let futureSegments = [[]]; // Store separate path segments for future
    let lastLng = null;

    for (let t = -minutesBack * 60; t <= minutesAhead * 60; t += stepSeconds) {
        let newDate = new Date(date.getTime() + t * 1000);
        let gmst = satelliteJs.gstime(newDate);
        let positionAndVelocity = satelliteJs.propagate(satrec, newDate);
        if (!positionAndVelocity.position) continue;

        let positionGd = satelliteJs.eciToGeodetic(positionAndVelocity.position, gmst);
        let lat = positionGd.latitude * DEGREES;
        let lng = positionGd.longitude * DEGREES;

        // Handle Antimeridian crossing
        if (lastLng !== null && Math.abs(lng - lastLng) > 180) {
            if (t < 0) {
                pastSegments.push([]); // Start a new segment for past path
            } else {
                futureSegments.push([]); // Start a new segment for future path
            }
        }

        // Add the current point to the correct segment
        if (t < 0) {
            pastSegments[pastSegments.length - 1].push([lat, lng]);
        } else {
            futureSegments[futureSegments.length - 1].push([lat, lng]);
        }

        lastLng = lng; // Update last longitude
    }
    return { pastSegments, futureSegments };
}

// Update function for satellite
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

        // Update satellite marker
        satmarker.setLatLng(this._position);

        if (this._altitude < 35700 || this._altitude > 36000) {

           pastmarker.remove();
           futuremarker.remove();

           pastmarker.addTo(leafletMap)
           futuremarker.addTo(leafletMap)
           // Compute paths with Antimeridian handling
           let { pastSegments, futureSegments } = computePath(this._satrec, this._date, 100, 100, 10);
           pastmarker.setLatLng({lat: pastSegments[0][0][0], lng: pastSegments[0][0][1]});
           futuremarker.setLatLng({lat: futureSegments[(futureSegments.length - 1)][futureSegments[(futureSegments.length - 1)].length - 1][0], lng: futureSegments[(futureSegments.length - 1)][futureSegments[(futureSegments.length - 1)].length - 1][1]});

           // Remove old polylines if they exist
           if (this._pastTrajectories) {
               this._pastTrajectories.forEach(poly => leafletMap.removeLayer(poly));
           }
           if (this._futureTrajectories) {
               this._futureTrajectories.forEach(poly => leafletMap.removeLayer(poly));
           }

           // Draw new trajectory segments
           this._pastTrajectories = pastSegments.map(segment =>
               L.polyline(segment, { color: 'red' }).addTo(leafletMap)
           );
           this._futureTrajectories = futureSegments.map(segment =>
               L.polyline(segment, { color: 'green' }).addTo(leafletMap)
           );

           // 📌 **Fix Arrow Direction Using Ground Track Bearing**
           let nextDate = new Date(this._date.getTime() + 10000); // 5 sec into the future
           let nextPos = satelliteJs.propagate(this._satrec, nextDate);
           let nextGd = satelliteJs.eciToGeodetic(nextPos.position, this._gmst);

           let nextLat = nextGd.latitude * DEGREES;
           let nextLng = nextGd.longitude * DEGREES;

           let heading = getBearing(this._position.lat, this._position.lng, nextLat, nextLng);

           // Remove old arrow marker if it exists
           if (this._directionArrow) {
               leafletMap.removeLayer(this._directionArrow);
           }

           // Define arrow icon using an SVG
           let arrowIcon = L.divIcon({
               className: "custom-arrow",
               html: `<div style="
                   transform: rotate(${heading-90}deg);
                   font-size: 20px;
                   color: yellow;
                   ">➤</div>`, // Unicode arrow
               iconSize: [20, 20],
               iconAnchor: [15, -15]
           });

           // Offset the arrow slightly ahead of the satellite position
           let arrowOffset = 0.1; // Small offset factor
           let arrowLat = this._position.lat + arrowOffset * Math.sin(heading * (Math.PI / 180));
           let arrowLng = this._position.lng + arrowOffset * Math.cos(heading * (Math.PI / 180));

           // Add the arrow marker
           this._directionArrow = L.marker([arrowLat, arrowLng], { icon: arrowIcon }).addTo(leafletMap);
        }

    } catch (e) {
        console.error("Error updating satellite:", e);
    }
};

/**
 * Compute bearing (heading) between two lat/lng points
 */
function getBearing(lat1, lng1, lat2, lng2) {
    let phi1 = lat1 * Math.PI / 180;
    let phi2 = lat2 * Math.PI / 180;
    let deltaPi = (lng2 - lng1) * Math.PI / 180;

    let y = Math.sin(deltaPi) * Math.cos(phi2);
    let x = Math.cos(phi1) * Math.sin(phi2) - Math.sin(phi1) * Math.cos(phi2) * Math.cos(deltaPi);
    let theta = Math.atan2(y, x);

    return (theta * 180 / Math.PI + 360) % 360; // Normalize to 0-360
}

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
      minZoom: 1,
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
			zIndex: 1000,
		}
	).addTo(leafletMap).on('click', displayUpComingPasses);

	pastmarker = L.marker(
		[0, 0], {
			icon: pasticon,
			zIndex: 1000,
		}
	);
	pastmarker.bindTooltip("-90 min", { permanent: true, offset: [15, 15], className: '', opacity: 0.65 });

	futuremarker = L.marker(
		[0, 0], {
			icon: futureicon,
			zIndex: 1000,
		}
	);
	futuremarker.bindTooltip("+90 min", { permanent: true, offset: [15, 15], className: '', opacity: 0.65 });

	// Add an always-visible label (tooltip)
	satmarker.bindTooltip(satellite, {
		permanent: true,  // Always visible
		direction: "top", // Position label above the marker
		offset: [0, -20], // Adjust position
		title: satellite,
		className: "satellite-label" // Optional: Custom CSS
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
		html += '<tr><td><span>Status</span></td><td align="right"><span id="status"></span></td></tr>';
		html += '<tr><td><span id="LAOS">AOS Az</span></td><td align="right"><span id="osaz"></span></td></tr>';
		html += '<tr><td><span>Visible</span></td><td align="right"><span id="visibility"></span></td></tr>';
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
			let az = (Math.round((sat._lookAngles.azimuth * 10), 2) / 10).toFixed(1);
			let ele = (Math.round((sat._lookAngles.elevation * 10), 2) / 10).toFixed(1);

			if (ele > 0) { // Satellite is in view
				let [nextLOS,losaz] = findNextEvent(sat, date, 1440, "LOS");
				$("#status").html(nextLOS ? `LOS in ${nextLOS}` : "No LOS found in next 24h");
				$("#visibility").html("<div class='bg-success awards BgSuccess text-center'>Yes</div>");
				$("#LAOS").html('LOS Az');
				$("#osaz").html(losaz !== null ? losaz+'&deg;' : 'n/a');
			} else { // Satellite is below horizon
				let [nextAOS,aosaz] = findNextEvent(sat, date, 1440, "AOS");
				$("#status").html(nextAOS ? `AOS in ${nextAOS}` : "No AOS found in next 24h");
				$("#visibility").html("<div class='bg-danger awards BgDanger text-center'>No</div>");
				$("#LAOS").html('AOS Az');
				$("#osaz").html(aosaz !== null ? aosaz+'&deg;' : 'n/a');
			}

			az = "<b>" + az + "°</b>";
			ele = "<b>" + ele + "°</b>";

			$("#az").html(az);
			$("#ele").html(ele);
			$("#satorbit").html(sat.getOrbitType());
			$("#satalt").html(Math.round(sat.altitude()) + " km");
			$("#grid").html(latLngToLocator(sat._position.lat, sat._position.lng));
		});
	}

	function findNextEvent(sat, observerDate, maxMinutesAhead = 1440, eventType = "AOS") {
		let stepSeconds = 1;
		let currentTime = new Date(observerDate);

		let lastElevation = -90; // Default below horizon
		for (let t = 0; t <= maxMinutesAhead * 60; t += stepSeconds) {
			let futureTime = new Date(currentTime.getTime() + t * 1000);
			let gmst = satelliteJs.gstime(futureTime);
			let positionAndVelocity = satelliteJs.propagate(sat._satrec, futureTime);
			if (!positionAndVelocity.position) continue;

			let positionGd = satelliteJs.eciToGeodetic(positionAndVelocity.position, gmst);
			let positionEcf = satelliteJs.eciToEcf(positionAndVelocity.position, gmst);
			let lookAngles = satelliteJs.ecfToLookAngles(observerGd, positionEcf);
			let elevation = lookAngles.elevation;

			if (eventType === "AOS" && lastElevation <= 0 && elevation > 0) {
				let timeDiff = Math.round((futureTime - currentTime) / 1000); // Seconds
				let aosaz = (Math.round((satelliteJs.radiansToDegrees(lookAngles.azimuth) * 10), 2) / 10).toFixed(1);
				return [formatCountdown(timeDiff), aosaz];
			}

			if (eventType === "LOS" && lastElevation > 0 && elevation <= 0) {
				let timeDiff = Math.round((futureTime - currentTime) / 1000); // Seconds
				let losaz = (Math.round((satelliteJs.radiansToDegrees(lookAngles.azimuth) * 10), 2) / 10).toFixed(1);
				return [formatCountdown(timeDiff),losaz];
			}

			lastElevation = elevation; // Store previous elevation
		}
		return [null,null]; // No event found
	}

	function formatCountdown(seconds) {
		let min = Math.floor(seconds / 60);
		let sec = seconds % 60;
		return `${min}m ${sec}s`;
	}



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

	  if (dateInMs - lastUpdateTime >= 1000) { // Only update every 1 second
		  updateSats(date);
		  lastUpdateTime = dateInMs;
	  }

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
					$('.satelliteinfo').click(function (event) {
						getSatelliteInfo(this);
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

function getSatelliteInfo(element) {
	var satname = $(element).closest('td').contents().first().text().trim();
	$.ajax({
        url: base_url + 'index.php/satellite/getSatelliteInfo',
        type: 'post',
        data: {'sat': satname,
        },
        success: function (html) {
			BootstrapDialog.show({
				title: 'Satellite information',
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
