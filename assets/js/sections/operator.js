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

function saveOperator() {
	var operatorInput = $("#operator_callsign");
	var operatorCallsign = operatorInput.val();

	if (operatorCallsign != "" && operatorCallsign != sc_account_call) {
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
				showToast("error", __("Error saving operator callsign. Please try again."));
			}
		});
	} else {
		operatorInput.addClass("is-invalid");
	}
}
