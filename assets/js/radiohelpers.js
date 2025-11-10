/**
 * Radio Frequency and Mode Utilities
 * Global helper functions for frequency conversion, band/mode determination, and radio control
 */

// ========================================
// CONSTANTS
// ========================================

/**
 * LSB/USB transition threshold
 * Below 10 MHz = LSB, above = USB for phone modes
 * @constant {number}
 */
const LSB_USB_THRESHOLD_KHZ = 10000; // 10 MHz in kHz

/**
 * Static FT8 calling frequencies (in kHz)
 * Exported globally for use across modules
 * @constant {Array<number>}
 */
const FT8_FREQUENCIES = [1840, 3573, 7074, 10136, 14074, 18100, 21074, 24915, 28074, 50313, 144174, 432065];
window.FT8_FREQUENCIES = FT8_FREQUENCIES; // Export globally

/**
 * Mode classification lists
 * Comprehensive list of radio modes organized by category
 * Only includes commonly used modes seen on DX clusters and amateur radio
 * @constant {Object}
 */
const MODE_LISTS = {
	PHONE: ['SSB', 'LSB', 'USB', 'AM', 'FM', 'SAM', 'DSB', 'J3E', 'A3E', 'PHONE'],
	WSJT: ['FT8', 'FT4', 'JT65', 'JT65B', 'JT6C', 'JT6M', 'JT9', 'JT9-1',
		   'Q65', 'QRA64', 'FST4', 'FST4W', 'WSPR', 'MSK144', 'ISCAT',
		   'ISCAT-A', 'ISCAT-B', 'JS8', 'JTMS', 'FSK441', 'JT4', 'OPERA'],
	DIGITAL_OTHER: ['RTTY', 'NAVTEX', 'SITORB', 'DIGI', 'DYNAMIC', 'RTTYFSK', 'RTTYM'],
	PSK: ['PSK', 'QPSK', '8PSK', 'PSK31', 'PSK63', 'PSK125', 'PSK250'],
	DIGITAL_MODES: ['OLIVIA', 'CONTESTIA', 'THOR', 'THROB', 'MFSK', 'MFSK8', 'MFSK16',
					'HELL', 'MT63', 'DOMINO', 'PACKET', 'PACTOR', 'CLOVER', 'AMTOR',
					'SITOR', 'SSTV', 'FAX', 'CHIP', 'CHIP64', 'ROS'],
	DIGITAL_VOICE: ['DIGITALVOICE', 'DSTAR', 'C4FM', 'DMR', 'FREEDV', 'M17'],
	DIGITAL_HF: ['VARA', 'ARDOP'],
	CW: ['CW', 'A1A']
};

/**
 * Available continents for cycling
 * Standard continent codes used in amateur radio
 * Exported globally for use across modules
 * @constant {Array<string>}
 */
const CONTINENTS = ['AF', 'AN', 'AS', 'EU', 'NA', 'OC', 'SA'];
window.CONTINENTS = CONTINENTS; // Export globally

/**
 * Signal bandwidth constants (in kHz)
 * Covers all modes from MODE_LISTS for comprehensive bandwidth determination
 * @constant {Object}
 */
