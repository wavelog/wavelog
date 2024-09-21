<?php

// this file catches the callsign data, performs some JS magic and redirects to the QSO logging page

?>
<div class="container mt-3">
    <h2><?php echo $page_title; ?></h2>

    <p id="redirect_message"><?= __("Redirecting to QSO logging page..."); ?></p>
    <div id="errormessage" style="display: none;"></div>
</div>


<script src="<?php echo base_url(); ?>assets/js/jquery-3.3.1.min.js"></script>
<script>
    $(document).ready(function() {
        let call = "<?php echo $callsign; ?>";

        // init some variables
        let qso_window_last_seen = Date.now() - 3600;
        let bc_qsowin = new BroadcastChannel('qso_window');
        let pong_rcvd = false;

        // send the ping
        bc_qsowin.postMessage('ping');

        // listen for the pong
        bc_qsowin.onmessage = function(ev) {
            if (ev.data === 'pong') {
                qso_window_last_seen = Date.now();
                pong_rcvd = true;
            }
        };

        // init the broadcast channel
        let bc2qso = new BroadcastChannel('qso_wish');

        // set some times
        let wait4pong = 2000; // we wait in max 2 seconds for the pong
        let check_intv = 100; // check every 100 ms

        let check_pong = setInterval(function() {
            if (pong_rcvd || ((Date.now() - qso_window_last_seen) < wait4pong)) {
                // max time reached or pong received
                clearInterval(check_pong);
                bc2qso.postMessage({
                    call: call
                });
                closeSelf();
            } else {
                clearInterval(check_pong);
                let newWindow = window.open('<?php echo base_url(); ?>' + 'index.php/qso?manual=0', '_blank');

                if (!newWindow || newWindow.closed || typeof newWindow.closed === 'undefined') {
                    $('#errormessage').html('<?= __("Pop-up was blocked! Please allow pop-ups for this site permanently."); ?>').addClass('alert alert-danger').show();
                    $('#redirect_message').hide();
                } else {
                    newWindow.focus();
                }

                // wait for the ready message
                bc2qso.onmessage = function(ev) {
                    if (ev.data === 'ready') {
                        bc2qso.postMessage({
                            call: call
                        });
                        closeSelf();
                    }
                };
            }
        }, check_intv);
    });

    function closeSelf() {
        $('#redirect_message').html('<?= __("The data was redirected. You can close this window."); ?>');
        window.close();
    }
</script>