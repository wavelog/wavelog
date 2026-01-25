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
    let hasTriedWsFallback = false; // Track if we've already tried WS fallback after WSS failed
    let activeWebSocketProtocol = 'wss'; // Track which protocol is currently active ('wss' or 'ws')
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

    // Global setting for radio status display mode (can be set by pages like bandmap)
    // Options: false (card wrapper), 'compact' (no card), 'ultra-compact' (icon+name+tooltip), 'icon-only' (icon+tooltip)
    window.CAT_COMPACT_MODE = window.CAT_COMPACT_MODE || false;

    /**
     * Safely dispose of a Bootstrap tooltip without triggering _isWithActiveTrigger errors
     * This works around a known Bootstrap bug where disposing during hide animation causes errors
     * @param {Element} element - The DOM element with the tooltip
     */
    function safeDisposeTooltip(element) {
        try {
            var tooltipInstance = bootstrap.Tooltip.getInstance(element);
            if (tooltipInstance) {
                // Set _activeTrigger to empty object to prevent _isWithActiveTrigger error
                if (tooltipInstance._activeTrigger) {
                    tooltipInstance._activeTrigger = {};
                }
                // Clear any pending timeouts
                if (tooltipInstance._timeout) {
                    clearTimeout(tooltipInstance._timeout);
                    tooltipInstance._timeout = null;
                }
                // Dispose without calling hide first
                tooltipInstance.dispose();
            }
        } catch(e) {
            // Silently ignore any remaining errors
        }
    }
    // Expose globally for other modules
    window.safeDisposeTooltip = safeDisposeTooltip;

    function initializeWebSocketConnection() {
        try {
            // Determine which protocol and port to use
            // Try WSS on port 54323 first, fall back to WS on port 54322
            const tryWss = !hasTriedWsFallback;
            const protocol = tryWss ? 'wss' : 'ws';
            const port = tryWss ? '54323' : '54322';
            const wsUrl = protocol + '://127.0.0.1:' + port;

            // Note: Browser will log WebSocket connection errors to console if server is unreachable
            // This is native browser behavior and cannot be suppressed - errors are handled in GUI via onerror handler
            websocket = new WebSocket(wsUrl);

            websocket.onopen = function(event) {
                reconnectAttempts = 0;
                websocketEnabled = true;
                activeWebSocketProtocol = protocol; // Remember which protocol worked
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
                // If WSS failed and we haven't tried WS fallback yet, try WS
                if (tryWss && !hasTriedWsFallback) {
                    hasTriedWsFallback = true;
                    // Close current connection (which failed anyway) and retry with WS
                    if (websocket && websocket.readyState === WebSocket.CONNECTING) {
                        websocket.close();
                    }
                    // Schedule reconnection with WS
                    setTimeout(() => {
                        initializeWebSocketConnection();
                    }, 100); // Short delay before retry
                    return;
                }

                // Original error handling for when both protocols have been tried
                if ($('.radios option:selected').val() != '0') {
                    var radioName = $('select.radios option:selected').text();
                    displayRadioStatus('error', radioName);
                }
                websocketEnabled = false;
            };

        websocket.onclose = function(event) {
            websocketEnabled = false;

            // Reset fallback flag on intentional close so we try WSS first next time
            if (websocketIntentionallyClosed) {
                hasTriedWsFallback = false;
            }

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
     * On bandmap, only processes radio status when CAT Control is enabled
     * @param {object} data - Message data from WebSocket server
     */
    function handleWebSocketData(data) {
        // Handle welcome message
        if (data.type === 'welcome') {
            return;
        }

        // Handle radio status updates
        if (data.type === 'radio_status' && data.radio && ($(".radios option:selected").val() == 'ws')) {
            // On bandmap page, check CAT Control state
            if (typeof window.isCatTrackingEnabled !== 'undefined') {
                if (!window.isCatTrackingEnabled) {
                    // CAT Control is OFF - show offline status and skip processing
                    if (window.CAT_COMPACT_MODE === 'ultra-compact' || window.CAT_COMPACT_MODE === 'icon-only') {
                        displayOfflineStatus('cat_disabled');
                    }
                    return;
                }
            }

            // Calculate age from timestamp, defaulting to 0 (fresh) if timestamp is missing
            if (data.timestamp) {
                data.updated_minutes_ago = Math.floor((Date.now() - data.timestamp) / 60000);
            } else {
                data.updated_minutes_ago = 0; // Assume fresh if no timestamp
            }
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
     * Sends frequency and mode to radio via HTTP/HTTPS request with failover
     * Tries HTTPS first, falls back to HTTP on failure
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

        // Determine which protocol to try first
        // If URL is already HTTPS, use it. If HTTP, upgrade to HTTPS for first attempt.
        const isHttps = catUrl.startsWith('https://');
        const httpsUrl = isHttps ? catUrl : catUrl.replace(/^http:\/\//, 'https://');
        const httpUrl = isHttps ? catUrl.replace(/^https:\/\//, 'http://') : catUrl;

        // Build the full URLs with frequency and mode
        const httpsRequestUrl = httpsUrl + '/' + freqHz + '/' + catMode;
        const httpRequestUrl = httpUrl + '/' + freqHz + '/' + catMode;

        // Try HTTPS first (unless original URL was already HTTPS, then just try that)
        const tryHttps = !isHttps;

        // Function to attempt tuning with a specific URL
        const tryTuning = function(url, isFallback) {
            return fetch(url, {
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
                return data;
            });
        };

        // Execute failover logic: try HTTPS first, then HTTP
        const primaryUrl = tryHttps ? httpsRequestUrl : httpRequestUrl;
        const fallbackUrl = tryHttps ? httpRequestUrl : null;

        tryTuning(primaryUrl, false)
            .catch(error => {
                // If HTTPS was attempted and failed, try HTTP fallback
                if (fallbackUrl !== null) {
                    return tryTuning(fallbackUrl, true)
                        .catch(fallbackError => {
                            // Both HTTPS and HTTP failed
                            throw fallbackError;
                        });
                }
                // No fallback available (was already HTTPS or only one URL to try)
                throw error;
            })
            .catch(error => {
                // All attempts failed - show error
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
     * Display radio status panel with current CAT information
     * Creates or updates a Bootstrap card panel showing radio connection status and frequency data
     * Includes visual feedback with color-coded icon and blink animation on updates
     * Respects global CAT_COMPACT_MODE setting for rendering style
     * @param {string} state - Display state: 'success', 'error', 'timeout', or 'not_logged_in'
     * @param {object|string} data - Radio data object (success) or radio name string (error/timeout/not_logged_in)
     * CAT_COMPACT_MODE options:
     *   false - Standard mode with card wrapper
     *   'compact' - Compact mode without card wrapper
     *   'ultra-compact' - Ultra-compact mode showing only tooltip with info
     * @param {string} reason - Optional reason: 'no_radio' (default) or 'cat_disabled'
     */
    function displayOfflineStatus(reason) {
        // Display "Working offline" message with tooltip in ultra-compact/icon-only modes
        if (window.CAT_COMPACT_MODE !== 'ultra-compact' && window.CAT_COMPACT_MODE !== 'icon-only') {
            return;
        }

        // Default to 'no_radio' for backward compatibility
        reason = reason || 'no_radio';

        // Use translation variable if available, fallback to English
        var offlineText = typeof lang_cat_working_offline !== 'undefined' ? lang_cat_working_offline : 'Working without CAT connection';

        var offlineHtml;
        if (window.CAT_COMPACT_MODE === 'icon-only') {
            // Icon-only mode: just the icon with tooltip, styled as button for consistent height
            offlineHtml = '<span id="radio_cat_state" class="btn btn-sm btn-secondary" style="display: inline-flex; align-items: center; justify-content: center; cursor: help;">' +
                         '<i id="radio-status-icon" class="fas fa-unlink text-warning" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="bottom"></i>' +
                         '</span>';
        } else {
            // Ultra-compact mode: icon + text + info icon
            offlineHtml = '<span id="radio_cat_state" class="text-body" style="display: inline-flex; align-items: center; font-size: 0.875rem;">' +
                         '<i class="fas fa-unlink text-warning" style="margin-right: 5px;"></i>' +
                         '<span style="margin-right: 5px;">' + offlineText + '</span>' +
                         '<i id="radio-status-icon" class="fas fa-info-circle text-muted" style="cursor: help;" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="bottom"></i>' +
                         '</span>';
        }

        let tooltipContent;
        if (reason === 'cat_disabled') {
            // Use translation variable if available, fallback to English
            tooltipContent = typeof lang_cat_offline_cat_disabled !== 'undefined'
                ? lang_cat_offline_cat_disabled
                : 'CAT connection is currently disabled. Enable CAT connection to work in online mode with your radio.';
        } else {
            // reason === 'no_radio' (default)
            tooltipContent = typeof lang_cat_offline_no_radio !== 'undefined'
                ? lang_cat_offline_no_radio
                : 'To connect your radio to Wavelog, visit the Wavelog Wiki for setup instructions.';
        }

        // Remove existing radio status if present
        $('#radio_cat_state').remove();

        // Add offline status
        $('#radio_status').append(offlineHtml);

        // Initialize tooltip
        var tooltipElement = document.querySelector('#radio_status [data-bs-toggle="tooltip"]');
        if (tooltipElement) {
            try {
                new bootstrap.Tooltip(tooltipElement, {
                    title: tooltipContent,
                    html: true,
                    placement: 'bottom'
                });
            } catch(e) {
                // Ignore tooltip initialization errors
            }
        }
    }

    /**
     * Send QSO data via WebSocket when CAT is enabled via WebSocket
     * This allows external systems (e.g., WLGate, external logging services) to receive logged QSOs in real-time
     * @param {object} qsoData - QSO data object with fields from the QSO form
     * @returns {boolean} - true if sent via WebSocket, false otherwise
     */
    function sendQSOViaWebSocket(qsoData) {
        // Only send if WebSocket is connected and enabled
        if (!websocket || !websocketEnabled || websocket.readyState !== WebSocket.OPEN) {
            return false;
        }

        // Only send for WebSocket radio ('ws')
        if ($(".radios option:selected").val() != 'ws') {
            return false;
        }

        try {
            // Prepare QSO message with standard format
            const qsoMessage = {
                type: 'qso_logged',
                timestamp: new Date().toISOString(),
                data: qsoData
            };

            // Send via WebSocket
            websocket.send(JSON.stringify(qsoMessage));
            return true;
        } catch (error) {
            console.warn('Failed to send QSO via WebSocket:', error);
            return false;
        }
    }

    // Expose sendQSOViaWebSocket globally so it can be called from qso.js
    window.sendQSOViaWebSocket = sendQSOViaWebSocket;

    /**
     * Send real-time satellite position (azimuth/elevation) via WebSocket
     * Only sends when using WebSocket CAT and working satellite
     * @param {string} satName - Satellite name
     * @param {number} azimuth - Antenna azimuth in decimal degrees
     * @param {number} elevation - Antenna elevation in decimal degrees
     * @returns {boolean} - true if sent via WebSocket, false otherwise
     */
    function sendSatellitePositionViaWebSocket(satName, azimuth, elevation) {
        // Only send if WebSocket is connected and enabled
        if (!websocket || !websocketEnabled || websocket.readyState !== WebSocket.OPEN) {
            return false;
        }

        // Only send for WebSocket radio ('ws')
        if ($(".radios option:selected").val() != 'ws') {
            return false;
        }

        // Only send if satellite name is provided
        if (!satName || satName === '') {
            return false;
        }

        try {
            // Prepare satellite position message with standard format
            const satMessage = {
                type: 'satellite_position',
                timestamp: new Date().toISOString(),
                data: {
                    sat_name: satName,
                    azimuth: azimuth,
                    elevation: elevation
                }
            };

            // Send via WebSocket
            websocket.send(JSON.stringify(satMessage));
            return true;
        } catch (error) {
            console.warn('Failed to send satellite position via WebSocket:', error);
            return false;
        }
    }

    // Expose sendSatellitePositionViaWebSocket globally so it can be called from qso.js
    window.sendSatellitePositionViaWebSocket = sendSatellitePositionViaWebSocket;

    /**
     * Display radio status in the UI
     * @param {string} state - One of 'success', 'error', 'timeout', 'not_logged_in'
     * @param {object|string} data - Radio data object (success) or radio name string (error/timeout/not_logged_in)
     * CAT_COMPACT_MODE options:
     *   false - Standard mode with card wrapper
     *   'compact' - Compact mode without card wrapper
     *   'ultra-compact' - Ultra-compact mode showing icon, radio name, and tooltip
     *   'icon-only' - Icon-only mode showing just icon with tooltip (for bandmap)
     */
    function displayRadioStatus(state, data) {
        // On bandmap page, only show radio status when CAT Control is enabled
        if (typeof window.isCatTrackingEnabled !== 'undefined') {
            if (!window.isCatTrackingEnabled) {
                // CAT Control is OFF on bandmap
                // In ultra-compact/icon-only mode, show "Working offline" with CAT disabled message
                if (window.CAT_COMPACT_MODE === 'ultra-compact' || window.CAT_COMPACT_MODE === 'icon-only') {
                    // Check if a radio is selected
                    var selectedRadio = $('.radios option:selected').val();
                    if (selectedRadio && selectedRadio !== '0') {
                        // Radio selected but CAT disabled
                        displayOfflineStatus('cat_disabled');
                        return;
                    }
                }
                // Standard behavior: remove radio status
                $('#radio_cat_state').remove();
                return;
            }
        }

        var iconClass, content;
        var baseStyle = '<div style="display: flex; align-items: center; font-size: calc(1rem - 2px);">';        if (state === 'success') {
            // Success state - display radio info
            iconClass = 'text-success'; // Bootstrap green for success

        // Determine connection type
        var connectionType = '';
        var connectionTooltip = '';
        if ($(".radios option:selected").val() == 'ws') {
            connectionType = ' (' + lang_cat_live + ')';
        } else {
            connectionType = ' (' + lang_cat_polling + ')';
            connectionTooltip = ' <span class="fas fa-question-circle" style="font-size: 0.9em; cursor: help;" data-bs-toggle="tooltip" title="' + lang_cat_polling_tooltip + '"></span>';
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
	if (($(".radios option:selected").val() == 'ws') && (data.radio != undefined)) {	// Are we on websocket? add hiddenfield with radioName
		$("#radio_ws_name").val(data.radio);
	} else {
		$("#radio_ws_name").val('');
	}

		// Update DOM based on global CAT_COMPACT_MODE setting
		if (window.CAT_COMPACT_MODE === 'icon-only') {
			// Icon-only mode: show just radio icon with tooltip containing all info
			var tooltipContent = '';

			if (state === 'success') {
				var radioName = $('select.radios option:selected').text();
				var connectionType = $(".radios option:selected").val() == 'ws' ? lang_cat_live : lang_cat_polling;
				tooltipContent = '<b>' + radioName + '</b> (' + connectionType + ')';

				// Ensure frequency_formatted exists
				var freqFormatted = data.frequency_formatted;
				if (!freqFormatted || freqFormatted === 'undefined' || freqFormatted === 'nullkHz') {
					freqFormatted = format_frequency(data.frequency);
				}

				// Add frequency info
				if(data.frequency_rx && data.frequency_rx != 0 && data.frequency_rx !== 'undefined') {
					// Split operation: show TX and RX separately
					if (freqFormatted && freqFormatted !== 'undefined') {
						tooltipContent += '<br><b>' + lang_cat_tx + ':</b> ' + freqFormatted;
					}
					var rxFormatted = format_frequency(data.frequency_rx);
					if (rxFormatted && rxFormatted !== 'undefined') {
						tooltipContent += '<br><b>' + lang_cat_rx + ':</b> ' + rxFormatted;
					}
				} else {
					// Simplex operation: show TX/RX combined
					if (freqFormatted && freqFormatted !== 'undefined') {
						tooltipContent += '<br><b>' + lang_cat_tx_rx + ':</b> ' + freqFormatted;
					}
				}

				if(data.mode != null) {
					tooltipContent += '<br><b>' + lang_cat_mode + ':</b> ' + data.mode;
				}
				if(data.power != null && data.power != 0) {
					tooltipContent += '<br><b>' + lang_cat_power + ':</b> ' + data.power + 'W';
				}
				if ($(".radios option:selected").val() != 'ws') {
					tooltipContent += '<br><br><i>' + lang_cat_polling_tooltip + '</i>';
				}
			} else if (state === 'error') {
				var radioName = typeof data === 'string' ? data : $('select.radios option:selected').text();
				tooltipContent = lang_cat_connection_error + ': <b>' + radioName + '</b><br>' + lang_cat_connection_lost;
			} else if (state === 'timeout') {
				var radioName = typeof data === 'string' ? data : $('select.radios option:selected').text();
				tooltipContent = lang_cat_connection_timeout + ': <b>' + radioName + '</b><br>' + lang_cat_data_stale;
			} else if (state === 'not_logged_in') {
				tooltipContent = lang_cat_not_logged_in;
			}

			var iconOnlyHtml = '<span id="radio_cat_state" class="btn btn-sm btn-secondary" style="display: inline-flex; align-items: center; justify-content: center; cursor: help;">' +
							  '<i id="radio-status-icon" class="fas fa-radio ' + iconClass + '" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="bottom"></i>' +
							  '</span>';

			if (!$('#radio_cat_state').length) {
				$('#radio_status').append(iconOnlyHtml);
			} else {
				$('#radio_cat_state [data-bs-toggle="tooltip"]').each(function() {
					safeDisposeTooltip(this);
				});
				$('#radio_cat_state').replaceWith(iconOnlyHtml);
			}

			var tooltipElement = document.querySelector('#radio_status [data-bs-toggle="tooltip"]');
			if (tooltipElement) {
				try {
					new bootstrap.Tooltip(tooltipElement, {
						title: tooltipContent,
						html: true,
						placement: 'bottom'
					});
				} catch(e) {
					// Ignore tooltip initialization errors
				}
			}

			// Add blink animation on update
			$('#radio_status .fa-radio').addClass('blink-once');
			setTimeout(function() {
				$('#radio_status .fa-radio').removeClass('blink-once');
			}, 600);

		} else if (window.CAT_COMPACT_MODE === 'ultra-compact') {
			// Ultra-compact mode: show radio icon, radio name, and question mark with tooltip
			var tooltipContent = '';
			var radioName = '';

		if (state === 'success') {
			// Build tooltip content with all radio information
			// Use the full dropdown text (includes "Polling - " and "(last updated)" etc.)
			radioName = $('select.radios option:selected').text();
			var connectionType = '';
			if ($(".radios option:selected").val() == 'ws') {
				connectionType = lang_cat_live;
			} else {
				connectionType = lang_cat_polling;
			}
			tooltipContent = '<b>' + radioName + '</b> (' + connectionType + ')';

				// Ensure frequency_formatted exists
				var freqFormatted = data.frequency_formatted;
				if (!freqFormatted || freqFormatted === 'undefined' || freqFormatted === 'nullkHz') {
					freqFormatted = format_frequency(data.frequency);
				}

				// Add frequency info
				if(data.frequency_rx && data.frequency_rx != 0 && data.frequency_rx !== 'undefined') {
					// Split operation: show TX and RX separately
					if (freqFormatted && freqFormatted !== 'undefined') {
						tooltipContent += '<br><b>' + lang_cat_tx + ':</b> ' + freqFormatted;
					}
					var rxFormatted = format_frequency(data.frequency_rx);
					if (rxFormatted && rxFormatted !== 'undefined') {
						tooltipContent += '<br><b>' + lang_cat_rx + ':</b> ' + rxFormatted;
					}
				} else {
					// Simplex operation: show TX/RX combined
					if (freqFormatted && freqFormatted !== 'undefined') {
						tooltipContent += '<br><b>' + lang_cat_tx_rx + ':</b> ' + freqFormatted;
					}
				}

				// Add mode
				if(data.mode != null) {
					tooltipContent += '<br><b>' + lang_cat_mode + ':</b> ' + data.mode;
				}

				// Add power
				if(data.power != null && data.power != 0) {
					tooltipContent += '<br><b>' + lang_cat_power + ':</b> ' + data.power + 'W';
				}

				// Add polling tooltip if applicable
				if ($(".radios option:selected").val() != 'ws') {
					tooltipContent += '<br><br><i>' + lang_cat_polling_tooltip + '</i>';
				}
			} else if (state === 'error') {
				radioName = typeof data === 'string' ? data : $('select.radios option:selected').text();
				tooltipContent = lang_cat_connection_error + ': <b>' + radioName + '</b><br>' + lang_cat_connection_lost;
			} else if (state === 'timeout') {
				radioName = typeof data === 'string' ? data : $('select.radios option:selected').text();
				tooltipContent = lang_cat_connection_timeout + ': <b>' + radioName + '</b><br>' + lang_cat_data_stale;
			} else if (state === 'not_logged_in') {
				radioName = '';
				tooltipContent = lang_cat_not_logged_in;
			}

			var ultraCompactHtml = '<span id="radio_cat_state" style="display: inline-flex; align-items: center; font-size: 0.875rem;">' +
								  '<i class="fas fa-radio ' + iconClass + '" style="margin-right: 5px;"></i>' +
								  '<span style="margin-right: 5px;">' + radioName + '</span>' +
								  '<i id="radio-status-icon" class="fas fa-info-circle" style="cursor: help;" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="bottom"></i>' +
								  '</span>';

			if (!$('#radio_cat_state').length) {
				$('#radio_status').append(ultraCompactHtml);
			} else {
				// Dispose of existing tooltips before updating content
				$('#radio_cat_state [data-bs-toggle="tooltip"]').each(function() {
					safeDisposeTooltip(this);
				});
				$('#radio_cat_state').replaceWith(ultraCompactHtml);
			}

			// Initialize tooltip with dynamic content
			var tooltipElement = document.querySelector('#radio_status [data-bs-toggle="tooltip"]');
			if (tooltipElement) {
				try {
					new bootstrap.Tooltip(tooltipElement, {
						title: tooltipContent,
						html: true,
						placement: 'bottom'
					});
				} catch(e) {
					// Ignore tooltip initialization errors
				}
			}

			// Add blink animation to radio icon on update
			$('#radio_status .fa-radio').addClass('blink-once');
			setTimeout(function() {
				$('#radio_status .fa-radio').removeClass('blink-once');
			}, 600);


		} else if (window.CAT_COMPACT_MODE === 'compact' || window.CAT_COMPACT_MODE === true) {
			// Compact mode: inject directly without card wrapper
			if (!$('#radio_cat_state').length) {
				$('#radio_status').prepend('<div id="radio_cat_state">' + html + '</div>');
			} else {
				// Dispose of existing tooltips before updating content
				$('#radio_cat_state [data-bs-toggle="tooltip"]').each(function() {
					safeDisposeTooltip(this);
				});
				$('#radio_cat_state').html(html);
			}
		} else {
			// Standard mode: create card wrapper (default for backward compatibility)
			if (!$('#radio_cat_state').length) {
				// Create panel if it doesn't exist
				$('#radio_status').prepend('<div id="radio_cat_state" class="card"><div class="card-body">' + html + '</div></div>');
			} else {
				// Dispose of existing tooltips before updating content
				$('#radio_cat_state [data-bs-toggle="tooltip"]').each(function() {
					safeDisposeTooltip(this);
				});
				// Update existing panel content
				$('#radio_cat_state .card-body').html(html);
			}
		}

		// Initialize Bootstrap tooltips for any new tooltip elements in the radio panel (except ultra-compact/icon-only which handle their own)
		if (window.CAT_COMPACT_MODE !== 'ultra-compact' && window.CAT_COMPACT_MODE !== 'icon-only') {
			$('#radio_cat_state [data-bs-toggle="tooltip"]').each(function() {
				new bootstrap.Tooltip(this);
			});
		}

		// Trigger blink animation on successful updates
		if (state === 'success') {
			$('#radio-status-icon').addClass('radio-icon-blink');
			setTimeout(function() {
				$('#radio-status-icon').removeClass('radio-icon-blink');
			}, 400);
		}
	}

	// Expose displayRadioStatus globally for bandmap and other components
	window.displayRadioStatus = displayRadioStatus;

    /**
     * Process CAT data and update UI elements
     * Performs timeout check, updates form fields, and displays radio status
     * Handles both WebSocket and polling data sources
     * Exposed globally for extension by other components (e.g., bandmap)
     * @param {object} data - CAT data object from radio (includes frequency, mode, power, etc.)
     */
    window.updateCATui = function updateCATui(data) {
        // Store last CAT data globally for other components (e.g., bandmap)
        window.lastCATData = data;

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

        // Validate that we have frequency data before proceeding
        if (!data.frequency || data.frequency == 0 || data.frequency == null) {
            console.warn('CAT: No valid frequency data received');
            return;
        }

        // Force update by clearing catValue (prevents cat2UI from blocking updates)
        $frequency.removeData('catValue');
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
                        // Update callsign status when band changes via CAT
                        if ($('#callsign').val().length >= 3) {
                            $('#callsign').blur();
                        }
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
                    // Update callsign status when band changes via CAT
                    if ($('#callsign').val().length >= 3) {
                        $('#callsign').blur();
                    }
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

        // Track previous mode to detect changes
        var previousMode = $mode.data('catValue');
        var newMode = catmode(data.mode);

        // Only refresh waterfall if mode actually changed (and both values are defined)
        var modeChanged = previousMode && previousMode !== newMode;

        cat2UI($mode,newMode,false,false);

        // Update RST fields when mode changes
        // Check if mode was actually updated (catValue changed after cat2UI call)
        var currentMode = $mode.data('catValue');
        if (currentMode !== previousMode && typeof setRst === 'function') {
            setRst(newMode);
            // Update callsign status when mode changes via CAT
            if ($('#callsign').val().length >= 3) {
                $('#callsign').blur();
            }
        }

        // Notify DX Waterfall of mode change for sideband display update
        // Only refresh if mode actually changed AND waterfall is active (has canvas)
        if (modeChanged && typeof dxWaterfall !== 'undefined' && dxWaterfall.canvas && dxWaterfall.refresh) {
            // Update virtual CAT state
            if (typeof window.catState !== 'undefined' && window.catState !== null) {
                window.catState.mode = newMode;
            }
            // Refresh waterfall to update bandwidth indicator
            dxWaterfall.refresh();
        }

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
     * On bandmap, only polls when CAT Control is enabled
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

            // On bandmap page, only poll when CAT Control is enabled
            if (typeof window.isCatTrackingEnabled !== 'undefined') {
                if (!window.isCatTrackingEnabled) {
                    return; // Skip polling when CAT Control is OFF
                }
            }

            if ((typeof radioID !== 'undefined') && (radioID !== null) && (radioID !== '') && (updateFromCAT_lock == 0)) {
                updateFromCAT_lock = 1;

                // Set timeout to release lock if AJAX fails
                if (updateFromCAT_lockTimeout) {
                    clearTimeout(updateFromCAT_lockTimeout);
                }
                updateFromCAT_lockTimeout = setTimeout(function() {
                    // Lock timeout - force release after 10 seconds
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

        // If switching to None, disable CAT tracking FIRST before stopping connections
        // This prevents any pending updates from interfering with the offline status
        if (selectedRadioId == '0') {
            if (typeof window.isCatTrackingEnabled !== 'undefined') {
                window.isCatTrackingEnabled = false;
            }
        }

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
            if (typeof set_new_qrg === 'function') {
                set_new_qrg();
            }
            $('#selectPropagation').val($('#selectPropagation option:first').val());
            // Set DX Waterfall CAT state to none if variable exists
            if (typeof dxwaterfall_cat_state !== 'undefined') {
                dxwaterfall_cat_state = "none";
            }
            // Disable CAT Connection button when no radio is selected
            $('#toggleCatTracking').prop('disabled', true).addClass('disabled');
            // Also turn OFF CAT Connection (remove green button state)
            $('#toggleCatTracking').removeClass('btn-success').addClass('btn-secondary');
            // Display offline status when no radio selected
            displayOfflineStatus('no_radio');
        } else if (selectedRadioId == 'ws') {
            websocketIntentionallyClosed = false; // Reset flag when opening WebSocket
            reconnectAttempts = 0; // Reset reconnect attempts
            hasTriedWsFallback = false; // Reset WSS failover state - try WSS first again
            // Set DX Waterfall CAT state to websocket if variable exists
            if (typeof dxwaterfall_cat_state !== 'undefined') {
                dxwaterfall_cat_state = "websocket";
            }
            // Enable CAT Control button when radio is selected
            $('#toggleCatTracking').prop('disabled', false).removeClass('disabled');
            // Always initialize WebSocket connection
            initializeWebSocketConnection();
            // In ultra-compact/icon-only mode, show offline status if CAT Control is disabled
            if ((window.CAT_COMPACT_MODE === 'ultra-compact' || window.CAT_COMPACT_MODE === 'icon-only') && typeof window.isCatTrackingEnabled !== 'undefined' && !window.isCatTrackingEnabled) {
                displayOfflineStatus('cat_disabled');
            }
        } else {
            // Set DX Waterfall CAT state to polling if variable exists
            if (typeof dxwaterfall_cat_state !== 'undefined') {
                dxwaterfall_cat_state = "polling";
            }
            // Enable CAT Control button when radio is selected
            $('#toggleCatTracking').prop('disabled', false).removeClass('disabled');
            // Always start polling
            CATInterval=setInterval(updateFromCAT, CAT_CONFIG.POLL_INTERVAL);
            // In ultra-compact/icon-only mode, show offline status if CAT Control is disabled
            if ((window.CAT_COMPACT_MODE === 'ultra-compact' || window.CAT_COMPACT_MODE === 'icon-only') && typeof window.isCatTrackingEnabled !== 'undefined' && !window.isCatTrackingEnabled) {
                displayOfflineStatus('cat_disabled');
            }
        }
    });

    // Trigger initial radio change to start monitoring selected radio
    $('.radios').change();

    // Expose displayOfflineStatus globally for other components (e.g., bandmap CAT Control toggle)
    window.displayOfflineStatus = displayOfflineStatus;
});
