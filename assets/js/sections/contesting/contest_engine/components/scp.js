/**
 * SCP (Super Check Partial) Component
 * Provides fast callsign lookup from MASTER.SCP and Clublog databases
 * Uses IndexedDB for efficient storage (instead of localStorage)
 */
class SCPComponent {
	constructor(containerId = 'scp') {
		this.container = document.querySelector(`.window#${containerId}`);
		this.isInitialized = false;
		this.callsigns = new Set();
		this.prefixCache = new Map();
		this.isLoading = false;
		this.totalCallsigns = 0;

		// IndexedDB configuration
		this.dbName = 'wavelog_scp';
		this.dbVersion = 2;
		this.storeName = 'callsigns';
		this.db = null;
		this.useIndexedDB = 'indexedDB' in window;

		if (!this.container) {
			console.warn(`SCPComponent: Container not found, retrying...`);
			setTimeout(() => this.retryInit(containerId), 100);
			return;
		}

		this.init();
	}

	retryInit(containerId) {
		this.container = document.querySelector(`.window#${containerId}`);
		if (this.container) {
			this.init();
		} else {
			setTimeout(() => this.retryInit(containerId), 100);
		}
	}

	async init() {
		if (this.isInitialized) return;
		this.isInitialized = true;

		// Initialize IndexedDB if available
		if (this.useIndexedDB) {
			await this.initIndexedDB();
		}

		this.setupEventListeners();
		await this.loadSCPData();
	}

	/**
	 * Initialize IndexedDB database
	 */
	async initIndexedDB() {
		return new Promise((resolve, reject) => {
			const request = indexedDB.open(this.dbName, this.dbVersion);

			request.onerror = () => {
				console.warn('SCPComponent: Failed to open IndexedDB');
				this.useIndexedDB = false;
				reject(request.error);
			};

			request.onsuccess = () => {
				this.db = request.result;
				resolve();
			};

			request.onupgradeneeded = (event) => {
				const db = event.target.result;

				// Create object store for callsigns if it doesn't exist
				if (!db.objectStoreNames.contains(this.storeName)) {
					const store = db.createObjectStore(this.storeName, { keyPath: 'callsign' });
					store.createIndex('callsign', 'callsign', { unique: true });
					// console.info('SCPComponent: Created IndexedDB callsigns object store');
				}

				// Create metadata store if it doesn't exist
				if (!db.objectStoreNames.contains('metadata')) {
					db.createObjectStore('metadata', { keyPath: 'id' });
					// console.info('SCPComponent: Created IndexedDB metadata object store');
				}
			};
		});
	}

	/**
	 * Get metadata from IndexedDB
	 */
	async getMetadata() {
		if (!this.db) return null;

		return new Promise((resolve) => {
			try {
				const tx = this.db.transaction('metadata', 'readonly');
				const store = tx.objectStore('metadata');
				const metaRequest = store.get('scp_version');

				metaRequest.onsuccess = () => {
					resolve(metaRequest.result);
				};

				metaRequest.onerror = () => {
					resolve(null);
				};
			} catch (error) {
				// Metadata store doesn't exist yet
				console.warn('SCPComponent: Metadata store not ready', error);
				resolve(null);
			}
		});
	}

	/**
	 * Save metadata to IndexedDB
	 */
	async saveMetadata(timestamp) {
		if (!this.db) return;

		return new Promise((resolve) => {
			try {
				const tx = this.db.transaction('metadata', 'readwrite');
				const store = tx.objectStore('metadata');
				store.put({ id: 'scp_version', timestamp });

				tx.oncomplete = () => resolve();
				tx.onerror = () => resolve();
			} catch (error) {
				// Metadata store doesn't exist yet
				console.warn('SCPComponent: Could not save metadata', error);
				resolve();
			}
		});
	}

	setupEventListeners() {
		// No local event listeners needed - search is triggered via API
	}

