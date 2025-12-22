// @ts-nocheck
/**
 * @fileoverview DX WATERFALL for WaveLog
 * @version 0.9.6 // also change line 32
 * @author Wavelog Team
 *
 * @description
 * Real-time DX spot visualization with interactive frequency navigation,
 * CAT control integration, and smart hunting features for amateur radio.
 *
 * @requires jQuery
 * @requires base_url (global from Wavelog)
 * @requires setFrequency (global function from Wavelog)
 * @requires setMode (global function from Wavelog)
 * @requires frequencyToBand (global function from Wavelog)

 * @features
 * - Canvas-based visualization
 * - ES6+ syntax (const/let recommended, var used for compatibility)
 * - Passive event listeners for scroll performance
 */

'use strict';


// ========================================
// CONSTANTS AND CONFIGURATION
// ========================================

var DX_WATERFALL_CONSTANTS = {
    // Version
    VERSION: '0.9.6', // DX Waterfall version (keep in sync with @version in file header)

    // Debug and logging
    DEBUG_MODE: false, // Set to true for verbose logging, false for production

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
        ZOOM_MENU_UPDATE_DELAY_MS: 150     	// Delay for zoom menu update after navigation
    },

    // CAT and radio control
    // Note: Some values are initialized and will be recalculated based on catPollInterval
    CAT: {
        POLL_INTERVAL_MS: 3000,           	// Default CAT polling interval (can be overridden by config)
        TUNING_FLAG_FALLBACK_MS: 4500,    	// Fallback timeout for tuning flags (1.5x poll interval)
        FREQUENCY_WAIT_TIMEOUT_MS: 6000,  	// Initial load wait time for CAT frequency (2x poll interval)

        // WebSocket timing (low latency - no overlay blink)
        WEBSOCKET_CONFIRM_TIMEOUT_MS: 2000, // WebSocket: Fast confirmation timeout (increased for rapid clicking)
        WEBSOCKET_FALLBACK_TIMEOUT_MS: 500, // WebSocket: Fast fallback timeout (vs 1.5x poll interval)
        WEBSOCKET_COMMIT_DELAY_MS: 10,      // WebSocket: Minimal commit delay (vs 50ms polling)

        // Polling timing (standard latency)
        POLLING_CONFIRM_TIMEOUT_MS: 10000,  // Polling: Extended confirmation timeout (3+ poll cycles for rapid clicking)
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
        MAJOR_TICK_TOLERANCE: 0.05,        	// Floating point precision for major tick detection
        SPOT_FREQUENCY_MATCH: 0.01,        	// Frequency match tolerance for spot navigation (kHz)
        CAT_FREQUENCY_HZ: 50,              	// CAT frequency confirmation tolerance (50 Hz for radio tuning variations)
        FREQUENCY_MATCH_KHZ: 0.1,          	// General frequency matching tolerance (kHz)
        CENTER_SPOT_TOLERANCE_KHZ: 0.1     	// Tolerance for center spot frequency matching (kHz)
    },

    // Zoom levels configuration
    ZOOM: {
        DEFAULT_LEVEL: 3,                  	// Default zoom level
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

    // Logo configuration
    LOGO_FILENAME: 'assets/logo/wavelog_logo_darkly_wide.png',

    // ========================================
    // STATE MACHINE STATES
    // ========================================
    STATES: {
        // Lifecycle states
        DISABLED: 'disabled',                   // DX Waterfall not initialized or fully destroyed
        INITIALIZING: 'initializing',           // Canvas setup, loading settings, event listeners
        DEINITIALIZING: 'deinitializing',       // Cleanup in progress, removing listeners, clearing timers

        // Data fetching states
        FETCHING_SPOTS: 'fetching_spots',       // AJAX request in progress (includes filter changes)

        // Frequency change states
        TUNING: 'tuning',                       // Radio tuning to new frequency (CAT command sent)

        // Normal operation states
        READY: 'ready',                         // Normal operation - DX Waterfall displaying (even if 0 spots)

        // Error states
        ERROR: 'error'                          // Critical error - user must manually restart DX Waterfall
    }

};

// ========================================
// STATE MACHINE
// ========================================

/**
 * DX Waterfall State Machine
 * Manages state transitions and ensures clean state handling
 */
