function searchpasses() {
	$(".ld-ext-right-plot").addClass('running');
    $(".ld-ext-right-plot").prop('disabled', true);
    $('#searchpass').prop("disabled", true);
	if ($('#addskedpartner').is(':hidden')) {
		loadPasses();
	} else {
		let skedgrid = $("#skedgrid").val();
		if (skedgrid == '') {
			$(".ld-ext-right-plot").removeClass('running');
            $(".ld-ext-right-plot").prop('disabled', false);
            $('#searchpass').prop("disabled", false);
			return;
		}
		loadSkedPasses();
	}
	return;

}

function loadPasses() {
	$.ajax({
        url: base_url + 'index.php/satellite/searchPasses',
        type: 'post',
        data: {'sat': $("#satlist").val(),
            'yourgrid': $("#yourgrid").val(),
            'minelevation': $("#minelevation").val(),
            'minazimuth': $("#minazimuth").val(),
            'maxazimuth': $("#maxazimuth").val(),
            'date': $("#date").val(),
            'mintime': $("#mintime").val(),
            'maxtime': $("#maxtime").val(),
        },
        success: function (html) {
            $("#resultpasses").html(html);
			$(".ld-ext-right-plot").removeClass('running');
            $(".ld-ext-right-plot").prop('disabled', false);
            $('#searchpass').prop("disabled", false);
			$('.satelliteinfo').click(function (event) {
				getSatelliteInfo(this);
			});
        },
        error: function(e) {
            modalloading=false;
        }
    });
}

function getSatelliteInfo(element) {
	var satname = $(element).closest('td').contents().first().text().trim();
	$.ajax({
        url: base_url + 'index.php/satellite/getSatelliteInfo',
        type: 'post',
        data: {'sat': satname,
        },
        success: function (html) {
			BootstrapDialog.show({
				title: 'Satellite information',
				size: BootstrapDialog.SIZE_WIDE,
				cssClass: 'information-dialog',
				nl2br: false,
				message: html,
				buttons: [{
					label: lang_admin_close,
					action: function (dialogItself) {
						dialogItself.close();
					}
				}]
			});
        },
        error: function(e) {

        }
    });
}

function loadSkedPasses() {
	$.ajax({
        url: base_url + 'index.php/satellite/searchSkedPasses',
        type: 'post',
        data: {'sat': $("#satlist").val(),
            'yourgrid': $("#yourgrid").val(),
            'minelevation': $("#minelevation").val(),
            'minazimuth': $("#minazimuth").val(),
            'maxazimuth': $("#maxazimuth").val(),
            'date': $("#date").val(),
            'mintime': $("#mintime").val(),
            'maxtime': $("#maxtime").val(),
			'skedgrid': $("#skedgrid").val(),
			'minskedelevation': $("#minskedelevation").val(),
        },
        success: function (html) {
            $("#resultpasses").html(html);
			$(".ld-ext-right-plot").removeClass('running');
            $(".ld-ext-right-plot").prop('disabled', false);
            $('#searchpass').prop("disabled", false);
        },
        error: function(e) {
            modalloading=false;
        }
    });
}

function addskedpartner() {
	if ($('#addskedpartner').is(':hidden')) {
		$('#addskedpartner').show();
		$('#satlist option[value=""]').remove();
	} else {
		$('#addskedpartner').hide();
		$('#satlist').prepend('<option value="">All</option>');
	}
}

$('#satlist').change(function () {
    if ($('#satlist').val() === "") {
		$('#addsked').prop('disabled', true);
    } else {
		$('#addsked').prop('disabled', false);
    }
});
