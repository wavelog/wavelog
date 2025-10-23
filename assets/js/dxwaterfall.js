// ========================================
// DX WATERFALL for WaveLog
// Check at https://www.wavelog.org
// ========================================

// ========================================
// CONSTANTS AND CONFIGURATION
// ========================================

var DX_WATERFALL_CONSTANTS = {
    // Timing and debouncing
    DEBOUNCE: {
        SPOT_COLLECTION_MS: 1000,         	// Minimum time between spot collections
        FREQUENCY_CACHE_REFRESH_MS: 200,  	// Throttle for frequency cache refresh
        ZOOM_CHANGE_MS: 100,                // Prevent rapid zoom changes
        DX_SPOTS_FETCH_INTERVAL_MS: 60000, 	// DX spots auto-refresh interval (60 seconds)
        FETCH_REQUEST_MS: 500,             	// Minimum time between debounced fetch requests
        MODE_FILTER_CHANGE_MS: 500,        	// Wait time after mode filter toggle
        SET_FREQUENCY_MS: 500,             	// Debounce for setFrequency calls
        PROGRAMMATIC_MODE_RESET_MS: 100,   	// Reset programmatic mode change flag
        FREQUENCY_COMMIT_SHORT_MS: 50,     	// Very short delay for CAT command completion
        FREQUENCY_COMMIT_RETRY_MS: 100,    	// Retry delay for frequency commit
        ICON_FEEDBACK_MS: 200,             	// Visual feedback duration for icon clicks
        ZOOM_ICON_FEEDBACK_MS: 150         	// Visual feedback duration for zoom icons
    },

    // CAT and radio control
    // Note: These values are initialized and will be recalculated based on catPollInterval
    CAT: {
        POLL_INTERVAL_MS: 3000,           	// Default CAT polling interval (can be overridden by config)
        TUNING_FLAG_FALLBACK_MS: 4500,    	// Fallback timeout for tuning flags (1.5x poll interval)
        FREQUENCY_WAIT_TIMEOUT_MS: 6000   	// Initial load wait time for CAT frequency (2x poll interval)
    },

    // Visual timing
    VISUAL: {
        STATIC_NOISE_REFRESH_MS: 100      	// Static noise animation frame rate
    },

    // Canvas dimensions and spacing
    CANVAS: {
        MIN_TEXT_AREA_WIDTH: 100,          	// Minimum width to display text labels
        RULER_HEIGHT: 25,                  	// Height of the frequency ruler at bottom
        TOP_MARGIN: 10,                    	// Top margin for spot labels
        BOTTOM_MARGIN: 10,                 	// Bottom margin above ruler
        SPOT_PADDING: 2,                   	// Padding around spot labels
        SPOT_TICKBOX_SIZE: 4,              	// Size of status tickbox in pixels
        LOGO_OFFSET_Y: 100,                	// Logo vertical offset from center
        TEXT_OFFSET_Y: 40                  	// Text vertical offset from center
    },

    // AJAX configuration
    AJAX: {
        TIMEOUT_MS: 30000                  	// AJAX request timeout (30 seconds)
    },

    // Thresholds and tolerances
    THRESHOLDS: {
        FREQUENCY_COMPARISON: 0.1,         	// Frequency comparison tolerance in kHz
        FT8_FREQUENCY_TOLERANCE: 5,        	// FT8 frequency detection tolerance in kHz
        BAND_CHANGE_THRESHOLD: 1000,       	// kHz outside band before recalculation
        MAJOR_TICK_TOLERANCE: 0.05,        	// Floating point precision for major tick detection
        SPOT_FREQUENCY_MATCH: 0.01         	// Frequency match tolerance for spot navigation (kHz)
    },

    // Zoom levels configuration
    ZOOM: {
        DEFAULT_LEVEL: 3,                 	// Default zoom level
        MAX_LEVEL: 5,                       // Maximum zoom level
        MIN_LEVEL: 1,                       // Minimum zoom level
        // Pixels per kHz for each zoom level
        PIXELS_PER_KHZ: {
            1: 4,   // ±25 kHz view (widest)
            2: 8,   // ±12.5 kHz view
            3: 20,  // ±5 kHz view (default)
            4: 32,  // ±3.125 kHz view
            5: 50   // ±2 kHz view (most zoomed)
        }
    },

    // Colors (using CSS-compatible color strings)
    COLORS: {
        // Background and base
        CANVAS_BORDER: '#000000',
        OVERLAY_BACKGROUND: 'rgba(0, 0, 0, 0.7)',
        OVERLAY_DARK_GREY: 'rgba(64, 64, 64, 0.7)',

        // Frequency ruler
        RULER_BACKGROUND: '#000000bb',
        RULER_LINE: '#888888',
        RULER_TEXT: '#888888',
        INVALID_FREQUENCY_OVERLAY: 'rgba(128, 128, 128, 0.5)',

        // Center marker and bandwidth
        CENTER_MARKER: '#FF0000',
        BANDWIDTH_INDICATOR: 'rgba(255, 255, 0, 0.3)',

        // Messages and text
        MESSAGE_TEXT_WHITE: '#FFFFFF',
        MESSAGE_TEXT_YELLOW: '#FFFF00',
        WATERFALL_LINK: '#888888',

        // Static noise RGB components
        STATIC_NOISE_RGB: {R: 34, G: 34, B: 34}, // Base RGB values for noise generation

        // DX Spots by mode
        SPOT_PHONE: '#00FF00',
        SPOT_CW: '#FFA500',
        SPOT_DIGI: '#0096FF',

        // Band limits
        OUT_OF_BAND: 'rgba(255, 0, 0, 0.2)'
    },

    // Font configurations
    FONTS: {
        RULER: '11px "Consolas", "Courier New", monospace',
        CENTER_MARKER: '12px "Consolas", "Courier New", monospace',
        SPOT_LABELS: 'bold 13px "Consolas", "Courier New", monospace',  // Increased from 12px to 13px (5% larger)
        SPOT_INFO: '11px "Consolas", "Courier New", monospace',
        WAITING_MESSAGE: '16px "Consolas", "Courier New", monospace',
        TITLE_LARGE: 'bold 24px "Consolas", "Courier New", monospace',
        FREQUENCY_CHANGE: 'bold 18px "Consolas", "Courier New", monospace',
        OUT_OF_BAND: 'bold 14px "Consolas", "Courier New", monospace',
        SMALL_MONO: '12px "Consolas", "Courier New", monospace'
    },

    // Available continents for cycling
    CONTINENTS: ['AF', 'AN', 'AS', 'EU', 'NA', 'OC', 'SA'],

    // Logo configuration
    LOGO_FILENAME: 'assets/logo/wavelog_logo_darkly_wide.png',

    // Data file paths
    IARU_BANDPLANS_PATH: 'assets/json/iaru_bandplans.json',

    // Frequency thresholds (in kHz)
    LSB_USB_THRESHOLD_KHZ: 10000, // Below 10 MHz = LSB, above = USB

    // Signal bandwidth constants (in kHz)
    SIGNAL_BANDWIDTHS: {
        SSB_KHZ: 2.7,       // Standard SSB bandwidth
        SSB_OFFSET_KHZ: 1.35, // Half bandwidth for offset
        AM_KHZ: 6.0,        // AM bandwidth
        FM_KHZ: 12.0,       // FM bandwidth (wide)
        CW_DETECTION_KHZ: 0.25 // CW detection range
    },

    // Static FT8 frequencies (in kHz)
    FT8_FREQUENCIES: [1840, 3573, 7074, 10136, 14074, 18100, 21074, 24915, 28074, 50313, 144174, 432065]
};

// ========================================
// CAT TIMING INITIALIZATION
// ========================================

/**
 * Initialize CAT timing constants based on configured poll interval
 * Called automatically from footer.php after catPollInterval is set
 * @param {number} pollInterval - CAT polling interval in milliseconds
 */
function initCATTimings(pollInterval) {
    DX_WATERFALL_CONSTANTS.CAT.POLL_INTERVAL_MS = pollInterval;
    DX_WATERFALL_CONSTANTS.CAT.TUNING_FLAG_FALLBACK_MS = pollInterval * 1.5;
    DX_WATERFALL_CONSTANTS.CAT.FREQUENCY_WAIT_TIMEOUT_MS = pollInterval * 2;
}

// ========================================
// UTILITY FUNCTIONS
// ========================================

var DX_WATERFALL_UTILS = {

    // Frequency conversion utilities
    frequency: {
        hzToKhz: function(hz) {
            return hz / 1000;
        },

        mhzToKhz: function(mhz) {
            return mhz * 1000;
        },

        // Convert any frequency unit to kHz
        convertToKhz: function(value, unit) {
            var freqValue = parseFloat(value) || 0;
            switch (unit.toLowerCase()) {
                case 'hz':
                    return freqValue / 1000;
                case 'khz':
                    return freqValue;
                case 'mhz':
                    return freqValue * 1000;
                case 'ghz':
                    return freqValue * 1000000;
                default:
                    return freqValue; // Default to kHz
            }
        },

        // Validate frequency value
        isValid: function(value) {
            var freq = parseFloat(value) || 0;
            return freq > 0;
        },

        // Parse and validate frequency
        parseAndValidate: function(value) {
            var freq = parseFloat(value) || 0;
            return { value: freq, valid: freq > 0 };
        }
    },

    // Sorting utilities for common patterns
    sorting: {
        byFrequency: function(a, b) {
            return a.frequency - b.frequency;
        },

        byAbsOffset: function(a, b) {
            return a.absOffset - b.absOffset;
        }
    },

    // Timing utilities
    timing: {
        /**
         * Generic debounce function - delays execution until after wait time has elapsed
         * @param {Function} func - Function to debounce
         * @param {number} wait - Milliseconds to wait
         * @param {Object} context - Context object that stores the timer
         * @param {string} timerProperty - Property name on context object for timer storage
         * @returns {Function} Debounced function
         */
        debounce: function(func, wait, context, timerProperty) {
            return function() {
                var args = arguments;
                var later = function() {
                    context[timerProperty] = null;
                    func.apply(context, args);
                };
                if (context[timerProperty]) {
                    clearTimeout(context[timerProperty]);
                }
                context[timerProperty] = setTimeout(later, wait);
            };
        }
    },

    // DOM selector utilities (cached for performance)
    dom: {
        waterfall: null,

        init: function() {
            this.waterfall = $('#dxWaterfall');
        },

        getWaterfall: function() {
            return this.waterfall || $('#dxWaterfall');
        }
    },

    // Platform detection utilities
    platform: {
        isMac: function() {
            // Use modern userAgentData API if available, fallback to userAgent
            if (navigator.userAgentData && navigator.userAgentData.platform) {
                return navigator.userAgentData.platform.toUpperCase().indexOf('MAC') >= 0;
            }
            return navigator.userAgent.toUpperCase().indexOf('MAC') >= 0;
        },

        isWindows: function() {
            if (navigator.userAgentData && navigator.userAgentData.platform) {
                return navigator.userAgentData.platform.toUpperCase().indexOf('WIN') >= 0;
            }
            return navigator.userAgent.toUpperCase().indexOf('WIN') >= 0;
        },

        isLinux: function() {
            if (navigator.userAgentData && navigator.userAgentData.platform) {
                return navigator.userAgentData.platform.toUpperCase().indexOf('LINUX') >= 0;
            }
            return navigator.userAgent.toUpperCase().indexOf('LINUX') >= 0;
        },

        // Check if the modifier key is pressed (Cmd on Mac, Ctrl on Windows/Linux)
        isModifierKey: function(event) {
            return this.isMac() ? event.metaKey : event.ctrlKey;
        }
    },

    // Mode classification utilities
    modes: {
        isCw: function(mode) {
            return mode && mode.toLowerCase().includes('cw');
        },

        isPhone: function(mode) {
            if (!mode) return false;
            const m = mode.toLowerCase();
            return m.includes('ssb') || m.includes('lsb') || m.includes('usb') ||
                   m.includes('am') || m.includes('fm') || m === 'phone';
        },

        isDigi: function(mode) {
            if (!mode) return false;
            const m = mode.toLowerCase();
            return m.includes('ft') || m.includes('rtty') || m.includes('psk') ||
                   m.includes('jt') || m.includes('mfsk') || m.includes('olivia') ||
                   m.includes('contestia') || m.includes('hell') || m.includes('throb') ||
                   m.includes('sstv') || m.includes('fax') || m === 'digi' || m === 'data';
        },

        getModeCategory: function(mode) {
            if (this.isCw(mode)) return 'cw';
            if (this.isPhone(mode)) return 'phone';
            if (this.isDigi(mode)) return 'digi';
            return 'other';
        },

        // Get color for a classified mode with customizable alpha
        getModeColor: function(classifiedMode, alpha) {
            alpha = alpha !== undefined ? alpha : 0.6; // Default 60% opacity
            return this.getModeColorBase(classifiedMode) + alpha + ')';
        },

        // Get base color string without alpha for gradient construction
        getModeColorBase: function(classifiedMode) {
            switch (classifiedMode) {
                case 'phone':
                    return 'rgba(0, 255, 0, ';
                case 'cw':
                    return 'rgba(255, 165, 0, ';
                case 'digi':
                    return 'rgba(0, 150, 255, ';
                default:
                    return 'rgba(160, 32, 240, ';
            }
        },

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
        classifyMode: function(spot) {
            if (!spot || !spot.mode || spot.mode === '') {
                return { category: 'other', submode: 'Unknown', confidence: 0 };
            }

            var mode = spot.mode.toUpperCase();
            var message = (spot.message || '').toUpperCase();
            var confidence = 1; // 1 = high confidence, 0.5 = medium, 0.3 = low

            // Check message first for higher accuracy
            var messageResult = this.classifyFromMessage(message);
            if (messageResult.category) {
                return {
                    category: messageResult.category,
                    submode: messageResult.submode,
                    confidence: messageResult.confidence
                };
            }

            // Fall back to mode field classification
            return this.classifyFromMode(mode);
        },

        classifyFromMessage: function(message) {
            // CW detection in message
            if (message.indexOf('CW') !== -1) {
                return { category: 'cw', submode: 'CW', confidence: 1 };
            }

            // Digital modes from message
            var digiModes = [
                { patterns: ['FT8'], submode: 'FT8' },
                { patterns: ['FT4'], submode: 'FT4' },
                { patterns: ['RTTY'], submode: 'RTTY' },
                { patterns: ['PSK31'], submode: 'PSK31' },
                { patterns: ['PSK'], submode: 'PSK' },
                { patterns: ['JT65'], submode: 'JT65' },
                { patterns: ['JT9'], submode: 'JT9' },
                { patterns: ['WSPR'], submode: 'WSPR' },
                { patterns: ['JS8'], submode: 'JS8' }
            ];

            // Optimized loop - breaks early on first match
            for (var i = 0; i < digiModes.length; i++) {
                var mode = digiModes[i];
                for (var j = 0; j < mode.patterns.length; j++) {
                    if (message.indexOf(mode.patterns[j]) !== -1) {
                        return { category: 'digi', submode: mode.submode, confidence: 1 };
                    }
                }
            }

            // Phone modes from message (optimized with regex word boundary)
            var phoneModes = [
                { patterns: ['LSB'], submode: 'LSB' },
                { patterns: ['USB'], submode: 'USB' },
                { patterns: ['SSB'], submode: 'SSB' },
                { patterns: ['AM'], submode: 'AM' },
                { patterns: ['FM'], submode: 'FM' }
            ];

            // Optimized loop - breaks early on first match
            for (var i = 0; i < phoneModes.length; i++) {
                var mode = phoneModes[i];
                for (var j = 0; j < mode.patterns.length; j++) {
                    // Use word boundary to avoid false matches
                    var pattern = '\\b' + mode.patterns[j] + '\\b';
                    if (new RegExp(pattern).test(message)) {
                        return { category: 'phone', submode: mode.submode, confidence: 1 };
                    }
                }
            }

            return { category: null, submode: null, confidence: 0 };
        },

        classifyFromMode: function(mode) {
            // CW modes
            if (mode === 'CW' || mode === 'A1A') {
                return { category: 'cw', submode: 'CW', confidence: 1 };
            }

            // Phone modes
            var phoneModes = ['SSB', 'LSB', 'USB', 'AM', 'FM', 'SAM', 'DSB', 'J3E', 'A3E', 'PHONE'];
            if (phoneModes.indexOf(mode) !== -1) {
                return { category: 'phone', submode: mode, confidence: 1 };
            }

            // Digital modes - WSJT-X family
            var wsjtModes = ['FT8', 'FT4', 'JT65', 'JT65B', 'JT6C', 'JT6M', 'JT9', 'JT9-1',
                           'Q65', 'QRA64', 'FST4', 'FST4W', 'WSPR', 'MSK144', 'ISCAT',
                           'ISCAT-A', 'ISCAT-B', 'JS8', 'JTMS', 'FSK441'];
            if (wsjtModes.indexOf(mode) !== -1) {
                return { category: 'digi', submode: mode, confidence: 1 };
            }

            // PSK variants
            if (mode.indexOf('PSK') !== -1 || mode.indexOf('QPSK') !== -1 || mode.indexOf('8PSK') !== -1) {
                return { category: 'digi', submode: mode, confidence: 1 };
            }

            // Other digital modes
            var otherDigiModes = ['RTTY', 'NAVTEX', 'SITORB', 'DIGI', 'DYNAMIC'];
            if (otherDigiModes.indexOf(mode) !== -1) {
                return { category: 'digi', submode: mode, confidence: 1 };
            }

            // Pattern-based digital mode detection
            if (mode.indexOf('HELL') !== -1 || mode.indexOf('FSK') === 0 ||
                mode.indexOf('THOR') !== -1 || mode.indexOf('THROB') !== -1 ||
                mode.indexOf('DOM') !== -1 || mode.indexOf('VARA') !== -1) {
                return { category: 'digi', submode: mode, confidence: 1 };
            }

            // Unknown mode - ensure we return a valid submode string
            return { category: 'other', submode: mode || 'Unknown', confidence: 0.3 };
        },

        // Determine LSB/USB for SSB based on frequency
        determineSSBMode: function(frequency) {
            var freq = parseFloat(frequency) || 0;
            if (freq > 0) {
                return freq < DX_WATERFALL_CONSTANTS.LSB_USB_THRESHOLD_KHZ ? 'LSB' : 'USB';
            }
            return 'SSB';
        },

        // Enhanced detailed submode information using unified classification
        getDetailedSubmode: function(spot) {
            return this.classifyMode(spot);
        }
    },

    // Spot utilities for common spot object creation
    spots: {
        // Create standardized spot object from raw spot data
        createSpotObject: function(spot, options) {
            options = options || {};
            var spotFreq = parseFloat(spot.frequency);

            // Determine the correct mode to use
            // Priority: program-specific mode from DXCC data > generic mode field
            // Check for POTA, SOTA, WWFF, or IOTA specific modes
            var spotMode = spot.mode || '';
            
            if (spot.dxcc_spotted) {
                // Check for program-specific modes in priority order
                if (spot.dxcc_spotted.pota_mode) {
                    spotMode = spot.dxcc_spotted.pota_mode;
                } else if (spot.dxcc_spotted.sota_mode) {
                    spotMode = spot.dxcc_spotted.sota_mode;
                } else if (spot.dxcc_spotted.wwff_mode) {
                    spotMode = spot.dxcc_spotted.wwff_mode;
                } else if (spot.dxcc_spotted.iota_mode) {
                    spotMode = spot.dxcc_spotted.iota_mode;
                }
            } else if (spot.dxcc_spotter) {
                // Fallback to spotter's DXCC data
                if (spot.dxcc_spotter.pota_mode) {
                    spotMode = spot.dxcc_spotter.pota_mode;
                } else if (spot.dxcc_spotter.sota_mode) {
                    spotMode = spot.dxcc_spotter.sota_mode;
                } else if (spot.dxcc_spotter.wwff_mode) {
                    spotMode = spot.dxcc_spotter.wwff_mode;
                } else if (spot.dxcc_spotter.iota_mode) {
                    spotMode = spot.dxcc_spotter.iota_mode;
                }
            }

            var spotObj = {
                callsign: spot.spotted,
                frequency: spotFreq,
                mode: spotMode
            };

            // Add optional fields based on options
            if (options.includeSpotter) {
                spotObj.spotter = spot.spotter;
            }
            if (options.includeTimestamp) {
                spotObj.when_pretty = spot.when_pretty || '';
            }
            if (options.includeMessage) {
                spotObj.message = spot.message || '';
            }
            if (options.includeOffsets && options.middleFreq !== undefined) {
                var freqOffset = spotFreq - options.middleFreq;
                spotObj.freqOffset = freqOffset;
                spotObj.absOffset = Math.abs(freqOffset);
            }
            if (options.includePosition && options.x !== undefined) {
                spotObj.x = options.x;
            }
            if (options.includeWorkStatus) {
                spotObj.dxcc_spotted = spot.dxcc_spotted || {};
                spotObj.lotw_user = spot.lotw_user || false;
                spotObj.worked_dxcc = spot.worked_dxcc || false;
                spotObj.worked_continent = spot.worked_continent || false;
                spotObj.worked_call = spot.worked_call || false;
                spotObj.cnfmd_dxcc = spot.cnfmd_dxcc || false;
                spotObj.cnfmd_continent = spot.cnfmd_continent || false;
                spotObj.cnfmd_call = spot.cnfmd_call || false;
            }

            // Handle park references (pre-calculated or extract from message)
            if (spot.sotaRef !== undefined || options.includeParkRefs !== false) {
                var parkRefs = (spot.sotaRef !== undefined) ? spot : DX_WATERFALL_UTILS.parkRefs.extract(spot);
                spotObj.sotaRef = parkRefs.sotaRef || '';
                spotObj.potaRef = parkRefs.potaRef || '';
                spotObj.iotaRef = parkRefs.iotaRef || '';
                spotObj.wwffRef = parkRefs.wwffRef || '';
            } else {
                spotObj.sotaRef = spotObj.potaRef = spotObj.iotaRef = spotObj.wwffRef = '';
            }

            return spotObj;
        },

        /**
         * Filter and collect spots based on criteria with automatic deduplication
         * Efficiently processes DX spots with mode filtering, custom filtering, and duplicate removal
         *
         * @param {Object} waterfallContext - The dxWaterfall object context
         * @param {Function} [filterFunction] - Optional custom filter function(spot, spotFreq, context) => boolean
         * @param {Object} [options] - Configuration options
         * @param {Object} [options.spotOptions] - Options passed to createSpotObject
         * @param {Function} [options.postProcess] - Optional post-processing function(spotObj, originalSpot) => spotObj
         * @param {boolean} [options.deduplication=true] - Enable automatic duplicate detection (default: true)
         * @returns {{spots: Array, stats: Object}} Object containing filtered spots array and statistics
         *          - spots: Array of processed spot objects
         *          - stats: {filtered, invalid, processed, duplicates} - Processing statistics
         */
        filterSpots: function(waterfallContext, filterFunction, options) {
            options = options || {};

            if (!waterfallContext.dxSpots || waterfallContext.dxSpots.length === 0) {
                return { spots: [], stats: { filtered: 0, invalid: 0, processed: 0, duplicates: 0 } };
            }

            // Use Map for efficient duplicate detection
            var spotMap = options.deduplication !== false ? {} : null; // Enable deduplication by default
            var spots = [];
            var stats = {
                filtered: 0,
                invalid: 0,
                processed: 0,
                duplicates: 0
            };

            for (var i = 0; i < waterfallContext.dxSpots.length; i++) {
                var spot = waterfallContext.dxSpots[i];
                var spotFreq = parseFloat(spot.frequency);

                // Validate basic spot data
                if (!spotFreq || !spot.spotted || !spot.mode) {
                    stats.invalid++;
                    continue;
                }

                // Check for duplicates using frequency:callsign key
                if (spotMap) {
                    var spotKey = spotFreq.toFixed(1) + ':' + spot.spotted;
                    if (spotMap[spotKey]) {
                        stats.duplicates++;
                        continue;
                    }
                    spotMap[spotKey] = true;
                }

                // Apply mode filter
                if (!waterfallContext.spotMatchesModeFilter(spot)) {
                    stats.filtered++;
                    continue;
                }

                // Apply custom filter function if provided
                if (filterFunction && !filterFunction(spot, spotFreq, waterfallContext)) {
                    stats.filtered++;
                    continue;
                }

                // Create spot object
                var spotOptions = options.spotOptions || {};
                var spotObj = this.createSpotObject(spot, spotOptions);

                // Apply any post-processing
                if (options.postProcess) {
                    spotObj = options.postProcess(spotObj, spot);
                }

                spots.push(spotObj);
                stats.processed++;
            }

            return {
                spots: spots,
                stats: stats
            };
        }
    },

    // Park reference extraction utilities
    parkRefs: {
        /**
         * Extract park references (SOTA/POTA/IOTA/WWFF) from spot data
         * Uses direct fields if available, otherwise extracts from message
         * @param {Object} spot - Raw spot object from DX cluster
         * @returns {Object} Object with sotaRef, potaRef, iotaRef, wwffRef properties
         */
        extract: function(spot) {
            var refs = {
                sotaRef: '',
                potaRef: '',
                iotaRef: '',
                wwffRef: ''
            };

            // First check if references are provided directly by the server
            if (spot.dxcc_spotted) {
                refs.sotaRef = spot.dxcc_spotted.sota_ref || '';
                refs.potaRef = spot.dxcc_spotted.pota_ref || '';
                refs.iotaRef = spot.dxcc_spotted.iota_ref || '';
                refs.wwffRef = spot.dxcc_spotted.wwff_ref || '';
            }

            // If any references are missing, try to extract from message
            var message = spot.message || '';
            if (message && (!refs.sotaRef || !refs.potaRef || !refs.iotaRef || !refs.wwffRef)) {
                var upperMessage = message.toUpperCase();

                // SOTA format: XX/YY-### or XX/YY-#### (e.g., "G/LD-001", "W4G/NG-001")
                if (!refs.sotaRef) {
                    var sotaMatch = upperMessage.match(/\b([A-Z0-9]{1,3}\/[A-Z]{2}-\d{3})\b/);
                    if (sotaMatch) {
                        refs.sotaRef = sotaMatch[1];
                    }
                }

                // POTA format: XX-#### (e.g., "US-4306", "K-1234")
                // Must not match WWFF patterns (ending in FF)
                if (!refs.potaRef) {
                    var potaMatch = upperMessage.match(/\b([A-Z0-9]{1,5}-\d{4,5})\b/);
                    if (potaMatch && !potaMatch[1].match(/FF-/)) {
                        refs.potaRef = potaMatch[1];
                    }
                }

                // IOTA format: XX-### (e.g., "EU-005", "NA-001", "OC-123")
                if (!refs.iotaRef) {
                    var iotaMatch = upperMessage.match(/\b((?:AF|AN|AS|EU|NA|OC|SA)-\d{3})\b/);
                    if (iotaMatch) {
                        refs.iotaRef = iotaMatch[1];
                    }
                }

                // WWFF format: XXFF-#### (e.g., "GIFF-0001", "K1FF-0123", "ON4FF-0050")
                if (!refs.wwffRef) {
                    var wwffMatch = upperMessage.match(/\b([A-Z0-9]{2,4}FF-\d{4})\b/);
                    if (wwffMatch) {
                        refs.wwffRef = wwffMatch[1];
                    }
                }
            }

            return refs;
        }
    },

    // QSO form utilities
    qsoForm: {
        // Timer for pending population to allow cancellation
        pendingPopulationTimer: null,
        pendingLookupTimer: null,

        // Cached jQuery selectors for performance
        $btnReset: null,

        /**
         * Initialize cached selectors
         * Call this once when DOM is ready
         */
        initCache: function() {
            this.$btnReset = $('#btn_reset');
        },

        /**
         * Clear the QSO form by clicking the reset button
         * Uses cached selector for performance
         */
        clearForm: function() {
            // Initialize cache if not done yet
            if (!this.$btnReset) {
                this.initCache();
            }

            // Check if button exists and click it
            if (this.$btnReset && this.$btnReset.length > 0) {
                this.$btnReset.click();
            }
        },

        /**
         * Populate QSO form with spot data (callsign, mode, and park references)
         * @param {Object} spotData - Spot data object
         * @param {string} spotData.callsign - Callsign to populate
         * @param {string} [spotData.mode] - Mode to set
         * @param {string} [spotData.sotaRef] - SOTA reference
         * @param {string} [spotData.potaRef] - POTA reference
         * @param {string} [spotData.iotaRef] - IOTA reference
         * @param {string} [spotData.wwffRef] - WWFF reference
         * @param {boolean} [triggerLookup=true] - Whether to trigger callsign lookup
         */
        populateFromSpot: function(spotData, triggerLookup) {
            if (typeof triggerLookup === 'undefined') {
                triggerLookup = true;
            }

            if (!spotData.callsign) return;

            // Cancel any pending population timers from previous navigation
            if (this.pendingLookupTimer) {
                clearTimeout(this.pendingLookupTimer);
                this.pendingLookupTimer = null;
            }

            // Set preventLookup flag BEFORE any form changes to prevent duplicate lookups
            // This blocks the $("#callsign").blur() triggered by mode change handler
            var wasPreventLookupSet = false;
            if (triggerLookup && typeof preventLookup !== 'undefined') {
                preventLookup = true;
                wasPreventLookupSet = true;
            }

            // Populate the callsign input field
            var callsignInput = $('#callsign');
            var formattedCallsign = spotData.callsign.toUpperCase().replace(/0/g, 'Ø');
            callsignInput.val(formattedCallsign);

            // Set the mode if available (this triggers mode change handler which calls $("#callsign").blur())
            if (spotData.mode) {
                setMode(spotData.mode);
            }

            // Populate SOTA reference if available (selectize field)
            if (spotData.sotaRef && spotData.sotaRef !== '') {
                var $sotaSelect = $('#sota_ref');
                if ($sotaSelect.length > 0 && $sotaSelect[0].selectize) {
                    var sotaSelectize = $sotaSelect[0].selectize;
                    sotaSelectize.addOption({name: spotData.sotaRef});
                    sotaSelectize.setValue(spotData.sotaRef, false);
                }
            }

            // Populate POTA reference if available (selectize field)
            if (spotData.potaRef && spotData.potaRef !== '') {
                var $potaSelect = $('#pota_ref');
                if ($potaSelect.length > 0 && $potaSelect[0].selectize) {
                    var potaSelectize = $potaSelect[0].selectize;
                    potaSelectize.addOption({name: spotData.potaRef});
                    potaSelectize.setValue(spotData.potaRef, false);
                }
            }

            // Populate IOTA reference if available (regular select dropdown)
            if (spotData.iotaRef && spotData.iotaRef !== '') {
                var $iotaSelect = $('#iota_ref');
                if ($iotaSelect.length > 0) {
                    var optionExists = $iotaSelect.find('option[value="' + spotData.iotaRef + '"]').length > 0;
                    if (optionExists) {
                        $iotaSelect.val(spotData.iotaRef);
                    } else {
                        $iotaSelect.append($('<option>', {
                            value: spotData.iotaRef,
                            text: spotData.iotaRef
                        }));
                        $iotaSelect.val(spotData.iotaRef);
                    }
                    // Don't trigger change event - it's unnecessary and may cause side effects
                }
            }

            // Populate WWFF reference if available (selectize field)
            if (spotData.wwffRef && spotData.wwffRef !== '') {
                var $wwffSelect = $('#wwff_ref');
                if ($wwffSelect.length > 0 && $wwffSelect[0].selectize) {
                    var wwffSelectize = $wwffSelect[0].selectize;
                    wwffSelectize.addOption({name: spotData.wwffRef});
                    wwffSelectize.setValue(spotData.wwffRef, false);
                }
            }

            // Trigger callsign lookup if requested
            if (triggerLookup) {
                var self = this;
                this.pendingLookupTimer = setTimeout(function() {
                    // Clear preventLookup flag just before triggering the lookup
                    if (wasPreventLookupSet) {
                        preventLookup = false;
                    }
                    callsignInput.trigger('focusout');
                    self.pendingLookupTimer = null;
                }, 50);
            }
        }
    },

    // Navigation utilities for spot navigation
    navigation: {
        // Timer for pending navigation actions
        pendingNavigationTimer: null,

        // Common navigation logic shared by all spot navigation functions
        navigateToSpot: function(waterfallContext, targetSpot, targetIndex) {
            if (!targetSpot) return false;

            // Cancel any pending navigation timers
            if (this.pendingNavigationTimer) {
                clearTimeout(this.pendingNavigationTimer);
                this.pendingNavigationTimer = null;
            }

            // Update the band spot index
            waterfallContext.currentBandSpotIndex = targetIndex;

            // Set frequency to the spot (like clicking behavior)
            if (targetSpot.frequency) {
                // Clear the QSO form when navigating to a new spot
                DX_WATERFALL_UTILS.qsoForm.clearForm();

                // Check if frequency is far outside current band and update band if needed
                if (waterfallContext.isFrequencyFarOutsideBand(targetSpot.frequency)) {
                    waterfallContext.updateBandFromFrequency(targetSpot.frequency);
                }

                // Use the same frequency setting approach as clicking
                setFrequency(targetSpot.frequency, true); // Pass true to indicate waterfall-initiated change

                // Manually set the frequency in the input field immediately
                var formattedFreq = Math.round(targetSpot.frequency * 1000); // Convert to Hz
                $('#frequency').val(formattedFreq);

                // CRITICAL: Directly update the cache to the target frequency
                // getCachedMiddleFreq() uses lastValidCommittedFreq which isn't updated by just setting the input value
                // So we bypass the cache and set it directly to ensure getSpotInfo() uses the correct frequency
                waterfallContext.cache.middleFreq = targetSpot.frequency; // Already in kHz
                waterfallContext.lastFreqInput = formattedFreq;
                waterfallContext.lastValidCommittedFreq = formattedFreq;
                waterfallContext.lastValidCommittedUnit = 'kHz';

                var cachedFreq = waterfallContext.getCachedMiddleFreq();

                // Now get spot info - this will use the new frequency we just set
                var spotInfo = waterfallContext.getSpotInfo();

                // Populate form after a brief delay (same as click handler)
                if (spotInfo) {
                    var self = this;
                    this.pendingNavigationTimer = setTimeout(function() {
                        DX_WATERFALL_UTILS.qsoForm.populateFromSpot(spotInfo, true);
                        self.pendingNavigationTimer = null;
                    }, 100);
                }
                // If no spot found, form remains cleared (already cleared above)

                // Commit the new frequency
                setTimeout(function() {
                    waterfallContext.commitFrequency();
                }, 50);
            }

            // Update zoom menu to reflect new position
            waterfallContext.updateZoomMenu();
            return true;
        },

        // Check if navigation is allowed (not during frequency changes)
        canNavigate: function(waterfallContext) {
            return !waterfallContext.frequencyChanging && waterfallContext.allBandSpots.length > 0;
        }
    },

    // Drawing utilities for common canvas operations
    drawing: {

        // Draw overlay message with logo
        drawOverlayMessage: function(canvas, ctx, message, colorKey) {
            if (!canvas) return;

            // Draw semi-transparent overlay over current content
            this.drawOverlay(ctx, canvas.width, canvas.height, 'OVERLAY_BACKGROUND');

            // Calculate center position
            var centerX = canvas.width / 2;
            var centerY = canvas.height / 2;

            // Draw pulsing Wavelog logo above the message
            var logoY = centerY - DX_WATERFALL_CONSTANTS.CANVAS.LOGO_OFFSET_Y;
            if (typeof dxWaterfall !== 'undefined' && dxWaterfall.drawWavelogLogo) {
                // Calculate pulsing opacity (0.5 to 1.0 for smooth fade effect)
                var pulseOpacity = 0.75 + 0.25 * Math.sin(Date.now() / 300);
                dxWaterfall.drawWavelogLogo(centerX, logoY, pulseOpacity);
            }

            // Text position (moved down lower for more space)
            var textY = centerY + DX_WATERFALL_CONSTANTS.CANVAS.TEXT_OFFSET_Y;

            // Draw message text
            this.drawCenteredText(ctx, message, centerX, textY, 'FREQUENCY_CHANGE', colorKey);

            // Reset opacity
            ctx.globalAlpha = 1.0;
        },

        drawCenteredText: function(ctx, text, x, y, fontKey, colorKey) {
            ctx.font = DX_WATERFALL_CONSTANTS.FONTS[fontKey] || DX_WATERFALL_CONSTANTS.FONTS.SMALL_MONO;
            ctx.fillStyle = DX_WATERFALL_CONSTANTS.COLORS[colorKey] || DX_WATERFALL_CONSTANTS.COLORS.MESSAGE_TEXT_WHITE;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(text, x, y);
        },

        drawOverlay: function(ctx, width, height, colorKey) {
            ctx.fillStyle = DX_WATERFALL_CONSTANTS.COLORS[colorKey || 'OVERLAY_BACKGROUND'];
            ctx.fillRect(0, 0, width, height);
        }
    },
};

