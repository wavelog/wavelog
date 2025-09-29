<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// Notes controller: handles all note actions, with security and input validation
class Notes extends CI_Controller {
    // AJAX endpoint: check for duplicate note title in category for user
    // Ensure only authorized users can access Notes controller
    function __construct() {
        parent::__construct();
        $this->load->model('user_model');
        if (!$this->user_model->authorize(2)) {
            $this->session->set_flashdata('error', __("You're not allowed to do that!"));
            redirect('dashboard');
            exit;
        }
    }

	// Main notes page: lists notes and categories
    public function index() {
        $this->load->model('note');
        $data = [];
        // Get all notes for logged-in user
        $data['notes'] = $this->note->list_all();
        // Get possible categories
        $data['categories'] = Note::$possible_categories;
        // Get note counts per category
        $category_counts = [];
        foreach ($data['categories'] as $cat) {
            $category_counts[$cat] = $this->note->count_by_category($cat);
        }
        $data['category_counts'] = $category_counts;
        // Get total notes count
        $data['all_notes_count'] = $this->note->count_by_category();
        $data['page_title'] = __("Notes");
        // Render views
        $this->load->view('interface_assets/header', $data);
        $this->load->view('notes/main', $data);
        $this->load->view('interface_assets/footer');
    }

	// Add a new note
    function add() {
        $this->load->model('note');
        $this->load->library('form_validation');
        $suggested_title = null;
        // Validate form fields
        $this->form_validation->set_rules('title', 'Note Title', 'required|callback_contacts_title_unique');
        $this->form_validation->set_rules('content', 'Content', 'required');
        if ($this->form_validation->run() == FALSE) {
            // If validation failed and Contacts, get suggested title
            $cat = $this->input->post('category', TRUE);
            if ($cat === 'Contacts') {
                $this->load->helper('callsign');
                $parsed = parse_callsign($this->input->post('title', TRUE));
                $suggested_title = strtoupper($parsed['core']);
            }
            $data['suggested_title'] = $suggested_title;
            $data['page_title'] = __("Add Notes");
            $this->load->view('interface_assets/header', $data);
            $this->load->view('notes/add', $data);
            $this->load->view('interface_assets/footer');
        } else {
            // Ensure title is uppercase for Contacts before saving
            $cat = $this->input->post('category', TRUE);
            $title = $this->input->post('title', TRUE);
            if ($cat === 'Contacts') {
                $_POST['title'] = strtoupper($title);
            }
            $this->note->add();
            redirect('notes');
        }
    }

    // View a single note
    function view($id) {
        $this->load->model('note'); // Ensure note model is loaded
        $clean_id = $this->security->xss_clean($id);
        // Validate note ID and ownership
        if (!is_numeric($clean_id) || !$this->note->belongs_to_user($clean_id, $this->session->userdata('user_id'))) {
            show_404();
        }
        $data['note'] = $this->note->view($clean_id);
        $data['page_title'] = __("Note");
        // Render note view
        $this->load->view('interface_assets/header', $data);
        $this->load->view('notes/view');
        $this->load->view('interface_assets/footer');
    }

    // Edit a note
    function edit($id) {
        $this->load->model('note'); // Ensure note model is loaded
        $clean_id = $this->security->xss_clean($id);
        // Validate note ID and ownership
        if (!is_numeric($clean_id) || !$this->note->belongs_to_user($clean_id, $this->session->userdata('user_id'))) {
            show_404();
        }
        $data['id'] = $clean_id;
        $data['note'] = $this->note->view($clean_id);
        $this->load->library('form_validation');
        $suggested_title = null;
        // Validate form fields
        $this->form_validation->set_rules('title', 'Note Title', 'required|callback_contacts_title_unique_edit');
        $this->form_validation->set_rules('content', 'Content', 'required');
        if ($this->form_validation->run() == FALSE) {
            $cat = $this->input->post('category', TRUE);
            if ($cat === 'Contacts') {
                $this->load->helper('callsign');
                $parsed = parse_callsign($this->input->post('title', TRUE));
                $suggested_title = strtoupper($parsed['core']);
            }
            $data['suggested_title'] = $suggested_title;
            $data['page_title'] = __("Edit Note");
            $this->load->view('interface_assets/header', $data);
            $this->load->view('notes/edit', $data);
            $this->load->view('interface_assets/footer');
        } else {
            // Ensure title is uppercase for Contacts before saving
            $cat = $this->input->post('category', TRUE);
            $title = $this->input->post('title', TRUE);
            if ($cat === 'Contacts') {
                $_POST['title'] = strtoupper($title);
            }
            $this->note->edit();
            redirect('notes');
        }
    }

