<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_normalize_note_titles extends CI_Migration
{
	public function up()
	{
		// Legacy code stored callsigns in note titles with Ø (U+00D8) instead of 0
		// for the slashed-zero display hack. Display now handled via font; normalize stored data.
		$this->db->query("UPDATE notes SET title = REPLACE(title, 'Ø', '0') WHERE cat = 'Contacts'");
	}

	public function down()
	{
		// Not possible — once normalized, original Ø vs 0 distinction is irrecoverable.
	}
}
