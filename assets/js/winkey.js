// Global variables for macros
let function1Name, function1Macro, function2Name, function2Macro, function3Name, function3Macro, function4Name, function4Macro, function5Name, function5Macro, function6Name, function6Macro, function7Name, function7Macro, function8Name, function8Macro, function9Name, function9Macro, function10Name, function10Macro;

// Morse element patterns, used to time the TX status highlight
const WINKEY_MORSE = {
	'A':'.-','B':'-...','C':'-.-.','D':'-..','E':'.','F':'..-.','G':'--.','H':'....','I':'..','J':'.---','K':'-.-','L':'.-..','M':'--','N':'-.','O':'---','P':'.--.','Q':'--.-','R':'.-.','S':'...','T':'-','U':'..-','V':'...-','W':'.--','X':'-..-','Y':'-.--','Z':'--..',
	'0':'-----','1':'.----','2':'..---','3':'...--','4':'....-','5':'.....','6':'-....','7':'--...','8':'---..','9':'----.',
	'.':'.-.-.-',',':'--..--','?':'..--..','/':'-..-.','=':'-...-','+':'.-.-.','-':'-....-','(':'-.--.',')':'-.--.-',':':'---...',"'":'.----.','"':'.-..-.','@':'.--.-.'
};

let winkeyTxTimers = [];

// Render the TX text and progressively highlight each character at the given WPM.
// Dot duration follows the PARIS standard: 1200 / WPM milliseconds.
function winkeyShowTx(text, wpm) {
	const container = document.getElementById('winkeySendStatus');
	if (!container) return;

	winkeyTxTimers.forEach(clearTimeout);
	winkeyTxTimers = [];

	const dotMs = 1200 / (wpm || 20);
	container.innerHTML = '';

	const spans = [...text].map(ch => {
		const span = document.createElement('span');
		span.textContent = ch === ' ' ? ' ' : ch;
		span.className = 'winkey-tx-char';
		container.appendChild(span);
		return span;
	});

	let t = 0;
	[...text].forEach((ch, i) => {
		if (ch === ' ') {
			t += 7 * dotMs; // word gap
			return;
		}
		const code = WINKEY_MORSE[ch.toUpperCase()];
		let units = 0;
		if (code) {
			for (let k = 0; k < code.length; k++) {
				units += code[k] === '-' ? 3 : 1;
				if (k < code.length - 1) units += 1; // intra-character gap
			}
		} else {
			units = 4; // unknown character fallback
		}
		t += units * dotMs;
		const at = t;
		winkeyTxTimers.push(setTimeout(() => spans[i].classList.add('winkey-tx-sent'), at));
		t += 3 * dotMs; // inter-character gap
	});

	// Clear the bar shortly after the transmission finishes
	winkeyTxTimers.push(setTimeout(() => { container.innerHTML = ''; }, t + 1500));
}

