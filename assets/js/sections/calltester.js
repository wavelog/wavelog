	$('#startDxccCheck').on('click', function() {
		let de = $('#de').val();
		let compare = $('#compareDxccClass').prop('checked');
		$('#startDxccCheck').addClass('running').prop('disabled', true);
		$('.result').html('');
		$.ajax({
			url: site_url + '/calltester/doDxccCheck',
			type: "POST",
			data: {
				de: de,
				compare: compare
			},
			success: function(response) {
				$('.result').html(response);
				$('.result [data-bs-toggle="tooltip"]').tooltip();
				$('#startDxccCheck').removeClass('running').prop('disabled', false);
			},
			error: function(xhr, status, error) {
				$('.result').html('<div class="alert alert-danger" role="alert"><?= __("An error occurred while processing the request.") ?></div>');
				$('#startDxccCheck').removeClass('running').prop('disabled', false);
			}
		});
	});

	// Open every QSO with the given callsign in a modal dialog (delegated so it
	// survives re-rendering of the result table)
	$(document).on('click', '.calltester-call-search', function(e) {
		e.preventDefault();
		showCallQsos($(this).attr('data-call'));
	});

	function showCallQsos(call) {
		BootstrapDialog.show({
			title: call,
			cssClass: 'qso-dialog',
			size: BootstrapDialog.SIZE_WIDE,
			nl2br: false,
			message: function(dialog) {
				let $content = $('<div class="text-center p-3"><div class="spinner-border text-primary" role="status"></div></div>');
				$.get(site_url + '/calltester/call_info/' + encodeURIComponent(call), function(html) {
					$content.html(html);
					$content.find('[data-bs-toggle="tooltip"]').tooltip();
				});
				return $content;
			}
		});
	}
