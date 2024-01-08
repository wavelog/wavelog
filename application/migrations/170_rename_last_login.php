<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Adding a column to users table for the timestamp of the last login

class Migration_rename_last_login extends CI_Migration
{

  public function up()
  {
    // if COL_AWARD_SUMMITED column exists, change it to COL_AWARD_SUBMITTED
    $fields = $this->db->field_data('users');
    foreach ($fields as $field) {
      if ($field->name == 'last_login_date') {
        $this->db->query(
          'ALTER TABLE ' .
            $this->db->escape_identifiers('users') .
            ' CHANGE last_login_date last_seen TIMESTAMP'
        );
      }
    }
  }

  public function down()
  {
    $this->db->query(
      'ALTER TABLE ' .
        $this->db->escape_identifiers('users') .
        ' CHANGE last_seen last_login_date TIMESTAMP'
    );
  }
}