// Call url and store the returned json data as variables
function getMacros() {
	fetch(base_url + 'index.php/qso/cwmacros_json')
	.then(response => response.json())
	.then(data => {
		// Check if all macro fields are empty (ignore esm_* config keys)
		const allEmpty = Object.keys(data).filter(key => key.startsWith('function')).every(key => data[key] === "");

		if (allEmpty) {
			// Set default values
			function1Name = 'CQ';
			function1Macro = 'CQ CQ CQ DE [MYCALL] [MYCALL] K';
			function2Name = 'REPT';
			function2Macro = '[CALL] DE [MYCALL] [RSTS] [RSTS] K';
			function3Name = 'TU';
			function3Macro = '[CALL] TU 73 DE [MYCALL] K';
			function4Name = 'QRZ';
			function4Macro = 'QRZ DE [MYCALL] K';
			function5Name = 'TEST';
			function5Macro = 'TEST DE [MYCALL] K';
		} else {
			function1Name = data.function1_name;
			function1Macro = data.function1_macro;
			function2Name = data.function2_name;
			function2Macro = data.function2_macro;
			function3Name = data.function3_name;
			function3Macro = data.function3_macro;
			function4Name = data.function4_name;
			function4Macro = data.function4_macro;
			function5Name = data.function5_name;
			function5Macro = data.function5_macro;
			function6Name = data.function6_name;
			function6Macro = data.function6_macro;
			function7Name = data.function7_name;
			function7Macro = data.function7_macro;
			function8Name = data.function8_name;
			function8Macro = data.function8_macro;
			function9Name = data.function9_name;
			function9Macro = data.function9_macro;
			function10Name = data.function10_name;
			function10Macro = data.function10_macro;
		}

		// ESM (Enter Sends Message) config
		window.winkeyEsmEnabled = data.esm_enabled == 1;
		window.winkeyEsmMap = {
			cq:       parseInt(data.esm_cq, 10)       || 1,
			qrz:      parseInt(data.esm_qrz, 10)      || 4,
			exchange: parseInt(data.esm_exchange, 10) || 2,
			tu:       parseInt(data.esm_tu, 10)       || 3,
			sp:       parseInt(data.esm_sp, 10)       || 4,
			spExch:   parseInt(data.esm_sp_exch, 10)  || 2,
		};
		const esmModeToggle = document.getElementById('esm_mode_toggle');
		if (esmModeToggle) esmModeToggle.style.display = window.winkeyEsmEnabled ? '' : 'none';

		const morsekey_func1_Button = document.getElementById('morsekey_func1');
		morsekey_func1_Button.textContent = 'F1 (' + function1Name + ')';

		const morsekey_func2_Button = document.getElementById('morsekey_func2');
		morsekey_func2_Button.textContent = 'F2 (' + function2Name + ')';

		const morsekey_func3_Button = document.getElementById('morsekey_func3');
		morsekey_func3_Button.textContent = 'F3 (' + function3Name + ')';

		const morsekey_func4_Button = document.getElementById('morsekey_func4');
		morsekey_func4_Button.textContent = 'F4 (' + function4Name + ')';

		const morsekey_func5_Button = document.getElementById('morsekey_func5');
		morsekey_func5_Button.textContent = 'F5 (' + function5Name + ')';

		const morsekey_func6_Button = document.getElementById('morsekey_func6');
		morsekey_func6_Button.textContent = 'F6 (' + function6Name + ')';

		const morsekey_func7_Button = document.getElementById('morsekey_func7');
		morsekey_func7_Button.textContent = 'F7 (' + function7Name + ')';

		const morsekey_func8_Button = document.getElementById('morsekey_func8');
		morsekey_func8_Button.textContent = 'F8 (' + function8Name + ')';

		const morsekey_func9_Button = document.getElementById('morsekey_func9');
		morsekey_func9_Button.textContent = 'F9 (' + function9Name + ')';

		const morsekey_func10_Button = document.getElementById('morsekey_func10');
		morsekey_func10_Button.textContent = 'F10 (' + function10Name + ')';
	});
}

