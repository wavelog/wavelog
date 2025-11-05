/**
 * RADIO CAT CONTROL FUNCTIONS
 *
 * Connection Types:
 * - WebSocket ('ws'): Real-time communication for live updates
 * - AJAX Polling: Periodic requests every 3 seconds for non-WebSocket radios
 *
 * Data Flow:
 * WebSocket: Server → handleWebSocketData() → updateCATui() → displayRadioStatus()
 * Polling:   updateFromCAT() → AJAX → updateCATui() → displayRadioStatus()
 *
 * Dependencies:
 * - jQuery
 * - Bootstrap tooltips
 * - Global variables from footer.php: base_url, site_url, lang_cat_*, cat_timeout_minutes
 * - Optional: DX Waterfall functions (setFrequency, handleCATFrequencyUpdate, initCATTimings, dxwaterfall_cat_state)
 * - Optional: showToast (from common.js)
 * - Required: frequencyToBand, catmode, setRst functions
 *
 * Required DOM Elements:
 * - select.radios              Radio selection dropdown
 * - #frequency                 Main frequency input field
 * - #band                      Band selection dropdown
 * - .mode                      Mode selection element(s)
 *
 * Optional DOM Elements:
 * - #radio_status              Container for radio status display (created if missing)
 * - #radio_cat_state           Radio CAT status card (dynamically created/removed)
 * - #frequency_rx              RX frequency for split operation
 * - #band_rx                   RX band for split operation
 * - #freq_calculated           Alternative frequency field
 * - #sat_name                  Satellite name field
 * - #sat_mode                  Satellite mode field
 * - #transmit_power            Transmit power field
 * - #selectPropagation         Propagation mode selector
 */

