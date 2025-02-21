<?php

/* user_model.php
 *
 * This model implements user authentication and authorization
 *
 */


// Uses 'phpass' from http://www.openwall.com/phpass/ to implement password hashing
// TODO migration away from this?
//require_once('application/third_party/PasswordHash.php');

class User_Model extends CI_Model {

	// FUNCTION: object get($username)
	// Retrieve a user
	function get($username) {
		// Clean ID
		$clean_username = $this->security->xss_clean($username);

		$this->db->where('user_name', $clean_username);
		$r = $this->db->get($this->config->item('auth_table'));
		return $r;
	}

	// FUNCTION: object get_by_id($id)
	// Retrieve a user by user ID
	function get_by_id($id) {
				// Clean ID
		$clean_id = $this->security->xss_clean($id);

		$this->db->where('user_id', $clean_id);
		$r = $this->db->get($this->config->item('auth_table'));
		return $r;
	}

	// FUNCTION: object get_all_lotw_users
	// Returns all users with lotw details
	function get_all_lotw_users() {
		$this->db->where('user_lotw_name !=', null);
		$this->db->where('user_lotw_name !=', "");
		$r = $this->db->get($this->config->item('auth_table'));
		return $r;
	}

	// FUNCTION: object get_by_email($email)
	// Retrieve a user by email address
	function get_by_email($email) {

		$clean_email = $this->security->xss_clean($email);

		$this->db->where('user_email', $clean_email);
		$r = $this->db->get($this->config->item('auth_table'));
		return $r;
	}

	/*
	 * Function: check_email_address
	 *
	 * Checks if an email address is already in use
	 *
	 * @param string $email
	 */
	function check_email_address($email) {

		$clean_email = $this->security->xss_clean($email);

		$this->db->where('user_email', $clean_email);
		$query = $this->db->get($this->config->item('auth_table'));

		if ($query->num_rows() > 0) {
			return true;
		} else {
			return false;
		}
	}

	function get_user_email_by_id($id) {

		$clean_id = $this->security->xss_clean($id);

		$this->db->where('user_id', $clean_id);
		$query = $this->db->get($this->config->item('auth_table'));

		$r = $query->row();
		return $r->user_email;
	}

	function get_user_amsat_status_upload_by_id($id) {

		$clean_id = $this->security->xss_clean($id);

		$this->db->where('user_id', $clean_id);
		$query = $this->db->get($this->config->item('auth_table'));

		$r = $query->row();
		return $r->user_amsat_status_upload;
	}

	function hasQrzKey($user_id) {
		$this->db->where('station_profile.qrzapikey is not null');
		$this->db->where('station_profile.qrzapikey != ""');
		$this->db->join('station_profile', 'station_profile.user_id = '.$user_id);
		$query = $this->db->get($this->config->item('auth_table'));

		$ret = $query->row();
		if ($ret->user_email ?? '' != '') {
			return $ret->user_email;
		} else {
			return '';
		}
	}

	function get_email_address($station_id) {
		$this->db->where('station_id', $station_id);
		$this->db->join('station_profile', 'station_profile.user_id = '.$this->config->item('auth_table').'.user_id');
		$query = $this->db->get($this->config->item('auth_table'));

		$ret = $query->row();
		return $ret->user_email;
	}

	// FUNCTION: bool exists($username)
	// Check if a user exists (by username)
	function exists($username) {
		$clean_username = $this->security->xss_clean($username);
		if($this->get($clean_username)->num_rows() == 0) {
			return 0;
		} else {
			return 1;
		}
	}

	// FUNCTION: bool exists_by_id($id)
	// Check if a user exists (by user ID)
	function exists_by_id($id) {
		$clean_id = $this->security->xss_clean($id);

		if($this->get_by_id($clean_id)->num_rows() == 0) {
			return 0;
		} else {
			return 1;
		}
	}

	// FUNCTION: bool exists_by_email($email)
	// Check if a user exists (by email address)
	function exists_by_email($email) {
		if($this->get_by_email($email)->num_rows() == 0) {
			return 0;
		} else {
			return 1;
		}
	}

	// FUNCTION: array search_users($query)
	// Search for users by parts of their callsign
	function search_users($query, $clubstations = false) {
		if (strlen($query) < 2) {
			return false;
		}
		$this->db->select('user_id, user_name, user_callsign, user_firstname, user_lastname');
		if (!$clubstations) {
			$this->db->where('clubstation', 0);
		}

		// if there is a space it's probably a firstname + lastname search
		if (strpos($query, ' ') !== false) {
			$parts = explode(' ', $query, 2);
	
			$this->db->group_start();
			$this->db->like('user_firstname', $parts[0]);
			$this->db->or_like('user_lastname', $parts[0]);
			$this->db->like('user_lastname', $parts[1]);
			$this->db->or_like('user_firstname', $parts[1]);
			$this->db->group_end();
		} else {
			$this->db->group_start();
			$this->db->like('user_callsign', $query);
			$this->db->or_like('user_name', $query);
			$this->db->or_like('user_firstname', $query);
			$this->db->or_like('user_lastname', $query);
			$this->db->group_end();
		}

		$this->db->limit(100);

		$r = $this->db->get($this->config->item('auth_table'));
		return $r;
	}