// Initialize DOM cache
$(document).ready(function() {
    DX_WATERFALL_UTILS.dom.init();
});

// ========================================
// MAIN DX WATERFALL OBJECT
// ========================================
var dxWaterfall = {

    // ========================================
    // CORE CANVAS PROPERTIES
    // ========================================
    canvas: null,
    ctx: null,

    // ========================================
    // DATA MANAGEMENT PROPERTIES
    // ========================================
    dxSpots: [], // Store DX cluster spots
    lastBand: null, // Track last band
    lastMode: null, // Track last mode
    initialFetchDone: false, // Track if initial DX spot fetch has been done
    totalSpotsCount: 0, // Track total number of spots pulled

    // Data loading state management
    pageLoadTime: null, // Track when page loaded
    operationStartTime: null, // Track when current operation (fetch/tune) started
    waitingForData: true, // Track if we're waiting for initial data
    minWaitTime: DX_WATERFALL_CONSTANTS.DEBOUNCE.SPOT_COLLECTION_MS,
    dataReceived: false, // Track if we've received any data
    waitingForCATFrequency: true, // Track if we're waiting for CAT frequency on initial load
    catFrequencyWaitTimer: null, // Timer for CAT frequency wait

    // ========================================
    // USER INTERFACE STATE
    // ========================================
    userEditingFrequency: false, // Track if user is actively editing frequency
    lastSpotInfoText: null, // Track last displayed spot info to prevent redundant updates
    spotInfoDiv: null, // Reference to the dxWaterfallSpot div
    currentContinent: 'NA', // Track current continent filter
    currentMaxAge: 60, // Track current max age filter

    // ========================================
    // SPOT NAVIGATION STATE
    // ========================================
    lastUpdateTime: null, // Track when spots were last updated
    lastFetchBand: null, // Track last fetched band
    lastFetchContinent: null, // Track last fetched continent
    lastFetchAge: null, // Track last fetched age
    fetchInProgress: false, // Track if a fetch is currently in progress
    relevantSpots: [], // Store all relevant spots in bandwidth
    currentSpotIndex: 0, // Track which spot is currently displayed
    allBandSpots: [], // Store all valid spots on the band (for navigation)
    currentBandSpotIndex: 0, // Track which band spot is currently displayed

    // ========================================
    // VISUAL CONFIGURATION
    // ========================================
    fonts: DX_WATERFALL_CONSTANTS.FONTS, // Font configuration

    // ========================================
    // PERFORMANCE CACHING
    // ========================================
    cache: {
        // Static noise animation caching
        noise1: null,
        noise2: null,
        currentNoiseFrame: 0, // 0 or 1 to alternate between noise patterns
        noiseWidth: 0,
        noiseHeight: 0,

        // Frequency and rendering caching
        middleFreq: null,

        // Input state caching
        lastFreqInput: null,
        lastQrgUnit: null,
        lastModeForCache: null,
        committedFreqInput: null, // Frequency value committed on blur/Enter
        committedQrgUnit: null, // Unit value committed on blur/Enter
        lastValidCommittedFreq: null, // Last VALID committed frequency
        lastValidCommittedUnit: null // Last VALID committed unit
    },

    // State flags
    programmaticModeChange: false, // Flag to prevent fetching spots when mode is changed by waterfall
    userChangedBand: false, // Flag to prevent auto band update when user manually changed band
    initializationComplete: false, // Flag to track if initial setup is done

    // ========================================
    // ZOOM AND NAVIGATION STATE
    // ========================================
    currentZoomLevel: DX_WATERFALL_CONSTANTS.ZOOM.DEFAULT_LEVEL,
    maxZoomLevel: DX_WATERFALL_CONSTANTS.ZOOM.MAX_LEVEL,
    zoomMenuDiv: null, // Reference to the dxWaterfallMenu div
    zoomChanging: false, // Flag to prevent rapid zoom changes
    spotNavigating: false, // Flag to prevent double navigation when clicking icons

    // ========================================
    // SMART HUNTER FUNCTIONALITY
    // ========================================
    smartHunterSpots: [], // Store spots with unworked continent or DXCC
    currentSmartHunterIndex: 0, // Track current position in smart hunter cycle
    smartHunterActive: false, // Track if smart hunter is active

    // ========================================
    // CONTINENT FILTERING
    // ========================================
    continents: DX_WATERFALL_CONSTANTS.CONTINENTS, // Available continents for cycling
    continentChanging: false, // Flag to prevent rapid continent changes
    continentChangeTimer: null, // Timer for debounced continent change
    pendingContinent: null, // Store pending continent during debounce period
    initialLoadDone: false, // Flag to track if initial continent load from PHP has been done

    // Frequency change state management
    frequencyChanging: false, // Flag to block inputs during frequency changes
    lastWaterfallFrequencyCommandTime: 0, // Track when waterfall sent a CAT command
    lastFrequencyRefreshTime: 0, // Throttle frequency cache refresh
    catTuning: false, // Flag to show "Tuning radio..." message during CAT operations

    // Spot fetch state management
    userInitiatedFetch: false, // Flag to distinguish user-initiated fetches from background refreshes

    // Spot collection throttling
    lastSpotCollectionTime: 0,
    spotCollectionThrottleMs: DX_WATERFALL_CONSTANTS.DEBOUNCE.SPOT_COLLECTION_MS,

    // Fetch request debouncing (reduced timing)
    fetchDebounceTimer: null,
    fetchDebounceMs: DX_WATERFALL_CONSTANTS.DEBOUNCE.FETCH_REQUEST_MS,

    // Mode filter management
    modeFilters: {
        phone: true,  // Active by default
        cw: true,     // Active by default
        digi: false   // Inactive by default
    },
    pendingModeFilters: null, // Store pending mode filters during debounce period
    modeFilterChangeTimer: null, // Timer for debounced mode filter change

    // Static FT8
    ft8Frequencies: DX_WATERFALL_CONSTANTS.FT8_FREQUENCIES,

    // Band plan management
    bandPlans: null, // Cached band plans from JSON
    currentRegion: null, // Current IARU region (R1, R2, R3)
    bandLimitsCache: null, // Cached band limits for current band+region

    // ========================================
    // INITIALIZATION AND SETUP FUNCTIONS
    // ========================================

    /**
     * Initialize the DX waterfall canvas and event handlers
     * Sets up canvas context, dimensions, and starts initial data fetch
     * @returns {void}
     */
    init: function() {
        this.canvas = document.getElementById('dxWaterfall');

        // Check if canvas element exists
        if (!this.canvas) {
            return;
        }

        this.ctx = this.canvas.getContext('2d');
        var $waterfall = DX_WATERFALL_UTILS.dom.getWaterfall();
        this.canvas.width = $waterfall.width();
        this.canvas.height = $waterfall.height();

        // Get reference to spot info div and menu div
        this.spotInfoDiv = document.getElementById('dxWaterfallSpot');
        this.zoomMenuDiv = document.getElementById('dxWaterfallMenu');

        // Cache frequently accessed DOM elements for performance
        this.$freqCalculated = $('#freq_calculated');
        this.$qrgUnit = $('#qrg_unit');
        this.$bandSelect = $('#band');
        this.$modeSelect = $('#mode');

        // Set up mouse wheel for zooming (must use passive: false to prevent page scroll)
        this.canvas.addEventListener('wheel', function(e) {
            // Block zooming when frequency is changing
            if (self.frequencyChanging) {
                return;
            }

            // Prevent page scroll when zooming over the canvas
            e.preventDefault();
            e.stopPropagation();

            // Get wheel direction (negative = scroll up = zoom in, positive = scroll down = zoom out)
            var delta = e.deltaY;

            // Zoom in on scroll up, zoom out on scroll down
            if (delta < 0) {
                self.zoomIn();
            } else if (delta > 0) {
                self.zoomOut();
            }
        }, { passive: false }); // passive: false is required for preventDefault to work

        // Set page load time for waiting state management
        this.pageLoadTime = Date.now();
        this.operationStartTime = Date.now(); // Initialize operation timer

        // Set top div to &nbsp; to maintain layout height
        if (this.spotInfoDiv) {
            this.spotInfoDiv.innerHTML = '&nbsp;';
        }

        // Set bottom menu to &nbsp; initially - will be populated after data fetch
        if (this.zoomMenuDiv) {
            this.zoomMenuDiv.innerHTML = '&nbsp;';
        }

        // Don't call updateZoomMenu() here - it will be populated after first data fetch

        // Set up frequency input event listeners to only commit on blur or Enter
        var self = this;

        this.$freqCalculated.on('focus', function() {
            // Mark that user is actively editing
            self.userEditingFrequency = true;
            // When user starts editing, commit current value first (if valid and not already committed)
            if (self.lastValidCommittedFreq === null) {
                var currentFreq = parseFloat($(this).val()) || 0;
                if (currentFreq > 0) {
                    self.commitFrequency();
                }
            }
        });

        this.$freqCalculated.on('blur', function() {
            // User finished editing
            self.userEditingFrequency = false;
            self.commitFrequency();
        });

        this.$freqCalculated.on('input', function() {
            // User is actively typing
            self.userEditingFrequency = true;
        });

        // Use keydown to catch Enter key for frequency commits
        this.$freqCalculated.on('keydown', function(e) {
            if (e.which === 13) { // Enter key
                e.preventDefault(); // Prevent form submission
                self.userEditingFrequency = false; // User finished editing
                self.commitFrequency();
                $(this).blur(); // Remove focus from the field
                return false;
            }
        });

        // Commit initial frequency with retry for slow page loads
        // This ensures lastValidCommittedFreq is set before user starts typing
        var attemptCommit = function(attemptsLeft) {
            var freq = parseFloat(self.$freqCalculated.val()) || 0;
            if (freq > 0) {
                self.commitFrequency();
                self.initializationComplete = true;
            } else if (attemptsLeft > 0) {
                // Retry with exponential backoff for slow page loads
                setTimeout(function() {
                    attemptCommit(attemptsLeft - 1);
                }, DX_WATERFALL_CONSTANTS.DEBOUNCE.FREQUENCY_COMMIT_RETRY_MS * (6 - attemptsLeft));
            } else {
                // Give up and mark complete to avoid blocking
                self.initializationComplete = true;
            }
        };
        attemptCommit(5); // Try up to 5 times

        // Initialize lastBand and lastMode to current values before first refresh
        // This prevents hasParametersChanged() from detecting initial state as a "change"
        this.lastBand = this.getCurrentBand();
        this.lastMode = this.getCurrentMode();

        // Set up delay for CAT frequency to arrive
        // After timeout, proceed with initial fetch regardless
        this.catFrequencyWaitTimer = setTimeout(function() {
            self.waitingForCATFrequency = false;
            // Trigger refresh which will now perform the initial fetch
            if (!self.initialFetchDone) {
                self.refresh();
            }
        }, DX_WATERFALL_CONSTANTS.CAT.FREQUENCY_WAIT_TIMEOUT_MS);

        // Safety fallback: If we're still stuck after 10 seconds, force the initial fetch
        setTimeout(function() {
            if (!self.initialFetchDone && !self.dataReceived) {
                self.waitingForCATFrequency = false;
                if (self.catFrequencyWaitTimer) {
                    clearTimeout(self.catFrequencyWaitTimer);
                    self.catFrequencyWaitTimer = null;
                }
                self.initialFetchDone = true;
                self.fetchDxSpots(true, false);
            }
        }, 10000); // 10 second safety timeout

        this.refresh();
    },

    // Commit the current frequency value (called on blur or Enter key)
    // This prevents the waterfall from shifting while the user is typing
    commitFrequency: function() {
        this.committedFreqInput = this.$freqCalculated.val();
        this.committedQrgUnit = this.$qrgUnit.text() || 'kHz';

        // If this is a valid frequency, save it as the last valid committed frequency
        var freqValue = parseFloat(this.committedFreqInput) || 0;
        if (freqValue > 0) {
            this.lastValidCommittedFreq = this.committedFreqInput;
            this.lastValidCommittedUnit = this.committedQrgUnit;

            // If we're still waiting for CAT frequency and user manually set a frequency, cancel the wait
            // Only cancel if initialization is complete (don't cancel during initial page load)
            if (this.waitingForCATFrequency && this.initializationComplete) {
                if (this.catFrequencyWaitTimer) {
                    clearTimeout(this.catFrequencyWaitTimer);
                    this.catFrequencyWaitTimer = null;
                }
                this.waitingForCATFrequency = false;
                // Trigger initial fetch now
                if (!this.initialFetchDone) {
                    this.refresh();
                }
            }
        }

        // Invalidate the cached frequency to force recalculation
        this.lastFreqInput = null;
        this.lastQrgUnit = null;

        // Force an immediate refresh to update the display with the new frequency
        if (this.canvas && this.ctx) {
            this.refresh();
        }

        // Update zoom menu to reflect new arrow states based on frequency position
        if (this.zoomMenuDiv) {
            this.updateZoomMenu();
        }
    },

    // Check if band or mode has changed
    hasParametersChanged: function() {
        // Get current values from form elements (same as getCurrentBand/getCurrentMode)
        var currentBand = this.getCurrentBand();
        var currentMode = this.getCurrentMode();

        // Check for invalid states that should prevent spot fetching
        var middleFreq = this.getCachedMiddleFreq(); // Returns frequency in kHz
        var isFrequencyInvalid = middleFreq <= 0;
        var isBandInvalid = !currentBand || currentBand === '' || currentBand.toLowerCase() === 'select';

        // If frequency or band is invalid, show waiting message but don't fetch spots
        if (isFrequencyInvalid || isBandInvalid) {
            this.waitingForData = true;
            this.dataReceived = false;
            this.lastSpotInfoText = null;
            this.relevantSpots = [];
            this.currentSpotIndex = 0;
            if (this.spotInfoDiv) {
                this.spotInfoDiv.innerHTML = '&nbsp;';
            }
            // Update tracking but don't trigger fetch
            this.lastBand = currentBand;
            this.lastMode = currentMode;
            return false; // Don't trigger spot fetch
        }

        var bandChanged = this.lastBand !== currentBand;
        var modeChanged = this.lastMode !== currentMode;

        // Update zoom menu when mode changes (but don't trigger spot fetch)
        if (modeChanged) {
            this.updateZoomMenu();
        }

        // Only band changes should trigger spot fetching
        // Mode changes should NOT trigger fetching (mode is just a display filter)
        if (bandChanged) {
            // Invalidate band limits cache (band changed)
            this.bandLimitsCache = null;

            // Only reset waiting state if we've already received initial data
            // This prevents treating initial parameter setting as a "change"
            if (this.lastBand !== null) {
                // Band changed after initial load, reset waiting state
                this.waitingForData = true;
                this.dataReceived = false;
                // Reset spot info text to force update
                this.lastSpotInfoText = null;
                // Reset relevant spots array and index
                this.relevantSpots = [];
                this.currentSpotIndex = 0;
                if (this.spotInfoDiv) {
                    this.spotInfoDiv.innerHTML = '&nbsp;';
                }
                // Reset timer for the new band fetch
                this.operationStartTime = Date.now();
            }
        }

        this.lastBand = currentBand;
        this.lastMode = currentMode;

        return bandChanged;
    },

    // Get cached middle frequency to avoid repeated DOM access and parsing
    // Always returns frequency in kHz for internal calculations
    getCachedMiddleFreq: function() {
        // Use committed frequency values (only updated on blur/Enter) to prevent shifting while typing
        // Strategy:
        // 1. If we have a valid committed frequency from this session, always use last VALID commit
        // 2. Otherwise use real-time values (initial load before any commits)

        var hasValidCommit = this.lastValidCommittedFreq !== null;

        var currentInput, currentUnit;

        if (hasValidCommit) {
            // After first valid commit, always use the LAST VALID committed values
            // This keeps the waterfall stable even when user deletes and starts typing
            currentInput = this.lastValidCommittedFreq;
            currentUnit = this.lastValidCommittedUnit || 'kHz';
        } else {
            // Before first valid commit (initial load), use real-time values
            currentInput = this.$freqCalculated.val();
            currentUnit = this.$qrgUnit.text() || 'kHz';
        }

        // Invalidate cache if input OR unit changes
        if (this.lastFreqInput !== currentInput || this.lastQrgUnit !== currentUnit) {
            this.lastFreqInput = currentInput;
            this.lastQrgUnit = currentUnit;

            // Convert to kHz using utility function
            this.cache.middleFreq = DX_WATERFALL_UTILS.frequency.convertToKhz(currentInput, currentUnit);
        }
        return this.cache.middleFreq;
    },

    // Force invalidate frequency cache - called when CAT updates frequency
    invalidateFrequencyCache: function() {
        // Don't invalidate cache if user is actively editing frequency
        if (this.userEditingFrequency) {
            return;
        }

        var oldFreq = this.cache.middleFreq;

        // If we're still waiting for CAT frequency on initial load, cancel the wait
        if (this.waitingForCATFrequency) {
            if (this.catFrequencyWaitTimer) {
                clearTimeout(this.catFrequencyWaitTimer);
                this.catFrequencyWaitTimer = null;
            }
            this.waitingForCATFrequency = false;
        }

        // Clear CAT tuning flags since frequency is now confirmed by CAT system
        this.catTuning = false;
        this.frequencyChanging = false; // Also clear frequency changing flag
        this.catTuningStartTime = null; // Clear timeout tracking
        this.spotNavigating = false; // Clear spot navigation flag on successful CAT completion

        // Update zoom menu immediately after clearing flags
        if (this.zoomMenuDiv) {
            this.updateZoomMenu();
        }

        // Only set completion overlay if CAT is actually available (this function was called due to CAT)
        var catAvailable = (typeof dxwaterfall_allowcat !== 'undefined' && dxwaterfall_allowcat !== null &&
                           typeof dxwaterfall_caturl !== 'undefined' && dxwaterfall_caturl !== null &&
                           dxwaterfall_allowcat && dxwaterfall_caturl !== "");

        if (catAvailable) {
            // Set a temporary overlay flag to keep message visible while marker moves
            this.showingCompletionOverlay = true;
        }

        // Force immediate cache refresh and visual update to move marker
        this.lastFrequencyRefreshTime = 0; // Reset throttle to allow immediate refresh
        this.refreshFrequencyCache();

        // Force immediate refresh to draw marker at new position (with overlay still visible)
        if (this.canvas && this.ctx) {
            this.refresh();
        }

        // Clear the overlay after marker has had time to move (only if we set it)
        if (catAvailable) {
            var self = this;
            setTimeout(function() {
                self.showingCompletionOverlay = false;
                // Force final refresh to show normal waterfall without overlay
                if (self.canvas && self.ctx) {
                    self.refresh();
                }
            }, 400); // Wait for marker to settle

            // Additional safety clearing
            setTimeout(function() {
                self.showingCompletionOverlay = false;
                if (self.canvas && self.ctx) {
                    self.refresh();
                }
            }, 600);
        }


        // Verify the new frequency after cache clear
        var newFreq = this.getCachedMiddleFreq();

        // Force an immediate refresh to ensure visual update
        if (this.canvas && this.ctx) {
            this.refresh();
        }
    },

    // Periodically refresh frequency cache to ensure display stays current
    refreshFrequencyCache: function() {
        // Don't interfere during waterfall-initiated frequency changes or when user is editing
        if (this.frequencyChanging || this.userEditingFrequency) {
            return;
        }

        // Throttle to prevent excessive calls (max once per 200ms)
        var currentTime = Date.now();
        if (currentTime - this.lastFrequencyRefreshTime < DX_WATERFALL_CONSTANTS.DEBOUNCE.FREQUENCY_CACHE_REFRESH_MS) {
            return;
        }
        this.lastFrequencyRefreshTime = currentTime;

        // Get current DOM frequency
        var currentInput = this.$freqCalculated.val();
        if (!currentInput || currentInput === '') {
            return;
        }

        var freqValue = parseFloat(currentInput) || 0;
        if (freqValue <= 0) {
            return;
        }

        var currentUnit = this.$qrgUnit.text() || 'kHz';

        // Convert to kHz using utility function
        var currentFreqFromDOM = DX_WATERFALL_UTILS.frequency.convertToKhz(freqValue, currentUnit);

        // If cache is outdated, refresh it (but only if not during waterfall operations)
        if (!this.cache.middleFreq || Math.abs(currentFreqFromDOM - this.cache.middleFreq) > 0.1) {
            // Clear all frequency-related cache to ensure fresh read
            this.cache.middleFreq = null;
            this.lastFreqInput = null;
            this.lastQrgUnit = null;
            this.lastMarkerFreq = undefined;

            // Directly set the new frequency from DOM calculation
            this.cache.middleFreq = currentFreqFromDOM;

            // Also update committed frequency values to prevent getCachedMiddleFreq() conflicts
            // This ensures that getCachedMiddleFreq() will use the updated frequency instead of old committed values
            this.lastValidCommittedFreq = currentInput;
            this.lastValidCommittedUnit = currentUnit;
            this.committedFreqInput = currentInput;
            this.committedQrgUnit = currentUnit;

            // Check if there's a relevant spot at the new frequency and populate form
            this.checkAndPopulateSpotAtFrequency();
        }
    },

    // Check if there's a relevant spot at current frequency and populate the QSO form
    checkAndPopulateSpotAtFrequency: function() {
        // Get spot info at current frequency
        var spotInfo = this.getSpotInfo();

        if (spotInfo && spotInfo.callsign) {
            // Clear the form first
            DX_WATERFALL_UTILS.qsoForm.clearForm();

            // Populate form with spot data after a short delay
            setTimeout(function() {
                if (typeof DX_WATERFALL_UTILS !== 'undefined' &&
                    typeof DX_WATERFALL_UTILS.qsoForm !== 'undefined' &&
                    typeof DX_WATERFALL_UTILS.qsoForm.populateFromSpot === 'function') {
                    DX_WATERFALL_UTILS.qsoForm.populateFromSpot(spotInfo, true);
                }
            }, 100);
        }
    },

    // Get cached pixels per kHz to avoid repeated mode checking
    getCachedPixelsPerKHz: function() {
        var currentMode = this.getCurrentMode();
        if (this.lastModeForCache !== currentMode) {
            this.lastModeForCache = currentMode;
            this.cachedPixelsPerKHz = this.getPixelsPerKHz();
        }
        return this.cachedPixelsPerKHz;
    },

    // Get cached bandwidth parameters to avoid repeated calculations
    getCachedBandwidthParams: function(mode, frequency) {
        // Use floor of frequency to reduce cache misses for small frequency changes
        var freqKey = Math.floor(frequency);

        // Check if cache is valid (same mode and frequency bucket)
        if (this.cachedBandwidthParams &&
            this.cachedBandwidthParams.mode === mode &&
            this.cachedBandwidthParams.freqKey === freqKey) {
            return this.cachedBandwidthParams.params;
        }

        // Cache miss - calculate and store
        this.cachedBandwidthParams = {
            mode: mode,
            freqKey: freqKey,
            params: this.getBandwidthParams(mode, frequency)
        };
        return this.cachedBandwidthParams.params;
    },

    // Optimized FT8 frequency checking using cached array
    isFT8Frequency: function(frequency) {
        return this.ft8Frequencies.some(function(freq) {
            return Math.abs(frequency - freq) < 1; // Within 1 kHz tolerance
        });
    },

    // Map continent to IARU region
    continentToRegion: function(continent) {
        switch(continent) {
            case 'EU': // Europe
            case 'AF': // Africa
                return 'R1';
            case 'NA': // North America
            case 'SA': // South America
                return 'R2';
            case 'AS': // Asia
            case 'OC': // Oceania
                return 'R3';
            case 'AN': // Antarctica
                return 'R1'; // Default to R1 for Antarctica
            default:
                return 'R1'; // Default to R1 if unknown
        }
    },

    // Load band plans from JSON file
    loadBandPlans: function() {
        var self = this;

        // Check if already loaded (or attempted to load)
        if (this.bandPlans !== null) {
            return;
        }

        // Mark as loading to prevent repeated attempts
        this.bandPlans = 'loading';

        // Check if base_url is defined
        var baseUrl = (typeof base_url !== 'undefined') ? base_url : '';
        if (!baseUrl) {
            this.bandPlans = {}; // Set to empty object
            return;
        }

        $.ajax({
            url: baseUrl + DX_WATERFALL_CONSTANTS.IARU_BANDPLANS_PATH,
            type: 'GET',
            dataType: 'json',
            cache: true, // Cache the band plans
            success: function(data) {
                self.bandPlans = data;
                // Invalidate cache to trigger redraw with band limits
                self.bandLimitsCache = null;
            },
            error: function(xhr, status, error) {
                self.bandPlans = {}; // Set to empty object to prevent repeated attempts
            }
        });
    },

    // Get band limits for current band and region
    getBandLimits: function() {
        var currentBand = this.getCurrentBand();
        var currentRegion = this.continentToRegion(this.currentContinent);

        // Check if we need to update cache
        if (this.bandLimitsCache &&
            this.bandLimitsCache.band === currentBand &&
            this.bandLimitsCache.region === currentRegion) {
            return this.bandLimitsCache.limits;
        }

        // Load band plans if not loaded yet
        if (this.bandPlans === null) {
            this.loadBandPlans();
            return null; // Will be available on next refresh
        }

        // Check if still loading
        if (this.bandPlans === 'loading') {
            return null;
        }

        // Get limits from band plans
        var limits = null;
        if (this.bandPlans && this.bandPlans[currentRegion]) {
            if (this.bandPlans[currentRegion][currentBand]) {
                var bandData = this.bandPlans[currentRegion][currentBand];
                limits = {
                    start_khz: bandData.start_hz / 1000, // Convert Hz to kHz
                    end_khz: bandData.end_hz / 1000       // Convert Hz to kHz
                };
            }
        }

        // Cache the result
        this.bandLimitsCache = {
            band: currentBand,
            region: currentRegion,
            limits: limits
        };

        return limits;
    },

    // Check if frequency is more than 1000 kHz outside current band limits
    // Returns true if band should be recalculated
    isFrequencyFarOutsideBand: function(frequencyKhz) {
        var bandLimits = this.getBandLimits();

        // If no band limits available, don't trigger recalculation
        if (!bandLimits) {
            return false;
        }

        var threshold = DX_WATERFALL_CONSTANTS.THRESHOLDS.BAND_CHANGE_THRESHOLD;
        var lowerThreshold = bandLimits.start_khz - threshold;
        var upperThreshold = bandLimits.end_khz + threshold;

        // Check if frequency is more than threshold outside the band
        if (frequencyKhz < lowerThreshold || frequencyKhz > upperThreshold) {
            return true;
        }

        return false;
    },

    // Recalculate and update band based on frequency
    // Get band name for a given frequency in kHz
    getFrequencyBand: function(frequencyKhz) {
        // Check if frequencyToBand function exists
        if (typeof frequencyToBand !== 'function') {
            return null;
        }

        // Convert kHz to Hz for frequencyToBand function
        var frequencyHz = frequencyKhz * 1000;
        var band = frequencyToBand(frequencyHz);

        return band && band !== '' ? band : null;
    },

    updateBandFromFrequency: function(frequencyKhz) {
        var newBand = this.getFrequencyBand(frequencyKhz);

        if (newBand) {
            // Check if the band exists in the select options
            var bandExists = this.$bandSelect.find('option[value="' + newBand + '"]').length > 0;

            if (bandExists) {
                // Update the band dropdown (in the QSO form)
                this.$bandSelect.val(newBand);
            } else {
                // Band doesn't exist in dropdown, select the first available option as fallback
                var firstOption = this.$bandSelect.find('option:first').val();
                if (firstOption) {
                    this.$bandSelect.val(firstOption);
                }
            }
        }
    },

    // ========================================
    // CANVAS DRAWING AND RENDERING FUNCTIONS
    // ========================================

    // Draw band limit overlays (out-of-band areas)
    drawBandLimits: function() {
        var bandLimits = this.getBandLimits();

        // If no band limits available, don't draw anything
        if (!bandLimits) {
            return;
        }

        var centerX = this.canvas.width / 2;
        var middleFreq = this.getCachedMiddleFreq(); // In kHz
        var pixelsPerKHz = this.getCachedPixelsPerKHz();

        var bandStart = bandLimits.start_khz;
        var bandEnd = bandLimits.end_khz;

        // Calculate pixel positions for band edges
        var startX = this.freqToPixel(bandStart, centerX, middleFreq, pixelsPerKHz);
        var endX = this.freqToPixel(bandEnd, centerX, middleFreq, pixelsPerKHz);

        // Cache canvas dimensions for performance
        var canvasWidth = this.canvas.width;
        var canvasHeight = this.canvas.height;

        // Draw left out-of-band area (below band start)
        if (startX > 0) {
            this.drawOutOfBandArea(0, 0, Math.min(startX, canvasWidth), canvasHeight, startX / 2, 'right');
        }

        // Draw right out-of-band area (above band end)
        if (endX < canvasWidth) {
            var rightStartX = Math.max(0, endX);
            var rightWidth = canvasWidth - rightStartX;
            this.drawOutOfBandArea(rightStartX, 0, rightWidth, canvasHeight, rightStartX + (rightWidth / 2), 'left');
        }
    },

    // Helper function to draw out-of-band areas with text
    drawOutOfBandArea: function(x, y, width, height, textCenterX, borderSide) {
        // Clip to the area to keep stripes inside
        this.ctx.save();
        this.ctx.beginPath();
        this.ctx.rect(x, y, width, height);
        this.ctx.clip();

        // Draw dark grey background
        this.ctx.fillStyle = DX_WATERFALL_CONSTANTS.COLORS.OVERLAY_DARK_GREY;
        this.ctx.fillRect(x, y, width, height);

        // Draw red diagonal stripes pattern (clipped to area)
        this.ctx.strokeStyle = 'rgba(255, 0, 0, 0.3)';
        this.ctx.lineWidth = 2;
        var stripeSpacing = 15; // Distance between stripes

        // Calculate stripe positions to cover the area
        var maxDistance = Math.sqrt(width * width + height * height);
        for (var i = -maxDistance; i < maxDistance; i += stripeSpacing) {
            this.ctx.beginPath();
            this.ctx.moveTo(x + i, y);
            this.ctx.lineTo(x + i + height, y + height);
            this.ctx.stroke();
        }

        this.ctx.restore();

        // Draw red border only on the side facing the valid band
        this.ctx.strokeStyle = 'rgba(255, 0, 0, 0.6)';
        this.ctx.lineWidth = 3;
        this.ctx.beginPath();
        if (borderSide === 'right') {
            // Border on the right edge (for left out-of-band area)
            this.ctx.moveTo(x + width, y);
            this.ctx.lineTo(x + width, y + height);
        } else if (borderSide === 'left') {
            // Border on the left edge (for right out-of-band area)
            this.ctx.moveTo(x, y);
            this.ctx.lineTo(x, y + height);
        }
        this.ctx.stroke();

        // Add "OUT OF BANDPLAN" text if there's enough space
        if (width > DX_WATERFALL_CONSTANTS.CANVAS.MIN_TEXT_AREA_WIDTH) {
            // Calculate vertical center of waterfall area (excluding ruler at bottom)
            var waterfallHeight = height - DX_WATERFALL_CONSTANTS.CANVAS.RULER_HEIGHT;
            var textCenterY = y + (waterfallHeight / 2);
            this.setCanvasTextStyle(this.ctx, DX_WATERFALL_CONSTANTS.FONTS.OUT_OF_BAND, DX_WATERFALL_CONSTANTS.COLORS.MESSAGE_TEXT_WHITE, 'center', 'middle');
            this.ctx.fillText(lang_dxwaterfall_out_of_bandplan, textCenterX, textCenterY);
        }
    },

    // Helper function to draw invalid frequency area (< 0 Hz)
    drawInvalidArea: function(x, y, width, height, textCenterX) {
        this.ctx.fillStyle = DX_WATERFALL_CONSTANTS.COLORS.OVERLAY_DARK_GREY;
        this.ctx.fillRect(x, y, width, height);

        // Add "INVALID" warning text if there's enough space
        if (width > DX_WATERFALL_CONSTANTS.CANVAS.MIN_TEXT_AREA_WIDTH) {
            // Calculate vertical center of waterfall area (excluding ruler at bottom)
            var waterfallHeight = height - DX_WATERFALL_CONSTANTS.CANVAS.RULER_HEIGHT;
            var textCenterY = y + (waterfallHeight / 2);
            this.setCanvasTextStyle(this.ctx, 'bold 14px "Consolas", "Courier New", monospace', DX_WATERFALL_CONSTANTS.COLORS.MESSAGE_TEXT_WHITE, 'center', 'middle');
            this.ctx.fillText(lang_dxwaterfall_invalid, textCenterX, textCenterY);
        }
    },

    // Helper function to set canvas text styling
    setCanvasTextStyle: function(ctx, font, color, align, baseline) {
        ctx.font = font || DX_WATERFALL_CONSTANTS.FONTS.SMALL_MONO;
        ctx.fillStyle = color || DX_WATERFALL_CONSTANTS.COLORS.MESSAGE_TEXT_WHITE;
        ctx.textAlign = align || 'center';
        ctx.textBaseline = baseline || 'middle';
    },

    // Helper function to convert frequency to pixel position
    freqToPixel: function(frequency, centerX, middleFreq, pixelsPerKHz) {
        return centerX + ((frequency - middleFreq) * pixelsPerKHz);
    },

    // ========================================
    // DATA FETCHING AND AJAX FUNCTIONS
    // ========================================

    /**
     * Fetch DX spots from the server with debouncing
     * Retrieves spots based on current band, mode, and continent settings
     * @param {boolean} immediate - If true, skip debouncing and fetch immediately
     * @param {boolean} userInitiated - If true, this is a user-initiated fetch (show loading indicator)
     * @returns {void}
     */
    fetchDxSpots: function(immediate, userInitiated) {
        var self = this;

        // Clear any existing debounce timer
        if (this.fetchDebounceTimer) {
            clearTimeout(this.fetchDebounceTimer);
            this.fetchDebounceTimer = null;
        }

        // If not immediate, debounce the request
        if (!immediate) {
            this.fetchDebounceTimer = setTimeout(function() {
                self.fetchDebounceTimer = null;
                self.fetchDxSpots(true, userInitiated); // Pass userInitiated through
            }, this.fetchDebounceMs);
            return;
        }

        // Set userInitiatedFetch flag
        this.userInitiatedFetch = userInitiated === true;

        var band = this.getCurrentBand();

        // If band is invalid or empty, use a default band for initial fetch
        if (!band || band === '' || band.toLowerCase() === 'select') {
            band = '40m'; // Default to 40m for initial fetch
        }

        var mode = "All"; // Fetch all modes
        var age = 60; // minutes
        var de = this.currentContinent; // Use current continent (may have been cycled)

        // On FIRST fetch only, use the continent from PHP options
        if (!this.initialLoadDone && typeof dxwaterfall_decont !== "undefined" && dxwaterfall_decont != null) {
            de = dxwaterfall_decont;
            this.currentContinent = de;
            this.initialLoadDone = true; // Mark that we've done the initial load
        }

        // Check if dxwaterfall_maxage is defined
        if (typeof dxwaterfall_maxage !== "undefined" && dxwaterfall_maxage != null) {
            age = dxwaterfall_maxage;
        }

        // Store current settings
        this.currentMaxAge = age;

        // Check if a fetch is already in progress for a DIFFERENT band
        // If band changed, we need to allow the new fetch (cancel the old one by letting it complete)
        if (this.fetchInProgress) {
            // If the band is different from what's being fetched, allow this fetch to proceed
            // The old fetch will complete but we'll overwrite with new data
            if (this.lastFetchBand && this.lastFetchBand !== band) {
                // Don't return - let the new fetch proceed
            } else {
                return;
            }
        }

        // Check if we recently fetched the same data (band, continent, age)
        // Skip if we fetched the same parameters within the fetch interval
        if (this.lastFetchBand === band &&
            this.lastFetchContinent === de &&
            this.lastFetchAge === age &&
            this.lastUpdateTime) {
            var timeSinceLastFetch = Date.now() - this.lastUpdateTime.getTime();
            if (timeSinceLastFetch < DX_WATERFALL_CONSTANTS.DEBOUNCE.DX_SPOTS_FETCH_INTERVAL_MS) {
                return;
            }
        }

        // Check if base_url is defined, if not use a default or skip
        var baseUrl = (typeof base_url !== 'undefined') ? base_url : '';
        if (!baseUrl) {
            return;
        }

        var ajaxUrl = baseUrl + 'index.php/dxcluster/spots/' + band + '/' + age + '/' + de + '/' + mode;

        // Mark fetch as in progress
        this.fetchInProgress = true;

        // Reset timer only for user-initiated fetches (band changes, continent changes)
        // Don't reset timer for background spot refreshes
        if (this.userInitiatedFetch) {
            this.operationStartTime = Date.now();
        }

        $.ajax({
            url: ajaxUrl,
            type: 'GET',
            dataType: 'json',
            timeout: DX_WATERFALL_CONSTANTS.AJAX.TIMEOUT_MS,
			cache: false,
            success: function(data) {
                // Clear fetch in progress flag
                self.fetchInProgress = false;

                if (data && !data.error) {
                    // Enrich spots with park references once during fetch
                    // This prevents recalculating them multiple times
                    for (var i = 0; i < data.length; i++) {
                        var parkRefs = DX_WATERFALL_UTILS.parkRefs.extract(data[i]);
                        data[i].sotaRef = parkRefs.sotaRef;
                        data[i].potaRef = parkRefs.potaRef;
                        data[i].iotaRef = parkRefs.iotaRef;
                        data[i].wwffRef = parkRefs.wwffRef;
                    }

                    self.dxSpots = data;
                    self.totalSpotsCount = data.length;
                    self.dataReceived = true; // Mark that we've received data
                    self.waitingForData = false; // No longer waiting
                    self.userInitiatedFetch = false; // Clear user-initiated flag
                    self.lastUpdateTime = new Date(); // Record update time
                    // Track fetch parameters to prevent duplicate fetches
                    self.lastFetchBand = band;
                    self.lastFetchContinent = de;
                    self.lastFetchAge = age;
                    self.collectAllBandSpots(true); // Update band spot collection for navigation (force after data fetch)
                    self.collectSmartHunterSpots(); // Update smart hunter spots collection

                    // Populate menu after first successful data fetch
                    self.updateZoomMenu();

                    // Check if we're standing on a spot and auto-populate QSO form
                    self.checkAndPopulateSpotAtFrequency();
                } else {
                    // No spots or error in response
                    self.dxSpots = [];
                    self.totalSpotsCount = 0;
                    self.dataReceived = true; // Mark as received even if empty
                    self.waitingForData = false; // Stop waiting
                    self.userInitiatedFetch = false; // Clear user-initiated flag
                    self.allBandSpots = []; // Clear band spots
                    self.currentBandSpotIndex = 0;
                    self.smartHunterSpots = []; // Clear smart hunter spots
                    self.currentSmartHunterIndex = 0;

                    // Populate menu even if no spots (so user can still interact)
                    self.updateZoomMenu();
                }
            },
            error: function(xhr, status, error) {
                // Clear fetch in progress flag
                self.fetchInProgress = false;
                self.userInitiatedFetch = false; // Clear user-initiated flag

                self.dxSpots = [];
                self.totalSpotsCount = 0;
                self.dataReceived = true; // Mark as received to stop waiting state
                self.waitingForData = false; // Stop waiting
                self.allBandSpots = []; // Clear band spots
                self.currentBandSpotIndex = 0;
                self.smartHunterSpots = []; // Clear smart hunter spots
                self.currentSmartHunterIndex = 0;

                // Populate menu even after error (so user can still interact)
                self.updateZoomMenu();
            }
        });
    },

    // Get current band from form or default to 20m
    getCurrentBand: function() {
        // Try to get band from form - adjust selector based on your HTML structure
        var band = this.$bandSelect.val() || '20m';
        return band;
    },

    // Get current mode from form or default to All
    getCurrentMode: function() {
        // Try to get mode from form - adjust selector based on your HTML structure
        var mode = this.$modeSelect.val() || 'All';
        return mode;
    },

    // Quick dimension update to prevent stretching - no redraw
    updateDimensions: function() {
        if (this.canvas) {
            var currentWidth = this.canvas.offsetWidth;
            var currentHeight = this.canvas.offsetHeight;

            if (this.canvas.width !== currentWidth || this.canvas.height !== currentHeight) {
                this.canvas.width = currentWidth;
                this.canvas.height = currentHeight;
                // Reset noise cache when dimensions change
                this.cache.noise1 = null;
                this.cache.noise2 = null;
            }
        }
    },

    // Generate and cache static noise patterns for animation
    generateCachedNoise: function() {
        var width = this.canvas.width;
        var height = this.canvas.height;

        // Only regenerate if canvas dimensions changed or first time
        if (this.cache.noiseWidth !== width || this.cache.noiseHeight !== height || !this.cache.noise1) {
            this.cache.noiseWidth = width;
            this.cache.noiseHeight = height;

            // Generate first noise pattern
            var imageData1 = this.ctx.createImageData(width, height);
            var data1 = imageData1.data;

            for (var i = 0; i < data1.length; i += 4) {
                // Start with dark background using constants
                var baseR = DX_WATERFALL_CONSTANTS.COLORS.STATIC_NOISE_RGB.R;
                var baseG = DX_WATERFALL_CONSTANTS.COLORS.STATIC_NOISE_RGB.G;
                var baseB = DX_WATERFALL_CONSTANTS.COLORS.STATIC_NOISE_RGB.B;

                // Generate random noise values
                var noise = Math.random() * 80; // 0-80 intensity

                // Add subtle blueish noise tint to the dark background
                data1[i] = baseR + (noise * 0.3);     // Red channel
                data1[i + 1] = baseG + (noise * 0.5); // Green channel
                data1[i + 2] = baseB + (noise * 0.4); // Blue channel
                data1[i + 3] = 255; // Fully opaque
            }
            this.cache.noise1 = imageData1;

            // Generate second noise pattern
            var imageData2 = this.ctx.createImageData(width, height);
            var data2 = imageData2.data;

            for (var i = 0; i < data2.length; i += 4) {
                // Start with dark background using constants
                var baseR = DX_WATERFALL_CONSTANTS.COLORS.STATIC_NOISE_RGB.R;
                var baseG = DX_WATERFALL_CONSTANTS.COLORS.STATIC_NOISE_RGB.G;
                var baseB = DX_WATERFALL_CONSTANTS.COLORS.STATIC_NOISE_RGB.B;

                // Generate random noise values
                var noise = Math.random() * 80; // 0-80 intensity

                // Add subtle blueish noise tint to the dark background
                data2[i] = baseR + (noise * 0.3);     // Red channel
                data2[i + 1] = baseG + (noise * 0.5); // Green channel
                data2[i + 2] = baseB + (noise * 0.4); // Blue channel
                data2[i + 3] = 255; // Fully opaque
            }
            this.cache.noise2 = imageData2;
        }
    },

    // Draw static noise background (cached and animated)
    drawStaticNoise: function() {
        // Generate cached noise only if needed (dimensions changed or first time)
        this.generateCachedNoise();

        // Alternate between noise patterns for animation effect
        var noiseToUse = (this.cache.currentNoiseFrame === 0) ? this.cache.noise1 : this.cache.noise2;
        this.ctx.putImageData(noiseToUse, 0, 0);

        // Switch to next frame for next refresh
        this.cache.currentNoiseFrame = 1 - this.cache.currentNoiseFrame; // Toggle between 0 and 1
    },

    // Draw Wavelog logo centered above message
    drawWavelogLogo: function(centerX, logoY, opacity) {
        var self = this;

        // opacity: 0.0 to 1.0 (optional, defaults to 1.0 for full opacity)
        if (typeof opacity === 'undefined') {
            opacity = 1.0;
        }

        // Ensure canvas context exists before proceeding
        if (!this.ctx || !this.canvas) {
            return;
        }

        // Create image object if it doesn't exist or reuse existing one
        if (!this.wavelogLogoImage) {
            this.wavelogLogoImage = new Image();
            this.wavelogLogoImage.onload = function() {
                self.wavelogLogoImage.loaded = true;
            };
            this.wavelogLogoImage.onerror = function() {
                console.error('Failed to load Wavelog logo');
            };
            // Get base URL from global variable or construct it
            var baseUrl = (typeof base_url !== 'undefined') ? base_url : '';
            this.wavelogLogoImage.src = baseUrl + DX_WATERFALL_CONSTANTS.LOGO_FILENAME;
        }

        // Draw logo if it's loaded, ensuring it stays within canvas bounds
        if (this.wavelogLogoImage.loaded) {
            var logoWidth = 140;
            var logoHeight = this.wavelogLogoImage.height * (logoWidth / this.wavelogLogoImage.width);
            var logoX = centerX - (logoWidth / 2);

            // Clamp logo position to stay within canvas bounds
            logoX = Math.max(0, Math.min(logoX, this.canvas.width - logoWidth));
            logoY = Math.max(0, Math.min(logoY, this.canvas.height - logoHeight));

            // Save canvas state to ensure logo doesn't affect other drawings
            this.ctx.save();
            this.ctx.globalAlpha = opacity;
            this.ctx.drawImage(this.wavelogLogoImage, logoX, logoY, logoWidth, logoHeight);
            this.ctx.restore();
        }
    },    // Display waiting message with black overlay and spinner
    displayWaitingMessage: function() {
        if (!this.canvas) {
            return;
        }

        // Hide zoom menu while waiting for data
        if (this.zoomMenuDiv) {
            this.zoomMenuDiv.innerHTML = '';
        }

        // Update canvas dimensions to match current CSS dimensions
        this.updateDimensions();

        // Draw semi-transparent black overlay over current content
        DX_WATERFALL_UTILS.drawing.drawOverlay(this.ctx, this.canvas.width, this.canvas.height, 'OVERLAY_BACKGROUND');

        // Calculate center position
        var centerX = this.canvas.width / 2;
        var centerY = this.canvas.height / 2;

        // Draw pulsing Wavelog logo above the message
        var logoY = centerY - DX_WATERFALL_CONSTANTS.CANVAS.LOGO_OFFSET_Y;
        // Calculate pulsing opacity (0.5 to 1.0 for smooth fade effect)
        var pulseOpacity = 0.75 + 0.25 * Math.sin(Date.now() / 300);
        this.drawWavelogLogo(centerX, logoY, pulseOpacity);

        // Text position (moved down lower for more space)
        var textY = centerY + DX_WATERFALL_CONSTANTS.CANVAS.TEXT_OFFSET_Y;

        // Draw "Waiting for DX Cluster data..." message
        DX_WATERFALL_UTILS.drawing.drawCenteredText(this.ctx, lang_dxwaterfall_waiting_data, centerX, textY, 'FREQUENCY_CHANGE', 'MESSAGE_TEXT_WHITE');

        // Reset opacity
        this.ctx.globalAlpha = 1.0;
    },

    // Display frequency change message with current waterfall as background
    displayChangingFrequencyMessage: function(message, color) {
        if (!this.canvas) {
            return;
        }

        // Default values for backward compatibility
        var displayMessage = message || 'Changing frequency...';
        var displayColor = color || 'MESSAGE_TEXT_YELLOW';

        // Update canvas dimensions to match current CSS dimensions
        this.updateDimensions();

        // Use utility function for overlay message
        DX_WATERFALL_UTILS.drawing.drawOverlayMessage(this.canvas, this.ctx, displayMessage, displayColor);
    },

    // Get pixels per kHz based on current mode and zoom level
    getPixelsPerKHz: function() {
        // Calculate pixels per kHz based on zoom level with better scaling
        // Level-specific scaling for better usability
        var pixelsPerKHz;
        switch(this.currentZoomLevel) {
            case 1:
                pixelsPerKHz = 4;  // ±25 kHz range
                break;
            case 2:
                pixelsPerKHz = 8;  // ±12.5 kHz range
                break;
            case 3:
                pixelsPerKHz = 20; // ±5 kHz range (default, more zoomed)
                break;
            case 4:
                pixelsPerKHz = 32; // ±3.125 kHz range
                break;
            case 5:
                pixelsPerKHz = 50; // ±2 kHz range (max zoom)
                break;
            default:
                pixelsPerKHz = 20; // Default to level 3
        }

        return pixelsPerKHz;
    },

    // Draw frequency ruler at bottom
    drawFrequencyRuler: function() {
        var rulerY = this.canvas.height - DX_WATERFALL_CONSTANTS.CANVAS.RULER_HEIGHT;
        var centerX = this.canvas.width / 2;
        var middleFreq = this.getCachedMiddleFreq(); // Use cached frequency
        var currentMode = this.getCurrentMode().toLowerCase();

		// Draw background bar for ruler
        this.ctx.fillStyle = DX_WATERFALL_CONSTANTS.COLORS.RULER_BACKGROUND;
        this.ctx.fillRect(0, rulerY, this.canvas.width, DX_WATERFALL_CONSTANTS.CANVAS.RULER_HEIGHT);

        // Use consistent scale for all modes
        var pixelsPerKHz = this.getCachedPixelsPerKHz(); // Use cached scaling
        var tickInterval = 1; // 1 kHz intervals for all modes
        var majorTickInterval = 5; // 5 kHz major ticks for all modes

        // Draw main ruler line
        this.ctx.strokeStyle = DX_WATERFALL_CONSTANTS.COLORS.RULER_LINE;
        this.ctx.lineWidth = 2;
        this.ctx.beginPath();
        this.ctx.moveTo(0, rulerY);
        this.ctx.lineTo(this.canvas.width, rulerY);
        this.ctx.stroke();

        // Calculate frequency range based on canvas width
        var halfWidthKHz = (this.canvas.width / 2) / pixelsPerKHz;
        var startFreq = middleFreq - halfWidthKHz;
        var endFreq = middleFreq + halfWidthKHz;

        // Draw ticks and labels
        this.ctx.lineWidth = 1;
        this.ctx.fillStyle = DX_WATERFALL_CONSTANTS.COLORS.RULER_TEXT;
        this.ctx.font = DX_WATERFALL_CONSTANTS.FONTS.RULER;
        this.ctx.textAlign = 'center';
        this.ctx.textBaseline = 'top';

        // Calculate tick positions
        var startTick = Math.floor(startFreq / tickInterval) * tickInterval;
        var endTick = Math.ceil(endFreq / tickInterval) * tickInterval;

        // For zoom level 1, track which major tick we're on to skip every other label
        var majorTickCounter = 0;

        for (var freq = startTick; freq <= endTick; freq += tickInterval) {
            // Round to avoid floating point precision issues
            freq = Math.round(freq * 10) / 10;

            // Skip frequencies <= 0 (not physically valid)
            if (freq <= 0) {
                continue;
            }

            var x = centerX + (freq - middleFreq) * pixelsPerKHz;

            if (x >= 0 && x <= this.canvas.width) {
                var isMajorTick = (Math.abs(freq % majorTickInterval) < DX_WATERFALL_CONSTANTS.THRESHOLDS.MAJOR_TICK_TOLERANCE);
                var tickHeight = isMajorTick ? 10 : 5;

                // Draw tick (only below the ruler line)
                this.ctx.strokeStyle = DX_WATERFALL_CONSTANTS.COLORS.RULER_LINE;
                this.ctx.beginPath();
                this.ctx.moveTo(x, rulerY);
                this.ctx.lineTo(x, rulerY + tickHeight);
                this.ctx.stroke();

                // Draw frequency label for major ticks
                if (isMajorTick) {
                    // For zoom level 1, only show every 2nd label to reduce clutter
                    var shouldShowLabel = true;
                    if (this.currentZoomLevel === 1) {
                        shouldShowLabel = (majorTickCounter % 2 === 0);
                        majorTickCounter++;
                    }

                    if (shouldShowLabel) {
                        var labelText = freq.toString();
                        this.ctx.font = DX_WATERFALL_CONSTANTS.FONTS.RULER;
                        this.ctx.fillText(labelText, x, rulerY + 14);
                    }
                }
            }
        }

        // Draw grey overlay on the left side for frequencies <= 0 (invalid range)
        if (startFreq <= 0) {
            var zeroFreqX = this.freqToPixel(0, centerX, middleFreq, pixelsPerKHz);
            if (zeroFreqX > 0) {
                this.drawInvalidArea(0, 0, zeroFreqX, this.canvas.height, zeroFreqX / 2);
            }
        }
    },

    // Draw receiving bandwidth indicator
    drawReceivingBandwidth: function() {
        var centerX = this.canvas.width / 2;
        var rulerY = this.canvas.height - DX_WATERFALL_CONSTANTS.CANVAS.RULER_HEIGHT;
        var middleFreq = this.getCachedMiddleFreq(); // Use cached frequency
        var pixelsPerKHz = this.getCachedPixelsPerKHz(); // Use cached scaling
        var currentMode = this.getCurrentMode().toLowerCase();

        // Use the same bandwidth logic as DX spots for consistency (with caching)
        var bandwidthParams = this.getCachedBandwidthParams(currentMode, middleFreq);
        var bandwidthKHz = bandwidthParams.bandwidth;
        var offsetKHz = bandwidthParams.offset;

        // Only draw bandwidth indicator for phone and CW modes (not for digital modes)
        var modeCategory = DX_WATERFALL_UTILS.modes.getModeCategory(currentMode);
        if (modeCategory !== 'phone' && modeCategory !== 'cw') {
            return; // No bandwidth indicator for digital modes
        }

        // Calculate pixel positions
        var bandwidthPixels = bandwidthKHz * pixelsPerKHz;
        var offsetPixels = offsetKHz * pixelsPerKHz;
        var startX = centerX + offsetPixels - (bandwidthPixels / 2);
        var endX = startX + bandwidthPixels;

        // Ensure we stay within canvas bounds
        startX = Math.max(0, startX);
        endX = Math.min(this.canvas.width, endX);

        if (startX < endX) {
            // Draw semi-transparent rectangle from red line to ruler
            this.ctx.fillStyle = DX_WATERFALL_CONSTANTS.COLORS.BANDWIDTH_INDICATOR;
            this.ctx.fillRect(startX, 0, endX - startX, rulerY);
        }
    },

    // Draw red center line marker
    drawCenterMarker: function() {
        var centerX = this.canvas.width / 2;
        var rulerY = this.canvas.height - DX_WATERFALL_CONSTANTS.CANVAS.RULER_HEIGHT;

        // Draw red line from top to ruler
        this.ctx.strokeStyle = DX_WATERFALL_CONSTANTS.COLORS.CENTER_MARKER;
        this.ctx.lineWidth = 2;
        this.ctx.beginPath();
        this.ctx.moveTo(centerX, 0);
        this.ctx.lineTo(centerX, rulerY+5);
        this.ctx.stroke();

    },

    /**
     * Get bandwidth parameters for a given mode and frequency
     * Returns the signal bandwidth and frequency offset for proper signal visualization
     *
     * @param {string} mode - The transmission mode (e.g., 'LSB', 'USB', 'FT8', 'CW')
     * @param {number} frequency - Frequency in kHz
     * @returns {{bandwidth: number, offset: number}} Object with bandwidth (in kHz) and offset (in kHz)
     *          - bandwidth: Width of the signal in kHz
     *          - offset: Frequency offset from carrier (negative for LSB, positive for USB, 0 for centered)
     */
    getBandwidthParams: function(mode, frequency) {
        var modeLC = mode.toLowerCase();
        var freq = parseFloat(frequency) || 0;

        // CW mode
        if (DX_WATERFALL_UTILS.modes.isCw(mode)) {
            return { bandwidth: 0.5, offset: 0 }; // 0.5 kHz centered
        }

        // WSJT-X modes
        if (modeLC === 'ft8' || modeLC === 'ft4') {
            return { bandwidth: 3.0, offset: 0 }; // 3.0 kHz centered
        }
        if (modeLC === 'jt65' || modeLC === 'jt65b' || modeLC === 'jt9' || modeLC === 'jt9-1' ||
            modeLC === 'jt6c' || modeLC === 'jt6m') {
            return { bandwidth: 2.0, offset: 0 }; // 2.0 kHz centered
        }
        if (modeLC === 'q65' || modeLC === 'qra64') {
            return { bandwidth: 2.5, offset: 0 }; // 2.5 kHz centered
        }
        if (modeLC === 'fst4' || modeLC === 'fst4w') {
            return { bandwidth: 2.5, offset: 0 }; // 2.5 kHz centered
        }
        if (modeLC === 'wspr') {
            return { bandwidth: 0.2, offset: 0 }; // 0.2 kHz centered (very narrow)
        }
        if (modeLC === 'msk144') {
            return { bandwidth: 2.5, offset: 0 }; // 2.5 kHz centered
        }
        if (modeLC === 'iscat' || modeLC === 'iscat-a' || modeLC === 'iscat-b') {
            return { bandwidth: 2.0, offset: 0 }; // 2.0 kHz centered
        }
        if (modeLC === 'js8' || modeLC === 'jtms') {
            return { bandwidth: 2.5, offset: 0 }; // 2.5 kHz centered
        }

        // PSK modes (all variants narrow)
        if (modeLC.indexOf('psk') !== -1 || modeLC.indexOf('qpsk') !== -1) {
            return { bandwidth: 0.5, offset: 0 }; // 0.5 kHz centered for all PSK
        }

        // RTTY and related
        if (modeLC === 'rtty' || modeLC === 'navtex' || modeLC === 'sitorb') {
            return { bandwidth: 0.5, offset: 0 }; // 0.5 kHz centered
        }

        // Hellschreiber modes
        if (modeLC.indexOf('hell') !== -1 || modeLC.indexOf('fsk') === 0) {
            return { bandwidth: 0.5, offset: 0 }; // 0.5 kHz centered
        }

        // THOR/THROB modes
        if (modeLC.indexOf('thor') !== -1 || modeLC.indexOf('throb') !== -1 || modeLC.indexOf('thrb') !== -1) {
            return { bandwidth: 1.0, offset: 0 }; // 1.0 kHz centered
        }

        // Domino modes
        if (modeLC.indexOf('dom') !== -1) {
            return { bandwidth: 1.0, offset: 0 }; // 1.0 kHz centered
        }

        // VARA modes (wider bandwidth)
        if (modeLC.indexOf('vara') !== -1) {
            return { bandwidth: 2.5, offset: 0 }; // 2.5 kHz centered
        }

        // SCAMP modes
        if (modeLC.indexOf('scamp') !== -1) {
            return { bandwidth: 1.0, offset: 0 }; // 1.0 kHz centered
        }

        // MFSK modes
        if (modeLC.indexOf('mfsk') !== -1) {
            return { bandwidth: 1.0, offset: 0 }; // 1.0 kHz centered
        }

        // FSK modes
        if (modeLC === 'fsk441') {
            return { bandwidth: 2.0, offset: 0 }; // 2.0 kHz centered
        }

        // Other digital modes
        if (modeLC === 'ros') {
            return { bandwidth: 2.5, offset: 0 }; // 2.5 kHz centered
        }
        if (modeLC === 'pkt' || modeLC === 'packet') {
            return { bandwidth: 3.0, offset: 0 }; // 3.0 kHz centered
        }
        if (modeLC === 'sstv') {
            return { bandwidth: 3.0, offset: 0 }; // 3.0 kHz for SSTV
        }

        // Digital voice modes (wider)
        if (modeLC === 'dmr' || modeLC === 'dstar' || modeLC === 'c4fm' ||
            modeLC === 'freedv' || modeLC === 'm17') {
            return { bandwidth: 3.0, offset: 0 }; // 3.0 kHz centered
        }

        // Generic digital fallback
        if (modeLC === 'digi' || modeLC === 'dynamic') {
            return { bandwidth: 2.5, offset: 0 }; // 2.5 kHz centered for generic digital
        }

        // Phone modes with sideband behavior
        if (modeLC === 'lsb') {
            return { bandwidth: DX_WATERFALL_CONSTANTS.SIGNAL_BANDWIDTHS.SSB_KHZ, offset: -DX_WATERFALL_CONSTANTS.SIGNAL_BANDWIDTHS.SSB_OFFSET_KHZ };
        }
        if (modeLC === 'usb') {
            return { bandwidth: DX_WATERFALL_CONSTANTS.SIGNAL_BANDWIDTHS.SSB_KHZ, offset: DX_WATERFALL_CONSTANTS.SIGNAL_BANDWIDTHS.SSB_OFFSET_KHZ };
        }
        if (modeLC === 'ssb' || modeLC === 'phone') {
            // For SSB/phone spots, determine LSB/USB based on frequency using utility
            var ssbMode = DX_WATERFALL_UTILS.modes.determineSSBMode(freq);
            if (ssbMode === 'LSB') {
                return { bandwidth: DX_WATERFALL_CONSTANTS.SIGNAL_BANDWIDTHS.SSB_KHZ, offset: -DX_WATERFALL_CONSTANTS.SIGNAL_BANDWIDTHS.SSB_OFFSET_KHZ };
            } else { // USB
                return { bandwidth: DX_WATERFALL_CONSTANTS.SIGNAL_BANDWIDTHS.SSB_KHZ, offset: DX_WATERFALL_CONSTANTS.SIGNAL_BANDWIDTHS.SSB_OFFSET_KHZ };
            }
        }

        // AM and FM (centered)
        if (modeLC === 'am') {
            return { bandwidth: DX_WATERFALL_CONSTANTS.SIGNAL_BANDWIDTHS.AM_KHZ, offset: 0 };
        }
        if (modeLC === 'fm') {
            return { bandwidth: DX_WATERFALL_CONSTANTS.SIGNAL_BANDWIDTHS.FM_KHZ, offset: 0 };
        }

        // Default fallback (centered SSB-width)
        return { bandwidth: DX_WATERFALL_CONSTANTS.SIGNAL_BANDWIDTHS.SSB_KHZ, offset: 0 };
    },

    // Draw bandwidth indicators for DX spots
    drawDxSpotBandwidths: function() {
        if (!this.dxSpots || this.dxSpots.length === 0) {
            return;
        }

        // Cache frequently accessed properties
        var canvasWidth = this.canvas.width;
        var canvasHeight = this.canvas.height;
        var centerX = canvasWidth / 2;
        var rulerY = canvasHeight - DX_WATERFALL_CONSTANTS.CANVAS.RULER_HEIGHT;
        var rulerBottom = rulerY + DX_WATERFALL_CONSTANTS.CANVAS.RULER_HEIGHT; // Precalculate for loop
        var middleFreq = this.getCachedMiddleFreq(); // Use cached frequency
        var pixelsPerKHz = this.getCachedPixelsPerKHz(); // Use cached scaling

        for (var i = 0, spotsLen = this.dxSpots.length; i < spotsLen; i++) {
            var spot = this.dxSpots[i];
            var spotFreq = parseFloat(spot.frequency);

            if (spotFreq && spot.spotted && spot.mode) {
                // Apply mode filter
                var matchesFilter = this.spotMatchesModeFilter(spot);
                if (!matchesFilter) {
                    continue;
                }

                // Get detailed submode information for consistent classification
                var submodeInfo = DX_WATERFALL_UTILS.modes.getDetailedSubmode(spot);
                var classifiedMode = submodeInfo.category;

                // Determine mode for bandwidth calculation using utility functions
                var modeForBandwidth = spot.mode.toLowerCase();

                // Use submode if available, otherwise use category
                if (submodeInfo.submode) {
                    modeForBandwidth = submodeInfo.submode.toLowerCase();
                } else {
                    var utilityCategory = DX_WATERFALL_UTILS.modes.getModeCategory(spot.mode);
                    if (utilityCategory === 'cw') {
                        modeForBandwidth = 'cw';
                    } else if (utilityCategory === 'phone' && modeForBandwidth !== 'lsb' && modeForBandwidth !== 'usb') {
                        // If classified as phone but mode isn't specific, use 'phone' to trigger freq-based LSB/USB
                        modeForBandwidth = 'phone';
                    } else if (utilityCategory === 'digi') {
                        modeForBandwidth = 'digi'; // Generic digital
                    }
                }

                var bandwidthParams = this.getCachedBandwidthParams(modeForBandwidth, spotFreq);
                var bandwidthKHz = bandwidthParams.bandwidth;
                var offsetKHz = bandwidthParams.offset;

                // Calculate pixel positions
                var spotCenterFreq = spotFreq + offsetKHz; // Center frequency of bandwidth
                var freqOffset = spotCenterFreq - middleFreq;
                var bandwidthPixels = bandwidthKHz * pixelsPerKHz;
                var centerX_spot = centerX + (freqOffset * pixelsPerKHz);

                var startX = centerX_spot - (bandwidthPixels / 2);
                var endX = startX + bandwidthPixels;

                // Calculate exact frequency line position
                var exactFreqX = centerX + ((spotFreq - middleFreq) * pixelsPerKHz);

                // Draw if any part is within canvas bounds
                if (endX > 0 && startX < canvasWidth) {
                    // Clip to canvas bounds
                    startX = Math.max(0, startX);
                    endX = Math.min(canvasWidth, endX);

                    // Draw gradient sideband based on classified mode and bandwidth mode
                    this.drawSidebandGradient(startX, endX, rulerBottom, spotFreq, modeForBandwidth, exactFreqX, classifiedMode);
                }

                // Draw exact frequency line with color based on mode
                if (exactFreqX >= 0 && exactFreqX <= canvasWidth) {
                    var lineColor = DX_WATERFALL_UTILS.modes.getModeColor(classifiedMode, 0.6);

                    this.ctx.strokeStyle = lineColor;
                    this.ctx.lineWidth = 1;
                    this.ctx.beginPath();
                    this.ctx.moveTo(exactFreqX, 0);
                    this.ctx.lineTo(exactFreqX, rulerBottom);
                    this.ctx.stroke();
                }
            }
        }
    },

    // Draw static gradient sideband for DX spots
    drawSidebandGradient: function(startX, endX, height, spotFreq, mode, exactFreqX, classifiedMode) {
        // Determine base color based on classified mode (passed from caller)
        var baseColor = DX_WATERFALL_UTILS.modes.getModeColorBase(classifiedMode);

        // Determine sideband type
        var modeStr = mode.toLowerCase();
        var sidebandType = 'centered'; // Default to centered

        // For phone/ssb modes, determine actual sideband based on frequency
        if (modeStr === 'phone' || modeStr === 'ssb') {
            var freq = parseFloat(spotFreq);
            sidebandType = DX_WATERFALL_UTILS.modes.determineSSBMode(freq).toLowerCase();
        } else if (modeStr === 'lsb' || modeStr === 'usb') {
            sidebandType = modeStr;
        }
        // AM and FM stay as 'centered' (default)
        // CW, digital modes also stay as 'centered' (default)

        // Create gradient based on sideband type
        var gradient;
        if (sidebandType === 'lsb') {
            // LSB: Most visible at spot frequency, fade to the left (lower frequencies)
            gradient = this.ctx.createLinearGradient(exactFreqX, 0, startX, 0);
            gradient.addColorStop(0, baseColor + '0.6)'); // Strong at spot frequency
            gradient.addColorStop(1, baseColor + '0.1)'); // Fade at left edge
        } else if (sidebandType === 'usb') {
            // USB: Most visible at spot frequency, fade to the right (higher frequencies)
            gradient = this.ctx.createLinearGradient(exactFreqX, 0, endX, 0);
            gradient.addColorStop(0, baseColor + '0.6)'); // Strong at spot frequency
            gradient.addColorStop(1, baseColor + '0.1)'); // Fade at right edge
        } else {
            // CW, digital modes (FT8, FT4, RTTY, digi), and other centered modes
            // Create horizontal linear gradient from center outward
            gradient = this.ctx.createLinearGradient(startX, 0, endX, 0);
            gradient.addColorStop(0, baseColor + '0.1)'); // Fade at left edge
            gradient.addColorStop(0.5, baseColor + '0.6)'); // Strong at center
            gradient.addColorStop(1, baseColor + '0.1)'); // Fade at right edge
        }

        // Apply gradient and draw
        this.ctx.fillStyle = gradient;
        this.ctx.fillRect(startX, 0, endX - startX, height);
    },

    /**
     * Get colors for spot label based on worked/confirmed status
     * @param {Object} spot - Spot object with status flags
     * @returns {Object} {bgColor, borderColor, tickboxColor}
     */
    getSpotColors: function(spot) {
        return {
            bgColor: spot.cnfmd_dxcc ? '#00FF00' : (spot.worked_dxcc ? '#FFA500' : '#FF0000'),
            borderColor: spot.cnfmd_continent ? '#00FF00' : (spot.worked_continent ? '#FFA500' : '#FF0000'),
            tickboxColor: spot.cnfmd_call ? '#00FF00' : (spot.worked_call ? '#FFA500' : '#FF0000')
        };
    },

    // Draw DX spots if available
    drawDxSpots: function() {
        if (!this.dxSpots || this.dxSpots.length === 0) {
            return; // No spots to draw
        }

        var centerX = this.canvas.width / 2;
        var rulerY = this.canvas.height - DX_WATERFALL_CONSTANTS.CANVAS.RULER_HEIGHT;
        var middleFreq = this.getCachedMiddleFreq(); // Use cached frequency
        var pixelsPerKHz = this.getCachedPixelsPerKHz(); // Use cached scaling

        // Get the spot shown in center (if any) to avoid drawing it twice
        var centerSpotInfo = this.getSpotInfo();
        var centerCallsign = centerSpotInfo ? centerSpotInfo.callsign : null;

        // Separate spots into left and right of center frequency
        var leftSpots = [];
        var rightSpots = [];

        for (var i = 0; i < this.dxSpots.length; i++) {
            var spot = this.dxSpots[i];
            var spotFreq = parseFloat(spot.frequency);

            if (spotFreq && spot.spotted && spot.mode) {
                // Apply mode filter
                if (!this.spotMatchesModeFilter(spot)) {
                    continue;
                }

                var freqOffset = spotFreq - middleFreq;
                var x = centerX + (freqOffset * pixelsPerKHz);

                // Only include if within canvas bounds
                if (x >= 0 && x <= this.canvas.width) {
                    var spotData = DX_WATERFALL_UTILS.spots.createSpotObject(spot, {
                        includePosition: true,
                        x: x,
                        includeOffsets: true,
                        middleFreq: middleFreq,
                        includeWorkStatus: true
                    });

                    // Skip this spot if it's currently shown in the center label
                    if (centerCallsign && spotData.callsign === centerCallsign) {
                        continue;
                    }

                    if (freqOffset < 0) {
                        leftSpots.push(spotData);
                    } else if (freqOffset > 0) {
                        rightSpots.push(spotData);
                    }
                    // Skip spots exactly on center frequency to avoid overlap with center marker
                }
            }
        }

        // Calculate available vertical space - from top margin to above ruler line
        var topMargin = DX_WATERFALL_CONSTANTS.CANVAS.TOP_MARGIN;
        var bottomMargin = DX_WATERFALL_CONSTANTS.CANVAS.BOTTOM_MARGIN;
        var topY = topMargin;
        var bottomY = rulerY - bottomMargin;
        var availableHeight = bottomY - topY;

		// Check if center label is shown to avoid that area
		var centerSpotShown = centerCallsign !== null;
		var centerY = (this.canvas.height - DX_WATERFALL_CONSTANTS.CANVAS.RULER_HEIGHT) / 2;
		var centerExclusionHeight = Math.ceil(13 * 1.2) + 20; // Center label height (13px base * 1.2) + 20px margin
		var centerExclusionTop = centerY - (centerExclusionHeight / 2);
		var centerExclusionBottom = centerY + (centerExclusionHeight / 2);

		// Capture references for use in nested function
		var self = this;
		var fonts = this.fonts;

		// Function to distribute spots vertically
		var drawSpotsSide = function(spots, ctx) {
			if (spots.length === 0) return;

			// Function to draw a single spot
			var drawSpot = function(spot, y) {
			// Get colors using utility function
			var colors = self.getSpotColors(spot);
			var bgColor = colors.bgColor;
			var borderColor = colors.borderColor;
			var tickboxColor = colors.tickboxColor;

			// Calculate dimensions (increased by 5% from original 12px base)
			ctx.font = fonts.spotLabels;
			var textWidth = ctx.measureText(spot.callsign).width;
			var padding = DX_WATERFALL_CONSTANTS.CANVAS.SPOT_PADDING;
			var rectX = spot.x - (textWidth / 2) - padding;
			var rectY = y - 7; // Adjusted from -6 to -7 for 13px height
			var rectWidth = textWidth + (padding * 2);
			var rectHeight = 13; // Increased from 12 to 13

			// Draw background rectangle
			ctx.fillStyle = bgColor;
			ctx.fillRect(rectX, rectY, rectWidth, rectHeight);

			// Draw border around the rectangle
			ctx.strokeStyle = borderColor;
			ctx.lineWidth = 1;
			ctx.strokeRect(rectX, rectY, rectWidth, rectHeight);

			// Draw small tickbox at top-right corner
			var tickboxSize = DX_WATERFALL_CONSTANTS.CANVAS.SPOT_TICKBOX_SIZE;
			ctx.fillStyle = tickboxColor;
			ctx.fillRect(rectX + rectWidth - tickboxSize, rectY, tickboxSize, tickboxSize);

			// Draw the callsign text in black
			ctx.fillStyle = '#000000';
			ctx.textAlign = 'center';
			ctx.textBaseline = 'middle';
			ctx.fillText(spot.callsign, spot.x, y + 1);

			// Draw underline if LoTW user
			if (spot.lotw_user) {
				ctx.strokeStyle = '#000000';
				ctx.lineWidth = 1;
				ctx.beginPath();
				ctx.moveTo(spot.x - (textWidth / 2), y + 3);
				ctx.lineTo(spot.x + (textWidth / 2), y + 3);
				ctx.stroke();
			}
			};

			if (spots.length === 1) {
				// Single spot - place in middle of available space (or top/bottom if center is occupied)
				if (centerSpotShown) {
					// Place in top section if center is occupied
					var topSectionMiddle = topY + ((centerExclusionTop - topY) / 2);
					drawSpot(spots[0], topSectionMiddle);
				} else {
					drawSpot(spots[0], topY + (availableHeight / 2));
				}
			} else {
				// Multiple spots - distribute evenly avoiding center if needed
				if (centerSpotShown) {
					// Split spots between top and bottom sections
					var topSectionHeight = centerExclusionTop - topY;
					var bottomSectionHeight = bottomY - centerExclusionBottom;
					var topSectionStart = topY;
					var bottomSectionStart = centerExclusionBottom;

					// Distribute spots proportionally between top and bottom
					var halfSpots = Math.ceil(spots.length / 2);

					// Top section
					if (halfSpots === 1) {
						drawSpot(spots[0], topSectionStart + (topSectionHeight / 2));
					} else if (topSectionHeight > 0) {
						var topSpacing = topSectionHeight / (halfSpots - 1);
						for (var i = 0; i < halfSpots && i < spots.length; i++) {
							drawSpot(spots[i], topSectionStart + (topSpacing * i));
						}
					}

					// Bottom section
					var bottomSpots = spots.length - halfSpots;
					if (bottomSpots === 1) {
						drawSpot(spots[halfSpots], bottomSectionStart + (bottomSectionHeight / 2));
					} else if (bottomSpots > 0 && bottomSectionHeight > 0) {
						var bottomSpacing = bottomSectionHeight / (bottomSpots - 1);
						for (var j = 0; j < bottomSpots; j++) {
							drawSpot(spots[halfSpots + j], bottomSectionStart + (bottomSpacing * j));
						}
					}
				} else {
					// No center label - distribute evenly across full height
					var spacing = availableHeight / (spots.length - 1);
					for (var i = 0; i < spots.length; i++) {
						drawSpot(spots[i], topY + (spacing * i));
					}
				}
			}
		};

        // Draw left side spots
        drawSpotsSide(leftSpots, this.ctx);

        // Draw right side spots
        drawSpotsSide(rightSpots, this.ctx);
    },

    // Draw vertical "www.wavelog.org" link on the right side
    drawWavelogLink: function() {
        var ctx = this.ctx;
        var text = 'www.wavelog.org';

        // Set font and measure text (using monospace font consistent with canvas)
        ctx.font = DX_WATERFALL_CONSTANTS.FONTS.SMALL_MONO;
        var textWidth = ctx.measureText(text).width;

        // Position: right side, vertically centered in waterfall area (excluding ruler), 2px inside from border
        var x = this.canvas.width - 8; // 8px from right edge (2px border + 6px spacing)
        var waterfallHeight = this.canvas.height - DX_WATERFALL_CONSTANTS.CANVAS.RULER_HEIGHT;
        var y = waterfallHeight / 2; // Vertically centered in waterfall area

        // Save context state
        ctx.save();

        // Move to position and rotate 270 degrees (90 degrees counter-clockwise, reads bottom to top)
        ctx.translate(x, y);
        ctx.rotate(-Math.PI / 2); // -90 degrees in radians (270 degrees clockwise)

        // Draw text in slightly brighter gray (above noise, below all other elements)
        ctx.fillStyle = '#888888'; // Brighter gray
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(text, 0, 0);

        // Restore context state
        ctx.restore();
    },

    // Draw center callsign label when standing at a relevant spot frequency
    drawCenterCallsignLabel: function() {
        // Get the current spot info
        var spotInfo = this.getSpotInfo();

        if (!spotInfo) {
            return; // No spot at current frequency
        }

        var ctx = this.ctx;
        var callsign = spotInfo.callsign;

        // Calculate center position
        var centerX = this.canvas.width / 2;
        var waterfallHeight = this.canvas.height - DX_WATERFALL_CONSTANTS.CANVAS.RULER_HEIGHT;
        var centerY = waterfallHeight / 2;

        // Get colors using same logic as spots
        var colors = this.getSpotColors(spotInfo);

        // Use 20% larger font than regular spots (13px -> ~15px with 5% increase applied)
        ctx.font = 'bold 15px "Consolas", "Courier New", monospace';
        var textWidth = ctx.measureText(callsign).width;

        // Calculate background rectangle dimensions (scaled 20% larger than 13px base)
        var padding = Math.ceil(DX_WATERFALL_CONSTANTS.CANVAS.SPOT_PADDING * 1.2);
        var rectHeight = Math.ceil(13 * 1.2); // Spot label height (13px) scaled
        var rectWidth = textWidth + (padding * 2);
        var rectX = centerX - (textWidth / 2) - padding;
        var rectY = centerY - (rectHeight / 2);

        // Draw background rectangle using DXCC status color
        ctx.fillStyle = colors.bgColor;
        ctx.fillRect(rectX, rectY, rectWidth, rectHeight);

        // Draw border using continent status color
        ctx.strokeStyle = colors.borderColor;
        ctx.lineWidth = 1;
        ctx.strokeRect(rectX, rectY, rectWidth, rectHeight);

        // Draw small tickbox at top-right corner using callsign status color
        var tickboxSize = Math.ceil(DX_WATERFALL_CONSTANTS.CANVAS.SPOT_TICKBOX_SIZE * 1.2);
        ctx.fillStyle = colors.tickboxColor;
        ctx.fillRect(rectX + rectWidth - tickboxSize, rectY, tickboxSize, tickboxSize);

        // Draw the callsign text in black (same as spots)
        ctx.fillStyle = '#000000';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(callsign, centerX, centerY);

        // Draw underline if LoTW user (same as spots)
        if (spotInfo.lotw_user) {
            ctx.strokeStyle = '#000000';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(centerX - (textWidth / 2), centerY + 3);
            ctx.lineTo(centerX + (textWidth / 2), centerY + 3);
            ctx.stroke();
        }
    },

    /**
     * Main refresh function - updates and redraws the entire DX waterfall canvas
     * Handles state management, data fetching, and rendering pipeline
     *
     * This function:
     * - Checks for parameter changes and fetches new spots if needed
     * - Manages waiting states and loading messages
     * - Handles CAT radio control operations
     * - Draws all canvas elements (noise, spots, ruler, markers, etc.)
     * - Updates the spot information display
     *
     * Called periodically by external refresh timer or on user interaction
     * Uses early returns to handle various states efficiently
     *
     * @returns {void}
     */
    refresh: function() {
        if (!this.canvas) {
            this.init();
            // If init still couldn't find the canvas, exit
            if (!this.canvas) {
                return;
            }
        }

        if (this.canvas) {
            // Check if canvas exists in DOM (dynamic page detection)
            if (!this.canvas.offsetParent && this.canvas.style.display !== 'none') {
                return; // Canvas not visible or removed from DOM
            }

            // Check if band or mode has changed and fetch new spots if needed
            // Skip during CAT operations to prevent interference
            if (!this.catTuning && !this.frequencyChanging) {
                // Force initial fetch if we haven't done one yet (even with invalid frequency/band)
                if (!this.initialFetchDone) {
                    // If we're still waiting for CAT frequency, don't fetch yet
                    if (!this.waitingForCATFrequency) {
                        this.initialFetchDone = true; // Set flag BEFORE fetch to prevent duplicate calls
                        this.fetchDxSpots(true, false); // Initial fetch, but not user-initiated (background)
                    }
                } else if (this.hasParametersChanged()) {
                    this.fetchDxSpots(true, true); // User changed band/mode - mark as user-initiated
                }
            }

            // Periodically refresh frequency cache to catch changes
            // Skip during CAT operations to prevent interference
            if (!this.catTuning && !this.frequencyChanging) {
                this.refreshFrequencyCache();
            }

            // Check if current frequency is far outside band limits and update band if needed
            // This handles manual frequency entry in the input field
            // Skip if user just manually changed the band to prevent reverting their choice
            var currentFreq = this.getCachedMiddleFreq();
            if (currentFreq > 0 && !this.userChangedBand && this.isFrequencyFarOutsideBand(currentFreq)) {
                this.updateBandFromFrequency(currentFreq);
            }

            // Update canvas internal dimensions to match current CSS dimensions
            this.updateDimensions();

            // Check if we should show waiting message
            var currentTime = Date.now();
            var timeSincePageLoad = currentTime - this.pageLoadTime;
            var isInitialLoad = timeSincePageLoad < this.minWaitTime;

            // Show waiting if:
            // 1. We're waiting for data AND either:
            //    a) It's the initial load and we haven't received data OR haven't waited 5 seconds yet
            //    b) It's a parameter change and we haven't received new data yet (no time wait)
            var shouldShowWaiting = this.waitingForData &&
                                  (isInitialLoad ? (!this.dataReceived || timeSincePageLoad < this.minWaitTime) : !this.dataReceived);

            if (shouldShowWaiting) {
                this.displayWaitingMessage();
                this.updateZoomMenu(); // Update menu to show loading indicator
                return; // Don't draw the normal display
            }

            // Check if CAT is tuning the radio with safety timeout
            if (this.catTuning) {
                // Safety check: if CAT tuning has been true for more than 2 seconds, force clear it
                if (!this.catTuningStartTime) {
                    this.catTuningStartTime = currentTime;
                }

                var catTuningDuration = currentTime - this.catTuningStartTime;
                if (catTuningDuration > DX_WATERFALL_CONSTANTS.CAT.TUNING_FLAG_FALLBACK_MS) {
                    console.warn('DX Waterfall: CAT tuning timeout after 2 seconds, clearing flags');
                    this.catTuning = false;
                    this.frequencyChanging = false;
                    this.catTuningStartTime = null;
                    // Update menu to show normal state after timeout
                    this.updateZoomMenu();
                } else {
                    this.displayChangingFrequencyMessage(lang_dxwaterfall_changing_frequency, 'MESSAGE_TEXT_WHITE');
                    return; // Don't draw normal display during CAT tuning
                }
            } else {
                // Clear the start time when not tuning
                this.catTuningStartTime = null;
            }

            // Check if frequency is changing (CAT command in progress)
            if (this.frequencyChanging) {
                this.displayChangingFrequencyMessage();
                this.updateZoomMenu(); // Update menu to show loading indicator
                return; // Don't draw normal display or process inputs
            }

            // Check if we're showing completion overlay (marker moved but hiding the animation)
            if (this.showingCompletionOverlay) {
                // Draw normal waterfall content first (including moved marker)
                this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
                this.drawStaticNoise();
                this.drawWavelogLink();
                this.drawBandLimits();
                this.drawFrequencyRuler();
                this.drawCenterMarker();
                this.drawDxSpots();
                this.drawCenterCallsignLabel();

                // Only show tuning message if CAT is actually available
                var catAvailable = (typeof dxwaterfall_allowcat !== 'undefined' && dxwaterfall_allowcat !== null &&
                                   typeof dxwaterfall_caturl !== 'undefined' && dxwaterfall_caturl !== null &&
                                   dxwaterfall_allowcat && dxwaterfall_caturl !== "");

                if (catAvailable) {
                    // Then draw overlay message on top
                    this.displayChangingFrequencyMessage(lang_dxwaterfall_changing_frequency, 'MESSAGE_TEXT_WHITE');
                }
                return; // Don't continue with normal refresh logic
            }

            // Show zoom menu when data is available (only if empty or mode changed)
            if (this.zoomMenuDiv && this.zoomMenuDiv.innerHTML === '') {
                // Collect all band spots for navigation
                this.collectAllBandSpots();
                this.collectSmartHunterSpots();
                this.updateZoomMenu();
            }

            // Clear the entire canvas
            this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

            // Draw static noise background
            this.drawStaticNoise();

            // Draw www.wavelog.org link (above noise, below all other elements)
            this.drawWavelogLink();

            // Draw band limit overlays (out-of-band areas with grey overlay)
            this.drawBandLimits();

            // Draw receiving bandwidth indicator (below red line, above static noise)
            this.drawReceivingBandwidth();

            // Draw DX spot bandwidth indicators
            this.drawDxSpotBandwidths();

            // Draw frequency ruler
            this.drawFrequencyRuler();

            // Draw red center marker
            this.drawCenterMarker();

            // Draw DX spots
            this.drawDxSpots();

            // Draw center callsign label (on top of everything)
            this.drawCenterCallsignLabel();

            // Update spot info in the div above canvas (prevents update on every frame)
            this.updateSpotInfoDiv();

			// Draw black border (left, right, bottom only - top border is on the div)
            this.ctx.strokeStyle = '#000000';
            this.ctx.lineWidth = 1;
            // Draw left border
            this.ctx.beginPath();
            this.ctx.moveTo(0, 0);
            this.ctx.lineTo(0, this.canvas.height);
            this.ctx.stroke();
            // Draw right border
            this.ctx.beginPath();
            this.ctx.moveTo(this.canvas.width, 0);
            this.ctx.lineTo(this.canvas.width, this.canvas.height);
            this.ctx.stroke();
        }
    },

    // Get the most relevant spot in our sideband
    getSpotInfo: function() {
        if (!this.dxSpots || this.dxSpots.length === 0) {
            return null;
        }

        var middleFreq = this.getCachedMiddleFreq(); // Use cached frequency
        var currentMode = this.getCurrentMode().toLowerCase();

        var relevantSpots = [];

        // Get current bandwidth parameters for signal width detection (with caching)
        var bandwidthParams = this.getCachedBandwidthParams(currentMode, middleFreq);
        var signalBandwidth = bandwidthParams.bandwidth; // kHz

        // Determine detection range based on mode
        var detectionRange = 0;
        var isCentered = false; // For modes with symmetric detection (CW, SSB, etc.)

        if (currentMode === 'lsb' || currentMode === 'usb' || currentMode === 'phone') {
            // SSB modes: Use symmetric ±1 kHz detection range
            // This allows spots within ±1 kHz to be detected regardless of sideband
            isCentered = true;
            detectionRange = 1.0; // ±1 kHz symmetric range for SSB
        } else if (currentMode === 'cw') {
            isCentered = true;
            detectionRange = DX_WATERFALL_CONSTANTS.SIGNAL_BANDWIDTHS.CW_DETECTION_KHZ;
        } else {
            // Other modes (digital, etc.) - centered with half bandwidth
            isCentered = true;
            detectionRange = signalBandwidth * 0.5; // 50% of bandwidth for other modes
        }        // Find spots within our signal bandwidth on the correct sideband
        for (var i = 0; i < this.dxSpots.length; i++) {
            var spot = this.dxSpots[i];
            var spotFreq = parseFloat(spot.frequency);

            if (spotFreq && spot.spotted && spot.spotter) {
                var freqOffset = spotFreq - middleFreq;
                var absOffset = Math.abs(freqOffset);

                // All modes now use centered/symmetric detection
                // For SSB/CW/Digital: spots within ±detectionRange are considered relevant
                var isInRange = absOffset <= detectionRange;

                if (isInRange) {
                    // Apply mode filter
                    if (!this.spotMatchesModeFilter(spot)) {
                        continue;
                    }

                    relevantSpots.push(DX_WATERFALL_UTILS.spots.createSpotObject(spot, {
                        includeSpotter: true,
                        includeTimestamp: true,
                        includeMessage: true,
                        includeOffsets: true,
                        middleFreq: middleFreq,
                        includeWorkStatus: true
                    }));
                }
            }
        }

        if (relevantSpots.length === 0) {
            this.relevantSpots = [];
            this.currentSpotIndex = 0;
            return null; // No relevant spots in our sideband
        }

        // Sort by absolute frequency offset (closest to our frequency first)
        relevantSpots.sort(DX_WATERFALL_UTILS.sorting.byAbsOffset);

        // Store all relevant spots and ensure current index is valid
        this.relevantSpots = relevantSpots;
        if (this.currentSpotIndex >= relevantSpots.length) {
            this.currentSpotIndex = 0;
        }

        // Return the currently selected spot
        return relevantSpots[this.currentSpotIndex];
    },

    // Format spot date/time from "DD/MM/YY HH:MM" to "YY-MM-DD HH:MM UTC"
    formatSpotDateTime: function(whenPretty) {
        if (!whenPretty) return '';

        // Input format: "18/10/25 14:30" (DD/MM/YY HH:MM)
        // Output format: "25-10-18 14:30 UTC" (YY-MM-DD HH:MM UTC)
        var parts = whenPretty.split(' ');
        if (parts.length !== 2) return whenPretty + ' UTC'; // Fallback if format is unexpected

        var datePart = parts[0]; // "18/10/25"
        var timePart = parts[1]; // "14:30"

        var dateComponents = datePart.split('/');
        if (dateComponents.length !== 3) return whenPretty + ' UTC'; // Fallback

        var day = dateComponents[0];
        var month = dateComponents[1];
        var year = dateComponents[2];

        // Reformat to YY-MM-DD HH:MM UTC
        return year + '-' + month + '-' + day + ' ' + timePart + ' UTC';
    },

    // Update spot information in the dxWaterfallSpot div
    updateSpotInfoDiv: function() {
        if (!this.spotInfoDiv) {
            return;
        }

        // If waiting for data, frequency, or radio is tuning, show nbsp to maintain layout height
        if (this.waitingForData || this.waitingForCATFrequency || this.frequencyChanging || this.catTuning) {
            if (this.spotInfoDiv.innerHTML !== '&nbsp;') {
                this.spotInfoDiv.innerHTML = '&nbsp;';
                this.lastSpotInfoText = null;
            }
            return;
        }

        var spotInfo = this.getSpotInfo();

        // Count how many spots are displayed after filtering
        var displayedSpotsCount = 0;
        if (this.dxSpots && this.dxSpots.length > 0) {
            for (var i = 0; i < this.dxSpots.length; i++) {
                if (this.spotMatchesModeFilter(this.dxSpots[i])) {
                    displayedSpotsCount++;
                }
            }
        }

        var infoText;
        if (!spotInfo) {
            // No active spot in bandwidth - show summary information
            // Format: "x spots fetched from DXCluster for band 40m, displaying y; showing spots de EU; maximum age of spot is set to 30 minutes; last update at HH:MM"
            var updateTimeStr = '';
            if (this.lastUpdateTime) {
                var hours = String(this.lastUpdateTime.getHours()).padStart(2, '0');
                var minutes = String(this.lastUpdateTime.getMinutes()).padStart(2, '0');
                updateTimeStr = hours + ':' + minutes;
            }

            var currentBand = this.getCurrentBand();
            infoText = this.totalSpotsCount + ' ' + lang_dxwaterfall_spots_fetched + ' ' + this.currentContinent + ' ' + lang_dxwaterfall_fetched_for_band + ' ' + currentBand + lang_dxwaterfall_displaying + ' ' + displayedSpotsCount;
            if (updateTimeStr) {
                infoText += ' (updated at ' + updateTimeStr + ' local time)';
            }
        } else {
            // Active spot in bandwidth - show spot details (no count prefix)

            // Get detailed submode information using centralized function
            var submodeInfo = DX_WATERFALL_UTILS.modes.getDetailedSubmode(spotInfo);
            var modeLabel = submodeInfo.submode || spotInfo.mode || 'Unknown';
            // Use detailed submode for mode field (e.g., "FT8" instead of "digi")
            var modeForField = submodeInfo.submode || spotInfo.mode || '';

            // Prepare text with flag, continent, entity, DXCC, and LoTW indicator
            var dxccInfo = spotInfo.dxcc_spotted || {};
            var flag = dxccInfo.flag || '';
            var continent = dxccInfo.cont || '';
            var entity = dxccInfo.entity || '';
            var dxccId = dxccInfo.dxcc_id || '';
            var lotwIndicator = spotInfo.lotw_user ? ' <span style="color: #FFFF00;">L</span>' : '';

            var prefixText = '';
            if (flag || continent || entity || dxccId) {
                // Wrap flag in span with default font for emoji support, rest uses monospace
                var flagPart = flag ? '<span class="flag-emoji">' + flag + '</span> ' : '';

                // Add tune icon to set frequency (use detailed submode)
                var tuneIcon = '<i class="fas fa-headset tune-icon" title="' + lang_dxwaterfall_tune_to_spot + '" data-frequency="' + spotInfo.frequency + '" data-mode="' + modeForField + '"></i> ';

                // Add cycle icon if there are multiple spots
                var cycleIcon = '';
                var spotCounter = '';
                if (this.relevantSpots.length > 1) {
                    cycleIcon = '<i class="fas fa-exchange-alt cycle-spot-icon" title="' + lang_dxwaterfall_cycle_through + ' ' + this.relevantSpots.length + ' ' + lang_dxwaterfall_spots_currently_showing + ' ' + (this.currentSpotIndex + 1) + '/' + this.relevantSpots.length + ')"></i> ';
                    spotCounter = '[' + (this.currentSpotIndex + 1) + '/' + this.relevantSpots.length + '] ';
                }

                // Use pre-calculated park references (extracted once during fetch)
                // Fall back to extraction if not available (for backwards compatibility)
                var sotaRef = spotInfo.sotaRef !== undefined ? spotInfo.sotaRef : '';
                var potaRef = spotInfo.potaRef !== undefined ? spotInfo.potaRef : '';
                var iotaRef = spotInfo.iotaRef !== undefined ? spotInfo.iotaRef : '';
                var wwffRef = spotInfo.wwffRef !== undefined ? spotInfo.wwffRef : '';

                // Fallback: if not pre-calculated, extract them
                if (spotInfo.sotaRef === undefined) {
                    var parkRefs = DX_WATERFALL_UTILS.parkRefs.extract(spotInfo);
                    sotaRef = parkRefs.sotaRef;
                    potaRef = parkRefs.potaRef;
                    iotaRef = parkRefs.iotaRef;
                    wwffRef = parkRefs.wwffRef;
                }

                // Add mode label after DXCC number (use detailed submode)
                prefixText = '<i class="fas fa-pen-to-square copy-icon" title="' + lang_dxwaterfall_log_qso_with + ' ' + spotInfo.callsign + ' [Ctrl+Space]" data-callsign="' + spotInfo.callsign + '" data-mode="' + modeForField + '" data-sota-ref="' + sotaRef + '" data-pota-ref="' + potaRef + '" data-iota-ref="' + iotaRef + '" data-wwff-ref="' + wwffRef + '"></i> ' + tuneIcon + cycleIcon + spotCounter + flagPart + continent + ' ' + entity + ' (' + dxccId + ') ' + modeLabel + lotwIndicator + ' ';
            }

            // Format the date/time with UTC
            var formattedDateTime = this.formatSpotDateTime(spotInfo.when_pretty);

            infoText = prefixText + spotInfo.callsign + ' de ' + spotInfo.spotter + ' at ' + formattedDateTime + ' ';

            // Add medal icons at the end if new (unconfirmed)
            // Order: Continent (Gold), DXCC (Silver), Callsign (Bronze)
            var awards = '';
            if (spotInfo.worked_continent === false) {
                // New Continent (not worked before) - Gold medal
                awards += ' <i class="fas fa-medal new-continent-icon" title="' + lang_dxwaterfall_new_continent + '"></i>';
            }
            if (spotInfo.worked_dxcc === false) {
                // New DXCC (not worked before) - Silver medal
                awards += ' <i class="fas fa-medal new-dxcc-icon" title="' + lang_dxwaterfall_new_dxcc + '"></i>';
            }
            if (spotInfo.worked_call === false) {
                // New Callsign (not worked before) - Bronze medal
                awards += ' <i class="fas fa-medal new-callsign-icon" title="' + lang_dxwaterfall_new_callsign + '"></i>';
            }

            infoText += awards + ' ' + lang_dxwaterfall_comment + spotInfo.message;
        }

        // Only update if the text has changed to prevent redundant DOM updates
        if (this.lastSpotInfoText !== infoText) {
            this.spotInfoDiv.innerHTML = infoText;
            this.lastSpotInfoText = infoText;
        }
    },

    // Update zoom menu display
    updateZoomMenu: function() {
        if (!this.zoomMenuDiv) {
            return;
        }

        // Don't show menu at all during frequency changes or CAT tuning
        // Don't show hourglass either - frequency changes should be invisible to user
        if (this.catTuning || this.frequencyChanging) {
            // Don't update menu during frequency changes - keep showing last state
            return;
        }

        // Don't show menu during background fetch operations
        // Show hourglass with counter during DX cluster fetch
        if (this.fetchInProgress) {
            var elapsed = ((Date.now() - this.operationStartTime) / 1000).toFixed(1);
            this.zoomMenuDiv.innerHTML = '<div style="display: flex; align-items: center; flex: 1;"><i class="fas fa-hourglass-half" style="margin-right: 5px; animation: blink 1s infinite;"></i><span style="margin-right: 10px;">' + elapsed + 's</span></div>';
            return;
        }

        // If no data received yet AND waiting for data, show only loading indicator
        // Once data is received, always show full menu (with loading indicator if needed)
        // Only show if it's a user-initiated fetch (band/continent change), not background updates
        if (!this.dataReceived) {
            if (this.waitingForData && this.userInitiatedFetch) {
                // Show loading indicator with counter for user-initiated operations
                var elapsed = ((Date.now() - this.operationStartTime) / 1000).toFixed(1);
                this.zoomMenuDiv.innerHTML = '<div style="display: flex; align-items: center; flex: 1;"><i class="fas fa-hourglass-half" style="margin-right: 5px; animation: blink 1s infinite;"></i><span style="margin-right: 10px;">' + elapsed + 's</span><span style="margin: 0 10px; opacity: 0; color: #000000;">|</span></div>';
            } else {
                // No data yet and not in proper loading state - show placeholder hourglass
                this.zoomMenuDiv.innerHTML = '<div style="display: flex; align-items: center; flex: 1;"><i class="fas fa-hourglass-half" style="margin-right: 5px; animation: blink 1s infinite;"></i></div>';
            }
            return;
        }

        var currentMode = this.getCurrentMode().toLowerCase();

        // Build zoom controls HTML - start with status indicator and band spot navigation
        var zoomHTML = '<div style="display: flex; align-items: center; flex: 1;">';

        // Add loading/tuning indicator at the very left if operation in progress
        // Show for: initial data fetch, user-initiated fetches (band changes)
        // Do NOT show for background spot refreshes or CAT tuning or frequency changes
        var showLoadingIndicator = this.waitingForData && this.userInitiatedFetch;

        if (showLoadingIndicator) {
            // Calculate elapsed time with tenths of seconds
            var elapsed = ((Date.now() - this.operationStartTime) / 1000).toFixed(1);
            zoomHTML += '<i class="fas fa-hourglass-half" style="margin-right: 5px; animation: blink 1s infinite;"></i><span style="margin-right: 10px;">' + elapsed + 's</span>';
        }

        // Add band spot navigation controls - always show them
        if (this.allBandSpots.length > 0) {
            var currentFreq = this.getCachedMiddleFreq();
            var freqTolerance = 0.001; // 1 Hz tolerance for frequency comparison

            // Check if there's any spot with lower frequency (for prev/left)
            var hasPrevSpot = false;
            for (var i = 0; i < this.allBandSpots.length; i++) {
                if (this.allBandSpots[i].frequency < (currentFreq - freqTolerance)) {
                    hasPrevSpot = true;
                    break;
                }
            }

            // Check if there's any spot with higher frequency (for next/right)
            var hasNextSpot = false;
            for (var i = 0; i < this.allBandSpots.length; i++) {
                if (this.allBandSpots[i].frequency > (currentFreq + freqTolerance)) {
                    hasNextSpot = true;
                    break;
                }
            }

            // Previous spot button
            if (hasPrevSpot) {
                zoomHTML += '<i class="fas fa-chevron-left prev-spot-icon" title="' + lang_dxwaterfall_previous_spot + '"></i> ';
            } else {
                zoomHTML += '<i class="fas fa-chevron-left prev-spot-icon disabled" title="' + lang_dxwaterfall_no_spots_lower + '" style="opacity: 0.3; cursor: not-allowed;"></i> ';
            }

            // Next spot button
            if (hasNextSpot) {
                zoomHTML += '<i class="fas fa-chevron-right next-spot-icon" title="' + lang_dxwaterfall_next_spot + '"></i>';
            } else {
                zoomHTML += '<i class="fas fa-chevron-right next-spot-icon disabled" title="' + lang_dxwaterfall_no_spots_higher + '" style="opacity: 0.3; cursor: not-allowed;"></i>';
            }
        } else {
            // No spots - show both as disabled
            zoomHTML += '<i class="fas fa-chevron-left prev-spot-icon disabled" title="' + lang_dxwaterfall_no_spots_available + '" style="opacity: 0.3; cursor: not-allowed;"></i> ';
            zoomHTML += '<i class="fas fa-chevron-right next-spot-icon disabled" title="' + lang_dxwaterfall_no_spots_available + '" style="opacity: 0.3; cursor: not-allowed;"></i>';
        }

        // Add separator
        zoomHTML += '<span style="margin: 0 10px; opacity: 0.5;">|</span>';

        // Check if there are any unworked continent/DXCC spots on the band
        var hasSmartSpots = this.smartHunterSpots.length > 0;

            if (hasSmartSpots) {
                zoomHTML += '<i class="fas fa-crosshairs smart-hunter-icon" title="' + lang_dxwaterfall_cycle_unworked + '"></i>';
                zoomHTML += '<span class="smart-hunter-text" title="' + lang_dxwaterfall_cycle_unworked + '">' + lang_dxwaterfall_dx_hunter + '</span>';
            } else {
                zoomHTML += '<i class="fas fa-crosshairs smart-hunter-icon disabled" title="' + lang_dxwaterfall_no_unworked + '" style="opacity: 0.3; cursor: not-allowed;"></i>';
                zoomHTML += '<span class="smart-hunter-text disabled" title="' + lang_dxwaterfall_no_unworked + '" style="opacity: 0.3; cursor: not-allowed;">' + lang_dxwaterfall_dx_hunter + '</span>';
            }

            // Add separator
            zoomHTML += '<span style="margin: 0 10px; opacity: 0.5;">|</span>';

            // Add continent cycling controls
            if (this.continentChanging) {
                // Fetching data - show as disabled
                zoomHTML += '<i class="fas fa-globe-americas continent-cycle-icon disabled" title="' + lang_dxwaterfall_fetching_spots + '" style="opacity: 0.3; cursor: not-allowed;"></i>';
                zoomHTML += '<span class="continent-cycle-text disabled" title="' + lang_dxwaterfall_fetching_spots + '" style="opacity: 0.3; cursor: not-allowed;">de ' + this.currentContinent + '</span>';
            } else if (this.pendingContinent) {
                // Pending change - show with blinking effect
                zoomHTML += '<i class="fas fa-globe-americas continent-cycle-icon" title="' + lang_dxwaterfall_click_to_cycle + '" style="animation: blink 0.5s linear infinite;"></i>';
                zoomHTML += '<span class="continent-cycle-text" title="' + lang_dxwaterfall_click_to_cycle + '" style="animation: blink 0.5s linear infinite;">de ' + this.pendingContinent + '</span>';
            } else {
                // Normal state
                zoomHTML += '<i class="fas fa-globe-americas continent-cycle-icon" title="' + lang_dxwaterfall_change_continent + '"></i>';
                zoomHTML += '<span class="continent-cycle-text" title="' + lang_dxwaterfall_change_continent + '">de ' + this.currentContinent + '</span>';
            }

            // Add separator before mode filters
            zoomHTML += '<span style="margin: 0 10px; opacity: 0.5;">|</span>';

            // Add mode filter controls
            var activeFilters = this.pendingModeFilters || this.modeFilters;
            var blinkStyle = this.pendingModeFilters ? 'animation: blink 0.5s linear infinite;' : '';

            zoomHTML += '<i class="fas fa-filter mode-filter-icon" title="' + lang_dxwaterfall_filter_by_mode + '"></i>';
            zoomHTML += '<span style="margin-left: 5px; margin-right: 3px; font-size: 11px;">' + lang_dxwaterfall_modes_label + '</span>';

            // Phone filter - Green
            var phoneClass = activeFilters.phone ? 'mode-filter-phone active' : 'mode-filter-phone';
            var phoneStyle = activeFilters.phone ? 'color: #00FF00; font-weight: bold;' : 'color: #888888;';
            if (this.pendingModeFilters) phoneStyle += ' ' + blinkStyle;
            phoneStyle += ' cursor: pointer;';
            zoomHTML += '<span class="' + phoneClass + '" title="' + lang_dxwaterfall_toggle_phone + '" style="' + phoneStyle + ' margin: 0 3px; font-size: 11px; transition: color 0.2s;">' + lang_dxwaterfall_phone + '</span>';

            // CW filter - Orange
            var cwClass = activeFilters.cw ? 'mode-filter-cw active' : 'mode-filter-cw';
            var cwStyle = activeFilters.cw ? 'color: #FFA500; font-weight: bold;' : 'color: #888888;';
            if (this.pendingModeFilters) cwStyle += ' ' + blinkStyle;
            cwStyle += ' cursor: pointer;';
            zoomHTML += '<span class="' + cwClass + '" title="' + lang_dxwaterfall_toggle_cw + '" style="' + cwStyle + ' margin: 0 3px; font-size: 11px; transition: color 0.2s;">' + lang_dxwaterfall_cw + '</span>';

            // Digi filter - Blue
            var digiClass = activeFilters.digi ? 'mode-filter-digi active' : 'mode-filter-digi';
            var digiStyle = activeFilters.digi ? 'color: #0096FF; font-weight: bold;' : 'color: #888888;';
            if (this.pendingModeFilters) digiStyle += ' ' + blinkStyle;
            digiStyle += ' cursor: pointer;';
            zoomHTML += '<span class="' + digiClass + '" title="' + lang_dxwaterfall_toggle_digi + '" style="' + digiStyle + ' margin: 0 3px; font-size: 11px; transition: color 0.2s;">' + lang_dxwaterfall_digi + '</span>';

        zoomHTML += '</div>';

        // Right side: zoom controls
        zoomHTML += '<div style="display: flex; align-items: center; white-space: nowrap;">';

        // Zoom out button (disabled if at level 1)
        if (this.currentZoomLevel > 1) {
            zoomHTML += '<i class="fas fa-search-minus zoom-out-icon" title="' + lang_dxwaterfall_zoom_out + '"></i> ';
        } else {
            zoomHTML += '<i class="fas fa-search-minus zoom-out-icon disabled" title="' + lang_dxwaterfall_zoom_out + '" style="opacity: 0.3; cursor: not-allowed;"></i> ';
        }

        // Reset zoom button (disabled if already at level 3)
        if (this.currentZoomLevel !== 3) {
            zoomHTML += '<i class="fas fa-undo zoom-reset-icon" title="' + lang_dxwaterfall_reset_zoom + '" style="margin: 0 5px; cursor: pointer; color: #FFFFFF; font-size: 12px;"></i> ';
        } else {
            zoomHTML += '<i class="fas fa-undo zoom-reset-icon disabled" title="' + lang_dxwaterfall_reset_zoom + '" style="margin: 0 5px; opacity: 0.3; cursor: not-allowed; font-size: 12px;"></i> ';
        }

        // Zoom in button (disabled if at max level)
        if (this.currentZoomLevel < this.maxZoomLevel) {
            zoomHTML += '<i class="fas fa-search-plus zoom-in-icon" title="' + lang_dxwaterfall_zoom_in + '"></i>';
        } else {
            zoomHTML += '<i class="fas fa-search-plus zoom-in-icon disabled" title="' + lang_dxwaterfall_zoom_in + '" style="opacity: 0.3; cursor: not-allowed;"></i>';
        }

        zoomHTML += '</div>';

        this.zoomMenuDiv.innerHTML = zoomHTML;
    },

    // Zoom in (increase zoom level)
    zoomIn: function() {
        // Prevent rapid-fire zoom changes
        if (this.zoomChanging) {
            return;
        }

        if (this.currentZoomLevel < this.maxZoomLevel) {
            this.zoomChanging = true;
            this.currentZoomLevel++;
            this.cachedPixelsPerKHz = null; // Invalidate cache
            this.lastModeForCache = null; // Force recalculation

            // Update zoom menu and reset flag after a delay
            var self = this;
            setTimeout(function() {
                self.updateZoomMenu();
                self.zoomChanging = false;
            }, 100);
        }
    },

    // Zoom out (decrease zoom level)
    zoomOut: function() {
        // Prevent rapid-fire zoom changes
        if (this.zoomChanging) {
            return;
        }

        if (this.currentZoomLevel > 1) {
            this.zoomChanging = true;
            this.currentZoomLevel--;
            this.cachedPixelsPerKHz = null; // Invalidate cache
            this.lastModeForCache = null; // Force recalculation

            // Update zoom menu and reset flag after a delay
            var self = this;
            setTimeout(function() {
                self.updateZoomMenu();
                self.zoomChanging = false;
            }, 100);
        }
    },

    // Reset zoom to default level (3)
    resetZoom: function() {
        // Prevent rapid-fire zoom changes
        if (this.zoomChanging) {
            return;
        }

        if (this.currentZoomLevel !== 3) {
            this.zoomChanging = true;
            this.currentZoomLevel = 3;
            this.cachedPixelsPerKHz = null; // Invalidate cache
            this.lastModeForCache = null; // Force recalculation

            // Update zoom menu and reset flag after a delay
            var self = this;
            setTimeout(function() {
                self.updateZoomMenu();
                self.zoomChanging = false;
            }, 100);
        }
    },

	// ========================================
    // SPOT NAVIGATION AND FREQUENCY FUNCTIONS
    // ========================================

    // Navigate to previous spot (nearest spot to the left of current frequency)
    prevSpot: function() {
        // Check if navigation is allowed
        if (!DX_WATERFALL_UTILS.navigation.canNavigate(this)) {
            return;
        }

        // Get current frequency
        var currentFreq = this.getCachedMiddleFreq();
        var targetSpot = null;
        var targetIndex = -1;

        // Find the nearest spot to the left (lower frequency) of current position
        // Since allBandSpots is sorted by frequency ascending, iterate backwards
        for (var i = this.allBandSpots.length - 1; i >= 0; i--) {
            if (this.allBandSpots[i].frequency < currentFreq) {
                targetSpot = this.allBandSpots[i];
                targetIndex = i;
                break;
            }
        }

        // Don't wrap around - only navigate if a spot exists to the left
        // If no spot found to the left, do nothing (button should be disabled)
        if (targetSpot) {
            DX_WATERFALL_UTILS.navigation.navigateToSpot(this, targetSpot, targetIndex);
        }
    },

    // Navigate to next spot (nearest spot to the right of current frequency)
    nextSpot: function() {
        // Check if navigation is allowed
        if (!DX_WATERFALL_UTILS.navigation.canNavigate(this)) {
            return;
        }

        // Get current frequency
        var currentFreq = this.getCachedMiddleFreq();
        var targetSpot = null;
        var targetIndex = -1;

        // Find the nearest spot to the right (higher frequency) of current position
        // Since allBandSpots is sorted by frequency ascending, iterate forward
        for (var i = 0; i < this.allBandSpots.length; i++) {
            if (this.allBandSpots[i].frequency > currentFreq) {
                targetSpot = this.allBandSpots[i];
                targetIndex = i;
                break;
            }
        }

        // Don't wrap around - only navigate if a spot exists to the right
        // If no spot found to the right, do nothing (button should be disabled)
        if (targetSpot) {
            DX_WATERFALL_UTILS.navigation.navigateToSpot(this, targetSpot, targetIndex);
        }
    },

    // Sync relevant spot index when navigating to a specific callsign/frequency
    syncRelevantSpotIndex: function(spot) {

        var found = false;
        // Find this spot in the relevantSpots array
        for (var i = 0; i < this.relevantSpots.length; i++) {
            if (this.relevantSpots[i].callsign === spot.callsign &&
                Math.abs(this.relevantSpots[i].frequency - spot.frequency) < 0.01) {
                // Found the spot - set the index
                this.currentSpotIndex = i;
                found = true;
                // Force update of spot info display
                this.lastSpotInfoText = null;
                this.updateSpotInfoDiv();
                break;
            }
        }
    },

    // Collect all valid spots on the band for navigation
    collectAllBandSpots: function(forceUpdate) {
        // Throttle spot collection to prevent excessive calls
        var currentTime = Date.now();
        if (!forceUpdate && (currentTime - this.lastSpotCollectionTime) < this.spotCollectionThrottleMs) {
            return;
        }
        this.lastSpotCollectionTime = currentTime;

        var currentFreq = this.getCachedMiddleFreq();
        var currentBand = this.getCurrentBand();
        var currentMode = this.getCurrentMode();

        // Filter spots for current band
        var result = DX_WATERFALL_UTILS.spots.filterSpots(this, function(spot, spotFreq, context) {
            // Validate that spot belongs to current band (prevent cross-band contamination)
            var spotBand = context.getFrequencyBand(spotFreq);
            return spotBand === currentBand;
        }, {
            postProcess: function(spotObj, originalSpot) {
                // Add reference to the original spot object for precise matching later
                spotObj._originalSpot = originalSpot;
                return spotObj;
            }
        });

        var spots = result.spots;

        // Sort by frequency (ascending)
        spots.sort(DX_WATERFALL_UTILS.sorting.byFrequency);

        this.allBandSpots = spots;

        // If no spots after filtering, reset index and return
        if (spots.length === 0) {
            this.currentBandSpotIndex = 0;
            return;
        }

        // Find current spot index based on current frequency
        var currentFreq = this.getCachedMiddleFreq();

        var closestIndex = 0;
        var minDiff = Math.abs(spots[0].frequency - currentFreq);

        for (var i = 1; i < spots.length; i++) {
            var diff = Math.abs(spots[i].frequency - currentFreq);
            if (diff < minDiff) {
                minDiff = diff;
                closestIndex = i;
            }
        }

        this.currentBandSpotIndex = closestIndex;
    },

    // Collect spots with unworked continents or DXCC entities
    collectSmartHunterSpots: function() {
        // Filter spots for unworked continents or DXCC entities
        var result = DX_WATERFALL_UTILS.spots.filterSpots(this, function(spot, spotFreq, context) {
            // Check if continent or DXCC is not worked (false = not worked)
            var isNewContinent = (spot.worked_continent === false);
            var isNewDxcc = (spot.worked_dxcc === false);
            return isNewContinent || isNewDxcc;
        }, {
            postProcess: function(spotObj, originalSpot) {
                // Add specific fields needed for smart hunter logic
                spotObj.worked_continent = originalSpot.worked_continent;
                spotObj.worked_dxcc = originalSpot.worked_dxcc;
                // Add reference to the original spot object for precise matching later
                spotObj._originalSpot = originalSpot;
                return spotObj;
            }
        });

        var spots = result.spots;

        // Sort by frequency (ascending)
        spots.sort(function(a, b) {
            return a.frequency - b.frequency;
        });

        this.smartHunterSpots = spots;

        // If smart hunter is active, find current spot index
        if (this.smartHunterActive && spots.length > 0) {
            var currentFreq = this.getCachedMiddleFreq();
            var closestIndex = 0;
            var minDiff = Math.abs(spots[0].frequency - currentFreq);

            for (var i = 1; i < spots.length; i++) {
                var diff = Math.abs(spots[i].frequency - currentFreq);
                if (diff < minDiff) {
                    minDiff = diff;
                    closestIndex = i;
                }
            }

            this.currentSmartHunterIndex = closestIndex;
        } else {
            this.currentSmartHunterIndex = 0;
        }
    },

    // Cycle to next smart hunter spot (nearest unworked spot to the right of current frequency)
    nextSmartHunterSpot: function() {

        if (this.spotNavigating) {
            return; // Already navigating, prevent double trigger
        }

        if (this.smartHunterSpots.length === 0) {
            return; // No smart spots available
        }

        this.spotNavigating = true;
        this.smartHunterActive = true;

        // Get current frequency
        var currentFreq = this.getCachedMiddleFreq();

        var targetSpot = null;
        var targetIndex = -1;

        // Find the nearest unworked spot to the right (higher frequency) of current position
        // Since smartHunterSpots is sorted by frequency ascending, iterate forward
        for (var i = 0; i < this.smartHunterSpots.length; i++) {
            if (this.smartHunterSpots[i].frequency > currentFreq) {
                targetSpot = this.smartHunterSpots[i];
                targetIndex = i;
                break;
            }
        }

        // If no spot found to the right, wrap to the lowest frequency unworked spot
        if (!targetSpot && this.smartHunterSpots.length > 0) {
            targetSpot = this.smartHunterSpots[0];
            targetIndex = 0;
        }

        if (targetSpot) {
            this.currentSmartHunterIndex = targetIndex;

            // Set frequency to the spot
            if (targetSpot.frequency) {
                // Clear the QSO form when navigating to a new spot
                DX_WATERFALL_UTILS.qsoForm.clearForm();

                // Check if frequency is far outside current band and update band if needed
                if (this.isFrequencyFarOutsideBand(targetSpot.frequency)) {
                    this.updateBandFromFrequency(targetSpot.frequency);
                }

                setFrequency(targetSpot.frequency, true); // Pass true to indicate waterfall-initiated change

                // Get the complete spot data from the stored reference
                // targetSpot from smartHunterSpots has a reference to the original spot
                var completeSpot = targetSpot._originalSpot || null;
                var self = this;

                // Populate callsign and trigger lookup after form is cleared
                setTimeout(function() {
                    // Use complete spot data if found, otherwise use targetSpot
                    var spotData;
                    if (completeSpot) {
                        // Use all data from the complete spot to ensure consistency
                        spotData = {
                            callsign: completeSpot.spotted,
                            mode: completeSpot.mode,
                            sotaRef: completeSpot.sotaRef || '',
                            potaRef: completeSpot.potaRef || '',
                            iotaRef: completeSpot.iotaRef || '',
                            wwffRef: completeSpot.wwffRef || ''
                        };
                    } else {
                        // Fallback to targetSpot data
                        spotData = {
                            callsign: targetSpot.callsign,
                            mode: targetSpot.mode,
                            sotaRef: targetSpot.sotaRef || '',
                            potaRef: targetSpot.potaRef || '',
                            iotaRef: targetSpot.iotaRef || '',
                            wwffRef: targetSpot.wwffRef || ''
                        };
                    }

                    DX_WATERFALL_UTILS.qsoForm.populateFromSpot(spotData, true);
                }, 100);

                // Commit the new frequency so waterfall doesn't shift when user types later
                setTimeout(function() {
                    self.commitFrequency();
                }, 50);

                // After frequency change, sync the band spot index and relevant spot index
                // Find this spot in the allBandSpots array
                var foundBandIndex = false;
                for (var i = 0; i < this.allBandSpots.length; i++) {
                    if (this.allBandSpots[i].callsign === targetSpot.callsign &&
                        Math.abs(this.allBandSpots[i].frequency - targetSpot.frequency) < 0.01) {
                        this.currentBandSpotIndex = i;
                        foundBandIndex = true;
                        break;
                    }
                }

                // Sync relevant spot index
                this.syncRelevantSpotIndex(targetSpot);
            }

            // Update zoom menu to reflect new position
            this.updateZoomMenu();
        } else {
            // No target spot found (should not happen)
        }

        // Reset flag after a delay
        var self = this;
        setTimeout(function() {
            self.spotNavigating = false;
        }, 100);
    },

    // Jump to first spot in band
    firstSpot: function() {
        // Don't handle navigation when frequency is changing
        if (this.frequencyChanging) {
            return; // Block navigation during frequency changes
        }

        var spot = this.allBandSpots[0];
        if (spot) {
            DX_WATERFALL_UTILS.navigation.navigateToSpot(this, spot, 0);
        }
    },

    // Jump to last spot in band
    lastSpot: function() {
        // Check if navigation is allowed
        if (!DX_WATERFALL_UTILS.navigation.canNavigate(this)) {
            return;
        }

        var lastIndex = this.allBandSpots.length - 1;
        var spot = this.allBandSpots[lastIndex];
        if (spot) {
            DX_WATERFALL_UTILS.navigation.navigateToSpot(this, spot, lastIndex);
        }
    },

    // Cycle to next continent (debounced - waits 1.5s after last click)
    cycleContinent: function() {
        var self = this;

        // Find current continent index (use pendingContinent if set, otherwise currentContinent)
        var baseContinent = this.pendingContinent || this.currentContinent;
        var currentIndex = this.continents.indexOf(baseContinent);

        // Move to next continent (with wrap around)
        var nextIndex = (currentIndex + 1) % this.continents.length;
        var nextContinent = this.continents[nextIndex];

        // Store pending continent
        this.pendingContinent = nextContinent;

        // Update menu immediately to show the pending continent
        this.updateZoomMenu();

        // Clear existing timer if there is one
        if (this.continentChangeTimer) {
            clearTimeout(this.continentChangeTimer);
        }

        // Set new timer - actual change happens after 1.5 seconds of no clicks
        this.continentChangeTimer = setTimeout(function() {
            // Only proceed if we have a pending continent
            if (!self.pendingContinent) {
                return;
            }

            // Apply the continent change
            self.currentContinent = self.pendingContinent;
            self.pendingContinent = null;
            self.continentChanging = true;

            // Invalidate band limits cache (region may have changed)
            self.bandLimitsCache = null;

            // Enter waiting state
            self.waitingForData = true;
            self.dataReceived = false;
            self.lastSpotInfoText = null;

            // Set spot info to nbsp to maintain layout height
            if (self.spotInfoDiv) {
                self.spotInfoDiv.innerHTML = '&nbsp;';
            }

            // Clear current spots
            self.dxSpots = [];
            self.allBandSpots = [];
            self.smartHunterSpots = [];
            self.relevantSpots = [];
            self.currentBandSpotIndex = 0;
            self.currentSmartHunterIndex = 0;
            self.currentSpotIndex = 0;

            // Update zoom menu to show new continent and waiting state
            self.updateZoomMenu();

            // Fetch new spots with the new continent
            self.fetchDxSpots(true, true); // User changed continent - mark as user-initiated

            // Reset changing flag after data is received (or timeout)
            setTimeout(function() {
                self.continentChanging = false;
                self.updateZoomMenu();
            }, 2000); // 2 seconds to allow AJAX to complete

        }, 1500); // Wait 1.5 seconds after last click before fetching
    },

    // Check if a spot should be shown based on active mode filters
    spotMatchesModeFilter: function(spot) {
        // Use comprehensive mode classification utility directly
        var spotMode = DX_WATERFALL_UTILS.modes.classifyMode(spot).category;

        // Use pending filters if they exist, otherwise use current filters
        var filters = this.pendingModeFilters || this.modeFilters;

        // If mode is unknown/unclassified, treat as "other"
        if (!spotMode || (spotMode !== 'phone' && spotMode !== 'cw' && spotMode !== 'digi')) {
            return filters.other === true;
        }

        // For digi mode spots: if digi filter is OFF, also hide spots on FT8 frequencies
        // This prevents clutter from FT8 spots when user doesn't want to see digi modes
        // But if digi filter is ON, show all digi spots including FT8 frequencies
        if (spotMode === 'digi') {
            var spotFreq = parseFloat(spot.frequency);
            var isOnFT8Freq = this.isFT8Frequency(spotFreq);

            // If digi filter is OFF and spot is on FT8 frequency, hide it
            if (!filters.digi && isOnFT8Freq) {
                return false;
            }

            // If digi filter is ON, show all digi spots (including FT8)
            // If digi filter is OFF and NOT on FT8, also hide (filter is off)
            return filters.digi;
        }

        // For phone, cw: check the corresponding filter
        return filters[spotMode] === true;
    },

    // Toggle a mode filter (debounced - waits 0.5s after last click, no re-fetch needed)
    toggleModeFilter: function(modeType) {
        var self = this;

        // Create pending filters if they don't exist (clone current filters)
        if (!this.pendingModeFilters) {
            this.pendingModeFilters = {
                phone: this.modeFilters.phone,
                cw: this.modeFilters.cw,
                digi: this.modeFilters.digi,
                other: this.modeFilters.other
            };
        }

        // Toggle the specified filter
        this.pendingModeFilters[modeType] = !this.pendingModeFilters[modeType];

        // Update menu immediately to show the pending state
        this.updateZoomMenu();

        // Clear existing timer if there is one
        if (this.modeFilterChangeTimer) {
            clearTimeout(this.modeFilterChangeTimer);
        }

        // Set new timer - actual change happens after 0.5 seconds of no clicks
        this.modeFilterChangeTimer = setTimeout(function() {
            // Only proceed if we have pending filters
            if (!self.pendingModeFilters) {
                return;
            }

            // Apply the filter changes
            self.modeFilters.phone = self.pendingModeFilters.phone;
            self.modeFilters.cw = self.pendingModeFilters.cw;
            self.modeFilters.digi = self.pendingModeFilters.digi;
            self.modeFilters.other = self.pendingModeFilters.other;
            self.pendingModeFilters = null;

            // No need to fetch new spots - we already have all modes from cluster
            // Just re-collect spots with the new filters applied
            self.collectAllBandSpots(true); // Update band spot collection (force after filter change)
            self.collectSmartHunterSpots(); // Update smart hunter spots

            // Reset spot info to force update
            self.lastSpotInfoText = null;

            // Update zoom menu to show new state
            self.updateZoomMenu();

        }, DX_WATERFALL_CONSTANTS.DEBOUNCE.MODE_FILTER_CHANGE_MS);
    },

    /**
     * Cleanup method to unbind event handlers and free resources
     * Call this method before removing the waterfall from the DOM to prevent memory leaks
     */
    destroy: function() {
        // Clear all timers
        if (this.fetchDebounceTimer) {
            clearTimeout(this.fetchDebounceTimer);
            this.fetchDebounceTimer = null;
        }
        if (this.continentChangeTimer) {
            clearTimeout(this.continentChangeTimer);
            this.continentChangeTimer = null;
        }
        if (this.modeFilterChangeTimer) {
            clearTimeout(this.modeFilterChangeTimer);
            this.modeFilterChangeTimer = null;
        }
        if (this.catFrequencyWaitTimer) {
            clearTimeout(this.catFrequencyWaitTimer);
            this.catFrequencyWaitTimer = null;
        }

        // Unbind all event handlers
        if (this.$freqCalculated) {
            this.$freqCalculated.off('focus blur input keydown');
        }
        if (this.canvas) {
            $(this.canvas).off('click');
        }
        if (this.zoomMenuDiv) {
            $(this.zoomMenuDiv).off('click');
        }
        if (this.spotInfoDiv) {
            $(this.spotInfoDiv).off('click');
        }

        // Clear canvas
        if (this.ctx && this.canvas) {
            this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        }

        // Clear cached data
        this.dxSpots = [];
        this.relevantSpots = [];
        this.allBandSpots = [];
        this.smartHunterSpots = [];

        // Clear cache references
        this.cache.noise1 = null;
        this.cache.noise2 = null;

        // Mark as not initialized
        this.initializationComplete = false;
    }
};

