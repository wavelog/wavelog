/**
 * Parse a callsign into core, prefix, and suffix.
 * @param {string} callsign
 * @returns {{core: string, prefix: string|null, suffix: string|null}}
 */
function parseCallsign(callsign) {
	callsign = (callsign || '').toUpperCase().trim();
	let prefix = null;
	let suffix = null;
	let core = callsign;
	// Match prefix (e.g. DL/SP9MOA)
	let prefixMatch = callsign.match(/^([A-Z0-9]+)\/([A-Z0-9]+)$/);
	if (prefixMatch) {
		prefix = prefixMatch[1];
		core = prefixMatch[2];
	}
	// Match suffix (e.g. SP9MOA/P)
	let suffixMatch = core.match(/^([A-Z0-9]+)\/(P|M|QRP|MM|AM|A|B|C|D|E|F|G|H|J|K|L|N|R|S|T|U|V|W|X|Y|Z)$/);
	if (suffixMatch) {
		core = suffixMatch[1];
		suffix = suffixMatch[2];
	}
	// Remove any remaining slashes from core
	core = core.replace(/\//g, '');
	return {
		core: core,
		prefix: prefix,
		suffix: suffix
	};
}