var DXWaterfallStateMachine = {
    currentState: DX_WATERFALL_CONSTANTS.STATES.DISABLED,
    previousState: null,
    stateData: {},  // Additional data for current state
    stateTimer: null,  // Timer for state timeouts

    /**
     * Transition to a new state
     * @param {string} newState - New state from DX_WATERFALL_CONSTANTS.STATES
     * @param {Object} data - Optional data associated with the state
     */
    setState: function(newState, data) {
        // Validate state
        var validStates = Object.values(DX_WATERFALL_CONSTANTS.STATES);
        if (validStates.indexOf(newState) === -1) {
            DX_WATERFALL_UTILS.log.error('[State Machine] Invalid state: ' + newState);
            return false;
        }

        // Skip if already in this state (unless data changed)
        if (this.currentState === newState && !data) {
            return false;
        }

        var oldState = this.currentState;
        this.previousState = oldState;
        this.currentState = newState;
        this.stateData = data || {};

        // Clear any existing state timer
        if (this.stateTimer) {
            clearTimeout(this.stateTimer);
            this.stateTimer = null;
        }

        // Log state transition
        DX_WATERFALL_UTILS.log.debug('[State Machine] ' + oldState + ' → ' + newState +
            (data ? ' (' + JSON.stringify(data) + ')' : ''));

        // Call state entry handler
        this._onStateEnter(newState, oldState);

        // Trigger refresh if waterfall is initialized
        if (typeof dxWaterfall !== 'undefined' && dxWaterfall.canvas && dxWaterfall.ctx) {
            dxWaterfall.refresh();
        }

        return true;
    },

    /**
     * Get current state
     * @returns {string} Current state
     */
    getState: function() {
        return this.currentState;
    },

    /**
     * Check if in a specific state
     * @param {string} state - State to check
     * @returns {boolean} True if in that state
     */
    isState: function(state) {
        return this.currentState === state;
    },

    /**
     * Check if in any of the provided states
     * @param {Array<string>} states - Array of states to check
     * @returns {boolean} True if in any of those states
     */
    isAnyState: function(states) {
        return states.indexOf(this.currentState) !== -1;
    },

    /**
     * Get state data
     * @returns {Object} Current state data
     */
    getStateData: function() {
        return this.stateData;
    },

    /**
     * Set a timeout for current state (auto-transition on timeout)
     * @param {number} ms - Milliseconds until timeout
     * @param {string} timeoutState - State to transition to on timeout
     */
    setStateTimeout: function(ms, timeoutState) {
        var self = this;
        if (this.stateTimer) {
            clearTimeout(this.stateTimer);
        }

        this.stateTimer = setTimeout(function() {
            // TUNING → READY timeout is normal (fallback when radio doesn't respond quickly)
            // Only log warnings for other timeout transitions that indicate problems
            var isNormalTimeout = (self.currentState === 'tuning' && timeoutState === 'ready');

            if (!isNormalTimeout) {
                DX_WATERFALL_UTILS.log.warn('[State Machine] State timeout: ' + self.currentState + ' → ' + timeoutState);
            }

            self.setState(timeoutState);
        }, ms);
    },

    /**
     * Handle state entry
     * @private
     */
    _onStateEnter: function(newState, oldState) {
        var STATES = DX_WATERFALL_CONSTANTS.STATES;

        switch (newState) {
            case STATES.INITIALIZING:
                // Set timeout for initialization - increased to 15 seconds to account for 3-second initial delay
                this.setStateTimeout(15000, STATES.ERROR);
                break;

            case STATES.FETCHING_SPOTS:
                // Set timeout for fetch operation - network issues should trigger error
                this.setStateTimeout(DX_WATERFALL_CONSTANTS.AJAX.TIMEOUT_MS, STATES.ERROR);
                break;

            case STATES.TUNING:
                // Set timeout for tuning operation - fallback to READY if radio doesn't respond
                var timings = getCATTimings();
                this.setStateTimeout(timings.fallbackTimeout, STATES.READY);
                break;

            case STATES.READY:
                // Normal operation - no timeout needed
                break;

            case STATES.ERROR:
                // ERROR state has no auto-recovery
                // User must manually turn off/on DX Waterfall to recover
                DX_WATERFALL_UTILS.log.error('[State Machine] Entered ERROR state - manual recovery required');
                break;

            case STATES.DEINITIALIZING:
                // Cleanup should be fast - timeout to force DISABLED if stuck
                this.setStateTimeout(2000, STATES.DISABLED);
                break;
        }
    }
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
 * Handle CAT frequency update
 * Handles frequency confirmation and cache invalidation
 * @param {number} radioFrequency - Frequency from CAT in Hz
 * @param {Function} updateCallback - Function to call to update UI
 * @returns {boolean} - True if update was processed
 */
/**
 * Handle CAT frequency update
 * Manages state transitions based on frequency confirmation
 * @param {number} radioFrequency - Frequency from CAT in Hz
 * @param {Function} updateCallback - Function to call to update UI
 * @returns {boolean} - True if update was processed
 */
function handleCATFrequencyUpdate(radioFrequency, updateCallback) {
    var now = Date.now();

    // Check if frequency actually changed BEFORE updating UI
    var frequencyChanged = false;
    var isInitialLoad = false;

    if (typeof dxWaterfall !== 'undefined' && dxWaterfall.lastValidCommittedFreqHz !== null) {
        // Compare incoming CAT frequency with last committed value
        // CAT sends frequency in Hz, convert for comparison
        var lastHz = dxWaterfall.lastValidCommittedFreqHz;
        var incomingHz = parseFloat(radioFrequency);
        var tolerance = 1; // 1 Hz
        var diff = Math.abs(incomingHz - lastHz);
        frequencyChanged = diff > tolerance;
    } else if (typeof dxWaterfall !== 'undefined') {
        // First time receiving CAT frequency - always consider it changed
        isInitialLoad = true;
        frequencyChanged = true;
    }

    // Check if we're waiting for a specific frequency to be confirmed BEFORE updating UI
    var shouldSkipStaleUpdate = false;

    // If we're waiting for radio to tune to a target frequency, check if this CAT update is stale
    if (typeof dxWaterfall !== 'undefined' && dxWaterfall.targetFrequencyHz) {
        // In split operation mode, use RX frequency for confirmation (waterfall is centered on RX)
        // In simplex mode, use TX frequency (main frequency)
        var incomingHz;
        if (window.catState && window.catState.frequency_rx && window.catState.frequency_rx > 0) {
            // Split mode - check RX frequency
            incomingHz = parseFloat(window.catState.frequency_rx);
            DX_WATERFALL_UTILS.log.debug('[CAT] Split mode - using RX frequency for confirmation');
        } else {
            // Simplex mode - check TX frequency (main frequency)
            incomingHz = parseFloat(radioFrequency);
        }

        var targetHz = dxWaterfall.targetFrequencyHz;
        var toleranceHz = DX_WATERFALL_CONSTANTS.THRESHOLDS.CAT_FREQUENCY_HZ; // 50 Hz tolerance
        var diff = Math.abs(incomingHz - targetHz);

        DX_WATERFALL_UTILS.log.debug('[CAT] Frequency check - Target: ' + targetHz + ' Hz, Incoming: ' + incomingHz + ' Hz, Diff: ' + diff + ' Hz, Tolerance: ' + toleranceHz + ' Hz');

        dxWaterfall.targetFrequencyConfirmAttempts = (dxWaterfall.targetFrequencyConfirmAttempts || 0) + 1;

        if (diff <= toleranceHz) {
            // ========================================
            // FREQUENCY CONFIRMED - TRANSITION TO READY
            // ========================================
            DX_WATERFALL_UTILS.log.debug('[CAT] Frequency CONFIRMED - transitioning to READY');

            // Cancel any pending frequency confirmation timeout
            if (dxWaterfall.frequencyConfirmTimeoutId) {
                clearTimeout(dxWaterfall.frequencyConfirmTimeoutId);
                dxWaterfall.frequencyConfirmTimeoutId = null;
            }

            // Clear state machine timeout (prevents fallback timeout warning)
            if (DXWaterfallStateMachine.stateTimer) {
                clearTimeout(DXWaterfallStateMachine.stateTimer);
                DXWaterfallStateMachine.stateTimer = null;
            }

            dxWaterfall.targetFrequencyConfirmAttempts = 0;
            dxWaterfall.targetFrequencyHz = null;

            // Wait 2 render frames before transitioning to READY
            // This allows marker animation to complete behind the TUNING overlay
            dxWaterfall.readyTransitionTimer = setTimeout(function() {
                dxWaterfall.readyTransitionTimer = null;
                DXWaterfallStateMachine.setState(DX_WATERFALL_CONSTANTS.STATES.READY);
            }, DX_WATERFALL_CONSTANTS.VISUAL.STATIC_NOISE_REFRESH_MS * 2);

            shouldSkipStaleUpdate = false; // Proceed normally - radio is at correct frequency
        } else {
            // Frequency doesn't match - this is a stale update from before radio finished tuning
            DX_WATERFALL_UTILS.log.debug('[CAT] Frequency MISMATCH - attempt ' + dxWaterfall.targetFrequencyConfirmAttempts + ' of 3');

            // If we've tried 3 times and still no match, give up and accept current frequency
            if (dxWaterfall.targetFrequencyConfirmAttempts >= 3) {
                DX_WATERFALL_UTILS.log.debug('[CAT] Giving up after 3 attempts, accepting current frequency');
                dxWaterfall.targetFrequencyHz = null;
                dxWaterfall.targetFrequencyConfirmAttempts = 0;

                // Give up waiting, transition to READY
                DXWaterfallStateMachine.setState(DX_WATERFALL_CONSTANTS.STATES.READY);

                shouldSkipStaleUpdate = false; // Give up, accept current frequency
            } else {
                // Skip this stale update - waterfall already showing target frequency
                shouldSkipStaleUpdate = true;
                return true; // Exit early - don't process this stale CAT update
            }
        }
    }

    // Update UI with new frequency
    if (updateCallback) {
        updateCallback();
    }

    // Only invalidate cache and commit if frequency actually changed
    if (typeof dxWaterfall !== 'undefined' && (frequencyChanged || isInitialLoad)) {
        // IMPORTANT: Commit BEFORE invalidating cache
        if (dxWaterfall.commitFrequency) {
            dxWaterfall.commitFrequency();
        }
        if (dxWaterfall.invalidateFrequencyCache) {
            dxWaterfall.invalidateFrequencyCache();
        }
    }

    return true;
}

/**
 * Check if CAT control is available
 * @returns {boolean} - True if CAT is available (polling or websocket)
 */
function isCATAvailable() {
    // Check if global CAT state variable exists and is defined
    if (typeof dxwaterfall_cat_state === 'undefined' || dxwaterfall_cat_state === null) {
        return false;
    }

    // Check if tuneRadioToFrequency function exists
    if (typeof tuneRadioToFrequency !== 'function') {
        return false;
    }

    // Valid states are 'polling' or 'websocket'
    return (dxwaterfall_cat_state === "polling" || dxwaterfall_cat_state === "websocket");
}

// ========================================
// UTILITY FUNCTIONS
// ========================================

var DX_WATERFALL_UTILS = {

    // Logging utilities with debug control
    log: {
        /**
         * Debug log - only shown when DEBUG_MODE is true
         * @param {string} message - Log message
         */
        debug: function(message) {
            if (DX_WATERFALL_CONSTANTS.DEBUG_MODE && console && console.log) {
                console.log(message);
            }
        },

        /**
         * Warning log - always shown
         * @param {string} message - Warning message
         */
        warn: function(message) {
            if (console && console.warn) {
                console.warn(message);
            }
        },

        /**
         * Error log - always shown
         * @param {string} message - Error message
         * @param {Error} [error] - Optional error object
         */
        error: function(message, error) {
            if (console && console.error) {
                if (error) {
                    console.error(message, error);
                } else {
                    console.error(message);
                }
            }
        }
    },

    // Frequency utilities
    frequency: {
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

    // Mode classification utilities (now use global functions from radiohelpers.js)
    modes: {
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
        }
    },

    // Spot utilities for common spot object creation
    spots: {
        // Create standardized spot object from raw spot data
        createSpotObject: function(spot, options) {
            options = options || {};
            var spotFreq = parseFloat(spot.frequency);

            // Use spot.mode and spot.submode from backend (already classified with priority logic)
            // Backend provides: mode ('phone'/'cw'/'digi') and submode ('LSB'/'USB'/'CW'/'FT8'/etc.)
            var spotMode = spot.mode || '';
            var spotSubmode = spot.submode || '';

            var spotObj = {
                callsign: spot.spotted,
                frequency: spotFreq,
                mode: spotMode,
                submode: spotSubmode
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

            // Park references are provided by server in dxcc_spotted object
            if (options.includeParkRefs !== false) {
                spotObj.sotaRef = (spot.dxcc_spotted && spot.dxcc_spotted.sota_ref) || '';
                spotObj.potaRef = (spot.dxcc_spotted && spot.dxcc_spotted.pota_ref) || '';
                spotObj.iotaRef = (spot.dxcc_spotted && spot.dxcc_spotted.iota_ref) || '';
                spotObj.wwffRef = (spot.dxcc_spotted && spot.dxcc_spotted.wwff_ref) || '';
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

    // QSO form utilities
    qsoForm: {
        // Timer for pending population to allow cancellation
        pendingPopulationTimer: null,
        pendingLookupTimer: null,

        /**
         * Clear the QSO form by clicking the reset button
         * Note: reset_fields() in qso.js handles all field clearing including park references
         */
        clearForm: function() {
            var $btnReset = $('#btn_reset');
            if ($btnReset.length > 0) {
                $btnReset.click();
            }
        },

        /**
         * Populate QSO form with spot data (callsign, mode, and park references)
         * Assumes form has already been cleared if needed
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
                // Use global determineRadioMode from radiohelpers.js
                var frequencyHz = parseFloat(spotData.frequency) * 1000; // Convert kHz to Hz
                var radioMode = determineRadioMode(spotData.mode, frequencyHz);
                // Use skipTrigger=true to prevent change event race condition
                setMode(radioMode, true);
            }

            // Store park ref data to re-apply after callsign lookup clears the form
            // Don't populate them now - they'll just be cleared by resetDefaultQSOFields()
            var parkRefs = {
                sota: spotData.sotaRef || null,
                pota: spotData.potaRef || null,
                iota: spotData.iotaRef || null,
                wwff: spotData.wwffRef || null
            };

            // Trigger callsign lookup immediately, then trigger park ref lookups
            if (triggerLookup) {
                var self = this;

                // Set up one-time event listener for when callsign lookup completes
                $(document).one('callsignLookupComplete', function() {
                    // SAFETY CHECK: Validate callsign hasn't been manually changed
                    var currentCallsign = $('#callsign').val().toUpperCase().replace(/0/g, 'Ø');
                    var originalCallsign = spotData.callsign.toUpperCase().replace(/0/g, 'Ø');

                    if (currentCallsign !== originalCallsign) {
                        // User changed the callsign - don't apply park references from original spot
                        DX_WATERFALL_UTILS.log.debug('[DX Waterfall] Callsign changed (' + originalCallsign + ' → ' + currentCallsign + ') - skipping park ref population');
                        return;
                    }

                    // Re-populate park references after callsign lookup has cleared them
                    if (parkRefs.sota) {
                        var $sotaSelect = $('#sota_ref');
                        if ($sotaSelect.length > 0 && $sotaSelect[0].selectize) {
                            var sotaSelectize = $sotaSelect[0].selectize;
                            sotaSelectize.addOption({name: parkRefs.sota});
                            sotaSelectize.setValue(parkRefs.sota, false);
                            $('#sota_ref').trigger('change');
                        }
                    }

                    if (parkRefs.pota) {
                        var $potaSelect = $('#pota_ref');
                        if ($potaSelect.length > 0 && $potaSelect[0].selectize) {
                            var potaSelectize = $potaSelect[0].selectize;
                            potaSelectize.addOption({name: parkRefs.pota});
                            potaSelectize.setValue(parkRefs.pota, false);
                            $('#pota_ref').trigger('change');
                        }
                    }

                    if (parkRefs.iota) {
                        var $iotaSelect = $('#iota_ref');
                        if ($iotaSelect.length > 0) {
                            var optionExists = $iotaSelect.find('option[value="' + parkRefs.iota + '"]').length > 0;
                            if (!optionExists) {
                                $iotaSelect.append($('<option>', {
                                    value: parkRefs.iota,
                                    text: parkRefs.iota
                                }));
                            }
                            $iotaSelect.val(parkRefs.iota);
                            $('#iota_ref').trigger('change');
                        }
                    }

                    if (parkRefs.wwff) {
                        var $wwffSelect = $('#wwff_ref');
                        if ($wwffSelect.length > 0 && $wwffSelect[0].selectize) {
                            var wwffSelectize = $wwffSelect[0].selectize;
                            wwffSelectize.addOption({name: parkRefs.wwff});
                            wwffSelectize.setValue(parkRefs.wwff, false);
                            $('#wwff_ref').trigger('change');
                        }
                    }

                });

				// If navigating, delay the lookup slightly to avoid interference
				DX_WATERFALL_UTILS.navigation.navigating = false;

				// Set a short timer to trigger the lookup after navigation completes
                this.pendingLookupTimer = setTimeout(function() {
                    // Clear preventLookup flag just before triggering the lookup
                    if (wasPreventLookupSet) {
                        preventLookup = false;
                    }

                    // Trigger callsign lookup
                    callsignInput.trigger('focusout');

                    self.pendingLookupTimer = null;

                }, 50);
            } else {
                // No lookup - clear navigation flag immediately
                DX_WATERFALL_UTILS.navigation.navigating = false;
                // Clear the preventLookup flag
                if (wasPreventLookupSet) {
                    preventLookup = false;
                }
            }
        }
    },

    // Navigation utilities for spot navigation
    navigation: {
        // Timer for pending navigation actions
        pendingNavigationTimer: null,
        // Flag to block interference during navigation
        navigating: false,

        // Common navigation logic shared by all spot navigation functions
        navigateToSpot: function(waterfallContext, targetSpot, targetIndex, shouldPrefill) {
            // Default to false - only prefill if explicitly requested
            shouldPrefill = (typeof shouldPrefill !== 'undefined') ? shouldPrefill : false;

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
                // Only clear form if we're going to prefill it
                if (shouldPrefill) {
                    DX_WATERFALL_UTILS.qsoForm.clearForm();
                }

                // CRITICAL: Set mode FIRST before calling setFrequency
                // setFrequency reads the mode from $('#mode').val(), so the mode must be set first
                var frequencyHz = parseFloat(targetSpot.frequency) * 1000; // Convert kHz to Hz
                var radioMode = determineRadioMode(targetSpot.mode, frequencyHz);

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
                }, DX_WATERFALL_CONSTANTS.DEBOUNCE.MODE_CHANGE_SETTLE_MS);

                // Manually set the frequency in the input field immediately
                var formattedFreqHz = Math.round(targetSpot.frequency * 1000); // Convert kHz to Hz
                $('#frequency').val(formattedFreqHz);

                // CRITICAL: Directly update the cache to the target frequency
                // getCachedMiddleFreq() uses lastValidCommittedFreqHz which isn't updated by just setting the input value
                // So we bypass the cache and set it directly to ensure getSpotInfo() uses the correct frequency
                waterfallContext.cache.middleFreq = targetSpot.frequency; // Already in kHz
                waterfallContext.lastValidCommittedFreqHz = formattedFreqHz; // Store in Hz

                var cachedFreq = waterfallContext.getCachedMiddleFreq();

                // Now get spot info - this will use the new frequency we just set
                var spotInfo = waterfallContext.getSpotInfo();

                // Only populate form if explicitly requested
                if (shouldPrefill && spotInfo) {
                    var self = this;
                    // Clear form before populating
                    DX_WATERFALL_UTILS.qsoForm.clearForm();
                    this.pendingNavigationTimer = setTimeout(function() {
                        DX_WATERFALL_UTILS.qsoForm.populateFromSpot(spotInfo, true);
                        self.pendingNavigationTimer = null;
                        // Clear navigation flag after population completes
                        self.navigating = false;
                    }, DX_WATERFALL_CONSTANTS.DEBOUNCE.FORM_POPULATE_DELAY_MS);
                } else {
                    // Clear navigation flag immediately if not populating
                    this.navigating = false;
                }

                // Commit the new frequency
                setTimeout(function() {
                    waterfallContext.commitFrequency();
                }, 50);

                // Update zoom menu immediately to reflect navigation button states
                waterfallContext.updateZoomMenu(true);
            }

            return true;
        },

        // Check if navigation is allowed (not during frequency changes)
        canNavigate: function(waterfallContext) {
            var currentState = DXWaterfallStateMachine.getState();
            return currentState === DX_WATERFALL_CONSTANTS.STATES.READY && waterfallContext.allBandSpots.length > 0;
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
    dxSpots: [],
    initialFetchDone: false,
    totalSpotsCount: 0,

    // Timing
    pageLoadTime: null,
    operationStartTime: null,

    // Refresh throttling
    lastRefreshTime: 0,
    refreshPending: false,

    // ========================================
    // USER INTERFACE STATE
    // ========================================
    userEditingFrequency: false,

    // Cache for zoom menu HTML to prevent unnecessary DOM updates
    lastZoomMenuHTML: null,
    spotInfoDiv: null,
    spotTooltipDiv: null,
    lastSpotInfoKey: null,
    currentContinent: dxcluster_default_decont,
    currentMaxAge: dxcluster_default_maxage,

    // ========================================
    // SPOT NAVIGATION STATE
    // ========================================
    lastUpdateTime: null,
    lastFetchBand: null,
    lastFetchContinent: null,
    lastFetchAge: null,
    relevantSpots: [],
    currentSpotIndex: 0,
    allBandSpots: [],
    currentBandSpotIndex: 0,

    // ========================================
    // VISUAL CONFIGURATION
    // ========================================
    fonts: DX_WATERFALL_CONSTANTS.FONTS,
    labelSizeLevel: 2, // 0=x-small, 1=small, 2=medium (default), 3=large, 4=x-large
    labelSizeProcessing: false, // Mutex lock to prevent concurrent label size operations

    // ========================================
    // PERFORMANCE CACHING
    // ========================================
    cache: {
        noise1: null,
        noise2: null,
        currentNoiseFrame: 0,
        noiseWidth: 0,
        noiseHeight: 0,
        middleFreq: null,
        visibleSpots: null,
        visibleSpotsParams: null
    },

    // Misc flags
    lastPopulatedSpot: null,
    pendingSpotSelection: null,

    // Band tracking - tracks which band we currently have spots for
    currentSpotBand: null, // The band we last fetched spots for

    // Display configuration
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
    zoomMenuDiv: null,

    // ========================================
    // SMART HUNTER FUNCTIONALITY
    // ========================================
    smartHunterSpots: [],
    currentSmartHunterIndex: 0,
    smartHunterActive: false,

    // ========================================
    // CONTINENT FILTERING
    // ========================================
    continents: CONTINENTS, // Use global CONTINENTS constant from radiohelpers.js
    pendingContinent: null,

    // CAT frequency tracking
    targetFrequencyHz: null,
    targetFrequencyConfirmAttempts: 0,
    lastWaterfallFrequencyCommandTime: 0,
    lastFrequencyRefreshTime: 0,

    // Frequency commit tracking (single source of truth in Hz)
    lastValidCommittedFreqHz: null,
    committedFrequencyKHz: null,
    lastModeForCache: null,
    lastMarkerFreq: undefined,

    // Spot fetch debouncing
    userInitiatedFetch: false,
    lastSpotCollectionTime: 0,
    spotCollectionThrottleMs: DX_WATERFALL_CONSTANTS.DEBOUNCE.SPOT_COLLECTION_MS,
    fetchDebounceTimer: null,
    fetchDebounceMs: DX_WATERFALL_CONSTANTS.DEBOUNCE.FETCH_REQUEST_MS,

    // Mode filter management
    modeFilters: {
        phone: true,
        cw: true,
        digi: false
    },
    pendingModeFilters: null,
    modeFilterChangeTimer: null,

    ft8Frequencies: FT8_FREQUENCIES, // Use global FT8_FREQUENCIES constant from radiohelpers.js

    // Error handling
    errorShutdownTimer: null, // Timer for auto-shutdown after error state
    readyTransitionTimer: null, // Timer for delayed TUNING → READY transition

    // Band plan management
    bandPlans: null, // Cached band plans from database
    bandEdgesData: null, // Raw band edges data with mode information for mode indicators
    currentRegion: null, // Current IARU region (1, 2, 3)
    bandLimitsCache: null, // Cached band limits for current band+region
    cachedBandForEdges: null, // The band for which band edges are currently cached

    // ========================================
    // INITIALIZATION AND SETUP FUNCTIONS
    // ========================================

    /**
     * Check if waterfall is properly initialized
     * @returns {boolean} - True if canvas and context are initialized
     */
    isInitialized: function() {
        return this.canvas !== null && this.ctx !== null;
    },

    // ========================================
    // STATE-BASED RENDERING HELPERS
    // ========================================

    /**
     * Render waterfall in DISABLED state
     * @private
     */
    _renderDisabled: function() {
        // Canvas is not available - nothing to render
        // This should not normally be called as refresh() checks for canvas existence
    },

    /**
     * Render waterfall in INITIALIZING state
     * @private
     */
    _renderInitializing: function() {
        // Display waiting message with black screen, logo, and "Please wait" message
        // During the initial 3-second delay, this shows a loading screen
        this.displayWaitingMessage(decodeHtml(lang_dxwaterfall_please_wait));
        this.updateZoomMenu();
    },

    /**
     * Render waterfall in FETCHING_SPOTS state
     * @private
     */
    _renderFetchingSpots: function() {
        // Show fetching message only for user-initiated fetches or band changes
        // Background periodic refreshes should not show the waiting screen
        if (this.userInitiatedFetch || !this.dxSpots || this.dxSpots.length === 0) {
            this.displayWaitingMessage(lang_dxwaterfall_downloading_data);
        }

        // Update zoom menu to show loading state
        this.updateZoomMenu();
    },

    /**
     * Render waterfall in TUNING state
     * @private
     */
    _renderTuning: function() {
        // Display waiting message with "Changing frequency" text
        this.displayWaitingMessage(lang_dxwaterfall_changing_frequency);
        this.updateZoomMenu();
    },

    /**
     * Render waterfall in READY state (normal operation)
     * @private
     */
    _renderReady: function() {
        // Update dimensions to match current CSS
        this.updateDimensions();

        // Collect all band spots for navigation
        this.collectAllBandSpots();
        this.collectSmartHunterSpots();

        // Always update zoom menu in READY state
        this.updateZoomMenu();

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
    },

    /**
     * Render waterfall in ERROR state
     * Auto-shuts down after 5 seconds
     * @private
     */
    _renderError: function() {
        // Get error message from state data if available
        var stateData = DXWaterfallStateMachine.getStateData();
        var errorMessage = stateData.message || 'Error occurred - DX Waterfall will shut down';

        DX_WATERFALL_UTILS.drawing.drawOverlayMessage(
            this.canvas,
            this.ctx,
            errorMessage,
            'MESSAGE_TEXT_WHITE'
        );

        // Set up auto-shutdown timer if not already set
        if (!this.errorShutdownTimer) {
            var self = this;
            this.errorShutdownTimer = setTimeout(function() {
                self.errorShutdownTimer = null;

                // Show error toast notification (10 seconds)
                if (typeof showToast === 'function') {
                    var toastMessage = (typeof lang_dxwaterfall_error_shutdown !== 'undefined')
                        ? lang_dxwaterfall_error_shutdown
                        : 'DX Waterfall has experienced an unexpected error and will be shut down. Please contact the Wavelog team for assistance.';
                    showToast('DX Waterfall Error', toastMessage, 'bg-danger text-white', 10000);
                }

                // Trigger power-off icon click to cleanly shut down waterfall
                $('#dxWaterfallPowerOffIcon').trigger('click');
            }, 5000); // 5 seconds
        }
    },

    /**
     * Render waterfall in DEINITIALIZING state
     * @private
     */
    _renderDeinitializing: function() {
        // Show cleanup message
        DX_WATERFALL_UTILS.drawing.drawOverlayMessage(
            this.canvas,
            this.ctx,
            'Shutting down...',
            'MESSAGE_TEXT_WHITE'
        );
    },

    // ========================================
    // INITIALIZATION AND SETUP FUNCTIONS
    // ========================================

    /**
     * Initialize the DX waterfall canvas and event handlers
     * Sets up canvas context, dimensions, and starts initial data fetch
     * @returns {void}
     */
    init: function() {
        // Check if already initialized to prevent duplicate initialization
        var currentState = DXWaterfallStateMachine.getState();
        if (currentState !== DX_WATERFALL_CONSTANTS.STATES.DISABLED) {
            DX_WATERFALL_UTILS.log.debug('[DX Waterfall] Already initialized, skipping');
            return;
        }

        // Check if we have valid frequency data before initializing
        var $freqInput = $('#frequency');
        var currentFreq = parseFloat($freqInput.val()) || 0;

        if (currentFreq === 0 || !DX_WATERFALL_UTILS.frequency.isValid(currentFreq)) {
            // No valid frequency - try to populate from freq_calculated if available
            var freqCalc = parseFloat($('#freq_calculated').val()) || 0;
            var unit = $('#qrg_unit').text() || 'kHz';
            if (freqCalc > 0) {
                var freqHz = convertFrequency(freqCalc, unit, 'Hz');
                $('#frequency').val(freqHz);
                $('#frequency').trigger('change');
                DX_WATERFALL_UTILS.log.debug('[DX Waterfall] Populated frequency from display field: ' + freqHz + ' Hz');
            }

            // Wait for frequency data
            DX_WATERFALL_UTILS.log.debug('[DX Waterfall] Waiting for valid frequency data...');

            // Transition to INITIALIZING state to show waiting message
            DXWaterfallStateMachine.setState(DX_WATERFALL_CONSTANTS.STATES.INITIALIZING);

            // Initialize canvas to show waiting message
            if (!this._initializeCanvas()) {
                DXWaterfallStateMachine.setState(DX_WATERFALL_CONSTANTS.STATES.ERROR, {
                    message: 'Canvas element not found'
                });
                return;
            }

            // Show waiting message while checking for valid frequency
            this.displayWaitingMessage(lang_dxwaterfall_please_wait);

            // Set up retry mechanism to check for valid frequency
            var self = this;
            var frequencyCheckTimer = null;
            var checkFrequency = function(attemptsLeft) {
                // Check if already completed (state changed from INITIALIZING)
                var state = DXWaterfallStateMachine.getState();
                if (state === DX_WATERFALL_CONSTANTS.STATES.READY ||
                    state === DX_WATERFALL_CONSTANTS.STATES.ERROR ||
                    state === DX_WATERFALL_CONSTANTS.STATES.DISABLED) {
                    // Already initialized, error occurred, or waterfall was disabled - stop checking
                    if (frequencyCheckTimer) {
                        clearTimeout(frequencyCheckTimer);
                        frequencyCheckTimer = null;
                    }
                    return;
                }

                var freq = parseFloat($freqInput.val()) || 0;
                if (freq > 0 && DX_WATERFALL_UTILS.frequency.isValid(freq)) {
                    // Valid frequency found - proceed with initialization
                    DX_WATERFALL_UTILS.log.debug('[DX Waterfall] Valid frequency detected: ' + freq + ' Hz');
                    if (frequencyCheckTimer) {
                        clearTimeout(frequencyCheckTimer);
                        frequencyCheckTimer = null;
                    }
                    self._completeInitialization();
                } else if (attemptsLeft > 0) {
                    // Retry after delay
                    frequencyCheckTimer = setTimeout(function() {
                        checkFrequency(attemptsLeft - 1);
                    }, DX_WATERFALL_CONSTANTS.DEBOUNCE.FREQUENCY_COMMIT_RETRY_MS);
                } else {
                    // Give up after max attempts - show error
                    if (frequencyCheckTimer) {
                        clearTimeout(frequencyCheckTimer);
                        frequencyCheckTimer = null;
                    }
                    DXWaterfallStateMachine.setState(DX_WATERFALL_CONSTANTS.STATES.ERROR, {
                        message: 'No valid frequency data available'
                    });
                }
            };

            // Start checking for valid frequency (20 attempts = 2 seconds)
            checkFrequency(20);
            return;
        }

        // Valid frequency available - proceed with initialization immediately
        this._completeInitialization();
    },

    /**
     * Complete waterfall initialization after valid frequency is confirmed
     * @private
     */
    _completeInitialization: function() {
        // Check if already initialized to prevent duplicate initialization
        var currentState = DXWaterfallStateMachine.getState();
        if (currentState === DX_WATERFALL_CONSTANTS.STATES.READY) {
            DX_WATERFALL_UTILS.log.debug('[DX Waterfall] Already in READY state, skipping re-initialization');
            return;
        }

        // Ensure we're in INITIALIZING state
        if (currentState !== DX_WATERFALL_CONSTANTS.STATES.INITIALIZING) {
            DXWaterfallStateMachine.setState(DX_WATERFALL_CONSTANTS.STATES.INITIALIZING);
        }

        // Always log initialization (user-facing message)
        DX_WATERFALL_UTILS.log.debug('[DX Waterfall] Initializing...');

        // Initialize canvas and context (may already be initialized from waiting state)
        if (!this.canvas || !this.ctx) {
            if (!this._initializeCanvas()) {
                // Failed to initialize - transition to ERROR state
                DXWaterfallStateMachine.setState(DX_WATERFALL_CONSTANTS.STATES.ERROR, {
                    message: 'Canvas element not found'
                });
                return; // Canvas not found, abort initialization
            }
        }

        // Set up event listeners
        this._setupEventListeners();

        // Load saved settings and initialize state
        this._loadSettings();

        // Set up initial frequency commit
        this._setupInitialFrequencyCommit();

        // Force initial spot fetch to transition from INITIALIZING to FETCHING_SPOTS
        // This ensures we don't get stuck in INITIALIZING state
        var self = this;
        setTimeout(function() {
            // Only fetch if still in INITIALIZING state (not already fetching)
            if (DXWaterfallStateMachine.getState() === DX_WATERFALL_CONSTANTS.STATES.INITIALIZING) {
                self.fetchDxSpots({userInitiated: false});
            }
        }, 100);

        // Trigger initial refresh to display INITIALIZING state
        this.refresh();

        // Always log successful initialization (user-facing message)
        DX_WATERFALL_UTILS.log.debug('[DX Waterfall] v' + DX_WATERFALL_CONSTANTS.VERSION + ' loaded successfully');
    },

    /**
     * Continue initialization after 3-second delay (called from turnOnWaterfall)
     * Checks for valid frequency and proceeds with setup
     * @private
     */
    _continueInitialization: function() {
        // Check if we have valid frequency data before proceeding
        var $freqInput = $('#frequency');
        var currentFreq = parseFloat($freqInput.val()) || 0;

        // If no frequency but we're in offline mode and have a valid band, use typical band frequency
        if ((currentFreq === 0 || !DX_WATERFALL_UTILS.frequency.isValid(currentFreq)) &&
            typeof isCATAvailable === 'function' && !isCATAvailable()) {
            var currentBand = this.$bandSelect ? this.$bandSelect.val() : null;
            if (currentBand && currentBand.toLowerCase() !== 'select') {
                var bandFreq = getTypicalBandFrequency(currentBand);
                if (bandFreq > 0) {
                    // Use typical band frequency for initialization
                    currentFreq = bandFreq;
                    DX_WATERFALL_UTILS.log.debug('[DX Waterfall] Offline mode - using typical frequency for ' + currentBand + ': ' + currentFreq + ' kHz');
                }
            }
        }

        if (currentFreq === 0 || !DX_WATERFALL_UTILS.frequency.isValid(currentFreq)) {
            // No valid frequency yet - set up retry mechanism
            DX_WATERFALL_UTILS.log.debug('[DX Waterfall] Waiting for valid frequency data...');

            var self = this;
            var frequencyCheckTimer = null;
            var checkFrequency = function(attemptsLeft) {
                // Check if already completed (state changed from INITIALIZING)
                var state = DXWaterfallStateMachine.getState();
                if (state === DX_WATERFALL_CONSTANTS.STATES.READY ||
                    state === DX_WATERFALL_CONSTANTS.STATES.FETCHING_SPOTS ||
                    state === DX_WATERFALL_CONSTANTS.STATES.ERROR) {
                    // Already initialized or error occurred - stop checking
                    if (frequencyCheckTimer) {
                        clearTimeout(frequencyCheckTimer);
                        frequencyCheckTimer = null;
                    }
                    return;
                }

                var freq = parseFloat($freqInput.val()) || 0;
                if (freq > 0 && DX_WATERFALL_UTILS.frequency.isValid(freq)) {
                    // Valid frequency found - proceed with initialization
                    DX_WATERFALL_UTILS.log.debug('[DX Waterfall] Valid frequency detected: ' + freq + ' Hz');
                    if (frequencyCheckTimer) {
                        clearTimeout(frequencyCheckTimer);
                        frequencyCheckTimer = null;
                    }
                    self._completeInitialization();
                } else if (attemptsLeft > 0) {
                    // Retry after delay
                    frequencyCheckTimer = setTimeout(function() {
                        checkFrequency(attemptsLeft - 1);
                    }, DX_WATERFALL_CONSTANTS.DEBOUNCE.FREQUENCY_COMMIT_RETRY_MS);
                } else {
                    // Give up after max attempts - show error
                    if (frequencyCheckTimer) {
                        clearTimeout(frequencyCheckTimer);
                        frequencyCheckTimer = null;
                    }
                    DXWaterfallStateMachine.setState(DX_WATERFALL_CONSTANTS.STATES.ERROR, {
                        message: 'No valid frequency data available'
                    });
                }
            };

            // Start checking for valid frequency (20 attempts = 2 seconds)
            checkFrequency(20);
            return;
        }

        // Valid frequency available - proceed with initialization immediately
        DX_WATERFALL_UTILS.log.debug('[DX Waterfall] Valid frequency detected: ' + currentFreq + ' Hz');
        this._completeInitialization();
    },

    /**
     * Initialize canvas element and context
     * @private
     * @returns {boolean} - True if successful, false if canvas not found
     */
    _initializeCanvas: function() {
        this.canvas = document.getElementById('dxWaterfall');

        // Check if canvas element exists
        if (!this.canvas) {
            return false;
        }

        this.ctx = this.canvas.getContext('2d');
        var $waterfall = DX_WATERFALL_UTILS.dom.getWaterfall();
        this.canvas.width = $waterfall.width();
        this.canvas.height = $waterfall.height();

        // Get reference to spot info div and menu div
        this.spotInfoDiv = document.getElementById('dxWaterfallSpotContent');
        this.zoomMenuDiv = document.getElementById('dxWaterfallMenu');

        // Cache frequently accessed DOM elements for performance
        this.$frequency = $('#frequency');           // Single source of truth (Hz)
        this.$freqCalculated = $('#freq_calculated'); // Display field (computed from frequency)
        this.$qrgUnit = $('#qrg_unit');
        this.$bandSelect = $('#band');
        this.$modeSelect = $('#mode');

        // Set page load time for waiting state management
        this.pageLoadTime = Date.now();
        this.operationStartTime = Date.now();

        // Set initial div content to maintain layout
        if (this.spotInfoDiv) {
            this.spotInfoDiv.innerHTML = '&nbsp;';
        }
        if (this.zoomMenuDiv) {
            this.zoomMenuDiv.innerHTML = '&nbsp;';
        }

        return true;
    },

    /**
     * Set up all event listeners for canvas and form inputs
     * @private
     */
    _setupEventListeners: function() {
        var self = this;

        // Ensure canvas exists before setting up event listeners
        if (!this.canvas) {
            DX_WATERFALL_UTILS.log.warn('[DX Waterfall] Cannot setup event listeners - canvas not initialized');
            return;
        }

        // Remove any existing event listeners first to prevent duplicates
        if (this._wheelHandler) {
            this.canvas.removeEventListener('wheel', this._wheelHandler);
        }
        if (this._mousemoveHandler) {
            this.canvas.removeEventListener('mousemove', this._mousemoveHandler);
        }

        // Store event handler references for proper cleanup
        this._wheelHandler = function(e) {
            var currentState = DXWaterfallStateMachine.getState();
            if (currentState === DX_WATERFALL_CONSTANTS.STATES.TUNING) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            var delta = e.deltaY;
            if (delta < 0) {
                self.zoomIn();
            } else if (delta > 0) {
                self.zoomOut();
            }
        };

        this._mousemoveHandler = function(e) {
            self.handleSpotLabelHover(e);
        };

        // Add canvas event listeners
        this.canvas.addEventListener('wheel', this._wheelHandler, { passive: false });
        this.canvas.addEventListener('mousemove', this._mousemoveHandler);

        DX_WATERFALL_UTILS.log.debug('[DX Waterfall] Event listeners attached to canvas');

        // Set up frequency input event listeners
        this.$freqCalculated.on('focus', function() {
            self.userEditingFrequency = true;
            var currentState = DXWaterfallStateMachine.getState();

            // If user is editing frequency while in TUNING state and no target set, transition to READY
            if (currentState === DX_WATERFALL_CONSTANTS.STATES.TUNING && !self.targetFrequencyHz) {
                DX_WATERFALL_UTILS.log.debug('[DX Waterfall] FOCUS: User editing frequency, transitioning to READY');
                DXWaterfallStateMachine.setState(DX_WATERFALL_CONSTANTS.STATES.READY);
                self.updateZoomMenu();
            }
            // On first focus before any commit, commit the initial frequency
            if (self.lastValidCommittedFreqHz === null) {
                var currentFreqHz = parseFloat(self.$frequency.val()) || 0;
                // If frequency is empty or 0, use set_new_qrg logic to get default for current band/mode
                if (currentFreqHz <= 0) {
                    if (typeof set_new_qrg === 'function') {
                        set_new_qrg().then(function() {
                            self.commitFrequency();
                        });
                        return; // Exit and let async completion handle commit
                    }
                }
                self.commitFrequency();
            }
        });

        this.$freqCalculated.on('blur', function() {
            self.userEditingFrequency = false;
            self.commitFrequency();
        });

        this.$freqCalculated.on('input', function() {
            self.userEditingFrequency = true;
        });

        this.$freqCalculated.on('keydown', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                self.userEditingFrequency = false;
                self.commitFrequency();
                $(this).blur();
                return false;
            }
        });

        // Listen to frequency field changes (single source of truth)
        // This catches updates from qrg_handler.js when user edits freq_calculated
        // or from CAT updates, ensuring waterfall stays in sync
        this.$frequency.on('change', function() {
            // Don't commit during user typing - wait for blur/Enter
            if (!self.userEditingFrequency) {
                self.commitFrequency();
            }
        });

        // Set up band dropdown change handler for offline mode
        // When user changes band in offline mode, update frequency and fetch spots
        this.$bandSelect.on('change', function() {
            // Only handle in offline mode
            if (typeof isCATAvailable === 'function' && !isCATAvailable()) {
                var newBand = $(this).val();

                // Get a typical frequency for the selected band
                var bandFreq = getTypicalBandFrequency(newBand);

                if (bandFreq > 0) {
                    // Update frequency field
                    self.$freqCalculated.val(bandFreq);
                    self.$qrgUnit.text('kHz');

                    // Update virtual CAT state
                    var freqHz = bandFreq * 1000; // Convert kHz to Hz
                    if (typeof window.catState === 'undefined' || window.catState === null) {
                        window.catState = {};
                    }
                    window.catState.frequency = freqHz;
                    window.catState.lastUpdate = Date.now();

                    // Update mode from form if available
                    if (self.$modeSelect && self.$modeSelect.val()) {
                        window.catState.mode = self.$modeSelect.val();
                    }

                    DX_WATERFALL_UTILS.log.debug('[DX Waterfall] Offline mode - band change to ' + newBand + ': virtual CAT updated with freq=' + freqHz + ' Hz');

                    // Commit the frequency change
                    self.commitFrequency();

                    // Fetch spots for the new band (state machine will handle FETCHING_SPOTS state)
                    self.fetchDxSpots(true, true); // User-initiated fetch

                    // Invalidate band-related caches
                    self.bandLimitsCache = null;
                    self.cachedBandForEdges = newBand;
                    self.currentSpotBand = newBand;

                    // Force refresh to show "Waiting for DX Cluster data" message
                    if (self.canvas && self.ctx) {
                        self.refresh();
                    }
                }
            }
        });

        // Set up mode dropdown change handler
        // When mode changes (CAT or manual), update display and refresh waterfall
        this.$modeSelect.on('change', function() {
            var newMode = $(this).val();

            // Update virtual CAT state (for both online and offline modes)
            if (typeof window.catState === 'undefined' || window.catState === null) {
                window.catState = {};
            }

            // In offline mode, also preserve frequency
            if (typeof isCATAvailable === 'function' && !isCATAvailable()) {
                // Preserve existing frequency if available - read from single source of truth
                if (!window.catState.frequency && self.$frequency.val()) {
                    var freqHz = parseFloat(self.$frequency.val());
                    window.catState.frequency = freqHz; // Already in Hz
                }
                DX_WATERFALL_UTILS.log.debug('[DX Waterfall] Offline mode - mode change to ' + newMode + ': virtual CAT updated');
            } else {
                DX_WATERFALL_UTILS.log.debug('[DX Waterfall] CAT mode change detected: ' + newMode);
            }

            window.catState.mode = newMode;
            window.catState.lastUpdate = Date.now();

            // Force refresh to update bandwidth indicator with new mode
            if (self.canvas && self.ctx) {
                self.refresh();
            }

            // Update relevant spots collection (mode affects spot filtering)
            if (self.collectAllBandSpots) {
                self.collectAllBandSpots(true);
            }
        });
    },

    /**
     * Load saved settings from cookies and initialize state
     * @private
     */
    _loadSettings: function() {
        this.loadSettingsFromCookies();

        // Initialize band cache for edge calculations
        this.cachedBandForEdges = this.getCurrentBand();
    },

    /**
     * Set up initial frequency commit with retry logic
     * @private
     */
    _setupInitialFrequencyCommit: function() {
        var self = this;
        var attemptCommit = function(attemptsLeft) {
            // Read from single source of truth (Hz), convert to kHz for waterfall
            var freqHz = parseFloat(self.$frequency.val()) || 0;
            if (freqHz > 0) {
                self.commitFrequency();
            } else if (attemptsLeft > 0) {
                setTimeout(function() {
                    attemptCommit(attemptsLeft - 1);
                }, DX_WATERFALL_CONSTANTS.DEBOUNCE.FREQUENCY_COMMIT_RETRY_MS * (6 - attemptsLeft));
            }
        };
        attemptCommit(5);
    },

    /**
     * Set up CAT frequency wait timeout
     * @private
     */
    // Check if current frequency input differs from last committed value
    // Returns true if frequency has changed, false if same
    hasFrequencyChanged: function() {
        // Safety check: return false if waterfall is not initialized
        if (!this.$frequency) {
            return false;
        }

        var currentHz = parseFloat(this.$frequency.val()) || 0;

        // If we don't have a last committed value, consider it changed
        if (this.lastValidCommittedFreqHz === null) {
            return true;
        }

        // Compare frequencies with 1 Hz tolerance to account for floating point errors
        var tolerance = 1; // 1 Hz
        return Math.abs(currentHz - this.lastValidCommittedFreqHz) > tolerance;
    },

    // Commit the current frequency value (called on blur or Enter key)
    // This prevents the waterfall from shifting while the user is typing
    commitFrequency: function() {
        // This function is primarily for the fallback case when CAT is not available
        // When CAT is active, the waterfall reads from window.catState.frequency

        // Safety check: return early if waterfall is not initialized (destroyed or not yet ready)
        if (!this.$frequency) {
            return;
        }

        // Read from single source of truth (Hz)
        var freqHz = parseFloat(this.$frequency.val()) || 0;

        // If this is a valid frequency, save it as the last valid committed frequency
        // (used as fallback when CAT not available)
        if (freqHz > 0) {
            this.lastValidCommittedFreqHz = freqHz;
            this.committedFrequencyKHz = freqHz / 1000; // Convert to kHz for waterfall display

            // In offline mode, populate catState with form values to act as "virtual CAT"
            if (typeof isCATAvailable === 'function' && !isCATAvailable()) {
                // Initialize catState if it doesn't exist
                if (typeof window.catState === 'undefined' || window.catState === null) {
                    window.catState = {};
                }

                // Update frequency in catState (already in Hz)
                window.catState.frequency = freqHz;
                window.catState.lastUpdate = Date.now();

                // Update mode from form
                if (this.$modeSelect && this.$modeSelect.val()) {
                    window.catState.mode = this.$modeSelect.val();
                }

                DX_WATERFALL_UTILS.log.debug('[DX Waterfall] Offline mode - virtual CAT updated: freq=' + freqHz + ' Hz, mode=' + window.catState.mode);

                // Update relevant spots for the new frequency
                if (this.collectAllBandSpots) {
                    this.collectAllBandSpots(true);
                }
            }

            // Manual frequency change triggers initial fetch if not done yet
            if (!this.initialFetchDone) {
                this.refresh();
            }
        }

        // Force a refresh to update the display (mainly for non-CAT usage)
        if (this.canvas && this.ctx) {
            this.refresh();
        }

        // Update zoom menu to reflect new arrow states based on frequency position
        if (this.zoomMenuDiv) {
            this.updateZoomMenu();
        }
    },

    // Get cached middle frequency to avoid repeated DOM access and parsing
    // Always returns frequency in kHz for internal calculations
    getCachedMiddleFreq: function() {
        // PRIORITY 1: Use CAT state if available (radio controls waterfall)
        // The waterfall should display what the radio is tuned to, not what's in the form
        if (window.catState && window.catState.frequency && window.catState.frequency > 0) {
            var freqHz = window.catState.frequency;
            var freqKhz = freqHz / 1000;

            // Cache the CAT frequency
            this.cache.middleFreq = freqKhz;

            // Update split operation state and get display configuration
            this.updateSplitOperationState();

            return this.displayConfig.centerFrequency;
        }

        // FALLBACK: Use committed frequency values (only updated on blur/Enter) to prevent shifting while typing
        // This is used when CAT is not available (no radio connected)
        // Strategy:
        // 1. If we have a valid committed frequency from this session, use last VALID commit
        // 2. Otherwise use real-time values (initial load before any commits)

        var hasValidCommit = this.lastValidCommittedFreqHz !== null;

        var currentFreqHz;

        if (hasValidCommit) {
            // After first valid commit, always use the LAST VALID committed values
            // This keeps the waterfall stable even when user deletes and starts typing
            currentFreqHz = this.lastValidCommittedFreqHz;
        } else {
            // Before first valid commit (initial load), use real-time values from single source
            currentFreqHz = parseFloat(this.$frequency.val()) || 0;
            // If frequency is still 0, trigger set_new_qrg to populate from band/mode defaults
            if (currentFreqHz <= 0 && typeof set_new_qrg === 'function') {
                // Trigger async frequency population but return 0 for now
                // Next render cycle will have the correct frequency
                set_new_qrg();
                currentFreqHz = 0; // Will be updated on next call
            }
        }

        // Invalidate cache if frequency changes
        if (this.lastValidCommittedFreqHz !== currentFreqHz) {
            this.lastValidCommittedFreqHz = currentFreqHz;

            // Convert to kHz for waterfall display
            this.cache.middleFreq = currentFreqHz / 1000;
        }

        // Update split operation state and get display configuration
        this.updateSplitOperationState();

        return this.displayConfig.centerFrequency;
    },

    // Update split operation state and configure display parameters
    updateSplitOperationState: function() {
        // Prefer CAT state for frequency_rx (radio controls split operation)
        var frequencyRxValue = null;

        if (window.catState && window.catState.frequency_rx) {
            frequencyRxValue = window.catState.frequency_rx;
        } else if (DX_WATERFALL_UTILS.fieldMapping.hasOptionalField('frequency_rx')) {
            // Fallback to form field if CAT not available
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
    /**
     * Invalidate frequency cache and handle CAT frequency updates
     * Manages state transitions for frequency changes
     * @param {number} frequencyKHz - New frequency in kHz
     * @param {boolean} isImmediateUpdate - True if spot click (immediate update)
     */
    invalidateFrequencyCache: function(frequencyKHz, isImmediateUpdate) {
        // Safety check: Don't run if waterfall is not initialized
        if (!this.canvas) {
            return;
        }

        // Don't invalidate cache if user is actively editing frequency
        if (this.userEditingFrequency) {
            return;
        }

        // If this is an immediate update from clicking a spot, update frequency NOW
        if (isImmediateUpdate && frequencyKHz) {
            this.cache.middleFreq = frequencyKHz;

            // ========================================
            // TRANSITION TO TUNING STATE (only if not already tuning)
            // ========================================
            if (isCATAvailable() && DXWaterfallStateMachine.getState() !== DX_WATERFALL_CONSTANTS.STATES.TUNING) {
                DXWaterfallStateMachine.setState(DX_WATERFALL_CONSTANTS.STATES.TUNING, {
                    targetFrequency: frequencyKHz,
                    reason: 'spot_click'
                });
            }

            return; // CAT will confirm later
        }

        // Check if we're waiting for a specific target frequency
        if (this.targetFrequencyHz) {
            // Waiting for target frequency - skip normal processing, CAT will confirm later
            return; // Exit early
        }

        // ========================================
        // TRANSITION TO READY STATE
        // ========================================
        // Frequency is now confirmed by CAT system
        var currentState = DXWaterfallStateMachine.getState();
        if (currentState === DX_WATERFALL_CONSTANTS.STATES.TUNING) {
            DXWaterfallStateMachine.setState(DX_WATERFALL_CONSTANTS.STATES.READY);
        }

        // Force immediate cache refresh and visual update
        this.lastFrequencyRefreshTime = 0;

        // Trigger refresh
        if (this.canvas && this.ctx) {
            this.refresh();
        }
    },

    // Periodically refresh frequency cache to ensure display stays current
    refreshFrequencyCache: function() {
        // Safety check: Don't run if waterfall is not initialized
        if (!this.$frequency) {
            return;
        }

        // Don't interfere during waterfall-initiated frequency changes or when user is editing
        var currentState = DXWaterfallStateMachine.getState();
        if (currentState === DX_WATERFALL_CONSTANTS.STATES.TUNING || this.userEditingFrequency) {
            return;
        }

        // Throttle to prevent excessive calls (max once per 200ms)
        var currentTime = Date.now();
        if (currentTime - this.lastFrequencyRefreshTime < DX_WATERFALL_CONSTANTS.DEBOUNCE.FREQUENCY_CACHE_REFRESH_MS) {
            return;
        }
        this.lastFrequencyRefreshTime = currentTime;

        // Get current DOM frequency (single source of truth in Hz)
        var currentInput = this.$frequency.val();
        if (!currentInput || currentInput === '') {
            return;
        }

        var freqHz = parseFloat(currentInput) || 0;
        if (freqHz <= 0) {
            return;
        }

        // Convert to kHz for waterfall display
        var currentFreqKhz = freqHz / 1000;

        // If cache is outdated, refresh it (but only if not during waterfall operations)
        if (!this.cache.middleFreq || Math.abs(currentFreqKhz - this.cache.middleFreq) > 0.1) {
            // Clear all frequency-related cache to ensure fresh read
            this.cache.middleFreq = null;
            this.lastMarkerFreq = undefined;

            // Directly set the new frequency from DOM calculation
            this.cache.middleFreq = currentFreqKhz;

            // Also update committed frequency values to prevent getCachedMiddleFreq() conflicts
            // This ensures that getCachedMiddleFreq() will use the updated frequency instead of old committed values
            this.lastValidCommittedFreqHz = freqHz;

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
        DX_WATERFALL_UTILS.log.debug('[Cookie] Saving font size to cookie: ' + this.labelSizeLevel);
        setCookie(
            DX_WATERFALL_CONSTANTS.COOKIE.NAME_FONT_SIZE,
            this.labelSizeLevel.toString(),
            DX_WATERFALL_CONSTANTS.COOKIE.EXPIRY_DAYS
        );
        DX_WATERFALL_UTILS.log.debug('[Cookie] Font size saved');
    },

    /**
     * Load font size from cookie
     * @returns {number|null} Font size level (0-4) or null if not found
     */
    loadFontSizeFromCookie: function() {
        var cookieValue = getCookie(DX_WATERFALL_CONSTANTS.COOKIE.NAME_FONT_SIZE);
        DX_WATERFALL_UTILS.log.debug('[Cookie] Loading font size from cookie, raw value: ' + cookieValue);
        if (cookieValue !== null) {
            var level = parseInt(cookieValue, 10);
            if (!isNaN(level) && level >= 0 && level <= 4) {
                DX_WATERFALL_UTILS.log.debug('[Cookie] Valid font size loaded: ' + level);
                return level;
            }
        }
        DX_WATERFALL_UTILS.log.debug('[Cookie] No valid font size in cookie, using default');
        return null;
    },

    /**
     * Save mode filters to cookie
     */
    saveModeFiltersToCookie: function() {
        setCookie(
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
        var cookieValue = getCookie(DX_WATERFALL_CONSTANTS.COOKIE.NAME_MODE_FILTERS);
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
        DX_WATERFALL_UTILS.log.debug('[Settings] Loading settings from cookies...');

        // Load font size
        var savedFontSize = this.loadFontSizeFromCookie();
        if (savedFontSize !== null) {
            this.labelSizeLevel = savedFontSize;
            DX_WATERFALL_UTILS.log.debug('[Settings] Font size level set to: ' + this.labelSizeLevel);
        } else {
            DX_WATERFALL_UTILS.log.debug('[Settings] Using default font size level: ' + this.labelSizeLevel);
        }

        // Load mode filters
        var savedModeFilters = this.loadModeFiltersFromCookie();
        if (savedModeFilters) {
            this.modeFilters.phone = savedModeFilters.phone;
            this.modeFilters.cw = savedModeFilters.cw;
            this.modeFilters.digi = savedModeFilters.digi;
            DX_WATERFALL_UTILS.log.debug('[Settings] Mode filters loaded: ' + JSON.stringify(this.modeFilters));
        } else {
            DX_WATERFALL_UTILS.log.debug('[Settings] Using default mode filters');
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
        // Don't show tooltips while not in READY state or if no spots
        var currentState = DXWaterfallStateMachine.getState();
        if (currentState !== DX_WATERFALL_CONSTANTS.STATES.READY || !this.dxSpots || this.dxSpots.length === 0) {
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
        var filteredCount = 0;
        var outOfBoundsCount = 0;
        var centerSkipCount = 0;

        for (var i = 0; i < this.dxSpots.length; i++) {
            var spot = this.dxSpots[i];
            var spotFreq = parseFloat(spot.frequency);

            if (spotFreq && spot.spotted && spot.mode) {
                // Apply mode filter
                if (!this.spotMatchesModeFilter(spot)) {
                    filteredCount++;
                    continue;
                }

                var freqOffset = spotFreq - middleFreq;
                var x = centerX + (freqOffset * pixelsPerKHz);

                // Only include if within canvas bounds
                if (x >= 0 && x <= this.canvas.width) {
                    // Skip spots at center frequency (within tolerance)
                    if (centerFrequency && Math.abs(spotFreq - centerFrequency) <= centerFrequencyTolerance) {
                        centerSkipCount++;
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
                } else {
                    outOfBoundsCount++;
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

    // Load band plans from database
    loadBandPlans: function() {
        var self = this;

        if (this.bandPlans !== null) {
            return;
        }

        this.bandPlans = 'loading';

        var baseUrl = (typeof base_url !== 'undefined') ? base_url : '';
        if (!baseUrl) {
            this.bandPlans = {};
            return;
        }

        // Determine region from current continent
        var region = continentToRegion(this.currentContinent);

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
            var band = frequencyToBand(centerFreq); // Use global function from radiohelpers.js

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

    // Get band limits for current band and region
    getBandLimits: function() {
        // Determine which band to use for limits
        // Use currentSpotBand if available (the band we fetched spots for)
        // Otherwise use frequency's band with 20kHz margin for tolerance
        var bandToUse;
        if (this.currentSpotBand && this.currentSpotBand !== 'All') {
            bandToUse = this.currentSpotBand;
        } else {
            var middleFreq = this.getCachedMiddleFreq();
            // Use 20kHz margin for band detection (extends band edges)
            bandToUse = frequencyToBandKhz(middleFreq, 20);
            if (!bandToUse) {
                return null; // Out of band and no spots loaded
            }
        }

        var currentRegion = continentToRegion(this.currentContinent);
        var regionKey = 'region' + currentRegion;

        // Check if we need to update cache
        if (this.bandLimitsCache &&
            this.bandLimitsCache.band === bandToUse &&
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
            if (this.bandPlans[regionKey][bandToUse]) {
                var bandData = this.bandPlans[regionKey][bandToUse];
                limits = {
                    start_khz: bandData.start_hz / 1000, // Convert Hz to kHz
                    end_khz: bandData.end_hz / 1000       // Convert Hz to kHz
                };
            }
        }

        // Cache the result
        this.bandLimitsCache = {
            band: bandToUse,
            region: currentRegion,
            limits: limits
        };

        return limits;
    },

    // ========================================
    // CANVAS DRAWING AND RENDERING FUNCTIONS
    // ========================================

    // Draw band mode indicators (colored lines below ruler showing CW/DIGI/PHONE segments)
    drawBandModeIndicators: function() {
        // Use the band we have spots for, not the form selector
        // This prevents drawing wrong band mode indicators when form is changed manually
        var currentBand = this.currentSpotBand || this.getCurrentBand();
        var currentRegion = continentToRegion(this.currentContinent);
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

        // Determine which band to draw
        // Use currentSpotBand if available (the band we fetched spots for)
        // Otherwise use frequency's band with 20kHz margin for tolerance
        var bandToDraw;
        if (this.currentSpotBand && this.currentSpotBand !== 'All') {
            bandToDraw = this.currentSpotBand;
        } else {
            // Use 20kHz margin for band detection (extends band edges)
            bandToDraw = frequencyToBandKhz(middleFreq, 20);
            if (!bandToDraw) {
                return; // Out of band and no spots loaded, don't draw
            }
        }

        // Get band edges for the band to draw
        var bandEdges = this.bandEdgesData[regionKey][bandToDraw];
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
            this.ctx.fillText(decodeHtml(lang_dxwaterfall_out_of_bandplan), textCenterX, textCenterY);
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
    /**
     * Fetch DX spots from server
     * Transitions to FETCHING_SPOTS state during AJAX request
     * @param {boolean} immediate - If true, fetch immediately. If false, debounce the request
     * @param {boolean} userInitiated - True if user clicked refresh button
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
            DX_WATERFALL_UTILS.log.debug('[DX Waterfall] FETCH SPOTS: Debouncing for ' + this.fetchDebounceMs + 'ms');
            this.fetchDebounceTimer = setTimeout(function() {
                self.fetchDebounceTimer = null;
                self.fetchDxSpots(true, userInitiated); // Pass userInitiated through
            }, this.fetchDebounceMs);
            return;
        }

        // Set userInitiatedFetch flag
        this.userInitiatedFetch = userInitiated === true;

        // Calculate band from current frequency with 20kHz margin for tolerance
        var currentFreqKhz = this.getCachedMiddleFreq();
        var band = null;

        if (currentFreqKhz > 0) {
            band = frequencyToBandKhz(currentFreqKhz, 20); // 20kHz margin
        }

        // If band is 'All' (out of band), handle based on state
        if (!band || band === 'All' || band === '' || band.toLowerCase() === 'select') {
            var currentState = DXWaterfallStateMachine.getState();

            // SPECIAL CASE: During initialization, default to 20m to allow waterfall to load
            // This prevents timeout errors on initial load when frequency is out of band
            if (currentState === DX_WATERFALL_CONSTANTS.STATES.INITIALIZING) {
                DX_WATERFALL_UTILS.log.debug('[DX Waterfall] FETCH SPOTS: Out of band during initialization, defaulting to 20m');
                band = '20m';
                // Continue with fetch below
            } else {
                // Normal operation: skip fetch when out of band
                DX_WATERFALL_UTILS.log.debug('[DX Waterfall] FETCH SPOTS: Out of band, skipping spot fetch');
                // Transition to ready if we were fetching
                if (currentState === DX_WATERFALL_CONSTANTS.STATES.FETCHING_SPOTS) {
                    this.stateMachine_setState(DX_WATERFALL_CONSTANTS.STATES.READY);
                }
                return;
            }
        }

        var mode = "All"; // Fetch all modes
        var age = 60; // minutes
        var de = this.currentContinent; // Use current continent (may have been cycled)

        // Check if dxcluster_default_maxage is defined
        if (typeof dxcluster_default_maxage !== "undefined" && dxcluster_default_maxage != null) {
            age = dxcluster_default_maxage;
        }

        // Store current settings
        this.currentMaxAge = age;

        // Check if we're already fetching for a DIFFERENT band - abort and start new fetch
        // Otherwise block concurrent requests for the same band
        var currentState = DXWaterfallStateMachine.getState();
        if (currentState === DX_WATERFALL_CONSTANTS.STATES.FETCHING_SPOTS) {
            // If fetching for a different band, abort and continue with new fetch
            if (this.lastFetchBand !== band) {
                DX_WATERFALL_UTILS.log.debug('[DX Waterfall] FETCH SPOTS: Band changed during fetch (' + this.lastFetchBand + ' → ' + band + '), aborting current request');
                if (this.pendingFetchRequest) {
                    this.pendingFetchRequest.abort();
                    this.pendingFetchRequest = null;
                }
                // Continue with new fetch below
            } else {
                // Same band - skip to avoid duplicate requests
                DX_WATERFALL_UTILS.log.debug('[DX Waterfall] FETCH SPOTS: Already fetching same band, skipping request');
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
                DX_WATERFALL_UTILS.log.debug('[DX Waterfall] FETCH SPOTS: Recently fetched same data, skipping');
                return;
            }
        }

        // Check if base_url is defined, if not use a default or skip
        var baseUrl = (typeof base_url !== 'undefined') ? base_url : '';
        if (!baseUrl) {
            DX_WATERFALL_UTILS.log.error('[DX Waterfall] FETCH SPOTS: base_url not defined');
            return;
        }

        var ajaxUrl = baseUrl + 'index.php/dxcluster/spots/' + band + '/' + age + '/' + de + '/' + mode;

        // ========================================
        // TRANSITION TO FETCHING_SPOTS STATE
        // ========================================
        DXWaterfallStateMachine.setState(DX_WATERFALL_CONSTANTS.STATES.FETCHING_SPOTS, {
            band: band,
            continent: de,
            age: age,
            userInitiated: userInitiated
        });

        // Force immediate refresh to show waiting message (if user-initiated)
        if (userInitiated && this.canvas && this.ctx) {
            this.refresh();
        }

        // Note: State machine handles timeout via setStateTimeout in _onStateEnter

        // Abort any pending fetch request
        if (this.pendingFetchRequest) {
            this.pendingFetchRequest.abort();
            this.pendingFetchRequest = null;
        }

        // Store the AJAX request so we can abort it if needed
        this.pendingFetchRequest = $.ajax({
            url: ajaxUrl,
            type: 'GET',
            dataType: 'json',
            timeout: DX_WATERFALL_CONSTANTS.AJAX.TIMEOUT_MS,
			cache: false,
            success: function(data) {
                // Clear the pending request reference
                self.pendingFetchRequest = null;

                // Check if band has changed since this fetch was initiated
                // Compare against currentSpotBand (what we're displaying) not form selector
                var currentDisplayBand = self.currentSpotBand || band;
                if (band !== currentDisplayBand) {
                    // Band changed - this data is stale, fetch for current band
                    DX_WATERFALL_UTILS.log.debug('[DX Waterfall] FETCH SUCCESS: Band changed during fetch, refetching');
                    self.userInitiatedFetch = false; // Clear user-initiated flag for stale data

                    // Trigger immediate fetch for the correct (current) band
                    self.fetchDxSpots(true);
                    return;
                }

                if (data && !data.error) {
                    // Clean up spotter callsigns (remove -# suffix)
                    // Park references are already provided by server in dxcc_spotted object
                    for (var i = 0; i < data.length; i++) {
                        if (data[i].spotter) {
                            data[i].spotter = data[i].spotter.replace(/-#$/, '');
                        }
                    }

                    self.dxSpots = data;
                    self.totalSpotsCount = data.length;
                    self.lastUpdateTime = new Date(); // Record update time

                    // Track fetch parameters to prevent duplicate fetches
                    self.lastFetchBand = band;
                    self.lastFetchContinent = de;
                    self.lastFetchAge = age;

                    // Track which band we currently have spots for
                    self.currentSpotBand = band;

                    // Invalidate caches when spots are updated
                    self.cache.visibleSpots = null;
                    self.cache.visibleSpotsParams = null;
                    self.relevantSpots = [];

                    self.collectAllBandSpots(true); // Update band spot collection for navigation (force after data fetch)
                    self.collectSmartHunterSpots(); // Update smart hunter spots collection

                    DX_WATERFALL_UTILS.log.debug('[DX Waterfall] FETCH SUCCESS: Received ' + data.length + ' spots for ' + band);
                } else {
                    // No spots or error in response (e.g., {"error": "not found"})
                    self.dxSpots = [];
                    self.totalSpotsCount = 0;
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

                    DX_WATERFALL_UTILS.log.debug('[DX Waterfall] FETCH SUCCESS: No spots for ' + band);
                }

                // Clear user-initiated flag
                self.userInitiatedFetch = false;

                // Clear operation timer to prevent stale timer display
                self.operationStartTime = null;

                // ========================================
                // TRANSITION BACK TO READY STATE
                // ========================================
                DXWaterfallStateMachine.setState(DX_WATERFALL_CONSTANTS.STATES.READY);

                // Force menu update after data fetch
                self.updateZoomMenu(true);

                // Trigger refresh to display new data
                self.refresh();
            },
            error: function(xhr, status, error) {
                // Clear the pending request reference
                self.pendingFetchRequest = null;

                // Check if this was an intentional abort (e.g., during waterfall disable)
                if (status === 'abort') {
                    DX_WATERFALL_UTILS.log.debug('[DX Waterfall] FETCH ABORTED: Request was intentionally cancelled');
                    // Don't transition to ERROR state - this is expected
                    return;
                }

                // AJAX request failed
                DX_WATERFALL_UTILS.log.error('[DX Waterfall] FETCH ERROR: status=' + status + ', error=' + error + ', readyState=' + xhr.readyState);

                // Clear user-initiated flag
                self.userInitiatedFetch = false;

                // Clear data
                self.dxSpots = [];
                self.totalSpotsCount = 0;

                // Invalidate caches on error
                self.cache.visibleSpots = null;
                self.cache.visibleSpotsParams = null;
                self.relevantSpots = [];

                self.allBandSpots = []; // Clear band spots
                self.currentBandSpotIndex = 0;
                self.smartHunterSpots = []; // Clear smart hunter spots
                self.currentSmartHunterIndex = 0;

                // ========================================
                // TRANSITION TO ERROR STATE
                // ========================================
                DXWaterfallStateMachine.setState(DX_WATERFALL_CONSTANTS.STATES.ERROR, {
                    message: 'Failed to fetch spots: ' + error
                });

                // Populate menu even after error (so user can still interact)
                self.updateZoomMenu();
            }
        });
    },

    // Get current band calculated from frequency (single source of truth)
    getCurrentBand: function() {
        var freqHz = 0;

        // When CAT is operational, use CAT frequency
        if (window.catState && window.catState.frequency && window.catState.frequency > 0) {
            freqHz = window.catState.frequency;
        } else if (this.$frequency) {
            // When offline, read directly from hidden frequency field (single source of truth)
            freqHz = parseFloat(this.$frequency.val()) || 0;
        }

        if (freqHz > 0) {
            var freqKhz = freqHz / 1000;
            var band = frequencyToBandKhz(freqKhz);
            if (band && band !== '' && band.toLowerCase() !== 'select') {
                return band;
            }
        }
        // Fallback to 20m if frequency not available or out of band
        return '20m';
    },

    // Get current mode from form or default to All
    getCurrentMode: function() {
        // Prefer CAT state if available (radio controls mode)
        if (window.catState && window.catState.mode) {
            return window.catState.mode;
        }

        // Fallback to form field if CAT not available
        // Safety check: return default if not initialized
        if (!this.$modeSelect) {
            return 'All';
        }
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
	if ((dxwaterfall_enable ?? 'Y') === 'E') { return; }
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
	if ((dxwaterfall_enable ?? 'Y') === 'E') { return; }
        try {
            // Generate cached noise only if needed (dimensions changed or first time)
            this.generateCachedNoise();

            // Alternate between noise patterns for animation effect
            var noiseToUse = (this.cache.currentNoiseFrame === 0) ? this.cache.noise1 : this.cache.noise2;
            this.ctx.putImageData(noiseToUse, 0, 0);

            // Switch to next frame for next refresh
            this.cache.currentNoiseFrame = 1 - this.cache.currentNoiseFrame; // Toggle between 0 and 1
        } catch (e) {
            DX_WATERFALL_UTILS.log.error('[DX Waterfall] Error drawing static noise:', e);
            // Fall back to simple black background
            this.ctx.fillStyle = DX_WATERFALL_CONSTANTS.COLORS.BLACK;
            this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
        }
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

        try {
            // Create image object if it doesn't exist or reuse existing one
            if (!this.wavelogLogoImage) {
                this.wavelogLogoImage = new Image();
                this.wavelogLogoImage.onload = function() {
                    self.wavelogLogoImage.loaded = true;
                };
                this.wavelogLogoImage.onerror = function() {
                    DX_WATERFALL_UTILS.log.error('Failed to load Wavelog logo');
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
        } catch (e) {
            DX_WATERFALL_UTILS.log.error('[DX Waterfall] Error drawing logo:', e);
            // Silently fail - logo is non-critical
        }
    },    // Display waiting message with black overlay and spinner
    displayWaitingMessage: function(customMessage) {
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

        // Use custom message if provided, otherwise default to downloading data
        var message = customMessage || lang_dxwaterfall_downloading_data;

        // Draw waiting message
        DX_WATERFALL_UTILS.drawing.drawCenteredText(this.ctx, message, centerX, textY, 'WAITING_MESSAGE', 'MESSAGE_TEXT_WHITE');

        // Reset opacity
        this.ctx.globalAlpha = 1.0;
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
        var modeCategory = getModeCategory(currentMode) || 'other';
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
     * Get bandwidth parameters for a given mode and frequency (WATERFALL VISUALIZATION SPECIFIC)
     * Returns the signal bandwidth and frequency offset for proper signal visualization
     * Uses getSignalBandwidth() from radiohelpers.js for bandwidth, adds offset for sideband drawing
     *
     * @param {string} mode - The transmission mode (e.g., 'LSB', 'USB', 'FT8', 'CW')
     * @param {number} frequency - Frequency in kHz
     * @returns {{bandwidth: number, offset: number}} Object with bandwidth (in kHz) and offset (in kHz)
     *          - bandwidth: Width of the signal in kHz
     *          - offset: Frequency offset from carrier (negative for LSB, positive for USB, 0 for centered)
     */
    getBandwidthParams: function(mode, frequency) {
        var freq = parseFloat(frequency) || 0;

        // Get bandwidth from global function (handles all modes consistently)
        var bandwidth = getSignalBandwidth(mode);

        // Phone modes with sideband behavior need offset calculation
        // Use isPhoneMode() to check if mode is phone/voice (more robust than string comparison)
        if (isPhoneMode(mode)) {
            var modeUpper = mode.toUpperCase();

            // AM and FM span both sides of carrier (like CW) - centered with no offset
            if (modeUpper === 'AM' || modeUpper === 'FM' || modeUpper === 'SAM' ||
                modeUpper === 'DSB' || modeUpper === 'A3E') {
                return { bandwidth: bandwidth, offset: 0 };
            }

            // If mode explicitly specifies LSB or USB, use that
            if (modeUpper === 'LSB') {
                return { bandwidth: bandwidth, offset: -bandwidth / 2 };
            } else if (modeUpper === 'USB') {
                return { bandwidth: bandwidth, offset: bandwidth / 2 };
            }

            // For generic phone/SSB mode, determine based on frequency
            // This handles cases where the mode is just "Phone" or "SSB" without explicit sideband
            var ssbMode = determineSSBMode(freq);
            if (ssbMode === 'LSB') {
                return { bandwidth: bandwidth, offset: -bandwidth / 2 };
            } else { // USB
                return { bandwidth: bandwidth, offset: bandwidth / 2 };
            }
        }

        // All other modes (CW, digital, etc.) are centered (offset = 0)
        return { bandwidth: bandwidth, offset: 0 };
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

                // Use backend-provided mode and submode (already classified with priority logic)
                // Backend priority: POTA/SOTA > message keywords > frequency-based
                var classifiedMode = spot.mode || 'phone'; // 'phone', 'cw', or 'digi'
                var modeForBandwidth = (spot.submode || spot.mode || 'phone').toLowerCase();

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
            sidebandType = determineSSBMode(freq).toLowerCase();
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

        // Clear position data from ALL spots before drawing
        // This ensures filtered-out spots don't remain hoverable/clickable
        for (var i = 0; i < this.dxSpots.length; i++) {
            delete this.dxSpots[i].x;
            delete this.dxSpots[i].y;
            delete this.dxSpots[i].labelWidth;
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
            if (spots.length === 0) return;			// Note: Label widths are already pre-calculated in getVisibleSpots() and cached
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
        var exactMatchTolerance = 0.01; // 10 Hz - very tight tolerance for exact match

        // First pass: check if there are any spots at our EXACT frequency (within 10 Hz)
        for (var i = 0; i < this.relevantSpots.length; i++) {
            var spot = this.relevantSpots[i];
            var spotFreq = parseFloat(spot.frequency);
            if (Math.abs(spotFreq - centerFreq) <= exactMatchTolerance) {
                spotsAtSameFreq.push({
                    spot: spot,
                    index: i
                });
            }
        }

        // If no exact matches, fall back to broader tolerance for nearby spots
        if (spotsAtSameFreq.length === 0) {
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

                // Draw border using continent status color
                ctx.strokeStyle = colors.borderColor;
                ctx.lineWidth = 1;
                ctx.strokeRect(rectX, rectY, rectWidth, rectHeight);

                // Draw additional black border for selected spot
                if (isSelected) {
                    ctx.strokeStyle = '#000000';
                    ctx.lineWidth = 1;
                    ctx.strokeRect(rectX - 1, rectY - 1, rectWidth + 2, rectHeight + 2);
                }

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
        // Throttle rapid refresh calls to prevent excessive rendering
        // This prevents excessive rendering when multiple events fire simultaneously
        var currentTime = Date.now();
        var minRefreshInterval = 50; // Minimum 50ms between actual refreshes (~20 FPS max)

        // If we already have a pending refresh, skip this call
        if (this.refreshPending) {
            return;
        }

        // If not enough time has passed since last refresh, schedule for next frame
        var timeSinceLastRefresh = currentTime - this.lastRefreshTime;
        if (timeSinceLastRefresh < minRefreshInterval) {
            this.refreshPending = true;
            var self = this;
            var remainingTime = minRefreshInterval - timeSinceLastRefresh;
            setTimeout(function() {
                self.refreshPending = false;
                self._performRefresh();
            }, remainingTime);
            return;
        }

        // Execute refresh immediately
        this._performRefresh();
    },

    /**
     * Internal refresh implementation (called by throttled refresh())
     * Uses state machine for clear, maintainable rendering logic
     *
     * MAIN RENDERING LOOP - State Machine Based
     * Each state has its own render method for clarity and maintainability
     *
     * @private
     */
    _performRefresh: function() {
        // Update last refresh time
        this.lastRefreshTime = Date.now();

        // Ensure canvas is initialized - don't auto-init, just return
        if (!this.canvas) {
            return; // Canvas not available, user must click power button
        }

        // Check if canvas is visible in DOM
        if (!this.canvas.offsetParent && this.canvas.style.display !== 'none') {
            return; // Canvas not visible or removed from DOM
        }

        // ========================================
        // AUTO-FETCH LOGIC (Band Change Detection)
        // ========================================
        // Check if band has changed via CAT - only when in READY state
        // This triggers automatic spot fetching when radio changes bands
        var currentState = DXWaterfallStateMachine.getState();
        var STATES = DX_WATERFALL_CONSTANTS.STATES;

        if (currentState === STATES.READY) {
            var currentFreqKhz = this.getCachedMiddleFreq();
            var calculatedBand = null;

            if (currentFreqKhz > 0) {
                calculatedBand = frequencyToBandKhz(currentFreqKhz);
            }

            // Check if we need to fetch spots for a different band
            if (calculatedBand && calculatedBand !== '' && calculatedBand.toLowerCase() !== 'select') {
                if (!this.currentSpotBand || calculatedBand !== this.currentSpotBand) {
                    // Band has changed! Fetch new spots
                    DX_WATERFALL_UTILS.log.debug('[DX Waterfall] Band changed: ' + this.currentSpotBand + ' → ' + calculatedBand);

                    // Update band immediately to prevent infinite loop
                    this.currentSpotBand = calculatedBand;

                    // Invalidate band-related caches
                    this.bandLimitsCache = null;
                    this.cachedBandForEdges = calculatedBand;

                    // Trigger spot fetch - mark as user-initiated to show loading message
                    // Band changes (via CAT or manual) are significant events that warrant visual feedback
                    this.fetchDxSpots(true, true);
                }
            }
        }

        // ========================================
        // STATE-BASED RENDERING
        // ========================================
        // Route to appropriate render method based on current state
        // Each state has clear, isolated rendering logic

        switch (currentState) {
            case STATES.DISABLED:
                // Waterfall not initialized - nothing to render
                this._renderDisabled();
                break;

            case STATES.INITIALIZING:
                // Canvas setup in progress - show loading
                this._renderInitializing();
                break;

            case STATES.FETCHING_SPOTS:
                // AJAX request in progress - show loading
                this._renderFetchingSpots();
                break;

            case STATES.TUNING:
                // Radio is tuning - show tuning message
                this._renderTuning();
                break;

            case STATES.READY:
                // Normal operation - render full waterfall
                this._renderReady();
                break;

            case STATES.ERROR:
                // Error occurred - show error message
                this._renderError();
                break;

            case STATES.DEINITIALIZING:
                // Cleanup in progress - show shutdown message
                this._renderDeinitializing();
                break;

            default:
                // Unknown state - log error and show error state
                DX_WATERFALL_UTILS.log.error('[DX Waterfall] Unknown state: ' + currentState);
                DXWaterfallStateMachine.setState(STATES.ERROR, {
                    message: 'Unknown state: ' + currentState
                });
                this._renderError();
                break;
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
        var exactMatchSpots = []; // Spots at our exact frequency
        var exactMatchTolerance = 0.01; // 10 Hz - very tight tolerance for exact match

        // Get current bandwidth parameters for signal width detection (with caching)
        var bandwidthParams = this.getCachedBandwidthParams(currentMode, middleFreq);
        var signalBandwidth = bandwidthParams.bandwidth; // kHz

        // Determine detection range based on mode
        var detectionRange = 0;

        if (currentMode === 'lsb' || currentMode === 'usb' || currentMode === 'phone') {
            // SSB modes: Use symmetric ±1 kHz detection range
            // This allows spots within ±1 kHz to be detected regardless of sideband
            detectionRange = 1.0; // ±1 kHz symmetric range for SSB
        } else if (currentMode === 'cw') {
            detectionRange = SIGNAL_BANDWIDTHS.CW;
        } else {
            // Other modes (digital, etc.) - centered with half bandwidth
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

                    var spotObj = DX_WATERFALL_UTILS.spots.createSpotObject(spot, {
                        includeSpotter: true,
                        includeTimestamp: true,
                        includeMessage: true,
                        includeOffsets: true,
                        middleFreq: middleFreq,
                        includeWorkStatus: true
                    });

                    relevantSpots.push(spotObj);

                    // Check if this is an exact match (within 10 Hz)
                    if (absOffset <= exactMatchTolerance) {
                        exactMatchSpots.push(spotObj);
                    }
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
        exactMatchSpots.sort(DX_WATERFALL_UTILS.sorting.byAbsOffset);

        // IMPORTANT: If we have exact matches, use ONLY those for relevantSpots
        // This ensures cycling only goes through spots at our exact frequency
        if (exactMatchSpots.length > 0) {
            this.relevantSpots = exactMatchSpots;
        } else {
            this.relevantSpots = relevantSpots;
        }

        // Ensure current index is valid
        if (this.currentSpotIndex >= this.relevantSpots.length) {
            this.currentSpotIndex = 0;
        }

        // If there's a pending spot selection (from clicking a stacked spot), restore the correct index
        if (this.pendingSpotSelection) {
            var foundIndex = -1;
            for (var i = 0; i < this.relevantSpots.length; i++) {
                if (this.relevantSpots[i].callsign === this.pendingSpotSelection.callsign &&
                    Math.abs(this.relevantSpots[i].frequency - this.pendingSpotSelection.frequency) < 0.01) {
                    foundIndex = i;
                    break;
                }
            }
            if (foundIndex >= 0) {
                this.currentSpotIndex = foundIndex;
            }
            // Clear the pending selection after restoring
            this.pendingSpotSelection = null;
        }

        // Return the currently selected spot
        var selectedSpot = this.relevantSpots[this.currentSpotIndex];
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

        // Don't show spot info unless in READY state
        var currentState = DXWaterfallStateMachine.getState();
        if (currentState !== DX_WATERFALL_CONSTANTS.STATES.READY) {
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

            // Use backend-provided mode and submode directly (backend already determined priority)
            // Backend provides: mode ('phone'/'cw'/'digi') and submode ('LSB'/'USB'/'CW'/'FT8'/etc.)
            var categoryStr = spotInfo.mode || 'phone';
            var submodeStr = spotInfo.submode || '';
            var modeLabel = submodeStr || categoryStr || 'Unknown';
            // Use detailed submode for mode field (e.g., "FT8" instead of "digi")
            var modeForField = submodeStr || categoryStr || '';

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
                // Store callsign to ensure correct spot is used when populating form
                var tuneIcon = '<i class="fas fa-headset tune-icon" title="' + lang_dxwaterfall_tune_to_spot + '" data-frequency="' + spotInfo.frequency + '" data-mode="' + modeForField + '" data-callsign="' + spotInfo.callsign + '"></i> ';

                // Add cycle icon if there are multiple spots
                var cycleIcon = '';
                var spotCounter = '';
                if (this.relevantSpots.length > 1) {
                    cycleIcon = '<i class="fas fa-exchange-alt cycle-spot-icon" title="' + lang_dxwaterfall_cycle_nearby_spots + '"></i> ';
                    spotCounter = '[' + (this.currentSpotIndex + 1) + '/' + this.relevantSpots.length + '] ';
                }

                // Build prefix with tune and cycle icons, then entity info
                prefixText = tuneIcon + cycleIcon + spotCounter + flagPart + entity + ' ';
            }

            // Format the time only (HH:MM) with Z suffix for UTC
            var timeMatch = spotInfo.when_pretty.match(/(\d{2}:\d{2})/);
            var timeStr = timeMatch ? timeMatch[1] : '??:??';

            // Build mode/submode string for display
            // Backend provides: categoryStr = mode ('phone'/'cw'/'digi'), submodeStr = submode ('LSB'/'FT8'/etc.)
            var modeDisplay = '';

            // Format: [Category-Submode] if both exist and differ, else just [Submode] or [Category]
            if (categoryStr && submodeStr && categoryStr !== submodeStr.toLowerCase()) {
                modeDisplay = '[' + categoryStr + '-' + submodeStr + ']';
            } else if (submodeStr) {
                modeDisplay = '[' + submodeStr + ']';
            } else if (categoryStr) {
                modeDisplay = '[' + categoryStr + ']';
            }

            infoText = prefixText + modeDisplay + lotwIndicator + ' ' + spotInfo.callsign + ' de ' + spotInfo.spotter + ' @' + timeStr + 'Z ';

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
    // @param {boolean} forceUpdate - If true, bypass state check
    updateZoomMenu: function(forceUpdate) {
        if (!this.zoomMenuDiv) {
            return;
        }

        var currentState = DXWaterfallStateMachine.getState();
        var STATES = DX_WATERFALL_CONSTANTS.STATES;

        // Don't show menu during TUNING state (frequency changes invisible to user)
        // UNLESS forceUpdate is true (e.g., after data fetch completes)
        if (!forceUpdate && currentState === STATES.TUNING) {
            // Don't update menu during frequency changes - keep showing last state
            return;
        }

        // ========================================
        // INITIALIZING STATE - Show loading
        // ========================================
        if (currentState === STATES.INITIALIZING) {
            var loadingHTML = '<div style="display: flex; align-items: center; flex: 1;"><i class="fas fa-hourglass-half" style="margin-right: 5px; animation: blink 1s infinite;"></i><span style="margin-right: 10px;">' + lang_dxwaterfall_please_wait + '</span></div>';
            if (this.lastZoomMenuHTML !== loadingHTML) {
                this.zoomMenuDiv.innerHTML = loadingHTML;
                this.lastZoomMenuHTML = loadingHTML;
            }
            return;
        }

        // ========================================
        // FETCHING_SPOTS STATE - Show loading with timer
        // ========================================
        if (currentState === STATES.FETCHING_SPOTS) {
            // If we have no spots yet (initial fetch), show simple loading message
            if (!this.dxSpots || this.dxSpots.length === 0) {
                var fetchingHTML;
                if (this.operationStartTime) {
                    var elapsed = ((Date.now() - this.operationStartTime) / 1000).toFixed(1);
                    var displayText = (elapsed < 1.0) ? lang_dxwaterfall_please_wait : elapsed + 's';
                    fetchingHTML = '<div style="display: flex; align-items: center; flex: 1;"><i class="fas fa-hourglass-half" style="margin-right: 5px; animation: blink 1s infinite;"></i><span style="margin-right: 10px;">' + displayText + '</span></div>';
                } else {
                    fetchingHTML = '<div style="display: flex; align-items: center; flex: 1;"><i class="fas fa-hourglass-half" style="margin-right: 5px; animation: blink 1s infinite;"></i><span style="margin-right: 10px;">&nbsp;</span></div>';
                }
                if (this.lastZoomMenuHTML !== fetchingHTML) {
                    this.zoomMenuDiv.innerHTML = fetchingHTML;
                    this.lastZoomMenuHTML = fetchingHTML;
                }
                return;
            }
            // If we have spots already, fall through to show full menu with loading indicator
        }

        // ========================================
        // READY STATE (or FETCHING with existing data) - Show full menu
        // ========================================
        var currentMode = this.getCurrentMode().toLowerCase();

        // Build zoom controls HTML - start with status indicator and band spot navigation
        var zoomHTML = '<div style="display: flex; align-items: center; flex: 1;">';

        // Add loading indicator if fetching spots (refreshing existing data)
        var showLoadingIndicator = (currentState === STATES.FETCHING_SPOTS) && this.userInitiatedFetch;

        if (showLoadingIndicator) {
            if (this.operationStartTime) {
                var elapsed = ((Date.now() - this.operationStartTime) / 1000).toFixed(1);
                var hasData = this.dxSpots && this.dxSpots.length > 0;
                var displayText = (!hasData && elapsed < 1.0) ? lang_dxwaterfall_please_wait : elapsed + 's';
                zoomHTML += '<i class="fas fa-hourglass-half" style="margin-right: 5px; animation: blink 1s infinite;"></i><span style="margin-right: 10px;">' + displayText + '</span>';
            } else {
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
            var isFetchingSpots = (currentState === DX_WATERFALL_CONSTANTS.STATES.FETCHING_SPOTS);
            if (isFetchingSpots) {
                // Fetching data - show as disabled
                zoomHTML += '<i class="fas fa-globe-americas continent-cycle-icon disabled" title="' + lang_dxwaterfall_downloading_data + '" style="opacity: 0.3; cursor: not-allowed;"></i>';
                zoomHTML += '<span class="continent-cycle-text disabled" title="' + lang_dxwaterfall_downloading_data + '" style="opacity: 0.3; cursor: not-allowed;">de ' + this.currentContinent + '</span>';
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
            zoomHTML += '<span style="margin-left: 5px; margin-right: 3px; font-size: 13px;">' + lang_dxwaterfall_modes_label + '</span>';

            // CW filter - Orange
            var cwClass = activeFilters.cw ? 'mode-filter-cw active' : 'mode-filter-cw';
            var cwStyle = activeFilters.cw ? 'color: #FFA500; font-weight: bold;' : 'color: #888888;';
            if (this.pendingModeFilters) cwStyle += ' ' + blinkStyle;
            cwStyle += ' cursor: pointer;';
            zoomHTML += '<span class="' + cwClass + '" title="' + lang_dxwaterfall_toggle_cw + '" style="' + cwStyle + ' margin: 0 3px; font-size: 13px; transition: color 0.2s;">' + lang_dxwaterfall_cw + '</span>';

            // Digi filter - Blue
            var digiClass = activeFilters.digi ? 'mode-filter-digi active' : 'mode-filter-digi';
            var digiStyle = activeFilters.digi ? 'color: #0096FF; font-weight: bold;' : 'color: #888888;';
            if (this.pendingModeFilters) digiStyle += ' ' + blinkStyle;
            digiStyle += ' cursor: pointer;';
            zoomHTML += '<span class="' + digiClass + '" title="' + lang_dxwaterfall_toggle_digi + '" style="' + digiStyle + ' margin: 0 3px; font-size: 13px; transition: color 0.2s;">' + lang_dxwaterfall_digi + '</span>';

            // Phone filter - Green
            var phoneClass = activeFilters.phone ? 'mode-filter-phone active' : 'mode-filter-phone';
            var phoneStyle = activeFilters.phone ? 'color: #00FF00; font-weight: bold;' : 'color: #888888;';
            if (this.pendingModeFilters) phoneStyle += ' ' + blinkStyle;
            phoneStyle += ' cursor: pointer;';
            zoomHTML += '<span class="' + phoneClass + '" title="' + lang_dxwaterfall_toggle_phone + '" style="' + phoneStyle + ' margin: 0 3px; font-size: 13px; transition: color 0.2s;">' + lang_dxwaterfall_phone + '</span>';

        zoomHTML += '</div>';

        // Center section: spot count information
        // Format: "31/43 20m NA spots @22:16LT" or "No spots" when empty
        zoomHTML += '<div style="flex: 1; display: flex; justify-content: center; align-items: center;">';

        // Check if we're out of band (using 20kHz margin)
        var currentFreqKhz = this.getCachedMiddleFreq();
        var detectedBand = frequencyToBandKhz(currentFreqKhz, 20);
        var isOutOfBand = (!detectedBand);

        if (isOutOfBand && (!this.currentSpotBand || this.currentSpotBand === 'All')) {
            // Out of band with no spots loaded - show "Out of band" message
            zoomHTML += '<span style="font-size: 13px; color: #FF6B6B; font-weight: bold;">';
            zoomHTML += '<i class="fas fa-exclamation-triangle" style="margin-right: 5px;"></i>';
            zoomHTML += (typeof lang_dxwaterfall_out_of_band !== 'undefined') ? lang_dxwaterfall_out_of_band : 'Out of band';
            zoomHTML += '</span>';
        } else if (this.lastUpdateTime) {
            var hours = String(this.lastUpdateTime.getHours()).padStart(2, '0');
            var minutes = String(this.lastUpdateTime.getMinutes()).padStart(2, '0');
            var updateTimeStr = hours + ':' + minutes;
            // Display the band we have spots for, not the form selector
            var currentBand = this.currentSpotBand || this.getCurrentBand();

            zoomHTML += '<span style="font-size: 13px; color: #888888;">';

            if (this.dxSpots && this.dxSpots.length > 0) {
                // Count displayed spots
                var displayedSpotsCount = 0;
                for (var i = 0; i < this.dxSpots.length; i++) {
                    if (this.spotMatchesModeFilter(this.dxSpots[i])) {
                        displayedSpotsCount++;
                    }
                }
                zoomHTML += displayedSpotsCount + '/' + this.totalSpotsCount + ' ' + currentBand + ' ' + this.currentContinent + ' ' + lang_dxwaterfall_spots + ' @' + updateTimeStr + 'LT';
            } else {
                // No spots available - still show band and continent info
                zoomHTML += '0 ' + currentBand + ' ' + this.currentContinent + ' ' + lang_dxwaterfall_spots + ' @' + updateTimeStr + 'LT';
            }

            zoomHTML += '</span>';
        }
        zoomHTML += '</div>';

        // Right side: label size and zoom controls
        zoomHTML += '<div style="display: flex; align-items: center; white-space: nowrap;">';

        // Label size cycle icon with tooltip showing current size and next size
        var labelSizeNames = [
            lang_dxwaterfall_label_size_xsmall,
            lang_dxwaterfall_label_size_small,
            lang_dxwaterfall_label_size_medium,
            lang_dxwaterfall_label_size_large,
            lang_dxwaterfall_label_size_xlarge
        ];
        var currentSizeText = labelSizeNames[this.labelSizeLevel];
        var nextSizeIndex = (this.labelSizeLevel + 1) % 5;
        var nextSizeText = labelSizeNames[nextSizeIndex];
        zoomHTML += '<i class="fas fa-font label-size-icon" title="' + lang_dxwaterfall_label_size_cycle + ': ' + currentSizeText + ' → ' + nextSizeText + '"></i>';

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
            zoomHTML += '<i class="fas fa-undo zoom-reset-icon" title="' + lang_dxwaterfall_reset_zoom + '" style="margin: 0 5px;"></i> ';
        } else {
            zoomHTML += '<i class="fas fa-undo zoom-reset-icon disabled" title="' + lang_dxwaterfall_reset_zoom + '" style="margin: 0 5px; opacity: 0.3; cursor: not-allowed;"></i> ';
        }

        // Zoom in button (disabled if at max level)
        if (this.currentZoomLevel < this.maxZoomLevel) {
            zoomHTML += '<i class="fas fa-search-plus zoom-in-icon" title="' + lang_dxwaterfall_zoom_in + '"></i>';
        } else {
            zoomHTML += '<i class="fas fa-search-plus zoom-in-icon disabled" title="' + lang_dxwaterfall_zoom_in + '" style="opacity: 0.3; cursor: not-allowed;"></i>';
        }

        zoomHTML += '</div>';

        // Only update DOM if HTML actually changed (prevents destroying button event handlers)
        if (this.lastZoomMenuHTML !== zoomHTML) {
            this.zoomMenuDiv.innerHTML = zoomHTML;
            this.lastZoomMenuHTML = zoomHTML;
        }
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
            DX_WATERFALL_UTILS.navigation.navigateToSpot(this, result.spot, result.index, false); // Don't prefill
        }
    },

    // Navigate to next spot (nearest spot to the right of current frequency)
    nextSpot: function() {
        var result = this.findNearestSpot('next');
        if (result) {
            DX_WATERFALL_UTILS.navigation.navigateToSpot(this, result.spot, result.index, false); // Don't prefill
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
        // Use the band we have spots for, not the form selector
        var currentBand = this.currentSpotBand || this.getCurrentBand();
        var currentMode = this.getCurrentMode();

        // Filter spots for current band
        var result = DX_WATERFALL_UTILS.spots.filterSpots(this, function(spot, spotFreq, context) {
            // Validate that spot belongs to current band (prevent cross-band contamination)
            var spotBand = frequencyToBandKhz(spotFreq);
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

        // Set smart hunter active flag
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
            // Update smart hunter index
            this.currentSmartHunterIndex = targetIndex;

            // Get the complete spot data from the stored reference
            var completeSpot = targetSpot._originalSpot || targetSpot;

            // Use centralized navigation logic with prefill enabled
            DX_WATERFALL_UTILS.navigation.navigateToSpot(this, completeSpot, targetIndex, true);
        }
    },

    // Jump to first spot in band
    firstSpot: function() {
        // Don't handle navigation when in TUNING state
        var currentState = DXWaterfallStateMachine.getState();
        if (currentState === DX_WATERFALL_CONSTANTS.STATES.TUNING) {
            return; // Block navigation during frequency changes
        }

        var spot = this.allBandSpots[0];
        if (spot) {
            DX_WATERFALL_UTILS.navigation.navigateToSpot(this, spot, 0, false); // Don't prefill
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
            DX_WATERFALL_UTILS.navigation.navigateToSpot(this, spot, lastIndex, false); // Don't prefill
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

            // Invalidate band limits cache (region may have changed)
            self.bandLimitsCache = null;

            // Reset band plans to force reload for new region
            self.bandPlans = null;
            self.bandEdgesData = null;

            // Load band plans for new region (based on new continent)
            self.loadBandPlans();

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

            // Update zoom menu to show new continent
            self.updateZoomMenu();

            // Fetch new spots with the new continent (state machine handles FETCHING_SPOTS state)
            self.fetchDxSpots(true, true); // User changed continent - mark as user-initiated

        }, 1500); // Wait 1.5 seconds after last click before fetching
    },

    // Check if a spot should be shown based on active mode filters
    spotMatchesModeFilter: function(spot) {
        // Use backend-provided mode (already classified with priority logic)
        // Backend provides: 'phone', 'cw', or 'digi'
        var spotMode = spot.mode || 'phone';

        // Use pending filters if they exist, otherwise use current filters
        var filters = this.pendingModeFilters || this.modeFilters;

        // If mode is unknown/unclassified, default to phone (treat as SSB)
        if (!spotMode || (spotMode !== 'phone' && spotMode !== 'cw' && spotMode !== 'digi')) {
            spotMode = 'phone';
        }

        // For digi mode spots: if digi filter is OFF, also hide spots on FT8 frequencies
        // This prevents clutter from FT8 spots when user doesn't want to see digi modes
        // But if digi filter is ON, show all digi spots including FT8 frequencies
        if (spotMode === 'digi') {
            var spotFreq = parseFloat(spot.frequency);
            var isOnFT8Freq = isFT8Frequency(spotFreq, 'kHz');

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

        // Prevent rapid double-clicks from causing issues
        var now = Date.now();
        if (this.lastFilterToggleTime && (now - this.lastFilterToggleTime) < 50) {
            DX_WATERFALL_UTILS.log.debug('[Filter Toggle] Ignoring rapid double-click');
            return;
        }
        this.lastFilterToggleTime = now;

        // Create pending filters if they don't exist (clone current filters)
        if (!this.pendingModeFilters) {
            this.pendingModeFilters = {
                phone: this.modeFilters.phone,
                cw: this.modeFilters.cw,
                digi: this.modeFilters.digi
            };
        }

        // Toggle the specified filter
        this.pendingModeFilters[modeType] = !this.pendingModeFilters[modeType];

        // Apply filter changes immediately for instant visual feedback
        this.modeFilters.phone = this.pendingModeFilters.phone;
        this.modeFilters.cw = this.pendingModeFilters.cw;
        this.modeFilters.digi = this.pendingModeFilters.digi;

        // Invalidate visible spots cache immediately for instant update
        this.cache.visibleSpots = null;
        this.cache.visibleSpotsParams = null;

        // Trigger immediate refresh to show filter changes
        // This ensures the display updates instantly without waiting for the next interval
        if (this.canvas && this.ctx) {
            this.refresh();
        }

        // Don't update menu here - it will be updated by the timeout handler
        // Updating here causes the button to be recreated which can trigger duplicate events

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
    /**
     * Destroy the waterfall and clean up all resources
     * Transitions through DEINITIALIZING to DISABLED state
     */
    destroy: function() {
        // Transition to DEINITIALIZING state
        DXWaterfallStateMachine.setState(DX_WATERFALL_CONSTANTS.STATES.DEINITIALIZING);

        // Clear error shutdown timer if active
        if (this.errorShutdownTimer) {
            clearTimeout(this.errorShutdownTimer);
            this.errorShutdownTimer = null;
        }

        // Clear ready transition timer if active
        if (this.readyTransitionTimer) {
            clearTimeout(this.readyTransitionTimer);
            this.readyTransitionTimer = null;
        }

        // Clear all state machine timers
        if (DXWaterfallStateMachine.stateTimer) {
            clearTimeout(DXWaterfallStateMachine.stateTimer);
            DXWaterfallStateMachine.stateTimer = null;
        }

        // Clear all application timers to prevent memory leaks
        if (this.fetchDebounceTimer) {
            clearTimeout(this.fetchDebounceTimer);
            this.fetchDebounceTimer = null;
        }
        if (this.modeFilterChangeTimer) {
            clearTimeout(this.modeFilterChangeTimer);
            this.modeFilterChangeTimer = null;
        }

        // Abort any pending AJAX requests
        if (this.pendingFetchRequest) {
            this.pendingFetchRequest.abort();
            this.pendingFetchRequest = null;
            DX_WATERFALL_UTILS.log.debug('[DX Waterfall] Aborted pending fetch request');
        }

        // Clear QSO form utility timers
        if (DX_WATERFALL_UTILS.qsoForm.pendingPopulationTimer) {
            clearTimeout(DX_WATERFALL_UTILS.qsoForm.pendingPopulationTimer);
            DX_WATERFALL_UTILS.qsoForm.pendingPopulationTimer = null;
        }
        if (DX_WATERFALL_UTILS.qsoForm.pendingLookupTimer) {
            clearTimeout(DX_WATERFALL_UTILS.qsoForm.pendingLookupTimer);
            DX_WATERFALL_UTILS.qsoForm.pendingLookupTimer = null;
        }

        // Clear navigation utility timers
        if (DX_WATERFALL_UTILS.navigation.pendingNavigationTimer) {
            clearTimeout(DX_WATERFALL_UTILS.navigation.pendingNavigationTimer);
            DX_WATERFALL_UTILS.navigation.pendingNavigationTimer = null;
        }

        // Remove event listeners added in init() to prevent memory leaks
        if (this.canvas) {
            if (this._wheelHandler) {
                this.canvas.removeEventListener('wheel', this._wheelHandler);
                this._wheelHandler = null;
            }
            if (this._mousemoveHandler) {
                this.canvas.removeEventListener('mousemove', this._mousemoveHandler);
                this._mousemoveHandler = null;
            }
        }

        // Unbind jQuery event handlers that were added in init()
        // Note: Event handlers registered outside dxWaterfall object (like menu clicks)
        // should NOT be unbound here as they are global and persistent
        if (this.$freqCalculated) {
            this.$freqCalculated.off('focus blur input keydown');
        }
        if (this.$frequency) {
            this.$frequency.off('change');
        }
        if (this.$bandSelect) {
            this.$bandSelect.off('change');
        }
        if (this.$modeSelect) {
            this.$modeSelect.off('change');
        }
        if (this.canvas) {
            // Unbind jQuery events on canvas (click is handled globally, not here)
            $(this.canvas).off('wheel');
        }

        // Clear canvas
        if (this.ctx && this.canvas) {
            try {
                this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
            } catch (e) {
                DX_WATERFALL_UTILS.log.warn('[DX Waterfall] Error clearing canvas:', e);
            }
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
        this.cache.visibleSpots = null;
        this.cache.visibleSpotsParams = null;

        // Clear frequency tracking properties (used in getCachedMiddleFreq)
        this.lastModeForCache = null;
        this.lastValidCommittedFreqHz = null;

        // Clear cached pixels per kHz
        this.cachedPixelsPerKHz = null;

        // Reset indices
        this.currentSpotIndex = 0;
        this.currentBandSpotIndex = 0;
        this.currentSmartHunterIndex = 0;

        // Reset zoom level to default
        this.currentZoomLevel = DX_WATERFALL_CONSTANTS.ZOOM.DEFAULT_LEVEL;

        // Clear pending states
        this.pendingContinent = null;
        this.pendingModeFilters = null;
        this.pendingSpotSelection = null;

        // Reset spot info key
        this.lastSpotInfoKey = null;

        // Clear band tracking
        this.lastFetchBand = null;
        this.lastFetchContinent = null;
        this.lastFetchAge = null;
        this.currentSpotBand = null; // Reset the band we have spots for

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

        // Transition to DISABLED state
        DXWaterfallStateMachine.setState(DX_WATERFALL_CONSTANTS.STATES.DISABLED);

        // Always log cleanup completion (user-facing message)
        DX_WATERFALL_UTILS.log.debug('[DX Waterfall] Unloaded successfully');
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
        var ssbMode = determineSSBMode(currentFreq);
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
            modeSelect.val(modeUpper);

            // Only trigger change if skipTrigger is false
            if (!skipTrigger) {
                modeSelect.trigger('change');
            }

            return true;
        } else {
            // Mode doesn't exist, select the first available option as fallback
            var firstOption = modeSelect.find('option:first').val();
            if (firstOption) {
                modeSelect.val(firstOption);

                // Only trigger change if skipTrigger is false
                if (!skipTrigger) {
                    modeSelect.trigger('change');
                }
            }
            return false;
        }
}

// Helper function to handle frequency changes via CAT or manual input
// Global function so it can be accessed from dxWaterfall methods
// @param frequencyInKHz - Target frequency in kHz
// @param fromWaterfall - True if this change was initiated by waterfall (clicking spot/tune icon), false for external calls
function setFrequency(frequencyInKHz, fromWaterfall) {

    // PROTECTION: If user is manually updating frequency from form, don't tune radio
    // This prevents form changes from controlling the radio (radio should control form)
    if (typeof window.user_updating_frequency !== 'undefined' && window.user_updating_frequency) {
        DX_WATERFALL_UTILS.log.info('[setFrequency] Skipping radio tune - user manually updating form');
        return;
    }

    // Input validation
    if (!frequencyInKHz || typeof frequencyInKHz !== 'number') {
        DX_WATERFALL_UTILS.log.warn('[setFrequency] Invalid frequency parameter:', frequencyInKHz);
        return;
    }

    // Validate frequency range (30 kHz to 3 GHz - reasonable amateur radio range)
    if (frequencyInKHz < 0.03 || frequencyInKHz > 3000000) {
        DX_WATERFALL_UTILS.log.warn('[setFrequency] Frequency out of valid range:', frequencyInKHz, 'kHz');
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

    // Check if already changing frequency (TUNING state) and block rapid commands
    var currentState = (typeof DXWaterfallStateMachine !== 'undefined') ? DXWaterfallStateMachine.getState() : null;
    if (currentState === DX_WATERFALL_CONSTANTS.STATES.TUNING) {
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
                var ssbMode = determineSSBMode(frequencyInKHz);
                catMode = ssbMode.toLowerCase();
            }
        }

        // Use the new unified tuneRadioToFrequency function with callbacks
        if (typeof tuneRadioToFrequency === 'function') {
            // Set frequency changing flag and show visual feedback
            if (typeof dxWaterfall !== 'undefined') {
                // Check if we're already at the target frequency (within 1Hz tolerance)
                var currentFreqHz = Math.round(dxWaterfall.committedFrequencyKHz * 1000);
                var diff = Math.abs(currentFreqHz - formattedFreq);
                if (diff <= 1) {
                    // Just update the waterfall display, don't transition states
                    dxWaterfall.invalidateFrequencyCache(formattedFreq / 1000, true);
                    return; // Skip the entire CAT process
                }

                // Cancel any previous frequency confirmation timeout
                if (dxWaterfall.frequencyConfirmTimeoutId) {
                    clearTimeout(dxWaterfall.frequencyConfirmTimeoutId);
                    dxWaterfall.frequencyConfirmTimeoutId = null;
                }

                // Set target frequency FIRST before transition
                dxWaterfall.targetFrequencyHz = formattedFreq;
                dxWaterfall.targetFrequencyConfirmAttempts = 0; // Reset confirmation counter
                dxWaterfall.operationStartTime = Date.now(); // Reset operation timer for display
                dxWaterfall.lastWaterfallFrequencyCommandTime = Date.now(); // Track waterfall command time

                // Transition to TUNING state
                DXWaterfallStateMachine.setState(DX_WATERFALL_CONSTANTS.STATES.TUNING, {
                    targetFrequency: formattedFreq,
                    reason: fromWaterfall ? 'spot_click' : 'external'
                });

                // IMMEDIATELY update waterfall to show new frequency (don't wait for CAT)
                // This prevents the visual "jump" when the old frequency comes back from CAT
                dxWaterfall.invalidateFrequencyCache(formattedFreq / 1000, true); // true = immediate update
            }

            // Define success callback
            var onSuccess = function(data, textStatus, jqXHR) {
                // Optionally log response if it contains useful info
                if (data && data.trim() !== '') {
                    // Response received
                }

                // Get timing based on connection type (WebSocket vs Polling)
                var timings = getCATTimings();

                // Note: State transitions are now handled by handleCATFrequencyUpdate()
                // when radio confirms the frequency change

                // Set a timeout to handle if radio doesn't confirm - store timeout ID so we can cancel it
                dxWaterfall.frequencyConfirmTimeoutId = setTimeout(function() {
                    // Clear the timeout ID
                    dxWaterfall.frequencyConfirmTimeoutId = null;

                    // Check if we're still waiting for frequency confirmation
                    if (typeof dxWaterfall !== 'undefined' && dxWaterfall.targetFrequencyHz) {
                        // Radio didn't confirm frequency within timeout - transition to ERROR
                        var targetKHz = dxWaterfall.targetFrequencyHz / 1000;
                        DXWaterfallStateMachine.setState(DX_WATERFALL_CONSTANTS.STATES.ERROR, {
                            message: 'Radio did not confirm frequency ' + targetKHz.toFixed(3) + ' kHz within timeout'
                        });

                        // Clear target frequency
                        dxWaterfall.targetFrequencyHz = null;
                        dxWaterfall.targetFrequencyConfirmAttempts = 0;
                        dxWaterfall.spotNavigating = false;
                    }
                }, timings.confirmTimeout); // WebSocket: 300ms, Polling: 3000ms
            };

            // Define error callback
            var onError = function(jqXHR, textStatus, errorThrown) {
                // CAT command failed - transition to ERROR state
                var errorMsg = 'CAT command failed';
                if (textStatus) {
                    errorMsg += ' (' + textStatus + ')';
                }
                if (errorThrown) {
                    errorMsg += ': ' + errorThrown;
                }

                if (typeof dxWaterfall !== 'undefined') {
                    // Clear target frequency tracking
                    dxWaterfall.targetFrequencyHz = null;
                    dxWaterfall.targetFrequencyConfirmAttempts = 0;
                    dxWaterfall.spotNavigating = false;

                    // Transition to ERROR state
                    DXWaterfallStateMachine.setState(DX_WATERFALL_CONSTANTS.STATES.ERROR, {
                        message: errorMsg
                    });
                }

                // Log detailed error for debugging
                if (textStatus !== 'timeout' && jqXHR && jqXHR.status !== 0) {
                    if (jqXHR.responseText) {
                        DX_WATERFALL_UTILS.log.warn('DX Waterfall: CAT command error details:', jqXHR.responseText);
                    }
                }
            };

            // Call unified tuning function with callbacks
            // Pass skipWaterfall=true to prevent infinite loop (don't call setFrequency again)
            tuneRadioToFrequency(null, formattedFreq, catMode, onSuccess, onError, true);
        }
        return;
    }

    // Write to frequency field in Hz (single source of truth)
    // The change event will trigger set_qrg() which updates freq_calculated display
    $('#frequency').val(frequencyInKHz * 1000);

    // Trigger change event to update calculated fields and unit display
    // Skip trigger when called from waterfall to prevent recursive updates
    // Exception: If no radio is selected, update display even when called from waterfall
    if (!fromWaterfall || $('#radio').val() == 0) {
        set_qrg();
    }

    // Clear navigation flags immediately since no CAT operation is happening
    if (typeof dxWaterfall !== 'undefined') {
        dxWaterfall.spotNavigating = false; // Clear navigation flag immediately
        // Don't call invalidateFrequencyCache - it's for CAT confirmation
        // When CAT is disabled, waterfall frequency is managed independently
    }
}

// Wait for jQuery to be available before initializing
(function waitForJQuery() {
    // Global timer variable to prevent multiple auto-refresh timers
    var autoRefreshTimer = null;
    var isInitialized = false;

    if (typeof jQuery !== 'undefined') {
        // jQuery is loaded, proceed with initialization
        $(document).ready(function() {
            // Initialize DOM cache
            DX_WATERFALL_UTILS.dom.init();

            // Function to try initializing the canvas with retries
            function tryInitCanvas() {
        if (document.getElementById('dxWaterfall')) {
            // Prevent multiple initializations
            if (isInitialized) {
                DX_WATERFALL_UTILS.log.debug('[DX Waterfall] Already initialized, skipping duplicate initialization');
                return;
            }
            isInitialized = true;

            // Canvas found, but DON'T auto-initialize
            // Wait for user to click the power button
            // Auto-refresh timer will be created when waterfall is turned on

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

    // Cleanup function to prevent memory leaks and multiple timers
    $(window).on('beforeunload pagehide', function() {
        if (autoRefreshTimer) {
            clearInterval(autoRefreshTimer);
            autoRefreshTimer = null;
            DX_WATERFALL_UTILS.log.debug('[DX Waterfall] Auto-refresh timer cleaned up on page unload');
        }
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
        var callsign = $(this).data('callsign');

        if (frequency) {
            // Set the mode if available - use skipTrigger=true to prevent change events
            // This prevents the form from being cleared by event handlers
            if (mode) {
                setMode(mode, true); // Skip triggering change event
            }

            // Use helper function to set frequency
            // fromWaterfall=true prevents frequency change event from being triggered
            setFrequency(frequency, true);

            // In offline mode, update virtual CAT state
            if (typeof isCATAvailable === 'function' && !isCATAvailable()) {
                var freqHz = Math.round(frequency * 1000); // Convert kHz to Hz
                if (typeof window.catState === 'undefined' || window.catState === null) {
                    window.catState = {};
                }
                window.catState.frequency = freqHz;
                window.catState.mode = mode;
                window.catState.lastUpdate = Date.now();
                DX_WATERFALL_UTILS.log.debug('[DX Waterfall] Offline mode - tune icon updated virtual CAT: freq=' + freqHz + ' Hz, mode=' + mode);

                // Update relevant spots for the new frequency
                if (typeof dxWaterfall !== 'undefined' && dxWaterfall && typeof dxWaterfall.collectAllBandSpots === 'function') {
                    dxWaterfall.collectAllBandSpots(true);
                }
            }

            // Populate the QSO form with spot data
            // Find the specific spot by callsign to ensure we use the displayed spot
            var spotInfo = null;
            if (callsign && dxWaterfall.relevantSpots && dxWaterfall.relevantSpots.length > 0) {
                // Find the spot with matching callsign from relevant spots
                for (var i = 0; i < dxWaterfall.relevantSpots.length; i++) {
                    if (dxWaterfall.relevantSpots[i].callsign === callsign) {
                        spotInfo = dxWaterfall.relevantSpots[i];
                        break;
                    }
                }
            }

            // Fallback to getSpotInfo if callsign lookup failed
            if (!spotInfo) {
                spotInfo = dxWaterfall.getSpotInfo();
            }

            if (spotInfo) {
                // Clear form first
                DX_WATERFALL_UTILS.qsoForm.clearForm();

                // Populate from spot with a brief delay (same as other prefill paths)
                setTimeout(function() {
                    DX_WATERFALL_UTILS.qsoForm.populateFromSpot(spotInfo, true);
                }, DX_WATERFALL_CONSTANTS.DEBOUNCE.FORM_POPULATE_DELAY_MS);
            }

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

    // Handle click on label size cycle button (with processing lock to prevent double-click)
    $('#dxWaterfallMenu').on('click', '.label-size-icon', function(e) {
        e.stopPropagation();
        e.preventDefault();

        // Lock check: if already processing, ignore this click
        if (dxWaterfall.labelSizeProcessing) {
            DX_WATERFALL_UTILS.log.debug('[Label Size] Click ignored (already processing)');
            return;
        }

        // Set lock immediately
        dxWaterfall.labelSizeProcessing = true;

        var oldLevel = dxWaterfall.labelSizeLevel;
        DX_WATERFALL_UTILS.log.debug('[Label Size] Click ACCEPTED, current level: ' + oldLevel);

        // Cycle through 5 label sizes: 0 -> 1 -> 2 -> 3 -> 4 -> 0
        dxWaterfall.labelSizeLevel = (dxWaterfall.labelSizeLevel + 1) % 5;

        DX_WATERFALL_UTILS.log.debug('[Label Size] New level after cycle: ' + dxWaterfall.labelSizeLevel);

        // Save to cookie
        dxWaterfall.saveFontSizeToCookie();
        DX_WATERFALL_UTILS.log.debug('[Label Size] Saved to cookie');

        // Visual feedback - briefly change icon color BEFORE updating menu
        var icon = $(this);
        icon.css({'color': '#FFFF00', 'transition': 'color 0.2s'});

        // Wait for visual feedback, then update menu and refresh
        setTimeout(function() {
            DX_WATERFALL_UTILS.log.debug('[Label Size] Updating menu and refreshing...');

            // Update the menu to show new size in tooltip (this replaces the icon)
            dxWaterfall.updateZoomMenu();

            // Refresh the display to show new label sizes
            dxWaterfall.refresh();

            // Release lock after refresh completes (add small delay for safety)
            setTimeout(function() {
                dxWaterfall.labelSizeProcessing = false;
                DX_WATERFALL_UTILS.log.debug('[Label Size] Processing lock released');
            }, 100);
        }, DX_WATERFALL_CONSTANTS.DEBOUNCE.ZOOM_ICON_FEEDBACK_MS);
    });    // Handle click on previous band spot button
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
        var currentState = DXWaterfallStateMachine.getState();
        if (currentState === DX_WATERFALL_CONSTANTS.STATES.TUNING) {
            return;
        }

        // Only allow clicks in READY state
        if (currentState !== DX_WATERFALL_CONSTANTS.STATES.READY) {
            return;
        }

        // Get click coordinates relative to canvas
        var canvas = this;
        var rect = canvas.getBoundingClientRect();
        var x = e.clientX - rect.left;
        var y = e.clientY - rect.top;

        // Check if user clicked on a spot label
        var clickedSpot = dxWaterfall.findSpotAtPosition(x, y);
        if (clickedSpot && clickedSpot.frequency) {

            // Preserve spot selection across frequency changes
            dxWaterfall.pendingSpotSelection = {
                callsign: clickedSpot.callsign,
                frequency: clickedSpot.frequency
            };

            // Update currentSpotIndex when clicking stacked center spots
            if (dxWaterfall.relevantSpots && dxWaterfall.relevantSpots.length > 0) {
                for (var i = 0; i < dxWaterfall.relevantSpots.length; i++) {
                    if (dxWaterfall.relevantSpots[i].callsign === clickedSpot.callsign &&
                        Math.abs(dxWaterfall.relevantSpots[i].frequency - clickedSpot.frequency) < 0.01) {
                        dxWaterfall.currentSpotIndex = i;
                        // Update spot info display to show the selected spot with visual border
                        dxWaterfall.updateSpotInfoDiv();
                        break;
                    }
                }
            }

            // Set navigation flag to block refresh interference during spot click
            DX_WATERFALL_UTILS.navigation.navigating = true;

            // CRITICAL: Set mode FIRST (without triggering change event), THEN set frequency
            // This ensures setFrequency() reads the correct mode from the dropdown
            var frequencyHz = parseFloat(clickedSpot.frequency) * 1000; // Convert kHz to Hz
            var radioMode = determineRadioMode(clickedSpot.mode, frequencyHz);
            setMode(radioMode, true); // skipTrigger = true to prevent change event

            // Now set frequency - it will read the correct mode from the dropdown
            setFrequency(clickedSpot.frequency, true);

            // In offline mode, update virtual CAT state with spot frequency and mode
            if (typeof isCATAvailable === 'function' && !isCATAvailable()) {
                var spotFreqHz = Math.round(clickedSpot.frequency * 1000); // Convert kHz to Hz
                if (typeof window.catState === 'undefined' || window.catState === null) {
                    window.catState = {};
                }
                window.catState.frequency = spotFreqHz;
                window.catState.mode = radioMode;
                window.catState.lastUpdate = Date.now();
                DX_WATERFALL_UTILS.log.debug('[DX Waterfall] Offline mode - spot click updated virtual CAT: freq=' + spotFreqHz + ' Hz, mode=' + radioMode);
            }

            // Send frequency command again after short delay to correct any drift from mode change
            // (radio control lib bug: mode change can cause slight frequency shift)
            setTimeout(function() {
                setFrequency(clickedSpot.frequency, true);
            }, 200); // 200ms delay to let mode change settle

            // Update band spot collection and zoom menu after navigation
            // This ensures the next/prev spot buttons reflect the new position
            setTimeout(function() {
                dxWaterfall.collectAllBandSpots(true); // Force update after spot click
                dxWaterfall.updateZoomMenu(true); // Force update with forceUpdate=true
            }, 300); // After frequency has settled

            // Clear form before populating with clicked spot
            DX_WATERFALL_UTILS.qsoForm.clearForm();

            // Populate QSO form - flag will be cleared when population completes
            DX_WATERFALL_UTILS.qsoForm.populateFromSpot(clickedSpot, true);

            return; // Don't calculate frequency from position
        }

        // DEBOUNCE: Prevent rapid-fire clicks (multiple events from single physical click)
        var now = Date.now();
        if (typeof DX_WATERFALL_UTILS.dom.getWaterfall().data('lastClickTime') === 'undefined') {
            DX_WATERFALL_UTILS.dom.getWaterfall().data('lastClickTime', 0);
        }
        var lastClickTime = DX_WATERFALL_UTILS.dom.getWaterfall().data('lastClickTime');
        if (now - lastClickTime < 300) { // Ignore clicks within 300ms of previous click
            return;
        }
        DX_WATERFALL_UTILS.dom.getWaterfall().data('lastClickTime', now);

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

        // Set the frequency to where user clicked
        setFrequency(clickedFreq, true);

        // Update cache directly AND sync tracking variables to prevent recalculation
        var formattedFreqHz = Math.round(clickedFreq * 1000); // Convert kHz to Hz
        dxWaterfall.cache.middleFreq = clickedFreq;
        dxWaterfall.lastValidCommittedFreqHz = formattedFreqHz; // Store in Hz

        // In offline mode, update catState with clicked frequency (virtual CAT)
        if (typeof isCATAvailable === 'function' && !isCATAvailable()) {
            if (typeof window.catState === 'undefined' || window.catState === null) {
                window.catState = {};
            }
            window.catState.frequency = formattedFreqHz; // Hz
            window.catState.lastUpdate = Date.now();
            DX_WATERFALL_UTILS.log.debug('[DX Waterfall] Offline mode - waterfall click updated virtual CAT: freq=' + formattedFreqHz + ' Hz');
        }

        // Update band spot collection and zoom menu to reflect new position
        // This ensures next/prev spot buttons and position counter are updated
        setTimeout(function() {
            dxWaterfall.collectAllBandSpots(true); // Force update after frequency click
            dxWaterfall.updateZoomMenu(true); // Force update with forceUpdate=true
        }, 100); // Brief delay to let frequency settle

        // Note: No need to call commitFrequency() here since we already set
        // lastValidCommittedFreqHz directly above
    });

    // Handle keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Block keyboard shortcuts when in TUNING state
        var currentState = DXWaterfallStateMachine.getState();
        if (currentState === DX_WATERFALL_CONSTANTS.STATES.TUNING) {
            return; // Don't handle keys during frequency changes
        }

        // Use Cmd on Mac, Ctrl on Windows/Linux
        var modKey = PlatformDetection.isModifierKey(e);

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
    $('#dxWaterfallMessage').html(lang_dxwaterfall_turn_on);
    $('#dxWaterfallPowerOnIcon').attr('title', decodeHtml(lang_dxwaterfall_turn_on));
    $('#dxWaterfallPowerOffIcon').attr('title', decodeHtml(lang_dxwaterfall_turn_off));

    // Debounce variables for power toggle
    var lastToggleTime = 0;
    var TOGGLE_DEBOUNCE_MS = 5000; // 5 second cooldown
    var initializationDelayTimer = null; // Track the 3-second initialization delay timer

    // Shared debounce check function
    var checkToggleDebounce = function() {
        var now = Date.now();
        if (now - lastToggleTime < TOGGLE_DEBOUNCE_MS) {
            var remainingSeconds = Math.ceil((TOGGLE_DEBOUNCE_MS - (now - lastToggleTime)) / 1000);
            if (typeof showToast === 'function') {
                var message = lang_dxwaterfall_wait_before_toggle.replace('%s', remainingSeconds);
                showToast(lang_general_word_warning, message, 'bg-warning text-dark', 3000);
            }
            return false;
        }
        lastToggleTime = now;
        return true;
    };

    // Function to turn on DX Waterfall (shared by icon and message click)
    var turnOnWaterfall = function(e) {
        // Check debounce - prevent rapid toggling
        if (!checkToggleDebounce()) {
            return;
        }

        // DEBUG: Log what triggered power-on
        DX_WATERFALL_UTILS.log.debug('[Power Control] Power-ON triggered', {
            currentState: DXWaterfallStateMachine.getState(),
            waterfallActive: waterfallActive,
            eventType: e ? e.type : 'unknown',
            eventTarget: e ? e.target : 'unknown'
        });

        if (waterfallActive) {
            DX_WATERFALL_UTILS.log.debug('[Power Control] Already active, ignoring');
            return; // Already active, prevent double initialization
        }

        waterfallActive = true;

        // Log user action
        DX_WATERFALL_UTILS.log.debug('[Power Control] User turned ON waterfall');

        // Update UI - hide header, show content area, show power-off icon, update container styling
        $('#dxWaterfallSpot').addClass('active');
        $('#dxWaterfallSpotHeader').addClass('hidden');
        $('#dxWaterfallSpotContent').addClass('active');
        $('#dxWaterfallPowerOffIcon').addClass('active');
        $('#dxWaterfallHelpIconOff').addClass('active');

        // Show waterfall and menu
        $('#dxWaterfallCanvasContainer').show();
        $('#dxWaterfallMenuContainer').show();

        // Completely destroy and reinitialize waterfall for clean state
        if (typeof dxWaterfall !== 'undefined') {
            var currentState = DXWaterfallStateMachine.getState();

            // Destroy if not already disabled (clears event handlers and state)
            if (currentState !== DX_WATERFALL_CONSTANTS.STATES.DISABLED && dxWaterfall.canvas) {
                dxWaterfall.destroy();
            }

            // Transition to INITIALIZING state
            DXWaterfallStateMachine.setState(DX_WATERFALL_CONSTANTS.STATES.INITIALIZING);

            // Initialize canvas immediately so we can show waiting message
            dxWaterfall._initializeCanvas();

            // Show waiting message
            dxWaterfall.displayWaitingMessage(lang_dxwaterfall_please_wait);

            // Set up periodic refresh interval for visual updates
            waterfallRefreshInterval = setInterval(function() {
                if (dxWaterfall.canvas) {
                    dxWaterfall.refresh();
                }
            }, DX_WATERFALL_CONSTANTS.VISUAL.STATIC_NOISE_REFRESH_MS);

            // Set up DX spots fetching at regular intervals
            if (autoRefreshTimer) {
                clearInterval(autoRefreshTimer);
            }
            autoRefreshTimer = setInterval(function() {
                if (dxWaterfall.canvas) {
                    dxWaterfall.fetchDxSpots(true, false); // Background fetch
                }
            }, DX_WATERFALL_CONSTANTS.DEBOUNCE.DX_SPOTS_FETCH_INTERVAL_MS);

            // Add 3 second delay before initializing (allows page to stabilize)
            initializationDelayTimer = setTimeout(function() {
                if (DXWaterfallStateMachine.getState() === DX_WATERFALL_CONSTANTS.STATES.INITIALIZING) {
                    // Complete initialization - this re-attaches event handlers and starts fetching
                    dxWaterfall._completeInitialization();
                }
                initializationDelayTimer = null;
            }, 3000);
        }
    };

    // Click anywhere on the header div to turn on DX Waterfall
    // Use .off().on() to prevent double-binding if script loads multiple times
    $('#dxWaterfallSpotHeader').off('click').on('click', turnOnWaterfall);

    // Click on power-off icon to turn off DX Waterfall
    $('#dxWaterfallPowerOffIcon').off('click').on('click', function(e) {
        e.stopPropagation(); // Prevent triggering parent click

        // DEBUG: Log what triggered power-off
        DX_WATERFALL_UTILS.log.debug('[Power Control] Power-OFF triggered', {
            currentState: DXWaterfallStateMachine.getState(),
            waterfallActive: waterfallActive,
            eventType: e ? e.type : 'unknown',
            eventTarget: e ? e.target : 'unknown'
        });

        if (!waterfallActive) {
            DX_WATERFALL_UTILS.log.debug('[Power Control] Already inactive, ignoring');
            return; // Already inactive
        }

        waterfallActive = false;
        isInitialized = false;  // Reset so waterfall can be turned on again

        // Log user action
        DX_WATERFALL_UTILS.log.debug('[Power Control] User turned OFF waterfall');

        // Clear the initialization delay timer if still pending
        if (initializationDelayTimer) {
            clearTimeout(initializationDelayTimer);
            initializationDelayTimer = null;
            DX_WATERFALL_UTILS.log.debug('[Power Control] Cleared pending initialization timer');
        }

        // Stop the refresh interval (managed outside dxWaterfall object)
        if (waterfallRefreshInterval) {
            clearInterval(waterfallRefreshInterval);
            waterfallRefreshInterval = null;
        }

        // Stop the auto-refresh timer for DX spots
        if (autoRefreshTimer) {
            clearInterval(autoRefreshTimer);
            autoRefreshTimer = null;
        }

        // Destroy the waterfall component (handles cleanup of memory, timers, event handlers, and DOM refs)
        if (typeof dxWaterfall !== 'undefined' && dxWaterfall.canvas) {
            dxWaterfall.destroy();
        }

        // Update UI - show header, hide content area, hide power-off icon, update container styling
        $('#dxWaterfallSpot').removeClass('active');
        $('#dxWaterfallSpotHeader').removeClass('hidden');
        $('#dxWaterfallSpotContent').removeClass('active');
        $('#dxWaterfallPowerOffIcon').removeClass('active');
        $('#dxWaterfallHelpIconOff').removeClass('active');

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