	// FUNCTION: bool add($username, $password, $email, $type)
	// Add a user
	function add($username, $password, $email, $type, $firstname, $lastname, $callsign, $locator, $timezone,
		$measurement, $dashboard_map, $user_date_format, $user_stylesheet, $user_qth_lookup, $user_sota_lookup, $user_wwff_lookup,
		$user_pota_lookup, $user_show_notes, $user_column1, $user_column2, $user_column3, $user_column4, $user_column5,
		$user_show_profile_image, $user_previous_qsl_type, $user_amsat_status_upload, $user_mastodon_url,
		$user_default_band, $user_default_confirmation, $user_qso_end_times, $user_quicklog, $user_quicklog_enter,
		$user_language, $user_hamsat_key, $user_hamsat_workable_only, $user_iota_to_qso_tab, $user_sota_to_qso_tab,
		$user_wwff_to_qso_tab, $user_pota_to_qso_tab, $user_sig_to_qso_tab, $user_dok_to_qso_tab,
		$user_lotw_name, $user_lotw_password, $user_eqsl_name, $user_eqsl_password, $user_clublog_name, $user_clublog_password,
		$user_winkey, $clubstation = 0) {
		// Check that the user isn't already used
		if(!$this->exists($username)) {
			$data = array(
				'user_name' => xss_clean($username),
				'user_password' => $this->_hash($password),
				'user_email' => xss_clean($email),
				'user_type' => xss_clean($type),
				'user_firstname' => xss_clean($firstname) ?? '',
				'user_lastname' => xss_clean($lastname) ?? '',
				'user_callsign' => strtoupper(xss_clean($callsign)),
				'user_locator' => strtoupper(xss_clean($locator)),
				'user_timezone' => xss_clean($timezone),
				'user_measurement_base' => xss_clean($measurement),
				'user_date_format' => xss_clean($user_date_format),
				'user_stylesheet' => xss_clean($user_stylesheet),
				'user_qth_lookup' => xss_clean($user_qth_lookup),
				'user_sota_lookup' => xss_clean($user_sota_lookup),
				'user_wwff_lookup' => xss_clean($user_wwff_lookup),
				'user_pota_lookup' => xss_clean($user_pota_lookup),
				'user_show_notes' => xss_clean($user_show_notes),
				'user_column1' => xss_clean($user_column1),
				'user_column2' => xss_clean($user_column2),
				'user_column3' => xss_clean($user_column3),
				'user_column4' => xss_clean($user_column4),
				'user_column5' => xss_clean($user_column5),
				'user_show_profile_image' => xss_clean($user_show_profile_image),
				'user_previous_qsl_type' => xss_clean($user_previous_qsl_type),
				'user_amsat_status_upload' => xss_clean($user_amsat_status_upload),
				'user_mastodon_url' => xss_clean($user_mastodon_url),
				'user_default_band' => xss_clean($user_default_band),
				'user_default_confirmation' => xss_clean($user_default_confirmation),
				'user_qso_end_times' => xss_clean($user_qso_end_times),
				'user_quicklog' => xss_clean($user_quicklog),
				'user_quicklog_enter' => xss_clean($user_quicklog_enter),
				'user_language' => xss_clean($user_language),
				'user_lotw_name' => xss_clean($user_lotw_name),
				'user_lotw_password' => xss_clean($user_lotw_password),
				'user_eqsl_name' => xss_clean($user_eqsl_name),
				'user_eqsl_password' => xss_clean($user_eqsl_password),
				'user_clublog_name' => xss_clean($user_clublog_name),
				'user_clublog_password' => xss_clean($user_clublog_password),
				'winkey' => xss_clean($user_winkey),
				'clubstation' => $clubstation,
			);

			// Check the password is valid
			if($data['user_password'] == EPASSWORDINVALID) {
				return EPASSWORDINVALID;
			}

			// Check the email address isn't in use
			if($this->exists_by_email($email)) {
				return EEMAILEXISTS;
			}

			// Add user and insert bandsettings for user
			$this->db->insert($this->config->item('auth_table'), $data);
			$insert_id = $this->db->insert_id();

			$this->db->query("insert into bandxuser (bandid, userid) select bands.id, " . $insert_id . " from bands;");
			$this->db->query("insert into paper_types (user_id,paper_name,metric,width,orientation,height) SELECT ".$insert_id.", paper_name, metric, width, orientation,height FROM paper_types where user_id = 0;");
			$this->db->query("insert into user_options (user_id, option_type, option_name, option_key, option_value) values (" . $insert_id . ", 'map_custom','icon','qso','{\"icon\":\"fas fa-dot-circle\",\"color\":\"#ff0000\"}');");
			$this->db->query("insert into user_options (user_id, option_type, option_name, option_key, option_value) values (" . $insert_id . ", 'map_custom','icon','qsoconfirm','{\"icon\":\"fas fa-dot-circle\",\"color\":\"#00ff00\"}');");
			$this->db->query("insert into user_options (user_id, option_type, option_name, option_key, option_value) values (" . $insert_id . ", 'map_custom','icon','station','{\"icon\":\"fas fa-broadcast-tower\",\"color\":\"#0000ff\"}');");
			$this->db->query("insert into user_options (user_id, option_type, option_name, option_key, option_value) values (" . $insert_id . ", 'map_custom','gridsquare','show','0');");
			$this->db->query("insert into user_options (user_id, option_type, option_name, option_key, option_value) values (" . $insert_id . ", 'hamsat','hamsat_key','api','".xss_clean($user_hamsat_key)."');");
			$this->db->query("insert into user_options (user_id, option_type, option_name, option_key, option_value) values (" . $insert_id . ", 'hamsat','hamsat_key','workable','".xss_clean($user_hamsat_workable_only)."');");
			$this->db->query("insert into user_options (user_id, option_type, option_name, option_key, option_value) values (" . $insert_id . ", 'qso_tab','iota','show',".(xss_clean($user_iota_to_qso_tab ?? 'off') == "on" ? 1 : 0).");");
			$this->db->query("insert into user_options (user_id, option_type, option_name, option_key, option_value) values (" . $insert_id . ", 'qso_tab','sota','show',".(xss_clean($user_sota_to_qso_tab ?? 'off') == "on" ? 1 : 0).");");
			$this->db->query("insert into user_options (user_id, option_type, option_name, option_key, option_value) values (" . $insert_id . ", 'qso_tab','wwff','show',".(xss_clean($user_wwff_to_qso_tab ?? 'off') == "on" ? 1 : 0).");");
			$this->db->query("insert into user_options (user_id, option_type, option_name, option_key, option_value) values (" . $insert_id . ", 'qso_tab','pota','show',".(xss_clean($user_pota_to_qso_tab ?? 'off') == "on" ? 1 : 0).");");
			$this->db->query("insert into user_options (user_id, option_type, option_name, option_key, option_value) values (" . $insert_id . ", 'qso_tab','sig','show',".(xss_clean($user_sig_to_qso_tab ?? 'off') == "on" ? 1 : 0).");");
			$this->db->query("insert into user_options (user_id, option_type, option_name, option_key, option_value) values (" . $insert_id . ", 'qso_tab','dok','show',".(xss_clean($user_dok_to_qso_tab ?? 'off') == "on" ? 1 : 0).");");
			$this->db->query("insert into user_options (user_id, option_type, option_name, option_key, option_value) values (" . $insert_id . ", 'dashboard','show_map','boolean','".xss_clean($fields['user_dashboard_map'] ?? 'Y')."');");

			return OK;
		} else {
			return EUSERNAMEEXISTS;
		}
	}

