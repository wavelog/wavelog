const isValidLocatorString = locatorString => locatorString.match(/^[A-Ra-r][A-Ra-r]\d\d[A-Xa-x][A-Xa-x]/) !== null;
const charToNumber = char => char.toUpperCase().charCodeAt(0) - CHAR_CODE_OFFSET;
const numberToChar = number => String.fromCharCode(number + CHAR_CODE_OFFSET);
const CHAR_CODE_OFFSET = 65;
const degToRad = deg => (deg % 360) * Math.PI / 180;
const radToDeg = rad => (rad / Math.PI *180) % 360;
const isValidPoint = (lat, lng) => (lat >= -90 && lat <= 90) && (lng >= -180 && lng <= 180);

var clickmarkers = [];
var clicklines = [];

function ConvertDDToDMS(lat, lng) {
	var LatLng = [];

	if (lng < -180) {
		lng = lng + 360;
	}
	if (lng > 180)  {
		lng = lng - 360;
	}

	LatLng['latDeg'] = (lat < 0 ? "S" : "N") + " " + pad((0 |(lat < 0 ? (lat = -lat) : lat)), 2) + "° " + pad(0 | (((lat += 1e-9) % 1) * 60),2) + "' " + ((0 | (((lat * 60) % 1) * 6000)) / 100) + "\"";

	LatLng['lngDeg'] = (lng < 0 ? "W" : "E") + " " + pad((0 | (lng < 0 ? (lng = -lng) : lng)), 3) + "° " + pad(0 | (((lng += 1e-9) % 1) * 60),2) + "' " + ((0 | (((lng * 60) % 1) * 6000)) / 100) + "\"";

	return LatLng;
}

function pad(n, width, z) {
  z = z || '0';
  n = n + '';
  return n.length >= width ? n : new Array(width - n.length + 1).join(z) + n;
}

const latLngToLocator = (lat, lng) => {
	if (lng < -180) {
		lng = lng + 360;
	}
	if (lng > 180)  {
		lng = lng - 360;
	}
	if (!isValidPoint(lat, lng)) {
		throw new Error('Input is not a valid coordinate');
	}

	const longitude = lng + 180;
	const latitude = lat + 90;

	const fieldLng = numberToChar(Math.floor(longitude / 20));
	const fieldLat = numberToChar(Math.floor(latitude / 10));

	const squareLng = Math.floor(longitude % 20 / 2);
	const squareLat = Math.floor(latitude % 10);

	const subsquareLng = numberToChar(Math.floor((longitude % 20 % 2) * 12)).toLowerCase();
	const subsquareLat = numberToChar((latitude % 10 - squareLat) * 24).toLowerCase();

	return fieldLng + fieldLat + squareLng + squareLat + subsquareLng + subsquareLat;
};

function onMapMove(event) {
	var LatLng = event.latlng;
	var lat = LatLng.lat;
	var lng = LatLng.lng;
	var LatLng2 = ConvertDDToDMS(lat, lng);
	$('#latDeg').html(LatLng2.latDeg);
	$('#lngDeg').html(LatLng2.lngDeg);
	var locator = latLngToLocator(lat,lng);
	$('#locator').html(locator);
	var distance = bearingDistance(homegrid, locator);

	let unit;

	switch (measurement_base) {
		case 'M':
			distance.distance = distance.distance * 3959;
			unit = 'mi';
			break;
		case 'K':
			distance.distance = distance.distance * 6371;
			unit = 'km';
			break;
		case 'N':
			distance.distance = distance.distance * 3440;
			unit = 'nmi';
			break;
		default:
			distance.distance = distance.distance * 6371;
			unit = 'km';
			break;
	}

	$('#bearing').html(distance.deg + '°');
	$('#distance').html(Math.round(distance.distance * 10) / 10 + ' ' +unit);

	if (typeof zonestuff !== 'undefined' && zonestuff) {
		const cqZone = findCQZone(event.latlng);
		$('#cqzonedisplay').html(cqZone);
	}

	if (typeof ituzonestuff !== 'undefined' && ituzonestuff) {
		const ituZone = findITUZone(event.latlng);
		$('#ituzonedisplay').html(ituZone);
	}
};

function findCQZone(latlng) {
	let cqZone = null;
	zonestuff.features.forEach(feature => {
        try {
            if (isMarkerInsidePolygon(latlng, feature)) {
				cqZone = feature.properties.cq_zone_number;
			}
        } catch (error) {
            console.error(error);
        }
    });

    return cqZone;
}

function findITUZone(latlng) {
	if (85 < parseFloat(latlng.lat).toFixed(6))
		return "75";
	if (-85 > parseFloat(latlng.lat).toFixed(6))
		return "74";
	let ituZone = null;
	ituzonestuff.features.forEach(feature => {
        try {
            if (isMarkerInsidePolygon(latlng, feature)) {
				ituZone = feature.properties.itu_zone_number;
			}
        } catch (error) {
            console.error(error);
        }
    });

    return ituZone;
}

