<?php

class Note extends CI_Model {
	// Get list of possible note categories with translations
	public static function get_possible_categories() {
		return [
			'Contacts' => __("Contacts"), // QSO partner notes
			'General' => __("General"),   // General notes
			'Antennas' => __("Antennas"), // Antenna-related notes
			'Satellites' => __("Satellites") // Satellite-related notes
		];
	}

	// Get list of possible note category keys (for backwards compatibility)
	public static function get_possible_category_keys() {
		return array_keys(self::get_possible_categories());
	}

	// Return note ID for given category and title (callsign for Contacts), else false
	public function get_note_id_by_category($user_id, $category, $title) {
		$check_title = $title;
		if ($category === 'Contacts') {
			$this->load->library('callbook'); // Used for callsign parsing
			$check_title = strtoupper($this->callbook->get_plaincall($title));
			$check_title_slashed = str_replace('0', 'Ø', $check_title);
		}
		$sql = "SELECT id FROM notes WHERE cat = ? AND user_id = ? AND (title = ? OR title = ?) LIMIT 1";
		$query = $this->db->query($sql, array($category, $user_id, $check_title, $check_title_slashed));
		if ($query->num_rows() > 0) {
			return $query->row()->id;
		}
		return false;
	}

	// List all notes for a user or API key
	function list_all($api_key = null) {
		// Determine user ID
		if ($api_key == null) {
			$user_id = $this->session->userdata('user_id');
		} else {
			$this->load->model('api_model');
			if (strpos($this->api_model->access($api_key), 'r') !== false) {
				$this->api_model->update_last_used($api_key);
				$user_id = $this->api_model->key_userid($api_key);
			}
		}
		$sql = "SELECT * FROM notes WHERE user_id = ?";
		return $this->db->query($sql, array($user_id));
	}

	// Add a new note for the logged-in user
	function add($category, $title, $content, $local_time = null) {
		$user_id = $this->session->userdata('user_id');
		$check_title = $title;
		if ($category === 'Contacts') {
			$check_title = trim(strtoupper($title));
			$title = str_replace('0', 'Ø', $check_title);
		}
		// Check for existing note with same title in Contacts category
		if ($this->get_note_id_by_category($user_id, $category, $check_title) && $category === 'Contacts') {
			show_error(__("In Contacts category, the titles of the notes need to be unique."));
			return;
		}
		$creation_date_utc = gmdate('Y-m-d H:i:s');
		if ($local_time) {
			$dt = new DateTime($local_time, new DateTimeZone(date_default_timezone_get()));
			$dt->setTimezone(new DateTimeZone('UTC'));
			$creation_date_utc = $dt->format('Y-m-d H:i:s');
		}
		$sql = "INSERT INTO notes (cat, title, note, user_id, creation_date, last_modified) VALUES (?, ?, ?, ?, ?, ?)";
		$this->db->query($sql, array($category, $title, $content, $user_id, $creation_date_utc, $creation_date_utc));
	}

	// Edit an existing note for the logged-in user
	function edit($note_id, $category, $title, $content, $local_time = null) {
		$user_id = $this->session->userdata('user_id');
		$check_title = $title;

		if($this->belongs_to_user($note_id, $user_id) === false) {
			show_404();
			return;
		}

		if ($category === 'Contacts') {
			$check_title = trim(strtoupper($title));
			$title = str_replace('0', 'Ø', $check_title);
		}
		// Check for existing note with same title in Contacts category
		$existing_id = $this->get_note_id_by_category($user_id, $category, $check_title);
		if ($existing_id > 0 && $existing_id != $note_id && $category === 'Contacts') {
			show_error(__("In Contacts category, the titles of the notes need to be unique."));
			return;
		}

		$last_modified_utc = gmdate('Y-m-d H:i:s');
		if ($local_time) {
			$dt = new DateTime($local_time, new DateTimeZone(date_default_timezone_get()));
			$dt->setTimezone(new DateTimeZone('UTC'));
			$last_modified_utc = $dt->format('Y-m-d H:i:s');
		}
		$sql = "UPDATE notes SET cat = ?, title = ?, note = ?, last_modified = ? WHERE id = ? AND user_id = ?";
		$this->db->query($sql, array($category, $title, $content, $last_modified_utc, $note_id, $user_id));
	}

	// Delete a note by ID for the logged-in user
	function delete($id) {
		$clean_id = $this->security->xss_clean($id);
		if (!is_numeric($clean_id)) {
			show_404();
		}
		$sql = "DELETE FROM notes WHERE id = ? AND user_id = ?";
		$this->db->query($sql, array($clean_id, $this->session->userdata('user_id')));
	}

