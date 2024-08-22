function loadYears() {
    $(".contestyear").empty();
    $(".contestname").empty();
    $(".contestdates").empty();
    $(".contestbands").empty();
    $(".additionalinfo").attr("hidden", true);
    $.ajax({
        url: base_url+'index.php/reg1test/getYears',
        type: 'post',
        data: {'station_id': $("#station_id").val()},
        success: function (data) {
            if (data.length > 0) {
                $(".contestyear").append('<div class="col-md-3 control-label" for="year">' + lang_export_reg1testedi_select_year + '</div>' +
                '<select id="year" class="form-select my-1 me-sm-2 col-md-2 w-auto" name="year">' +
                '</select>' +
                '  <button onclick="loadContests();" class="btn btn-sm btn-primary w-auto" type="button" id="btncontests">' + lang_export_reg1testedi_proceed + '</button>');

                $.each(data, function(key, value) {
                    $('#year')
                        .append($("<option></option>")
                        .attr("value",value.year)
                        .text(value.year));
                });
            } else {
                $(".contestyear").append(lang_export_reg1testedi_no_contests_for_stationlocation);
            }
        }
    });
}

function loadContests() {
    $(".contestname").empty();
    $(".contestdates").empty();
    $(".contestbands").empty();
    $.ajax({
        url: base_url+'index.php/reg1test/getContests',
        type: 'post',
        data: {'year': $("#year").val(),
                'station_id': $("#station_id").val()
        },
        success: function (data) {
                $(".contestname").append('<div class="col-md-3 control-label" for="contestid">' + lang_export_reg1testedi_select_contest + '</div>' +
                '<select class="form-select my-1 me-sm-2 col-md-4 w-auto" id="contestid" name="contestid">' +
                '</select>' +
                '  <button onclick="loadContestDates();" class="btn btn-sm btn-primary w-auto" type="button" id="btndates">' + lang_export_reg1testedi_proceed + '</button>');

                $.each(data, function(key, value) {
                    $('#contestid')
                        .append($("<option></option>")
                        .attr("value",value.col_contest_id)
                        .text(value.contestname));
                });
        }
    });
}

function loadContestDates() {
    $(".contestdates").empty();
    $(".contestbands").empty();
    $.ajax({
        url: base_url+'index.php/reg1test/getContestDates',
        type: 'post',
        data: {'year': $("#year").val(),
                'contestid': $("#contestid").val(),
                'station_id': $("#station_id").val()},
        success: function (data) {
                $(".contestdates").append('<div class="col-md-3 control-label" for="contestdates">' + lang_export_reg1testedi_select_date_range + '</div>' +
                '<select class="form-select my-1 me-sm-2 col-md-2 w-auto" id="contestdatesfrom" name="contestdatesfrom">' +
                '</select>' +
                '<select class="form-select my-1 me-sm-2 col-md-2 w-auto" id="contestdatesto" name="contestdatesto">' +
                '</select>' +
                '  <button class="btn btn-sm btn-primary w-auto" onclick="loadContestBands();" type="button" id="btnbands">' + lang_export_reg1testedi_proceed + '</button>');

                $.each(data, function(key, value) {
                    $('#contestdatesfrom')
                        .append($("<option></option>")
                        .attr("value", value.date)
                        .text(value.date));
                });


                $.each(data, function(key, value) {
                    $('#contestdatesto')
                        .append($("<option></option>")
                        .attr("value", value.date)
                        .text(value.date));
                });
        }
    });
}

function loadContestBands() {
    $(".contestbands").empty();

    $.ajax({
        url: base_url+'index.php/reg1test/getContestBands',
        type: 'post',
        data: {'year': $("#year").val(),
                'contestid': $("#contestid").val(),
                'station_id': $("#station_id").val(),
                'contestdatesfrom': $("#contestdatesfrom").val(),
                'contestdatesto': $("#contestdatesto").val()
            },
        success: function (data) {
                $(".contestbands").append('<div class="col-md-3 control-label" for="contestband">' + lang_export_reg1testedi_select_band + '</div>' +
                '<select class="form-select my-1 me-sm-2 col-md-4 w-auto" id="contestband" name="contestband">' +
                '</select>' +
                '  <button onclick="addAdditionalInfo();" class="btn btn-sm btn-primary w-auto" type="button" id="btnadditionalinfo">' + lang_export_reg1testedi_proceed + '</button>');

                $.each(data, function(key, value) {
                    $('#contestband')
                        .append($("<option></option>")
                        .attr("value",value.band)
                        .text(value.band));
                });
        }
    });
}

function addAdditionalInfo() {
    $(".additionalinfo").removeAttr("hidden");
}