// Helper function to safely set the mode with fallback
// @param mode - Mode to set
// @param skipTrigger - If true, don't trigger change event (prevents side effects)
function setMode(mode, skipTrigger) {
    if (!mode) {
        return false;
    }

    // Default skipTrigger to false
    if (typeof skipTrigger === 'undefined') {
        skipTrigger = false;
    }

    var modeSelect = $('#mode');
    var modeUpper = mode.toUpperCase();

    // Map common mode variations to standard ADIF modes
    // Prefer LSB/USB over generic SSB when possible
    var modeMapping = {
        'PHONE-LSB': 'LSB',
        'PHONE-USB': 'USB',
        'SSB-LSB': 'LSB',
        'SSB-USB': 'USB',
        'DIGI': 'RTTY',  // Fallback for generic digital
        'DATA': 'RTTY'   // Fallback for generic data
    };

    // Check if we need to map the mode
    if (modeMapping[modeUpper]) {
        modeUpper = modeMapping[modeUpper];
    }
    // For generic PHONE/SSB, try to determine LSB/USB based on frequency
    else if (modeUpper === 'PHONE' || modeUpper === 'SSB') {
        var currentFreq = dxWaterfall.getCachedMiddleFreq(); // Get frequency in kHz
        var ssbMode = DX_WATERFALL_UTILS.modes.determineSSBMode(currentFreq);
        if (ssbMode === 'LSB') {
            // Check if LSB exists in options
            if (modeSelect.find('option[value="LSB"]').length > 0) {
                modeUpper = 'LSB';
            } else {
                modeUpper = 'SSB'; // Fallback to SSB
            }
        } else { // USB
            // Check if USB exists in options
            if (modeSelect.find('option[value="USB"]').length > 0) {
                modeUpper = 'USB';
            } else {
                modeUpper = 'SSB'; // Fallback to SSB
            }
        }
    }

    // Check if the mode exists in the select options
    var modeExists = modeSelect.find('option[value="' + modeUpper + '"]').length > 0;

    if (modeExists) {
        // Temporarily mark that we're programmatically setting the mode
        // to prevent waterfall from fetching new spots
        dxWaterfall.programmaticModeChange = true;

        modeSelect.val(modeUpper);

        // Only trigger change if skipTrigger is false
        if (!skipTrigger) {
            modeSelect.trigger('change');
        }

        // Reset the flag after a short delay
        setTimeout(function() {
            dxWaterfall.programmaticModeChange = false;
        }, 100);

        return true;
    } else {
        // Mode doesn't exist, select the first available option as fallback
        var firstOption = modeSelect.find('option:first').val();
        if (firstOption) {
            dxWaterfall.programmaticModeChange = true;
            modeSelect.val(firstOption);

            // Only trigger change if skipTrigger is false
            if (!skipTrigger) {
                modeSelect.trigger('change');
            }

            setTimeout(function() {
                dxWaterfall.programmaticModeChange = false;
            }, 100);
        }
        return false;
    }
}

