<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Session-based management of API v2 tokens (table `api_token`).
 *
 * Handles the create/revoke form posts from the API page (views/api/index.php).
 * Kept separate from both the legacy v1 controller (Api.php) and the REST
 * dispatcher (Api_v2.php): the former stays pure v1, the latter is sessionless.
 */
class Api_token extends CI_Controller {

	public function __construct() {
		parent::__construct();
		$this->load->model('user_model');
		$this->load->model('api_v2_model');
	}

	/**
	 * Create a new API v2 token (name + scopes + optional expiry).
	 * The plaintext token is passed to the next request via flashdata and
	 * shown exactly once; only its hash is stored.
	 */
	public function generate() {
		// CSRF mitigation: reject non-POST requests
		if ($this->input->method() !== 'post') {
			$this->session->set_flashdata('error', __("Invalid request method"));
			redirect('api');
			return;
		}

		if(!$this->user_model->authorize(3)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		$name = trim($this->input->post('token_name', TRUE) ?? '');
		if ($name === '' || mb_strlen($name) > 100) {
			$this->session->set_flashdata('error', __("Please provide a token name"));
			redirect('api');
			return;
		}

		$scopes = $this->input->post('scopes', TRUE);
		$valid_scopes = array_keys(Api_v2_model::scope_registry());
		$scopes = is_array($scopes) ? array_intersect($scopes, $valid_scopes) : [];
		if (empty($scopes)) {
			$this->session->set_flashdata('error', __("Please select at least one scope"));
			redirect('api');
			return;
		}

		// Optional expiry: a fixed set of choices, anything else means never.
		$expiry = $this->input->post('expiry', TRUE);
		$expires_at = null;
		if (in_array($expiry, ['30', '90', '365'], true)) {
			$expires_at = date('Y-m-d H:i:s', strtotime('+' . $expiry . ' days'));
		}

		if ($this->session->userdata('clubstation') == 1 && $this->session->userdata('impersonate') == 1) {
			$creator = $this->session->userdata('source_uid');
		} else {
			$creator = $this->session->userdata('user_id');
		}

		$token = $this->api_v2_model->create_token($name, $scopes, $expires_at, null, $creator);

		if ($token !== false) {
			$this->session->set_flashdata('new_api_token', $token);
			$this->session->set_flashdata('success', __("API Token generated"));
		} else {
			$this->session->set_flashdata('error', __("API Token could not be generated"));
		}
		redirect('api');
	}

	/**
	 * Delete (revoke) an API v2 token.
	 */
	public function delete() {
		// CSRF mitigation: reject non-POST requests
		if ($this->input->method() !== 'post') {
			$this->session->set_flashdata('error', __("Invalid request method"));
			redirect('api');
			return;
		}

		if(!$this->user_model->authorize(3)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		$id = $this->input->post('id', TRUE);
		if (empty($id) || !is_numeric($id)) {
			$this->session->set_flashdata('error', __("Invalid API Token"));
			redirect('api');
			return;
		}

		$this->api_v2_model->revoke_token($id);

		$this->session->set_flashdata('notice', __("API Token has been deleted"));

		redirect('api');
	}
}
