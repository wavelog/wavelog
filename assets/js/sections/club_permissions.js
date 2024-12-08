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

    $('#user_id').selectize({
        delimiter: ';',
        maxItems: 1,
        closeAfterSelect: true,
        valueField: 'user_id',
        labelField: 'user_callsign',
        searchField: ['user_callsign', 'user_firstname', 'user_lastname'],
        options: [],
        create: false,
        load: function(query, callback) {
            if (!query) return callback();
            query = query.toUpperCase();
            $.ajax({
                url: base_url + 'index.php/club/get_users',
                type: 'POST',
                dataType: 'json',
                data: { query: query },
                error: function() {
                    callback();
                },
                success: function(res) {
                    callback(res);
                }
            });
        },
        render: {
            option: function(item) {
                let string = '<div style="text-align: left; margin-left: 10px; padding: 3px;">' + item.user_callsign.toUpperCase() + ' - ' + item.user_firstname + ' ' + item.user_lastname + '</div>';
                return string;
            },
            item: function(item) {
                let string = '<div style="text-align: left; margin-left: 2px;">' + item.user_callsign.toUpperCase() + ' - ' + item.user_firstname + ' ' + item.user_lastname + '</div>';
                return string;
            }
        },
        onInitialize: function() {
            $('.selectize-control').parents().each(function() {
                $(this).css('overflow', 'visible');
            });
        }
    });
});