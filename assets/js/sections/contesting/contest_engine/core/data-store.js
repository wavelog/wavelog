export class DataStore {
	constructor(storageKey = null) {
		// Without storageKey we cannot persist data uniquely
		if (!storageKey) {
			throw new Error('DataStore: storageKey missing');
		}
		this.storageKey = storageKey;
		this.sessionId = storageKey.replace(/^wl_contestdata_/, '');
		this.data = new Map(); // In-memory store for ALL data
		this.persistentNamespaces = new Set(['qso', 'session', 'config']);
		this.listeners = {}; // Global event listeners (for backwards compatibility)
		this.subscriptions = new Map(); // Key-specific subscriptions
		this.syncRequests = new Set(); // Active sync requests

		// IndexedDB state
		this.db = null;
		this.idbReady = false;
		this._idbWriteQueue = Promise.resolve(); // Serializes async writes

		// init() is the async entry point — called by app.js with await
	}

	/**
	 * Async initialization — opens IndexedDB, loads data, cleans up old sessions.
	 * Must be called (and awaited) before using the DataStore.
	 * @returns {DataStore} this
	 */
	async init() {
		await this._openIDB();
		await this.load();
		await this.cleanupOldSessions();
		return this;
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
			this._persistKey(key, value);
		}

		this.requestSync(key);
	}

	/**
	 * Delete data by key
	 * @param {string} key - Data key
	 * @returns {boolean} True if deleted
	 */
	delete(key) {
		const deleted = this.data.delete(key);
		if (deleted && this.shouldPersist(key)) {
			this._unpersistKey(key);
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
	 * Check if key should be persisted
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
		let count = 0;
		for (const value of this.getPattern('qso.*').values()) {
			if (value?.state === 'synced') count++;
		}
		return count;
	}

	// === PERSISTENCE METHODS ===

	/**
	 * Open IndexedDB connection and create schema if needed.
	 * Resolves gracefully on failure — idbReady stays false.
	 * @private
	 */
	_openIDB() {
		return new Promise((resolve) => {
			if (!('indexedDB' in window)) {
				console.warn('DataStore: IndexedDB not available, falling back to localStorage');
				resolve();
				return;
			}

			const request = indexedDB.open('wavelog_contest', 1);

			request.onerror = () => {
				console.error('DataStore: Failed to open IndexedDB', request.error);
				resolve();
			};

			request.onsuccess = () => {
				this.db = request.result;
				this.idbReady = true;

				this.db.onclose = () => {
					console.warn('DataStore: IDB connection closed unexpectedly');
					this.idbReady = false;
				};
				this.db.onerror = (event) => {
					console.error('DataStore: IDB error', event.target.error);
				};

				resolve();
			};

			request.onupgradeneeded = (event) => {
				const db = event.target.result;

				if (!db.objectStoreNames.contains('contest_records')) {
					const store = db.createObjectStore('contest_records', { keyPath: 'id' });
					store.createIndex('sessionId', 'sessionId', { unique: false });
					store.createIndex('namespace', 'namespace', { unique: false });
				}

				if (!db.objectStoreNames.contains('session_metadata')) {
					db.createObjectStore('session_metadata', { keyPath: 'sessionId' });
				}
			};
		});
	}

	/**
	 * Load data from IndexedDB or fall back to localStorage.
	 * @private
	 */
	async load() {
		if (this.idbReady) {
			await this._loadFromIDB();
		} else {
			this._loadFromLocalStorage();
		}
	}

	/**
	 * Load all records for this session from IndexedDB.
	 * @private
	 */
	_loadFromIDB() {
		return new Promise((resolve) => {
			try {
				const tx = this.db.transaction('contest_records', 'readonly');
				const store = tx.objectStore('contest_records');
				const index = store.index('sessionId');
				const request = index.getAll(IDBKeyRange.only(this.sessionId));

				request.onsuccess = () => {
					const records = request.result;
					if (records.length === 0) {
						// New session — initialize and register in metadata
						this.data.set('_created', new Date().toISOString());
						this._upsertSessionMetadata();
					} else {
						records.forEach(record => {
							this.data.set(record.flatKey, record.value);
						});
						console.info(`DataStore: Loaded ${records.length} records from IndexedDB`);
					}
					resolve();
				};

				request.onerror = () => {
					console.error('DataStore: IDB load error', request.error);
					this._loadFromLocalStorage();
					resolve();
				};
			} catch (e) {
				console.error('DataStore: IDB load exception', e);
				this._loadFromLocalStorage();
				resolve();
			}
		});
	}

	/**
	 * Load from localStorage (fallback when IndexedDB is unavailable).
	 * @private
	 */
	_loadFromLocalStorage() {
		try {
			const stored = localStorage.getItem(this.storageKey);
			if (!stored) {
				this.data = new Map();
				this.data.set('_created', new Date().toISOString());
				return;
			}

			const parsed = JSON.parse(stored);

			// v1 format is deprecated — start fresh
			if (!parsed._version || parsed._version === '1.0') {
				console.info('DataStore: Found v1 format data, initializing empty store');
				this.data = new Map();
				this.data.set('_created', new Date().toISOString());
				return;
			}

			if (parsed._created) {
				this.data.set('_created', parsed._created);
			}

			this.persistentNamespaces.forEach(namespace => {
				if (parsed[namespace]) {
					this.loadNamespace(namespace, parsed[namespace]);
				}
			});
		} catch (e) {
			console.error('DataStore: localStorage load error', e);
			this.data = new Map();
		}
	}

	/**
	 * Load namespace data into Map (used by localStorage fallback)
	 * @private
	 */
	loadNamespace(namespace, data) {
		if (typeof data === 'object' && data !== null) {
			Object.entries(data).forEach(([subKey, subData]) => {
				if (typeof subData === 'object' && subData !== null && !Array.isArray(subData)) {
					const hasNestedObjects = Object.values(subData).some(v =>
						typeof v === 'object' && v !== null && !Array.isArray(v)
					);

					if (hasNestedObjects) {
						Object.entries(subData).forEach(([id, item]) => {
							const fullKey = `${namespace}.${subKey}.${id}`;
							this.data.set(fullKey, item);
						});
					} else {
						const fullKey = `${namespace}.${subKey}`;
						this.data.set(fullKey, subData);
					}
				} else {
					const fullKey = `${namespace}.${subKey}`;
					this.data.set(fullKey, subData);
				}
			});
		}
	}

	/**
	 * Persist a key to IndexedDB (fire-and-forget, serialized via write queue).
	 * Falls back to localStorage if IDB is not ready.
	 * @private
	 */
	_persistKey(key, value) {
		if (!this.idbReady) {
			this._saveToLocalStorage();
			return;
		}
		this._idbWriteQueue = this._idbWriteQueue
			.then(() => this._idbPut(key, value))
			.catch(e => console.error('DataStore: IDB write error for key', key, e));
	}

	/**
	 * Remove a key from IndexedDB (fire-and-forget, serialized via write queue).
	 * Falls back to localStorage if IDB is not ready.
	 * @private
	 */
	_unpersistKey(key) {
		if (!this.idbReady) {
			this._saveToLocalStorage();
			return;
		}
		this._idbWriteQueue = this._idbWriteQueue
			.then(() => this._idbDelete(key))
			.catch(e => console.error('DataStore: IDB delete error for key', key, e));
	}

	/**
	 * Write a single record to IndexedDB.
	 * @private
	 */
	_idbPut(key, value) {
		return new Promise((resolve, reject) => {
			const tx = this.db.transaction('contest_records', 'readwrite');
			const store = tx.objectStore('contest_records');
			store.put({
				id: `${this.sessionId}::${key}`,
				sessionId: this.sessionId,
				flatKey: key,
				namespace: key.split('.')[0],
				value: value,
				modified: Date.now()
			});
			tx.oncomplete = () => resolve();
			tx.onerror = () => reject(tx.error);
			tx.onabort = () => reject(tx.error);
		});
	}

	/**
	 * Delete a single record from IndexedDB.
	 * @private
	 */
	_idbDelete(key) {
		return new Promise((resolve, reject) => {
			const tx = this.db.transaction('contest_records', 'readwrite');
			const store = tx.objectStore('contest_records');
			store.delete(`${this.sessionId}::${key}`);
			tx.oncomplete = () => resolve();
			tx.onerror = () => reject(tx.error);
			tx.onabort = () => reject(tx.error);
		});
	}

	/**
	 * Write or update the session metadata entry.
	 * @private
	 */
	_upsertSessionMetadata() {
		if (!this.idbReady) return;
		const tx = this.db.transaction('session_metadata', 'readwrite');
		const store = tx.objectStore('session_metadata');
		const created = this.data.get('_created');
		store.put({
			sessionId: this.sessionId,
			storageKey: this.storageKey,
			created: created ? new Date(created).getTime() : Date.now()
		});
		tx.onerror = () => console.warn('DataStore: Failed to upsert session metadata', tx.error);
	}

	/**
	 * Save all persistent data to localStorage (fallback when IDB unavailable).
	 * @private
	 */
	_saveToLocalStorage() {
		try {
			const output = {
				_version: '2.0',
				_created: this.data.get('_created') || new Date().toISOString(),
				_last_modified: new Date().toISOString()
			};

			this.persistentNamespaces.forEach(namespace => {
				output[namespace] = this.buildNamespaceStructure(namespace);
			});

			localStorage.setItem(this.storageKey, JSON.stringify(output));
		} catch (e) {
			console.error('DataStore: localStorage save error', e);
		}
	}

	/**
	 * Build hierarchical structure for a namespace (used by localStorage fallback)
	 * @private
	 */
	buildNamespaceStructure(namespace) {
		const result = {};

		for (const [key, value] of this.data.entries()) {
			if (key.startsWith(namespace + '.')) {
				const parts = key.split('.');

				if (parts.length === 2) {
					result[parts[1]] = value;
				} else if (parts.length === 3) {
					if (!result[parts[1]]) {
						result[parts[1]] = {};
					}
					result[parts[1]][parts[2]] = value;
				}
			}
		}

		return result;
	}

	/**
	 * Cleanup old sessions (>7 days) from IndexedDB and localStorage.
	 */
	async cleanupOldSessions() {
		const cutoff = Date.now() - 7 * 24 * 60 * 60 * 1000;

		if (this.idbReady) {
			await this._cleanupOldSessionsFromIDB(cutoff);
		}

		this._cleanupOldSessionsFromLocalStorage(cutoff);
	}

	/**
	 * Remove sessions older than cutoff from IndexedDB.
	 * @private
	 */
	_cleanupOldSessionsFromIDB(cutoff) {
		return new Promise((resolve) => {
			try {
				const tx = this.db.transaction(['session_metadata', 'contest_records'], 'readwrite');
				const metaStore = tx.objectStore('session_metadata');
				const recordStore = tx.objectStore('contest_records');
				const request = metaStore.getAll();

				request.onsuccess = () => {
					request.result.forEach(session => {
						if (session.created < cutoff && session.sessionId !== this.sessionId) {
							console.info(`DataStore: Removing old IDB session ${session.sessionId}`);
							const index = recordStore.index('sessionId');
							const cursorRequest = index.openCursor(IDBKeyRange.only(session.sessionId));
							cursorRequest.onsuccess = (e) => {
								const cursor = e.target.result;
								if (cursor) {
									cursor.delete();
									cursor.continue();
								}
							};
							metaStore.delete(session.sessionId);
						}
					});
				};

				tx.oncomplete = () => resolve();
				tx.onerror = () => {
					console.error('DataStore: IDB cleanup error', tx.error);
					resolve();
				};
			} catch (e) {
				console.error('DataStore: IDB cleanup exception', e);
				resolve();
			}
		});
	}

	/**
	 * Remove sessions older than cutoff from localStorage.
	 * @private
	 */
	_cleanupOldSessionsFromLocalStorage(cutoff) {
		Object.keys(localStorage).forEach(key => {
			if (key.startsWith('wl_contestdata_')) {
				try {
					const data = JSON.parse(localStorage.getItem(key));
					const created = new Date(data._created).getTime();
					if (created < cutoff) {
						console.info(`DataStore: Removing old localStorage session ${key}`);
						localStorage.removeItem(key);
					}
				} catch (e) {
					console.warn(`DataStore: Invalid data in ${key}, skipping cleanup`);
				}
			}
		});
	}
}

// Register on global scope
window.DataStore = DataStore;
