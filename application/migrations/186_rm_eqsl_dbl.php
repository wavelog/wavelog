<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Adding a column to users table for the timestamp of the last login

class Migration_rm_eqsl_dbl extends CI_Migration {

	public function up()
	{
		$sql='select min(id) as id,qso_id from eQSL_images group by qso_id having count(1)>1;';
		$query=$this->db->query($sql);
		if ($query->num_rows() > 0) {
			$eqsl2del=[];
			foreach ($query->result() as $row)
				$eqsl2del[]=$row->id;
			foreach ($eqsl2del as $oneeqsl) {
				$res=$this->db->query("select image_file from eQSL_images where id=?",$oneeqsl)->result()[0]->image_file;
				log_message("Error"," Need to remove file ".$res." with PK ".$oneeqsl);	// Add moving of files HERE
			}
			foreach ($eqsl2del as $oneeqsl) {
				// $this->db->query('delete from eQSL_images where id=?',$oneeqsl);
			}
		}
	}

	public function down() {
	}
}

