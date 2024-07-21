function searchpasses() {
    $.ajax({
        url: base_url + 'index.php/satellite/searchpasses',
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