const SIGNAL_BANDWIDTHS = {
	// Phone modes (voice)
	SSB: 2.7,
	LSB: 2.7,
	USB: 2.7,
	AM: 6.0,
	FM: 12.0,
	SAM: 6.0,
	DSB: 6.0,
	J3E: 2.7,
	A3E: 6.0,
	PHONE: 2.7,

	// CW modes
	CW: 0.25,
	A1A: 0.25,

	// WSJT-X family (weak signal digital)
	FT8: 3.0,
	FT4: 3.0,
	JT65: 2.7,
	JT65B: 2.7,
	JT6C: 2.7,
	JT6M: 2.7,
	JT9: 0.5,
	'JT9-1': 0.5,
	Q65: 2.7,
	QRA64: 2.7,
	FST4: 3.0,
	FST4W: 3.0,
	WSPR: 0.006,
	MSK144: 2.4,
	ISCAT: 2.0,
	'ISCAT-A': 2.0,
	'ISCAT-B': 2.0,
	JS8: 0.05,
	JTMS: 2.0,
	FSK441: 4.4,
	JT4: 2.7,
	OPERA: 0.5,

	// PSK variants
	PSK: 0.5,
	QPSK: 0.5,
	'8PSK': 0.5,
	PSK31: 0.062,
	PSK63: 0.125,
	PSK125: 0.25,
	PSK250: 0.5,

	// RTTY and related
	RTTY: 0.5,
	NAVTEX: 0.3,
	SITORB: 0.5,
	DIGI: 2.5,
	DYNAMIC: 2.5,
	RTTYFSK: 0.5,
	RTTYM: 0.5,

	// Other digital modes
	OLIVIA: 2.5,
	CONTESTIA: 2.5,
	THOR: 2.3,
	THROB: 2.2,
	MFSK: 2.5,
	MFSK8: 0.316,
	MFSK16: 0.316,
	HELL: 2.5,
	MT63: 2.5,
	DOMINO: 0.172,
	PACKET: 2.5,
	PACTOR: 2.4,
	CLOVER: 2.5,
	AMTOR: 0.5,
	SITOR: 0.5,
	SSTV: 2.7,
	FAX: 2.3,
	CHIP: 2.5,
	CHIP64: 2.5,
	ROS: 2.5,

	// Digital voice
	DIGITALVOICE: 6.25,
	DSTAR: 6.25,
	C4FM: 6.25,
	DMR: 6.25,
	FREEDV: 1.25,
	M17: 9.0,

	// Digital HF modes
	VARA: 2.5,
	ARDOP: 2.5
};

/**
 * Radio band groupings by frequency range
 * MF = Medium Frequency (300 kHz - 3 MHz) - 160m
 * HF = High Frequency (3-30 MHz) - 80m through 10m
 * VHF = Very High Frequency (30-300 MHz) - 6m through 1.25m
 * UHF = Ultra High Frequency (300 MHz-3 GHz) - 70cm through 23cm
 * SHF = Super High Frequency (3-30 GHz) - 13cm and above
 * @constant {Object}
 */
const BAND_GROUPS = {
	'MF': ['160m'],
	'HF': ['80m', '60m', '40m', '30m', '20m', '17m', '15m', '12m', '10m'],
	'VHF': ['6m', '4m', '2m', '1.25m'],
	'UHF': ['70cm', '33cm', '23cm'],
	'SHF': ['13cm', '9cm', '6cm', '3cm', '1.25cm', '6mm', '4mm', '2.5mm', '2mm', '1mm']
};

// ========================================
// FREQUENCY CONVERSION & BAND UTILITIES
// ========================================

/**
 * Check if a mode is in any of the MODE_LISTS categories
 * @param {string} mode - Mode to check (case-insensitive)
 * @param {string} category - Category key from MODE_LISTS ('CW', 'PHONE', 'WSJT', etc.)
 * @returns {boolean} True if mode is in the category
 */
function isModeInCategory(mode, category) {
	if (!mode || !MODE_LISTS[category]) return false;
	const modeUpper = mode.toUpperCase();
	return MODE_LISTS[category].indexOf(modeUpper) !== -1;
}

/**
 * Check if mode matches any mode in a category (substring match)
 * @param {string} mode - Mode to check
 * @param {string} category - Category key from MODE_LISTS
 * @returns {boolean} True if mode contains any mode from category
 */
function isModeInCategoryPartial(mode, category) {
	if (!mode || !MODE_LISTS[category]) return false;
	const modeUpper = mode.toUpperCase();
	for (let i = 0; i < MODE_LISTS[category].length; i++) {
		if (modeUpper.indexOf(MODE_LISTS[category][i]) !== -1) {
			return true;
		}
	}
	return false;
}

