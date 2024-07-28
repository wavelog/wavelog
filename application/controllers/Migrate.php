<?php
class Migrate extends CI_Controller {

    public function index() {
        $this->load->library('Migration');

        $result = array();
        $latest = $this->migration->latest();

        if (!$latest) {
            show_error($this->migration->error_string());
            log_message('error', 'Migration failed');
            $result['status'] = 'error';
        } else {
            while (file_exists(APPPATH . 'cache/.migration_running')) {
                sleep(1);
            }
            $result['status'] = 'success';
            $result['version'] = $latest;
        }
        header('Content-Type: application/json');
        echo json_encode($result);
    }
}