	// FUNCTION: bool edit()
	// Edit a user
	function edit($fields) {

		// Check user privileges
		if(($this->session->userdata('user_type') == 99) || ($this->session->userdata('user_id') == $fields['id'])) {
			if($this->exists_by_id($fields['id'])) {
				$data = array(
					'user_name' => xss_clean($fields['user_name']),
					'user_email' => xss_clean($fields['user_email']),
					'user_callsign' => strtoupper(xss_clean($fields['user_callsign'])),
					'user_locator' => strtoupper(xss_clean($fields['user_locator'])),
					'user_firstname' => xss_clean($fields['user_firstname']),
					'user_lastname' => xss_clean($fields['user_lastname']),
					'user_timezone' => xss_clean($fields['user_timezone']),
					'user_lotw_name' => xss_clean($fields['user_lotw_name']),
					'user_eqsl_name' => xss_clean($fields['user_eqsl_name']),
					'user_clublog_name' => xss_clean($fields['user_clublog_name']),
					'user_measurement_base' => xss_clean($fields['user_measurement_base']),
					'user_date_format' => xss_clean($fields['user_date_format']),
					'user_stylesheet' => xss_clean($fields['user_stylesheet']),
					'user_qth_lookup' => xss_clean($fields['user_qth_lookup']),
					'user_sota_lookup' => xss_clean($fields['user_sota_lookup']),
					'user_wwff_lookup' => xss_clean($fields['user_wwff_lookup']),
					'user_pota_lookup' => xss_clean($fields['user_pota_lookup']),
					'user_show_notes' => xss_clean($fields['user_show_notes']),
					'user_column1' => xss_clean($fields['user_column1']),
					'user_column2' => xss_clean($fields['user_column2']),
					'user_column3' => xss_clean($fields['user_column3']),
					'user_column4' => xss_clean($fields['user_column4']),
					'user_column5' => xss_clean($fields['user_column5']),
					'user_show_profile_image' => xss_clean($fields['user_show_profile_image']),
					'user_previous_qsl_type' => xss_clean($fields['user_previous_qsl_type']),
					'user_amsat_status_upload' => xss_clean($fields['user_amsat_status_upload']),
					'user_mastodon_url' => xss_clean($fields['user_mastodon_url']),
					'user_default_band' => xss_clean($fields['user_default_band']),
					'user_default_confirmation' => (isset($fields['user_default_confirmation_qsl']) ? 'Q' : '').(isset($fields['user_default_confirmation_lotw']) ? 'L' : '').(isset($fields['user_default_confirmation_eqsl']) ? 'E' : '').(isset($fields['user_default_confirmation_qrz']) ? 'Z' : '').(isset($fields['user_default_confirmation_clublog']) ? 'C' : ''),
					'user_qso_end_times' => xss_clean($fields['user_qso_end_times']),
					'user_quicklog' => xss_clean($fields['user_quicklog']),
					'user_quicklog_enter' => xss_clean($fields['user_quicklog_enter']),
					'user_language' => xss_clean($fields['user_language']),
					'winkey' => xss_clean($fields['user_winkey']),
				);

				// Hard limit safety check for last (recent) QSO count settings
				$dashboard_last_qso_count = xss_clean($fields['user_dashboard_last_qso_count']);
				$dashboard_last_qso_count = $dashboard_last_qso_count > DASHBOARD_QSOS_COUNT_LIMIT ? DASHBOARD_QSOS_COUNT_LIMIT : $dashboard_last_qso_count;
				$qso_page_last_qso_count = xss_clean($fields['user_qso_page_last_qso_count']);
				$qso_page_last_qso_count = $qso_page_last_qso_count > QSO_PAGE_QSOS_COUNT_LIMIT ? QSO_PAGE_QSOS_COUNT_LIMIT : $qso_page_last_qso_count;

				$this->db->query("replace into user_options (user_id, option_type, option_name, option_key, option_value) values (" . $fields['id'] . ", 'hamsat','hamsat_key','api','".xss_clean($fields['user_hamsat_key'])."');");
				$this->db->query("replace into user_options (user_id, option_type, option_name, option_key, option_value) values (" . $fields['id'] . ", 'hamsat','hamsat_key','workable','".xss_clean($fields['user_hamsat_workable_only'])."');");
				$this->db->query("replace into user_options (user_id, option_type, option_name, option_key, option_value) values (" . $fields['id'] . ", 'qso_tab','iota','show',".(xss_clean($fields['user_iota_to_qso_tab'] ?? 'off') == "on" ? 1 : 0).");");
				$this->db->query("replace into user_options (user_id, option_type, option_name, option_key, option_value) values (" . $fields['id'] . ", 'qso_tab','sota','show',".(xss_clean($fields['user_sota_to_qso_tab'] ?? 'off') == "on" ? 1 : 0).");");
				$this->db->query("replace into user_options (user_id, option_type, option_name, option_key, option_value) values (" . $fields['id'] . ", 'qso_tab','wwff','show',".(xss_clean($fields['user_wwff_to_qso_tab'] ?? 'off') == "on" ? 1 : 0).");");
				$this->db->query("replace into user_options (user_id, option_type, option_name, option_key, option_value) values (" . $fields['id'] . ", 'qso_tab','pota','show',".(xss_clean($fields['user_pota_to_qso_tab'] ?? 'off') == "on" ? 1 : 0).");");
				$this->db->query("replace into user_options (user_id, option_type, option_name, option_key, option_value) values (" . $fields['id'] . ", 'qso_tab','sig','show',".(xss_clean($fields['user_sig_to_qso_tab'] ?? 'off') == "on" ? 1 : 0).");");
				$this->db->query("replace into user_options (user_id, option_type, option_name, option_key, option_value) values (" . $fields['id'] . ", 'qso_tab','dok','show',".(xss_clean($fields['user_dok_to_qso_tab'] ?? 'off') == "on" ? 1 : 0).");");
				$this->db->query("replace into user_options (user_id, option_type, option_name, option_key, option_value) values (" . $fields['id'] . ", 'dashboard','last_qso_count','count','".$dashboard_last_qso_count."');");
				$this->db->query("replace into user_options (user_id, option_type, option_name, option_key, option_value) values (" . $fields['id'] . ", 'qso_tab','last_qso_count','count','".$qso_page_last_qso_count."');");
				$this->db->query("replace into user_options (user_id, option_type, option_name, option_key, option_value) values (" . $fields['id'] . ", 'dashboard','show_map','boolean','".xss_clean($fields['user_dashboard_map'] ?? 'Y')."');");
				$this->session->set_userdata('dashboard_last_qso_count', $dashboard_last_qso_count);
				$this->session->set_userdata('qso_page_last_qso_count', $qso_page_last_qso_count);				
				$this->session->set_userdata('user_dashboard_map',xss_clean($fields['user_dashboard_map'] ?? 'Y'));

				// Check to see if the user is allowed to change user levels
				if($this->session->userdata('user_type') == 99) {
					$data['user_type'] = $fields['user_type'];
				}

				// Check to see if username is used already
				if($this->exists($fields['user_name']) && $this->get($fields['user_name'])->row()->user_id != $fields['id']) {
					return EUSERNAMEEXISTS;
				}
				// Check to see if email address is used already
				if($this->exists_by_email($fields['user_email']) && $this->get_by_email($fields['user_email'])->row()->user_id != $fields['id']) {
					return EEMAILEXISTS;
				}

				$pwd_placeholder = '**********';

				// Hash password
				if($fields['user_password'] != NULL)
				{
					if (!file_exists('.demo') || (file_exists('.demo') && $this->session->userdata('user_type') == 99)) {

						if ($fields['user_password'] !== $pwd_placeholder) {
							$decoded_password = htmlspecialchars_decode($fields['user_password']);
							$data['user_password'] = $this->_hash($decoded_password);
							if($data['user_password'] == EPASSWORDINVALID) {
								return EPASSWORDINVALID;
							}
							$data['login_attempts'] = 0;
						}
					}
				}

				if($fields['user_lotw_password'] != '')
				{
					if ($fields['user_lotw_password'] !== $pwd_placeholder) {
						$data['user_lotw_password'] = $fields['user_lotw_password'];
					}
				} else {
					$data['user_lotw_password'] = NULL;
				}

				if($fields['user_clublog_password'] != '')
				{
					if ($fields['user_clublog_password'] !== $pwd_placeholder) {
						$data['user_clublog_password'] = $fields['user_clublog_password'];
					}
				} else {
					$data['user_clublog_password'] = NULL;
				}

				if($fields['user_eqsl_password'] != '')
				{
					if ($fields['user_eqsl_password'] !== $pwd_placeholder) {
						$data['user_eqsl_password'] = $fields['user_eqsl_password'];
					}
				} else {
					$data['user_eqsl_password'] = NULL;
				}

				// Update the user
				$this->db->where('user_id', $fields['id']);
				$this->db->update($this->config->item('auth_table'), $data);

				// Remove static map images in cache to make sure they are updated
				$this->load->model('Stations');
				$this->load->model('staticmap_model');
				$stations = $this->Stations->all_station_ids_of_user($fields['id']);
				$station_ids = explode(',', $stations);
				foreach ($station_ids as $station_id) {
					$this->staticmap_model->remove_static_map_image(trim($station_id));
				}

				return OK;
			} else {
				return ENOSUCHUSER;
			}
		} else {
			return EFORBIDDEN;
		}
	}

