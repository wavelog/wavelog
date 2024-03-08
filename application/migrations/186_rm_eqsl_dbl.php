<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Remove Dupes from eQSL-Table and add a unique idx

class Migration_rm_eqsl_dbl extends CI_Migration
{

	public function up()
	{
		$dbltrbl = 1;
		while ($dbltrbl>0) {
		$sql = 'SELECT MIN(id) as id,qso_id FROM eQSL_images GROUP BY qso_id HAVING count(1)>1;';
		$query = $this->db->query($sql);
		$dbltrbl = $query->num_rows();

		if ($dbltrbl > 0) {
			$eqsl2del = [];

			foreach ($query->result() as $row)
				$eqsl2del[] = $row->id;

			foreach ($eqsl2del as $oneeqsl) {
				$res = $this->db->query("SELECT image_file FROM eQSL_images WHERE id=?", $oneeqsl)->result()[0]->image_file;

				if ($this->config->item('userdata')) {

					$userdata_dir = $this->config->item('userdata');
					$qso_id = $this->get_qsoid_from_eqsl_filename($res) ?? '';

					// we need to get the user ID which corresponds to that particular qso
					if (!empty($qso_id)) {
						$get_user_id = $this->get_user_id_from_qso($qso_id);

						// can be an deleted qso
						if (!empty($get_user_id)) {
							$user_id = $get_user_id;
						} else {
							$user_id = 'not_assigned';
						}
					} else {
						$user_id = 'not_assigned';
					}

					// target path with userdata dir
					$target_path = $userdata_dir . '/' . $user_id . '/eqsl_card';

					// then remove the file
					if (!unlink($target_path . '/' . $res)) {
						log_message('error', "Mig 186: Dupe file: '" . $target_path . "/" . $res . "' could not be deleted. There is no file with this filename. This shouldn't be a problem.");
					} else {
						log_message('debug', "Mig 186: Dupe file: '" . $target_path . "/" . $res . "' were deleted because it was a dupe.");
					}
				} else {
					// if 'userdata' is disabled we can use the old paths
					if (!unlink('images/eqsl_card_images/' . $res)) {
						log_message('error', "Mig 186: Dupe file: 'images/eqsl_card_images/" . $res . "' could not be deleted. There is no file with this filename. This shouldn't be a problem.");
					} else {
						log_message('debug', "Mig 186: Dupe file: 'images/eqsl_card_images/" . $res . "' were deleted because it was a dupe.");
					}
				}
			}
			foreach ($eqsl2del as $oneeqsl) {
				$this->db->query('delete from eQSL_images where id=?',$oneeqsl);
			}
		}
		}
		
		$index = $this->db->query("SHOW INDEX FROM eQSL_images WHERE Key_name = 'qso_id_UNIQUE'")->num_rows();
		if ($index == 0) {
			$this->db->query("ALTER TABLE `eQSL_images` ADD UNIQUE INDEX `qso_id_UNIQUE` (`qso_id` ASC);");
		}
		
	}

	public function down()
	{
		$index = $this->db->query("SHOW INDEX FROM eQSL_images WHERE Key_name = 'qso_id_UNIQUE'")->num_rows();
		if ($index > 0) {
			$this->db->query("ALTER TABLE `eQSL_images` DROP INDEX `qso_id_UNIQUE`;");
		}
	}

	function get_qsoid_from_eqsl_filename($filename)
	{

		$sql = "SELECT qso_id FROM eQSL_images WHERE image_file = ?";

		$result = $this->db->query($sql, $filename);

		$row = $result->row();
		return $row->qso_id;
	}

	function get_user_id_from_qso($qso_id)
	{

		$clean_qsoid = $this->security->xss_clean($qso_id);

		$sql =    'SELECT station_profile.user_id
				  FROM ' . $this->config->item('table_name') . ' 
				  INNER JOIN station_profile ON (' . $this->config->item('table_name') . '.station_id = station_profile.station_id)
				  WHERE ' . $this->config->item('table_name') . '.COL_PRIMARY_KEY = ?';

		$result = $this->db->query($sql, $clean_qsoid);
		$row = $result->row();

		return $row->user_id;
	}
}
