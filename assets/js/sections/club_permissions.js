$(document).ready(function(){

    var clubUsersTable = $('#clubuserstable').DataTable({
        "pageLength": 25,
        responsive: true,
        ordering: true,
        columnDefs: [
            { orderable: false, targets: 0 } // checkbox column
        ],
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
                    columns: [ 1, 2, 3, 4, 5, 6 ] // skip checkbox col 0
                }
            }
        ]
    });

    // ---- Batch member selection (current DataTable page only) ----
    var lastChecked = null;

    function selectedIds() {
        return $('#clubuserstable input.row-check:checked').map(function () {
            return this.value;
        }).get();
    }

    function syncBatchUI() {
        var n = selectedIds().length;
        $('#batchEditCount').text(n);
        $('#batchDeleteCount').text(n);
        $('#batchEditBtn').prop('disabled', n === 0);
        $('#batchDeleteBtn').prop('disabled', n === 0);

        var boxes = $('#clubuserstable input.row-check');
        $('#checkBoxAll').prop('checked', boxes.length > 0 && boxes.filter(':checked').length === boxes.length);
    }

    // Select / unselect all rows on the current page
    $('#checkBoxAll').on('change', function () {
        $('#clubuserstable input.row-check').prop('checked', this.checked);
        lastChecked = null;
        syncBatchUI();
    });

    // Per-row change: shift-range select + header sync (current page only)
    $('#clubuserstable tbody').on('change', 'input.row-check', function (e) {
        if (e.shiftKey && this.checked && lastChecked) {
            var boxes = $('#clubuserstable input.row-check');
            var from = boxes.index(this);
            var to = boxes.index(lastChecked);
            var lo = Math.min(from, to), hi = Math.max(from, to);
            boxes.slice(lo, hi + 1).prop('checked', true);
        }
        lastChecked = this;
        syncBatchUI();
    });

    // Reset selection state whenever the table redraws (page/sort/filter)
    clubUsersTable.on('draw.dt', function () {
        $('#checkBoxAll').prop('checked', false);
        lastChecked = null;
        syncBatchUI();
    });

    $('#batchEditBtn').on('click', function () {
        var ids = selectedIds();
        if (ids.length === 0) return;
        $('#batchEditIds').val(ids.join(','));
        bootstrap.Modal.getOrCreateInstance(document.getElementById('batchEditModal')).show();
    });

    $('#batchDeleteBtn').on('click', function () {
        var ids = selectedIds();
        if (ids.length === 0) return;
        $('#batchDeleteIds').val(ids.join(','));
        $('#batchDeleteConfirmCount').text(ids.length);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('batchDeleteModal')).show();
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
                data: { 
                    club_id: $('#club_id').val(),
                    query: query 
                },
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
                let string = '<div style="text-align: left; margin-left: 10px; padding: 3px;"><span class="text-muted small">[' + item.user_name + ']</span> <span class="callsign">' + item.user_callsign.toUpperCase() + '</span> - ' + item.user_firstname + ' ' + item.user_lastname + '</div>';
                return string;
            },
            item: function(item) {
                let string = '<div style="text-align: left; margin-left: 2px;"><span class="text-muted small">[' + item.user_name + ']</span> <span class="callsign">' + item.user_callsign.toUpperCase() + '</span> - ' + item.user_firstname + ' ' + item.user_lastname + '</div>';
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