/**
 * Check if mode is in any digital category
 * @param {string} mode - Mode to check
 * @returns {boolean} True if mode is in any digital category
 */
function isDigitalCategory(mode) {
	return isModeInCategory(mode, 'WSJT') ||
		   isModeInCategory(mode, 'DIGITAL_OTHER') ||
		   isModeInCategory(mode, 'PSK') ||
		   isModeInCategory(mode, 'DIGITAL_MODES') ||
		   isModeInCategory(mode, 'DIGITAL_HF');
}

/**
 * Convert frequency to ham radio band name
 * @param {number} frequency - Frequency value
 * @param {string} unit - Unit of frequency: 'Hz' (default) or 'kHz'
 * @param {number} marginKhz - Optional margin in kHz to extend band edges (default: 0)
 * @returns {string} Band name (e.g., '20m', '2m', '70cm') or 'All' if not in a known band
 */
function frequencyToBand(frequency, unit = 'Hz', marginKhz = 0) {
	// Convert to Hz if input is in kHz
	const freqHz = (unit.toLowerCase() === 'khz') ? frequency * 1000 : parseInt(frequency);

	// Convert margin to Hz (ensure non-negative)
	const marginHz = Math.max(0, marginKhz) * 1000;

	// MF/HF Bands (with margin)
	if (freqHz >= (1800000 - marginHz) && freqHz <= (2000000 + marginHz)) return '160m';
	if (freqHz >= (3500000 - marginHz) && freqHz <= (4000000 + marginHz)) return '80m';
	if (freqHz >= (5250000 - marginHz) && freqHz <= (5450000 + marginHz)) return '60m';
	if (freqHz >= (7000000 - marginHz) && freqHz <= (7300000 + marginHz)) return '40m';
	if (freqHz >= (10100000 - marginHz) && freqHz <= (10150000 + marginHz)) return '30m';
	if (freqHz >= (14000000 - marginHz) && freqHz <= (14350000 + marginHz)) return '20m';
	if (freqHz >= (18068000 - marginHz) && freqHz <= (18168000 + marginHz)) return '17m';
	if (freqHz >= (21000000 - marginHz) && freqHz <= (21450000 + marginHz)) return '15m';
	if (freqHz >= (24890000 - marginHz) && freqHz <= (24990000 + marginHz)) return '12m';
	if (freqHz >= (28000000 - marginHz) && freqHz <= (29700000 + marginHz)) return '10m';

	// VHF Bands (with margin)
	if (freqHz >= (50000000 - marginHz) && freqHz <= (54000000 + marginHz)) return '6m';
	if (freqHz >= (70000000 - marginHz) && freqHz <= (71000000 + marginHz)) return '4m';
	if (freqHz >= (144000000 - marginHz) && freqHz <= (148000000 + marginHz)) return '2m';
	if (freqHz >= (222000000 - marginHz) && freqHz <= (225000000 + marginHz)) return '1.25m';

	// UHF Bands (with margin)
	if (freqHz >= (420000000 - marginHz) && freqHz <= (450000000 + marginHz)) return '70cm';
	if (freqHz >= (902000000 - marginHz) && freqHz <= (928000000 + marginHz)) return '33cm';
	if (freqHz >= (1240000000 - marginHz) && freqHz <= (1300000000 + marginHz)) return '23cm';

	// SHF Bands (with margin)
	if (freqHz >= (2300000000 - marginHz) && freqHz <= (2450000000 + marginHz)) return '13cm';
	if (freqHz >= (3300000000 - marginHz) && freqHz <= (3500000000 + marginHz)) return '9cm';
	if (freqHz >= (5650000000 - marginHz) && freqHz <= (5925000000 + marginHz)) return '6cm';
	if (freqHz >= (10000000000 - marginHz) && freqHz <= (10500000000 + marginHz)) return '3cm';
	if (freqHz >= (24000000000 - marginHz) && freqHz <= (24250000000 + marginHz)) return '1.25cm';
	if (freqHz >= (47000000000 - marginHz) && freqHz <= (47200000000 + marginHz)) return '6mm';
	if (freqHz >= (75500000000 - marginHz) && freqHz <= (81000000000 + marginHz)) return '4mm';
	if (freqHz >= (119980000000 - marginHz) && freqHz <= (120020000000 + marginHz)) return '2.5mm';
	if (freqHz >= (142000000000 - marginHz) && freqHz <= (149000000000 + marginHz)) return '2mm';
	if (freqHz >= (241000000000 - marginHz) && freqHz <= (250000000000 + marginHz)) return '1mm';

	return 'All';
}