	// FUNCTION: bool delete()
	// Deletes a user
	function delete($user_id) {
		if($this->exists_by_id($user_id)) {
			$this->load->model('Stations');
			$stations = $this->Stations->all_of_user($user_id);
			foreach ($stations->result() as $row) {
				$this->Stations->delete($row->station_id,true, $user_id);
			}
			// Delete QSOs from $this->config->item('table_name')
			$this->db->query("DELETE FROM bandxuser WHERE userid = ?",$user_id);
			$this->db->query("DELETE FROM api WHERE user_id = ? OR created_by = ?", [$user_id, $user_id]);
			$this->db->query("DELETE FROM club_permissions WHERE user_id = ? OR club_id = ?", [$user_id, $user_id]);
			$this->db->query("DELETE FROM cat WHERE user_id = ?",$user_id);
			$this->db->query("DELETE FROM lotw_certs WHERE user_id = ?",$user_id);
			$this->db->query("DELETE FROM notes WHERE user_id = ?",$user_id);
			$this->db->query("DELETE FROM paper_types WHERE user_id = ?",$user_id);
			$this->db->query("DELETE FROM label_types WHERE user_id = ?",$user_id);
			$this->db->query("DELETE FROM queries WHERE userid = ?",$user_id);
			$this->db->query("DELETE FROM station_profile WHERE user_id = ?",$user_id);
			$this->db->query("DELETE FROM station_logbooks WHERE user_id = ?",$user_id);
			$this->db->query("DELETE FROM user_options WHERE user_id=?",$user_id);
			$this->db->query("DELETE FROM ".$this->config->item('auth_table')." WHERE user_id = ?",$user_id);
			return 1;
		} else {
			return 0;
		}
	}

