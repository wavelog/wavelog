<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
*   Just an empty placeholder. To keep compatibility for the migration from Cloudlog to Wavelog we need an empty migration here as placeholder.
*   Find the tutorial here: https://docs.wavelog.org/getting-started/migration/cloudlog-to-wavelog/
*
*   During development in February 2026 the migration Tutorial above became deprecated since the code diverged way too much and with this also the database.
*   However this file keeps a minimum amount of compatibility and does not hurt. Therefore we keep it here in case someone want's to give this tutorial a try
*   even we highly do not recommend that.
*/

class Migration_welcome_to_wavelog extends CI_Migration {

    public function up()
    {
        // Nothing happening here
    }

    public function down()
    {
        // No rollback available. This is just a placeholder.
    }
}