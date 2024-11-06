<?php
class Migrate extends CI_Controller {

    public function index() {
        $this->load->library('Migration');
        $this->load->config('migration');

        $result = array();
        $latest = $this->migration->latest();

        if (!$latest) {
            show_error($this->migration->error_string());
            log_message('error', 'Migration failed');
            $result['status'] = 'error';
        } else {
            while (file_exists($this->config->item('migration_lockfile'))) {
                sleep(1);
            }
            $result['status'] = 'success';
            $result['version'] = $latest;
        }
        header('Content-Type: application/json');
        echo json_encode($result);
    }

    // During installation we need a way to get encrypted strings using the former Codeigniter encryption library
    public function encrypt() {
        if (ENVIRONMENT == 'production') {
            $cfg_path = 'application/config/config.php';
        } else {
            $cfg_path = 'application/config/' . ENVIRONMENT . '/config.php';
        }
        if (!file_exists('install/.lock') && file_exists($cfg_path)) {
            $this->load->library('encryption');
            echo $this->encryption->encrypt($this->input->post('string', true));
        } else {
            redirect('user/login');
        }
    }
}
