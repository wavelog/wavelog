<div class="container">

    <br>

    <h2><?php echo $page_title; ?></h2>

    <div class="card">
        <div class="card-header">
            <?= __("Request a QSL card"); ?>
        </div>
        <div class="card-body">

            <div class="stationinfo">

                <?php
		if ($global_oqrs_text) {
			echo $global_oqrs_text;
			echo '<br /><br />';
		}
        if (($widget_call ?? false) == true) {
            $callsign_value = 'value="'.$callsign.'"';
        } else {
            $callsign_value = '';
        }
		if ($groupedSearch == 'on') {
			echo __("This search will search in all station locations where OQRS is active.").'<br /><br /><form class="d-flex align-items-center" onsubmit="return false;"><label class="my-1 me-2" for="oqrssearch">' . __("Enter your callsign") . ': </label>
			<input class="form-control me-sm-2 w-auto" id="oqrssearch" type="search" name="callsign" placeholder="' . __("Search Callsign") . '" aria-label="Search" '.$callsign_value.' required="required">
			<input hidden class="form-control me-sm-2 w-auto" id="slug" type="search" name="slug" value = '.$slug.'>
			<button onclick="searchOqrsGrouped();" class="btn btn-sm btn-primary" id="stationbuttonsubmit" type="button"><i class="fas fa-search"></i> ' . __("Search") . '</button>
			</form>';
			echo '<div class="searchinfo"></div>';
			?>
                <script>
                // Get the input field
                var input = document.getElementById("oqrssearch");

                // Execute a function when the user presses a key on the keyboard
                input.addEventListener("keypress", function(event) {
                    // If the user presses the "Enter" key on the keyboard
                    if (event.key === "Enter") {
                        // Cancel the default action, if needed
                        event.preventDefault();
                        // Trigger the button element with a click
                        document.getElementById("stationbuttonsubmit").click();
                    }
                });
                </script>
				</div>
                <?php
			} else {
		  if ($stations) { ?>

                <form class="d-flex align-items-center" enctype="multipart/form-data">
                    <label class="my-1 me-2" for="station"><?= __("Select station"); ?>: </label>
                    <select id="station" class="form-select w-auto my-1 me-sm-2" name="station">
                        <?php foreach($stations->result() as $station) {
									echo '<option value="' . $station->station_id . '">' . $station->station_profile_name . ' - ' . $station->station_callsign . '</option>'."\n";
								} ?>
                    </select>
                    <button id="button1id" type="button" onclick="loadStationInfo();" name="button1id" class="btn btn-sm btn-primary"> <?= __("Proceed"); ?></button>
                </form>
            </div>
			<div class="resulttable"></div>

            <div class="searchinfo"></div>
            <?php
		}

		else {
			echo __("No stations found that are using Wavelog OQRS.");
		}
	}
		?>

        </div>
    </div>
</div>
