$(document).ready(function () {
	$('#jccTable').DataTable({
        "pageLength": 25,
        responsive: false,
        ordering: false,
        "scrollY":        "400px",
        "scrollCollapse": true,
        "paging":         false,
        "scrollX": true,
        "language": {
            url: getDataTablesLanguageUrl(),
        },
        dom: 'Bfrtip',
        buttons: [
            'csv'
        ]
    });
});

function export_qsos() {
   console.log("TEST");
   $.ajax({
       url: base_url + 'index.php/awards/jcc_export',
       type: 'post',
       xhrFields: {
          responseType: 'blob',
       },
       data: {
           band: $('#band2').val(),
           mode: $('#mode').val(),
           worked: +$('#worked').prop('checked'),
           confirmed: +$('#confirmed').prop('checked'),
           notworked: +$('#notworked').prop('checked'),
           qsl: +$('#qsl').prop('checked'),
           lotw: +$('#lotw').prop('checked'),
           qrz: +$('#qrz').prop('checked'),
           eqsl: +$('#eqsl').prop('checked'),
           includedeleted: +$('#includedeleted').prop('checked'),
           Africa: +$('#Africa').prop('checked'),
           Asia: +$('#Asia').prop('checked'),
           Europe: +$('#Europe').prop('checked'),
           NorthAmerica: +$('#NorthAmerica').prop('checked'),
           SouthAmerica: +$('#SouthAmerica').prop('checked'),
           Oceania: +$('#Oceania').prop('checked'),
           Antarctica: +$('#Antarctica').prop('checked'),
           sat: $("#sats").val(),
           orbit: $("#orbits").val(),
       },
       success: function(data) {
           var a = document.createElement('a');
           var url = window.URL.createObjectURL(data);
           a.href = url;
           a.download = 'report.csv';
           document.body.append(a);
           a.click();
           a.remove();
           window.URL.revokeObjectURL(url);
       },
       error: function() {
          console.log("error");
       },
   });
}