	// View a note by ID for the logged-in user
	function view($id) {
		$clean_id = $this->security->xss_clean($id);
		if (!is_numeric($clean_id)) {
			show_404();
		}
		$sql = "SELECT * FROM notes WHERE id = ? AND user_id = ?";
		return $this->db->query($sql, array($clean_id, $this->session->userdata('user_id')));
	}

	// Check if note belongs to a user
	public function belongs_to_user($note_id, $user_id) {
		$sql = "SELECT COUNT(*) as count FROM notes WHERE id = ? AND user_id = ?";
		$query = $this->db->query($sql, array($note_id, $user_id));
		return $query->row()->count > 0;
	}

	// Search notes by category and/or text for the logged-in user
	public function search($criteria = []) {
		$user_id = $this->session->userdata('user_id');
		$params = array($user_id);
		$sql = "SELECT * FROM notes WHERE user_id = ?";

		// Filter by category
		if (!empty($criteria['cat'])) {
			$cats = array_map('trim', explode(',', $criteria['cat']));
			if (count($cats) > 0) {
				$placeholders = str_repeat('?,', count($cats) - 1) . '?';
				$sql .= " AND cat IN ($placeholders)";
				$params = array_merge($params, $cats);
			}
		}

		// Filter by search term (title or note)
		if (!empty($criteria['search'])) {
			$search = '%' . $criteria['search'] . '%';
			$sql .= " AND (title LIKE ? OR note LIKE ?)";
			$params[] = $search;
			$params[] = $search;
		}

		$query = $this->db->query($sql, $params);
		return $query->result();
	}

	// Count notes by category for the logged-in user
	public function count_by_category($category = null) {
		$user_id = $this->session->userdata('user_id');
		$params = array($user_id);
		$sql = "SELECT COUNT(*) as count FROM notes WHERE user_id = ?";

		if ($category !== null) {
			$sql .= " AND cat = ?";
			$params[] = $category;
		}

		$query = $this->db->query($sql, $params);
		return $query->row()->count;
	}

	// Get categories with their respective note counts for the logged-in user
	public function get_categories_with_counts() {
		$user_id = $this->session->userdata('user_id');
		$sql = "SELECT cat, COUNT(*) as count FROM notes WHERE user_id = ? GROUP BY cat";
		$query = $this->db->query($sql, array($user_id));
		$result = [];
		foreach ($query->result() as $row) {
			$result[$row->cat] = (int)$row->count;
		}
		return $result;
	}

	// Search notes with pagination and sorting for the logged-in user
	public function search_paginated($criteria = [], $page = 1, $per_page = 25, $sort_col = null, $sort_dir = null) {
		$page = max(1, intval($page));
		$per_page = max(1, min(100, intval($per_page))); // Enforce max limit

		$user_id = $this->session->userdata('user_id');
		$params = array($user_id);
		$where_clause = "WHERE user_id = ?";

		// Filter by category
		if (!empty($criteria['cat'])) {
			$cats = array_map('trim', explode(',', $criteria['cat']));
			if (count($cats) > 0) {
				$placeholders = str_repeat('?,', count($cats) - 1) . '?';
				$where_clause .= " AND cat IN ($placeholders)";
				$params = array_merge($params, $cats);
			}
		}

		// Filter by search term (title or note)
		if (!empty($criteria['search'])) {
			$search = '%' . $criteria['search'] . '%';
			$where_clause .= " AND (title LIKE ? OR note LIKE ?)";
			$params[] = $search;
			$params[] = $search;
		}

		// Get total count
		$count_sql = "SELECT COUNT(*) as count FROM notes $where_clause";
		$count_query = $this->db->query($count_sql, $params);
		$total = $count_query->row()->count;

		// Build main query with sorting
		$sql = "SELECT id, cat, title, note, creation_date, last_modified FROM notes $where_clause";

		// Sorting - use strict array key mapping to prevent SQL injection
		$allowed_columns = [
			'cat' => 'cat',
			'title' => 'title',
			'creation_date' => 'creation_date',
			'last_modified' => 'last_modified'
		];
		$sort_column = isset($allowed_columns[$sort_col]) ? $allowed_columns[$sort_col] : 'creation_date';
		$sort_direction = ($sort_dir === 'desc') ? 'DESC' : 'ASC';
		$sql .= " ORDER BY $sort_column $sort_direction";

		$offset = ($page - 1) * $per_page;
		$limit = intval($per_page);
		$offset_val = intval($offset);
		$sql .= " LIMIT $limit OFFSET $offset_val";

		$query = $this->db->query($sql, $params);
		$notes = [];
		foreach ($query->result() as $row) {
			$notes[] = [
				'id' => $row->id,
				'cat' => $row->cat,
				'title' => $row->title,
				'note' => $row->note,
				'creation_date' => $row->creation_date,
				'last_modified' => $row->last_modified
			];
		}
		return [
			'notes' => $notes,
			'total' => $total
		];
	}

}

?>
