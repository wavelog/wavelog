<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Adding a column to users table for the timestamp of the last login

class Migration_rename_last_login extends CI_Migration
{

  public function up()
  {
    // if last_login_date column exists, change it to last_seen
    $fields = $this->db->field_data('users');

    foreach ($fields as $field) {

      if ($field->name == 'last_login_date') {
        $this->db->query('ALTER TABLE users CHANGE last_login_date last_seen TIMESTAMP DEFAULT NULL');
      }

    }
  }

  public function down()
  {
    $this->db->query('ALTER TABLE users CHANGE last_seen last_login_date TIMESTAMP DEFAULT NULL');
  }
}

