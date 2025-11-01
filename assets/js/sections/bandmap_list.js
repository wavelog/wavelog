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

		// Contest button
		if (additionalFlags.includes('Contest')) {
			$('#toggleContextFilter').removeClass('btn-primary').addClass('btn-warning');
			$('#toggleContextFilter i').removeClass('fa-trophy').addClass('fa-check-circle');
		} else {
			$('#toggleContextFilter').removeClass('btn-warning').addClass('btn-primary');
			$('#toggleContextFilter i').removeClass('fa-check-circle').addClass('fa-trophy');
		}

		// Geo Hunter button
		let geoFlags = ['POTA', 'SOTA', 'IOTA', 'WWFF'];
		let hasGeoFlag = geoFlags.some(flag => additionalFlags.includes(flag));
		if (hasGeoFlag) {
			$('#toggleGeoHunterFilter').removeClass('btn-primary').addClass('btn-warning');
			$('#toggleGeoHunterFilter i').removeClass('fa-map-marked-alt').addClass('fa-check-circle');
		} else {
			$('#toggleGeoHunterFilter').removeClass('btn-warning').addClass('btn-primary');
			$('#toggleGeoHunterFilter i').removeClass('fa-check-circle').addClass('fa-map-marked-alt');
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
		let allModesSelected = modeValues.length === 1 && modeValues.includes('All');
		let allContinentsSelected = decontValues.length === 1 && decontValues.includes('Any');

		// Band filter buttons - green if All, orange if specific band, blue if not selected
		let bandButtons = ['#toggle160mFilter', '#toggle80mFilter', '#toggle60mFilter', '#toggle40mFilter', '#toggle30mFilter',
		                   '#toggle20mFilter', '#toggle17mFilter', '#toggle15mFilter', '#toggle12mFilter', '#toggle10mFilter',
		                   '#toggle6mFilter', '#toggle4mFilter', '#toggle2mFilter', '#toggle70cmFilter', '#toggle23cmFilter'];
		let bandIds = ['160m', '80m', '60m', '40m', '30m', '20m', '17m', '15m', '12m', '10m', '6m', '4m', '2m', '70cm', '23cm'];

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

		// Mode buttons - green if All, orange if selected, blue if not
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
					'targets': 1, "type":"num",
					'createdCell':  function (td, cellData, rowData, row, col) {
						$(td).addClass("MHz");
					}
				},
				{
					'targets': 2,
					'createdCell':  function (td, cellData, rowData, row, col) {
						$(td).addClass("mode");
					}
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

		let cellIndex = $(e.target).closest('td').index();			// If clicking callsign column, open QRZ link directly
			if (cellIndex === 3) {
				let rowData = table.row(this).data();
				if (!rowData) return;

				let callsignHtml = rowData[3];
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

			let callsignHtml = rowData[3];
			let tempDiv = $('<div>').html(callsignHtml);
			let call = tempDiv.find('a').text().trim();
			if (!call) return;

			let qrg = parseFloat(rowData[1]) * 1000;
			let mode = rowData[2];

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
			allFilters = allFilters.concat(serverFilters.map(f => 'de ' + f));
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
			statusMessage += ', showing ' + displayedSpots + ' (active filters: ' + allFilters.join(', ') + ')';
		} else if (displayedSpots < totalSpots) {
			statusMessage += ', showing ' + displayedSpots;
		} else if (totalSpots > 0) {
			statusMessage += ', showing all';
		}

		let tooltipLines = ['Last fetched for:'];
		tooltipLines.push('Band: ' + lastFetchParams.band);
		tooltipLines.push('Continent: ' + lastFetchParams.continent);
		tooltipLines.push('Mode: ' + lastFetchParams.mode);
		tooltipLines.push('Max Age: ' + lastFetchParams.maxAge + ' min');
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
			filters.push('DXCC: ' + cwnLabels.join('/'));
		}

		// Band is now a client filter
		if (!currentFilters.band.includes('All')) {
			filters.push('Band: ' + currentFilters.band.join('/'));
		}

		if (!currentFilters.spottedContinent.includes('Any')) {
			filters.push('spotted: ' + currentFilters.spottedContinent.join('/'));
		}

		// Mode is now a client filter
		if (!currentFilters.mode.includes('All')) {
			let modeLabels = currentFilters.mode.map(function(m) {
				return m.charAt(0).toUpperCase() + m.slice(1);
			});
			filters.push('Mode: ' + modeLabels.join('/'));
		}

		if (!currentFilters.additionalFlags.includes('All')) {
			filters.push(currentFilters.additionalFlags.join('/'));
		}

		// Required flags - special handling (must have ALL selected flags)
		if (currentFilters.requiredFlags && currentFilters.requiredFlags.length > 0) {
			let requiredLabels = currentFilters.requiredFlags.map(function(flag) {
				if (flag === 'lotw') return 'LoTW User';
				if (flag === 'notworked') return 'Not worked before';
				return flag;
			});
			filters.push('Required: ' + requiredLabels.join(' + '));
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
				let freq_khz = single.frequency;
				let spot_band = getBandFromFrequency(freq_khz);
				passesBandFilter = bands.includes(spot_band);
			}
			if (!passesBandFilter) return;

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

			// Apply additional flags filter (POTA, SOTA, WWFF, IOTA, Contest)
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
				lotw_badge = '<a id="lotw_badge" href="https://lotw.arrl.org/lotwuser/act?act=' + single.spotted + '" target="_blank">' + buildBadge('success ' + lclass, '', lotw_title, 'L') + '</a>';
			}

		// Build activity badges (POTA, SOTA, WWFF, IOTA, Contest, Worked)
		let activity_flags = '';

		if (single.dxcc_spotted && single.dxcc_spotted.pota_ref) {
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

			if (single.worked_call) {
				let worked_title = 'Worked Before';
				if (single.last_wked && single.last_wked.LAST_QSO && single.last_wked.LAST_MODE) {
					worked_title = 'Worked: ' + single.last_wked.LAST_QSO + ' in ' + single.last_wked.LAST_MODE;
				}
			let worked_badge_type = single.cnfmd_call ? 'success' : 'warning';
			activity_flags += buildBadge(worked_badge_type, 'fa-check-circle', worked_title, null, true);
		}

		// Build table row array
		data[0] = [];
		// Time column: extract time portion from ISO datetime and format as HH:MM
		if (timeOnly) {
			// ISO format: split by 'T' and take time part, then remove milliseconds and Z
			if (timeOnly.includes('T')) {
				timeOnly = timeOnly.split('T')[1].split('.')[0];
			}
			// Extract only HH:MM from HH:MM:SS
			if (timeOnly.includes(':')) {
				let timeParts = timeOnly.split(':');
				timeOnly = timeParts[0] + ':' + timeParts[1];
			}
		}
		data[0].push(timeOnly || '');
		// Frequency column: convert kHz to MHz with 3 decimal places
		let freqMHz = (single.frequency / 1000).toFixed(3);
		data[0].push(freqMHz);			// Mode column: capitalize properly

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

			// Continent column: color code based on worked/confirmed status (moved before DXCC)
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

			// DXCC entity column: flag emoji + entity name with color coding
			let dxcc_entity_full = single.dxcc_spotted.entity;
			if (single.dxcc_spotted.flag) {
				let flagSpan = '<span class="flag-emoji">' + single.dxcc_spotted.flag + '</span>';
				dxcc_wked_info = ((dxcc_wked_info != '' ? '<span class="' + dxcc_wked_info + '">' : '') + flagSpan + ' <span style="font-family: monospace;">' + single.dxcc_spotted.entity + '</span>' + (dxcc_wked_info != '' ? '</span>' : ''));
			} else {
				dxcc_wked_info = ((dxcc_wked_info != '' ? '<span class="' + dxcc_wked_info + '">' : '') + '<span style="font-family: monospace;">' + single.dxcc_spotted.entity + '</span>' + (dxcc_wked_info != '' ? '</span>' : ''));
			}
			data[0].push('<a href="javascript:spawnLookupModal(\'' + single.dxcc_spotted.dxcc_id + '\',\'dxcc\')"; data-bs-toggle="tooltip" title="See details for ' + dxcc_entity_full + '">' + dxcc_wked_info + '</a>');

			// Spotter column
			data[0].push(single.spotter);

			// Flags column: combine LoTW and activity badges
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
			continentForAPI = de[0];
		}

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

		// Clear text search
		$('#spotSearchInput').val('');
		table.search('').draw();

		syncQuickFilterButtons();
		updateFilterIcon();
		applyFilters(true);
		$('#filterDropdown').dropdown('hide');
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
	var CatCallbackURL = "http://127.0.0.1:54321";

	let wait4pong = 2000;
	let check_intv = 100;

	function prepareLogging(call, qrg, mode) {
		let ready_listener = true;

		try {
			let irrelevant = fetch(CatCallbackURL + '/'+qrg+'/'+mode).catch(() => {
				openedWindow = window.open(CatCallbackURL + '/' + qrg + '/' + mode);
				openedWindow.close();
			});
		} finally {}

		let check_pong = setInterval(function() {
			if (pong_rcvd || ((Date.now() - qso_window_last_seen) < wait4pong)) {
				clearInterval(check_pong);
				bc2qso.postMessage({ frequency: qrg, call: call });
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

	let websocket = null;
	let reconnectAttempts = 0;
	let websocketEnabled = false;
	let CATInterval=null;

	function initializeWebSocketConnection() {
		try {
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
				}
			};
			websocket.onerror = function(error) {
				websocketEnabled=false;
			};
			websocket.onclose = function(event) {
				websocketEnabled = false;

				if (reconnectAttempts < 5) {
					setTimeout(() => {
						reconnectAttempts++;
						initializeWebSocketConnection();
					}, 2000 * reconnectAttempts);
				} else {
					$(".radio_cat_state" ).remove();
					$('#radio_status').html('<div class="alert alert-danger radio_timeout_error" role="alert"><i class="fas fa-broadcast-tower"></i> Radio connection timed-out: ' + $('select.radios option:selected').text() + ' Websocket connection lost, chose another radio.</div>');
					websocketEnabled = false;
				}
			};
		} catch (error) {
			websocketEnabled=false;
		}
	}

	function handleWebSocketData(data) {
		if (data.type === 'welcome') {
			return;
		}
		if (data.type === 'radio_status' && data.radio && ($(".radios option:selected").val() == 'ws')) {
			data.updated_minutes_ago = Math.floor((Date.now() - data.timestamp) / 60000);
			data.cat_url = 'http://127.0.0.1:54321';
			updateCATui(data);
		}
	}


	$( "#radio" ).change(function() {
		if (CATInterval) {
			clearInterval(CATInterval);
			CATInterval=null;
		}
		if (websocket) {
			websocket.close();
			websocketEnabled = false;
		}
		if ($("#radio option:selected").val() == '0') {
			$(".radio_cat_state" ).remove();
		} else if ($("#radio option:selected").val() == 'ws') {
			initializeWebSocketConnection();
		} else {
			// Update frequency every three second
			CATInterval=setInterval(updateFromCAT, 3000);
		}
	});

	function updateCATui(data) {
		const band = frequencyToBand(data.frequency);
		CatCallbackURL=data.cat_url;
		if (band !== $("#band").val()) {
			$("#band").val(band);
			$("#band").trigger("change");
		}

		const minutes = Math.floor(cat_timeout_interval / 60);

		if(data.updated_minutes_ago > minutes) {
			$(".radio_cat_state" ).remove();
			if($('.radio_timeout_error').length == 0) {
				$('.messages').prepend('<div class="alert alert-danger radio_timeout_error" role="alert"><i class="fas fa-broadcast-tower"></i> Radio connection timed-out: ' + $('select.radios option:selected').text() + ' data is ' + data.updated_minutes_ago + ' minutes old.</div>');
			} else {
				$('.radio_timeout_error').html('Radio connection timed-out: ' + $('select.radios option:selected').text() + ' data is ' + data.updated_minutes_ago + ' minutes old.');
			}
		} else {
			$(".radio_timeout_error" ).remove();
			text = '<i class="fas fa-broadcast-tower"></i><span style="margin-left:10px;"></span><b>TX:</b> '+(Math.round(parseInt(data.frequency)/100)/10000).toFixed(4)+' MHz';
			highlight_current_qrg((parseInt(data.frequency))/1000);
			if(data.mode != null) {
				text = text+'<span style="margin-left:10px"></span>'+data.mode;
			}
			if(data.power != null && data.power != 0) {
				text = text+'<span style="margin-left:10px"></span>'+data.power+' W';
			}
			if (! $('#radio_cat_state').length) {
				$('.messages').prepend('<div aria-hidden="true"><div id="radio_cat_state" class="alert alert-success radio_cat_state" role="alert">'+text+'</div></div>');
			} else {
				$('#radio_cat_state').html(text);
			}
		}
	}

	var updateFromCAT = function() {
		if($('select.radios option:selected').val() != '0') {
			radioID = $('select.radios option:selected').val();
			$.getJSON( base_url+"index.php/radio/json/" + radioID, function( data ) {

				if (data.error) {
					if (data.error == 'not_logged_in') {
						$(".radio_cat_state" ).remove();
						if($('.radio_login_error').length == 0) {
							$('.messages').prepend('<div class="alert alert-danger radio_login_error" role="alert"><i class="fas fa-broadcast-tower"></i> You\'re not logged it. Please <a href="'+base_url+'">login</a></div>');
						}
					}
					// Put future Errorhandling here
				} else {
					if($('.radio_login_error').length != 0) {
						$(".radio_login_error" ).remove();
					}
					updateCATui(data);
				}
			});
		}
	};

	$.fn.dataTable.moment(custom_date_format + ' HH:mm');

	$('#radio').change();

	let isFullscreen = false;

	$('#fullscreenToggle').on('click', function() {
		const container = $('#bandmapContainer');
		const icon = $('#fullscreenIcon');
		const radioSelector = container.find('.d-flex.align-items-center.mb-3').first();

		if (!isFullscreen) {
			container.addClass('bandmap-fullscreen');
			$('body').addClass('fullscreen-active');
			icon.removeClass('fa-expand').addClass('fa-compress');
			$(this).attr('title', 'Exit Fullscreen');

			radioSelector.hide();
			$('#radio_status').hide();
			$('.messages').hide();

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

			radioSelector.show();
			$('#radio_status').show();
			$('.messages').show();

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

	// Continent filter buttons (spotter's continent - de continent)
	$('#toggleAfricaFilter').on('click', function() {
		let currentValues = $('#decontSelect').val() || [];
		if (currentValues.includes('Any')) currentValues = currentValues.filter(v => v !== 'Any');
		if (currentValues.includes('AF')) {
			currentValues = currentValues.filter(v => v !== 'AF');
			if (currentValues.length === 0) currentValues = ['Any'];
		} else {
			currentValues.push('AF');
		}
		$('#decontSelect').val(currentValues).trigger('change');
		syncQuickFilterButtons();
		applyFilters(false);
	});

	$('#toggleAsiaFilter').on('click', function() {
		let currentValues = $('#decontSelect').val() || [];
		if (currentValues.includes('Any')) currentValues = currentValues.filter(v => v !== 'Any');
		if (currentValues.includes('AS')) {
			currentValues = currentValues.filter(v => v !== 'AS');
			if (currentValues.length === 0) currentValues = ['Any'];
		} else {
			currentValues.push('AS');
		}
		$('#decontSelect').val(currentValues).trigger('change');
		syncQuickFilterButtons();
		applyFilters(false);
	});

	$('#toggleEuropeFilter').on('click', function() {
		let currentValues = $('#decontSelect').val() || [];
		if (currentValues.includes('Any')) currentValues = currentValues.filter(v => v !== 'Any');
		if (currentValues.includes('EU')) {
			currentValues = currentValues.filter(v => v !== 'EU');
			if (currentValues.length === 0) currentValues = ['Any'];
		} else {
			currentValues.push('EU');
		}
		$('#decontSelect').val(currentValues).trigger('change');
		syncQuickFilterButtons();
		applyFilters(false);
	});

	$('#toggleNorthAmericaFilter').on('click', function() {
		let currentValues = $('#decontSelect').val() || [];
		if (currentValues.includes('Any')) currentValues = currentValues.filter(v => v !== 'Any');
		if (currentValues.includes('NA')) {
			currentValues = currentValues.filter(v => v !== 'NA');
			if (currentValues.length === 0) currentValues = ['Any'];
		} else {
			currentValues.push('NA');
		}
		$('#decontSelect').val(currentValues).trigger('change');
		syncQuickFilterButtons();
		applyFilters(false);
	});

	$('#toggleSouthAmericaFilter').on('click', function() {
		let currentValues = $('#decontSelect').val() || [];
		if (currentValues.includes('Any')) currentValues = currentValues.filter(v => v !== 'Any');
		if (currentValues.includes('SA')) {
			currentValues = currentValues.filter(v => v !== 'SA');
			if (currentValues.length === 0) currentValues = ['Any'];
		} else {
			currentValues.push('SA');
		}
		$('#decontSelect').val(currentValues).trigger('change');
		syncQuickFilterButtons();
		applyFilters(false);
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
	$('#toggleContextFilter').on('click', function() {
		let currentValues = $('#additionalFlags').val() || [];
		let btn = $(this);

		// Remove 'All' if present
		if (currentValues.includes('All')) {
			currentValues = currentValues.filter(v => v !== 'All');
		}

		if (currentValues.includes('Contest')) {
			// Remove Contest filter
			currentValues = currentValues.filter(v => v !== 'Contest');
			if (currentValues.length === 0) currentValues = ['All'];
			btn.removeClass('btn-warning').addClass('btn-primary');
			btn.find('i').removeClass('fa-check-circle').addClass('fa-trophy');
		} else {
			// Add Contest filter
			currentValues.push('Contest');
			btn.removeClass('btn-primary').addClass('btn-warning');
			btn.find('i').removeClass('fa-trophy').addClass('fa-check-circle');
		}

		$('#additionalFlags').val(currentValues).trigger('change');
		applyFilters(false);
	});

	// Toggle Geo Hunter filter (POTA, SOTA, IOTA, WWFF)
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

});
