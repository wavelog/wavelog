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
				className: 'mb-1 btn btn-primary', // Bootstrap classes
				init: function(api, node, config) {
					$(node).removeClass('dt-button').addClass('btn btn-primary'); // Ensure Bootstrap class applies
				},
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
        searchField: ['user_name', 'user_callsign', 'user_firstname', 'user_lastname'],
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
                let string = '<div style="text-align: left; margin-left: 10px; padding: 3px;"><span class="text-muted small">[' + item.user_name + ']</span> ' + item.user_callsign.toUpperCase() + ' - ' + item.user_firstname + ' ' + item.user_lastname + '</div>';
                return string;
            },
            item: function(item) {
                let string = '<div style="text-align: left; margin-left: 2px;"><span class="text-muted small">[' + item.user_name + ']</span> ' + item.user_callsign.toUpperCase() + ' - ' + item.user_firstname + ' ' + item.user_lastname + '</div>';
                return string;
            }
        },
        onInitialize: function() {
            this.$control.addClass('selectize-dark');

            $('.selectize-control').parents().each(function() {
                $(this).css('overflow', 'visible');
            });
        }
    });

    $('[type="submit"]').on('click', function() {
        $(this).prop('disabled', true).addClass('running');
        $(this).closest('form').submit();
    });
});
