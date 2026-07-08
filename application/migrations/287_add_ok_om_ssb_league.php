<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
	Add the Czech-Slovak SSB League (Česko-slovenská SSB liga) to the contest list.
	https://ssbliga.nagano.cz/
*/

class Migration_add_ok_om_ssb_league extends CI_Migration {

	public function up() {

		$contests = array(
			array('name' => 'OK-OM SSB Liga', 'adifname' => 'OK-OM-SSB-LEAGUE', 'active' => 1),
		);

		foreach ($contests as $contest) {
			$exists = $this->db->where('adifname', $contest['adifname'])
							->get('contest')
							->num_rows() > 0;

			if (!$exists) {
				$this->db->insert('contest', $contest);
			}
		}

	}

	public function down() {

		$this->db->where_in('adifname', array('OK-OM-SSB-LEAGUE'));
		$this->db->delete('contest');

	}
}