	// FUNCTION: bool login()
	// Validates a username/password combination
	// This is really just a wrapper around User_Model::authenticate
	function login() {

		$username = $this->input->post('user_name', true);
		$password = htmlspecialchars_decode($this->input->post('user_password', true));

		return $this->authenticate($username, $password);
	}

	// FUNCTION: void clear_session()
	// Clears a user's login session
	// Nothing is returned - it can be assumed that if this is called, the user's
	// login session *will* be cleared, no matter what state it is in
	function clear_session() {

		$this->session->sess_destroy();
	}

	// FUNCTION: void update_session()
	// Updates a user's login session after they've logged in
	// TODO: This should return bool TRUE/FALSE or 0/1
	function update_session($id, $u = null, $impersonate = false, $custom_data = null) {

		if ($u == null) {
			$u = $this->get_by_id($id);
		}

		$userdata = array(
			'user_id'		 => $u->row()->user_id,
			'user_name'		 => $u->row()->user_name,
			'user_type'		 => $u->row()->user_type,
			'user_callsign'		 => $u->row()->user_callsign,
			'operator_callsign'	 => ((($this->session->userdata('operator_callsign') ?? '') == '') ? $u->row()->user_callsign : $this->session->userdata('operator_callsign')),
			'user_locator'		 => $u->row()->user_locator,
			'user_lotw_name'	 => $u->row()->user_lotw_name,
			'user_clublog_name'	 => $u->row()->user_clublog_name ?? '',
			'user_eqsl_name'	 => $u->row()->user_eqsl_name,
			'user_eqsl_qth_nickname' => $u->row()->user_eqsl_qth_nickname,
			'user_hash'		 => $this->_hash($u->row()->user_id."-".$u->row()->user_type),
			'radio' => ((($this->session->userdata('radio') ?? '') == '') ? $this->user_options_model->get_options('cat', array('option_name' => 'default_radio'))->row()->option_value ?? '' : $this->session->userdata('radio')),
			'station_profile_id' => $this->session->userdata('station_profile_id') ?? '',
			'user_measurement_base' => $u->row()->user_measurement_base,
			'user_dashboard_map' => ((($this->session->userdata('user_dashboard_map') ?? 'Y') == 'Y') ? $this->user_options_model->get_options('dashboard', array('option_name' => 'show_map', 'option_key' => 'boolean'))->row()->option_value ?? 'Y' : $this->session->userdata('user_dashboard_map')),
			'user_date_format' => $u->row()->user_date_format,
			'user_stylesheet' => $u->row()->user_stylesheet,
			'user_qth_lookup' => isset($u->row()->user_qth_lookup) ? $u->row()->user_qth_lookup : 0,
			'user_sota_lookup' => isset($u->row()->user_sota_lookup) ? $u->row()->user_sota_lookup : 0,
			'user_wwff_lookup' => isset($u->row()->user_wwff_lookup) ? $u->row()->user_wwff_lookup : 0,
			'user_pota_lookup' => isset($u->row()->user_pota_lookup) ? $u->row()->user_pota_lookup : 0,
			'user_show_notes' => isset($u->row()->user_show_notes) ? $u->row()->user_show_notes : 1,
			'user_show_profile_image' => isset($u->row()->user_show_profile_image) ? $u->row()->user_show_profile_image : 0,
			'user_column1' => isset($u->row()->user_column1) ? $u->row()->user_column1: 'Mode',
			'user_column2' => isset($u->row()->user_column2) ? $u->row()->user_column2: 'RSTS',
			'user_column3' => isset($u->row()->user_column3) ? $u->row()->user_column3: 'RSTR',
			'user_column4' => isset($u->row()->user_column4) ? $u->row()->user_column4: 'Band',
			'user_column5' => isset($u->row()->user_column5) ? $u->row()->user_column5: 'Country',
			'user_previous_qsl_type' => isset($u->row()->user_previous_qsl_type) ? $u->row()->user_previous_qsl_type: 0,
			'user_amsat_status_upload' => isset($u->row()->user_amsat_status_upload) ? $u->row()->user_amsat_status_upload: 0,
			'user_mastodon_url'	 => $u->row()->user_mastodon_url,
			'user_default_band'	 => $u->row()->user_default_band,
			'user_default_confirmation'	 => $u->row()->user_default_confirmation,
			'user_qso_end_times' => isset($u->row()->user_qso_end_times) ? $u->row()->user_qso_end_times : 1,
			'user_quicklog' => isset($u->row()->user_quicklog) ? $u->row()->user_quicklog : 1,
			'user_quicklog_enter' => isset($u->row()->user_quicklog_enter) ? $u->row()->user_quicklog_enter : 1,
			'active_station_logbook' => $u->row()->active_station_logbook,
			'user_language' => isset($u->row()->user_language) ? $u->row()->user_language: 'english',
			'isWinkeyEnabled' => $u->row()->winkey,
			'FirstLoginWizard' => ((($this->session->userdata('FirstLoginWizard') ?? '') == '') ? ($this->user_options_model->get_options('FirstLoginWizard', 'shown')->row()->option_value ?? null) : $this->session->userdata('FirstLoginWizard')),
			'hasQrzKey' => $this->hasQrzKey($u->row()->user_id),
			'impersonate' => $this->session->userdata('impersonate') ?? false,
			'clubstation' => $u->row()->clubstation,
			'dashboard_last_qso_count' => ($this->session->userdata('dashboard_last_qso_count') ?? '') == '' ? ($this->user_options_model->get_options('dashboard', array('option_name' => 'last_qso_count', 'option_key' => 'count'))->row()->option_value ?? '') : $this->session->userdata('dashboard_last_qso_count'),
			'qso_page_last_qso_count' => ($this->session->userdata('qso_page_last_qso_count') ?? '') == '' ? ($this->user_options_model->get_options('qso_tab', array('option_name' => 'last_qso_count', 'option_key' => 'count'))->row()->option_value ?? '') : $this->session->userdata('qso_page_last_qso_count'),
			'source_uid' => $this->session->userdata('source_uid') ?? ''
		);

		if ($this->config->item('special_callsign')) {
			$userdata['available_clubstations'] = $this->get_clubstations($u->row()->user_id) ?? 'none';
		}

		foreach (array_keys($this->frequency->defaultFrequencies) as $band) {
			$qrg_unit = $this->session->userdata("qrgunit_$band") ?? ($this->user_options_model->get_options('frequency', array('option_name' => 'unit', 'option_key' => $band), $u->row()->user_id)->row()->option_value ?? '');
			if ($qrg_unit !== '') {
				$userdata['qrgunit_'.$band] = $qrg_unit;
			} else {
				$userdata['qrgunit_'.$band] = $this->frequency->defaultFrequencies[$band]['UNIT'];
			}
		}

		// Restore custom data in impersonation mode
		foreach ($this->session->userdata() as $key => $value) {
			if (substr($key, 0, 3) == 'cd_') {
				$userdata[$key] = $value;
			}
		}

		// Overrides
		if ($impersonate) {
			$userdata['impersonate'] = true;
			$userdata['available_clubstations'] = $this->get_clubstations($u->row()->user_id);
		}
		if ($userdata['clubstation'] == 1) {
			$userdata['available_clubstations'] = 'none';
		}
		if (isset($custom_data)) {
			foreach ($custom_data as $key => $value) {
				$userdata['cd_' . $key] = $value;
			}
		}

		$this->session->set_userdata($userdata);
	}

