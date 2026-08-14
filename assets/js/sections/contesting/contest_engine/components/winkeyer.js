/**
 * WinkeyerComponent - CW keyer integration for the contest logger
 * Delegates all serial/macro logic to the globally loaded winkey.js
 */
class WinkeyerComponent {
	constructor(containerId) {
		this.container = document.getElementById(containerId);
		if (!this.container) {
			console.warn('WinkeyerComponent: Container #' + containerId + ' not found');
			return;
		}

		if (typeof window.initWinkeyer !== 'function') {
			console.warn('WinkeyerComponent: winkey.js not loaded or initWinkeyer() unavailable');
			return;
		}

		window.initWinkeyer();
	}

	destroy() {}
}

// Self-register when app is ready
window.addEventListener('contestAppReady', () => {
	const winkeyerComponent = new WinkeyerComponent('winkeyer-component');

	if (window.contestApp) {
		window.contestApp.winkeyerComponent = winkeyerComponent;
	}
});

window.WinkeyerComponent = WinkeyerComponent;
