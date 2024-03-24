function reassign(call, target_profile_id) {
	let qsoids = [];
	let elements = document.getElementsByName("cBox[]");
	elements.forEach((item) => {
		if (item.checked) {
			qsoids.push(item.value);
		}
	});
	$.ajax({
		url: base_url + "index.php/debug/reassign",
		type: "post",
		data: { call: call, station_id: target_profile_id, qsoids: qsoids },
		success: function (resu) {
			if (resu.status) {
				location.reload();
			}
		},
	});
}

function toggleAll(source) {
	if (source.checked) {
		let elements = document.getElementsByName("cBox[]");
		elements.forEach((item) => {
			item.checked = true;
		});
		source.checked = true;
	}
	if (!source.checked) {
		let elements = document.getElementsByName("cBox[]");
		elements.forEach((item) => {
			item.checked = false;
		});
		source.checked = false;
	}
}

function updateCallsign(item) {
	let text = item.options[item.selectedIndex].text;
	let call = text.substr(
		text.lastIndexOf("(") + 1,
		text.lastIndexOf(")") - text.lastIndexOf("(") - 1
	);
	document.getElementById("station_call").innerHTML = call;
}

function version_check(callback) {
    var latest_tag; 
	$('#version_check_button').prop("disabled", true).addClass("running");
    $.ajax({
        url: base_url + 'index.php/debug/wavelog_version',
        success: function(database_version) {
            $.ajax({
                url: 'https://api.github.com/repos/wavelog/wavelog/tags',
                type: 'GET',
                success: function(tags) {
                    // Extract the latest tag
                    latest_tag = tags[0].name;

                    // Compare database version with the latest tag
                    var is_latest_version = (database_version === latest_tag);

                    // Call the callback function with the result
                    callback(is_latest_version, latest_tag); 
					$('#version_check_button').prop("disabled", false).removeClass("running");
                },
                error: function(xhr, status, error) {
                    console.error('ERROR fetching Git tags:', error);
                    callback(null, null);
					$('#version_check_button').prop("disabled", false).removeClass("running");
                }
            });
        },
        error: function(xhr, status, error) {
            console.error('ERROR fetching database version:', error);
            callback(null, null);
        }
    });
}

function update_version_check() {
	version_check(function(is_latest_version, latest_tag) {
		$('#version_check_result').removeClass('alert alert-success alert-warning alert-danger').text('');
		$('#version_update_button').hide();
		var timestamp = Date.now();

        if (is_latest_version !== null) {
            if (is_latest_version) {
				$('#version_check_result').addClass('alert alert-success');
                $('#version_check_result').text("Wavelog is up to date!");
            } else {
				$('#version_check_result').addClass('alert alert-warning');
                $('#version_check_result').text("There is a newer version available: " + latest_tag);
				$('#version_update_button').show();
            }
        } else {
			$('#version_check_result').addClass('alert alert-danger');
            $('#version_check_result').text("Failed to determine the latest version.");
        }

		$('#last_version_check').text("Last version check: " + new Date(timestamp).toUTCString());
    });
}

$(document).ready(function () {
    update_version_check();
});

