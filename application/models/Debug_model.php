<?php

class Debug_model extends CI_Model {

    function migrate_userdata() {

        $this->load->model('Logbook_model');

        $userdata_dir = $this->config->item('userdata');

        // *****   EQSL   ***** //
        $src = 'images/eqsl_card_images';
        $eqsl_dir = 'eqsl_card';  // make sure this is the same as in Eqsl_images.php function get_imagePath()

        // Let's scan the whole folder and get necessary data for each file
        foreach (scandir($src) as $file) {
            // Ignore .html files
            if (pathinfo($file, PATHINFO_EXTENSION) === 'html') continue;

			if (!is_readable($src . '/' . $file)) continue;
			if ($file != '.' && $file != '..') {

                // we need the qso_id from the database to get the necessary user_id
                $qso_id = $this->get_qsoid_from_eqsl_filename($file);

                // only copy the file if the qso_id is not empty
                if (!empty($qso_id)) {

                    // get the user_id for this qso_id
                    $user_id = $this->Logbook_model->get_user_id_from_qso($qso_id);

                    // make sure the target path exists
                    $target = $userdata_dir.'/'.$user_id.'/'.$eqsl_dir;
                    if (!file_exists(realpath(APPPATH.'../').'/'.$target)) {
                        mkdir(realpath(APPPATH.'../').'/'.$target, 0755, true);
                    }

                    // then copy the file
                    if (!copy($src.'/'.$file, $target.'/'. $file)) {
                        return false; // Failed to copy file
                    }
                }
			}
		}

        // *****   QSL Cards   ***** //
        $src = 'assets/qslcard';
        $qsl_dir = 'qsl_card';  // make sure this is the same as in Qsl_model.php function get_imagePath()

        // Let's scan the whole folder and get necessary data for each file
        foreach (scandir($src) as $file) {
            // Ignore .html files
            if (pathinfo($file, PATHINFO_EXTENSION) === 'html') continue;

			if (!is_readable($src . '/' . $file)) continue;
			if ($file != '.' && $file != '..') {

                // we need the qso_id from the database to get the necessary user_id
                $qso_id = $this->get_qsoid_from_qsl_filename($file);

                // only copy the file if the qso_id is not empty
                if (!empty($qso_id)) {

                    // get the user_id for this qso_id
                    $user_id = $this->Logbook_model->get_user_id_from_qso($qso_id);

                    // make sure the target path exists
                    $target = $userdata_dir.'/'.$user_id.'/'.$qsl_dir;
                    if (!file_exists(realpath(APPPATH.'../').'/'.$target)) {
                        mkdir(realpath(APPPATH.'../').'/'.$target, 0755, true);
                    }

                    // then copy the file
                    if (!copy($src.'/'.$file, $target.'/'. $file)) {
                        return false; // Failed to copy file
                    }
                }
			}
		}

        return true;
    }

    function get_qsoid_from_eqsl_filename($filename) {

        $sql = "SELECT qso_id FROM eQSL_images WHERE image_file = ?";

        $result = $this->db->query($sql, $filename);

        $row = $result->row();
        return $row->qso_id;
    }

    function get_qsoid_from_qsl_filename($filename) {

        $sql = "SELECT qsoid FROM qsl_images WHERE filename = ?";

        $result = $this->db->query($sql, $filename);

        $row = $result->row();
        return $row->qsoid;
    }

    function check_for_not_migrated_files() {
        
    }

}