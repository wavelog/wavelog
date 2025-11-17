/**
 * DX Cluster Bandmap - Real-time spot filtering with CAT control
 *
 * Features:
 * - Smart filtering (server for bands/continents, client for modes/flags)
 * - CAT radio integration with frequency visualization
 * - Multi-select filters with status color coding
 * - Auto-refresh with activity references (POTA/SOTA/WWFF/IOTA/Contest)
 * - Works with radiohelpers.js for mode/band/frequency utilities
 */

'use strict';

// ========================================
// CONFIGURATION & CONSTANTS
// ========================================

const SPOT_REFRESH_INTERVAL = 60;  // Auto-refresh interval in seconds

// Mode display capitalization lookup (API returns lowercase)
const MODE_CAPITALIZATION = { 'phone': 'Phone', 'cw': 'CW', 'digi': 'Digi' };

// Filter button configurations
const BAND_BUTTONS = [
	{ id: '#toggle160mFilter', band: '160m' },
	{ id: '#toggle80mFilter', band: '80m' },
	{ id: '#toggle60mFilter', band: '60m' },
	{ id: '#toggle40mFilter', band: '40m' },
	{ id: '#toggle30mFilter', band: '30m' },
	{ id: '#toggle20mFilter', band: '20m' },
	{ id: '#toggle17mFilter', band: '17m' },
	{ id: '#toggle15mFilter', band: '15m' },
	{ id: '#toggle12mFilter', band: '12m' },
	{ id: '#toggle10mFilter', band: '10m' }
];

const BAND_GROUP_BUTTONS = [
	{ id: '#toggleVHFFilter', group: 'VHF' },
	{ id: '#toggleUHFFilter', group: 'UHF' },
	{ id: '#toggleSHFFilter', group: 'SHF' }
];

const MODE_BUTTONS = [
	{ id: '#toggleCwFilter', mode: 'cw', icon: 'fa-wave-square' },
	{ id: '#toggleDigiFilter', mode: 'digi', icon: 'fa-keyboard' },
	{ id: '#togglePhoneFilter', mode: 'phone', icon: 'fa-microphone' }
];

const CONTINENT_BUTTONS = [
	{ id: '#toggleAfricaFilter', continent: 'AF' },
	{ id: '#toggleAntarcticaFilter', continent: 'AN' },
	{ id: '#toggleAsiaFilter', continent: 'AS' },
	{ id: '#toggleEuropeFilter', continent: 'EU' },
	{ id: '#toggleNorthAmericaFilter', continent: 'NA' },
	{ id: '#toggleOceaniaFilter', continent: 'OC' },
	{ id: '#toggleSouthAmericaFilter', continent: 'SA' }
];

const GEO_FLAGS = ['POTA', 'SOTA', 'IOTA', 'WWFF'];

// ========================================
// MAIN APPLICATION
// ========================================

