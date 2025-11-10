/**
 * Convert frequency to ham radio band name
 * @param {number} frequency - Frequency value
 * @param {string} unit - Unit of frequency: 'Hz' (default) or 'kHz'
 * @returns {string} Band name (e.g., '20m', '2m', '70cm') or 'All' if not in a known band
 */
function frequencyToBand(frequency, unit = 'Hz') {
	// Convert to Hz if input is in kHz
	const freqHz = (unit.toLowerCase() === 'khz') ? frequency * 1000 : parseInt(frequency);

	// MF/HF Bands
	if (freqHz >= 1800000 && freqHz <= 2000000) return '160m';
	if (freqHz >= 3500000 && freqHz <= 4000000) return '80m';
	if (freqHz >= 5250000 && freqHz <= 5450000) return '60m';
	if (freqHz >= 7000000 && freqHz <= 7300000) return '40m';
	if (freqHz >= 10100000 && freqHz <= 10150000) return '30m';
	if (freqHz >= 14000000 && freqHz <= 14350000) return '20m';
	if (freqHz >= 18068000 && freqHz <= 18168000) return '17m';
	if (freqHz >= 21000000 && freqHz <= 21450000) return '15m';
	if (freqHz >= 24890000 && freqHz <= 24990000) return '12m';
	if (freqHz >= 28000000 && freqHz <= 29700000) return '10m';

	// VHF Bands
	if (freqHz >= 50000000 && freqHz <= 54000000) return '6m';
	if (freqHz >= 70000000 && freqHz <= 71000000) return '4m';
	if (freqHz >= 144000000 && freqHz <= 148000000) return '2m';
	if (freqHz >= 222000000 && freqHz <= 225000000) return '1.25m';

	// UHF Bands
	if (freqHz >= 420000000 && freqHz <= 450000000) return '70cm';
	if (freqHz >= 902000000 && freqHz <= 928000000) return '33cm';
	if (freqHz >= 1240000000 && freqHz <= 1300000000) return '23cm';

	// SHF Bands
	if (freqHz >= 2300000000 && freqHz <= 2450000000) return '13cm';
	if (freqHz >= 3300000000 && freqHz <= 3500000000) return '9cm';
	if (freqHz >= 5650000000 && freqHz <= 5925000000) return '6cm';
	if (freqHz >= 10000000000 && freqHz <= 10500000000) return '3cm';
	if (freqHz >= 24000000000 && freqHz <= 24250000000) return '1.25cm';
	if (freqHz >= 47000000000 && freqHz <= 47200000000) return '6mm';
	if (freqHz >= 75500000000 && freqHz <= 81000000000) return '4mm';
	if (freqHz >= 119980000000 && freqHz <= 120020000000) return '2.5mm';
	if (freqHz >= 142000000000 && freqHz <= 149000000000) return '2mm';
	if (freqHz >= 241000000000 && freqHz <= 250000000000) return '1mm';

	return 'All';
}

/**
 * Alias for backward compatibility - converts frequency in kHz to band name
 * @deprecated Use frequencyToBand(frequency, 'kHz') instead
 * @param {number} freq_khz - Frequency in kilohertz
 * @returns {string} Band name or 'All'
 */
function frequencyToBandKhz(freq_khz) {
	return frequencyToBand(freq_khz, 'kHz');
}

/**
 * Determine appropriate radio mode based on spot mode and frequency
 * @param {string} spotMode - Mode from DX spot (e.g., 'CW', 'SSB', 'FT8')
 * @param {number} freqHz - Frequency in Hz
 * @returns {string} Radio mode (CW, USB, LSB, RTTY, AM, FM)
 */
function determineRadioMode(spotMode, freqHz) {
	if (!spotMode) {
		// No mode specified - use frequency to determine USB/LSB
		return freqHz < 10000000 ? 'LSB' : 'USB'; // Below 10 MHz = LSB, above = USB
	}

	const modeUpper = spotMode.toUpperCase();

	// CW modes
	if (modeUpper === 'CW' || modeUpper === 'A1A') {
		return 'CW';
	}

	// Digital modes - use RTTY as standard digital mode
	const digitalModes = ['FT8', 'FT4', 'PSK', 'RTTY', 'JT65', 'JT9', 'WSPR', 'FSK', 'MFSK', 'OLIVIA', 'CONTESTI', 'DOMINO'];
	for (let i = 0; i < digitalModes.length; i++) {
		if (modeUpper.indexOf(digitalModes[i]) !== -1) {
			return 'RTTY';
		}
	}

	// Phone modes or SSB - determine USB/LSB based on frequency
	if (modeUpper.indexOf('SSB') !== -1 || modeUpper.indexOf('PHONE') !== -1 ||
	    modeUpper === 'USB' || modeUpper === 'LSB' || modeUpper === 'AM' || modeUpper === 'FM') {
		// If already USB or LSB, use as-is
		if (modeUpper === 'USB') return 'USB';
		if (modeUpper === 'LSB') return 'LSB';
		if (modeUpper === 'AM') return 'AM';
		if (modeUpper === 'FM') return 'FM';

		// Otherwise determine based on frequency
		return freqHz < 10000000 ? 'LSB' : 'USB';
	}

	// Default: use frequency to determine USB/LSB
	return freqHz < 10000000 ? 'LSB' : 'USB';
}

