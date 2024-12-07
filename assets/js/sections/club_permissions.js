$(document).ready(function(){

    $('#clubuserstable').DataTable({
        "pageLength": 25,
        responsive: true,
        ordering: true,
        "scrollY": "100%",
        "scrollCollapse": true,
        "paging": true,
        "language": {
            url: getDataTablesLanguageUrl(),
        },
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'csv',
                exportOptions: {
                    columns: [ 0, 1, 2, 3, 4, 5 ]
                }
            }
        ]
    });
});