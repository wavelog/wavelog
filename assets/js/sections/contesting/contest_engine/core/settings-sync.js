/**
 * SettingsSyncHandler - SyncEngine handler for detecting contest session settings changes.
 *
 * Registers with the SyncEngine to fetch fresh session settings from the server
 * on every heartbeat tick (always-on via buildRequests).
 * On first receipt, stores a baseline snapshot. On subsequent ticks, compares against
 * the baseline and shows a persistent warning toast if the settings have changed,
 * prompting the user to reload. Settings are never applied silently.
 */
export class SettingsSyncHandler {
	constructor(dataStore, syncEngine) {
		this.dataStore = dataStore;
		this.syncEngine = syncEngine;
		this._baseline = null;
		this._notified = false;

		syncEngine.registerSyncHandler('session_settings', {
			buildRequest: () => null,
			buildRequests: () => [{ type: 'get_session_settings' }],
			canHandle: (data) => !!data.session_settings,
			processResponse: (data) => {
				const incoming = JSON.stringify(data.session_settings);

				if (this._baseline === null) {
					this._baseline = incoming;
					return;
				}

				if (!this._notified && incoming !== this._baseline) {
					this._notified = true;
					this._showSettingsChangedToast();
				}
			}
		});
	}

	_showSettingsChangedToast() {
		const container = document.getElementById('toast-container');
		if (!container) return;

		const toastEl = document.createElement('div');
		toastEl.className = 'toast align-items-center bg-warning text-dark';
		toastEl.setAttribute('role', 'alert');
		toastEl.setAttribute('aria-live', 'assertive');
		toastEl.setAttribute('aria-atomic', 'true');
		toastEl.setAttribute('data-bs-autohide', 'false');

		toastEl.innerHTML = `
			<div class="d-flex">
				<div class="toast-body">
					<strong>${lang_warning}</strong><br>
					${lang_settings_changed}<br>
					<a class="text-black d-block text-center mt-2" href="#" onclick="window.location.reload(); return false;"><i class="fas fa-sync-alt"></i> <b>${lang_reload_now}</b></a>
				</div>
			</div>
		`;

		container.appendChild(toastEl);
		new bootstrap.Toast(toastEl).show();

		toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
	}
}
