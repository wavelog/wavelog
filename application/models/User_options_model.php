<?php

class User_options_model extends CI_Model {

	public function set_option($option_type, $option_name, $option_array, $uid = null) {
		if (empty($uid)) {
			$uid = $this->session->userdata('user_id');
		}
		$sql = 'INSERT INTO user_options (user_id,option_type,option_name,option_key,option_value) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE option_value = ?';
		foreach ($option_array as $option_key => $option_value) {
			$query = $this->db->query($sql, array($uid, $option_type, $option_name, $option_key, $option_value, $option_value));
		}
		return $query;
	}

	public function set_option_at_all_users($option_type, $option_name, $option_array) {
		$query = $this->db->select('user_id')->get('users');
		if ($query->num_rows() > 0) {
			foreach ($query->result() as $row) {
				$user_id = $row->user_id;
				$sql = 'INSERT INTO user_options (user_id, option_type, option_name, option_key, option_value) VALUES (?, ?, ?, ?, ?)
						ON DUPLICATE KEY UPDATE option_value = ?';
				foreach ($option_array as $option_key => $option_value) {
					$this->db->query($sql, array($user_id, $option_type, $option_name, $option_key, $option_value, $option_value));
				}
			}
			return true;
		} else {
			log_message('error', 'set_option_at_all_users() failed because users table is empty');
		}
	}

	public function get_options($option_type, $option_array = null, $uid = null) {
		if (empty($uid)) {
			$uid = $this->session->userdata('user_id');
		}
		$sql_more = "";
		$array_sql_value = array($uid, $option_type);
		if (is_array($option_array)) {
			foreach ($option_array as $key => $value) {
				$sql_more .= ' AND ' . $key . ' = ?';
				$array_sql_value[] = $value;
			}
		}
		$sql = 'SELECT option_name, option_key, option_value FROM user_options WHERE user_id = ? AND option_type = ?' . $sql_more;
		return $this->db->query($sql, $array_sql_value);
	}

	public function get_all_options_for_user($uid = null) {
		if (empty($uid)) {
			$uid = $this->session->userdata('user_id');
		}
		$sql = 'SELECT option_type, option_name, option_key, option_value FROM user_options WHERE user_id = ?';
		$rows = $this->db->query($sql, array($uid))->result_array();

		$options = array();
		foreach ($rows as $r) {
			// index by type -> name -> key; first row per (type,name,key) wins,
			// matching the previous get_options()->row() behaviour
			$options[$r['option_type']][$r['option_name']][$r['option_key']] = $r['option_value'];
		}
		return $options;
	}

	public function del_option($option_type, $option_name, $option_array = null, $uid = null) {
		if (empty($uid)) {
			$uid = $this->session->userdata('user_id');
		}

		$sql_more = "";
		$array_sql_value = array($uid, $option_type, $option_name);
		if (is_array($option_array)) {
			foreach ($option_array as $key => $value) {
				$sql_more .= ' AND ' . $key . ' = ?';
				$array_sql_value[] = $value;
			}
		}
		$sql = 'DELETE FROM user_options WHERE user_id = ? AND option_type = ? AND option_name = ?' . $sql_more;
		return $this->db->query($sql, $array_sql_value);
	}
}
