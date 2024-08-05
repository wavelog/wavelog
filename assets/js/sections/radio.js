$(document).ready(function () {

    setInterval(function () {
        // Update the Radio Interfaces every 2 Seconds
        $.get(base_url + 'index.php/radio/status/', function (result) {
            $('.status').html(result);
        });
    }, 2000);

});