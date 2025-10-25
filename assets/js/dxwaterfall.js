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
        ZOOM_ICON_FEEDBACK_MS: 150,        	// Visual feedback duration for zoom icons
        MODE_CHANGE_SETTLE_MS: 200,        	// Delay for radio mode change to settle
        FORM_POPULATE_DELAY_MS: 50,        	// Delay before populating QSO form
        SPOT_NAVIGATION_COMPLETE_MS: 100,  	// Delay for spot navigation completion
        ZOOM_MENU_UPDATE_DELAY_MS: 150     	// Delay for zoom menu update after navigation
    },

    // CAT and radio control
    // Note: Some values are initialized and will be recalculated based on catPollInterval
    CAT: {
        POLL_INTERVAL_MS: 3000,           	// Default CAT polling interval (can be overridden by config)
        TUNING_FLAG_FALLBACK_MS: 4500,    	// Fallback timeout for tuning flags (1.5x poll interval)
        FREQUENCY_WAIT_TIMEOUT_MS: 6000,  	// Initial load wait time for CAT frequency (2x poll interval)

        // WebSocket timing (low latency)
        WEBSOCKET_CONFIRM_TIMEOUT_MS: 500,  // WebSocket: Fast confirmation timeout (vs 3000ms polling)
        WEBSOCKET_FALLBACK_TIMEOUT_MS: 750, // WebSocket: Fast fallback timeout (vs 1.5x poll interval)
        WEBSOCKET_COMMIT_DELAY_MS: 20,      // WebSocket: Fast commit delay (vs 50ms polling)

        // Polling timing (standard latency)
        POLLING_CONFIRM_TIMEOUT_MS: 3000,   // Polling: Standard confirmation timeout
        POLLING_COMMIT_DELAY_MS: 50,        // Polling: Standard commit delay (from DEBOUNCE.FREQUENCY_COMMIT_SHORT_MS)

        // Auto-populate timing
        TUNING_STOPPED_DELAY_MS: 1000       // Delay after user stops tuning to auto-populate spot (1 second)
    },

    // Visual timing
    VISUAL: {
        STATIC_NOISE_REFRESH_MS: 100      	// Static noise animation frame rate (100ms = 10fps, twice as fast as before)
    },

    // Cookie configuration
    COOKIE: {
        NAME_FONT_SIZE: 'dxwaterfall_fontsize',  // Cookie name for font size
        NAME_MODE_FILTERS: 'dxwaterfall_modefilters', // Cookie name for mode filters
        EXPIRY_DAYS: 365                         // Cookie expiration in days
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
        SPOT_FREQUENCY_MATCH: 0.01,        	// Frequency match tolerance for spot navigation (kHz)
        CAT_FREQUENCY_HZ: 1000,            	// CAT frequency confirmation tolerance (Hz)
        FREQUENCY_MATCH_KHZ: 0.1,          	// General frequency matching tolerance (kHz)
        CENTER_SPOT_TOLERANCE_KHZ: 0.1     	// Tolerance for center spot frequency matching (kHz)
    },

    // Zoom levels configuration
    ZOOM: {
        DEFAULT_LEVEL: 3,                 	// Default zoom level
        MAX_LEVEL: 5,                       // Maximum zoom level
        MIN_LEVEL: 0,                       // Minimum zoom level
        // Pixels per kHz for each zoom level
        PIXELS_PER_KHZ: {
            0: 2,   // ±50 kHz view (widest - new level)
            1: 4,   // ±25 kHz view
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
        BLACK: '#000000',
        WHITE: '#FFFFFF',
        OVERLAY_BACKGROUND: 'rgba(0, 0, 0, 0.7)',
        OVERLAY_DARK_GREY: 'rgba(64, 64, 64, 0.7)',
        TOOLTIP_BACKGROUND: 'rgba(0, 0, 0, 0.9)',

        // Frequency ruler
        RULER_BACKGROUND: '#000000bb',
        RULER_LINE: '#888888',
        RULER_TEXT: '#888888',
        INVALID_FREQUENCY_OVERLAY: 'rgba(128, 128, 128, 0.5)',

        // Center marker and bandwidth
        CENTER_MARKER: '#FF0000',
        CENTER_MARKER_RX: '#00FF00', // Green for RX in split operation
        CENTER_MARKER_TX: '#FF0000', // Red for TX in split operation
        RED: '#FF0000',
        GREEN: '#00FF00',
        BANDWIDTH_INDICATOR: 'rgba(255, 255, 0, 0.3)',

        // Messages and text
        MESSAGE_TEXT_WHITE: '#FFFFFF',
        WATERFALL_LINK: '#888888',
        GREY: '#888888',

        // Static noise RGB components
        STATIC_NOISE_RGB: {R: 34, G: 34, B: 34}, // Base RGB values for noise generation

        // DX Spots by mode
        SPOT_PHONE: '#00FF00',
        SPOT_CW: '#FFA500',
        SPOT_DIGI: '#0096FF',
        SPOT_OTHER: 'rgba(160, 32, 240, ', // Purple - incomplete for opacity

        // Spot status colors
        GREEN: '#00FF00',        // Confirmed
        ORANGE: '#FFA500',       // Worked

        // Spot mode color bases (for rgba with variable opacity)
        PHONE_RGB: 'rgba(0, 255, 0, ',      // Green
        CW_RGB: 'rgba(255, 165, 0, ',       // Orange
        DIGI_RGB: 'rgba(0, 150, 255, ',     // Blue
        OTHER_RGB: 'rgba(160, 32, 240, ',   // Purple

        // Band limits
        OUT_OF_BAND: 'rgba(255, 0, 0, 0.2)',
        OUT_OF_BAND_BORDER_LIGHT: 'rgba(255, 0, 0, 0.3)',
        OUT_OF_BAND_BORDER_DARK: 'rgba(255, 0, 0, 0.6)'
    },

    // Font configurations
    FONTS: {
        FAMILY: '"Consolas", "Courier New", monospace',
        RULER: '11px "Consolas", "Courier New", monospace',
        CENTER_MARKER: '12px "Consolas", "Courier New", monospace',
        SPOT_LABELS: 'bold 13px "Consolas", "Courier New", monospace',  // Increased from 12px to 13px (5% larger)
        SPOT_INFO: '11px "Consolas", "Courier New", monospace',
        WAITING_MESSAGE: '14px "Consolas", "Courier New", monospace',
        TITLE_LARGE: 'bold 24px "Consolas", "Courier New", monospace',
        FREQUENCY_CHANGE: '14px "Consolas", "Courier New", monospace',
        OUT_OF_BAND: 'bold 14px "Consolas", "Courier New", monospace',
        SMALL_MONO: '12px "Consolas", "Courier New", monospace',

        // Label size configurations (x-small, small, medium, large, x-large)
        LABEL_SIZES: [11, 12, 13, 15, 17],        // Regular spot label sizes
        LABEL_HEIGHTS: [13, 14, 15, 17, 19]       // Heights for overlap detection
    },

    // Available continents for cycling
    CONTINENTS: ['AF', 'AN', 'AS', 'EU', 'NA', 'OC', 'SA'],

    // Mode classification lists (consolidated from multiple locations)
    MODE_LISTS: {
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
    },

    // Logo configuration
    LOGO_FILENAME: 'assets/logo/wavelog_logo_darkly_wide.png',

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

/**
 * Get CAT timing based on connection type (WebSocket vs Polling)
 * WebSocket connections have much lower latency and can use shorter timeouts
 * @returns {object} - Object with timeout values appropriate for current connection type
 */
function getCATTimings() {
    var isWebSocket = typeof dxwaterfall_cat_state !== 'undefined' && dxwaterfall_cat_state === 'websocket';

    if (isWebSocket) {
        return {
            confirmTimeout: DX_WATERFALL_CONSTANTS.CAT.WEBSOCKET_CONFIRM_TIMEOUT_MS,
            fallbackTimeout: DX_WATERFALL_CONSTANTS.CAT.WEBSOCKET_FALLBACK_TIMEOUT_MS,
            commitDelay: DX_WATERFALL_CONSTANTS.CAT.WEBSOCKET_COMMIT_DELAY_MS
        };
    } else {
        return {
            confirmTimeout: DX_WATERFALL_CONSTANTS.CAT.POLLING_CONFIRM_TIMEOUT_MS,
            fallbackTimeout: DX_WATERFALL_CONSTANTS.CAT.TUNING_FLAG_FALLBACK_MS,
            commitDelay: DX_WATERFALL_CONSTANTS.CAT.POLLING_COMMIT_DELAY_MS
        };
    }
}

/**
 * Handle CAT frequency update with adaptive debounce
 * Uses getCATTimings() to apply appropriate delays for WebSocket (fast) vs Polling (slow)
 * Returns true if frequency should be updated, false if blocked by debounce
 * Also handles frequency confirmation and cache invalidation
 * @param {number} radioFrequency - Frequency from CAT in Hz
 * @param {Function} updateCallback - Function to call if update should proceed
 * @returns {boolean} - True if update was allowed, false if blocked
 */
function handleCATFrequencyUpdate(radioFrequency, updateCallback) {
    // Get adaptive timing based on connection type (WebSocket vs Polling)
    var timings = getCATTimings();
    var now = Date.now();

    // Check if we're in a debounce period
    if (typeof window.catFrequencyDebounce !== 'undefined' && window.catFrequencyDebounce) {
        var timeSinceLastUpdate = now - (window.catFrequencyDebounce.lastUpdate || 0);

        // If we're within the commit delay window, skip this update
        if (timeSinceLastUpdate < timings.commitDelay) {
            console.log('[DX Waterfall] CAT DEBOUNCE: Skipping update (within ' + timings.commitDelay + 'ms window, ' + timeSinceLastUpdate + 'ms since last)');
            return false;
        }
    }

    // Initialize debounce tracking if needed
    if (typeof window.catFrequencyDebounce === 'undefined') {
        window.catFrequencyDebounce = { lastUpdate: 0 };
    }

    // Update debounce timestamp
    window.catFrequencyDebounce.lastUpdate = now;

    // Check if frequency actually changed BEFORE updating UI
    var frequencyChanged = false;
    var isInitialLoad = false;

    if (typeof dxWaterfall !== 'undefined' && dxWaterfall.lastValidCommittedFreq !== null && dxWaterfall.lastValidCommittedUnit) {
        // Compare incoming CAT frequency with last committed value
        // CAT sends frequency in Hz, convert to kHz for comparison
        var lastKhz = DX_WATERFALL_UTILS.frequency.convertToKhz(
            dxWaterfall.lastValidCommittedFreq,
            dxWaterfall.lastValidCommittedUnit
        );
        var incomingHz = parseFloat(radioFrequency);
        var incomingKhz = incomingHz / 1000; // Convert Hz to kHz
        var tolerance = 0.001; // 1 Hz
        var diff = Math.abs(incomingKhz - lastKhz);
        frequencyChanged = diff > tolerance;

        console.log('[DX Waterfall] CAT CHECK: incoming=' + incomingHz + ' Hz (' + incomingKhz + ' kHz), last=' + lastKhz + ' kHz, diff=' + diff + ' kHz, changed=' + frequencyChanged);
    } else if (typeof dxWaterfall !== 'undefined') {
        // First time - consider it changed
        isInitialLoad = dxWaterfall.waitingForCATFrequency;
        frequencyChanged = true;
        console.log('[DX Waterfall] CAT CHECK: First time, isInitialLoad=' + isInitialLoad);
    }

    // Always update UI
    if (updateCallback) updateCallback();

    // Only invalidate cache and commit if frequency actually changed
    if (typeof dxWaterfall !== 'undefined' && (frequencyChanged || isInitialLoad)) {
        // IMPORTANT: Commit BEFORE invalidating cache
        if (dxWaterfall.commitFrequency) {
            dxWaterfall.commitFrequency();
        }
        if (dxWaterfall.invalidateFrequencyCache) {
            dxWaterfall.invalidateFrequencyCache();
        }

        // Auto-populate spot when user stops tuning (debounced)
        // Clear any existing auto-populate timer
        if (window.catFrequencyDebounce.autoPopulateTimer) {
            clearTimeout(window.catFrequencyDebounce.autoPopulateTimer);
            window.catFrequencyDebounce.autoPopulateTimer = null;
        }

        // Set new timer to auto-populate after user stops tuning
        // Only do this if frequency change was NOT initiated by waterfall (spotNavigating would be true)
        if (!dxWaterfall.spotNavigating) {
            window.catFrequencyDebounce.autoPopulateTimer = setTimeout(function() {
                console.log('[DX Waterfall] AUTO-POPULATE: User stopped tuning, checking for nearby spot');

                // Get current spot at this frequency
                var currentSpotInfo = dxWaterfall.getSpotInfo ? dxWaterfall.getSpotInfo() : null;

                if (currentSpotInfo && currentSpotInfo.callsign) {
                    // Create unique identifier for this spot
                    var currentSpotId = currentSpotInfo.callsign + '_' + currentSpotInfo.frequency + '_' + (currentSpotInfo.mode || '');

                    // Only populate if this is a DIFFERENT spot than what's already populated
                    if (dxWaterfall.lastPopulatedSpot !== currentSpotId) {
                        console.log('[DX Waterfall] AUTO-POPULATE: New spot detected (' + currentSpotInfo.callsign + '), populating form');
                        if (dxWaterfall.checkAndPopulateSpotAtFrequency) {
                            dxWaterfall.checkAndPopulateSpotAtFrequency();
                        }
                    } else {
                        console.log('[DX Waterfall] AUTO-POPULATE: Still within same spot area (' + currentSpotInfo.callsign + '), skipping re-populate');
                    }
                } else {
                    console.log('[DX Waterfall] AUTO-POPULATE: No spot at current frequency');
                    // Clear last populated spot since we're not on any spot
                    dxWaterfall.lastPopulatedSpot = null;
                }

                window.catFrequencyDebounce.autoPopulateTimer = null;
            }, DX_WATERFALL_CONSTANTS.CAT.TUNING_STOPPED_DELAY_MS);
        } else {
            console.log('[DX Waterfall] AUTO-POPULATE: Skipping (spotNavigating active)');
        }
    }

    return true;
}

/**
 * Check if CAT control is available
 * @returns {boolean} - True if CAT is available (polling or websocket)
 */
function isCATAvailable() {
    var state = typeof dxwaterfall_cat_state !== 'undefined' ? dxwaterfall_cat_state : 'undefined';
    var result = (typeof dxwaterfall_cat_state !== 'undefined' &&
            dxwaterfall_cat_state !== null &&
            (dxwaterfall_cat_state === "polling" || dxwaterfall_cat_state === "websocket") &&
            typeof tuneRadioToFrequency === 'function');
    return result;
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

    // Cookie utilities
    cookie: {
        /**
         * Set a cookie
         * @param {string} name - Cookie name
         * @param {string} value - Cookie value
         * @param {number} days - Days until expiration
         */
        set: function(name, value, days) {
            var expires = "";
            if (days) {
                var date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = "; expires=" + date.toUTCString();
            }
            document.cookie = name + "=" + (value || "") + expires + "; path=/";
        },

        /**
         * Get a cookie value
         * @param {string} name - Cookie name
         * @returns {string|null} Cookie value or null if not found
         */
        get: function(name) {
            var nameEQ = name + "=";
            var ca = document.cookie.split(';');
            for (var i = 0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) === ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
            }
            return null;
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

    // Field mapping utilities - allows pages to remap field IDs
    fieldMapping: {
        /**
         * Get the actual field ID for a logical field name
         * @param {string} fieldName - Logical field name (e.g., 'callsign', 'freq_calculated')
         * @param {boolean} isOptional - Whether this is an optional field
         * @returns {string} Actual DOM element ID
         */
        getFieldId: function(fieldName, isOptional) {
            isOptional = isOptional || false;

            // Check if page has defined a field mapping
            if (window.DX_WATERFALL_FIELD_MAP) {
                var category = isOptional ? 'optional' : 'required';
                if (window.DX_WATERFALL_FIELD_MAP[category] &&
                    window.DX_WATERFALL_FIELD_MAP[category][fieldName]) {
                    return window.DX_WATERFALL_FIELD_MAP[category][fieldName];
                }
            }

            // Fallback to default field name (for backward compatibility)
            return fieldName;
        },

        /**
         * Get jQuery element for a field by logical name
         * @param {string} fieldName - Logical field name
         * @param {boolean} isOptional - Whether this is an optional field
         * @returns {jQuery} jQuery element or empty jQuery object if not found
         */
        getField: function(fieldName, isOptional) {
            var fieldId = this.getFieldId(fieldName, isOptional);
            return $('#' + fieldId);
        },

        /**
         * Check if an optional field exists on the page
         * @param {string} fieldName - Logical field name
         * @returns {boolean} True if field exists in DOM
         */
        hasOptionalField: function(fieldName) {
            // Use custom checker if provided
            if (window.DX_WATERFALL_HAS_FIELD && typeof window.DX_WATERFALL_HAS_FIELD === 'function') {
                return window.DX_WATERFALL_HAS_FIELD(fieldName);
            }

            // Fallback to checking DOM
            var fieldId = this.getFieldId(fieldName, true);
            return document.getElementById(fieldId) !== null;
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
                    return DX_WATERFALL_CONSTANTS.COLORS.PHONE_RGB;
                case 'cw':
                    return DX_WATERFALL_CONSTANTS.COLORS.CW_RGB;
                case 'digi':
                    return DX_WATERFALL_CONSTANTS.COLORS.DIGI_RGB;
                default:
                    return DX_WATERFALL_CONSTANTS.COLORS.OTHER_RGB;
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

            // Phone modes from message (use constants)
            var phoneModes = DX_WATERFALL_CONSTANTS.MODE_LISTS.PHONE.slice(0, 5); // LSB, USB, SSB, AM, FM
            var phonePatterns = [
                { patterns: ['LSB'], submode: 'LSB' },
                { patterns: ['USB'], submode: 'USB' },
                { patterns: ['SSB'], submode: 'SSB' },
                { patterns: ['AM'], submode: 'AM' },
                { patterns: ['FM'], submode: 'FM' }
            ];

            // Optimized loop - breaks early on first match
            for (var i = 0; i < phonePatterns.length; i++) {
                var mode = phonePatterns[i];
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
            if (DX_WATERFALL_CONSTANTS.MODE_LISTS.CW.indexOf(mode) !== -1) {
                return { category: 'cw', submode: 'CW', confidence: 1 };
            }

            // Phone modes (use constants)
            if (DX_WATERFALL_CONSTANTS.MODE_LISTS.PHONE.indexOf(mode) !== -1) {
                return { category: 'phone', submode: mode, confidence: 1 };
            }

            // Digital modes - WSJT-X family (use constants)
            if (DX_WATERFALL_CONSTANTS.MODE_LISTS.WSJT.indexOf(mode) !== -1) {
                return { category: 'digi', submode: mode, confidence: 1 };
            }

            // PSK variants
            if (mode.indexOf('PSK') !== -1 || mode.indexOf('QPSK') !== -1 || mode.indexOf('8PSK') !== -1) {
                return { category: 'digi', submode: mode, confidence: 1 };
            }

            // Other digital modes (use constants)
            if (DX_WATERFALL_CONSTANTS.MODE_LISTS.DIGITAL_OTHER.indexOf(mode) !== -1) {
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

            // Set the mode if available - determine the actual radio mode
            if (spotData.mode) {
                // Use determineRadioMode to get the correct radio mode (same as clicking)
                var radioMode = DX_WATERFALL_UTILS.navigation.determineRadioMode(spotData);
                // Use skipTrigger=true to prevent change event race condition
                setMode(radioMode, true);
            } else {
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

                    // Clear navigation flag after form population completes
                    DX_WATERFALL_UTILS.navigation.navigating = false;
                }, 50);
            } else {
                // No lookup - clear navigation flag immediately
                DX_WATERFALL_UTILS.navigation.navigating = false;
            }
        }
    },

    // Navigation utilities for spot navigation
    navigation: {
        // Timer for pending navigation actions
        pendingNavigationTimer: null,
        // Flag to block interference during navigation
        navigating: false,

        /**
         * Determine the appropriate radio mode to set based on spot mode and frequency
         * @param {Object} spot - Spot object with mode and frequency
         * @returns {string} - The mode to set (CW, USB, LSB, RTTY, etc.)
         */
        determineRadioMode: function(spot) {

            if (!spot) {
                return 'USB'; // Default fallback
            }

            var spotMode = (spot.mode || '').toUpperCase();
            var frequency = parseFloat(spot.frequency); // Frequency in kHz


            // CW mode - always use CW
            if (DX_WATERFALL_CONSTANTS.MODE_LISTS.CW.indexOf(spotMode) !== -1) {
                return 'CW';
            }

            // Digital modes - use RTTY as the standard digital mode (use constants)
            var digiModes = DX_WATERFALL_CONSTANTS.MODE_LISTS.WSJT.concat(
                DX_WATERFALL_CONSTANTS.MODE_LISTS.PSK,
                DX_WATERFALL_CONSTANTS.MODE_LISTS.DIGITAL_MODES,
                DX_WATERFALL_CONSTANTS.MODE_LISTS.DIGITAL_VOICE,
                DX_WATERFALL_CONSTANTS.MODE_LISTS.DIGITAL_HF,
                DX_WATERFALL_CONSTANTS.MODE_LISTS.DIGITAL_OTHER
            );

            for (var i = 0; i < digiModes.length; i++) {
                if (spotMode.indexOf(digiModes[i]) !== -1) {
                    return 'RTTY';
                }
            }

            // Phone modes - determine USB or LSB based on frequency (use constants)
            var isPhoneMode = DX_WATERFALL_CONSTANTS.MODE_LISTS.PHONE.indexOf(spotMode) !== -1;

            if (isPhoneMode || !spotMode) {
                // Use frequency-based determination for phone modes or unknown modes
                // Use the same logic as bandwidth drawing for consistency
                var ssbMode = DX_WATERFALL_UTILS.modes.determineSSBMode(frequency);
                return ssbMode;
            }

            // For any other unrecognized mode, default to USB/LSB based on frequency
            var defaultMode = DX_WATERFALL_UTILS.modes.determineSSBMode(frequency);
            return defaultMode;
        },

        // Common navigation logic shared by all spot navigation functions
        navigateToSpot: function(waterfallContext, targetSpot, targetIndex) {

            if (!targetSpot) {
                return false;
            }

            // Set navigation flag to block refresh interference
            this.navigating = true;

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

            // CRITICAL: Set mode FIRST before calling setFrequency
            // setFrequency reads the mode from $('#mode').val(), so the mode must be set first
            var radioMode = this.determineRadioMode(targetSpot);

            // Set CAT debounce lock early to block incoming CAT updates during navigation
            if (typeof setFrequency.catDebounceLock !== 'undefined') {
                setFrequency.catDebounceLock = 1;
            }

            setMode(radioMode, true); // skipTrigger = true to prevent change event

            // Now set frequency - it will read the correct mode from the dropdown
            setFrequency(targetSpot.frequency, true); // Pass true to indicate waterfall-initiated change

            // Send frequency command again after short delay to correct any drift from mode change
            // (radio control lib bug: mode change can cause slight frequency shift)
            setTimeout(function() {
                setFrequency(targetSpot.frequency, true);
            }, DX_WATERFALL_CONSTANTS.DEBOUNCE.MODE_CHANGE_SETTLE_MS);                // Manually set the frequency in the input field immediately
                var formattedFreq = Math.round(targetSpot.frequency * 1000); // Convert to Hz
                $('#frequency').val(formattedFreq);

                // CRITICAL: Directly update the cache to the target frequency
                // getCachedMiddleFreq() uses lastValidCommittedFreq which isn't updated by just setting the input value
                // So we bypass the cache and set it directly to ensure getSpotInfo() uses the correct frequency
                waterfallContext.cache.middleFreq = targetSpot.frequency; // Already in kHz
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
                        // Clear navigation flag after population completes
                        self.navigating = false;
                    }, DX_WATERFALL_CONSTANTS.DEBOUNCE.FORM_POPULATE_DELAY_MS);
                } else {
                    // Clear navigation flag immediately if no spot to populate
                    this.navigating = false;
                }
                // If no spot found, form remains cleared (already cleared above)

                // Commit the new frequency
                setTimeout(function() {
                    waterfallContext.commitFrequency();
                }, 50);

                // Update zoom menu immediately to reflect navigation button states
                // (no delay needed since we already updated the cache above)
                waterfallContext.updateZoomMenu();
            }

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
    spotInfoDiv: null, // Reference to the dxWaterfallSpot div
    spotTooltipDiv: null, // Reference to the spot tooltip div
    lastSpotInfoKey: null, // Track last displayed spot to prevent unnecessary re-renders
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
    labelSizeLevel: 2, // 0 = x-small (11px), 1 = small (12px), 2 = medium (13px - default), 3 = large (15px), 4 = x-large (17px)

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
        lastQrgUnit: null,
        lastValidCommittedFreq: null, // Last VALID committed frequency
        lastValidCommittedUnit: null, // Last VALID committed unit

        // Visible spots caching
        visibleSpots: null, // Cached filtered and positioned spots
        visibleSpotsParams: null // Parameters used to generate cached visible spots
    },

    // State flags
    programmaticModeChange: false, // Flag to prevent fetching spots when mode is changed by waterfall
    programmaticBandUpdate: false, // Flag to indicate band was changed programmatically by CAT (not user)
    userChangedBand: false, // Flag to prevent auto band update when user manually changed band
    initializationComplete: false, // Flag to track if initial setup is done
    lastPopulatedSpot: null, // Track the last spot that was used to populate the form

    // Display configuration - centralized mapping for simplex/split operation
    displayConfig: {
        isSplit: false,
        centerFrequency: null,
        markers: [],
        showBandwidthIndicator: true
    },

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
    bandPlans: null, // Cached band plans from database
    bandEdgesData: null, // Raw band edges data with mode information for mode indicators
    currentRegion: null, // Current IARU region (1, 2, 3)
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
        console.log('[DX Waterfall] INIT: Starting initialization');
        this.canvas = document.getElementById('dxWaterfall');

        // Check if canvas element exists
        if (!this.canvas) {
            console.log('[DX Waterfall] INIT: Canvas element not found');
            return;
        }

        console.log('[DX Waterfall] INIT: Canvas found, setting up context');
        this.ctx = this.canvas.getContext('2d');
        var $waterfall = DX_WATERFALL_UTILS.dom.getWaterfall();
        this.canvas.width = $waterfall.width();
        this.canvas.height = $waterfall.height();

        // Get reference to spot info div and menu div
        this.spotInfoDiv = document.getElementById('dxWaterfallSpotContent');
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

        // Set up mousemove for spot label tooltips (efficient - only when mouse is over canvas)
        this.canvas.addEventListener('mousemove', function(e) {
            self.handleSpotLabelHover(e);
        });

        // Set page load time for waiting state management
        this.pageLoadTime = Date.now();
        this.operationStartTime = Date.now(); // Initialize operation timer

        // Load saved settings from cookies
        this.loadSettingsFromCookies();

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

            // Clear any stuck CAT tuning or frequency changing flags when user manually interacts
            // This ensures that even if CAT is in an error state, user can still change frequency
            if (self.catTuning || self.frequencyChanging) {
                self.catTuning = false;
                self.frequencyChanging = false;
                self.catTuningStartTime = null;
                // Force refresh to update the display
                self.updateZoomMenu();
            }

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

        // Set up adaptive timeout for CAT frequency to arrive
        // WebSocket connections are much faster, so use shorter timeout
        var timings = getCATTimings();
        var catWaitTimeout = timings.confirmTimeout; // 500ms for WebSocket, 3000ms for polling

        this.catFrequencyWaitTimer = setTimeout(function() {
            console.log('[DX Waterfall] INIT: CAT frequency wait timeout (' + catWaitTimeout + 'ms), proceeding with fetch');
            self.waitingForCATFrequency = false;
            // Trigger refresh which will now perform the initial fetch
            if (!self.initialFetchDone) {
                self.refresh();
            }
        }, catWaitTimeout);

        // Safety fallback: If we're still stuck after 10 seconds, force the initial fetch
        setTimeout(function() {
            if (!self.initialFetchDone && !self.dataReceived) {
                console.log('[DX Waterfall] INIT: 10-second safety timeout, forcing fetch');
                self.waitingForCATFrequency = false;
                if (self.catFrequencyWaitTimer) {
                    clearTimeout(self.catFrequencyWaitTimer);
                    self.catFrequencyWaitTimer = null;
                }
                self.initialFetchDone = true;
                self.fetchDxSpots(true, false);
            }
        }, 10000); // 10 second safety timeout

        console.log('[DX Waterfall] INIT: Complete, calling initial refresh');
        this.refresh();
    },

    // Check if current frequency input differs from last committed value
    // Returns true if frequency has changed, false if same
    hasFrequencyChanged: function() {
        // Safety check: return false if waterfall is not initialized
        if (!this.$freqCalculated || !this.$qrgUnit) {
            return false;
        }

        var currentInput = this.$freqCalculated.val();
        var currentUnit = this.$qrgUnit.text() || 'kHz';

        // If we don't have a last committed value, consider it changed
        if (this.lastValidCommittedFreq === null) {
            return true;
        }

        // Convert both frequencies to kHz for comparison (normalize units)
        var currentKhz = DX_WATERFALL_UTILS.frequency.convertToKhz(currentInput, currentUnit);
        var lastKhz = DX_WATERFALL_UTILS.frequency.convertToKhz(this.lastValidCommittedFreq, this.lastValidCommittedUnit);

        // Compare frequencies with 1 Hz tolerance (0.001 kHz) to account for floating point errors
        var tolerance = 0.001; // 1 Hz
        return Math.abs(currentKhz - lastKhz) > tolerance;
    },

    // Commit the current frequency value (called on blur or Enter key)
    // This prevents the waterfall from shifting while the user is typing
    commitFrequency: function() {
        // Safety check: return early if waterfall is not initialized (destroyed or not yet ready)
        if (!this.$freqCalculated || !this.$qrgUnit) {
            return;
        }

        var currentInput = this.$freqCalculated.val();
        var currentUnit = this.$qrgUnit.text() || 'kHz';

        console.log('[DX Waterfall] FREQ COMMIT:', currentInput, currentUnit);

        // If this is a valid frequency, save it as the last valid committed frequency
        var freqValue = parseFloat(currentInput) || 0;
        if (freqValue > 0) {
            this.lastValidCommittedFreq = currentInput;
            this.lastValidCommittedUnit = currentUnit;

            // CRITICAL: Update band from frequency BEFORE refresh() is called
            // This ensures programmaticBandUpdate flag is set before hasParametersChanged() runs
            // BUT only do this if the band actually needs to change (frequency is outside current band)
            var currentFreqKhz = DX_WATERFALL_UTILS.frequency.convertToKhz(freqValue, currentUnit);
            if (currentFreqKhz > 0) {
                var expectedBand = this.getFrequencyBand(currentFreqKhz);
                var currentBand = this.getCurrentBand();

                // Only update band if frequency's band differs from current band
                // This prevents overriding manual band changes when frequency is already in that band
                if (expectedBand && currentBand && expectedBand !== currentBand) {
                    this.updateBandFromFrequency(currentFreqKhz);
                }
            }

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
        // Get current values from form elements FIRST to detect immediate changes
        var currentBand = this.getCurrentBand();
        var currentMode = this.getCurrentMode();

        // Check if band changed (even during cooldown) and reset dataReceived flag immediately
        // This prevents old band data from being displayed while waiting for new band to fetch
        var bandChanged = (currentBand !== this.lastBand);
        if (bandChanged && this.lastBand !== null) {
            // Band changed - immediately mark as waiting for new data
            this.dataReceived = false;
            this.waitingForData = true;
        }

        // Note: We DON'T block during userChangedBand cooldown anymore
        // Instead, we allow the parameter check to proceed, but we handle the fetch differently
        // Manual band changes should still fetch spots (user expects to see new band data)

        console.log('[DX Waterfall] PARAMS CHECK: Band=' + currentBand + ', Mode=' + currentMode +
                    ' (last: Band=' + this.lastBand + ', Mode=' + this.lastMode + ')');

        // Check for invalid states that should prevent spot fetching
        var middleFreq = this.getCachedMiddleFreq(); // Returns frequency in kHz
        var isFrequencyInvalid = middleFreq <= 0;
        var isBandInvalid = !currentBand || currentBand === '' || currentBand.toLowerCase() === 'select';

        // If frequency or band is invalid, show waiting message but don't fetch spots
        if (isFrequencyInvalid || isBandInvalid) {
            console.log('[DX Waterfall] PARAMS CHECK: Invalid parameters (freq=' + middleFreq + ', band=' + currentBand + ')');
            this.waitingForData = true;
            this.dataReceived = false;
            this.relevantSpots = [];
            this.currentSpotIndex = 0;
            this.lastSpotInfoKey = null; // Reset spot info key
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
            console.log('[DX Waterfall] BAND CHANGE: ' + this.lastBand + ' -> ' + currentBand);

            // Invalidate band limits cache (band changed)
            this.bandLimitsCache = null;

            // Invalidate visible spots cache (new band means new spots)
            this.cache.visibleSpots = null;
            this.cache.visibleSpotsParams = null;

            // Only reset waiting state if we've already received initial data
            // This prevents treating initial parameter setting as a "change"
            if (this.lastBand !== null) {
                // Band changed after initial load, reset waiting state
                this.waitingForData = true;
                this.dataReceived = false;
                // Reset relevant spots array and index
                this.relevantSpots = [];
                this.currentSpotIndex = 0;
                this.lastSpotInfoKey = null; // Reset spot info key
                if (this.spotInfoDiv) {
                    this.spotInfoDiv.innerHTML = '&nbsp;';
                }
                // Reset timer for the new band fetch
                this.operationStartTime = Date.now();
                // Immediately update menu to show loading indicator
                this.updateZoomMenu();

                // Determine if this is a programmatic (CAT) change or manual user change
                // Programmatic changes bypass cooldown, manual changes set cooldown (if not already active)
                if (this.programmaticBandUpdate) {
                    console.log('[DX Waterfall] BAND CHANGE: Programmatic (CAT) - no cooldown, fetching immediately');
                    // Reset the flag immediately after use
                    this.programmaticBandUpdate = false;
                    // If there was a manual change cooldown active, cancel it (CAT takes priority)
                    if (this.userChangedBand) {
                        this.userChangedBand = false;
                    }
                } else if (!this.userChangedBand) {
                    // Manual change AND no cooldown already active
                    // This happens when waterfall detects band is outside frequency range
                    console.log('[DX Waterfall] BAND CHANGE: Auto-detected from frequency - setting 2s cooldown');
                    this.userChangedBand = true;
                    var self = this;
                    setTimeout(function() {
                        self.userChangedBand = false;
                    }, 2000);
                } else {
                    // Manual change and cooldown already active (from qso.js)
                    console.log('[DX Waterfall] BAND CHANGE: Manual user change - cooldown already active');
                }
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
        if (this.lastValidCommittedFreq !== currentInput || this.lastQrgUnit !== currentUnit) {
            this.lastValidCommittedFreq = currentInput;
            this.lastQrgUnit = currentUnit;

            // Convert to kHz using utility function
            this.cache.middleFreq = DX_WATERFALL_UTILS.frequency.convertToKhz(currentInput, currentUnit);
        }

        // Update split operation state and get display configuration
        this.updateSplitOperationState();

        return this.displayConfig.centerFrequency;
    },

    // Update split operation state and configure display parameters
    updateSplitOperationState: function() {
        // Check if frequency_rx field exists and has a value
        var frequencyRxValue = null;
        if (DX_WATERFALL_UTILS.fieldMapping.hasOptionalField('frequency_rx')) {
            var $frequencyRx = DX_WATERFALL_UTILS.fieldMapping.getField('frequency_rx', true);
            frequencyRxValue = $frequencyRx.val();
        }

        if (frequencyRxValue && frequencyRxValue != '' && parseFloat(frequencyRxValue) > 0) {
            // SPLIT OPERATION MODE
            var rxFreq = parseFloat(frequencyRxValue) / 1000; // Convert Hz to kHz
            var txFreq = this.cache.middleFreq; // TX is from main frequency field

            this.displayConfig = {
                isSplit: true,
                centerFrequency: rxFreq,           // Waterfall centered on RX
                markers: [
                    {
                        frequency: rxFreq,
                        color: DX_WATERFALL_CONSTANTS.COLORS.CENTER_MARKER_RX,
                        label: 'RX'
                    },
                    {
                        frequency: txFreq,
                        color: DX_WATERFALL_CONSTANTS.COLORS.CENTER_MARKER_TX,
                        label: 'TX'
                    }
                ],
                showBandwidthIndicator: false
            };
        } else {
            // SIMPLEX OPERATION MODE
            this.displayConfig = {
                isSplit: false,
                centerFrequency: this.cache.middleFreq,
                markers: [
                    {
                        frequency: this.cache.middleFreq,
                        color: DX_WATERFALL_CONSTANTS.COLORS.CENTER_MARKER,
                        label: 'CENTER'
                    }
                ],
                showBandwidthIndicator: true
            };
        }
    },

    // Force invalidate frequency cache - called when CAT updates frequency
    invalidateFrequencyCache: function() {
        // Safety check: Don't run if waterfall is not initialized
        if (!this.canvas) {
            return;
        }

        // Don't invalidate cache if user is actively editing frequency
        if (this.userEditingFrequency) {
            return;
        }

        var oldFreq = this.cache.middleFreq;

        // Track if this was clearing the initial CAT wait
        var wasWaitingForCAT = this.waitingForCATFrequency;

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

        // Only set completion overlay if:
        // 1. CAT is available AND
        // 2. This is NOT the initial load (we weren't waiting for CAT frequency) AND
        // 3. We've already received data (prevents overlay on first load) AND
        // 4. This was a waterfall-initiated frequency change (user clicked a spot, not turning radio dial)
        if (isCATAvailable() && !wasWaitingForCAT && this.dataReceived && this.spotNavigating) {
            // Set a temporary overlay flag to keep message visible while marker moves
            console.log('[DX Waterfall] OVERLAY: Setting completion overlay (wasWaitingForCAT=' + wasWaitingForCAT + ', dataReceived=' + this.dataReceived + ', spotNavigating=' + this.spotNavigating + ')');
            this.showingCompletionOverlay = true;
        } else {
            console.log('[DX Waterfall] OVERLAY: Skipping completion overlay (CAT available=' + isCATAvailable() + ', wasWaitingForCAT=' + wasWaitingForCAT + ', dataReceived=' + this.dataReceived + ', spotNavigating=' + this.spotNavigating + ')');
        }

        // Force immediate cache refresh and visual update to move marker
        this.lastFrequencyRefreshTime = 0; // Reset throttle to allow immediate refresh

        // Only refresh from DOM if CAT is available - otherwise keep waterfall frequency independent
        if (isCATAvailable()) {
            this.refreshFrequencyCache();
        }

        // Force immediate refresh to draw marker at new position (with overlay still visible)
        if (this.canvas && this.ctx) {
            this.refresh();
        }

        // Clear the overlay after marker has had time to move (only if we set it)
        if (isCATAvailable()) {
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
        // Safety check: Don't run if waterfall is not initialized
        if (!this.$freqCalculated || !this.$qrgUnit) {
            return;
        }

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
            this.lastQrgUnit = null;
            this.lastMarkerFreq = undefined;

            // Directly set the new frequency from DOM calculation
            this.cache.middleFreq = currentFreqFromDOM;

            // Also update committed frequency values to prevent getCachedMiddleFreq() conflicts
            // This ensures that getCachedMiddleFreq() will use the updated frequency instead of old committed values
            this.lastValidCommittedFreq = currentInput;
            this.lastValidCommittedUnit = currentUnit;

            // Check if there's a relevant spot at the new frequency and populate form
            this.checkAndPopulateSpotAtFrequency();
        }
    },

    // Check if there's a relevant spot at current frequency and populate the QSO form
    checkAndPopulateSpotAtFrequency: function() {
        // Get spot info at current frequency
        var spotInfo = this.getSpotInfo();

        if (spotInfo && spotInfo.callsign) {
            // Create a unique identifier for this spot
            var spotId = spotInfo.callsign + '_' + spotInfo.frequency + '_' + (spotInfo.mode || '');

            // Only populate if this is a different spot than the last one we populated
            if (this.lastPopulatedSpot !== spotId) {
                this.lastPopulatedSpot = spotId;

                // Clear the form first
                DX_WATERFALL_UTILS.qsoForm.clearForm();

                // Populate form with spot data after a short delay
                setTimeout(function() {
                    if (typeof DX_WATERFALL_UTILS !== 'undefined' &&
                        typeof DX_WATERFALL_UTILS.qsoForm !== 'undefined' &&
                        typeof DX_WATERFALL_UTILS.qsoForm.populateFromSpot === 'function') {
                        DX_WATERFALL_UTILS.qsoForm.populateFromSpot(spotInfo, true);
                    }
                }, DX_WATERFALL_CONSTANTS.DEBOUNCE.FORM_POPULATE_DELAY_MS);
            }
        } else {
            // No spot at current frequency, clear the last populated spot tracker
            this.lastPopulatedSpot = null;
        }
    },

    // Get the current label font based on labelSizeLevel
    getCurrentLabelFont: function() {
        var size = DX_WATERFALL_CONSTANTS.FONTS.LABEL_SIZES[this.labelSizeLevel] || 13;
        return 'bold ' + size + 'px ' + DX_WATERFALL_CONSTANTS.FONTS.FAMILY;
    },

    // Get the current CENTER label font (1px larger than regular labels)
    getCurrentCenterLabelFont: function() {
        var size = (DX_WATERFALL_CONSTANTS.FONTS.LABEL_SIZES[this.labelSizeLevel] || 13) + 1;
        return 'bold ' + size + 'px ' + DX_WATERFALL_CONSTANTS.FONTS.FAMILY;
    },

    // Get the current label height in pixels based on labelSizeLevel
    getCurrentLabelHeight: function() {
        return DX_WATERFALL_CONSTANTS.FONTS.LABEL_HEIGHTS[this.labelSizeLevel] || 15;
    },

    // ========================================
    // COOKIE MANAGEMENT
    // ========================================

    /**
     * Save font size to cookie
     */
    saveFontSizeToCookie: function() {
        DX_WATERFALL_UTILS.cookie.set(
            DX_WATERFALL_CONSTANTS.COOKIE.NAME_FONT_SIZE,
            this.labelSizeLevel.toString(),
            DX_WATERFALL_CONSTANTS.COOKIE.EXPIRY_DAYS
        );
    },

    /**
     * Load font size from cookie
     * @returns {number|null} Font size level (0-4) or null if not found
     */
    loadFontSizeFromCookie: function() {
        var cookieValue = DX_WATERFALL_UTILS.cookie.get(DX_WATERFALL_CONSTANTS.COOKIE.NAME_FONT_SIZE);
        if (cookieValue !== null) {
            var level = parseInt(cookieValue, 10);
            if (!isNaN(level) && level >= 0 && level <= 4) {
                return level;
            }
        }
        return null;
    },

    /**
     * Save mode filters to cookie
     */
    saveModeFiltersToCookie: function() {
        DX_WATERFALL_UTILS.cookie.set(
            DX_WATERFALL_CONSTANTS.COOKIE.NAME_MODE_FILTERS,
            JSON.stringify(this.modeFilters),
            DX_WATERFALL_CONSTANTS.COOKIE.EXPIRY_DAYS
        );
    },

    /**
     * Load mode filters from cookie
     * @returns {Object|null} Mode filters object or null if not found
     */
    loadModeFiltersFromCookie: function() {
        var cookieValue = DX_WATERFALL_UTILS.cookie.get(DX_WATERFALL_CONSTANTS.COOKIE.NAME_MODE_FILTERS);
        if (cookieValue) {
            try {
                var filters = JSON.parse(cookieValue);
                // Validate that it has the expected properties
                if (typeof filters.phone === 'boolean' &&
                    typeof filters.cw === 'boolean' &&
                    typeof filters.digi === 'boolean') {
                    return filters;
                }
            } catch (e) {
                // Silently ignore invalid cookie data
            }
        }
        return null;
    },

    /**
     * Load saved settings from cookies on initialization
     */
    loadSettingsFromCookies: function() {
        // Load font size
        var savedFontSize = this.loadFontSizeFromCookie();
        if (savedFontSize !== null) {
            this.labelSizeLevel = savedFontSize;
        }

        // Load mode filters
        var savedModeFilters = this.loadModeFiltersFromCookie();
        if (savedModeFilters) {
            this.modeFilters.phone = savedModeFilters.phone;
            this.modeFilters.cw = savedModeFilters.cw;
            this.modeFilters.digi = savedModeFilters.digi;
        }
    },

    // ========================================
    // SPOT LABEL TOOLTIP HANDLER
    // ========================================

    /**
     * Handle mousemove over canvas to show spot label tooltips
     * Efficient implementation - only creates tooltip when needed
     */
    handleSpotLabelHover: function(e) {
        // Don't show tooltips while waiting for data or if no spots
        if (this.waitingForData || !this.dxSpots || this.dxSpots.length === 0) {
            this.hideSpotTooltip();
            return;
        }

        var rect = this.canvas.getBoundingClientRect();
        var mouseX = e.clientX - rect.left;
        var mouseY = e.clientY - rect.top;

        // Find if mouse is over any spot label
        var hoveredSpot = this.findSpotAtPosition(mouseX, mouseY);

        if (hoveredSpot) {
            this.canvas.style.cursor = 'pointer'; // Change cursor to pointer when over spot
            this.showSpotTooltip(hoveredSpot, e.clientX, e.clientY);
        } else {
            this.canvas.style.cursor = 'default'; // Reset cursor when not over spot
            this.hideSpotTooltip();
        }
    },

    /**
     * Find spot at mouse position (checks both left and right spots + center spot)
     */
    findSpotAtPosition: function(x, y) {
        var labelHeight = this.getCurrentLabelHeight();
        var tolerance = 2; // Pixels tolerance for easier hovering

        // Check center spot(s) first
        var centerSpot = this.getSpotInfo();
        if (centerSpot && this.relevantSpots && this.relevantSpots.length > 0) {
            var centerX = this.canvas.width / 2;
            var waterfallHeight = this.canvas.height - DX_WATERFALL_CONSTANTS.CANVAS.RULER_HEIGHT;
            var centerY = waterfallHeight / 2;

            // Check if we have multiple spots near the center frequency (same logic as drawCenterCallsignLabel)
            var centerFreq = this.getCachedMiddleFreq(); // Use the actual tuned frequency
            var spotsAtSameFreq = [];
            var frequencyTolerance = 0.1; // Same as in drawCenterCallsignLabel

            for (var i = 0; i < this.relevantSpots.length; i++) {
                var spot = this.relevantSpots[i];
                var spotFreq = parseFloat(spot.frequency);
                if (Math.abs(spotFreq - centerFreq) <= frequencyTolerance) {
                    spotsAtSameFreq.push(spot);
                }
            }

            this.ctx.font = this.getCurrentCenterLabelFont();
            var padding = Math.ceil(DX_WATERFALL_CONSTANTS.CANVAS.SPOT_PADDING * 1.1);
            var centerLabelHeight = labelHeight + 1;
            var spacing = 4;

            if (spotsAtSameFreq.length > 1) {
                // Check each stacked label
                var totalHeight = (spotsAtSameFreq.length * centerLabelHeight) + ((spotsAtSameFreq.length - 1) * spacing);
                var startY = centerY - (totalHeight / 2);

                for (var j = 0; j < spotsAtSameFreq.length; j++) {
                    var stackedSpot = spotsAtSameFreq[j];
                    var textWidth = this.ctx.measureText(stackedSpot.callsign).width;
                    var centerLabelWidth = textWidth + (padding * 2);

                    var rectY = startY + (j * (centerLabelHeight + spacing));
                    var centerLeft = centerX - centerLabelWidth / 2;
                    var centerRight = centerX + centerLabelWidth / 2;
                    var centerTop = rectY;
                    var centerBottom = rectY + centerLabelHeight;

                    if (x >= centerLeft - tolerance && x <= centerRight + tolerance &&
                        y >= centerTop - tolerance && y <= centerBottom + tolerance) {
                        return stackedSpot;
                    }
                }
            } else {
                // Single center spot
                var textWidth = this.ctx.measureText(centerSpot.callsign).width;
                var centerLabelWidth = textWidth + (padding * 2);
                var centerLabelHeight = labelHeight + 1;

                var centerLeft = centerX - centerLabelWidth / 2;
                var centerRight = centerX + centerLabelWidth / 2;
                var centerTop = centerY - centerLabelHeight / 2;
                var centerBottom = centerY + centerLabelHeight / 2;

                if (x >= centerLeft - tolerance && x <= centerRight + tolerance &&
                    y >= centerTop - tolerance && y <= centerBottom + tolerance) {
                    return centerSpot;
                }
            }
        }

        // Check all visible spots (left and right)
        for (var i = 0; i < this.dxSpots.length; i++) {
            var spot = this.dxSpots[i];
            if (spot.x !== undefined && spot.y !== undefined && spot.labelWidth !== undefined) {
                // spot.x is the CENTER of the label
                // spot.y is the CENTER of the text (y + 1 from drawing)
                // spot.labelWidth is the full width of the label

                var spotLeft = spot.x - spot.labelWidth / 2;
                var spotRight = spot.x + spot.labelWidth / 2;
                var spotTop = spot.y - labelHeight / 2;
                var spotBottom = spot.y + labelHeight / 2;

                if (x >= spotLeft - tolerance && x <= spotRight + tolerance &&
                    y >= spotTop - tolerance && y <= spotBottom + tolerance) {
                    // Return a properly formatted spot object (like getSpotInfo does)
                    return DX_WATERFALL_UTILS.spots.createSpotObject(spot, {
                        includeSpotter: true,
                        includeTimestamp: true,
                        includeMessage: true,
                        includeWorkStatus: true
                    });
                }
            }
        }

        return null;
    },

    /**
     * Show tooltip for a spot
     */
    showSpotTooltip: function(spot, clientX, clientY) {
        // Create tooltip div if it doesn't exist
        if (!this.spotTooltipDiv) {
            this.spotTooltipDiv = document.createElement('div');
            this.spotTooltipDiv.id = 'dxWaterfallTooltip';
            this.spotTooltipDiv.style.position = 'fixed';
            this.spotTooltipDiv.style.backgroundColor = DX_WATERFALL_CONSTANTS.COLORS.TOOLTIP_BACKGROUND;
            this.spotTooltipDiv.style.color = DX_WATERFALL_CONSTANTS.COLORS.WHITE;
            this.spotTooltipDiv.style.padding = '5px 10px';
            this.spotTooltipDiv.style.borderRadius = '4px';
            this.spotTooltipDiv.style.fontSize = '11px';
            this.spotTooltipDiv.style.fontFamily = '"Consolas", "Courier New", monospace';
            this.spotTooltipDiv.style.pointerEvents = 'none';
            this.spotTooltipDiv.style.zIndex = '10000';
            this.spotTooltipDiv.style.whiteSpace = 'nowrap';
            document.body.appendChild(this.spotTooltipDiv);
        }

        // Build tooltip content with all information
        var tooltipParts = [];

        // Spotter name (already cleaned during data load)
        if (spot.spotter) {
            tooltipParts.push(lang_dxwaterfall_spotted_by + ' ' + spot.spotter);
        }

        // Time from when_pretty field (format: "DD/MM/YY HH:MM")
        if (spot.when_pretty) {
            var parts = spot.when_pretty.split(' ');
            if (parts.length === 2) {
                // Extract just the time part (HH:MM) and add Z for UTC
                tooltipParts.push('@' + parts[1] + 'Z');
            }
        }

        // Add worked status indicators
        // Check both transformed properties (newContinent, newDxcc, newCallsign)
        // and raw properties (worked_continent, worked_dxcc, worked_call)
        var statusParts = [];

        // New continent: check newContinent or worked_continent === false
        if (spot.newContinent || spot.worked_continent === false) {
            statusParts.push(lang_dxwaterfall_new_continent);
        }

        // New DXCC: check newDxcc or worked_dxcc === false
        if (spot.newDxcc || spot.worked_dxcc === false) {
            statusParts.push(lang_dxwaterfall_new_dxcc);
        }

        // New callsign: check newCallsign or worked_call === false
        if (spot.newCallsign || spot.worked_call === false) {
            statusParts.push(lang_dxwaterfall_new_callsign);
        }

        if (statusParts.length > 0) {
            tooltipParts.push('(' + statusParts.join(') (') + ')');
        }

        this.spotTooltipDiv.textContent = tooltipParts.join(' ');

        // Get canvas boundaries to keep tooltip inside
        var canvasRect = this.canvas.getBoundingClientRect();

        // Calculate tooltip dimensions (need to show it briefly to measure)
        this.spotTooltipDiv.style.display = 'block';
        var tooltipWidth = this.spotTooltipDiv.offsetWidth;
        var tooltipHeight = this.spotTooltipDiv.offsetHeight;

        // Default position (offset from cursor)
        var tooltipLeft = clientX + 15;
        var tooltipTop = clientY + 10;

        // Keep tooltip inside canvas horizontally
        if (tooltipLeft + tooltipWidth > canvasRect.right) {
            tooltipLeft = clientX - tooltipWidth - 15; // Show on left side of cursor
        }
        if (tooltipLeft < canvasRect.left) {
            tooltipLeft = canvasRect.left + 5; // Clamp to left edge
        }

        // Keep tooltip inside canvas vertically
        if (tooltipTop + tooltipHeight > canvasRect.bottom) {
            tooltipTop = clientY - tooltipHeight - 10; // Show above cursor
        }
        if (tooltipTop < canvasRect.top) {
            tooltipTop = canvasRect.top + 5; // Clamp to top edge
        }

        this.spotTooltipDiv.style.left = tooltipLeft + 'px';
        this.spotTooltipDiv.style.top = tooltipTop + 'px';
    },

    /**
     * Hide tooltip
     */
    hideSpotTooltip: function() {
        if (this.spotTooltipDiv) {
            this.spotTooltipDiv.style.display = 'none';
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

    /**
     * Get visible spots with caching to avoid re-filtering on every render
     * Cache is invalidated when spots, frequency, canvas size, or mode filter changes
     * @returns {{left: Array, right: Array}} Object with left and right spot arrays
     */
    getVisibleSpots: function() {
        var centerX = this.canvas.width / 2;
        var middleFreq = this.getCachedMiddleFreq();
        var pixelsPerKHz = this.getCachedPixelsPerKHz();
        var currentMode = this.getCurrentMode();

        // Create cache key based on parameters that affect visible spots
        var cacheKey = {
            spotsLength: this.dxSpots ? this.dxSpots.length : 0,
            middleFreq: middleFreq,
            canvasWidth: this.canvas.width,
            mode: currentMode,
            labelSizeLevel: this.labelSizeLevel,
            phoneFilter: this.modeFilters.phone,
            cwFilter: this.modeFilters.cw,
            digiFilter: this.modeFilters.digi
        };

        // Check if cache is valid
        if (this.cache.visibleSpots && this.cache.visibleSpotsParams) {
            var params = this.cache.visibleSpotsParams;
            if (params.spotsLength === cacheKey.spotsLength &&
                params.middleFreq === cacheKey.middleFreq &&
                params.canvasWidth === cacheKey.canvasWidth &&
                params.mode === cacheKey.mode &&
                params.labelSizeLevel === cacheKey.labelSizeLevel &&
                params.phoneFilter === cacheKey.phoneFilter &&
                params.cwFilter === cacheKey.cwFilter &&
                params.digiFilter === cacheKey.digiFilter) {
                return this.cache.visibleSpots;
            }
        }

        // Cache miss - rebuild visible spots
        var leftSpots = [];
        var rightSpots = [];
        var centerFrequency = middleFreq;
        var centerFrequencyTolerance = DX_WATERFALL_CONSTANTS.THRESHOLDS.CENTER_SPOT_TOLERANCE_KHZ;

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
                    // Skip spots at center frequency (within tolerance)
                    if (centerFrequency && Math.abs(spotFreq - centerFrequency) <= centerFrequencyTolerance) {
                        continue;
                    }

                    var spotData = DX_WATERFALL_UTILS.spots.createSpotObject(spot, {
                        includePosition: true,
                        x: x,
                        includeOffsets: true,
                        middleFreq: middleFreq,
                        includeWorkStatus: true
                    });

                    // Store reference to original spot
                    spotData.originalSpot = spot;

                    if (freqOffset < 0) {
                        leftSpots.push(spotData);
                    } else if (freqOffset > 0) {
                        rightSpots.push(spotData);
                    }
                }
            }
        }

        // Pre-calculate label widths for all spots (cached with visible spots)
        if (this.ctx) {
            var currentLabelFont = this.getCurrentLabelFont();
            this.ctx.font = currentLabelFont;
            var padding = DX_WATERFALL_CONSTANTS.CANVAS.SPOT_PADDING;

            for (var j = 0; j < leftSpots.length; j++) {
                var textWidth = this.ctx.measureText(leftSpots[j].callsign).width;
                leftSpots[j].labelWidth = textWidth + (padding * 2);
            }

            for (var k = 0; k < rightSpots.length; k++) {
                var textWidthRight = this.ctx.measureText(rightSpots[k].callsign).width;
                rightSpots[k].labelWidth = textWidthRight + (padding * 2);
            }
        }

        // Cache the result
        var result = { left: leftSpots, right: rightSpots };
        this.cache.visibleSpots = result;
        this.cache.visibleSpotsParams = cacheKey;

        return result;
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
    },

    // Load band plans from database
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

        // Determine region from current continent using the same logic as JSON bandplans
        // Region selection not yet fully implemented, but prepared for future use
        var region = this.continentToRegion(this.currentContinent);

        $.ajax({
            url: baseUrl + 'index.php/band/get_user_bandedges?region=' + region,
            type: 'GET',
            dataType: 'json',
            cache: true, // Cache the band plans
            success: function(data) {
                // Transform the database format to the expected band plans format
                // Database returns: [{frequencyfrom: 14000000, frequencyto: 14070000, mode: "CW"}, ...]
                // Need to group by band and create structure for getBandLimits
                self.bandPlans = self.transformBandEdgesToBandPlans(data, region);
                // Invalidate cache to trigger redraw with band limits
                self.bandLimitsCache = null;
            },
            error: function(xhr, status, error) {
                self.bandPlans = {}; // Set to empty object to prevent repeated attempts
            }
        });
    },

    // Transform band edges from database into band plans structure
    transformBandEdgesToBandPlans: function(bandEdges, region) {
        if (!bandEdges || bandEdges.length === 0) {
            return {};
        }

        var bandPlans = {};
        var regionKey = 'region' + region;
        bandPlans[regionKey] = {};

        // Also store raw band edges data grouped by band for mode indicators
        if (!this.bandEdgesData) {
            this.bandEdgesData = {};
        }
        this.bandEdgesData[regionKey] = {};

        // Group by band - find min/max frequencies for each band
        var bandRanges = {};

        for (var i = 0; i < bandEdges.length; i++) {
            var edge = bandEdges[i];
            var freqFrom = parseInt(edge.frequencyfrom);
            var freqTo = parseInt(edge.frequencyto);

            // Determine band from frequency (use center frequency)
            var centerFreq = (freqFrom + freqTo) / 2;
            var band = this.getFrequencyBandFromHz(centerFreq);

            if (band) {
                // Store band ranges for limits
                if (!bandRanges[band]) {
                    bandRanges[band] = {
                        start_hz: freqFrom,
                        end_hz: freqTo
                    };
                } else {
                    // Expand range if this edge extends beyond current range
                    if (freqFrom < bandRanges[band].start_hz) {
                        bandRanges[band].start_hz = freqFrom;
                    }
                    if (freqTo > bandRanges[band].end_hz) {
                        bandRanges[band].end_hz = freqTo;
                    }
                }

                // Store raw band edges for mode indicators
                if (!this.bandEdgesData[regionKey][band]) {
                    this.bandEdgesData[regionKey][band] = [];
                }
                this.bandEdgesData[regionKey][band].push({
                    frequencyfrom: freqFrom,
                    frequencyto: freqTo,
                    mode: edge.mode
                });
            }
        }

        // Convert to expected format
        bandPlans[regionKey] = bandRanges;
        return bandPlans;
    },

    // Helper function to determine band from frequency in Hz
    getFrequencyBandFromHz: function(frequencyHz) {
        // Check if frequencyToBand function exists
        if (typeof frequencyToBand === 'function') {
            return frequencyToBand(frequencyHz);
        }

        // Fallback: simple band detection based on common amateur radio bands
        var freqMhz = frequencyHz / 1000000;

        if (freqMhz >= 1.8 && freqMhz < 2.0) return '160m';
        if (freqMhz >= 3.5 && freqMhz < 4.0) return '80m';
        if (freqMhz >= 7.0 && freqMhz < 7.3) return '40m';
        if (freqMhz >= 10.1 && freqMhz < 10.15) return '30m';
        if (freqMhz >= 14.0 && freqMhz < 14.35) return '20m';
        if (freqMhz >= 18.068 && freqMhz < 18.168) return '17m';
        if (freqMhz >= 21.0 && freqMhz < 21.45) return '15m';
        if (freqMhz >= 24.89 && freqMhz < 24.99) return '12m';
        if (freqMhz >= 28.0 && freqMhz < 29.7) return '10m';
        if (freqMhz >= 50.0 && freqMhz < 54.0) return '6m';
        if (freqMhz >= 144.0 && freqMhz < 148.0) return '2m';
        if (freqMhz >= 420.0 && freqMhz < 450.0) return '70cm';

        return null;
    },

    // Get band limits for current band and region
    getBandLimits: function() {
        var currentBand = this.getCurrentBand();
        var currentRegion = this.continentToRegion(this.currentContinent);
        var regionKey = 'region' + currentRegion;

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
        if (this.bandPlans && this.bandPlans[regionKey]) {
            if (this.bandPlans[regionKey][currentBand]) {
                var bandData = this.bandPlans[regionKey][currentBand];
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
                // Set flag to prevent band change event handler from running
                // This prevents form reset during CAT/WebSocket frequency updates
                window.programmaticBandChange = true;

                // CRITICAL: Set waterfall flag IMMEDIATELY before the band changes
                // This ensures hasParametersChanged() can detect this was programmatic
                this.programmaticBandUpdate = true;

                // Update the band dropdown (in the QSO form)
                this.$bandSelect.val(newBand);

                // Reset flags after a short delay to allow event to process
                var self = this;
                setTimeout(function() {
                    window.programmaticBandChange = false;
                    // Keep waterfall flag longer to survive the parameter check
                }, 50);
            } else {
                // Band doesn't exist in dropdown, select the first available option as fallback
                var firstOption = this.$bandSelect.find('option:first').val();
                if (firstOption) {
                    window.programmaticBandChange = true;
                    this.programmaticBandUpdate = true;
                    this.$bandSelect.val(firstOption);
                    var self = this;
                    setTimeout(function() {
                        window.programmaticBandChange = false;
                    }, 50);
                }
            }
        }
    },

    // ========================================
    // CANVAS DRAWING AND RENDERING FUNCTIONS
    // ========================================

    // Draw band mode indicators (colored lines below ruler showing CW/DIGI/PHONE segments)
    drawBandModeIndicators: function() {
        // Get current region and band
        var currentBand = this.getCurrentBand();
        var currentRegion = this.continentToRegion(this.currentContinent);
        var regionKey = 'region' + currentRegion;

        // Check if we have band plans loaded
        if (!this.bandPlans || this.bandPlans === 'loading' || !this.bandPlans[regionKey]) {
            return;
        }

        // Get band edges from the raw data (we need mode information)
        // We need to access the original band edges data with mode info
        if (!this.bandEdgesData || !this.bandEdgesData[regionKey]) {
            return;
        }

        var centerX = this.canvas.width / 2;
        var middleFreq = this.getCachedMiddleFreq(); // In kHz
        var pixelsPerKHz = this.getCachedPixelsPerKHz();
        var rulerY = this.canvas.height - DX_WATERFALL_CONSTANTS.CANVAS.RULER_HEIGHT;

        // Get band edges for current band
        var bandEdges = this.bandEdgesData[regionKey][currentBand];
        if (!bandEdges || bandEdges.length === 0) {
            return;
        }

        // Draw mode indicators as 2px lines below the ruler
        this.ctx.lineWidth = 2;
        var indicatorY = rulerY + 2; // 2px below the ruler line

        for (var i = 0; i < bandEdges.length; i++) {
            var edge = bandEdges[i];
            var freqFromKhz = edge.frequencyfrom / 1000; // Convert Hz to kHz
            var freqToKhz = edge.frequencyto / 1000;
            var mode = edge.mode.toLowerCase();

            // Calculate pixel positions
            var startX = this.freqToPixel(freqFromKhz, centerX, middleFreq, pixelsPerKHz);
            var endX = this.freqToPixel(freqToKhz, centerX, middleFreq, pixelsPerKHz);

            // Clip to canvas bounds
            startX = Math.max(0, Math.min(startX, this.canvas.width));
            endX = Math.max(0, Math.min(endX, this.canvas.width));

            // Only draw if visible on canvas
            if (endX > 0 && startX < this.canvas.width && endX > startX) {
                // Determine color based on mode
                var color;
                if (mode === 'cw') {
                    color = DX_WATERFALL_CONSTANTS.COLORS.SPOT_CW;
                } else if (mode === 'digi' || mode === 'data') {
                    color = DX_WATERFALL_CONSTANTS.COLORS.SPOT_DIGI;
                } else if (mode === 'phone' || mode === 'ssb' || mode === 'lsb' || mode === 'usb') {
                    color = DX_WATERFALL_CONSTANTS.COLORS.SPOT_PHONE;
                } else {
                    // Unknown mode, skip
                    continue;
                }

                // Draw the mode indicator line
                this.ctx.strokeStyle = color;
                this.ctx.beginPath();
                this.ctx.moveTo(startX, indicatorY);
                this.ctx.lineTo(endX, indicatorY);
                this.ctx.stroke();
            }
        }
    },

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
        this.ctx.strokeStyle = DX_WATERFALL_CONSTANTS.COLORS.OUT_OF_BAND_BORDER_LIGHT;
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
        this.ctx.strokeStyle = DX_WATERFALL_CONSTANTS.COLORS.OUT_OF_BAND_BORDER_DARK;
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

        console.log('[DX Waterfall] FETCH SPOTS: immediate=' + immediate + ', userInitiated=' + userInitiated);

        // Clear any existing debounce timer
        if (this.fetchDebounceTimer) {
            clearTimeout(this.fetchDebounceTimer);
            this.fetchDebounceTimer = null;
        }

        // If not immediate, debounce the request
        if (!immediate) {
            console.log('[DX Waterfall] FETCH SPOTS: Debouncing for ' + this.fetchDebounceMs + 'ms');
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

        console.log('[DX Waterfall] FETCH SPOTS: Starting AJAX fetch for band=' + band + ', continent=' + this.currentContinent);

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

        // Reset timer ONLY for user-initiated fetches or initial load
        // Background auto-refreshes should be silent (no hourglass/timer display)
        // Don't reset if timer was already started (e.g., during band change detection)
        if (this.userInitiatedFetch && !this.operationStartTime) {
            this.operationStartTime = Date.now();
            this.updateZoomMenu(); // Immediately show timer/hourglass
        } else if (!this.dataReceived && !this.operationStartTime) {
            // Initial load - show timer
            this.operationStartTime = Date.now();
            this.updateZoomMenu(); // Immediately show timer/hourglass
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

                console.log('[DX Waterfall] FETCH SPOTS: Success - received ' + (data ? data.length || 0 : 0) + ' spots');

                if (data && !data.error) {
                    // Enrich spots with park references once during fetch
                    // This prevents recalculating them multiple times
                    for (var i = 0; i < data.length; i++) {
                        var parkRefs = DX_WATERFALL_UTILS.parkRefs.extract(data[i]);
                        data[i].sotaRef = parkRefs.sotaRef;
                        data[i].potaRef = parkRefs.potaRef;
                        data[i].iotaRef = parkRefs.iotaRef;
                        data[i].wwffRef = parkRefs.wwffRef;

                        // Clean up spotter callsign (remove -# suffix)
                        if (data[i].spotter) {
                            data[i].spotter = data[i].spotter.replace(/-#$/, '');
                        }
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

                    // Invalidate caches when spots are updated
                    self.cache.visibleSpots = null;
                    self.cache.visibleSpotsParams = null;
                    self.relevantSpots = [];

                    self.collectAllBandSpots(true); // Update band spot collection for navigation (force after data fetch)
                    self.collectSmartHunterSpots(); // Update smart hunter spots collection

                    // Force menu update after data fetch - bypass catTuning/frequencyChanging check
                    // This ensures menu shows data immediately even if frequency is still settling
                    self.updateZoomMenu(true); // Pass true to force update

                    // Check if we're standing on a spot and auto-populate QSO form
                    self.checkAndPopulateSpotAtFrequency();
                } else {
                    // No spots or error in response (e.g., {"error": "not found"})
                    console.log('[DX Waterfall] FETCH SPOTS: Error response - no spots found');
                    self.dxSpots = [];
                    self.totalSpotsCount = 0;
                    self.dataReceived = true; // Mark as received even if empty
                    self.waitingForData = false; // Stop waiting
                    self.userInitiatedFetch = false; // Clear user-initiated flag
                    self.lastUpdateTime = new Date(); // Record update time even on error

                    // Track fetch parameters to prevent duplicate fetches
                    self.lastFetchBand = band;
                    self.lastFetchContinent = de;
                    self.lastFetchAge = age;

                    // Invalidate caches when spots are cleared
                    self.cache.visibleSpots = null;
                    self.cache.visibleSpotsParams = null;
                    self.relevantSpots = [];

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

                console.log('[DX Waterfall] FETCH SPOTS: AJAX error - ' + status + ', ' + error);

                self.dxSpots = [];
                self.totalSpotsCount = 0;
                self.dataReceived = true; // Mark as received to stop waiting state
                self.waitingForData = false; // Stop waiting

                // Invalidate caches on error
                self.cache.visibleSpots = null;
                self.cache.visibleSpotsParams = null;
                self.relevantSpots = [];

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
        // Safety check: return default if not initialized
        if (!this.$bandSelect) {
            return '20m';
        }
        // Try to get band from form - adjust selector based on your HTML structure
        var band = this.$bandSelect.val() || '20m';
        return band;
    },

    // Get current mode from form or default to All
    getCurrentMode: function() {
        // Safety check: return default if not initialized
        if (!this.$modeSelect) {
            return 'All';
        }
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

        // Don't clear zoom menu here - let updateZoomMenu() handle it
        // This prevents brief empty states when displayWaitingMessage() is called
        // followed immediately by updateZoomMenu()

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
        DX_WATERFALL_UTILS.drawing.drawCenteredText(this.ctx, lang_dxwaterfall_waiting_data, centerX, textY, 'WAITING_MESSAGE', 'MESSAGE_TEXT_WHITE');

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
        var displayColor = color || 'MESSAGE_TEXT_WHITE';

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
            case 0:
                pixelsPerKHz = 2;  // ±50 kHz range (widest)
                break;
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

        // Draw band mode indicators (colored lines showing CW/DIGI/PHONE segments)
        this.drawBandModeIndicators();

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

        // For very wide zoom levels, track which major tick we're on to skip labels
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
                    // For very wide zoom levels, skip labels to reduce clutter
                    var shouldShowLabel = true;
                    if (this.currentZoomLevel === 0) {
                        // Zoom level 0: show every 4th label (widest view)
                        shouldShowLabel = (majorTickCounter % 4 === 0);
                        majorTickCounter++;
                    } else if (this.currentZoomLevel === 1) {
                        // Zoom level 1: show every 2nd label
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
        // Skip bandwidth indicator if disabled in display config
        if (!this.displayConfig.showBandwidthIndicator) {
            return;
        }

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

    // Draw center line marker(s) - uses displayConfig mapping
    drawCenterMarker: function() {
        var centerX = this.canvas.width / 2;
        var rulerY = this.canvas.height - DX_WATERFALL_CONSTANTS.CANVAS.RULER_HEIGHT;
        var pixelsPerKhz = this.getPixelsPerKHz();
        var centerFreq = this.displayConfig.centerFrequency;

        // Draw all configured markers
        for (var i = 0; i < this.displayConfig.markers.length; i++) {
            var marker = this.displayConfig.markers[i];

            // Calculate marker position relative to center frequency
            var offset = (marker.frequency - centerFreq) * pixelsPerKhz;
            var markerX = centerX + offset;

            // Check if marker is within canvas bounds
            if (markerX >= 0 && markerX <= this.canvas.width) {
                // Draw marker line
                this.ctx.strokeStyle = marker.color;
                this.ctx.lineWidth = 2;
                this.ctx.beginPath();
                this.ctx.moveTo(markerX, 0);
                this.ctx.lineTo(markerX, rulerY + 5);
                this.ctx.stroke();
            } else if (this.displayConfig.isSplit && marker.label === 'TX') {
                // In split mode, if TX marker is off-screen, draw arrow indicator on ruler
                var arrowY = rulerY + 10; // Position on ruler
                var arrowSize = 6;

                this.ctx.fillStyle = marker.color;
                this.ctx.beginPath();

                if (markerX < 0) {
                    // TX is to the left (below current view) - draw left-pointing arrow on left side
                    var arrowX = 10;
                    this.ctx.moveTo(arrowX + arrowSize, arrowY - arrowSize);
                    this.ctx.lineTo(arrowX, arrowY);
                    this.ctx.lineTo(arrowX + arrowSize, arrowY + arrowSize);
                } else {
                    // TX is to the right (above current view) - draw right-pointing arrow on right side
                    var arrowX = this.canvas.width - 10;
                    this.ctx.moveTo(arrowX - arrowSize, arrowY - arrowSize);
                    this.ctx.lineTo(arrowX, arrowY);
                    this.ctx.lineTo(arrowX - arrowSize, arrowY + arrowSize);
                }

                this.ctx.closePath();
                this.ctx.fill();
            }
        }
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
            bgColor: spot.cnfmd_dxcc ? DX_WATERFALL_CONSTANTS.COLORS.GREEN : (spot.worked_dxcc ? DX_WATERFALL_CONSTANTS.COLORS.ORANGE : DX_WATERFALL_CONSTANTS.COLORS.RED),
            borderColor: spot.cnfmd_continent ? DX_WATERFALL_CONSTANTS.COLORS.GREEN : (spot.worked_continent ? DX_WATERFALL_CONSTANTS.COLORS.ORANGE : DX_WATERFALL_CONSTANTS.COLORS.RED),
            tickboxColor: spot.cnfmd_call ? DX_WATERFALL_CONSTANTS.COLORS.GREEN : (spot.worked_call ? DX_WATERFALL_CONSTANTS.COLORS.ORANGE : DX_WATERFALL_CONSTANTS.COLORS.RED)
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

        // Get visible spots using cached method - this avoids re-filtering on every render
        var visibleSpots = this.getVisibleSpots();
        var leftSpots = visibleSpots.left;
        var rightSpots = visibleSpots.right;

        // Smart label culling based on zoom level to prevent overcrowding
        // At lower zoom levels (zoomed out), limit the number of labels shown
        // Increased limits to show more spots at all zoom levels
        var maxLabelsPerSide = Math.max(20, Math.floor(40 + (this.currentZoomLevel * 20)));

        // If we have too many spots, keep only the closest ones to center
        if (leftSpots.length > maxLabelsPerSide) {
            // Sort by absolute frequency offset (closest first)
            leftSpots.sort(function(a, b) {
                return Math.abs(a.freqOffset) - Math.abs(b.freqOffset);
            });
            leftSpots = leftSpots.slice(0, maxLabelsPerSide);
        }

        if (rightSpots.length > maxLabelsPerSide) {
            rightSpots.sort(function(a, b) {
                return Math.abs(a.freqOffset) - Math.abs(b.freqOffset);
            });
            rightSpots = rightSpots.slice(0, maxLabelsPerSide);
        }

        // Calculate available vertical space - from top margin to above ruler line
        var topMargin = DX_WATERFALL_CONSTANTS.CANVAS.TOP_MARGIN;
        var bottomMargin = DX_WATERFALL_CONSTANTS.CANVAS.BOTTOM_MARGIN;
        var topY = topMargin;
        var bottomY = rulerY - bottomMargin;
        var availableHeight = bottomY - topY;

		// Check if center label is shown to avoid that area
		var centerFrequency = middleFreq;
		var centerSpotShown = centerFrequency !== null;
		var centerY = (this.canvas.height - DX_WATERFALL_CONSTANTS.CANVAS.RULER_HEIGHT) / 2;
		var baseLabelHeight = this.getCurrentLabelHeight(); // Regular label height
		var centerLabelHeight = baseLabelHeight + 1; // Center is 1px taller
		var centerExclusionHeight = Math.ceil(centerLabelHeight * 1.2) + 20; // Center label height + 20px margin
		var centerExclusionTop = centerY - (centerExclusionHeight / 2);
		var centerExclusionBottom = centerY + (centerExclusionHeight / 2);

		// Capture references for use in nested function
		var self = this;
		var fonts = this.fonts;

		// Get current label font based on size level
		var currentLabelFont = this.getCurrentLabelFont();

		// Label height constants for overlap detection - adjust based on label size
		var labelHeight = this.getCurrentLabelHeight();
		var minSpacing = 3; // Minimum spacing between labels in pixels

		// Function to distribute spots vertically with anti-overlap algorithm
		var drawSpotsSide = function(spots, ctx) {
			if (spots.length === 0) return;

			// Note: Label widths are already pre-calculated in getVisibleSpots() and cached
			// Set font for drawing (not for measuring)
			ctx.font = currentLabelFont;

			// Sort spots by absolute frequency offset (closest to center first)
			// This helps prioritize important spots and improves distribution
			spots.sort(function(a, b) {
				return Math.abs(a.absOffset) - Math.abs(b.absOffset);
			});

			// Function to draw a single spot
			var drawSpot = function(spot, y) {
				// Get colors using utility function
				var colors = self.getSpotColors(spot);
				var bgColor = colors.bgColor;
				var borderColor = colors.borderColor;
				var tickboxColor = colors.tickboxColor;

				// Calculate dimensions based on label size level
				var rectHeight = labelHeight;
				var rectX = spot.x - (spot.labelWidth / 2);
				var rectY = y - Math.floor(rectHeight / 2) - 1; // Center vertically
				var rectWidth = spot.labelWidth;

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

				// Store coordinates and dimensions in original spot for tooltip hover detection
				if (spot.originalSpot) {
					spot.originalSpot.x = spot.x;
					spot.originalSpot.y = y + 1;
					spot.originalSpot.labelWidth = spot.labelWidth;
				}

				// Draw underline if LoTW user
				if (spot.lotw_user) {
					var textWidth = spot.labelWidth - (padding * 2);
					ctx.strokeStyle = '#000000';
					ctx.lineWidth = 1;
					ctx.beginPath();
					ctx.moveTo(spot.x - (textWidth / 2), y + 3);
					ctx.lineTo(spot.x + (textWidth / 2), y + 3);
					ctx.stroke();
				}
			};

			// Check if a position would overlap with center label, other spots, or horizontally with nearby spots
			var checkOverlap = function(spot, y, occupiedPositions) {
				var spotLeft = spot.x - (spot.labelWidth / 2);
				var spotRight = spot.x + (spot.labelWidth / 2);
				var spotTop = y - (labelHeight / 2);
				var spotBottom = y + (labelHeight / 2);

				// Check center label overlap
				if (centerSpotShown) {
					if (!(spotBottom < centerExclusionTop || spotTop > centerExclusionBottom)) {
						return true; // Overlaps center vertically
					}
				}

				// Check overlap with other spots (both vertical and horizontal)
				for (var i = 0; i < occupiedPositions.length; i++) {
					var other = occupiedPositions[i];
					var otherLeft = other.x - (other.labelWidth / 2);
					var otherRight = other.x + (other.labelWidth / 2);
					var otherTop = other.y - (labelHeight / 2);
					var otherBottom = other.y + (labelHeight / 2);

					// Calculate horizontal distance between label edges (not centers)
					var horizontalGap;
					if (spot.x < other.x) {
						// Spot is to the left
						horizontalGap = otherLeft - spotRight;
					} else {
						// Spot is to the right
						horizontalGap = spotLeft - otherRight;
					}

					// Check if rectangles overlap (both horizontally AND vertically)
					var horizontalOverlap = !(spotRight < otherLeft - minSpacing || spotLeft > otherRight + minSpacing);
					var verticalOverlap = !(spotBottom < otherTop - minSpacing || spotTop > otherBottom + minSpacing);

					// If there's sufficient horizontal gap (accounting for actual label widths),
					// allow labels to share vertical space. At lower zoom levels (more zoomed out),
					// be more aggressive about sharing vertical space since spots are spread wider.
					// Zoom level 0 = max zoom out, 5 = max zoom in
					var gapMultiplier = 0.5 - (self.currentZoomLevel * 0.05); // 0.5 at zoom 0, 0.25 at zoom 5
					var minClearGap = Math.max(spot.labelWidth, other.labelWidth) * gapMultiplier;
					if (horizontalGap > minClearGap) {
						continue; // Enough horizontal separation, can share Y position
					}

					if (horizontalOverlap && verticalOverlap) {
						return true; // Overlaps both ways
					}
				}

				return false; // No overlap
			};

			// Find best vertical position for a spot
			var findBestPosition = function(spot, occupiedPositions) {
				// Create candidate positions - distribute across available space
				var candidates = [];
				var numCandidates = Math.max(20, spots.length * 3); // More candidates for better distribution

				// Generate candidate positions
				if (centerSpotShown) {
					// Split candidates between top and bottom sections
					var topSectionHeight = centerExclusionTop - topY;
					var bottomSectionHeight = bottomY - centerExclusionBottom;
					var halfCandidates = Math.floor(numCandidates / 2);

					// Top section candidates
					for (var i = 0; i < halfCandidates; i++) {
						candidates.push(topY + (topSectionHeight * i / (halfCandidates - 1 || 1)));
					}

					// Bottom section candidates
					for (var j = 0; j < (numCandidates - halfCandidates); j++) {
						candidates.push(centerExclusionBottom + (bottomSectionHeight * j / ((numCandidates - halfCandidates - 1) || 1)));
					}
				} else {
					// Full height candidates
					for (var k = 0; k < numCandidates; k++) {
						candidates.push(topY + (availableHeight * k / (numCandidates - 1 || 1)));
					}
				}

				// Find first non-overlapping candidate
				for (var m = 0; m < candidates.length; m++) {
					if (!checkOverlap(spot, candidates[m], occupiedPositions)) {
						return candidates[m];
					}
				}

				// If no good position found, return middle position (fallback)
				return topY + (availableHeight / 2);
			};

			// Track occupied positions with full rectangle info
			var occupiedPositions = [];

			// Position and draw each spot
			for (var i = 0; i < spots.length; i++) {
				var spot = spots[i];
				var bestY = findBestPosition(spot, occupiedPositions);
				occupiedPositions.push({
					x: spot.x,
					y: bestY,
					labelWidth: spot.labelWidth
				});
				drawSpot(spot, bestY);
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
        // Get the current spot info (populates this.relevantSpots)
        var spotInfo = this.getSpotInfo();

        if (!spotInfo || !this.relevantSpots || this.relevantSpots.length === 0) {
            return; // No spot at current frequency
        }

        var ctx = this.ctx;
        var centerX = this.canvas.width / 2;
        var waterfallHeight = this.canvas.height - DX_WATERFALL_CONSTANTS.CANVAS.RULER_HEIGHT;
        var centerY = waterfallHeight / 2;

        // Check if we have multiple spots near the CENTER/TUNED frequency
        var centerFreq = this.getCachedMiddleFreq(); // Use the actual tuned frequency
        var spotsAtSameFreq = [];
        var frequencyTolerance = DX_WATERFALL_CONSTANTS.THRESHOLDS.CENTER_SPOT_TOLERANCE_KHZ;

        for (var i = 0; i < this.relevantSpots.length; i++) {
            var spot = this.relevantSpots[i];
            var spotFreq = parseFloat(spot.frequency);
            if (Math.abs(spotFreq - centerFreq) <= frequencyTolerance) {
                spotsAtSameFreq.push({
                    spot: spot,
                    index: i
                });
            }
        }

        // If we have multiple spots at the same frequency, stack them
        if (spotsAtSameFreq.length > 1) {
            // Calculate dimensions for labels
            var baseLabelHeight = this.getCurrentLabelHeight();
            var centerLabelHeight = baseLabelHeight + 1;
            var padding = Math.ceil(DX_WATERFALL_CONSTANTS.CANVAS.SPOT_PADDING * 1.1);
            var spacing = 4; // Spacing between stacked labels

            // Use center label font
            ctx.font = this.getCurrentCenterLabelFont();

            // Calculate total height needed for all labels
            var totalHeight = (spotsAtSameFreq.length * centerLabelHeight) + ((spotsAtSameFreq.length - 1) * spacing);
            var startY = centerY - (totalHeight / 2);

            // Draw each spot at the same frequency
            for (var j = 0; j < spotsAtSameFreq.length; j++) {
                var spotData = spotsAtSameFreq[j];
                var spot = spotData.spot;
                var callsign = spot.callsign;
                var isSelected = (spotData.index === this.currentSpotIndex);

                // Get colors using same logic as spots
                var colors = this.getSpotColors(spot);

                // Measure text
                var textWidth = ctx.measureText(callsign).width;

                // Calculate position for this label
                var rectHeight = centerLabelHeight;
                var rectWidth = textWidth + (padding * 2);
                var rectX = centerX - (textWidth / 2) - padding;
                var rectY = startY + (j * (centerLabelHeight + spacing));
                var labelCenterY = rectY + (rectHeight / 2);

                // Draw background rectangle using DXCC status color
                ctx.fillStyle = colors.bgColor;
                ctx.fillRect(rectX, rectY, rectWidth, rectHeight);

                // Draw border - use thicker border for selected spot
                ctx.strokeStyle = colors.borderColor;
                ctx.lineWidth = isSelected ? 2 : 1;
                ctx.strokeRect(rectX, rectY, rectWidth, rectHeight);

                // Draw small tickbox at top-right corner using callsign status color
                var tickboxSize = DX_WATERFALL_CONSTANTS.CANVAS.SPOT_TICKBOX_SIZE;
                ctx.fillStyle = colors.tickboxColor;
                ctx.fillRect(rectX + rectWidth - tickboxSize, rectY, tickboxSize, tickboxSize);

                // Draw the callsign text in black (same as spots)
                ctx.fillStyle = '#000000';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(callsign, centerX, labelCenterY);

                // Draw underline if LoTW user (same as spots)
                if (spot.lotw_user) {
                    ctx.strokeStyle = '#000000';
                    ctx.lineWidth = 1;
                    ctx.beginPath();
                    ctx.moveTo(centerX - (textWidth / 2), labelCenterY + 3);
                    ctx.lineTo(centerX + (textWidth / 2), labelCenterY + 3);
                    ctx.stroke();
                }
            }
        } else {
            // Original logic: Draw single selected spot label
            var callsign = spotInfo.callsign;

            // Get colors using same logic as spots
            var colors = this.getSpotColors(spotInfo);

            // Use center label font (1px larger than regular spots, scales with labelSizeLevel)
            ctx.font = this.getCurrentCenterLabelFont();
            var textWidth = ctx.measureText(callsign).width;

            // Calculate background rectangle dimensions based on current label size
            var baseLabelHeight = this.getCurrentLabelHeight(); // Same as regular labels
            var centerLabelHeight = baseLabelHeight + 1; // Center is 1px taller
            var padding = Math.ceil(DX_WATERFALL_CONSTANTS.CANVAS.SPOT_PADDING * 1.1); // Slightly more padding for center
            var rectHeight = centerLabelHeight;
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
            var tickboxSize = DX_WATERFALL_CONSTANTS.CANVAS.SPOT_TICKBOX_SIZE;
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

            // Check if we're waiting for CAT/WebSocket frequency on initial load
            // This prevents fetching spots before we have the actual radio frequency
            if (this.waitingForCATFrequency && !this.dataReceived) {
                this.displayWaitingMessage();
                this.updateZoomMenu(); // Update menu to show loading indicator
                return; // Don't fetch spots or draw normal display until CAT frequency arrives
            }

            // Check if we should show waiting message
            var currentTime = Date.now();
            var timeSincePageLoad = currentTime - this.pageLoadTime;
            var isInitialLoad = timeSincePageLoad < this.minWaitTime;

            // Show waiting if:
            // 1. We're waiting for data AND either:
            //    a) It's the initial load and we haven't received data OR haven't waited 5 seconds yet
            //    b) It's a parameter change and we haven't received new data yet (no time wait)
            // BUT if we've already received data (including error responses), don't show waiting
            var shouldShowWaiting = this.waitingForData && !this.dataReceived &&
                                  (isInitialLoad ? timeSincePageLoad < this.minWaitTime : true);

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
                this.displayChangingFrequencyMessage(lang_dxwaterfall_changing_frequency, 'MESSAGE_TEXT_WHITE');
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
                if (isCATAvailable()) {
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
        var selectedSpot = relevantSpots[this.currentSpotIndex];
        return selectedSpot;
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

        // Block updates during navigation to prevent interference
        if (DX_WATERFALL_UTILS.navigation.navigating) {
            return;
        }

        // If waiting for data, frequency, or radio is tuning, show nbsp to maintain layout height
        if (this.waitingForData || this.waitingForCATFrequency || this.frequencyChanging || this.catTuning) {
            if (this.spotInfoDiv.innerHTML !== '&nbsp;') {
                this.spotInfoDiv.innerHTML = '&nbsp;';
                this.lastSpotInfoKey = null;
            }
            return;
        }

        var spotInfo = this.getSpotInfo();

        // Create a unique key for the current spot state to detect changes
        var currentKey;
        if (!spotInfo) {
            currentKey = 'no-spot';
        } else {
            // Include spot details and index in the key to detect any meaningful change
            currentKey = spotInfo.callsign + '|' + spotInfo.frequency + '|' +
                         this.currentSpotIndex + '|' + this.relevantSpots.length;
        }

        // Only update if the spot has actually changed
        if (this.lastSpotInfoKey === currentKey) {
            return; // No change, skip re-rendering
        }

        // Store the new key
        this.lastSpotInfoKey = currentKey;

        var infoText;
        if (!spotInfo) {
            // No active spot in bandwidth - clear the div (don't show cluster statistics here)
            infoText = '&nbsp;';
        } else {
            // Active spot in bandwidth - show spot details

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
                    cycleIcon = '<i class="fas fa-exchange-alt cycle-spot-icon" title="' + lang_dxwaterfall_cycle_nearby_spots + '"></i> ';
                    spotCounter = '[' + (this.currentSpotIndex + 1) + '/' + this.relevantSpots.length + '] ';
                }

                // Build prefix with tune and cycle icons, then spot info
                prefixText = tuneIcon + cycleIcon + spotCounter + flagPart + continent + ' ' + entity + ' (' + dxccId + ') ' + modeLabel + lotwIndicator + ' ';
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

        // Update the div only when content actually changed
        this.spotInfoDiv.innerHTML = infoText;
    },

    // Update zoom menu display
    // @param {boolean} forceUpdate - If true, bypass catTuning/frequencyChanging check
    updateZoomMenu: function(forceUpdate) {
        if (!this.zoomMenuDiv) {
            return;
        }

        // Don't show menu at all during frequency changes or CAT tuning
        // Don't show hourglass either - frequency changes should be invisible to user
        // UNLESS forceUpdate is true (e.g., after data fetch completes)
        if (!forceUpdate && (this.catTuning || this.frequencyChanging)) {
            // Don't update menu during frequency changes - keep showing last state
            return;
        }

        // Don't show menu during background fetch operations
        // Show hourglass with counter during DX cluster fetch
        if (this.fetchInProgress) {
            if (this.operationStartTime) {
                var elapsed = ((Date.now() - this.operationStartTime) / 1000).toFixed(1);
                // Only show "Warming up..." if we haven't received ANY data yet
                // Once we have data, always show counter (prevents "Warming up" from reappearing)
                var displayText = (!this.dataReceived && elapsed < 1.0) ? lang_dxwaterfall_warming_up : elapsed + 's';
                this.zoomMenuDiv.innerHTML = '<div style="display: flex; align-items: center; flex: 1;"><i class="fas fa-hourglass-half" style="margin-right: 5px; animation: blink 1s infinite;"></i><span style="margin-right: 10px;">' + displayText + '</span></div>';
            } else {
                // Fetch in progress but timer not started - show hourglass without counter
                this.zoomMenuDiv.innerHTML = '<div style="display: flex; align-items: center; flex: 1;"><i class="fas fa-hourglass-half" style="margin-right: 5px; animation: blink 1s infinite;"></i><span style="margin-right: 10px;">&nbsp;</span></div>';
            }
            return;
        }

        // If no data received yet AND waiting for data, show only loading indicator
        // Once data is received, always show full menu (with loading indicator if needed)
        // Show loading indicator for both user-initiated and pending fetches to avoid layout shifts
        if (!this.dataReceived) {
            if (this.waitingForData || this.operationStartTime) {
                // Show loading indicator with counter for any waiting state
                // Use operationStartTime check as fallback to catch brief transition moments
                if (this.operationStartTime) {
                    var elapsed = ((Date.now() - this.operationStartTime) / 1000).toFixed(1);
                    // Only show "Warming up..." if we haven't received ANY data yet and elapsed < 1s
                    var displayText = (!this.dataReceived && elapsed < 1.0) ? lang_dxwaterfall_warming_up : elapsed + 's';
                    this.zoomMenuDiv.innerHTML = '<div style="display: flex; align-items: center; flex: 1;"><i class="fas fa-hourglass-half" style="margin-right: 5px; animation: blink 1s infinite;"></i><span style="margin-right: 10px;">' + displayText + '</span></div>';
                } else {
                    // Waiting but no timer started yet - show hourglass without counter
                    this.zoomMenuDiv.innerHTML = '<div style="display: flex; align-items: center; flex: 1;"><i class="fas fa-hourglass-half" style="margin-right: 5px; animation: blink 1s infinite;"></i><span style="margin-right: 10px;">' + lang_dxwaterfall_warming_up + '</span></div>';
                }
            } else {
                // No data yet and not waiting - show hourglass placeholder to maintain height and prevent empty state
                this.zoomMenuDiv.innerHTML = '<div style="display: flex; align-items: center; flex: 1;"><i class="fas fa-hourglass-half" style="margin-right: 5px; color: transparent;"></i><span style="margin-right: 10px;">&nbsp;</span></div>';
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
            if (this.operationStartTime) {
                // Calculate elapsed time with tenths of seconds
                var elapsed = ((Date.now() - this.operationStartTime) / 1000).toFixed(1);
                // Only show "Warming up..." if we haven't received ANY data yet
                // Once we have data, always show counter (prevents "Warming up" from reappearing)
                var displayText = (!this.dataReceived && elapsed < 1.0) ? lang_dxwaterfall_warming_up : elapsed + 's';
                zoomHTML += '<i class="fas fa-hourglass-half" style="margin-right: 5px; animation: blink 1s infinite;"></i><span style="margin-right: 10px;">' + displayText + '</span>';
            } else {
                // Show hourglass without counter if timer not started yet
                zoomHTML += '<i class="fas fa-hourglass-half" style="margin-right: 5px; animation: blink 1s infinite;"></i><span style="margin-right: 10px;">&nbsp;</span>';
            }
        }

        // Add band spot navigation controls - always show them
        if (this.allBandSpots.length > 0) {
            var currentFreq = this.getCachedMiddleFreq();

            // Check if there's any spot with lower frequency (for prev/left)
            var hasPrevSpot = false;
            for (var i = 0; i < this.allBandSpots.length; i++) {
                if (this.allBandSpots[i].frequency < currentFreq) {
                    hasPrevSpot = true;
                    break;
                }
            }

            // Check if there's any spot with higher frequency (for next/right)
            var hasNextSpot = false;
            for (var i = 0; i < this.allBandSpots.length; i++) {
                if (this.allBandSpots[i].frequency > currentFreq) {
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

            // Phone filter - Green
            var phoneClass = activeFilters.phone ? 'mode-filter-phone active' : 'mode-filter-phone';
            var phoneStyle = activeFilters.phone ? 'color: #00FF00; font-weight: bold;' : 'color: #888888;';
            if (this.pendingModeFilters) phoneStyle += ' ' + blinkStyle;
            phoneStyle += ' cursor: pointer;';
            zoomHTML += '<span class="' + phoneClass + '" title="' + lang_dxwaterfall_toggle_phone + '" style="' + phoneStyle + ' margin: 0 3px; font-size: 11px; transition: color 0.2s;">' + lang_dxwaterfall_phone + '</span>';

        zoomHTML += '</div>';

        // Center section: spot count information
        // Format: "31/43 20m NA spots @22:16LT"
        zoomHTML += '<div style="flex: 1; display: flex; justify-content: center; align-items: center;">';
        if (this.dataReceived && this.lastUpdateTime) {
            // Count displayed spots
            var displayedSpotsCount = 0;
            if (this.dxSpots && this.dxSpots.length > 0) {
                for (var i = 0; i < this.dxSpots.length; i++) {
                    if (this.spotMatchesModeFilter(this.dxSpots[i])) {
                        displayedSpotsCount++;
                    }
                }
            }

            var hours = String(this.lastUpdateTime.getHours()).padStart(2, '0');
            var minutes = String(this.lastUpdateTime.getMinutes()).padStart(2, '0');
            var updateTimeStr = hours + ':' + minutes;
            var currentBand = this.getCurrentBand();

            zoomHTML += '<span style="font-size: 11px; color: #888888;">';
            zoomHTML += displayedSpotsCount + '/' + this.totalSpotsCount + ' ' + currentBand + ' ' + this.currentContinent + ' ' + lang_dxwaterfall_spots + ' @' + updateTimeStr + 'LT';
            zoomHTML += '</span>';
        }
        zoomHTML += '</div>';

        // Right side: label size and zoom controls
        zoomHTML += '<div style="display: flex; align-items: center; white-space: nowrap;">';

        // Label size cycle icon with tooltip showing current size
        var labelSizeNames = [
            lang_dxwaterfall_label_size_xsmall,
            lang_dxwaterfall_label_size_small,
            lang_dxwaterfall_label_size_medium,
            lang_dxwaterfall_label_size_large,
            lang_dxwaterfall_label_size_xlarge
        ];
        var labelSizeText = labelSizeNames[this.labelSizeLevel];
        zoomHTML += '<i class="fas fa-font label-size-icon" title="' + lang_dxwaterfall_label_size_cycle + ' (' + labelSizeText + ')"></i>';

        // Separator
        zoomHTML += '<span style="color: #666666; margin: 0 8px;">|</span>';

        // Zoom out button (disabled if at minimum level)
        if (this.currentZoomLevel > DX_WATERFALL_CONSTANTS.ZOOM.MIN_LEVEL) {
            zoomHTML += '<i class="fas fa-search-minus zoom-out-icon" title="' + lang_dxwaterfall_zoom_out + '"></i> ';
        } else {
            zoomHTML += '<i class="fas fa-search-minus zoom-out-icon disabled" title="' + lang_dxwaterfall_zoom_out + '" style="opacity: 0.3; cursor: not-allowed;"></i> ';
        }

        // Reset zoom button (disabled if already at default level)
        if (this.currentZoomLevel !== DX_WATERFALL_CONSTANTS.ZOOM.DEFAULT_LEVEL) {
            zoomHTML += '<i class="fas fa-undo zoom-reset-icon" title="' + lang_dxwaterfall_reset_zoom + '" style="margin: 0 5px; cursor: pointer; color: ' + DX_WATERFALL_CONSTANTS.COLORS.WHITE + '; font-size: 12px;"></i> ';
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
            this.cache.visibleSpots = null; // Invalidate visible spots cache
            this.cache.visibleSpotsParams = null;

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

        if (this.currentZoomLevel > DX_WATERFALL_CONSTANTS.ZOOM.MIN_LEVEL) {
            this.zoomChanging = true;
            this.currentZoomLevel--;
            this.cachedPixelsPerKHz = null; // Invalidate cache
            this.lastModeForCache = null; // Force recalculation
            this.cache.visibleSpots = null; // Invalidate visible spots cache
            this.cache.visibleSpotsParams = null;

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

        if (this.currentZoomLevel !== DX_WATERFALL_CONSTANTS.ZOOM.DEFAULT_LEVEL) {
            this.zoomChanging = true;
            this.currentZoomLevel = DX_WATERFALL_CONSTANTS.ZOOM.DEFAULT_LEVEL;
            this.cachedPixelsPerKHz = null; // Invalidate cache
            this.lastModeForCache = null; // Force recalculation
            this.cache.visibleSpots = null; // Invalidate visible spots cache
            this.cache.visibleSpotsParams = null;

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

    /**
     * Find nearest spot in a given direction (prev/next)
     * @param {string} direction - 'prev' (lower frequency) or 'next' (higher frequency)
     * @returns {object|null} Object with {spot, index} or null if not found
     */
    findNearestSpot: function(direction) {
        if (!DX_WATERFALL_UTILS.navigation.canNavigate(this)) {
            return null;
        }

        var currentFreq = this.getCachedMiddleFreq();
        var targetSpot = null;
        var targetIndex = -1;

        if (direction === 'prev') {
            // Find nearest spot to the left (lower frequency)
            // Iterate backwards since allBandSpots is sorted ascending
            for (var i = this.allBandSpots.length - 1; i >= 0; i--) {
                if (this.allBandSpots[i].frequency < currentFreq) {
                    targetSpot = this.allBandSpots[i];
                    targetIndex = i;
                    break;
                }
            }
        } else if (direction === 'next') {
            // Find nearest spot to the right (higher frequency)
            // Iterate forward since allBandSpots is sorted ascending
            for (var i = 0; i < this.allBandSpots.length; i++) {
                if (this.allBandSpots[i].frequency > currentFreq) {
                    targetSpot = this.allBandSpots[i];
                    targetIndex = i;
                    break;
                }
            }
        }

        return targetSpot ? {spot: targetSpot, index: targetIndex} : null;
    },

    // Navigate to previous spot (nearest spot to the left of current frequency)
    prevSpot: function() {
        var result = this.findNearestSpot('prev');
        if (result) {
            DX_WATERFALL_UTILS.navigation.navigateToSpot(this, result.spot, result.index);
        }
    },

    // Navigate to next spot (nearest spot to the right of current frequency)
    nextSpot: function() {
        var result = this.findNearestSpot('next');
        if (result) {
            DX_WATERFALL_UTILS.navigation.navigateToSpot(this, result.spot, result.index);
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

                // CRITICAL: Set mode FIRST before calling setFrequency
                var radioMode = DX_WATERFALL_UTILS.navigation.determineRadioMode(targetSpot);

                // Set CAT debounce lock early to block incoming CAT updates during navigation
                if (typeof setFrequency.catDebounceLock !== 'undefined') {
                    setFrequency.catDebounceLock = 1;
                }

                setMode(radioMode, true); // skipTrigger = true to prevent change event

                // Now set frequency - it will read the correct mode from the dropdown
                setFrequency(targetSpot.frequency, true); // Pass true to indicate waterfall-initiated change

                // Send frequency command again after short delay to correct any drift from mode change
                // (radio control lib bug: mode change can cause slight frequency shift)
                setTimeout(function() {
                    setFrequency(targetSpot.frequency, true);
                }, 200); // 200ms delay to let mode change settle

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

            // Reset band plans to force reload for new region
            self.bandPlans = null;
            self.bandEdgesData = null;

            // Load band plans for new region (based on new continent)
            self.loadBandPlans();

            // Enter waiting state
            self.waitingForData = true;
            self.dataReceived = false;

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
            self.lastSpotInfoKey = null; // Reset spot info key

            // Invalidate visible spots cache
            self.cache.visibleSpots = null;
            self.cache.visibleSpotsParams = null;

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

        // Apply filter changes immediately for instant visual feedback
        this.modeFilters.phone = this.pendingModeFilters.phone;
        this.modeFilters.cw = this.pendingModeFilters.cw;
        this.modeFilters.digi = this.pendingModeFilters.digi;
        this.modeFilters.other = this.pendingModeFilters.other;

        // Invalidate visible spots cache immediately for instant update
        this.cache.visibleSpots = null;
        this.cache.visibleSpotsParams = null;

        // Update menu immediately to show the new state
        this.updateZoomMenu();

        // Clear existing timer if there is one
        if (this.modeFilterChangeTimer) {
            clearTimeout(this.modeFilterChangeTimer);
        }

        // Set new timer - for saving and collection updates after 0.5 seconds of no clicks
        this.modeFilterChangeTimer = setTimeout(function() {
            // Only proceed if we have pending filters
            if (!self.pendingModeFilters) {
                return;
            }

            // Clear pending filters
            self.pendingModeFilters = null;

            // Save to cookie
            self.saveModeFiltersToCookie();

            // No need to fetch new spots - we already have all modes from cluster
            // Just re-collect spots with the new filters applied
            self.collectAllBandSpots(true); // Update band spot collection (force after filter change)
            self.collectSmartHunterSpots(); // Update smart hunter spots

            // Update zoom menu to show final state
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

        // Unbind event handlers that were added in init()
        // Note: Event handlers registered outside dxWaterfall object (like menu clicks)
        // should NOT be unbound here as they are global and persistent
        if (this.$freqCalculated) {
            this.$freqCalculated.off('focus blur input keydown');
        }
        if (this.canvas) {
            // Only unbind wheel event (added in init), not click events from global handlers
            $(this.canvas).off('wheel');
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
        this.cache.middleFreq = null;
        this.cache.lastQrgUnit = null;
        this.cache.lastValidCommittedFreq = null;
        this.cache.lastValidCommittedUnit = null;
        this.cache.visibleSpots = null;
        this.cache.visibleSpotsParams = null;

        // Clear frequency tracking properties (used in getCachedMiddleFreq)
        this.lastQrgUnit = null;
        this.lastModeForCache = null;
        this.lastValidCommittedFreq = null;
        this.lastValidCommittedUnit = null;

        // Clear cached pixels per kHz
        this.cachedPixelsPerKHz = null;

        // Reset all state flags
        this.waitingForData = true;
        this.dataReceived = false;
        this.initialFetchDone = false;
        this.waitingForCATFrequency = true;
        this.userEditingFrequency = false;
        this.userChangedBand = false;
        this.programmaticModeChange = false;
        this.zoomChanging = false;
        this.spotNavigating = false;
        this.smartHunterActive = false;
        this.continentChanging = false;
        this.initialLoadDone = false;
        this.frequencyChanging = false;
        this.catTuning = false;
        this.userInitiatedFetch = false;
        this.fetchInProgress = false;

        // Reset indices
        this.currentSpotIndex = 0;
        this.currentBandSpotIndex = 0;
        this.currentSmartHunterIndex = 0;

        // Reset zoom level to default
        this.currentZoomLevel = DX_WATERFALL_CONSTANTS.ZOOM.DEFAULT_LEVEL;

        // Clear pending states
        this.pendingContinent = null;
        this.pendingModeFilters = null;

        // Reset spot info key
        this.lastSpotInfoKey = null;

        // Clear band/mode tracking
        this.lastBand = null;
        this.lastMode = null;
        this.lastFetchBand = null;
        this.lastFetchContinent = null;
        this.lastFetchAge = null;

        // Reset timestamps
        this.lastUpdateTime = null;
        this.lastWaterfallFrequencyCommandTime = 0;
        this.lastFrequencyRefreshTime = 0;
        this.lastSpotCollectionTime = 0;

        // Clear canvas and context references to force reinitialization
        this.canvas = null;
        this.ctx = null;

        // Clear DOM element references
        this.spotInfoDiv = null;
        this.zoomMenuDiv = null;
        this.$freqCalculated = null;
        this.$qrgUnit = null;
        this.$bandSelect = null;
        this.$modeSelect = null;

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

    // Hide spot tooltip when changing frequency
    if (typeof dxWaterfall !== 'undefined' && dxWaterfall.hideSpotTooltip) {
        dxWaterfall.hideSpotTooltip();
    }

    // Check if already changing frequency and block rapid commands
    if (typeof dxWaterfall !== 'undefined' && dxWaterfall.frequencyChanging) {
        return;
    }

    // Add simple debounce to prevent rapid-fire calls
    // But allow waterfall-initiated calls (spot clicks) to bypass debounce
    var now = Date.now();
    if (typeof setFrequency.lastCall === 'undefined') {
        setFrequency.lastCall = 0;
    }
    if (!fromWaterfall && now - setFrequency.lastCall < 500) { // Only debounce external calls
        return;
    }
    setFrequency.lastCall = now;

    var formattedFreq = Math.round(frequencyInKHz * 1000); // Convert to Hz and round to integer
    var modeVal = $('#mode').val();

    // Check if CAT is enabled and configured
    if (isCATAvailable()) {
        // Set the radio frequency via CAT command
        // Use formattedFreq (in Hz) for consistency - rounded to integer for radio compatibility

        // Map UI mode to CAT mode parameter using determineRadioMode logic
        // CAT expects specific modes like: CW, RTTY, PSK, USB, LSB, AM, FM
        var catMode = 'usb'; // Default fallback

        if (modeVal) {
            var modeUpper = modeVal.toUpperCase();

            // Use the same logic as determineRadioMode for consistency
            // CW modes
            if (modeUpper === 'CW' || modeUpper === 'A1A') {
                catMode = 'cw';
            }
            // Digital modes that should be sent as RTTY
            else if (modeUpper === 'RTTY') {
                catMode = 'rtty';
            }
            // PSK modes - transceiver expects 'psk'
            else if (modeUpper.indexOf('PSK') !== -1) {
                catMode = 'psk';
            }
            // AM mode
            else if (modeUpper === 'AM') {
                catMode = 'am';
            }
            // FM mode
            else if (modeUpper === 'FM') {
                catMode = 'fm';
            }
            // USB/LSB - pass through directly (already in correct format from determineRadioMode)
            else if (modeUpper === 'USB') {
                catMode = 'usb';
            }
            else if (modeUpper === 'LSB') {
                catMode = 'lsb';
            }
            // Any other mode - default to frequency-based USB/LSB
            else {
                var ssbMode = DX_WATERFALL_UTILS.modes.determineSSBMode(frequencyInKHz);
                catMode = ssbMode.toLowerCase();
            }
        }

        // Use the new unified tuneRadioToFrequency function with callbacks
        if (typeof tuneRadioToFrequency === 'function') {
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

            // Define success callback
            var onSuccess = function(data, textStatus, jqXHR) {
                // Optionally log response if it contains useful info
                if (data && data.trim() !== '') {
                    // Response received
                }

                // Get timing based on connection type (WebSocket vs Polling)
                var timings = getCATTimings();

                // Clear frequency changing flag on successful command
                if (typeof dxWaterfall !== 'undefined') {
                    // Clear frequency changing flag immediately since CAT command succeeded
                    // The catTuning flag will be cleared by invalidateFrequencyCache() when frequency is confirmed
                    setTimeout(function() {
                        dxWaterfall.frequencyChanging = false;
                    }, timings.commitDelay); // WebSocket: 20ms, Polling: 50ms
                }

                // Set a timeout to unlock if radio doesn't confirm - WebSocket uses 500ms, Polling uses 3000ms
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
                }, timings.confirmTimeout); // WebSocket: 500ms, Polling: 3000ms
            };

            // Define error callback
            var onError = function(jqXHR, textStatus, errorThrown) {
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
            };

            // Call unified tuning function with callbacks
            // Pass skipWaterfall=true to prevent infinite loop (don't call setFrequency again)
            tuneRadioToFrequency(null, formattedFreq, catMode, onSuccess, onError, true);
        }
        return;
    }

    // CAT not available - use manual frequency setting
    // Update both frequency fields
    $('#frequency').val(formattedFreq);

    // Also update freq_calculated field that waterfall reads from
    var freqInKHz = frequencyInKHz;
    $('#freq_calculated').val(freqInKHz);

    // Only trigger change if this is NOT from waterfall (external frequency change)
    if (!fromWaterfall) {
        $('#frequency').trigger('change');
    }

    // Clear navigation flags immediately since no CAT operation is happening
    if (typeof dxWaterfall !== 'undefined') {
        dxWaterfall.frequencyChanging = false;
        dxWaterfall.catTuning = false; // No CAT, so no CAT tuning
        dxWaterfall.spotNavigating = false; // Clear navigation flag immediately
        // Don't call invalidateFrequencyCache - it's for CAT confirmation
        // When CAT is disabled, waterfall frequency is managed independently
    }
}

// Wait for jQuery to be available before initializing
(function waitForJQuery() {
    if (typeof jQuery !== 'undefined') {
        // jQuery is loaded, proceed with initialization
        $(document).ready(function() {
            // Initialize DOM cache
            DX_WATERFALL_UTILS.dom.init();

            // Function to try initializing the canvas with retries
            function tryInitCanvas() {
        if (document.getElementById('dxWaterfall')) {
            // Canvas found, but DON'T auto-initialize
            // Wait for user to click the power button

            // Set up DX spots fetching at regular intervals (only when initialized)
            setInterval(function() {
                if (dxWaterfall.canvas) { // Only fetch if waterfall has been initialized
                    dxWaterfall.fetchDxSpots(true, false); // Background fetch - NOT user-initiated
                }
            }, DX_WATERFALL_CONSTANTS.DEBOUNCE.DX_SPOTS_FETCH_INTERVAL_MS);

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

    // Handle click on the cycle icon in dxWaterfallSpotContent div to cycle through spots
    $('#dxWaterfallSpotContent').on('click', '.cycle-spot-icon', function(e) {
        e.stopPropagation(); // Prevent event bubbling

        // Prevent rapid clicking - check if navigation is in progress
        if (dxWaterfall.spotNavigating) {
            return;
        }

        // Cycle to next spot
        if (dxWaterfall.relevantSpots.length > 1) {
            dxWaterfall.spotNavigating = true;

            dxWaterfall.currentSpotIndex = (dxWaterfall.currentSpotIndex + 1) % dxWaterfall.relevantSpots.length;

            // Update spot info display
            dxWaterfall.updateSpotInfoDiv();

            // Clear QSO form first
            DX_WATERFALL_UTILS.qsoForm.clearForm();

            // Populate form with the new spot data after delay
            setTimeout(function() {
                var spotInfo = dxWaterfall.getSpotInfo();
                if (spotInfo) {
                    DX_WATERFALL_UTILS.qsoForm.populateFromSpot(spotInfo, true);
                }

                // Re-enable navigation after operation completes
                setTimeout(function() {
                    dxWaterfall.spotNavigating = false;
                }, 100);
            }, 100);

            // Visual feedback - briefly change icon color (with transition for smooth effect)
            var icon = $(this);
            icon.css({'color': '#FFFF00', 'transition': 'color 0.2s'});
            setTimeout(function() {
                icon.css('color', '');
            }, 200);
        }
    });

    // Handle click on the tune icon in dxWaterfallSpotContent div to set frequency
    $('#dxWaterfallSpotContent').on('click', '.tune-icon', function(e) {
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

            // Visual feedback - briefly change icon color (with transition for smooth effect)
            var icon = $(this);
            icon.css({'color': '#FFFF00', 'transition': 'color 0.2s'});
            setTimeout(function() {
                icon.css('color', '');
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

    // Handle click on label size cycle button
    $('#dxWaterfallMenu').on('click', '.label-size-icon', function(e) {
        e.stopPropagation();
        e.preventDefault();

        // Cycle through 5 label sizes: 0 -> 1 -> 2 -> 3 -> 4 -> 0
        dxWaterfall.labelSizeLevel = (dxWaterfall.labelSizeLevel + 1) % 5;

        // Save to cookie
        dxWaterfall.saveFontSizeToCookie();

        // Visual feedback - briefly change icon color BEFORE updating menu
        var icon = $(this);
        icon.css({'color': '#FFFF00', 'transition': 'color 0.2s'});

        // Wait for visual feedback, then update menu and refresh
        setTimeout(function() {
            // Update the menu to show new size in tooltip (this replaces the icon)
            dxWaterfall.updateZoomMenu();

            // Refresh the display to show new label sizes
            dxWaterfall.refresh();
        }, DX_WATERFALL_CONSTANTS.DEBOUNCE.ZOOM_ICON_FEEDBACK_MS);
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

        // First check if user clicked on a spot label - if so, tune to that exact spot frequency
        var clickedSpot = dxWaterfall.findSpotAtPosition(x, y);
        if (clickedSpot && clickedSpot.frequency) {
            // User clicked on a spot label - tune to exact spot frequency and set mode

            // Set navigation flag to block refresh interference during spot click
            DX_WATERFALL_UTILS.navigation.navigating = true;

            // Set CAT debounce lock BEFORE mode/frequency changes to block incoming CAT updates
            if (typeof window.dxwaterfall_cat_debounce_lock !== 'undefined') {
                window.dxwaterfall_cat_debounce_lock = 1;
            }

            if (dxWaterfall.isFrequencyFarOutsideBand(clickedSpot.frequency)) {
                dxWaterfall.updateBandFromFrequency(clickedSpot.frequency);
            }

            // CRITICAL: Set mode FIRST (without triggering change event), THEN set frequency
            // This ensures setFrequency() reads the correct mode from the dropdown
            var radioMode = DX_WATERFALL_UTILS.navigation.determineRadioMode(clickedSpot);
            setMode(radioMode, true); // skipTrigger = true to prevent change event

            // Now set frequency - it will read the correct mode from the dropdown
            setFrequency(clickedSpot.frequency, true);

            // Send frequency command again after short delay to correct any drift from mode change
            // (radio control lib bug: mode change can cause slight frequency shift)
            setTimeout(function() {
                setFrequency(clickedSpot.frequency, true);
            }, 200); // 200ms delay to let mode change settle

            // Populate QSO form - flag will be cleared when population completes
            DX_WATERFALL_UTILS.qsoForm.populateFromSpot(clickedSpot, true);

            return; // Don't calculate frequency from position
        }

        // No spot label clicked - calculate frequency at clicked position
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

        // Update cache directly AND sync tracking variables to prevent recalculation
        var formattedFreq = Math.round(clickedFreq * 1000); // Convert to Hz
        dxWaterfall.cache.middleFreq = clickedFreq;
        dxWaterfall.lastValidCommittedFreq = clickedFreq; // Store in kHz
        dxWaterfall.lastValidCommittedUnit = 'kHz';
        dxWaterfall.lastQrgUnit = 'kHz';

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

        // Note: No need to call commitFrequency() here since we already set
        // lastValidCommittedFreq directly above (line 6407-6408)
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
        // Ctrl/Cmd+Space: Cycle through nearby spots
        else if (modKey && !e.shiftKey && e.key === ' ') {
            e.preventDefault();
            // Trigger the existing cycle icon click
            var cycleIcon = $('#dxWaterfallSpotContent .cycle-spot-icon');
            if (cycleIcon.length > 0) {
                cycleIcon.trigger('click');
            }
        }
        // Ctrl/Cmd+Shift+Space: Tune to current spot frequency
        else if (modKey && e.shiftKey && e.key === ' ') {
            e.preventDefault();
            // Find the tune icon in the spot info div and trigger it
            var tuneIcon = $('#dxWaterfallSpotContent .tune-icon');
            if (tuneIcon.length > 0) {
                tuneIcon.trigger('click');
            }
        }
    });

    // Handle DX Waterfall power on/off
    var waterfallActive = false;
    var waterfallRefreshInterval = null; // Store interval ID for cleanup

    // Initialize UI text and tooltip from language variables
    $('#dxWaterfallMessage').text(lang_dxwaterfall_turn_on);
    $('#dxWaterfallPowerOnIcon').attr('title', lang_dxwaterfall_turn_on);
    $('#dxWaterfallPowerOffIcon').attr('title', lang_dxwaterfall_turn_off);

    // Function to turn on waterfall (shared by icon and message click)
    var turnOnWaterfall = function(e) {
        if (waterfallActive) {
            return; // Already active, prevent double initialization
        }

        waterfallActive = true;

        // Update UI - hide header, show content area, show power-off icon, update container styling
        $('#dxWaterfallSpot').addClass('active');
        $('#dxWaterfallSpotHeader').addClass('hidden');
        $('#dxWaterfallSpotContent').addClass('active');
        $('#dxWaterfallPowerOffIcon').addClass('active');

        // Show waterfall and menu
        $('#dxWaterfallCanvasContainer').show();
        $('#dxWaterfallMenuContainer').show();

        // Initialize waterfall from scratch (destroy ensures clean state)
        if (typeof dxWaterfall !== 'undefined') {
            // Force reinitialization by ensuring canvas is null
            if (dxWaterfall.canvas) {
                dxWaterfall.destroy();
            }

            // Now initialize from clean state
            dxWaterfall.init();

            // Call refresh immediately to avoid delay
            if (dxWaterfall.canvas) {
                dxWaterfall.refresh();
            }

            // Set up periodic refresh - faster during CAT operations for spinner animation
            waterfallRefreshInterval = setInterval(function() {
                if (dxWaterfall.canvas) {
                    if (dxWaterfall.catTuning || dxWaterfall.frequencyChanging) {
                        // Fast refresh during CAT operations for spinner animation
                        dxWaterfall.refresh();
                    } else {
                        // Normal refresh when idle
                        dxWaterfall.refresh();
                    }
                }
            }, DX_WATERFALL_CONSTANTS.VISUAL.STATIC_NOISE_REFRESH_MS);
        }
    };

    // Click anywhere on the header div to turn on waterfall
    $('#dxWaterfallSpotHeader').on('click', turnOnWaterfall);

    // Click on power-off icon to turn off waterfall
    $('#dxWaterfallPowerOffIcon').on('click', function(e) {
        e.stopPropagation(); // Prevent triggering parent click

        if (!waterfallActive) {
            return; // Already inactive
        }

        waterfallActive = false;

        // Stop the refresh interval
        if (waterfallRefreshInterval) {
            clearInterval(waterfallRefreshInterval);
            waterfallRefreshInterval = null;
        }

        // Destroy the waterfall component (cleanup memory, timers, event handlers)
        if (typeof dxWaterfall !== 'undefined' && dxWaterfall.canvas) {
            dxWaterfall.destroy();
        }

        // Clear spot info and menu divs
        if (dxWaterfall.spotInfoDiv) {
            dxWaterfall.spotInfoDiv.innerHTML = '&nbsp;';
        }
        if (dxWaterfall.zoomMenuDiv) {
            dxWaterfall.zoomMenuDiv.innerHTML = '&nbsp;';
        }

        // Update UI - show header, hide content area, hide power-off icon, update container styling
        $('#dxWaterfallSpot').removeClass('active');
        $('#dxWaterfallSpotHeader').removeClass('hidden');
        $('#dxWaterfallSpotContent').removeClass('active');
        $('#dxWaterfallPowerOffIcon').removeClass('active');

        // Hide waterfall and menu
        $('#dxWaterfallCanvasContainer').hide();
        $('#dxWaterfallMenuContainer').hide();
    });
        }); // End of $(document).ready()
    } else {
        // jQuery not loaded yet, try again in 50ms
        setTimeout(waitForJQuery, 50);
    }
})(); // End of waitForJQuery IIFE


