$('#qrg_unit').on('click', function () {
	if ($(this).html() == 'Hz') {
		$(this).html('kHz');
		$("#freq_calculated").val($("#frequency").val() / 1000);
		localStorage.setItem('qrgunit_' + $('#band').val(), 'kHz');
	} else if ($(this).html() == 'kHz') {
		$(this).html('MHz');
		$("#freq_calculated").val($("#frequency").val() / 1000000);
		localStorage.setItem('qrgunit_' + $('#band').val(), 'MHz');
	} else if ($(this).html() == 'MHz') {
		$(this).html('GHz');
		$("#freq_calculated").val($("#frequency").val() / 1000000000);
		localStorage.setItem('qrgunit_' + $('#band').val(), 'GHz');
	} else if ($(this).html() == 'GHz') {
		$(this).html('Hz');
		$("#freq_calculated").val($("#frequency").val());
		localStorage.setItem('qrgunit_' + $('#band').val(), 'Hz');
	}
});

function qrg_inputtype() {
	// on small screens we change the input type of the frequency and frequency_rx fields to number to show the numeric keyboard
	if (window.innerWidth < 768) {
		$('#freq_calculated').attr('type', 'number').attr('step', '0.001').attr('inputmode', 'decimal').attr('lang', 'en');
		$('#frequency_rx').attr('type', 'number').attr('step', '0.001').attr('inputmode', 'decimal').attr('lang', 'en');
	}
}

async function set_qrg() {

	let frequency = $('#frequency').val();
	let band = $('#band').val();

	// check if there are qrgunits in the localStorage
	if (!localStorage.getItem('qrgunit_' + band)) {
		// console.log('fetching qrg units');
		await $.getJSON(base_url + 'index.php/user_options/get_qrg_units', async function (result) {
			$.each(result, function(key, value) {
				localStorage.setItem('qrgunit_' + key, value);
			});
		});
	}

	let qrgunit = localStorage.getItem('qrgunit_' + band);

	if (qrgunit != null) {
		$('#qrg_unit').html(localStorage.getItem('qrgunit_' + band));
	} else {
		$('#qrg_unit').html('...');
	}

	if (qrgunit == 'Hz') {
		$("#freq_calculated").val(frequency);
	} else if (qrgunit == 'kHz') {
		$("#freq_calculated").val(frequency / 1000);
	} else if (qrgunit == 'MHz') {
		$("#freq_calculated").val(frequency / 1000000);
	} else if (qrgunit == 'GHz') {
		$("#freq_calculated").val(frequency / 1000000000);
	}
}

async function set_new_qrg() {
	let new_qrg = $('#freq_calculated').val().trim();
	let parsed_qrg = parseFloat(new_qrg);
	let unit = $('#qrg_unit').html();

	// check if the input contains a unit and parse the qrg
	if (/^\d+(\.\d+)?\s*(hz|h)$/i.test(new_qrg)) {
		unit = 'Hz';
		parsed_qrg = parseFloat(new_qrg);
	} else if (/^\d+(\.\d+)?\s*(khz|k)$/i.test(new_qrg)) {
		unit = 'kHz';
		parsed_qrg = parseFloat(new_qrg);
	} else if (/^\d+(\.\d+)?\s*(mhz|m)$/i.test(new_qrg)) {
		unit = 'MHz';
		parsed_qrg = parseFloat(new_qrg);
	} else if (/^\d+(\.\d+)?\s*(ghz|g)$/i.test(new_qrg)) {
		unit = 'GHz';
		parsed_qrg = parseFloat(new_qrg);
	}

	// update the unit if there was any change
	$('#qrg_unit').html(unit);

	// calculate the other stuff
	let qrg_hz;
	switch (unit) {
		case 'Hz':
			qrg_hz = parsed_qrg;
			localStorage.setItem('qrgunit_' + $('#band').val(), 'Hz');
			break;
		case 'kHz':
			qrg_hz = parsed_qrg * 1000;
			localStorage.setItem('qrgunit_' + $('#band').val(), 'kHz');
			break;
		case 'MHz':
			qrg_hz = parsed_qrg * 1000000;
			localStorage.setItem('qrgunit_' + $('#band').val(), 'MHz');
			break;
		case 'GHz':
			qrg_hz = parsed_qrg * 1000000000;
			localStorage.setItem('qrgunit_' + $('#band').val(), 'GHz');
			break;
		default:
			qrg_hz = 0;
			console.error('Invalid unit');
	}

	$('#frequency').val(qrg_hz);
	$('#freq_calculated').val(parsed_qrg);
	$('#band').val(frequencyToBand(qrg_hz));

}

$('#frequency').on('change', function () {
	// console.log('frequency changed');
	set_qrg();
});

$('#freq_calculated').on('input', function () {
	if (window.innerWidth > 768) {
    	$(this).val($(this).val().replace(',', '.'));
	}
});

$('#freq_calculated').on('change', function () {
	// console.log('freq_calculated changed');
	set_new_qrg();
});

$('#freq_calculated').on('keydown', function (e) {
	if (e.which === 13) {
		e.preventDefault();
		if ($('#callsign').val() != '') {
			set_new_qrg().then(() => {
				$("#qso_input").trigger('submit');
			});
		} else {
			set_new_qrg();
		}
    }
});