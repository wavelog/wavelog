<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// Notes controller: handles all note actions, with security and input validation
class Notes extends CI_Controller {
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

        // Support prefilled title/category from query string
        $prefill_title = $this->input->get('title', TRUE);
        $prefill_category = $this->input->get('category', TRUE);

        $suggested_title = null;
        // Validate form fields
        $this->form_validation->set_rules('title', 'Note Title', 'required|callback_contacts_title_unique'); // Custom callback for Contacts category
        $this->form_validation->set_rules('content', 'Content', 'required');
        if ($this->form_validation->run() == FALSE) {
            // Use POST if available, otherwise use prefill from query string
            $category = $this->input->post('category', TRUE);
            if (empty($category) && !empty($prefill_category)) {
                $category = $prefill_category;
            }
            if ($category === 'Contacts') {
                $title_input = $this->input->post('title', TRUE);
                if (empty($title_input) && !empty($prefill_title)) {
                    $title_input = $prefill_title;
                }
                $suggested_title = strtoupper($this->callbook->get_plaincall($title_input));
				$suggested_title = str_replace('0', 'Ø', $suggested_title);
            }
            // Pass prefill values to view
            $data['suggested_title'] = $suggested_title;
            $data['prefill_title'] = $prefill_title;
            $data['prefill_category'] = $prefill_category;
            $data['category'] = $category;
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
				$suggested_title = str_replace('0', 'Ø', $suggested_title);
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

        // Validate and sanitize pagination parameters
        $max_per_page = 100; // Prevent denial of service
        if ($per_page < 1 || $per_page > $max_per_page) $per_page = 15;
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
            $this->output->set_content_type('application/json')->set_output(json_encode(['status' => 'error', 'message' => __("Duplicate note title for this category and user - not allowed for Contacts category.")]));
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
        $exists = false;
        $note_id = null;
        $this->load->model('note');
        $note_id_found = $this->note->get_note_id_by_category($user_id, $category, $title);
        if ($note_id_found) {
            // If editing, ignore current note
            if ($id && $note_id_found == $id) {
                $exists = false;
            } else {
                $exists = true;
                $note_id = $note_id_found;
            }
        }
        $response = ['exists' => $exists];
        if ($exists && $note_id) {
            $response['id'] = $note_id;
        }
        $this->output->set_content_type('application/json')->set_output(json_encode($response));
    }

	// API endpoint to get note details by ID
    public function get($id = null) {
        $this->load->model('note');
        $clean_id = $this->security->xss_clean($id);
        if (!is_numeric($clean_id) || !$this->note->belongs_to_user($clean_id, $this->session->userdata('user_id'))) {
            $this->output->set_content_type('application/json')->set_output(json_encode(['error' => __("Not found or not allowed")]));
            return;
        }
        $query = $this->note->view($clean_id);
        if ($query && $query->num_rows() > 0) {
            $row = $query->row();
            $response = [
                'id' => $row->id,
                'category' => $row->cat,
                'user_id' => $row->user_id,
                'title' => $row->title,
                'content' => $row->note
            ];
            $this->output->set_content_type('application/json')->set_output(json_encode($response));
        } else {
            $this->output->set_content_type('application/json')->set_output(json_encode(['error' => __("Not found")]));
        }
    }

    // API endpoint to save note (create new or update existing or delete, based on presence of ID and content)
    public function save($id = null) {
        $this->load->model('note');
        $this->load->library('callbook');

        $user_id = $this->session->userdata('user_id');
        $category = $this->input->post('category', TRUE);
        $title = $this->input->post('title', TRUE);
        $content = $this->input->post('content', TRUE);

        // Validate required fields
        if (empty($category) || empty($title)) {
            $this->output->set_content_type('application/json')->set_output(json_encode(['success' => false, 'message' => __("Category and title are required")]));
            return;
        }

        // Clean title for Contacts category
        if ($category === 'Contacts') {
            $title = strtoupper($this->callbook->get_plaincall($title));
            $title = str_replace('0', 'Ø', $title);
        }

        if ($id !== null) {
            // Edit existing note
            $clean_id = $this->security->xss_clean($id);
            if (!is_numeric($clean_id) || !$this->note->belongs_to_user($clean_id, $user_id)) {
                $this->output->set_content_type('application/json')->set_output(json_encode(['success' => false, 'message' => __("Note not found or not allowed")]));
                return;
            }

            // If content is empty, delete the note
            if (empty(trim($content))) {
                $this->note->delete($clean_id);
                $this->output->set_content_type('application/json')->set_output(json_encode(['success' => true, 'message' => __("Note deleted"), 'deleted' => true]));
            } else {
                // Update the note
                $this->note->edit($clean_id, $category, $title, $content);
                $this->output->set_content_type('application/json')->set_output(json_encode(['success' => true, 'message' => __("Note updated"), 'id' => $clean_id]));
            }
        } else {
            // Create new note
            if (empty(trim($content))) {
                $this->output->set_content_type('application/json')->set_output(json_encode(['success' => false, 'message' => __("Cannot create empty note")]));
                return;
            }

            // Check for duplicate in Contacts category
            if ($category === 'Contacts') {
                $existing_id = $this->note->get_note_id_by_category($user_id, $category, $title);
                if ($existing_id) {
                    $this->output->set_content_type('application/json')->set_output(json_encode(['success' => false, 'message' => __("A note with this callsign already exists")]));
                    return;
                }
            }

            // Create the note
            $this->note->add($category, $title, $content);

            // Get the new note ID
            $new_id = $this->note->get_note_id_by_category($user_id, $category, $title);
            $this->output->set_content_type('application/json')->set_output(json_encode(['success' => true, 'message' => __("Note created"), 'id' => $new_id]));
        }
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
                $core = str_replace('0', 'Ø', $core);
                $this->form_validation->set_message('contacts_title_unique', sprintf(__("Contacts note title must be a callsign only, without prefix/suffix. Suggested: %s"), $core));
                return FALSE;
            }
			// Check for existing note with the same title
			$this->load->model('note');
            if ($this->note->get_note_id_by_category($user_id, 'Contacts', $core) > 0) {
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
				$core = str_replace('0', 'Ø', $core);
                $this->form_validation->set_message('contacts_title_unique_edit', sprintf(__("Contacts note title must be a callsign only, without prefix/suffix. Suggested: %s"),$core));
                return FALSE;
            }

			// Check for existing note with the same title
			$this->load->model('note');
			$existing_id = $this->note->get_note_id_by_category($user_id, 'Contacts', $core);
            if ($existing_id > 0 && $existing_id != $note_id) {
                $this->form_validation->set_message('contacts_title_unique_edit', __("A note with this callsign already exists in your Contacts. Please enter a unique callsign."));
                return FALSE;
			}
            return TRUE;
        }
        return TRUE;
    }



}
