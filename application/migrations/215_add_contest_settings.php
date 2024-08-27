<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_contest_settings extends CI_Migration {

    public function up()
    {
		$fields = array(
			'settings' => array(
				'type' => 'TEXT',
				'null' => TRUE,
			),
		);

		// Get the current data so we can add it back in
		$data = array(
			'exchangetype' => $this->db->get('contest_session')->row()->exchangetype,
			'exchange_sequence' => 's-g-e',
			'copyexchangeto' => $this->db->get('contest_session')->row()->copytodok,
		);

		// Add the settings field to the contest_session table
		if (!$this->db->field_exists('settings', 'contest_session')) {
			$this->dbforge->add_column('contest_session', $fields);

			// We also can drop the now unused columns
			$this->dbforge->drop_column('contest_session', 'exchangetype');
			$this->dbforge->drop_column('contest_session', 'copytodok');

			// Update the settings field with the old data
			$this->db->update('contest_session', array('settings' => json_encode($data)));
		}
	}

    public function down()
    {
		// Drop the settings field from the contest_session table
		if ($this->db->field_exists('settings', 'contest_session')) {
			$this->dbforge->drop_column('contest_session', 'settings');
		}

		$fields = array(
			'exchangetype' => array(
				'type' => 'VARCHAR',
				'constraint' => 20,
				'unsigned' => TRUE,
				'null' => TRUE,
				'after' => 'contestid',
			),
			'copytodok' => array(
				'type' => 'INT',
				'constraint' => 10,
				'unsigned' => TRUE,
				'null' => TRUE,
				'after' => 'serialsent',
			),
		);
		
		// Add the fields to the contest_session table
		if (!$this->db->field_exists('exchangetype', 'contest_session')) {
			$this->dbforge->add_column('contest_session', $fields);
		}

	}
}
