function searchAdditionalQsos(filename) {
	$.ajax({
		url: base_url + 'index.php/qsl/searchQsos',
		type: 'post',
		data: {'callsign': $('#callsign').val(), 'filename': filename},
		success: function(html) {
			$('#searchresult').empty();
			$('#searchresult').append(html);
		}
	});
}

function getConfirmations() {
	let selectedQslTypes = $('#confirmationtype').val();
	if (Array.isArray(selectedQslTypes) && selectedQslTypes.length === 0) {
		BootstrapDialog.alert({
			title: 'INFO',
			message: 'You need to select at least one QSL type to do a search!',
			type: BootstrapDialog.TYPE_INFO,
			closable: false,
			draggable: false,
			callback: function (result) {
			}
		});
		return false;
	}

	$('#confirmationbutton').prop("disabled", true).addClass("running");
	$.ajax({
		url: base_url + 'index.php/generic_qsl/searchConfirmations',
		type: 'post',
		data: {'type': $('#confirmationtype').val()},
		success: function(html) {
			$('#searchresult').empty();
			$('#searchresult').append(html);
			$(".confirmationtable").DataTable({
				responsive: false,
				scrollY: window.innerHeight - $('.confirmationform').innerHeight() - 300,
				scrollCollapse: true,
				paging: false,
				scrollX: true,
				ordering: false,
				language: {
					url: getDataTablesLanguageUrl(),
				},
				dom: "Bfrtip",
				buttons: [
					{
						extend: 'csv'
					},
					{
						extend: 'clear',
						text: lang_admin_clear
					}
				]
			});
			$('#confirmationbutton').prop("disabled", false).removeClass("running");
		}
	});
}

$(document).ready(function () {
	if ($('#confirmationtype').length) {
		$('#confirmationtype').multiselect({
			enableFiltering: false,
			enableCaseInsensitiveFiltering: false,
			filterPlaceholder: lang_general_word_search,
			templates: {
				button: '<button type="button" class="multiselect dropdown-toggle btn btn-sm btn-secondary me-2 w-auto" data-bs-toggle="dropdown" aria-expanded="false"><span class="multiselect-selected-text"></span></button>',
			},
			numberDisplayed: 1,
			inheritClass: true,
			includeSelectAllOption: true
		});
	}
});