	// FUNCTION: bool validate_session()
	// Validate a user's login session
	// If the user's session is corrupted in any way, it will clear the session
	function validate_session($u = null) {

		if($this->session->userdata('user_id'))
		{
			$user_id = $this->session->userdata('user_id');
			$user_type = $this->session->userdata('user_type');
			$src_user_type = $this->session->userdata('cd_src_user_type');
			$user_hash = $this->session->userdata('user_hash');
			$impersonate = $this->session->userdata('impersonate');

			if(ENVIRONMENT != 'maintenance') {
				if($this->_auth($user_id."-".$user_type, $user_hash)) {
					// Freshen the session
					$this->update_session($user_id, $u);
					return 1;
				} else {
					$this->clear_session();
					return 0;
				}
			} else {  // handle the maintenance mode and kick out user on page reload if not an admin
				if($user_type == '99' || $src_user_type === '99') {
					if($this->_auth($user_id."-".$user_type, $user_hash)) {
						// Freshen the session
						$this->update_session($user_id, $u);
						return 1;
					} else {
						$this->clear_session();
						return 0;
					}
				} else {
					$this->clear_session();
					return 0;
				}
			}
		} else {
			return 0;
		}
	}

	// FUNCTION: bool authenticate($username, $password)
	// Authenticate a user against the users table
	function authenticate($username, $password) {
		$u = $this->get($username);
		if($u->num_rows() != 0) {
			// direct login to clubstations are not allowed
			if ($u->row()->clubstation == 1) {
				$uid = $u->row()->user_id;
				log_message('debug', "User ID: [$uid] Login rejected because of a external clubstation login attempt.");
				return 2;
			}

			if ($this->config->item('max_login_attempts')) {
				$maxattempts = $this->config->item('max_login_attempts');
			} else {
				$maxattempts = 3;
			}
			if ($u->row()->login_attempts > $maxattempts) {
				$uid = $u->row()->user_id;
				log_message('debug', "User ID: [$uid] Login rejected because of too many failed login attempts.");
				return 3;
			}

			if($this->_auth($password, $u->row()->user_password)) {
				$this->db->query("UPDATE users SET login_attempts = 0 WHERE user_id = ?", [$u->row()->user_id]);	// Reset failurecount
				if (ENVIRONMENT != "maintenance") {
					return 1;
				} else {
					if($u->row()->user_type != 99){
						return 0;
					} else {
						return 1;
					}
				}
			} else { // Update failurecount
				$this->db->query("UPDATE users SET login_attempts = login_attempts+1 WHERE user_id = ?", [$u->row()->user_id]);
			}
		}
		return 0;
	}

