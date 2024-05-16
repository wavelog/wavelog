<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Adding a column to store status message about last LoTW upload

class Migration_add_lotw_upload_status_message extends CI_Migration
{

  public function up()
  {
    if (!$this->db->field_exists('last_upload_fail', 'lotw_certs')) {
      $fields = array(
        'last_upload_fail DATETIME NULL DEFAULT NULL AFTER `last_upload`'
      );
      $this->dbforge->add_column('lotw_certs', $fields);
    }
    if (!$this->db->field_exists('last_upload_status', 'lotw_certs')) {
      $fields = array(
        'last_upload_status VARCHAR(50) NULL DEFAULT NULL AFTER `last_upload_fail`',
      );
      $this->dbforge->add_column('lotw_certs', $fields);
    }
  }

  public function down()
  {
    if ($this->db->field_exists('last_upload_fail', 'lotw_certs')) {
       $this->dbforge->drop_column('lotw_certs', 'last_upload_fail');
    }
    if ($this->db->field_exists('last_upload_status', 'lotw_certs')) {
       $this->dbforge->drop_column('lotw_certs', 'last_upload_status');
    }
  }
}