/**
 * Alias for backward compatibility - converts frequency in kHz to band name
 * @deprecated Use frequencyToBand(frequency, 'kHz') instead
 * @param {number} freq_khz - Frequency in kilohertz
 * @param {number} marginKhz - Optional margin in kHz to extend band edges (default: 0)
 * @returns {string} Band name or 'All'
 */
function frequencyToBandKhz(freq_khz, marginKhz = 0) {
	return frequencyToBand(freq_khz, 'kHz', marginKhz);
}

/**
 * Get a typical (center/common) frequency for a given amateur radio band
 * Returns frequency in kHz - useful for band changes when no specific frequency is needed
 * @param {string} band - Band designation (e.g., '20m', '40m', '2m')
 * @returns {number} Typical frequency in kHz, or 0 if band not recognized
 *
 * @example
 * getTypicalBandFrequency('20m') // → 14100 (kHz)
 * getTypicalBandFrequency('2m')  // → 144300 (kHz)
 */
function getTypicalBandFrequency(band) {
	const frequencies = {
		'160m': 1850,
		'80m': 3550,
		'60m': 5357,
		'40m': 7050,
		'30m': 10120,
		'20m': 14100,
		'17m': 18100,
		'15m': 21100,
		'12m': 24920,
		'10m': 28400,
		'6m': 50100,
		'4m': 70100,
		'2m': 144300,
		'1.25m': 222100,
		'70cm': 432100,
		'33cm': 902100,
		'23cm': 1296100,
		'13cm': 2304100,
		'9cm': 3456100,
		'6cm': 5760100,
		'3cm': 10368100,
		'1.25cm': 24048100
	};

	return frequencies[band] || 0;
}

/**
 * Determine appropriate radio mode based on spot mode and frequency
 * Uses MODE_LISTS to intelligently map any amateur radio mode to a standard CAT mode
 * @param {string} spotMode - Mode from DX spot (e.g., 'CW', 'SSB', 'FT8')
 * @param {number} freqHz - Frequency in Hz
 * @returns {string} Radio mode (CW, USB, LSB, RTTY, AM, FM, DIGI)
 */
function determineRadioMode(spotMode, freqHz) {
	if (!spotMode) {
		// No mode specified - use frequency to determine USB/LSB
		return (freqHz / 1000) < LSB_USB_THRESHOLD_KHZ ? 'LSB' : 'USB';
	}

	const modeUpper = spotMode.toUpperCase();

	// CW modes - return CW
	if (isModeInCategory(spotMode, 'CW')) {
		return 'CW';
	}

	// Phone modes - determine specific sideband/voice mode
	if (isModeInCategory(spotMode, 'PHONE')) {
		// Check if it's a specific CAT mode that should be preserved
		// USB, LSB, AM, FM are actual CAT modes, not generic SSB/PHONE
		if (modeUpper === 'USB' || modeUpper === 'LSB' || modeUpper === 'AM' || modeUpper === 'FM') {
			return modeUpper;
		}

		// For generic SSB/PHONE, determine USB/LSB based on frequency
		return (freqHz / 1000) < LSB_USB_THRESHOLD_KHZ ? 'LSB' : 'USB';
	}

	// Digital voice modes - use FM as closest analog
	if (isModeInCategory(spotMode, 'DIGITAL_VOICE')) {
		return 'FM';
	}

	// All other digital modes - check if radio supports specific mode, otherwise use RTTY/DIGI
	// WSJT-X, PSK, RTTY, and other digital modes typically use RTTY or DIGI mode on the radio
	if (isDigitalCategory(spotMode)) {
		// Some radios support specific digital modes, check for them
		if (modeUpper === 'RTTY') return 'RTTY';
		if (modeUpper === 'PSK') return 'PSK';
		if (modeUpper === 'PKTUSB' || modeUpper === 'PKTLSB') return modeUpper;

		// Default to RTTY for most digital modes (most common CAT mode for digital)
		return 'RTTY';
	}

	// Unknown mode - default to USB/LSB based on frequency
	return (freqHz / 1000) < LSB_USB_THRESHOLD_KHZ ? 'LSB' : 'USB';
}