/**
 * Ham radio band groupings by frequency range
 * MF = Medium Frequency (300 kHz - 3 MHz) - 160m
 * HF = High Frequency (3-30 MHz) - 80m through 10m
 * VHF = Very High Frequency (30-300 MHz) - 6m through 1.25m
 * UHF = Ultra High Frequency (300 MHz-3 GHz) - 70cm through 23cm
 * SHF = Super High Frequency (3-30 GHz) - 13cm and above
 */
const BAND_GROUPS = {
	'MF': ['160m'],
	'HF': ['80m', '60m', '40m', '30m', '20m', '17m', '15m', '12m', '10m'],
	'VHF': ['6m', '4m', '2m', '1.25m'],
	'UHF': ['70cm', '33cm', '23cm'],
	'SHF': ['13cm', '9cm', '6cm', '3cm', '1.25cm', '6mm', '4mm', '2.5mm', '2mm', '1mm']
};

/**
 * Map individual band to its band group (MF, HF, VHF, UHF, SHF)
 * @param {string} band - Band identifier (e.g., '20m', '2m', '70cm', '13cm')
 * @returns {string|null} Band group name or null if band not found
 */
function getBandGroup(band) {
	for (const [group, bands] of Object.entries(BAND_GROUPS)) {
		if (bands.includes(band)) return group;
	}
	return null;
}

/**
 * Get all bands in a band group
 * @param {string} group - Band group name (MF, HF, VHF, UHF, or SHF)
 * @returns {Array} Array of band identifiers or empty array if group not found
 */
function getBandsInGroup(group) {
	return BAND_GROUPS[group] || [];
}

/**
 * Categorize amateur radio mode into phone/cw/digi for filtering
 * @param {string} mode - Mode name (e.g., 'USB', 'CW', 'FT8', 'phone')
 * @returns {string|null} Mode category: 'phone', 'cw', 'digi', or null if unknown
 */
function getModeCategory(mode) {
	if (!mode) return null;

	const modeLower = mode.toLowerCase();

	// Check if already a category
	if (['phone', 'cw', 'digi'].includes(modeLower)) {
		return modeLower;
	}

	const modeUpper = mode.toUpperCase();

	// CW modes
	if (['CW', 'CWR', 'A1A'].includes(modeUpper) || modeLower.includes('cw')) {
		return 'cw';
	}

	// Phone modes (voice)
	if (['SSB', 'LSB', 'USB', 'FM', 'AM', 'DV', 'PHONE', 'C3E', 'J3E'].includes(modeUpper)) {
		return 'phone';
	}

	// Digital modes
	const digitalModes = ['RTTY', 'PSK', 'PSK31', 'PSK63', 'FT8', 'FT4', 'JT65', 'JT9', 'MFSK',
	                      'OLIVIA', 'CONTESTIA', 'HELL', 'THROB', 'SSTV', 'FAX', 'PACKET', 'PACTOR',
	                      'THOR', 'DOMINO', 'MT63', 'ROS', 'WSPR', 'VARA', 'ARDOP', 'WINMOR'];
	if (digitalModes.includes(modeUpper)) {
		return 'digi';
	}

	// Check for digital mode substrings
	if (modeLower.includes('ft') || modeLower.includes('psk') || modeLower.includes('rtty') ||
	    modeLower.includes('jt') || modeLower === 'digi' || modeLower === 'data') {
		return 'digi';
	}

	return null;
}

function catmode(mode) {
	switch ((mode || '').toUpperCase()) {
		case 'CW-U':
		case 'CW-L':
		case 'CW-R':
		case 'CWU':
		case 'CWL':
			return 'CW';
			break;
		case 'RTTY-L':
		case 'RTTY-U':
		case 'RTTY-R':
			return 'RTTY';
			break;
		case 'USB-D':
		case 'USB-D1':
			return 'USB';
			break;
		case 'LSB-D':
		case 'LSB-D1':
			return 'LSB';
			break;
		default:
			return (mode || '');;
			break;
	}
}

function LatLng2Loc(y, x, num) {
	if (x<-180) {x=x+360;}
	if (x>180) {x=x-360;}
	var yqth, yi, yk, ydiv, yres, ylp, y;
    var ycalc = new Array(0,0,0);
    var yn    = new Array(0,0,0,0,0,0,0);

    var ydiv_arr=new Array(10, 1, 1/24, 1/240, 1/240/24);
    ycalc[0] = (x + 180)/2;
    ycalc[1] =  y +  90;

    for (yi = 0; yi < 2; yi++) {
	for (yk = 0; yk < 5; yk++) {
	    ydiv = ydiv_arr[yk];
	    yres = ycalc[yi] / ydiv;
	    ycalc[yi] = yres;
	    if (ycalc[yi] > 0) ylp = Math.floor(yres); else ylp = Math.ceil(yres);
	    ycalc[yi] = (ycalc[yi] - ylp) * ydiv;
	    yn[2*yk + yi] = ylp;
	}
    }

    var qthloc="";
    if (num >= 2) qthloc+=String.fromCharCode(yn[0] + 0x41) + String.fromCharCode(yn[1] + 0x41);
    if (num >= 4) qthloc+=String.fromCharCode(yn[2] + 0x30) + String.fromCharCode(yn[3] + 0x30);
    if (num >= 6) qthloc+=String.fromCharCode(yn[4] + 0x41) + String.fromCharCode(yn[5] + 0x41);
    if (num >= 8) qthloc+=' ' + String.fromCharCode(yn[6] + 0x30) + String.fromCharCode(yn[7] + 0x30);
    if (num >= 10) qthloc+=String.fromCharCode(yn[8] + 0x61) + String.fromCharCode(yn[9] + 0x61);
	return qthloc;

}