	async loadSCPData() {
		this.isLoading = true;
		this.updateStatus('loading', lang_scp_loading);

		try {
			const maxAge = 7 * 24 * 60 * 60 * 1000; // 7 days (the SCP cron runs by default once a week)

			let combinedData = null;
			let needsFetch = true;
			let cachedCount = 0;

			// Try to load from IndexedDB first
			if (this.useIndexedDB && this.db) {
				cachedCount = await this.getCallsignCountFromIndexedDB();
				const metadata = await this.getMetadata();
				const cacheAge = metadata ? Date.now() - metadata.timestamp : Infinity;

				if (cachedCount > 0 && cacheAge < maxAge) {
					await this.loadCallsignsFromIndexedDB();
					this.updateStatus('ready', lang_scp_ready);
					this.isLoading = false;
					return;
				}
			}

			// Fallback: Try localStorage (for migration)
			const cachedData = localStorage.getItem('scp');
			const cachedVersion = localStorage.getItem('scp_version');
			const cacheAge = Date.now() - (parseInt(cachedVersion) || 0);

			if (cachedData && cacheAge < maxAge) {
				// console.info('SCPComponent: Using cached data from localStorage');
				combinedData = cachedData;
				needsFetch = false;
			}

			// Fetch from server if no cache
			if (needsFetch) {
				// console.info('SCPComponent: Fetching SCP files from server...');
				
				const [masterResponse, clublogResponse] = await Promise.all([
					fetch(base_url + 'assets/resources/MASTER.SCP'),
					fetch(base_url + 'assets/resources/clublog_scp.txt')
				]);

				if (!masterResponse.ok) throw new Error('Failed to fetch MASTER.SCP');
				if (!clublogResponse.ok) throw new Error('Failed to fetch clublog_scp.txt');

				const [masterData, clublogData] = await Promise.all([
					masterResponse.text(),
					clublogResponse.text()
				]);

				combinedData = masterData + '\n' + clublogData;
			}

			// Parse and store the data
			await this.parseData(combinedData);

			// Cache to IndexedDB for future use
			if (this.useIndexedDB && this.db && needsFetch) {
				try {
					await this.saveCallsignsToIndexedDB();
					await this.saveMetadata(Date.now());
					// console.info('SCPComponent: Cached SCP data to IndexedDB');
				} catch (storageError) {
					console.warn('SCPComponent: Could not cache SCP data to IndexedDB', storageError);
				}
			}

			// Clear old localStorage cache to free space
			try {
				localStorage.removeItem('scp');
				localStorage.removeItem('scp_version');
				// console.info('SCPComponent: Cleared old localStorage cache');
			} catch (error) {
				console.warn('SCPComponent: Could not clear localStorage cache', error);
			}

			this.updateStatus('ready', lang_scp_ready);
			this.isLoading = false;

		} catch (error) {
			console.error('SCPComponent: Error loading SCP data', error);
			this.updateStatus('error', lang_scp_error);
			this.isLoading = false;
		}
	}



	async parseData(text) {
		return new Promise((resolve) => {
			// Use setTimeout to avoid blocking the UI
			setTimeout(() => {
				const lines = text.split('\n');
				lines.forEach(line => {
					const callsign = line.trim();
					if (callsign) {
						this.callsigns.add(callsign);
					}
				});

				this.totalCallsigns = this.callsigns.size;
				this.updateTotalCount();
				resolve();
			}, 0);
		});
	}

	/**
	 * Save callsigns to IndexedDB with progress updates
	 */
	async saveCallsignsToIndexedDB() {
		if (!this.db) return;

		const batchSize = 5000;
		const callsignArray = Array.from(this.callsigns);
		let savedCount = 0;

		return new Promise((resolve, reject) => {
			const saveBatch = (startIndex) => {
				if (startIndex >= callsignArray.length) {
					// console.info(`SCPComponent: All ${savedCount} callsigns saved to IndexedDB`);
					resolve();
					return;
				}

				const endIndex = Math.min(startIndex + batchSize, callsignArray.length);
				const batch = callsignArray.slice(startIndex, endIndex);

				const tx = this.db.transaction(this.storeName, 'readwrite');
				const store = tx.objectStore(this.storeName);

				// Clear only on first batch
				if (startIndex === 0) {
					store.clear();
				}

				// Add batch of callsigns
				batch.forEach(callsign => {
					store.add({ callsign });
				});

				tx.oncomplete = () => {
					savedCount = endIndex;
					const percentage = Math.round((savedCount / callsignArray.length) * 100);
					this.updateStatusText(lang_scp_loading_pct.replace('%s', percentage + '%'));
					
					// Schedule next batch
					setTimeout(() => saveBatch(endIndex), 10);
				};

				tx.onerror = () => {
					console.error('SCPComponent: Error saving batch to IndexedDB', tx.error);
					reject(tx.error);
				};
			};

			saveBatch(0);
		});
	}

