<?php
class Migrate extends CI_Controller {

  public function index() {
    $this->load->library('Migration');

    if (!$this->migration->latest()) {
      show_error($this->migration->error_string());
      $result = 'error';
    } else {
      while (file_exists(APPPATH . 'cache/.migration_running')) {
        sleep(1);
      }
      $result = 'success';
    }
    echo $result;
  }
}
