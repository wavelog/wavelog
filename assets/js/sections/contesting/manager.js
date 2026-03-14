$(document).ready(function () {
    $("#user_contests_table").DataTable({
        stateSave: true,
        language: {
            url: getDataTablesLanguageUrl(),
        },
    });
});

function create_modal() {
    $.ajax({
        url: base_url + 'index.php/contesting/create_session',
        type: 'GET',
        success: function (response) {
            $('#contestSessionModal-container').html(response);
            $('#contestCreateSessionModal').modal('show');
        },
        error: function () {
            alert('Error');
        }
    });
}

function edit_modal(session_id) {
    $.ajax({
        url: base_url + 'index.php/contesting/edit_session?contest_session_id=' + session_id,
        type: 'GET',
        success: function (response) {
            $('#contestSessionModal-container').html(response);
            $('#contestCreateSessionModal').modal('show');
        },
        error: function () {
            alert('Error');
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
            alert('Error');
        }
    })
}