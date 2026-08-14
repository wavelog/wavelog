
$(document).ready(function () {
    if ($('#station_dxcc').length) {
        $('#station_dxcc').multiselect({
            // template is needed for bs5 support
            templates: {
                button: '<button type="button" style="text-align: left !important;" class="multiselect dropdown-toggle btn btn-secondary w-auto" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false"><span class="multiselect-selected-text"></span></button>',
            },
            enableFiltering: true,
            enableFullValueFiltering: false,
            enableCaseInsensitiveFiltering: true,
            filterPlaceholder: lang_general_word_search,
            widthSynchronizationMode: 'always',
            numberDisplayed: 1,
            inheritClass: true,
            buttonWidth: '100%',
            maxHeight: 300,
        });
        $('.multiselect-container .multiselect-filter', $('#station_dxcc').parent()).css({
            'position': 'sticky', 'top': '0px', 'z-index': 1, 'background-color': 'inherit', 'width': '100%', 'height': '37px'
        });
    }

    const loadRadio = () => wlLoadInto(base_url + 'index.php/dashboard/radio_display_component', '#radio_display');
	loadRadio();

    var pending = null;
	var missed = false;
	function throttleLoadRadio() {
		// Load at most once every 1 seconds. If more pushes arrive during the
		// lockout, refresh once afterwards so no update is lost.
		if (pending) {
			missed = true;
			return;
		}
		loadRadio();
		pending = setTimeout(function () {
			pending = null;
			if (missed) {
				missed = false;
				throttleLoadRadio();
			}
		}, 1000);
	}

	var rw = window.radiosUserWorker;
	if (rw && window.WavelogWorker && WavelogWorker.isAvailable()) {
		setInterval(loadRadio, 60000);
		WavelogWorker.subscribe({
			topic: rw.topic,
			token: rw.token,
			onMessage: function (frame) {
				if (frame.type !== 'push' || !frame.payload || frame.payload.type !== 'radio_updated' || !frame.payload.radio_status) {
					return;
				}
				var cell = $('#radio_display tr[data-radio-id="' + frame.payload.radio_id + '"] .radio-qrg');
				if (!cell.length) {
					throttleLoadRadio();
					return;
				}
				var s = frame.payload.radio_status;
				if (s.prop_mode === 'SAT') {
					cell.text(s.satname || '');
				} else {
					cell.text((s.frequency_formatted || '') + ' (' + (s.mode || '') + ')');
				}
			},
			onReconnect: function () { throttleLoadRadio(); }
		});
	} else {
		setInterval(loadRadio, 5000);
	}
});