	// FUNCTION: set's the last-login timestamp in user table
	function set_last_seen($user_id) {
		$data = array(
			'last_seen' => date('Y-m-d H:i:s')
		);

		$this->db->where('user_id', $user_id);
		$this->db->update('users', $data);
	}

	// FUNCTION: bool authorize($level)
	// Checks a user's level of access against the given $level
	function authorize($level) {
		$u = $this->get_by_id($this->session->userdata('user_id'));
		$l = $this->config->item('auth_mode');
		// Check to see if the minimum level of access is higher than
		// the user's own level. If it is, use that.
		if($this->config->item('auth_mode') > $level) {
			$level = $this->config->item('auth_mode');
		}
		if(($this->validate_session($u)) && ($u->row()->user_type >= $level) || $this->config->item('use_auth') == FALSE || $level == 0) {
			$ls = strtotime($u->row()->last_seen ?? '1970-01-01');
			$n = time();
			if (($n - $ls) > 60) {	// Reduce load. 'set_last_seen()' Shouldn't be called at anytime. 60 seconds diff is enough.

				$this->set_last_seen($u->row()->user_id);
			}
				return 1;
		} else {
			return 0;
		}
	}

	// FUNCTION: bool unlock($user_id)
	// Unlocks a user account after it was locked doe too many failed login attempts
	function unlock($user_id) {
		return $this->db->query("UPDATE users SET login_attempts = 0 WHERE user_id = ?", [$user_id]);
	}

	// FUNCTION: object users()
	// Returns a list of users with additional counts
	function users($club = '') {
		$qsocount_select = "";
		$qsocount_join = "";
		if (!($this->config->item('disable_user_stats') ?? false)) {
			$qsocount_select = ", COALESCE(lc.qsocount, 0) AS qsocount, lc.lastqso";
			$qsocount_join = 
				" LEFT JOIN (
					SELECT sp.user_id, 
						COUNT(l.col_primary_key) AS qsocount,
						MAX(l.COL_TIME_ON)      AS lastqso
					FROM station_profile sp
					JOIN " . $this->config->item('table_name') . " l ON l.station_id = sp.station_id
					GROUP BY sp.user_id
				) lc ON lc.user_id = u.user_id";
		}
		$sql = "SELECT 
					u.user_id,
					u.user_name,
					u.user_firstname,
					u.user_lastname,
					u.user_callsign,
					u.user_email,
					u.user_type,
					u.last_seen,
					u.login_attempts,
					u.clubstation,
					COALESCE(sp_count.stationcount, 0)    	AS stationcount,
					COALESCE(sl_count.logbookcount, 0)   	AS logbookcount
					".$qsocount_select."
				FROM users u
				LEFT JOIN (
					SELECT user_id, COUNT(*) AS stationcount
					FROM station_profile
					GROUP BY user_id
				) sp_count ON sp_count.user_id = u.user_id
				LEFT JOIN (
					SELECT user_id, COUNT(*) AS logbookcount
					FROM station_logbooks
					GROUP BY user_id
				) sl_count ON sl_count.user_id = u.user_id"
				 .$qsocount_join;

		if ($this->config->item('special_callsign')) {
			if ($club === 'is_club') {
				$sql .= " WHERE u.clubstation = 1";
			} else {
				$sql .= " WHERE u.clubstation != 1";
			}
		}