/**
 * Determine LSB or USB based on frequency (for phone modes)
 * @param {number} frequency - Frequency in kHz
 * @returns {string} 'LSB', 'USB', or 'SSB' (fallback if frequency invalid)
 *
 * @example
 * determineSSBMode(7100)  // → 'LSB' (below 10 MHz)
 * determineSSBMode(14200) // → 'USB' (above 10 MHz)
 */
function determineSSBMode(frequency) {
	var freq = parseFloat(frequency) || 0;
	if (freq > 0) {
		return freq < LSB_USB_THRESHOLD_KHZ ? 'LSB' : 'USB';
	}
	return 'SSB';
}

// ========================================
// BAND GROUP UTILITIES
// ========================================

/**
 * Map individual band to its band group (MF, HF, VHF, UHF, SHF)
 * @param {string} band - Band identifier (e.g., '20m', '2m', '70cm', '13cm')
 * @returns {string|null} Band group name or null if band not found
 */
function 
(band) {
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

// ========================================
// MODE CATEGORIZATION & CAT UTILITIES
// ========================================

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

	// CW modes - use MODE_LISTS.CW
	if (isModeInCategory(mode, 'CW')) {
		return 'cw';
	}

	// Phone modes - use MODE_LISTS.PHONE
	if (isModeInCategory(mode, 'PHONE')) {
		return 'phone';
	}

	// Digital modes - check all digital categories from MODE_LISTS
	if (isDigitalCategory(mode) || isModeInCategory(mode, 'DIGITAL_VOICE')) {
		return 'digi';
	}

	// Fallback for generic digital mode strings
	if (modeLower === 'digi' || modeLower === 'data') {
		return 'digi';
	}

	return null;
}

/**
 * Normalize CAT (Computer Aided Transceiver) mode names to standard modes
 * Strips radio-specific suffixes and variations to return canonical mode names
 * @param {string} mode - CAT mode string from radio (e.g., 'CW-U', 'USB-D1', 'RTTY-R')
 * @returns {string} Normalized mode name (e.g., 'CW', 'USB', 'RTTY')
 *
 * @example
 * catmode('CW-U')    // → 'CW'
 * catmode('USB-D1')  // → 'USB'
 * catmode('RTTY-R')  // → 'RTTY'
 */
function catmode(mode) {
	switch ((mode || '').toUpperCase()) {
		case 'CW-U':
		case 'CW-L':
		case 'CW-R':
		case 'CWU':
		case 'CWL':
			return 'CW';
		case 'RTTY-L':
		case 'RTTY-U':
		case 'RTTY-R':
			return 'RTTY';
		case 'USB-D':
		case 'USB-D1':
			return 'USB';
		case 'LSB-D':
		case 'LSB-D1':
			return 'LSB';
		default:
			return (mode || '');
	}
}

/**
 * Frequency conversion utility
 * Convert between Hz, kHz, and MHz
 *
 * @param {number} value - Frequency value
 * @param {string} fromUnit - Source unit: 'Hz', 'kHz', or 'MHz' (case-insensitive)
 * @param {string} [toUnit='kHz'] - Target unit: 'Hz', 'kHz', or 'MHz' (default: 'kHz')
 * @returns {number} Converted frequency
 *
 * @example
 * convertFrequency(14074000, 'Hz', 'kHz')  // → 14074
 * convertFrequency(14.074, 'MHz', 'Hz')    // → 14074000
 * convertFrequency(7074, 'kHz', 'MHz')     // → 7.074
 * convertFrequency(14074, 'kHz')           // → 14074 (defaults to kHz)
 */
