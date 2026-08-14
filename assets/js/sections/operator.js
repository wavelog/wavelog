$(document).ready(function () {
	$("#operator_callsign").on("keydown", function (event) {
		if (event.which == 13) {
			saveOperator();
		}
	});
});

function displayOperatorDialog() {
	$.ajax({
		url: base_url + "index.php/Operator/displayOperatorDialog",
		type: "GET",
		dataType: "html",
		success: function (data) {
			$("#operatorModal").remove();
			$("body").append(data);

			var operatorModal = new bootstrap.Modal($("#operatorModal"));
			operatorModal.show();
		},
		error: function () {
			console.log("Error loading the PHP view for the operator call dialog.");
		},
	});
}

function closeOperatorDialog() {
	var operatorModal = bootstrap.Modal.getInstance($("#operatorModal"));
	if (operatorModal) {
		operatorModal.hide();
		$("#operatorModal").remove();
	}
}

// Kept in sync with the server-side check in Operator::isValidCallsign().
function isValidOperatorCallsign(callsign) {
	if (!callsign || callsign.length < 3) return false;
	if (!/^[A-Z0-9]+$/.test(callsign)) return false;	// letters and digits only
	if (!/[A-Z]/.test(callsign)) return false;		// at least one letter
	if (!/[0-9]/.test(callsign)) return false;		// at least one digit
	// A bare 3-char string ending in a digit is a prefix, not a call (e.g. "ZL3")
	if (callsign.length === 3 && !/[A-Z]$/.test(callsign)) return false;
	return true;
}

function saveOperator() {
	var operatorInput = $("#operator_callsign");
	var operatorCallsign = operatorInput.val().trim().toUpperCase();

	if (operatorCallsign != sc_account_call && isValidOperatorCallsign(operatorCallsign)) {
		$.ajax({
			url: base_url + "index.php/operator/saveOperator",
			method: "POST",
			type: "post",
			data: {
				operator_callsign: operatorCallsign,
			},
			// Reload only after the session write succeeded — reloading earlier races
			// the POST and can render the previous operator.
			success: function () {
				closeOperatorDialog();
				window.location.reload();
			},
			error: function () {
				showToast("error", lang_operator_modal_save_error, 'bg-danger text-white', 5000);
			}
		});
	} else {
		operatorInput.addClass("is-invalid");
	}
}
