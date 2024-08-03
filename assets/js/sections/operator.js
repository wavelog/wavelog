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
	var operatorFirstname = $("#operator_firstname").val();
	var operatorCallsign = operatorInput.val();

	if (operatorCallsign != "" && operatorCallsign != sc_account_call) {
		$.ajax({
			url: base_url + "index.php/operator/saveOperator",
			method: "POST",
			type: "post",
			data: {
				operator_callsign: operatorCallsign,
				operator_firstname: operatorFirstname
			},
		});
		closeOperatorDialog();
		// console.log("saveOperator executed");
		// console.log("operator:" + operatorCallsign);
	} else {
		operatorInput.addClass("is-invalid");
	}
}
