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
            'altitude': $("#altitude").val(),
            'timezone': $("#timezone").val(),
            'date': $("#date").val(),
            'mintime': $("#mintime").val(),
            'maxtime': $("#maxtime").val(),
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

function loadSkedPasses() {
	$.ajax({
        url: base_url + 'index.php/satellite/searchSkedPasses',
        type: 'post',
        data: {'sat': $("#satlist").val(),
            'yourgrid': $("#yourgrid").val(),
            'minelevation': $("#minelevation").val(),
            'minazimuth': $("#minazimuth").val(),
            'maxazimuth': $("#maxazimuth").val(),
            'altitude': $("#altitude").val(),
            'timezone': $("#timezone").val(),
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
	} else {
		$('#addskedpartner').hide();
	}

}
