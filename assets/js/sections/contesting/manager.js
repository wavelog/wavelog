var contestsTable;

$(document).ready(function () {
    contestsTable = $("#user_contests_table").DataTable({
        stateSave: false,
        order: [], // keep server-side order by default
        columnDefs: [
            { orderable: false, targets: 0 }, // checkbox column
            { orderable: false, targets: 1 }, // start column
        ],
        language: {
            url: getDataTablesLanguageUrl(),
        },
    });

    // Select/unselect all rows (including rows on other DataTables pages)
    $('#checkBoxAll').on('change', function () {
        contestsTable.$('input.row-check').prop('checked', this.checked);
    });

    // Unchecking a single row unchecks the "select all" checkbox
    $('#user_contests_table tbody').on('change', 'input.row-check', function () {
        if (!this.checked) {
            $('#checkBoxAll').prop('checked', false);
        }
    });

    // Batch delete of selected contest sessions
    $('#deleteSelectedSessions').on('click', function () {
        var ids = contestsTable.$('input.row-check:checked').map(function () {
            return this.value;
        }).get();

        if (ids.length === 0) {
            alert(lang_contesting_no_selection);
            return;
        }

        $('#batchDeleteCount').text(ids.length);
        $('#batchDeleteIds').val(ids.join(','));
        $('#batchDeleteQsosCheck').prop('checked', false);
        $('#contestBatchDeleteModal').modal('show');
    });
});

function initContestDropdown() {
    var $select = $('#contest_adif_id');
    if ($select.length && !$select[0].selectize) {
        $select.selectize({
            create: false,
            closeAfterSelect: true,
            placeholder: $select.attr('placeholder'),
        });
    }
}

function initCopyExchangeToggle() {
    var exchangeCheckbox = document.getElementById('ef-exchange');
    var copyExchangeTo = document.getElementById('copyexchangeto');
    if (!exchangeCheckbox || !copyExchangeTo) return;

    function syncState() {
        copyExchangeTo.disabled = !exchangeCheckbox.checked;
        if (!exchangeCheckbox.checked) {
            copyExchangeTo.value = '';
        }
    }

    exchangeCheckbox.addEventListener('change', syncState);
    syncState();
}

function create_modal() {
    $.ajax({
        url: base_url + 'index.php/contesting/create_session',
        type: 'GET',
        success: function (response) {
            $('#contestSessionModal-container').html(response);
            initContestDropdown();
            initCopyExchangeToggle();
            $('#contestCreateSessionModal').modal('show');
        },
        error: function () {
            alert(lang_error);
        }
    });
}

function edit_modal(session_id) {
    $.ajax({
        url: base_url + 'index.php/contesting/edit_session?contest_session_id=' + session_id,
        type: 'GET',
        success: function (response) {
            $('#contestSessionModal-container').html(response);
            initContestDropdown();
            initCopyExchangeToggle();
            $('#contestCreateSessionModal').modal('show');
        },
        error: function () {
            alert(lang_error);
        }
    });
}

function delete_modal(session_id) {
    $.ajax({
        url: base_url + 'index.php/contesting/delete_session?contest_session_id=' + session_id,
        type: 'GET',
        success: function (response) {
            $('#contestSessionModal-container').html(response);
            $('#contestDeleteSessionModal').modal('show');
        },
        error: function () {
            alert(lang_error);
        }
    })
}