function convertFrequency(value, fromUnit, toUnit) {
	var freqValue = parseFloat(value) || 0;
	toUnit = toUnit || 'kHz'; // Default target is kHz

	// Normalize units to lowercase
	var from = (fromUnit || 'Hz').toLowerCase();
	var to = toUnit.toLowerCase();

	// If units are the same, no conversion needed
	if (from === to) return freqValue;

	// Convert to Hz first (base unit)
	var freqHz;
	switch (from) {
		case 'hz': freqHz = freqValue; break;
		case 'khz': freqHz = freqValue * 1000; break;
		case 'mhz': freqHz = freqValue * 1000000; break;
		default: freqHz = freqValue; // Assume Hz if unknown
	}

	// Convert from Hz to target unit
	switch (to) {
		case 'hz': return freqHz;
		case 'khz': return freqHz / 1000;
		case 'mhz': return freqHz / 1000000;
		default: return freqHz / 1000; // Default to kHz
	}
}

/**
 * Legacy wrapper for backward compatibility
 * @deprecated Use convertFrequency(value, fromUnit, 'kHz') instead
 */
function convertToKhz(value, unit) {
	return convertFrequency(value, unit || 'Hz', 'kHz');
}

/**
 * Compare two frequencies with tolerance for floating point precision
 * @param {number} freq1 - First frequency
 * @param {number} freq2 - Second frequency
 * @param {string} unit - Unit: 'Hz', 'kHz', or 'MHz' (default: 'kHz')
 * @param {number} [tolerance] - Tolerance (default: 0.001 kHz = 1 Hz)
 * @returns {boolean} True if frequencies are equal within tolerance
 */
function areFrequenciesEqual(freq1, freq2, unit, tolerance) {
	unit = unit || 'kHz';

	// Convert both to kHz for comparison
	var freq1Khz = convertFrequency(freq1, unit, 'kHz');
	var freq2Khz = convertFrequency(freq2, unit, 'kHz');

	// Default tolerance: 1 Hz = 0.001 kHz
	tolerance = tolerance !== undefined ? tolerance : 0.001;

	return Math.abs(freq1Khz - freq2Khz) <= tolerance;
}

/**
 * Check if a frequency is an FT8 calling frequency (within 5 kHz tolerance)
 * @param {number} frequency - Frequency value
 * @param {string} [unit='kHz'] - Unit: 'Hz', 'kHz', or 'MHz'
 * @returns {boolean} True if frequency is an FT8 calling frequency
 */
function isFT8Frequency(frequency, unit) {
	var freqKhz = convertFrequency(frequency, unit || 'kHz', 'kHz');
	for (var i = 0; i < FT8_FREQUENCIES.length; i++) {
		if (Math.abs(freqKhz - FT8_FREQUENCIES[i]) < 5) return true;
	}
	return false;
}

// ========================================
// SIGNAL BANDWIDTH UTILITIES
// ========================================

/**
 * Get signal bandwidth for a radio mode
 * @param {string} mode - Radio mode (e.g., 'USB', 'CW', 'FT8', 'AM')
 * @returns {number} Bandwidth in kHz
 */
function getSignalBandwidth(mode) {
	if (!mode) return SIGNAL_BANDWIDTHS.SSB;

	var modeUpper = mode.toUpperCase();

	// Check exact matches first
	if (SIGNAL_BANDWIDTHS[modeUpper]) return SIGNAL_BANDWIDTHS[modeUpper];

	// Fallback for substring matches (e.g., mode variations not in exact list)
	if (modeUpper.indexOf('CW') !== -1) return SIGNAL_BANDWIDTHS.CW;
	if (modeUpper.indexOf('RTTY') !== -1) return SIGNAL_BANDWIDTHS.RTTY;
	if (modeUpper.indexOf('PSK') !== -1) return SIGNAL_BANDWIDTHS.PSK;

	// Default to SSB bandwidth for phone modes
	return SIGNAL_BANDWIDTHS.SSB;
}

