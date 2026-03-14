/**
 * TransportAdapter - Abstract base class for sync transport implementations
 * Allows switching between different transport mechanisms (Ajax, WebSocket, etc.)
 * 
 * Don't import this directly to app.js - import specific implementations instead
 */
export class TransportAdapter {
	/**
	 * Send a sync payload to the server
	 * @param {Object} payload - The payload to send
	 * @returns {Promise} - Resolves with server response
	 * @throws {Error} - Network or server errors
	 */
	send(payload) {
		throw new Error('send() must be implemented by subclass');
	}

	/**
	 * Check if transport is connected/ready
	 * @returns {boolean}
	 */
	isConnected() {
		throw new Error('isConnected() must be implemented by subclass');
	}

	/**
	 * Cleanup resources
	 */
	// TODO: Do we need this? Ajax transport might not need cleanup. Websocket transport would. Really?
	disconnect() {
		// Optional - override if needed
	}
}
