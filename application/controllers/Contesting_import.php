<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Contesting_import extends CI_Controller {

	function __construct() {
		parent::__construct();

		$this->load->model('user_model');
		if (!$this->user_model->authorize(2)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}
		if ($this->config->item('contest_legacy_import') === false) {
			$this->session->set_flashdata('error', __("This feature is disabled."));
			redirect('contesting');
		}
	}

	/**
	 * Shows a preview of historical contest QSO groups that can be imported
	 * into the contest management system. Only groups not yet linked to any
	 * contest session are shown.
	 */
	public function index() {
		if (!clubaccess_check(9)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('contesting');
		}

		$this->load->model('contesting_import_model');
		$this->load->library('form_validation');

		$groups = $this->contesting_import_model->get_legacy_contest_groups();

		if (empty($groups)) {
			$this->session->set_flashdata('error', __("No historical contests found that could be imported."));
			redirect('contesting');
		}

		$data['page_title']  = __("Import Historical Contests");
		$data['groups']      = $groups;
		$data['all_users']   = false;
		$data['form_action'] = site_url('contesting_import/do_import');

		$this->load->view('interface_assets/header', $data);
		$this->load->view('contesting/manager/import_legacy', $data);
		$this->load->view('interface_assets/footer');
	}

	/**
	 * Processes the legacy contest import for the current user.
	 * POST: groups[] = "adif_name|station_id|year"
	 */
	public function do_import() {
		if (!clubaccess_check(9)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('contesting');
		}

		$this->load->model('contesting_import_model');

		$selected = $this->input->post('groups') ?: [];
		$sessions = 0;
		$qsos     = 0;

		foreach ($selected as $key) {
			$parts = explode('|', $key);
			if (count($parts) !== 3) continue;

			[$adif_name, $station_id, $year] = $parts;
			$linked = $this->contesting_import_model->import_legacy_contest_group(
				$adif_name,
				(int)$station_id,
				(int)$year
			);
			if ($linked > 0) {
				$sessions++;
				$qsos += $linked;
			}
		}

		$this->session->set_flashdata('message', sprintf(
			__("%d session(s) created, %d QSO(s) linked."),
			$sessions, $qsos
		));
		redirect('contesting');
	}

	/**
	 * Admin-only: shows a preview of all legacy contest groups across all users.
	 */
	public function all() {
		if (!$this->user_model->authorize(99)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('contesting');
		}

		$this->load->model('contesting_import_model');
		$this->load->library('form_validation');

		$groups = $this->contesting_import_model->get_all_legacy_contest_groups();

		if (empty($groups)) {
			$this->session->set_flashdata('error', __("No historical contests found that could be imported."));
			redirect('contesting');
		}

		$data['page_title']  = __("Import Historical Contests (All Users)");
		$data['groups']      = $groups;
		$data['all_users']   = true;
		$data['form_action'] = site_url('contesting_import/do_import_all');

		$this->load->view('interface_assets/header', $data);
		$this->load->view('contesting/manager/import_legacy', $data);
		$this->load->view('interface_assets/footer');
	}

	/**
	 * Admin-only: processes the legacy contest import for all users.
	 * POST: groups[] = "adif_name|station_id|year|user_id"
	 */
	public function do_import_all() {
		if (!$this->user_model->authorize(99)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('contesting');
		}

		$this->load->model('contesting_import_model');

		$selected = $this->input->post('groups') ?: [];
		$sessions = 0;
		$qsos     = 0;

		foreach ($selected as $key) {
			$parts = explode('|', $key);
			if (count($parts) !== 4) continue;

			[$adif_name, $station_id, $year, $user_id] = $parts;
			$linked = $this->contesting_import_model->import_legacy_contest_group_as_user(
				$adif_name,
				(int)$station_id,
				(int)$year,
				(int)$user_id
			);
			if ($linked > 0) {
				$sessions++;
				$qsos += $linked;
			}
		}

		$this->session->set_flashdata('message', sprintf(
			__("%d session(s) created, %d QSO(s) linked."),
			$sessions, $qsos
		));
		redirect('contesting');
	}
}
