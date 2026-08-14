<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_unmark_unsupported_lotw_uploads extends CI_Migration
{
   public function up() {

      $this->db->where_in('COL_PROP_MODE', $this->config->item('lotw_unsupported_prop_modes'));
      $this->db->update($this->config->item('table_name'), array('COL_LOTW_QSLSDATE' => null, 'COL_LOTW_QSL_SENT' => 'I', 'COL_LOTW_QSLRDATE' => null, 'COL_LOTW_QSL_RCVD' => 'I'));
   }

   public function down() {
   }
}