    // API search for notes
    function search() {
        $this->load->model('note');
        // Map and sanitize search parameters
        $searchCriteria = $this->mapParameters();
        // Get pagination and sorting parameters
        $page = (int)$this->input->post('page', TRUE);
        $per_page = (int)$this->input->post('per_page', TRUE);
        $sort_col = $this->input->post('sort_col', TRUE);
        $sort_dir = $this->input->post('sort_dir', TRUE);
        if ($per_page < 1) $per_page = 15;
        if ($page < 1) $page = 1;
        // Get paginated, sorted notes
        $result = $this->note->search_paginated($searchCriteria, $page, $per_page, $sort_col, $sort_dir);
        $response = [
            'notes' => $result['notes'],
            'total' => $result['total']
        ];
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    // Map and sanitize search parameters
    function mapParameters() {
        return array(
            'cat' => $this->security->xss_clean($this->input->post('cat', TRUE)),
            'search' => $this->security->xss_clean($this->input->post('search', TRUE))
        );
    }

    // Delete a note
    function delete($id) {
		$this->load->model('note');

        $clean_id = $this->security->xss_clean($id);
        // Validate note ID and ownership
        if (!is_numeric($clean_id) || !$this->note->belongs_to_user($clean_id, $this->session->userdata('user_id'))) {
            show_404();
        }
        $this->note->delete($clean_id);
        redirect('notes');
    }

    // Duplicate a note by ID for the logged-in user, appending #[timestamp] to title
    public function duplicate($id) {
        $this->load->model('note');
        $clean_id = $this->security->xss_clean($id);
        if (!is_numeric($clean_id)) {
            show_404();
        }
        $timestamp = $this->input->post('timestamp', TRUE);
        $user_id = $this->session->userdata('user_id');
        // Get original note
        $query = $this->db->get_where('notes', array('id' => $clean_id, 'user_id' => $user_id));
        if ($query->num_rows() !== 1) {
            show_404();
        }
        $note = $query->row();
        // Simple duplicate check
        $cat = $note->cat;
        $new_title = $note->title . ' #' . $timestamp;
        $check_title = $new_title;
        if ($cat === 'Contacts') {
            $check_title = strtoupper($new_title);
        }
        $existing = $this->db->get_where('notes', [
            'cat' => $cat,
            'user_id' => $user_id,
            'title' => $check_title
        ])->num_rows();
        if ($existing > 0) {
            $this->output->set_content_type('application/json')->set_output(json_encode(['status' => 'error', 'message' => 'Duplicate note title for this category and user.']));
            return;
        }
        // Duplicate note with new title
        $data = array(
            'cat' => $note->cat,
            'title' => $new_title,
            'note' => $note->note,
            'user_id' => $user_id,
            'creation_date' => date('Y-m-d H:i:s'),
            'last_modified' => date('Y-m-d H:i:s')
        );
        $this->db->insert('notes', $data);
        $this->output->set_content_type('application/json')->set_output(json_encode(['status' => 'ok']));
    }

	// API endpoint to get note counts per category and total
    public function get_category_counts() {
        $this->load->model('note');
        $categories = Note::$possible_categories;
        $category_counts = [];
        foreach ($categories as $cat) {
            $category_counts[$cat] = $this->note->count_by_category($cat);
        }
        $all_notes_count = $this->note->count_by_category();
        $response = [
            'category_counts' => $category_counts,
            'all_notes_count' => $all_notes_count
        ];
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

	// API endpoint to check for duplicate note title in category for user
	public function check_duplicate() {
        $user_id = $this->session->userdata('user_id');
        $cat = $this->security->xss_clean($this->input->get('cat', TRUE));
        $title = $this->security->xss_clean($this->input->get('title', TRUE));
        $id = $this->security->xss_clean($this->input->get('id', TRUE)); // Optional, for edit
        $check_title = $title;
        if ($cat === 'Contacts') {
            $check_title = strtoupper($title);
        }
        $where = [
            'cat' => $cat,
            'user_id' => $user_id,
            'title' => $check_title
        ];
        $query = $this->db->get_where('notes', $where);
        $duplicate = false;
        if ($id) {
            foreach ($query->result() as $note) {
                if ($note->id != $id) {
                    $duplicate = true;
                    break;
                }
            }
        } else {
            $duplicate = $query->num_rows() > 0;
        }
        $this->output->set_content_type('application/json')->set_output(json_encode(['duplicate' => $duplicate]));
    }

    // Form validation callback for add: unique Contacts note title for user, only core callsign
    public function contacts_title_unique($title) {
        $cat = $this->input->post('category', TRUE);
        if ($cat === 'Contacts') {
            $user_id = $this->session->userdata('user_id');
            $this->load->helper('callsign');
            $parsed = parse_callsign($title);
            $core = strtoupper($parsed['core']);
            // Only fail if prefix or suffix is present
            if ($parsed['prefix'] || $parsed['suffix']) {
                $this->form_validation->set_message('contacts_title_unique', 'Contacts note title must be a callsign only, without prefix/suffix. Suggested: ' . $core);
                $_POST['title'] = $core;
                return FALSE;
            }
            // Accept and normalize casing
            $_POST['title'] = $core;
            $existing = $this->db->get_where('notes', [
                'cat' => 'Contacts',
                'user_id' => $user_id,
                'title' => $core
            ])->num_rows();
            if ($existing > 0) {
                $this->form_validation->set_message('contacts_title_unique', 'A note with this callsign already exists in your Contacts. Please enter a unique callsign.');
                return FALSE;
            }
            return TRUE;
        }
        return TRUE;
    }
    // Form validation callback for edit: unique Contacts note title for user (ignore current note), only core callsign
    public function contacts_title_unique_edit($title) {
        $cat = $this->input->post('category', TRUE);
        if ($cat === 'Contacts') {
            $user_id = $this->session->userdata('user_id');
            $note_id = $this->input->post('id', TRUE);
            $this->load->helper('callsign');
            $parsed = parse_callsign($title);
            $core = strtoupper($parsed['core']);
            if ($parsed['prefix'] || $parsed['suffix'] || $title !== $core) {
                $this->form_validation->set_message('contacts_title_unique_edit', 'Contacts note title must be a callsign only, without prefix/suffix. Suggested: ' . $core);
                $_POST['title'] = $core;
                return FALSE;
            }
            $query = $this->db->get_where('notes', [
                'cat' => 'Contacts',
                'user_id' => $user_id,
                'title' => $core
            ]);
            foreach ($query->result() as $note) {
                if ($note->id != $note_id) {
                    $this->form_validation->set_message('contacts_title_unique_edit', 'A note with this callsign already exists in your Contacts. Please enter a unique callsign.');
                    return FALSE;
                }
            }
            $_POST['title'] = $core;
            return TRUE;
        }
        return TRUE;
    }

}
