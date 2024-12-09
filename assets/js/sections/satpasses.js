function searchpasses() {
	if ($('#addskedpartner').is(':hidden')) {
		loadPasses();
	} else {
		let skedgrid = $("#skedgrid").val();
		if (skedgrid == '') {
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
