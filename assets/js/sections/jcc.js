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
   $.ajax({
       url: base_url + 'index.php/awards/jcc_export',
       type: 'post',
       xhrFields: {
          responseType: 'text/csv;charset=utf8',
       },
       data: {
           band: $('#band2').val(),
           mode: $('#mode').val(),
           qsl: +$('#qsl').prop('checked'),
           lotw: +$('#lotw').prop('checked'),
           qrz: +$('#qrz').prop('checked'),
           eqsl: +$('#eqsl').prop('checked'),
           clublog: +$('#clublog').prop('checked'),
           includedeleted: +$('#includedeleted').prop('checked'),
       },
       success: function(data) {
           var a = document.createElement('a');
           var fileData = ['\ufeff'+data];
           console.log(fileData);
           var blob = new Blob(fileData,{
              type: "text/csv;charset=utf-8;"
           });
           var url = URL.createObjectURL(blob);
           a.href = url;
           a.download = 'qso_export.csv';

           document.body.appendChild(a);
           a.click();
           document.body.removeChild(a);
       },
       error: function() {
          console.log("error");
       },
   });
}
