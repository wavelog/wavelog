<?php

class Debug_model extends CI_Model
{

    private $userdata_dir;
    private $flag_file;

    private $src_eqsl;
    private $eqsl_dir;

    private $src_qsl;
    private $qsl_dir;

    public function __construct()
    {
        $this->userdata_dir = $this->config->item('userdata');
        $this->flag_file = '.migrated'; // we use this flag file to determine if the migration already run through

        $this->src_eqsl = 'images/eqsl_card_images';
        $this->eqsl_dir = 'eqsl_card';  // make sure this is the same as in Eqsl_images.php function get_imagePath()

        $this->src_qsl = 'assets/qslcard';
        $this->qsl_dir = 'qsl_card';  // make sure this is the same as in Qsl_model.php function get_imagePath()
    }

    function migrate_userdata()
    {

        $this->load->model('Logbook_model');

        $allowed_file_extensions = ['jpg', 'jpeg', 'gif', 'png'];

        // *****   EQSL   ***** //

        // Let's scan the whole folder and get necessary data for each file
        foreach (scandir($this->src_eqsl) as $file) {
            // Ignore files if they are not jpg, png or gif
            $file_extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (!in_array($file_extension, $allowed_file_extensions)) continue;

            if (!is_readable($this->src_eqsl . '/' . $file)) continue;
            if ($file != '.' && $file != '..') {

                // we need the qso_id from the database to get the necessary user_id
                $qso_id = $this->get_qsoid_from_eqsl_filename($file) ?? '';

                // check if the qso_id is empty, if yes we create a folder 'not assigned' instead of 'user_id'
                if (!empty($qso_id)) {
                    // get the user_id for this qso_id
                    $get_user_id = $this->Logbook_model->get_user_id_from_qso($qso_id);

                    // it can happen that the user_id is empty even there is a qso_id (deleted qso or deleted user)
                    if(!empty($get_user_id)) {
                        $user_id = $get_user_id;
                    } else {
                        $user_id = 'not_assigned';
                    }
                } else {
                    $user_id = 'not_assigned';
                }

                // make sure the target path exists
                $target = $this->userdata_dir . '/' . $user_id . '/' . $this->eqsl_dir;
                if (!file_exists(realpath(APPPATH . '../') . '/' . $target)) {
                    mkdir(realpath(APPPATH . '../') . '/' . $target, 0755, true);
                }

                // then copy the file
                if (!copy($this->src_eqsl . '/' . $file, $target . '/' . $file)) {
                    return false; // Failed to copy file
                }
            }
        }

        // *****   QSL Cards   ***** //

        // Let's scan the whole folder and get necessary data for each file
        foreach (scandir($this->src_qsl) as $file) {
            // Ignore files if they are not jpg, png or gif
            $file_extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (!in_array($file_extension, $allowed_file_extensions)) continue;

            if (!is_readable($this->src_qsl . '/' . $file)) continue;
            if ($file != '.' && $file != '..') {

                // we need the qso_id from the database to get the necessary user_id
                $qso_id = $this->get_qsoid_from_qsl_filename($file) ?? '';

                // check if the qso_id is empty, if yes we create a folder 'not assigned' instead of 'user_id'
                if (!empty($qso_id)) {
                    // get the user_id for this qso_id
                    $get_user_id = $this->Logbook_model->get_user_id_from_qso($qso_id);

                    // it can happen that the user_id is empty even there is a qso_id (deleted qso or deleted user)
                    if(!empty($get_user_id)) {
                        $user_id = $get_user_id;
                    } else {
                        $user_id = 'not_assigned';
                    }
                } else {
                    $user_id = 'not_assigned';
                }

                // make sure the target path exists
                $target = $this->userdata_dir . '/' . $user_id . '/' . $this->qsl_dir;
                if (!file_exists(realpath(APPPATH . '../') . '/' . $target)) {
                    mkdir(realpath(APPPATH . '../') . '/' . $target, 0755, true);
                }

                // then copy the file
                if (!copy($this->src_qsl . '/' . $file, $target . '/' . $file)) {
                    return false; // Failed to copy file
                }
            }
        }

        // here we create the 'migrated' flag
        if (!file_exists(realpath(APPPATH . '../') . '/' . $this->userdata_dir . '/' . $this->flag_file)) {
            touch(realpath(APPPATH . '../') . '/' . $this->userdata_dir . '/' . $this->flag_file);
        }

        return true;
    }

    function check_migrated_flag()
    {
        if (!file_exists(realpath(APPPATH . '../') . '/' . $this->userdata_dir . '/' . $this->flag_file)) {
            return false;
        } else {
            return true;
        }
    }

    function get_qsoid_from_eqsl_filename($filename)
    {

        $sql = "SELECT qso_id FROM eQSL_images WHERE image_file = ?";

        $result = $this->db->query($sql, $filename);

        $row = $result->row();
        return $row->qso_id;
    }

    function get_qsoid_from_qsl_filename($filename)
    {

        $sql = "SELECT qsoid FROM qsl_images WHERE filename = ?";

        $result = $this->db->query($sql, $filename);

        $row = $result->row();
        return $row->qsoid;
    }

	// Returns the number of qso's total on this instance
	function count_all_qso() {
		$sql = 'SELECT COUNT(*) AS total FROM '. $this->config->item('table_name').' WHERE station_id IS NOT NULL;';
		$query = $this->db->query($sql);
		return $query->row()->total;
	}

    function count_users() {
        $sql = 'SELECT COUNT(*) AS total FROM users;';
        $query = $this->db->query($sql);
        return $query->row()->total;
    }

	function getMigrationVersion() {
        $this->db->select_max('version');
        $query = $this->db->get('migrations');
        $migration_version = $query->row();

        if ($query->num_rows() == 1) {
            $migration_version = $query->row()->version;
            return $migration_version;
        } else {
            return null;
        }
    }

	public function calls_without_station_id() {
		$query=$this->db->query("select distinct COL_STATION_CALLSIGN from ".$this->config->item('table_name')." where station_id is null or station_id = ''");
		$result = $query->result_array();
		return $result;
    }
}
