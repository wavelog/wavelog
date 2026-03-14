export class DataStore {
	constructor(storageKey = null) {
		// Without storageKey we cannot persist data uniquely
		if (!storageKey) {
			throw new Error('DataStore: storageKey missing');
		}
		this.storageKey = storageKey;
		this.data = new Map(); // In-memory store for ALL data
		this.persistentNamespaces = new Set(['qso', 'session', 'config']);
		this.listeners = {}; // Global event listeners (for backwards compatibility)
		this.subscriptions = new Map(); // Key-specific subscriptions
		this.syncRequests = new Set(); // Active sync requests
		
		this.load(); // Load from localStorage
		this.cleanupOldSessions(); // Cleanup sessions older than 7 days
	}

	/**
	 * Get data by key
	 * @param {string} key - Data key (e.g., "radio.1.frequency")
	 * @returns {*} Value or undefined
	 */
	get(key) {
		return this.data.get(key);
	}

	/**
	 * Set data by key
	 * @param {string} key - Data key
	 * @param {*} value - Value to store
	 */
	set(key, value) {
		this.data.set(key, value);
		this.notify(key, value);
		
		// Persist if namespace is persistent
		if (this.shouldPersist(key)) {
			this.save();
		}
	}

	/**
	 * Delete data by key
	 * @param {string} key - Data key
	 * @returns {boolean} True if deleted
	 */
	delete(key) {
		const deleted = this.data.delete(key);
		if (deleted && this.shouldPersist(key)) {
			this.save();
		}
		return deleted;
	}

	/**
	 * Subscribe to updates for a specific key
	 * @param {string} key - Data key to watch
	 * @param {Function} callback - Callback function(value)
	 * @param {Object} options - { realtime: boolean }
	 * @returns {*} Current value
	 */
	subscribe(key, callback, options = {}) {
		if (!this.subscriptions.has(key)) {
			this.subscriptions.set(key, new Set());
		}
		this.subscriptions.get(key).add(callback);

		// If realtime updates requested, register with sync engine
		if (options.realtime) {
			this.requestSync(key);
		}

		// Return current value immediately
		return this.get(key);
	}

	/**
	 * Unsubscribe from key updates
	 * @param {string} key - Data key
	 * @param {Function} callback - Callback to remove
	 */
	unsubscribe(key, callback) {
		const subs = this.subscriptions.get(key);
		if (subs) {
			subs.delete(callback);
			if (subs.size === 0) {
				this.subscriptions.delete(key);
				this.cancelSync(key);
			}
		}
	}

	/**
	 * Notify subscribers of key change
	 * @private
	 */
	notify(key, value) {
		const subs = this.subscriptions.get(key);
		if (subs) {
			subs.forEach(callback => {
				try {
					callback(value);
				} catch (e) {
					console.error('DataStore: Subscription callback error', e);
				}
			});
		}
	}

	/**
	 * Request sync for a key
	 * @param {string} key - Data key to sync
	 */
	requestSync(key) {
		this.syncRequests.add(key);
		this.emit('sync_requested', key);
	}

	/**
	 * Cancel sync for a key
	 * @param {string} key - Data key
	 */
	cancelSync(key) {
		this.syncRequests.delete(key);
		this.emit('sync_cancelled', key);
	}

	/**
	 * Get all active sync requests
	 * @returns {Array<string>} Array of keys
	 */
	getActiveSyncRequests() {
		return Array.from(this.syncRequests);
	}

	/**
	 * Check if key should be persisted to localStorage
	 * @private
	 */
	shouldPersist(key) {
		const namespace = key.split('.')[0];
		return this.persistentNamespaces.has(namespace);
	}

	/**
	 * Get all items matching a pattern
	 * @param {string} pattern - Pattern with * wildcard (e.g., "qso.pending.*")
	 * @returns {Map} Map of matching key-value pairs
	 */
	getPattern(pattern) {
		const regex = new RegExp('^' + pattern.replace(/\*/g, '.*') + '$');
		const result = new Map();
		
		for (const [key, value] of this.data.entries()) {
			if (regex.test(key)) {
				result.set(key, value);
			}
		}
		
		return result;
	}

	/**
	 * Get all items from a namespace
	 * @param {string} namespace - Namespace name (e.g., "radio")
	 * @returns {Map} Map of matching key-value pairs
	 */
	getNamespace(namespace) {
		const result = new Map();
		for (const [key, value] of this.data.entries()) {
			if (key.startsWith(namespace + '.')) {
				result.set(key, value);
			}
		}
		return result;
	}

	// Event system for internal coordination
	on(event, callback) {
		if (!this.listeners[event]) {
			this.listeners[event] = [];
		}
		this.listeners[event].push(callback);
	}

	off(event, callback) {
		if (!this.listeners[event]) return;
		this.listeners[event] = this.listeners[event].filter(cb => cb !== callback);
	}

	emit(event, data) {
		if (!this.listeners[event]) return;
		this.listeners[event].forEach(callback => {
			try {
				callback(data);
			} catch (e) {
				console.error('DataStore: Event callback error', e);
			}
		});
	}

	// === HELPER METHODS ===

	/**
	 * Generate temporary ID for new QSOs
	 * @returns {string} Temporary ID
	 */
	generateId() {
		return 'tmp_' + Date.now() + '_' + Math.random().toString(36).slice(2, 11);
	}

	/**
	 * Get count of synced QSOs
	 * @returns {number} Count
	 */
	getSyncedQSOCount() {
		return this.getPattern('qso.synced.*').size;
	}

	// === PERSISTENCE METHODS ===

	/**
	 * Load from LocalStorage
	 */
	load() {
		try {
			const stored = localStorage.getItem(this.storageKey);
			if (!stored) {
				this.data = new Map();
				this.set('_created', new Date().toISOString());
				return;
			}

			const parsed = JSON.parse(stored);
			
			// Check version and migrate if needed
			if (!parsed._version || parsed._version === '1.0') {
				this.migrate_v1_to_v2(parsed);
				return;
			}

			// Load metadata
			if (parsed._created) {
				this.data.set('_created', parsed._created);
			}

			// Load all persistent namespaces into Map
			this.persistentNamespaces.forEach(namespace => {
				if (parsed[namespace]) {
					this.loadNamespace(namespace, parsed[namespace]);
				}
			});

		} catch (e) {
			console.error('DataStore: Load error', e);
			this.data = new Map();
		}
	}

	/**
	 * Load namespace data into Map
	 * @private
	 */
	loadNamespace(namespace, data) {
		if (typeof data === 'object' && data !== null) {
			Object.entries(data).forEach(([subKey, subData]) => {
				if (typeof subData === 'object' && subData !== null && !Array.isArray(subData)) {
					// Check if this is a nested structure (e.g., qso.pending.tmp_123)
					const hasNestedObjects = Object.values(subData).some(v => 
						typeof v === 'object' && v !== null && !Array.isArray(v)
					);
					
					if (hasNestedObjects) {
						// Another level (e.g., qso.pending.tmp_123)
						Object.entries(subData).forEach(([id, item]) => {
							const fullKey = `${namespace}.${subKey}.${id}`;
							this.data.set(fullKey, item);
						});
					} else {
						// Direct object value (e.g., session.config)
						const fullKey = `${namespace}.${subKey}`;
						this.data.set(fullKey, subData);
					}
				} else {
					// Direct value (e.g., session.id)
					const fullKey = `${namespace}.${subKey}`;
					this.data.set(fullKey, subData);
				}
			});
		}
	}

	/**
	 * Save to LocalStorage
	 */
	save() {
		try {
			const output = {
				_version: '2.0',
				_created: this.data.get('_created') || new Date().toISOString(),
				_last_modified: new Date().toISOString()
			};

			// Build hierarchical structure from flat Map
			this.persistentNamespaces.forEach(namespace => {
				output[namespace] = this.buildNamespaceStructure(namespace);
			});

			localStorage.setItem(this.storageKey, JSON.stringify(output));
		} catch (e) {
			console.error('DataStore: Save error', e);
		}
	}

	/**
	 * Build hierarchical structure for a namespace
	 * @private
	 */
	buildNamespaceStructure(namespace) {
		const result = {};
		
		// Find all keys starting with this namespace
		for (const [key, value] of this.data.entries()) {
			if (key.startsWith(namespace + '.')) {
				const parts = key.split('.');
				
				if (parts.length === 2) {
					// Direct value: "session.id"
					result[parts[1]] = value;
				} else if (parts.length === 3) {
					// Nested: "qso.pending.tmp_123"
					if (!result[parts[1]]) {
						result[parts[1]] = {};
					}
					result[parts[1]][parts[2]] = value;
				}
				// Add more levels if needed in the future
			}
		}
		
		return result;
	}

	/**
	 * Migrate from v1 (old array format) to v2 (hierarchical)
	 * @private
	 */
	migrate_v1_to_v2(oldData) {
		console.info('DataStore: Found v1 format data, initializing empty store');
		// v1 format is deprecated - start fresh
		this.data = new Map();
		this.set('_created', new Date().toISOString());
	}

	/**
	 * Cleanup old sessions (>7 days)
	 * @static
	 */
	cleanupOldSessions() {
		const now = Date.now();
		const maxAge = 7 * 24 * 60 * 60 * 1000; // 7 days
		
		Object.keys(localStorage).forEach(key => {
			if (key.includes('_wavelog_qsos')) {
				try {
					const data = JSON.parse(localStorage.getItem(key));
					const created = new Date(data._created).getTime();
					
					if (now - created > maxAge) {
						console.info(`DataStore: Removing old session ${key}`);
						localStorage.removeItem(key);
					}
				} catch (e) {
					// Invalid data, consider removing it
					console.warn(`DataStore: Invalid data in ${key}, skipping cleanup`);
				}
			}
		});
	}
}

// Register on global scope
window.DataStore = DataStore;