// Helper function to handle frequency changes via CAT or manual input
// Global function so it can be accessed from dxWaterfall methods
// @param frequencyInKHz - Target frequency in kHz
// @param fromWaterfall - True if this change was initiated by waterfall (clicking spot/tune icon), false for external calls
function setFrequency(frequencyInKHz, fromWaterfall) {
    if (!frequencyInKHz) {
        return;
    }

    // Default fromWaterfall to false (external call) unless explicitly set to true
    if (typeof fromWaterfall === 'undefined') {
        fromWaterfall = false;
    }

    // Check if already changing frequency and block rapid commands
    if (typeof dxWaterfall !== 'undefined' && dxWaterfall.frequencyChanging) {
        return;
    }

    // Add simple debounce to prevent rapid-fire calls
    var now = Date.now();
    if (typeof setFrequency.lastCall === 'undefined') {
        setFrequency.lastCall = 0;
    }
    if (now - setFrequency.lastCall < 500) { // 500ms debounce - prevent rapid navigation
        return;
    }
    setFrequency.lastCall = now;

    var formattedFreq = Math.round(frequencyInKHz * 1000); // Convert to Hz and round to integer
    var modeVal = $('#mode').val();
    var mode = modeVal ? modeVal.toLowerCase() : '';

    // Check if CAT is enabled and configured
    var catAvailable = (typeof dxwaterfall_allowcat !== 'undefined' && dxwaterfall_allowcat !== null &&
                       typeof dxwaterfall_caturl !== 'undefined' && dxwaterfall_caturl !== null &&
                       dxwaterfall_allowcat && dxwaterfall_caturl !== "");

    if (catAvailable) {
        // Set the radio frequency via CAT command
        // Use formattedFreq (in Hz) for consistency - rounded to integer for radio compatibility

        // Map current mode to CAT mode parameter
        var catMode = 'phone'; // Default fallback
            if (mode) {
                var modeUpper = mode.toUpperCase();

                // CW modes
                if (modeUpper === 'CW' || modeUpper === 'A1A') {
                    catMode = 'cw';
                }
                // Digital modes
                else if (modeUpper === 'FT8' || modeUpper === 'FT4' || modeUpper === 'RTTY' ||
                         modeUpper === 'PSK31' || modeUpper === 'JT65' || modeUpper === 'JT9' ||
                         modeUpper === 'WSPR' || modeUpper === 'MFSK' || modeUpper === 'OLIVIA' ||
                         modeUpper === 'CONTESTIA' || modeUpper === 'MT63' || modeUpper === 'SSTV' ||
                         modeUpper === 'PACKET' || modeUpper === 'FSK441' || modeUpper === 'ISCAT' ||
                         modeUpper === 'MSK144' || modeUpper === 'Q65' || modeUpper === 'QRA64' ||
                         modeUpper === 'FST4' || modeUpper === 'FST4W' || modeUpper === 'JS8' ||
                         modeUpper === 'VARA' || modeUpper === 'VARAC' || modeUpper === 'DMR' ||
                         modeUpper === 'DSTAR' || modeUpper === 'C4FM' || modeUpper === 'FREEDV' ||
                         modeUpper === 'M17' || modeUpper === 'DYNAMIC' ||
                         modeUpper.indexOf('PSK') !== -1 || modeUpper.indexOf('HELL') !== -1 ||
                         modeUpper.indexOf('THOR') !== -1 || modeUpper.indexOf('THROB') !== -1 ||
                         modeUpper.indexOf('DOM') !== -1 || modeUpper.indexOf('MFSK') !== -1) {
                    catMode = 'digi';
                }
                // Phone modes (SSB, AM, FM, etc.) - default to 'phone'
                else {
                    catMode = 'phone';
                }
            }

            var catUrl = dxwaterfall_caturl + '/' + formattedFreq + '/' + catMode;

            // Set frequency changing flag and show visual feedback
            if (typeof dxWaterfall !== 'undefined') {
                dxWaterfall.frequencyChanging = true;
                // Only set catTuning flag if this is a waterfall-initiated change (not external CAT updates)
                if (fromWaterfall) {
                    dxWaterfall.catTuning = true; // Set CAT tuning flag
                }
                dxWaterfall.catTuningStartTime = Date.now(); // Track when CAT tuning started for timeout protection
                dxWaterfall.operationStartTime = Date.now(); // Reset operation timer for display
                dxWaterfall.lastWaterfallFrequencyCommandTime = Date.now(); // Track waterfall command time

                // Only show changing message if this is a waterfall-initiated change
                if (fromWaterfall) {
                    dxWaterfall.displayChangingFrequencyMessage(lang_dxwaterfall_changing_frequency, 'MESSAGE_TEXT_WHITE');
                }
            }

            // Set debounce lock to prevent CAT feedback
            if (typeof window.dxwaterfall_cat_debounce_lock !== 'undefined') {
                window.dxwaterfall_cat_debounce_lock = 1;
                window.dxwaterfall_expected_frequency = formattedFreq; // Store expected frequency for confirmation
            }

            // Add timeout and better error handling
            $.ajax({
                url: catUrl,
                type: 'GET',
                dataType: 'text', // Accept any response format, not just JSON
                timeout: 5000, // 5 second timeout
                success: function(data, textStatus, jqXHR) {
                    // Optionally log response if it contains useful info
                    if (data && data.trim() !== '') {
						// Do nothing
                    }

                    // Clear frequency changing flag on successful command
                    if (typeof dxWaterfall !== 'undefined') {
                        // Clear frequency changing flag immediately since CAT command succeeded
                        // The catTuning flag will be cleared by invalidateFrequencyCache() when frequency is confirmed
                        setTimeout(function() {
                            dxWaterfall.frequencyChanging = false;
                        }, 50); // Very short delay just to show the message briefly
                    }

                    // Set a timeout to unlock if radio doesn't confirm within 3 seconds
                    setTimeout(function() {
                        if (typeof window.dxwaterfall_cat_debounce_lock !== 'undefined' && window.dxwaterfall_cat_debounce_lock === 1) {
                            window.dxwaterfall_cat_debounce_lock = 0;
                            window.dxwaterfall_expected_frequency = null;
                            // Also clear CAT tuning flag on timeout and force cache refresh
                            if (typeof dxWaterfall !== 'undefined') {
                                dxWaterfall.catTuning = false;
                                dxWaterfall.frequencyChanging = false;
                                dxWaterfall.catTuningStartTime = null;
                                dxWaterfall.spotNavigating = false; // Clear navigation flag on timeout
                                // Force immediate cache refresh and visual update when timeout occurs
                                dxWaterfall.refreshFrequencyCache();
                                if (dxWaterfall.canvas && dxWaterfall.ctx) {
                                    dxWaterfall.ctx.clearRect(0, 0, dxWaterfall.canvas.width, dxWaterfall.canvas.height);
                                    dxWaterfall.refresh();
                                }
                            }
                        }
                    }, 3000);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    // Clear frequency changing flag on error
                    if (typeof dxWaterfall !== 'undefined') {
                        dxWaterfall.frequencyChanging = false;
                        dxWaterfall.catTuning = false; // Clear CAT tuning flag on error
                        dxWaterfall.spotNavigating = false; // Clear navigation flag on error
                        // Force clear canvas on error too
                        if (dxWaterfall.canvas && dxWaterfall.ctx) {
                            dxWaterfall.ctx.clearRect(0, 0, dxWaterfall.canvas.width, dxWaterfall.canvas.height);
                        }
                    }

                    // Clear lock on error
                    if (typeof window.dxwaterfall_cat_debounce_lock !== 'undefined') {
                        window.dxwaterfall_cat_debounce_lock = 0;
                        window.dxwaterfall_expected_frequency = null;
                    }

                    // Only log if it's not a simple timeout or network issue
                    if (textStatus !== 'timeout' && jqXHR.status !== 0) {
                        if (jqXHR.responseText) {
                            console.warn('DX Waterfall: CAT command failed: Response text:', jqXHR.responseText);
                        }
                    }
                    // Silently fall through to manual frequency setting
                }
            });
            return;
    }

    // CAT not available - use manual frequency setting
    // Don't trigger change events to avoid side effects like form clearing
    $('#frequency').val(formattedFreq);

    // Only trigger change if this is NOT from waterfall (external frequency change)
    if (!fromWaterfall) {
        $('#frequency').trigger('change');
    }

    // Clear navigation flags immediately since no CAT operation is happening
    if (typeof dxWaterfall !== 'undefined') {
        dxWaterfall.frequencyChanging = false;
        dxWaterfall.catTuning = false; // No CAT, so no CAT tuning
        dxWaterfall.spotNavigating = false; // Clear navigation flag immediately
        // Invalidate frequency cache to ensure waterfall updates immediately
        if (dxWaterfall.invalidateFrequencyCache) {
            dxWaterfall.invalidateFrequencyCache();
        }
    }
}