window.initWinkeyer = function() {
	if (window.winkeyInitialized) return;
	window.winkeyInitialized = true;

    const ModeSelected = document.getElementById('mode');

	$('#winkey_buttons').hide();

	if (location.protocol == 'http:') {
		$('#winkey').hide(); // Hide the CW buttons
	}

	getMacros();

	$('#winkey_settings').click(function (event) {
		$.ajax({
			url: base_url + 'index.php/qso/winkeysettings',
			type: 'post',
			data: { contest: window.winkeySerialField ? 1 : 0 },
			success: function (html) {
				BootstrapDialog.show({
					title: 'Winkey Macros',
					size: BootstrapDialog.SIZE_WIDE,
					cssClass: 'options',
					nl2br: false,
					message: html,
					onshown: function(dialog) {
					},
					buttons: [{
						label: 'Save',
						cssClass: 'btn-primary btn-sm',
						id: 'saveButton',
						action: function (dialogItself) {
							winkey_macro_save();
							dialogItself.close();
						}
					},
					{
						label: lang_admin_close,
						cssClass: 'btn-sm',
						id: 'closeButton',
						action: function (dialogItself) {
							$('#optionButton').prop("disabled", false);
							dialogItself.close();
						}
					}],
					onhide: function(dialogRef){
						$('#optionButton').prop("disabled", false);
					},
				});
			}
		});
	});

	// WebSerial requires HTTPS + Chrome/Edge
	if (!navigator.serial) {
		const statusBar = document.getElementById('statusBar');
		const connectButton = document.getElementById('connectButton');
		if (statusBar) statusBar.innerText = location.protocol === 'http:'
			? 'WebSerial requires HTTPS'
			: 'WebSerial not supported - use Chrome or Edge';
		if (connectButton) connectButton.disabled = true;
		return;
	}

	// Function to update winkey visibility based on mode
	// Can be called directly from other scripts (e.g., cat.js)
	window.updateWinkeyVisibility = function(mode) {
		if (mode == 'CW') {
			$('#winkey').show();
		} else {
			$('#winkey').hide();
		}
	};

	if (window.winkeyAlwaysVisible) {
		// Contest context: always show, no mode-based hiding
		$('#winkey').show();
	} else if (ModeSelected) {
		// Normal QSO context: show/hide based on mode dropdown
		updateWinkeyVisibility(ModeSelected.value);
		ModeSelected.addEventListener('change', (event) => {
			updateWinkeyVisibility(event.target.value);
		});
	}

	// Restore the last used CW speed so the user doesn't have to re-set it each session
	const savedCwSpeed = localStorage.getItem('winkey_cw_speed');
	if (savedCwSpeed && document.getElementById('winkeycwspeed')) {
		$('#winkeycwspeed').val(savedCwSpeed);
	}

	$('#winkeycwspeed').change(function (event) {
		// Get the value from the input and store it in localStorage for persistence
		let speed = parseInt($('#winkeycwspeed').val(), 10);
		localStorage.setItem('winkey_cw_speed', speed);

		// Convert to hexadecimal and pad if necessary
		let hexspeed = speed.toString(16).padStart(2, '0');

		// Create the command
		let command = `02 ${hexspeed}`;

		// Send the command as hex bytes
		sendHexToSerial(command);
	});

	// ESM Run / Search & Pounce toggle (operational, per-device preference)
	window.winkeyEsmSP = localStorage.getItem('winkey_esm_sp') === '1';
	const esmModeToggleBtn = document.getElementById('esm_mode_toggle');
	if (esmModeToggleBtn) {
		const updateEsmModeLabel = () => {
			esmModeToggleBtn.textContent = window.winkeyEsmSP ? (window.lang_esm_sp || 'S&P') : (window.lang_esm_run || 'Run');
		};
		updateEsmModeLabel();
		esmModeToggleBtn.addEventListener('click', () => {
			window.winkeyEsmSP = !window.winkeyEsmSP;
			localStorage.setItem('winkey_esm_sp', window.winkeyEsmSP ? '1' : '0');
			updateEsmModeLabel();
		});
	}

	document.addEventListener('keydown', function(event) {

		if (event.key === 'F1') {
			event.preventDefault();
			morsekey_func1();
		}

		if (event.key === 'F2') {
			event.preventDefault();
			morsekey_func2();
		}

		if (event.key === 'F3') {
			event.preventDefault();
			morsekey_func3();
		}

		if (event.key === 'F4') {
			event.preventDefault();
			morsekey_func4();
		}

		if (event.key === 'F5') {
			event.preventDefault();
			morsekey_func5();
		}

		if (event.key === 'F6') {
			event.preventDefault();
			morsekey_func6();
		}

		if (event.key === 'F7') {
			event.preventDefault();
			morsekey_func7();
		}

		if (event.key === 'F8') {
			event.preventDefault();
			morsekey_func8();
		}

		if (event.key === 'F9') {
			event.preventDefault();
			morsekey_func9();
		}

		if (event.key === 'F10') {
			event.preventDefault();
			morsekey_func10();
		}
	});

	let sendText = document.getElementById("sendText");
	let sendButton = document.getElementById("sendButton");
	let receiveText = document.getElementById("receiveText");
	let connectButton = document.getElementById("connectButton");
	let statusBar = document.getElementById("statusBar");

	//Couple the elements to the Events
	connectButton.addEventListener("click", clickConnect);
	sendButton.addEventListener("click", clickSend);

	// Manual send field: force uppercase and send on Enter
	if (sendText) {
		sendText.addEventListener("input", () => {
			sendText.value = sendText.value.toUpperCase();
		});
		sendText.addEventListener("keydown", (event) => {
			if (event.key === "Enter") {
				event.preventDefault();
				clickSend();
			}
		});
	}

	//When the connectButton is pressed
	async function clickConnect() {
		if (port) {
			//if already connected, disconnect
			disconnect();
			$('#winkey_buttons').hide();
		} else {
			//otherwise connect
			await connect();
			$('#winkey_buttons').show();
		}
	}

	//Define outputstream, inputstream and port so they can be used throughout the sketch
	var outputStream, inputStream, port;
	navigator.serial.addEventListener('connect', e => {
		statusBar.innerText = `Connected to ${e.port}`;
		connectButton.innerText = "Disconnect"
	});

	navigator.serial.addEventListener('disconnect', e => {
		statusBar.innerText = `Disconnected`;
		connectButton.innerText = "Connect"
	});

	let debug              = 0;
	let speed              = 24;
	let minSpeed           = 20;
	let maxSpeed           = 40;

	//Connect to the serial
	async function connect() {

		//Optional filter to only see relevant boards
		const filter = {
			usbVendorId: 0x2341 // Arduino SA
		};

		//Try to connect to the Serial port
		try {
			port = await navigator.serial.requestPort(/*{ filters: [filter] }*/);
			// Continue connecting to |port|.

			// - Wait for the port to open.
			await port.open({ baudRate: 1200 });
			await port.setSignals({ dataTerminalReady: true });

			statusBar.innerText = "Connected";
			connectButton.innerText = "Disconnect"

			let decoder = new TextDecoderStream();
			inputDone = port.readable.pipeTo(decoder.writable);
			inputStream = decoder.readable;

			// Keyer init
			sendHexToSerial("00 02");
			await delay(300); // Wait for 300ms
			sendHexToSerial("02 00");
			await delay(300); // Wait for 300ms
			// Init keyer with the speed currently shown (restored from localStorage)
			let initSpeed = parseInt($('#winkeycwspeed').val(), 10) || 20;
			sendHexToSerial(`02 ${initSpeed.toString(16).padStart(2, '0')}`);

			$('#winkey_buttons').show();

			reader = inputStream.getReader();
			readLoop();
		} catch (e) {
			//If the pipeTo error appears; clarify the problem by giving suggestions.
			if (e == "TypeError: Cannot read property 'pipeTo' of undefined") {
				e += "\n Use Google Chrome and enable-experimental-web-platform-features"
			}
			connectButton.innerText = "Connect"
			statusBar.innerText = e;
		}
	}

	window.stop_cw_sending = function() {
		sendHexToSerial("0A");
		$("#send_carrier").attr("hidden", false);
		$("#stop_carrier").attr("hidden", true);
	}

	window.send_carrier = function() {
		sendHexToSerial("0B 01");
		$("#send_carrier").attr("hidden", true);
		$("#stop_carrier").attr("hidden", false);
	}

	window.stop_carrier = function() {
		sendHexToSerial("0B 00");
		$("#send_carrier").attr("hidden", false);
		$("#stop_carrier").attr("hidden", true);
	}

	function delay(ms) {
		return new Promise(resolve => setTimeout(resolve, ms));
	}

	// Helper function to convert a hex string to a Uint8Array
	function hexStringToUint8Array(hexString) {
		// Remove any spaces or non-hex characters
		hexString = hexString.replace(/[^0-9a-f]/gi, '');

		// Ensure the string has an even length
		if (hexString.length % 2 !== 0) {
			console.warn('Hex string has an odd length, padding with a leading zero.');
			hexString = '0' + hexString;
		}

		const byteArray = new Uint8Array(hexString.length / 2);

		for (let i = 0; i < hexString.length; i += 2) {
			byteArray[i / 2] = parseInt(hexString.substr(i, 2), 16);
		}

		return byteArray;
	}

	async function sendHexToSerial(hexString) {
		if (port && port.writable) {
			// Convert the hex string to a Uint8Array
			const byteArray = hexStringToUint8Array(hexString);

			// Create a writer from the writable stream
			const writer = port.writable.getWriter();

			try {
				// Write the byte array to the serial port
				await writer.write(byteArray);
			} catch (error) {
				console.error('Error writing to serial port:', error);
			} finally {
				// Release the lock on the writer
				writer.releaseLock();
			}
		} else {
			console.error('Port is not available or writable.');
		}
	}

	//Write to the Serial port
	async function writeToStream(line) {
		const outputStream = port.writable.getWriter();

		// Convert the text to a Uint8Array
		const encoder = new TextEncoder();
		const text = line.toUpperCase();
		const buffer = encoder.encode(text);

		// Show what is being sent in the TX status bar, highlighting each character
		// in sync with the configured CW speed
		winkeyShowTx(text, parseInt($('#winkeycwspeed').val(), 10) || 20);

		// Write the Uint8Array to the serial port
		await outputStream.write(buffer);

		// Release the stream lock
		outputStream.releaseLock();
	}

	//Disconnect from the Serial port
	async function disconnect() {
		sendHexToSerial("00 03");

		if (reader) {
			await reader.cancel();
			await inputDone.catch(() => { });
			reader = null;
			inputDone = null;
		}
		if (outputStream) {
			await outputStream.getWriter().close();
			await outputDone;
			outputStream = null;
			outputDone = null;
		}
		statusBar.innerText = "Disconnected";
		connectButton.innerText = "Connect"
		//Close the port.
		await port.close();
		port = null;
	}

	//When the send button is pressed
	function clickSend() {
		writeToStream(sendText.value.replaceAll('Ø', '0')).then(function() {
			// writeToStream("\r");
			//and clear the input field, so it's clear it has been sent
			$('#sendText').val('');
		});

	}

	window.morsekey_func1 = function() {
		writeToStream(UpdateMacros(function1Macro));
		//and clear the input field, so it's clear it has been sent
		sendText.value = "";
	}

	window.morsekey_func2 = function() {
		writeToStream(UpdateMacros(function2Macro));
		sendText.value = "";
	}

	window.morsekey_func3 = function() {
		writeToStream(UpdateMacros(function3Macro));
		sendText.value = "";
	}

	window.morsekey_func4 = function() {
		writeToStream(UpdateMacros(function4Macro));
		sendText.value = "";
	}

	window.morsekey_func5 = function() {
		writeToStream(UpdateMacros(function5Macro));
		sendText.value = "";
	}

	window.morsekey_func6 = function() {
		writeToStream(UpdateMacros(function6Macro));
		sendText.value = "";
	}

	window.morsekey_func7 = function() {
		writeToStream(UpdateMacros(function7Macro));
		sendText.value = "";
	}

	window.morsekey_func8 = function() {
		writeToStream(UpdateMacros(function8Macro));
		sendText.value = "";
	}

	window.morsekey_func9 = function() {
		writeToStream(UpdateMacros(function9Macro));
		sendText.value = "";
	}

	window.morsekey_func10 = function() {
		writeToStream(UpdateMacros(function10Macro));
		sendText.value = "";
	}

	// --- ESM (Enter Sends Message) ---

	function macroBySlot(slot) {
		const macros = [null, function1Macro, function2Macro, function3Macro, function4Macro,
			function5Macro, function6Macro, function7Macro, function8Macro, function9Macro, function10Macro];
		return macros[slot] || '';
	}

	function esmSend(slot) {
		writeToStream(UpdateMacros(macroBySlot(slot)));
		sendText.value = "";
	}

	// Exchange is complete when every visible received exchange field holds a value
	function esmExchangeComplete() {
		const fields = [window.winkeyExchangeRField, window.winkeySerialRField, window.winkeyGridRField];
		return fields.every(id => {
			const el = id ? document.getElementById(id) : null;
			if (!el || el.offsetParent === null) return true; // hidden/absent → not required
			return el.value.trim() !== '';
		});
	}

	function esmLog() {
		if (typeof window.logQso === 'function') window.logQso();
	}

	// Drives the QSO via the Enter key. Returns true when ESM handled the keypress
	// (so the caller skips its default logging), false when ESM is inactive.
	window.winkeyEsmEnter = function(event) {
		if (!window.winkeyEsmEnabled || !port) return false;

		// Escape hatch: Alt+Enter (and Ctrl+Alt+Enter) log without sending anything
		if (event && event.altKey) {
			esmLog();
			return true;
		}

		const map = window.winkeyEsmMap || { cq: 1, qrz: 4, exchange: 2, tu: 3, sp: 4, spExch: 2 };
		const sp = !!window.winkeyEsmSP; // Search & Pounce mode
		const callsignEl = document.getElementById(window.winkeyCallsignField || 'callsign');
		const callsign = (callsignEl?.value || '').trim();

		if (callsign === '') {
			if (!sp) esmSend(map.cq); // Run: call CQ; S&P: nothing to do
		} else if (callsign.includes('?')) {
			esmSend(map.qrz);
		} else if (!esmExchangeComplete()) {
			esmSend(sp ? map.sp : map.exchange); // S&P: send own call; Run: send report
		} else {
			// Send first: logQso() clears the form, which would blank [CALL] etc.
			esmSend(sp ? map.spExch : map.tu); // Run: send TU; S&P: send your closing exchange
			esmLog();
		}
		return true;
	};



	//Read the incoming data
	async function readLoop() {
		while (true) {
			const { value, done } = await reader.read();
			if (done === true){
				break;
			}

			//When recieved something add it to the big textarea
			if (receiveText) {
				receiveText.value += value;
				receiveText.scrollTop = receiveText.scrollHeight;
			}
		}
	}

	function UpdateMacros(macrotext) {
		let callsignId  = window.winkeyCallsignField  || 'callsign';
		let rstId       = window.winkeyRstField       || 'rst_sent';
		let rstRId      = window.winkeyRstRField      || 'rst_rcvd';
		let serialId    = window.winkeySerialField    || null;
		let serialRId   = window.winkeySerialRField   || null;
		let exchangeId  = window.winkeyExchangeField  || null;
		let exchangeRId = window.winkeyExchangeRField || null;
		let gridId      = window.winkeyGridField      || null;
		let gridRId     = window.winkeyGridRField     || null;

		const val = id => id ? (document.getElementById(id)?.value || '') : '';

		let CALL      = val(callsignId).toUpperCase().replaceAll('Ø', '0');
		let RSTS      = val(rstId);
		let RSTR      = val(rstRId);
		let SERIAL    = val(serialId);
		let SERIALR   = val(serialRId);
		let EXCHANGE  = val(exchangeId);
		let EXCHANGER = val(exchangeRId);
		let GRID      = val(gridId);
		let GRIDR     = val(gridRId);

		my_call = my_call.replaceAll('Ø', '0');
		let newString = macrotext.replace(/\[MYCALL\]/g,    station_callsign);
		newString = newString.replace(/\[CALL\]/g,      CALL);
		newString = newString.replace(/\[RSTS\]/g,      RSTS);
		newString = newString.replace(/\[RST_S\]/g,     RSTS);
		newString = newString.replace(/\[RST_R\]/g,     RSTR);
		newString = newString.replace(/\[SERIAL\]/g,    SERIAL);
		newString = newString.replace(/\[SERIAL_S\]/g,  SERIAL);
		newString = newString.replace(/\[SERIAL_R\]/g,  SERIALR);
		newString = newString.replace(/\[EXCHANGE\]/g,   EXCHANGE);
		newString = newString.replace(/\[EXCHANGE_S\]/g, EXCHANGE);
		newString = newString.replace(/\[EXCHANGE_R\]/g, EXCHANGER);
		newString = newString.replace(/\[GRID\]/g,      GRID);
		newString = newString.replace(/\[GRID_S\]/g,    GRID);
		newString = newString.replace(/\[GRID_R\]/g,    GRIDR);
		return newString;
	}

};

