<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Remove Dupes from eQSL-Table and add a unique idx

class Migration_rm_eqsl_dbl extends CI_Migration {

	public function up()
	{
		$dbltrbl=1;
		// while ($dbltrbl>0) {	// Enable this after Development // Need this to make sure we also delete eQSLs with  an amount>2. So repeat until we have no more dupes
			$sql='select min(id) as id,qso_id from eQSL_images group by qso_id having count(1)>1;';
			$query=$this->db->query($sql);
			$dbltrbl=$query->num_rows();
			if ($dbltrbl > 0) {
				$eqsl2del=[];
				foreach ($query->result() as $row)
					$eqsl2del[]=$row->id;
				foreach ($eqsl2del as $oneeqsl) {
					$res=$this->db->query("select image_file from eQSL_images where id=?",$oneeqsl)->result()[0]->image_file;
					log_message("Error"," Need to remove file ".$res." with PK ".$oneeqsl);	// Add moving of files HERE
				}
				foreach ($eqsl2del as $oneeqsl) {
					//$this->db->query('delete from eQSL_images where id=?',$oneeqsl);	// Enable this after development
				}
			}
		// } // Enable this after development
		/* Enable this after development
		$index = $this->db->query("SHOW INDEX FROM eQSL_images WHERE Key_name = 'qso_id_UNIQUE'")->num_rows();
		if ($index == 0) {
			$this->db->query("ALTER TABLE `eQSL_images` ADD UNIQUE INDEX `qso_id_UNIQUE` (`qso_id` ASC);");
		}
		*/ 
	}

	public function down() {
		$index = $this->db->query("SHOW INDEX FROM eQSL_images WHERE Key_name = 'qso_id_UNIQUE'")->num_rows();
		if ($index > 0) {
			$this->db->query("ALTER TABLE `eQSL_images` DROP INDEX `qso_id_UNIQUE`;");
		}
	}
}

