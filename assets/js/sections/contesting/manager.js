$(document).ready(function () {
    $("#user_contests_table").DataTable({
        stateSave: true,
        language: {
            url: getDataTablesLanguageUrl(),
        },
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