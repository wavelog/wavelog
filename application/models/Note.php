<?php

class Note extends CI_Model {
	// List of possible note categories
	public static $possible_categories = [
		'Contacts', // QSO partner notes
		'General',  // General notes
		'Antennas', // Antenna-related notes
		'Satellites' // Satellite-related notes
	];
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
		$this->db->where('user_id', $user_id);
		return $this->db->get('notes');
	}

	// Add a new note for the logged-in user
	function add() {
		$cat = $this->security->xss_clean($this->input->post('category', TRUE));
		$title = $this->security->xss_clean($this->input->post('title', TRUE));
		$user_id = $this->session->userdata('user_id');
		// Block duplicate title for any category
		$check_title = $title;
		if ($cat === 'Contacts') {
			$check_title = strtoupper($title);
		}
		$existing = $this->db->get_where('notes', [
			'cat' => $cat,
			'user_id' => $user_id,
			'title' => $check_title
		])->num_rows();
		if ($existing > 0 && $cat === 'Contacts') {
			show_error('In Contacts category, the titles of the notes need to be unique.');
			return;
		}
		$local_time = $this->input->post('local_time', TRUE);
		$creation_date_utc = gmdate('Y-m-d H:i:s');
		if ($local_time) {
			// Convert browser local time to UTC
			$dt = new DateTime($local_time, new DateTimeZone(date_default_timezone_get()));
			$dt->setTimezone(new DateTimeZone('UTC'));
			$creation_date_utc = $dt->format('Y-m-d H:i:s');
		}
		$data = array(
			'cat' => $cat,
			'title' => $title,
			'note' => $this->security->xss_clean($this->input->post('content', TRUE)),
			'user_id' => $this->session->userdata('user_id'),
			'creation_date' => $creation_date_utc,
			'last_modified' => $creation_date_utc
		);
		$this->db->insert('notes', $data);
	}

	// Edit an existing note for the logged-in user
	function edit() {
		$cat = $this->security->xss_clean($this->input->post('category', TRUE));
		$title = $this->security->xss_clean($this->input->post('title', TRUE));
		$user_id = $this->session->userdata('user_id');
		$note_id = $this->security->xss_clean($this->input->post('id', TRUE));
		$check_title = $title;
		if ($cat === 'Contacts') {
			$check_title = strtoupper($title);
		}
		$existing = $this->db->get_where('notes', [
			'cat' => $cat,
			'user_id' => $user_id,
			'title' => $check_title
		])->result();
		foreach ($existing as $note) {
			if ($note->id != $note_id && $cat === 'Contacts') {
				show_error('In Contacts category, the titles of the notes need to be unique.');
				return;
			}
		}
		$local_time = $this->input->post('local_time', TRUE);
		$last_modified_utc = gmdate('Y-m-d H:i:s');
		if ($local_time) {
			// Convert browser local time to UTC
			$dt = new DateTime($local_time, new DateTimeZone(date_default_timezone_get()));
			$dt->setTimezone(new DateTimeZone('UTC'));
			$last_modified_utc = $dt->format('Y-m-d H:i:s');
		}
		$data = array(
			'cat' => $cat,
			'title' => $title,
			'note' => $this->security->xss_clean($this->input->post('content', TRUE)),
			'last_modified' => $last_modified_utc
		);
		$this->db->where('id', $this->security->xss_clean($this->input->post('id', TRUE)));
		$this->db->where('user_id', $this->session->userdata('user_id'));
		$this->db->update('notes', $data);
	}

	// Delete a note by ID for the logged-in user
	function delete($id) {
		$clean_id = $this->security->xss_clean($id);
		if (!is_numeric($clean_id)) {
			show_404();
		}
		$this->db->delete('notes', array('id' => $clean_id, 'user_id' => $this->session->userdata('user_id')));
	}

	// View a note by ID for the logged-in user
	function view($id) {
		$clean_id = $this->security->xss_clean($id);
		if (!is_numeric($clean_id)) {
			show_404();
		}
		$this->db->where('id', $clean_id);
		$this->db->where('user_id', $this->session->userdata('user_id'));
		return $this->db->get('notes');
	}

	// Check if note belongs to a user
	public function belongs_to_user($note_id, $user_id) {
		$this->db->where('id', $note_id);
		$this->db->where('user_id', $user_id);
		$query = $this->db->get('notes');
		return $query->num_rows() > 0;
	}

	// Search notes by category and/or text for the logged-in user
	public function search($criteria = []) {
		$user_id = $this->session->userdata('user_id');
		$this->db->where('user_id', $user_id);
		// Filter by category
		if (!empty($criteria['cat'])) {
			$cats = array_map('trim', explode(',', $criteria['cat']));
			if (count($cats) > 0) {
				$this->db->where_in('cat', $cats);
			}
		}
		// Filter by search term (title or note)
		if (!empty($criteria['search'])) {
			$search = $criteria['search'];
			$this->db->group_start();
			$this->db->like('title', $search);
			$this->db->or_like('note', $search);
			$this->db->group_end();
		}
		$query = $this->db->get('notes');
		return $query->result();
	}

	// Count notes by category for the logged-in user
	public function count_by_category($cat = null) {
		$user_id = $this->session->userdata('user_id');
		$this->db->where('user_id', $user_id);
		if ($cat !== null) {
			$this->db->where('cat', $cat);
		}
		return $this->db->count_all_results('notes');
	}

	// Get categories with their respective note counts for the logged-in user
	public function get_categories_with_counts() {
		$user_id = $this->session->userdata('user_id');
		$this->db->select('cat, COUNT(*) as count');
		$this->db->where('user_id', $user_id);
		$this->db->group_by('cat');
		$query = $this->db->get('notes');
		$result = [];
		foreach ($query->result() as $row) {
			$result[$row->cat] = (int)$row->count;
		}
		return $result;
	}

	// Count all notes with user_id NULL (system notes)
	function CountAllNotes() {
		$this->db->where('user_id =', NULL);
		$query = $this->db->get('notes');
		return $query->num_rows();
	}

	// Search notes with pagination and sorting for the logged-in user
	public function search_paginated($criteria = [], $page = 1, $per_page = 25, $sort_col = null, $sort_dir = null) {
		$user_id = $this->session->userdata('user_id');
		$this->db->where('user_id', $user_id);
		// Filter by category
		if (!empty($criteria['cat'])) {
			$cats = array_map('trim', explode(',', $criteria['cat']));
			if (count($cats) > 0) {
				$this->db->where_in('cat', $cats);
			}
		}
		// Filter by search term (title or note)
		if (!empty($criteria['search'])) {
			$search = $criteria['search'];
			$this->db->group_start();
			$this->db->like('title', $search);
			$this->db->or_like('note', $search);
			$this->db->group_end();
		}
		// Get total count
		$total_query = clone $this->db;
		$total = $total_query->count_all_results('notes', FALSE);
		// Sorting
		$columns = ['cat', 'title', 'last_modified'];
		if ($sort_col !== null && in_array($sort_col, $columns) && ($sort_dir === 'asc' || $sort_dir === 'desc')) {
			$this->db->order_by($sort_col, $sort_dir);
		}
		// Pagination
		$offset = ($page - 1) * $per_page;
		$this->db->limit($per_page, $offset);
		$query = $this->db->get('notes');
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