function isMarkerInsidePolygon(marker, poly) {
    const x = marker.lng; // Longitude
    const y = marker.lat; // Latitude

    const polyPoints = poly.geometry.coordinates[0];
    let inside = false;

    for (let i = 0, j = polyPoints.length - 1; i < polyPoints.length; j = i++) {
        const xi = polyPoints[i][0], yi = polyPoints[i][1];
        const xj = polyPoints[j][0], yj = polyPoints[j][1];

        const intersect = ((yi > y) !== (yj > y)) &&
                          (x < (xj - xi) * (y - yi) / (yj - yi) + xi);
        if (intersect) inside = !inside;
    }

    return inside;
}

function onMapClick(event) {
	if ($('.modal-dialog')[0]) {
		return;
	}
	var LatLng = event.latlng;
	var lat = LatLng.lat;
	var lng = LatLng.lng;
	var locator = latLngToLocator(lat,lng);
	var fromCoords = locatorToLatLng(homegrid);

	var marker = L.marker([fromCoords[0], fromCoords[1]], {closeOnClick: false, autoClose: false}).addTo(map).bindPopup(homegrid);

	clickmarkers.push(marker);

	var result = bearingDistance(homegrid, locator);

	let unit;

	switch (measurement_base) {
		case 'M':
			result.distance = result.distance * 3959;
			unit = 'mi';
			break;
		case 'K':
			result.distance = result.distance * 6371;
			unit = 'km';
			break;
		case 'N':
			result.distance = result.distance * 3440;
			unit = 'nmi';
			break;
		default:
			result.distance = result.distance * 6371;
			unit = 'km';
			break;
	}


	var distance = Math.round(result.distance * 10) / 10 + ' ' +unit;
	var bearing = Math.round(result.deg * 10) / 10 + '°';
	var popupmessage = '<div class="popup">' +
	'From gridsquare: ' + homegrid + '<br />To gridsquare: ' + locator +'<br />Distance: ' + distance+ '<br />Bearing: ' + bearing +
	'</div>';

	const multiplelines = [];
	multiplelines.push(
		new L.LatLng(fromCoords[0], fromCoords[1]),
		new L.LatLng(lat, lng)
	)

	if (lng < -170) {
		lng = parseFloat(lng) + 360;
	}

	var marker2 = L.marker([lat, lng], {closeOnClick: false, autoClose: false}).addTo(map);


	clickmarkers.push(marker2);

	marker2.bindTooltip(popupmessage);

	const geodesic = L.geodesic(multiplelines, {
		weight: 3,
		opacity: 1,
		color: 'red',
		wrap: false,
		steps: 100
	}).addTo(map);

	clicklines.push(geodesic);
};

const bearingDistance = (from, to) => {
	const fromCoords = locatorToLatLng(from);
	const toCoords = locatorToLatLng(to);
	const dLat = degToRad(toCoords[0] - fromCoords[0]);
	const dLon = degToRad(toCoords[1] - fromCoords[1]);
	const fromLat = degToRad(fromCoords[0]);
	const toLat = degToRad(toCoords[0]);
	const a = Math.pow(Math.sin(dLat / 2), 2) + Math.pow(Math.sin(dLon / 2), 2) * Math.cos(fromLat) * Math.cos(toLat);
	const b = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

	const y = (dLon) * Math.cos(fromLat) * Math.cos(toLat);
	const x = Math.sin(toLat) - Math.sin(fromLat) * Math.cos(b);

	let az = Math.atan2(y, x);

	if (az < 0) {
		az += 2 * Math.PI;
	}

	return {
		distance: b,
		deg: calcAngle(fromCoords, toCoords)
	};
};

var calcAngle = function (p1, p2) {
	var lat1 = p1[0] / 180 * Math.PI;
	var lat2 = p2[0] / 180 * Math.PI;
	var lng1 = p1[1] / 180 * Math.PI;
	var lng2 = p2[1] / 180 * Math.PI;
	var y = Math.sin(lng2-lng1) * Math.cos(lat2);
	var x = Math.cos(lat1)*Math.sin(lat2) - Math.sin(lat1)*Math.cos(lat2)*Math.cos(lng2-lng1);
	var brng = (Math.atan2(y, x) * 180 / Math.PI + 360).toFixed(0);

	return (brng % 360);
}

const locatorToLatLng = (locatorString) => {
	locatorString += 'll'; // append subsquare in case is 4 chars long...  If not, is ignored.
	if (!isValidLocatorString(locatorString)) {
		throw new Error('Input is not valid locator string');
	}

	const fieldLng = charToNumber(locatorString[0]) * 20;
	const fieldLat = charToNumber(locatorString[1]) * 10;
	const squareLng = Number.parseInt(locatorString[2]) * 2;
	const squareLat = Number.parseInt(locatorString[3]);
	const subsquareLng = (charToNumber(locatorString[4]) + 0.5) / 12;
	const subsquareLat = (charToNumber(locatorString[5]) + 0.5) / 24;

	return [
		fieldLat + squareLat + subsquareLat - 90,
		fieldLng + squareLng + subsquareLng - 180
	];
};