	/**
	 * Load callsigns from IndexedDB
	 */
	async loadCallsignsFromIndexedDB() {
		if (!this.db) return;

		return new Promise((resolve, reject) => {
			const tx = this.db.transaction(this.storeName, 'readonly');
			const store = tx.objectStore(this.storeName);
			const request = store.getAll();

			request.onsuccess = () => {
				const results = request.result;
				results.forEach(item => {
					this.callsigns.add(item.callsign);
				});
				this.totalCallsigns = this.callsigns.size;
				this.updateTotalCount();
				resolve();
			};

			request.onerror = () => {
				console.error('SCPComponent: Error loading from IndexedDB', request.error);
				reject(request.error);
			};
		});
	}

	/**
	 * Get count of callsigns in IndexedDB
	 */
	async getCallsignCountFromIndexedDB() {
		if (!this.db) return 0;

		return new Promise((resolve) => {
			const tx = this.db.transaction(this.storeName, 'readonly');
			const store = tx.objectStore(this.storeName);
			const request = store.count();

			request.onsuccess = () => {
				resolve(request.result);
			};

			request.onerror = () => {
				resolve(0);
			};
		});
	}

	/**
	 * Public API: Search for callsign (called by other components)
	 * @param {string} query - Partial callsign to search for
	 */
	searchCallsign(query) {
		if (!this.isInitialized || this.isLoading) {
			return;
		}

		this.search(query);
	}

	search(query) {
		if (!query || query.length < 1) {
			this.clearResults();
			return;
		}

		// Check cache first
		if (this.prefixCache.has(query)) {
			this.displayResults(this.prefixCache.get(query), query);
			return;
		}

		// Perform search
		const matches = [];
		const maxResults = 200;

		// Check if query contains wildcard (?)
		const hasWildcard = query.includes('?');
		let regex = null;

		if (hasWildcard) {
			// Convert ? to . for regex (any single character)
			const pattern = '^' + query.replace(/\?/g, '.') + '.*';
			regex = new RegExp(pattern);
		}

		for (let callsign of this.callsigns) {
			let isMatch = false;

			if (hasWildcard) {
				// Wildcard search with regex
				isMatch = regex.test(callsign);
			} else {
				// Simple prefix search
				isMatch = callsign.startsWith(query);
			}

			if (isMatch) {
				matches.push(callsign);
				if (matches.length >= maxResults) break;
			}
		}

		// Sort by length (shorter matches first, then alphabetically)
		matches.sort((a, b) => {
			if (a.length !== b.length) {
				return a.length - b.length;
			}
			return a.localeCompare(b);
		});

		// Cache results
		this.prefixCache.set(query, matches);

		// Display
		this.displayResults(matches, query);
	}

	displayResults(matches, query) {
		const resultsContainer = this.container.querySelector('#scp-results');
		if (!resultsContainer) return;

		if (matches.length === 0) {
			resultsContainer.style.display = 'flex';
			resultsContainer.innerHTML = `
				<div class="scp-empty">
					<i class="fas fa-search fa-2x mb-2"></i>
					<p>${lang_scp_no_matches.replace('%s', query)}</p>
				</div>
			`;
			this.updateMatchCount(0);
			return;
		}

		resultsContainer.style.display = 'block';
		resultsContainer.innerHTML = matches.map(callsign => {
			// Highlight matching prefix
			const highlighted = this.highlightMatch(callsign, query);
			return `
				<div class="scp-result-item font-monospace" data-callsign="${callsign}">
					<span class="scp-callsign">${highlighted}</span>
				</div>
			`;
		}).join('');

		// Add click handlers
		resultsContainer.querySelectorAll('.scp-result-item').forEach(item => {
			item.addEventListener('click', () => {
				const callsign = item.dataset.callsign;
				this.selectCallsign(callsign);
			});
		});

		this.updateMatchCount(matches.length);
	}