		$result = $this->db->query($sql);
		if ($this->config->item('special_callsign')) {
			if ($club === 'is_club' && !($this->config->item('disable_user_stats') ?? false)) {
				foreach ($result->result() as &$row) {
					$row->lastoperator = $this->get_last_op($row->user_id, $row->lastqso);
				}
			} else {
				foreach ($result->result() as &$row) {
					$row->lastoperator = '';   				// Important: If 'disable_user_stats' is set to true, the admin won't see the last operator of a clubstation
				}
			}
		}
		return $result;
	}

	function get_last_op($userid,$lastqso) {
		$sql="SELECT log.COL_OPERATOR FROM ". $this->config->item('table_name') ." log INNER JOIN station_profile sp ON (log.station_id=sp.station_id) where sp.user_id=? AND col_time_on=? ORDER BY col_time_on DESC LIMIT 1";
		$resu=$this->db->query($sql,array($userid,$lastqso));
		return $resu->result()[0]->COL_OPERATOR ?? '';
	}

	// FUNCTION: array timezones()
	// Returns a list of timezones
	function timezones() {
		$r = $this->db->query('SELECT id, name FROM timezones ORDER BY `offset`');
		$ts = array();
		foreach ($r->result_array() as $t) {
			$ts[$t['id']] = $t['name'];
		}
		return $ts;
	}

	// FUNCTION: array getThemes()
	// Returns a list of themes
	function getThemes() {
		$result = $this->db->query('SELECT * FROM themes order by name');

		return $result->result();
	}

	/*
	 * FUNCTION: set_password_reset_code
	 *
	 * Stores generated password reset code in the database and sets the date to exactly
	 * when the sql query runs.
	 *
	 * @param string $user_email
	 * @return string $reset_code
	 */
	function set_password_reset_code($user_email, $reset_code) {
		$data = array(
			'reset_password_code' => $reset_code,
			'reset_password_date' => date('Y-m-d H:i:s')
		);

		$this->db->where('user_email', $user_email);
		$this->db->update('users', $data);
	}

	/*
	 * FUNCTION: reset_password
	 *
	 * Sets new password for users account where the reset code matches then clears the password reset code and password reset date.
	 *
	 * @param string $password
	 * @return string $reset_code
	 */
	function reset_password($password, $reset_code) {
		$data = array(
			'user_password' => $this->_hash($password),
			'reset_password_code' => NULL,
			'reset_password_date' => NULL,
			'login_attempts' => 0
		);

		$this->db->where('reset_password_code', $reset_code);
		$this->db->update('users', $data);
	}

	// FUNCTION: bool _auth($password, $hash)
	// Checks a password against the stored hash
	private function _auth($password, $hash) {
		if(password_verify($password, $hash)) {
			return 1;
		} else {
			return 0;
		}
	}

	// FUNCTION: string _hash($password)
	// Returns a hashed version of the supplied $password
	// Will return '0' in the event of problems with the
	// hashing function
	private function _hash($password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

		if(strlen($hash) < 20) {
			return EPASSWORDINVALID;
		} else {
			return $hash;
		}
	}

	/**
	 * Function to create a safe hash, which can be securely stored in the browser
	 * to keep a user logged in for a defined time range.
	 */
	function keep_cookie_hash($user_id) {

		/**
		 * get some client information, to include in the hash we want to make a has unique for a certain browser
		 */

		// Client Browser and OS
		$client_browser = base64_encode($_SERVER['HTTP_USER_AGENT']);

		// Client language
		$client_lang = base64_encode($_SERVER['HTTP_ACCEPT_LANGUAGE']);

		$uid = base64_encode($user_id);

		// Create a long string out of the client data
		$client_string = $client_browser . $client_lang . $uid;

		// Now we load the Encryption Lib
		if (!$this->load->is_loaded('encryption')) {
			$this->load->library('encryption');
		}

		// And creating a secure hash of the client data
		$encrypted_string = $this->encryption->encrypt($client_string);
		$hash = $encrypted_string . base64_encode($this->config->item('base_url')) . base64_encode($user_id);

		return $hash;

	}

	function check_keep_hash($a, $b) {

		// Load the Encryption Lib
		if (!$this->load->is_loaded('encryption')) {
			$this->load->library('encryption');
		}

		// Decrypt string a
		$dec_a = $this->encryption->decrypt($a);

		// Decrypt string b
		$dec_b = $this->encryption->decrypt($b);

		if ($dec_a === $dec_b) {
			return true;
		} else {
			return false;
		}
	}

	function get_clubstations($user_id) {
		$this->load->model('club_model');
		$clubstations = $this->club_model->get_clubstations($user_id);

		return $clubstations;
	}

	function convert($user_id, $clubstation) {
		$sql = "UPDATE users SET clubstation = ? WHERE user_id = ?;";
	
		$this->db->trans_start();
	
		if (!$this->db->query($sql, [$clubstation, $user_id])) {
			$this->db->trans_rollback();
			return false;
		}
	
		// Remove all club permissions in case there is a club with this user id
		$delete_sql = "DELETE FROM club_permissions WHERE club_id = ?;";
		if (!$this->db->query($delete_sql, [$user_id])) {
			$this->db->trans_rollback();
			return false;
		}
	
		$this->db->trans_complete();
	
		return $this->db->trans_status();
	}

	function firstlogin_wizard($stationdata) {
		if (empty($stationdata)) {
			$this->user_options_model->set_option('FirstLoginWizard', 'showed',  array('boolean' => 1));  // We try to setup the station only once, so we set the user_option to 1 to prevent the wizard from showing up again
			return false;
		}

		try {
			$this->db->query("INSERT INTO station_logbooks (user_id, logbook_name, modified, public_slug, public_search) 
				VALUES (?, 'Home Logbook', NULL, NULL, 0)", [$stationdata['user_id']]
			);
			$station_logbooks_insert_id = $this->db->insert_id();

			$this->db->query("UPDATE users 
				SET active_station_logbook = ? 
				WHERE user_id = ?", [$station_logbooks_insert_id, $stationdata['user_id']]
			);

			$this->load->model('logbook_model');

			$this->db->query("INSERT INTO station_profile (
					station_profile_name, station_gridsquare, station_city, station_iota, station_sota, station_callsign, station_power,
					station_dxcc, station_cnty, station_cq, station_itu, station_active, eqslqthnickname, state, qrzapikey, county,
					station_sig, station_sig_info, qrzrealtime, user_id, station_wwff, station_pota, oqrs, oqrs_text, oqrs_email,
					webadifapikey, webadifapiurl, webadifrealtime, clublogignore, clublogrealtime, hrdlogrealtime, hrdlog_code, hrdlog_username
				) VALUES (
					?, ?, '', '', '', ?, NULL, ?, '', ?, ?, 1, '', '', '', '', '', '', 0, ?, '', '', 0, '', 0, '', 
					'https://qo100dx.club/api', 0, 0, 0, 0, '', ''
				)", [
					$stationdata['station_name'],
					strtoupper($stationdata['station_locator']),
					strtoupper($stationdata['station_callsign']),
					$stationdata['station_dxcc'],
					$stationdata['station_cqz'],
					$stationdata['station_ituz'],
					$stationdata['user_id']
				]
			);
			$station_profile_insert_id = $this->db->insert_id();

			$this->db->query("INSERT INTO station_logbooks_relationship (station_logbook_id, station_location_id, modified) 
				VALUES (?, ?, NULL)", [$station_logbooks_insert_id, $station_profile_insert_id]
			);

			$this->user_options_model->set_option('FirstLoginWizard', 'showed',  array('boolean' => 1));

			return true;

		} catch (Exception $e) {
			log_message('error', 'Firstlogin wizard failed: ' . $e->getMessage());
			$this->user_options_model->set_option('FirstLoginWizard', 'showed',  array('boolean' => 1));  // We try to setup the station only once, so we set the user_option to 1 to prevent the wizard from showing up again
			return false;
		}
	}

}

?>