// ========================================
// MODE CLASSIFICATION FOR DX SPOTS
// ========================================

/**
 * Check if mode is CW
 * @param {string} mode - Radio mode
 * @returns {boolean} True if mode is CW
 */
function isCwMode(mode) {
	return isModeInCategory(mode, 'CW') || (mode && mode.toLowerCase().includes('cw'));
}

/**
 * Check if mode is phone/voice
 * @param {string} mode - Radio mode
 * @returns {boolean} True if mode is phone/voice
 */
function isPhoneMode(mode) {
	if (!mode) return false;
	return isModeInCategory(mode, 'PHONE') || mode.toLowerCase() === 'phone';
}

/**
 * Check if mode is digital
 * @param {string} mode - Radio mode
 * @returns {boolean} True if mode is digital
 */
function isDigiMode(mode) {
	if (!mode) return false;
	// Check all digital categories from MODE_LISTS
	return isDigitalCategory(mode) ||
		   isModeInCategory(mode, 'DIGITAL_VOICE') ||
		   mode.toLowerCase() === 'digi' ||
		   mode.toLowerCase() === 'data';
}

/**
 * Comprehensive mode classification system
 * Classifies a DX spot into phone, CW, digi, or other categories
 *
 * @param {Object} spot - DX spot object with mode and optional message fields
 * @param {string} spot.mode - The transmission mode
 * @param {string} [spot.message] - Optional spot comment/message for additional classification hints
 * @returns {{category: string, submode: string, confidence: number}} Classification result
 *          - category: 'phone', 'cw', 'digi', or 'other'
 *          - submode: Specific mode name (e.g., 'FT8', 'USB', 'CW')
 *          - confidence: 0-1, where 1 is high confidence, 0.3 is low
 */
function classifyMode(spot) {
	if (!spot || !spot.mode || spot.mode === '') {
		return { category: 'phone', submode: 'SSB', confidence: 0 };
	}

	var mode = spot.mode.toUpperCase();
	var message = (spot.message || '').toUpperCase();

	// Check message first for higher accuracy
	var messageResult = classifyFromMessage(message);
	if (messageResult.category) {
		return {
			category: messageResult.category,
			submode: messageResult.submode,
			confidence: messageResult.confidence
		};
	}

	// Fall back to mode field classification
	return classifyFromMode(mode);
}

/**
 * Classify mode from spot message text
 * @param {string} message - Spot message/comment
 * @returns {{category: string|null, submode: string|null, confidence: number}}
 */
function classifyFromMessage(message) {
	if (!message) return { category: null, submode: null, confidence: 0 };

	// Check CW modes from MODE_LISTS.CW
	for (var i = 0; i < MODE_LISTS.CW.length; i++) {
		if (message.indexOf(MODE_LISTS.CW[i]) !== -1) {
			return { category: 'cw', submode: MODE_LISTS.CW[i], confidence: 1 };
		}
	}

	// Check all digital mode categories
	var digitalCategories = ['WSJT', 'PSK', 'DIGITAL_OTHER', 'DIGITAL_MODES', 'DIGITAL_VOICE', 'DIGITAL_HF'];
	for (var cat = 0; cat < digitalCategories.length; cat++) {
		var categoryName = digitalCategories[cat];
		var modes = MODE_LISTS[categoryName];
		for (var i = 0; i < modes.length; i++) {
			if (message.indexOf(modes[i]) !== -1) {
				return { category: 'digi', submode: modes[i], confidence: 1 };
			}
		}
	}

	// Check phone modes from MODE_LISTS.PHONE (with word boundaries for accuracy)
	for (var i = 0; i < MODE_LISTS.PHONE.length; i++) {
		var pattern = '\\b' + MODE_LISTS.PHONE[i] + '\\b';
		if (new RegExp(pattern).test(message)) {
			return { category: 'phone', submode: MODE_LISTS.PHONE[i], confidence: 1 };
		}
	}

	return { category: null, submode: null, confidence: 0 };
}