	highlightMatch(callsign, query) {
		// If query has wildcard, highlight matching characters differently
		if (query.includes('?')) {
			const pattern = '^' + query.replace(/\?/g, '(.)');
			const regex = new RegExp(pattern);
			const match = callsign.match(regex);

			if (match) {
				let highlighted = '';
				let pos = 0;

				for (let i = 0; i < query.length; i++) {
					if (query[i] === '?') {
						// Wildcard character - show in different color
						highlighted += `<span class="scp-wildcard">${callsign[pos]}</span>`;
					} else {
						// Fixed character
						highlighted += `<span class="scp-highlight">${callsign[pos]}</span>`;
					}
					pos++;
				}

				// Remaining characters
				highlighted += callsign.substring(pos);
				return highlighted;
			}
		}

		// Simple prefix highlighting
		const prefix = callsign.substring(0, query.length);
		const rest = callsign.substring(query.length);
		return `<span class="scp-highlight">${prefix}</span>${rest}`;
	}

	selectCallsign(callsign) {
		console.log('SCP: Selected callsign:', callsign);

		// Try to insert into QSO form if available
		const qsoCallsignInput = document.querySelector('#qso-callsign');
		if (qsoCallsignInput) {
			qsoCallsignInput.value = callsign;
			// Simulate a Tab press: fire blur (triggers the callbook lookup) and
			// move focus to the next field in tab order (tabindex=2), so the
			// operator can keep logging without pressing an extra Tab key.
			qsoCallsignInput.dispatchEvent(new Event('blur', { bubbles: true }));
			const nextField = document.querySelector('[tabindex="2"]');
			if (nextField) nextField.focus();
			else qsoCallsignInput.focus();
		}

		// Emit event for other components
		window.dispatchEvent(new CustomEvent('scp-callsign-selected', {
			detail: { callsign }
		}));
	}

	clearResults() {
		const resultsContainer = this.container.querySelector('#scp-results');
		if (!resultsContainer) return;

		resultsContainer.style.display = 'flex';
		resultsContainer.innerHTML = `
			<div class="text-center text-muted p-4">
				<i class="fas fa-info-circle fa-2x mb-2"></i>
				<p>${lang_scp_hint}</p>
			</div>
		`;
		this.updateMatchCount(0);
	}

	updateStatus(state, message) {
		const statusEl = this.container.querySelector('#scp-status');
		if (!statusEl) return;

		const icons = {
			loading: '<i class="fas fa-circle-notch fa-spin"></i>',
			ready: '<i class="fas fa-check-circle"></i>',
			error: '<i class="fas fa-exclamation-circle"></i>'
		};

		const colors = {
			loading: 'bg-secondary',
			ready: 'bg-success',
			error: 'bg-danger'
		};

		statusEl.className = `badge ${colors[state] || 'bg-secondary'}`;
		statusEl.innerHTML = `${icons[state] || ''} <span class="status-text">${message}</span>`;
	}

	updateStatusText(message) {
		const statusEl = this.container.querySelector('#scp-status .status-text');
		if (statusEl) {
			statusEl.textContent = message;
		}
	}

	updateTotalCount() {
		const countEl = this.container.querySelector('#scp-total-count');
		if (countEl) {
			countEl.textContent = this.totalCallsigns.toLocaleString();
		}
	}

	updateMatchCount(count) {
		const countEl = this.container.querySelector('#scp-match-count');
		if (countEl) {
			countEl.textContent = count.toLocaleString();

			// Pulse animation
			countEl.classList.add('updated');
			setTimeout(() => countEl.classList.remove('updated'), 300);
		}
	}

	hasCallsign(callsign) {
		return this.callsigns.has(callsign.toUpperCase());
	}
}

// Auto-register when app is ready
window.addEventListener('contestAppReady', (event) => {
	const initComponent = () => {
		const scpComponent = new SCPComponent('scp');

		if (window.contestApp) {
			window.contestApp.scpComponent = scpComponent;
		}
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', () => setTimeout(initComponent, 200));
	} else {
		setTimeout(initComponent, 200);
	}
});

// Register globally for debugging
window.SCPComponent = SCPComponent;
