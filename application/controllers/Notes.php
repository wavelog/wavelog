<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// Notes controller: handles all note actions, with security and input validation
class Notes extends CI_Controller {
    // API endpoint: check for duplicate note title in category for user
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
        $data['categories'] = Note::get_possible_categories();
        // Get note counts per category
        $category_counts = [];
        foreach (Note::get_possible_category_keys() as $category) {
            $category_counts[$category] = $this->note->count_by_category($category);
        }
        $data['category_counts'] = $category_counts;
        // Get total notes count
        $data['all_notes_count'] = $this->note->count_by_category();
        $data['page_title'] = __("Notes");
        // Render views
        $this->load->view('interface_assets/header', $data);
        $this->load->view('notes/main');
        $this->load->view('interface_assets/footer');
    }

	// Add a new note
    function add() {
        $this->load->model('note');
        $this->load->library('form_validation');
		$this->load->library('callbook'); // Used for callsign parsing

        $suggested_title = null;
        // Validate form fields
        $this->form_validation->set_rules('title', 'Note Title', 'required|callback_contacts_title_unique'); // Custom callback for Contacts category
        $this->form_validation->set_rules('content', 'Content', 'required');
        if ($this->form_validation->run() == FALSE) {
            $category = $this->input->post('category', TRUE);
            if ($category === 'Contacts') {

                $suggested_title = strtoupper($this->callbook->get_plaincall($this->input->post('title', TRUE)));
            }
            $data['suggested_title'] = $suggested_title;
            $data['page_title'] = __("Add Notes");
            $this->load->view('interface_assets/header', $data);
            $this->load->view('notes/add');
            $this->load->view('interface_assets/footer');
        } else {
            $category = $this->input->post('category', TRUE);
            $title = $this->input->post('title', TRUE);
            $content = $this->input->post('content', TRUE);
            $local_time = $this->input->post('local_time', TRUE);
            if ($category === 'Contacts') {
                $title = strtoupper($this->callbook->get_plaincall($title));
            }
            $this->note->add($category, $title, $content, $local_time);
            redirect('notes');
        }
    }

    // View a single note
    function view($id = null) {
        $this->load->model('note');
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
    function edit($id = null) {
        $this->load->model('note');
        $clean_id = $this->security->xss_clean($id);
        // Validate note ID and ownership
        if (!is_numeric($clean_id) || !$this->note->belongs_to_user($clean_id, $this->session->userdata('user_id'))) {
            show_404();
        }
		$this->load->library('callbook'); // Used for callsign parsing
        $data['id'] = $clean_id;
        $data['note'] = $this->note->view($clean_id);
        $this->load->library('form_validation');
        $suggested_title = null;
        // Validate form fields
        $this->form_validation->set_rules('title', 'Note Title', 'required|callback_contacts_title_unique_edit'); // Custom callback for Contacts category
        $this->form_validation->set_rules('content', 'Content', 'required');
        if ($this->form_validation->run() == FALSE) {
            $category = $this->input->post('category', TRUE);
            if ($category === 'Contacts') {
                $suggested_title = strtoupper($this->callbook->get_plaincall($this->input->post('title', TRUE)));
            }
            $data['suggested_title'] = $suggested_title;
            $data['page_title'] = __("Edit Note");
            $this->load->view('interface_assets/header', $data);
            $this->load->view('notes/edit');
            $this->load->view('interface_assets/footer');
        } else {
            $category = $this->input->post('category', TRUE);
            $title = $this->input->post('title', TRUE);
            $content = $this->input->post('content', TRUE);
            $local_time = $this->input->post('local_time', TRUE);
            $note_id = $this->input->post('id', TRUE);
            if ($category === 'Contacts') {
                $title = strtoupper($this->callbook->get_plaincall($title));
            }
            $this->note->edit($note_id, $category, $title, $content, $local_time);
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
            'cat' => $this->input->post('cat', TRUE),
            'search' => $this->input->post('search', TRUE)
        );
    }

    // Delete a note
    function delete($id = null) {
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
    public function duplicate($id = null) {
        $this->load->model('note');
        $clean_id = $this->security->xss_clean($id);
        if (!is_numeric($clean_id) || !$this->note->belongs_to_user($clean_id, $this->session->userdata('user_id'))) {
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
        $category = $note->cat;
        $new_title = $note->title . ' #' . $timestamp;
        $check_title = $new_title;
        if ($category === 'Contacts') {
            $check_title = strtoupper($new_title);
        }
        $existing = $this->db->get_where('notes', [
            'cat' => $category,
            'user_id' => $user_id,
            'title' => $check_title
        ])->num_rows();
        if ($existing > 0) {
            $this->output->set_content_type('application/json')->set_output(json_encode(['status' => 'error', 'message' => 'Duplicate note title for this category and user - not allowed for Contacts category.']));
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
        $categories = Note::get_possible_category_keys();
        $category_counts = [];
        foreach ($categories as $category) {
            $category_counts[$category] = $this->note->count_by_category($category);
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
        $category = $this->input->get('category', TRUE);
        $title = $this->input->get('title', TRUE);
        $id = $this->input->get('id', TRUE); // Optional, for edit
        $check_title = $title;
        if ($category === 'Contacts') {
            $check_title = strtoupper($title);
        }
        $where = [
            'category' => $category,
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
    public function contacts_title_unique($title = null) {
        $category = $this->input->post('category', TRUE);
        if ($category === 'Contacts') {
			$this->load->library('callbook'); // Used for callsign parsing
            $user_id = $this->session->userdata('user_id');
            $core = strtoupper($this->callbook->get_plaincall($title));
            // Only fail if prefix or suffix is present
            if (strtoupper($title) <> $core) {
                $this->form_validation->set_message('contacts_title_unique', sprintf(__("Contacts note title must be a callsign only, without prefix/suffix. Suggested: %s"), $core));
                return FALSE;
            }
            $existing = $this->db->get_where('notes', [
                'cat' => 'Contacts',
                'user_id' => $user_id,
                'title' => $core
            ])->num_rows();
            if ($existing > 0) {
                $this->form_validation->set_message('contacts_title_unique', __("A note with this callsign already exists in your Contacts. Please enter a unique callsign."));
                return FALSE;
            }
            return TRUE;
        }
        return TRUE;
    }
    // Form validation callback for edit: unique Contacts note title for user (ignore current note), only core callsign
    public function contacts_title_unique_edit($title = null) {
        $category = $this->input->post('category', TRUE);
        if ($category === 'Contacts') {
			$this->load->library('callbook'); // Used for callsign parsing
            $user_id = $this->session->userdata('user_id');
            $note_id = $this->input->post('id', TRUE);
            $core = strtoupper($this->callbook->get_plaincall($title));
			// Only fail if prefix or suffix is present
            if (strtoupper($title) <> $core) {
                $this->form_validation->set_message('contacts_title_unique_edit', sprintf(__("Contacts note title must be a callsign only, without prefix/suffix. Suggested: %s"),$core));
                return FALSE;
            }
            $query = $this->db->get_where('notes', [
                'cat' => 'Contacts',
                'user_id' => $user_id,
                'title' => $core
            ]);
            foreach ($query->result() as $note) {
                if ($note->id != $note_id) {
                    $this->form_validation->set_message('contacts_title_unique_edit', __("A note with this callsign already exists in your Contacts. Please enter a unique callsign."));
                    return FALSE;
                }
            }
            return TRUE;
        }
        return TRUE;
    }

}
