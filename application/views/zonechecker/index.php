<div class="container">
	<br />
    <h5><?= __("Gridsquare Zone identification"); ?></h5>
	<div class="d-flex align-items-center mb-3">
		<label class="me-2" for="de"><?= __("Station Location"); ?></label>
		<select class="form-select form-select-sm w-auto me-2" id="de" name="de">
			<option value="all">All</option>
			<?php foreach ($station_profile->result() as $station) { ?>
				<option value="<?php echo $station->station_id; ?>">
					<?= __("Callsign: ") . " " ?>
					<?php echo str_replace("0", "&Oslash;", strtoupper($station->station_callsign)); ?> (<?php echo $station->station_profile_name; ?>)
				</option>
			<?php } ?>
		</select>
		<label class="me-2" for="zoneType"><?= __("Zone Type"); ?></label>
		<select class="form-select form-select-sm w-auto me-2" id="zoneType" name="zoneType">
			<option value="cq"><?= __("CQ Zone"); ?></option>
			<option value="itu"><?= __("ITU Zone"); ?></option>
		</select>
		<button id="startDxccCheck" class="btn btn-primary btn-sm"><?= __("Start Zone Check"); ?></button>
	</div>
	<div class='result'>
	</div>
</div>

<script>
// Check if jQuery is loaded, if not wait for it
if (typeof $ === 'undefined') {
    // jQuery not yet loaded, add event listener
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof $ === 'undefined') {
            // Wait for jQuery to load
            var checkJQuery = setInterval(function() {
                if (typeof $ !== 'undefined') {
                    clearInterval(checkJQuery);
					$('#startDxccCheck').on('click', function() {
		let de = $('#de').val();
		let zoneType = $('#zoneType').val();
		$('.result').html('<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div> <?= __("Processing...") ?>');
		$.ajax({
			url: site_url + '/zonechecker/doWazCheck',
			type: "POST",
			data: {de: de,
				zoneType: zoneType
			},
			success: function(response) {
				$('.result').html(response);
			},
			error: function(xhr, status, error) {
				$('.result').html('<div class="alert alert-danger" role="alert"><?= __("An error occurred while processing the request.") ?></div>');
			}
		});
	});


                }
            }, 100);
        } else {
            $('#startDxccCheck').on('click', function() {
		let de = $('#de').val();
		let zoneType = $('#zoneType').val();
		$('.result').html('<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div> <?= __("Processing...") ?>');
		$.ajax({
			url: site_url + '/zonechecker/doWazCheck',
			type: "POST",
			data: {de: de,
				zoneType: zoneType
			},
			success: function(response) {
				$('.result').html(response);
			},
			error: function(xhr, status, error) {
				$('.result').html('<div class="alert alert-danger" role="alert"><?= __("An error occurred while processing the request.") ?></div>');
			}
		});
	});
        }
    });
} else {
    // jQuery already loaded
    $(document).ready(function() {
        initMap();
    });
}

</script>
