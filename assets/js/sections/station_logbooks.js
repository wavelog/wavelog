$(document).ready( function () {
    $('#station_logbooks_table').DataTable({
        "stateSave": true,
        "language": {
            url: get_datatables_language()
        }
    });
} );

$(document).ready( function () {
    $('#station_logbooks_linked_table').DataTable({
        "stateSave": true,
        "paging": true,
        "language": {
            url: get_datatables_language()
        }
    });
} );
