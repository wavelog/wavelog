<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_unmark_unsupported_lotw_uploads extends CI_Migration
{
   public function up() {

      // Missing in tqsl 2.7.3 config.xml
      $lotw_unsupported_modes = array('INTERNET', 'RPT');

      $this->db->where_in('COL_PROP_MODE', $lotw_unsupported_modes);
      $this->db->update($this->config->item('table_name'), array('COL_LOTW_QSLSDATE' => null, 'COL_LOTW_QSL_SENT' => 'N'));
   }

   public function down() {
   }
}