$(document).ready(function() {
    // Function to try initializing the canvas with retries
    function tryInitCanvas() {
        if (document.getElementById('dxWaterfall')) {
            // Canvas found, initialize normally
            dxWaterfall.init(); // This calls refresh() which calls fetchDxSpots()

            // Set up periodic refresh - faster during CAT operations for spinner animation
            setInterval(function() {
                if (dxWaterfall.catTuning || dxWaterfall.frequencyChanging) {
                    // Fast refresh during CAT operations for spinner animation
                    dxWaterfall.refresh();
                } else {
                    // Normal refresh when idle
                    dxWaterfall.refresh();
                }
            }, DX_WATERFALL_CONSTANTS.VISUAL.STATIC_NOISE_REFRESH_MS); // Faster refresh for smooth spinner animation

            // Set up DX spots fetching at regular intervals
            setInterval(function() {
                dxWaterfall.fetchDxSpots(true, false); // Background fetch - NOT user-initiated
            }, DX_WATERFALL_CONSTANTS.DEBOUNCE.DX_SPOTS_FETCH_INTERVAL_MS);

            // Initial fetch is handled by init() -> refresh() -> fetchDxSpots()
            // No need for additional delayed fetch
        } else {
            // Canvas not found, try again in 100ms
            setTimeout(tryInitCanvas, 100);
        }
    }

    // Start trying to initialize
    tryInitCanvas();

    // Handle window resize to prevent canvas stretching
    $(window).on('resize', function() {
        // Immediately update canvas dimensions to prevent stretching
        dxWaterfall.updateDimensions();
    });

    // Handle click on the copy icon in dxWaterfallSpot div to populate callsign
    $('#dxWaterfallSpot').on('click', '.copy-icon', function(e) {
        e.stopPropagation(); // Prevent event bubbling

        var spotData = {
            callsign: $(this).data('callsign'),
            mode: $(this).data('mode'),
            sotaRef: $(this).data('sota-ref'),
            potaRef: $(this).data('pota-ref'),
            iotaRef: $(this).data('iota-ref'),
            wwffRef: $(this).data('wwff-ref')
        };

        if (spotData.callsign) {
            // Use the utility function to populate the form
            DX_WATERFALL_UTILS.qsoForm.populateFromSpot(spotData, true);

            // Visual feedback - briefly change icon color
            var icon = $(this);
            icon.css('color', '#FFFF00');
            setTimeout(function() {
                icon.css('color', '#FFFFFF');
            }, 200);
        }
    });

    // Handle click on the cycle icon in dxWaterfallSpot div to cycle through spots
    $('#dxWaterfallSpot').on('click', '.cycle-spot-icon', function(e) {
        e.stopPropagation(); // Prevent event bubbling

        // Cycle to next spot
        if (dxWaterfall.relevantSpots.length > 1) {
            dxWaterfall.currentSpotIndex = (dxWaterfall.currentSpotIndex + 1) % dxWaterfall.relevantSpots.length;

            // Force update of spot info display
            dxWaterfall.lastSpotInfoText = null; // Reset to force update
            dxWaterfall.updateSpotInfoDiv();

            // Clear QSO form first
            DX_WATERFALL_UTILS.qsoForm.clearForm();

            // Populate form with the new spot data after delay
            setTimeout(function() {
                var spotInfo = dxWaterfall.getSpotInfo();
                if (spotInfo) {
                    DX_WATERFALL_UTILS.qsoForm.populateFromSpot(spotInfo, true);
                }
            }, 100);

            // Visual feedback - briefly change icon color
            var icon = $(this);
            icon.css('color', '#FFFF00');
            setTimeout(function() {
                icon.css('color', '#FFFFFF');
            }, 200);
        }
    });

    // Handle click on the tune icon in dxWaterfallSpot div to set frequency
    $('#dxWaterfallSpot').on('click', '.tune-icon', function(e) {
        e.stopPropagation(); // Prevent event bubbling

        var frequency = parseFloat($(this).data('frequency'));
        var mode = $(this).data('mode');

        if (frequency) {
            // Set the mode if available - use skipTrigger=true to prevent change events
            // This prevents the form from being cleared by event handlers
            if (mode) {
                setMode(mode, true); // Skip triggering change event
            }

            // Use helper function to set frequency
            // fromWaterfall=true prevents frequency change event from being triggered
            setFrequency(frequency, true);

            // Visual feedback - briefly change icon color
            var icon = $(this);
            icon.css('color', '#FFFF00');
            setTimeout(function() {
                icon.css('color', '#FFFFFF');
            }, 200);
        }
    });

    // Handle click on zoom in button
    $('#dxWaterfallMenu').on('click', '.zoom-in-icon:not(.disabled)', function(e) {
        e.stopPropagation();
        e.preventDefault();

        dxWaterfall.zoomIn();
    });

    // Handle click on zoom out button
    $('#dxWaterfallMenu').on('click', '.zoom-out-icon:not(.disabled)', function(e) {
        e.stopPropagation();
        e.preventDefault();

        dxWaterfall.zoomOut();
    });

    // Handle click on zoom reset button
    $('#dxWaterfallMenu').on('click', '.zoom-reset-icon:not(.disabled)', function(e) {
        e.stopPropagation();
        e.preventDefault();

        dxWaterfall.resetZoom();
    });

    // Handle click on previous band spot button
    $('#dxWaterfallMenu').on('click', '.prev-spot-icon:not(.disabled)', function(e) {
        e.stopPropagation();
        e.preventDefault();

        dxWaterfall.prevSpot();
    });

    // Handle click on next band spot button
    $('#dxWaterfallMenu').on('click', '.next-spot-icon:not(.disabled)', function(e) {
        e.stopPropagation();
        e.preventDefault();

        dxWaterfall.nextSpot();
    });

    // Handle click on Smart DX Hunter icon
    $('#dxWaterfallMenu').on('click', '.smart-hunter-icon:not(.disabled)', function(e) {
        e.stopPropagation();
        e.preventDefault();

        dxWaterfall.nextSmartHunterSpot();
    });

    // Handle click on Smart DX Hunter text
    $('#dxWaterfallMenu').on('click', '.smart-hunter-text:not(.disabled)', function(e) {
        e.stopPropagation();
        e.preventDefault();

        dxWaterfall.nextSmartHunterSpot();
    });

    // Handle click on continent cycle icon
    $('#dxWaterfallMenu').on('click', '.continent-cycle-icon:not(.disabled)', function(e) {
        e.stopPropagation();
        e.preventDefault();

        dxWaterfall.cycleContinent();
    });

    // Handle click on continent cycle text
    $('#dxWaterfallMenu').on('click', '.continent-cycle-text:not(.disabled)', function(e) {
        e.stopPropagation();
        e.preventDefault();

        dxWaterfall.cycleContinent();
    });

    // Handle click on mode filter - Phone
    $('#dxWaterfallMenu').on('click', '.mode-filter-phone:not(.disabled)', function(e) {
        e.stopPropagation();
        e.preventDefault();

        dxWaterfall.toggleModeFilter('phone');
    });

    // Handle click on mode filter - CW
    $('#dxWaterfallMenu').on('click', '.mode-filter-cw:not(.disabled)', function(e) {
        e.stopPropagation();
        e.preventDefault();

        dxWaterfall.toggleModeFilter('cw');
    });

    // Handle click on mode filter - Digi
    $('#dxWaterfallMenu').on('click', '.mode-filter-digi:not(.disabled)', function(e) {
        e.stopPropagation();
        e.preventDefault();

        dxWaterfall.toggleModeFilter('digi');
    });

    // Handle canvas click events for frequency detection
    DX_WATERFALL_UTILS.dom.getWaterfall().on('click', function(e) {
        // Don't handle clicks when frequency is changing
        if (dxWaterfall.frequencyChanging) {
            return; // Block clicks during frequency changes
        }

        // Don't handle clicks when waiting for data
        if (dxWaterfall.waitingForData && !dxWaterfall.dataReceived) {
            return; // Do nothing while waiting for cluster data
        }

        // Get click coordinates relative to canvas
        var canvas = this;
        var rect = canvas.getBoundingClientRect();
        var x = e.clientX - rect.left;
        var y = e.clientY - rect.top;

        // Calculate frequency at clicked position
        var centerX = canvas.width / 2;
        var middleFreq = dxWaterfall.getCachedMiddleFreq(); // Use cached frequency
        var pixelsPerKHz = dxWaterfall.getPixelsPerKHz(); // Mode-aware scaling

        // Calculate frequency offset from center
        var pixelOffset = x - centerX;
        var freqOffset = pixelOffset / pixelsPerKHz;
        var clickedFreq = middleFreq + freqOffset;

        // Round to 3 decimal places (1 Hz precision in kHz)
        clickedFreq = Math.round(clickedFreq * 1000) / 1000;

        // Prevent setting frequency <= 0 (not physically valid)
        if (clickedFreq <= 0) {
            return; // Ignore clicks in invalid frequency range
        }

        // Check if frequency is far outside current band and update band if needed
        if (dxWaterfall.isFrequencyFarOutsideBand(clickedFreq)) {
            dxWaterfall.updateBandFromFrequency(clickedFreq);
        }

        // Set the frequency to where user clicked
        setFrequency(clickedFreq, true);

        // Update cache directly (same as navigation fix)
        var formattedFreq = Math.round(clickedFreq * 1000); // Convert to Hz
        dxWaterfall.cache.middleFreq = clickedFreq;
        dxWaterfall.lastFreqInput = formattedFreq;
        dxWaterfall.lastValidCommittedFreq = formattedFreq;
        dxWaterfall.lastValidCommittedUnit = 'kHz';

        // Try to find a nearby spot at this frequency and populate QSO form
        var spotInfo = dxWaterfall.getSpotInfo();

        if (spotInfo) {
            // Clear the QSO form first
            DX_WATERFALL_UTILS.qsoForm.clearForm();

            // Populate from the spot
            setTimeout(function() {
                DX_WATERFALL_UTILS.qsoForm.populateFromSpot(spotInfo, true);
            }, 100);
        }

        // Commit the new frequency
        setTimeout(function() {
            dxWaterfall.commitFrequency();
        }, 50);
    });

    // Handle keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Block keyboard shortcuts when frequency is changing
        if (dxWaterfall.frequencyChanging) {
            return; // Don't handle keys during frequency changes
        }

        // Use Cmd on Mac, Ctrl on Windows/Linux
        var modKey = DX_WATERFALL_UTILS.platform.isModifierKey(e);

        // Ctrl/Cmd+Left: Previous spot
        if (modKey && !e.shiftKey && e.key === 'ArrowLeft') {
            e.preventDefault();
            dxWaterfall.prevSpot();
        }
        // Ctrl/Cmd+Right: Next spot
        else if (modKey && !e.shiftKey && e.key === 'ArrowRight') {
            e.preventDefault();
            dxWaterfall.nextSpot();
        }
        // Ctrl/Cmd+Up: Jump to last spot in band
        else if (modKey && !e.shiftKey && e.key === 'ArrowUp') {
            e.preventDefault();
            dxWaterfall.lastSpot();
        }
        // Ctrl/Cmd+Down: Jump to first spot in band
        else if (modKey && !e.shiftKey && e.key === 'ArrowDown') {
            e.preventDefault();
            dxWaterfall.firstSpot();
        }
        // Ctrl/Cmd++: Zoom in
        else if (modKey && !e.shiftKey && (e.key === '+' || e.key === '=')) {
            e.preventDefault();
            dxWaterfall.zoomIn();
        }
        // Ctrl/Cmd+-: Zoom out
        else if (modKey && !e.shiftKey && (e.key === '-' || e.key === '_')) {
            e.preventDefault();
            dxWaterfall.zoomOut();
        }
        // Ctrl/Cmd+Shift+Space: Tune to current spot frequency
        else if (modKey && e.shiftKey && e.key === ' ') {
            e.preventDefault();
            // Find the tune icon in the spot info div and trigger it
            var tuneIcon = $('#dxWaterfallSpot .tune-icon');
            if (tuneIcon.length > 0) {
                tuneIcon.trigger('click');
            }
        }
        // Ctrl/Cmd+Space: Copy callsign from current spot info
        else if (modKey && !e.shiftKey && e.key === ' ') {
            e.preventDefault();
            // Find the copy icon in the spot info div and trigger it
            var copyIcon = $('#dxWaterfallSpot .copy-icon');
            if (copyIcon.length > 0) {
                copyIcon.trigger('click');
            }
        }
    });
});

