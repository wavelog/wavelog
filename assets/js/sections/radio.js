$(document).ready(function () {
    const loadStatus = () => $.get(base_url + 'index.php/radio/status/', function (result) {
        $('.status').html(result);
        $('#radioResultsLoading').hide();
    });
    loadStatus();
    // Exposed so the global set/release default handlers can refresh the table.
    window.reloadRadioStatus = loadStatus;

    // Reload the whole status table at most once per second — only used to add a
    // radio row that isn't shown yet and on reconnect; live freq/mode changes are
    // patched into the existing cells (see below), not re-fetched.
    var pending = null, missed = false;
    function throttleLoadStatus() {
        if (pending) { missed = true; return; }
        loadStatus();
        pending = setTimeout(function () {
            pending = null;
            if (missed) { missed = false; throttleLoadStatus(); }
        }, 1000);
    }

    var rw = window.radiosUserWorker;
    if (rw && window.WavelogWorker && WavelogWorker.isAvailable()) {
        // Slow safety-net reload keeps "last updated", timestamp, new radios and
        // deletions in sync; freq/mode update live from the stream in between.
        setInterval(loadStatus, 60000);
        WavelogWorker.subscribe({
            topic: rw.topic,
            token: rw.token,
            onMessage: function (frame) {
                if (frame.type !== 'push' || !frame.payload || frame.payload.type !== 'radio_updated' || !frame.payload.radio_status) {
                    return;
                }
                var row = $('.status tr[data-radio-id="' + frame.payload.radio_id + '"]');
                if (!row.length) {
                    throttleLoadStatus(); // radio not in the table yet — reload to add it
                    return;
                }
                // Patch freq/mode cells straight from the payload (split-aware),
                // matching what the server would render.
                var s = frame.payload.radio_status;
                var freqText;
                if (!s.frequency) {
                    freqText = '- / -';
                } else if (!s.frequency_rx || s.frequency_rx == s.frequency) {
                    freqText = s.frequency_formatted;
                } else {
                    freqText = s.frequency_rx_formatted + ' / ' + s.frequency_formatted;
                }
                row.find('.radio-freq').text(freqText);

                var modeText;
                if (!s.mode) {
                    modeText = 'N/A';
                } else if (!s.mode_rx) {
                    modeText = s.mode;
                } else {
                    modeText = s.mode_rx + ' / ' + s.mode;
                }
                row.find('.radio-mode').text(modeText);

                // Move the "last updated" marker to the radio that just updated.
                $('.status .radio-lastupdated').empty();
                row.find('.radio-lastupdated').append($('<i>').text(lang_radio_last_updated));
            },
            onReconnect: function () { throttleLoadStatus(); }
        });
    } else {
        // Fallback: original fixed 2s poll when the worker is unavailable.
        setInterval(loadStatus, 2000);
    }
});

$(document).on('click', '.editCatSettings', async function (e) {	// Dynamic binding, since element doesn't exists when loading this JS
	editCatUrlDialog(e);
});

function editCatUrlDialog(e) {
	$.ajax({
		url: base_url + 'index.php/radio/editCatUrl',
		type: 'post',
		data: {
			id: e.currentTarget.id.replace('edit_cat_settings_', '')
		},
		success: function (data) {
			BootstrapDialog.show({
				title: lang_edit_cat_settings,
				size: BootstrapDialog.SIZE_NORMAL,
				cssClass: 'options',
				id: "CatUrlModal",
				nl2br: false,
				message: data,
				onshown: function(dialog) {
				},
				buttons: [{
					label: lang_admin_save,
					cssClass: 'btn-primary btn-sm saveContainerName',
					action: function (dialogItself) {
						saveCatUrl();
						dialogItself.close();
					}
				},
					{
						label: lang_admin_close,
						cssClass: 'btn-sm',
						id: 'closeButton',
						action: function (dialogItself) {
							dialogItself.close();
						}
					}],
			});
		},
		error: function (data) {

		},
	});
	return false;
}

function saveCatUrl() {
	$.ajax({
		url: base_url + 'index.php/radio/saveCatUrl',
		type: 'post',
		data: {
			id: $('#catid').val(),
			caturl: $('#CatUrlInput').val()
		},
		error: function (data) {

		},
	});
}

function set_default_radio(radio_id) {
    $('#default_radio_btn_' + radio_id).addClass('running').prop('disable', true);
    $('#default_radio_btn_' + radio_id).removeClass('btn-outline-primary').addClass('btn-primary');
    $.ajax({
        type: 'POST',
        url: base_url + 'index.php/radio/set_default_radio',
        data: {
            radio_id: radio_id
        },
        // Reload so all default/release buttons reflect the new default.
        success: function () { if (window.reloadRadioStatus) { window.reloadRadioStatus(); } }
    });
}

function release_default_radio(radio_id) {
    $('#default_radio_btn_' + radio_id).addClass('running').prop('disable', true);
    $.ajax({
        type: 'POST',
        url: base_url + 'index.php/radio/release_default_radio',
        data: {
            radio_id: radio_id
        },
        success: function () { if (window.reloadRadioStatus) { window.reloadRadioStatus(); } }
    });
}
