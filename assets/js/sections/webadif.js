$(document).ready(function(){
	$('#markWebAdifAsExported').click(function(e){
		let form = $(this).closest('form');
		let station = form.find('select[name=station_profile]');
		if (station.val() == 0) {
			station.addClass('is-invalid');
		}else{
			form.submit();
		}
	})
});

function ResetWebADIFRejected(station_id) {
	$(".ld-ext-right-rejected-"+station_id).addClass('running');
	$(".ld-ext-right-rejected-"+station_id).prop('disabled', true);

	$.ajax({
		url: base_url + 'index.php/webadif/reset_rejected',
		type: 'post',
		data: {'station_id': station_id},
		success: function (data) {
			$(".ld-ext-right-rejected-"+station_id).removeClass('running');
			$(".ld-ext-right-rejected-"+station_id).prop('disabled', false);
			if (data.status == 'OK') {
				$("#webadif-rejected-"+station_id).remove();
				showToast(lang_general_word_success, data.message, 'bg-success text-white', 5000);
			}
		}
	});
}

function ExportWebADIF(station_id) {
	if ($(".alert").length > 0) {
		$(".alert").remove();
	}
	if ($(".errormessages").length > 0) {
		$(".errormessages").remove();
	}
	$(".ld-ext-right-"+station_id).addClass('running');
	$(".ld-ext-right-"+station_id).prop('disabled', true);

	$.ajax({
		url: base_url + 'index.php/webadif/upload_station',
		type: 'post',
		data: {'station_id': station_id},
		success: function (data) {
			$(".ld-ext-right-"+station_id).removeClass('running');
			$(".ld-ext-right-"+station_id).prop('disabled', false);
			if (data.status == 'OK') {
				$.each(data.info, function(index, value){
					$('#notcount'+value.station_id).html(value.notcount);
					$('#totcount'+value.station_id).html(value.totcount);
				});
				showToast(lang_general_word_success, data.infomessage, 'bg-success text-white', 5000);
			}
			else {
				showToast(lang_general_word_error, data.info, 'bg-danger text-white', 0, false);
			}

			if (data.rejected_html !== undefined && $("#webadif-rejected-wrap").length) {
				$("#webadif-rejected-wrap").html(data.rejected_html);
			}

			if (data.errormessages.length > 0) {
				var $errorcard = $(
					'<div class="errormessages">\n' +
					'    <div class="card mt-2">\n' +
					'        <div class="card-header bg-danger">\n' +
					'            Error Message\n' +
					'        </div>\n' +
					'        <div class="card-body">\n' +
					'            <div class="errors"></div>\n' +
					'        </div>\n' +
					'    </div>\n' +
					'</div>'
				);
				$("#export").append($errorcard);
				$.each(data.errormessages, function (index, value) {
					$errorcard.find(".errors").append($('<li>').text(value));
				});
			}
		}
	});
}
