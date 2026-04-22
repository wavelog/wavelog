/**
 * SyncEngine - Core synchronization engine
 * Manages bidirectional communication (Push/Pull) with server
 * Transport-agnostic: works with any TransportAdapter implementation
 * Handler-based: Components register their own sync handlers
 */
export class SyncEngine {
	constructor(dataStore, transportAdapter, windowManager = null) {
		this.dataStore = dataStore;
		this.transport = transportAdapter;
		this.windowManager = windowManager;
		this.isRunning = false;
		this.heartbeatInterval = null;
		this.syncInterval = 1000; // 1 second
		this.consecutiveErrors = 0;
		this.maxConsecutiveErrors = 5;
		this.isPending = false; // Track if a sync request is currently in-flight
		this.lastHeartbeatTime = 0; // Track when last request was sent
		this.heartbeatStartTime = 0; // Track when heartbeat request started
		this.heartbeatMaxDuration = 2000; // Maximum acceptable duration is 2 seconds (no parallel requests possible)
		
		// Handler system
		this.syncHandlers = new Map(); // key pattern -> handler object

		// Listen for sync requests from DataStore
		this.dataStore.on('sync_requested', (key) => {
			// Sync will be picked up in next heartbeat
		});
	}

	/**
	 * Register a sync handler for a key pattern
	 * @param {string} pattern - Key pattern with wildcards (e.g., "radio.*")
	 * @param {Object} handler - Handler with buildRequest, canHandle, processResponse
	 */
	registerSyncHandler(pattern, handler) {
		if (!handler.buildRequest || !handler.canHandle || !handler.processResponse) {
			console.error('SyncEngine: Invalid handler - must have buildRequest, canHandle, processResponse');
			return;
		}
		this.syncHandlers.set(pattern, handler);
	}

	/**
	 * Unregister a sync handler
	 * @param {string} pattern - Key pattern
	 */
	unregisterSyncHandler(pattern) {
		this.syncHandlers.delete(pattern);
	}

	/**
	 * Start the sync heartbeat (1x per second)
	 */
	start() {
		if (this.isRunning) {
			console.warn('SyncEngine: Already running');
			return;
		}

		this.isRunning = true;
		this.consecutiveErrors = 0;
		// console.info('SyncEngine: Started');

		// Run immediately, then every second
		this._heartbeat();
	}

	/**
	 * Stop the sync heartbeat
	 */
	stop() {
		if (this.heartbeatInterval) {
			clearTimeout(this.heartbeatInterval);
		}
		this.isRunning = false;
		// console.info('SyncEngine: Stopped');
	}

	/**
	 * Single heartbeat cycle
	 * @private
	 */
	_heartbeat() {
		if (!this.isRunning) return;

		// We don't need to run the heartbeat if the tab is not active/visible
		if (document.hidden) {
			this.heartbeatInterval = setTimeout(() => this._heartbeat(), this.syncInterval);
			return;
		}

		// If a request is pending, wait for it to complete
		if (this.isPending) {
			this.heartbeatInterval = setTimeout(() => this._heartbeat(), 100);
			return;
		}

		// Ensure minimum interval between requests
		const timeSinceLastHeartbeat = Date.now() - this.lastHeartbeatTime;
		const delayNeeded = Math.max(0, this.syncInterval - timeSinceLastHeartbeat);

		if (delayNeeded > 0) {
			this.heartbeatInterval = setTimeout(() => this._heartbeat(), delayNeeded);
			return;
		}

		try {
			const payload = this._buildPayload();

			// Only sync if there's something to sync
			if (payload.commands.length > 0 || payload.requests.length > 0) {
				this.lastHeartbeatTime = Date.now();
				this.heartbeatStartTime = Date.now(); // Start timing the request
				this.isPending = true;
				this.transport.send(payload)
					.then(response => {
						// Measure heartbeat duration
						const duration = Date.now() - this.heartbeatStartTime;
						if (duration > this.heartbeatMaxDuration && this.windowManager) {
							this.windowManager.showToast(
								'Heartbeat Warning',
								`Heartbeat request took ${duration}ms (threshold: ${this.heartbeatMaxDuration}ms)`, // TODO: Localize and add hint about WavelogWorker (not existing yet)
								'bg-warning text-dark',
								4000
							);
						}
						return this._processResponse(response);
					})
					.catch(err => this._handleError(err))
					.finally(() => {
						this.isPending = false;
						this.heartbeatInterval = setTimeout(() => this._heartbeat(), 0);
					});
				return;
			}
		} catch (err) {
			console.error('SyncEngine: Heartbeat error', err);
		}

		// Schedule next heartbeat
		this.heartbeatInterval = setTimeout(() => this._heartbeat(), this.syncInterval);
	}

