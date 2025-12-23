	$('#startDxccCheck').on('click', function() {
		let de = $('#de').val();
		let compare = $('#compareDxccClass').prop('checked');
		$('.result').html('<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div> <?= __("Processing...") ?>');
		$.ajax({
			url: site_url + '/calltester/doDxccCheck',
			type: "POST",
			data: {de: de,
				compare: compare
			},
			success: function(response) {
				$('.result').html(response);
			},
			error: function(xhr, status, error) {
				$('.result').html('<div class="alert alert-danger" role="alert"><?= __("An error occurred while processing the request.") ?></div>');
			}
		});
	});
