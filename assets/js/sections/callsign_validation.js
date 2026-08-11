/*
 * Shared client-side callsign format validation.
 *
 * Single source of truth on the client: mirrors Logbook_model::is_valid_callsign()
 */
window.wlIsValidCallsign = function (call) {
	if (call === null || call === undefined) return false;
	return /^[A-Z0-9\/-]{1,30}$/.test(String(call).trim().toUpperCase());
};