	/**
	 * Build the sync payload from DataStore and handlers
	 * @private
	 * @returns {Object}
	 */
	_buildPayload() {
		const activeSyncKeys = this.dataStore.getActiveSyncRequests();
		
		return {
			session_info: window.ContestLoggerConfig?.sessionInfo,
			timestamp: Date.now(),
			commands: this._collectCommandsFromHandlers(),
			requests: this._buildRequestsFromKeys(activeSyncKeys)
		};
	}

	/**
	 * Build requests from active sync keys using handlers
	 * @private
	 * @returns {Array}
	 */
	_buildRequestsFromKeys(keys) {
		const requests = [];
		const processedKeys = new Set();
		
		keys.forEach(key => {
			// Avoid duplicate requests for same resource
			if (processedKeys.has(key)) return;
			
			// Find matching handler
			for (const [pattern, handler] of this.syncHandlers) {
				if (this._matchPattern(key, pattern)) {
					const request = handler.buildRequest(key, this.dataStore);
					if (request) {
						// Check if we already have a request for this resource
						const isDuplicate = requests.some(r => 
							r.type === request.type && 
							JSON.stringify(r) === JSON.stringify(request)
						);
						
						if (!isDuplicate) {
							requests.push(request);
							processedKeys.add(key);
						}
					}
					break; // Use first matching handler
				}
			}
		});

		// Allow handlers to add always-on requests
		for (const handler of this.syncHandlers.values()) {
			if (typeof handler.buildRequests === 'function') {
				const extraRequests = handler.buildRequests(this.dataStore) || [];
				extraRequests.forEach(req => {
					const isDuplicate = requests.some(r => JSON.stringify(r) === JSON.stringify(req));
					if (!isDuplicate) requests.push(req);
				});
			}
		}

		return requests;
	}

	/**
	 * Match key against pattern
	 * @private
	 */
	_matchPattern(key, pattern) {
		const regex = new RegExp('^' + pattern.replace(/\*/g, '.*') + '$');
		return regex.test(key);
	}

	/**
	 * Collect all commands (server write operations)
	 * @private
	 * @returns {Array}
	 */
	_collectCommandsFromHandlers() {
		const commands = [];

		for (const handler of this.syncHandlers.values()) {
			if (typeof handler.buildCommands === 'function') {
				const handlerCommands = handler.buildCommands(this.dataStore) || [];
				handlerCommands.forEach(cmd => commands.push(cmd));
			}
		}

		return commands;
	}

	/**
	 * Process server response using handlers
	 * @private
	 */
	_processResponse(response) {
		if (!response || !response.success) {
			this.consecutiveErrors++;
			console.warn(
				'SyncEngine: Server returned error',
				response?.error || 'Unknown error'
			);

			if (this.consecutiveErrors >= this.maxConsecutiveErrors) {
				console.error(
					`SyncEngine: Too many errors (${this.consecutiveErrors}), stopping sync`
				);
				this.stop();
			}
			return;
		}

		// Reset error counter on success
		this.consecutiveErrors = 0;

		// Process responses using registered handlers
		if (response.data) {
			// Let handlers process their responses
			for (const [pattern, handler] of this.syncHandlers) {
				try {
					if (handler.canHandle(response.data)) {
						handler.processResponse(response.data, this.dataStore);
					}
				} catch (e) {
					console.error(`SyncEngine: Handler error for pattern "${pattern}"`, e);
				}
			}
		}

		// Log sync status
		if (response.server_qso_count !== undefined) {
			const localCount = this.dataStore.getSyncedQSOCount();
			// console.debug(`SyncEngine: Local=${localCount}, Server=${response.server_qso_count}`);
		}
	}

	/**
	 * Handle sync errors
	 * @private
	 */
	_handleError(err) {
		this.consecutiveErrors++;
		console.error(
			`SyncEngine: Sync failed (${this.consecutiveErrors}/${this.maxConsecutiveErrors})`,
			err.message
		);

		if (this.consecutiveErrors >= this.maxConsecutiveErrors) {
			console.error('SyncEngine: Max error threshold reached, stopping sync');
			this.stop();
		}
	}
}