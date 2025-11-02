/**
 * @fileoverview DX CLUSTER BANDMAP for WaveLog
 * @version 1.0.0
 * @author Wavelog Team
 *
 * @description
 * Real-time DX spot filtering and display with intelligent client/server-side
 * filter architecture, smart caching, and multi-criteria spot filtering.
 *
 * @requires jQuery
 * @requires DataTables
 * @requires base_url (global from Wavelog)
 * @requires dxcluster_provider (global from Wavelog)
 * @requires dxcluster_maxage (global from Wavelog)
 *
 * @browserSupport
 * - Chrome 90+
 * - Firefox 88+
 * - Safari 14+
 * - Edge 90+
 *
 * @features
 * - Smart filter architecture (server-side: continent only; client-side: band, mode, flags)
 * - Real-time spot caching and client-side filtering
 * - Multi-select filters with AND/OR logic
 * - Required flags (LoTW, Not Worked) with AND logic
 * - Activity flags (POTA, SOTA, WWFF, IOTA, Contest)
 * - Auto-refresh with 60-second countdown timer
 * - DXCC status color coding (Confirmed/Worked/New)
 */

'use strict';

$(function() {

	// ========================================
	// FILTER UI MANAGEMENT
	// ========================================

	// Check if any filters are active (not default "All"/"Any" values)
	function areFiltersApplied() {
		let cwnVal = $('#cwnSelect').val() || [];
		let decontVal = $('#decontSelect').val() || [];
		let continentVal = $('#continentSelect').val() || [];
		let bandVal = $('#band').val() || [];
		let modeVal = $('#mode').val() || [];
		let flagsVal = $('#additionalFlags').val() || [];
		let requiredVal = $('#requiredFlags').val() || [];

		// Check if anything is selected besides "All"/"Any"
		let isDefaultCwn = cwnVal.length === 1 && cwnVal.includes('All');
		let isDefaultDecont = decontVal.length === 1 && decontVal.includes('Any');
		let isDefaultContinent = continentVal.length === 1 && continentVal.includes('Any');
		let isDefaultBand = bandVal.length === 1 && bandVal.includes('All');
		let isDefaultMode = modeVal.length === 1 && modeVal.includes('All');
		let isDefaultFlags = flagsVal.length === 1 && flagsVal.includes('All');
		let isDefaultRequired = requiredVal.length === 0;

		return !(isDefaultCwn && isDefaultDecont && isDefaultContinent && isDefaultBand && isDefaultMode && isDefaultFlags && isDefaultRequired);
	}

	// Update filter icon based on whether filters are active
	function updateFilterIcon() {
		if (areFiltersApplied()) {
			$('#filterIcon').removeClass('fa-filter').addClass('fa-filter-circle-xmark');
			$('#filterDropdown').removeClass('btn-primary').addClass('btn-warning');
		} else {
			$('#filterIcon').removeClass('fa-filter-circle-xmark').addClass('fa-filter');
			$('#filterDropdown').removeClass('btn-warning').addClass('btn-primary');
		}
	}

	// Sync quick filter button states with their corresponding dropdown values
	function syncQuickFilterButtons() {
		let requiredFlags = $('#requiredFlags').val() || [];
		let additionalFlags = $('#additionalFlags').val() || [];
		let cwnValues = $('#cwnSelect').val() || [];
		let modeValues = $('#mode').val() || [];
		let bandValues = $('#band').val() || [];
		let decontValues = $('#decontSelect').val() || [];

		// LoTW button
		if (requiredFlags.includes('lotw')) {
			$('#toggleLotwFilter').removeClass('btn-primary').addClass('btn-warning');
			$('#toggleLotwFilter i').removeClass('fa-upload').addClass('fa-check-circle');
		} else {
			$('#toggleLotwFilter').removeClass('btn-warning').addClass('btn-primary');
			$('#toggleLotwFilter i').removeClass('fa-check-circle').addClass('fa-upload');
		}

		// Not Worked button
		if (requiredFlags.includes('notworked')) {
			$('#toggleNotWorkedFilter').removeClass('btn-primary').addClass('btn-warning');
			$('#toggleNotWorkedFilter i').removeClass('fa-star').addClass('fa-check-circle');
		} else {
			$('#toggleNotWorkedFilter').removeClass('btn-warning').addClass('btn-primary');
			$('#toggleNotWorkedFilter i').removeClass('fa-check-circle').addClass('fa-star');
		}

		// DXCC Needed button
		if (cwnValues.length === 1 && cwnValues[0] === 'notwkd') {
			$('#toggleDxccNeededFilter').removeClass('btn-primary').addClass('btn-warning');
			$('#toggleDxccNeededFilter i').removeClass('fa-globe').addClass('fa-check-circle');
		} else {
			$('#toggleDxccNeededFilter').removeClass('btn-warning').addClass('btn-primary');
			$('#toggleDxccNeededFilter i').removeClass('fa-check-circle').addClass('fa-globe');
		}

		// Contest button (now in Required Flags)
		if (requiredFlags.includes('Contest')) {
			$('#toggleContestFilter').removeClass('btn-primary').addClass('btn-warning');
			$('#toggleContestFilter i').removeClass('fa-trophy').addClass('fa-check-circle');
		} else {
			$('#toggleContestFilter').removeClass('btn-warning').addClass('btn-primary');
			$('#toggleContestFilter i').removeClass('fa-check-circle').addClass('fa-trophy');
		}

		// Geo Hunter button (stays in Additional Flags)
		let geoFlags = ['POTA', 'SOTA', 'IOTA', 'WWFF'];
		let hasGeoFlag = geoFlags.some(flag => additionalFlags.includes(flag));
		if (hasGeoFlag) {
			$('#toggleGeoHunterFilter').removeClass('btn-primary').addClass('btn-warning');
			$('#toggleGeoHunterFilter i').removeClass('fa-map-marked-alt').addClass('fa-check-circle');
		} else {
			$('#toggleGeoHunterFilter').removeClass('btn-warning').addClass('btn-primary');
			$('#toggleGeoHunterFilter i').removeClass('fa-check-circle').addClass('fa-map-marked-alt');
		}

		// Fresh filter button
		if (additionalFlags.includes('Fresh')) {
			$('#toggleFreshFilter').removeClass('btn-primary').addClass('btn-warning');
			$('#toggleFreshFilter i').removeClass('fa-bolt').addClass('fa-check-circle');
		} else {
			$('#toggleFreshFilter').removeClass('btn-warning').addClass('btn-primary');
			$('#toggleFreshFilter i').removeClass('fa-check-circle').addClass('fa-bolt');
		}

		// CW mode button
		if (modeValues.includes('cw')) {
			$('#toggleCwFilter').removeClass('btn-primary').addClass('btn-warning');
			$('#toggleCwFilter i').removeClass('fa-wave-square').addClass('fa-check-circle');
		} else {
			$('#toggleCwFilter').removeClass('btn-warning').addClass('btn-primary');
			$('#toggleCwFilter i').removeClass('fa-check-circle').addClass('fa-wave-square');
		}

		// Digi mode button
		if (modeValues.includes('digi')) {
			$('#toggleDigiFilter').removeClass('btn-primary').addClass('btn-warning');
			$('#toggleDigiFilter i').removeClass('fa-keyboard').addClass('fa-check-circle');
		} else {
			$('#toggleDigiFilter').removeClass('btn-warning').addClass('btn-primary');
			$('#toggleDigiFilter i').removeClass('fa-check-circle').addClass('fa-keyboard');
		}

		// Phone mode button
		if (modeValues.includes('phone')) {
			$('#togglePhoneFilter').removeClass('btn-primary').addClass('btn-warning');
			$('#togglePhoneFilter i').removeClass('fa-microphone').addClass('fa-check-circle');
		} else {
			$('#togglePhoneFilter').removeClass('btn-warning').addClass('btn-primary');
			$('#togglePhoneFilter i').removeClass('fa-check-circle').addClass('fa-microphone');
		}

	// Check if "All" is selected for bands, modes, and continents
	let allBandsSelected = bandValues.length === 1 && bandValues.includes('All');

	// For modes: check if "All" is selected OR if all individual modes are selected
	let allModesSelected = (modeValues.length === 1 && modeValues.includes('All')) ||
	                       (modeValues.includes('cw') && modeValues.includes('digi') && modeValues.includes('phone'));

	// For continents: check if "Any" is selected OR if all continents are selected
	// All continents: AF, AN, AS, EU, NA, OC, SA (7 continents)
	let allContinentsSelected = (decontValues.length === 1 && decontValues.includes('Any')) ||
	                            (decontValues.includes('AF') && decontValues.includes('AN') &&
	                             decontValues.includes('AS') && decontValues.includes('EU') &&
	                             decontValues.includes('NA') && decontValues.includes('OC') &&
	                             decontValues.includes('SA'));

	// Band filter buttons - green if All, orange if specific band, blue if not selected
	// Always update colors, even when CAT Control is enabled (so users can see which band is active)
	let bandButtons = ['#toggle160mFilter', '#toggle80mFilter', '#toggle60mFilter', '#toggle40mFilter',
	                   '#toggle30mFilter', '#toggle20mFilter', '#toggle17mFilter', '#toggle15mFilter',
	                   '#toggle12mFilter', '#toggle10mFilter'];
	let bandIds = ['160m', '80m', '60m', '40m', '30m', '20m', '17m', '15m', '12m', '10m'];

	bandButtons.forEach((btnId, index) => {
		let $btn = $(btnId);
		$btn.removeClass('btn-primary btn-warning btn-success');
		if (allBandsSelected) {
			$btn.addClass('btn-success');
		} else if (bandValues.includes(bandIds[index])) {
			$btn.addClass('btn-warning');
		} else {
			$btn.addClass('btn-primary');
		}
	});

	// Band group buttons (VHF, UHF, SHF)
	let groupButtons = [
		{ id: '#toggleVHFFilter', group: 'VHF' },
		{ id: '#toggleUHFFilter', group: 'UHF' },
		{ id: '#toggleSHFFilter', group: 'SHF' }
	];

	groupButtons.forEach(btn => {
		let $btn = $(btn.id);
		$btn.removeClass('btn-primary btn-warning btn-success');

		if (allBandsSelected) {
			$btn.addClass('btn-success');
		} else {
			// Check if ALL bands in the group are selected (not just some)
			const groupBands = getBandsInGroup(btn.group);
			const allGroupBandsSelected = groupBands.every(b => bandValues.includes(b));

			if (allGroupBandsSelected) {
				$btn.addClass('btn-warning');
			} else {
				$btn.addClass('btn-primary');
			}
		}
	});		// Mode buttons - green if All, orange if selected, blue if not
		let modeButtons = [
			{ id: '#toggleCwFilter', mode: 'cw', icon: 'fa-wave-square' },
			{ id: '#toggleDigiFilter', mode: 'digi', icon: 'fa-keyboard' },
			{ id: '#togglePhoneFilter', mode: 'phone', icon: 'fa-microphone' }
		];

		modeButtons.forEach(btn => {
			let $btn = $(btn.id);
			$btn.removeClass('btn-primary btn-warning btn-success');
			let $icon = $btn.find('i');

			if (allModesSelected) {
				$btn.addClass('btn-success');
				$icon.removeClass(btn.icon).addClass('fa-check-circle');
			} else if (modeValues.includes(btn.mode)) {
				$btn.addClass('btn-warning');
				$icon.removeClass(btn.icon).addClass('fa-check-circle');
			} else {
				$btn.addClass('btn-primary');
				$icon.removeClass('fa-check-circle').addClass(btn.icon);
			}
		});

		// Continent filter buttons - green if Any, orange if selected, blue if not
		let continentButtons = [
			{ id: '#toggleAfricaFilter', continent: 'AF' },
			{ id: '#toggleAsiaFilter', continent: 'AS' },
			{ id: '#toggleEuropeFilter', continent: 'EU' },
			{ id: '#toggleNorthAmericaFilter', continent: 'NA' },
			{ id: '#toggleSouthAmericaFilter', continent: 'SA' }
		];

		continentButtons.forEach(btn => {
			let $btn = $(btn.id);
			$btn.removeClass('btn-primary btn-warning btn-success');
			if (allContinentsSelected) {
				$btn.addClass('btn-success');
			} else if (decontValues.includes(btn.continent)) {
				$btn.addClass('btn-warning');
			} else {
				$btn.addClass('btn-primary');
			}
		});
	}

	// Add checkbox-style indicators (☑/☐) to multi-select dropdowns
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

	// Required flags filter doesn't use "All" option - handle separately
	$('#requiredFlags').on('change', function() {
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
				"zeroRecords": "No spots found"
			},
			'columnDefs': [
				{
					'targets': 2,  // Frequency is now column 3 (0-indexed = 2)
				"type":"num",
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
			}
		});

	$('.spottable tbody').off('click', 'tr').on('click', 'tr', function(e) {
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
		let mode = rowData[3];  // Mode is column 4 (0-indexed = 3)

		prepareLogging(call, qrg, mode);
	});

		return table;
	}

	// ========================================
	// FILTER STATE TRACKING
	// ========================================

	// Track what backend parameters were used for last data fetch
	// NOTE: Changed architecture - only de continent affects backend now
	// Band and Mode are now client-side filters only
	var loadedBackendFilters = {
		continent: 'Any'
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
	var isFetchInProgress = false;  // Prevent multiple simultaneous fetches
	var currentAjaxRequest = null;  // Track active AJAX request for cancellation
	var lastFetchParams = {  // Track last successful fetch parameters
		continent: 'Any',
		maxAge: 60,
		timestamp: null
	};

	// Auto-refresh timer state
	var refreshCountdown = 60;
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

			let loadingMessage = 'Loading data from DX Cluster';
			if (allFilters.length > 0) {
				loadingMessage += ' (' + allFilters.join(', ') + ')';
			}
			loadingMessage += '...';

			$('#statusMessage').text(loadingMessage);
			$('#refreshIcon').removeClass('fa-hourglass-half').addClass('fa-spinner fa-spin');
			$('#refreshTimer').text('');
			return;
		}

		if (lastFetchParams.timestamp === null) {
			$('#statusMessage').text('');
			$('#refreshTimer').text('');
			return;
		}

		let now = new Date();
		let timeStr = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
		let statusMessage = totalSpots + ' spots fetched @ ' + timeStr;
		let allFilters = [];

		if (serverFilters && serverFilters.length > 0) {
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

		if (allFilters.length > 0) {
			statusMessage += ', showing ' + displayedSpots + ' (' + allFilters.join(' and ') + ')';
		} else if (displayedSpots < totalSpots) {
			statusMessage += ', showing ' + displayedSpots;
		} else if (totalSpots > 0) {
			statusMessage += ', showing all';
		}

		let tooltipLines = ['Last fetched for:'];
		tooltipLines.push('Band: ' + (lastFetchParams.band || 'All'));
		tooltipLines.push('Continent: ' + (lastFetchParams.continent || 'All'));
		tooltipLines.push('Mode: ' + (lastFetchParams.mode || 'All'));
		tooltipLines.push('Max Age: ' + (lastFetchParams.maxAge || '120') + ' min');
		if (lastFetchParams.timestamp) {
			let fetchTime = new Date(lastFetchParams.timestamp);
			let fetchTimeStr = fetchTime.getHours().toString().padStart(2, '0') + ':' +
			                   fetchTime.getMinutes().toString().padStart(2, '0') + ':' +
			                   fetchTime.getSeconds().toString().padStart(2, '0');
			tooltipLines.push('Fetched at: ' + fetchTimeStr);
		}

		$('#statusMessage').text(statusMessage).attr('title', tooltipLines.join('\n'));

		if (isFetching) {
			$('#refreshIcon').removeClass('fa-hourglass-half').addClass('fa-spinner fa-spin');
			$('#refreshTimer').text('Fetching...');
		} else {
			$('#refreshIcon').removeClass('fa-spinner fa-spin').addClass('fa-hourglass-half');
			$('#refreshTimer').text('Next update in ' + refreshCountdown + 's');
		}
	}

	function getDisplayedSpotCount() {
		var table = get_dtable();
		return table.rows({search: 'applied'}).count();
	}

	// Start/restart the auto-refresh countdown timer (60 seconds)
	function startRefreshTimer() {
		if (refreshTimerInterval) {
			clearInterval(refreshTimerInterval);
		}

		refreshCountdown = 60;

		refreshTimerInterval = setInterval(function() {
			refreshCountdown--;
			if (refreshCountdown <= 0) {
				console.log('Timer countdown: reloading spot data with current filters');
				let table = get_dtable();
				table.clear();
				fill_list(currentFilters.deContinent, dxcluster_maxage);
				refreshCountdown = 60;
			} else {
				if (!isFetchInProgress && lastFetchParams.timestamp !== null) {
					$('#refreshIcon').removeClass('fa-spinner fa-spin').addClass('fa-hourglass-half');
					$('#refreshTimer').text('Next update in ' + refreshCountdown + 's');
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
					case 'notwkd': return 'Not worked';
					case 'wkd': return 'Worked';
					case 'cnf': return 'Confirmed';
					case 'ucnf': return 'Worked, not Confirmed';
					default: return status;
				}
			});
			filters.push('"DXCC: ' + cwnLabels.join('/') + '"');
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
			filters.push('"de: ' + currentFilters.deContinent.join('/') + '"');
		}

		// Spotted continent
		if (!currentFilters.spottedContinent.includes('Any')) {
			filters.push('"spotted: ' + currentFilters.spottedContinent.join('/') + '"');
		}

		// Band
		if (!currentFilters.band.includes('All')) {
			filters.push('"Band: ' + currentFilters.band.join('/') + '"');
		}

		// Mode
		if (!currentFilters.mode.includes('All')) {
			let modeLabels = currentFilters.mode.map(function(m) {
				return m.charAt(0).toUpperCase() + m.slice(1);
			});
			filters.push('"Mode: ' + modeLabels.join('/') + '"');
		}

		// Required flags - each one is shown individually with "and"
		if (currentFilters.requiredFlags && currentFilters.requiredFlags.length > 0) {
			currentFilters.requiredFlags.forEach(function(flag) {
				if (flag === 'lotw') {
					filters.push('"LoTW User"');
				} else if (flag === 'notworked') {
					filters.push('"New Callsign"');
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
			table.settings()[0].oLanguage.sEmptyTable = "No data available";
			table.draw();
			return;
		}

		let bands = currentFilters.band;
		let deContinent = currentFilters.deContinent;
		let spottedContinents = currentFilters.spottedContinent;
		let cwnStatuses = currentFilters.cwn;
		let modes = currentFilters.mode;
		let flags = currentFilters.additionalFlags;
		let requiredFlags = currentFilters.requiredFlags || [];

		table.clear();
		let oldtable = table.data();
		let spots2render = 0;

	cachedSpotData.forEach((single) => {
		// Extract time from spot data - use 'when' field
		let timeOnly = single.when;

		// Apply required flags FIRST (must have ALL selected required flags)
		if (requiredFlags.length > 0) {
			for (let reqFlag of requiredFlags) {
				if (reqFlag === 'lotw') {
					if (!single.dxcc_spotted || !single.dxcc_spotted.lotw_user) return;
				}
				if (reqFlag === 'notworked') {
					if (single.worked_call) return;  // Reject if already worked
				}
				if (reqFlag === 'Contest') {
					if (!single.dxcc_spotted || !single.dxcc_spotted.isContest) return;
				}
			}
		}			// Apply CWN (Confirmed/Worked/New) filter
			let passesCwnFilter = cwnStatuses.includes('All');
			if (!passesCwnFilter) {
				if (cwnStatuses.includes('notwkd') && !single.worked_dxcc) passesCwnFilter = true;
				if (cwnStatuses.includes('wkd') && single.worked_dxcc) passesCwnFilter = true;
				if (cwnStatuses.includes('cnf') && single.cnfmd_dxcc) passesCwnFilter = true;
				if (cwnStatuses.includes('ucnf') && single.worked_dxcc && !single.cnfmd_dxcc) passesCwnFilter = true;
			}
			if (!passesCwnFilter) return;

		// Apply band filter (client-side for multi-select)
		let passesBandFilter = bands.includes('All');
		if (!passesBandFilter) {
			// Check if spot has band field set, otherwise determine from frequency
			let spot_band = single.band;

			// If no band field, try to determine from frequency
			if (!spot_band) {
				spot_band = getBandFromFrequency(single.frequency);
			}

			passesBandFilter = bands.includes(spot_band);
		}
		if (!passesBandFilter) return;			// Apply de continent filter (which continent the spotter is in)
			// When multiple de continents are selected, fetch 'Any' from backend and filter client-side
			let passesDeContFilter = deContinent.includes('Any');
			if (!passesDeContFilter && single.dxcc_spotter && single.dxcc_spotter.cont) {
				passesDeContFilter = deContinent.includes(single.dxcc_spotter.cont);
			}
			if (!passesDeContFilter) return;

			// Apply spotted continent filter (which continent the DX station is in)
			let passesContinentFilter = spottedContinents.includes('Any');
			if (!passesContinentFilter) {
				passesContinentFilter = spottedContinents.includes(single.dxcc_spotted.cont);
			}
			if (!passesContinentFilter) return;

			// Apply mode filter (client-side for multi-select)
			let passesModeFilter = modes.includes('All');
			if (!passesModeFilter) {
				let spot_mode_category = getModeCategory(single.mode);
				// Only pass if mode has a category and it matches one of the selected filters
				passesModeFilter = spot_mode_category && modes.includes(spot_mode_category);
			}
			if (!passesModeFilter) return;

			// Apply additional flags filter (POTA, SOTA, WWFF, IOTA, Contest, Fresh)
			let passesFlagsFilter = flags.includes('All');
			if (!passesFlagsFilter) {
				for (let flag of flags) {
					if (flag === 'SOTA' && single.dxcc_spotted && single.dxcc_spotted.sota_ref) {
						passesFlagsFilter = true;
						break;
					}
					if (flag === 'POTA' && single.dxcc_spotted && single.dxcc_spotted.pota_ref) {
						passesFlagsFilter = true;
						break;
					}
					if (flag === 'WWFF' && single.dxcc_spotted && single.dxcc_spotted.wwff_ref) {
						passesFlagsFilter = true;
						break;
					}
					if (flag === 'IOTA' && single.dxcc_spotted && single.dxcc_spotted.iota_ref) {
						passesFlagsFilter = true;
						break;
					}
					if (flag === 'Contest' && single.dxcc_spotted && single.dxcc_spotted.isContest) {
						passesFlagsFilter = true;
						break;
					}
					if (flag === 'Fresh' && (single.age || 0) < 5) {
						passesFlagsFilter = true;
						break;
					}
				}
			}
			if (!passesFlagsFilter) return;

			// All filters passed - build table row data
			spots2render++;
			var data = [];
			var dxcc_wked_info, wked_info;

			// Color code DXCC entity: green=confirmed, yellow=worked, red=new

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
		let lotw_title = 'LoTW User. Last upload was ' + single.dxcc_spotted.lotw_user + ' days ago';
		lotw_badge = '<a id="lotw_badge" href="https://lotw.arrl.org/lotwuser/act?act=' + single.spotted + '" target="_blank" style="display: inline-flex; align-items: center; vertical-align: middle; text-decoration: none;">' + buildBadge('success ' + lclass, '', lotw_title, 'L') + '</a>';
	}

	// Build activity badges (POTA, SOTA, WWFF, IOTA, Contest, Worked)
	let activity_flags = '';		if (single.dxcc_spotted && single.dxcc_spotted.pota_ref) {
			let pota_title = 'POTA: ' + single.dxcc_spotted.pota_ref;
			if (single.dxcc_spotted.pota_mode) {
				pota_title += ' (' + single.dxcc_spotted.pota_mode + ')';
			}
			pota_title += ' - Click to view on POTA.app';
			let pota_url = 'https://pota.app/#/park/' + single.dxcc_spotted.pota_ref;
			activity_flags += '<a href="' + pota_url + '" target="_blank" onclick="event.stopPropagation();">' + buildBadge('success', 'fa-tree', pota_title) + '</a>';
		}

		if (single.dxcc_spotted && single.dxcc_spotted.sota_ref) {
			let sota_title = 'SOTA: ' + single.dxcc_spotted.sota_ref + ' - Click to view on SOTL.as';
			let sota_url = 'https://sotl.as/summits/' + single.dxcc_spotted.sota_ref;
			activity_flags += '<a href="' + sota_url + '" target="_blank" onclick="event.stopPropagation();">' + buildBadge('primary', 'fa-mountain', sota_title) + '</a>';
		}

		if (single.dxcc_spotted && single.dxcc_spotted.wwff_ref) {
			let wwff_title = 'WWFF: ' + single.dxcc_spotted.wwff_ref + ' - Click to view on WWFF.co';
			let wwff_url = 'https://wwff.co/directory/?showRef=' + single.dxcc_spotted.wwff_ref;
			activity_flags += '<a href="' + wwff_url + '" target="_blank" onclick="event.stopPropagation();">' + buildBadge('success', 'fa-leaf', wwff_title) + '</a>';
		}			if (single.dxcc_spotted && single.dxcc_spotted.iota_ref) {
				activity_flags += buildBadge('info', 'fa-island-tropical', 'IOTA: ' + single.dxcc_spotted.iota_ref);
			}

		if (single.dxcc_spotted && single.dxcc_spotted.isContest) {
			activity_flags += buildBadge('warning', 'fa-trophy', 'Contest');
		}

		// Add "Fresh" badge for spots less than 5 minutes old
		let ageMinutesCheck = single.age || 0;
		let isFresh = ageMinutesCheck < 5;

		if (single.worked_call) {
			let worked_title = 'Worked Before';
			if (single.last_wked && single.last_wked.LAST_QSO && single.last_wked.LAST_MODE) {
				worked_title = 'Worked: ' + single.last_wked.LAST_QSO + ' in ' + single.last_wked.LAST_MODE;
			}
			let worked_badge_type = single.cnfmd_call ? 'success' : 'warning';
			// isLast is true only if fresh badge won't be added
			activity_flags += buildBadge(worked_badge_type, 'fa-check-circle', worked_title, null, !isFresh);
		}

		if (isFresh) {
			activity_flags += buildBadge('danger', 'fa-bolt', 'Fresh spot (< 5 minutes old)', null, true);
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

		// Mode column: capitalize properly
		let displayMode = single.mode || '';
		if (displayMode.toLowerCase() === 'phone') displayMode = 'Phone';
		else if (displayMode.toLowerCase() === 'cw') displayMode = 'CW';
		else if (displayMode.toLowerCase() === 'digi') displayMode = 'Digi';
		data[0].push(displayMode);

		// Callsign column: wrap in QRZ link with color coding
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
		continent_wked_info = ((continent_wked_info != '' ? '<span class="' + continent_wked_info + '">' : '') + single.dxcc_spotted.cont + (continent_wked_info != '' ? '</span>' : ''));
		data[0].push(continent_wked_info);

		// CQ Zone column: show CQ Zone (moved here, right after Cont)
		data[0].push(single.dxcc_spotted.cqz || '');

		// Flag column: just the flag emoji without entity name
		let flag_only = '';
		if (single.dxcc_spotted.flag) {
			flag_only = '<span class="flag-emoji">' + single.dxcc_spotted.flag + '</span>';
		}
		data[0].push(flag_only);

		// Entity column: entity name with color coding (no flag)
		let dxcc_entity_full = single.dxcc_spotted.entity;
		let entity_colored = (dxcc_wked_info != '' ? '<span class="' + dxcc_wked_info + '">' : '') + single.dxcc_spotted.entity + (dxcc_wked_info != '' ? '</span>' : '');
		data[0].push('<a href="javascript:spawnLookupModal(\'' + single.dxcc_spotted.dxcc_id + '\',\'dxcc\')"; data-bs-toggle="tooltip" title="See details for ' + dxcc_entity_full + '">' + entity_colored + '</a>');

		// DXCC Number column: show ADIF DXCC entity number with color coding
		let dxcc_number = ((dxcc_wked_info != '' ? '<span class="' + dxcc_wked_info + '">' : '') + single.dxcc_spotted.dxcc_id + (dxcc_wked_info != '' ? '</span>' : ''));
		data[0].push(dxcc_number);

		// de Callsign column (Spotter) - clickable QRZ link
		let spotterQrzLink = '<a href="https://www.qrz.com/db/' + single.spotter + '" target="_blank" onclick="event.stopPropagation();" data-bs-toggle="tooltip" title="Click to view ' + single.spotter + ' on QRZ.com">' + single.spotter + '</a>';
		data[0].push(spotterQrzLink);

		// de Cont column: spotter's continent
		data[0].push(single.dxcc_spotter.cont || '');

		// de CQZ column: spotter's CQ Zone
		data[0].push(single.dxcc_spotter.cqz || '');

		// Special column: combine LoTW and activity badges
		let flags_column = lotw_badge + activity_flags;
		data[0].push(flags_column);

		// Message column
		data[0].push(single.message || '');

			// Add row to table (with "fresh" class for new spots animation)
			if (oldtable.length > 0) {
				let update = false;
				oldtable.each(function (srow) {
					if (JSON.stringify(srow) === JSON.stringify(data[0])) {
						update = true;
					}
				});
				if (!update) {
					table.rows.add(data).draw().nodes().to$().addClass("fresh");
				} else {
					table.rows.add(data).draw();
				}
			} else {
				table.rows.add(data).draw();
			}
		});

		// Remove "fresh" highlight after 10 seconds
		setTimeout(function () {
			$(".fresh").removeClass("fresh");
		}, 10000);

		if (spots2render == 0) {
			table.clear();
			table.settings()[0].oLanguage.sEmptyTable = "No data available";
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

		// Update status bar after render completes
		setTimeout(function() {
			if (!isFetchInProgress) {
				let actualDisplayedCount = table.rows({search: 'applied'}).count();
				updateStatusBar(cachedSpotData.length, actualDisplayedCount, getServerFilterText(), getClientFilterText(), false, false);
				$('#refreshIcon').removeClass('fa-spinner fa-spin').addClass('fa-hourglass-half');
				$('#refreshTimer').text('Next update in ' + refreshCountdown + 's');
			}
		}, 100);
	}

	// ========================================
	// BAND COUNT BADGES
	// ========================================

	// Update badge counts on band and mode filter buttons
	function updateBandCountBadges() {
		if (!cachedSpotData || cachedSpotData.length === 0) {
			// Clear all badges when no data
			$('.band-count-badge, .mode-count-badge').text('0');
			return;
		}

		// Count spots per band and mode
		let bandCounts = {};
		let modeCounts = { cw: 0, digi: 0, phone: 0 };
		let totalSpots = 0;

	cachedSpotData.forEach((spot) => {
		// Count by band
		let freq_khz = spot.frequency;
		let band = spot.band;

		// If no band field, try to determine from frequency
		if (!band) {
			band = getBandFromFrequency(freq_khz);
		}

		if (band) {
			bandCounts[band] = (bandCounts[band] || 0) + 1;
			totalSpots++;
		}

		// Count by mode
		let modeCategory = getModeCategory(spot.mode);
		if (modeCategory && modeCounts.hasOwnProperty(modeCategory)) {
			modeCounts[modeCategory]++;
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
			let count = bandCounts[band] || 0;
			let $badge = $('#toggle' + band + 'Filter .band-count-badge');
			if ($badge.length === 0) {
				// Badge doesn't exist yet, create it
				$('#toggle' + band + 'Filter').append(' <span class="badge bg-secondary band-count-badge">' + count + '</span>');
			} else {
				// Update existing badge
				$badge.text(count);
			}
		});

		// Update band group button badges (VHF, UHF, SHF)
		['VHF', 'UHF', 'SHF'].forEach(group => {
			let count = groupCounts[group] || 0;
			let $badge = $('#toggle' + group + 'Filter .band-count-badge');
			if ($badge.length === 0) {
				// Badge doesn't exist yet, create it
				$('#toggle' + group + 'Filter').append(' <span class="badge bg-secondary band-count-badge">' + count + '</span>');
			} else {
				// Update existing badge
				$badge.text(count);
			}
		});

		// Update mode button badges
		const modeButtons = ['Cw', 'Digi', 'Phone'];
		modeButtons.forEach(mode => {
			let modeKey = mode.toLowerCase();
			let count = modeCounts[modeKey] || 0;
			let $badge = $('#toggle' + mode + 'Filter .mode-count-badge');
			if ($badge.length === 0) {
				// Badge doesn't exist yet, create it
				$('#toggle' + mode + 'Filter').append(' <span class="badge bg-secondary mode-count-badge">' + count + '</span>');
			} else {
				// Update existing badge
				$badge.text(count);
			}
		});
	}

	// ========================================
	// BACKEND DATA FETCH
	// ========================================

	// Fetch spot data from DX cluster API
	// Backend filters: band, de continent (where spotter is), mode
	// Client filters applied after fetch: cwn, spotted continent, additionalFlags
	function fill_list(de, maxAgeMinutes) {
		var table = get_dtable();

		// Normalize de continent parameter to array
		let deContinent = Array.isArray(de) ? de : [de];
		if (deContinent.includes('Any') || deContinent.length === 0) deContinent = ['Any'];

		// Backend API only accepts single values for continent
		// Band and mode are always 'All' - filtering happens client-side
		let continentForAPI = 'Any';
		if (deContinent.length === 1 && !deContinent.includes('Any')) continentForAPI = deContinent[0];

		// Update backend filter state (only continent now)
		loadedBackendFilters = {
			continent: continentForAPI
		};

		lastFetchParams.continent = continentForAPI;
		lastFetchParams.maxAge = maxAgeMinutes;

		// Build API URL: /spots/{band}/{maxAge}/{continent}/{mode}
		// Always use 'All' for band and mode - we filter client-side
		let dxurl = dxcluster_provider + "/spots/All/" + maxAgeMinutes + "/" + continentForAPI + "/All";
		console.log('Loading from backend: ' + dxurl);

		// Cancel any in-flight request before starting new one
		if (currentAjaxRequest) {
			console.log('Aborting previous fetch request');
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

			if (dxspots.length > 0) {
				dxspots.sort(SortByQrg);  // Sort by frequency
				cachedSpotData = dxspots;
			} else {
				cachedSpotData = [];
			}

			lastFetchParams.timestamp = new Date();
			isFetchInProgress = false;

			renderFilteredSpots();  // Apply client-side filters and render
			startRefreshTimer();  // Start 60s countdown

		}).fail(function(jqXHR, textStatus) {
			currentAjaxRequest = null;

			// Don't show error if user cancelled the request
			if (textStatus === 'abort') {
				console.log('Fetch request aborted');
				return;
			}

			cachedSpotData = null;
			isFetchInProgress = false;
			table.clear();
			table.settings()[0].oLanguage.sEmptyTable = "Error loading spots. Please try again.";
			table.draw();
			updateStatusBar(0, 0, getServerFilterText(), getClientFilterText(), false, false);
			startRefreshTimer();
		});
	}	// Highlight rows within ±20 kHz of specified frequency (for CAT integration)
	function highlight_current_qrg(qrg) {
		var table = get_dtable();
		table.rows().eq(0).each( function ( index ) {
			let row = table.row( index );
			var d=row.data();
			var distance=Math.abs(parseInt(d[1])-qrg);
			if (distance<=20) {
				distance++;
				alpha=(.5/distance);
				$(row.node()).css('--bs-table-bg', 'rgba(0,0,255,' + alpha + ')');
				$(row.node()).css('--bs-table-accent-bg', 'rgba(0,0,255,' + alpha + ')');
			} else {
				$(row.node()).css('--bs-table-bg', '');
				$(row.node()).css('--bs-table-accent-bg', '');
			}
		});
	}

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
		const fontSize = text ? '0.7rem' : '0.65rem';
		const content = text ? text : '<i class="fas ' + icon + '"></i>';
		return '<small class="badge text-bg-' + type + '" style="display: inline-flex; align-items: center; justify-content: center; width: 18px; height: 18px; padding: 0; margin: ' + margin + '; font-size: ' + fontSize + '; line-height: 1;" data-bs-toggle="tooltip" title="' + title + '">' + content + '</small>';
	}

	// Map frequency (in kHz) to ham band name
	function getBandFromFrequency(freq_khz) {
		if (freq_khz >= 1800 && freq_khz <= 2000) return '160m';
		if (freq_khz >= 3500 && freq_khz <= 4000) return '80m';
		if (freq_khz >= 5250 && freq_khz <= 5450) return '60m';
		if (freq_khz >= 7000 && freq_khz <= 7300) return '40m';
		if (freq_khz >= 10100 && freq_khz <= 10150) return '30m';
		if (freq_khz >= 14000 && freq_khz <= 14350) return '20m';
		if (freq_khz >= 18068 && freq_khz <= 18168) return '17m';
		if (freq_khz >= 21000 && freq_khz <= 21450) return '15m';
		if (freq_khz >= 24890 && freq_khz <= 24990) return '12m';
		if (freq_khz >= 28000 && freq_khz <= 29700) return '10m';
		if (freq_khz >= 50000 && freq_khz <= 54000) return '6m';
		if (freq_khz >= 70000 && freq_khz <= 71000) return '4m';
		if (freq_khz >= 144000 && freq_khz <= 148000) return '2m';
		if (freq_khz >= 222000 && freq_khz <= 225000) return '1.25m';
		if (freq_khz >= 420000 && freq_khz <= 450000) return '70cm';
		if (freq_khz >= 902000 && freq_khz <= 928000) return '33cm';
		if (freq_khz >= 1240000 && freq_khz <= 1300000) return '23cm';
		if (freq_khz >= 2300000 && freq_khz <= 2450000) return '13cm';
		return 'All';
	}

	// Map individual bands to their band groups (VHF, UHF, SHF)
	function getBandGroup(band) {
		const VHF_BANDS = ['6m', '4m', '2m', '1.25m'];
		const UHF_BANDS = ['70cm', '33cm', '23cm'];
		const SHF_BANDS = ['13cm', '9cm', '6cm', '3cm', '1.25cm', '6mm', '4mm', '2.5mm', '2mm', '1mm'];

		if (VHF_BANDS.includes(band)) return 'VHF';
		if (UHF_BANDS.includes(band)) return 'UHF';
		if (SHF_BANDS.includes(band)) return 'SHF';
		return null; // MF/HF bands don't have groups
	}

	// Get all bands in a band group
	function getBandsInGroup(group) {
		const BAND_GROUPS = {
			'VHF': ['6m', '4m', '2m', '1.25m'],
			'UHF': ['70cm', '33cm', '23cm'],
			'SHF': ['13cm', '9cm', '6cm', '3cm', '1.25cm', '6mm', '4mm', '2.5mm', '2mm', '1mm']
		};
		return BAND_GROUPS[group] || [];
	}

	// Categorize mode as phone/cw/digi for filtering
	function getModeCategory(mode) {
		if (!mode) return null;

		// Mode can come from server as lowercase category names (phone, cw, digi)
		// or as actual mode names (SSB, LSB, FT8, etc.)
		let modeLower = mode.toLowerCase();

		// Check if already a category
		if (['phone', 'cw', 'digi'].includes(modeLower)) {
			return modeLower;
		}

		// Otherwise categorize by mode name
		mode = mode.toUpperCase();

		// Phone modes
		if (['SSB', 'LSB', 'USB', 'FM', 'AM', 'DV'].includes(mode)) return 'phone';

		// CW modes
		if (['CW', 'CWR'].includes(mode)) return 'cw';

		// Digital modes
		if (['RTTY', 'PSK', 'PSK31', 'PSK63', 'FT8', 'FT4', 'JT65', 'JT9', 'MFSK',
		     'OLIVIA', 'CONTESTIA', 'HELL', 'SSTV', 'FAX', 'PACKET', 'PACTOR',
		     'THOR', 'DOMINO', 'MT63', 'ROS', 'WSPR'].includes(mode)) return 'digi';

		// Return null for uncategorized modes instead of 'All'
		return null;
	}

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
		let requiredFlags = $('#requiredFlags').val() || [];

		let continentForAPI = 'Any';
		if (de.length === 1 && !de.includes('Any')) {
			// Single continent selected - use backend filter
			continentForAPI = de[0];
		}
		// If multiple continents selected, fetch 'Any' from backend and filter client-side

		console.log('applyFilters - Current backend filters:', loadedBackendFilters);
		console.log('applyFilters - Requested backend params:', {continent: continentForAPI});

		// Check if backend parameters changed (requires new data fetch)
		// Only de continent affects backend now - band and mode are client-side only
		let backendParamsChanged = forceReload ||
			loadedBackendFilters.continent !== continentForAPI;

		console.log('applyFilters - backendParamsChanged:', backendParamsChanged);

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
			console.log('Reloading from backend: continent=' + continentForAPI);
			table.clear();
			fill_list(de, dxcluster_maxage);
		} else {
			console.log('Client-side filtering changed - using cached data');
			renderFilteredSpots();
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
		$('#cwnSelect').val(['All']);
		$('#decontSelect').val(['Any']);
		$('#continentSelect').val(['Any']);
		$('#band').val(['All']);
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

		syncQuickFilterButtons();
		updateFilterIcon();
		applyFilters(true);
		$('#filterDropdown').dropdown('hide');
	});

	// Clear Filters Quick Button (preserves De Continent)
	$("#clearFiltersButtonQuick").on("click", function() {
		// Preserve current De Continent selection
		let currentDecont = $('#decontSelect').val();

		// Reset all other filters
		$('#cwnSelect').val(['All']).trigger('change');
		$('#continentSelect').val(['Any']).trigger('change');
		$('#band').val(['All']).trigger('change');
		$('#mode').val(['All']).trigger('change');
		$('#additionalFlags').val(['All']).trigger('change');
		$('#requiredFlags').val([]).trigger('change');

		// Restore De Continent
		$('#decontSelect').val(currentDecont).trigger('change');

		// Clear text search
		$('#spotSearchInput').val('');
		table.search('').draw();

		syncQuickFilterButtons();
		updateFilterIcon();
		applyFilters(false);  // Don't refetch from server since De Continent is preserved
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
	});

	$("#searchIcon").on("click", function() {
		const searchValue = $("#spotSearchInput").val();
		if (searchValue.length > 2) {
			table.search(searchValue).draw();
		}
	});

	$("#radio").on("change", function() {
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

	// Debounce timer for de continent filter changes (3 second cooldown)
	let decontFilterTimeout = null;

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
	 * Determine appropriate radio mode based on spot mode and frequency
	 * Similar to dxwaterfall.js logic
	 * @param {string} spotMode - Mode from the spot (e.g., 'CW', 'SSB', 'FT8')
	 * @param {number} freqHz - Frequency in Hz
	 * @returns {string} Radio mode ('CW', 'USB', 'LSB', 'RTTY', etc.)
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
	 * Tune radio to specified frequency when CAT Control is active
	 * @param {number} freqHz - Frequency in Hz
	 * @param {string} mode - Mode (optional, e.g., 'USB', 'LSB', 'CW')
	 */
	function tuneRadio(freqHz, mode) {
		const selectedRadio = $('.radios option:selected').val();

		if (!selectedRadio || selectedRadio === '0') {
			console.log('No radio selected - cannot tune');
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
					console.log('Radio tuned to:', freqHz, 'Hz', 'Mode:', radioMode);
					if (typeof showToast === 'function') {
						showToast('Radio Tuned', `Tuned to ${(freqHz / 1000000).toFixed(3)} MHz (${radioMode})`, 'bg-success text-white', 2000);
					}
				},
				function(jqXHR, textStatus, errorThrown) {
					// Error callback
					console.error('Failed to tune radio:', errorThrown);
					if (typeof showToast === 'function') {
						showToast('Tuning Failed', 'Failed to tune radio to frequency', 'bg-danger text-white', 3000);
					}
				}
			);
		} else {
			console.error('tuneRadioToFrequency function not available');
		}
	}

	function prepareLogging(call, qrg, mode) {
		let ready_listener = true;

		// If CAT Control is enabled, tune the radio to the spot frequency
		if (isCatTrackingEnabled) {
			tuneRadio(qrg, mode);
		}		let check_pong = setInterval(function() {
			if (pong_rcvd || ((Date.now() - qso_window_last_seen) < wait4pong)) {
				clearInterval(check_pong);
				bc2qso.postMessage({ frequency: qrg, call: call });
				// Show toast notification when callsign is sent to existing QSO window
				showToast('QSO Prepared', `Callsign ${call} sent to logging form`, 'bg-success text-white', 3000);
			} else {
				clearInterval(check_pong);
				let cl={};
				cl.call=call;
				cl.qrg=qrg;

				let newWindow = window.open(base_url + 'index.php/qso?manual=0', '_blank');

                if (!newWindow || newWindow.closed || typeof newWindow.closed === 'undefined') {
                    $('#errormessage').html(popup_warning).addClass('alert alert-danger').show();
					setTimeout(function() {
						$('#errormessage').fadeOut();
					}, 3000);
                } else {
                    newWindow.focus();
					// Show toast notification when opening new QSO window
					showToast('QSO Prepared', `Callsign ${call} sent to logging form`, 'bg-success text-white', 3000);
                }

                bc2qso.onmessage = function(ev) {
					if (ready_listener == true) {
						if (ev.data === 'ready') {
							bc2qso.postMessage({ frequency: cl.qrg, call: cl.call })
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

		prepareLogging(call, qrg, mode);
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

	// Save reference to cat.js's updateCATui if it exists
	var catJsUpdateCATui = window.updateCATui;

	// Override updateCATui to add bandmap-specific behavior
	window.updateCATui = function(data) {
		console.log('Bandmap: updateCATui called with data:', data);

		const band = frequencyToBand(data.frequency);

		console.log('Bandmap CAT Update - Frequency:', data.frequency, 'Band:', band, 'Control enabled:', isCatTrackingEnabled);

		// Bandmap-specific: Update band filter if CAT Control is enabled
		if (isCatTrackingEnabled) {
			const currentBands = $("#band").val() || [];

			if (band && band !== '') {
				// Valid band found - set filter to this specific band
				// Check if current selection is not just this band
				if (currentBands.length !== 1 || currentBands[0] !== band) {
					console.log('Updating band filter to:', band);
					$("#band").val([band]);
					updateSelectCheckboxes('band');
					syncQuickFilterButtons();
					applyFilters(false);
					// Show toast notification when band filter is changed by CAT
					if (typeof showToast === 'function') {
						showToast('CAT Control', `Frequency filter changed to ${band} by transceiver`, 'bg-info text-white', 3000);
					}
				}
			} else {
				// No band match - clear band filter to show all bands
				// Only update if not already showing all bands
				if (currentBands.length !== 1 || currentBands[0] !== 'All') {
					console.log('Frequency outside known bands - clearing band filter to show all');
					$("#band").val(['All']);
					updateSelectCheckboxes('band');
					syncQuickFilterButtons();
					applyFilters(false);
					// Show toast notification
					if (typeof showToast === 'function') {
						showToast('CAT Control', 'Frequency outside known bands - showing all bands', 'bg-warning text-dark', 3000);
					}
				}
			}
		}

	// Bandmap-specific: Highlight current QRG in the spot list
	if (data.frequency) {
		highlight_current_qrg((parseInt(data.frequency))/1000);
	}

	// Call cat.js's original updateCATui for standard CAT UI updates
	if (typeof catJsUpdateCATui === 'function') {
		console.log('Bandmap: Calling cat.js updateCATui');

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
};	console.log('Bandmap: CAT integration complete, updateCATui override installed');

	$.fn.dataTable.moment(custom_date_format + ' HH:mm');

	let isFullscreen = false;

	$('#fullscreenToggle').on('click', function() {
		const container = $('#bandmapContainer');
		const icon = $('#fullscreenIcon');

		if (!isFullscreen) {
			container.addClass('bandmap-fullscreen');
			$('body').addClass('fullscreen-active');
			icon.removeClass('fa-expand').addClass('fa-compress');
			$(this).attr('title', 'Exit Fullscreen');

			isFullscreen = true;

			// Request browser fullscreen
			const elem = document.documentElement;
			if (elem.requestFullscreen) {
				elem.requestFullscreen().catch(err => {
					console.log('Fullscreen request failed:', err);
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
			$(this).attr('title', 'Toggle Fullscreen');

			isFullscreen = false;

			// Exit browser fullscreen
			if (document.exitFullscreen) {
				document.exitFullscreen().catch(err => {
					console.log('Exit fullscreen failed:', err);
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
		applyFilters(false);
	});

	// Band filter buttons
	$('#toggle160mFilter').on('click', function() {
		let currentValues = $('#band').val() || [];
		if (currentValues.includes('All')) currentValues = currentValues.filter(v => v !== 'All');
		if (currentValues.includes('160m')) {
			currentValues = currentValues.filter(v => v !== '160m');
			if (currentValues.length === 0) currentValues = ['All'];
		} else {
			currentValues.push('160m');
		}
		$('#band').val(currentValues).trigger('change');
		syncQuickFilterButtons();
		applyFilters(false);
	});

	$('#toggle80mFilter').on('click', function() {
		let currentValues = $('#band').val() || [];
		if (currentValues.includes('All')) currentValues = currentValues.filter(v => v !== 'All');
		if (currentValues.includes('80m')) {
			currentValues = currentValues.filter(v => v !== '80m');
			if (currentValues.length === 0) currentValues = ['All'];
		} else {
			currentValues.push('80m');
		}
		$('#band').val(currentValues).trigger('change');
		syncQuickFilterButtons();
		applyFilters(false);
	});

	$('#toggle60mFilter').on('click', function() {
		let currentValues = $('#band').val() || [];
		if (currentValues.includes('All')) currentValues = currentValues.filter(v => v !== 'All');
		if (currentValues.includes('60m')) {
			currentValues = currentValues.filter(v => v !== '60m');
			if (currentValues.length === 0) currentValues = ['All'];
		} else {
			currentValues.push('60m');
		}
		$('#band').val(currentValues).trigger('change');
		syncQuickFilterButtons();
		applyFilters(false);
	});

	$('#toggle40mFilter').on('click', function() {
		let currentValues = $('#band').val() || [];
		if (currentValues.includes('All')) currentValues = currentValues.filter(v => v !== 'All');
		if (currentValues.includes('40m')) {
			currentValues = currentValues.filter(v => v !== '40m');
			if (currentValues.length === 0) currentValues = ['All'];
		} else {
			currentValues.push('40m');
		}
		$('#band').val(currentValues).trigger('change');
		syncQuickFilterButtons();
		applyFilters(false);
	});

	$('#toggle30mFilter').on('click', function() {
		let currentValues = $('#band').val() || [];
		if (currentValues.includes('All')) currentValues = currentValues.filter(v => v !== 'All');
		if (currentValues.includes('30m')) {
			currentValues = currentValues.filter(v => v !== '30m');
			if (currentValues.length === 0) currentValues = ['All'];
		} else {
			currentValues.push('30m');
		}
		$('#band').val(currentValues).trigger('change');
		syncQuickFilterButtons();
		applyFilters(false);
	});

	$('#toggle20mFilter').on('click', function() {
		let currentValues = $('#band').val() || [];
		if (currentValues.includes('All')) currentValues = currentValues.filter(v => v !== 'All');
		if (currentValues.includes('20m')) {
			currentValues = currentValues.filter(v => v !== '20m');
			if (currentValues.length === 0) currentValues = ['All'];
		} else {
			currentValues.push('20m');
		}
		$('#band').val(currentValues).trigger('change');
		syncQuickFilterButtons();
		applyFilters(false);
	});

	$('#toggle17mFilter').on('click', function() {
		let currentValues = $('#band').val() || [];
		if (currentValues.includes('All')) currentValues = currentValues.filter(v => v !== 'All');
		if (currentValues.includes('17m')) {
			currentValues = currentValues.filter(v => v !== '17m');
			if (currentValues.length === 0) currentValues = ['All'];
		} else {
			currentValues.push('17m');
		}
		$('#band').val(currentValues).trigger('change');
		syncQuickFilterButtons();
		applyFilters(false);
	});

	$('#toggle15mFilter').on('click', function() {
		let currentValues = $('#band').val() || [];
		if (currentValues.includes('All')) currentValues = currentValues.filter(v => v !== 'All');
		if (currentValues.includes('15m')) {
			currentValues = currentValues.filter(v => v !== '15m');
			if (currentValues.length === 0) currentValues = ['All'];
		} else {
			currentValues.push('15m');
		}
		$('#band').val(currentValues).trigger('change');
		syncQuickFilterButtons();
		applyFilters(false);
	});

	$('#toggle12mFilter').on('click', function() {
		let currentValues = $('#band').val() || [];
		if (currentValues.includes('All')) currentValues = currentValues.filter(v => v !== 'All');
		if (currentValues.includes('12m')) {
			currentValues = currentValues.filter(v => v !== '12m');
			if (currentValues.length === 0) currentValues = ['All'];
		} else {
			currentValues.push('12m');
		}
		$('#band').val(currentValues).trigger('change');
		syncQuickFilterButtons();
		applyFilters(false);
	});

	$('#toggle10mFilter').on('click', function() {
		let currentValues = $('#band').val() || [];
		if (currentValues.includes('All')) currentValues = currentValues.filter(v => v !== 'All');
		if (currentValues.includes('10m')) {
			currentValues = currentValues.filter(v => v !== '10m');
			if (currentValues.length === 0) currentValues = ['All'];
		} else {
			currentValues.push('10m');
		}
		$('#band').val(currentValues).trigger('change');
		syncQuickFilterButtons();
		applyFilters(false);
	});

	$('#toggle6mFilter').on('click', function() {
		let currentValues = $('#band').val() || [];
		if (currentValues.includes('All')) currentValues = currentValues.filter(v => v !== 'All');
		if (currentValues.includes('6m')) {
			currentValues = currentValues.filter(v => v !== '6m');
			if (currentValues.length === 0) currentValues = ['All'];
		} else {
			currentValues.push('6m');
		}
		$('#band').val(currentValues).trigger('change');
		syncQuickFilterButtons();
		applyFilters(false);
	});

	$('#toggle4mFilter').on('click', function() {
		let currentValues = $('#band').val() || [];
		if (currentValues.includes('All')) currentValues = currentValues.filter(v => v !== 'All');
		if (currentValues.includes('4m')) {
			currentValues = currentValues.filter(v => v !== '4m');
			if (currentValues.length === 0) currentValues = ['All'];
		} else {
			currentValues.push('4m');
		}
		$('#band').val(currentValues).trigger('change');
		syncQuickFilterButtons();
		applyFilters(false);
	});

	$('#toggle2mFilter').on('click', function() {
		let currentValues = $('#band').val() || [];
		if (currentValues.includes('All')) currentValues = currentValues.filter(v => v !== 'All');
		if (currentValues.includes('2m')) {
			currentValues = currentValues.filter(v => v !== '2m');
			if (currentValues.length === 0) currentValues = ['All'];
		} else {
			currentValues.push('2m');
		}
		$('#band').val(currentValues).trigger('change');
		syncQuickFilterButtons();
		applyFilters(false);
	});

	$('#toggle70cmFilter').on('click', function() {
		let currentValues = $('#band').val() || [];
		if (currentValues.includes('All')) currentValues = currentValues.filter(v => v !== 'All');
		if (currentValues.includes('70cm')) {
			currentValues = currentValues.filter(v => v !== '70cm');
			if (currentValues.length === 0) currentValues = ['All'];
		} else {
			currentValues.push('70cm');
		}
		$('#band').val(currentValues).trigger('change');
		syncQuickFilterButtons();
		applyFilters(false);
	});

	$('#toggle23cmFilter').on('click', function() {
		let currentValues = $('#band').val() || [];
		if (currentValues.includes('All')) currentValues = currentValues.filter(v => v !== 'All');
		if (currentValues.includes('23cm')) {
			currentValues = currentValues.filter(v => v !== '23cm');
			if (currentValues.length === 0) currentValues = ['All'];
		} else {
			currentValues.push('23cm');
		}
		$('#band').val(currentValues).trigger('change');
		syncQuickFilterButtons();
		applyFilters(false);
	});

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

	// Continent filter buttons (spotter's continent - de continent)
	$('#toggleAfricaFilter').on('click', function() {
		let currentValues = $('#decontSelect').val() || [];
		if (currentValues.includes('Any')) currentValues = currentValues.filter(v => v !== 'Any');
		if (currentValues.includes('AF')) {
			currentValues = currentValues.filter(v => v !== 'AF');
			if (currentValues.length === 0) currentValues = ['Any'];
		} else {
			currentValues.push('AF');
			// Check if all continents are now selected
			if (currentValues.includes('AF') && currentValues.includes('AN') && currentValues.includes('AS') &&
			    currentValues.includes('EU') && currentValues.includes('NA') && currentValues.includes('OC') &&
			    currentValues.includes('SA')) {
				currentValues = ['Any'];
			}
		}
		$('#decontSelect').val(currentValues).trigger('change');
		syncQuickFilterButtons();

		// Debounce the filter application (3 second cooldown)
		clearTimeout(decontFilterTimeout);
		decontFilterTimeout = setTimeout(function() {
			applyFilters(false);
		}, 3000);
	});

	$('#toggleAsiaFilter').on('click', function() {
		let currentValues = $('#decontSelect').val() || [];
		if (currentValues.includes('Any')) currentValues = currentValues.filter(v => v !== 'Any');
		if (currentValues.includes('AS')) {
			currentValues = currentValues.filter(v => v !== 'AS');
			if (currentValues.length === 0) currentValues = ['Any'];
		} else {
			currentValues.push('AS');
			// Check if all continents are now selected
			if (currentValues.includes('AF') && currentValues.includes('AN') && currentValues.includes('AS') &&
			    currentValues.includes('EU') && currentValues.includes('NA') && currentValues.includes('OC') &&
			    currentValues.includes('SA')) {
				currentValues = ['Any'];
			}
		}
		$('#decontSelect').val(currentValues).trigger('change');
		syncQuickFilterButtons();

		// Debounce the filter application (3 second cooldown)
		clearTimeout(decontFilterTimeout);
		decontFilterTimeout = setTimeout(function() {
			applyFilters(false);
		}, 3000);
	});

	$('#toggleEuropeFilter').on('click', function() {
		let currentValues = $('#decontSelect').val() || [];
		if (currentValues.includes('Any')) currentValues = currentValues.filter(v => v !== 'Any');
		if (currentValues.includes('EU')) {
			currentValues = currentValues.filter(v => v !== 'EU');
			if (currentValues.length === 0) currentValues = ['Any'];
		} else {
			currentValues.push('EU');
			// Check if all continents are now selected
			if (currentValues.includes('AF') && currentValues.includes('AN') && currentValues.includes('AS') &&
			    currentValues.includes('EU') && currentValues.includes('NA') && currentValues.includes('OC') &&
			    currentValues.includes('SA')) {
				currentValues = ['Any'];
			}
		}
		$('#decontSelect').val(currentValues).trigger('change');
		syncQuickFilterButtons();

		// Debounce the filter application (3 second cooldown)
		clearTimeout(decontFilterTimeout);
		decontFilterTimeout = setTimeout(function() {
			applyFilters(false);
		}, 3000);
	});

	$('#toggleNorthAmericaFilter').on('click', function() {
		let currentValues = $('#decontSelect').val() || [];
		if (currentValues.includes('Any')) currentValues = currentValues.filter(v => v !== 'Any');
		if (currentValues.includes('NA')) {
			currentValues = currentValues.filter(v => v !== 'NA');
			if (currentValues.length === 0) currentValues = ['Any'];
		} else {
			currentValues.push('NA');
			// Check if all continents are now selected
			if (currentValues.includes('AF') && currentValues.includes('AN') && currentValues.includes('AS') &&
			    currentValues.includes('EU') && currentValues.includes('NA') && currentValues.includes('OC') &&
			    currentValues.includes('SA')) {
				currentValues = ['Any'];
			}
		}
		$('#decontSelect').val(currentValues).trigger('change');
		syncQuickFilterButtons();

		// Debounce the filter application (3 second cooldown)
		clearTimeout(decontFilterTimeout);
		decontFilterTimeout = setTimeout(function() {
			applyFilters(false);
		}, 3000);
	});

	$('#toggleSouthAmericaFilter').on('click', function() {
		let currentValues = $('#decontSelect').val() || [];
		if (currentValues.includes('Any')) currentValues = currentValues.filter(v => v !== 'Any');
		if (currentValues.includes('SA')) {
			currentValues = currentValues.filter(v => v !== 'SA');
			if (currentValues.length === 0) currentValues = ['Any'];
		} else {
			currentValues.push('SA');
			// Check if all continents are now selected
			if (currentValues.includes('AF') && currentValues.includes('AN') && currentValues.includes('AS') &&
			    currentValues.includes('EU') && currentValues.includes('NA') && currentValues.includes('OC') &&
			    currentValues.includes('SA')) {
				currentValues = ['Any'];
			}
		}
		$('#decontSelect').val(currentValues).trigger('change');
		syncQuickFilterButtons();

		// Debounce the filter application (3 second cooldown)
		clearTimeout(decontFilterTimeout);
		decontFilterTimeout = setTimeout(function() {
			applyFilters(false);
		}, 3000);
	});

	// Toggle LoTW User filter
	$('#toggleLotwFilter').on('click', function() {
		let currentValues = $('#requiredFlags').val() || [];
		let btn = $(this);

		if (currentValues.includes('lotw')) {
			// Remove LoTW filter
			currentValues = currentValues.filter(v => v !== 'lotw');
			btn.removeClass('btn-warning').addClass('btn-primary');
			btn.find('i').removeClass('fa-check-circle').addClass('fa-upload');
		} else {
			// Add LoTW filter
			currentValues.push('lotw');
			btn.removeClass('btn-primary').addClass('btn-warning');
			btn.find('i').removeClass('fa-upload').addClass('fa-check-circle');
		}

		$('#requiredFlags').val(currentValues).trigger('change');
		applyFilters(false);
	});

	// Toggle Not Worked Before filter
	$('#toggleNotWorkedFilter').on('click', function() {
		let currentValues = $('#requiredFlags').val() || [];
		let btn = $(this);

		if (currentValues.includes('notworked')) {
			// Remove Not Worked filter
			currentValues = currentValues.filter(v => v !== 'notworked');
			btn.removeClass('btn-warning').addClass('btn-primary');
			btn.find('i').removeClass('fa-check-circle').addClass('fa-star');
		} else {
			// Add Not Worked filter
			currentValues.push('notworked');
			btn.removeClass('btn-primary').addClass('btn-warning');
			btn.find('i').removeClass('fa-star').addClass('fa-check-circle');
		}

		$('#requiredFlags').val(currentValues).trigger('change');
		applyFilters(false);
	});

	// Toggle DXCC Needed filter (not worked DXCC)
	$('#toggleDxccNeededFilter').on('click', function() {
		let currentValues = $('#cwnSelect').val() || [];
		let btn = $(this);

		if (currentValues.length === 1 && currentValues[0] === 'notwkd') {
			// Remove DXCC filter - reset to All
			currentValues = ['All'];
			btn.removeClass('btn-warning').addClass('btn-primary');
			btn.find('i').removeClass('fa-check-circle').addClass('fa-globe');
		} else {
			// Set DXCC filter to Not Worked only
			currentValues = ['notwkd'];
			btn.removeClass('btn-primary').addClass('btn-warning');
			btn.find('i').removeClass('fa-globe').addClass('fa-check-circle');
		}

		$('#cwnSelect').val(currentValues).trigger('change');
		applyFilters(false);
	});

	// Toggle Contest filter
	// Toggle Contest filter - moved to Required Flags
	$('#toggleContestFilter').on('click', function() {
		let currentValues = $('#requiredFlags').val() || [];
		let btn = $(this);

		if (currentValues.includes('Contest')) {
			// Remove Contest filter
			currentValues = currentValues.filter(v => v !== 'Contest');
			btn.removeClass('btn-warning').addClass('btn-primary');
			btn.find('i').removeClass('fa-check-circle').addClass('fa-trophy');
		} else {
			// Add Contest filter
			currentValues.push('Contest');
			btn.removeClass('btn-primary').addClass('btn-warning');
			btn.find('i').removeClass('fa-trophy').addClass('fa-check-circle');
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
			btn.removeClass('btn-warning').addClass('btn-primary');
			btn.find('i').removeClass('fa-check-circle').addClass('fa-map-marked-alt');
		} else {
			// Add all geo flags
			currentValues = currentValues.concat(geoFlags.filter(flag => !currentValues.includes(flag)));
			btn.removeClass('btn-primary').addClass('btn-warning');
			btn.find('i').removeClass('fa-map-marked-alt').addClass('fa-check-circle');
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
			btn.removeClass('btn-warning').addClass('btn-primary');
			btn.find('i').removeClass('fa-check-circle').addClass('fa-bolt');
		} else {
			// Add Fresh filter
			currentValues.push('Fresh');
			btn.removeClass('btn-primary').addClass('btn-warning');
			btn.find('i').removeClass('fa-bolt').addClass('fa-check-circle');
		}

		$('#additionalFlags').val(currentValues).trigger('change');
		applyFilters(false);
	});

	// Toggle Favorites filter - applies user's active bands and modes
	$('#toggleFavoritesFilter').on('click', function() {
		// Fetch user's active bands and modes
		let base_url = dxcluster_provider.replace('/dxcluster', '');
		$.ajax({
			url: base_url + '/bandmap/get_user_favorites',
			method: 'GET',
			dataType: 'json',
			success: function(favorites) {
				// Apply bands
				if (favorites.bands && favorites.bands.length > 0) {
					$('#band').val(favorites.bands).trigger('change');
				} else {
					// No active bands, set to All
					$('#band').val(['All']).trigger('change');
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
				applyFilters(false);

				showToast('My Favorites', 'Applied your favorite bands and modes', 'bg-success text-white', 3000);
			},
			error: function() {
				showToast('My Favorites', 'Failed to load favorites', 'bg-danger text-white', 3000);
			}
		});
	});

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
			bandLabel.append(' <i class="fas fa-info-circle cat-control-info" title="Band filtering is controlled by your radio when CAT Control is enabled"></i>');
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

	// Toggle CAT Control
	$('#toggleCatTracking').on('click', function() {
		let btn = $(this);

		if (btn.hasClass('btn-warning')) {
			// Disable CAT Control
			btn.removeClass('btn-warning').addClass('btn-primary');
			btn.find('i').removeClass('fa-check-circle').addClass('fa-radio');
			isCatTrackingEnabled = false;
			window.isCatTrackingEnabled = false; // Update window variable for cat.js
			console.log('CAT Control disabled');

			// Hide radio status when CAT Control is disabled
			$('#radio_cat_state').remove();

			// Re-enable band filter controls
			enableBandFilterControls();
		} else {
			// Enable CAT Control
			btn.removeClass('btn-primary').addClass('btn-warning');
			btn.find('i').removeClass('fa-radio').addClass('fa-check-circle');
			isCatTrackingEnabled = true;
			window.isCatTrackingEnabled = true; // Update window variable for cat.js
			console.log('CAT Control enabled');

			// Trigger radio status display if we have data
			if (window.lastCATData) {
				if (typeof window.displayRadioStatus === 'function') {
					window.displayRadioStatus('success', window.lastCATData);
				}
			}

			// Disable band filter controls
			disableBandFilterControls();

			// Immediately apply current radio frequency if available
			if (window.lastCATData && window.lastCATData.frequency) {
				console.log('Applying current radio frequency:', window.lastCATData.frequency);
				const band = frequencyToBand(window.lastCATData.frequency);

				if (band && band !== '') {
					// Valid band found - set filter to this specific band
					console.log('Setting band filter to:', band);
					$("#band").val([band]);
					updateSelectCheckboxes('band');
					syncQuickFilterButtons();
					applyFilters(false);
					if (typeof showToast === 'function') {
						showToast('CAT Control', `Frequency filter set to ${band} by transceiver`, 'bg-info text-white', 3000);
					}
				} else {
					// No band match - clear band filter to show all bands
					console.log('Frequency outside known bands - showing all');
					$("#band").val(['All']);
					updateSelectCheckboxes('band');
					syncQuickFilterButtons();
					applyFilters(false);
					if (typeof showToast === 'function') {
						showToast('CAT Control', 'Frequency outside known bands - showing all bands', 'bg-warning text-dark', 3000);
					}
				}
			} else {
				console.log('No radio data available yet - waiting for next CAT update');
				if (typeof showToast === 'function') {
					showToast('CAT Control', 'Waiting for radio data...', 'bg-info text-white', 2000);
				}
			}
		}
	});

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

	// Initialize ResizeObserver to watch for container size changes
	if (typeof ResizeObserver !== 'undefined') {
		const tableContainer = document.querySelector('.table-responsive');
		if (tableContainer) {
			const resizeObserver = new ResizeObserver(function(entries) {
				handleResponsiveColumns();
			});
			resizeObserver.observe(tableContainer);
		}
	} else {
		// Fallback for browsers without ResizeObserver support
		$(window).on('resize', function() {
			handleResponsiveColumns();
		});
	}

	// Initial call to set up column visibility
	handleResponsiveColumns();

	// ========================================
	// INITIALIZE CAT CONTROL STATE
	// ========================================

	/**
	 * Initialize CAT Control state on page load
	 * CAT Control is OFF by default, so ensure band filter controls are enabled
	 */
	enableBandFilterControls();

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

});