$(document).ready(function() {
    // Global flag for CAT updates (used by all pages, not just DX Waterfall)
    var cat_updating_frequency = false;

    // Global variable for currently selected radio
    var selectedRadioId = null;

    // Cache for radio CAT URLs to avoid repeated AJAX calls
    var radioCatUrlCache = {};

    // Cache for radio names to avoid repeated AJAX calls
    var radioNameCache = {};

    // Global CAT state - stores last received data from radio
    // This allows other components (like DX Waterfall) to read radio state
    // without depending on form fields
    window.catState = {
        frequency: null,      // Hz
        frequency_rx: null,   // Hz (for split operation)
        mode: null,           // String (USB, LSB, CW, etc.)
        lastUpdate: null      // Timestamp of last update
    };

    /**
     * Initialize WebSocket connection for real-time radio updates
     * Handles connection, reconnection logic, and error states
     */
    // Javascript for controlling rig frequency.
    let websocket = null;
    let reconnectAttempts = 0;
    let websocketEnabled = false;
    let websocketIntentionallyClosed = false; // Flag to prevent auto-reconnect when user switches away
    let CATInterval=null;
    var updateFromCAT_lock = 0; // This mechanism prevents multiple simultaneous calls to query the CAT interface information
    var updateFromCAT_lockTimeout = null; // Timeout to release lock if AJAX fails

    // CAT Configuration Constants
    const CAT_CONFIG = {
        POLL_INTERVAL: 3000, // Polling interval in milliseconds
        WEBSOCKET_RECONNECT_MAX: 5,
        WEBSOCKET_RECONNECT_DELAY_MS: 2000,
        AJAX_TIMEOUT_MS: 5000,
        LOCK_TIMEOUT_MS: 10000
    };

    function initializeWebSocketConnection() {
        try {
            // Note: Browser will log WebSocket connection errors to console if server is unreachable
            // This is native browser behavior and cannot be suppressed - errors are handled in GUI via onerror handler
            websocket = new WebSocket('ws://localhost:54322');

            websocket.onopen = function(event) {
                reconnectAttempts = 0;
                websocketEnabled = true;
            };

            websocket.onmessage = function(event) {
                try {
                    const data = JSON.parse(event.data);
                    handleWebSocketData(data);
                } catch (error) {
                    // Invalid JSON data received - silently ignore
                }
            };

            websocket.onerror = function(error) {
                if ($('.radios option:selected').val() != '0') {
                    var radioName = $('select.radios option:selected').text();
                    displayRadioStatus('error', radioName);
                }
                websocketEnabled = false;
            };

        websocket.onclose = function(event) {
            websocketEnabled = false;

            // Only attempt to reconnect if the closure was not intentional
            if (!websocketIntentionallyClosed && reconnectAttempts < CAT_CONFIG.WEBSOCKET_RECONNECT_MAX) {
                setTimeout(() => {
                reconnectAttempts++;
                initializeWebSocketConnection();
            }, CAT_CONFIG.WEBSOCKET_RECONNECT_DELAY_MS * reconnectAttempts);
            } else if (!websocketIntentionallyClosed) {
                // Only show error if it wasn't an intentional close AND radio is not "None"
                if ($('.radios option:selected').val() != '0') {
                    var radioName = $('select.radios option:selected').text();
                    displayRadioStatus('error', radioName);
                }
                websocketEnabled = false;
            }
        };
        } catch (error) {
            websocketEnabled=false;
        }
    }

    /**
     * Handle incoming WebSocket data messages
     * Processes 'welcome' and 'radio_status' message types
     * @param {object} data - Message data from WebSocket server
     */
    function handleWebSocketData(data) {
        // Handle welcome message
        if (data.type === 'welcome') {
            return;
        }

        // Handle radio status updates
        if (data.type === 'radio_status' && data.radio && ($(".radios option:selected").val() == 'ws')) {
            data.updated_minutes_ago = Math.floor((Date.now() - data.timestamp) / 60000);
            // Cache the radio data
            updateCATui(data);
        }
    }

    /**
     * Update UI elements from CAT data
     * Maps CAT values to form fields, handling empty/zero values appropriately
     * @param {string} ui - jQuery selector for UI element
     * @param {*} cat - CAT data value to display
     * @param {boolean} allow_empty - Whether to update UI with empty values
     * @param {boolean} allow_zero - Whether to update UI with zero values
     * @param {function} callback_on_update - Optional callback when update occurs
     */
    const cat2UI = function(ui, cat, allow_empty, allow_zero, callback_on_update) {
        // Check, if cat-data is available
        if(cat == null) {
            return;
        } else if (typeof allow_empty !== 'undefined' && !allow_empty && cat == '') {
            return;
        } else if (typeof allow_zero !== 'undefined' && !allow_zero && cat == '0' ) {
            return;
        }

        // Don't update frequency field if user is currently editing it
        if (ui.attr('id') === 'frequency' || ui.attr('id') === 'freq_calculated') {
            if (ui.is(':focus') || $('#freq_calculated').is(':focus')) {
                return;
            }
        }

        // Only update the ui-element, if cat-data has changed
        if (ui.data('catValue') != cat) {
            ui.val(cat);
            ui.data('catValue',cat);
            if (typeof callback_on_update === 'function') { callback_on_update(cat); }
        }
    }

    /**
     * Format frequency for display based on user preference
     * @param {number} freq - Frequency in Hz
     * @returns {string|null} Formatted frequency with unit or null if invalid
     */
    function format_frequency(freq) {
        // Return null if frequency is invalid
        if (freq == null || freq == 0 || freq == '' || isNaN(freq)) {
            return null;
        }

        const qrgunit = localStorage.getItem('qrgunit_' + $('#band').val()) || 'kHz'; // Default to kHz if not set
        let frequency_formatted=null;
        if (qrgunit == 'Hz') {
            frequency_formatted=freq;
        } else if (qrgunit == 'kHz') {
            frequency_formatted=(freq / 1000);
        } else if (qrgunit == 'MHz') {
            frequency_formatted=(freq / 1000000);
        } else if (qrgunit == 'GHz') {
            frequency_formatted=(freq / 1000000000);
        }
        return frequency_formatted+''+qrgunit;
    }

    /**
     * Tune radio to a specific frequency and mode via CAT interface
     * @param {string} radioId - Radio ID (or 'ws' for WebSocket), optional - defaults to selectedRadioId
     * @param {number} freqHz - Frequency in Hz
     * @param {string} mode - Radio mode (e.g., 'usb', 'lsb', 'cw'), optional - auto-detects if not provided
     * @param {function} onSuccess - Optional callback called on successful tuning
     * @param {function} onError - Optional callback called on tuning error
     * @param {boolean} skipWaterfall - If true, skip DX Waterfall integration
     */
    window.tuneRadioToFrequency = function(radioId, freqHz, mode, onSuccess, onError, skipWaterfall) {
        // Default radioId to global selectedRadioId if not provided
        if (typeof radioId === 'undefined' || radioId === null || radioId === '') {
            radioId = selectedRadioId;
        }

        // Default mode to current mode if not provided
        if (typeof mode === 'undefined' || mode === null || mode === '') {
            mode = $('#mode').val() ? $('#mode').val().toLowerCase() : 'usb';
        } else {
            mode = mode.toLowerCase();
        }

        // Check if DX Waterfall is ACTIVE and should handle this (only if not called from within DX Waterfall)
        // DX Waterfall is active if dxWaterfall object exists AND has a canvas (meaning it's initialized)
        if (!skipWaterfall && typeof setFrequency === 'function' && typeof dxWaterfall !== 'undefined' && dxWaterfall.canvas) {
            const catAvailable = (typeof dxwaterfall_cat_state !== 'undefined' &&
                             (dxwaterfall_cat_state === 'polling' || dxwaterfall_cat_state === 'websocket'));

            if (catAvailable) {
                const freqKHz = freqHz / 1000;
                setFrequency(freqKHz, false); // false = not from DX Waterfall
                return;
            }
        }

        // Direct client-side radio control via CAT interface
        if (radioId && radioId != 0 && radioId != '') {
            // Get the CAT URL for the radio
            let catUrl;

            if (radioId === 'ws') {
                // WebSocket radio uses localhost gateway
                catUrl = 'http://127.0.0.1:54321';
            } else {
                // Check if CAT URL is cached
                if (radioCatUrlCache[radioId]) {
                    // Use cached CAT URL
                    catUrl = radioCatUrlCache[radioId];
                    performRadioTuning(catUrl, freqHz, mode, onSuccess, onError);
                    return;
                } else {
                    // Fetch CAT URL from radio data and cache it
                    $.ajax({
                        url: base_url + 'index.php/radio/json/' + radioId,
                        type: 'GET',
                        dataType: 'json',
                        timeout: CAT_CONFIG.AJAX_TIMEOUT_MS,
                        success: function(radioData) {
                            if (radioData.cat_url) {
                                // Cache the CAT URL and radio name for future use
                                radioCatUrlCache[radioId] = radioData.cat_url;
                                if (radioData.radio) {
                                    radioNameCache[radioId] = radioData.radio;
                                }
                                performRadioTuning(radioData.cat_url, freqHz, mode, onSuccess, onError);
                            } else {
                                if (typeof onError === 'function') {
                                    onError(null, 'error', lang_cat_no_url_configured);
                                }
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            if (typeof onError === 'function') {
                                onError(jqXHR, textStatus, errorThrown);
                            }
                        }
                    });
                    return; // Exit here for non-WebSocket radios
                }
            }

            // For WebSocket radios, tune immediately
            performRadioTuning(catUrl, freqHz, mode, onSuccess, onError);
        }
    };

    /**
     * Perform the actual radio tuning via CAT interface
     * Sends frequency and mode to radio via HTTP request
     * @param {string} catUrl - CAT interface URL for the radio
     * @param {number} freqHz - Frequency in Hz
     * @param {string} mode - Radio mode (validated against supported modes)
     * @param {function} onSuccess - Callback on successful tuning
     * @param {function} onError - Callback on tuning error
     */
    function performRadioTuning(catUrl, freqHz, mode, onSuccess, onError) {
        // Validate and normalize mode parameter
        const validModes = ['lsb', 'usb', 'cw', 'fm', 'am', 'rtty', 'pkt', 'dig', 'pktlsb', 'pktusb', 'pktfm'];
        const catMode = mode && validModes.includes(mode.toLowerCase()) ? mode.toLowerCase() : 'usb';

        // Format: {cat_url}/{frequency}/{mode}
        const url = catUrl + '/' + freqHz + '/' + catMode;

        // Make request with proper error handling
        fetch(url, {
            method: 'GET'
        })
        .then(response => {
            if (response.ok) {
                // Success - HTTP 200-299, get response text
                return response.text();
            } else {
                // HTTP error status (4xx, 5xx)
                throw new Error('HTTP ' + response.status);
            }
        })
        .then(data => {
            // Call success callback with response data
            if (typeof onSuccess === 'function') {
                onSuccess(data);
            }
        })
        .catch(error => {
            // Only show error on actual failures (network error, HTTP error, etc.)
            const freqMHz = (freqHz / 1000000).toFixed(3);
            const errorTitle = lang_cat_radio_tuning_failed;
            const errorMsg = lang_cat_failed_to_tune + ' ' + freqMHz + ' MHz (' + catMode.toUpperCase() + '). ' + lang_cat_not_responding;

            // Use showToast if available (from qso.js), otherwise use Bootstrap alert
            if (typeof showToast === 'function') {
                showToast(errorTitle, errorMsg, 'bg-danger text-white', 5000);
            }

            // Call error callback if provided
            if (typeof onError === 'function') {
                onError(null, 'error', error.message);
            }
        });
    }

    /**
     * Display radio status panel with unified styling
     * Handles success (active connection), error (connection lost), timeout (stale data), and not_logged_in states
     * Includes visual feedback with color-coded icon and blink animation on updates
     * @param {string} state - Display state: 'success', 'error', 'timeout', or 'not_logged_in'
     * @param {object|string} data - Radio data object (success) or radio name string (error/timeout/not_logged_in)
     */
    function displayRadioStatus(state, data) {
        var iconClass, content;
        var baseStyle = '<div style="display: flex; align-items: center; font-size: calc(1rem - 2px);">';

        if (state === 'success') {
            // Success state - display radio info
            iconClass = 'text-success'; // Bootstrap green for success

        // Determine connection type
        var connectionType = '';
        var connectionTooltip = '';
        if ($(".radios option:selected").val() == 'ws') {
            connectionType = ' (' + lang_cat_live + ')';
        } else {
            connectionType = ' (' + lang_cat_polling + ')';
            connectionTooltip = ' <i class="fas fa-question-circle" style="font-size: 0.9em; cursor: help;" data-bs-toggle="tooltip" title="' + lang_cat_polling_tooltip + '"></i>';
        }

        // Build radio info line
        var radioInfo = '';
        if (data.radio != null && data.radio != '') {
            radioInfo = '<b>' + data.radio + '</b>' + connectionType + connectionTooltip;
        }

        // Build frequency/mode/power line
        var freqLine = '';
        var separator = '   ';

        // Check if we have RX frequency (split operation)
        if(data.frequency_rx != null && data.frequency_rx != 0) {
            // Split operation: show TX and RX separately
            freqLine = '<b>' + lang_cat_tx + ':</b> ' + data.frequency_formatted;
            data.frequency_rx_formatted = format_frequency(data.frequency_rx);
            if (data.frequency_rx_formatted) {
                freqLine = freqLine + separator + '<b>' + lang_cat_rx + ':</b> ' + data.frequency_rx_formatted;
            }
        } else {
            // Simplex operation: show TX/RX combined
            freqLine = '<b>' + lang_cat_tx_rx + ':</b> ' + data.frequency_formatted;
        }

        // Add mode and power (only if we have valid frequency)
        if(data.mode != null) {
            freqLine = freqLine + separator + '<b>' + lang_cat_mode + ':</b> ' + data.mode;
        }
        if(data.power != null && data.power != 0) {
            freqLine = freqLine + separator + '<b>' + lang_cat_power + ':</b> ' + data.power+'W';
        }

        // Add complementary info to frequency line
        var complementary_info = [];
        if(data.prop_mode != null && data.prop_mode != '') {
            if (data.prop_mode == 'SAT') {
                complementary_info.push(data.prop_mode + ' ' + data.satname);
            } else {
                complementary_info.push(data.prop_mode);
            }
        }
        if(complementary_info.length > 0) {
            freqLine = freqLine + separator + '(' + complementary_info.join(separator) + ')';
        }

        // Combine radio info and frequency line into single line
        var infoLine = radioInfo;
        if (radioInfo && freqLine) {
            infoLine = infoLine + separator + freqLine;
        } else if (freqLine) {
            infoLine = freqLine;
        }

        content = '<div>' + infoLine + '</div>';

        } else if (state === 'error') {
            // Error state - WebSocket connection error
            iconClass = 'text-danger'; // Bootstrap red for error
            var radioName = typeof data === 'string' ? data : $('select.radios option:selected').text();
            content = '<div>' + lang_cat_connection_error + ': <b>' + radioName + '</b>. ' + lang_cat_connection_lost + '</div>';

        } else if (state === 'timeout') {
            // Timeout state - data too old
            iconClass = 'text-warning'; // Bootstrap yellow/amber for timeout
            var radioName = typeof data === 'string' ? data : $('select.radios option:selected').text();
            content = '<div>' + lang_cat_connection_timeout + ': <b>' + radioName + '</b>. ' + lang_cat_data_stale + '</div>';

        } else if (state === 'not_logged_in') {
            // Not logged in state
            iconClass = 'text-danger'; // Bootstrap red for error
            content = '<div>' + lang_cat_not_logged_in + '</div>';
        }

        // Build icon with Bootstrap color class and ID for animation
        var icon = '<i id="radio-status-icon" class="fas fa-radio ' + iconClass + '" style="margin-right: 10px; font-size: 1.2em;"></i>';
        var html = baseStyle + icon + content + '</div>';

    // Update DOM
    if (!$('#radio_cat_state').length) {
        // Create panel if it doesn't exist
        $('#radio_status').prepend('<div id="radio_cat_state" class="card"><div class="card-body">' + html + '</div></div>');
    } else {
        // Dispose of existing tooltips before updating content
        $('#radio_cat_state [data-bs-toggle="tooltip"]').each(function() {
            var tooltipInstance = bootstrap.Tooltip.getInstance(this);
            if (tooltipInstance) {
                tooltipInstance.dispose();
            }
        });
        // Update existing panel content
        $('#radio_cat_state .card-body').html(html);
    }			// Initialize Bootstrap tooltips for any new tooltip elements in the radio panel
        $('#radio_cat_state [data-bs-toggle="tooltip"]').each(function() {
            new bootstrap.Tooltip(this);
        });

        // Trigger blink animation on successful updates
        if (state === 'success') {
            $('#radio-status-icon').addClass('radio-icon-blink');
            setTimeout(function() {
                $('#radio-status-icon').removeClass('radio-icon-blink');
            }, 400);
        }
    }

    /**
     * Process CAT data and update UI elements
     * Performs timeout check, updates form fields, and displays radio status
     * Handles both WebSocket and polling data sources
     * @param {object} data - CAT data object from radio (includes frequency, mode, power, etc.)
     */
    function updateCATui(data) {
        // Check if data is too old FIRST - before any UI updates
        // cat_timeout_minutes is set in footer.php from PHP config
        var minutes = cat_timeout_minutes;

        if(data.updated_minutes_ago > minutes) {
            if (typeof dxwaterfall_cat_state !== 'undefined') {
                dxwaterfall_cat_state = "none";
            }

            // Display timeout error
            var radioName = $('select.radios option:selected').text();
            displayRadioStatus('timeout', radioName);
            return; // Exit early - do not update any fields with old data
        }

        // Update global CAT state FIRST before any UI updates
        // This allows DX Waterfall and other components to read radio state
        // without depending on form fields
        if (window.catState) {
            window.catState.frequency = data.frequency || null;
            window.catState.frequency_rx = data.frequency_rx || null;
            window.catState.mode = data.mode ? catmode(data.mode) : null;
            window.catState.lastUpdate = new Date();
        }

        // Cache frequently used DOM selectors
        var $frequency = $('#frequency');
        var $band = $('#band');
        var $frequencyRx = $('#frequency_rx');
        var $bandRx = $('#band_rx');
        var $mode = $('.mode');

        // If radio name is not in data, try to get it from cache first, then from dropdown
        if (!data.radio || data.radio == null || data.radio == '') {
            var currentRadioId = $('select.radios option:selected').val();
            if (currentRadioId && radioNameCache[currentRadioId]) {
                // Use cached radio name
                data.radio = radioNameCache[currentRadioId];
            } else if (currentRadioId == 'ws') {
                // WebSocket radio - use default name if not provided
                data.radio = lang_cat_websocket_radio;
            } else {
                // Fall back to dropdown text
                data.radio = $('select.radios option:selected').text();
            }
        }

        // Force update by clearing catValue (prevents cat2UI from blocking updates)
        $frequency.removeData('catValue');
        $mode.removeData('catValue'); // Also clear mode cache
        cat_updating_frequency = true; // Set flag before CAT update

        // Check if DX Waterfall's CAT frequency handler is available
        if (typeof handleCATFrequencyUpdate === 'function') {
            // DX Waterfall is active - use its debounce handler
            handleCATFrequencyUpdate(data.frequency, function() {
                cat2UI($frequency,data.frequency,false,true,function(d){
                    $frequency.trigger('change'); // Trigger for other event handlers
                    const newBand = frequencyToBand(d);
                    // Auto-update band based on frequency
                    if ($band.val() != newBand) {
                        $band.val(newBand).trigger('change'); // Trigger band change
                    }
                });
            });
        } else {
            // Standard frequency update (no DX Waterfall debounce handling)
            cat2UI($frequency,data.frequency,false,true,function(d){
                $frequency.trigger('change');
                // Auto-update band based on frequency
                if ($band.val() != frequencyToBand(d)) {
                    $band.val(frequencyToBand(d)).trigger('change');
                }
            });
        }

        cat2UI($frequencyRx,data.frequency_rx,false,true,function(d){$bandRx.val(frequencyToBand(d))});

        // If frequency_rx is not provided by radio, clear the field
        if (!data.frequency_rx || data.frequency_rx == 0 || data.frequency_rx == null) {
            if ($frequencyRx.val() != '' && $frequencyRx.val() != '0') {
                $frequencyRx.val('');
                $frequencyRx.removeData('catValue'); // Clear cache so cat2UI can update again
                $bandRx.val('');
            }
        }

        cat2UI($mode,catmode(data.mode),false,false,function(d){setRst($mode.val())});
        cat2UI($('#sat_name'),data.satname,false,false);
        cat2UI($('#sat_mode'),data.satmode,false,false);
        cat2UI($('#transmit_power'),data.power,false,false);
        cat2UI($('#selectPropagation'),data.prop_mode,false,false);

        // Clear the CAT updating flag AFTER all updates
        cat_updating_frequency = false;

        // Data is fresh (timeout check already passed at function start)
        // Set CAT state for waterfall if dxwaterfall_cat_state is available
        if (typeof dxwaterfall_cat_state !== 'undefined') {
            if ($(".radios option:selected").val() == 'ws') {
                dxwaterfall_cat_state = "websocket";
            } else {
                dxwaterfall_cat_state = "polling";
            }
        }

        // Format frequency for display
        separator = '<span style="margin-left:10px"></span>';

        // Format frequency - always recalculate if it contains 'null' (from previous invalid formatting)
        if (!(data.frequency_formatted) || (typeof data.frequency_formatted === 'string' && data.frequency_formatted.includes('null'))) {
            data.frequency_formatted=format_frequency(data.frequency);
        }

        // Only display radio info if we have valid frequency (not null and doesn't contain 'null' string)
        if (data.frequency_formatted && (typeof data.frequency_formatted !== 'string' || !data.frequency_formatted.includes('null'))) {
            // Display success status with radio data
            displayRadioStatus('success', data);
        } else {
            // No valid frequency - remove radio panel if it exists
            $('#radio_cat_state').remove();
        }
    }

    /**
     * Periodic AJAX polling function for radio status updates
     * Only runs for non-WebSocket radios (skips if radio is 'ws')
     * Fetches CAT data every 3 seconds and updates UI
     * Includes lock mechanism to prevent simultaneous requests
     */
    var updateFromCAT = function() {
        if ($('select.radios option:selected').val() != '0') {
            var radioID = $('select.radios option:selected').val();

            // Skip AJAX polling if radio is using WebSockets
            if (radioID == 'ws') {
                return;
            }

            if ((typeof radioID !== 'undefined') && (radioID !== null) && (radioID !== '') && (updateFromCAT_lock == 0)) {
                updateFromCAT_lock = 1;

                // Set timeout to release lock if AJAX fails
                if (updateFromCAT_lockTimeout) {
                    clearTimeout(updateFromCAT_lockTimeout);
                }
                updateFromCAT_lockTimeout = setTimeout(function() {
                    console.warn('CAT lock timeout - forcing release');
                    updateFromCAT_lock = 0;
                }, CAT_CONFIG.LOCK_TIMEOUT_MS);

                $.getJSON(base_url + 'index.php/radio/json/' + radioID, function(data) {
                    if (data.error) {
                        if (data.error == 'not_logged_in') {
                            // Use dedicated not_logged_in state
                            displayRadioStatus('not_logged_in');
                        } else {
                            // Other errors - generic error state
                            var radioName = $('select.radios option:selected').text();
                            displayRadioStatus('error', radioName);
                        }
                    } else {
                        // Update CAT UI with received data
                        updateCATui(data);
                    }

                    // Clear lock timeout and release lock
                    if (updateFromCAT_lockTimeout) {
                        clearTimeout(updateFromCAT_lockTimeout);
                        updateFromCAT_lockTimeout = null;
                    }
                    updateFromCAT_lock = 0;
                    }).fail(function() {
                        // Release lock on AJAX failure (silently - don't show error)
                        if (updateFromCAT_lockTimeout) {
                            clearTimeout(updateFromCAT_lockTimeout);
                            updateFromCAT_lockTimeout = null;
                        }
                        updateFromCAT_lock = 0;
                    });
            }
        }
    };

    /******************************************************************************
     * RADIO CAT INITIALIZATION AND EVENT HANDLERS
     ******************************************************************************/

    // Initialize DX_WATERFALL_CONSTANTS CAT timings based on poll interval
    // Only call if the function exists (DX Waterfall is loaded)
    if (typeof initCATTimings === 'function') {
        initCATTimings(CAT_CONFIG.POLL_INTERVAL);
    }

    /**
     * Radio selection change handler
     * Cleans up previous connection (WebSocket or polling) and initializes new one
     * Clears caches, stops timers, closes connections, and starts appropriate connection type
     */
    $('.radios').change(function() {
        // Update global selected radio variable
        selectedRadioId = $('.radios option:selected').val();

        // Clear both caches when radio changes
        radioCatUrlCache = {};
        radioNameCache = {};

        // Hide radio status box (both success and error states)
        $('#radio_cat_state').remove();

        if (CATInterval) {	// We've a change - stop polling if active
            clearInterval(CATInterval);
            CATInterval=null;
        }
        if (websocket) {	// close possible websocket connection
            websocketIntentionallyClosed = true; // Mark as intentional close to prevent auto-reconnect
            websocket.close();
            websocketEnabled = false;
        }
        if (selectedRadioId == '0') {
            $('#sat_name').val('');
            $('#sat_mode').val('');
            $('#frequency').val('');
            $('#frequency_rx').val('');
            $('#band_rx').val('');
            $('#selectPropagation').val($('#selectPropagation option:first').val());
            // Set DX Waterfall CAT state to none if variable exists
            if (typeof dxwaterfall_cat_state !== 'undefined') {
                dxwaterfall_cat_state = "none";
            }
        } else if (selectedRadioId == 'ws') {
            websocketIntentionallyClosed = false; // Reset flag when opening WebSocket
            reconnectAttempts = 0; // Reset reconnect attempts
            // Set DX Waterfall CAT state to websocket if variable exists
            if (typeof dxwaterfall_cat_state !== 'undefined') {
                dxwaterfall_cat_state = "websocket";
            }
            initializeWebSocketConnection();
        } else {
            // Set DX Waterfall CAT state to polling if variable exists
            if (typeof dxwaterfall_cat_state !== 'undefined') {
                dxwaterfall_cat_state = "polling";
            }
            // Update frequency at configured interval
            CATInterval=setInterval(updateFromCAT, CAT_CONFIG.POLL_INTERVAL);
        }
    });

    // Trigger initial radio change to start monitoring selected radio
    $('.radios').change();
});
