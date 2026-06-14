import { TransportAdapter } from './transport-adapter.js';

/**
 * AjaxTransport - Concrete implementation of TransportAdapter using fetch/AJAX
 * Handles communication with /contesting/heartbeat endpoint
 */
export class AjaxTransport extends TransportAdapter {
	constructor() {
		super();
		this.endpoint = base_url + 'index.php/contesting/heartbeat';
		this.retryCount = 0;
		this.maxRetries = 3;
		this.timeout = 4000;
	}

	/**
	 * Send payload via POST request
	 * @param {Object} payload - Data to send
	 * @returns {Promise}
	 */
	async send(payload) {
		if (!this.isConnected()) {
			return this._handleError(new Error('Offline'), payload);
		}
		const controller = new AbortController();
		const timeoutId = setTimeout(() => controller.abort(), this.timeout);
		return fetch(this.endpoint, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-Requested-With': 'XMLHttpRequest'
			},
			body: JSON.stringify(payload),
			signal: controller.signal
		})
			.then(response => {
				clearTimeout(timeoutId);
				if (!response.ok) {
					throw new Error(`HTTP ${response.status}: ${response.statusText}`);
				}
				this.retryCount = 0; // reset after a successful round-trip
				return response.json();
			})
			.catch(err => this._handleError(err, payload));
	}

	/**
	 * Handle sync errors with retry logic
	 * @private
	 */
	_handleError(err, payload) {
		this.retryCount++;

		if (this.retryCount < this.maxRetries) {
			console.warn(
				`AjaxTransport: Sync failed (${err.message}). ` +
				`Retry ${this.retryCount}/${this.maxRetries}`
			);
			// Return successful response so SyncEngine doesn't break
			// Next heartbeat will retry sending
			return {
				success: false,
				error: 'Temporary network error, retrying next cycle',
				data: {}
			};
		} else {
			console.error(
				`AjaxTransport: Sync failed after ${this.maxRetries} retries`,
				err
			);
			this.retryCount = 0; // Reset for next attempt
			return {
				success: false,
				error: `Failed after ${this.maxRetries} retries: ${err.message}`,
				data: {}
			};
		}
	}

	/**
	 * Check if transport is ready (always ready for Ajax)
	 * @returns {boolean}
	 */
	isConnected() {
		return navigator.onLine;
	}
}

// Register on global scope
window.AjaxTransport = AjaxTransport;