/**
 * Classify mode from mode field
 * @param {string} mode - Mode string from spot
 * @returns {{category: string, submode: string, confidence: number}}
 */
function classifyFromMode(mode) {
	// CW modes - use MODE_LISTS.CW
	if (isModeInCategory(mode, 'CW')) {
		return { category: 'cw', submode: 'CW', confidence: 1 };
	}

	// Phone modes - use MODE_LISTS.PHONE
	if (isModeInCategory(mode, 'PHONE')) {
		return { category: 'phone', submode: mode, confidence: 1 };
	}

	// Digital modes - check all digital categories from MODE_LISTS
	if (isDigitalCategory(mode) || isModeInCategory(mode, 'DIGITAL_VOICE')) {
		return { category: 'digi', submode: mode, confidence: 1 };
	}

	// Unknown mode - default to phone/SSB
	return { category: 'phone', submode: mode || 'SSB', confidence: 0.3 };
}

/**
 * Compare two frequencies with tolerance
 * Optimized version for general frequency comparison
 * @param {number} freq1 - First frequency in kHz
 * @param {number} freq2 - Second frequency in kHz
 * @param {number} [tolerance=0.001] - Tolerance in kHz (default: 1 Hz)
 * @returns {boolean} - True if frequencies are equal within tolerance
 */
function areFrequenciesEqualSimple(freq1, freq2, tolerance) {
	tolerance = tolerance !== undefined ? tolerance : 0.001; // Default 1 Hz
	return Math.abs(freq1 - freq2) <= tolerance;
}

// ========================================
// GEOGRAPHIC UTILITIES
// ========================================

/**
 * Map continent code to IARU region number
 * @param {string} continent - Two-letter continent code (EU, AF, NA, SA, AS, OC, AN)
 * @returns {number} IARU region number (1, 2, or 3)
 *
 * IARU Region 1: Europe, Africa, Middle East, Northern Asia
 * IARU Region 2: Americas (North, Central, South)
 * IARU Region 3: Asia-Pacific (Southern Asia, Oceania)
 *
 * @example
 * continentToRegion('EU') // → 1 (Europe = Region 1)
 * continentToRegion('NA') // → 2 (North America = Region 2)
 * continentToRegion('AS') // → 3 (Asia = Region 3)
 */
function continentToRegion(continent) {
	switch(continent) {
		case 'EU': // Europe
		case 'AF': // Africa
			return 1; // IARU Region 1
		case 'NA': // North America
		case 'SA': // South America
			return 2; // IARU Region 2
		case 'AS': // Asia
		case 'OC': // Oceania
			return 3; // IARU Region 3
		case 'AN': // Antarctica
			return 1; // Default to Region 1 for Antarctica
		default:
			return 1; // Default to Region 1 if unknown
	}
}

/**
 * Convert latitude/longitude coordinates to Maidenhead grid square locator
 * @param {number} y - Latitude in decimal degrees (-90 to +90)
 * @param {number} x - Longitude in decimal degrees (-180 to +180)
 * @param {number} num - Precision level: 2=field, 4=square, 6=subsquare, 8=extended, 10=extended subsquare
 * @returns {string} Maidenhead locator string (e.g., 'JO01ab' for 6-character precision)
 *
 * @example
 * LatLng2Loc(51.5074, -0.1278, 6)  // → 'IO91wm' (London)
 * LatLng2Loc(40.7128, -74.0060, 4) // → 'FN20' (New York)
 */
function LatLng2Loc(y, x, num) {
	if (x<-180) {x=x+360;}
	if (x>180) {x=x-360;}
	var yi, yk, ydiv, yres, ylp, y;
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