// Auto-init on normal QSO pages (not on contest logger)
// Check is deferred to DOM-ready so ContestLoggerConfig is already defined by then
$(document).ready(function() {
	if (typeof window.ContestLoggerConfig === 'undefined') {
		window.initWinkeyer();
	}
});

function winkey_macro_save() {
	$.ajax({
		url: base_url + 'index.php/qso/cwmacrosave',
		type: 'post',
		data: {
			function1_name: $('#function1_name').val(),
			function1_macro: $('#function1_macro').val(),
			function2_name: $('#function2_name').val(),
			function2_macro: $('#function2_macro').val(),
			function3_name: $('#function3_name').val(),
			function3_macro: $('#function3_macro').val(),
			function4_name: $('#function4_name').val(),
			function4_macro: $('#function4_macro').val(),
			function5_name: $('#function5_name').val(),
			function5_macro: $('#function5_macro').val(),
			function6_name: $('#function6_name').val(),
			function6_macro: $('#function6_macro').val(),
			function7_name: $('#function7_name').val(),
			function7_macro: $('#function7_macro').val(),
			function8_name: $('#function8_name').val(),
			function8_macro: $('#function8_macro').val(),
			function9_name: $('#function9_name').val(),
			function9_macro: $('#function9_macro').val(),
			function10_name: $('#function10_name').val(),
			function10_macro: $('#function10_macro').val(),
			esm_enabled: $('#esm_enabled').is(':checked') ? 1 : 0,
			esm_cq: $('#esm_cq').val(),
			esm_qrz: $('#esm_qrz').val(),
			esm_exchange: $('#esm_exchange').val(),
			esm_tu: $('#esm_tu').val(),
			esm_sp: $('#esm_sp').val(),
			esm_sp_exch: $('#esm_sp_exch').val(),
		},
		success: function (html) {
			winkeyToast('Macros saved');
			getMacros();
		}
	});
}

// Toast that reuses whichever mechanism the page provides: the contest logger's
// WindowManager toast, or the global showToast() from common.js on the normal QSO page.
function winkeyToast(message) {
	if (window.contestApp?.wm?.showToast) {
		window.contestApp.wm.showToast('Winkeyer', message, 'bg-success text-white', 3000);
	} else if (typeof showToast === 'function') {
		showToast('Winkeyer', message, 'bg-success text-white', 3000);
	}
}