$(function() {

	// ========================================
	// DATATABLES ERROR HANDLING
	// ========================================

	// Configure DataTables to log errors to console instead of showing alert dialogs
	// MUST be set before any DataTable is initialized
	if ($.fn.dataTable) {
		$.fn.dataTable.ext.errMode = function(settings, helpPage, message) {
			console.error('=== DataTables Error ===');
			console.error('Message:', message);
			console.error('Help page:', helpPage);
			console.error('Settings:', settings);
			// Also log which row/column caused the issue
			if (message.indexOf('parameter') !== -1) {
				console.error('This usually means the data array has wrong number of columns');
				console.error('Expected columns: 15 (Age, Band, Freq, Mode, Spotted, Cont, CQZ, Flag, Entity, DXCC#, Spotter, de Cont, de CQZ, Special, Message)');
			}
		};
	} else {
		console.error('$.fn.dataTable not available!');
	}

	// ========================================
	// UTILITY FUNCTIONS
	// ========================================

	/**
	 * Dispose of all Bootstrap tooltips in the table before clearing
	 */
	function disposeTooltips() {
		$('.spottable [data-bs-toggle="tooltip"]').tooltip('dispose');
	}

	/**
	 * Get current values from all filter selects
	 * @returns {Object} Object containing all filter values
	 */
	function getAllFilterValues() {
		return {
			cwn: $('#cwnSelect').val() || [],
			deCont: $('#decontSelect').val() || [],
			continent: $('#continentSelect').val() || [],
			band: $('#band').val() || [],
			mode: $('#mode').val() || [],
			additionalFlags: $('#additionalFlags').val() || [],
			requiredFlags: ($('#requiredFlags').val() || []).filter(v => v !== 'None')
		};
	}

	/**
	 * Check if a filter array contains default "All" or "Any" value
	 * @param {Array} values - Filter values array
	 * @param {string} defaultValue - Default value to check ('All' or 'Any')
	 * @returns {boolean} True if array contains only the default value
	 */
	function isDefaultFilterValue(values, defaultValue = 'All') {
		return values.length === 1 && values.includes(defaultValue);
	}

	/**
	 * Update button visual state (active/inactive)
	 * @param {string} buttonId - jQuery selector for button
	 * @param {boolean} isActive - Whether button should appear active
	 */
	function updateButtonState(buttonId, isActive) {
		const $btn = $(buttonId);
		$btn.removeClass('btn-secondary btn-success');
		$btn.addClass(isActive ? 'btn-success' : 'btn-secondary');
	}

	// ========================================
	// FILTER UI MANAGEMENT
	// ========================================

	/**
	 * Check if any filters are active (not default "All"/"Any" values)
	 * @returns {boolean} True if any non-default filters are applied
	 */
	function areFiltersApplied() {
		const filters = getAllFilterValues();

		const isDefaultCwn = isDefaultFilterValue(filters.cwn);
		const isDefaultDecont = isDefaultFilterValue(filters.deCont, 'Any');
		const isDefaultContinent = isDefaultFilterValue(filters.continent, 'Any');
		const isDefaultBand = isDefaultFilterValue(filters.band);
		const isDefaultMode = isDefaultFilterValue(filters.mode);
		const isDefaultFlags = isDefaultFilterValue(filters.additionalFlags);
		const isDefaultRequired = filters.requiredFlags.length === 0;

		return !(isDefaultCwn && isDefaultDecont && isDefaultContinent && isDefaultBand && isDefaultMode && isDefaultFlags && isDefaultRequired);
	}

	/**
	 * Update filter icon and button colors based on whether filters are active
	 */
	function updateFilterIcon() {
		if (areFiltersApplied()) {
			// When filters are active:
			// - Advanced Filters button: colored (btn-success/greenish) with filter icon only
			$('#filterDropdown').removeClass('btn-secondary').addClass('btn-success');
		} else {
			// When no filters are active:
			// - Advanced Filters button: secondary color with filter icon
			$('#filterDropdown').removeClass('btn-success').addClass('btn-secondary');
		}
		// Note: Clear Filters buttons always keep their reddish eraser icon (set in HTML)
	}

	/**
	 * Toggle a value in a multi-select filter
	 * @param {string} selectId - jQuery selector for the select element
	 * @param {string} value - Value to toggle in the selection
	 * @param {string} defaultValue - Default value to restore if selection becomes empty (default: 'All')
	 * @param {boolean} applyFiltersAfter - Whether to trigger filter application (default: true)
	 * @param {number} debounceMs - Debounce delay in milliseconds (default: 0 for no debounce)
	 * @param {boolean} updateBadges - Whether to call updateBandCountBadges() (default: false)
	 */
	function toggleFilterValue(selectId, value, defaultValue = 'All', applyFiltersAfter = true, debounceMs = 0, updateBadges = false) {
		let currentValues = $(selectId).val() || [];

		// Remove default value if present
		if (currentValues.includes(defaultValue)) {
			currentValues = currentValues.filter(v => v !== defaultValue);
		}

		// Toggle the target value
		if (currentValues.includes(value)) {
			currentValues = currentValues.filter(v => v !== value);
			// Restore default if empty
			if (currentValues.length === 0) {
				currentValues = [defaultValue];
			}
		} else {
			currentValues.push(value);
		}

		// Update selectize
		$(selectId).val(currentValues).trigger('change');
		syncQuickFilterButtons();

		// Update badge counts if requested
		if (updateBadges && typeof updateBandCountBadges === 'function') {
			updateBandCountBadges();
		}

		// Apply filters with optional debounce
		if (applyFiltersAfter) {
			if (debounceMs > 0) {
				clearTimeout(window.filterDebounceTimer);
				window.filterDebounceTimer = setTimeout(() => {
					applyFilters(false);
				}, debounceMs);
			} else {
				applyFilters(false);
			}
		}
	}

	/**
	 * Sync quick filter button states with their corresponding dropdown values
	 */
	function syncQuickFilterButtons() {
		const filters = getAllFilterValues();

		// Required flags buttons
		const requiredFlagButtons = [
			{ id: '#toggleLotwFilter', flag: 'lotw' },
			{ id: '#toggleDxSpotFilter', flag: 'dxspot' },
			{ id: '#toggleNewContinentFilter', flag: 'newcontinent' },
			{ id: '#toggleDxccNeededFilter', flag: 'newcountry' },
			{ id: '#toggleNewCallsignFilter', flag: 'newcallsign' },
			{ id: '#toggleContestFilter', flag: 'Contest' }
		];

		requiredFlagButtons.forEach(btn => {
			updateButtonState(btn.id, filters.requiredFlags.includes(btn.flag));
		});

		// Geo Hunter button (stays in Additional Flags)
		const hasGeoFlag = GEO_FLAGS.some(flag => filters.additionalFlags.includes(flag));
		updateButtonState('#toggleGeoHunterFilter', hasGeoFlag);

		// Fresh filter button
		updateButtonState('#toggleFreshFilter', filters.additionalFlags.includes('Fresh'));

		// Mode buttons
		MODE_BUTTONS.forEach(btn => {
			updateButtonState(btn.id, filters.mode.includes(btn.mode));
		});

		// Check if "All" is selected for bands, modes, and continents
		const allBandsSelected = isDefaultFilterValue(filters.band);
		const allModesSelected = isDefaultFilterValue(filters.mode) ||
			(filters.mode.includes('cw') && filters.mode.includes('digi') && filters.mode.includes('phone'));
		const allContinentsSelected = isDefaultFilterValue(filters.deCont, 'Any') ||
			(filters.deCont.includes('AF') && filters.deCont.includes('AN') &&
			filters.deCont.includes('AS') && filters.deCont.includes('EU') &&
			filters.deCont.includes('NA') && filters.deCont.includes('OC') &&
			filters.deCont.includes('SA'));

		// Band filter buttons - always update colors (for CAT Control visibility)
		BAND_BUTTONS.forEach(btn => {
			const isActive = allBandsSelected || filters.band.includes(btn.band);
			updateButtonState(btn.id, isActive);
		});

		// Band group buttons (VHF, UHF, SHF)
		BAND_GROUP_BUTTONS.forEach(btn => {
			const groupBands = getBandsInGroup(btn.group);
			const allGroupBandsSelected = groupBands.every(b => filters.band.includes(b));
			const isActive = allBandsSelected || allGroupBandsSelected;
			updateButtonState(btn.id, isActive);
		});

		// Mode buttons
		MODE_BUTTONS.forEach(btn => {
			const isActive = allModesSelected || filters.mode.includes(btn.mode);
			updateButtonState(btn.id, isActive);
		});

		// "All Continents" button
		updateButtonState('#toggleAllContinentsFilter', allContinentsSelected);

		// Individual continent buttons
		CONTINENT_BUTTONS.forEach(btn => {
			const isActive = allContinentsSelected || filters.deCont.includes(btn.continent);
			updateButtonState(btn.id, isActive);
		});
	}

	/**
	 * Add checkbox-style indicators (☑/☐) to multi-select dropdowns
	 * @param {string} selectId - ID of the select element
	 */
	function updateSelectCheckboxes(selectId) {
		let $select = $('#' + selectId);
		$select.find('option').each(function() {
			let $option = $(this);
			let originalText = $option.data('original-text');

				if (!originalText) {
					originalText = $option.text();
					$option.data('original-text', originalText);
				}

				if ($option.is(':selected')) {
					$option.text('☑ ' + originalText);
				} else {
					$option.text('☐ ' + originalText);
				}
			});
		}

		// Initialize checkbox indicators for all filter selects
		function initFilterCheckboxes() {
			['cwnSelect', 'decontSelect', 'continentSelect', 'band', 'mode', 'additionalFlags', 'requiredFlags'].forEach(function(selectId) {
				updateSelectCheckboxes(selectId);
				$('#' + selectId).on('change', function() {
					updateSelectCheckboxes(selectId);
				});
			});
		}

		// Handle "All"/"Any" option behavior in multi-selects
		// If "All" is selected with other options, keep only "All"
		// If nothing selected, default back to "All"/"Any"
		function handleAllOption(selectId) {
			$('#' + selectId).on('change', function() {
				let selected = $(this).val() || [];

				if (selected.includes('All') || selected.includes('Any')) {
					let allValue = selected.includes('All') ? 'All' : 'Any';
					if (selected.length > 1) {
						$(this).val([allValue]);
					}
				} else if (selected.length === 0) {
					let allValue = (selectId === 'decontSelect' || selectId === 'continentSelect') ? 'Any' : 'All';
					$(this).val([allValue]);
				}

				updateFilterIcon();

				// Sync button states when band, mode, or continent filters change
				if (selectId === 'band' || selectId === 'mode' || selectId === 'decontSelect' || selectId === 'continentSelect') {
					syncQuickFilterButtons();
				}
			});
		}

		// Apply "All" handler to all filter dropdowns
		['cwnSelect', 'decontSelect', 'continentSelect', 'band', 'mode', 'additionalFlags'].forEach(handleAllOption);

		// Required flags filter - handle "None" option
		$('#requiredFlags').on('change', function() {
			let currentValues = $(this).val() || [];

			// If "None" is selected, deselect all others
			if (currentValues.includes('None')) {
				if (currentValues.length > 1) {
					// User selected something else, remove "None"
					currentValues = currentValues.filter(v => v !== 'None');
				}
			} else if (currentValues.length === 0) {
				// If nothing is selected, select "None"
				currentValues = ['None'];
			}

			$(this).val(currentValues);
			updateFilterIcon();
		});

	// ========================================
	// DATATABLE CONFIGURATION
	// ========================================

	// Sort spots by frequency (ascending)
	function SortByQrg(a, b) {
		return a.frequency - b.frequency;
	}

	// Initialize DataTables instance with custom row click handlers
	function get_dtable() {
		var table = $('.spottable').DataTable({
			paging: false,
			searching: true,
			dom: 'rt',
			retrieve: true,
			language: {
				url: getDataTablesLanguageUrl(),
				"emptyTable": "<i class='fas fa-spinner fa-spin'></i> Loading spots...",
				"zeroRecords": lang_bandmap_no_spots_found
			},
			'columnDefs': [
				{
					'targets': 2,  // Frequency is now column 3 (0-indexed = 2)
				"type":"num",
				'render': function (data, type, row) {
					// For sorting and filtering, return numeric value
					if (type === 'sort' || type === 'type') {
						return parseFloat(data) || 0;
					}
					// For display, return the string as-is
					return data;
				},
					'createdCell':  function (td, cellData, rowData, row, col) {
						$(td).addClass("MHz");
					}
				},
				{
					'targets': 3,  // Mode column is now column 4 (0-indexed = 3)
					'createdCell':  function (td, cellData, rowData, row, col) {
						$(td).addClass("mode");
					}
				},
				{
					'targets': [5, 6, 7, 11, 12, 13, 14],  // Cont, CQZ, Flag, de Cont, de CQZ, Special, Message - disable sorting
					'orderable': false
				}
			],
		search: { smart: true },
		drawCallback: function(settings) {
			// Update status bar after table is drawn (including after search)
			let totalRows = cachedSpotData ? cachedSpotData.length : 0;
			let displayedRows = this.api().rows({ search: 'applied' }).count();
			updateStatusBar(totalRows, displayedRows, getServerFilterText(), getClientFilterText(), false, false);

			// Note: CAT frequency gradient is now updated only from updateCATui (every 3s)
			// to prevent recursion issues with table redraws
		}
	});	$('.spottable tbody').off('click', 'tr').on('click', 'tr', function(e) {
		// Don't trigger row click if clicking on a link (LoTW, POTA, SOTA, WWFF, QRZ, etc.)
		if ($(e.target).is('a') || $(e.target).closest('a').length) {
			return;
		}

		let cellIndex = $(e.target).closest('td').index();
		// If clicking callsign column (column 5, 0-indexed = 4), open QRZ link directly
		if (cellIndex === 4) {
			let rowData = table.row(this).data();
			if (!rowData) return;

			let callsignHtml = rowData[4];
			let tempDiv = $('<div>').html(callsignHtml);
			let qrzLink = tempDiv.find('a');

			if (qrzLink.length) {
				qrzLink[0].click();
				return;
			}
		}

	// Default row click: prepare QSO logging with callsign, frequency, mode
	let rowData = table.row(this).data();
	if (!rowData) return;

	let callsignHtml = rowData[4];  // Callsign is column 5 (0-indexed = 4)
	let tempDiv = $('<div>').html(callsignHtml);
	let call = tempDiv.find('a').text().trim();
	if (!call) return;

	let qrg = parseFloat(rowData[2]) * 1000000;  // Frequency in MHz, convert to Hz
	let modeHtml = rowData[3];  // Mode is column 4 (0-indexed = 3)
	let modeDiv = $('<div>').html(modeHtml);
	let mode = modeDiv.text().trim();  // Extract clean mode text from HTML

	// Ctrl+click: Only tune radio, don't prepare logging form
	if (e.ctrlKey || e.metaKey) {
		if (isCatTrackingEnabled) {
			tuneRadio(qrg, mode);
		} else {
			if (typeof showToast === 'function') {
				showToast(lang_bandmap_cat_required, lang_bandmap_enable_cat, 'bg-warning text-dark', 3000);
			}
		}
		return;
	}

	// Find the original spot data to get reference information
	let spotData = null;
	if (cachedSpotData) {
		// Note: spot.frequency is in kHz, so multiply by 1000 to get Hz
		spotData = cachedSpotData.find(spot =>
			spot.spotted === call &&
			Math.abs(spot.frequency * 1000 - qrg) < 100  // Match within 100 Hz tolerance
		);
	}

	prepareLogging(call, qrg, mode, spotData);
	});
	return table;
	}

	// ========================================
	// FILTER STATE TRACKING
	// ========================================

	// Track what backend parameters were used for last data fetch
	// NOTE: Changed architecture - only de continent affects backend now
	// Band and Mode are now client-side filters only
	// UPDATE: Band becomes backend filter when CAT Control is active (single-band fetch mode)
	var loadedBackendFilters = {
		continent: 'Any',
		band: 'All'
	};

	// Initialize backend filter state from form values
	function initializeBackendFilters() {
		const decontSelect = $('#decontSelect').val();
		loadedBackendFilters.continent = (decontSelect && decontSelect.length > 0) ? decontSelect[0] : 'Any';
	}

	// Track all current filter selections (both client and server-side)
	var currentFilters = {
		band: ['All'],
		deContinent: ['Any'],
		spottedContinent: ['Any'],
		cwn: ['All'],
		mode: ['All'],
		additionalFlags: ['All'],
		requiredFlags: []
	};

	// ========================================
	// DATA CACHING & FETCH STATE
	// ========================================

	var cachedSpotData = null;  // Raw spot data from last backend fetch
	var cachedUserFavorites = null;  // Cached user favorites (bands and modes)
	var isFetchInProgress = false;  // Prevent multiple simultaneous fetches
	var currentAjaxRequest = null;  // Track active AJAX request for cancellation
	var lastFetchParams = {  // Track last successful fetch parameters
		continent: 'Any',
		maxAge: 60,
		timestamp: null
	};

	// TTL (Time To Live) management for spots
	// Key: "callsign_frequency_spotter", Value: TTL count
	var spotTTLMap = new Map();

	// Generate unique key for spot identification
	function getSpotKey(spot) {
		return spot.spotted + '_' + spot.frequency + '_' + spot.spotter;
	}

	// Convert array to Set for O(1) lookups, handle 'All'/'Any' values
	function arrayToFilterSet(arr, defaultValue = 'All') {
		if (!arr || arr.length === 0 || arr.includes(defaultValue)) {
			return null; // null means "accept all"
		}
		return new Set(arr);
	}

	// Auto-refresh timer state
	var refreshCountdown = SPOT_REFRESH_INTERVAL;
	var refreshTimerInterval = null;

	// ========================================
	// STATUS BAR & UI UPDATES
	// ========================================

	// Update status bar with spot counts, filter info, and fetch status
	function updateStatusBar(totalSpots, displayedSpots, serverFilters, clientFilters, isFetching, isInitialLoad) {
		if (isFetching) {
			let allFilters = [];
			if (serverFilters && serverFilters.length > 0) {
				allFilters = allFilters.concat(serverFilters.map(f => 'de ' + f));
			}
			if (clientFilters && clientFilters.length > 0) {
				allFilters = allFilters.concat(clientFilters);
			}

			let loadingMessage = lang_bandmap_loading_data;
			if (allFilters.length > 0) {
				loadingMessage += '...';
			} else {
				loadingMessage += '...';
			}

			$('#statusMessage').text(loadingMessage).attr('title', '');
			$('#statusFilterInfo').remove();
			$('#refreshIcon').removeClass('fa-hourglass-half').addClass('fa-spinner fa-spin');
			$('#refreshTimer').text('');
			return;
		}

		if (lastFetchParams.timestamp === null) {
			$('#statusMessage').text('');
			$('#statusFilterInfo').remove();
			$('#refreshTimer').text('');
			return;
		}

	let now = new Date();
	let timeStr = now.getUTCHours().toString().padStart(2, '0') + ':' + now.getUTCMinutes().toString().padStart(2, '0') + 'Z';
	let statusMessage = totalSpots + ' ' + lang_bandmap_spots_fetched + ' @ ' + timeStr;
	let allFilters = [];		if (serverFilters && serverFilters.length > 0) {
			allFilters = allFilters.concat(serverFilters.map(f => 'de "' + f + '"'));
		}

		if (clientFilters && clientFilters.length > 0) {
			allFilters = allFilters.concat(clientFilters);
		}

		var table = get_dtable();
		var searchValue = table.search();
		if (searchValue) {
			allFilters.push('search: "' + searchValue + '"');
		}

	// Build status message
	if (allFilters.length > 0) {
		statusMessage += ', ' + lang_bandmap_showing + ' ' + displayedSpots;
	} else if (displayedSpots < totalSpots) {
		statusMessage += ', ' + lang_bandmap_showing + ' ' + displayedSpots;
	} else if (totalSpots > 0) {
		statusMessage += ', ' + lang_bandmap_showing_all;
	}		// Build tooltip for status message (fetch information)
		let fetchTooltipLines = [lang_bandmap_last_fetched + ':'];
		fetchTooltipLines.push(lang_bandmap_band + ': ' + (lastFetchParams.band || lang_bandmap_all));
		fetchTooltipLines.push(lang_bandmap_continent + ': ' + (lastFetchParams.continent || lang_bandmap_all));
		fetchTooltipLines.push(lang_bandmap_mode + ': ' + (lastFetchParams.mode || lang_bandmap_all));
		fetchTooltipLines.push(lang_bandmap_max_age + ': ' + (lastFetchParams.maxAge || '120') + ' min');
		if (lastFetchParams.timestamp) {
			let fetchTime = new Date(lastFetchParams.timestamp);
			let fetchTimeStr = fetchTime.getUTCHours().toString().padStart(2, '0') + ':' +
			                   fetchTime.getUTCMinutes().toString().padStart(2, '0') + ':' +
			                   fetchTime.getUTCSeconds().toString().padStart(2, '0') + 'Z';
			fetchTooltipLines.push(lang_bandmap_fetched_at + ': ' + fetchTimeStr);
		}

	$('#statusMessage').text(statusMessage).attr('title', fetchTooltipLines.join('\n'));

	// Add info icon if filters are active (with separate tooltip for active filters)
	$('#statusFilterInfo').remove();
	if (allFilters.length > 0) {
		let filterTooltip = lang_bandmap_active_filters + ':\n' + allFilters.join('\n');
		$('#statusMessage').after(' <i class="fas fa-info-circle text-muted" id="statusFilterInfo" style="cursor: help;" title="' + filterTooltip.replace(/"/g, '&quot;') + '"></i>');
	}

	if (isFetching) {
		$('#refreshIcon').removeClass('fa-hourglass-half').addClass('fa-spinner fa-spin');
		$('#refreshTimer').text(lang_bandmap_fetching);
	} else {
		$('#refreshIcon').removeClass('fa-spinner fa-spin').addClass('fa-hourglass-half');
		$('#refreshTimer').text(lang_bandmap_next_update + ' ' + refreshCountdown + 's');
	}
}	function getDisplayedSpotCount() {
		var table = get_dtable();
		return table.rows({search: 'applied'}).count();
	}

	// Start/restart the auto-refresh countdown timer (60 seconds)
	function startRefreshTimer() {
		if (refreshTimerInterval) {
			clearInterval(refreshTimerInterval);
		}

		refreshCountdown = SPOT_REFRESH_INTERVAL;

		refreshTimerInterval = setInterval(function() {
			refreshCountdown--;
			if (refreshCountdown <= 0) {
				let table = get_dtable();
				table.clear();

				// Determine band for API fetch based on CAT Control state and current filter
				let bandForRefresh = 'All';
				if (isCatTrackingEnabled) {
					let currentBand = currentFilters.band || [];
					if (currentBand.length === 1 && !currentBand.includes('All') && currentBand[0] !== '') {
						bandForRefresh = currentBand[0];
					}
				}

				fill_list(currentFilters.deContinent, dxcluster_maxage, bandForRefresh);
				refreshCountdown = SPOT_REFRESH_INTERVAL;
			} else {
				if (!isFetchInProgress && lastFetchParams.timestamp !== null) {
					$('#refreshIcon').removeClass('fa-spinner fa-spin').addClass('fa-hourglass-half');
					$('#refreshTimer').text(lang_bandmap_next_update + ' ' + refreshCountdown + 's');
				}
			}
		}, 1000);
	}

	// Build array of server-side filter labels for display
	function getServerFilterText() {
		let filters = [];
		// Only de continent is a server filter now
		if (loadedBackendFilters.continent !== 'Any') {
			filters.push(loadedBackendFilters.continent);
		}
		return filters;
	}

	// Build array of client-side filter labels for display
	function getClientFilterText() {
		let filters = [];

		// DXCC Status
		if (!currentFilters.cwn.includes('All')) {
			let cwnLabels = currentFilters.cwn.map(function(status) {
				switch(status) {
					case 'notwkd': return lang_bandmap_not_worked;
					case 'wkd': return lang_bandmap_worked;
					case 'cnf': return lang_bandmap_confirmed;
					case 'ucnf': return lang_bandmap_worked_not_confirmed;
					default: return status;
				}
			});
			filters.push('"' + lang_bandmap_dxcc + ': ' + cwnLabels.join('/') + '"');
		}

		// Additional Flags - special handling for OR logic
		if (!currentFilters.additionalFlags.includes('All')) {
			let flagsList = currentFilters.additionalFlags.filter(f => f !== 'All');
			if (flagsList.length > 0) {
				if (flagsList.length === 1) {
					filters.push('"' + flagsList[0] + '"');
				} else {
					filters.push('("' + flagsList.join('" or "') + '")');
				}
			}
		}

		// De continent
		// Only show in client filter when multiple continents are selected
		// Single continent is already shown in server filter
		if (!currentFilters.deContinent.includes('Any') && currentFilters.deContinent.length > 1) {
			filters.push('"' + lang_bandmap_de + ': ' + currentFilters.deContinent.join('/') + '"');
		}

		// Spotted continent
		if (!currentFilters.spottedContinent.includes('Any')) {
			filters.push('"' + lang_bandmap_spotted + ': ' + currentFilters.spottedContinent.join('/') + '"');
		}

		// Band
		if (!currentFilters.band.includes('All')) {
			filters.push('"' + lang_bandmap_band + ': ' + currentFilters.band.join('/') + '"');
		}

		// Mode
		if (!currentFilters.mode.includes('All')) {
			let modeLabels = currentFilters.mode.map(function(m) {
				return m.charAt(0).toUpperCase() + m.slice(1);
			});
			filters.push('"' + lang_bandmap_mode + ': ' + modeLabels.join('/') + '"');
		}

		// Required flags - each one is shown individually with "and"
		if (currentFilters.requiredFlags && currentFilters.requiredFlags.length > 0) {
			currentFilters.requiredFlags.forEach(function(flag) {
				if (flag === 'lotw') {
					filters.push('"' + lang_bandmap_lotw_user + '"');
				} else if (flag === 'notworked') {
					filters.push('"' + lang_bandmap_new_callsign + '"');
				} else {
					filters.push('"' + flag + '"');
				}
			});
		}

		return filters;
	}

	// ========================================
	// CLIENT-SIDE FILTERING & RENDERING
	// ========================================

	// Render spots from cached data, applying client-side filters
	// Client filters: spottedContinent, cwn status, additionalFlags
	function renderFilteredSpots() {
		var table = get_dtable();

		if (!cachedSpotData || cachedSpotData.length === 0) {
			table.clear();
			table.settings()[0].oLanguage.sEmptyTable = lang_bandmap_no_data;
			table.draw();
			return;
		}

		// Convert filter arrays to Sets for O(1) lookup performance
		let bandSet = arrayToFilterSet(currentFilters.band);
		let deContinentSet = arrayToFilterSet(currentFilters.deContinent, 'Any');
		let spottedContinentSet = arrayToFilterSet(currentFilters.spottedContinent, 'Any');
		let cwnSet = arrayToFilterSet(currentFilters.cwn);
		let modeSet = arrayToFilterSet(currentFilters.mode);
		let flagSet = arrayToFilterSet(currentFilters.additionalFlags);
		let requiredFlags = currentFilters.requiredFlags || [];
		const hasRequiredFlags = requiredFlags.length > 0;
		const hasCwnFilter = cwnSet !== null;
		const hasFlagFilter = flagSet !== null;

		table.clear();
		let oldtable = table.data();
		let spots2render = 0;

	cachedSpotData.forEach((single) => {
		// Check TTL - skip spots with TTL < 0 (completely hidden)
		let spotKey = getSpotKey(single);
		let ttl = spotTTLMap.get(spotKey);

		// Skip if TTL is undefined or < 0
		if (ttl === undefined || ttl < 0) {
			return;
		}

		// Extract time from spot data - use 'when' field
		let timeOnly = single.when;

		// Cache DXCC references to avoid repeated property access
		const dxccSpotted = single.dxcc_spotted;
		const dxccSpotter = single.dxcc_spotter;

		// Apply required flags FIRST (must have ALL selected required flags)
		if (hasRequiredFlags) {
			for (let i = 0; i < requiredFlags.length; i++) {
				const reqFlag = requiredFlags[i];
				switch (reqFlag) {
					case 'lotw':
						if (!dxccSpotted || !dxccSpotted.lotw_user) return;
						break;
					case 'dxspot':
						// DX Spot: spotted continent must be different from spotter continent
						if (!dxccSpotted?.cont || !dxccSpotter?.cont || dxccSpotted.cont === dxccSpotter.cont) return;
						break;
					case 'newcontinent':
						if (single.worked_continent !== false) return;
						break;
					case 'newcountry':
						if (single.worked_dxcc !== false) return;
						break;
					case 'newcallsign':
						if (single.worked_call !== false) return;
						break;
					case 'workedcallsign':
						if (single.worked_call === false) return;
						break;
					case 'Contest':
						if (!dxccSpotted || !dxccSpotted.isContest) return;
						break;
				}
			}
		}		// Apply CWN (Confirmed/Worked/New) filter
		if (hasCwnFilter) {
			const workedDxcc = single.worked_dxcc;
			const cnfmdDxcc = single.cnfmd_dxcc;
			let passesCwnFilter = false;
			if ((cwnSet.has('notwkd') && !workedDxcc) ||
				(cwnSet.has('wkd') && workedDxcc) ||
				(cwnSet.has('cnf') && cnfmdDxcc) ||
				(cwnSet.has('ucnf') && workedDxcc && !cnfmdDxcc)) {
				passesCwnFilter = true;
			}
			if (!passesCwnFilter) return;
		}

		// Apply band filter (band always provided by API)
		if (bandSet && !bandSet.has(single.band)) return;

		// Apply de continent filter (which continent the spotter is in)
		if (deContinentSet && (!dxccSpotter || !dxccSpotter.cont || !deContinentSet.has(dxccSpotter.cont))) return;

		// Apply spotted continent filter (which continent the DX station is in)
		if (spottedContinentSet && (!dxccSpotted || !dxccSpotted.cont || !spottedContinentSet.has(dxccSpotted.cont))) return;		// Apply mode filter (API already returns mode categories)
		if (modeSet && (!single.mode || !modeSet.has(single.mode))) return;

		// Apply additional flags filter (POTA, SOTA, WWFF, IOTA, Contest, Fresh)
		if (hasFlagFilter) {
			let passesFlagsFilter = false;
			const age = single.age || 0;
			if ((flagSet.has('SOTA') && dxccSpotted && dxccSpotted.sota_ref) ||
				(flagSet.has('POTA') && dxccSpotted && dxccSpotted.pota_ref) ||
				(flagSet.has('WWFF') && dxccSpotted && dxccSpotted.wwff_ref) ||
				(flagSet.has('IOTA') && dxccSpotted && dxccSpotted.iota_ref) ||
				(flagSet.has('Contest') && dxccSpotted && dxccSpotted.isContest) ||
				(flagSet.has('Fresh') && age < 5)) {
				passesFlagsFilter = true;
			}
			if (!passesFlagsFilter) return;
		}

		// All filters passed - validate essential data exists (reuse cached references)
		if (!dxccSpotted) {
			console.warn('Spot missing dxcc_spotted - creating placeholder:', single.spotted, single.frequency);
			single.dxcc_spotted = { dxcc_id: 0, cont: '', cqz: '', flag: '', entity: 'Unknown' };
		}
		if (!dxccSpotter) {
			console.warn('Spot missing dxcc_spotter - creating placeholder:', single.spotted, single.frequency);
			single.dxcc_spotter = { dxcc_id: 0, cont: '', cqz: '', flag: '', entity: 'Unknown' };
		}

		// Build table row data
		spots2render++;
		var data = [];
		var dxcc_wked_info, wked_info;			// Color code DXCC entity: green=confirmed, yellow=worked, red=new

			if (single.cnfmd_dxcc) {
				dxcc_wked_info = "text-success";
			} else if (single.worked_dxcc) {
				dxcc_wked_info = "text-warning";
			} else {
				dxcc_wked_info = "text-danger";
			}
			// Color code callsign: green=confirmed, yellow=worked
			if (single.cnfmd_call) {
				wked_info = "text-success";
			} else if (single.worked_call) {
				wked_info = "text-warning";
			} else {
				wked_info = "";
			}

		// Build LoTW badge with color coding based on last upload age
		var lotw_badge = '';
		if (single.dxcc_spotted && single.dxcc_spotted.lotw_user) {
			let lclass = '';
			if (single.dxcc_spotted.lotw_user > 365) {
				lclass = 'lotw_info_red';
			} else if (single.dxcc_spotted.lotw_user > 30) {
				lclass = 'lotw_info_orange';
			} else if (single.dxcc_spotted.lotw_user > 7) {
			lclass = 'lotw_info_yellow';
		}
		let lotw_title = lang_bandmap_lotw_last_upload.replace('%d', single.dxcc_spotted.lotw_user);
		lotw_badge = '<a href="https://lotw.arrl.org/lotwuser/act?act=' + single.spotted + '" target="_blank" onclick="event.stopPropagation();">' + buildBadge('success ' + lclass, 'fa-upload', lotw_title) + '</a>';
	}

	// Build activity badges (POTA, SOTA, WWFF, IOTA, Contest, Worked)
	let activity_flags = '';

	if (single.dxcc_spotted && single.dxcc_spotted.pota_ref) {
		let pota_title = 'POTA: ' + single.dxcc_spotted.pota_ref;
		if (single.dxcc_spotted.pota_mode) {
			pota_title += ' (' + single.dxcc_spotted.pota_mode + ')';
		}
		pota_title += ' - ' + lang_bandmap_click_to_view_pota;
		let pota_url = 'https://pota.app/#/park/' + single.dxcc_spotted.pota_ref;
		activity_flags += '<a href="' + pota_url + '" target="_blank" onclick="event.stopPropagation();">' + buildBadge('success', 'fa-tree', pota_title) + '</a>';
	}

	if (single.dxcc_spotted && single.dxcc_spotted.sota_ref) {
		let sota_title = 'SOTA: ' + single.dxcc_spotted.sota_ref + ' - ' + lang_bandmap_click_to_view_sotl;
		let sota_url = 'https://sotl.as/summits/' + single.dxcc_spotted.sota_ref;
		activity_flags += '<a href="' + sota_url + '" target="_blank" onclick="event.stopPropagation();">' + buildBadge('primary', 'fa-mountain', sota_title) + '</a>';
	}

	if (single.dxcc_spotted && single.dxcc_spotted.wwff_ref) {
		let wwff_title = 'WWFF: ' + single.dxcc_spotted.wwff_ref + ' - ' + lang_bandmap_click_to_view_wwff;
		let wwff_url = 'https://www.cqgma.org/zinfo.php?ref=' + single.dxcc_spotted.wwff_ref;
		activity_flags += '<a href="' + wwff_url + '" target="_blank" onclick="event.stopPropagation();">' + buildBadge('success', 'fa-leaf', wwff_title) + '</a>';
	}

	if (single.dxcc_spotted && single.dxcc_spotted.iota_ref) {
		let iota_title = 'IOTA: ' + single.dxcc_spotted.iota_ref + ' - ' + lang_bandmap_click_to_view_iota;
		let iota_url = 'https://www.iota-world.org/';
		activity_flags += '<a href="' + iota_url + '" target="_blank" onclick="event.stopPropagation();">' + buildBadge('info', 'fa-water', iota_title) + '</a>';
	}

	if (single.dxcc_spotted && single.dxcc_spotted.isContest) {
		// Build contest badge with contest name in tooltip if available
		let contestTitle = lang_bandmap_contest;
		if (single.dxcc_spotted.contestName && single.dxcc_spotted.contestName !== '') {
			contestTitle = lang_bandmap_contest_name + ': ' + single.dxcc_spotted.contestName;
		}
		activity_flags += buildBadge('warning', 'fa-trophy', contestTitle);
	}

	// Add "Fresh" badge for spots less than 5 minutes old
	let ageMinutesCheck = single.age || 0;
	let isFresh = ageMinutesCheck < 5;

	if (single.worked_call) {
		let worked_title = lang_bandmap_worked_before;
		if (single.last_wked && single.last_wked.LAST_QSO && single.last_wked.LAST_MODE) {
			worked_title = lang_bandmap_worked_details.replace('%s', single.last_wked.LAST_QSO).replace('%s', single.last_wked.LAST_MODE);
		}
		let worked_badge_type = single.cnfmd_call ? 'success' : 'warning';
		// isLast is true only if fresh badge won't be added
		activity_flags += buildBadge(worked_badge_type, 'fa-check-circle', worked_title, null, !isFresh);
	}

	if (isFresh) {
		activity_flags += buildBadge('danger', 'fa-bolt', lang_bandmap_fresh_spot, null, true);
	}		// Build table row array
		data[0] = [];		// Age column: show age in minutes with auto-update attribute
		let ageMinutes = single.age || 0;
		let spotTimestamp = single.when ? new Date(single.when).getTime() : Date.now();
		data[0].push('<span class="spot-age" data-spot-time="' + spotTimestamp + '">' + ageMinutes + '</span>');

		// Band column: show band designation
		data[0].push(single.band || '');

		// Frequency column: convert kHz to MHz with 3 decimal places
		let freqMHz = (single.frequency / 1000).toFixed(3);
		data[0].push(freqMHz);

	// Mode column: capitalize properly (API returns lowercase categories)
	// Show submode in tooltip if available
	let displayMode = single.mode || '';
	displayMode = MODE_CAPITALIZATION[displayMode] || displayMode;
	if (single.submode && single.submode !== '') {
		displayMode = '<span data-bs-toggle="tooltip" title="' + single.submode + '">' + displayMode + '</span>';
	}
	data[0].push(displayMode);		// Callsign column: wrap in QRZ link with color coding
		let qrzLink = '<a href="https://www.qrz.com/db/' + single.spotted + '" target="_blank" onclick="event.stopPropagation();" data-bs-toggle="tooltip" title="Click to view ' + single.spotted + ' on QRZ.com">' + single.spotted + '</a>';
		wked_info = ((wked_info != '' ? '<span class="' + wked_info + '">' : '') + qrzLink + (wked_info != '' ? '</span>' : ''));
		var spotted = wked_info;
		data[0].push(spotted);

	// Continent column: color code based on worked/confirmed status
	var continent_wked_info;
	if (single.cnfmd_continent) {
		continent_wked_info = "text-success";
	} else if (single.worked_continent) {
		continent_wked_info = "text-warning";
	} else {
		continent_wked_info = "text-danger";
	}
	let continent_value = (single.dxcc_spotted && single.dxcc_spotted.cont) ? single.dxcc_spotted.cont : '';
	if (continent_value) {
		let continent_display = (continent_wked_info != '' ? '<span class="' + continent_wked_info + '">' : '') + continent_value + (continent_wked_info != '' ? '</span>' : '');
		continent_wked_info = '<a href="javascript:spawnLookupModal(\'' + continent_value.toLowerCase() + '\',\'continent\')"; data-bs-toggle="tooltip" title="' + lang_bandmap_see_details_continent + ' ' + continent_value + '">' + continent_display + '</a>';
	} else {
		continent_wked_info = '';
	}
	data[0].push(continent_wked_info);

	// CQ Zone column: show CQ Zone (moved here, right after Cont)
	let cqz_value = (single.dxcc_spotted && single.dxcc_spotted.cqz) ? single.dxcc_spotted.cqz : '';
	if (cqz_value) {
		data[0].push('<a href="javascript:spawnLookupModal(\'' + cqz_value + '\',\'cq\')"; data-bs-toggle="tooltip" title="' + lang_bandmap_see_details_cqz_value.replace('%s', cqz_value) + '">' + cqz_value + '</a>');
	} else {
		data[0].push('');
	}	// Flag column: just the flag emoji without entity name
	let flag_only = '';
	if (single.dxcc_spotted && single.dxcc_spotted.flag) {
		flag_only = '<span class="flag-emoji">' + single.dxcc_spotted.flag + '</span>';
	}
	data[0].push(flag_only);

	// Entity column: entity name with color coding (no flag)
	let dxcc_entity_full = single.dxcc_spotted ? (single.dxcc_spotted.entity || '') : '';
	let entity_colored = dxcc_entity_full ? ((dxcc_wked_info != '' ? '<span class="' + dxcc_wked_info + '">' : '') + dxcc_entity_full + (dxcc_wked_info != '' ? '</span>' : '')) : '';
	if (single.dxcc_spotted && single.dxcc_spotted.dxcc_id && dxcc_entity_full) {
		data[0].push('<a href="javascript:spawnLookupModal(\'' + single.dxcc_spotted.dxcc_id + '\',\'dxcc\')"; data-bs-toggle="tooltip" title="' + lang_bandmap_see_details + ' ' + dxcc_entity_full + '">' + entity_colored + '</a>');
	} else {
		data[0].push(entity_colored);
	}

	// DXCC Number column: show ADIF DXCC entity number with color coding
	let dxcc_id_value = (single.dxcc_spotted && single.dxcc_spotted.dxcc_id) ? single.dxcc_spotted.dxcc_id : '';
	let dxcc_number = dxcc_id_value ? ((dxcc_wked_info != '' ? '<span class="' + dxcc_wked_info + '">' : '') + dxcc_id_value + (dxcc_wked_info != '' ? '</span>' : '')) : '';
	data[0].push(dxcc_number);

	// de Callsign column (Spotter) - clickable QRZ link
	let spotterQrzLink = '<a href="https://www.qrz.com/db/' + single.spotter + '" target="_blank" onclick="event.stopPropagation();" data-bs-toggle="tooltip" title="' + lang_bandmap_click_view_qrz_callsign.replace('%s', single.spotter) + '">' + single.spotter + '</a>';
	data[0].push(spotterQrzLink);

	// de Cont column: spotter's continent
	data[0].push((single.dxcc_spotter && single.dxcc_spotter.cont) ? single.dxcc_spotter.cont : '');

	// de CQZ column: spotter's CQ Zone
	data[0].push((single.dxcc_spotter && single.dxcc_spotter.cqz) ? single.dxcc_spotter.cqz : '');	// Build medal badge - show only highest priority: continent > country > callsign
	let medals = '';
	if (single.worked_continent === false) {
		// New Continent (not worked before) - Gold medal
		medals += buildBadge('gold', 'fa-medal', lang_bandmap_new_continent);
	} else if (single.worked_dxcc === false) {
		// New DXCC (not worked before) - Silver medal
		medals += buildBadge('silver', 'fa-medal', lang_bandmap_new_country);
	} else if (single.worked_call === false) {
		// New Callsign (not worked before) - Bronze medal
		medals += buildBadge('bronze', 'fa-medal', lang_bandmap_new_callsign);
	}

	// Special column: combine medals, LoTW and activity badges
	let flags_column = medals + lotw_badge + activity_flags;
	data[0].push(flags_column);		// Message column
		data[0].push(single.message || '');

		// Debug: Validate data array has exactly 15 columns
		if (data[0].length !== 15) {
			console.error('INVALID DATA ARRAY LENGTH:', data[0].length, 'Expected: 15');
			console.error('Spot:', single.spotted, 'Frequency:', single.frequency);
			console.error('Data array:', data[0]);
			console.error('Missing columns:', 15 - data[0].length);
			// Pad array with empty strings to prevent DataTables error
			while (data[0].length < 15) {
				data[0].push('');
			}
		}

			// Add row to table with appropriate styling based on TTL and age
			// Priority: TTL=0 (expiring) > age < 1 min (very new) > fresh
			let rowClass = '';
			let ageMinutesForStyling = single.age || 0;

			if (ttl === 0) {
				// Expiring spot (gone from cluster but visible for one more cycle)
				rowClass = 'spot-expiring';
			} else if (ageMinutesForStyling < 1) {
				// Very new spot (less than 1 minute old)
				rowClass = 'spot-very-new';
			} else if (oldtable.length > 0) {
				// Check if this is a new spot (not in old table)
				let update = false;
				oldtable.each(function (srow) {
					if (JSON.stringify(srow) === JSON.stringify(data[0])) {
						update = true;
					}
				});
				if (!update) {
					rowClass = 'fresh';  // Fresh spot animation
				}
			}

		// Add row with appropriate class
		let addedRow = table.rows.add(data).draw().nodes().to$();

		if (rowClass) {
			addedRow.addClass(rowClass);
		}
			// Apply CAT frequency gradient AFTER adding lifecycle classes to ensure it overrides
			if (isCatTrackingEnabled && currentRadioFrequency) {
				const spotFreqKhz = single.frequency * 1000; // Convert MHz to kHz
				const gradientColor = getFrequencyGradientColor(spotFreqKhz, currentRadioFrequency);
				if (gradientColor) {
					// Store gradient color and frequency for later reapplication
					addedRow.attr('data-spot-frequency', spotFreqKhz);
					addedRow.attr('data-gradient-color', gradientColor);
					// Use setProperty with priority 'important' to force override
					addedRow.each(function() {
						this.style.setProperty('--bs-table-bg', gradientColor, 'important');
						this.style.setProperty('--bs-table-accent-bg', gradientColor, 'important');
						this.style.setProperty('background-color', gradientColor, 'important');
					});
					addedRow.addClass('cat-frequency-gradient');
				}
			}
		});

		// Remove "fresh" highlight after 10 seconds
		// (CAT gradient is updated every 3s from updateCATui, no need to force here)
		setTimeout(function () {
			$(".fresh").removeClass("fresh");
		}, 10000);

		if (spots2render == 0) {
			table.clear();
			table.settings()[0].oLanguage.sEmptyTable = lang_bandmap_no_data;
			table.draw();
		}

		// Parse emoji flags for proper rendering
		if (typeof twemoji !== 'undefined') {
			twemoji.parse(document.querySelector('.spottable'), {
				folder: 'svg',
				ext: '.svg'
			});
		}

		// Add hover tooltips to all rows
		$('.spottable tbody tr').each(function() {
			$(this).attr('title', lang_click_to_prepare_logging);
		});

		$('[data-bs-toggle="tooltip"]').tooltip();

		let displayedCount = spots2render || 0;

		// Update band count badges after rendering
		updateBandCountBadges();

		// Update CAT frequency gradient colors/borders after rendering if CAT is enabled
		// Force update to ensure borders appear even if frequency hasn't changed
		if (isCatTrackingEnabled && currentRadioFrequency) {
			updateFrequencyGradientColors(true);
		}

		// Update status bar after render completes
		setTimeout(function() {
			if (!isFetchInProgress) {
				let actualDisplayedCount = table.rows({search: 'applied'}).count();
				updateStatusBar(cachedSpotData.length, actualDisplayedCount, getServerFilterText(), getClientFilterText(), false, false);
				$('#refreshIcon').removeClass('fa-spinner fa-spin').addClass('fa-hourglass-half');
				$('#refreshTimer').text(lang_bandmap_next_update + ' ' + refreshCountdown + 's');
			}

			// Update DX Map only if visible (don't waste resources)
			if (dxMapVisible && dxMap) {
				updateDxMap();
			}
		}, 100);
	}

	// ========================================
	// BAND COUNT BADGES
	// ========================================

	// Update badge counts on band and mode filter buttons
	function updateBandCountBadges() {
		// Check if we fetched only a specific band (single band fetch mode)
		// This happens when CAT Control is active and limited the API fetch to current band
		let fetchedBand = null;
		if (loadedBackendFilters && loadedBackendFilters.band && loadedBackendFilters.band !== 'All') {
			fetchedBand = loadedBackendFilters.band;
		}

		if (!cachedSpotData || cachedSpotData.length === 0) {
			// Clear all badges when no data
			if (fetchedBand) {
				// Set all to "-" when in single band fetch mode but no data
				$('.band-count-badge, .mode-count-badge').text('-');
			} else {
				$('.band-count-badge, .mode-count-badge').text('0');
			}
			return;
		}

		// Get current filter values (excluding band and mode since we're counting those)
		let deContinent = currentFilters.deContinent || ['Any'];
		let spottedContinents = currentFilters.spottedContinent || ['Any'];
		let cwnStatuses = currentFilters.cwn || ['All'];
		let flags = currentFilters.additionalFlags || ['All'];
		let requiredFlags = (currentFilters.requiredFlags || []).filter(v => v !== 'None');  // Remove "None"

		// Convert to Sets for O(1) lookups
		const deContinentSet = arrayToFilterSet(deContinent, 'Any');
		const spottedContinentSet = arrayToFilterSet(spottedContinents, 'Any');
		const cwnSet = arrayToFilterSet(cwnStatuses);
		const flagSet = arrayToFilterSet(flags);

		// Get current mode and band selections to apply when counting
		let selectedModes = $('#mode').val() || ['All'];
		let selectedBands = $('#band').val() || ['All'];
		const selectedModeSet = arrayToFilterSet(selectedModes);
		const selectedBandSet = arrayToFilterSet(selectedBands);

		// Count spots per band and mode, applying all OTHER filters
		let bandCounts = {};
		let modeCounts = { cw: 0, digi: 0, phone: 0 };
		let totalSpots = 0;

		cachedSpotData.forEach((spot) => {
			// Apply required flags FIRST (must have ALL selected required flags)
			if (requiredFlags.length > 0) {
				for (let reqFlag of requiredFlags) {
					if (reqFlag === 'lotw') {
						if (!spot.dxcc_spotted || !spot.dxcc_spotted.lotw_user) return;
					}
					if (reqFlag === 'newcontinent') {
						if (spot.worked_continent !== false) return;
					}
					if (reqFlag === 'newcountry') {
						if (spot.worked_dxcc !== false) return;
					}
					if (reqFlag === 'newcallsign') {
						if (spot.worked_call !== false) return;
					}
					if (reqFlag === 'workedcallsign') {
						if (spot.worked_call === false) return;
					}
					if (reqFlag === 'Contest') {
						if (!spot.dxcc_spotted || !spot.dxcc_spotted.isContest) return;
					}
				}
			}

			// Apply CWN (Confirmed/Worked/New) filter
			let passesCwnFilter = cwnStatuses.includes('All');
			if (!passesCwnFilter) {
				if (cwnStatuses.includes('notwkd') && !spot.worked_dxcc) passesCwnFilter = true;
				if (cwnStatuses.includes('wkd') && spot.worked_dxcc) passesCwnFilter = true;
				if (cwnStatuses.includes('cnf') && spot.cnfmd_dxcc) passesCwnFilter = true;
				if (cwnStatuses.includes('ucnf') && spot.worked_dxcc && !spot.cnfmd_dxcc) passesCwnFilter = true;
			}
			if (!passesCwnFilter) return;

			// Apply de continent filter (which continent the spotter is in)
			let passesDeContFilter = deContinent.includes('Any');
			if (!passesDeContFilter && spot.dxcc_spotter && spot.dxcc_spotter.cont) {
				passesDeContFilter = deContinent.includes(spot.dxcc_spotter.cont);
			}
			if (!passesDeContFilter) return;

			// Apply spotted continent filter (which continent the DX station is in)
			let passesContinentFilter = spottedContinents.includes('Any');
			if (!passesContinentFilter) {
				passesContinentFilter = spottedContinents.includes(spot.dxcc_spotted.cont);
			}
			if (!passesContinentFilter) return;

			// Apply additional flags filter (POTA, SOTA, WWFF, IOTA, Fresh)
			let passesFlagsFilter = flags.includes('All');
			if (!passesFlagsFilter) {
				for (let flag of flags) {
					if (flag === 'SOTA' && spot.dxcc_spotted && spot.dxcc_spotted.sota_ref) {
						passesFlagsFilter = true;
						break;
					}
					if (flag === 'POTA' && spot.dxcc_spotted && spot.dxcc_spotted.pota_ref) {
						passesFlagsFilter = true;
						break;
					}
					if (flag === 'WWFF' && spot.dxcc_spotted && spot.dxcc_spotted.wwff_ref) {
						passesFlagsFilter = true;
						break;
					}
					if (flag === 'IOTA' && spot.dxcc_spotted && spot.dxcc_spotted.iota_ref) {
						passesFlagsFilter = true;
						break;
					}
					if (flag === 'Fresh' && (spot.age || 0) < 5) {
						passesFlagsFilter = true;
						break;
					}
				}
			}
			if (!passesFlagsFilter) return;

			// Get spot's band and mode for filtering (both always provided by API)
			const band = spot.band;
			const modeCategory = spot.mode;

			// Count by band (applying MODE filter when counting bands)
			if (band && (!selectedModeSet || (modeCategory && selectedModeSet.has(modeCategory)))) {
				bandCounts[band] = (bandCounts[band] || 0) + 1;
				totalSpots++;
			}

			// Count by mode (applying BAND filter when counting modes)
			if (modeCategory && modeCounts.hasOwnProperty(modeCategory)) {
				let passesBandFilter = !selectedBandSet;
				if (!passesBandFilter && band) {
					if (selectedBandSet.has(band)) {
						passesBandFilter = true;
					} else {
						// Check if band is in a selected group (VHF, UHF, SHF)
						const bandGroup = getBandGroup(band);
						if (bandGroup && selectedBandSet.has(bandGroup)) {
							passesBandFilter = true;
						}
					}
				}
				if (passesBandFilter) {
					modeCounts[modeCategory]++;
				}
			}
		});

		// Count band groups (VHF, UHF, SHF)
		let groupCounts = {
			'VHF': 0,
			'UHF': 0,
			'SHF': 0
		};

		Object.keys(bandCounts).forEach(band => {
			let group = getBandGroup(band);
			if (group) {
				groupCounts[group] += bandCounts[band];
			}
		});

	// Update individual MF/HF band button badges
	const mfHfBands = [
		'160m', '80m', '60m', '40m', '30m', '20m', '17m', '15m', '12m', '10m'
	];

	mfHfBands.forEach(band => {
		let count;
		let displayText;

		// If in single band fetch mode and this is not the fetched band, show "-"
		if (fetchedBand && band !== fetchedBand) {
			displayText = '-';
		} else {
			count = bandCounts[band] || 0;
			displayText = count.toString();
		}

		let $badge = $('#toggle' + band + 'Filter .band-count-badge');
		if ($badge.length === 0) {
			// Badge doesn't exist yet, create it
			$('#toggle' + band + 'Filter').append(' <span class="badge bg-dark band-count-badge">' + displayText + '</span>');
		} else {
			// Update existing badge
			$badge.text(displayText);
		}
	});

	// Update band group button badges (VHF, UHF, SHF)
	['VHF', 'UHF', 'SHF'].forEach(group => {
		let count;
		let displayText;

		// Check if fetched band is in this group
		let isActiveGroup = false;
		if (fetchedBand) {
			let fetchedBandGroup = getBandGroup(fetchedBand);
			isActiveGroup = (fetchedBandGroup === group);
		}

		// If in single band fetch mode and this is not the fetched band's group, show "-"
		if (fetchedBand && !isActiveGroup) {
			displayText = '-';
		} else {
			count = groupCounts[group] || 0;
			displayText = count.toString();
		}

		let $badge = $('#toggle' + group + 'Filter .band-count-badge');
		if ($badge.length === 0) {
			// Badge doesn't exist yet, create it
			$('#toggle' + group + 'Filter').append(' <span class="badge bg-dark band-count-badge">' + displayText + '</span>');
		} else {
			// Update existing badge
			$badge.text(displayText);
		}
	});		// Update mode button badges
		const modeButtons = ['Cw', 'Digi', 'Phone'];
		modeButtons.forEach(mode => {
			let modeKey = mode.toLowerCase();
			let count = modeCounts[modeKey] || 0;
			let $badge = $('#toggle' + mode + 'Filter .mode-count-badge');
			if ($badge.length === 0) {
				// Badge doesn't exist yet, create it
				$('#toggle' + mode + 'Filter').append(' <span class="badge bg-dark mode-count-badge">' + count + '</span>');
			} else {
				// Update existing badge
				$badge.text(count);
			}
		});

	// Count spots for quick filter badges
	let quickFilterCounts = {
		lotw: 0,
		dxspot: 0,
		newcontinent: 0,
		newcountry: 0,
		newcallsign: 0,
		contest: 0,
		geohunter: 0,
		fresh: 0
	};

	cachedSpotData.forEach((spot) => {
		// Cache DXCC references
		const dxccSpotted = spot.dxcc_spotted;
		const dxccSpotter = spot.dxcc_spotter;

		// Apply de continent filter
		if (deContinentSet && (!dxccSpotter || !dxccSpotter.cont || !deContinentSet.has(dxccSpotter.cont))) return;

		// Apply spotted continent filter
		if (spottedContinentSet && (!dxccSpotted || !dxccSpotted.cont || !spottedContinentSet.has(dxccSpotted.cont))) return;

		// Apply CWN status filter
		if (cwnSet) {
			const workedDxcc = spot.worked_dxcc;
			const cnfmdDxcc = spot.cnfmd_dxcc;
			if (!((cwnSet.has('notwkd') && !workedDxcc) ||
				  (cwnSet.has('wkd') && workedDxcc) ||
				  (cwnSet.has('cnf') && cnfmdDxcc) ||
				  (cwnSet.has('ucnf') && workedDxcc && !cnfmdDxcc))) {
				return;
			}
		}

		// Apply band filter
		if (selectedBandSet && !selectedBandSet.has(spot.band)) return;

		// Apply mode filter
		if (selectedModeSet && (!spot.mode || !selectedModeSet.has(spot.mode))) return;

		// Count quick filter matches (use cached references)
		if (dxccSpotted && dxccSpotted.lotw_user) quickFilterCounts.lotw++;
		if (dxccSpotted?.cont && dxccSpotter?.cont && dxccSpotted.cont !== dxccSpotter.cont) quickFilterCounts.dxspot++;
		if (spot.worked_continent === false) quickFilterCounts.newcontinent++;
		if (spot.worked_dxcc === false) quickFilterCounts.newcountry++;
		if (spot.worked_call === false) quickFilterCounts.newcallsign++;
		if (dxccSpotted && dxccSpotted.isContest) quickFilterCounts.contest++;
		if (dxccSpotted && (dxccSpotted.pota_ref || dxccSpotted.sota_ref || dxccSpotted.wwff_ref || dxccSpotted.iota_ref)) quickFilterCounts.geohunter++;
		if ((spot.age || 0) < 5) quickFilterCounts.fresh++;
	});

	// Update quick filter badges
	const quickFilters = [
		{ id: 'toggleLotwFilter', count: quickFilterCounts.lotw },
		{ id: 'toggleDxSpotFilter', count: quickFilterCounts.dxspot },
		{ id: 'toggleNewContinentFilter', count: quickFilterCounts.newcontinent },
		{ id: 'toggleDxccNeededFilter', count: quickFilterCounts.newcountry },
		{ id: 'toggleNewCallsignFilter', count: quickFilterCounts.newcallsign },
		{ id: 'toggleContestFilter', count: quickFilterCounts.contest },
		{ id: 'toggleGeoHunterFilter', count: quickFilterCounts.geohunter },
		{ id: 'toggleFreshFilter', count: quickFilterCounts.fresh }
	];

	quickFilters.forEach(filter => {
		let $badge = $('#' + filter.id + ' .quick-filter-count-badge');
		if ($badge.length === 0) {
			// Badge doesn't exist yet, create it
			$('#' + filter.id).append(' <span class="badge bg-dark quick-filter-count-badge">' + filter.count + '</span>');
		} else {
			// Update existing badge
			$badge.text(filter.count);
		}
	});
}	// ========================================
	// BACKEND DATA FETCH
	// ========================================

	// Fetch spot data from DX cluster API
	// Backend filters: band, de continent (where spotter is), mode
	// Client filters applied after fetch: cwn, spotted continent, additionalFlags
	function fill_list(de, maxAgeMinutes, bandForAPI = 'All') {
		var table = get_dtable();

		// Normalize de continent parameter to array
		let deContinent = Array.isArray(de) ? de : [de];
		if (deContinent.includes('Any') || deContinent.length === 0) deContinent = ['Any'];

		// Backend API only accepts single values for continent
		let continentForAPI = 'Any';
		if (deContinent.length === 1 && !deContinent.includes('Any')) continentForAPI = deContinent[0];

		// bandForAPI is now passed as a parameter from applyFilters()
		// Log if CAT Control influenced the band selection
		if (bandForAPI !== 'All') {

		}

		// Update backend filter state
		loadedBackendFilters = {
			continent: continentForAPI,
			band: bandForAPI
		};

		lastFetchParams.continent = continentForAPI;
		lastFetchParams.band = bandForAPI;
		lastFetchParams.maxAge = maxAgeMinutes;

		// Build API URL: /spots/{band}/{maxAge}/{continent}/{mode}
		// Mode is always 'All' - filtering happens client-side
		let dxurl = dxcluster_provider + "/spots/" + bandForAPI + "/" + maxAgeMinutes + "/" + continentForAPI + "/All";


		// Cancel any in-flight request before starting new one
		if (currentAjaxRequest) {

			currentAjaxRequest.abort();
			currentAjaxRequest = null;
		}

		isFetchInProgress = true;

		updateStatusBar(0, 0, getServerFilterText(), getClientFilterText(), true, false);

		currentAjaxRequest = $.ajax({
			url: dxurl,
			cache: false,
			dataType: "json"
		}).done(function(dxspots) {
			currentAjaxRequest = null;
			table.page.len(50);

			// Check if response is an error object
			if (dxspots && dxspots.error) {
				console.warn('Backend returned error:', dxspots.error);
				cachedSpotData = [];
				table.clear();
				table.settings()[0].oLanguage.sEmptyTable = lang_bandmap_no_spots_filters;
				table.draw();
				updateStatusBar(0, 0, getServerFilterText(), getClientFilterText(), false, false);
				isFetchInProgress = false;
				startRefreshTimer();
				return;
			}

			if (dxspots.length > 0) {
				dxspots.sort(SortByQrg);  // Sort by frequency

				// TTL Management: Process new spots and update TTL values
				// In single-band fetch mode, only update TTL for spots in the fetched band
				let newSpotKeys = new Set();

				// First pass: identify all spots in the new data
				dxspots.forEach(function(spot) {
					let key = getSpotKey(spot);
					newSpotKeys.add(key);
				});

				// Second pass: Update TTL for existing spots
				// - In single-band mode (bandForAPI != 'All'), only decrement TTL for spots in the fetched band
				// - In all-band mode, decrement all TTL values
				// - If spot exists in new data, set TTL back to 1 (stays valid)
				// - Remove spots with TTL < -1
				let ttlStats = { stillValid: 0, expiring: 0, removed: 0, added: 0 };
				let expiringSpots = [];  // Store spots with TTL=0 that need to be shown

				for (let [key, ttl] of spotTTLMap.entries()) {
					let newTTL = ttl;

				// Only decrement TTL if:
				// - We fetched all bands (bandForAPI === 'All'), OR
				// - This spot is in the band we just fetched
				let shouldDecrementTTL = (bandForAPI === 'All');
				if (!shouldDecrementTTL && cachedSpotData) {
					// Look up band from cached spot data (band always provided by API)
					let cachedSpot = cachedSpotData.find(s => getSpotKey(s) === key);
					if (cachedSpot && cachedSpot.band) {
						shouldDecrementTTL = (cachedSpot.band === bandForAPI);
					}
				}					if (shouldDecrementTTL) {
						newTTL = ttl - 1;  // Decrement only if in scope of this fetch
					}

					if (newSpotKeys.has(key)) {
						newTTL = 1;  // Reset to 1 if spot still exists (keeps it valid)
						ttlStats.stillValid++;
					} else if (shouldDecrementTTL) {
						if (newTTL === 0) {
							ttlStats.expiring++;
							// Find the spot in previous cachedSpotData to keep it for display
							if (cachedSpotData) {
								let expiringSpot = cachedSpotData.find(s => getSpotKey(s) === key);
								if (expiringSpot) {
									expiringSpots.push(expiringSpot);
								}
							}
						}
					}

					if (newTTL < -1) {
						spotTTLMap.delete(key);  // Remove completely hidden spots
						ttlStats.removed++;
					} else {
						spotTTLMap.set(key, newTTL);
					}
				}

				// Third pass: Add new spots that weren't in the map
				dxspots.forEach(function(spot) {
					let key = getSpotKey(spot);
					if (!spotTTLMap.has(key)) {
						spotTTLMap.set(key, 1);  // New spot starts with TTL = 1
						ttlStats.added++;
					}
				});


				if (expiringSpots.length > 0) {

				}

				// Merge new spots with expiring spots (TTL=0) for display
				cachedSpotData = dxspots.concat(expiringSpots);
				cachedSpotData.sort(SortByQrg);  // Re-sort after merging
			} else {
				cachedSpotData = [];
			}

			lastFetchParams.timestamp = new Date();
			isFetchInProgress = false;

			renderFilteredSpots();  // Apply client-side filters and render
			startRefreshTimer();  // Start 10s countdown - TEMPORARY

		}).fail(function(jqXHR, textStatus) {
			currentAjaxRequest = null;

			// Don't show error if user cancelled the request
			if (textStatus === 'abort') {

				return;
			}

			cachedSpotData = null;
			isFetchInProgress = false;
			table.clear();
			table.settings()[0].oLanguage.sEmptyTable = lang_bandmap_error_loading;
			table.draw();
			updateStatusBar(0, 0, getServerFilterText(), getClientFilterText(), false, false);
			startRefreshTimer();
		});
	}	// Highlight rows within ±20 kHz of specified frequency (for CAT integration)
	// Old highlight_current_qrg function removed - now using updateFrequencyGradientColors

	// Initialize DataTable
	var table=get_dtable();
	table.order([1, 'asc']);  // Sort by frequency column
	table.clear();

	// ========================================
	// HELPER FUNCTIONS
	// ========================================

	// Build a badge HTML string with consistent styling
	// type: badge color (success/primary/info/warning/danger)
	// icon: FontAwesome icon class (e.g., 'fa-tree')
	// title: tooltip text
	// text: optional text content instead of icon
	// isLast: if true, uses margin: 0 instead of negative margin
	function buildBadge(type, icon, title, text = null, isLast = false) {
		const margin = isLast ? '0' : '0 2px 0 0';
		const fontSize = text ? '0.75rem' : '0.7rem';
		const content = text ? text : '<i class="fas ' + icon + '" style="display: block;"></i>';
		return '<small class="badge text-bg-' + type + '" style="display: inline-flex; align-items: center; justify-content: center; width: 20px; height: 20px; padding: 0; margin: ' + margin + '; font-size: ' + fontSize + '; line-height: 1;" data-bs-toggle="tooltip" title="' + title + '">' + content + '</small>';
	}

	// Use BAND_GROUPS from radiohelpers.js (loaded globally in footer)
	// Note: These functions are now available globally, but we keep local references for consistency
	// If radiohelpers not loaded, fallback to local definition (shouldn't happen in production)

	// Get selected values from multi-select dropdown
	function getSelectedValues(selectId) {
		let values = $('#' + selectId).val();
		if (!values || values.length === 0) {
			return ['All'];
		}
		return values;
	}

	// ========================================
	// SMART FILTER APPLICATION
	// ========================================

	// Intelligently decide whether to reload from backend or filter client-side
	// Backend filter (requires new API call): de continent only
	// Client filters (use cached data): band, mode, cwn, spotted continent, requiredFlags, additionalFlags
	function applyFilters(forceReload = false) {
		let band = getSelectedValues('band');
		let de = getSelectedValues('decontSelect');
		let continent = getSelectedValues('continentSelect');
		let cwn = getSelectedValues('cwnSelect');
		let mode = getSelectedValues('mode');
		let additionalFlags = getSelectedValues('additionalFlags');
		let requiredFlags = ($('#requiredFlags').val() || []).filter(v => v !== 'None');  // Remove "None"

		let continentForAPI = 'Any';
		if (de.length === 1 && !de.includes('Any')) {
			// Single continent selected - use backend filter
			continentForAPI = de[0];
		}
		// If multiple continents selected, fetch 'Any' from backend and filter client-side

		// Determine if we're in single-band fetch mode (CAT Control active)
		// In this mode, band changes require a new server fetch
		// Use the selected band filter value when CAT is active and a single band is selected
		let bandForAPI = 'All';
		let isSingleBandMode = false;
		if (isCatTrackingEnabled) {
			// Check if a single specific band is selected (not 'All')
			if (band.length === 1 && !band.includes('All') && band[0] !== '') {
				bandForAPI = band[0];
				isSingleBandMode = true;
			}
		}




		// Check if backend parameters changed (requires new data fetch)
		// In single-band mode, band selection changes also require server fetch
		let backendParamsChanged = forceReload ||
			loadedBackendFilters.continent !== continentForAPI ||
			(isSingleBandMode && loadedBackendFilters.band !== bandForAPI);



		// Always update current filters for client-side filtering
		currentFilters = {
			band: band,
			deContinent: de,              // Spots FROM continent (server filter)
			spottedContinent: continent,  // Spotted STATION continent (client filter)
			cwn: cwn,
			mode: mode,
			requiredFlags: requiredFlags,
			additionalFlags: additionalFlags
		};

		if (backendParamsChanged) {

			table.clear();
			fill_list(de, dxcluster_maxage, bandForAPI);
		} else {

			renderFilteredSpots();
			updateBandCountBadges();
		}

		updateFilterIcon();
	}

	initializeBackendFilters();

	initFilterCheckboxes();

	applyFilters(true);

	// Sync button states on initial load
	syncQuickFilterButtons();
	updateFilterIcon();

	$("#applyFiltersButtonPopup").on("click", function() {
		applyFilters(false);
		$('#filterDropdown').dropdown('hide');
	});

	$("#clearFiltersButton").on("click", function() {
		// Preserve current band selection if CAT Control is enabled
		let currentBand = isCatTrackingEnabled ? $('#band').val() : null;

		$('#cwnSelect').val(['All']);
		$('#decontSelect').val(['Any']);
		$('#continentSelect').val(['Any']);
		$('#band').val(currentBand || ['All']); // Preserve band if CAT is enabled
		$('#mode').val(['All']);
		$('#additionalFlags').val(['All']);
		$('#requiredFlags').val([]);

		// Update checkbox indicators for all selects
		updateSelectCheckboxes('cwnSelect');
		updateSelectCheckboxes('decontSelect');
		updateSelectCheckboxes('continentSelect');
		updateSelectCheckboxes('band');
		updateSelectCheckboxes('mode');
		updateSelectCheckboxes('additionalFlags');
		updateSelectCheckboxes('requiredFlags');

		// Clear text search
		$('#spotSearchInput').val('');
		table.search('').draw();
		$('#clearSearchBtn').hide();

		syncQuickFilterButtons();
		updateFilterIcon();
		applyFilters(true);
		$('#filterDropdown').dropdown('hide');

		if (isCatTrackingEnabled && typeof showToast === 'function') {
			showToast(lang_bandmap_clear_filters, lang_bandmap_band_preserved, 'bg-info text-white', 2000);
		}
	});

	// Clear Filters Quick Button (preserves De Continent)
	$("#clearFiltersButtonQuick").on("click", function() {
		// Preserve current De Continent selection
		let currentDecont = $('#decontSelect').val();
		// Preserve current band selection if CAT Control is enabled
		let currentBand = isCatTrackingEnabled ? $('#band').val() : null;

		// Reset all other filters
		$('#cwnSelect').val(['All']).trigger('change');
		$('#continentSelect').val(['Any']).trigger('change');
		$('#band').val(currentBand || ['All']).trigger('change'); // Preserve band if CAT is enabled
		$('#mode').val(['All']).trigger('change');
		$('#additionalFlags').val(['All']).trigger('change');
		$('#requiredFlags').val([]).trigger('change');

		// Restore De Continent
		$('#decontSelect').val(currentDecont).trigger('change');

		// Clear text search
		$('#spotSearchInput').val('');
		table.search('').draw();
		$('#clearSearchBtn').hide();

		syncQuickFilterButtons();
		updateFilterIcon();
		applyFilters(false);  // Don't refetch from server since De Continent is preserved

		if (isCatTrackingEnabled && typeof showToast === 'function') {
			showToast(lang_bandmap_clear_filters, lang_bandmap_band_preserved, 'bg-info text-white', 2000);
		}
	});

	// Sync button states when dropdown is shown
	$('#filterDropdown').on('show.bs.dropdown', function() {
		syncQuickFilterButtons();
	});

	// Sync button states when dropdown is hidden
	$('#filterDropdown').on('hide.bs.dropdown', function() {
		syncQuickFilterButtons();
	});

	$("#spotSearchInput").on("keyup", function() {
		table.search(this.value).draw();
		// Show/hide clear button based on input value
		if (this.value.length > 0) {
			$('#clearSearchBtn').show();
		} else {
			$('#clearSearchBtn').hide();
		}
	});

	$("#spotSearchInput").on("input", function() {
		const cursorPos = this.selectionStart;
		const oldValue = this.value;
		const newValue = oldValue.replace(/0/g, "Ø");

		if (newValue !== oldValue) {
			this.value = newValue;
			// Restore cursor position
			this.setSelectionRange(cursorPos, cursorPos);
			// Trigger search with new value
			table.search(newValue).draw();
		}

		// Show/hide clear button based on input value
		if (this.value.length > 0) {
			$('#clearSearchBtn').show();
		} else {
			$('#clearSearchBtn').hide();
		}
	});

	// Clear search button handler
	$("#clearSearchBtn").on("click", function() {
		$('#spotSearchInput').val('');
		table.search('').draw();
		$('#clearSearchBtn').hide();
		$('#spotSearchInput').focus();
	});

	$("#searchIcon").on("click", function() {
		const searchValue = $("#spotSearchInput").val();
		if (searchValue.length > 2) {
			table.search(searchValue).draw();
		}
	});

	$("#radio").on("change", function() {
		let selectedRadio = $(this).val();

		// If "None" (value "0") is selected, automatically disable CAT Control
		if (selectedRadio === "0") {


			// If CAT Control is currently enabled, turn it off
			if (isCatTrackingEnabled) {
				let btn = $('#toggleCatTracking');
				btn.removeClass('btn-success').addClass('btn-secondary');
				isCatTrackingEnabled = false;
				window.isCatTrackingEnabled = false;

				// Show offline status instead of just hiding
				if (typeof window.displayOfflineStatus === 'function') {
					window.displayOfflineStatus('no_radio');
				} else {
					$('#radio_cat_state').remove();
				}

				// Re-enable band filter controls
				enableBandFilterControls();

				// Unlock table sorting
				unlockTableSorting();

				// Reset band filter to 'All' and fetch all bands
				const currentBands = $("#band").val() || [];
				if (currentBands.length !== 1 || currentBands[0] !== 'All') {

					$("#band").val(['All']);
					updateSelectCheckboxes('band');
					syncQuickFilterButtons();
					applyFilters(true); // Force reload to fetch all bands
				}

				if (typeof showToast === 'function') {
					showToast(lang_bandmap_radio, lang_bandmap_radio_none, 'bg-info text-white', 3000);
				}
			}
		}
	});

	$("#spottertoggle").on("click", function() {
		if (table.column(4).visible()) {
			table.column(4).visible(false);
		} else {
			table.column(4).visible(true);
		}
	});

	let qso_window_last_seen=Date.now()-3600;
	let bc_qsowin = new BroadcastChannel('qso_window');
	let pong_rcvd = false;

	bc_qsowin.onmessage = function (ev) {
		if (ev.data == 'pong') {
			qso_window_last_seen=Date.now();
			pong_rcvd = true;
		}
	};

	setInterval(function () {
		if (qso_window_last_seen < (Date.now()-1000)) {
			pong_rcvd = false;
		}
		bc_qsowin.postMessage('ping');
	},500);

	let bc2qso = new BroadcastChannel('qso_wish');

	let wait4pong = 2000;
	let check_intv = 100;

	/**
	 * Tune radio to specified frequency when CAT Control is active
	 * Uses determineRadioMode() from radiohelpers.js for mode selection
	 * @param {number} freqHz - Frequency in Hz
	 * @param {string} mode - Mode (optional, e.g., 'USB', 'LSB', 'CW')
	 */
	function tuneRadio(freqHz, mode) {
		const selectedRadio = $('.radios option:selected').val();

		if (!selectedRadio || selectedRadio === '0') {

			return;
		}

		// Determine the best radio mode based on spot mode and frequency
		const radioMode = determineRadioMode(mode, freqHz);

		if (typeof window.tuneRadioToFrequency === 'function') {
			window.tuneRadioToFrequency(
				selectedRadio,
				freqHz,
				radioMode, // Use determined radio mode
				function() {
					// Success callback

					if (typeof showToast === 'function') {
						showToast(lang_bandmap_radio_tuned, `${lang_bandmap_tuned_to} ${(freqHz / 1000000).toFixed(3)} MHz (${radioMode})`, 'bg-success text-white', 2000);
					}
				},
				function(jqXHR, textStatus, errorThrown) {
					// Error callback
					console.error('Failed to tune radio:', errorThrown);
					if (typeof showToast === 'function') {
						showToast(lang_bandmap_tuning_failed, lang_bandmap_tune_failed_msg, 'bg-danger text-white', 3000);
					}
				}
			);
		} else {
			console.error('tuneRadioToFrequency function not available');
		}
	}

	function prepareLogging(call, qrg, mode, spotData) {
		let ready_listener = true;

		// If CAT Control is enabled, tune the radio to the spot frequency
		if (isCatTrackingEnabled) {
			tuneRadio(qrg, mode);
		}

		// Build message object with backward compatibility
		let message = {
			frequency: qrg,
			frequency_rx: "", // Default empty for non-split operation
			call: call
		};

		// Add mode with fallback to SSB (backward compatible - optional field)
		if (mode) {
			// Determine appropriate radio mode based on spot mode and frequency
			message.mode = determineRadioMode(mode, qrg);
		} else {
			// Fallback to SSB based on frequency
			message.mode = qrg < 10000000 ? 'LSB' : 'USB';
		}

		// If radio is in split mode, include the RX frequency
		if (window.lastCATData && window.lastCATData.frequency_rx) {
			message.frequency_rx = window.lastCATData.frequency_rx;

		}

		// Add reference fields if available (backward compatible - only if spotData exists)
		if (spotData && spotData.dxcc_spotted) {

			if (spotData.dxcc_spotted.pota_ref) {
				message.pota_ref = spotData.dxcc_spotted.pota_ref;

			}
			if (spotData.dxcc_spotted.sota_ref) {
				message.sota_ref = spotData.dxcc_spotted.sota_ref;

			}
			if (spotData.dxcc_spotted.wwff_ref) {
				message.wwff_ref = spotData.dxcc_spotted.wwff_ref;

			}
			if (spotData.dxcc_spotted.iota_ref) {
				message.iota_ref = spotData.dxcc_spotted.iota_ref;

			}
		} else {

		}



		let check_pong = setInterval(function() {
			if (pong_rcvd || ((Date.now() - qso_window_last_seen) < wait4pong)) {
				clearInterval(check_pong);
				bc2qso.postMessage(message);
				// Show toast notification when callsign is sent to existing QSO window
				showToast(lang_bandmap_qso_prepared, `${lang_bandmap_callsign_sent} ${call} ${lang_bandmap_sent_to_form}`, 'bg-success text-white', 3000);
			} else {
				clearInterval(check_pong);
				let cl = message;  // Use the message object with all fields

			let newWindow = window.open(base_url + 'index.php/qso?manual=0', '_blank');

			if (!newWindow || newWindow.closed || typeof newWindow.closed === 'undefined') {
				// Pop-up was blocked - show toast notification
				if (typeof showToast === 'function') {
					showToast(lang_bandmap_popup_blocked, lang_bandmap_popup_warning, 'bg-danger text-white', 5000);
				}
			} else {
				newWindow.focus();
				// Show toast notification when opening new QSO window
				showToast(lang_bandmap_qso_prepared, `${lang_bandmap_callsign_sent} ${call} ${lang_bandmap_sent_to_form}`, 'bg-success text-white', 3000);
			}
                bc2qso.onmessage = function(ev) {
					if (ready_listener == true) {
						if (ev.data === 'ready') {
							bc2qso.postMessage(cl);  // Send the full message object with references
							ready_listener = false;
						}
					}
				};
			}
		}, check_intv);
	}

	$(document).on('click','#prepcall', function() {
		let call=this.innerText;
		let qrg=''
		let mode='';
		if (this.parentNode.parentNode.className.indexOf('spotted_call')>=0) {
			qrg=this.parentNode.parentNode.parentNode.cells[1].textContent*1000;
			mode=this.parentNode.parentNode.parentNode.cells[2].textContent;
		} else {
			qrg=this.parentNode.parentNode.cells[1].textContent*1000;
			mode=this.parentNode.parentNode.cells[2].textContent;
		}

		// Find the original spot data to get reference information
		let spotData = null;
		if (cachedSpotData) {
			// Note: spot.frequency is in kHz, so multiply by 1000 to get Hz
			spotData = cachedSpotData.find(spot =>
				spot.spotted === call &&
				Math.abs(spot.frequency * 1000 - qrg) < 100  // Match within 100 Hz tolerance
			);
		}

		prepareLogging(call, qrg, mode, spotData);
	});

	$("#menutoggle").on("click", function() {
		if ($('.navbar').is(":hidden")) {
			$('.navbar').show();
			$('#dxtabs').show();
			$('#dxtitle').show();
			$('#menutoggle_i').removeClass('fa-arrow-down');
			$('#menutoggle_i').addClass('fa-arrow-up');
		} else {
			$('.navbar').hide();
			$('#dxtabs').hide();
			$('#dxtitle').hide();
			$('#menutoggle_i').removeClass('fa-arrow-up');
			$('#menutoggle_i').addClass('fa-arrow-down');
		}
	});

	// ========================================
	// CAT CONTROL INTEGRATION
	// ========================================
	// Note: WebSocket and AJAX polling infrastructure is handled by cat.js
	// We extend cat.js functionality for bandmap-specific band filtering

	var isCatTrackingEnabled = false; // Track CAT Control button state
	window.isCatTrackingEnabled = isCatTrackingEnabled; // Expose to window for cat.js
	var currentRadioFrequency = null; // Store current radio frequency in kHz
	var lastGradientFrequency = null; // Track last frequency used for gradient update

	// Three-state CAT control: 'off', 'on', 'on+marker'
	var catState = 'off';
	var isFrequencyMarkerEnabled = false;

	// Click detection for single/double-click
	var catClickTimer = null;
	var catClickPreventSingle = false;

	/**
	 * Calculate frequency gradient color based on distance from radio frequency
	 * @param {number} spotFreqKhz - Spot frequency in kHz
	 * @param {number} radioFreqKhz - Radio frequency in kHz
	 * @returns {string|null} - CSS background color or null if outside gradient range
	 */
	function getFrequencyGradientColor(spotFreqKhz, radioFreqKhz) {
		if (!radioFreqKhz || !isCatTrackingEnabled || !isFrequencyMarkerEnabled) {
			return null;
		}

		// Determine if we're in LSB or USB mode (below/above 10 MHz)
		const isLSB = radioFreqKhz < 10000;

		// Calculate frequency difference in kHz
		// For LSB: lower frequencies are "closer" to tune (radio freq - spot freq)
		// For USB: higher frequencies are "closer" to tune (spot freq - radio freq)
		let freqDiff;
		if (isLSB) {
			freqDiff = Math.abs(radioFreqKhz - spotFreqKhz);
		} else {
			freqDiff = Math.abs(spotFreqKhz - radioFreqKhz);
		}

		// Maximum gradient range: 2.5 kHz
		const maxGradientKhz = 2.5;

		if (freqDiff > maxGradientKhz) {
			return null; // Outside gradient range, use default color
		}

		// Calculate gradient factor: 0 (perfectly tuned) to 1 (2.5 kHz away)
		const gradientFactor = freqDiff / maxGradientKhz;

		// Violet color for perfectly tuned: rgb(138, 43, 226) - BlueViolet
		// Fade to transparent as we move away
		const alpha = 1 - gradientFactor; // 1 at perfect tune, 0 at 2.5 kHz
		const intensity = 0.3 + (alpha * 0.4); // Range from 0.3 to 0.7 alpha

		return `rgba(138, 43, 226, ${intensity})`;
	}

	/**
	 * Update frequency gradient colors for all visible table rows
	 * Called when radio frequency changes
	 */
	function updateFrequencyGradientColors(forceUpdate = false) {
		if (!isCatTrackingEnabled || !isFrequencyMarkerEnabled || !currentRadioFrequency) {
			return;
		}

		// Skip update if frequency hasn't changed significantly (unless forced)
		// Only update if frequency changed by more than 500 Hz to reduce flickering
		if (!forceUpdate && lastGradientFrequency !== null) {
			const freqDiff = Math.abs(currentRadioFrequency - lastGradientFrequency);
			if (freqDiff < 0.5) { // 500 Hz threshold
				return;
			}
		}

		lastGradientFrequency = currentRadioFrequency;
		var table = get_dtable();
		let coloredCount = 0;
		let nearestAbove = null; // Spot above current frequency
		let nearestBelow = null; // Spot below current frequency
		let minDistanceAbove = Infinity;
		let minDistanceBelow = Infinity;
		let nearestAboveFreq = null; // Track frequency of nearest above
		let nearestBelowFreq = null; // Track frequency of nearest below

		// Iterate through all visible rows
		table.rows({ search: 'applied' }).every(function() {
			const row = this.node();
			const rowData = this.data();

			if (!rowData || !rowData[2]) return;

			// Get spot frequency (column 2, in MHz)
			const spotFreqMhz = parseFloat(rowData[2]);
			const spotFreqKhz = spotFreqMhz * 1000;

			// Calculate gradient color
			const gradientColor = getFrequencyGradientColor(spotFreqKhz, currentRadioFrequency);

			// Store gradient data for persistence
			$(row).attr('data-spot-frequency', spotFreqKhz);

			// Track nearest spots above and below current frequency
			// For nearestAbove (gets BOTTOM border): use <= to select LAST occurrence (bottommost in group)
			// For nearestBelow (gets TOP border): use < to select FIRST occurrence (topmost in group)
			const distance = spotFreqKhz - currentRadioFrequency;
			if (distance > 0) {
				// Spot is above current frequency
				if (distance <= minDistanceAbove) {
					minDistanceAbove = distance;
					nearestAbove = row;
					nearestAboveFreq = spotFreqKhz;
				}
			} else if (distance < 0) {
				// Spot is below current frequency
				const absDistance = Math.abs(distance);
				if (absDistance < minDistanceBelow) {
					minDistanceBelow = absDistance;
					nearestBelow = row;
					nearestBelowFreq = spotFreqKhz;
				}
			}

			if (gradientColor) {
				coloredCount++;
				// Store and apply gradient color directly to override Bootstrap striping
				$(row).attr('data-gradient-color', gradientColor);
				// Use setProperty with 'important' priority to force override .fresh, .spot-expiring, etc.
				row.style.setProperty('--bs-table-bg', gradientColor, 'important');
				row.style.setProperty('--bs-table-accent-bg', gradientColor, 'important');
				row.style.setProperty('background-color', gradientColor, 'important');
				$(row).addClass('cat-frequency-gradient');
				// Remove border markers if spot has gradient
				$(row).removeClass('cat-nearest-above cat-nearest-below');
			} else {
				// Remove gradient styling if outside range
				$(row).removeAttr('data-gradient-color');
				$(row).removeClass('cat-frequency-gradient');
				row.style.removeProperty('--bs-table-bg');
				row.style.removeProperty('--bs-table-accent-bg');
				row.style.removeProperty('background-color');
			}
		});

		// If no spots are colored, add purple borders to nearest spots above/below
		// NOTE: Table is sorted DESC, so higher frequencies appear at TOP, lower at BOTTOM
		// Borders point TOWARD current frequency to create visual bracket
		if (coloredCount === 0) {
			// First, remove any existing border classes from all rows
			table.rows().every(function() {
				$(this.node()).removeClass('cat-nearest-above cat-nearest-below');
			});





			// Spot BELOW current freq (lower number) appears at BOTTOM of DESC table → TOP border points UP toward you
			if (nearestBelow) {
				$(nearestBelow).addClass('cat-nearest-below');
			}
			// Spot ABOVE current freq (higher number) appears at TOP of DESC table → BOTTOM border points DOWN toward you
			if (nearestAbove) {
				$(nearestAbove).addClass('cat-nearest-above');
			}
		} else {
			// Remove border indicators when spots are in gradient range
			table.rows().every(function() {
				$(this.node()).removeClass('cat-nearest-above cat-nearest-below');
			});

		}
	}	// Save reference to cat.js's updateCATui if it exists
	var catJsUpdateCATui = window.updateCATui;

	// Override updateCATui to add bandmap-specific behavior
	window.updateCATui = function(data) {
		// Determine band from frequency
		const band = frequencyToBand(data.frequency);
		// Store current radio frequency (convert Hz to kHz)
		currentRadioFrequency = data.frequency / 1000;

		// Bandmap-specific: Update band filter if CAT Control is enabled
		if (isCatTrackingEnabled) {
			const currentBands = $("#band").val() || [];

			if (band && band !== '') {
				// Valid band found - set filter to this specific band
				// Check if current selection is not just this band
				if (currentBands.length !== 1 || currentBands[0] !== band) {

					$("#band").val([band]);
					updateSelectCheckboxes('band');
					syncQuickFilterButtons();
					applyFilters(false);
					// Show toast notification when band filter is changed by CAT
					if (typeof showToast === 'function') {
						showToast(lang_bandmap_cat_control, `${lang_bandmap_freq_changed} ${band} ${lang_bandmap_by_transceiver}`, 'bg-info text-white', 3000);
					}
				}
			} else {
				// No band match - clear band filter to show all bands
				// Only update if not already showing all bands
				if (currentBands.length !== 1 || currentBands[0] !== 'All') {

					$("#band").val(['All']);
					updateSelectCheckboxes('band');
					syncQuickFilterButtons();
					applyFilters(false);
					// Show toast notification
					if (typeof showToast === 'function') {
						showToast(lang_bandmap_cat_control, lang_bandmap_freq_outside, 'bg-warning text-dark', 3000);
					}
				}
			}

			// Update frequency gradient colors for all visible rows
			updateFrequencyGradientColors();
		}

		// Call cat.js's original updateCATui for standard CAT UI updates
		if (typeof catJsUpdateCATui === 'function') {

			// Store current band selection before calling cat.js updateCATui
			const bandBeforeUpdate = $("#band").val();

			catJsUpdateCATui(data);

			// If CAT Control is OFF, restore the band selection
			// (cat.js updateCATui automatically sets band based on frequency, but we don't want that on bandmap unless CAT Control is ON)
			if (!isCatTrackingEnabled && bandBeforeUpdate) {
				$("#band").val(bandBeforeUpdate);
				updateSelectCheckboxes('band');
			}
		} else {
			console.warn('Bandmap: cat.js updateCATui not available');
		}
	};

	$.fn.dataTable.moment(custom_date_format + ' HH:mm');

	let isFullscreen = false;

	// Handle clicks on both the button and wrapper
	$('#fullscreenToggle, #fullscreenToggleWrapper').on('click', function(e) {
		// Prevent double firing if clicking directly on button
		if (e.target.id === 'fullscreenToggle' && this.id === 'fullscreenToggleWrapper') {
			return;
		}

		const container = $('#bandmapContainer');
		const icon = $('#fullscreenIcon');

		if (!isFullscreen) {
			container.addClass('bandmap-fullscreen');
			$('body').addClass('fullscreen-active');
			icon.removeClass('fa-expand').addClass('fa-compress');
			$('#fullscreenToggle').attr('title', lang_bandmap_exit_fullscreen);

			isFullscreen = true;

			// Request browser fullscreen
			const elem = document.documentElement;
			if (elem.requestFullscreen) {
				elem.requestFullscreen().catch(err => {

				});
			} else if (elem.webkitRequestFullscreen) { // Safari
				elem.webkitRequestFullscreen();
			} else if (elem.msRequestFullscreen) { // IE11
				elem.msRequestFullscreen();
			}

			setTimeout(function() {
				if ($.fn.DataTable.isDataTable('.spottable')) {
					$('.spottable').DataTable().columns.adjust();
				}
			}, 100);
		} else {
			container.removeClass('bandmap-fullscreen');
			$('body').removeClass('fullscreen-active');
			icon.removeClass('fa-compress').addClass('fa-expand');
			$(this).attr('title', lang_bandmap_toggle_fullscreen);

			isFullscreen = false;

			// Exit browser fullscreen
			if (document.exitFullscreen) {
				document.exitFullscreen().catch(err => {

				});
			} else if (document.webkitExitFullscreen) { // Safari
				document.webkitExitFullscreen();
			} else if (document.msExitFullscreen) { // IE11
				document.msExitFullscreen();
			}

			setTimeout(function() {
				if ($.fn.DataTable.isDataTable('.spottable')) {
					$('.spottable').DataTable().columns.adjust();
				}
			}, 100);
		}
	});

	$(document).on('keydown', function(e) {
		if (e.key === 'Escape' && isFullscreen) {
			$('#fullscreenToggle').click();
		}
	});

	// ========================================
	// QUICK FILTER TOGGLE BUTTONS
	// ========================================

	// Toggle CW mode filter
	$('#toggleCwFilter').on('click', function() {
		let currentValues = $('#mode').val() || [];

		// Remove 'All' if present
		if (currentValues.includes('All')) {
			currentValues = currentValues.filter(v => v !== 'All');
		}

		if (currentValues.includes('cw')) {
			// Remove CW filter
			currentValues = currentValues.filter(v => v !== 'cw');
			if (currentValues.length === 0) currentValues = ['All'];
		} else {
			// Add CW filter
			currentValues.push('cw');
			// Check if all modes are now selected
			if (currentValues.includes('cw') && currentValues.includes('digi') && currentValues.includes('phone')) {
				currentValues = ['All'];
			}
		}

		$('#mode').val(currentValues).trigger('change');
		syncQuickFilterButtons();
		updateBandCountBadges();
		applyFilters(false);
	});

	// Toggle Digital mode filter
	$('#toggleDigiFilter').on('click', function() {
		let currentValues = $('#mode').val() || [];

		// Remove 'All' if present
		if (currentValues.includes('All')) {
			currentValues = currentValues.filter(v => v !== 'All');
		}

		if (currentValues.includes('digi')) {
			// Remove Digi filter
			currentValues = currentValues.filter(v => v !== 'digi');
			if (currentValues.length === 0) currentValues = ['All'];
		} else {
			// Add Digi filter
			currentValues.push('digi');
			// Check if all modes are now selected
			if (currentValues.includes('cw') && currentValues.includes('digi') && currentValues.includes('phone')) {
				currentValues = ['All'];
			}
		}

		$('#mode').val(currentValues).trigger('change');
		syncQuickFilterButtons();
		updateBandCountBadges();
		applyFilters(false);
	});

	// Toggle Phone mode filter
	$('#togglePhoneFilter').on('click', function() {
		let currentValues = $('#mode').val() || [];

		// Remove 'All' if present
		if (currentValues.includes('All')) {
			currentValues = currentValues.filter(v => v !== 'All');
		}

		if (currentValues.includes('phone')) {
			// Remove Phone filter
			currentValues = currentValues.filter(v => v !== 'phone');
			if (currentValues.length === 0) currentValues = ['All'];
		} else {
			// Add Phone filter
			currentValues.push('phone');
			// Check if all modes are now selected
			if (currentValues.includes('cw') && currentValues.includes('digi') && currentValues.includes('phone')) {
				currentValues = ['All'];
			}
		}

		$('#mode').val(currentValues).trigger('change');
		syncQuickFilterButtons();
		updateBandCountBadges();
		applyFilters(false);
	});

	// Band filter buttons - Individual bands
	$('#toggle160mFilter').on('click', () => toggleFilterValue('#band', '160m'));
	$('#toggle80mFilter').on('click', () => toggleFilterValue('#band', '80m'));
	$('#toggle60mFilter').on('click', () => toggleFilterValue('#band', '60m'));
	$('#toggle40mFilter').on('click', () => toggleFilterValue('#band', '40m'));
	$('#toggle30mFilter').on('click', () => toggleFilterValue('#band', '30m'));
	$('#toggle20mFilter').on('click', () => toggleFilterValue('#band', '20m'));
	$('#toggle17mFilter').on('click', () => toggleFilterValue('#band', '17m'));
	$('#toggle15mFilter').on('click', () => toggleFilterValue('#band', '15m'));
	$('#toggle12mFilter').on('click', () => toggleFilterValue('#band', '12m'));
	$('#toggle10mFilter').on('click', () => toggleFilterValue('#band', '10m'));
	$('#toggle6mFilter').on('click', () => toggleFilterValue('#band', '6m'));
	$('#toggle4mFilter').on('click', () => toggleFilterValue('#band', '4m'));
	$('#toggle2mFilter').on('click', () => toggleFilterValue('#band', '2m'));
	$('#toggle70cmFilter').on('click', () => toggleFilterValue('#band', '70cm'));
	$('#toggle23cmFilter').on('click', () => toggleFilterValue('#band', '23cm'));

	// Band group filter buttons (VHF, UHF, SHF, SAT)
	$('#toggleVHFFilter').on('click', function() {
		let currentValues = $('#band').val() || [];
		if (currentValues.includes('All')) currentValues = currentValues.filter(v => v !== 'All');

		const vhfBands = getBandsInGroup('VHF');
		const hasAllVHF = vhfBands.every(b => currentValues.includes(b));

		if (hasAllVHF) {
			// Remove all VHF bands
			currentValues = currentValues.filter(v => !vhfBands.includes(v));
			if (currentValues.length === 0) currentValues = ['All'];
		} else {
			// Add all VHF bands
			vhfBands.forEach(b => {
				if (!currentValues.includes(b)) currentValues.push(b);
			});
		}
		$('#band').val(currentValues).trigger('change');
		syncQuickFilterButtons();
		applyFilters(false);
	});

	$('#toggleUHFFilter').on('click', function() {
		let currentValues = $('#band').val() || [];
		if (currentValues.includes('All')) currentValues = currentValues.filter(v => v !== 'All');

		const uhfBands = getBandsInGroup('UHF');
		const hasAllUHF = uhfBands.every(b => currentValues.includes(b));

		if (hasAllUHF) {
			// Remove all UHF bands
			currentValues = currentValues.filter(v => !uhfBands.includes(v));
			if (currentValues.length === 0) currentValues = ['All'];
		} else {
			// Add all UHF bands
			uhfBands.forEach(b => {
				if (!currentValues.includes(b)) currentValues.push(b);
			});
		}
		$('#band').val(currentValues).trigger('change');
		syncQuickFilterButtons();
		applyFilters(false);
	});

	$('#toggleSHFFilter').on('click', function() {
		let currentValues = $('#band').val() || [];
		if (currentValues.includes('All')) currentValues = currentValues.filter(v => v !== 'All');

		const shfBands = getBandsInGroup('SHF');
		const hasAllSHF = shfBands.every(b => currentValues.includes(b));

		if (hasAllSHF) {
			// Remove all SHF bands
			currentValues = currentValues.filter(v => !shfBands.includes(v));
			if (currentValues.length === 0) currentValues = ['All'];
		} else {
			// Add all SHF bands
			shfBands.forEach(b => {
				if (!currentValues.includes(b)) currentValues.push(b);
			});
		}
		$('#band').val(currentValues).trigger('change');
		syncQuickFilterButtons();
		applyFilters(false);
	});

	// "All" continents button - select all continents (de continent)
	$('#toggleAllContinentsFilter').on('click', function() {
		// Always set to "Any" to show "Any" selected in the filter popup
		let currentValues = ['Any'];

		$('#decontSelect').val(currentValues).trigger('change');
		syncQuickFilterButtons();

		// Update badge counts immediately (before debounced filter application)
		updateBandCountBadges();

		// Debounce the filter application (3 second cooldown)
		clearTimeout(window.filterDebounceTimer);
		window.filterDebounceTimer = setTimeout(function() {
			applyFilters(false);
		}, 3000);
	});

	// Continent filter buttons (spotter's continent - de continent) - with 3s debounce
	$('#toggleAfricaFilter').on('click', () => toggleFilterValue('#decontSelect', 'AF', 'Any', true, 3000, true));
	$('#toggleAntarcticaFilter').on('click', () => toggleFilterValue('#decontSelect', 'AN', 'Any', true, 3000, true));
	$('#toggleAsiaFilter').on('click', () => toggleFilterValue('#decontSelect', 'AS', 'Any', true, 3000, true));
	$('#toggleEuropeFilter').on('click', () => toggleFilterValue('#decontSelect', 'EU', 'Any', true, 3000, true));
	$('#toggleNorthAmericaFilter').on('click', () => toggleFilterValue('#decontSelect', 'NA', 'Any', true, 3000, true));
	$('#toggleOceaniaFilter').on('click', () => toggleFilterValue('#decontSelect', 'OC', 'Any', true, 3000, true));
	$('#toggleSouthAmericaFilter').on('click', () => toggleFilterValue('#decontSelect', 'SA', 'Any', true, 3000, true));

	// Toggle LoTW User filter
	$('#toggleLotwFilter').on('click', function() {
		let currentValues = $('#requiredFlags').val() || [];
		let btn = $(this);

		// Remove "None" if present
		currentValues = currentValues.filter(v => v !== 'None');

		if (currentValues.includes('lotw')) {
			// Remove LoTW filter
			currentValues = currentValues.filter(v => v !== 'lotw');
			if (currentValues.length === 0) currentValues = ['None'];
			btn.removeClass('btn-success').addClass('btn-secondary');
		} else {
			// Add LoTW filter
			currentValues.push('lotw');
			btn.removeClass('btn-secondary').addClass('btn-success');
		}

		$('#requiredFlags').val(currentValues).trigger('change');
		applyFilters(false);
	});

	// Toggle DX Spot filter (spotted continent ≠ spotter continent)
	$('#toggleDxSpotFilter').on('click', function() {
		let currentValues = $('#requiredFlags').val() || [];
		let btn = $(this);

		// Remove "None" if present
		currentValues = currentValues.filter(v => v !== 'None');

		if (currentValues.includes('dxspot')) {
			// Remove DX Spot filter
			currentValues = currentValues.filter(v => v !== 'dxspot');
			if (currentValues.length === 0) currentValues = ['None'];
			btn.removeClass('btn-success').addClass('btn-secondary');
		} else {
			// Add DX Spot filter
			currentValues.push('dxspot');
			btn.removeClass('btn-secondary').addClass('btn-success');
		}

		$('#requiredFlags').val(currentValues).trigger('change');
		applyFilters(false);
	});

	// Toggle New Continent filter
	$('#toggleNewContinentFilter').on('click', function() {
		let currentValues = $('#requiredFlags').val() || [];
		let btn = $(this);

		// Remove "None" if present
		currentValues = currentValues.filter(v => v !== 'None');

		if (currentValues.includes('newcontinent')) {
			// Remove New Continent filter
			currentValues = currentValues.filter(v => v !== 'newcontinent');
			if (currentValues.length === 0) currentValues = ['None'];
			btn.removeClass('btn-success').addClass('btn-secondary');
		} else {
			// Add New Continent filter
			currentValues.push('newcontinent');
			btn.removeClass('btn-secondary').addClass('btn-success');
		}

		$('#requiredFlags').val(currentValues).trigger('change');
		applyFilters(false);
	});

	// Toggle New Country filter (previously DXCC Needed)
	$('#toggleDxccNeededFilter').on('click', function() {
		let currentValues = $('#requiredFlags').val() || [];
		let btn = $(this);

		// Remove "None" if present
		currentValues = currentValues.filter(v => v !== 'None');

		if (currentValues.includes('newcountry')) {
			// Remove New Country filter
			currentValues = currentValues.filter(v => v !== 'newcountry');
			if (currentValues.length === 0) currentValues = ['None'];
			btn.removeClass('btn-success').addClass('btn-secondary');
		} else {
			// Add New Country filter
			currentValues.push('newcountry');
			btn.removeClass('btn-secondary').addClass('btn-success');
		}

		$('#requiredFlags').val(currentValues).trigger('change');
		applyFilters(false);
	});

	// Toggle New Callsign filter (previously Not Worked Before)
	$('#toggleNewCallsignFilter').on('click', function() {
		let currentValues = $('#requiredFlags').val() || [];
		let btn = $(this);

		// Remove "None" if present
		currentValues = currentValues.filter(v => v !== 'None');

		if (currentValues.includes('newcallsign')) {
			// Remove New Callsign filter
			currentValues = currentValues.filter(v => v !== 'newcallsign');
			if (currentValues.length === 0) currentValues = ['None'];
			btn.removeClass('btn-success').addClass('btn-secondary');
		} else {
			// Add New Callsign filter
			currentValues.push('newcallsign');
			btn.removeClass('btn-secondary').addClass('btn-success');
		}

		$('#requiredFlags').val(currentValues).trigger('change');
		applyFilters(false);
	});

	// Toggle Contest filter
	// Toggle Contest filter - moved to Required Flags
	$('#toggleContestFilter').on('click', function() {
		let currentValues = $('#requiredFlags').val() || [];
		let btn = $(this);

		// Remove "None" if present
		currentValues = currentValues.filter(v => v !== 'None');

		if (currentValues.includes('Contest')) {
			// Remove Contest filter
			currentValues = currentValues.filter(v => v !== 'Contest');
			if (currentValues.length === 0) currentValues = ['None'];
			btn.removeClass('btn-success').addClass('btn-secondary');
		} else {
			// Add Contest filter
			currentValues.push('Contest');
			btn.removeClass('btn-secondary').addClass('btn-success');
		}

		$('#requiredFlags').val(currentValues).trigger('change');
		updateSelectCheckboxes('requiredFlags');
		applyFilters(false);
	});

	// Toggle Geo Hunter filter (POTA, SOTA, IOTA, WWFF) - stays in Additional Flags
	$('#toggleGeoHunterFilter').on('click', function() {
		let currentValues = $('#additionalFlags').val() || [];
		let btn = $(this);
		let geoFlags = ['POTA', 'SOTA', 'IOTA', 'WWFF'];

		// Remove 'All' if present
		if (currentValues.includes('All')) {
			currentValues = currentValues.filter(v => v !== 'All');
		}

		// Check if any geo flag is active
		let hasGeoFlag = geoFlags.some(flag => currentValues.includes(flag));

		if (hasGeoFlag) {
			// Remove all geo flags
			currentValues = currentValues.filter(v => !geoFlags.includes(v));
			if (currentValues.length === 0) currentValues = ['All'];
			btn.removeClass('btn-success').addClass('btn-secondary');
		} else {
			// Add all geo flags
			currentValues = currentValues.concat(geoFlags.filter(flag => !currentValues.includes(flag)));
			btn.removeClass('btn-secondary').addClass('btn-success');
		}

		$('#additionalFlags').val(currentValues).trigger('change');
		applyFilters(false);
	});

	// Toggle Fresh filter (< 5 minutes)
	$('#toggleFreshFilter').on('click', function() {
		let currentValues = $('#additionalFlags').val() || [];
		let btn = $(this);

		// Remove 'All' if present
		if (currentValues.includes('All')) {
			currentValues = currentValues.filter(v => v !== 'All');
		}

		if (currentValues.includes('Fresh')) {
			// Remove Fresh filter
			currentValues = currentValues.filter(v => v !== 'Fresh');
			if (currentValues.length === 0) currentValues = ['All'];
			btn.removeClass('btn-success').addClass('btn-secondary');
		} else {
			// Add Fresh filter
			currentValues.push('Fresh');
			btn.removeClass('btn-secondary').addClass('btn-success');
		}

		$('#additionalFlags').val(currentValues).trigger('change');
		applyFilters(false);
	});

	// Toggle Favorites filter - applies user's active bands and modes
	$('#toggleFavoritesFilter').on('click', function() {
		// Use cached favorites if available, otherwise fetch
		if (cachedUserFavorites !== null) {
			applyUserFavorites(cachedUserFavorites);
		} else {
			// Fallback: fetch if cache is not available
			let base_url = dxcluster_provider.replace('/dxcluster', '');
			$.ajax({
				url: base_url + '/bandmap/get_user_favorites',
				method: 'GET',
				dataType: 'json',
				success: function(favorites) {
					cachedUserFavorites = favorites;
					applyUserFavorites(favorites);
				},
				error: function() {
					showToast(lang_bandmap_my_favorites, lang_bandmap_favorites_failed, 'bg-danger text-white', 3000);
				}
			});
		}
	});

	/**
	 * Apply user favorites to band and mode filters
	 */
	function applyUserFavorites(favorites) {
		// Apply bands - but preserve current band if CAT Control is enabled
		if (isCatTrackingEnabled) {
			// CAT Control is active - don't change band filter

			if (typeof showToast === 'function') {
				showToast(lang_bandmap_my_favorites, lang_bandmap_modes_applied, 'bg-info text-white', 3000);
			}
		} else {
			// CAT Control is off - apply favorite bands
			if (favorites.bands && favorites.bands.length > 0) {
				$('#band').val(favorites.bands).trigger('change');
			} else {
				// No active bands, set to All
				$('#band').val(['All']).trigger('change');
			}
		}

		// Apply modes
		let activeModes = [];
		if (favorites.modes.cw) activeModes.push('cw');
		if (favorites.modes.phone) activeModes.push('phone');
		if (favorites.modes.digi) activeModes.push('digi');

		if (activeModes.length > 0) {
			$('#mode').val(activeModes).trigger('change');
		} else {
			// No active modes, filter out everything (or set to All if you prefer)
			$('#mode').val(['All']).trigger('change');
		}

		// Sync button states and apply filters
		syncQuickFilterButtons();
		updateBandCountBadges();
		applyFilters(false);

		showToast(lang_bandmap_my_favorites, lang_bandmap_favorites_applied, 'bg-success text-white', 3000);
	}

	// ========================================
	// CAT CONTROL - BAND FILTER LOCK
	// ========================================

	/**
	 * Disable band filter controls when CAT Control is active
	 * Adds visual indicators and tooltips to explain why controls are disabled
	 */
	function disableBandFilterControls() {
		// Disable all band quick filter buttons (both individual and grouped)
		$('[id^="toggle"][id$="mFilter"], [id^="toggle"][id$="Filter"][id*="VHF"], [id^="toggle"][id$="Filter"][id*="UHF"], [id^="toggle"][id$="Filter"][id*="SHF"]').prop('disabled', true);

		// Disable band select in advanced filters popup
		$('#band').prop('disabled', true);

		// Add info icon and message to band filter label in popup
		const bandLabel = $('#band').closest('.mb-3').find('label');
		if (!bandLabel.find('.cat-control-info').length) {
			bandLabel.append(' <i class="fas fa-info-circle cat-control-info" title="' + lang_bandmap_cat_band_control + '"></i>');
		}
	}

	/**
	 * Re-enable band filter controls when CAT Control is disabled
	 */
	function enableBandFilterControls() {
		// Re-enable all band quick filter buttons (both individual and grouped)
		$('[id^="toggle"][id$="mFilter"], [id^="toggle"][id$="Filter"][id*="VHF"], [id^="toggle"][id$="Filter"][id*="UHF"], [id^="toggle"][id$="Filter"][id*="SHF"]').prop('disabled', false);

		// Re-enable band select in advanced filters popup
		$('#band').prop('disabled', false);

		// Remove info icon
		$('.cat-control-info').remove();
	}

	// ========================================
	// CAT CONTROL - TABLE SORTING LOCK
	// ========================================

	/**
	 * Lock table sorting to frequency column only (descending) when CAT Control is active
	 */
	function lockTableSortingToFrequency() {
		var table = get_dtable();

		// Add class to table for CSS styling
		$('.spottable').addClass('cat-sorting-locked');

		// Force sort by frequency (column 2) descending
		table.order([2, 'desc']).draw();

		// Disable sorting on all columns
		table.settings()[0].aoColumns.forEach(function(col, index) {
			col.bSortable = false;
		});

	// Disable click events on all column headers
	$('.spottable thead th').off('click.DT');

	// Redraw column headers to update sort icons
	table.columns.adjust();
}
	/**
	 * Unlock table sorting when CAT Control is disabled
	 */
	function unlockTableSorting() {
		var table = get_dtable();

		// Remove class from table
		$('.spottable').removeClass('cat-sorting-locked');

		// Re-enable sorting on all columns that were originally sortable
		// Based on columnDefs: columns 5, 6, 7, 11, 12, 13, 14 are not sortable
		const nonSortableColumns = [5, 6, 7, 11, 12, 13, 14];

		table.settings()[0].aoColumns.forEach(function(col, index) {
			if (!nonSortableColumns.includes(index)) {
				col.bSortable = true;
			}
		});

		// Re-enable DataTables default click handlers
		table.off('click.DT', 'thead th');

		// Reset to default sort (frequency ascending)
		table.order([2, 'asc']).draw();

		// Redraw column headers to update sort icons
		table.columns.adjust();

		// Clear frequency gradient colors
		clearFrequencyGradientColors();


	}

	/**
	 * Clear all frequency gradient colors from table rows
	 */
	function clearFrequencyGradientColors() {
		var table = get_dtable();

		table.rows().every(function() {
			const row = this.node();
			$(row).removeClass('cat-frequency-gradient cat-nearest-above cat-nearest-below');
			$(row).css({
				'--bs-table-bg': '',
				'--bs-table-accent-bg': '',
				'background-color': ''
			});
		});
	}

	/**
	 * Update button visual appearance based on CAT state
	 */
	function updateButtonVisual(state) {
		let btn = $('#toggleCatTracking');
		let radioIcon = btn.find('i.fa-radio');

		btn.removeClass('btn-secondary btn-success');
		radioIcon.removeClass('fa-podcast fa-crosshairs').css('color', '');
		btn.css('box-shadow', '');

		if (state === 'off') {
			btn.addClass('btn-secondary').attr('data-bs-original-title', lang_bandmap_cat_off);
			radioIcon.addClass('fa-radio');
		} else if (state === 'on') {
			btn.addClass('btn-success').attr('data-bs-original-title', lang_bandmap_cat_on);
			radioIcon.addClass('fa-radio');
		} else if (state === 'on+marker') {
			btn.addClass('btn-success').attr('data-bs-original-title', lang_bandmap_cat_marker);
			radioIcon.addClass('fa-radio').css('color', '#8a2be2');
			btn.css('box-shadow', '0 0 8px rgba(138, 43, 226, 0.6)');
		}

		// Update tooltip if it exists
		if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
			let tooltip = bootstrap.Tooltip.getInstance(btn[0]);
			if (tooltip) {
				tooltip.hide();
			}
		}
	}

	// Toggle CAT Control with state machine
	$('#toggleCatTracking').on('click', function() {
		catClickTimer = setTimeout(function() {
			if (!catClickPreventSingle) {
				handleCatSingleClick();
			}
			catClickPreventSingle = false;
		}, 300);
	});

	$('#toggleCatTracking').on('dblclick', function() {
		clearTimeout(catClickTimer);
		catClickPreventSingle = true;
		handleCatDoubleClick();
	});

	// Initialize tooltip on page load - set initial title only
	$('#toggleCatTracking').attr('data-bs-original-title', lang_bandmap_cat_off);

	function handleCatSingleClick() {
		const selectedRadio = $('.radios option:selected').val();

		switch(catState) {
			case 'off':
				// OFF → ON
				if (!selectedRadio || selectedRadio === '0') {
					if (typeof showToast === 'function') {
						showToast(lang_bandmap_cat_control, lang_bandmap_select_radio_first, 'bg-warning text-dark', 3000);
					}
					return;
				}

				isCatTrackingEnabled = true;
				window.isCatTrackingEnabled = true;
				catState = 'on';

				if (window.lastCATData && typeof window.displayRadioStatus === 'function') {
					window.displayRadioStatus('success', window.lastCATData);
				}

				disableBandFilterControls();

				if (window.lastCATData && window.lastCATData.frequency) {
					const band = frequencyToBand(window.lastCATData.frequency);
					if (band && band !== '') {
						$("#band").val([band]);
						updateSelectCheckboxes('band');
						syncQuickFilterButtons();
						applyFilters(false);
					}
				}

				updateButtonVisual('on');
				break;

			case 'on':
				// ON → ON+MARKER
				isFrequencyMarkerEnabled = true;
				catState = 'on+marker';

				lockTableSortingToFrequency();

				if (window.lastCATData && window.lastCATData.frequency) {
					updateFrequencyGradientColors(window.lastCATData.frequency);
				}

				updateButtonVisual('on+marker');
				break;

			case 'on+marker':
				// ON+MARKER → ON
				isFrequencyMarkerEnabled = false;
				catState = 'on';

				unlockTableSorting();
				clearFrequencyGradientColors();

				updateButtonVisual('on');
				break;
		}
	}

	function handleCatDoubleClick() {
		// Double-click: Force disable from any state
		if (catState === 'off') return;

		isCatTrackingEnabled = false;
		window.isCatTrackingEnabled = false;
		isFrequencyMarkerEnabled = false;
		catState = 'off';

		const selectedRadio = $('.radios option:selected').val();
		if (selectedRadio && selectedRadio !== '0' && typeof window.displayOfflineStatus === 'function') {
			window.displayOfflineStatus('cat_disabled');
		} else if (selectedRadio === '0' && typeof window.displayOfflineStatus === 'function') {
			window.displayOfflineStatus('no_radio');
		} else {
			$('#radio_cat_state').remove();
		}

		enableBandFilterControls();
		unlockTableSorting();
		clearFrequencyGradientColors();

		const currentBands = $("#band").val() || [];
		if (currentBands.length !== 1 || currentBands[0] !== 'All') {
			$("#band").val(['All']);
			updateSelectCheckboxes('band');
			syncQuickFilterButtons();
			applyFilters(true);
		}

		updateButtonVisual('off');
	}

	// ========================================
	// RESPONSIVE COLUMN VISIBILITY
	// ========================================

	/**
	 * Handle responsive column visibility based on available table width
	 * Dynamically shows/hides columns to optimize space usage
	 *
	 * Column indices (0-based):
	 * 0: Age, 1: Band, 2: Frequency, 3: Mode, 4: Callsign, 5: Continent, 6: CQZ,
	 * 7: Flag, 8: Entity, 9: DXCC, 10: de Callsign, 11: de Cont, 12: de CQZ,
	 * 13: Special, 14: Message
	 *
	 * Breakpoints:
	 * - Full screen or > 1374px: Show all columns
	 * - <= 1374px: Hide DXCC (9), CQZ (6), de CQZ (12)
	 * - <= 1294px: Additionally hide Band (1), Cont (5), de Cont (11)
	 * - <= 1024px: Additionally hide Flag (7)
	 * - <= 500px: Show only Age (0), Freq (2), Callsign (4), Entity (8)
	 */
	function handleResponsiveColumns() {
		const tableContainer = $('.table-responsive');
		if (!tableContainer.length) return;

		const containerWidth = tableContainer.width();

		// Check if in fullscreen mode
		const isFullscreen = $('#bandmapContainer').hasClass('bandmap-fullscreen');

		// Reset all columns to visible first
		$('.spottable th, .spottable td').removeClass('column-hidden');

		// If fullscreen, show all columns and exit
		if (isFullscreen) {
			if ($.fn.DataTable && $.fn.DataTable.isDataTable('.spottable')) {
				$('.spottable').DataTable().columns.adjust();
			}
			return;
		}

		// Apply visibility rules based on container width
		if (containerWidth <= 500) {
			// Show only Age, Freq, Callsign, Entity
			$('.spottable th:nth-child(2), .spottable td:nth-child(2)').addClass('column-hidden'); // Band
			$('.spottable th:nth-child(4), .spottable td:nth-child(4)').addClass('column-hidden'); // Mode
			$('.spottable th:nth-child(6), .spottable td:nth-child(6)').addClass('column-hidden'); // Continent
			$('.spottable th:nth-child(7), .spottable td:nth-child(7)').addClass('column-hidden'); // CQZ
			$('.spottable th:nth-child(8), .spottable td:nth-child(8)').addClass('column-hidden'); // Flag
			$('.spottable th:nth-child(10), .spottable td:nth-child(10)').addClass('column-hidden'); // DXCC
			$('.spottable th:nth-child(11), .spottable td:nth-child(11)').addClass('column-hidden'); // de Callsign
			$('.spottable th:nth-child(12), .spottable td:nth-child(12)').addClass('column-hidden'); // de Cont
			$('.spottable th:nth-child(13), .spottable td:nth-child(13)').addClass('column-hidden'); // de CQZ
			$('.spottable th:nth-child(14), .spottable td:nth-child(14)').addClass('column-hidden'); // Special
			$('.spottable th:nth-child(15), .spottable td:nth-child(15)').addClass('column-hidden'); // Message
		} else if (containerWidth <= 1024) {
			// Hide: DXCC, CQZ, de CQZ, Band, Cont, de Cont, Flag
			$('.spottable th:nth-child(2), .spottable td:nth-child(2)').addClass('column-hidden'); // Band
			$('.spottable th:nth-child(6), .spottable td:nth-child(6)').addClass('column-hidden'); // Continent
			$('.spottable th:nth-child(7), .spottable td:nth-child(7)').addClass('column-hidden'); // CQZ
			$('.spottable th:nth-child(8), .spottable td:nth-child(8)').addClass('column-hidden'); // Flag
			$('.spottable th:nth-child(10), .spottable td:nth-child(10)').addClass('column-hidden'); // DXCC
			$('.spottable th:nth-child(12), .spottable td:nth-child(12)').addClass('column-hidden'); // de Cont
			$('.spottable th:nth-child(13), .spottable td:nth-child(13)').addClass('column-hidden'); // de CQZ
		} else if (containerWidth <= 1294) {
			// Hide: DXCC, CQZ, de CQZ, Band, Cont, de Cont
			$('.spottable th:nth-child(2), .spottable td:nth-child(2)').addClass('column-hidden'); // Band
			$('.spottable th:nth-child(6), .spottable td:nth-child(6)').addClass('column-hidden'); // Continent
			$('.spottable th:nth-child(7), .spottable td:nth-child(7)').addClass('column-hidden'); // CQZ
			$('.spottable th:nth-child(10), .spottable td:nth-child(10)').addClass('column-hidden'); // DXCC
			$('.spottable th:nth-child(12), .spottable td:nth-child(12)').addClass('column-hidden'); // de Cont
			$('.spottable th:nth-child(13), .spottable td:nth-child(13)').addClass('column-hidden'); // de CQZ
		} else if (containerWidth <= 1374) {
			// Hide: DXCC, CQZ, de CQZ
			$('.spottable th:nth-child(7), .spottable td:nth-child(7)').addClass('column-hidden'); // CQZ
			$('.spottable th:nth-child(10), .spottable td:nth-child(10)').addClass('column-hidden'); // DXCC
			$('.spottable th:nth-child(13), .spottable td:nth-child(13)').addClass('column-hidden'); // de CQZ
		}
		// else: containerWidth > 1374 - show all columns (already reset above)

		// Adjust DataTable columns if initialized
		if ($.fn.DataTable && $.fn.DataTable.isDataTable('.spottable')) {
			$('.spottable').DataTable().columns.adjust();
		}
	}

	// Wait for table container to be ready, then initialize ResizeObserver
	function initResizeObserver() {
		const tableContainer = document.querySelector('.table-responsive');
		const dataTable = document.querySelector('#DataTables_Table_0_wrapper');

		if (tableContainer && dataTable) {
			// now we found the datatable and the table container is also available
			handleResponsiveColumns();

			if (typeof ResizeObserver !== 'undefined') {
				const resizeObserver = new ResizeObserver(function(entries) {
					handleResponsiveColumns();
				});
				resizeObserver.observe(tableContainer);
			} else {
				// Fallback for browsers without ResizeObserver support
				$(window).on('resize', function() {
					handleResponsiveColumns();
				});
			}

		} else {
			// elements not ready yet, try again
			setTimeout(initResizeObserver, 50);
			return;
		}
	}

	// Start init process of the ResizeObserver
	initResizeObserver();

	// ========================================
	// INITIALIZE CAT CONTROL STATE
	// ========================================

	/**
	 * Initialize CAT Control state on page load
	 * CAT Control is OFF by default, so ensure band filter controls are enabled
	 */
	enableBandFilterControls();

	// ========================================
	// CACHE USER FAVORITES ON PAGE LOAD
	// ========================================

	/**
	 * Fetch and cache user favorites on page load for instant access
	 * This prevents the delay when clicking the favorites button
	 */
	function fetchUserFavorites() {
		let base_url = dxcluster_provider.replace('/dxcluster', '');
		$.ajax({
			url: base_url + '/bandmap/get_user_favorites',
			method: 'GET',
			dataType: 'json',
			success: function(favorites) {
				cachedUserFavorites = favorites;

			},
			error: function() {
				console.warn('Failed to cache user favorites');
				cachedUserFavorites = null;
			}
		});
	}

	// Fetch favorites on page load
	fetchUserFavorites();

	// ========================================
	// AGE AUTO-UPDATE
	// ========================================

	/**
	 * Update spot ages every minute without full table refresh
	 * Ages are calculated from the spot timestamp stored in data attribute
	 */
	function updateSpotAges() {
		const now = Date.now();
		$('.spot-age').each(function() {
			const spotTime = parseInt($(this).attr('data-spot-time'));
			if (spotTime) {
				const ageMinutes = Math.floor((now - spotTime) / 60000);
				$(this).text(ageMinutes);
			}
		});
	}

	// Update ages every 60 seconds
	setInterval(updateSpotAges, 60000);

	// ========================================
	// DX MAP
	// ========================================

	let dxMap = null;
	let dxMapVisible = false;
	let dxccMarkers = [];
	let spotterMarkers = [];
	let connectionLines = [];
	let userHomeMarker = null;
	let showSpotters = false;
	let showDayNight = true; // Day/Night terminator enabled by default
	let terminatorLayer = null; // Store terminator layer reference
	let hoverSpottersData = new Map(); // Store spotter data for hover
	let hoverSpotterMarkers = []; // Temporary markers shown on hover
	let hoverConnectionLines = []; // Temporary lines shown on hover
	let hoverEventsInitialized = false; // Flag to prevent duplicate event handlers

	/**
	 * Initialize the DX Map with Leaflet
	 */
	function initDxMap() {
		if (dxMap) return;

		dxMap = L.map('dxMap', {
			center: [50.0647, 19.9450], // Krakow, Poland
			zoom: 6,
			zoomControl: true,
			scrollWheelZoom: true,
			fullscreenControl: true,
			fullscreenControlOptions: {
				position: 'topleft'
			}
		});

		// Create custom panes for proper layering
		dxMap.createPane('connectionLines');
		dxMap.getPane('connectionLines').style.zIndex = 400;
		dxMap.createPane('arrowsPane');
		dxMap.getPane('arrowsPane').style.zIndex = 450;

		L.tileLayer(map_tile_server || 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			attribution: map_tile_server_copyright || '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
			maxZoom: 18,
			minZoom: 1,
			id: 'mapbox.streets'
		}).addTo(dxMap);

		addUserHomeMarker();
		addSpottersControl();

		// Initialize terminator (enabled by default)
		updateTerminator();
	}

	/**
	 * Add user's home position marker
	 */
	function addUserHomeMarker() {
		$.ajax({
			url: base_url + 'index.php/logbook/qralatlngjson',
			type: 'post',
			data: { qra: user_gridsquare },
			success: function(data) {
				try {
					const result = JSON.parse(data);
					if (result && result[0] !== undefined && result[1] !== undefined) {
						const homeIcon = L.icon({
							iconUrl: icon_dot_url,
							iconSize: [18, 18]
						});
						userHomeMarker = L.marker([result[0], result[1]], { icon: homeIcon })
							.addTo(dxMap)
							.bindPopup('<strong>' + lang_bandmap_your_qth + '</strong>');
					}
				} catch (e) {
					console.warn('Could not parse user location:', e);
				}
			}
		});
	}

	/**
	 * Add spotters control legend
	 */
	function addSpottersControl() {
		const legend = L.control({ position: "topright" });
		legend.onAdd = function(map) {
			const div = L.DomUtil.create("div", "legend");
			div.innerHTML = '<input type="checkbox" id="toggleSpotters" style="outline: none;"><span> ' + lang_bandmap_draw_spotters + '</span><br>';
			div.innerHTML += '<input type="checkbox" id="extendMapCheckbox" style="outline: none;"><span> ' + lang_bandmap_extend_map + '</span><br>';
			div.innerHTML += '<input type="checkbox" id="showDayNightCheckbox" checked style="outline: none;"><span> ' + lang_bandmap_show_daynight + '</span><br>';
			return div;
		};
		legend.addTo(dxMap);

		setTimeout(() => {
			$('#toggleSpotters').on('change', function() {
				showSpotters = this.checked;
				updateDxMap();
			});

			$('#extendMapCheckbox').on('change', function() {
				const mapContainer = $('#dxMap');
				if (this.checked) {
					// Double the height (345px -> 690px)
					mapContainer.css('height', '690px');
				} else {
					// Restore original height
					mapContainer.css('height', '345px');
				}
				// Invalidate map size to ensure it redraws properly
				if (dxMap) {
					dxMap.invalidateSize();
				}
			});

			$('#showDayNightCheckbox').on('change', function() {
				showDayNight = this.checked;
				updateTerminator();
			});
		}, 100);
	}

	/**
	 * Update day/night terminator layer
	 */
	function updateTerminator() {
		if (!dxMap) return;

		// Remove existing terminator layer if it exists
		if (terminatorLayer) {
			dxMap.removeLayer(terminatorLayer);
			terminatorLayer = null;
		}

		// Add new terminator layer if enabled
		if (showDayNight) {
			terminatorLayer = L.terminator({
				fillOpacity: 0.3,
				color: '#000',
				weight: 1
			});
			terminatorLayer.addTo(dxMap);
		}
	}

	/**
	 * Group spots by DXCC entity
	 */
	function groupSpotsByDXCC(spots) {
		const dxccGroups = new Map();

		spots.forEach(spot => {
			const dxccId = spot.dxcc_spotted?.dxcc_id;
			if (!dxccId) {
				return;
			}

			if (!dxccGroups.has(dxccId)) {
				dxccGroups.set(dxccId, {
					dxccId: dxccId,
					lat: spot.dxcc_spotted.lat,
					lng: spot.dxcc_spotted.lng,
					entity: spot.dxcc_spotted.entity,
					flag: spot.dxcc_spotted.flag,
					continent: spot.dxcc_spotted.cont,
					spots: []
				});
			}

			dxccGroups.get(dxccId).spots.push(spot);
		});

		return dxccGroups;
	}

	/**
	 * Create HTML table for popup
	 */
	function createSpotTable(spots, dxccEntity, dxccFlag) {
		// Add DXCC name header with flag (bigger flag size)
		const flagEmoji = dxccFlag ? '<span class="flag-emoji" style="font-size: 20px;">' + dxccFlag + '</span> ' : '';
		let html = '<div style="font-weight: bold; font-size: 14px; padding: 4px 8px; background: rgba(0,0,0,0.1); margin-bottom: 4px; text-align: center;">' + flagEmoji + dxccEntity + '</div>';

		// Create scrollable container if more than 5 spots
		const needsScroll = spots.length > 5;
		if (needsScroll) {
			html += '<div style="max-height: 200px; overflow-y: auto; overflow-x: hidden;">';
		}

		html += '<table class="table table-sm table-striped" style="margin: 0; width: 100%; table-layout: fixed;">';
		html += '<thead><tr>';
		html += '<th style="width: 25%; overflow: hidden; text-overflow: ellipsis;">' + lang_bandmap_callsign + '</th>';
		html += '<th style="width: 25%; overflow: hidden; text-overflow: ellipsis;">' + lang_bandmap_frequency + '</th>';
		html += '<th style="width: 12%; overflow: hidden; text-overflow: ellipsis;">' + lang_bandmap_mode + '</th>';
		html += '<th style="width: 13%; overflow: hidden; text-overflow: ellipsis;">' + lang_bandmap_band + '</th>';
		html += '<th style="width: 25%; overflow: hidden; text-overflow: ellipsis;">' + lang_bandmap_spotter + '</th>';
		html += '</tr></thead><tbody>';

		spots.forEach(spot => {
			const freqMHz = (spot.frequency / 1000).toFixed(3);

			// Color code callsign based on worked/confirmed status (matching bandmap table)
			let callClass = '';
			if (spot.cnfmd_call) {
				callClass = 'text-success'; // Green = confirmed
			} else if (spot.worked_call) {
				callClass = 'text-warning'; // Yellow = worked
			}

			html += '<tr>';
			html += '<td style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><strong><a href="#" class="spot-link ' + callClass + '" data-callsign="' + spot.spotted + '">' + spot.spotted + '</a></strong></td>';
			html += '<td style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">' + freqMHz + ' MHz</td>';
			html += '<td style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">' + (spot.mode || '') + '</td>';
			html += '<td style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">' + (spot.band || '') + '</td>';
			html += '<td style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">' + (spot.spotter || '') + '</td>';
			html += '</tr>';
		});

		html += '</tbody></table>';

		if (needsScroll) {
			html += '</div>';
		}

		return html;
	}

	/**
	 * Get border color based on continent status (matching bandmap table colors)
	 */
	function getContinentStatusColor(cnfmdContinent, workedContinent) {
		// Green = confirmed, Yellow = worked (not confirmed), Red = new (not worked)
		if (cnfmdContinent) {
			return '#28a745'; // Bootstrap success green (confirmed)
		} else if (workedContinent) {
			return '#ffc107'; // Bootstrap warning yellow (worked but not confirmed)
		}
		return '#dc3545'; // Bootstrap danger red (new/not worked)
	}

	/**
	 * Get fill color based on DXCC status (matching bandmap table colors)
	 */
	function getDxccStatusColor(cnfmdDxcc, workedDxcc) {
		// Green = confirmed, Yellow = worked (not confirmed), Red = new (not worked)
		if (cnfmdDxcc) {
			return '#28a745'; // Bootstrap success green (confirmed)
		} else if (workedDxcc) {
			return '#ffc107'; // Bootstrap warning yellow (worked but not confirmed)
		}
		return '#dc3545'; // Bootstrap danger red (new/not worked)
	}

	/**
	 * Get darker border color for map markers (30% darker than fill)
	 */
	function getDarkerBorderColor(fillColor) {
		// Convert hex to RGB
		const hex = fillColor.replace('#', '');
		const r = parseInt(hex.substring(0, 2), 16);
		const g = parseInt(hex.substring(2, 4), 16);
		const b = parseInt(hex.substring(4, 6), 16);

		// Make 30% darker
		const darkerR = Math.floor(r * 0.7);
		const darkerG = Math.floor(g * 0.7);
		const darkerB = Math.floor(b * 0.7);

		// Convert back to hex
		return '#' + [darkerR, darkerG, darkerB].map(x => {
			const hex = x.toString(16);
			return hex.length === 1 ? '0' + hex : hex;
		}).join('');
	}

	/**
	 * Scroll to spot in the main DataTable
	 */
	function scrollToSpotInTable(callsign) {
		const table = get_dtable();
		if (!table) return;

		// Find row with matching callsign
		const row = table.rows().nodes().toArray().find(node => {
			const callsignCell = $(node).find('td:eq(4)').text();
			return callsignCell.includes(callsign);
		});

		if (row) {
			// Scroll to row
			$('html, body').animate({
				scrollTop: $(row).offset().top - 100
			}, 500);

			// Briefly highlight the row
			$(row).addClass('table-active');
			setTimeout(() => {
				$(row).removeClass('table-active');
			}, 2000);
		}
	}

	/**
	 * Update DX Map with DXCC grouping
	 */
	function updateDxMap() {
		if (!dxMap) {
			return;
		}

		// Clear existing markers
		dxccMarkers.forEach(marker => dxMap.removeLayer(marker));
		spotterMarkers.forEach(marker => dxMap.removeLayer(marker));
		connectionLines.forEach(line => dxMap.removeLayer(line));
		dxccMarkers = [];
		spotterMarkers = [];
		connectionLines = [];

		// Get filtered spots from DataTable
		const table = get_dtable();
		if (!table) {
			return;
		}

		const filteredData = table.rows({ search: 'applied' }).data();
		if (filteredData.length === 0) {
			return;
		}

		// Build list of spots from filtered data
		const spots = [];
		filteredData.each(function(row) {
			const freqMHzStr = row[2];
			const freqKHz = parseFloat(freqMHzStr) * 1000;
			const callsignHtml = row[4];

			let callsign = null;
			let match = callsignHtml.match(/db\/([^"]+)"/);
			if (match) {
				callsign = match[1];
			} else {
				const tempDiv = document.createElement('div');
				tempDiv.innerHTML = callsignHtml;
				callsign = tempDiv.textContent.trim();
			}

			if (!callsign) return;

			const spot = cachedSpotData.find(s =>
				s.spotted === callsign &&
				Math.abs(s.frequency - freqKHz) < 5
			);

			if (spot && spot.dxcc_spotted?.lat && spot.dxcc_spotted?.lng) {
				spots.push(spot);
			}
		});

		// Group by DXCC
		const dxccGroups = groupSpotsByDXCC(spots);

		// Clear hover data for new update
		hoverSpottersData.clear();

		// Create one marker per DXCC
		const bounds = [];
		let markersCreated = 0;

		dxccGroups.forEach(dxccInfo => {
			const lat = parseFloat(dxccInfo.lat);
			const lng = parseFloat(dxccInfo.lng);
			if (isNaN(lat) || isNaN(lng)) {
				return;
			}

			const count = dxccInfo.spots.length;
			const countText = count > 1 ? ` x${count}` : '';

			// Derive a short prefix from the first callsign
			const firstCall = dxccInfo.spots[0]?.spotted || '';
			const prefix = firstCall.match(/^[A-Z0-9]{1,3}/)?.[0] || dxccInfo.entity.substring(0, 3).toUpperCase();

			// Find the best (most optimistic) status in the group
			// Priority: confirmed > worked > new
			let bestContinentConfirmed = false;
			let bestContinentWorked = false;
			let bestDxccConfirmed = false;
			let bestDxccWorked = false;

			dxccInfo.spots.forEach(spot => {
				// Check continent status
				if (spot.cnfmd_continent) {
					bestContinentConfirmed = true;
				}
				if (spot.worked_continent) {
					bestContinentWorked = true;
				}

				// Check DXCC status
				if (spot.cnfmd_dxcc) {
					bestDxccConfirmed = true;
				}
				if (spot.worked_dxcc) {
					bestDxccWorked = true;
				}
			});

			const borderColor = getContinentStatusColor(bestContinentConfirmed, bestContinentWorked);
			const fillColor = getDxccStatusColor(bestDxccConfirmed, bestDxccWorked);
			// Use darker border to ensure visibility even when border and fill are the same
			const darkerBorder = getDarkerBorderColor(fillColor);

			const marker = L.marker([lat, lng], {
				icon: L.divIcon({
					className: 'dx-dxcc-marker',
					html: `<div class="dx-marker-label" data-dxcc-id="${dxccInfo.dxccId}" style="text-align: center; font-size: 10px; font-weight: bold; color: #000; background: ${fillColor}; padding: 1px 4px; border-radius: 2px; border: 2px solid ${darkerBorder}; box-shadow: 0 1px 2px rgba(0,0,0,0.3); white-space: nowrap;">
						${prefix}${countText}
					</div>`,
					iconSize: [45, 18],
					iconAnchor: [22, 9]
				})
			});

			// Store spotter data for this DXCC for hover functionality (incoming spots)
			const spottersForThisDxcc = [];
			dxccInfo.spots.forEach(spot => {
				if (spot.dxcc_spotter?.dxcc_id && spot.dxcc_spotter.lat && spot.dxcc_spotter.lng) {
					spottersForThisDxcc.push({
						dxccId: spot.dxcc_spotter.dxcc_id,
						lat: spot.dxcc_spotter.lat,
						lng: spot.dxcc_spotter.lng,
						entity: spot.dxcc_spotter.entity,
						flag: spot.dxcc_spotter.flag,
						continent: spot.dxcc_spotter.cont,
						spotter: spot.spotter
					});
				}
			});

			// Store outgoing spots data (where this DXCC is the spotter)
			const outgoingSpots = [];
			spots.forEach(spot => {
				if (spot.dxcc_spotter?.dxcc_id === dxccInfo.dxccId &&
					spot.dxcc_spotted?.dxcc_id &&
					spot.dxcc_spotted.lat &&
					spot.dxcc_spotted.lng) {
					outgoingSpots.push({
						dxccId: spot.dxcc_spotted.dxcc_id,
						lat: spot.dxcc_spotted.lat,
						lng: spot.dxcc_spotted.lng,
						entity: spot.dxcc_spotted.entity,
						flag: spot.dxcc_spotted.flag,
						continent: spot.dxcc_spotted.cont,
						callsign: spot.callsign
					});
				}
			});

			hoverSpottersData.set(String(dxccInfo.dxccId), {
				spotters: spottersForThisDxcc,  // incoming (red)
				outgoing: outgoingSpots,         // outgoing (green)
				targetLat: lat,
				targetLng: lng,
				targetContinent: dxccInfo.continent
			});

			marker.bindPopup(createSpotTable(dxccInfo.spots, dxccInfo.entity, dxccInfo.flag), {
				maxWidth: 650,
				minWidth: 450
			});
			marker.on('popupopen', function() {
				// Add click handlers to callsign links after popup opens
				setTimeout(() => {
					document.querySelectorAll('.spot-link').forEach(link => {
						link.addEventListener('click', function(e) {
							e.preventDefault();
							const callsign = this.getAttribute('data-callsign');
							scrollToSpotInTable(callsign);
						});
					});
				}, 10);
			});
			marker.addTo(dxMap);
			dxccMarkers.push(marker);
			bounds.push([lat, lng]);
			markersCreated++;
		});

		// Draw spotters if enabled
		if (showSpotters) {
			const spotterGroups = new Map();
			const drawnConnections = new Set(); // Track drawn connections

			spots.forEach(spot => {
				const spotterId = spot.dxcc_spotter?.dxcc_id;
				if (!spotterId) return;

				if (!spotterGroups.has(spotterId)) {
					spotterGroups.set(spotterId, {
						lat: spot.dxcc_spotter.lat,
						lng: spot.dxcc_spotter.lng,
						entity: spot.dxcc_spotter.entity,
						flag: spot.dxcc_spotter.flag,
						continent: spot.dxcc_spotter.cont,
						spotIds: new Set(),
						callsigns: []
					});
				}

				spotterGroups.get(spotterId).spotIds.add(spot.dxcc_spotted?.dxcc_id);
				spotterGroups.get(spotterId).callsigns.push(spot.spotter);
			});

			// Detect bi-directional connections
			const biDirectionalPairs = new Set();
			spotterGroups.forEach((spotterInfo, spotterId) => {
				spotterInfo.spotIds.forEach(spotId => {
					const spottedGroup = spotterGroups.get(spotId);
					if (spottedGroup && spottedGroup.spotIds.has(spotterId)) {
						// Create consistent pair key (sorted to avoid duplicates)
					const pairKey = [spotterId, spotId].sort().join('-');
					biDirectionalPairs.add(pairKey);
				}
			});
		});

		// Draw blue dots for spotters (permanent connections shown in orange)
		spotterGroups.forEach((spotterInfo, spotterId) => {
			const lat = parseFloat(spotterInfo.lat);
			const lng = parseFloat(spotterInfo.lng);
			if (isNaN(lat) || isNaN(lng)) return;				const marker = L.circleMarker([lat, lng], {
					radius: 5,
					fillColor: '#ff9900',
					color: '#fff',
					weight: 2,
					opacity: 1,
					fillOpacity: 0.8
				});

				// Add tooltip showing spotter entity and count
				const uniqueCallsigns = [...new Set(spotterInfo.callsigns)];
				const spotterCount = uniqueCallsigns.length;
				const tooltipText = `${spotterInfo.flag || ''} ${spotterInfo.entity}<br>${spotterCount} spotter${spotterCount !== 1 ? 's' : ''}`;
				marker.bindTooltip(tooltipText, { permanent: false, direction: 'top' });

				marker.addTo(dxMap);
				spotterMarkers.push(marker);

				// Draw lines to spotted DXCC entities (skip if same continent)
				spotterInfo.spotIds.forEach(spotId => {
					const dxccInfo = dxccGroups.get(spotId);
					if (dxccInfo) {
						// Skip line if both are in same continent
						if (spotterInfo.continent && dxccInfo.continent &&
							spotterInfo.continent === dxccInfo.continent) {
							return;
						}

						const spotLat = parseFloat(dxccInfo.lat);
						const spotLng = parseFloat(dxccInfo.lng);
						if (!isNaN(spotLat) && !isNaN(spotLng)) {
							// Check if this is a bi-directional connection
							const pairKey = [spotterId, spotId].sort().join('-');
							const isBiDirectional = biDirectionalPairs.has(pairKey);

							// Only draw once for bi-directional pairs (using sorted key)
							if (isBiDirectional && drawnConnections.has(pairKey)) {
								return;
							}
							drawnConnections.add(pairKey);

							// Use L.Geodesic instead of L.polyline for great circle paths
							const line = L.geodesic([[lat, lng], [spotLat, spotLng]], {
								color: '#ff9900',
								weight: 1,
								opacity: 0.5,
								dashArray: '5, 5',
								pane: 'connectionLines',
								wrap: false
							});

							line.addTo(dxMap);
							connectionLines.push(line);

							// Add arrow decorator(s) to show direction (spotter → spotted)
							if (typeof L.polylineDecorator !== 'undefined') {
								if (isBiDirectional) {
									// Bi-directional: add two filled arrows pointing in opposite directions
									const decorator = L.polylineDecorator(line, {
										patterns: [
											{
												offset: '30%',
												repeat: 0,
												symbol: L.Symbol.arrowHead({
													pixelSize: 10,
													polygon: true,
													pathOptions: {
														fillColor: '#ff9900',
														fillOpacity: 0.9,
														color: '#cc6600',
														weight: 1,
														opacity: 1
													}
												})
											},
											{
												offset: '70%',
												repeat: 0,
												symbol: L.Symbol.arrowHead({
													pixelSize: 10,
													polygon: true,
													pathOptions: {
														fillColor: '#ff9900',
														fillOpacity: 0.9,
														color: '#cc6600',
														weight: 1,
														opacity: 1
													}
												})
											}
										]
									});
									decorator.addTo(dxMap);
									connectionLines.push(decorator);
								} else {
									// Uni-directional: single filled arrow
									const decorator = L.polylineDecorator(line, {
										patterns: [{
											offset: '50%',
											repeat: 0,
											symbol: L.Symbol.arrowHead({
												pixelSize: 10,
												polygon: true,
												pathOptions: {
													fillColor: '#ff9900',
													fillOpacity: 0.9,
													color: '#cc6600',
													weight: 1,
													opacity: 1
												}
											})
										}]
									});
									decorator.addTo(dxMap);
									connectionLines.push(decorator);
								}
							}
						}
					}
				});
			});
		}

		// Set up hover event handlers only once
		if (!hoverEventsInitialized) {
			hoverEventsInitialized = true;

			$(document).on('mouseenter', '.dx-marker-label', function() {
				if (!dxMap) {
					console.log('Hover: Map not initialized');
					return;
				}

				// Hide all other spot labels (fade them out)
				$('.dx-marker-label').not(this).css('opacity', '0.15');

				// Clear any existing hover elements
				hoverSpotterMarkers.forEach(marker => {
					try { dxMap.removeLayer(marker); } catch(e) {}
				});
				hoverConnectionLines.forEach(line => {
					try { dxMap.removeLayer(line); } catch(e) {}
				});
				hoverSpotterMarkers = [];
				hoverConnectionLines = [];

				const dxccId = String($(this).data('dxcc-id'));
				if (!dxccId || dxccId === 'undefined') {
					console.log('Hover: No dxccId found');
					return;
				}

				const hoverData = hoverSpottersData.get(dxccId);
				if (!hoverData) {
					console.log('Hover: No hover data for', dxccId);
					return;
				}

				// Group incoming spotters by their DXCC to avoid duplicate lines
				const spotterMap = new Map();
				if (hoverData.spotters && hoverData.spotters.length > 0) {
					hoverData.spotters.forEach(spotter => {
						if (!spotterMap.has(spotter.dxccId)) {
							spotterMap.set(spotter.dxccId, {
								lat: spotter.lat,
								lng: spotter.lng,
								entity: spotter.entity,
								flag: spotter.flag,
								continent: spotter.continent,
								callsigns: []
							});
						}
						spotterMap.get(spotter.dxccId).callsigns.push(spotter.spotter);
					});
				}

				// Group outgoing spots by their DXCC
				const outgoingMap = new Map();
				if (hoverData.outgoing && hoverData.outgoing.length > 0) {
					hoverData.outgoing.forEach(spotted => {
						if (!outgoingMap.has(spotted.dxccId)) {
							outgoingMap.set(spotted.dxccId, {
								lat: spotted.lat,
								lng: spotted.lng,
								entity: spotted.entity,
								flag: spotted.flag,
								continent: spotted.continent,
								callsigns: []
							});
						}
						outgoingMap.get(spotted.dxccId).callsigns.push(spotted.callsign);
					});
				}

				// Use requestAnimationFrame for smooth rendering
				requestAnimationFrame(() => {
					// Draw incoming spotter markers and lines (RED)
					spotterMap.forEach((spotterInfo) => {
						const lat = parseFloat(spotterInfo.lat);
						const lng = parseFloat(spotterInfo.lng);
						if (isNaN(lat) || isNaN(lng)) return;

						try {
							const marker = L.circleMarker([lat, lng], {
								radius: 5,
								fillColor: '#ff0000',
								color: '#fff',
								weight: 2,
								opacity: 1,
								fillOpacity: 0.8
							});

						const uniqueCallsigns = [...new Set(spotterInfo.callsigns)];
						const spotterCount = uniqueCallsigns.length;
						const tooltipText = `${spotterInfo.flag || ''} ${spotterInfo.entity}<br>${spotterCount} ${spotterCount !== 1 ? lang_bandmap_spotters : lang_bandmap_spotter}<br>→ ${lang_bandmap_incoming}`;
						marker.bindTooltip(tooltipText, { permanent: false, direction: 'top' });

						marker.addTo(dxMap);
						hoverSpotterMarkers.push(marker);							// Draw RED line (incoming: spotter → target) using geodesic
							const line = L.geodesic([[lat, lng], [hoverData.targetLat, hoverData.targetLng]], {
								color: '#ff0000',
								weight: 2,
								opacity: 0.7,
								dashArray: '5, 5',
								pane: 'connectionLines',
								wrap: false
							});

							line.addTo(dxMap);
							hoverConnectionLines.push(line);

							// Add arrow decorator to show direction (spotter → spotted)
							if (L.polylineDecorator) {
								const decorator = L.polylineDecorator(line, {
									patterns: [{
										offset: '50%',
										repeat: 0,
										symbol: L.Symbol.arrowHead({
											pixelSize: 10,
											polygon: true,
											pathOptions: {
												fillColor: '#ff0000',
												fillOpacity: 0.9,
												color: '#990000',
												weight: 1,
												opacity: 1
											}
										})
									}]
								});
								decorator.addTo(dxMap);
								hoverConnectionLines.push(decorator);
							}
						} catch(e) {
							console.error('Error drawing incoming hover spotter:', e);
						}
					});

					// Draw outgoing spot markers and lines (GREEN)
					outgoingMap.forEach((spottedInfo) => {
						const lat = parseFloat(spottedInfo.lat);
						const lng = parseFloat(spottedInfo.lng);
						if (isNaN(lat) || isNaN(lng)) return;

						try {
							const marker = L.circleMarker([lat, lng], {
								radius: 5,
								fillColor: '#00ff00',
								color: '#fff',
								weight: 2,
								opacity: 1,
								fillOpacity: 0.8
							});

						const uniqueCallsigns = [...new Set(spottedInfo.callsigns)];
						const spotCount = uniqueCallsigns.length;
						const tooltipText = `${spottedInfo.flag || ''} ${spottedInfo.entity}<br>${spotCount} ${spotCount !== 1 ? lang_bandmap_spots : lang_bandmap_spot}<br>← ${lang_bandmap_outgoing}`;
						marker.bindTooltip(tooltipText, { permanent: false, direction: 'top' });

						marker.addTo(dxMap);
						hoverSpotterMarkers.push(marker);							// Draw GREEN line (outgoing: target → spotted) using geodesic
							const line = L.geodesic([[hoverData.targetLat, hoverData.targetLng], [lat, lng]], {
								color: '#00ff00',
								weight: 2,
								opacity: 0.7,
								dashArray: '5, 5',
								pane: 'connectionLines',
								wrap: false
							});

							line.addTo(dxMap);
							hoverConnectionLines.push(line);

							// Add arrow decorator to show direction (target → spotted)
							if (L.polylineDecorator) {
								const decorator = L.polylineDecorator(line, {
									patterns: [{
										offset: '50%',
										repeat: 0,
										symbol: L.Symbol.arrowHead({
											pixelSize: 10,
											polygon: true,
											pathOptions: {
												fillColor: '#00ff00',
												fillOpacity: 0.9,
												color: '#009900',
												weight: 1,
												opacity: 1
											}
										})
									}]
								});
								decorator.addTo(dxMap);
								hoverConnectionLines.push(decorator);
							}
						} catch(e) {
							console.error('Error drawing outgoing hover spot:', e);
						}
					});
				});
			});

			$(document).on('mouseleave', '.dx-marker-label', function() {
				if (!dxMap) return;

				// Restore visibility of all spot labels
				$('.dx-marker-label').css('opacity', '1');

				// Use requestAnimationFrame for smooth cleanup
				requestAnimationFrame(() => {
					// Remove hover spotters and lines
					hoverSpotterMarkers.forEach(marker => {
						try { dxMap.removeLayer(marker); } catch(e) {}
					});
					hoverConnectionLines.forEach(line => {
						try { dxMap.removeLayer(line); } catch(e) {}
					});
					hoverSpotterMarkers = [];
					hoverConnectionLines = [];
				});
			});
		}

		// Fit bounds
		if (bounds.length > 0) {
			dxMap.fitBounds(bounds, { padding: [50, 50], maxZoom: 8 });
		}

		// Update day/night terminator
		updateTerminator();

		setTimeout(() => {
			if (dxMap) dxMap.invalidateSize();
		}, 100);
	}

	/**
	 * Toggle DX Map visibility
	 */
	$('#dxMapButton').on('click', function() {
		const container = $('#dxMapContainer');

		if (dxMapVisible) {
			// Hide map
			container.slideUp(300);
			dxMapVisible = false;
			$(this).removeClass('btn-success').addClass('btn-primary');
		} else {
			// Show map
			if (!dxMap) {
				initDxMap();
			}
			container.slideDown(300, function() {
				updateDxMap();
				// After first show, wait 1 second and reset zoom/viewport
				setTimeout(() => {
					if (dxMap) {
						const table = get_dtable();
						if (table) {
							const filteredData = table.rows({ search: 'applied' }).data();
							if (filteredData.length > 0) {
								// Collect bounds from all visible markers
								const mapBounds = [];
								dxccMarkers.forEach(marker => {
									const latLng = marker.getLatLng();
									if (latLng) mapBounds.push([latLng.lat, latLng.lng]);
								});
								if (mapBounds.length > 0) {
									dxMap.fitBounds(mapBounds, { padding: [50, 50], maxZoom: 8 });
								}
							}
						}
					}
				}, 1000);
			});
			dxMapVisible = true;
			$(this).removeClass('btn-primary').addClass('btn-success');
		}
	});

	// Update map when filters change (if map is visible)
	const originalApplyFilters = applyFilters;
	applyFilters = function(forceReload = false) {
		originalApplyFilters(forceReload);
		// Only update map if it's visible - don't waste resources
		if (dxMapVisible && dxMap) {
			setTimeout(updateDxMap, 500);
		}
	};

	// Initial check: Display offline status if radio selected but CAT Control disabled
	// This handles page load state
	setTimeout(function() {
		const selectedRadio = $('.radios option:selected').val();
		if (selectedRadio === '0') {
			// No radio selected - disable CAT Control button
			$('#toggleCatTracking').prop('disabled', true).addClass('disabled');
			if (typeof window.displayOfflineStatus === 'function') {
				window.displayOfflineStatus('no_radio');
			}
		} else if (selectedRadio && selectedRadio !== '0' && !isCatTrackingEnabled) {
			// Radio is selected but CAT Control is disabled on page load
			$('#toggleCatTracking').prop('disabled', false).removeClass('disabled');
			if (typeof window.displayOfflineStatus === 'function') {
				window.displayOfflineStatus('cat_disabled');
			}
		} else if (selectedRadio && selectedRadio !== '0') {
			// Radio is selected and CAT Control is enabled
			$('#toggleCatTracking').prop('disabled', false).removeClass('disabled');
		}
	}, 100); // Small delay to ensure cat.js has loaded and exposed the function

});


